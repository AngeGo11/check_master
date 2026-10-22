<?php
namespace App\Actions\HumanResources;
use App\Models\{StaffReference, User};
use App\Services\{CollaborationLock, StaffAccess};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class ManageStaffCatalog
{
    public function save(User $actor, array $data, ?StaffReference $target = null): StaffReference
    {
        return CollaborationLock::run($actor, 'hr.catalogs.manage', function (User $actor) use ($data, $target) {
            abort_unless(app(StaffAccess::class)->canCatalog($actor), 403);
            $ref = $target ? StaffReference::whereKey($target->id)->lockForUpdate()->firstOrFail() : new StaffReference;
            if ($target) { StaffAccess::revision($ref, (int) $data['revision']); }
            if ($target && $ref->kind !== $data['kind']) { throw ValidationException::withMessages(['kind' => 'Le type de catalogue ne peut pas être modifié.']); }
            $name = Str::squish($data['name']); $key = Str::lower($name);
            if ($name === '') { throw ValidationException::withMessages(['name' => 'Indiquez un libellé.']); }
            if (StaffReference::where('kind', $data['kind'])->where('name_key', $key)->when($target, fn ($q) => $q->where('id', '!=', $ref->id))->exists()) { throw ValidationException::withMessages(['name' => 'Cette valeur existe déjà, éventuellement désactivée.']); }
            $before = $target ? $ref->only('name', 'is_active') : null;
            $ref->forceFill(['kind' => $data['kind'], 'name' => $name, 'name_key' => $key, 'is_active' => (bool) $data['is_active'], 'revision' => $target ? $ref->revision + 1 : 1])->save();
            StaffAccess::audit($actor, $target ? 'catalog.updated' : 'catalog.created', referenceId: $ref->id, metadata: ['before' => $before, 'after' => $ref->only('name', 'is_active')]);
            return $ref;
        });
    }
}
