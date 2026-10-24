<?php
namespace Tests\Feature;
use App\Actions\HumanResources\{ManageStaff, ManageStaffCatalog, ManageStaffPhotos};
use App\Actions\Collaboration\ManageMeetings;
use App\Models\{StaffProfile, StaffReference, StaffAssignment, StaffPhoto, User, UserGroup};
use App\Services\ReportAccess;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Support\CollaborationFixtures;
use Tests\TestCase;
class HumanResourcesTest extends TestCase
{
    use RefreshDatabase, CollaborationFixtures;
    protected function setUp(): void { parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 22)->setTime(9, 0)); Storage::fake('staff'); }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function data(array $overrides = []): array
    {
        return [...['kind' => 'teacher', 'last_name' => 'Fictif', 'first_name' => 'Enseignant', 'email' => Str::ulid().'@example.test', 'phone' => '+225 0102030405', 'gender' => 'Femme', 'date_of_birth' => '1990-01-01', 'hired_on' => '2020-09-01', 'position' => null, 'specialty_ids' => [], 'grade_id' => null, 'grade_since' => null, 'function_id' => null, 'function_since' => null, 'revision' => 0, 'operation_key' => (string) Str::uuid()], ...$overrides];
    }
    private function profile(array $overrides = []): array
    {
        $admin = $this->person(8); $data = $this->data($overrides); return [$admin, app(ManageStaff::class)->save($admin, $data), $data];
    }
    private function reference(string $kind, int $legacyId = 1): StaffReference { return StaffReference::where('kind', $kind)->where('legacy_id', $legacyId)->firstOrFail(); }
    private function assignmentData(StaffProfile $p, ?StaffReference $ref, array $overrides = []): array
    {
        return [...['kind' => $ref?->kind ?? 'grade', 'reference_id' => $ref?->id, 'effective_on' => '2026-10-01', 'revision' => $p->fresh()->revision], ...$overrides];
    }
    private function attach(User $admin, StaffProfile $p, User $user): void { app(ManageStaff::class)->link($admin, $p, ['account_email' => $user->email, 'revision' => $p->fresh()->revision]); }
    private function photoData(StaffProfile $p, array $overrides = []): array { return [...['revision' => $p->fresh()->revision, 'operation_key' => (string) Str::uuid()], ...$overrides]; }
    public function test_migration_preserves_reference_catalogs_without_importing_people_or_passwords(): void
    {
        $this->assertDatabaseCount('permissions', 42); $this->assertDatabaseCount('group_permission', 79); $this->assertDatabaseCount('staff_references', 16);
        foreach (['staff_profiles', 'staff_assignments', 'staff_photos', 'users'] as $table) { $this->assertDatabaseCount($table, 0); }
        $this->assertSame("Professeur d'université", $this->reference('grade')->name); $this->assertSame('Enseignant vacataire', $this->reference('function', 4)->name); $this->assertSame('Big Data', $this->reference('specialty', 8)->name);
        $this->assertSame([8], DB::table('group_permission')->where('permission_code', 'hr.manage')->pluck('user_group_id')->all());
        $this->assertSame(range(2, 9), DB::table('group_permission')->where('permission_code', 'hr.self')->orderBy('user_group_id')->pluck('user_group_id')->all());
    }
    public function test_guests_nonadministrators_and_incompatible_memberships_cannot_open_hr_management(): void
    {
        $this->get('/staff')->assertRedirect('/login');
        foreach ([1, 2, 3, 4, 5, 6, 7, 9] as $group) {
            $this->signIn($this->person($group))->get('/staff')->assertForbidden(); $this->get('/staff/catalogs')->assertForbidden(); $this->post('/staff', $this->data())->assertForbidden();
        }
        $bad = $this->person(1); $bad->groups()->attach(8); $this->signIn($bad)->get('/staff')->assertForbidden();
        $this->signIn($this->person(8))->get('/staff')->assertOk();
    }
    public function test_read_permission_does_not_grant_mutation_or_linking_and_catalog_right_is_separate(): void
    {
        [$admin, $p, $data] = $this->profile(); UserGroup::findOrFail(8)->permissions()->detach(['hr.manage', 'hr.catalogs.manage']);
        $this->signIn($admin)->get('/staff/'.$p->id)->assertInertia(fn (Assert $v) => $v->where('canManage', false)->where('canLink', false)->where('account', null));
        $this->get('/staff/create')->assertForbidden(); $this->put('/staff/'.$p->id, [...$data, 'revision' => 1])->assertForbidden();
        $this->post('/staff/catalogs', ['kind' => 'grade', 'name' => 'Test', 'is_active' => true, 'revision' => 0])->assertForbidden();
    }
    public function test_create_teacher_normalizes_identity_and_assigns_grade_function_and_multiple_specialties_atomically(): void
    {
        $admin = $this->person(8); $grade = $this->reference('grade'); $function = $this->reference('function'); $specialty = $this->reference('specialty'); $second = $this->reference('specialty', 2);
        $data = $this->data(['last_name' => '  Fictif  ', 'email' => ' TEACHER@EXAMPLE.TEST ', 'grade_id' => $grade->id, 'grade_since' => '2021-01-01', 'function_id' => $function->id, 'function_since' => '2022-01-01', 'specialty_ids' => [$specialty->id, $second->id]]);
        $this->signIn($admin)->post('/staff', $data)->assertSessionHasNoErrors(); $p = StaffProfile::firstOrFail();
        $this->assertSame('teacher@example.test', $p->email); $this->assertSame('Fictif', $p->last_name); $this->assertSame(1, $p->revision); $this->assertNull($p->user_id);
        $this->assertDatabaseCount('users', 1); $this->assertDatabaseCount('staff_assignments', 2); $this->assertDatabaseCount('staff_specialties', 2);
        $this->get('/staff/'.$p->id)->assertInertia(fn (Assert $v) => $v->has('profile.current_assignments', 2)->has('profile.specialties', 2));
    }
    public function test_administrative_profile_saves_coordinates_and_position_without_assigning_access(): void
    {
        [$admin, $p] = $this->profile(['kind' => 'administrative', 'position' => 'Chargé de communication']);
        $this->assertSame('Chargé de communication', $p->position); $this->assertNull($p->user_id); $this->assertDatabaseCount('group_user', 1);
        $this->signIn($admin)->get('/staff?kind=administrative')->assertInertia(fn (Assert $v) => $v->where('profiles.total', 1));
        $this->post('/staff', $this->data(['kind' => 'administrative', 'specialty_ids' => [$this->reference('specialty')->id]]))->assertSessionHasErrors('specialty_ids');
    }
    public function test_creation_retries_are_idempotent_and_key_reuse_cannot_change_content(): void
    {
        $admin = $this->person(8); $data = $this->data(); $this->signIn($admin);
        $this->post('/staff', $data)->assertSessionHasNoErrors(); $this->post('/staff', $data)->assertSessionHasNoErrors();
        $this->post('/staff', [...$data, 'first_name' => 'Autre personne'])->assertSessionHasErrors('operation_key');
        $this->assertDatabaseCount('staff_profiles', 1); $this->assertDatabaseCount('staff_audits', 1);
    }
    public function test_invalid_dates_phone_catalog_kinds_and_forged_identity_fields_are_rejected(): void
    {
        $admin = $this->person(8); $this->signIn($admin);
        foreach (['date_of_birth' => '2030-01-01', 'hired_on' => '1980-01-01', 'phone' => 'bad phone', 'specialty_ids' => [$this->reference('grade')->id], 'user_id' => $admin->id, 'password' => 'forged', 'groups' => [9], 'is_archived' => true, 'photo_path' => '../public/file.php', 'reference' => 'FORGED'] as $key => $value) {
            $this->post('/staff', $this->data([$key => $value]))->assertSessionHasErrors($key);
        }
        $this->post('/staff', $this->data(['grade_id' => $this->reference('grade')->id]))->assertSessionHasErrors('grade_since');
        $this->assertDatabaseCount('staff_profiles', 0);
    }
    public function test_duplicate_email_is_reserved_in_archived_records_and_case_is_normalized(): void
    {
        [$admin, $p] = $this->profile(['email' => 'reserved@example.test']); app(ManageStaff::class)->archive($admin, $p, ['revision' => 1, 'is_archived' => true, 'reason' => 'Archivage fictif']);
        $this->signIn($admin)->post('/staff', $this->data(['email' => ' RESERVED@EXAMPLE.TEST ']))->assertSessionHasErrors('email');
        $this->assertDatabaseCount('staff_profiles', 1);
    }
    public function test_updates_reject_stale_forms_type_switches_and_assignment_overwrites(): void
    {
        [$admin, $p, $data] = $this->profile(); $this->signIn($admin);
        $this->put('/staff/'.$p->id, [...$data, 'revision' => 1, 'first_name' => 'Modifié'])->assertSessionHasNoErrors();
        $this->put('/staff/'.$p->id, [...$data, 'revision' => 1])->assertSessionHasErrors('revision');
        $this->put('/staff/'.$p->id, [...$data, 'revision' => 2, 'kind' => 'administrative'])->assertSessionHasErrors('kind');
        $this->put('/staff/'.$p->id, [...$data, 'revision' => 2, 'grade_id' => $this->reference('grade')->id])->assertSessionHasErrors('grade_id');
        $this->assertSame('Modifié', $p->fresh()->first_name);
    }
    public function test_archival_is_reversible_preserves_history_and_does_not_change_the_linked_account_or_commission(): void
    {
        [$admin, $p, $data] = $this->profile(); $member = $this->person(9); $this->attach($admin, $p, $member); $before = app(ReportAccess::class)->roster()->modelKeys();
        $this->signIn($admin)->patch('/staff/'.$p->id.'/archive', ['revision' => 2, 'is_archived' => true, 'reason' => 'Archivage de la fiche'])->assertSessionHasNoErrors();
        $this->assertTrue($p->fresh()->is_archived); $this->assertTrue($member->fresh()->is_active); $this->assertSame($before, app(ReportAccess::class)->roster()->modelKeys());
        $this->put('/staff/'.$p->id, [...$data, 'revision' => 3])->assertSessionHasErrors('staff');
        $this->get('/staff')->assertInertia(fn (Assert $v) => $v->where('profiles.total', 0));
        $this->get('/staff?archive=archived')->assertInertia(fn (Assert $v) => $v->where('profiles.total', 1));
        $this->signIn($member)->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->where('profile.is_archived', true));
        $this->signIn($admin)->patch('/staff/'.$p->id.'/archive', ['revision' => 3, 'is_archived' => false, 'reason' => 'Retour de la fiche'])->assertSessionHasNoErrors();
        $this->assertFalse($p->fresh()->is_archived); $this->assertDatabaseHas('staff_audits', ['action' => 'staff.archived', 'staff_profile_id' => $p->id]);
    }
    public function test_catalog_creation_renaming_deactivation_and_case_duplicates_preserve_history(): void
    {
        [$admin, $p] = $this->profile(); $grade = $this->reference('grade'); app(ManageStaff::class)->assignment($admin, $p, $this->assignmentData($p, $grade));
        $this->signIn($admin)->put('/staff/catalogs/'.$grade->id, ['kind' => 'grade', 'name' => 'Grade renommé', 'is_active' => false, 'revision' => 1])->assertSessionHasNoErrors();
        $this->assertSame("Professeur d'université", $p->assignments()->first()->name_snapshot); $this->assertSame('Grade renommé', $grade->fresh()->name);
        $this->post('/staff/catalogs', ['kind' => 'grade', 'name' => ' GRADE   RENOMMÉ ', 'is_active' => true, 'revision' => 0])->assertSessionHasErrors('name');
        $this->post('/staff/catalogs', ['kind' => 'specialty', 'name' => 'Analyse appliquée', 'is_active' => true, 'revision' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('staff_references', 17);
    }
    public function test_catalog_mutations_reject_stale_versions_wrong_kind_and_forged_legacy_identifiers(): void
    {
        $admin = $this->person(8); $r = $this->reference('grade'); $data = ['kind' => 'grade', 'name' => 'Nouveau grade', 'is_active' => true, 'revision' => 1]; $this->signIn($admin);
        $this->put('/staff/catalogs/'.$r->id, $data)->assertSessionHasNoErrors(); $this->put('/staff/catalogs/'.$r->id, $data)->assertSessionHasErrors('revision');
        $this->put('/staff/catalogs/'.$r->id, [...$data, 'revision' => 2, 'kind' => 'function'])->assertSessionHasErrors('kind');
        $this->put('/staff/catalogs/'.$r->id, [...$data, 'revision' => 2, 'legacy_id' => 999])->assertSessionHasErrors('legacy_id');
    }
    public function test_inactive_specialties_can_be_kept_but_cannot_be_newly_assigned(): void
    {
        $s = $this->reference('specialty'); [$admin, $p, $data] = $this->profile(['specialty_ids' => [$s->id]]); $s->forceFill(['is_active' => false])->save();
        $this->signIn($admin)->put('/staff/'.$p->id, [...$data, 'revision' => 1, 'phone' => '+225 0203040506'])->assertSessionHasNoErrors();
        $this->post('/staff', $this->data(['specialty_ids' => [$s->id]]))->assertSessionHasErrors('specialty_ids');
        $this->put('/staff/'.$p->id, [...$data, 'revision' => 2, 'specialty_ids' => []])->assertSessionHasNoErrors(); $this->assertSame(0, $p->specialties()->count());
    }
    public function test_assignment_changes_close_previous_periods_and_preserve_snapshots(): void
    {
        [$admin, $p] = $this->profile(); $first = $this->reference('grade'); $second = $this->reference('grade', 2); $action = app(ManageStaff::class);
        $action->assignment($admin, $p, $this->assignmentData($p, $first, ['effective_on' => '2021-09-01']));
        $action->assignment($admin, $p, $this->assignmentData($p, $second, ['effective_on' => '2024-09-01']));
        $this->assertSame(1, $p->currentAssignments()->count()); $this->assertSame('2024-09-01', $p->assignments()->oldest('id')->first()->ended_on->format('Y-m-d'));
        $this->signIn($admin)->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, null))->assertSessionHasNoErrors();
        $this->assertSame(0, $p->currentAssignments()->count()); $this->assertSame(2, $p->assignments()->count());
        $this->get('/staff/'.$p->id)->assertInertia(fn (Assert $v) => $v->where('profile.assignments.total', 2));
    }
    public function test_assignment_rules_reject_wrong_catalog_inactive_values_backdating_and_stale_forms(): void
    {
        [$admin, $p] = $this->profile(); $grade = $this->reference('grade'); $function = $this->reference('function'); $other = $this->reference('grade', 2);
        app(ManageStaff::class)->assignment($admin, $p, $this->assignmentData($p, $grade)); $this->signIn($admin);
        $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, $function, ['kind' => 'grade']))->assertSessionHasErrors('reference_id');
        $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, $other, ['effective_on' => '2026-09-01']))->assertSessionHasErrors('effective_on');
        $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, $other, ['revision' => 1]))->assertSessionHasErrors('revision');
        $other->forceFill(['is_active' => false])->save(); $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, $other))->assertSessionHasErrors('reference_id');
        $this->assertDatabaseCount('staff_assignments', 1);
    }
    public function test_assignment_dates_must_follow_employment_and_closed_periods_and_cannot_be_future(): void
    {
        [$admin, $p] = $this->profile(); $grade = $this->reference('grade'); $this->signIn($admin);
        foreach (['2019-01-01', '2030-01-01'] as $date) { $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, $grade, ['effective_on' => $date]))->assertSessionHasErrors('effective_on'); }
        $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, $grade))->assertSessionHasNoErrors();
        $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, null, ['effective_on' => '2026-10-20']))->assertSessionHasNoErrors();
        $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, $grade, ['effective_on' => '2026-10-19']))->assertSessionHasErrors('effective_on');
        $this->assertSame(0, $p->currentAssignments()->count());
    }
    public function test_hire_date_edits_cannot_invalidate_existing_assignment_history(): void
    {
        [$admin, $p, $data] = $this->profile(); app(ManageStaff::class)->assignment($admin, $p, $this->assignmentData($p, $this->reference('grade')));
        $this->signIn($admin)->put('/staff/'.$p->id, [...$data, 'revision' => 2, 'hired_on' => '2026-10-21'])->assertSessionHasErrors('hired_on');
        $this->assertSame('2020-09-01', $p->fresh()->hired_on->format('Y-m-d'));
    }
    public function test_creation_assignment_failure_rolls_back_profile_specialties_and_audit(): void
    {
        $admin = $this->person(8); $this->signIn($admin)->post('/staff', $this->data(['specialty_ids' => [$this->reference('specialty')->id], 'grade_id' => $this->reference('function')->id, 'grade_since' => '2021-01-01']))->assertSessionHasErrors('reference_id');
        foreach (['staff_profiles', 'staff_specialties', 'staff_assignments', 'staff_audits'] as $table) { $this->assertDatabaseCount($table, 0); }
    }
    public function test_assignment_write_failure_rolls_back_the_previous_active_period(): void
    {
        [$admin, $p] = $this->profile(); app(ManageStaff::class)->assignment($admin, $p, $this->assignmentData($p, $this->reference('grade'))); $previous = $p->currentAssignments()->firstOrFail();
        StaffAssignment::creating(fn () => throw new RuntimeException('Simulated assignment failure'));
        try { app(ManageStaff::class)->assignment($admin, $p, $this->assignmentData($p, $this->reference('grade', 2))); $this->fail('Expected failure.'); }
        catch (RuntimeException $e) { $this->assertSame('Simulated assignment failure', $e->getMessage()); }
        finally { StaffAssignment::flushEventListeners(); }
        $this->assertNull($previous->fresh()->ended_on); $this->assertSame(1, $p->currentAssignments()->count()); $this->assertSame(2, $p->fresh()->revision);
    }
    public function test_linking_requires_user_management_and_compatible_active_account(): void
    {
        [$admin, $p] = $this->profile(); $student = $this->person(1); $administrative = $this->person(2); $inactive = $this->person(5, ['is_active' => false]); $teacher = $this->person(5); $this->signIn($admin);
        foreach ([$student, $administrative, $inactive] as $u) { $this->patch('/staff/'.$p->id.'/account', ['revision' => 1, 'account_email' => $u->email])->assertSessionHasErrors('account_email'); }
        UserGroup::findOrFail(8)->permissions()->detach('users.manage'); $this->signIn($admin)->patch('/staff/'.$p->id.'/account', ['revision' => 1, 'account_email' => $teacher->email])->assertForbidden();
        $this->assertNull($p->fresh()->user_id);
    }
    public function test_personal_record_requires_explicit_link_and_never_matches_email_implicitly(): void
    {
        $teacher = $this->person(5); [$admin, $p] = $this->profile(['email' => $teacher->email]);
        $this->signIn($teacher)->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->where('profile', null));
        $this->attach($admin, $p, $teacher);
        $this->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->where('profile.id', $p->id)->where('personal', true)->where('canManage', false)->where('account', null));
        $this->get('/staff/'.$p->id)->assertForbidden(); $this->put('/staff/'.$p->id, $this->data())->assertForbidden();
        $this->signIn($this->person(5))->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->where('profile', null));
    }
    public function test_personnel_accounts_link_only_to_administrative_profiles(): void
    {
        [$admin, $p] = $this->profile(['kind' => 'administrative', 'position' => 'Secrétariat']); $user = $this->person(4); $this->attach($admin, $p, $user);
        $this->signIn($user)->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->where('profile.kind', 'administrative')->where('profile.position', 'Secrétariat'));
        $this->assertSame([4], $user->groups()->pluck('user_groups.id')->all());
    }
    public function test_account_uniqueness_is_retained_for_archived_profiles_and_unlinking_revokes_personal_access(): void
    {
        [$admin, $p] = $this->profile(); $teacher = $this->person(5); $this->attach($admin, $p, $teacher); $other = app(ManageStaff::class)->save($admin, $this->data());
        app(ManageStaff::class)->archive($admin, $p, ['revision' => 2, 'is_archived' => true, 'reason' => 'Fiche archivée']);
        $this->signIn($admin)->patch('/staff/'.$other->id.'/account', ['revision' => 1, 'account_email' => $teacher->email])->assertSessionHasErrors('account_email');
        $this->patch('/staff/'.$p->id.'/account', ['revision' => 3, 'account_email' => null])->assertSessionHasNoErrors();
        $this->signIn($teacher)->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->where('profile', null));
    }
    public function test_stale_links_type_changes_and_permission_revocation_remove_personal_access(): void
    {
        [$admin, $p] = $this->profile(); $teacher = $this->person(5); $this->attach($admin, $p, $teacher);
        $this->signIn($admin)->patch('/staff/'.$p->id.'/account', ['revision' => 1, 'account_email' => null])->assertSessionHasErrors('revision');
        $teacher->types()->sync([4]); $teacher->groups()->sync([2]);
        $this->signIn($teacher)->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->where('profile', null));
        UserGroup::findOrFail(2)->permissions()->detach('hr.self'); $this->signIn($teacher)->get('/my-staff-record')->assertForbidden();
    }
    public function test_group_changes_and_function_labels_do_not_implicitly_grant_commission_membership(): void
    {
        [$admin, $p] = $this->profile(); $teacher = $this->person(5); $this->attach($admin, $p, $teacher);
        app(ManageStaff::class)->assignment($admin, $p, $this->assignmentData($p, $this->reference('function')));
        $this->assertFalse($teacher->fresh()->can('reports.vote')); $this->assertCount(0, app(ReportAccess::class)->roster());
        $this->signIn($admin)->get('/staff/'.$p->id)->assertInertia(fn (Assert $v) => $v->where('account.id', $teacher->id)->where('account.roles.0.id', 5));
        $this->put('/settings/groups/5', ['permissions' => ['hr.manage', 'hr.view']])->assertSessionHasErrors('permissions');
    }
    public function test_permissions_are_rechecked_inside_hr_writes_after_revocation(): void
    {
        [$admin, $p, $data] = $this->profile(); UserGroup::findOrFail(8)->permissions()->detach('hr.manage');
        try { app(ManageStaff::class)->save($admin, [...$data, 'revision' => 1], $p); $this->fail('Revoked write accepted.'); } catch (AuthorizationException) {}
        $this->assertSame(1, $p->fresh()->revision);
    }
    public function test_photo_is_private_reencoded_and_readable_only_through_authorized_profile_routes(): void
    {
        [$admin, $p] = $this->profile(); $teacher = $this->person(5); $this->attach($admin, $p, $teacher);
        $file = UploadedFile::fake()->image('portrait.png', 40, 40); file_put_contents($file->getRealPath(), 'TRAILING_PRIVATE_METADATA', FILE_APPEND);
        $this->signIn($admin)->post('/staff/'.$p->id.'/photo', [...$this->photoData($p), 'photo' => $file])->assertSessionHasNoErrors(); $photo = StaffPhoto::firstOrFail();
        $this->get('/staff/'.$p->id.'/photo')->assertOk()->assertHeader('Content-Type', 'image/png')->assertHeader('X-Content-Type-Options', 'nosniff')->assertDontSee('TRAILING_PRIVATE_METADATA');
        $this->signIn($teacher)->get('/my-staff-record/photo')->assertOk(); $this->get('/staff/'.$p->id.'/photo')->assertForbidden();
        $this->signIn($this->person(5))->get('/my-staff-record/photo')->assertNotFound(); $this->get('/storage/staff/'.$photo->storage_path)->assertNotFound();
        $this->assertStringNotContainsString('TRAILING_PRIVATE_METADATA', Storage::disk('staff')->get($photo->storage_path));
    }
    public function test_photos_reject_spoofed_formats_large_files_and_excessive_dimensions(): void
    {
        [$admin, $p] = $this->profile(); $this->signIn($admin);
        foreach ([UploadedFile::fake()->createWithContent('bad.png', '<svg><script>alert(1)</script></svg>'), UploadedFile::fake()->create('large.png', 5121, 'image/png'), UploadedFile::fake()->image('wide.png', 2049, 10)] as $photo) {
            $this->post('/staff/'.$p->id.'/photo', [...$this->photoData($p), 'photo' => $photo])->assertSessionHasErrors('photo');
        }
        $this->assertDatabaseCount('staff_photos', 0); $this->assertSame([], Storage::disk('staff')->allFiles());
    }
    public function test_photo_retries_do_not_duplicate_and_replacement_preserves_old_file_without_serving_it(): void
    {
        [$admin, $p] = $this->profile(); $file = UploadedFile::fake()->image('portrait.png', 40, 40); $data = $this->photoData($p); $this->signIn($admin);
        $this->post('/staff/'.$p->id.'/photo', [...$data, 'photo' => $file])->assertSessionHasNoErrors();
        $this->post('/staff/'.$p->id.'/photo', [...$data, 'photo' => $file])->assertSessionHasNoErrors(); $old = StaffPhoto::firstOrFail();
        $this->post('/staff/'.$p->id.'/photo', [...$data, 'photo' => UploadedFile::fake()->image('other.png', 50, 50)])->assertSessionHasErrors('operation_key');
        $this->post('/staff/'.$p->id.'/photo', [...$this->photoData($p), 'photo' => UploadedFile::fake()->image('new.jpg', 60, 60)])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('staff_photos', 2); $this->assertNotNull($old->fresh()->withdrawn_at); Storage::disk('staff')->assertExists($old->storage_path);
        $this->get('/staff/'.$p->id.'/photo')->assertHeader('Content-Type', 'image/jpeg');
    }
    public function test_photo_removal_revocation_and_integrity_failures_block_downloads(): void
    {
        [$admin, $p] = $this->profile(); $teacher = $this->person(5); $this->attach($admin, $p, $teacher);
        $photo = app(ManageStaffPhotos::class)->upload($admin, $p, $this->photoData($p), UploadedFile::fake()->image('portrait.png', 40, 40));
        Storage::disk('staff')->put($photo->storage_path, str_repeat('x', $photo->size)); $this->signIn($teacher)->get('/my-staff-record/photo')->assertNotFound();
        app(ManageStaffPhotos::class)->remove($admin, $p, $p->fresh()->revision); $this->get('/my-staff-record/photo')->assertNotFound();
        $this->assertNotNull($photo->fresh()->withdrawn_at); Storage::disk('staff')->assertExists($photo->storage_path);
    }
    public function test_photo_metadata_failure_rolls_back_and_cleans_only_the_new_file(): void
    {
        [$admin, $p] = $this->profile(); $old = app(ManageStaffPhotos::class)->upload($admin, $p, $this->photoData($p), UploadedFile::fake()->image('old.png', 40, 40));
        StaffPhoto::creating(fn () => throw new RuntimeException('Simulated photo failure'));
        try { app(ManageStaffPhotos::class)->upload($admin, $p, $this->photoData($p), UploadedFile::fake()->image('new.png', 50, 50)); $this->fail('Expected failure.'); }
        catch (RuntimeException $e) { $this->assertSame('Simulated photo failure', $e->getMessage()); }
        finally { StaffPhoto::flushEventListeners(); }
        $this->assertNull($old->fresh()->withdrawn_at); $this->assertDatabaseCount('staff_photos', 1); $this->assertCount(1, Storage::disk('staff')->allFiles()); $this->assertSame(2, $p->fresh()->revision);
    }
    public function test_photo_forms_are_bound_to_profile_revision_and_archived_profiles_cannot_receive_new_photos(): void
    {
        [$admin, $p] = $this->profile(); app(ManageStaff::class)->archive($admin, $p, ['revision' => 1, 'is_archived' => true, 'reason' => 'Archivage de test']); $this->signIn($admin);
        $this->post('/staff/'.$p->id.'/photo', [...$this->photoData($p, ['revision' => 1]), 'photo' => UploadedFile::fake()->image('p.png')])->assertSessionHasErrors('revision');
        $this->post('/staff/'.$p->id.'/photo', [...$this->photoData($p), 'photo' => UploadedFile::fake()->image('p.png')])->assertSessionHasErrors('staff');
        $this->assertDatabaseCount('staff_photos', 0);
    }
    public function test_filters_and_pagination_count_each_teacher_once_despite_multiple_specialties(): void
    {
        $admin = $this->person(8); $s = $this->reference('specialty'); $other = $this->reference('specialty', 2);
        for ($i = 0; $i < 21; $i++) { app(ManageStaff::class)->save($admin, $this->data(['specialty_ids' => [$s->id, $other->id], 'last_name' => 'Recherche'])); }
        app(ManageStaff::class)->save($admin, $this->data(['kind' => 'administrative']));
        $this->signIn($admin)->get('/staff?search=Recherche&specialty_id='.$s->id)->assertInertia(fn (Assert $v) => $v->where('profiles.total', 21)->has('profiles.data', 20)->where('statistics.total', 21));
        $this->get('/staff?page=2')->assertInertia(fn (Assert $v) => $v->has('profiles.data', 1));
        $this->get('/staff?account=linked')->assertInertia(fn (Assert $v) => $v->where('profiles.total', 0));
        $this->get('/dashboard')->assertInertia(fn (Assert $v) => $v->where('dashboard.cards.teachers.value', 21));
        $this->signIn($this->person(9))->get('/dashboard')->assertInertia(fn (Assert $v) => $v->missing('dashboard.cards.teachers'));
    }
    public function test_staff_planning_only_contains_meetings_the_viewer_can_already_read(): void
    {
        [$admin, $p] = $this->profile(); $member = $this->person(9); $owner = $this->person(9); $this->attach($admin, $p, $member);
        $visible = app(ManageMeetings::class)->save($owner, $this->meetingData([$member]));
        $other = $this->person(9); app(ManageMeetings::class)->save($other, $this->meetingData([$other]));
        $this->signIn($member)->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->has('meetings', 1)->where('meetings.0.id', $visible->id));
        UserGroup::findOrFail(9)->permissions()->detach('meetings.view'); $this->signIn($member)->get('/my-staff-record')->assertInertia(fn (Assert $v) => $v->has('meetings', 0));
    }
    public function test_payloads_and_audits_do_not_expose_passwords_storage_paths_or_private_account_fields(): void
    {
        [$admin, $p] = $this->profile(); $teacher = $this->person(5); $this->attach($admin, $p, $teacher); $this->signIn($admin);
        $this->get('/staff')->assertInertia(fn (Assert $v) => $v->missing('profiles.data.0.date_of_birth')->missing('profiles.data.0.user_id')->missing('profiles.data.0.operation_key'));
        $this->get('/staff/'.$p->id)->assertInertia(fn (Assert $v) => $v->missing('profile.request_hash')->missing('profile.user_id')->missing('profile.photo_path')->missing('account.password')->missing('account.auth_version')->missing('profile.activity.0.metadata'));
        $this->assertStringNotContainsString($p->email, DB::table('staff_audits')->pluck('metadata')->implode(' '));
    }
    public function test_csrf_is_required_for_identity_assignments_catalog_links_archive_and_photos(): void
    {
        [$admin, $p, $data] = $this->profile(); $this->app->instance('env', 'local'); $this->signIn($admin);
        $this->post('/staff', $data)->assertStatus(419); $this->put('/staff/'.$p->id, [...$data, 'revision' => 1])->assertStatus(419);
        $this->post('/staff/'.$p->id.'/assignments', $this->assignmentData($p, $this->reference('grade')))->assertStatus(419);
        $this->patch('/staff/'.$p->id.'/account', ['revision' => 1, 'account_email' => null])->assertStatus(419);
        $this->patch('/staff/'.$p->id.'/archive', ['revision' => 1, 'is_archived' => true, 'reason' => 'Test sans jeton'])->assertStatus(419);
        $this->post('/staff/catalogs', ['kind' => 'grade', 'name' => 'Test', 'is_active' => true, 'revision' => 0])->assertStatus(419);
        $this->post('/staff/'.$p->id.'/photo', [...$this->photoData($p), 'photo' => UploadedFile::fake()->image('test.png')])->assertStatus(419); $this->delete('/staff/'.$p->id.'/photo', ['revision' => 1])->assertStatus(419);
    }
}
