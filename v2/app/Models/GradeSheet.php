<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class GradeSheet extends Model
{
    protected $attributes = ['status' => 'draft', 'revision' => 1];
    protected function casts(): array { return ['marks' => 'array', 'revision' => 'integer']; }
    public function enrollment() { return $this->belongsTo(Enrollment::class); }
    public function program() { return $this->belongsTo(TeachingProgram::class, 'teaching_program_id'); }
    public function semester() { return $this->belongsTo(AcademicSemester::class, 'academic_semester_id'); }
    public function publications() { return $this->hasMany(GradePublication::class); }
    public function latestPublication() { return $this->hasOne(GradePublication::class)->latestOfMany(); }
}
