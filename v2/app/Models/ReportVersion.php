<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportVersion extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['number' => 'integer', 'size' => 'integer', 'created_at' => 'datetime', 'eligibility_snapshot' => 'array']; }
    public function report(): BelongsTo { return $this->belongsTo(Report::class); }
    public function enrollment(): BelongsTo { return $this->belongsTo(Enrollment::class); }
    public function rounds(): HasMany { return $this->hasMany(ReportRound::class); }
    public function latestRound(): HasOne { return $this->hasOne(ReportRound::class)->latestOfMany(); }
    public function decision(): HasOne { return $this->hasOne(ReportDecision::class); }
}
