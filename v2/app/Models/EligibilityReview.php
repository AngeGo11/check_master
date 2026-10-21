<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EligibilityReview extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['criteria' => 'array', 'created_at' => 'datetime']; }
}
