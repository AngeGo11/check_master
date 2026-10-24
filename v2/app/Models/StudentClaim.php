<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentClaim extends Model
{
    public const STATUSES = ['submitted' => 'En attente', 'in_review' => 'En cours à la scolarité', 'escalated' => 'Transmise à la filière', 'resolved' => 'Traitée', 'withdrawn' => 'Retirée par l’étudiant'];
    public const OPEN = ['submitted', 'in_review', 'escalated'];
    protected $attributes = ['status' => 'submitted', 'revision' => 1];

    protected function casts(): array { return ['subjects' => 'array', 'revision' => 'integer', 'size' => 'integer']; }
    public function enrollment() { return $this->belongsTo(Enrollment::class); }
    public function publication() { return $this->belongsTo(GradePublication::class, 'grade_publication_id'); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }
    public function events() { return $this->hasMany(ClaimEvent::class); }
}
