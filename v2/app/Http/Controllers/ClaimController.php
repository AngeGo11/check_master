<?php

namespace App\Http\Controllers;

use App\Actions\Claims\ManageClaims;
use App\Http\Requests\ClaimRequest;
use App\Models\{Enrollment, GradeSheet, StudentClaim};
use App\Services\{AcademicAccess, ClaimAccess, ClaimFiles};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClaimController extends Controller
{
    public function index(Request $r) { return $this->listing($r, false); }
    public function own(Request $r) { return $this->listing($r, true); }
    private function listing(Request $r, bool $personal)
    {
        $access = app(ClaimAccess::class);
        $filters = $r->validate(['status' => ['nullable', Rule::in(array_keys(StudentClaim::STATUSES))], 'search' => ['nullable', 'string', 'max:150'],
            'academic_year_id' => ['nullable', 'integer', 'min:1'], 'page' => ['nullable', 'integer', 'min:1'], 'space' => ['nullable', Rule::in(['service', 'assigned'])]]);
        $space = $personal ? 'personal' : ($filters['space'] ?? ($access->global($r->user()) ? 'service' : 'assigned'));
        $q = $access->query($r->user(), $space);
        $years = Enrollment::whereIn('id', (clone $q)->select('enrollment_id'))->with('academicYear')->get()->pluck('academicYear')->unique('id')->sortByDesc('starts_on')->values()->map->only('id', 'name');
        if ($filters['academic_year_id'] ?? null) { $q->whereHas('enrollment', fn ($q) => $q->where('academic_year_id', $filters['academic_year_id'])); }
        if ($text = trim($filters['search'] ?? '')) {
            $q->where(fn ($q) => $q->where('subject', 'like', '%'.$text.'%')->orWhere('reference', 'like', '%'.$text.'%')
                ->orWhereHas('enrollment.student', fn ($q) => $q->where(fn ($q) => $q->where('last_name', 'like', '%'.$text.'%')->orWhere('first_name', 'like', '%'.$text.'%')->orWhere('card_number', 'like', '%'.$text.'%'))));
        }
        $statistics = ['total' => (clone $q)->count(), 'pending' => (clone $q)->whereIn('status', ['submitted', 'in_review'])->count(),
            'escalated' => (clone $q)->where('status', 'escalated')->count(), 'resolved' => (clone $q)->where('status', 'resolved')->count()];
        if ($filters['status'] ?? null) { $q->where('status', $filters['status']); }
        $claims = $q->with(['enrollment.student', 'enrollment.academicYear', 'enrollment.studyLevel', 'assignee'])->orderByDesc('updated_at')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($c) => $this->summary($c));
        return $this->page($r, 'claims/index', ['claims' => $claims, 'statistics' => $statistics, 'years' => $years, 'filters' => $filters, 'space' => $space,
            'hasService' => $access->global($r->user()), 'hasAssigned' => $access->assigned($r->user()), 'personal' => $personal]);
    }

    public function create(Request $r)
    {
        $access = app(ClaimAccess::class); abort_unless($access->personal($r->user()), 403);
        $selected = $r->validate(['publication' => ['nullable', 'integer', 'min:1']]);
        $enrollments = Enrollment::whereHas('student', fn ($q) => $q->where('user_id', $r->user()->id)->where('is_archived', false))->whereNull('cancelled_at')
            ->with(['academicYear', 'studyLevel'])->orderByDesc('academic_year_id')->orderByDesc('id')->get();
        $publications = collect();
        if ($r->user()->can('grades.self')) {
            $sheets = GradeSheet::whereIn('enrollment_id', $enrollments->modelKeys())->whereHas('publications')->with(['semester', 'publications'])->get();
            foreach ($sheets as $sheet) {
                foreach ($sheet->publications->sortByDesc('number') as $p) {
                    $publications->push(['id' => $p->id, 'enrollment_id' => $sheet->enrollment_id, 'label' => $sheet->semester->name.' · publication '.$p->number,
                        'subjects' => array_values($access->subjects($p))]);
                }
            }
        }
        if ($selected['publication'] ?? null) { abort_unless($publications->contains('id', (int) $selected['publication']), 404); }
        return $this->page($r, 'claims/create', ['enrollments' => $enrollments->map(fn ($e) => ['id' => $e->id, 'label' => $e->academicYear->name.' · '.$e->studyLevel->name.' · '.$e->reference]),
            'publications' => $publications, 'selectedPublication' => isset($selected['publication']) ? (int) $selected['publication'] : null, 'operationKey' => (string) Str::uuid()]);
    }

    public function show(Request $r, StudentClaim $claim) { return $this->detail($r, $claim, false); }
    public function ownShow(Request $r, StudentClaim $claim) { return $this->detail($r, $claim, true); }
    private function detail(Request $r, StudentClaim $c, bool $personal)
    {
        $access = app(ClaimAccess::class);
        if ($personal) { abort_unless($access->personal($r->user()), 403); abort_unless($access->own($r->user(), $c), 404); }
        else { abort_unless($access->global($r->user()) || $access->assigned($r->user()), 403); abort_unless($access->canView($r->user(), $c), 404); }
        $r->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $actions = $access->actions($r->user(), $c);
        if ($personal) { $actions = array_values(array_intersect($actions, ['comment', 'withdraw'])); }
        $publication = $c->publication; $sheet = $publication ? GradeSheet::with('semester')->findOrFail($publication->grade_sheet_id) : null;
        $publicationHref = $publication && ($personal ? $r->user()->can('grades.self') : app(AcademicAccess::class)->canView($r->user()))
            ? ($personal ? '/my-grades/' : '/grades/').$sheet->id.'?publication='.$publication->id : null;
        $events = $c->events()->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($e) => [
            ...$e->only('id', 'actor_name', 'actor_role', 'action', 'from_status', 'to_status', 'body', 'assignee_name'), 'created_at' => $e->created_at->toIso8601String()]);
        return $this->page($r, 'claims/show', ['claim' => [...$this->summary($c), ...$c->only('revision', 'body', 'subjects'),
            'publication' => $publication ? ['number' => $publication->number, 'semester' => $sheet->semester->name, 'href' => $publicationHref] : null,
            'document' => $c->storage_path ? ['href' => '/claims/'.$c->id.'/document', 'size' => $c->size] : null],
            'personal' => $personal, 'events' => $events, 'actions' => $actions,
            'assignees' => in_array('escalate', $actions, true) ? $access->assignees($c)->reject(fn ($u) => (int) $c->assigned_to === $u->id)->values()->map->only('id', 'name') : [], 'operationKey' => (string) Str::uuid()]);
    }

    public function store(ClaimRequest $r, ManageClaims $a) { $c = $a->create($r->user(), $r->validated(), $r->file('document')); return to_route('claims.own.show', $c)->with('success', 'Réclamation déposée auprès de la scolarité.'); }
    public function act(ClaimRequest $r, StudentClaim $claim, ManageClaims $a) { $a->act($r->user(), $claim, $r->validated()); return back()->with('success', 'Action enregistrée dans le suivi de la réclamation.'); }
    public function document(Request $r, StudentClaim $claim) { abort_unless(app(ClaimAccess::class)->canView($r->user(), $claim), 404); return app(ClaimFiles::class)->download($claim); }
    private function summary(StudentClaim $c): array
    {
        $e = $c->enrollment;
        return [...$c->only('id', 'reference', 'subject', 'status'), 'created_at' => $c->created_at->toIso8601String(), 'updated_at' => $c->updated_at->toIso8601String(),
            'student' => $e->student->last_name.' '.$e->student->first_name, 'card_number' => $e->student->card_number,
            'year' => $e->academicYear->name, 'level' => $e->studyLevel->name, 'assignee' => $c->assignee?->name];
    }
    private function page(Request $r, string $name, array $props) { $response = Inertia::render($name, $props)->toResponse($r); $response->headers->set('Cache-Control', 'private, no-store, max-age=0'); return $response; }
}
