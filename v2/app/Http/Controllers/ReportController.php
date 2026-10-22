<?php
namespace App\Http\Controllers;

use App\Actions\Reports\ManageReports;
use App\Http\Requests\ForwardReportRequest;
use App\Http\Requests\VoteReportRequest;
use App\Http\Requests\SubmitReportRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\ReportRound;
use App\Models\User;
use App\Models\Student;
use App\Services\Eligibility;
use App\Services\ReportFiles;
use App\Services\ReportAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportAccess $access) {}
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', Rule::in(Report::STATUSES)],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'], 'submitted_on' => ['nullable', 'date_format:Y-m-d'], 'page' => ['nullable', 'integer', 'min:1']]);
        $scope = $this->access->reports($request->user())->when($filters['academic_year_id'] ?? null, fn ($q, $id) => $q->whereHas('enrollment', fn ($q) => $q->where('academic_year_id', $id)));
        $statistics = ['total' => (clone $scope)->count()];
        foreach (Report::STATUSES as $status) { $statistics[$status] = (clone $scope)->where('status', $status)->count(); }
        $search = trim($filters['search'] ?? '');
        $reports = $scope->with(['student', 'enrollment.academicYear'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['submitted_on'] ?? null, fn ($q, $date) => $q->whereDate('latest_submitted_at', $date))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('reference', 'like', '%'.$search.'%')
                ->orWhereHas('student', fn ($q) => $q->where(fn ($q) => $q->where('last_name', 'like', '%'.$search.'%')->orWhere('first_name', 'like', '%'.$search.'%')->orWhere('card_number', 'like', '%'.$search.'%')))))
            ->orderByDesc('latest_submitted_at')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($report) => $this->summary($report));
        return Inertia::render('reports/index', ['reports' => $reports, 'statistics' => $statistics, 'filters' => $filters, 'years' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name']), 'assignedOnly' => ! $request->user()->can('reports.view')]);
    }

    public function own(Request $request, Eligibility $eligibility): Response
    {
        $this->studentType($request);
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $student = Student::where('user_id', $request->user()->id)->first();
        $report = $student ? Report::with(['student', 'enrollment.academicYear'])->where('student_id', $student->id)->first() : null;
        $enrollments = $student ? Enrollment::with(['academicYear', 'studyLevel', 'student', 'internship', 'latestReview'])->withPaidAmount()
            ->where('student_id', $student->id)->whereHas('academicYear', fn ($q) => $q->where('status', 'current'))->orderByDesc('id')->get()
            ->map(function ($e) use ($eligibility) {
                $result = $eligibility->evaluate($e);
                return ['id' => $e->id, 'reference' => $e->reference, 'year' => $e->academicYear->name, 'level' => $e->studyLevel->name,
                    'eligibility' => $result['status'], 'can_submit' => $result['status'] === 'eligible' && $result['context_valid'] && $result['computed_result'] === 'eligible'];
            }) : [];
        return Inertia::render('reports/own', [
            'student' => $student?->only('card_number', 'last_name', 'first_name'), 'report' => $report ? $this->summary($report) : null,
            'versions' => $report ? $this->versions($report) : null, 'enrollments' => $enrollments, 'operationKey' => (string) Str::uuid(),
        ]);
    }
    public function submit(SubmitReportRequest $request, ManageReports $action): RedirectResponse
    {
        $action->submit($request->user(), $request->safe()->except('document'), $request->file('document'));
        return to_route('reports.own')->with('success', 'Dépôt enregistré. Retrouvez la version et son statut dans votre dossier.');
    }
    public function show(Request $request, Report $report, Eligibility $eligibility, ReportFiles $files): Response
    {
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        $report = $this->access->reports($request->user())->findOrFail($report->id);
        $report->load(['student', 'enrollment.academicYear', 'latestVersion']);
        $round = $report->latestVersion->latestRound;
        $roster = $this->access->roster();
        $rosterChanged = $round && $round->status === 'open' && ! hash_equals($this->access->fingerprint($round->members()->pluck('user_id')->all()), $this->access->fingerprint($roster->modelKeys()));
        $own = (int) $report->latestVersion->submitted_by === $request->user()->id || (int) $report->student->user_id === $request->user()->id;
        $member = $round?->members()->where('user_id', $request->user()->id)->first();
        $result = $eligibility->evaluate($report->enrollment);
        return Inertia::render('reports/show', [
            'report' => $this->summary($report), 'versions' => $this->versions($report, $request->user()),
            'eligibility' => ['status' => $result['status'], 'criteria' => array_map(fn ($criterion) => $criterion['key'] === 'tuition' ? [...$criterion, 'detail' => 'La scolarité de l’inscription doit être entièrement réglée.'] : $criterion, $result['criteria']), 'context_valid' => $result['context_valid']],
            'documentAvailable' => $files->intact($report->latestVersion),
            'currentRound' => $round ? $this->roundData($round, $request->user()) : null, 'rosterChanged' => (bool) $rosterChanged,
            'canForward' => ! $own && $this->access->canForward($request->user()) && ($report->status === 'submitted' || ($report->status === 'under_review' && $rosterChanged)),
            'canVote' => ! $own && ! $rosterChanged && $this->access->canVote($request->user()) && $report->status === 'under_review' && $round?->status === 'open' && $member && ! $member->vote,
            'committee' => $this->access->canForward($request->user()) ? $roster->map(fn ($user) => $user->only('id', 'name')) : [],
            'committeeFingerprint' => $this->access->canForward($request->user()) ? $this->access->fingerprint($roster->modelKeys()) : null,
        ]);
    }
    public function forward(ForwardReportRequest $request, Report $report, ManageReports $action): RedirectResponse
    {
        $action->forward($request->user(), $report, $request->validated());
        return to_route('reports.show', $report)->with('success', 'Rapport transmis à tous les membres de la commission. Chaque approbation est nécessaire.');
    }
    public function vote(VoteReportRequest $request, Report $report, ReportRound $round, ManageReports $action): RedirectResponse
    {
        $action->vote($request->user(), $report, $round, $request->validated());
        return to_route('reports.show', $report)->with('success', 'Votre avis personnel est enregistré. L’acceptation exige l’approbation de tous les membres.');
    }
    public function download(Request $request, Report $report, ReportVersion $version, ReportFiles $files): StreamedResponse
    {
        $this->access->reports($request->user())->findOrFail($report->id);
        abort_unless((int) $version->report_id === $report->id && $this->access->versionAllowed($request->user(), $version), 404);
        return $files->download($version);
    }
    public function ownDownload(Request $request, int $report, int $version, ReportFiles $files): StreamedResponse
    {
        $this->studentType($request);
        $owned = Report::whereHas('student', fn ($q) => $q->where('user_id', $request->user()->id))->findOrFail($report);
        return $files->download($owned->versions()->findOrFail($version));
    }
    private function studentType(Request $request): void { abort_unless($request->user()->types()->where('user_types.id', 1)->exists(), 403); }
    private function summary(Report $report): array
    {
        return [...$report->only('id', 'reference', 'title', 'status', 'revision', 'version_count'),
            'student' => $report->student->only('card_number', 'last_name', 'first_name'), 'year' => $report->enrollment->academicYear->only('id', 'name'),
            'latest_submitted_at' => $report->latest_submitted_at->toIso8601String()];
    }
    private function versions(Report $report, ?User $viewer = null)
    {
        return $report->versions()->with(['decision.reviewer', 'enrollment.academicYear', 'rounds.members.vote'])
            ->when($viewer && ! $viewer->can('reports.view'), fn ($q) => $q->whereHas('rounds.members', fn ($q) => $q->where('user_id', $viewer->id)))
            ->orderByDesc('number')->paginate(10)->withQueryString()->through(fn ($v) => [
                ...$v->only('id', 'number', 'title', 'size'), 'created_at' => $v->created_at->toIso8601String(), 'year' => $v->enrollment->academicYear->name,
                'legacy_decision' => $v->decision ? [...$v->decision->only('decision', 'comment'), 'created_at' => $v->decision->created_at->toIso8601String(), 'reviewer' => $v->decision->reviewer->name] : null,
                'rounds' => $v->rounds->sortByDesc('number')->values()->map(fn ($round) => $this->roundData($round, $viewer)),
            ]);
    }
    private function roundData(ReportRound $round, ?User $viewer): array
    {
        $round->loadMissing('members.vote');
        return [...$round->only('id', 'number', 'status', 'reason'), 'created_at' => $round->created_at->toIso8601String(),
            'total' => $round->members->count(), 'approved' => $round->members->filter(fn ($member) => $member->vote?->decision === 'approved')->count(),
            'members' => $round->members->map(fn ($member) => ['name' => $member->name_snapshot, 'is_you' => $viewer && (int) $member->user_id === $viewer->id,
                'decision' => $member->vote?->decision, 'comment' => $member->vote?->comment, 'decided_at' => $member->vote?->created_at->toIso8601String()]),
        ];
    }
}
