<?php

namespace Tests\Feature;

use App\Actions\Access\ManageAccess;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function administrator(): User
    {
        $user = User::factory()->create();
        $user->types()->attach(3);
        $user->groups()->attach(8);

        return $user;
    }

    private function signIn(User $user): static
    {
        return $this->actingAs($user->fresh())->withSession(['auth_version' => $user->fresh()->auth_version]);
    }

    private function payload(User $user, array $overrides = []): array
    {
        return [...[
            'name' => $user->name, 'email' => $user->email, 'is_active' => $user->is_active,
            'type_ids' => $user->types()->pluck('user_types.id')->all(),
            'group_ids' => $user->groups()->pluck('user_groups.id')->all(),
        ], ...$overrides];
    }

    private function newAccount(array $overrides = []): array
    {
        return [...[
            'name' => 'Compte étudiant', 'email' => 'STUDENT@EXAMPLE.TEST', 'is_active' => true,
            'type_ids' => [1], 'group_ids' => [1], 'password' => 'temporary-password-2026',
            'password_confirmation' => 'temporary-password-2026',
        ], ...$overrides];
    }

    public function test_reference_catalog_preserves_the_legacy_types_and_groups_without_importing_users(): void
    {
        $this->assertDatabaseCount('user_types', 4);
        $this->assertDatabaseCount('user_groups', 9);
        $this->assertDatabaseCount('user_group_user_type', 10);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('user_groups', ['id' => 9, 'name' => 'Commission de validation']);
        $this->assertDatabaseCount('group_permission', 86);
    }

    public function test_guests_cannot_access_account_management(): void
    {
        foreach (['/settings', '/settings/users', '/settings/users/create', '/settings/groups', '/profile'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }

    public function test_a_type_never_inherits_another_users_group_permissions(): void
    {
        $this->administrator();
        $user = User::factory()->create();
        $user->types()->attach(3);
        $user->groups()->attach(6);
        $this->assertFalse($user->hasPermission('users.manage'));
        $this->signIn($user)->get('/settings/users')->assertForbidden();
        // The level-manager group has its own mailbox, never the administrator's powers.
        $this->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('auth.permissions', fn ($codes) => collect($codes)->sort()->values()->all() === ['defenses.assess', 'defenses.assigned', 'hr.self', 'messages.use']));
    }

    public function test_incompatible_group_membership_cannot_grant_a_permission_even_if_inserted_directly(): void
    {
        $user = User::factory()->create();
        $user->types()->attach(1);
        $user->groups()->attach(8);
        $this->assertSame([], $user->permissionCodes());
        $this->signIn($user)->get('/settings/users')->assertForbidden();
    }

    public function test_an_ordinary_account_cannot_read_or_mutate_users_via_direct_urls(): void
    {
        $target = $this->administrator();
        $user = User::factory()->create();
        $this->signIn($user)->get('/settings/users/'.$target->id.'/edit')->assertForbidden();
        $this->post('/settings/users', $this->newAccount())->assertForbidden();
        $this->put('/settings/users/'.$target->id, $this->payload($target, ['is_active' => false]))->assertForbidden();
        $this->put('/settings/groups/1', ['permissions' => ['users.view', 'users.manage']])->assertForbidden();
        $this->assertTrue($target->fresh()->is_active);
        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseCount('access_audits', 0);
    }

    public function test_read_only_permission_allows_listing_but_not_writing(): void
    {
        $user = User::factory()->create();
        $user->types()->attach(4);
        $user->groups()->attach(4);
        UserGroup::findOrFail(4)->permissions()->sync(['users.view']);
        $this->signIn($user)->get('/settings/users')->assertOk();
        $this->get('/settings/users/create')->assertForbidden();
        $this->post('/settings/users', $this->newAccount())->assertForbidden();
    }

    public function test_an_administrator_can_create_an_account_with_explicit_compatible_access(): void
    {
        $this->signIn($this->administrator())->post('/settings/users', $this->newAccount([
            'auth_version' => 999, 'must_change_password' => false, 'permissions' => ['users.manage'],
        ]))->assertSessionHasNoErrors();
        $user = User::where('email', 'student@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('temporary-password-2026', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertSame(1, $user->auth_version);
        $this->assertSame([1], $user->types()->pluck('user_types.id')->all());
        $this->assertSame([1], $user->groups()->pluck('user_groups.id')->all());
        $this->assertFalse($user->hasPermission('users.manage'));
        $this->assertDatabaseHas('access_audits', ['action' => 'user.created', 'target_user_id' => $user->id]);
        $this->assertStringNotContainsString('temporary-password', DB::table('access_audits')->value('metadata'));
    }

    public function test_incompatible_groups_and_duplicate_emails_are_rejected(): void
    {
        $admin = $this->administrator();
        $this->signIn($admin)->post('/settings/users', $this->newAccount(['group_ids' => [8]]))
            ->assertSessionHasErrors('group_ids');
        $this->post('/settings/users', $this->newAccount(['email' => strtoupper($admin->email)]))
            ->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('access_audits', 0);
        $this->assertArrayNotHasKey('password', session()->getOldInput());
        $this->assertArrayNotHasKey('password_confirmation', session()->getOldInput());
    }

    public function test_privacy_is_preserved_in_the_account_list_and_edit_props(): void
    {
        $admin = $this->administrator();
        $this->signIn($admin)->get('/settings/users')->assertInertia(fn (Assert $page) => $page
            ->component('settings/users/index')->has('users.data', 1)
            ->missing('users.data.0.password')->missing('users.data.0.remember_token')->missing('users.data.0.auth_version')
            ->missing('auth.user.password')->missing('auth.user.auth_version'));
        $this->get('/settings/users/'.$admin->id.'/edit')->assertInertia(fn (Assert $page) => $page
            ->missing('account.password')->missing('account.remember_token')->missing('account.auth_version'));
    }

    public function test_search_type_group_and_status_filters_are_combined(): void
    {
        $admin = $this->administrator();
        $student = User::factory()->create(['name' => 'Cible recherchée', 'is_active' => false]);
        $student->types()->attach(1);
        $student->groups()->attach(1);
        User::factory()->create(['name' => 'Cible différente']);
        $this->signIn($admin)->get('/settings/users?search=Cible&type_id=1&group_id=1&status=inactive')
            ->assertInertia(fn (Assert $page) => $page->has('users.data', 1)->where('users.data.0.id', $student->id)
                ->where('statistics.total', 3)->where('statistics.active', 2)->where('statistics.inactive', 1));
        $this->get('/settings/users?group_id=9999')->assertSessionHasErrors('group_id');
    }

    public function test_pagination_is_bounded_and_counts_accounts_only_once(): void
    {
        $admin = $this->administrator();
        User::factory()->count(21)->create();
        $admin->types()->attach(2);
        $admin->groups()->attach(9);
        $this->signIn($admin)->get('/settings/users')->assertInertia(fn (Assert $page) => $page
            ->has('users.data', 20)->where('users.total', 22)->where('users.last_page', 2));
        $this->get('/settings/users?page=2')->assertInertia(fn (Assert $page) => $page->has('users.data', 2));
    }

    public function test_account_changes_revoke_sessions_and_remember_tokens(): void
    {
        $admin = $this->administrator();
        $user = User::factory()->create();
        $user->types()->attach(1);
        $user->groups()->attach(1);
        $token = $user->remember_token;
        DB::table('sessions')->insert([
            ['id' => 'target-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()],
            ['id' => 'admin-session', 'user_id' => $admin->id, 'payload' => '', 'last_activity' => time()],
        ]);
        $this->signIn($admin)->put('/settings/users/'.$user->id, $this->payload($user, ['is_active' => false]))
            ->assertRedirect('/settings/users')->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame(2, $user->fresh()->auth_version);
        $this->assertNotSame($token, $user->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'target-session']);
        $this->assertDatabaseHas('sessions', ['id' => 'admin-session']);
    }

    public function test_password_cannot_be_overwritten_through_an_account_update(): void
    {
        $admin = $this->administrator();
        $user = User::factory()->create();
        $user->types()->attach(1);
        $oldHash = $user->password;
        $this->signIn($admin)->put('/settings/users/'.$user->id, $this->payload($user, ['password' => 'forged-password-123']))
            ->assertSessionHasErrors('password');
        $this->assertSame($oldHash, $user->fresh()->password);
    }

    public function test_self_deactivation_is_refused(): void
    {
        $admin = $this->administrator();
        $this->administrator();
        $this->signIn($admin)->put('/settings/users/'.$admin->id, $this->payload($admin, ['is_active' => false]))
            ->assertSessionHasErrors('is_active');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_the_last_administrator_cannot_remove_their_own_group(): void
    {
        $admin = $this->administrator();
        $this->signIn($admin)->put('/settings/users/'.$admin->id, $this->payload($admin, ['group_ids' => []]))
            ->assertSessionHasErrors('group_ids');
        $this->assertTrue($admin->fresh()->hasPermission('users.manage'));
        $this->assertDatabaseCount('access_audits', 0);
    }

    public function test_a_delegated_manager_cannot_disable_the_last_administrator(): void
    {
        $admin = $this->administrator();
        $manager = User::factory()->create();
        $manager->types()->attach(3);
        $manager->groups()->attach(7);
        UserGroup::findOrFail(7)->permissions()->sync(['users.view', 'users.manage']);
        $this->signIn($manager)->put('/settings/users/'.$admin->id, $this->payload($admin, ['is_active' => false]))
            ->assertSessionHasErrors('group_ids');
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_another_administrator_can_be_disabled_when_one_remains(): void
    {
        $actor = $this->administrator();
        $target = $this->administrator();
        $this->signIn($actor)->put('/settings/users/'.$target->id, $this->payload($target, ['is_active' => false]))
            ->assertSessionHasNoErrors();
        $this->assertFalse($target->fresh()->is_active);
        $this->assertTrue($actor->fresh()->hasPermission('users.manage'));
    }

    public function test_group_permission_changes_apply_to_explicit_members_immediately(): void
    {
        $admin = $this->administrator();
        $member = User::factory()->create();
        $member->types()->attach(2);
        $member->groups()->attach(9);
        $peer = User::factory()->create();
        $peer->types()->attach(2);
        $peer->groups()->attach(5);
        $this->signIn($admin)->put('/settings/groups/9', ['permissions' => ['users.view']])->assertSessionHasNoErrors();
        $this->assertTrue($member->hasPermission('users.view'));
        $this->assertFalse($peer->hasPermission('users.view'));
        $this->signIn($member)->get('/settings/users')->assertOk();
        app(ManageAccess::class)->updateGroup($admin, UserGroup::findOrFail(9), []);
        $this->get('/settings/users')->assertForbidden();
    }

    public function test_administrator_permissions_and_unknown_permissions_are_protected(): void
    {
        $this->signIn($this->administrator())->put('/settings/groups/8', ['permissions' => []])->assertSessionHasErrors('permissions');
        $this->put('/settings/groups/1', ['permissions' => ['super-admin']])->assertSessionHasErrors('permissions.0');
        $this->put('/settings/groups/1', ['permissions' => ['users.manage']])->assertSessionHasErrors('permissions');
        $this->assertDatabaseCount('group_permission', 86);
        $this->assertDatabaseCount('access_audits', 0);
    }

    public function test_a_revoked_actor_is_rechecked_inside_the_access_transaction(): void
    {
        $actor = $this->administrator();
        $actor->forceFill(['is_active' => false])->save();
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(ManageAccess::class)->createUser($actor, $this->newAccount());
    }

    public function test_inactive_credentials_are_rejected_and_existing_sessions_are_closed(): void
    {
        $user = User::factory()->create(['is_active' => false]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $user->forceFill(['is_active' => true])->save();
        $this->signIn($user);
        User::whereKey($user->id)->update(['is_active' => false]);
        $this->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_a_stale_session_version_requires_reauthentication(): void
    {
        $user = User::factory()->create();
        $this->signIn($user);
        User::whereKey($user->id)->update(['auth_version' => 2]);
        $this->get('/profile')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_temporary_password_accounts_must_change_their_password_before_any_other_page(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/password/change');
        $this->get('/dashboard')->assertRedirect('/password/change');
        $this->get('/settings/users')->assertRedirect('/password/change');
        $this->get('/password/change')->assertOk();
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_password_change_requires_the_current_password_and_matching_confirmation(): void
    {
        $this->signIn(User::factory()->create())->patch('/password', [
            'current_password' => 'wrong', 'password' => 'new-password-2026', 'password_confirmation' => 'different',
        ])->assertSessionHasErrors(['current_password', 'password']);
        $this->assertArrayNotHasKey('current_password', session()->getOldInput());
        $this->assertArrayNotHasKey('password', session()->getOldInput());
    }

    public function test_password_change_rehashes_clears_the_requirement_and_revokes_other_sessions(): void
    {
        $user = User::factory()->create(['must_change_password' => true]);
        $remember = $user->remember_token;
        DB::table('sessions')->insert(['id' => 'other-session', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
        $this->signIn($user)->patch('/password', [
            'current_password' => 'password', 'password' => 'my-new-password-2026', 'password_confirmation' => 'my-new-password-2026',
        ])->assertRedirect('/profile')->assertSessionHasNoErrors();
        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('my-new-password-2026', $fresh->password));
        $this->assertFalse($fresh->must_change_password);
        $this->assertNotSame($remember, $fresh->remember_token);
        $this->assertSame(2, session('auth_version'));
        $this->assertDatabaseMissing('sessions', ['id' => 'other-session']);
        $this->get('/profile')->assertOk();
        $this->assertStringNotContainsString('my-new-password', DB::table('access_audits')->value('metadata'));
    }

    public function test_access_mutations_require_csrf_outside_the_test_bypass(): void
    {
        $admin = $this->administrator();
        $this->app->instance('env', 'local');
        $this->signIn($admin)->post('/settings/users', $this->newAccount())->assertStatus(419);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_administrator_assignment_is_explicit_and_can_be_cancelled(): void
    {
        $user = User::factory()->create(['email' => 'bootstrap@example.test']);
        $question = 'Accorder à ce compte les droits d’administrateur plateforme (tous les modules disponibles) ?';
        $this->artisan('checkmaster:grant-admin', ['email' => $user->email])->expectsConfirmation($question, 'no')
            ->expectsOutput('Aucune modification.')->assertExitCode(0);
        $this->assertCount(0, $user->fresh()->groups);
        $this->artisan('checkmaster:grant-admin', ['email' => $user->email])->expectsConfirmation($question, 'yes')
            ->expectsOutput('Droits attribués. Le compte doit se reconnecter.')->assertExitCode(0);
        $this->assertTrue($user->fresh()->hasPermission('users.manage'));
        $this->assertDatabaseHas('access_audits', ['action' => 'administrator.granted.from.console', 'target_user_id' => $user->id]);
    }
}
