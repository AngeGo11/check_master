<?php
namespace Tests\Feature;
use App\Actions\Collaboration\{ManageMeetings, ManageMeetingDocuments};
use App\Models\{Meeting, MeetingDocument, InternalMessage, MessageRecipient, User, UserGroup};
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Mail, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Support\CollaborationFixtures;
use Tests\TestCase;

class MeetingsTest extends TestCase
{
    use RefreshDatabase, CollaborationFixtures;
    protected function setUp(): void
    {
        parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 22)->setTime(9, 0));
        Storage::fake('meetings'); Storage::fake('reports'); Mail::fake();
    }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function responseData(Meeting $m, User $u, string $response = 'accepted'): array
    {
        return ['revision' => $m->fresh()->revision, 'response_revision' => $m->participants()->where('user_id', $u->id)->value('response_revision'), 'response' => $response];
    }
    private function documentData(array $overrides = []): array
    {
        return [...['revision' => 1, 'operation_key' => (string) Str::uuid(), 'title' => 'Document de séance fictif'], ...$overrides];
    }
    public function test_only_compatible_commission_members_can_organize_and_administrators_have_read_only_oversight(): void
    {
        $this->get('/meetings')->assertRedirect('/login');
        foreach ([1, 2, 3, 4, 5, 6, 7] as $group) { $this->signIn($this->person($group))->get('/meetings')->assertForbidden(); }
        $admin = $this->person(8); $this->signIn($admin)->get('/meetings')->assertOk(); $this->get('/meetings/create')->assertForbidden();
        $this->post('/meetings', $this->meetingData([$admin]))->assertForbidden();
        $incompatible = $this->person(1); $incompatible->groups()->attach(9); $this->signIn($incompatible)->get('/meetings')->assertForbidden();
        $this->signIn($this->person())->get('/meetings/create')->assertOk();
        $this->assertSame([8, 9], DB::table('group_permission')->where('permission_code', 'meetings.view')->orderBy('user_group_id')->pluck('user_group_id')->all());
        $this->assertSame([9], DB::table('group_permission')->where('permission_code', 'meetings.manage')->pluck('user_group_id')->all());
    }
    public function test_creation_adds_the_organizer_and_delivers_one_internal_invitation_per_guest(): void
    {
        $owner = $this->person(); $guest = $this->person(); $data = $this->meetingData([$guest]);
        $this->signIn($owner)->post('/meetings', $data)->assertSessionHasNoErrors(); $m = Meeting::firstOrFail();
        $this->assertDatabaseCount('meeting_participants', 2); $this->assertDatabaseCount('message_recipients', 1);
        $this->assertSame('2026-10-23 10:00:00', $m->starts_at->format('Y-m-d H:i:s')); $this->assertSame('2026-10-23 11:00:00', $m->ends_at->format('Y-m-d H:i:s'));
        $notice = InternalMessage::firstOrFail(); $this->assertSame('meeting_invitation', $notice->kind);
        $this->signIn($guest)->get('/messages/'.$notice->id)->assertInertia(fn (Assert $p) => $p->where('message.meeting_link', '/meetings/'.$m->id)->where('message.can_reply', false));
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('upcomingMeetings', 1)->where('unreadMessageCount', 1)); Mail::assertNothingSent();
    }
    public function test_creation_retries_are_idempotent_and_reused_keys_cannot_create_different_meetings(): void
    {
        [$owner, $guest, $m, $data] = $this->meetingFixture(); $this->signIn($owner);
        $this->post('/meetings', $data)->assertSessionHasNoErrors();
        $this->post('/meetings', [...$data, 'title' => 'Autre titre'])->assertSessionHasErrors('operation_key');
        $this->signIn($guest)->post('/meetings', $data)->assertSessionHasErrors('operation_key');
        $this->assertDatabaseCount('meetings', 1); $this->assertDatabaseCount('internal_messages', 1);
    }
    public function test_lists_and_direct_links_are_scoped_to_invited_members_with_admin_oversight(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture(); $other = $this->person();
        $this->signIn($other)->get('/meetings')->assertInertia(fn (Assert $p) => $p->where('meetings.total', 0)->has('calendar', 0));
        $this->get('/meetings/'.$m->id)->assertNotFound(); $this->get('/meetings/'.$m->id.'/calendar.ics')->assertNotFound();
        foreach ([$owner, $guest, $this->person(8)] as $user) { $this->signIn($user)->get('/meetings/'.$m->id)->assertOk(); }
        $this->get('/meetings/'.$m->id)->assertInertia(fn (Assert $p) => $p->where('canManage', false)->where('canRespond', false));
    }
    public function test_invalid_dates_participants_and_forged_state_fields_create_nothing(): void
    {
        $owner = $this->person(); $student = $this->person(1); $inactive = $this->person(9, ['is_active' => false]); $this->signIn($owner);
        foreach (['starts_at' => '2026-10-21T10:00', 'duration_minutes' => 14, 'priority' => 'arbitrary', 'title' => str_repeat('a', 201), 'participant_ids' => [$student->id], 'created_by' => $student->id, 'status' => 'completed', 'responses' => ['accepted']] as $key => $value) {
            $this->post('/meetings', $this->meetingData([$owner], [$key => $value]))->assertSessionHasErrors($key);
        }
        $this->post('/meetings', $this->meetingData([$inactive]))->assertSessionHasErrors('participant_ids');
        $this->assertDatabaseCount('meetings', 0); $this->assertDatabaseCount('internal_messages', 0);
    }
    public function test_overlapping_invitations_are_rejected_but_adjacent_time_slots_are_allowed(): void
    {
        [$owner, $guest] = $this->meetingFixture(); $this->signIn($owner);
        $this->post('/meetings', $this->meetingData([$guest], ['starts_at' => '2026-10-23T10:30']))->assertSessionHasErrors('starts_at');
        $this->post('/meetings', $this->meetingData([$guest], ['starts_at' => '2026-10-23T11:00']))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('meetings', 2);
    }
    public function test_declined_invitations_free_the_slot_but_later_acceptance_rechecks_conflicts(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture();
        $this->signIn($guest)->patch('/meetings/'.$m->id.'/response', $this->responseData($m, $guest, 'declined'))->assertSessionHasNoErrors();
        $other = $this->person(); app(ManageMeetings::class)->save($other, $this->meetingData([$guest]));
        $this->signIn($guest)->patch('/meetings/'.$m->id.'/response', $this->responseData($m, $guest))->assertSessionHasErrors('starts_at');
        $this->assertDatabaseHas('meeting_participants', ['meeting_id' => $m->id, 'user_id' => $guest->id, 'response' => 'declined']);
    }
    public function test_each_participant_controls_only_their_response_without_invalidating_other_forms(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture(); $ownerData = $this->responseData($m, $owner); $guestData = $this->responseData($m, $guest);
        $this->signIn($guest)->patch('/meetings/'.$m->id.'/response', [...$guestData, 'user_id' => $owner->id])->assertSessionHasErrors('user_id');
        $this->patch('/meetings/'.$m->id.'/response', $guestData)->assertSessionHasNoErrors();
        $this->patch('/meetings/'.$m->id.'/response', [...$guestData, 'response' => 'declined'])->assertSessionHasErrors('response_revision');
        $this->signIn($owner)->patch('/meetings/'.$m->id.'/response', $ownerData)->assertSessionHasNoErrors();
        $this->assertSame(2, $m->participants()->where('response', 'accepted')->count()); $this->assertSame(1, $m->fresh()->revision);
        $this->signIn($this->person(8))->patch('/meetings/'.$m->id.'/response', $guestData)->assertNotFound();
    }
    public function test_reprogramming_resets_responses_records_history_and_revokes_removed_invitees(): void
    {
        [$owner, $guest, $m, $data] = $this->meetingFixture(); $new = $this->person();
        app(ManageMeetings::class)->respond($guest, $m, $this->responseData($m, $guest));
        $this->signIn($owner)->put('/meetings/'.$m->id, [...$data, 'revision' => 1, 'reason' => 'Changement de créneau', 'starts_at' => '2026-10-24T10:00', 'participant_ids' => [$new->id]])->assertSessionHasNoErrors();
        $this->assertSame(2, $m->fresh()->revision); $this->assertSame(2, $m->participants()->whereNull('removed_at')->where('response', 'pending')->count());
        $this->assertNotNull($m->participants()->where('user_id', $guest->id)->value('removed_at'));
        $audit = DB::table('collaboration_audits')->where('action', 'meeting.previous_schedule')->first();
        $this->assertStringContainsString('accepted', $audit->metadata);
        $this->signIn($guest)->get('/meetings/'.$m->id)->assertNotFound();
        $notice = InternalMessage::where('kind', 'meeting_removed')->firstOrFail();
        $this->get('/messages/'.$notice->id)->assertInertia(fn (Assert $p) => $p->where('message.meeting_link', null));
        $this->signIn($new)->get('/meetings/'.$m->id)->assertOk();
    }
    public function test_only_the_organizer_can_edit_cancel_or_add_documents(): void
    {
        [$owner, $guest, $m, $data] = $this->meetingFixture();
        foreach ([$guest, $this->person(), $this->person(8)] as $other) {
            $this->signIn($other)->get('/meetings/'.$m->id.'/edit')->assertForbidden();
            $this->put('/meetings/'.$m->id, [...$data, 'revision' => 1, 'reason' => 'Modification interdite'])->assertForbidden();
            $this->patch('/meetings/'.$m->id.'/cancel', ['revision' => 1, 'reason' => 'Annulation interdite'])->assertForbidden();
            $this->post('/meetings/'.$m->id.'/documents', [...$this->documentData(), 'document' => $this->pdf()])->assertForbidden();
        }
        $this->assertSame(1, $m->fresh()->revision); $this->assertDatabaseCount('meeting_documents', 0);
    }
    public function test_stale_schedule_response_and_cancellation_forms_cannot_overwrite_changes(): void
    {
        [$owner, $guest, $m, $data] = $this->meetingFixture(); $oldResponse = $this->responseData($m, $guest);
        $edit = [...$data, 'revision' => 1, 'reason' => 'Horaire mis à jour', 'starts_at' => '2026-10-24T10:00'];
        $this->signIn($owner)->put('/meetings/'.$m->id, $edit)->assertSessionHasNoErrors();
        $this->put('/meetings/'.$m->id, $edit)->assertSessionHasErrors('revision');
        $this->patch('/meetings/'.$m->id.'/cancel', ['revision' => 1, 'reason' => 'Ancien formulaire'])->assertSessionHasErrors('revision');
        $this->signIn($guest)->patch('/meetings/'.$m->id.'/response', $oldResponse)->assertSessionHasErrors('revision');
        $this->assertSame('scheduled', $m->fresh()->state());
    }
    public function test_cancellation_notifies_guests_preserves_history_and_releases_the_slot(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture();
        $this->signIn($owner)->patch('/meetings/'.$m->id.'/cancel', ['revision' => 1, 'reason' => 'Séance reportée ultérieurement'])->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $m->fresh()->state()); $this->assertDatabaseHas('internal_messages', ['meeting_id' => $m->id, 'kind' => 'meeting_cancelled']);
        $this->signIn($guest)->get('/meetings/'.$m->id.'/calendar.ics')->assertOk()->assertSee('STATUS:CANCELLED')->assertSee('SEQUENCE:2');
        $this->patch('/meetings/'.$m->id.'/response', $this->responseData($m, $guest))->assertSessionHasErrors('meeting');
        $this->signIn($owner)->post('/meetings', $this->meetingData([$guest]))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('meetings', 2); Mail::assertNothingSent();
    }
    public function test_started_and_completed_meetings_remain_readable_but_cannot_be_rescheduled_or_answered(): void
    {
        [$owner, $guest, $m, $data] = $this->meetingFixture();
        foreach ([10 => 'ongoing', 11 => 'completed'] as $hour => $state) {
            $this->travelTo(now()->setDate(2026, 10, 23)->setTime($hour, 0)); $this->assertSame($state, $m->fresh()->state());
            $this->signIn($owner)->get('/meetings/'.$m->id)->assertInertia(fn (Assert $p) => $p->where('meeting.status', $state)->where('canRespond', false));
            $this->put('/meetings/'.$m->id, [...$data, 'revision' => 1, 'reason' => 'Modification trop tardive'])->assertSessionHasErrors('meeting');
            $this->signIn($guest)->patch('/meetings/'.$m->id.'/response', $this->responseData($m, $guest))->assertSessionHasErrors('meeting');
        }
        $this->signIn($owner)->patch('/meetings/'.$m->id.'/cancel', ['revision' => 1, 'reason' => 'Séance déjà terminée'])->assertSessionHasErrors('meeting');
    }
    public function test_pdf_download_is_private_scoped_and_has_safe_headers(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture();
        $d = app(ManageMeetingDocuments::class)->upload($owner, $m, $this->documentData(), $this->pdf()); $url = '/meetings/'.$m->id.'/documents/'.$d->id.'/download';
        foreach ([$owner, $guest, $this->person(8)] as $u) { $this->signIn($u)->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')->assertDownload('reunion-'.$m->id.'-document-'.$d->id.'.pdf'); }
        $this->signIn($this->person())->get($url)->assertNotFound(); $this->signIn($this->person(1))->get($url)->assertForbidden();
        $other = app(ManageMeetings::class)->save($owner, $this->meetingData([$owner], ['starts_at' => '2026-10-24T10:00']));
        $this->signIn($owner)->get('/meetings/'.$other->id.'/documents/'.$d->id.'/download')->assertNotFound();
        $this->get('/storage/meetings/'.$d->storage_path)->assertNotFound();
    }
    public function test_pdf_validation_rejects_spoofed_content_and_oversized_files(): void
    {
        [$owner, , $m] = $this->meetingFixture(); $this->signIn($owner);
        foreach ([UploadedFile::fake()->createWithContent('fake.pdf', '<html>not a PDF</html>'), UploadedFile::fake()->create('large.pdf', 20481, 'application/pdf')] as $file) {
            $this->post('/meetings/'.$m->id.'/documents', [...$this->documentData(), 'document' => $file])->assertSessionHasErrors('document');
        }
        $this->assertDatabaseCount('meeting_documents', 0); $this->assertSame([], Storage::disk('meetings')->allFiles());
    }
    public function test_upload_retries_do_not_duplicate_files_and_key_reuse_is_rejected(): void
    {
        [$owner, , $m] = $this->meetingFixture(); $data = $this->documentData(); $this->signIn($owner);
        for ($i = 0; $i < 2; $i++) { $this->post('/meetings/'.$m->id.'/documents', [...$data, 'document' => $this->pdf()])->assertSessionHasNoErrors(); }
        $this->post('/meetings/'.$m->id.'/documents', [...$data, 'document' => $this->pdf('Autre contenu')])->assertSessionHasErrors('operation_key');
        $this->assertDatabaseCount('meeting_documents', 1); $this->assertCount(1, Storage::disk('meetings')->allFiles());
    }
    public function test_withdrawn_documents_retain_storage_and_audit_but_cannot_be_downloaded(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture(); $d = app(ManageMeetingDocuments::class)->upload($owner, $m, $this->documentData(), $this->pdf());
        $this->signIn($owner)->delete('/meetings/'.$m->id.'/documents/'.$d->id, ['revision' => 1, 'reason' => 'Document remplacé après correction'])->assertSessionHasNoErrors();
        $this->signIn($guest)->get('/meetings/'.$m->id.'/documents/'.$d->id.'/download')->assertNotFound();
        Storage::disk('meetings')->assertExists($d->storage_path); $this->assertDatabaseCount('meeting_documents', 1);
        $this->assertDatabaseHas('collaboration_audits', ['action' => 'meeting.document_withdrawn', 'meeting_id' => $m->id]);
    }
    public function test_removed_invitees_lose_document_access_and_readding_them_requires_a_new_response(): void
    {
        [$owner, $guest, $m, $data] = $this->meetingFixture(); $d = app(ManageMeetingDocuments::class)->upload($owner, $m, $this->documentData(), $this->pdf());
        app(ManageMeetings::class)->respond($guest, $m, $this->responseData($m, $guest));
        app(ManageMeetings::class)->save($owner, [...$data, 'participant_ids' => [$owner->id], 'revision' => 1, 'reason' => 'Retrait du participant'], $m);
        $this->signIn($guest)->get('/meetings/'.$m->id.'/documents/'.$d->id.'/download')->assertNotFound();
        app(ManageMeetings::class)->save($owner, [...$data, 'revision' => 2, 'reason' => 'Nouvelle invitation'], $m);
        $this->get('/meetings/'.$m->id)->assertInertia(fn (Assert $p) => $p->where('meeting.my_response.response', 'pending'));
        $this->assertDatabaseCount('meeting_participants', 2);
    }
    public function test_tampered_or_missing_pdf_is_never_served(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture(); $d = app(ManageMeetingDocuments::class)->upload($owner, $m, $this->documentData(), $this->pdf());
        $disk = Storage::disk('meetings'); $disk->put($d->storage_path, str_repeat('x', $d->size));
        $this->signIn($guest)->get('/meetings/'.$m->id.'/documents/'.$d->id.'/download')->assertNotFound();
        $disk->delete($d->storage_path); $this->get('/meetings/'.$m->id.'/documents/'.$d->id.'/download')->assertNotFound();
    }
    public function test_failed_document_metadata_write_rolls_back_and_cleans_unreferenced_storage(): void
    {
        [$owner, , $m] = $this->meetingFixture(); $count = DB::table('collaboration_audits')->count();
        MeetingDocument::creating(fn () => throw new RuntimeException('Simulated metadata failure'));
        try { app(ManageMeetingDocuments::class)->upload($owner, $m, $this->documentData(), $this->pdf()); $this->fail('Expected failure.'); }
        catch (RuntimeException $e) { $this->assertSame('Simulated metadata failure', $e->getMessage()); }
        finally { MeetingDocument::flushEventListeners(); }
        $this->assertDatabaseCount('meeting_documents', 0); $this->assertDatabaseCount('collaboration_audits', $count); $this->assertSame([], Storage::disk('meetings')->allFiles());
    }
    public function test_failed_invitation_delivery_rolls_back_the_entire_meeting_transaction(): void
    {
        $owner = $this->person(); $guest = $this->person(); MessageRecipient::creating(fn () => throw new RuntimeException('Simulated invitation failure'));
        try { app(ManageMeetings::class)->save($owner, $this->meetingData([$guest])); $this->fail('Expected failure.'); }
        catch (RuntimeException $e) { $this->assertSame('Simulated invitation failure', $e->getMessage()); }
        finally { MessageRecipient::flushEventListeners(); }
        foreach (['meetings', 'meeting_participants', 'internal_messages', 'message_recipients', 'collaboration_audits'] as $table) { $this->assertDatabaseCount($table, 0); }
    }
    public function test_agenda_uses_already_transmitted_reports_and_never_casts_votes_or_accepts_a_report(): void
    {
        $owner = $this->person(); $guest = $this->person(); [$report, $round] = $this->transmittedReport();
        $before = $round->members()->get()->toArray();
        $m = app(ManageMeetings::class)->save($owner, $this->meetingData([$guest], ['round_ids' => [$round->id]]));
        app(ManageMeetings::class)->respond($guest, $m, $this->responseData($m, $guest));
        $this->signIn($guest)->get('/meetings/'.$m->id)->assertInertia(fn (Assert $p) => $p->has('meeting.agenda', 1)->where('meeting.agenda.0.href', '/reports/'.$report->id));
        $this->assertSame('under_review', $report->fresh()->status); $this->assertSame('open', $round->fresh()->status); $this->assertSame($before, $round->members()->get()->toArray());
    }
    public function test_agenda_rejects_nonexistent_closed_or_untransmitted_rounds_and_unassigned_participants(): void
    {
        $owner = $this->person(); $guest = $this->person(); [$report, $round] = $this->transmittedReport(); $newMember = $this->person(); $this->signIn($owner);
        $this->post('/meetings', $this->meetingData([$guest], ['round_ids' => [999999]]))->assertSessionHasErrors('round_ids');
        $this->post('/meetings', $this->meetingData([$newMember], ['round_ids' => [$round->id]]))->assertSessionHasErrors('round_ids');
        $round->forceFill(['status' => 'superseded'])->save();
        $this->post('/meetings', $this->meetingData([$guest], ['round_ids' => [$round->id]]))->assertSessionHasErrors('round_ids');
        $this->assertDatabaseCount('meetings', 0); $this->assertSame('under_review', $report->fresh()->status);
    }
    public function test_revoked_report_access_hides_agenda_links_without_leaking_titles(): void
    {
        $owner = $this->person(); $guest = $this->person(); [$report, $round] = $this->transmittedReport();
        $m = app(ManageMeetings::class)->save($owner, $this->meetingData([$guest], ['round_ids' => [$round->id]]));
        UserGroup::findOrFail(9)->permissions()->detach('reports.assigned');
        $this->signIn($guest)->get('/meetings/'.$m->id)->assertInertia(fn (Assert $p) => $p->has('meeting.agenda', 0));
        $this->get('/reports/'.$report->id)->assertForbidden();
    }
    public function test_revoked_organizer_permission_is_rechecked_inside_the_transaction(): void
    {
        [$owner, , $m, $data] = $this->meetingFixture(); UserGroup::findOrFail(9)->permissions()->detach('meetings.manage');
        try { app(ManageMeetings::class)->save($owner, [...$data, 'revision' => 1, 'reason' => 'Permission retirée'], $m); $this->fail('Revoked writer accepted.'); }
        catch (AuthorizationException) {}
        $this->assertSame(1, $m->fresh()->revision);
    }
    public function test_calendar_exports_are_scoped_escaped_and_folded_without_broken_utf8(): void
    {
        $owner = $this->person(); $guest = $this->person(); $title = str_repeat('é', 80).", réunion; test\\";
        $m = app(ManageMeetings::class)->save($owner, $this->meetingData([$guest], ['title' => $title, 'description' => "Texte\r\nBEGIN:VEVENT\nSuite"]));
        $other = $this->person(); app(ManageMeetings::class)->save($other, $this->meetingData([$other], ['title' => 'Séance invisible']));
        $response = $this->signIn($guest)->get('/meetings/calendar.ics?month=2026-10')->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=UTF-8')->assertDontSee('Séance invisible');
        $ics = $response->getContent(); $unfolded = str_replace("\r\n ", '', $ics);
        $this->assertSame(1, substr_count($ics, "\r\nBEGIN:VEVENT\r\n")); $this->assertStringContainsString('DTSTART:20261023T100000Z', $ics);
        $this->assertStringContainsString('DESCRIPTION:Texte\\nBEGIN:VEVENT\\nSuite', $unfolded); $this->assertStringContainsString('\\, réunion\\; test\\\\', $unfolded);
        foreach (explode("\r\n", $ics) as $line) { $this->assertLessThanOrEqual(75, strlen($line)); $this->assertTrue(mb_check_encoding($line, 'UTF-8')); }
        $this->get('/meetings/calendar.ics?month=2026-99')->assertSessionHasErrors('month');
    }
    public function test_month_search_status_and_dashboard_respect_scope_without_writing_status_on_reads(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture(); $other = $this->person(); app(ManageMeetings::class)->save($other, $this->meetingData([$other], ['title' => 'Invisible']));
        app(ManageMeetings::class)->save($owner, $this->meetingData([$guest], ['starts_at' => '2026-11-03T10:00']));
        $before = DB::table('collaboration_audits')->count();
        $this->signIn($guest)->get('/meetings?month=2026-10&status=scheduled')->assertInertia(fn (Assert $p) => $p->where('meetings.total', 1)->where('statistics.scheduled', 1)->has('calendar', 1));
        $this->get('/meetings?month=2026-11')->assertInertia(fn (Assert $p) => $p->where('meetings.total', 1));
        $this->get('/meetings?search=Invisible')->assertInertia(fn (Assert $p) => $p->where('meetings.total', 0));
        $this->get('/dashboard')->assertInertia(fn (Assert $p) => $p->has('upcomingMeetings', 2)); $this->assertDatabaseCount('collaboration_audits', $before);
    }
    public function test_meeting_payloads_exclude_private_storage_keys_and_other_response_revisions(): void
    {
        [$owner, $guest, $m] = $this->meetingFixture(); app(ManageMeetingDocuments::class)->upload($owner, $m, $this->documentData(), $this->pdf());
        $this->signIn($guest)->get('/meetings/'.$m->id)->assertInertia(fn (Assert $p) => $p->missing('meeting.operation_key')->missing('meeting.request_hash')->missing('meeting.documents.0.storage_path')->missing('meeting.documents.0.sha256')->missing('meeting.participants.0.user_id')->missing('meeting.participants.0.response_revision')->missing('meeting.activity.0.metadata'));
    }
    public function test_csrf_protects_planning_responses_cancellation_and_document_changes(): void
    {
        [$owner, $guest, $m, $data] = $this->meetingFixture(); $this->app->instance('env', 'local');
        $this->signIn($owner)->post('/meetings', $data)->assertStatus(419); $this->put('/meetings/'.$m->id, $data)->assertStatus(419);
        $this->patch('/meetings/'.$m->id.'/cancel', ['revision' => 1, 'reason' => 'Sans jeton CSRF'])->assertStatus(419);
        $this->post('/meetings/'.$m->id.'/documents', [...$this->documentData(), 'document' => $this->pdf()])->assertStatus(419);
        $this->signIn($guest)->patch('/meetings/'.$m->id.'/response', $this->responseData($m, $guest))->assertStatus(419);
    }
}
