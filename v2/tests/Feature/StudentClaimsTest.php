<?php

namespace Tests\Feature;

use App\Actions\Academic\ManageGrades;
use App\Actions\Claims\ManageClaims;
use App\Models\{ClaimEvent, Enrollment, GradePublication, StudentClaim, User, UserGroup};
use App\Services\{ClaimFiles, RoleDashboard};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Mail, Storage};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AcademicFixtures;
use Tests\TestCase;

class StudentClaimsTest extends TestCase
{
    use RefreshDatabase, AcademicFixtures;

    protected function setUp(): void { parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 22)->setTime(9, 0)); Storage::fake('claims'); Mail::fake(); }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function data(Enrollment $e, array $overrides = []): array
    {
        return [...['enrollment_id' => $e->id, 'subject' => 'Vérification de mon dossier', 'body' => 'Merci de vérifier les éléments de mon dossier fictif.',
            'grade_publication_id' => null, 'subject_keys' => [], 'operation_key' => (string) Str::uuid()], ...$overrides];
    }
    private function claim(bool $file = false): array
    {
        [$owner, $e] = $this->enrollment(); $data = $this->data($e);
        $claim = app(ManageClaims::class)->create($owner, $data, $file ? $this->pdf() : null);
        return [$owner, $claim, $e, $data];
    }
    private function action(StudentClaim $c, string $action, array $overrides = []): array
    {
        return [...['revision' => $c->fresh()->revision, 'action' => $action, 'body' => 'Explication fictive de cette action.', 'assigned_to' => null, 'operation_key' => (string) Str::uuid()], ...$overrides];
    }
    private function act(User $u, StudentClaim $c, string $action, array $overrides = [])
    {
        return $this->signIn($u)->post('/claims/'.$c->id.'/actions', $this->action($c, $action, $overrides));
    }

    public function test_access_catalog_and_distinct_personal_service_assigned_routes(): void
    {
        $this->assertDatabaseCount('permissions', 48); $this->assertDatabaseCount('group_permission', 86);
        $this->get('/my-claims')->assertRedirect('/login'); $this->get('/claims')->assertRedirect('/login');
        foreach ([2, 4, 5, 6, 9] as $g) { $this->signIn($this->person($g))->get('/claims')->assertForbidden(); $this->get('/my-claims')->assertForbidden(); }
        foreach ([3, 8] as $g) { $this->signIn($this->person($g))->get('/claims')->assertOk(); $this->get('/my-claims/create')->assertForbidden(); }
        $this->signIn($this->person(7))->get('/claims')->assertOk()->assertInertia(fn (Assert $p) => $p->where('space', 'assigned')->where('claims.total', 0));
        $this->get('/claims?space=service')->assertForbidden();
        $this->signIn($this->person(1))->get('/my-claims/create')->assertOk()->assertInertia(fn (Assert $p) => $p->has('enrollments', 0));
        $this->get('/claims')->assertForbidden();
        $bad = $this->person(1); $bad->groups()->attach(3); $this->signIn($bad)->get('/claims')->assertForbidden();
    }

    public function test_only_explicitly_linked_student_can_submit_and_payload_cannot_choose_owner_or_status(): void
    {
        [$owner, $e] = $this->enrollment(); [$other, $otherE] = $this->enrollment();
        $e->student->forceFill(['email' => $other->email])->save();
        $data = $this->data($e, ['status' => 'resolved', 'submitted_by' => $other->id, 'assigned_to' => $this->person(7)->id]);
        unset($data['subject_keys']); // Multipart browsers omit empty arrays.
        $this->signIn($owner)->post('/my-claims', $data)->assertSessionHasNoErrors();
        $c = StudentClaim::firstOrFail(); $this->assertSame('submitted', $c->status); $this->assertSame($owner->id, $c->submitted_by); $this->assertNull($c->assigned_to);
        $this->assertDatabaseCount('claim_events', 1); $this->assertDatabaseCount('internal_messages', 0); Mail::assertNothingSent();
        $this->signIn($other)->post('/my-claims', $this->data($e))->assertNotFound();
        $this->get('/my-claims/'.$c->id)->assertNotFound();
        $this->signIn($owner)->post('/my-claims', $this->data($otherE))->assertNotFound();
    }

    public function test_current_and_past_enrollments_work_without_tuition_stage_or_grade_gate(): void
    {
        [$owner, $e] = $this->enrollment(); $e->academicYear->forceFill(['status' => 'closed', 'current_slot' => null])->save();
        $this->signIn($owner)->post('/my-claims', $this->data($e))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('student_claims', 1); $this->assertDatabaseCount('grade_sheets', 0);
        $this->assertDatabaseCount('tuition_payments', 0); $this->assertDatabaseCount('enrollment_internships', 0);
    }

    public function test_cancelled_or_archived_records_block_new_submissions_but_keep_existing_history(): void
    {
        [$owner, $c, $e] = $this->claim(); $e->forceFill(['cancelled_at' => now()])->save();
        $this->signIn($owner)->post('/my-claims', $this->data($e))->assertSessionHasErrors('enrollment_id');
        $e->forceFill(['cancelled_at' => null])->save(); $e->student->forceFill(['is_archived' => true])->save();
        $this->post('/my-claims', $this->data($e))->assertSessionHasErrors('enrollment_id');
        $this->get('/my-claims/'.$c->id)->assertOk();
        $this->get('/my-claims/create')->assertInertia(fn (Assert $p) => $p->has('enrollments', 0));
    }

    public function test_claim_can_target_only_a_published_version_and_real_subjects_of_its_enrollment(): void
    {
        [$school, $owner, $sheet] = $this->sheet(); $this->publishSheet($school, $sheet); $pub = $sheet->fresh()->latestPublication;
        $this->signIn($owner)->get('/my-claims/create?publication='.$pub->id)->assertInertia(fn (Assert $p) => $p->where('selectedPublication', $pub->id)->has('publications', 1)->has('publications.0.subjects', 4));
        $data = $this->data($sheet->enrollment, ['grade_publication_id' => $pub->id, 'subject_keys' => ['ue:A', 'ecue:A1']]);
        $this->post('/my-claims', $data)->assertSessionHasNoErrors(); $c = StudentClaim::firstOrFail();
        $this->assertSame(['ecue:A1', 'ue:A'], array_column($c->subjects, 'key'));
        $this->post('/my-claims', [...$data, 'operation_key' => (string) Str::uuid(), 'subject_keys' => ['ecue:INVENTED']])->assertSessionHasErrors('subject_keys');
        $this->post('/my-claims', [...$data, 'operation_key' => (string) Str::uuid(), 'subject_keys' => []])->assertSessionHasErrors('subject_keys');
        [$other, $otherE] = $this->enrollment();
        $this->signIn($other)->get('/my-claims/create?publication='.$pub->id)->assertNotFound();
        $this->post('/my-claims', $this->data($otherE, ['grade_publication_id' => $pub->id, 'subject_keys' => ['ue:A']]))->assertNotFound();
        $a = app(ManageGrades::class); $a->reopen($school, $sheet, ['revision' => $sheet->fresh()->revision, 'reason' => 'Correction fictive']);
        $a->save($school, $sheet, ['revision' => $sheet->fresh()->revision, 'marks' => $this->marks($sheet, '18')]);
        $this->signIn($owner)->get('/my-claims/create')->assertInertia(fn (Assert $p) => $p->has('publications', 1)->missing('publications.0.marks')->missing('publications.0.subjects.0.score'));
        $a->publish($school, $sheet, ['revision' => $sheet->fresh()->revision, 'decision' => 'validated', 'reason' => 'Correction publiée']);
        $this->get('/my-claims/'.$c->id)->assertInertia(fn (Assert $p) => $p->where('claim.publication.number', 1)->where('claim.publication.href', '/my-grades/'.$sheet->id.'?publication='.$pub->id));
        $this->assertSame($pub->id, $c->fresh()->grade_publication_id);
    }

    public function test_no_draft_grades_or_revoked_grade_permission_can_be_used_to_select_a_bulletin(): void
    {
        [$school, $owner, $s] = $this->sheet();
        $this->signIn($owner)->get('/my-claims/create')->assertInertia(fn (Assert $p) => $p->has('publications', 0));
        $this->publishSheet($school, $s); $pub = $s->fresh()->latestPublication;
        UserGroup::findOrFail(1)->permissions()->detach('grades.self');
        $this->signIn($owner)->get('/my-claims/create')->assertInertia(fn (Assert $p) => $p->has('publications', 0));
        $this->post('/my-claims', $this->data($s->enrollment, ['grade_publication_id' => $pub->id, 'subject_keys' => ['ue:A']]))->assertForbidden();
    }

    public function test_creation_retries_do_not_duplicate_records_or_files_and_changed_payload_is_rejected(): void
    {
        [$owner, $e] = $this->enrollment(); $data = $this->data($e);
        $this->signIn($owner)->post('/my-claims', [...$data, 'document' => $this->pdf()])->assertSessionHasNoErrors();
        $this->post('/my-claims', [...$data, 'document' => $this->pdf()])->assertSessionHasNoErrors();
        $this->post('/my-claims', [...$data, 'subject' => 'Demande différente', 'document' => $this->pdf()])->assertSessionHasErrors('operation_key');
        $this->post('/my-claims', [...$data, 'document' => $this->pdf('Autre contenu')])->assertSessionHasErrors('operation_key');
        $this->assertDatabaseCount('student_claims', 1); $this->assertDatabaseCount('claim_events', 1); $this->assertCount(1, Storage::disk('claims')->allFiles());
    }

    public function test_schooling_review_reply_resolution_and_reopening_preserve_all_steps(): void
    {
        [$owner, $c] = $this->claim(); $school = $this->person(3); $admin = $this->person(8);
        $this->signIn($admin)->get('/claims/'.$c->id)->assertInertia(fn (Assert $p) => $p->has('actions', 0));
        $this->act($admin, $c, 'review')->assertForbidden();
        $this->act($school, $c, 'review')->assertSessionHasNoErrors(); $this->assertSame('in_review', $c->fresh()->status);
        $this->act($owner, $c, 'comment', ['body' => 'Voici mon complément étudiant.'])->assertSessionHasNoErrors();
        $this->act($school, $c, 'resolve', ['body' => 'Réponse finale de la scolarité.'])->assertSessionHasNoErrors();
        $this->assertSame('resolved', $c->fresh()->status);
        $this->act($owner, $c, 'comment')->assertSessionHasErrors('action');
        $this->act($school, $c, 'reopen', ['body' => 'Nouvel élément à examiner.'])->assertSessionHasNoErrors();
        $this->assertSame('in_review', $c->fresh()->status); $this->assertDatabaseCount('claim_events', 5);
        $this->signIn($owner)->get('/my-claims/'.$c->id)->assertInertia(fn (Assert $p) => $p->where('events.total', 5)->where('events.data.1.body', 'Réponse finale de la scolarité.'));
    }

    public function test_escalation_is_personal_and_reassignment_removes_previous_recipient_access(): void
    {
        [$owner, $c] = $this->claim(true); $school = $this->person(3); $manager = $this->person(7); $next = $this->person(7);
        $this->signIn($manager)->get('/claims/'.$c->id)->assertNotFound();
        $this->act($school, $c, 'escalate', ['assigned_to' => $manager->id])->assertSessionHasNoErrors();
        $this->signIn($manager)->get('/claims/'.$c->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('claim.status', 'escalated')->where('actions', ['resolve', 'comment']));
        $this->get('/claims/'.$c->id.'/document')->assertOk();
        $this->signIn($next)->get('/claims/'.$c->id)->assertNotFound();
        $this->act($school, $c, 'resolve')->assertSessionHasErrors('action');
        $this->act($school, $c, 'escalate', ['assigned_to' => $next->id])->assertSessionHasNoErrors();
        $this->signIn($manager)->get('/claims/'.$c->id)->assertNotFound(); $this->get('/claims/'.$c->id.'/document')->assertNotFound();
        $this->act($next, $c, 'resolve')->assertSessionHasNoErrors();
        $this->signIn($owner)->get('/my-claims/'.$c->id)->assertInertia(fn (Assert $p) => $p->where('claim.status', 'resolved')->where('events.data.1.assignee_name', $next->name)->where('events.data.2.assignee_name', $manager->name));
        $this->act($school, $c, 'reopen')->assertSessionHasNoErrors(); $this->assertNull($c->fresh()->assigned_to);
        $this->signIn($next)->get('/claims/'.$c->id)->assertNotFound();
    }

    public function test_only_active_compatible_authorized_managers_can_receive_a_transfer(): void
    {
        [, $c] = $this->claim(); $school = $this->person(3); $bad = $this->person(1); $bad->groups()->attach(7);
        foreach ([$bad, $this->person(6), $this->person(7, ['is_active' => false]), $this->person(7, ['must_change_password' => true])] as $u) {
            $this->act($school, $c, 'escalate', ['assigned_to' => $u->id])->assertSessionHasErrors('assigned_to');
        }
        $good = $this->person(7); UserGroup::findOrFail(7)->permissions()->detach('claims.assigned');
        $this->act($school, $c, 'escalate', ['assigned_to' => $good->id])->assertSessionHasErrors('assigned_to');
        $this->assertSame('submitted', $c->fresh()->status); $this->assertDatabaseCount('claim_events', 1);
    }

    public function test_student_cannot_process_own_claim_even_with_multiple_staff_roles(): void
    {
        [$owner, $c] = $this->claim(); $owner->types()->attach([3, 4]); $owner->groups()->attach([3, 7, 8]);
        foreach (['review', 'resolve', 'reopen', 'escalate'] as $action) {
            $this->act($owner, $c, $action, ['assigned_to' => $owner->id])->assertForbidden();
        }
        $this->act($this->person(3), $c, 'escalate', ['assigned_to' => $owner->id])->assertSessionHasErrors('assigned_to');
        $this->act($owner, $c, 'comment')->assertSessionHasNoErrors();
        $this->signIn($owner)->get('/my-claims/'.$c->id)->assertInertia(fn (Assert $p) => $p->where('actions', ['comment', 'withdraw']));
    }

    public function test_owner_can_withdraw_only_before_processing_and_no_history_is_deleted(): void
    {
        [$owner, $c] = $this->claim(true);
        $this->act($owner, $c, 'withdraw')->assertSessionHasNoErrors(); $this->assertSame('withdrawn', $c->fresh()->status);
        $this->signIn($owner)->get('/my-claims/'.$c->id)->assertInertia(fn (Assert $p) => $p->has('actions', 0)->where('events.total', 2));
        $this->get('/claims/'.$c->id.'/document')->assertOk();
        $this->act($this->person(3), $c, 'reopen')->assertSessionHasErrors('action');
        [$other, $open] = $this->claim(); $this->act($this->person(3), $open, 'review')->assertSessionHasNoErrors();
        $this->act($other, $open, 'withdraw')->assertSessionHasErrors('action');
        $this->assertDatabaseCount('student_claims', 2);
    }

    public function test_actions_reject_stale_revisions_but_exact_retries_are_idempotent(): void
    {
        [, $c] = $this->claim(); $school = $this->person(3); $data = $this->action($c, 'review');
        $this->signIn($school)->post('/claims/'.$c->id.'/actions', $data)->assertSessionHasNoErrors();
        $this->post('/claims/'.$c->id.'/actions', $data)->assertSessionHasNoErrors();
        $this->post('/claims/'.$c->id.'/actions', [...$data, 'body' => 'Autre commentaire'])->assertSessionHasErrors('operation_key');
        $this->post('/claims/'.$c->id.'/actions', [...$data, 'operation_key' => (string) Str::uuid(), 'action' => 'resolve'])->assertSessionHasErrors('revision');
        $this->assertSame(2, $c->fresh()->revision); $this->assertDatabaseCount('claim_events', 2);
    }

    public function test_permissions_are_rechecked_after_revocation_and_relinking_removes_former_student_access(): void
    {
        [$owner, $c, $e] = $this->claim(true); $school = $this->person(3); $manager = $this->person(7);
        $this->act($school, $c, 'escalate', ['assigned_to' => $manager->id])->assertSessionHasNoErrors();
        UserGroup::findOrFail(7)->permissions()->detach('claims.assigned');
        $this->signIn($manager)->get('/claims/'.$c->id)->assertForbidden(); $this->get('/claims/'.$c->id.'/document')->assertNotFound();
        $this->act($manager, $c, 'resolve')->assertForbidden();
        UserGroup::findOrFail(3)->permissions()->detach('claims.manage'); $this->act($school, $c, 'escalate', ['assigned_to' => $manager->id])->assertForbidden();
        $e->student->forceFill(['user_id' => null])->save();
        $this->signIn($owner)->get('/my-claims/'.$c->id)->assertNotFound(); $this->get('/claims/'.$c->id.'/document')->assertNotFound();
    }

    public function test_private_pdf_downloads_enforce_scope_integrity_and_do_not_expose_storage_fields(): void
    {
        [$owner, $c] = $this->claim(true); [$other] = $this->claim();
        $this->signIn($other)->get('/claims/'.$c->id.'/document')->assertNotFound();
        $this->signIn($owner)->get('/my-claims/'.$c->id)->assertHeader('Cache-Control', 'max-age=0, no-store, private')->assertInertia(fn (Assert $p) => $p->missing('claim.storage_path')->missing('claim.sha256')->missing('claim.submission_hash')->missing('events.data.0.actor_user_id')->missing('events.data.0.request_hash'));
        $this->get('/claims/'.$c->id.'/document')->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Cache-Control', 'max-age=0, no-store, private');
        $disk = Storage::disk('claims'); $disk->put($c->storage_path, str_repeat('x', $c->size));
        $this->get('/claims/'.$c->id.'/document')->assertNotFound(); $disk->delete($c->storage_path);
        $this->get('/claims/'.$c->id.'/document')->assertNotFound();
    }

    public function test_invalid_or_failed_uploads_leave_no_partial_claim(): void
    {
        [$owner, $e] = $this->enrollment();
        $this->signIn($owner)->post('/my-claims', [...$this->data($e), 'document' => UploadedFile::fake()->createWithContent('fake.pdf', '<html>not a PDF</html>')])->assertSessionHasErrors('document');
        $this->post('/my-claims', [...$this->data($e), 'document' => UploadedFile::fake()->create('large.pdf', 10241, 'application/pdf')])->assertSessionHasErrors('document');
        $this->mock(ClaimFiles::class, function ($mock) {
            $mock->shouldReceive('fingerprint')->once()->andReturn(str_repeat('a', 64));
            $mock->shouldReceive('store')->once()->andThrow(\Illuminate\Validation\ValidationException::withMessages(['document' => 'Stockage indisponible.']));
        });
        $this->post('/my-claims', [...$this->data($e), 'document' => $this->pdf()])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('student_claims', 0); $this->assertDatabaseCount('claim_events', 0); $this->assertCount(0, Storage::disk('claims')->allFiles());
    }

    public function test_invalid_forms_cannot_publish_empty_responses_or_fabricated_states(): void
    {
        [$owner, $c, $e] = $this->claim();
        $this->signIn($owner)->post('/my-claims', $this->data($e, ['subject' => '', 'body' => '', 'operation_key' => 'bad']))->assertSessionHasErrors(['subject', 'body', 'operation_key']);
        $this->act($this->person(3), $c, 'resolve', ['body' => ''])->assertSessionHasErrors('body');
        $this->act($owner, $c, 'invented')->assertSessionHasErrors('action');
        $this->assertSame('submitted', $c->fresh()->status);
    }

    public function test_failed_history_write_rolls_back_the_claim_and_removes_its_stored_pdf(): void
    {
        [$owner, $e] = $this->enrollment();
        ClaimEvent::creating(fn () => throw new \RuntimeException('Échec simulé de l’historique'));
        try {
            app(ManageClaims::class)->create($owner, $this->data($e), $this->pdf());
            $this->fail('La transaction devait échouer.');
        } catch (\RuntimeException $error) {
            $this->assertSame('Échec simulé de l’historique', $error->getMessage());
        } finally {
            ClaimEvent::flushEventListeners();
        }
        $this->assertDatabaseCount('student_claims', 0); $this->assertDatabaseCount('claim_events', 0);
        $this->assertCount(0, Storage::disk('claims')->allFiles());
    }

    public function test_lists_filters_counts_and_history_pagination_stay_inside_the_role_scope(): void
    {
        [$owner, $c, $e] = $this->claim(); [, $other] = $this->claim(); $school = $this->person(3);
        for ($i = 0; $i < 21; $i++) { app(ManageClaims::class)->act($owner, $c, $this->action($c, 'comment', ['body' => 'Complément fictif numéro '.$i])); }
        $this->signIn($owner)->get('/my-claims/'.$c->id)->assertInertia(fn (Assert $p) => $p->has('events.data', 20)->where('events.total', 22));
        $this->get('/my-claims/'.$c->id.'?page=2')->assertInertia(fn (Assert $p) => $p->has('events.data', 2));
        $this->get('/my-claims?search='.$c->reference)->assertInertia(fn (Assert $p) => $p->where('claims.total', 1)->where('statistics.total', 1));
        $this->get('/my-claims?search='.$other->reference)->assertInertia(fn (Assert $p) => $p->where('claims.total', 0)->where('statistics.total', 0));
        for ($i = 0; $i < 20; $i++) { app(ManageClaims::class)->create($owner, $this->data($e)); }
        $this->get('/my-claims')->assertInertia(fn (Assert $p) => $p->has('claims.data', 20)->where('claims.total', 21));
        $this->get('/my-claims?page=2')->assertInertia(fn (Assert $p) => $p->has('claims.data', 1));
        $this->act($school, $c, 'resolve')->assertSessionHasNoErrors();
        $this->signIn($owner)->get('/my-claims?status=resolved')->assertInertia(fn (Assert $p) => $p->where('claims.total', 1)->where('statistics.total', 21)->where('statistics.resolved', 1));
        $this->get('/my-claims?status=bad')->assertSessionHasErrors('status');
        $this->get('/my-claims?page=0')->assertSessionHasErrors('page');
    }

    public function test_dashboard_widgets_use_selected_role_scope_and_never_mix_admin_student_or_manager_data(): void
    {
        [$owner, $c] = $this->claim(); [, $other] = $this->claim(); $school = $this->person(3); $manager = $this->person(7);
        $this->act($school, $other, 'escalate', ['assigned_to' => $manager->id])->assertSessionHasNoErrors();
        $owner->types()->attach(3); $owner->groups()->attach(8); $manager->groups()->attach(8);
        $this->signIn($owner)->get('/dashboard?space=student')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.claims.value', 1)->where('dashboard.panels.claims.items.0.id', $c->id));
        $this->get('/dashboard?space=platform-administrator')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.claims.value', 2));
        $this->signIn($manager)->get('/dashboard?space=program-manager')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.claims.value', 1)->where('dashboard.panels.claims.items.0.id', $other->id));
        $this->get('/claims?space=assigned')->assertInertia(fn (Assert $p) => $p->where('claims.total', 1));
        $this->get('/claims?space=service')->assertInertia(fn (Assert $p) => $p->where('claims.total', 2));
        $this->signIn($this->person(4))->get('/dashboard')->assertInertia(fn (Assert $p) => $p->missing('dashboard.cards.claims'));
        UserGroup::findOrFail(1)->permissions()->detach('claims.self');
        $this->signIn($owner)->get('/dashboard?space=student')->assertInertia(fn (Assert $p) => $p->missing('dashboard.cards.claims'));
    }

    public function test_resolving_a_grade_claim_never_changes_a_grade_publication(): void
    {
        [$school, $owner, $s] = $this->sheet(); $this->publishSheet($school, $s); $pub = $s->fresh()->latestPublication; $before = $pub->result;
        $c = app(ManageClaims::class)->create($owner, $this->data($s->enrollment, ['grade_publication_id' => $pub->id, 'subject_keys' => ['ecue:A1']]));
        $manager = $this->person(7); $this->act($school, $c, 'escalate', ['assigned_to' => $manager->id])->assertSessionHasNoErrors();
        $this->signIn($manager)->get('/claims/'.$c->id)->assertInertia(fn (Assert $p) => $p->where('claim.publication.href', null)->missing('claim.marks'));
        $this->act($manager, $c, 'resolve')->assertSessionHasNoErrors();
        $this->assertSame($before, $pub->fresh()->result); $this->assertSame('published', $s->fresh()->status); $this->assertDatabaseCount('grade_publications', 1);
    }

    public function test_claim_permissions_cannot_be_delegated_to_unrelated_groups_and_manage_requires_view(): void
    {
        $admin = $this->person(8); $this->signIn($admin)->put('/settings/groups/4', ['permissions' => ['claims.view']])->assertSessionHasErrors('permissions');
        $this->put('/settings/groups/3', ['permissions' => ['claims.manage']])->assertSessionHasErrors('permissions');
        $this->put('/settings/groups/6', ['permissions' => ['claims.assigned']])->assertSessionHasErrors('permissions');
    }
    public function test_claim_creation_and_actions_require_csrf_in_real_requests(): void
    {
        [$owner, $c, $e] = $this->claim(); $this->app->instance('env', 'local');
        $this->signIn($owner)->post('/my-claims', $this->data($e))->assertStatus(419);
        $this->post('/claims/'.$c->id.'/actions', $this->action($c, 'comment'))->assertStatus(419);
        $this->assertDatabaseCount('student_claims', 1); $this->assertDatabaseCount('claim_events', 1);
    }

    public function test_claim_shortcut_on_bulletins_requires_an_active_student_record_and_permission(): void
    {
        [$school, $owner, $sheet] = $this->sheet(); $this->publishSheet($school, $sheet);
        $this->signIn($owner)->get('/my-grades/'.$sheet->id)->assertInertia(fn (Assert $p) => $p->where('canClaim', true));
        $sheet->enrollment->forceFill(['cancelled_at' => now()])->save();
        $this->get('/my-grades/'.$sheet->id)->assertInertia(fn (Assert $p) => $p->where('canClaim', false));
        $sheet->enrollment->forceFill(['cancelled_at' => null])->save(); $sheet->enrollment->student->forceFill(['is_archived' => true])->save();
        $this->get('/my-grades/'.$sheet->id)->assertInertia(fn (Assert $p) => $p->where('canClaim', false));
        $sheet->enrollment->student->forceFill(['is_archived' => false])->save(); UserGroup::findOrFail(1)->permissions()->detach('claims.self');
        $this->signIn($owner)->get('/my-grades/'.$sheet->id)->assertInertia(fn (Assert $p) => $p->where('canClaim', false));
    }

}
