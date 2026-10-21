<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Report extends Model
{
    protected $table = 'student_reports';
    protected $attributes = ['status' => 'submitted', 'revision' => 1, 'version_count' => 1];
    public const STATUSES = ['submitted', 'changes_requested', 'approved'];
    protected function casts(): array { return ['revision' => 'integer', 'version_count' => 'integer', 'latest_submitted_at' => 'datetime']; }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function versions(): HasMany { return $this->hasMany(ReportVersion::class); }
    public function latestVersion(): HasOne { return $this->hasOne(ReportVersion::class)->latestOfMany(); }
}
