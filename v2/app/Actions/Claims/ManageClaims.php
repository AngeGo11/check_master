<?php

namespace App\Actions\Claims;

use App\Models\{ClaimEvent, Enrollment, GradePublication, GradeSheet, StudentClaim, User};
use App\Services\{AcademicAccess, ClaimAccess, ClaimFiles, CollaborationLock};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageClaims
{
    public function create(User $actor, array $data, ?UploadedFile $file = null): StudentClaim
    {
        $path = null;
        try {
            return CollaborationLock::run($actor, 'claims.self', function (User $actor) use ($data, $file, &$path) {
                $access = app(ClaimAccess::class);
                abort_unless($access->personal($actor), 403);
                $enrollment = Enrollment::whereHas('student', fn ($q) => $q->where('user_id', $actor->id))->findOrFail($data['enrollment_id']);
                $digest = $file ? app(ClaimFiles::class)->fingerprint($file) : null;
                $keys = $data['subject_keys'] ?? []; sort($keys);
                $hash = $this->hash([(int) $enrollment->id, $data['subject'], $data['body'], (int) ($data['grade_publication_id'] ?? 0), $keys, $digest]);
                $existing = StudentClaim::where('operation_key', $data['operation_key'])->first();
                if ($existing) {
                    if ((int) $existing->submitted_by !== $actor->id || ! hash_equals($existing->submission_hash, $hash)) { $this->invalid('operation_key', 'Cet envoi a déjà été utilisé pour une autre demande. Rechargez le formulaire.'); }
                    return $existing;
                }
                if ($enrollment->cancelled_at || $enrollment->student->is_archived) { $this->invalid('enrollment_id', 'Choisissez une inscription non annulée pour un dossier étudiant actif.'); }
                $subjects = []; $publication = null;
                if ($data['grade_publication_id'] ?? null) {
                    abort_unless($actor->can('grades.self'), 403);
                    $publication = GradePublication::whereIn('grade_sheet_id', GradeSheet::where('enrollment_id', $enrollment->id)->select('id'))->findOrFail($data['grade_publication_id']);
                    $available = $access->subjects($publication);
                    if (! $keys || count($keys) > 20 || count(array_unique($keys)) !== count($keys) || array_diff($keys, array_keys($available))) { $this->invalid('subject_keys', 'Sélectionnez une à vingt UE/ECUE de cette publication.'); }
                    foreach ($keys as $key) { $subjects[] = $available[$key]; }
                } elseif ($keys) { $this->invalid('subject_keys', 'Choisissez le bulletin publié correspondant aux matières.'); }
                $claim = new StudentClaim;
                $claim->forceFill(['reference' => 'REC-'.Str::ulid(), 'enrollment_id' => $enrollment->id, 'submitted_by' => $actor->id,
                    'grade_publication_id' => $publication?->id, 'subject' => $data['subject'], 'body' => $data['body'], 'subjects' => $subjects,
                    'operation_key' => $data['operation_key'], 'submission_hash' => $hash]);
                if ($file) {
                    $path = Str::ulid().'/justificatif.pdf';
                    app(ClaimFiles::class)->store($file, $path);
                    $claim->forceFill(['storage_path' => $path, 'size' => $file->getSize(), 'sha256' => $digest]);
                }
                $claim->save();
                $this->event($actor, $claim, 'submitted', $data['body'], null, $data['operation_key'], $hash, 'student');
                return $claim;
            });
        } catch (\Throwable $error) {
            if ($path) { try { Storage::disk('claims')->delete($path); } catch (\Throwable $cleanup) { report($cleanup); } }
            throw $error;
        }
    }

    public function act(User $actor, StudentClaim $target, array $data): void
    {
        // The common lock serializes access changes, enrollment links and claim transitions.
        $access = app(ClaimAccess::class);
        $permission = $access->own($actor, $target) ? 'claims.self' : ($access->staff($actor) ? 'claims.manage' : 'claims.assigned');
        CollaborationLock::run($actor, $permission, function (User $actor) use ($target, $data) {
            $access = app(ClaimAccess::class);
            $claim = StudentClaim::whereKey($target->id)->lockForUpdate()->firstOrFail();
            abort_unless($access->canView($actor, $claim), 404);
            $action = $data['action'];
            abort_unless($access->capabilities($actor, $claim)[$action] ?? false, 403);
            $assigned = $action === 'escalate' ? (int) ($data['assigned_to'] ?? 0) : null;
            $hash = $this->hash([$claim->id, $action, (int) $data['revision'], $data['body'], $assigned]);
            $existing = ClaimEvent::where('operation_key', $data['operation_key'])->first();
            if ($existing) {
                if ((int) $existing->actor_user_id !== $actor->id || ! hash_equals($existing->request_hash, $hash)) { $this->invalid('operation_key', 'Cet envoi a déjà servi à une autre action. Rechargez la page.'); }
                return;
            }
            AcademicAccess::revision($claim, (int) $data['revision']);
            if (! in_array($action, $access->actions($actor, $claim), true)) { $this->invalid('action', 'Cette action n’est plus disponible à cette étape. Rechargez la page.'); }
            $previous = $claim->status;
            if ($action === 'escalate') {
                $assignee = $access->assignees($claim)->firstWhere('id', $assigned);
                if (! $assignee || (int) $claim->assigned_to === $assigned) { $this->invalid('assigned_to', 'Choisissez un autre responsable de filière actif et habilité.'); }
                $claim->assigned_to = $assigned;
            }
            if ($action === 'reopen') { $claim->assigned_to = null; }
            $claim->status = match ($action) {
                'review', 'reopen' => 'in_review', 'escalate' => 'escalated', 'resolve' => 'resolved', 'withdraw' => 'withdrawn', default => $claim->status,
            };
            $claim->revision++;
            $claim->save();
            $role = $access->own($actor, $claim) ? 'student' : ($access->staff($actor) ? 'schooling' : 'program-manager');
            $this->event($actor, $claim, $action, $data['body'], $previous, $data['operation_key'], $hash, $role);
        });
    }

    private function event(User $actor, StudentClaim $claim, string $action, string $body, ?string $from, string $key, string $hash, string $role): void
    {
        $event = new ClaimEvent;
        $event->forceFill(['student_claim_id' => $claim->id, 'actor_user_id' => $actor->id, 'actor_name' => $actor->name, 'actor_role' => $role,
            'action' => $action, 'from_status' => $from, 'to_status' => $claim->status, 'body' => $body, 'assigned_to' => $claim->assigned_to,
            'assignee_name' => $claim->assigned_to ? User::findOrFail($claim->assigned_to)->name : null,
            'operation_key' => $key, 'request_hash' => $hash, 'created_at' => now()])->save();
    }
    private function hash(array $data): string { return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)); }
    private function invalid(string $field, string $message): never { throw ValidationException::withMessages([$field => $message]); }
}
