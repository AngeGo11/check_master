<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AnnualDecision extends Model
{
    public const OUTCOMES = ['passed' => 'Année validée', 'conditional' => 'Passage à cheval autorisé', 'repeat' => 'Ajourné · redoublement'];
    public $timestamps = false;
    protected function casts(): array { return ['number' => 'integer', 'snapshot' => 'array', 'selected_subjects' => 'array', 'partial_confirmed' => 'boolean', 'created_at' => 'datetime']; }
    public function enrollment() { return $this->belongsTo(Enrollment::class); }
    public function nextEnrollment() { return $this->hasOne(Enrollment::class, 'source_annual_decision_id'); }
}
