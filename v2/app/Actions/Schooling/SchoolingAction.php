<?php

namespace App\Actions\Schooling;

use App\Models\User;
use App\Models\UserGroup;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

abstract class SchoolingAction
{
    protected function transact(User $actor, string $permission, Closure $callback): mixed
    {
        return DB::transaction(function () use ($actor, $permission, $callback) {
            // Shared lock with access/student writes: revoke, archive, tariff and payment changes serialize.
            UserGroup::where('code', UserGroup::ADMINISTRATOR)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize($permission);
            return $callback();
        }, attempts: 3);
    }

    protected function revision(int $current, int $submitted): void
    {
        if ($current !== $submitted) {
            throw ValidationException::withMessages(['revision' => 'Ces informations ont changé. Rechargez la page avant de réessayer.']);
        }
    }

    protected function audit(User $actor, string $action, ?int $enrollmentId, array $metadata = []): void
    {
        DB::table('enrollment_audits')->insert([
            'actor_user_id' => $actor->id, 'enrollment_id' => $enrollmentId, 'action' => $action,
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
