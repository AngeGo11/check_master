<?php
namespace App\Services;
use App\Models\{User, UserGroup};
use Closure;
use Illuminate\Support\Facades\{DB, Gate};
class CollaborationLock
{
    public static function run(User $actor, string $permission, Closure $callback): mixed
    {
        return DB::transaction(function () use ($actor, $permission, $callback) {
            UserGroup::where('code', UserGroup::ADMINISTRATOR)->lockForUpdate()->firstOrFail();
            $fresh = $actor->fresh(); Gate::forUser($fresh)->authorize($permission);
            return $callback($fresh);
        }, attempts: 1);
    }
}
