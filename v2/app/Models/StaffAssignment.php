<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class StaffAssignment extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['assigned_on' => 'immutable_date', 'ended_on' => 'immutable_date', 'created_at' => 'datetime']; }
    public function reference(): BelongsTo { return $this->belongsTo(StaffReference::class, 'staff_reference_id'); }
}
