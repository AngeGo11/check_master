<?php
namespace Tests\Feature;
use App\Actions\Academic\ManageGrades;
use App\Actions\Progression\ManageProgression;
use App\Actions\Schooling\ManageEnrollments;
use App\Actions\Reports\ManageReports;
use App\Models\{AcademicYear, AnnualDecision, Enrollment, GradeSheet, RetakeEvent, RetakeFee, RetakeSubject, Student, TuitionFee, User, UserGroup};
use App\Services\{AnnualProgression, Eligibility, RoleDashboard};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Mail, Storage};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AcademicFixtures;
use Tests\TestCase;
class AnnualProgressionTest extends TestCase
{
    use RefreshDatabase, AcademicFixtures;
    protected function setUp(): void { parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 22)->setTime(9, 0)); Storage::fake('reports'); Mail::fake(); }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function annual(int $level = 1, string $score = '12'): array
    {
        [$student, $e] = $this->enrollment($level); $school = $this->person(3); $sheets = [];
        foreach (\App\Models\AcademicSemester::where('study_level_id', $level)->get() as $semester) {
            $this->program(semester: $semester->id, units: $this->units());
            $s = app(ManageGrades::class)->create($school, ['enrollment_id' => $e->id, 'semester_id' => $semester->id]); $this->publishSheet($school, $s, $score); $sheets[] = $s->fresh();
        }
        return [$school, $student, $e, $sheets];
    }
    private function decisionData(Enrollment $e, string $outcome = 'passed', array $overrides = []): array
    {
        $summary = app(AnnualProgression::class)->build($e);
        return [...['revision' => (int) $e->annualDecisions()->max('number'), 'operation_key' => (string) Str::uuid(), 'source_fingerprint' => $summary['fingerprint'],
            'outcome' => $outcome, 'selected' => $outcome === 'conditional' ? [$summary['subjects'][0]['key']] : [], 'reference' => 'PV-TEST-ANNUEL', 'reason' => 'Décision du jury fictif vérifiée et consignée.', 'partial_confirmed' => false], ...$overrides];
    }
    private function decide(User $school, Enrollment $e, string $outcome = 'passed', array $overrides = []): AnnualDecision { return app(ManageProgression::class)->publish($school, $e, $this->decisionData($e, $outcome, $overrides)); }
    private function newYear(Enrollment $e, int $level, ?int $unit = 25000): AcademicYear
    {
        $old = $e->academicYear; $yearStart = $old->starts_on->copy()->addYear()->format('Y-m-d'); $yearEnd = $old->ends_on->copy()->addYear()->format('Y-m-d');
        $old->forceFill(['status' => 'closed', 'current_slot' => null])->save(); $y = new AcademicYear;
        $y->forceFill(['name' => 'Année '.substr($yearStart, 0, 4), 'starts_on' => $yearStart, 'ends_on' => $yearEnd, 'status' => 'current', 'current_slot' => 1])->save();
        $this->travelTo($y->starts_on->copy()->addMonth()->setTime(9, 0));
        $f = new TuitionFee; $f->forceFill(['academic_year_id' => $y->id, 'study_level_id' => $level, 'amount' => 200000])->save();
        if ($unit !== null) { app(ManageProgression::class)->saveFee($this->person(8), ['academic_year_id' => $y->id, 'study_level_id' => $level, 'amount' => $unit, 'revision' => 0]); }
        return $y;
    }
    private function registerData(AnnualDecision $d, AcademicYear $y, array $overrides = []): array
    {
        return [...['revision' => $d->number, 'operation_key' => (string) Str::uuid(), 'academic_year_id' => $y->id, 'enrolled_on' => today()->format('Y-m-d'), 'quote_fingerprint' => app(AnnualProgression::class)->quote($d, $y)['fingerprint']], ...$overrides];
    }
    private function publishCurrentYear(User $school, Enrollment $e): void
    {
        foreach (\App\Models\AcademicSemester::where('study_level_id', $e->study_level_id)->get() as $semester) {
            $this->program(semester: $semester->id, units: $this->units());
            $s = app(ManageGrades::class)->create($school, ['enrollment_id' => $e->id, 'semester_id' => $semester->id]); $this->publishSheet($school, $s);
        }
    }
    private function carried(int $level = 1): array
    {
        [$school, $student, $e] = $this->annual($level, '8'); $d = $this->decide($school, $e, 'conditional'); $year = $this->newYear($e, $level + 1);
        $data = $this->registerData($d, $year); $next = app(ManageProgression::class)->register($school, $d, $data);
        return [$school, $student, $e, $next, $d, $data];
    }
    private function resultData(RetakeSubject $s, array $overrides = []): array { return [...['revision' => $s->fresh()->revision, 'operation_key' => (string) Str::uuid(), 'status' => 'validated', 'score' => '13.25', 'reference' => 'PV-RATTRAPAGE', 'reason' => 'Résultat fictif approuvé par le jury.'], ...$overrides]; }

    public function test_roles_permissions_and_personal_ownership_are_enforced_on_direct_routes(): void
    {
        [$school, $student, $e] = $this->annual();
        $this->get('/progression')->assertRedirect('/login');
        foreach ([2, 4, 5, 6, 7, 9] as $group) { $u = $this->person($group); $this->signIn($u)->get('/progression')->assertForbidden(); $this->get('/my-progression')->assertForbidden(); }
        $this->signIn($school)->get('/progression')->assertOk(); $this->get('/settings/retake-fees')->assertForbidden();
        $this->signIn($student)->get('/my-progression/'.$e->id)->assertOk(); $this->get('/progression/'.$e->id)->assertForbidden();
        $this->signIn($this->person(1))->get('/my-progression/'.$e->id)->assertNotFound();
        $admin = $this->person(8); $this->signIn($admin)->get('/progression/'.$e->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('canDecide', false));
        $this->post('/progression/'.$e->id.'/decisions', $this->decisionData($e))->assertForbidden(); $this->get('/settings/retake-fees')->assertOk();
        $this->assertDatabaseCount('permissions', 48); $this->assertDatabaseCount('group_permission', 86);
    }
    public function test_incompatible_groups_bad_grants_revoked_access_and_self_assessment_are_rejected(): void
    {
        [$school, $student, $e] = $this->annual(); $student->groups()->attach(3);
        $this->signIn($student)->get('/progression')->assertForbidden();
        UserGroup::findOrFail(5)->permissions()->attach(['progression.view', 'progression.manage']); $teacher = $this->person(5);
        $this->signIn($teacher)->get('/progression')->assertForbidden(); $this->post('/progression/'.$e->id.'/decisions', $this->decisionData($e))->assertForbidden();
        $student->types()->attach(4); $this->signIn($student)->post('/progression/'.$e->id.'/decisions', $this->decisionData($e))->assertForbidden();
        UserGroup::findOrFail(3)->permissions()->detach('progression.manage'); $this->signIn($school)->post('/progression/'.$e->id.'/decisions', $this->decisionData($e))->assertForbidden();
        UserGroup::findOrFail(1)->permissions()->detach('grades.self'); $this->signIn($student)->get('/my-progression')->assertForbidden();
    }
    public function test_bilan_uses_published_scores_real_credits_and_never_draft_marks(): void
    {
        [$school, $student, $e, $sheets] = $this->annual(score: '12'); $service = app(AnnualProgression::class); $before = $service->build($e);
        $this->assertTrue($before['complete']); $this->assertSame(12.0, $before['average']); $this->assertSame(12, $before['credits']); $this->assertSame(12, $before['credits_at_least_ten']);
        $s = $sheets[0]; app(ManageGrades::class)->reopen($school, $s, ['revision' => $s->revision, 'reason' => 'Correction fictive']);
        app(ManageGrades::class)->save($school, $s, ['revision' => $s->fresh()->revision, 'marks' => $this->marks($s, '19')]);
        $this->signIn($student)->get('/my-progression/'.$e->id)->assertInertia(fn (Assert $p) => $p->where('summary.complete', false)->where('summary.available_average', 12)->where('summary.subjects.0.score', 12)->missing('summary.fingerprint'));
        $this->signIn($school)->post('/progression/'.$e->id.'/decisions', $this->decisionData($e))->assertSessionHasErrors('decision');
    }
    public function test_annual_average_weights_real_semester_credits_before_rounding(): void
    {
        [$student, $e] = $this->enrollment(); $school = $this->person(3);
        foreach ([1 => [1, 2], 2 => [1]] as $semester => $credits) {
            $units = []; $marks = [];
            foreach ($credits as $index => $credit) {
                $code = 'S'.$semester.'U'.$index;
                $units[] = ['code' => $code, 'name' => 'Matière fictive '.$code, 'credits' => $credit, 'hours' => 10, 'components' => []];
                $marks['ue:'.$code] = $index === 0 ? '10.01' : '10.00';
            }
            $this->program(semester: $semester, units: $units);
            $a = app(ManageGrades::class); $s = $a->create($school, ['enrollment_id' => $e->id, 'semester_id' => $semester]);
            $a->save($school, $s, ['revision' => $s->revision, 'marks' => $marks]);
            $a->publish($school, $s, ['revision' => $s->fresh()->revision, 'decision' => 'pending', 'reason' => 'Publication fictive pour vérifier les arrondis']);
        }
        // 40.02 points pondérés / 4 crédits = 10.005, soit 10.01 après un seul arrondi.
        $summary = app(AnnualProgression::class)->build($e);
        $this->assertSame(4, $summary['credits']); $this->assertSame(10.01, $summary['average']);
        $this->assertEquals([10, 10.01], array_column($summary['semesters'], 'average'));
    }
    public function test_missing_known_semester_is_never_replaced_by_zero_or_overridden_by_acknowledgement(): void
    {
        [$school, $student, $s] = $this->sheet(); $this->publishSheet($school, $s); $e = $s->enrollment;
        $this->signIn($school)->post('/progression/'.$e->id.'/decisions', $this->decisionData($e, overrides: ['partial_confirmed' => true]))->assertSessionHasErrors('decision');
        $this->get('/progression/'.$e->id)->assertInertia(fn (Assert $p) => $p->where('summary.average', null)->where('summary.semesters.1.publication_id', null));
        $this->assertDatabaseCount('annual_decisions', 0);
    }
    public function test_m2_partial_catalog_requires_explicit_pv_confirmation_and_creates_no_diploma_or_level_six(): void
    {
        [$school, $student, $e] = $this->annual(5);
        $this->signIn($school)->post('/progression/'.$e->id.'/decisions', $this->decisionData($e))->assertSessionHasErrors('partial_confirmed');
        $data = $this->decisionData($e, overrides: ['partial_confirmed' => true]); $this->post('/progression/'.$e->id.'/decisions', $data)->assertSessionHasNoErrors();
        $d = AnnualDecision::firstOrFail(); $this->assertNull($d->snapshot['average']); $this->assertTrue($d->partial_confirmed); $this->assertNull(app(AnnualProgression::class)->targetLevel($d));
        $this->assertDatabaseCount('academic_semesters', 9); $this->assertDatabaseCount('enrollments', 1);
    }
    public function test_annual_decision_is_explicit_versioned_and_idempotent_and_rejects_foreign_subjects(): void
    {
        [$school, $student, $e] = $this->annual(score: '8'); $data = $this->decisionData($e, 'conditional');
        $this->signIn($school)->post('/progression/'.$e->id.'/decisions', $data)->assertSessionHasNoErrors(); $this->post('/progression/'.$e->id.'/decisions', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('annual_decisions', 1); $old = AnnualDecision::firstOrFail();
        $this->post('/progression/'.$e->id.'/decisions', [...$data, 'reason' => 'Autre décision avec même référence'])->assertSessionHasErrors('operation_key');
        $this->post('/progression/'.$e->id.'/decisions', $this->decisionData($e, 'conditional', ['selected' => ['999:ecue:X']]))->assertSessionHasErrors('selected');
        $this->post('/progression/'.$e->id.'/decisions', $this->decisionData($e, 'repeat'))->assertSessionHasNoErrors();
        $this->assertSame('conditional', $old->fresh()->outcome); $this->assertDatabaseCount('annual_decisions', 2);
        $this->assertSame('repeat', $e->fresh()->latestAnnualDecision->outcome); $this->assertDatabaseCount('retake_subjects', 0);
    }
    public function test_stale_summary_and_stale_decision_revision_are_rejected(): void
    {
        [$school, $student, $e, $sheets] = $this->annual(); $data = $this->decisionData($e);
        $this->decide($school, $e); $this->signIn($school)->post('/progression/'.$e->id.'/decisions', $data)->assertSessionHasErrors('revision');
        $data = $this->decisionData($e); $s = $sheets[0]; app(ManageGrades::class)->reopen($school, $s, ['revision' => $s->revision, 'reason' => 'Correction test']);
        $this->post('/progression/'.$e->id.'/decisions', $data)->assertSessionHasErrors('source_fingerprint');
        $this->get('/progression/'.$e->id)->assertInertia(fn (Assert $p) => $p->where('stale', true)->where('canRegister', false));
    }
    public function test_reenrollment_creates_exact_subjects_and_frozen_fee_then_supports_payment_stage_and_retry(): void
    {
        [$school, $student, $old, $new, $d, $data] = $this->carried(4);
        $this->assertSame(225000, $new->amount_due); $this->assertSame(5, (int) $new->study_level_id); $this->assertSame(2, (int) $new->student_status_id);
        $this->assertSame(2, (int) $new->student->student_status_id);
        $this->assertDatabaseCount('retake_subjects', 1); $this->assertDatabaseCount('enrollment_retake', 1); $this->assertDatabaseCount('tuition_payments', 0);
        $this->signIn($school)->post('/progression/decisions/'.$d->id.'/register', $data)->assertRedirect('/enrollments/'.$new->id);
        $this->post('/progression/decisions/'.$d->id.'/register', [...$data, 'operation_key' => (string) Str::uuid()])->assertSessionHasErrors('operation_key');
        $admin = $this->person(8); $a = app(ManageEnrollments::class);
        $a->recordPayment($admin, $new, ['revision' => 1, 'operation_key' => (string) Str::uuid(), 'amount' => 225000, 'method' => 'cash', 'cheque_number' => null, 'purpose' => 'Scolarité et rattrapage', 'paid_on' => today()->format('Y-m-d')]);
        $a->saveInternship($student, $new, ['revision' => 2, 'company' => 'Entreprise fictive', 'title' => 'Stage fictif', 'starts_on' => today()->format('Y-m-d'), 'ends_on' => today()->addMonths(3)->format('Y-m-d')]);
        $this->assertSame('eligible', app(Eligibility::class)->evaluate($new->fresh())['status']);
        app(ManageReports::class)->submit($student, ['enrollment_id' => $new->id, 'title' => 'Mémoire fictif', 'operation_key' => (string) Str::uuid(), 'revision' => 0], $this->pdf());
        $this->assertDatabaseCount('student_reports', 1); $this->assertSame('pending', $new->retakes()->first()->status);
        Mail::assertNothingSent();
    }
    public function test_normal_passage_reuses_base_tuition_without_an_automatic_payment(): void
    {
        [$school, $student, $e] = $this->annual(); $d = $this->decide($school, $e); $year = $this->newYear($e, 2, null);
        $new = app(ManageProgression::class)->register($school, $d, $this->registerData($d, $year));
        $this->assertSame(200000, $new->amount_due); $this->assertSame(2, (int) $new->study_level_id); $this->assertSame(1, (int) $new->student_status_id);
        $this->assertSame(0, $new->retakes()->count()); $this->assertSame(0, $new->payments()->count());
    }
    public function test_repeat_reenrolls_at_the_same_level_without_inventing_extra_retake_charges(): void
    {
        [$school, $student, $e] = $this->annual(); $d = $this->decide($school, $e, 'repeat'); $year = $this->newYear($e, 1, null);
        $new = app(ManageProgression::class)->register($school, $d, $this->registerData($d, $year));
        $this->assertSame(200000, $new->amount_due); $this->assertSame(1, (int) $new->study_level_id); $this->assertSame(3, (int) $new->student_status_id);
        $this->assertSame(0, $new->retakes()->count()); $this->assertSame(0, $new->payments()->count());
    }
    public function test_missing_or_changed_retake_tariff_and_client_total_cannot_produce_underbilling(): void
    {
        [$school, $student, $e] = $this->annual(); $d = $this->decide($school, $e, 'conditional'); $year = $this->newYear($e, 2, null); $data = $this->registerData($d, $year);
        $this->signIn($school)->post('/progression/decisions/'.$d->id.'/register', $data)->assertSessionHasErrors('fees');
        $admin = $this->person(8); app(ManageProgression::class)->saveFee($admin, ['academic_year_id' => $year->id, 'study_level_id' => 2, 'amount' => 25000, 'revision' => 0]);
        $this->post('/progression/decisions/'.$d->id.'/register', $data)->assertSessionHasErrors('quote_fingerprint');
        $this->post('/progression/decisions/'.$d->id.'/register', [...$this->registerData($d, $year), 'amount_due' => 1])->assertSessionHasNoErrors();
        $this->assertSame(225000, $d->fresh()->nextEnrollment->amount_due);
        app(ManageProgression::class)->saveFee($admin, ['academic_year_id' => $year->id, 'study_level_id' => 2, 'amount' => 100000, 'revision' => 1]);
        $this->assertSame(225000, $d->fresh()->nextEnrollment->amount_due);
    }
    public function test_reenrollment_rejects_superseded_decisions_and_changed_bulletins(): void
    {
        [$school, $student, $e, $sheets] = $this->annual(); $old = $this->decide($school, $e); $latest = $this->decide($school, $e, 'repeat');
        $s = $sheets[0]; app(ManageGrades::class)->reopen($school, $s, ['revision' => $s->revision, 'reason' => 'Correction fictive']);
        $year = $this->newYear($e, 1);
        $this->signIn($school)->post('/progression/decisions/'.$old->id.'/register', $this->registerData($old, $year))->assertSessionHasErrors('revision');
        $this->post('/progression/decisions/'.$latest->id.'/register', $this->registerData($latest, $year))->assertSessionHasErrors('decision');
        $this->assertDatabaseCount('enrollments', 1);
    }
    public function test_standard_enrollment_and_student_editor_cannot_bypass_an_annual_decision(): void
    {
        [$school, $student, $e] = $this->annual(); $this->decide($school, $e); $year = $this->newYear($e, 2); $admin = $this->person(8);
        $this->signIn($admin)->post('/enrollments', ['card_number' => $e->student->card_number, 'academic_year_id' => $year->id, 'study_level_id' => 2, 'enrolled_on' => today()->format('Y-m-d')])->assertSessionHasErrors('card_number');
        $record = $e->student->fresh(); $payload = [...$record->only(Student::FIELDS), 'date_of_birth' => null, 'gender' => null, 'revision' => $record->revision, 'study_level_id' => 2];
        $this->put('/students/'.$record->id, $payload)->assertSessionHasErrors('study_level_id');
    }
    public function test_decision_history_is_frozen_once_it_has_created_a_reenrollment(): void
    {
        [$school, $student, $old, $new] = $this->carried();
        $this->signIn($school)->post('/progression/'.$old->id.'/decisions', $this->decisionData($old, 'repeat'))->assertSessionHasErrors('decision');
        $this->get('/progression/'.$old->id)->assertInertia(fn (Assert $p) => $p->where('canDecide', false)->where('used', true)->where('nextEnrollment.id', $new->id));
    }
    public function test_retake_result_is_scoped_versioned_idempotent_and_does_not_rewrite_grades_or_fees(): void
    {
        [$school, $student, $old, $new] = $this->carried(); $s = $new->retakes()->first(); $data = $this->resultData($s); $oldScores = GradeSheet::where('enrollment_id', $old->id)->pluck('marks')->all();
        $this->signIn($student)->post('/progression/'.$new->id.'/retakes/'.$s->id, $data)->assertForbidden();
        $this->signIn($school)->post('/progression/'.$old->id.'/retakes/'.$s->id, $data)->assertNotFound();
        $this->post('/progression/'.$new->id.'/retakes/'.$s->id, $data)->assertSessionHasNoErrors(); $this->post('/progression/'.$new->id.'/retakes/'.$s->id, $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('retake_events', 1); $this->assertSame('validated', $s->fresh()->status); $this->assertSame(1325, $s->fresh()->score);
        $this->post('/progression/'.$new->id.'/retakes/'.$s->id, [...$data, 'operation_key' => (string) Str::uuid()])->assertSessionHasErrors('revision');
        $this->post('/progression/'.$new->id.'/retakes/'.$s->id, $this->resultData($s, ['status' => 'failed', 'score' => '9']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('retake_events', 2); $this->assertSame(225000, $new->fresh()->amount_due);
        $this->assertSame($oldScores, GradeSheet::where('enrollment_id', $old->id)->pluck('marks')->all());
        $this->signIn($student)->get('/my-progression/'.$new->id)->assertInertia(fn (Assert $p) => $p->where('retakes.0.status', 'failed')->has('retakes.0.events.data', 2));
    }
    public function test_retake_history_pagination_and_ownership_never_leak_other_dossiers(): void
    {
        [$school, $student, $old, $new] = $this->carried(); $s = $new->retakes()->first();
        for ($i = 0; $i < 22; $i++) { app(ManageProgression::class)->updateRetake($school, $new, $s->id, $this->resultData($s, ['status' => 'pending'])); }
        $this->signIn($student)->get('/my-progression/'.$new->id.'?retake_id='.$s->id.'&retake_page=2')->assertInertia(fn (Assert $p) => $p->has('retakes.0.events.data', 2)->where('retakes.0.events.total', 22));
        $this->get('/my-progression/'.$new->id.'?retake_id=999')->assertNotFound();
        $this->signIn($this->person(1))->get('/my-progression/'.$new->id.'?retake_id='.$s->id)->assertNotFound();
    }
    public function test_fee_settings_require_admin_and_preserve_revision_and_year_constraints(): void
    {
        $year = $this->academicYear(); $data = ['academic_year_id' => $year->id, 'study_level_id' => 2, 'amount' => 0, 'revision' => 0];
        $this->signIn($this->person(3))->post('/settings/retake-fees', $data)->assertForbidden();
        $this->signIn($this->person(8))->post('/settings/retake-fees', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('retake_fees', ['amount' => 0, 'revision' => 1]);
        $this->post('/settings/retake-fees', $data)->assertSessionHasErrors('revision');
        $this->post('/settings/retake-fees', [...$data, 'revision' => 1, 'amount' => -1])->assertSessionHasErrors('amount');
        $year->forceFill(['status' => 'closed', 'current_slot' => null])->save(); $this->post('/settings/retake-fees', [...$data, 'revision' => 1])->assertSessionHasErrors('academic_year_id');
    }
    public function test_dashboard_and_audit_follow_permissions_and_do_not_mix_multigroup_spaces(): void
    {
        [$school, $student, $old, $new] = $this->carried();
        $this->signIn($student)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.retakes.value', 1)->where('dashboard.actions.progression.href', '/my-progression'));
        $this->signIn($school)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.actions.progression.href', '/progression'));
        $student->types()->attach(3); $student->groups()->attach(8);
        $this->signIn($student)->get('/dashboard?space=student')->assertInertia(fn (Assert $p) => $p->where('dashboard.actions.progression.href', '/my-progression'));
        $this->get('/audit?module=academic&action=progression.enrolled')->assertInertia(fn (Assert $p) => $p->where('events.total', 1)->where('events.data.0.label', 'Réinscription après délibération'));
        UserGroup::findOrFail(8)->permissions()->detach('progression.view');
        $this->get('/progression')->assertForbidden();
    }
    public function test_result_without_numeric_score_and_invalid_score_are_handled_explicitly(): void
    {
        [$school, $student, $old, $new] = $this->carried(); $s = $new->retakes()->first();
        $this->signIn($school)->post('/progression/'.$new->id.'/retakes/'.$s->id, $this->resultData($s, ['score' => '21']))->assertSessionHasErrors('score');
        $this->post('/progression/'.$new->id.'/retakes/'.$s->id, $this->resultData($s, ['score' => null]))->assertSessionHasNoErrors();
        $this->assertNull($s->fresh()->score); $this->assertSame('validated', $s->fresh()->status);
    }
    public function test_unresolved_debts_follow_the_student_across_years_without_duplicate_subjects(): void
    {
        [$school, $student, $old, $current] = $this->carried(); $retake = $current->retakes()->first(); $this->publishCurrentYear($school, $current);
        $this->signIn($school)->post('/progression/'.$current->id.'/decisions', $this->decisionData($current))->assertSessionHasErrors('outcome');
        $d = $this->decide($school, $current, 'conditional', ['selected' => []]); $year = $this->newYear($current, 3);
        $next = app(ManageProgression::class)->register($school, $d, $this->registerData($d, $year));
        $this->assertSame([$retake->id], $next->retakes()->pluck('retake_subjects.id')->all());
        $this->assertDatabaseCount('retake_subjects', 1); $this->assertDatabaseCount('enrollment_retake', 2); $this->assertSame(225000, $next->amount_due);
    }
    public function test_validated_debts_allow_regular_progression_without_charging_them_again(): void
    {
        [$school, $student, $old, $current] = $this->carried(); $retake = $current->retakes()->first();
        app(ManageProgression::class)->updateRetake($school, $current, $retake->id, $this->resultData($retake));
        $this->publishCurrentYear($school, $current); $d = $this->decide($school, $current); $year = $this->newYear($current, 3, null);
        $next = app(ManageProgression::class)->register($school, $d, $this->registerData($d, $year));
        $this->assertSame(200000, $next->amount_due); $this->assertSame(1, (int) $next->student_status_id); $this->assertSame(0, $next->retakes()->count());
        $this->assertDatabaseCount('retake_subjects', 1); $this->assertDatabaseCount('retake_events', 1);
    }
    public function test_retake_changes_invalidate_a_decision_before_reenrollment(): void
    {
        [$school, $student, $old, $current] = $this->carried(); $retake = $current->retakes()->first(); $this->publishCurrentYear($school, $current);
        $d = $this->decide($school, $current, 'conditional', ['selected' => []]);
        app(ManageProgression::class)->updateRetake($school, $current, $retake->id, $this->resultData($retake)); $year = $this->newYear($current, 3);
        $this->signIn($school)->post('/progression/decisions/'.$d->id.'/register', $this->registerData($d, $year))->assertSessionHasErrors('decision');
        $this->assertDatabaseCount('enrollments', 2);
    }
    public function test_reenrollment_is_atomic_if_creating_the_subject_assignment_fails(): void
    {
        [$school, $student, $e] = $this->annual(); $d = $this->decide($school, $e, 'conditional'); $year = $this->newYear($e, 2); $fail = true;
        DB::listen(function ($query) use (&$fail) { if ($fail && str_contains($query->sql, 'insert into "enrollment_retake"')) { $fail = false; throw new \RuntimeException('Simulated assignment failure'); } });
        try { app(ManageProgression::class)->register($school, $d, $this->registerData($d, $year)); $this->fail('An exception was expected.'); }
        catch (\RuntimeException $exception) { $this->assertSame('Simulated assignment failure', $exception->getMessage()); }
        $this->assertDatabaseCount('enrollments', 1); $this->assertDatabaseCount('retake_subjects', 0); $this->assertDatabaseCount('enrollment_retake', 0);
        $this->assertSame(1, (int) $e->student->fresh()->study_level_id);
    }
    public function test_reenrollment_requires_the_enrollment_permission_and_valid_new_year_and_date(): void
    {
        [$school, $student, $e] = $this->annual(); $d = $this->decide($school, $e); $year = $this->newYear($e, 2); $data = $this->registerData($d, $year);
        $this->signIn($school)->post('/progression/decisions/'.$d->id.'/register', [...$data, 'enrolled_on' => '2026-10-22'])->assertSessionHasErrors('enrolled_on');
        $this->post('/progression/decisions/'.$d->id.'/register', [...$data, 'enrolled_on' => today()->addDay()->format('Y-m-d')])->assertSessionHasErrors('enrolled_on');
        UserGroup::findOrFail(3)->permissions()->detach('enrollments.manage');
        $this->post('/progression/decisions/'.$d->id.'/register', $data)->assertForbidden();
        $this->assertDatabaseCount('enrollments', 1);
    }
    public function test_an_existing_later_enrollment_blocks_reusing_an_old_decision(): void
    {
        [$school, $student, $e] = $this->annual(); $d = $this->decide($school, $e, 'repeat'); $year = $this->newYear($e, 1);
        $existing = new Enrollment; $existing->forceFill(['reference' => 'REG-'.Str::ulid(), 'student_id' => $e->student_id, 'academic_year_id' => $year->id,
            'study_level_id' => 1, 'tuition_fee_id' => TuitionFee::where('academic_year_id', $year->id)->firstOrFail()->id,
            'student_status_id' => 3, 'amount_due' => 200000, 'enrolled_on' => today()->format('Y-m-d')])->save();
        $this->signIn($school)->post('/progression/decisions/'.$d->id.'/register', $this->registerData($d, $year))->assertSessionHasErrors('decision');
        $this->assertDatabaseCount('enrollments', 2); $this->assertNull($d->fresh()->nextEnrollment);
    }
    public function test_archived_cancelled_and_inactive_accounts_cannot_publish_decisions(): void
    {
        [$school, $student, $e] = $this->annual(); $e->student->forceFill(['is_archived' => true])->save();
        $this->signIn($school)->post('/progression/'.$e->id.'/decisions', $this->decisionData($e))->assertForbidden();
        $e->student->forceFill(['is_archived' => false])->save(); $e->forceFill(['cancelled_at' => now()])->save();
        $this->post('/progression/'.$e->id.'/decisions', $this->decisionData($e))->assertForbidden();
        $school->forceFill(['is_active' => false])->save(); $this->get('/progression')->assertRedirect('/login');
        $this->assertDatabaseCount('annual_decisions', 0);
    }
    public function test_filters_and_history_only_return_the_selected_student_year_and_decision(): void
    {
        [$school, $student, $e] = $this->annual(); $this->decide($school, $e, 'repeat'); $this->decide($school, $e, 'passed');
        $this->signIn($student)->get('/my-progression?outcome=repeat')->assertInertia(fn (Assert $p) => $p->where('enrollments.total', 0));
        $this->get('/my-progression?outcome=passed&year_id='.$e->academic_year_id)->assertInertia(fn (Assert $p) => $p->where('enrollments.total', 1));
        $this->get('/my-progression/'.$e->id)->assertInertia(fn (Assert $p) => $p->where('decisions.total', 2)->where('decisions.data.0.number', 2)->where('decisions.data.1.outcome', 'repeat')->missing('decisions.data.0.operation_key')->missing('decisions.data.0.snapshot.fingerprint'));
        $this->get('/my-progression?outcome=unknown')->assertSessionHasErrors('outcome');
    }

}
