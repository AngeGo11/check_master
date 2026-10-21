<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    public const CODES = ['users.view', 'users.manage', 'groups.view', 'groups.manage', 'students.view', 'students.manage', 'students.self', 'academic.view', 'academic.manage'];

    protected $primaryKey = 'code';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
}
