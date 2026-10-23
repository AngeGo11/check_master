<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Defense extends Model
{
    protected $attributes = ['status' => 'requested', 'revision' => 1];
    protected function casts(): array { return ['revision' => 'integer', 'score' => 'integer', 'requested_at' => 'datetime', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'result_at' => 'datetime']; }
    public function student() { return $this->belongsTo(Student::class); }
    public function enrollment() { return $this->belongsTo(Enrollment::class); }
    public function report() { return $this->belongsTo(Report::class); }
    public function version() { return $this->belongsTo(ReportVersion::class, 'report_version_id'); }
    public function round() { return $this->belongsTo(ReportRound::class, 'report_round_id'); }
    public function jurors() { return $this->hasMany(DefenseJuror::class); }
    public function currentJurors() { return $this->jurors()->where('current_slot', 1); }
}
