<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AcademicYear extends Model
{
    protected $attributes = ['status' => 'pending', 'revision' => 1];
    protected function casts(): array { return ['starts_on' => 'date', 'ends_on' => 'date', 'revision' => 'integer']; }
    public function enrollments(): HasMany { return $this->hasMany(Enrollment::class); }
}
