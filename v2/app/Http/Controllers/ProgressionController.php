<?php
namespace App\Http\Controllers;
use App\Actions\Progression\ManageProgression;
use App\Http\Requests\ProgressionRequest;
use App\Models\{AcademicYear, AnnualDecision, Enrollment, RetakeFee, StudyLevel};
use App\Services\{AnnualProgression, ProgressionAccess};
use Illuminate\Http\Request;
use Illuminate\Support\{Arr, Str};
use Illuminate\Validation\Rule;
use Inertia\Inertia;
class ProgressionController extends Controller
{
    public function index(Request $r) { return $this->listing($r, false); }
    public function own(Request $r) { return $this->listing($r, true); }
    private function listing(Request $r, bool $personal)
    {
        $filters = $r->validate(['search' => ['nullable', 'string', 'max:150'], 'year_id' => ['nullable', 'integer', 'min:1'], 'outcome' => ['nullable', Rule::in(['pending', ...array_keys(AnnualDecision::OUTCOMES)])], 'page' => ['nullable', 'integer', 'min:1']]);
        $q = app(ProgressionAccess::class)->query($r->user(), $personal);
        $years = AcademicYear::whereIn('id', (clone $q)->select('academic_year_id'))->orderByDesc('starts_on')->get(['id', 'name']);
        if ($filters['year_id'] ?? null) { $q->where('academic_year_id', $filters['year_id']); }
        if ($s = trim($filters['search'] ?? '')) { $q->whereHas('student', fn ($q) => $q->where(fn ($q) => $q->where('last_name', 'like', '%'.$s.'%')->orWhere('first_name', 'like', '%'.$s.'%')->orWhere('card_number', 'like', '%'.$s.'%'))); }
        if (($filters['outcome'] ?? null) === 'pending') { $q->whereDoesntHave('annualDecisions'); }
        elseif ($filters['outcome'] ?? null) { $q->whereHas('latestAnnualDecision', fn ($q) => $q->where('outcome', $filters['outcome'])); }
        $stats = ['total' => (clone $q)->count(), 'pending' => (clone $q)->whereDoesntHave('annualDecisions')->count(), 'conditional' => (clone $q)->whereHas('latestAnnualDecision', fn ($q) => $q->where('outcome', 'conditional'))->count(), 'retakes' => (clone $q)->whereHas('retakes', fn ($q) => $q->where('status', '!=', 'validated'))->count()];
        $rows = $q->with(['student', 'academicYear', 'studyLevel', 'latestAnnualDecision'])->withCount(['retakes as pending_retakes' => fn ($q) => $q->where('status', '!=', 'validated')])->orderByDesc('academic_year_id')->orderByDesc('id')->paginate(20)->withQueryString()
            ->through(fn ($e) => [...$this->enrollment($e), 'outcome' => $e->latestAnnualDecision?->outcome, 'pending_retakes' => $e->pending_retakes]);
        return $this->page($r, 'progression/index', ['enrollments' => $rows, 'filters' => $filters, 'years' => $years, 'statistics' => $stats, 'personal' => $personal]);
    }
    public function show(Request $r, int $enrollment) { return $this->detail($r, $enrollment, false); }
    public function ownShow(Request $r, int $enrollment) { return $this->detail($r, $enrollment, true); }
    private function detail(Request $r, int $id, bool $personal)
    {
        $history = $r->validate(['history_page' => ['nullable', 'integer', 'min:1'], 'retake_id' => ['nullable', 'integer', 'min:1'], 'retake_page' => ['nullable', 'integer', 'min:1']]);
        $access = app(ProgressionAccess::class); $e = $access->query($r->user(), $personal)->with(['student', 'academicYear', 'studyLevel'])->findOrFail($id);
        $service = app(AnnualProgression::class); $summary = $service->build($e); $latest = $e->latestAnnualDecision; $next = $latest?->nextEnrollment;
        $editable = ! $personal && $access->canEdit($r->user(), $e); $year = AcademicYear::where('status', 'current')->first();
        $validYear = $year && $year->starts_on->gt($e->academicYear->ends_on);
        $quote = $latest && $validYear ? $service->quote($latest, $year) : null;
        $used = Enrollment::whereIn('source_annual_decision_id', $e->annualDecisions()->select('id'))->exists();
        $stale = $latest && ! $used && ! hash_equals($latest->source_fingerprint, $summary['fingerprint']);
        if ($history['retake_id'] ?? null) { abort_unless($e->retakes()->where('retake_subjects.id', $history['retake_id'])->exists(), 404); }
        $retakes = $e->retakes()->with(['sourceLevel', 'semester'])->orderBy('retake_subjects.id')->get()->map(fn ($s) => [
            ...$s->only('id', 'name', 'subject_key', 'credits', 'status', 'revision'), 'score' => $s->score === null ? null : $s->score / 100,
            'level' => $s->sourceLevel->name, 'semester' => $s->semester->name, 'amount' => (int) $s->pivot->amount,
            'events' => $s->events()->orderByDesc('id')->paginate(20, ['*'], 'retake_page', (int) ($history['retake_id'] ?? 0) === $s->id ? (int) ($history['retake_page'] ?? 1) : 1)->appends(['retake_id' => $s->id])->through(fn ($event) => [...$event->only('id', 'actor_name', 'from_status', 'to_status', 'reference', 'reason'), 'score' => $event->score === null ? null : $event->score / 100, 'created_at' => $event->created_at->toIso8601String()]),
            'history_count' => $s->events()->count(), 'operation_key' => $editable ? (string) Str::uuid() : null,
        ]);
        $decisions = $e->annualDecisions()->orderByDesc('number')->paginate(10, ['*'], 'history_page')->withQueryString()->through(fn ($d) => [
            ...$d->only('id', 'number', 'outcome', 'reason', 'reference', 'actor_name', 'partial_confirmed'), 'created_at' => $d->created_at->toIso8601String(),
            'snapshot' => Arr::except($d->snapshot, ['fingerprint']), 'selected_subjects' => $d->selected_subjects,
        ]);
        return $this->page($r, 'progression/show', ['enrollment' => $this->enrollment($e), 'summary' => $personal ? Arr::except($summary, ['fingerprint']) : $summary,
            'decisions' => $decisions, 'latest' => $latest ? ['id' => $latest->id, 'number' => $latest->number, 'outcome' => $latest->outcome] : null, 'retakes' => $retakes,
            'canDecide' => $editable && ! $used, 'canRetake' => $editable && $e->academicYear->status === 'current', 'canRegister' => $editable && $r->user()->can('enrollments.manage') && $latest && ! $used && ! $stale && $quote && $quote['total'] !== null && $quote['level_id'] !== null,
            'personal' => $personal, 'stale' => (bool) $stale, 'quote' => ! $personal ? $quote : null, 'used' => $used,
            'nextEnrollment' => $next ? ['id' => $next->id, 'reference' => $next->reference] : null,
            'feeSnapshot' => $e->progression_data ? Arr::only($e->progression_data, ['base', 'unit_amount', 'count', 'total']) : null,
            'operationKey' => $editable ? (string) Str::uuid() : null, 'today' => today()->format('Y-m-d')]);
    }
    public function publish(ProgressionRequest $r, Enrollment $enrollment, ManageProgression $a) { $a->publish($r->user(), $enrollment, $r->validated()); return back()->with('success', 'Décision annuelle publiée et conservée dans l’historique.'); }
    public function register(ProgressionRequest $r, AnnualDecision $decision, ManageProgression $a) { $e = $a->register($r->user(), $decision, $r->validated()); return redirect('/enrollments/'.$e->id)->with('success', 'Réinscription créée avec son tarif et ses matières à reprendre.'); }
    public function retake(ProgressionRequest $r, Enrollment $enrollment, int $subject, ManageProgression $a) { $a->updateRetake($r->user(), $enrollment, $subject, $r->validated()); return back()->with('success', 'Résultat de rattrapage consigné. Le montant initial reste conservé.'); }
    public function fees(Request $r)
    {
        abort_unless(app(ProgressionAccess::class)->fees($r->user()), 403);
        return $this->page($r, 'progression/fees', ['years' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'status']), 'levels' => StudyLevel::get(['id', 'name']),
            'fees' => RetakeFee::with(['academicYear', 'studyLevel'])->get()->map(fn ($f) => [...$f->only('id', 'academic_year_id', 'study_level_id', 'amount', 'revision'), 'year' => $f->academicYear->name, 'status' => $f->academicYear->status, 'level' => $f->studyLevel->name])]);
    }
    public function saveFee(ProgressionRequest $r, ManageProgression $a) { $a->saveFee($r->user(), $r->validated()); return back()->with('success', 'Tarif enregistré pour les prochaines réinscriptions.'); }
    private function enrollment(Enrollment $e): array { return [...$e->only('id', 'reference', 'study_level_id', 'student_status_id'), 'student' => $e->student->last_name.' '.$e->student->first_name, 'card_number' => $e->student->card_number, 'year' => $e->academicYear->name, 'year_status' => $e->academicYear->status, 'level' => $e->studyLevel->name, 'cancelled' => (bool) $e->cancelled_at, 'archived' => $e->student->is_archived]; }
    private function page(Request $r, string $name, array $data) { return Inertia::render($name, $data)->toResponse($r)->header('Cache-Control', 'private, no-store'); }
}
