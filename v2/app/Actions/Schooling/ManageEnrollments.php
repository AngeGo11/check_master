<?php

namespace App\Actions\Schooling;

use App\Models\AcademicYear;
use App\Models\EligibilityReview;
use App\Models\Enrollment;
use App\Models\EnrollmentInternship;
use App\Models\Student;
use App\Models\TuitionFee;
use App\Models\TuitionPayment;
use App\Models\User;
use App\Services\Eligibility;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageEnrollments extends SchoolingAction
{
    public function create(User $actor, array $data): Enrollment
    {
        return $this->transact($actor, 'enrollments.manage', function () use ($actor, $data) {
            $student = Student::where('card_number', $data['card_number'])->lockForUpdate()->firstOrFail();
            if (\App\Models\AnnualDecision::whereHas('enrollment', fn ($q) => $q->where('student_id', $student->id))->exists()) { throw ValidationException::withMessages(['card_number' => 'Utilisez la réinscription depuis la dernière décision du parcours annuel.']); }
            $year = AcademicYear::findOrFail($data['academic_year_id']);
            $this->available($student, $year, (int) $data['study_level_id']);
            $date = Carbon::parse($data['enrolled_on']);
            if ($date->gt(today()) || $date->lt($year->starts_on) || $date->gt($year->ends_on)) {
                throw ValidationException::withMessages(['enrolled_on' => 'La date doit appartenir à l’année académique et ne pas être future.']);
            }
            $fee = TuitionFee::where('academic_year_id', $year->id)->where('study_level_id', $data['study_level_id'])->first();
            if (! $fee) { throw ValidationException::withMessages(['study_level_id' => 'Configurez d’abord le tarif de ce niveau pour cette année.']); }
            if (Enrollment::where('student_id', $student->id)->where('academic_year_id', $year->id)->where('study_level_id', $data['study_level_id'])->exists()) {
                throw ValidationException::withMessages(['card_number' => 'Cette inscription existe déjà, éventuellement annulée. Consultez sa fiche.']);
            }
            $enrollment = new Enrollment;
            $enrollment->forceFill(['reference' => 'REG-'.Str::ulid(), 'student_id' => $student->id, 'academic_year_id' => $year->id,
                'study_level_id' => $data['study_level_id'], 'tuition_fee_id' => $fee->id, 'student_status_id' => $student->student_status_id,
                'amount_due' => $fee->amount, 'enrolled_on' => $data['enrolled_on']])->save();
            $this->audit($actor, 'enrollment.created', $enrollment->id, ['fee_id' => $fee->id, 'amount_due' => $fee->amount]);
            return $enrollment;
        });
    }

    public function recordPayment(User $actor, Enrollment $target, array $data): TuitionPayment
    {
        return $this->transact($actor, 'payments.manage', function () use ($actor, $target, $data) {
            $enrollment = $this->lock($target);
            $existing = TuitionPayment::where('operation_key', $data['operation_key'])->first();
            if ($existing) {
                $matches = (int) $existing->enrollment_id === $enrollment->id && (int) $existing->recorded_by === $actor->id
                    && $existing->amount === (int) $data['amount'] && $existing->method === $data['method']
                    && $existing->cheque_number === ($data['cheque_number'] ?? null) && $existing->purpose === $data['purpose']
                    && $existing->paid_on->format('Y-m-d') === $data['paid_on'];
                if (! $matches) { throw ValidationException::withMessages(['operation_key' => 'Cette référence de saisie a déjà été utilisée. Rechargez la page.']); }
                return $existing; // Retries never create another payment, even if the first has since been voided.
            }
            $this->revision($enrollment->revision, (int) $data['revision']);
            $this->active($enrollment);
            $amount = (int) $data['amount'];
            if ($amount <= 0 || $amount > $enrollment->amount_due - $enrollment->paidAmount()) {
                throw ValidationException::withMessages(['amount' => 'Le montant doit être positif et ne pas dépasser le reste à payer.']);
            }
            $date = Carbon::parse($data['paid_on']);
            if ($date->lt($enrollment->enrolled_on) || $date->gt(today()) || $date->gt($enrollment->academicYear->ends_on)) {
                throw ValidationException::withMessages(['paid_on' => 'Choisissez une date entre l’inscription et la fin de l’année, sans dépasser aujourd’hui.']);
            }
            $payment = new TuitionPayment;
            $payment->forceFill([...Arr::only($data, ['operation_key', 'amount', 'method', 'cheque_number', 'purpose', 'paid_on']),
                'enrollment_id' => $enrollment->id, 'recorded_by' => $actor->id, 'receipt_number' => 'REC-'.Str::ulid()])->save();
            $this->bump($enrollment);
            $this->audit($actor, 'payment.recorded', $enrollment->id, ['payment_id' => $payment->id, 'amount' => $amount]);
            return $payment;
        });
    }

    public function voidPayment(User $actor, Enrollment $target, TuitionPayment $targetPayment, array $data): void
    {
        $this->transact($actor, 'payments.manage', function () use ($actor, $target, $targetPayment, $data) {
            $enrollment = $this->lock($target);
            $payment = $enrollment->payments()->whereKey($targetPayment->id)->lockForUpdate()->firstOrFail();
            $this->revision($enrollment->revision, (int) $data['revision']);
            if ($payment->voided_at) { throw ValidationException::withMessages(['reason' => 'Cette écriture est déjà annulée.']); }
            $payment->forceFill(['voided_at' => now(), 'voided_by' => $actor->id, 'void_reason' => $data['reason']])->save();
            $this->bump($enrollment);
            $this->audit($actor, 'payment.voided', $enrollment->id, ['payment_id' => $payment->id, 'amount' => $payment->amount]);
        });
    }

    public function changeStatus(User $actor, Enrollment $target, array $data): void
    {
        $this->transact($actor, 'enrollments.manage', function () use ($actor, $target, $data) {
            $enrollment = $this->lock($target);
            $this->revision($enrollment->revision, (int) $data['revision']);
            if ((bool) $enrollment->cancelled_at === (bool) $data['cancelled']) {
                throw ValidationException::withMessages(['cancelled' => 'Cette inscription possède déjà ce statut.']);
            }
            if ($data['cancelled'] && $enrollment->paidAmount() > 0) {
                throw ValidationException::withMessages(['cancelled' => 'Annulez les écritures erronées avant d’annuler une inscription. Aucun remboursement n’est effectué ici.']);
            }
            if (! $data['cancelled']) { $this->available($enrollment->student, $enrollment->academicYear, (int) $enrollment->study_level_id, $enrollment->source_annual_decision_id !== null); }
            $enrollment->forceFill(['cancelled_at' => $data['cancelled'] ? now() : null, 'cancellation_reason' => $data['cancelled'] ? $data['reason'] : null]);
            $this->bump($enrollment);
            $this->audit($actor, $data['cancelled'] ? 'enrollment.cancelled' : 'enrollment.restored', $enrollment->id, ['reason' => $data['reason']]);
        });
    }

    public function saveInternship(User $actor, Enrollment $target, array $data): void
    {
        $this->transact($actor, 'internships.self', function () use ($actor, $target, $data) {
            $enrollment = $this->ownedInternship($actor, $target);
            $this->revision($enrollment->revision, (int) $data['revision']);
            $this->active($enrollment);
            $stage = $enrollment->internship ?? new EnrollmentInternship;
            $stage->forceFill([...Arr::only($data, ['company', 'title', 'starts_on', 'ends_on', 'evidence_reference']),
                'evidence_reference' => $data['evidence_reference'] ?? '', 'enrollment_id' => $enrollment->id, 'recorded_by' => $actor->id])->save();
            $this->bump($enrollment);
            $this->audit($actor, 'internship.saved', $enrollment->id, ['internship_id' => $stage->id]);
        });
    }

    public function removeInternship(User $actor, Enrollment $target, array $data): void
    {
        $this->transact($actor, 'internships.self', function () use ($actor, $target, $data) {
            $enrollment = $this->ownedInternship($actor, $target);
            $this->revision($enrollment->revision, (int) $data['revision']);
            $this->active($enrollment);
            $stage = $enrollment->internship()->firstOrFail();
            $this->audit($actor, 'internship.removed', $enrollment->id, ['internship_id' => $stage->id, 'reason' => $data['reason']]);
            $stage->delete();
            $this->bump($enrollment);
        });
    }

    public function review(User $actor, Enrollment $target, array $data): void
    {
        $this->transact($actor, 'eligibility.review', function () use ($actor, $target, $data) {
            $enrollment = $this->lock($target);
            $this->revision($enrollment->revision, (int) $data['revision']);
            $this->active($enrollment);
            $this->bump($enrollment);
            $decision = app(Eligibility::class)->evaluate($enrollment->fresh());
            $review = new EligibilityReview;
            $review->forceFill(['enrollment_id' => $enrollment->id, 'reviewed_by' => $actor->id,
                'result' => $decision['computed_result'], 'fingerprint' => $decision['fingerprint'], 'criteria' => $decision['criteria'],
                'reason' => $data['reason'], 'created_at' => now()])->save();
            $this->audit($actor, 'eligibility.reviewed', $enrollment->id, ['review_id' => $review->id, 'result' => $review->result]);
        });
    }

    private function ownedInternship(User $actor, Enrollment $target): Enrollment
    {
        abort_unless($actor->fresh()->belongsToCompatibleGroup('student'), 403);
        return Enrollment::whereKey($target->id)->whereHas('student', fn ($q) => $q->where('user_id', $actor->id))->lockForUpdate()->firstOrFail();
    }

    private function lock(Enrollment $target): Enrollment { return Enrollment::whereKey($target->id)->lockForUpdate()->firstOrFail(); }
    private function bump(Enrollment $enrollment): void { $enrollment->revision++; $enrollment->save(); }
    private function active(Enrollment $enrollment): void
    {
        if ($enrollment->cancelled_at) { throw ValidationException::withMessages(['revision' => 'Cette inscription est annulée.']); }
        $this->available($enrollment->student, $enrollment->academicYear, (int) $enrollment->study_level_id, $enrollment->source_annual_decision_id !== null);
        if ((int) $enrollment->student_status_id === 2 && ! $enrollment->source_annual_decision_id) { throw ValidationException::withMessages(['revision' => 'Les inscriptions À cheval nécessitent le module de rattrapage.']); }
    }
    private function available(Student $student, AcademicYear $year, int $level, bool $managed = false): void
    {
        if ($year->status !== 'current') { throw ValidationException::withMessages(['academic_year_id' => 'L’année académique doit être en cours.']); }
        if ($student->is_archived) { throw ValidationException::withMessages(['card_number' => 'Le dossier étudiant est archivé.']); }
        if ((int) $student->student_status_id === 2 && ! $managed) { throw ValidationException::withMessages(['card_number' => 'Le tarif À cheval dépend des matières à reprendre. Utilisez une décision du parcours annuel pour la réinscription.']); }
        if ((int) $student->study_level_id !== $level) { throw ValidationException::withMessages(['study_level_id' => 'Le niveau doit correspondre au dossier étudiant.']); }
    }
}
