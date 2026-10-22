<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    protected $attributes = ['is_active' => true, 'must_change_password' => false, 'auth_version' => 1];

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'auth_version' => 'integer',
        ];
    }

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(UserType::class, 'type_user');
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(UserGroup::class, 'group_user');
    }

    public function belongsToCompatibleGroup(string $code): bool
    {
        $group = $this->groups()->where('code', $code)->first();
        return $group && $this->types()->whereIn('user_types.id', $group->types()->pluck('user_types.id'))->exists();
    }

    public function permissionCodes(): array
    {
        if (! $this->is_active || $this->must_change_password) {
            return [];
        }

        // A type alone must never grant the rights of another person's group.
        return DB::table('group_user as membership')
            ->join('group_permission as grant', 'grant.user_group_id', '=', 'membership.user_group_id')
            ->join('user_group_user_type as eligible', 'eligible.user_group_id', '=', 'membership.user_group_id')
            ->join('type_user as type', function ($join) {
                $join->on('type.user_type_id', '=', 'eligible.user_type_id')
                    ->on('type.user_id', '=', 'membership.user_id');
            })
            ->where('membership.user_id', $this->id)
            ->whereIn('grant.permission_code', Permission::CODES)
            ->distinct()->pluck('grant.permission_code')->all();
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissionCodes(), true);
    }
}
