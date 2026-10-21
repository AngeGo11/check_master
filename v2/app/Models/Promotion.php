<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    public $timestamps = false;
    protected $attributes = ['revision' => 1];

    protected function casts(): array { return ['revision' => 'integer']; }
    public function students(): HasMany { return $this->hasMany(Student::class); }
}
