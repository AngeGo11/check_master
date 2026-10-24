<?php

namespace Tests\Feature;

use App\Actions\Academic\ManageGrades;
use App\Actions\Collaboration\{ManageMeetings, ManageMessages};
use App\Actions\Reports\ManageReports;
use App\Actions\Schooling\ManageEnrollments;
use App\Models\{Defense, GradeSheet, Report, ReportRound, ReportRoundMember, Student, User, UserGroup};
use App\Services\RoleDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AcademicFixtures;
use Tests\TestCase;

class RoleDashboardTest extends TestCase
{
    use RefreshDatabase, AcademicFixtures;
    protected function setUp(): void { parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 22)->setTime(9, 0)); Storage::fake('reports'); }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }

    public function test_each_compatible_group_has_its_own_dashboard_and_relevant_modules(): void
    {
        $spaces = [1 => 'student', 2 => 'communications', 3 => 'schooling', 4 => 'secretary', 5 => 'teacher', 6 => 'level-manager', 7 => 'program-manager', 8 => 'platform-administrator', 9 => 'validation-committee'];
        foreach ($spaces as $id => $space) {
            $this->signIn($this->person($id))->get('/dashboard')->assertOk()->assertInertia(fn (Assert $p) => $p->component('dashboard')->where('dashboard.space', $space)->has('dashboard.spaces', 1)->has('dashboard.actions.profile'));
        }
        $this->signIn($this->person(1))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('dashboard.panels.student_record')->missing('dashboard.cards.users')->missing('dashboard.panels.levels')->missing('dashboard.cards.reports'));
        $this->signIn($this->person(8))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('dashboard.cards.users')->has('dashboard.panels.groups')->missing('dashboard.panels.student_record')->missing('dashboard.cards.my_grades'));
        $this->signIn($this->person(7))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('dashboard.panels.jury_outcomes')->missing('dashboard.cards.students'));
        $this->signIn($this->person(5))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('dashboard.panels.professional_profile')->missing('dashboard.panels.jury_outcomes'));
    }

    public function test_multigroup_accounts_can_switch_views_without_combining_their_data(): void
    {
        [$owner, $e] = $this->enrollment(); $owner->types()->attach(3); $owner->groups()->attach(8);
        $this->signIn($owner)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.space', 'platform-administrator')->has('dashboard.spaces', 2)->has('dashboard.cards.users')->missing('dashboard.cards.tuition'));
        $this->get('/dashboard?space=student')->assertInertia(fn (Assert $p) => $p->where('dashboard.space', 'student')->where('dashboard.panels.student_record.items.0.id', $e->student_id)->missing('dashboard.cards.users')->missing('dashboard.panels.levels'));
        $this->get('/dashboard?space=platform-administrator')->assertInertia(fn (Assert $p) => $p->where('dashboard.space', 'platform-administrator')->missing('dashboard.panels.student_record'));
    }

    public function test_a_query_parameter_cannot_select_an_unassigned_or_incompatible_group(): void
    {
        $u = $this->person(1); $u->groups()->attach(8); $this->signIn($u)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.space', 'student')->has('dashboard.spaces', 1));
        foreach (['platform-administrator', 'schooling', 'validation-committee', 'unknown'] as $space) { $this->get('/dashboard?space='.$space)->assertForbidden(); }
        $this->get('/dashboard?space[]=student')->assertSessionHasErrors('space');
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.space', 'student'));
    }

    public function test_unassigned_accounts_have_a_small_personal_dashboard_and_no_business_payload(): void
    {
        $u = User::factory()->create(); $this->signIn($u)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.space', 'personal')->has('dashboard.cards', 0)->has('dashboard.panels', 0)->has('dashboard.actions', 1)->where('dashboard.journey', null));
        $this->get('/dashboard?space=student')->assertForbidden();
    }

    public function test_permissions_and_group_compatibility_are_rechecked_and_inertia_payload_is_private(): void
    {
        $u = $this->person(8); $this->signIn($u)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('dashboard.cards.users'))->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        UserGroup::findOrFail(8)->permissions()->detach(['users.view', 'students.view', 'hr.view', 'reports.view']);
        $this->signIn($u)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->missing('dashboard.cards.users')->missing('dashboard.panels.groups')->missing('dashboard.cards.students')->missing('dashboard.cards.teachers')->missing('dashboard.panels.reports')->missing('dashboard.actions.users'));
        $u->types()->sync([1]); $this->signIn($u)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.space', 'personal')->has('dashboard.cards', 0));
    }

    public function test_admin_distribution_counts_distinct_compatible_active_memberships(): void
    {
        $admin = $this->person(8); $member = $this->person(9); $member->types()->attach(3); // Two compatible types must not double-count.
        $member->groups()->attach(8); $bad = $this->person(1); $bad->groups()->attach(8); $this->person(9, ['is_active' => false]);
        $data = app(RoleDashboard::class)->build($admin);
        $groups = collect($data['panels']['groups']['items'])->keyBy('id');
        $this->assertSame(1, $groups[9]['value']); $this->assertSame(2, $groups[8]['value']); $this->assertSame(3, $data['cards']['users']['value']);
        $this->assertArrayNotHasKey('password', $data); $this->assertArrayNotHasKey('user_id', $groups[8]);
    }

    public function test_student_identity_is_explicit_and_other_students_records_are_never_returned(): void
    {
        [$owner, $e] = $this->enrollment(); [, $other] = $this->enrollment();
        $e->student->forceFill(['email' => $other->student->user->email])->save();
        $this->signIn($owner)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.panels.student_record.items.0.id', $e->student_id)->has('dashboard.panels.student_record.items', 1)->where('dashboard.cards.tuition.value', '100 000 FCFA')->missing('dashboard.panels.tuition'));
        $this->signIn($this->person(1))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('dashboard.panels.student_record.items', 0)->missing('dashboard.cards.tuition')->where('dashboard.journey', null));
    }

    public function test_student_can_go_straight_to_deposit_once_the_three_criteria_are_met(): void
    {
        [$owner, $e] = $this->enrollment(5); $admin = $this->person(8); $a = app(ManageEnrollments::class);
        $a->recordPayment($admin, $e, ['amount' => 100000, 'method' => 'cash', 'cheque_number' => null, 'purpose' => 'Test', 'paid_on' => '2026-10-20', 'operation_key' => (string) Str::uuid(), 'revision' => 1]);
        $a->saveInternship($owner, $e, ['company' => 'Entreprise fictive', 'title' => 'Stage fictif', 'starts_on' => '2026-10-01', 'ends_on' => '2027-01-01', 'revision' => 2]);
        $this->signIn($owner)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.journey.eligible', true)->has('dashboard.journey.criteria', 3)->where('dashboard.actions.report.label', 'Déposer mon mémoire')->where('dashboard.cards.tuition.value', '0 FCFA'));
        $this->assertDatabaseCount('grade_sheets', 0); $this->assertDatabaseCount('eligibility_reviews', 0);
        $e->payments()->first()->forceFill(['voided_at' => now()])->save();
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.journey.eligible', false)->where('dashboard.cards.tuition.value', '100 000 FCFA'));
    }

    public function test_student_only_sees_published_grades_even_during_correction(): void
    {
        [$school, $owner, $s] = $this->sheet();
        $this->signIn($owner)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.my_grades.value', 0)->has('dashboard.panels.my_grades.items', 0));
        $this->publishSheet($school, $s); $a = app(ManageGrades::class);
        $a->reopen($school, $s, ['revision' => 3, 'reason' => 'Correction fictive']); $a->save($school, $s, ['revision' => 4, 'marks' => $this->marks($s, '18')]);
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.my_grades.value', 1)->where('dashboard.panels.my_grades.items.0.value', '12,00/20')->missing('dashboard.panels.my_grades.items.0.marks'));
        UserGroup::findOrFail(1)->permissions()->detach('grades.self'); $this->signIn($owner)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->missing('dashboard.cards.my_grades')->missing('dashboard.panels.my_grades'));
    }

    public function test_schooling_focuses_on_current_non_cancelled_inscriptions_and_ignores_void_payments(): void
    {
        [$owner, $e] = $this->enrollment(); [, $cancelled] = $this->enrollment(); $cancelled->forceFill(['cancelled_at' => now()])->save();
        $admin = $this->person(8); app(ManageEnrollments::class)->recordPayment($admin, $e, ['amount' => 100000, 'method' => 'cash', 'cheque_number' => null, 'purpose' => 'Test', 'paid_on' => '2026-10-20', 'operation_key' => (string) Str::uuid(), 'revision' => 1]);
        $school = $this->person(3); $this->signIn($school)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.enrollments.value', 1)->where('dashboard.cards.tuition_due.value', 0)->missing('dashboard.cards.users'));
        $e->payments()->first()->forceFill(['voided_at' => now()])->save();
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.tuition_due.value', 1)->where('dashboard.panels.tuition.items.0.value', '100 000 FCFA'));
        $e->academicYear->forceFill(['status' => 'closed', 'current_slot' => null])->save();
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.enrollments.value', 0)->has('dashboard.panels.tuition.items', 0));
    }

    public function test_schooling_has_draft_bulletins_and_pending_defenses_without_admin_access(): void
    {
        [$school, , $s] = $this->sheet(); $this->enrollment(5); [, , $d] = $this->defense();
        $this->signIn($school)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.grade_drafts.value', 1)->where('dashboard.panels.grade_drafts.items.0.id', $s->id)->where('dashboard.cards.defenses_requested.value', 1)->where('dashboard.panels.defenses_requested.items.0.id', $d->id)->missing('dashboard.panels.groups'));
        UserGroup::findOrFail(3)->permissions()->detach(['grades.view', 'defenses.view']);
        $this->signIn($school)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->missing('dashboard.panels.grade_drafts')->missing('dashboard.panels.defenses_requested')->missing('dashboard.actions.grades')->missing('dashboard.actions.defenses'));
    }

    public function test_communications_prioritizes_submitted_reports_and_cannot_approve_from_dashboard(): void
    {
        $this->person(9); [$r] = $this->transmittedReport(); $r = $r->fresh(); $r->forceFill(['status' => 'submitted'])->save();
        $this->assertDatabaseHas('student_reports', ['id' => $r->id, 'status' => 'submitted']);
        $u = $this->person(2); $this->signIn($u)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.to_forward.value', 1)->where('dashboard.panels.to_forward.items.0.id', $r->id)->where('dashboard.actions.reports.label', 'Transmettre les mémoires')->missing('dashboard.cards.votes_due')->missing('dashboard.cards.grade_drafts'));
        UserGroup::findOrFail(2)->permissions()->detach('reports.forward');
        $this->signIn($u)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.actions.reports.label', 'Consulter les mémoires'));
    }

    public function test_commission_pending_vote_uses_current_round_and_excludes_its_already_recorded_vote(): void
    {
        $member = $this->person(9); $second = $this->person(9); [$r, $round] = $this->transmittedReport();
        $this->signIn($member)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.votes_due.value', 1)->where('dashboard.panels.votes_due.items.0.id', $r->id));
        app(ManageReports::class)->vote($member, $r, $round, ['decision' => 'approved', 'comment' => null]);
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.votes_due.value', 0)->has('dashboard.panels.votes_due.items', 0)->where('dashboard.cards.reports_pending.value', 1));
        // A new transmission of the same version must not reuse the old round's vote.
        $this->person(9);
        $access = app(\App\Services\ReportAccess::class);
        app(ManageReports::class)->forward($this->person(2), $r, ['revision' => $r->fresh()->revision, 'committee_fingerprint' => $access->fingerprint($access->roster()->modelKeys()), 'reason' => 'Composition de commission actualisée']);
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.votes_due.value', 1));
    }

    public function test_admin_who_switches_to_commission_keeps_personal_assignment_scope(): void
    {
        $member = $this->person(9); [$r] = $this->transmittedReport(); $member->types()->attach(3); $member->groups()->attach(8);
        $late = $this->person(9); [$other] = $this->transmittedReport();
        DB::table('report_round_members')->where('user_id', $member->id)->whereIn('report_round_id', $other->latestVersion->rounds()->select('id'))->delete();
        $this->signIn($member)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.reports.value', 2));
        $this->get('/dashboard?space=validation-committee')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.reports.value', 1)->where('dashboard.panels.reports.items.0.id', $r->id)->missing('dashboard.cards.users'));
    }

    public function test_jury_widgets_only_include_current_members_and_results_due_for_president(): void
    {
        [$school, $owner, $d, $president, $examiner] = $this->defense(true);
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(12, 0));
        $this->signIn($president)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.results_due.value', 1)->where('dashboard.cards.jury_upcoming.value', 0)->where('dashboard.panels.results_due.items.0.id', $d->id));
        $this->signIn($examiner)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.results_due.value', 0)->has('dashboard.panels.results_due.items', 0));
        $d->currentJurors()->where('user_id', $president->id)->update(['current_slot' => null, 'removed_at' => now()]);
        $this->signIn($president)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.results_due.value', 0)->has('dashboard.panels.defenses.items', 0));
        $this->signIn($this->person(7))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.panels.jury_outcomes.items.0.value', 0));
    }

    public function test_meeting_invites_require_current_participation_and_no_response_does_not_mark_read(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture(); $before = DB::table('collaboration_audits')->count();
        $this->signIn($guest)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.meeting_replies.value', 1)->where('dashboard.cards.unread.value', 1));
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.unread.value', 1)); $this->assertDatabaseCount('collaboration_audits', $before);
        app(ManageMeetings::class)->respond($guest, $m, ['revision' => 1, 'response_revision' => 1, 'response' => 'declined']);
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.meeting_replies.value', 0));
        $m->participants()->where('user_id', $guest->id)->update(['removed_at' => now()]);
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('dashboard.panels.meetings.items', 0));
    }

    public function test_message_preview_only_contains_own_unread_inbox_and_is_bounded(): void
    {
        $sender = $this->person(2); $owner = $this->person(1); $other = $this->person(1); $a = app(ManageMessages::class);
        for ($i = 0; $i < 6; $i++) { $a->save($sender, $this->mailData([$owner], ['subject' => 'Personnel '.$i])); }
        $a->save($sender, $this->mailData([$other], ['subject' => 'Confidentiel autre étudiant']));
        $this->signIn($owner)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.unread.value', 6)->has('dashboard.panels.messages.items', 5)->where('dashboard.panels.messages.items.0.title', 'Personnel 5')->missing('dashboard.panels.messages.items.0.body'))->assertDontSee('Confidentiel autre étudiant');
        DB::table('message_recipients')->where('user_id', $owner->id)->update(['folder' => 'trash']);
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.unread.value', 0)->has('dashboard.panels.messages.items', 0));
    }

    public function test_secretary_has_directory_tools_without_financial_or_grade_payload(): void
    {
        [, $e] = $this->enrollment(); $e->student->forceFill(['user_id' => null])->save();
        $this->signIn($this->person(4))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.unlinked_students.value', 1)->where('dashboard.panels.students.items.0.id', $e->student_id)->has('dashboard.actions.students')->missing('dashboard.cards.tuition_due')->missing('dashboard.panels.grade_drafts')->missing('dashboard.panels.reports'));
    }

    public function test_dashboard_omits_raw_models_history_secrets_and_obsolete_global_props(): void
    {
        [$r, , $owner] = $this->acceptedReport();
        $this->signIn($owner)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->missing('studentCount')->missing('reportCount')->missing('latestReport')->missing('dashboard.panels.my_report.items.0.storage_path')->missing('dashboard.panels.my_report.items.0.operation_key')->missing('dashboard.journey.fingerprint')->missing('dashboard.journey.review'));
        $this->assertDatabaseCount('defenses', 0); $this->assertDatabaseCount('grade_sheets', 0);
    }
}
