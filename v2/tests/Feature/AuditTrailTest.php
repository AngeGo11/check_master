<?php

namespace Tests\Feature;

use App\Actions\Access\ManageAccess;
use App\Actions\Academic\ManageGrades;
use App\Actions\Claims\ManageClaims;
use App\Models\{User, UserGroup};
use App\Services\{AuditTrail, RoleDashboard};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AcademicFixtures;
use Tests\TestCase;

class AuditTrailTest extends TestCase
{
    use RefreshDatabase, AcademicFixtures;

    protected function setUp(): void
    {
        parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 22)->setTime(9, 0));
        Storage::fake('reports'); Storage::fake('claims');
    }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }

    private function event(string $source = 'access', ?User $actor = null, string $action = 'user.created', array $metadata = [], array $columns = []): int
    {
        return DB::table(AuditTrail::SOURCES[$source]['table'])->insertGetId([
            'actor_user_id' => $actor?->id, 'action' => $action, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now(), ...$columns,
        ]);
    }
    private function csv(array $filters = []): array
    {
        $response = $this->get('/audit/export?'.http_build_query($filters))->assertOk()->assertDownload();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $body = $response->streamedContent(); $this->assertStringStartsWith("\xEF\xBB\xBF", $body);
        $stream = fopen('php://temp', 'w+'); fwrite($stream, substr($body, 3)); rewind($stream); $rows = [];
        while (($row = fgetcsv($stream, null, ';', '"', '')) !== false) { $rows[] = $row; } fclose($stream); return $rows;
    }

    public function test_only_administrators_can_read_detail_and_export_even_via_direct_urls(): void
    {
        $id = $this->event();
        foreach (['/audit', '/audit/access/'.$id, '/audit/export'] as $url) { $this->get($url)->assertRedirect('/login'); }
        foreach ([1, 2, 3, 4, 5, 6, 7, 9] as $group) {
            $this->signIn($this->person($group));
            foreach (['/audit', '/audit/access/'.$id, '/audit/export'] as $url) { $this->get($url)->assertForbidden(); }
        }
        $this->signIn($this->person(8))->get('/audit')->assertOk(); $this->get('/audit/access/'.$id)->assertOk(); $this->csv();
        $this->assertDatabaseCount('permissions', 52); $this->assertDatabaseCount('group_permission', 100);
    }

    public function test_group_and_type_compatibility_cannot_be_bypassed_by_bad_direct_grants(): void
    {
        $user = $this->person(1); $user->groups()->attach(8);
        $this->signIn($user)->get('/audit')->assertForbidden();
        UserGroup::findOrFail(1)->permissions()->attach(['audit.view', 'audit.export']);
        $this->signIn($user)->get('/audit')->assertForbidden();
        $this->assertNotContains('audit.view', $user->fresh()->permissionCodes());
        $admin = $this->person(8);
        $this->signIn($admin)->put('/settings/groups/3', ['permissions' => ['audit.view', 'audit.export']])->assertSessionHasErrors('permissions');
        $this->put('/settings/groups/8', ['permissions' => ['audit.export']])->assertSessionHasErrors('permissions');
    }

    public function test_export_and_view_permissions_are_rechecked_independently(): void
    {
        $u = $this->person(8); $id = $this->event(actor: $u); $this->signIn($u)->get('/audit')->assertInertia(fn (Assert $p) => $p->where('canExport', true));
        UserGroup::findOrFail(8)->permissions()->detach('audit.export');
        $this->get('/audit')->assertInertia(fn (Assert $p) => $p->where('canExport', false)); $this->get('/audit/export')->assertForbidden();
        UserGroup::findOrFail(8)->permissions()->attach('audit.export'); UserGroup::findOrFail(8)->permissions()->detach('audit.view');
        foreach (['/audit', '/audit/access/'.$id, '/audit/export'] as $url) { $this->get($url)->assertForbidden(); }
    }

    public function test_inactive_and_temporary_password_accounts_cannot_read_the_journal(): void
    {
        $this->signIn($this->person(8, ['is_active' => false]))->get('/audit')->assertRedirect('/login');
        $this->signIn($this->person(8, ['must_change_password' => true]))->get('/audit')->assertRedirect('/password/change');
    }

    public function test_dashboard_entry_is_limited_to_the_administration_space_and_permission(): void
    {
        $u = $this->person(8); $u->types()->attach(1); $u->groups()->attach(1);
        $this->assertSame('/audit', app(RoleDashboard::class)->build($u, 'platform-administrator')['actions']['audit']['href']);
        $this->assertArrayNotHasKey('audit', app(RoleDashboard::class)->build($u, 'student')['actions']);
        UserGroup::findOrFail(8)->permissions()->sync(['audit.view']);
        $this->signIn($u)->get('/settings')->assertOk();
        UserGroup::findOrFail(8)->permissions()->detach('audit.view');
        $this->assertArrayNotHasKey('audit', app(RoleDashboard::class)->build($u)['actions']);
    }

    public function test_all_nine_sources_are_present_without_copying_or_overwriting_events(): void
    {
        $admin = $this->person(8); $this->person(9);
        [$report] = $this->transmittedReport(); [$student, $e] = $this->enrollment();
        $this->event(actor: $admin); $this->event('students', $admin, 'student.created', columns: ['student_id' => $e->student_id]);
        $this->event('documents', $admin, 'document.created'); $this->event('staff', $admin, 'staff.created'); $this->event('academic', $admin, 'program.created'); $this->messageFixture();
        $claim = app(ManageClaims::class)->create($student, ['enrollment_id' => $e->id, 'subject' => 'Objet confidentiel', 'body' => 'Réclamation privée test', 'grade_publication_id' => null, 'subject_keys' => [], 'operation_key' => (string) Str::uuid()], null);
        $before = collect(AuditTrail::SOURCES)->mapWithKeys(fn ($d, $s) => [$s => DB::table($d['table'])->count()])->all();
        $this->signIn($admin)->get('/audit')->assertInertia(fn (Assert $p) => $p->component('audit/index')->where('statistics.modules', 9)->where('statistics.total', array_sum($before))->where('events.total', array_sum($before))->has('modules', 9));
        $rows = app(AuditTrail::class)->query()->get(); $this->assertCount(9, $rows->pluck('source')->unique());
        foreach ($rows as $row) {
            $this->get('/audit/'.$row->source.'/'.$row->event_id)->assertOk()->assertInertia(fn (Assert $p) => $p->component('audit/show')->where('event.key', $row->source.':'.$row->event_id)->missing('event.metadata')->missing('event.body'));
        }
        $this->get('/audit/claims/'.$claim->events()->first()->id)->assertInertia(fn (Assert $p) => $p->where('event.references.0', 'Réclamation #'.$claim->id)->where('event.details.0.value', 'En attente'));
        $this->csv();
        foreach ($before as $source => $count) { $this->assertDatabaseCount(AuditTrail::SOURCES[$source]['table'], $count); }
    }

    public function test_empty_and_filtered_out_journals_show_zero_counts_and_export_only_headers(): void
    {
        $this->signIn($this->person(8))->get('/audit')->assertInertia(fn (Assert $p) => $p->where('statistics.total', 0)->where('statistics.actors', 0)->where('statistics.modules', 0)->where('statistics.last_at', null)->has('events.data', 0)->has('actions', 0));
        $this->assertCount(1, $this->csv()); $this->event();
        $this->get('/audit?module=claims')->assertInertia(fn (Assert $p) => $p->where('events.total', 0));
    }

    public function test_combined_filters_and_stats_use_inclusive_utc_calendar_days(): void
    {
        $u = $this->person(8, ['name' => 'Alice Audit']); $other = $this->person(8, ['name' => 'Autre Compte']);
        foreach (['2026-10-21 23:59:59', '2026-10-22 00:00:00', '2026-10-22 23:59:59', '2026-10-23 00:00:00'] as $date) { $this->event(actor: $u, columns: ['created_at' => $date]); }
        $this->event(actor: $other); $this->event('students', $u, 'student.created'); $this->event(actor: $u, action: 'user.updated');
        $filters = ['module' => 'access', 'action' => 'user.created', 'actor' => '#'.$u->id, 'from' => '2026-10-22', 'to' => '2026-10-22'];
        $this->signIn($u)->get('/audit?'.http_build_query($filters))->assertInertia(fn (Assert $p) => $p->where('events.total', 2)->where('statistics.total', 2)->where('statistics.actors', 1)->where('statistics.modules', 1)->where('statistics.last_at', '2026-10-22T23:59:59+00:00'));
        $rows = $this->csv($filters); $this->assertCount(3, $rows); $this->assertSame('2026-10-22T23:59:59+00:00', $rows[1][1]);
        $this->get('/audit?actor=Alice')->assertInertia(fn (Assert $p) => $p->where('events.total', 6));
    }

    public function test_actor_search_treats_sql_wildcards_and_quotes_as_literal_text(): void
    {
        $a = $this->person(8, ['name' => 'A%_! O’Neil']); $b = $this->person(8, ['name' => 'Abc O’Neil']); $this->event(actor: $a); $this->event(actor: $b);
        $this->signIn($a)->get('/audit?'.http_build_query(['actor' => '%_!']))->assertInertia(fn (Assert $p) => $p->where('events.total', 1)->where('events.data.0.actor_id', $a->id));
        $this->get('/audit?'.http_build_query(['actor' => "' OR 1=1 --"]))->assertInertia(fn (Assert $p) => $p->where('events.total', 0));
    }

    public function test_unattributed_console_events_and_current_account_names_are_explicit(): void
    {
        $u = $this->person(8, ['name' => 'Nom avant']); $id = $this->event(actor: $u); app(ManageAccess::class)->grantAdministrator($u);
        $u->forceFill(['name' => 'Nom actuel'])->save(); $this->event();
        $this->signIn($u)->get('/audit?unattributed=1')->assertInertia(fn (Assert $p) => $p->where('events.total', 2)->where('statistics.actors', 0)->where('events.data.0.actor', 'Compte indisponible')->where('events.data.1.actor', 'Console système'));
        $this->get('/audit/access/'.$id)->assertInertia(fn (Assert $p) => $p->where('event.actor', 'Nom actuel'));
        $this->get('/audit?'.http_build_query(['unattributed' => 1, 'actor' => '#'.$u->id]))->assertInertia(fn (Assert $p) => $p->where('events.total', 0));
    }

    public function test_stable_order_and_pagination_keep_composite_keys_unique_when_timestamps_tie(): void
    {
        $u = $this->person(8); for ($i = 0; $i < 30; $i++) { $this->event(actor: $u); }
        $this->event('students', $u, 'student.created');
        $this->signIn($u)->get('/audit')->assertInertia(fn (Assert $p) => $p->has('events.data', 25)->where('events.data.0.key', 'access:30')->where('events.data.24.key', 'access:6')->where('events.total', 31));
        $this->get('/audit?page=2')->assertInertia(fn (Assert $p) => $p->has('events.data', 6)->where('events.data.0.key', 'access:5')->where('events.data.5.key', 'students:1'));
        $this->get('/audit/access/5?module=access&page=2')->assertInertia(fn (Assert $p) => $p->where('filters.page', 2)->where('filters.module', 'access'));
        $rows = $this->csv(['page' => 2]); $this->assertCount(32, $rows); $this->assertCount(31, array_unique(array_column(array_slice($rows, 1), 0)));
    }

    public function test_invalid_filters_and_unknown_events_cannot_select_arbitrary_tables(): void
    {
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
        $this->signIn($this->person(8));
        foreach ([['module' => 'users'], ['action' => "' or 1=1"], ['actor' => ['bad']], ['from' => '2026-02-30'], ['to' => 'nonsense'], ['from' => '2026-10-23', 'to' => '2026-10-22'], ['page' => 0], ['unattributed' => 2]] as $filters) {
            $this->get('/audit?'.http_build_query($filters))->assertSessionHasErrors();
            $this->get('/audit/export?'.http_build_query($filters))->assertSessionHasErrors();
        }
        foreach (['/audit/users/1', '/audit/access/99999', '/audit/access/abc'] as $url) { $this->get($url)->assertNotFound(); }
    }

    public function test_unknown_historical_actions_remain_readable_and_filterable_without_raw_metadata(): void
    {
        $u = $this->person(8); $id = $this->event(actor: $u, action: 'legacy.custom', metadata: ['password' => 'DO-NOT-EXPOSE']);
        $this->signIn($u)->get('/audit?action=legacy.custom')->assertInertia(fn (Assert $p) => $p->where('events.total', 1)->where('events.data.0.label', 'Action enregistrée')->where('actions.0.value', 'legacy.custom'));
        $this->get('/audit/access/'.$id)->assertInertia(fn (Assert $p) => $p->has('event.details', 0)->missing('event.metadata'));
    }

    public function test_private_metadata_is_not_serialized_in_list_detail_or_csv(): void
    {
        $u = $this->person(8); $id = $this->event('collaboration', $u, 'message.sent', ['body' => 'SECRET-CONTENT', 'subject' => 'SECRET-SUBJECT',
            'recipient_ids' => [123], 'recipient_count' => 1, 'password' => 'SECRET-PASSWORD', 'storage_path' => 'SECRET-PATH', 'operation_key' => 'SECRET-TOKEN', 'reason' => 'SECRET-REASON']);
        $this->signIn($u); $response = $this->get('/audit/collaboration/'.$id)->assertInertia(fn (Assert $p) => $p->has('event.details', 1)->where('event.details.0.value', '1'));
        $this->assertStringNotContainsString('SECRET-', $response->getContent());
        $this->assertStringNotContainsString('SECRET-', $this->get('/audit')->getContent());
        $this->assertStringNotContainsString('SECRET-', json_encode($this->csv()));
    }

    public function test_real_grade_changes_do_not_leak_unpublished_marks_or_before_after_values(): void
    {
        [$school, , $sheet] = $this->sheet(); app(ManageGrades::class)->save($school, $sheet, ['revision' => 1, 'marks' => $this->marks($sheet, '17.25')]);
        $id = DB::table('academic_audits')->where('action', 'grades.saved')->value('id');
        $metadata = json_decode(DB::table('academic_audits')->where('id', $id)->value('metadata'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(1725, $metadata['after']['ecue:A1']); // Marks are stored in hundredths.
        $this->signIn($this->person(8))->get('/audit/academic/'.$id)->assertInertia(fn (Assert $p) => $p->has('event.details', 1)->where('event.details.0.label', 'Feuille de notes')->missing('event.before')->missing('event.after')->missing('event.marks'));
    }

    public function test_access_metadata_shows_only_known_fields_roles_and_permissions(): void
    {
        $u = $this->person(8); $id = $this->event(actor: $u, action: 'user.updated', metadata: [
            'before' => ['is_active' => true, 'type_ids' => [3], 'group_ids' => [8], 'password' => 'SECRET'],
            'after' => ['is_active' => false, 'type_ids' => [1], 'group_ids' => [1]], 'identity_fields_changed' => ['email', 'SECRET'],
        ]);
        $this->signIn($u)->get('/audit/access/'.$id)->assertInertia(fn (Assert $p) => $p->where('event.details.0.value', 'Adresse e-mail')->has('event.details', 7));
        $id = $this->event(actor: $u, action: 'group.permissions.updated', metadata: ['before' => ['users.view', 'SECRET'], 'after' => ['audit.view', 'audit.export']]);
        $this->get('/audit/access/'.$id)->assertInertia(fn (Assert $p) => $p->where('event.details.0.value', 'users.view')->where('event.details.1.value', 'audit.view, audit.export'));
    }

    public function test_csv_escapes_quotes_semicolons_newlines_and_neutralizes_spreadsheet_formulas(): void
    {
        $u = $this->person(8, ['name' => " =HYPERLINK(\"x\");\n+SUM(1)"]); $this->event(actor: $u); $this->signIn($u);
        $rows = $this->csv(); $this->assertCount(2, $rows); $this->assertCount(8, $rows[1]); $this->assertSame("'".$u->name, $rows[1][5]);
        foreach (['=1', '+1', '-1', '@A1', "\t1", "\n1", '  =1', "\u{FEFF}=1"] as $value) { $this->assertSame("'".$value, AuditTrail::csvCell($value)); }
        $this->assertSame('Jean Dupont', AuditTrail::csvCell('Jean Dupont'));
    }

    public function test_csv_refuses_over_limit_selections_without_truncating_them(): void
    {
        $u = $this->person(8);
        $row = ['actor_user_id' => $u->id, 'action' => 'user.created', 'metadata' => '{}', 'created_at' => now()];
        foreach (array_chunk(array_fill(0, AuditTrail::EXPORT_LIMIT, $row), 250) as $chunk) { DB::table('access_audits')->insert($chunk); }
        $this->signIn($u); $this->assertCount(AuditTrail::EXPORT_LIMIT + 1, $this->csv());
        $this->event(actor: $u, action: 'user.updated');
        $this->get('/audit/export')->assertRedirect('/audit')->assertSessionHasErrors('export');
        $this->assertCount(2, $this->csv(['action' => 'user.updated']));
    }

    public function test_journal_endpoints_have_no_update_delete_or_purge_operations(): void
    {
        $u = $this->person(8); $id = $this->event(actor: $u); $before = DB::table('access_audits')->find($id); $this->signIn($u);
        foreach (['post', 'put', 'patch', 'delete'] as $method) { $this->{$method}('/audit/access/'.$id, ['action' => 'changed'])->assertStatus(405); }
        $this->assertEquals($before, DB::table('access_audits')->find($id));
        $response = $this->get('/audit')->assertOk(); $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $response = $this->get('/audit/access/'.$id)->assertOk(); $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_export_rate_limit_is_enforced(): void
    {
        $this->signIn($this->person(8)); for ($i = 0; $i < 6; $i++) { $this->get('/audit/export')->assertOk(); }
        $this->get('/audit/export')->assertStatus(429);
    }

    public function test_migration_round_trips_preserve_existing_history_and_reinstate_permissions(): void
    {
        $u = $this->person(8); $id = $this->event(actor: $u); $before = DB::table('access_audits')->find($id);
        $migration = require database_path('migrations/2026_10_25_000000_add_audit_trail_access.php');
        for ($i = 0; $i < 2; $i++) {
            $migration->down();
            $this->assertDatabaseMissing('permissions', ['code' => 'audit.view']);
            $this->assertEquals($before, DB::table('access_audits')->find($id));
            $migration->up();
            $this->assertDatabaseHas('group_permission', ['user_group_id' => 8, 'permission_code' => 'audit.export']);
            $this->assertDatabaseCount('permissions', 52); $this->assertDatabaseCount('group_permission', 100);
        }
        $this->signIn($u)->get('/audit')->assertInertia(fn (Assert $p) => $p->where('events.total', 1));
    }
}
