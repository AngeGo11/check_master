<?php
namespace App\Http\Controllers;
use App\Actions\Academic\ManagePrograms;
use App\Http\Requests\ProgramRequest;
use App\Models\{AcademicSemester, AcademicYear, TeachingProgram};
use App\Services\{AcademicAccess, GradeCalculator};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
class ProgramController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(app(AcademicAccess::class)->canView($request->user()), 403);
        $filters = $request->validate(['academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'], 'semester_id' => ['nullable', 'integer', 'exists:academic_semesters,id'], 'page' => ['nullable', 'integer', 'min:1']]);
        $q = TeachingProgram::with(['semester.studyLevel', 'academicYear'])->when($filters['academic_year_id'] ?? null, fn ($q, $id) => $q->where('academic_year_id', $id))->when($filters['semester_id'] ?? null, fn ($q, $id) => $q->where('academic_semester_id', $id));
        return Inertia::render('grades/programs', ['programs' => $q->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($p) => $this->summary($p)), 'years' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'status']), 'semesters' => AcademicSemester::with('studyLevel')->get()->map(fn ($s) => ['id' => $s->id, 'name' => $s->name, 'level' => $s->studyLevel->name]), 'canManage' => app(AcademicAccess::class)->canCatalog($request->user()), 'filters' => $filters, 'operationKey' => (string) Str::uuid()]);
    }
    public function show(Request $request, TeachingProgram $program)
    {
        abort_unless(app(AcademicAccess::class)->canView($request->user()), 403);
        return Inertia::render('grades/program', ['program' => [...$this->summary($program), ...$program->only('revision', 'units', 'review_note')], 'warnings' => app(GradeCalculator::class)->warnings($program->units), 'canManage' => app(AcademicAccess::class)->canCatalog($request->user())]);
    }
    public function store(ProgramRequest $request, ManagePrograms $action) { $p = $action->create($request->user(), $request->validated()); return to_route('programs.show', $p)->with('success', 'Programme préparé. Vérifiez les matières et les crédits avant publication.'); }
    public function update(ProgramRequest $request, TeachingProgram $program, ManagePrograms $action) { $action->save($request->user(), $program, $request->validated()); return back()->with('success', 'Brouillon du programme enregistré.'); }
    public function publish(ProgramRequest $request, TeachingProgram $program, ManagePrograms $action) { $action->publish($request->user(), $program, $request->validated()); return back()->with('success', 'Programme publié pour les prochains bulletins. Les bulletins existants conservent leur programme.'); }
    private function summary(TeachingProgram $p): array { return [...$p->only('id', 'number', 'status', 'academic_year_id', 'academic_semester_id'), 'year' => $p->academicYear->name, 'semester' => $p->semester->name, 'level' => $p->semester->studyLevel->name, 'unit_count' => count($p->units), 'credits' => array_sum(array_column($p->units, 'credits'))]; }
}
