<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RetakeSubject extends Model
{
    public const STATUSES = ['pending' => 'En cours', 'validated' => 'Validée', 'failed' => 'À reprendre'];
    protected $attributes = ['status' => 'pending', 'revision' => 1];
    protected function casts(): array { return ['revision' => 'integer', 'score' => 'integer', 'credits' => 'integer']; }
    public function enrollments() { return $this->belongsToMany(Enrollment::class, 'enrollment_retake')->withPivot('amount'); }
    public function events() { return $this->hasMany(RetakeEvent::class); }
    public function sourceLevel() { return $this->belongsTo(StudyLevel::class, 'source_level_id'); }
    public function semester() { return $this->belongsTo(AcademicSemester::class, 'academic_semester_id'); }
}
