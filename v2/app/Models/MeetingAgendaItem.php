<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class MeetingAgendaItem extends Model
{
    public $timestamps = false;
    public function round(): BelongsTo { return $this->belongsTo(ReportRound::class, 'report_round_id'); }
}
