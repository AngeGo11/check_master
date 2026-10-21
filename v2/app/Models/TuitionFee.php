<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TuitionFee extends Model
{
    protected $attributes = ['revision' => 1];
    protected function casts(): array { return ['amount' => 'integer', 'revision' => 'integer']; }
    public function academicYear(): BelongsTo { return $this->belongsTo(AcademicYear::class); }
    public function studyLevel(): BelongsTo { return $this->belongsTo(StudyLevel::class); }
}
