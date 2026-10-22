<?php
namespace App\Actions\HumanResources;
use App\Models\{StaffProfile, StaffReference, StaffAssignment, User};
use App\Services\{CollaborationLock, StaffAccess};
use Illuminate\Support\{Arr, Str};
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
class ManageStaff
{
    public function __construct(private StaffAccess $access) {}
    public function save(User $actor, array $data, ?StaffProfile $target = null): StaffProfile
    {
        return CollaborationLock::run($actor, 'hr.manage', function (User $actor) use ($data, $target) {
            abort_unless($this->access->canManage($actor), 403);
            $fields = []; foreach (StaffProfile::FIELDS as $field) { $fields[$field] = $data[$field] ?? null; } $fields['email'] = Str::lower(trim($fields['email']));
            $ids = array_values(array_unique(array_map('intval', $data['specialty_ids']))); sort($ids);
            $hash = hash('sha256', json_encode([$data['kind'], $fields, $ids, $data['grade_id'] ?? null, $data['grade_since'] ?? null, $data['function_id'] ?? null, $data['function_since'] ?? null], JSON_THROW_ON_ERROR));
            $profile = $target ? StaffProfile::whereKey($target->id)->lockForUpdate()->firstOrFail() : StaffProfile::where('operation_key', $data['operation_key'])->first();
            if ($profile && ! $target) {
                if (hash_equals($profile->request_hash, $hash)) { return $profile; }
                throw ValidationException::withMessages(['operation_key' => 'Cette référence de création a déjà été utilisée.']);
            }
            if ($profile) { StaffAccess::revision($profile, (int) $data['revision']); $this->editable($profile); }
            if ($profile && $profile->kind !== $data['kind']) { throw ValidationException::withMessages(['kind' => 'Le type de fiche ne peut pas être modifié.']); }
            if ($data['kind'] === 'administrative' && ($ids || ($data['grade_id'] ?? null) || ($data['function_id'] ?? null))) { throw ValidationException::withMessages(['specialty_ids' => 'Les attributions académiques concernent les enseignants.']); }
            if (StaffProfile::where('email', $fields['email'])->when($profile, fn ($q) => $q->where('id', '!=', $profile->id))->exists()) { throw ValidationException::withMessages(['email' => 'Cette adresse appartient déjà à une fiche, éventuellement archivée.']); }
            $previous = $profile?->specialties()->pluck('staff_references.id')->all() ?? [];
            $references = StaffReference::where('kind', 'specialty')->whereIn('id', $ids)->get();
            if ($references->count() !== count($ids) || $references->contains(fn ($r) => ! $r->is_active && ! in_array($r->id, $previous, true))) { throw ValidationException::withMessages(['specialty_ids' => 'Choisissez des spécialités actives ; les anciennes attributions peuvent être conservées.']); }
            if ($profile && ($fields['hired_on'] ?? null) && $profile->assignments()->where('assigned_on', '<', $fields['hired_on'])->exists()) { throw ValidationException::withMessages(['hired_on' => 'La date d’entrée ne peut pas être postérieure à une attribution déjà enregistrée.']); }
            if ($profile && ($fields['date_of_birth'] ?? null) && $profile->assignments()->where('assigned_on', '<', $fields['date_of_birth'])->exists()) { throw ValidationException::withMessages(['date_of_birth' => 'La date de naissance est incompatible avec les attributions enregistrées.']); }
            $profile ??= new StaffProfile;
            $profile->forceFill($fields); $changed = array_keys($profile->getDirty());
            if (! $target) { $profile->forceFill(['kind' => $data['kind'], 'reference' => ($data['kind'] === 'teacher' ? 'ENS-' : 'ADM-').Str::ulid(), 'operation_key' => $data['operation_key'], 'request_hash' => $hash]); }
            else { $profile->revision++; }
            $profile->save(); $profile->specialties()->sync($ids);
            if (! $target) {
                foreach (['grade', 'function'] as $kind) { if ($data[$kind.'_id'] ?? null) { $this->assign($actor, $profile, $kind, (int) $data[$kind.'_id'], $data[$kind.'_since']); } }
            }
            StaffAccess::audit($actor, $target ? 'staff.updated' : 'staff.created', $profile->id, metadata: ['fields_changed' => $changed, 'previous_specialty_ids' => $previous, 'specialty_ids' => $ids]);
            return $profile;
        });
    }
    public function archive(User $actor, StaffProfile $target, array $data): void
    {
        CollaborationLock::run($actor, 'hr.manage', function (User $actor) use ($target, $data) {
            abort_unless($this->access->canManage($actor), 403); $p = StaffProfile::whereKey($target->id)->lockForUpdate()->firstOrFail(); StaffAccess::revision($p, (int) $data['revision']);
            if ($p->is_archived === (bool) $data['is_archived']) { return; }
            $p->forceFill(['is_archived' => (bool) $data['is_archived'], 'revision' => $p->revision + 1])->save();
            StaffAccess::audit($actor, $p->is_archived ? 'staff.archived' : 'staff.restored', $p->id, metadata: ['reason' => $data['reason']]);
        });
    }
    public function link(User $actor, StaffProfile $target, array $data): void
    {
        CollaborationLock::run($actor, 'hr.manage', function (User $actor) use ($target, $data) {
            abort_unless($this->access->canManage($actor), 403); Gate::forUser($actor)->authorize('users.manage');
            $p = StaffProfile::whereKey($target->id)->lockForUpdate()->firstOrFail(); StaffAccess::revision($p, (int) $data['revision']); $user = null;
            if ($data['account_email'] !== null) {
                $this->editable($p); $user = User::where('email', Str::lower(trim($data['account_email'])))->first();
                if (! $user || ! $user->is_active || ! $this->access->compatible($user, $p->kind) || StaffProfile::where('user_id', $user->id)->where('id', '!=', $p->id)->exists()) { throw ValidationException::withMessages(['account_email' => 'Choisissez un compte actif du type correspondant, non rattaché à une autre fiche RH.']); }
            }
            $before = $p->user_id; $p->forceFill(['user_id' => $user?->id, 'revision' => $p->revision + 1])->save();
            StaffAccess::audit($actor, 'staff.account_updated', $p->id, metadata: ['previous_user_id' => $before, 'user_id' => $user?->id]);
        });
    }
    public function assignment(User $actor, StaffProfile $target, array $data): void
    {
        CollaborationLock::run($actor, 'hr.manage', function (User $actor) use ($target, $data) {
            abort_unless($this->access->canManage($actor), 403); $p = StaffProfile::whereKey($target->id)->lockForUpdate()->firstOrFail(); StaffAccess::revision($p, (int) $data['revision']); $this->editable($p);
            $this->assign($actor, $p, $data['kind'], isset($data['reference_id']) ? (int) $data['reference_id'] : null, $data['effective_on']); $p->revision++; $p->save();
        });
    }
    private function assign(User $actor, StaffProfile $p, string $kind, ?int $id, string $date): void
    {
        if ($p->kind !== 'teacher' || ! in_array($kind, ['grade', 'function'], true)) { throw ValidationException::withMessages(['kind' => 'Cette attribution est réservée aux enseignants.']); }
        if ($date > today()->format('Y-m-d') || ($p->hired_on && $date < $p->hired_on->format('Y-m-d')) || ($p->date_of_birth && $date < $p->date_of_birth->format('Y-m-d'))) { throw ValidationException::withMessages(['effective_on' => 'La date doit se situer entre l’entrée en fonction (ou la naissance) et aujourd’hui.']); }
        $reference = $id ? StaffReference::whereKey($id)->where('kind', $kind)->where('is_active', true)->first() : null;
        if ($id && ! $reference) { throw ValidationException::withMessages(['reference_id' => 'Choisissez une valeur active du bon catalogue.']); }
        $last = $p->assignments()->where('kind', $kind)->orderByDesc('id')->first();
        if ($last && $date < ($last->ended_on ?? $last->assigned_on)->format('Y-m-d')) { throw ValidationException::withMessages(['effective_on' => 'Cette date précède la dernière attribution ou sa clôture.']); }
        $current = $p->currentAssignments()->where('kind', $kind)->first();
        if ($current && $id === (int) $current->staff_reference_id) { throw ValidationException::withMessages(['reference_id' => 'Cette attribution est déjà en cours.']); }
        if (! $current && ! $id) { throw ValidationException::withMessages(['reference_id' => 'Aucune attribution en cours à clôturer.']); }
        if ($current) { $current->forceFill(['ended_on' => $date, 'current_slot' => null])->save(); }
        if ($reference) { $assignment = new StaffAssignment; $assignment->forceFill(['staff_profile_id' => $p->id, 'staff_reference_id' => $reference->id, 'kind' => $kind, 'name_snapshot' => $reference->name, 'assigned_on' => $date, 'current_slot' => 1, 'assigned_by' => $actor->id, 'created_at' => now()])->save(); }
        StaffAccess::audit($actor, 'staff.assignment_changed', $p->id, metadata: ['kind' => $kind, 'previous_assignment_id' => $current?->id, 'reference_id' => $id, 'effective_on' => $date]);
    }
    private function editable(StaffProfile $profile): void { if ($profile->is_archived) { throw ValidationException::withMessages(['staff' => 'Restaurez la fiche avant de la modifier.']); } }
}
