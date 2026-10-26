<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class RetakeFee extends Model
{
    protected $attributes = ['revision' => 1];
    protected function casts(): array { return ['amount' => 'integer', 'revision' => 'integer']; }
    public function academicYear() { return $this->belongsTo(AcademicYear::class); }
    public function studyLevel() { return $this->belongsTo(StudyLevel::class); }
}
