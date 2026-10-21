<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UserGroup extends Model
{
    public const ADMINISTRATOR = 'platform-administrator';

    public $timestamps = false;

    public function types(): BelongsToMany
    {
        return $this->belongsToMany(UserType::class, 'user_group_user_type');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'group_user');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'group_permission', 'user_group_id', 'permission_code', 'id', 'code');
    }
}
