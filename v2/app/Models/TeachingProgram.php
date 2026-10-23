<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TeachingProgram extends Model
{
    protected $attributes = ['status' => 'draft', 'revision' => 1];
    protected function casts(): array { return ['units' => 'array', 'number' => 'integer', 'revision' => 'integer', 'published_at' => 'datetime']; }
    public function semester() { return $this->belongsTo(AcademicSemester::class, 'academic_semester_id'); }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
}
