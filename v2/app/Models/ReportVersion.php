<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReportVersion extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['number' => 'integer', 'size' => 'integer', 'created_at' => 'datetime']; }
    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function decision(): HasOne { return $this->hasOne(ReportDecision::class); }
}
