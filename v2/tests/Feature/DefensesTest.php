<?php
namespace Tests\Feature;
use App\Actions\Academic\ManageDefenses;
use App\Actions\Collaboration\ManageMeetings;
use App\Models\{Defense, DefenseJuror, ReportVote, UserGroup};
use App\Services\{DefenseAccess, Eligibility};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Support\AcademicFixtures;
use Tests\TestCase;
class DefensesTest extends TestCase
{
    use RefreshDatabase, AcademicFixtures;
    protected function setUp(): void { parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 22)->setTime(9, 0)); Storage::fake('reports'); }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function resultData(Defense $d, array $overrides = []): array { return [...['revision' => $d->fresh()->revision, 'outcome' => 'validated', 'score' => '15,25', 'reason' => 'Résultat fictif arrêté par le jury'], ...$overrides]; }
    public function test_student_requests_only_own_accepted_report_and_retries_do_not_duplicate(): void
    {
        [$r, $round, $owner] = $this->acceptedReport(); $data = $this->defenseData($r); $this->signIn($owner)->post('/defenses', $data)->assertSessionHasNoErrors(); $d = Defense::firstOrFail();
        $this->post('/defenses', $data)->assertRedirect('/defenses/'.$d->id); $this->assertDatabaseCount('defenses', 1);
        $this->assertSame($r->latestVersion->id, $d->report_version_id); $this->assertSame($round->id, $d->report_round_id);
        $this->post('/defenses', [...$data, 'operation_key' => (string) Str::uuid()])->assertSessionHasErrors('report_id');
        $this->signIn($this->person(1))->post('/defenses', $data)->assertNotFound();
        $this->signIn($this->person(3))->post('/defenses', $data)->assertForbidden();
    }
    public function test_unanimity_is_checked_against_votes_and_not_only_a_status_label(): void
    {
        $this->person(9); [$r, $round, $owner] = $this->transmittedReport(); $this->signIn($owner)->post('/defenses', $this->defenseData($r))->assertSessionHasErrors('eligibility');
        $r->forceFill(['status' => 'accepted'])->save(); $round->forceFill(['status' => 'approved'])->save();
        $this->post('/defenses', $this->defenseData($r))->assertSessionHasErrors('eligibility'); $this->assertDatabaseCount('defenses', 0);
    }
    public function test_unpaid_cancelled_archived_or_missing_pdf_report_blocks_application(): void
    {
        [$r, , $owner] = $this->acceptedReport(); $this->signIn($owner); $e = $r->enrollment; $payment = $e->payments()->firstOrFail();
        $payment->forceFill(['voided_at' => now()])->save(); $this->post('/defenses', $this->defenseData($r))->assertSessionHasErrors('eligibility');
        $payment->forceFill(['voided_at' => null])->save(); $e->forceFill(['cancelled_at' => now()])->save(); $this->post('/defenses', $this->defenseData($r))->assertSessionHasErrors('eligibility');
        $e->forceFill(['cancelled_at' => null])->save(); $r->student->forceFill(['is_archived' => true])->save(); $this->post('/defenses', $this->defenseData($r))->assertSessionHasErrors('eligibility');
        $r->student->forceFill(['is_archived' => false])->save(); Storage::disk('reports')->delete($r->latestVersion->storage_path); $this->post('/defenses', $this->defenseData($r))->assertSessionHasErrors('eligibility');
        $this->assertDatabaseCount('defenses', 0);
    }
    public function test_notes_do_not_add_a_fourth_deposit_or_defense_eligibility_condition(): void
    {
        [$r, , $owner] = $this->acceptedReport(); $this->assertDatabaseCount('grade_sheets', 0);
        $this->assertSame('eligible', app(Eligibility::class)->evaluate($r->fresh()->enrollment)['computed_result']);
        $this->signIn($owner)->get('/defenses')->assertInertia(fn (Assert $v) => $v->where('candidate.readiness.ready', true));
        $this->post('/defenses', $this->defenseData($r))->assertSessionHasNoErrors(); $this->assertDatabaseCount('defenses', 1);
    }
    public function test_global_views_are_reserved_and_jurors_see_only_current_assignments(): void
    {
        [$school, $owner, $d, $president] = $this->defense(true); $other = $this->person(5);
        foreach ([2, 4] as $group) { $this->signIn($this->person($group))->get('/defenses')->assertForbidden(); $this->get('/defenses/'.$d->id)->assertForbidden(); }
        $this->signIn($other)->get('/defenses')->assertInertia(fn (Assert $v) => $v->where('defenses.total', 0)); $this->get('/defenses/'.$d->id)->assertNotFound();
        $this->signIn($president)->get('/defenses/'.$d->id)->assertInertia(fn (Assert $v) => $v->where('canAssess', true)->where('canManage', false)->has('candidates', 0));
        $this->signIn($owner)->get('/defenses/'.$d->id)->assertInertia(fn (Assert $v) => $v->where('canAssess', false)->where('canWithdraw', false));
        $this->signIn($this->person(1))->get('/defenses/'.$d->id)->assertNotFound();
        $this->signIn($this->person(8))->get('/defenses/'.$d->id)->assertInertia(fn (Assert $v) => $v->where('canManage', false));
        $this->signIn($school)->get('/defenses/'.$d->id)->assertInertia(fn (Assert $v) => $v->where('canManage', true));
    }
    public function test_schedule_requires_schooling_and_rejects_spoofed_protected_fields(): void
    {
        [$school, $owner, $d, $p, $e] = $this->defense(); $data = $this->scheduleData($d, $p, $e);
        foreach ([$owner, $p, $this->person(8)] as $actor) { $this->signIn($actor)->put('/defenses/'.$d->id.'/schedule', $data)->assertForbidden(); }
        $this->signIn($school)->put('/defenses/'.$d->id.'/schedule', [...$data, 'status' => 'completed'])->assertSessionHasErrors('status');
        $this->put('/defenses/'.$d->id.'/schedule', $data)->assertSessionHasNoErrors(); $this->assertSame('scheduled', $d->fresh()->status); $this->assertSame(2, $d->currentJurors()->count());
    }
    public function test_jury_must_be_distinct_active_compatible_and_have_exactly_one_president(): void
    {
        [$school, $owner, $d, $p, $e] = $this->defense(); $data = $this->scheduleData($d, $p, $e); $this->signIn($school);
        $cases = [[['user_id' => $p->id, 'role' => 'president']], [['user_id' => $p->id, 'role' => 'president'], ['user_id' => $p->id, 'role' => 'examiner']], [['user_id' => $p->id, 'role' => 'examiner'], ['user_id' => $e->id, 'role' => 'examiner']], [['user_id' => $p->id, 'role' => 'president'], ['user_id' => $e->id, 'role' => 'president']]];
        foreach ($cases as $jury) { $this->put('/defenses/'.$d->id.'/schedule', [...$data, 'jurors' => $jury])->assertSessionHasErrors(); }
        foreach ([$owner, $this->person(2), $this->person(5, ['is_active' => false])] as $bad) { $this->put('/defenses/'.$d->id.'/schedule', $this->scheduleData($d, $p, $bad))->assertSessionHasErrors('jurors'); }
        UserGroup::findOrFail(5)->permissions()->detach('defenses.assess'); $this->signIn($school)->put('/defenses/'.$d->id.'/schedule', $data)->assertSessionHasErrors('jurors');
        $this->assertDatabaseCount('defense_jurors', 0);
    }
    public function test_candidate_cannot_be_on_own_jury_even_with_a_teacher_role(): void
    {
        [$school, $owner, $d, $p] = $this->defense(); $owner->types()->attach(2); $owner->groups()->attach(5);
        $this->signIn($school)->put('/defenses/'.$d->id.'/schedule', $this->scheduleData($d, $p, $owner))->assertSessionHasErrors('jurors');
        $owner->types()->attach(4); $owner->groups()->attach(3); $this->signIn($owner)->put('/defenses/'.$d->id.'/schedule', $this->scheduleData($d, $p, $this->person(5)))->assertForbidden();
    }
    public function test_schedule_rechecks_unanimity_and_rejects_stale_or_invalid_times(): void
    {
        [$school, , $d, $p, $e] = $this->defense(); $data = $this->scheduleData($d, $p, $e); $this->signIn($school);
        $this->put('/defenses/'.$d->id.'/schedule', [...$data, 'revision' => 99])->assertSessionHasErrors('revision');
        $this->put('/defenses/'.$d->id.'/schedule', [...$data, 'starts_at' => '2026-10-21T10:00'])->assertSessionHasErrors('starts_at');
        $this->put('/defenses/'.$d->id.'/schedule', [...$data, 'starts_at' => '2026-02-30T10:00'])->assertSessionHasErrors('starts_at');
        foreach ([14, 241] as $duration) { $this->put('/defenses/'.$d->id.'/schedule', [...$data, 'duration_minutes' => $duration])->assertSessionHasErrors('duration_minutes'); }
        ReportVote::query()->delete(); $this->put('/defenses/'.$d->id.'/schedule', $data)->assertSessionHasErrors('eligibility');
    }
    public function test_room_and_juror_conflicts_block_overlap_but_allow_adjacent_slots(): void
    {
        [, , $first, $p1] = $this->defense(true); [$school, , $second, $p2, $e2] = $this->defense(); $this->signIn($school);
        $this->put('/defenses/'.$second->id.'/schedule', $this->scheduleData($second, $p2, $e2, ['location' => '  SALLE   DE test  ']))->assertSessionHasErrors('starts_at');
        $this->put('/defenses/'.$second->id.'/schedule', $this->scheduleData($second, $p1, $e2, ['location' => 'Autre salle']))->assertSessionHasErrors('starts_at');
        $this->put('/defenses/'.$second->id.'/schedule', $this->scheduleData($second, $p1, $e2, ['starts_at' => '2026-10-23T11:00']))->assertSessionHasNoErrors(); $this->assertSame('scheduled', $second->fresh()->status);
    }
    public function test_meetings_and_defenses_check_each_others_busy_jurors(): void
    {
        $member = $this->person(9); $organizer = $this->person(9); $m = app(ManageMeetings::class)->save($organizer, $this->meetingData([$member]));
        [$school, , $d, , $e] = $this->defense(); $this->signIn($school)->put('/defenses/'.$d->id.'/schedule', $this->scheduleData($d, $member, $e))->assertSessionHasErrors('starts_at');
        app(ManageMeetings::class)->respond($member, $m, ['revision' => 1, 'response_revision' => 1, 'response' => 'declined']);
        $this->put('/defenses/'.$d->id.'/schedule', $this->scheduleData($d, $member, $e))->assertSessionHasNoErrors();
        $this->signIn($organizer)->post('/meetings', $this->meetingData([$member], ['starts_at' => '2026-10-23T10:30']))->assertSessionHasErrors('starts_at');
        $this->signIn($member)->patch('/meetings/'.$m->id.'/response', ['revision' => 1, 'response_revision' => 2, 'response' => 'accepted'])->assertSessionHasErrors('starts_at');
        $this->assertSame('declined', $m->participants()->where('user_id', $member->id)->first()->response);
    }
    public function test_rescheduling_preserves_history_and_removes_former_juror_access(): void
    {
        [$school, , $d, $p, $e] = $this->defense(true); $replacement = $this->person(5);
        $this->signIn($school)->put('/defenses/'.$d->id.'/schedule', $this->scheduleData($d, $replacement, $e, ['starts_at' => '2026-10-24T10:00', 'reason' => 'Président remplacé sur demande']))->assertSessionHasNoErrors();
        $this->assertSame(4, $d->jurors()->count()); $this->assertSame(2, $d->currentJurors()->count()); $this->assertSame(3, $d->fresh()->revision);
        $this->signIn($p)->get('/defenses/'.$d->id)->assertNotFound(); $this->get('/defenses/'.$d->id.'/report')->assertNotFound();
        $this->signIn($replacement)->get('/defenses/'.$d->id.'/report')->assertOk();
        $this->assertDatabaseHas('academic_audits', ['defense_id' => $d->id, 'action' => 'defense.scheduled']);
    }
    public function test_private_report_access_enforces_assignment_and_file_integrity(): void
    {
        [, $owner, $d, $p] = $this->defense(true); $this->signIn($p)->get('/defenses/'.$d->id.'/report')->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/defenses/'.$d->id)->assertInertia(fn (Assert $v) => $v->missing('defense.operation_key')->missing('defense.storage_path')->missing('defense.report_round_id')->missing('activity.0.metadata'));
        $this->signIn($this->person(5))->get('/defenses/'.$d->id.'/report')->assertNotFound();
        $v = $d->version; Storage::disk('reports')->put($v->storage_path, str_repeat('x', $v->size)); $this->signIn($owner)->get('/defenses/'.$d->id.'/report')->assertNotFound();
    }
    public function test_result_requires_current_president_and_end_of_scheduled_slot(): void
    {
        [$school, $owner, $d, $p, $e] = $this->defense(true);
        foreach ([$school, $owner, $e, $this->person(5)] as $actor) { $this->signIn($actor)->post('/defenses/'.$d->id.'/result', $this->resultData($d))->assertForbidden(); }
        $this->signIn($p)->post('/defenses/'.$d->id.'/result', $this->resultData($d))->assertSessionHasErrors('defense');
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(11, 0)); $this->post('/defenses/'.$d->id.'/result', $this->resultData($d))->assertSessionHasNoErrors();
        $this->assertSame('completed', $d->fresh()->status); $this->assertSame(1525, $d->fresh()->score); $this->assertDatabaseCount('grade_sheets', 0);
        $this->signIn($owner)->get('/defenses/'.$d->id)->assertInertia(fn (Assert $v) => $v->where('defense.score', 15.25)->where('defense.outcome', 'validated'));
    }
    public function test_result_rules_distinguish_absence_zero_and_invalid_notes(): void
    {
        [, , $d, $p] = $this->defense(true); $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0)); $this->signIn($p);
        foreach ([null, '20.01', '-1', '12.345'] as $score) { $this->post('/defenses/'.$d->id.'/result', $this->resultData($d, ['score' => $score]))->assertSessionHasErrors('score'); }
        $this->post('/defenses/'.$d->id.'/result', $this->resultData($d, ['outcome' => 'absent', 'score' => '0']))->assertSessionHasErrors('score');
        $this->post('/defenses/'.$d->id.'/result', $this->resultData($d, ['outcome' => 'absent', 'score' => null]))->assertSessionHasNoErrors(); $this->assertNull($d->fresh()->score);
        $this->post('/defenses/'.$d->id.'/result', $this->resultData($d, ['outcome' => 'adjourned', 'score' => '0', 'reason' => 'Correction motivée du procès-verbal']))->assertSessionHasNoErrors(); $this->assertSame(0, $d->fresh()->score);
    }
    public function test_result_correction_is_versioned_audited_and_completed_defense_cannot_be_replanned(): void
    {
        [$school, , $d, $p, $e] = $this->defense(true); $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0)); $a = app(ManageDefenses::class); $a->result($p, $d, $this->resultData($d));
        $this->signIn($p)->post('/defenses/'.$d->id.'/result', $this->resultData($d, ['revision' => 2, 'score' => '16']))->assertSessionHasErrors('revision');
        $this->post('/defenses/'.$d->id.'/result', $this->resultData($d, ['score' => '16', 'reason' => 'Correction du procès-verbal vérifiée']))->assertSessionHasNoErrors();
        $audit = DB::table('academic_audits')->where('defense_id', $d->id)->where('action', 'defense.result_corrected')->first(); $this->assertSame(1525, json_decode($audit->metadata, true)['before']['score']);
        $this->signIn($school)->put('/defenses/'.$d->id.'/schedule', $this->scheduleData($d, $p, $e, ['starts_at' => '2026-10-24T10:00']))->assertSessionHasErrors('defense');
        $this->patch('/defenses/'.$d->id.'/cancel', ['revision' => 4, 'reason' => 'Annulation impossible'])->assertSessionHasErrors('defense');
    }
    public function test_student_can_withdraw_only_before_scheduling_and_schooling_can_reprogram_cancelled_request(): void
    {
        [$school, $owner, $d, $p, $e] = $this->defense(); $this->signIn($owner)->patch('/defenses/'.$d->id.'/cancel', ['revision' => 1, 'reason' => 'Demande retirée par le candidat'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $d->fresh()->status);
        $this->signIn($school)->put('/defenses/'.$d->id.'/schedule', $this->scheduleData($d, $p, $e))->assertSessionHasNoErrors();
        $this->signIn($owner)->patch('/defenses/'.$d->id.'/cancel', ['revision' => 3, 'reason' => 'Demande retirée par le candidat'])->assertForbidden();
        $this->signIn($school)->patch('/defenses/'.$d->id.'/cancel', ['revision' => 3, 'reason' => 'Report demandé par la scolarité'])->assertSessionHasNoErrors(); $this->assertSame('cancelled', $d->fresh()->status);
        $this->assertSame(2, $d->currentJurors()->count()); $this->assertDatabaseCount('defenses', 1);
    }
    public function test_cancelled_defense_frees_room_and_members_but_cannot_receive_a_result(): void
    {
        [$school, , $first, $p, $e] = $this->defense(true); app(ManageDefenses::class)->cancel($school, $first, ['revision' => 2, 'reason' => 'Séance annulée pour test']);
        [, , $second] = $this->defense(); $this->signIn($school)->put('/defenses/'.$second->id.'/schedule', $this->scheduleData($second, $p, $e))->assertSessionHasNoErrors();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0)); $this->signIn($p)->post('/defenses/'.$first->id.'/result', $this->resultData($first))->assertSessionHasErrors('defense');
    }
    public function test_scheduling_failure_rolls_back_previous_jury_and_audit(): void
    {
        [$school, , $d, $p, $e] = $this->defense(true); $before = DB::table('academic_audits')->count(); DefenseJuror::creating(fn () => throw new RuntimeException('Simulated jury failure'));
        try { app(ManageDefenses::class)->schedule($school, $d, $this->scheduleData($d, $p, $e, ['starts_at' => '2026-10-24T10:00'])); $this->fail('Expected failure'); }
        catch (RuntimeException $error) { $this->assertSame('Simulated jury failure', $error->getMessage()); } finally { DefenseJuror::flushEventListeners(); }
        $this->assertSame(2, $d->currentJurors()->count()); $this->assertSame(2, $d->fresh()->revision); $this->assertSame('2026-10-23 10:00', $d->fresh()->starts_at->format('Y-m-d H:i')); $this->assertDatabaseCount('academic_audits', $before);
    }
    public function test_revoked_jury_and_management_permissions_take_effect_immediately(): void
    {
        [$school, , $d, $p, $e] = $this->defense(true); UserGroup::findOrFail(5)->permissions()->detach('defenses.assigned');
        $this->signIn($p)->get('/defenses/'.$d->id)->assertForbidden(); $this->get('/defenses/'.$d->id.'/report')->assertForbidden(); $this->post('/defenses/'.$d->id.'/result', $this->resultData($d))->assertForbidden();
        UserGroup::findOrFail(3)->permissions()->detach('defenses.manage');
        try { app(ManageDefenses::class)->schedule($school, $d, $this->scheduleData($d, $p, $e)); $this->fail('Revoked permission accepted'); } catch (AuthorizationException) {}
        $this->assertSame(2, $d->fresh()->revision);
    }
    public function test_dashboard_counts_and_upcoming_agenda_respect_defense_visibility(): void
    {
        [$school, $owner, $d, $p] = $this->defense(true);
        foreach ([$school, $owner, $p] as $u) { $this->signIn($u)->get('/dashboard')->assertInertia(fn (Assert $v) => $v->where('defenseCount', 1)->has('upcomingDefenses', 1)->where('upcomingDefenses.0.id', $d->id)); }
        $this->signIn($this->person(5))->get('/dashboard')->assertInertia(fn (Assert $v) => $v->where('defenseCount', 0)->has('upcomingDefenses', 0));
        $this->signIn($this->person(2))->get('/dashboard')->assertInertia(fn (Assert $v) => $v->where('defenseCount', null)->where('upcomingDefenses', null));
        $this->signIn($school)->get('/defenses?status=requested')->assertInertia(fn (Assert $v) => $v->where('defenses.total', 0)->where('statistics.scheduled', 1));
        $this->get('/defenses?search='.$d->reference)->assertInertia(fn (Assert $v) => $v->where('defenses.total', 1));
    }
    public function test_csrf_protects_requests_schedule_results_and_cancellation(): void
    {
        [$school, $owner, $d, $p, $e] = $this->defense(true); $this->app->instance('env', 'local');
        $this->signIn($owner)->post('/defenses', [])->assertStatus(419); $this->patch('/defenses/'.$d->id.'/cancel', [])->assertStatus(419);
        $this->signIn($school)->put('/defenses/'.$d->id.'/schedule', [])->assertStatus(419); $this->signIn($p)->post('/defenses/'.$d->id.'/result', [])->assertStatus(419);
    }
}
