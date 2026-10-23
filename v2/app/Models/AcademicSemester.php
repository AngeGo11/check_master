<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class AcademicSemester extends Model
{
    public $timestamps = false;
    public function studyLevel() { return $this->belongsTo(StudyLevel::class); }
}
