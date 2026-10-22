<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportRound extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['number' => 'integer', 'created_at' => 'datetime', 'closed_at' => 'datetime']; }
    public function version(): BelongsTo { return $this->belongsTo(ReportVersion::class, 'report_version_id'); }
    public function members(): HasMany { return $this->hasMany(ReportRoundMember::class); }
}
