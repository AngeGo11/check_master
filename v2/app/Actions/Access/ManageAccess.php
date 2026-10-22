<?php

namespace App\Actions\Access;

use App\Models\User;
use App\Models\Permission;
use App\Models\UserGroup;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageAccess
{
    public function createUser(User $actor, array $data): User
    {
        return $this->locked(function () use ($actor, $data) {
            Gate::forUser($actor->fresh())->authorize('users.manage');
            $this->ensureEmailIsAvailable($data['email']);
            $user = new User;
            $user->forceFill([
                'name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                'is_active' => $data['is_active'], 'must_change_password' => true,
            ])->save();
            $user->types()->sync($data['type_ids']);
            $user->groups()->sync($data['group_ids']);
            $this->audit('user.created', $actor, $user, null, $this->accessState($user));

            return $user;
        });
    }

    public function updateUser(User $actor, User $target, array $data): User
    {
        return $this->locked(function () use ($actor, $target, $data) {
            Gate::forUser($actor->fresh())->authorize('users.manage');
            $user = $target->fresh();
            $this->ensureEmailIsAvailable($data['email'], $user->id);
            if ($actor->is($user) && ! $data['is_active']) {
                throw ValidationException::withMessages(['is_active' => 'Vous ne pouvez pas désactiver votre propre compte.']);
            }
            $before = $this->accessState($user);
            $wasAdministrator = $this->isActiveAdministrator($user);
            $emailChanged = $user->email !== $data['email'];
            $nameChanged = $user->name !== $data['name'];
            $user->forceFill(['name' => $data['name'], 'email' => $data['email'], 'is_active' => $data['is_active']]);
            if ($emailChanged) {
                $user->email_verified_at = null;
            }
            $user->save();
            $user->types()->sync($data['type_ids']);
            $user->groups()->sync($data['group_ids']);

            if ($wasAdministrator && ! $this->activeAdministratorsExist()) {
                throw ValidationException::withMessages(['group_ids' => 'Conservez au moins un administrateur plateforme actif.']);
            }
            $after = $this->accessState($user);
            if ($emailChanged || $before !== $after) {
                $this->revokeSessions($user);
            }
            $this->audit('user.updated', $actor, $user, null, [
                'before' => $before, 'after' => $after,
                'identity_fields_changed' => array_values(array_filter([$emailChanged ? 'email' : null, $nameChanged ? 'name' : null])),
            ]);

            return $user;
        });
    }

    public function updateGroup(User $actor, UserGroup $group, array $permissions): void
    {
        $this->locked(function () use ($actor, $group, $permissions) {
            Gate::forUser($actor->fresh())->authorize('groups.manage');
            if ($group->code === UserGroup::ADMINISTRATOR) {
                throw ValidationException::withMessages(['permissions' => 'Les droits du groupe Administrateur plateforme sont protégés.']);
            }
            foreach ($permissions as $permission) {
                if (! Permission::allowedForGroup($permission, $group->code)) { throw ValidationException::withMessages(['permissions' => 'Ce droit est réservé au groupe métier correspondant.']); }
            }
            $before = $group->permissions()->orderBy('code')->pluck('code')->all();
            $group->permissions()->sync($permissions);
            $this->audit('group.permissions.updated', $actor, null, $group, [
                'before' => $before, 'after' => $group->permissions()->orderBy('code')->pluck('code')->all(),
            ]);
        });
    }

    public function changePassword(User $target, string $password): User
    {
        return $this->locked(function () use ($target, $password) {
            $user = $target->fresh();
            // Also reject a password change if access was revoked after request validation.
            abort_unless($user->is_active && $user->auth_version === $target->auth_version, 403);
            $user->forceFill(['password' => $password, 'must_change_password' => false])->save();
            $this->revokeSessions($user);
            $this->audit('user.password.changed', $user, $user);

            return $user;
        });
    }

    public function grantAdministrator(User $target): void
    {
        $this->locked(function () use ($target) {
            $user = $target->fresh();
            if (! $user->is_active) {
                throw ValidationException::withMessages(['email' => 'Le compte doit être actif avant l’attribution.']);
            }
            $group = UserGroup::where('code', UserGroup::ADMINISTRATOR)->firstOrFail();
            $user->types()->syncWithoutDetaching($group->types()->pluck('user_types.id')->all());
            $user->groups()->syncWithoutDetaching([$group->id]);
            $this->revokeSessions($user);
            $this->audit('administrator.granted.from.console', null, $user, $group);
        });
    }

    private function locked(Closure $callback): mixed
    {
        return DB::transaction(function () use ($callback) {
            // Serialize access changes, including concurrent removal of the last two administrators.
            UserGroup::where('code', UserGroup::ADMINISTRATOR)->lockForUpdate()->firstOrFail();

            return $callback();
        }, attempts: 3);
    }

    private function ensureEmailIsAvailable(string $email, ?int $except = null): void
    {
        if (User::where('email', $email)->when($except, fn ($query) => $query->where('id', '!=', $except))->exists()) {
            throw ValidationException::withMessages(['email' => 'Cette adresse e-mail est déjà utilisée.']);
        }
    }

    private function isActiveAdministrator(User $user): bool
    {
        return $user->is_active && $user->groups()->where('code', UserGroup::ADMINISTRATOR)->exists()
            && $user->types()->whereHas('groups', fn ($query) => $query->where('code', UserGroup::ADMINISTRATOR))->exists();
    }

    private function activeAdministratorsExist(): bool
    {
        return User::where('is_active', true)
            ->whereHas('groups', fn ($query) => $query->where('code', UserGroup::ADMINISTRATOR))
            ->whereHas('types.groups', fn ($query) => $query->where('code', UserGroup::ADMINISTRATOR))->exists();
    }

    private function accessState(User $user): array
    {
        return [
            'is_active' => (bool) $user->is_active,
            'type_ids' => $user->types()->orderBy('user_types.id')->pluck('user_types.id')->all(),
            'group_ids' => $user->groups()->orderBy('user_groups.id')->pluck('user_groups.id')->all(),
        ];
    }

    private function revokeSessions(User $user): void
    {
        $user->forceFill(['auth_version' => $user->auth_version + 1, 'remember_token' => Str::random(60)])->save();
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }

    private function audit(string $action, ?User $actor = null, ?User $target = null, ?UserGroup $group = null, array $metadata = []): void
    {
        DB::table('access_audits')->insert([
            'actor_user_id' => $actor?->id, 'target_user_id' => $target?->id, 'target_group_id' => $group?->id,
            'action' => $action, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }
}
