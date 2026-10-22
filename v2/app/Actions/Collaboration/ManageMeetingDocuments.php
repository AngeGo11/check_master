<?php
namespace App\Actions\Collaboration;
use App\Models\{Meeting, MeetingDocument, User};
use App\Services\{CollaborationAccess, CollaborationAudit, CollaborationLock, MeetingFiles, ReportFiles};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
class ManageMeetingDocuments
{
    public function __construct(private CollaborationAccess $access, private MeetingFiles $files, private ManageMeetings $meetings) {}
    public function upload(User $actor, Meeting $target, array $data, UploadedFile $file): MeetingDocument
    {
        $digest = app(ReportFiles::class)->fingerprint($file); $path = null;
        try {
            return CollaborationLock::run($actor, 'meetings.manage', function (User $actor) use ($target, $data, $file, $digest, &$path) {
                $meeting = Meeting::whereKey($target->id)->lockForUpdate()->firstOrFail(); abort_unless($this->access->canManage($actor, $meeting), 403);
                $existing = MeetingDocument::where('operation_key', $data['operation_key'])->first();
                if ($existing) {
                    if ((int) $existing->meeting_id === $meeting->id && (int) $existing->uploaded_by === $actor->id && $existing->title === $data['title'] && hash_equals($existing->sha256, $digest)) { return $existing; }
                    throw ValidationException::withMessages(['operation_key' => 'Cette référence de document a déjà été utilisée.']);
                }
                $this->meetings->revision($meeting, $data['revision']);
                if ($meeting->cancelled_at) { throw ValidationException::withMessages(['meeting' => 'Cette réunion est annulée.']); }
                $path = 'documents/'.Str::uuid().'.pdf'; $this->files->store($file, $path);
                $document = new MeetingDocument;
                $document->forceFill(['meeting_id' => $meeting->id, 'uploaded_by' => $actor->id, 'operation_key' => $data['operation_key'], 'title' => $data['title'],
                    'storage_path' => $path, 'size' => $file->getSize(), 'sha256' => $digest, 'created_at' => now()])->save();
                CollaborationAudit::record($actor, 'meeting.document_uploaded', $meeting->id, metadata: ['document_id' => $document->id]);
                return $document;
            });
        } catch (Throwable $error) {
            if ($path && ! MeetingDocument::where('storage_path', $path)->exists()) { Storage::disk('meetings')->delete($path); }
            throw $error;
        }
    }
    public function withdraw(User $actor, Meeting $target, MeetingDocument $document, array $data): void
    {
        CollaborationLock::run($actor, 'meetings.manage', function (User $actor) use ($target, $document, $data) {
            $meeting = Meeting::whereKey($target->id)->lockForUpdate()->firstOrFail(); abort_unless($this->access->canManage($actor, $meeting), 403);
            $this->meetings->revision($meeting, $data['revision']);
            $document = $meeting->documents()->whereKey($document->id)->firstOrFail();
            if ($document->withdrawn_at) { return; }
            $document->forceFill(['withdrawn_at' => now(), 'withdrawal_reason' => $data['reason']])->save();
            CollaborationAudit::record($actor, 'meeting.document_withdrawn', $meeting->id, metadata: ['document_id' => $document->id, 'reason' => $data['reason']]);
        });
    }
}
