<?php
namespace App\Http\Controllers;

use App\Actions\Reports\ManageReports;
use App\Http\Requests\ReviewReportRequest;
use App\Http\Requests\SubmitReportRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Services\Eligibility;
use App\Services\ReportFiles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:150'], 'status' => ['nullable', Rule::in(Report::STATUSES)],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'], 'submitted_on' => ['nullable', 'date_format:Y-m-d'], 'page' => ['nullable', 'integer', 'min:1']]);
        $scope = Report::query()->when($filters['academic_year_id'] ?? null, fn ($q, $id) => $q->whereHas('enrollment', fn ($q) => $q->where('academic_year_id', $id)));
        $statistics = ['total' => (clone $scope)->count()];
        foreach (Report::STATUSES as $status) { $statistics[$status] = (clone $scope)->where('status', $status)->count(); }
        $search = trim($filters['search'] ?? '');
        $reports = $scope->with(['student', 'enrollment.academicYear'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['submitted_on'] ?? null, fn ($q, $date) => $q->whereDate('latest_submitted_at', $date))
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.$search.'%')->orWhere('reference', 'like', '%'.$search.'%')
                ->orWhereHas('student', fn ($q) => $q->where(fn ($q) => $q->where('last_name', 'like', '%'.$search.'%')->orWhere('first_name', 'like', '%'.$search.'%')->orWhere('card_number', 'like', '%'.$search.'%')))))
            ->orderByDesc('latest_submitted_at')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($report) => $this->summary($report));
        return Inertia::render('reports/index', ['reports' => $reports, 'statistics' => $statistics, 'filters' => $filters, 'years' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name'])]);
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
        $report->load(['student', 'enrollment.academicYear', 'latestVersion']);
        $result = $eligibility->evaluate($report->enrollment);
        return Inertia::render('reports/show', [
            'report' => $this->summary($report), 'versions' => $this->versions($report),
            'eligibility' => ['status' => $result['status'], 'criteria' => array_map(fn ($criterion) => $criterion['key'] === 'tuition' ? [...$criterion, 'detail' => 'La scolarité de l’inscription doit être entièrement réglée.'] : $criterion, $result['criteria']), 'context_valid' => $result['context_valid']],
            'documentAvailable' => $files->intact($report->latestVersion),
            'canReview' => $request->user()->can('reports.review') && $report->status === 'submitted'
                && (int) $report->latestVersion->submitted_by !== $request->user()->id && (int) $report->student->user_id !== $request->user()->id,
        ]);
    }
    public function review(ReviewReportRequest $request, Report $report, ManageReports $action): RedirectResponse
    {
        $action->review($request->user(), $report, $request->validated());
        return to_route('reports.show', $report)->with('success', $request->validated('decision') === 'approved' ? 'Approbation administrative enregistrée.' : 'Demande de correction enregistrée et visible par l’étudiant.');
    }
    public function download(Report $report, ReportVersion $version, ReportFiles $files): StreamedResponse
    {
        abort_unless((int) $version->report_id === $report->id, 404);
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
    private function versions(Report $report)
    {
        return $report->versions()->with(['decision.reviewer', 'enrollment.academicYear'])->orderByDesc('number')->paginate(10)->withQueryString()->through(fn ($v) => [
            ...$v->only('id', 'number', 'title', 'size'), 'created_at' => $v->created_at->toIso8601String(), 'year' => $v->enrollment->academicYear->name,
            'decision' => $v->decision ? [...$v->decision->only('decision', 'comment'), 'created_at' => $v->decision->created_at->toIso8601String(), 'reviewer' => $v->decision->reviewer->name] : null,
        ]);
    }
}
