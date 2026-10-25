<?php
namespace Tests\Feature;
use App\Actions\Collaboration\ManageMessages;
use App\Models\{InternalMessage, MessageRecipient, User, UserGroup};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Mail};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Support\CollaborationFixtures;
use Tests\TestCase;

class MessagingTest extends TestCase
{
    use RefreshDatabase, CollaborationFixtures;
    protected function setUp(): void { parent::setUp(); $this->withoutVite(); Mail::fake(); }

    public function test_migration_grants_roles_but_imports_no_private_messages_or_accounts(): void
    {
        $this->assertDatabaseCount('permissions', 44); $this->assertDatabaseCount('group_permission', 81);
        foreach (['internal_messages', 'message_recipients', 'meetings', 'users'] as $table) { $this->assertDatabaseCount($table, 0); }
        $this->assertSame(range(1, 9), DB::table('group_permission')->where('permission_code', 'messages.use')->orderBy('user_group_id')->pluck('user_group_id')->all());
        $this->assertSame([2, 8, 9], DB::table('group_permission')->where('permission_code', 'messages.broadcast')->orderBy('user_group_id')->pluck('user_group_id')->all());
    }
    public function test_guests_unassigned_types_and_incompatible_groups_have_no_mailbox(): void
    {
        $this->get('/messages')->assertRedirect('/login'); $this->post('/messages', [])->assertRedirect('/login');
        $user = User::factory()->create(); $user->types()->attach(1); $user->groups()->attach(9);
        $this->signIn($user)->get('/messages')->assertForbidden(); $this->get('/messages/contacts')->assertForbidden(); $this->post('/messages', [])->assertForbidden();
    }
    public function test_sending_delivers_private_copies_without_external_mail(): void
    {
        [$sender, $first, $second, $m] = $this->messageFixture();
        $this->assertDatabaseCount('internal_messages', 1); $this->assertDatabaseCount('message_recipients', 2); Mail::assertNothingSent();
        $this->signIn($sender)->get('/messages?folder=sent')->assertInertia(fn (Assert $p) => $p->has('messages.data', 1)->where('counts.inbox', 0));
        foreach ([$first, $second] as $person) { $this->signIn($person)->get('/messages')->assertInertia(fn (Assert $p) => $p->has('messages.data', 1)->where('counts.unread', 1)->where('unreadMessageCount', 1)); }
        $this->signIn($this->person(8))->get('/messages/'.$m->id)->assertNotFound();
        $this->get('/messages')->assertInertia(fn (Assert $p) => $p->where('messages.total', 0)->where('counts.unread', 0));
    }
    public function test_blank_drafts_are_private_and_can_be_resumed(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $data = $this->mailData([$recipient], ['action' => 'draft', 'subject' => '', 'body' => '']);
        $this->signIn($sender)->post('/messages', $data)->assertSessionHasNoErrors(); $m = InternalMessage::firstOrFail();
        $this->assertSame('draft', $m->status); $this->assertDatabaseCount('message_recipients', 0);
        $this->get('/messages/'.$m->id.'/edit')->assertInertia(fn (Assert $p) => $p->where('draft.id', $m->id)->has('selected', 1));
        $this->signIn($recipient)->get('/messages/'.$m->id)->assertNotFound(); $this->get('/messages/'.$m->id.'/edit')->assertNotFound();
        $this->get('/messages')->assertInertia(fn (Assert $p) => $p->where('messages.total', 0));
    }
    public function test_draft_update_send_and_retries_protect_against_duplicates_and_lost_edits(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $data = $this->mailData([$recipient], ['action' => 'draft']);
        $m = app(ManageMessages::class)->save($sender, $data); $this->signIn($sender);
        $edit = [...$data, 'revision' => 1, 'body' => 'Brouillon modifié.']; $this->put('/messages/'.$m->id, $edit)->assertSessionHasNoErrors();
        $this->put('/messages/'.$m->id, [...$edit, 'body' => 'Ancien formulaire'])->assertSessionHasErrors('revision');
        $send = [...$edit, 'revision' => 2, 'action' => 'send'];
        $this->put('/messages/'.$m->id, $send)->assertSessionHasNoErrors(); $this->put('/messages/'.$m->id, $send)->assertSessionHasNoErrors();
        $this->assertSame('sent', $m->fresh()->status); $this->assertDatabaseCount('message_recipients', 1);
        $this->put('/messages/'.$m->id, [...$send, 'body' => 'Changer un message envoyé'])->assertSessionHasErrors('operation_key');
    }
    public function test_new_message_replay_is_idempotent_and_key_reuse_is_rejected(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $data = $this->mailData([$recipient]); $this->signIn($sender);
        $this->post('/messages', $data)->assertSessionHasNoErrors(); $this->post('/messages', $data)->assertSessionHasNoErrors();
        $this->post('/messages', [...$data, 'subject' => 'Autre contenu'])->assertSessionHasErrors('operation_key');
        $this->assertDatabaseCount('internal_messages', 1); $this->assertDatabaseCount('message_recipients', 1); $this->assertDatabaseCount('collaboration_audits', 1);
        $this->signIn($recipient)->post('/messages', [...$data, 'recipient_ids' => [$sender->id]])->assertSessionHasErrors('operation_key');
    }
    public function test_sender_and_delivery_fields_cannot_be_forged(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $this->signIn($sender);
        foreach (['sender_id' => $recipient->id, 'status' => 'sent', 'kind' => 'meeting_invitation', 'group_ids' => [9], 'meeting_id' => 1] as $key => $value) {
            $this->post('/messages', [...$this->mailData([$recipient]), $key => $value])->assertSessionHasErrors($key);
        }
        $this->assertDatabaseCount('internal_messages', 0);
    }
    public function test_invalid_recipient_sets_send_nothing_to_any_recipient(): void
    {
        $sender = $this->person(1); $valid = $this->person(2); $inactive = $this->person(3, ['is_active' => false]); $unassigned = User::factory()->create(); $this->signIn($sender);
        foreach ([[$sender->id], [$valid->id, $inactive->id], [$valid->id, $unassigned->id], []] as $ids) {
            $this->post('/messages', $this->mailData($ids))->assertSessionHasErrors();
        }
        $this->post('/messages', $this->mailData([$valid->id, $valid->id]))->assertSessionHasErrors('recipient_ids.1');
        $this->assertDatabaseCount('internal_messages', 0); $this->assertDatabaseCount('message_recipients', 0);
    }
    public function test_content_limits_and_categories_are_enforced(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $this->signIn($sender);
        foreach (['subject' => str_repeat('x', 256), 'body' => str_repeat('x', 20001), 'priority' => 'system', 'category' => 'private', 'action' => 'delete'] as $field => $value) {
            $this->post('/messages', $this->mailData([$recipient], [$field => $value]))->assertSessionHasErrors($field);
        }
        $this->post('/messages', $this->mailData([$recipient], ['subject' => '  ', 'body' => '  ']))->assertSessionHasErrors();
        $this->assertDatabaseCount('internal_messages', 0);
    }
    public function test_contacts_include_only_active_compatible_message_accounts_and_group_selection_needs_its_permission(): void
    {
        $sender = $this->person(1); $good = $this->person(9); $bad = $this->person(9, ['is_active' => false]);
        $incompatible = $this->person(1); $incompatible->groups()->attach(9);
        $this->signIn($sender)->getJson('/messages/contacts?group_id=9')->assertForbidden();
        $this->signIn($this->person(2))->getJson('/messages/contacts?group_id=9')->assertOk()->assertJsonCount(1, 'users')->assertJsonPath('users.0.id', $good->id)->assertDontSee($bad->email);
        $this->getJson('/messages/contacts?search='.urlencode($good->email))->assertJsonPath('users.0.id', $good->id)->assertJsonMissingPath('users.0.password')->assertJsonMissingPath('users.0.auth_version');
    }
    public function test_group_selection_is_a_visible_snapshot_not_a_later_dynamic_broadcast(): void
    {
        $sender = $this->person(2); $first = $this->person(9); $second = $this->person(9); $this->signIn($sender);
        $ids = array_column($this->getJson('/messages/contacts?group_id=9')->assertOk()->json('users'), 'id');
        $later = $this->person(9); $this->post('/messages', $this->mailData($ids))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('message_recipients', 2); $this->assertDatabaseMissing('message_recipients', ['user_id' => $later->id]);
    }
    public function test_regular_accounts_cannot_send_to_more_than_twenty_contacts(): void
    {
        $sender = $this->person(1); $ids = []; for ($i = 0; $i < 21; $i++) { $ids[] = $this->person(5)->id; }
        $this->signIn($sender)->post('/messages', $this->mailData($ids))->assertSessionHasErrors('recipient_ids');
        $this->assertDatabaseCount('internal_messages', 0);
        $this->signIn($this->person(2))->post('/messages', $this->mailData($ids))->assertSessionHasNoErrors(); $this->assertDatabaseCount('message_recipients', 21);
    }
    public function test_read_state_is_explicit_and_independent_for_each_recipient(): void
    {
        [$sender, $first, $second, $m] = $this->messageFixture();
        $this->signIn($first)->get('/messages/'.$m->id)->assertOk(); $this->assertNull($m->recipients()->where('user_id', $first->id)->value('read_at'));
        $this->patch('/messages/'.$m->id.'/state', ['action' => 'read', 'revision' => 1])->assertSessionHasNoErrors();
        $this->get('/messages')->assertInertia(fn (Assert $p) => $p->where('counts.unread', 0)->where('unreadMessageCount', 0));
        $this->signIn($second)->get('/messages')->assertInertia(fn (Assert $p) => $p->where('counts.unread', 1));
        $this->signIn($first)->patch('/messages/'.$m->id.'/state', ['action' => 'unread', 'revision' => 2])->assertSessionHasNoErrors();
        $this->signIn($sender)->patch('/messages/'.$m->id.'/state', ['action' => 'read', 'revision' => 1])->assertSessionHasErrors('action');
    }
    public function test_archive_trash_and_restore_affect_only_the_actors_copy(): void
    {
        [$sender, $first, $second, $m] = $this->messageFixture();
        $this->signIn($first)->patch('/messages/'.$m->id.'/state', ['action' => 'archive', 'revision' => 1])->assertSessionHasNoErrors();
        $this->get('/messages?folder=archive')->assertInertia(fn (Assert $p) => $p->where('messages.total', 1)->where('counts.inbox', 0));
        $this->patch('/messages/'.$m->id.'/state', ['action' => 'trash', 'revision' => 2])->assertSessionHasNoErrors();
        $this->signIn($second)->get('/messages')->assertInertia(fn (Assert $p) => $p->where('messages.total', 1));
        $this->signIn($sender)->get('/messages?folder=sent')->assertInertia(fn (Assert $p) => $p->where('messages.total', 1));
        $this->patch('/messages/'.$m->id.'/state', ['action' => 'trash', 'revision' => 1])->assertSessionHasNoErrors();
        $this->signIn($first)->patch('/messages/'.$m->id.'/state', ['action' => 'restore', 'revision' => 3])->assertSessionHasNoErrors();
        $this->get('/messages')->assertInertia(fn (Assert $p) => $p->where('messages.total', 1)); $this->assertDatabaseCount('internal_messages', 1); $this->assertDatabaseCount('message_recipients', 2);
    }
    public function test_stale_mailbox_actions_and_forged_personal_targets_are_rejected(): void
    {
        [, $first, $second, $m] = $this->messageFixture(); $this->signIn($first);
        $this->patch('/messages/'.$m->id.'/state', ['action' => 'read', 'revision' => 1, 'user_id' => $second->id])->assertSessionHasErrors('user_id');
        $this->patch('/messages/'.$m->id.'/state', ['action' => 'read', 'revision' => 1])->assertSessionHasNoErrors();
        $this->patch('/messages/'.$m->id.'/state', ['action' => 'trash', 'revision' => 1])->assertSessionHasErrors('revision');
        $this->assertDatabaseHas('message_recipients', ['user_id' => $first->id, 'folder' => 'inbox']);
        $this->signIn($this->person(8))->patch('/messages/'.$m->id.'/state', ['action' => 'trash', 'revision' => 1])->assertNotFound();
    }
    public function test_discarding_a_draft_never_exposes_it_and_restore_allows_editing(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $m = app(ManageMessages::class)->save($sender, $this->mailData([$recipient], ['action' => 'draft']));
        $this->signIn($sender)->patch('/messages/'.$m->id.'/state', ['action' => 'trash', 'revision' => 1])->assertSessionHasNoErrors();
        $this->get('/messages?folder=drafts')->assertInertia(fn (Assert $p) => $p->where('messages.total', 0));
        $this->get('/messages/'.$m->id.'/edit')->assertNotFound(); $this->get('/messages/'.$m->id)->assertOk();
        $this->patch('/messages/'.$m->id.'/state', ['action' => 'restore', 'revision' => 2])->assertSessionHasNoErrors(); $this->get('/messages/'.$m->id.'/edit')->assertOk();
        $this->assertDatabaseCount('message_recipients', 0);
    }
    public function test_replies_require_access_to_the_original_and_target_its_sender_only(): void
    {
        [$sender, $first, $second, $m] = $this->messageFixture();
        $this->signIn($first)->get('/messages/create?source_id='.$m->id.'&source_mode=reply')->assertInertia(fn (Assert $p) => $p->where('selected.0.id', $sender->id));
        $data = $this->mailData([$sender], ['source_id' => $m->id, 'source_mode' => 'reply']);
        $this->post('/messages', [...$data, 'recipient_ids' => [$second->id]])->assertSessionHasErrors('recipient_ids');
        $this->post('/messages', $data)->assertSessionHasNoErrors(); $reply = InternalMessage::latest('id')->firstOrFail();
        $this->signIn($second)->get('/messages/'.$reply->id)->assertNotFound();
        $this->signIn($sender)->get('/messages/'.$reply->id)->assertInertia(fn (Assert $p) => $p->where('message.history.0.id', $m->id));
        $this->signIn($this->person(8))->get('/messages/create?source_id='.$m->id.'&source_mode=reply')->assertNotFound();
    }
    public function test_forwarding_copies_only_the_selected_message_and_never_grants_source_access(): void
    {
        [$sender, $first, $second, $m] = $this->messageFixture(); $third = $this->person(4);
        $this->signIn($first)->get('/messages/create?source_id='.$m->id.'&source_mode=forward')->assertInertia(fn (Assert $p) => $p->where('draft.body', fn ($body) => str_contains($body, $m->body))->has('selected', 0));
        $this->post('/messages', $this->mailData([$third], ['source_id' => $m->id, 'source_mode' => 'forward', 'body' => 'Extrait volontairement partagé.']))->assertSessionHasNoErrors();
        $forward = InternalMessage::latest('id')->firstOrFail();
        $this->signIn($third)->get('/messages/'.$forward->id)->assertInertia(fn (Assert $p) => $p->has('message.history', 0));
        $this->get('/messages/'.$m->id)->assertNotFound(); $this->signIn($sender)->get('/messages/'.$forward->id)->assertNotFound();
    }
    public function test_trashed_messages_cannot_be_used_as_reply_or_forward_sources(): void
    {
        [$sender, $first, , $m] = $this->messageFixture(); $this->signIn($first)->patch('/messages/'.$m->id.'/state', ['action' => 'trash', 'revision' => 1]);
        $this->get('/messages/create?source_id='.$m->id.'&source_mode=forward')->assertNotFound();
        $this->post('/messages', $this->mailData([$sender], ['source_id' => $m->id, 'source_mode' => 'reply']))->assertNotFound();
    }
    public function test_permission_and_recipient_revocation_are_rechecked_under_the_write_lock(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $data = $this->mailData([$recipient]);
        UserGroup::findOrFail(1)->permissions()->detach('messages.use');
        try { app(ManageMessages::class)->save($sender, $data); $this->fail('Revoked sender accepted.'); } catch (AuthorizationException) {}
        UserGroup::findOrFail(1)->permissions()->attach('messages.use'); $recipient->forceFill(['is_active' => false])->save();
        try { app(ManageMessages::class)->save($sender, $data); $this->fail('Inactive recipient accepted.'); } catch (ValidationException $e) { $this->assertArrayHasKey('recipient_ids', $e->errors()); }
        $this->assertDatabaseCount('internal_messages', 0);
    }
    public function test_temporary_password_recipients_receive_but_must_personalize_before_reading(): void
    {
        $sender = $this->person(1); $recipient = $this->person(9, ['must_change_password' => true]);
        $m = app(ManageMessages::class)->save($sender, $this->mailData([$recipient]));
        $this->signIn($recipient)->get('/messages/'.$m->id)->assertRedirect('/password/change');
        $this->assertDatabaseCount('message_recipients', 1);
    }
    public function test_delivery_failure_rolls_back_message_copies_and_audit(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2);
        MessageRecipient::creating(fn () => throw new RuntimeException('Simulated delivery failure'));
        try { app(ManageMessages::class)->save($sender, $this->mailData([$recipient])); $this->fail('Expected failure.'); }
        catch (RuntimeException $e) { $this->assertSame('Simulated delivery failure', $e->getMessage()); }
        finally { MessageRecipient::flushEventListeners(); }
        foreach (['internal_messages', 'message_recipients', 'collaboration_audits'] as $table) { $this->assertDatabaseCount($table, 0); }
    }
    public function test_filters_pagination_and_counts_remain_in_the_personal_mailbox(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $other = $this->person(3);
        for ($i = 0; $i < 21; $i++) { app(ManageMessages::class)->save($sender, $this->mailData([$recipient], ['subject' => 'Recherche '.$i, 'priority' => 'urgent'])); }
        app(ManageMessages::class)->save($sender, $this->mailData([$other], ['subject' => 'Secret Recherche', 'priority' => 'urgent']));
        $this->signIn($recipient)->get('/messages?search=Recherche&priority=urgent')->assertInertia(fn (Assert $p) => $p->has('messages.data', 20)->where('messages.total', 21)->where('messages.last_page', 2)->where('counts.unread', 21));
        $this->get('/messages?search=Secret')->assertInertia(fn (Assert $p) => $p->where('messages.total', 0));
        $this->get('/messages?folder=unread&page=2')->assertInertia(fn (Assert $p) => $p->has('messages.data', 1));
    }
    public function test_message_payloads_do_not_expose_storage_secrets_or_other_recipients_folders(): void
    {
        [$sender, $first, , $m] = $this->messageFixture();
        $this->signIn($first)->get('/messages/'.$m->id)->assertInertia(fn (Assert $p) => $p->missing('message.operation_key')->missing('message.request_hash')->missing('message.sender_id')->missing('message.recipients.0.folder')->missing('message.recipients.0.email')->missing('message.recipients.0.user_id'));
    }
    public function test_csrf_is_required_for_sending_draft_edits_and_copy_actions(): void
    {
        [$sender, $first, , $m, $data] = $this->messageFixture(); $this->app->instance('env', 'local');
        $this->signIn($sender)->post('/messages', $data)->assertStatus(419); $this->put('/messages/'.$m->id, $data)->assertStatus(419);
        $this->signIn($first)->patch('/messages/'.$m->id.'/state', ['action' => 'read', 'revision' => 1])->assertStatus(419);
    }
    public function test_send_rate_limit_applies_even_to_idempotent_retries(): void
    {
        $sender = $this->person(1); $recipient = $this->person(2); $data = $this->mailData([$recipient]); $this->signIn($sender);
        for ($i = 0; $i < 20; $i++) { $this->post('/messages', $data)->assertSessionHasNoErrors(); }
        $this->post('/messages', $data)->assertStatus(429); $this->assertDatabaseCount('internal_messages', 1);
    }
}
