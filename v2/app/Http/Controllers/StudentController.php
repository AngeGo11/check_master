<?php

namespace App\Http\Controllers;

use App\Actions\Students\ManageStudents;
use App\Http\Requests\LinkStudentAccountRequest;
use App\Http\Requests\SaveStudentRequest;
use App\Models\Promotion;
use App\Models\Student;
use App\Models\StudentStatus;
use App\Models\StudyLevel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'study_level_id' => ['nullable', 'integer', 'exists:study_levels,id'],
            'promotion_id' => ['nullable', 'integer', 'exists:promotions,id'],
            'student_status_id' => ['nullable', 'integer', 'exists:student_statuses,id'],
            'archive' => ['nullable', Rule::in(['active', 'archived', 'all'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $search = trim($filters['search'] ?? '');
        $archive = $filters['archive'] ?? 'active';
        $students = Student::with(['studyLevel', 'promotion', 'studentStatus'])
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                foreach (['last_name', 'first_name', 'card_number', 'email'] as $field) {
                    $query->orWhere($field, 'like', '%'.$search.'%');
                }
                $query->orWhereHas('promotion', fn ($q) => $q->where('name', 'like', '%'.$search.'%'));
            }))
            ->when($archive !== 'all', fn ($q) => $q->where('is_archived', $archive === 'archived'))
            ->when($filters['study_level_id'] ?? null, fn ($q, $id) => $q->where('study_level_id', $id))
            ->when($filters['promotion_id'] ?? null, fn ($q, $id) => $q->where('promotion_id', $id))
            ->when($filters['student_status_id'] ?? null, fn ($q, $id) => $q->where('student_status_id', $id))
            ->orderBy('last_name')->orderBy('first_name')->orderBy('id')->paginate(20)->withQueryString()
            ->through(fn (Student $student) => $this->summary($student));

        return Inertia::render('students/index', [
            'students' => $students, 'filters' => [...$filters, 'archive' => $archive], ...$this->options(),
            'statistics' => ['total' => Student::count(), 'archived' => Student::where('is_archived', true)->count()],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('students/form', ['student' => null, ...$this->options()]);
    }

    public function store(SaveStudentRequest $request, ManageStudents $students): RedirectResponse
    {
        $student = $students->saveStudent($request->user(), $request->validated());

        return to_route('students.show', $student)->with('success', 'Dossier étudiant créé.');
    }

    public function show(Request $request, Student $student): Response
    {
        $student->load(['studyLevel', 'promotion', 'studentStatus']);
        $canLink = $request->user()->can('students.manage') && $request->user()->can('users.manage');

        return Inertia::render('students/show', [
            'student' => $this->detail($student), 'personal' => false, 'canLink' => $canLink,
            'account' => $canLink ? $student->user?->only('id', 'name', 'email', 'is_active') : null,
        ]);
    }

    public function own(Request $request): Response
    {
        // The URL has no student identifier. A login email alone never establishes ownership.
        abort_unless($request->user()->types()->where('user_types.id', 1)->exists(), 403);
        $student = Student::with(['studyLevel', 'promotion', 'studentStatus'])->where('user_id', $request->user()->id)->first();

        return Inertia::render('students/show', [
            'student' => $student ? $this->detail($student) : null, 'personal' => true, 'canLink' => false, 'account' => null,
        ]);
    }

    public function edit(Student $student): Response
    {
        return Inertia::render('students/form', [
            'student' => [...$student->only('id', 'revision', ...Student::FIELDS), 'date_of_birth' => $student->date_of_birth?->format('Y-m-d')],
            ...$this->options(),
        ]);
    }

    public function update(SaveStudentRequest $request, Student $student, ManageStudents $students): RedirectResponse
    {
        $students->saveStudent($request->user(), $request->validated(), $student);

        return to_route('students.show', $student)->with('success', 'Dossier étudiant mis à jour.');
    }

    public function linkAccount(LinkStudentAccountRequest $request, Student $student, ManageStudents $students): RedirectResponse
    {
        $data = $request->validated();
        $students->linkAccount($request->user(), $student, $data['account_email'], (int) $data['revision']);

        return to_route('students.show', $student)->with('success', $data['account_email'] ? 'Compte rattaché au dossier.' : 'Rattachement du compte retiré.');
    }

    private function summary(Student $student): array
    {
        return [
            ...$student->only('id', 'card_number', 'last_name', 'first_name', 'email', 'is_archived'),
            'study_level' => $student->studyLevel->only('id', 'name'),
            'promotion' => $student->promotion->only('id', 'name'),
            'student_status' => $student->studentStatus->only('id', 'name'),
        ];
    }

    private function detail(Student $student): array
    {
        return [...$this->summary($student), ...$student->only('revision', 'gender'), 'date_of_birth' => $student->date_of_birth?->format('Y-m-d')];
    }

    private function options(): array
    {
        return [
            'levels' => StudyLevel::orderBy('id')->get(['id', 'name']),
            'promotions' => Promotion::orderByDesc('name')->get(['id', 'name']),
            'statuses' => StudentStatus::orderBy('id')->get(['id', 'name']),
        ];
    }
}
