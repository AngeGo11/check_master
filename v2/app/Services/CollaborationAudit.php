<?php
namespace App\Services;
use App\Models\User;
use Illuminate\Support\Facades\DB;
class CollaborationAudit
{
    public static function record(User $actor, string $action, ?int $meetingId = null, ?int $messageId = null, array $metadata = []): void
    {
        DB::table('collaboration_audits')->insert(['actor_user_id' => $actor->id, 'meeting_id' => $meetingId, 'message_id' => $messageId,
            'action' => $action, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
