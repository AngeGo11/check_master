<?php

namespace Tests\Feature;

use App\Actions\Schooling\ManageEnrollments;
use App\Actions\Schooling\ManageSchooling;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Student;
use App\Models\TuitionFee;
use App\Models\TuitionPayment;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Eligibility;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EnrollmentsTest extends TestCase
{
    use RefreshDatabase;
    private ?User $operator = null;
    protected function setUp(): void { parent::setUp(); $this->withoutVite(); $this->travelTo(Carbon::parse('2026-10-21 12:00:00')); }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function admin(): User
    {
        if (! $this->operator) {
            $this->operator = User::factory()->create();
            $this->operator->types()->attach(3); $this->operator->groups()->attach(8);
        }
        return $this->operator;
    }
    private function delegate(array $permissions): User
    {
        $user = User::factory()->create(); $user->types()->attach(4); $user->groups()->attach(4);
        UserGroup::findOrFail(4)->permissions()->sync($permissions);
        return $user;
    }
    private function studentAccount(): User
    {
        $user = User::factory()->create(); $user->types()->attach(1); $user->groups()->attach(1); return $user;
    }
    private function signIn(User $user): static { return $this->actingAs($user->fresh())->withSession(['auth_version' => $user->fresh()->auth_version]); }
    private function year(array $overrides = []): AcademicYear
    {
        $year = new AcademicYear;
        $data = [...['name' => '2026-2027-'.Str::ulid(), 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'status' => 'current'], ...$overrides];
        $year->forceFill([...$data, 'current_slot' => $data['status'] === 'current' ? 1 : null])->save(); return $year;
    }
    private function student(array $overrides = []): Student
    {
        $student = new Student;
        $student->forceFill([...['card_number' => 'CI-'.Str::ulid(), 'last_name' => 'Kouadio', 'first_name' => 'Camille', 'email' => Str::ulid().'@example.test',
            'study_level_id' => 5, 'promotion_id' => 22, 'student_status_id' => 1], ...$overrides])->save(); return $student;
    }
    private function fee(AcademicYear $year, int $level = 5, int $amount = 100000): TuitionFee
    {
        $fee = new TuitionFee; $fee->forceFill(['academic_year_id' => $year->id, 'study_level_id' => $level, 'amount' => $amount])->save(); return $fee;
    }
    private function enrollment(?Student $student = null): Enrollment
    {
        $student ??= $this->student(['user_id' => $this->studentAccount()->id]);
        if (! $student->user_id) { $student->forceFill(['user_id' => $this->studentAccount()->id])->save(); }
        $year = AcademicYear::where('status', 'current')->first() ?? $this->year();
        if (! TuitionFee::where('academic_year_id', $year->id)->where('study_level_id', $student->study_level_id)->exists()) { $this->fee($year, (int) $student->study_level_id); }
        return app(ManageEnrollments::class)->create($this->admin(), ['card_number' => $student->card_number, 'academic_year_id' => $year->id, 'study_level_id' => $student->study_level_id, 'enrolled_on' => '2026-10-01']);
    }
    private function paymentData(Enrollment $enrollment, array $overrides = []): array
    {
        return [...['amount' => 40000, 'method' => 'cash', 'cheque_number' => null, 'purpose' => 'Frais de scolarité', 'paid_on' => '2026-10-20', 'operation_key' => (string) Str::uuid(), 'revision' => $enrollment->fresh()->revision], ...$overrides];
    }
    private function payment(Enrollment $enrollment, array $overrides = []): TuitionPayment
    {
        return app(ManageEnrollments::class)->recordPayment($this->admin(), $enrollment, $this->paymentData($enrollment, $overrides));
    }
    private function stageData(Enrollment $enrollment, array $overrides = []): array
    {
        return [...['company' => 'Entreprise de démonstration', 'title' => 'Stage de fin d’études', 'starts_on' => '2026-10-01', 'ends_on' => '2027-01-31', 'evidence_reference' => 'Convention fictive TEST-001', 'revision' => $enrollment->fresh()->revision], ...$overrides];
    }
    private function review(Enrollment $enrollment): void
    {
        app(ManageEnrollments::class)->review($this->admin(), $enrollment, ['reason' => 'Critères et justificatif vérifiés.', 'revision' => $enrollment->fresh()->revision]);
    }
    private function decision(Enrollment $enrollment): array { return app(Eligibility::class)->evaluate($enrollment->fresh()); }
    private function eligible(): Enrollment
    {
        $enrollment = $this->enrollment(); $this->payment($enrollment, ['amount' => 100000]);
        app(ManageEnrollments::class)->saveInternship(User::findOrFail($enrollment->student->user_id), $enrollment, $this->stageData($enrollment));
        $this->review($enrollment); return $enrollment;
    }

    public function test_migration_adds_no_historical_financial_data_or_automatic_year(): void
    {
        foreach (['academic_years', 'tuition_fees', 'enrollments', 'tuition_payments', 'eligibility_reviews', 'enrollment_audits'] as $table) { $this->assertDatabaseCount($table, 0); }
        $this->assertDatabaseCount('permissions', 38); $this->assertDatabaseCount('group_permission', 74);
        $this->assertDatabaseHas('group_permission', ['user_group_id' => 8, 'permission_code' => 'payments.manage']);
        $this->assertDatabaseMissing('group_permission', ['user_group_id' => 1, 'permission_code' => 'enrollments.view']);
    }

    public function test_new_modules_require_their_own_permissions(): void
    {
        foreach (['/settings/schooling', '/enrollments', '/my-enrollments'] as $url) { $this->get($url)->assertRedirect('/login'); }
        $this->signIn($this->delegate(['academic.view', 'academic.manage', 'students.view', 'students.manage']));
        foreach (['/settings/schooling', '/enrollments', '/enrollments/create', '/my-enrollments'] as $url) { $this->get($url)->assertForbidden(); }
        $this->post('/settings/years', [])->assertForbidden(); $this->post('/settings/fees', [])->assertForbidden(); $this->post('/enrollments', [])->assertForbidden();
    }

    public function test_only_one_year_is_current_and_old_forms_cannot_reactivate_it(): void
    {
        $old = $this->year();
        $new = ['name' => '2027-2028', 'starts_on' => '2027-09-01', 'ends_on' => '2028-08-31', 'status' => 'current'];
        $this->signIn($this->admin())->post('/settings/years', $new)->assertSessionHasNoErrors();
        $this->assertSame('closed', $old->fresh()->status); $this->assertNull($old->fresh()->current_slot); $this->assertSame(2, $old->fresh()->revision);
        $this->assertSame(1, AcademicYear::where('status', 'current')->count());
        $this->put('/settings/years/'.$old->id, ['name' => $old->name, 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'status' => 'current', 'revision' => 1])->assertSessionHasErrors('revision');
    }

    public function test_year_validation_and_enrolled_dates_are_protected(): void
    {
        $e = $this->enrollment(); $year = $e->academicYear;
        $this->signIn($this->admin())->post('/settings/years', ['name' => $year->name, 'starts_on' => '2027-01-01', 'ends_on' => '2026-01-01', 'status' => 'bogus'])->assertSessionHasErrors(['name', 'ends_on', 'status']);
        $this->put('/settings/years/'.$year->id, ['name' => $year->name, 'starts_on' => '2026-08-01', 'ends_on' => '2027-08-31', 'status' => 'current', 'revision' => 1])->assertSessionHasErrors('starts_on');
        $this->assertSame('2026-09-01', $year->fresh()->starts_on->format('Y-m-d'));
    }

    public function test_fee_changes_apply_only_to_new_enrollments(): void
    {
        $e = $this->enrollment(); $fee = TuitionFee::findOrFail($e->tuition_fee_id);
        $this->signIn($this->admin())->put('/settings/fees/'.$fee->id, ['amount' => 150000, 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame(100000, $e->fresh()->amount_due); $this->assertSame(150000, $this->enrollment()->amount_due);
        $this->put('/settings/fees/'.$fee->id, ['amount' => 200000, 'revision' => 1])->assertSessionHasErrors('revision');
    }

    public function test_fee_pairs_and_whole_positive_fcfa_are_enforced(): void
    {
        $year = $this->year(); $this->fee($year);
        $this->signIn($this->admin());
        foreach ([0, -1, '1.5', 1000000001] as $amount) {
            $this->post('/settings/fees', ['academic_year_id' => $year->id, 'study_level_id' => 4, 'amount' => $amount])->assertSessionHasErrors('amount');
        }
        $this->post('/settings/fees', ['academic_year_id' => $year->id, 'study_level_id' => 5, 'amount' => 50000])->assertSessionHasErrors('study_level_id');
        $this->assertDatabaseCount('tuition_fees', 1);
    }

    public function test_closed_year_tariffs_and_read_only_configuration_cannot_be_changed(): void
    {
        $year = $this->year(['status' => 'closed']); $fee = $this->fee($year);
        $this->signIn($this->admin())->put('/settings/fees/'.$fee->id, ['amount' => 80000, 'revision' => 1])->assertSessionHasErrors('amount');
        $this->signIn($this->delegate(['schooling.view']))->get('/settings')->assertOk();
        $this->get('/settings/schooling')->assertInertia(fn (Assert $page) => $page->component('settings/schooling')->has('years', 1)->has('fees', 1));
        $this->put('/settings/fees/'.$fee->id, ['amount' => 80000, 'revision' => 1])->assertForbidden();
    }

    public function test_enrollment_derives_the_tariff_and_rejects_duplicates_and_client_amounts(): void
    {
        $year = $this->year(); $this->fee($year); $student = $this->student(['student_status_id' => 3]);
        $data = ['card_number' => strtolower($student->card_number), 'academic_year_id' => $year->id, 'study_level_id' => 5, 'enrolled_on' => '2026-10-01'];
        $this->signIn($this->admin())->post('/enrollments', [...$data, 'amount_due' => 1])->assertSessionHasErrors('amount_due');
        $this->post('/enrollments', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('enrollments', ['student_id' => $student->id, 'amount_due' => 100000, 'student_status_id' => 3]);
        $this->post('/enrollments', $data)->assertSessionHasErrors('card_number'); $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_enrollment_blocks_archived_makeup_and_wrong_level_students(): void
    {
        $year = $this->year(); $this->fee($year); $student = $this->student();
        $data = ['card_number' => $student->card_number, 'academic_year_id' => $year->id, 'study_level_id' => 5, 'enrolled_on' => '2026-10-01'];
        $this->signIn($this->admin())->post('/enrollments', [...$data, 'study_level_id' => 4])->assertSessionHasErrors('study_level_id');
        $student->forceFill(['student_status_id' => 2])->save(); $this->post('/enrollments', $data)->assertSessionHasErrors('card_number');
        $student->forceFill(['student_status_id' => 1, 'is_archived' => true])->save(); $this->post('/enrollments', $data)->assertSessionHasErrors('card_number');
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_enrollment_requires_current_year_configured_fee_and_valid_date(): void
    {
        $year = $this->year(['status' => 'pending']); $student = $this->student();
        $data = ['card_number' => $student->card_number, 'academic_year_id' => $year->id, 'study_level_id' => 5, 'enrolled_on' => '2026-10-01'];
        $this->signIn($this->admin())->post('/enrollments', $data)->assertSessionHasErrors('academic_year_id');
        $year->forceFill(['status' => 'current', 'current_slot' => 1])->save();
        $this->post('/enrollments', $data)->assertSessionHasErrors('study_level_id'); $this->fee($year);
        foreach (['2026-08-31', '2026-10-22'] as $date) { $this->post('/enrollments', [...$data, 'enrolled_on' => $date])->assertSessionHasErrors('enrolled_on'); }
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_cash_and_cheque_payments_compute_partial_and_paid_balances(): void
    {
        $e = $this->enrollment(); $this->signIn($this->admin())->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e))->assertSessionHasNoErrors();
        $this->get('/enrollments/'.$e->id)->assertInertia(fn (Assert $page) => $page->where('enrollment.paid_amount', 40000)->where('enrollment.balance', 60000)->where('enrollment.payment_status', 'partial')->missing('payments.0.operation_key')->missing('payments.0.recorded_by')->missing('enrollment.student.email'));
        $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e, ['amount' => 60000, 'method' => 'cheque', 'cheque_number' => 'TEST-CH001']))->assertSessionHasNoErrors();
        $this->assertSame('paid', $e->fresh()->paymentStatus()); $this->assertSame(100000, $e->fresh()->paidAmount());
    }

    public function test_overpayment_and_invalid_payment_inputs_leave_no_entries(): void
    {
        $e = $this->enrollment(); $this->signIn($this->admin());
        foreach ([0, -10, '10.5', 100001, 1000000001] as $amount) { $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e, ['amount' => $amount]))->assertSessionHasErrors('amount'); }
        foreach ([['method' => 'cheque', 'cheque_number' => null], ['method' => 'cash', 'cheque_number' => 'NO']] as $data) {
            $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e, $data))->assertSessionHasErrors('cheque_number');
        }
        foreach (['2026-09-30', '2026-10-22'] as $date) { $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e, ['paid_on' => $date]))->assertSessionHasErrors('paid_on'); }
        $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e, ['paid_amount' => 1]))->assertSessionHasErrors('paid_amount');
        $this->assertDatabaseCount('tuition_payments', 0); $this->assertSame(1, $e->fresh()->revision);
    }

    public function test_repeated_submission_returns_one_payment_and_one_audit(): void
    {
        $e = $this->enrollment(); $data = $this->paymentData($e); $before = DB::table('enrollment_audits')->count();
        $this->signIn($this->admin())->post('/enrollments/'.$e->id.'/payments', $data)->assertSessionHasNoErrors();
        $this->post('/enrollments/'.$e->id.'/payments', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('tuition_payments', 1); $this->assertDatabaseCount('enrollment_audits', $before + 1); $this->assertSame(2, $e->fresh()->revision);
    }

    public function test_reused_key_with_different_content_or_enrollment_is_rejected(): void
    {
        $e = $this->enrollment(); $other = $this->enrollment(); $data = $this->paymentData($e); $this->payment($e, $data);
        $this->signIn($this->admin())->post('/enrollments/'.$e->id.'/payments', [...$data, 'amount' => 50000])->assertSessionHasErrors('operation_key');
        $this->post('/enrollments/'.$other->id.'/payments', $data)->assertSessionHasErrors('operation_key'); $this->assertDatabaseCount('tuition_payments', 1);
    }

    public function test_stale_payment_form_is_rejected_and_balance_is_reread(): void
    {
        $e = $this->enrollment(); $data = $this->paymentData($e); $this->payment($e, ['amount' => 70000]);
        $this->signIn($this->admin())->post('/enrollments/'.$e->id.'/payments', $data)->assertSessionHasErrors('revision');
        $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e))->assertSessionHasErrors('amount'); $this->assertSame(70000, $e->fresh()->paidAmount());
    }

    public function test_payment_void_keeps_receipt_history_and_replay_never_recreates_it(): void
    {
        $e = $this->enrollment(); $data = $this->paymentData($e); $payment = $this->payment($e, $data);
        $this->signIn($this->admin())->patch('/enrollments/'.$e->id.'/payments/'.$payment->id.'/void', ['reason' => 'Saisie en double vérifiée.', 'revision' => 2])->assertSessionHasNoErrors();
        $this->assertSame(0, $e->fresh()->paidAmount()); $this->assertNotNull($payment->fresh()->voided_at);
        $this->post('/enrollments/'.$e->id.'/payments', $data)->assertSessionHasNoErrors(); $this->assertSame(0, $e->fresh()->paidAmount()); $this->assertDatabaseCount('tuition_payments', 1);
        $this->get('/enrollments/'.$e->id.'/payments/'.$payment->id.'/receipt')->assertInertia(fn (Assert $page) => $page->component('enrollments/receipt')->where('payment.void_reason', 'Saisie en double vérifiée.')->missing('payment.operation_key'));
        $this->patch('/enrollments/'.$e->id.'/payments/'.$payment->id.'/void', ['reason' => 'Deuxième annulation.', 'revision' => 3])->assertSessionHasErrors('reason');
    }

    public function test_receipts_and_voids_cannot_mix_enrollment_identifiers(): void
    {
        $e = $this->enrollment(); $other = $this->enrollment(); $payment = $this->payment($e);
        $this->signIn($this->admin())->get('/enrollments/'.$other->id.'/payments/'.$payment->id.'/receipt')->assertNotFound();
        $this->patch('/enrollments/'.$other->id.'/payments/'.$payment->id.'/void', ['reason' => 'Mauvaise inscription.', 'revision' => 1])->assertNotFound();
        $this->assertNull($payment->fresh()->voided_at);
    }

    public function test_closed_year_disallows_new_money_but_preserves_corrections(): void
    {
        $e = $this->enrollment(); $payment = $this->payment($e);
        $e->academicYear->forceFill(['status' => 'closed', 'current_slot' => null])->save();
        $this->signIn($this->admin())->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e))->assertSessionHasErrors('academic_year_id');
        $this->patch('/enrollments/'.$e->id.'/payments/'.$payment->id.'/void', ['reason' => 'Correction après clôture.', 'revision' => 2])->assertSessionHasNoErrors();
        $this->assertSame(0, $e->fresh()->paidAmount());
    }

    public function test_enrollment_cancellation_requires_zero_active_payments_and_can_be_reversed(): void
    {
        $e = $this->enrollment(); $payment = $this->payment($e); $this->signIn($this->admin());
        $this->patch('/enrollments/'.$e->id.'/status', ['cancelled' => true, 'reason' => 'Erreur d’inscription.', 'revision' => 2])->assertSessionHasErrors('cancelled');
        $this->patch('/enrollments/'.$e->id.'/payments/'.$payment->id.'/void', ['reason' => 'Erreur de saisie.', 'revision' => 2])->assertSessionHasNoErrors();
        $this->patch('/enrollments/'.$e->id.'/status', ['cancelled' => true, 'reason' => 'Erreur d’inscription.', 'revision' => 3])->assertSessionHasNoErrors();
        $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e))->assertSessionHasErrors('revision');
        $this->patch('/enrollments/'.$e->id.'/status', ['cancelled' => false, 'reason' => 'Dossier régularisé.', 'revision' => 4])->assertSessionHasNoErrors();
        $this->assertNull($e->fresh()->cancelled_at); $this->assertSame(100000, $e->fresh()->amount_due);
    }

    public function test_financial_and_eligibility_powers_are_separate(): void
    {
        $e = $this->enrollment(); $payment = $this->payment($e);
        $this->signIn($this->delegate(['enrollments.view', 'enrollments.manage']))->get('/enrollments/'.$e->id)->assertOk();
        $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e))->assertForbidden();
        $this->patch('/enrollments/'.$e->id.'/payments/'.$payment->id.'/void', ['reason' => 'Test interdit.', 'revision' => 2])->assertForbidden();
        $this->post('/enrollments/'.$e->id.'/eligibility', ['reason' => 'Test interdit.', 'revision' => 2])->assertForbidden();
        $this->signIn($this->delegate(['enrollments.view', 'payments.manage']));
        $this->put('/my-enrollments/'.$e->id.'/internship', $this->stageData($e))->assertForbidden();
        $this->patch('/enrollments/'.$e->id.'/status', ['cancelled' => true, 'reason' => 'Test interdit.', 'revision' => 2])->assertForbidden();
    }

    public function test_each_new_mutation_requires_its_permission_even_with_direct_urls(): void
    {
        $e = $this->enrollment();
        $this->signIn($this->delegate(['enrollments.view']));
        $this->get('/enrollments/create')->assertForbidden(); $this->post('/enrollments', [])->assertForbidden();
        $this->put('/my-enrollments/'.$e->id.'/internship', [])->assertForbidden(); $this->delete('/my-enrollments/'.$e->id.'/internship', [])->assertForbidden();
        $this->post('/enrollments/'.$e->id.'/eligibility', [])->assertForbidden();
    }

    public function test_student_stage_dates_are_required_and_edits_use_revisions(): void
    {
        $e = $this->enrollment(); $this->signIn(User::findOrFail($e->student->user_id));
        $this->put('/my-enrollments/'.$e->id.'/internship', $this->stageData($e, ['evidence_reference' => '', 'ends_on' => '2026-09-01']))->assertSessionHasErrors('ends_on');
        $data = $this->stageData($e); $this->put('/my-enrollments/'.$e->id.'/internship', $data)->assertSessionHasNoErrors();
        $this->put('/my-enrollments/'.$e->id.'/internship', [...$data, 'title' => 'Modification périmée'])->assertSessionHasErrors('revision');
        $this->assertDatabaseCount('enrollment_internships', 1);
    }

    public function test_three_criteria_immediately_enable_eligibility_without_an_explicit_review(): void
    {
        $e = $this->enrollment(); $this->payment($e, ['amount' => 100000]);
        app(ManageEnrollments::class)->saveInternship(User::findOrFail($e->student->user_id), $e, $this->stageData($e));
        $this->assertSame('eligible', $this->decision($e)['computed_result']); $this->assertSame($this->decision($e)['computed_result'], $this->decision($e)['status']);
        $this->signIn($this->admin())->post('/enrollments/'.$e->id.'/eligibility', ['reason' => 'Les critères sont vérifiés.', 'revision' => 3])->assertSessionHasNoErrors();
        $this->assertSame('eligible', $this->decision($e)['status']); $this->assertDatabaseCount('eligibility_reviews', 1);
        $this->get('/enrollments/'.$e->id)->assertInertia(fn (Assert $page) => $page->where('eligibility.status', 'eligible')->has('eligibility.criteria', 3)->missing('eligibility.fingerprint'));
    }

    public function test_review_cannot_accept_client_results_or_mark_missing_criteria_eligible(): void
    {
        $e = $this->enrollment(); $this->signIn($this->admin());
        $this->post('/enrollments/'.$e->id.'/eligibility', ['reason' => 'Résultat falsifié.', 'revision' => 1, 'result' => 'eligible', 'criteria' => []])->assertSessionHasErrors('result');
        $this->post('/enrollments/'.$e->id.'/eligibility', ['reason' => 'Paiement et stage manquants.', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame('ineligible', $this->decision($e)['status']);
        $this->post('/enrollments/'.$e->id.'/eligibility', ['reason' => 'Ancien formulaire.', 'revision' => 1])->assertSessionHasErrors('revision');
        $this->assertDatabaseCount('eligibility_reviews', 1);
    }

    public function test_declared_future_stage_counts_but_non_master2_blocks_deposit(): void
    {
        $e = $this->enrollment($this->student(['study_level_id' => 4])); $this->payment($e, ['amount' => 100000]);
        app(ManageEnrollments::class)->saveInternship(User::findOrFail($e->student->user_id), $e, $this->stageData($e, ['starts_on' => '2026-11-01']));
        $this->review($e); $decision = $this->decision($e);
        $this->assertFalse($decision['criteria'][1]['met']); $this->assertTrue($decision['criteria'][2]['met']); $this->assertSame('ineligible', $decision['status']);
    }

    public function test_voiding_a_payment_invalidates_a_previous_eligible_decision(): void
    {
        $e = $this->eligible(); $payment = $e->payments()->firstOrFail();
        app(ManageEnrollments::class)->voidPayment($this->admin(), $e, $payment, ['reason' => 'Écriture incorrecte.', 'revision' => $e->fresh()->revision]);
        $this->assertSame($this->decision($e)['computed_result'], $this->decision($e)['status']); $this->assertSame('ineligible', $this->decision($e)['computed_result']); $this->assertFalse($this->decision($e)['review']['current']);
        $this->review($e); $this->assertSame('ineligible', $this->decision($e)['status']); $this->assertDatabaseCount('eligibility_reviews', 2);
    }

    public function test_removing_a_stage_invalidates_eligibility_and_records_the_reason(): void
    {
        $e = $this->eligible();
        $this->signIn(User::findOrFail($e->student->user_id))->delete('/my-enrollments/'.$e->id.'/internship', ['reason' => 'Convention attribuée par erreur.', 'revision' => $e->fresh()->revision])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('enrollment_internships', 0); $this->assertSame($this->decision($e)['computed_result'], $this->decision($e)['status']);
        $this->assertFalse($this->decision($e)['criteria'][2]['met']); $this->assertDatabaseHas('enrollment_audits', ['action' => 'internship.removed', 'enrollment_id' => $e->id]);
    }

    public function test_student_and_year_changes_make_a_review_stale_and_block_new_entries(): void
    {
        $e = $this->eligible(); $e->student->forceFill(['is_archived' => true, 'revision' => 2])->save();
        $this->assertSame($this->decision($e)['computed_result'], $this->decision($e)['status']); $this->assertFalse($this->decision($e)['context_valid']);
        $this->signIn($this->admin())->post('/enrollments/'.$e->id.'/eligibility', ['reason' => 'Nouvel examen.', 'revision' => $e->fresh()->revision])->assertSessionHasErrors('card_number');
        $e->student->forceFill(['is_archived' => false, 'revision' => 3])->save(); $this->review($e);
        $e->academicYear->forceFill(['status' => 'closed', 'current_slot' => null, 'revision' => 2])->save();
        $this->assertSame($this->decision($e)['computed_result'], $this->decision($e)['status']); $this->assertFalse($this->decision($e)['context_valid']);
    }

    public function test_declared_stage_does_not_require_waiting_for_its_start_or_a_manual_review(): void
    {
        $e = $this->enrollment(); $this->payment($e, ['amount' => 100000]);
        app(ManageEnrollments::class)->saveInternship(User::findOrFail($e->student->user_id), $e, $this->stageData($e, ['starts_on' => '2026-10-22']));
        $this->assertSame('eligible', $this->decision($e)['status']);
        $this->assertNull($this->decision($e)['review']);
        $this->travelTo(Carbon::parse('2026-10-22 12:00:00')); $this->assertSame($this->decision($e)['computed_result'], $this->decision($e)['status']);
    }

    public function test_personal_history_and_receipts_are_scoped_to_the_explicit_owner(): void
    {
        $user = $this->studentAccount(); $e = $this->enrollment($this->student(['user_id' => $user->id])); $payment = $this->payment($e);
        $other = $this->enrollment(); $otherPayment = $this->payment($other);
        $this->signIn($user)->get('/my-enrollments?student_id='.$other->student_id)->assertInertia(fn (Assert $page) => $page->where('personal', true)->has('enrollments.data', 1)->where('enrollments.data.0.id', $e->id)->where('statistics', null));
        $this->get('/my-enrollments/'.$e->id)->assertInertia(fn (Assert $page) => $page->where('personal', true)->where('operationKey', null));
        $this->get('/my-enrollments/'.$e->id.'/payments/'.$payment->id.'/receipt')->assertOk();
        $this->get('/my-enrollments/'.$other->id)->assertNotFound();
        $this->get('/my-enrollments/'.$e->id.'/payments/'.$otherPayment->id.'/receipt')->assertNotFound();
        $this->get('/my-enrollments/'.$other->id.'/payments/'.$otherPayment->id.'/receipt')->assertNotFound();
        $this->get('/enrollments/'.$e->id)->assertForbidden(); $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e))->assertForbidden();
    }

    public function test_email_match_does_not_grant_personal_access_and_revocation_is_immediate(): void
    {
        $user = $this->studentAccount(); $student = $this->student(['email' => $user->email]); $e = $this->enrollment($student);
        $this->signIn($user)->get('/my-enrollments')->assertInertia(fn (Assert $page) => $page->has('enrollments.data', 0));
        $this->get('/my-enrollments/'.$e->id)->assertNotFound();
        $student->forceFill(['user_id' => $user->id])->save(); $this->get('/my-enrollments/'.$e->id)->assertOk();
        $student->forceFill(['user_id' => null])->save(); $this->get('/my-enrollments/'.$e->id)->assertNotFound();
        $user->types()->detach(1); $this->get('/my-enrollments')->assertForbidden();
    }

    public function test_list_filters_and_statistics_never_mix_academic_years(): void
    {
        $first = $this->enrollment(); $this->payment($first); $second = $this->enrollment(); $this->payment($second, ['amount' => 100000]);
        $unpaid = $this->enrollment(); $year = $first->academicYear;
        $old = $this->year(['status' => 'closed', 'name' => '2025-2026', 'starts_on' => '2025-09-01', 'ends_on' => '2026-08-31']);
        $historic = $this->enrollment(); $historic->forceFill(['academic_year_id' => $old->id])->save();
        $this->signIn($this->admin())->get('/enrollments')->assertInertia(fn (Assert $page) => $page->has('enrollments.data', 3)->where('statistics', ['total' => 3, 'paid' => 1, 'partial' => 1, 'unpaid' => 1]));
        $this->get('/enrollments?payment_status=unpaid&academic_year_id='.$year->id)->assertInertia(fn (Assert $page) => $page->has('enrollments.data', 1)->where('enrollments.data.0.id', $unpaid->id)->where('statistics.total', 3));
        $this->get('/enrollments?search='.$first->student->card_number.'&enrolled_on=2026-10-01&study_level_id=5')->assertInertia(fn (Assert $page) => $page->has('enrollments.data', 1)->where('enrollments.data.0.id', $first->id));
        $this->get('/enrollments?academic_year_id='.$old->id)->assertInertia(fn (Assert $page) => $page->has('enrollments.data', 1)->where('statistics.total', 1));
    }

    public function test_list_paginates_and_cancelled_enrollments_are_excluded_by_default(): void
    {
        for ($i = 0; $i < 22; $i++) { $last = $this->enrollment(); }
        $last->forceFill(['cancelled_at' => now(), 'cancellation_reason' => 'Test'])->save();
        $this->signIn($this->admin())->get('/enrollments')->assertInertia(fn (Assert $page) => $page->has('enrollments.data', 20)->where('enrollments.total', 21)->where('statistics.total', 21));
        $this->get('/enrollments?page=2')->assertInertia(fn (Assert $page) => $page->has('enrollments.data', 1));
        $this->get('/enrollments?include_cancelled=1')->assertInertia(fn (Assert $page) => $page->where('enrollments.total', 22)->where('statistics.total', 21));
    }

    public function test_delegation_requires_read_permissions_and_revocation_is_rechecked_under_lock(): void
    {
        $e = $this->enrollment(); $this->signIn($this->admin());
        foreach ([['schooling.manage'], ['enrollments.manage'], ['payments.manage'], ['eligibility.review']] as $permissions) {
            $this->put('/settings/groups/4', ['permissions' => $permissions])->assertSessionHasErrors('permissions');
        }
        $actor = $this->delegate(['enrollments.view', 'payments.manage']); UserGroup::findOrFail(4)->permissions()->sync([]);
        try { app(ManageEnrollments::class)->recordPayment($actor, $e, $this->paymentData($e)); $this->fail('Revoked permission accepted.'); }
        catch (AuthorizationException) { $this->assertDatabaseCount('tuition_payments', 0); }
    }

    public function test_duplicate_tariffs_are_rechecked_inside_the_transaction(): void
    {
        $year = $this->year(); $this->fee($year);
        try { app(ManageSchooling::class)->saveFee($this->admin(), ['academic_year_id' => $year->id, 'study_level_id' => 5, 'amount' => 100]); $this->fail('Duplicate tariff accepted.'); }
        catch (ValidationException) { $this->assertDatabaseCount('tuition_fees', 1); $this->assertDatabaseCount('enrollment_audits', 0); }
    }

    public function test_money_and_review_mutations_require_csrf_in_real_requests(): void
    {
        $e = $this->enrollment(); $this->app->instance('env', 'local'); $this->signIn($this->admin());
        $this->post('/enrollments/'.$e->id.'/payments', $this->paymentData($e))->assertStatus(419);
        $this->post('/enrollments/'.$e->id.'/eligibility', ['reason' => 'Test CSRF.', 'revision' => 1])->assertStatus(419);
        $this->post('/settings/years', [])->assertStatus(419); $this->assertDatabaseCount('tuition_payments', 0); $this->assertDatabaseCount('eligibility_reviews', 0);
    }

    public function test_only_the_linked_student_can_declare_and_remove_their_stage(): void
    {
        $e = $this->enrollment(); $owner = User::findOrFail($e->student->user_id); $other = $this->studentAccount();
        $this->signIn($this->admin())->put('/my-enrollments/'.$e->id.'/internship', $this->stageData($e))->assertForbidden();
        $this->put('/enrollments/'.$e->id.'/internship', $this->stageData($e))->assertNotFound();
        $this->signIn($other)->put('/my-enrollments/'.$e->id.'/internship', $this->stageData($e))->assertNotFound();
        $this->signIn($owner)->put('/my-enrollments/'.$e->id.'/internship', $this->stageData($e, ['evidence_reference' => null]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('enrollment_internships', ['enrollment_id' => $e->id, 'recorded_by' => $owner->id, 'evidence_reference' => '']);
        $this->signIn($other)->delete('/my-enrollments/'.$e->id.'/internship', ['revision' => 2, 'reason' => 'Retrait interdit.'])->assertNotFound();
        $this->signIn($owner)->delete('/my-enrollments/'.$e->id.'/internship', ['revision' => 2, 'reason' => 'Déclaration incorrecte.'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('enrollment_internships', 0);
    }

    public function test_student_stage_permission_revocation_and_unlinking_are_checked_under_lock(): void
    {
        $e = $this->enrollment(); $owner = User::findOrFail($e->student->user_id); $data = $this->stageData($e);
        UserGroup::findOrFail(1)->permissions()->detach('internships.self');
        try { app(ManageEnrollments::class)->saveInternship($owner, $e, $data); $this->fail('Revoked declaration right accepted.'); }
        catch (AuthorizationException) { $this->assertDatabaseCount('enrollment_internships', 0); }
        UserGroup::findOrFail(1)->permissions()->attach('internships.self');
        $e->student->forceFill(['user_id' => null])->save();
        $this->signIn($owner)->put('/my-enrollments/'.$e->id.'/internship', $data)->assertNotFound();
    }

    public function test_personal_stage_mutations_validate_csrf_and_reject_forged_ownership(): void
    {
        $e = $this->enrollment(); $owner = User::findOrFail($e->student->user_id); $this->signIn($owner);
        $this->put('/my-enrollments/'.$e->id.'/internship', $this->stageData($e, ['recorded_by' => $this->admin()->id, 'student_id' => 999]))->assertSessionHasErrors(['recorded_by', 'student_id']);
        $this->app->instance('env', 'local');
        $this->put('/my-enrollments/'.$e->id.'/internship', $this->stageData($e))->assertStatus(419);
        $this->delete('/my-enrollments/'.$e->id.'/internship', ['revision' => 1, 'reason' => 'Test CSRF.'])->assertStatus(419);
        $this->assertDatabaseCount('enrollment_internships', 0);
    }

}
