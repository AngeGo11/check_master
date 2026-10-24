<?php
namespace App\Http\Controllers;
use App\Actions\Academic\ManageGrades;
use App\Http\Requests\GradeRequest;
use App\Models\{AcademicSemester, AcademicYear, Enrollment, GradeSheet, StudyLevel};
use App\Services\{AcademicAccess, GradeCalculator};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
class GradeController extends Controller
{
    public function index(Request $r) { abort_unless(app(AcademicAccess::class)->canView($r->user()), 403); return $this->listing($r, false); }
    public function own(Request $r) { app(AcademicAccess::class)->own($r->user(), 'grades.self'); return $this->listing($r, true); }
    private function listing(Request $r, bool $personal)
    {
        $f = $r->validate(['academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'], 'level_id' => ['nullable', 'integer', 'exists:study_levels,id'], 'search' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1']]);
        $q = Enrollment::with(['student', 'academicYear', 'studyLevel']);
        if ($personal) { $q->whereHas('student', fn ($q) => $q->where('user_id', $r->user()->id)); }
        if ($f['academic_year_id'] ?? null) { $q->where('academic_year_id', $f['academic_year_id']); }
        if ($f['level_id'] ?? null) { $q->where('study_level_id', $f['level_id']); }
        if ($search = trim($f['search'] ?? '')) { $q->whereHas('student', fn ($q) => $q->where(fn ($q) => $q->where('last_name', 'like', '%'.$search.'%')->orWhere('first_name', 'like', '%'.$search.'%')->orWhere('card_number', 'like', '%'.$search.'%'))); }
        $ids = (clone $q)->select('enrollments.id'); $sheets = GradeSheet::whereIn('enrollment_id', $ids);
        $stats = $personal ? null : ['enrollments' => (clone $q)->count(), 'draft' => (clone $sheets)->where('status', 'draft')->count(), 'published' => (clone $sheets)->whereHas('publications')->count(), 'awaiting' => (clone $q)->whereNotIn('id', GradeSheet::select('enrollment_id'))->count()];
        $page = $q->orderByDesc('academic_year_id')->orderByDesc('id')->paginate(20)->withQueryString();
        $visible = GradeSheet::with(['semester', 'latestPublication'])->whereIn('enrollment_id', $page->getCollection()->modelKeys())->when($personal, fn ($q) => $q->whereHas('publications'))->get()->groupBy('enrollment_id');
        $page->through(fn ($e) => [...$this->enrollment($e), 'sheets' => ($visible[$e->id] ?? collect())->map(fn ($s) => ['id' => $s->id, 'semester' => $s->semester->name, 'status' => $personal ? 'published' : $s->status, 'average' => $s->latestPublication?->result['average'], 'decision' => $s->latestPublication?->decision])]);
        return Inertia::render('grades/index', ['enrollments' => $page, 'statistics' => $stats, 'personal' => $personal, 'canManage' => ! $personal && app(AcademicAccess::class)->canManage($r->user()), 'filters' => $f,
            'semesters' => AcademicSemester::orderBy('id')->get(['id', 'name', 'study_level_id']), 'years' => $personal ? [] : AcademicYear::orderByDesc('starts_on')->get(['id', 'name']), 'levels' => $personal ? [] : StudyLevel::get(['id', 'name'])]);
    }
    public function show(Request $r, GradeSheet $sheet) { abort_unless(app(AcademicAccess::class)->canView($r->user()), 403); return $this->detail($r, $sheet, false); }
    public function ownShow(Request $r, GradeSheet $sheet)
    {
        app(AcademicAccess::class)->own($r->user(), 'grades.self'); abort_unless((int) $sheet->enrollment->student->user_id === $r->user()->id && $sheet->publications()->exists(), 404); return $this->detail($r, $sheet, true);
    }
    private function detail(Request $r, GradeSheet $s, bool $personal)
    {
        $data = $r->validate(['publication' => ['nullable', 'integer', 'min:1']]);
        $publication = isset($data['publication']) ? $s->publications()->whereKey($data['publication'])->firstOrFail() : ($personal || $s->status === 'published' ? $s->latestPublication : null);
        $canManage = ! $personal && ! isset($data['publication']) && app(AcademicAccess::class)->canManage($r->user()) && (int) $s->enrollment->student->user_id !== $r->user()->id;
        $result = $publication?->result ?? app(GradeCalculator::class)->calculate($s->program->units, $s->marks);
        return Inertia::render('grades/show', ['sheet' => ['id' => $s->id, 'revision' => $personal ? null : $s->revision, 'status' => $s->status, 'semester' => $s->semester->name, 'program_number' => $s->program->number, 'enrollment' => $this->enrollment($s->enrollment)],
            'result' => $result, 'publication' => $publication ? $this->publication($publication) : null, 'publications' => $s->publications()->orderByDesc('number')->get()->map(fn ($p) => $this->publication($p)),
            'marks' => $canManage && $s->status === 'draft' ? collect($s->marks)->map(fn ($v) => $v === null ? '' : number_format($v / 100, 2, '.', '')) : null, 'personal' => $personal, 'canManage' => $canManage,
            'canClaim' => $personal && app(\App\Services\ClaimAccess::class)->personal($r->user()) && ! $s->enrollment->cancelled_at && ! $s->enrollment->student->is_archived,
            'activity' => $personal ? [] : DB::table('academic_audits')->where('enrollment_id', $s->enrollment_id)->where('teaching_program_id', $s->teaching_program_id)->orderByDesc('id')->limit(30)->get(['id', 'action', 'created_at'])]);
    }
    public function store(GradeRequest $r, ManageGrades $a) { $s = $a->create($r->user(), $r->validated()); return to_route('grades.show', $s); }
    public function update(GradeRequest $r, GradeSheet $sheet, ManageGrades $a) { $a->save($r->user(), $sheet, $r->validated()); return back()->with('success', 'Notes enregistrées en brouillon.'); }
    public function publish(GradeRequest $r, GradeSheet $sheet, ManageGrades $a) { $a->publish($r->user(), $sheet, $r->validated()); return back()->with('success', 'Bulletin publié dans l’espace étudiant.'); }
    public function reopen(GradeRequest $r, GradeSheet $sheet, ManageGrades $a) { $a->reopen($r->user(), $sheet, $r->validated()); return back()->with('success', 'Correction ouverte. La dernière publication reste consultable par l’étudiant.'); }
    private function enrollment(Enrollment $e): array { return [...$e->only('id', 'reference', 'study_level_id'), 'student' => $e->student->last_name.' '.$e->student->first_name, 'card_number' => $e->student->card_number, 'year' => $e->academicYear->name, 'level' => $e->studyLevel->name, 'cancelled' => $e->cancelled_at !== null]; }
    private function publication($p): array { return [...$p->only('id', 'number', 'decision', 'comment'), 'created_at' => $p->created_at->toIso8601String()]; }
}
