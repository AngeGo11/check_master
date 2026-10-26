<?php
namespace App\Actions\Documents;
use App\Actions\Reports\ManageReports;
use App\Models\{DocumentEvent, PersonalArchive, ReportVersion, Student, User, WorkDocument, WorkDocumentEntry, WorkDocumentVersion};
use App\Services\{AcademicAccess, CollaborationLock, DocumentAccess, DocumentFiles};
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class ManageDocuments
{
    private function invalid(string $key, string $message): never { throw ValidationException::withMessages([$key => $message]); }
    private function hash(array $data): string { return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)); }
    private function previous(User $actor, string $key, string $action, string $hash): ?DocumentEvent
    {
        $e = DocumentEvent::where('operation_key', $key)->first();
        if ($e && ((int) $e->actor_user_id !== $actor->id || $e->action !== $action || ! hash_equals($e->request_hash, $hash))) { $this->invalid('operation_key', 'Cette référence a déjà servi à une autre action. Rechargez le formulaire.'); }
        return $e;
    }
    private function event(User $u, string $action, string $key, string $hash, ?WorkDocumentVersion $v = null, ?PersonalArchive $a = null): void
    {
        $e = new DocumentEvent; $e->forceFill(['actor_user_id' => $u->id, 'action' => $action, 'operation_key' => $key, 'request_hash' => $hash,
            'work_document_id' => $v?->work_document_id, 'work_document_version_id' => $v?->id, 'personal_archive_id' => $a?->id,
            'metadata' => $v ? ['number' => $v->number] : [], 'created_at' => now()])->save();
    }
    private function sections(array $input): array
    {
        $sections = array_map(fn ($s) => ['heading' => trim($s['heading'] ?? ''), 'body' => trim($s['body']), 'style' => $s['style']], $input);
        if (array_sum(array_map(fn ($s) => mb_strlen($s['heading'].$s['body']), $sections)) > 50000) { $this->invalid('sections', 'Limitez le document à 50 000 caractères.'); }
        return $sections;
    }
    private function cleanUp(?string $path): void
    {
        if ($path && ! WorkDocumentVersion::where('storage_path', $path)->exists()) {
            try { Storage::disk('documents')->delete($path); } catch (\Throwable $e) { report($e); }
        }
    }
    private function version(User $actor, WorkDocument $d, array $data, array $entries, ?UploadedFile $upload, ?string &$path, bool $publish = false): WorkDocumentVersion
    {
        $number = (int) $d->versions()->max('number') + 1;
        $files = app(DocumentFiles::class);
        $bytes = $upload ? file_get_contents($upload->getRealPath()) : $files->render(['reference' => $d->reference, 'number' => $number,
            'title' => $data['title'], 'kind' => $d->kind, 'published' => $publish, 'publicationReference' => $data['publication_reference'] ?? null,
            'actor' => $actor->name, 'date' => today()->format('d/m/Y'), 'sections' => $data['sections'], 'entries' => $entries, 'extract' => false]);
        $path = 'versions/'.Str::uuid().'.pdf'; $files->put($path, $bytes);
        $v = new WorkDocumentVersion; $v->forceFill(['work_document_id' => $d->id, 'number' => $number, 'title' => $data['title'], 'sections' => $data['sections'],
            'reason' => $data['reason'], 'created_by' => $actor->id, 'actor_name' => $actor->name, 'storage_path' => $path, 'size' => strlen($bytes), 'sha256' => hash('sha256', $bytes),
            'published_at' => $publish ? now() : null, 'publication_reference' => $publish ? $data['publication_reference'] : null, 'created_at' => now()])->save();
        foreach ($entries as $entry) { $r = new WorkDocumentEntry; $r->forceFill([...$entry, 'work_document_version_id' => $v->id])->save(); }
        return $v;
    }
    public function save(User $actor, array $data, ?WorkDocument $target = null, ?UploadedFile $upload = null): WorkDocument
    {
        $kind = $target?->kind ?? $data['kind']; $path = null;
        try {
            return CollaborationLock::run($actor, $kind === 'personal' ? 'documents.self' : 'documents.minutes', function (User $actor) use ($kind, $data, $target, $upload, &$path) {
                $access = app(DocumentAccess::class); abort_unless($kind === 'personal' ? $access->personal($actor) : $access->writer($actor), 403);
                $d = $target ? WorkDocument::whereKey($target->id)->lockForUpdate()->firstOrFail() : null;
                if ($d) { abort_unless($access->editable($actor, $d), 403); }
                $format = $d?->format ?? $data['format']; $sections = $this->sections($data['sections'] ?? []); $input = $data['entries'] ?? [];
                usort($input, fn ($a, $b) => (int) $a['round_id'] <=> (int) $b['round_id']);
                if ($kind === 'minutes' && $format !== 'text') { $this->invalid('format', 'Un compte rendu utilise l’éditeur structuré.'); }
                if ($format === 'pdf' && ! $upload) { $this->invalid('document', 'Sélectionnez le PDF de cette version.'); }
                if ($format === 'text' && $upload) { $this->invalid('document', 'Ce document utilise l’éditeur de texte.'); }
                if ($format === 'text' && ! $sections && $kind === 'personal') { $this->invalid('sections', 'Ajoutez au moins une section.'); }
                if (($format === 'pdf' && $sections) || ($kind === 'personal' && $input)) { $this->invalid('sections', 'Le contenu ne correspond pas au type de document.'); }
                $digest = $upload ? app(DocumentFiles::class)->fingerprint($upload) : null;
                $normalized = array_map(fn ($i) => [(int) $i['round_id'], $i['source_fingerprint'], $i['body']], $input);
                $hash = $this->hash([$d?->id, $kind, $format, (int) $data['revision'], $data['title'], $data['reason'], $sections, $normalized, $digest]);
                $action = $d ? 'document.saved' : 'document.created';
                if ($event = $this->previous($actor, $data['operation_key'], $action, $hash)) {
                    $existing = WorkDocument::findOrFail($event->work_document_id); abort_unless($access->editable($actor, $existing), 403); return $existing;
                }
                if ((int) $data['revision'] !== ($d?->revision ?? 0)) { $this->invalid('revision', 'Une nouvelle version existe. Rechargez le document avant de modifier.'); }
                $entries = []; $reports = [];
                if ($kind === 'minutes') {
                    if (! $input || count($input) > 10) { $this->invalid('entries', 'Choisissez un à dix rapports examinés par votre commission.'); }
                    foreach ($input as $item) {
                        $round = $access->rounds($actor)->findOrFail($item['round_id']);
                        $fingerprint = $access->sourceFingerprint($round);
                        if (! hash_equals($fingerprint, $item['source_fingerprint'])) { $this->invalid('entries', 'Une commission a changé. Rechargez la sélection.'); }
                        if (in_array($round->version->report_id, $reports, true)) { $this->invalid('entries', 'Un rapport ne peut figurer qu’une fois dans le compte rendu.'); }
                        $reports[] = $round->version->report_id;
                        $entries[] = ['report_round_id' => $round->id, 'report_version_id' => $round->report_version_id, 'body' => $item['body'],
                            'snapshot' => $access->sourceSnapshot($round), 'source_fingerprint' => $fingerprint];
                    }
                }
                if (! $d) {
                    $student = $kind === 'personal' ? Student::where('user_id', $actor->id)->where('is_archived', false)->firstOrFail() : null;
                    $d = new WorkDocument; $d->forceFill(['reference' => ($kind === 'minutes' ? 'CR-' : 'DOC-').Str::ulid(), 'kind' => $kind, 'format' => $format,
                        'owner_user_id' => $actor->id, 'student_id' => $student?->id])->save();
                } else { $d->forceFill(['revision' => $d->revision + 1])->save(); }
                $v = $this->version($actor, $d, ['title' => $data['title'], 'reason' => $data['reason'], 'sections' => $sections], $entries, $upload, $path);
                $this->event($actor, $action, $data['operation_key'], $hash, $v); return $d;
            });
        } catch (\Throwable $e) { $this->cleanUp($path); throw $e; }
    }
    public function publish(User $actor, WorkDocument $target, array $data): WorkDocumentVersion
    {
        $path = null;
        try {
            return CollaborationLock::run($actor, 'documents.minutes', function (User $actor) use ($target, $data, &$path) {
                $access = app(DocumentAccess::class); $d = WorkDocument::whereKey($target->id)->lockForUpdate()->firstOrFail();
                abort_unless($d->kind === 'minutes' && $access->editable($actor, $d), 403);
                $hash = $this->hash([$d->id, (int) $data['revision'], $data['publication_reference'], $data['reason']]);
                if ($event = $this->previous($actor, $data['operation_key'], 'document.published', $hash)) { return WorkDocumentVersion::findOrFail($event->work_document_version_id); }
                AcademicAccess::revision($d, (int) $data['revision']); $draft = $d->latestVersion;
                if ($draft->published_at) { $this->invalid('revision', 'Cette version est déjà publiée. Enregistrez une correction pour publier à nouveau.'); }
                abort_unless(app(DocumentFiles::class)->intact($draft), 404);
                $entries = [];
                foreach ($draft->entries as $entry) {
                    $round = $access->rounds($actor)->findOrFail($entry->report_round_id);
                    if (! hash_equals($entry->source_fingerprint, $access->sourceFingerprint($round))) { $this->invalid('entries', 'Une commission a changé. Enregistrez une version actualisée.'); }
                    $entries[] = $entry->only('report_round_id', 'report_version_id', 'body', 'snapshot', 'source_fingerprint');
                }
                $v = $this->version($actor, $d, ['title' => $draft->title, 'sections' => $draft->sections, 'reason' => $data['reason'], 'publication_reference' => $data['publication_reference']], $entries, null, $path, true);
                $d->forceFill(['revision' => $d->revision + 1])->save(); $this->event($actor, 'document.published', $data['operation_key'], $hash, $v); return $v;
            });
        } catch (\Throwable $e) { $this->cleanUp($path); throw $e; }
    }
    public function archive(User $actor, array $data): PersonalArchive
    {
        return CollaborationLock::run($actor, 'documents.archive', function (User $actor) use ($data) {
            $access = app(DocumentAccess::class); $type = $data['source_type']; $id = (int) $data['source_id']; $key = $type.':'.$id;
            $report = $type === 'report' ? $access->reportVersions($actor)->findOrFail($id) : null;
            $version = $type === 'document' ? $access->fullVersions($actor)->findOrFail($id) : null;
            $entry = $type === 'extract' ? $access->ownEntries($actor)->findOrFail($id) : null;
            abort_unless($report || $version || $entry, 422);
            $hash = $this->hash([$key, (int) $data['revision'], (bool) $data['active']]);
            if ($event = $this->previous($actor, $data['operation_key'], 'document.archive_changed', $hash)) { return $access->archives($actor)->findOrFail($event->personal_archive_id); }
            $a = PersonalArchive::where('user_id', $actor->id)->where('source_key', $key)->lockForUpdate()->first();
            if ((int) $data['revision'] !== ($a?->revision ?? 0)) { $this->invalid('revision', 'L’archive a changé. Rechargez la liste.'); }
            $a ??= new PersonalArchive; $a->forceFill(['user_id' => $actor->id, 'source_key' => $key, 'report_version_id' => $report?->id,
                'work_document_version_id' => $version?->id ?? $entry?->work_document_version_id, 'work_document_entry_id' => $entry?->id,
                'active' => (bool) $data['active'], 'revision' => $a->exists ? $a->revision + 1 : 1])->save();
            $this->event($actor, 'document.archive_changed', $data['operation_key'], $hash, a: $a); return $a;
        });
    }
    public function deposit(User $actor, int $id, array $data)
    {
        $access = app(DocumentAccess::class); abort_unless($access->personal($actor), 403);
        $v = $access->fullVersions($actor)->whereHas('document', fn ($q) => $q->where('kind', 'personal'))->findOrFail($id);
        abort_unless(app(DocumentFiles::class)->intact($v), 404);
        $file = new UploadedFile(Storage::disk('documents')->path($v->storage_path), 'rapport.pdf', 'application/pdf', null, true);
        return app(ManageReports::class)->submit($actor, [...$data, 'title' => $v->title], $file, $v->id);
    }
}
