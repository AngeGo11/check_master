<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class MeetingParticipant extends Model
{
    public $timestamps = false;
    protected function casts(): array { return ['responded_at' => 'datetime', 'removed_at' => 'datetime', 'response_revision' => 'integer']; }
}
