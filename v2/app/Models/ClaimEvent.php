<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimEvent extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['created_at' => 'datetime']; }
}
