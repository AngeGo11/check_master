<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, HasMany};
class Meeting extends Model
{
    protected function casts(): array { return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'cancelled_at' => 'datetime', 'revision' => 'integer']; }
    public function organizer(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function participants(): HasMany { return $this->hasMany(MeetingParticipant::class); }
    public function agenda(): HasMany { return $this->hasMany(MeetingAgendaItem::class); }
    public function documents(): HasMany { return $this->hasMany(MeetingDocument::class); }
    public function state(): string { return $this->cancelled_at ? 'cancelled' : (now()->lt($this->starts_at) ? 'scheduled' : (now()->lt($this->ends_at) ? 'ongoing' : 'completed')); }
}
