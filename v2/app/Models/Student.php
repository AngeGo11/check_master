<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Student extends Model
{
    public const FIELDS = ['card_number', 'last_name', 'first_name', 'email', 'date_of_birth', 'gender', 'study_level_id', 'promotion_id', 'student_status_id', 'is_archived'];

    protected $attributes = ['is_archived' => false, 'revision' => 1];

    protected function casts(): array
    {
        return ['is_archived' => 'boolean', 'revision' => 'integer', 'date_of_birth' => 'date'];
    }

    public function studyLevel(): BelongsTo { return $this->belongsTo(StudyLevel::class); }
    public function promotion(): BelongsTo { return $this->belongsTo(Promotion::class); }
    public function studentStatus(): BelongsTo { return $this->belongsTo(StudentStatus::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
