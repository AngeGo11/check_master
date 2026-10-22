<?php
namespace App\Actions\Collaboration;

use App\Models\{InternalMessage, MessageRecipient, User};
use App\Services\{CollaborationAccess, CollaborationAudit, CollaborationLock};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageMessages
{
    public function __construct(private CollaborationAccess $access) {}
    public function save(User $actor, array $data, ?InternalMessage $target = null): InternalMessage
    {
        return CollaborationLock::run($actor, 'messages.use', function (User $actor) use ($data, $target) {
            $ids = array_map('intval', $data['recipient_ids'] ?? []); $ids = array_values(array_unique($ids)); sort($ids);
            $payload = ['subject' => trim($data['subject'] ?? ''), 'body' => trim($data['body'] ?? ''),
                'priority' => $data['priority'], 'category' => $data['category'], 'recipient_ids' => $ids,
                'source_id' => isset($data['source_id']) ? (int) $data['source_id'] : null, 'source_mode' => $data['source_mode'] ?? null, 'action' => $data['action']];
            $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
            $message = $target ? InternalMessage::where('sender_id', $actor->id)->whereKey($target->id)->lockForUpdate()->firstOrFail()
                : InternalMessage::where('operation_key', $data['operation_key'])->lockForUpdate()->first();
            if ($message && $message->operation_key !== $data['operation_key']) { throw ValidationException::withMessages(['operation_key' => 'La référence du brouillon a changé. Rechargez la page.']); }
            if ($message && (! $target || $message->status === 'sent')) {
                if ((int) $message->sender_id === $actor->id && hash_equals($message->request_hash, $hash)) { return $message; }
                throw ValidationException::withMessages(['operation_key' => 'Cette référence a déjà été utilisée. Les messages envoyés ne sont plus modifiables.']);
            }
            if ($message && ($message->revision !== (int) $data['revision'] || $message->sender_folder === 'trash')) {
                throw ValidationException::withMessages(['revision' => 'Le brouillon a changé ou se trouve dans la corbeille. Rechargez-le.']);
            }
            if (count($ids) > ($actor->can('messages.broadcast') ? 200 : 20) || in_array($actor->id, $ids, true)) {
                throw ValidationException::withMessages(['recipient_ids' => 'Vérifiez les destinataires : pas d’envoi à soi-même ; 20 contacts maximum (200 avec le droit de diffusion).']);
            }
            $recipients = $this->access->usersWith('messages.use')->whereIn('id', $ids)->get();
            if ($recipients->count() !== count($ids)) { throw ValidationException::withMessages(['recipient_ids' => 'Un destinataire est indisponible ou n’a plus accès à la messagerie.']); }
            if ($payload['source_id']) {
                $source = $this->source($actor, $payload['source_id']);
                if ($payload['source_mode'] === 'reply' && ($source->kind !== 'message' || (int) $source->sender_id === $actor->id || $ids !== [(int) $source->sender_id])) {
                    throw ValidationException::withMessages(['recipient_ids' => 'La réponse est adressée uniquement à l’expéditeur du message reçu.']);
                }
            }
            if ($payload['action'] === 'send' && (! $ids || $payload['subject'] === '' || $payload['body'] === '')) {
                throw ValidationException::withMessages(['message' => 'Indiquez au moins un destinataire, un objet et un contenu avant l’envoi.']);
            }
            $message ??= new InternalMessage;
            $message->forceFill(['reference' => $message->reference ?? 'MSG-'.Str::ulid(), 'sender_id' => $actor->id, 'sender_name' => $actor->name,
                'operation_key' => $data['operation_key'], 'request_hash' => $hash, 'subject' => $payload['subject'], 'body' => $payload['body'],
                'priority' => $payload['priority'], 'category' => $payload['category'], 'source_id' => $payload['source_id'], 'source_mode' => $payload['source_mode'],
                'status' => $payload['action'] === 'send' ? 'sent' : 'draft', 'draft_recipients' => $payload['action'] === 'send' ? null : $ids,
                'sent_at' => $payload['action'] === 'send' ? now() : null, 'revision' => $message->exists ? $message->revision + 1 : 1])->save();
            if ($message->status === 'sent') { $this->deliver($message, $recipients); }
            CollaborationAudit::record($actor, $message->status === 'sent' ? 'message.sent' : 'message.draft_saved', messageId: $message->id,
                metadata: ['recipient_count' => count($ids), 'source_mode' => $payload['source_mode']]);
            return $message;
        });
    }
    public function source(User $actor, int $id): InternalMessage
    {
        $source = $this->access->messages($actor)->where('status', 'sent')->findOrFail($id);
        $folder = (int) $source->sender_id === $actor->id ? $source->sender_folder : $source->recipients()->where('user_id', $actor->id)->value('folder');
        abort_if($folder === 'trash', 404);
        return $source;
    }
    public function state(User $actor, InternalMessage $target, string $action, int $revision): void
    {
        CollaborationLock::run($actor, 'messages.use', function (User $actor) use ($target, $action, $revision) {
            $message = $this->access->messages($actor)->whereKey($target->id)->lockForUpdate()->firstOrFail();
            $sender = (int) $message->sender_id === $actor->id;
            $copy = $sender ? $message : $message->recipients()->where('user_id', $actor->id)->firstOrFail();
            if (($sender ? $copy->sender_revision : $copy->revision) !== $revision) { throw ValidationException::withMessages(['revision' => 'Votre boîte a changé. Rechargez la page avant cette action.']); }
            if (in_array($action, ['read', 'unread'], true)) {
                if ($sender) { throw ValidationException::withMessages(['action' => 'Seul le destinataire modifie son état de lecture.']); }
                $copy->read_at = $action === 'read' ? ($copy->read_at ?? now()) : null;
            } else {
                if ($message->status === 'draft' && $action === 'archive') { throw ValidationException::withMessages(['action' => 'Enregistrez ou supprimez le brouillon.']); }
                $folder = match ($action) { 'archive' => 'archive', 'trash' => 'trash', 'restore' => $sender ? 'sent' : 'inbox', default => null };
                abort_unless($folder, 422);
                $copy->{$sender ? 'sender_folder' : 'folder'} = $folder;
            }
            $copy->{$sender ? 'sender_revision' : 'revision'}++; $copy->save();
            CollaborationAudit::record($actor, 'message.'.$action, messageId: $message->id);
        });
    }
    // Only called within the locked meeting transaction; no SMTP, SMS or external delivery.
    public function meetingNotice(User $actor, int $meetingId, array $recipientIds, string $kind, string $subject, string $body): void
    {
        $recipients = $this->access->usersWith('messages.use')->whereIn('id', $recipientIds)->where('id', '!=', $actor->id)->get();
        if ($recipients->isEmpty()) { return; }
        $message = new InternalMessage;
        $message->forceFill(['reference' => 'MSG-'.Str::ulid(), 'sender_id' => $actor->id, 'sender_name' => $actor->name,
            'operation_key' => (string) Str::uuid(), 'request_hash' => hash('sha256', $body), 'status' => 'sent', 'kind' => $kind,
            'subject' => $subject, 'body' => $body, 'category' => 'commission', 'priority' => 'normal', 'meeting_id' => $meetingId, 'sent_at' => now()])->save();
        $this->deliver($message, $recipients);
        CollaborationAudit::record($actor, 'message.meeting_notice', $meetingId, $message->id, ['recipient_count' => $recipients->count()]);
    }
    private function deliver(InternalMessage $message, $recipients): void
    {
        foreach ($recipients as $recipient) {
            $copy = new MessageRecipient;
            $copy->forceFill(['internal_message_id' => $message->id, 'user_id' => $recipient->id, 'name_snapshot' => $recipient->name])->save();
        }
    }
}
