<?php

namespace App\Http\Controllers;

use App\Actions\Schooling\ManageEnrollments;
use App\Http\Requests\ChangeEnrollmentStatusRequest;
use App\Http\Requests\CreateEnrollmentRequest;
use App\Http\Requests\RecordPaymentRequest;
use App\Http\Requests\RemoveInternshipRequest;
use App\Http\Requests\ReviewEligibilityRequest;
use App\Http\Requests\SaveInternshipRequest;
use App\Http\Requests\VoidPaymentRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\StudyLevel;
use App\Models\TuitionFee;
use App\Models\TuitionPayment;
use App\Services\Eligibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class EnrollmentController extends Controller
{
    private const PAID = '(select coalesce(sum(amount), 0) from tuition_payments where tuition_payments.enrollment_id = enrollments.id and voided_at is null)';

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'], 'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'study_level_id' => ['nullable', 'integer', 'exists:study_levels,id'],
            'payment_status' => ['nullable', Rule::in(['paid', 'partial', 'unpaid'])],
            'enrolled_on' => ['nullable', 'date_format:Y-m-d'], 'include_cancelled' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $yearId = $filters['academic_year_id'] ?? AcademicYear::where('status', 'current')->value('id');
        $filters['academic_year_id'] = $yearId;
        $scope = Enrollment::where('academic_year_id', $yearId)->whereNull('cancelled_at');
        $statistics = [
            'total' => (clone $scope)->count(), 'paid' => (clone $scope)->whereRaw(self::PAID.' >= amount_due')->count(),
            'partial' => (clone $scope)->whereRaw(self::PAID.' > 0 and '.self::PAID.' < amount_due')->count(),
            'unpaid' => (clone $scope)->whereRaw(self::PAID.' = 0')->count(),
        ];
        $search = trim($filters['search'] ?? '');
        $query = Enrollment::with(['student', 'academicYear', 'studyLevel'])->withPaidAmount()->where('academic_year_id', $yearId)
            ->when(! ($filters['include_cancelled'] ?? false), fn ($q) => $q->whereNull('cancelled_at'))
            ->when($filters['study_level_id'] ?? null, fn ($q, $id) => $q->where('study_level_id', $id))
            ->when($filters['enrolled_on'] ?? null, fn ($q, $date) => $q->whereDate('enrolled_on', $date))
            ->when($search !== '', fn ($q) => $q->whereHas('student', function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    foreach (['last_name', 'first_name', 'card_number'] as $field) { $q->orWhere($field, 'like', '%'.$search.'%'); }
                });
            }));
        if ($filters['payment_status'] ?? null) {
            $query->whereRaw(match ($filters['payment_status']) {
                'paid' => self::PAID.' >= amount_due', 'partial' => self::PAID.' > 0 and '.self::PAID.' < amount_due', default => self::PAID.' = 0',
            });
        }
        return Inertia::render('enrollments/index', [
            'enrollments' => $query->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($row) => $this->summary($row)),
            'filters' => $filters, 'statistics' => $statistics, 'personal' => false,
            'years' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name', 'status']), 'levels' => StudyLevel::orderBy('id')->get(['id', 'name']),
        ]);
    }

    public function own(Request $request): Response
    {
        $this->studentType($request);
        $request->validate(['page' => ['nullable', 'integer', 'min:1']]);
        return Inertia::render('enrollments/index', [
            'enrollments' => $this->owned($request)->with(['student', 'academicYear', 'studyLevel'])->withPaidAmount()->orderByDesc('id')->paginate(20)->through(fn ($row) => $this->summary($row)),
            'filters' => [], 'statistics' => null, 'personal' => true, 'years' => [], 'levels' => [],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('enrollments/create', [
            'years' => AcademicYear::where('status', 'current')->get(['id', 'name']), 'levels' => StudyLevel::orderBy('id')->get(['id', 'name']),
            'fees' => TuitionFee::whereHas('academicYear', fn ($q) => $q->where('status', 'current'))->get(['academic_year_id', 'study_level_id', 'amount']),
            'today' => today()->format('Y-m-d'),
        ]);
    }
    public function store(CreateEnrollmentRequest $request, ManageEnrollments $action): RedirectResponse
    {
        return to_route('enrollments.show', $action->create($request->user(), $request->validated()))->with('success', 'Inscription enregistrée.');
    }
    public function show(Enrollment $enrollment): Response { return $this->detail($enrollment, false); }
    public function ownShow(Request $request, int $enrollment): Response
    {
        $this->studentType($request);
        return $this->detail($this->owned($request)->findOrFail($enrollment), true);
    }
    public function recordPayment(RecordPaymentRequest $request, Enrollment $enrollment, ManageEnrollments $action): RedirectResponse
    {
        $payment = $action->recordPayment($request->user(), $enrollment, $request->validated());
        return to_route('enrollments.show', $enrollment)->with('success', 'Écriture enregistrée : '.$payment->receipt_number.'.');
    }
    public function voidPayment(VoidPaymentRequest $request, Enrollment $enrollment, TuitionPayment $payment, ManageEnrollments $action): RedirectResponse
    {
        $action->voidPayment($request->user(), $enrollment, $payment, $request->validated());
        return to_route('enrollments.show', $enrollment)->with('success', 'Écriture annulée et conservée dans l’historique. Aucun remboursement effectué.');
    }
    public function changeStatus(ChangeEnrollmentStatusRequest $request, Enrollment $enrollment, ManageEnrollments $action): RedirectResponse
    {
        $action->changeStatus($request->user(), $enrollment, $request->validated());
        return to_route('enrollments.show', $enrollment)->with('success', 'Statut de l’inscription mis à jour.');
    }
    public function saveInternship(SaveInternshipRequest $request, Enrollment $enrollment, ManageEnrollments $action): RedirectResponse
    {
        $action->saveInternship($request->user(), $enrollment, $request->validated());
        return redirect('/my-enrollments/'.$enrollment->id)->with('success', 'Votre stage est déclaré. Votre éligibilité au dépôt est actualisée immédiatement.');
    }
    public function removeInternship(RemoveInternshipRequest $request, Enrollment $enrollment, ManageEnrollments $action): RedirectResponse
    {
        $action->removeInternship($request->user(), $enrollment, $request->validated());
        return redirect('/my-enrollments/'.$enrollment->id)->with('success', 'Déclaration retirée. Votre éligibilité a été recalculée.');
    }
    public function review(ReviewEligibilityRequest $request, Enrollment $enrollment, ManageEnrollments $action): RedirectResponse
    {
        $action->review($request->user(), $enrollment, $request->validated());
        return to_route('enrollments.show', $enrollment)->with('success', 'Examen des critères enregistré.');
    }
    public function receipt(Enrollment $enrollment, TuitionPayment $payment): Response
    {
        abort_unless((int) $payment->enrollment_id === $enrollment->id, 404);
        return $this->receiptPage($enrollment, $payment, false);
    }
    public function ownReceipt(Request $request, int $enrollment, int $payment): Response
    {
        $this->studentType($request);
        $record = $this->owned($request)->findOrFail($enrollment);
        return $this->receiptPage($record, $record->payments()->findOrFail($payment), true);
    }
    private function studentType(Request $request): void { abort_unless($request->user()->types()->where('user_types.id', 1)->exists(), 403); }
    private function owned(Request $request) { return Enrollment::whereHas('student', fn ($q) => $q->where('user_id', $request->user()->id)); }
    private function summary(Enrollment $enrollment): array
    {
        return [...$enrollment->only('id', 'reference', 'amount_due'),
            'student' => $enrollment->student->only('id', 'card_number', 'last_name', 'first_name', 'is_archived'),
            'year' => $enrollment->academicYear->only('id', 'name', 'status'), 'level' => $enrollment->studyLevel->only('id', 'name'),
            'enrolled_on' => $enrollment->enrolled_on->format('Y-m-d'), 'cancelled' => (bool) $enrollment->cancelled_at,
            'paid_amount' => $enrollment->paidAmount(), 'balance' => max(0, $enrollment->amount_due - $enrollment->paidAmount()), 'payment_status' => $enrollment->paymentStatus(),
        ];
    }
    private function detail(Enrollment $enrollment, bool $personal): Response
    {
        $enrollment->load(['student', 'academicYear', 'studyLevel', 'internship', 'latestReview']);
        $eligibility = Arr::except(app(Eligibility::class)->evaluate($enrollment), ['fingerprint']);
        $stage = $enrollment->internship;
        return Inertia::render('enrollments/show', [
            'enrollment' => [...$this->summary($enrollment), ...$enrollment->only('revision', 'cancellation_reason'),
                'progression' => $enrollment->source_annual_decision_id !== null,
                'editable' => $eligibility['context_valid'] && (int) $enrollment->study_level_id === (int) $enrollment->student->study_level_id],
            'payments' => $enrollment->payments()->orderByDesc('id')->get()->map(fn ($p) => $this->paymentData($p)),
            'internship' => $stage ? [...$stage->only('company', 'title', 'evidence_reference'), 'starts_on' => $stage->starts_on->format('Y-m-d'), 'ends_on' => $stage->ends_on->format('Y-m-d')] : null,
            'eligibility' => $eligibility, 'personal' => $personal, 'operationKey' => $personal ? null : (string) Str::uuid(), 'today' => today()->format('Y-m-d'),
        ]);
    }
    private function paymentData(TuitionPayment $payment): array
    {
        return [...$payment->only('id', 'receipt_number', 'amount', 'method', 'cheque_number', 'purpose', 'void_reason'),
            'paid_on' => $payment->paid_on->format('Y-m-d'), 'voided_at' => $payment->voided_at?->toIso8601String()];
    }
    private function receiptPage(Enrollment $enrollment, TuitionPayment $payment, bool $personal): Response
    {
        $enrollment->load(['student', 'academicYear', 'studyLevel']);
        return Inertia::render('enrollments/receipt', ['enrollment' => $this->summary($enrollment), 'payment' => $this->paymentData($payment), 'personal' => $personal]);
    }
}
