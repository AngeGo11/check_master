<?php

namespace Tests\Feature;

use App\Actions\Students\ManageStudents;
use App\Models\Permission;
use App\Models\Promotion;
use App\Models\Student;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StudentRecordsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->withoutVite(); }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->types()->attach(3);
        $user->groups()->attach(8);
        return $user;
    }

    private function delegate(array $permissions): User
    {
        $user = User::factory()->create();
        $user->types()->attach(4);
        $user->groups()->attach(4);
        UserGroup::findOrFail(4)->permissions()->sync($permissions);
        return $user;
    }

    private function studentAccount(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->types()->attach(1);
        $user->groups()->attach(1);
        return $user;
    }

    private function signIn(User $user): static
    {
        return $this->actingAs($user->fresh())->withSession(['auth_version' => $user->fresh()->auth_version]);
    }

    private function data(array $overrides = []): array
    {
        return [...[
            'card_number' => 'CI-2026-001', 'last_name' => 'Kouadio', 'first_name' => 'Camille',
            'email' => 'camille@example.test', 'date_of_birth' => '2001-06-12', 'gender' => null,
            'study_level_id' => 5, 'promotion_id' => 22, 'student_status_id' => 1, 'is_archived' => false,
        ], ...$overrides];
    }

    private function record(array $overrides = []): Student
    {
        $student = new Student;
        $student->forceFill($this->data($overrides))->save();
        return $student;
    }

    public function test_only_historical_reference_catalogues_are_seeded(): void
    {
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('study_levels', 5);
        $this->assertDatabaseCount('student_statuses', 3);
        $this->assertDatabaseCount('promotions', 22);
        $this->assertDatabaseHas('study_levels', ['id' => 5, 'name' => 'Master 2']);
        $this->assertDatabaseHas('student_statuses', ['id' => 2, 'name' => 'À cheval']);
        $this->assertDatabaseHas('promotions', ['id' => 22, 'name' => '2024-2025']);
        $this->assertDatabaseHas('group_permission', ['user_group_id' => 1, 'permission_code' => 'students.self']);
        $this->assertDatabaseMissing('group_permission', ['user_group_id' => 1, 'permission_code' => 'students.view']);
        $this->assertDatabaseCount('group_permission', 9);
    }

    public function test_guests_and_unprivileged_accounts_cannot_access_student_management(): void
    {
        $student = $this->record();
        foreach (['/students', '/students/create', '/students/'.$student->id, '/settings/academic', '/my-student-record'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
        $user = User::factory()->create();
        $user->types()->attach(4);
        $this->signIn($user)->get('/students')->assertForbidden();
        $this->post('/students', $this->data())->assertForbidden();
        $this->put('/students/'.$student->id, $this->data(['revision' => 1]))->assertForbidden();
        $this->put('/students/'.$student->id.'/account', ['account_email' => $user->email, 'revision' => 1])->assertForbidden();
        $this->post('/settings/promotions', ['name' => '2026-2027'])->assertForbidden();
        $this->assertDatabaseCount('student_audits', 0);
    }

    public function test_a_reader_can_consult_records_but_cannot_change_or_link_them(): void
    {
        $student = $this->record();
        $this->signIn($this->delegate(['students.view']))->get('/students')->assertOk();
        $this->get('/students/'.$student->id)->assertInertia(fn (Assert $page) => $page
            ->component('students/show')->where('student.id', $student->id)->where('canLink', false)->where('account', null));
        $this->get('/students/create')->assertForbidden();
        $this->get('/students/'.$student->id.'/edit')->assertForbidden();
        $this->put('/students/'.$student->id, $this->data(['revision' => 1]))->assertForbidden();
    }

    public function test_creating_a_student_normalizes_identity_and_does_not_create_a_login(): void
    {
        $admin = $this->admin();
        $this->signIn($admin)->post('/students', $this->data([
            'card_number' => ' ci-2026-001 ', 'email' => ' CAMILLE@EXAMPLE.TEST ', 'last_name' => ' Kouadio ',
            'id' => 800, 'permissions' => ['users.manage'],
        ]))->assertSessionHasNoErrors();
        $student = Student::sole();
        $this->assertSame('CI-2026-001', $student->card_number);
        $this->assertSame('camille@example.test', $student->email);
        $this->assertSame('Kouadio', $student->last_name);
        $this->assertNotSame(800, $student->id);
        $this->assertNull($student->user_id);
        $this->assertSame(1, $student->revision);
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('student_audits', ['actor_user_id' => $admin->id, 'student_id' => $student->id, 'action' => 'student.created']);
        $metadata = DB::table('student_audits')->value('metadata');
        $this->assertStringNotContainsString('camille@example.test', $metadata);
        $this->assertStringNotContainsString('2001-06-12', $metadata);
    }

    public function test_invalid_references_dates_and_identity_fields_are_rejected_without_writes(): void
    {
        $this->signIn($this->admin())->post('/students', $this->data([
            'study_level_id' => 999, 'promotion_id' => 999, 'student_status_id' => 999,
            'date_of_birth' => now()->addDay()->format('Y-m-d'), 'email' => 'invalid', 'card_number' => '<script>',
        ]))->assertSessionHasErrors(['study_level_id', 'promotion_id', 'student_status_id', 'date_of_birth', 'email', 'card_number']);
        $this->assertDatabaseCount('students', 0);
        $this->assertDatabaseCount('student_audits', 0);
    }

    public function test_duplicate_cards_and_emails_remain_reserved_when_archived(): void
    {
        $this->record(['is_archived' => true]);
        $this->signIn($this->admin())->post('/students', $this->data(['card_number' => 'ci-2026-001', 'email' => 'CAMILLE@EXAMPLE.TEST']))
            ->assertSessionHasErrors(['card_number', 'email']);
        $this->assertDatabaseCount('students', 1);
    }

    public function test_identity_link_and_eligibility_cannot_be_forged_through_student_fields(): void
    {
        $admin = $this->admin();
        $this->signIn($admin)->post('/students', $this->data(['user_id' => $admin->id, 'eligibility' => 'Éligible', 'password' => 'predictable']))
            ->assertSessionHasErrors(['user_id', 'eligibility', 'password']);
        $this->assertDatabaseCount('students', 0);
        $this->assertArrayNotHasKey('password', session()->getOldInput());
    }

    public function test_students_can_be_updated_archived_and_restored_without_changing_the_login(): void
    {
        $account = $this->studentAccount();
        $student = $this->record(['user_id' => $account->id]);
        $email = $account->email;
        $this->signIn($this->admin())->put('/students/'.$student->id, $this->data(['revision' => 1, 'is_archived' => true, 'email' => 'new-contact@example.test']))
            ->assertRedirect('/students/'.$student->id)->assertSessionHasNoErrors();
        $this->assertTrue($student->fresh()->is_archived);
        $this->assertTrue($account->fresh()->is_active);
        $this->assertSame($email, $account->fresh()->email);
        $this->assertSame($account->id, $student->fresh()->user_id);
        $this->put('/students/'.$student->id, $this->data(['revision' => 2]))->assertSessionHasNoErrors();
        $this->assertFalse($student->fresh()->is_archived);
        $this->assertSame(3, $student->fresh()->revision);
    }

    public function test_a_stale_form_cannot_overwrite_a_more_recent_edit(): void
    {
        $student = $this->record();
        $this->signIn($this->admin())->put('/students/'.$student->id, $this->data(['revision' => 1, 'first_name' => 'Prénom corrigé']))->assertSessionHasNoErrors();
        $this->put('/students/'.$student->id, $this->data(['revision' => 1, 'is_archived' => true]))->assertSessionHasErrors('revision');
        $this->assertSame('Prénom corrigé', $student->fresh()->first_name);
        $this->assertFalse($student->fresh()->is_archived);
        $this->assertDatabaseCount('student_audits', 1);
    }

    public function test_filters_combine_search_promotion_level_status_and_archive(): void
    {
        $student = $this->record(['is_archived' => true, 'student_status_id' => 2]);
        $this->record(['card_number' => 'OTHER', 'email' => 'other@example.test']);
        $this->signIn($this->admin())->get('/students?search=Kouadio&study_level_id=5&promotion_id=22&student_status_id=2&archive=archived')
            ->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->where('students.data.0.id', $student->id)
                ->where('statistics.total', 2)->where('statistics.archived', 1));
        $this->get('/students')->assertInertia(fn (Assert $page) => $page->has('students.data', 1)->where('students.data.0.card_number', 'OTHER'));
        $this->get('/students?promotion_id=999&archive=unknown')->assertSessionHasErrors(['promotion_id', 'archive']);
    }

    public function test_pagination_is_stable_and_preserves_filters(): void
    {
        for ($i = 1; $i <= 21; $i++) { $this->record(['card_number' => 'C'.$i, 'email' => 'student'.$i.'@example.test']); }
        $this->signIn($this->admin())->get('/students?promotion_id=22')->assertInertia(fn (Assert $page) => $page
            ->has('students.data', 20)->where('students.total', 21)->where('students.last_page', 2)
            ->where('students.next_page_url', fn ($url) => str_contains($url, 'promotion_id=22')));
        $this->get('/students?promotion_id=22&page=2')->assertInertia(fn (Assert $page) => $page->has('students.data', 1));
    }

    public function test_list_and_detail_props_do_not_leak_credentials_or_unneeded_identity_fields(): void
    {
        $account = $this->studentAccount();
        $student = $this->record(['user_id' => $account->id]);
        $this->signIn($this->admin())->get('/students')->assertInertia(fn (Assert $page) => $page
            ->missing('students.data.0.user_id')->missing('students.data.0.user')->missing('students.data.0.date_of_birth'));
        $this->get('/students/'.$student->id)->assertInertia(fn (Assert $page) => $page
            ->where('account.id', $account->id)->missing('account.password')->missing('account.remember_token')->missing('account.auth_version'));
    }

    public function test_linking_an_existing_student_account_requires_both_management_permissions(): void
    {
        $student = $this->record();
        $account = $this->studentAccount();
        $this->signIn($this->delegate(['students.view', 'students.manage']))->put('/students/'.$student->id.'/account', ['account_email' => $account->email, 'revision' => 1])->assertForbidden();
        $this->signIn($this->delegate(['users.view', 'users.manage']))->put('/students/'.$student->id.'/account', ['account_email' => $account->email, 'revision' => 1])->assertForbidden();
        $this->assertNull($student->fresh()->user_id);
        $this->signIn($this->admin())->put('/students/'.$student->id.'/account', ['account_email' => strtoupper($account->email), 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame($account->id, $student->fresh()->user_id);
        $this->assertSame(2, $student->fresh()->revision);
    }

    public function test_an_account_cannot_be_linked_to_two_dossiers_including_archived_ones(): void
    {
        $account = $this->studentAccount();
        $first = $this->record(['user_id' => $account->id, 'is_archived' => true]);
        $second = $this->record(['card_number' => 'SECOND', 'email' => 'second@example.test']);
        $this->signIn($this->admin())->put('/students/'.$second->id.'/account', ['account_email' => $account->email, 'revision' => 1])->assertSessionHasErrors('account_email');
        $this->assertNull($second->fresh()->user_id);
        $this->assertSame($account->id, $first->fresh()->user_id);
        $this->assertDatabaseCount('student_audits', 0);
    }

    public function test_inactive_nonstudent_missing_accounts_and_archived_records_cannot_be_linked(): void
    {
        $student = $this->record();
        $inactive = $this->studentAccount(['is_active' => false]);
        $ordinary = User::factory()->create();
        $eligible = $this->studentAccount();
        $this->signIn($this->admin());
        foreach ([$inactive->email, $ordinary->email, 'missing@example.test'] as $email) {
            $this->put('/students/'.$student->id.'/account', ['account_email' => $email, 'revision' => 1])->assertSessionHasErrors('account_email');
        }
        $student->forceFill(['is_archived' => true])->save();
        $this->put('/students/'.$student->id.'/account', ['account_email' => $eligible->email, 'revision' => 1])->assertSessionHasErrors('account_email');
        $this->assertNull($student->fresh()->user_id);
    }

    public function test_a_student_sees_only_the_explicitly_linked_dossier(): void
    {
        $account = $this->studentAccount();
        $own = $this->record(['user_id' => $account->id]);
        $other = $this->record(['card_number' => 'OTHER', 'email' => 'other@example.test']);
        $this->signIn($account)->get('/my-student-record?student_id='.$other->id)->assertInertia(fn (Assert $page) => $page
            ->where('student.id', $own->id)->where('personal', true)->where('canLink', false)->missing('students'));
        $this->get('/students')->assertForbidden();
        $this->get('/students/'.$other->id)->assertForbidden();
        $this->get('/students/'.$own->id.'/edit')->assertForbidden();
        $this->put('/students/'.$own->id, $this->data(['revision' => 1]))->assertForbidden();
    }

    public function test_matching_email_alone_never_grants_access_and_changed_login_email_keeps_the_link(): void
    {
        $account = $this->studentAccount(['email' => 'camille@example.test']);
        $student = $this->record();
        $this->signIn($account)->get('/my-student-record')->assertInertia(fn (Assert $page) => $page->where('student', null));
        app(ManageStudents::class)->linkAccount($this->admin(), $student, $account->email, 1);
        $account->forceFill(['email' => 'changed-login@example.test'])->save();
        $this->signIn($account)->get('/my-student-record')->assertInertia(fn (Assert $page) => $page->where('student.id', $student->id));
    }

    public function test_unlinking_and_permission_or_type_removal_take_effect_immediately(): void
    {
        $admin = $this->admin();
        $account = $this->studentAccount();
        $student = $this->record(['user_id' => $account->id]);
        $this->signIn($account)->get('/my-student-record')->assertOk();
        app(ManageStudents::class)->linkAccount($admin, $student, null, 1);
        $this->get('/my-student-record')->assertInertia(fn (Assert $page) => $page->where('student', null));
        UserGroup::findOrFail(1)->permissions()->sync([]);
        $this->get('/my-student-record')->assertForbidden();
        UserGroup::findOrFail(1)->permissions()->sync(['students.self']);
        $account->types()->detach(1);
        $this->get('/my-student-record')->assertForbidden();
    }

    public function test_a_stale_link_form_cannot_reassign_identity(): void
    {
        $student = $this->record();
        $first = $this->studentAccount();
        $second = $this->studentAccount();
        $this->signIn($this->admin())->put('/students/'.$student->id.'/account', ['account_email' => $first->email, 'revision' => 1])->assertSessionHasNoErrors();
        $this->put('/students/'.$student->id.'/account', ['account_email' => $second->email, 'revision' => 1])->assertSessionHasErrors('revision');
        $this->assertSame($first->id, $student->fresh()->user_id);
        $this->assertDatabaseCount('student_audits', 1);
    }

    public function test_dashboard_counts_use_actual_active_records_and_require_directory_access(): void
    {
        $this->record();
        $this->record(['card_number' => 'OLD', 'email' => 'old@example.test', 'is_archived' => true]);
        $this->signIn($this->admin())->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('studentCount', 1)->has('studentsByLevel', 1)->where('studentsByLevel.0.name', 'Master 2')->where('studentsByLevel.0.total', 1));
        $this->signIn($this->studentAccount())->get('/dashboard')->assertInertia(fn (Assert $page) => $page
            ->where('studentCount', null)->where('studentsByLevel', null));
    }

    public function test_promotions_can_be_created_and_renamed_without_detaching_students(): void
    {
        $student = $this->record();
        $this->signIn($this->admin())->post('/settings/promotions', ['name' => ' 2026-2027 '])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('promotions', ['name' => '2026-2027']);
        $this->put('/settings/promotions/22', ['name' => 'Promotion 2024-2025', 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame(22, $student->fresh()->promotion_id);
        $this->get('/settings/academic')->assertInertia(fn (Assert $page) => $page->component('settings/academic')
            ->where('promotions.0.name', 'Promotion 2024-2025')->where('promotions.0.students_count', 1));
        $this->put('/settings/promotions/22', ['name' => 'Écrasement', 'revision' => 1])->assertSessionHasErrors('revision');
        $this->assertSame('Promotion 2024-2025', Promotion::findOrFail(22)->name);
    }

    public function test_reference_reader_cannot_mutate_promotions_and_duplicate_names_are_rejected(): void
    {
        $this->signIn($this->delegate(['academic.view']))->get('/settings')->assertOk();
        $this->get('/settings/academic')->assertOk();
        $this->post('/settings/promotions', ['name' => '2026-2027'])->assertForbidden();
        $this->put('/settings/promotions/22', ['name' => 'Change', 'revision' => 1])->assertForbidden();
        $this->signIn($this->admin())->post('/settings/promotions', ['name' => '2024-2025'])->assertSessionHasErrors('name');
        $this->assertDatabaseCount('promotions', 22);
    }

    public function test_new_permissions_are_delegable_but_management_requires_read_access(): void
    {
        $this->signIn($this->admin())->put('/settings/groups/4', ['permissions' => ['students.manage', 'academic.manage']])->assertSessionHasErrors('permissions');
        $this->put('/settings/groups/4', ['permissions' => Permission::CODES])->assertSessionHasNoErrors();
        $this->assertCount(count(Permission::CODES), UserGroup::findOrFail(4)->permissions);
    }

    public function test_revoked_actor_is_rechecked_inside_the_student_transaction(): void
    {
        $actor = $this->delegate(['students.view', 'students.manage']);
        UserGroup::findOrFail(4)->permissions()->sync([]);
        try { app(ManageStudents::class)->saveStudent($actor, $this->data()); $this->fail('A revoked actor was accepted.'); }
        catch (AuthorizationException) { $this->assertDatabaseCount('students', 0); }
    }

    public function test_uniqueness_is_rechecked_inside_the_transaction_after_validation(): void
    {
        $actor = $this->admin();
        $data = $this->data();
        $this->record();
        try { app(ManageStudents::class)->saveStudent($actor, $data); $this->fail('A duplicate dossier was accepted.'); }
        catch (ValidationException) { $this->assertDatabaseCount('students', 1); $this->assertDatabaseCount('student_audits', 0); }
    }

    public function test_student_mutations_require_csrf_in_real_requests(): void
    {
        $actor = $this->admin();
        $this->app->instance('env', 'local');
        $this->signIn($actor)->post('/students', $this->data())->assertStatus(419);
        $this->post('/settings/promotions', ['name' => '2026-2027'])->assertStatus(419);
        $this->assertDatabaseCount('students', 0);
    }
}
