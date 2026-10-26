<?php
namespace App\Http\Controllers;
use App\Actions\Documents\ManageDocuments;
use App\Http\Requests\DocumentRequest;
use App\Models\{Enrollment, PersonalArchive, Report, Student, WorkDocument, WorkDocumentEntry, WorkDocumentVersion};
use App\Services\{DocumentAccess, DocumentFiles, Eligibility, ReportFiles};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
class DocumentController extends Controller
{
    public function __construct(private DocumentAccess $access) {}
    private function page(Request $r, string $name, array $data) { return Inertia::render($name, $data)->toResponse($r)->header('Cache-Control', 'private, no-store'); }
    private function summary(WorkDocumentVersion $v): array
    {
        return ['id' => $v->id, 'document_id' => $v->work_document_id, 'reference' => $v->document->reference, 'kind' => $v->document->kind, 'format' => $v->document->format,
            ...$v->only('number', 'title', 'actor_name', 'size'), 'published' => (bool) $v->published_at, 'created_at' => $v->created_at->toIso8601String()];
    }
    private function bookmark(Request $r, string $type, int $id): ?array
    {
        if (! $this->access->archive($r->user())) { return null; }
        $a = PersonalArchive::where('user_id', $r->user()->id)->where('source_key', $type.':'.$id)->first();
        return ['source_type' => $type, 'source_id' => $id, 'revision' => $a?->revision ?? 0, 'active' => $a?->active ?? false, 'operation_key' => (string) Str::uuid()];
    }
    public function index(Request $r)
    {
        abort_unless($this->access->enter($r->user()), 403);
        $f = $r->validate(['search' => ['nullable', 'string', 'max:150'], 'kind' => ['nullable', Rule::in(['personal', 'minutes'])], 'page' => ['nullable', 'integer', 'min:1']]);
        $latest = $this->access->fullVersions($r->user())->selectRaw('max(work_document_versions.id)')->groupBy('work_document_id');
        $q = $this->access->fullVersions($r->user())->whereIn('id', $latest);
        if ($f['kind'] ?? null) { $q->whereHas('document', fn ($q) => $q->where('kind', $f['kind'])); }
        if ($s = trim($f['search'] ?? '')) { $q->where(fn ($q) => $q->where('title', 'like', '%'.$s.'%')->orWhereHas('document', fn ($q) => $q->where('reference', 'like', '%'.$s.'%'))); }
        $stats = ['total' => (clone $q)->count(), 'personal' => (clone $q)->whereHas('document', fn ($q) => $q->where('kind', 'personal'))->count(),
            'published' => (clone $q)->whereNotNull('published_at')->count(), 'drafts' => (clone $q)->whereNull('published_at')->whereHas('document', fn ($q) => $q->where('kind', 'minutes'))->count()];
        $rows = $q->with('document')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($v) => $this->summary($v));
        $student = $this->access->personal($r->user()) ? Student::where('user_id', $r->user()->id)->where('is_archived', false)->exists() : false;
        return $this->page($r, 'documents/index', ['documents' => $rows, 'filters' => $f, 'statistics' => $stats, 'canCreatePersonal' => $student,
            'canCreateMinutes' => $this->access->writer($r->user()), 'personal' => $this->access->personal($r->user()), 'canArchive' => $this->access->archive($r->user())]);
    }
    public function create(Request $r)
    {
        $f = $r->validate(['kind' => ['required', Rule::in(['personal', 'minutes'])]]); $kind = $f['kind'];
        abort_unless($kind === 'personal' ? $this->access->personal($r->user()) : $this->access->writer($r->user()), 403);
        if ($kind === 'personal') { Student::where('user_id', $r->user()->id)->where('is_archived', false)->firstOrFail(); }
        return $this->editor($r, null, $kind);
    }
    public function edit(Request $r, WorkDocument $document)
    {
        abort_unless($this->access->editable($r->user(), $document), 403); return $this->editor($r, $document, $document->kind);
    }
    private function editor(Request $r, ?WorkDocument $d, string $kind)
    {
        $v = $d?->latestVersion;
        return $this->page($r, 'documents/edit', ['document' => $d ? [...$d->only('id', 'reference', 'kind', 'format', 'revision'), 'title' => $v->title, 'sections' => $v->sections,
            'entries' => $v->entries->map(fn ($e) => ['round_id' => $e->report_round_id, 'source_fingerprint' => $e->source_fingerprint, 'body' => $e->body, 'snapshot' => $e->snapshot])] : null,
            'kind' => $kind, 'operationKey' => (string) Str::uuid()]);
    }
    public function sources(Request $r)
    {
        abort_unless($this->access->writer($r->user()), 403);
        $f = $r->validate(['search' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1']]);
        $q = $this->access->rounds($r->user());
        if ($s = trim($f['search'] ?? '')) { $q->whereHas('version', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$s.'%')->orWhereHas('report.student', fn ($q) => $q->where(fn ($q) => $q->where('card_number', 'like', '%'.$s.'%')->orWhere('last_name', 'like', '%'.$s.'%'))))); }
        return response()->json($q->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($round) => ['round_id' => $round->id, 'source_fingerprint' => $this->access->sourceFingerprint($round), 'snapshot' => $this->access->sourceSnapshot($round)]))->header('Cache-Control', 'private, no-store');
    }
    public function show(Request $r, WorkDocument $document)
    {
        $f = $r->validate(['version' => ['nullable', 'integer', 'min:1'], 'history_page' => ['nullable', 'integer', 'min:1']]);
        $q = $this->access->fullVersions($r->user())->where('work_document_id', $document->id);
        $v = (clone $q)->when($f['version'] ?? null, fn ($q, $id) => $q->whereKey($id))->orderByDesc('id')->firstOrFail();
        $editable = $this->access->editable($r->user(), $document); $latest = (int) $document->latestVersion->id === $v->id;
        $enrollments = []; $report = null;
        if ($document->kind === 'personal' && $editable) {
            $report = Report::where('student_id', $document->student_id)->first();
            $enrollments = Enrollment::with(['student', 'academicYear', 'internship'])->withPaidAmount()->where('student_id', $document->student_id)
                ->whereHas('academicYear', fn ($q) => $q->where('status', 'current'))->get()->filter(fn ($e) => app(Eligibility::class)->evaluate($e)['status'] === 'eligible')
                ->map(fn ($e) => ['id' => $e->id, 'label' => $e->reference.' · '.$e->academicYear->name])->values()->all();
        }
        return $this->page($r, 'documents/show', ['version' => [...$this->summary($v), 'sections' => $v->sections, 'reason' => $v->reason, 'publication_reference' => $v->publication_reference,
            'entries' => $v->entries->map(fn ($e) => ['id' => $e->id, 'body' => $e->body, 'snapshot' => $e->snapshot])],
            'history' => (clone $q)->with('document')->orderByDesc('number')->paginate(10, ['*'], 'history_page')->withQueryString()->through(fn ($row) => $this->summary($row)),
            'revision' => $document->revision, 'canEdit' => $editable, 'canPublish' => $editable && $latest && $document->kind === 'minutes' && ! $v->published_at,
            'canDeposit' => $editable && $document->kind === 'personal' && count($enrollments) > 0 && (! $report || $report->status === 'changes_requested'),
            'enrollments' => $enrollments, 'reportRevision' => $report?->revision ?? 0, 'operationKey' => (string) Str::uuid(), 'archive' => $this->bookmark($r, 'document', $v->id),
            'available' => app(DocumentFiles::class)->intact($v)]);
    }
    public function extracts(Request $r)
    {
        abort_unless($this->access->personal($r->user()), 403); $r->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $rows = $this->access->ownEntries($r->user())->with('version.document')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($e) => $this->extractData($e));
        return $this->page($r, 'documents/extracts', ['entries' => $rows]);
    }
    private function extractData(WorkDocumentEntry $e): array
    {
        return ['id' => $e->id, 'reference' => $e->version->document->reference, 'number' => $e->version->number, 'body' => $e->body, 'snapshot' => $e->snapshot,
            'actor_name' => $e->version->actor_name, 'publication_reference' => $e->version->publication_reference, 'created_at' => $e->version->published_at->toIso8601String()];
    }
    public function extractShow(Request $r, int $entry)
    {
        $e = $this->access->ownEntries($r->user())->findOrFail($entry);
        return $this->page($r, 'documents/extract', ['entry' => $this->extractData($e), 'archive' => $this->bookmark($r, 'extract', $e->id)]);
    }
    public function download(Request $r, int $version, DocumentFiles $files) { return $files->download($this->access->fullVersions($r->user())->findOrFail($version)); }
    public function extractDownload(Request $r, int $entry, DocumentFiles $files) { return $files->extract($this->access->ownEntries($r->user())->findOrFail($entry)); }
    public function reports(Request $r)
    {
        abort_unless($this->access->archive($r->user()), 403);
        $f = $r->validate(['search' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1']]); $q = $this->access->reportVersions($r->user());
        if ($s = trim($f['search'] ?? '')) { $q->where(fn ($q) => $q->where('title', 'like', '%'.$s.'%')->orWhereHas('report', fn ($q) => $q->where('reference', 'like', '%'.$s.'%'))); }
        $rows = $q->with('report')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($v) => [...$v->only('id', 'title', 'number'), 'reference' => $v->report->reference,
            'created_at' => $v->created_at->toIso8601String(), 'archive' => $this->bookmark($r, 'report', $v->id)]);
        return $this->page($r, 'documents/reports', ['versions' => $rows, 'filters' => $f]);
    }
    public function reportDownload(Request $r, int $version, ReportFiles $files)
    {
        abort_unless($this->access->archive($r->user()), 403); return $files->download($this->access->reportVersions($r->user())->findOrFail($version));
    }
    public function archives(Request $r)
    {
        $f = $r->validate(['search' => ['nullable', 'string', 'max:150'], 'state' => ['nullable', Rule::in(['active', 'removed'])], 'page' => ['nullable', 'integer', 'min:1']]);
        $q = $this->access->archives($r->user())->where('active', ($f['state'] ?? 'active') === 'active');
        if ($s = trim($f['search'] ?? '')) { $q->where(fn ($q) => $q->whereHas('version.document', fn ($q) => $q->where('reference', 'like', '%'.$s.'%'))->orWhereHas('reportVersion.report', fn ($q) => $q->where('reference', 'like', '%'.$s.'%'))->orWhereHas('reportVersion', fn ($q) => $q->where('title', 'like', '%'.$s.'%'))
            ->orWhere(fn ($q) => $q->whereNull('work_document_entry_id')->whereHas('version', fn ($q) => $q->where('title', 'like', '%'.$s.'%')))
            ->orWhereHas('entry.reportVersion', fn ($q) => $q->where('title', 'like', '%'.$s.'%'))); }
        $rows = $q->with(['reportVersion.report', 'version.document', 'entry'])->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString()->through(function ($a) use ($r) {
            $type = $a->work_document_entry_id ? 'extract' : ($a->report_version_id ? 'report' : 'document'); $id = $a->work_document_entry_id ?? $a->report_version_id ?? $a->work_document_version_id;
            return ['id' => $a->id, 'type' => $type, 'title' => $type === 'extract' ? 'Extrait individuel du compte rendu' : ($a->reportVersion?->title ?? $a->version->title),
                'reference' => $a->reportVersion?->report->reference ?? $a->version->document->reference, 'number' => $a->reportVersion?->number ?? $a->version->number,
                'updated_at' => $a->updated_at->toIso8601String(), 'archive' => $this->bookmark($r, $type, $id),
                'download' => '/documents/'.($type === 'report' ? 'report-versions/' : ($type === 'extract' ? 'extracts/' : 'versions/')).$id.'/download',
                'href' => $type === 'extract' ? '/documents/extracts/'.$id : ($type === 'document' ? '/documents/'.$a->version->work_document_id.'?version='.$id : null)];
        });
        return $this->page($r, 'documents/archives', ['archives' => $rows, 'filters' => $f]);
    }
    public function store(DocumentRequest $r, ManageDocuments $a) { $d = $a->save($r->user(), $r->safe()->except('document'), upload: $r->file('document')); return redirect('/documents/'.$d->id)->with('success', 'Document enregistré avec sa première version.'); }
    public function save(DocumentRequest $r, WorkDocument $document, ManageDocuments $a) { $d = $a->save($r->user(), $r->safe()->except('document'), $document, $r->file('document')); return redirect('/documents/'.$d->id)->with('success', 'Nouvelle version enregistrée. Les versions précédentes sont conservées.'); }
    public function publish(DocumentRequest $r, WorkDocument $document, ManageDocuments $a) { $a->publish($r->user(), $document, $r->validated()); return back()->with('success', 'Compte rendu publié. Chaque étudiant dispose uniquement de son extrait.'); }
    public function archive(DocumentRequest $r, ManageDocuments $a) { $a->archive($r->user(), $r->validated()); return back()->with('success', $r->boolean('active') ? 'Version ajoutée à vos archives personnelles.' : 'Version retirée de vos archives. Le document d’origine reste conservé.'); }
    public function deposit(DocumentRequest $r, int $version, ManageDocuments $a) { $a->deposit($r->user(), $version, $r->validated()); return redirect('/my-reports')->with('success', 'Version déposée. Le suivi et les avis de commission sont disponibles dans votre mémoire.'); }
}
