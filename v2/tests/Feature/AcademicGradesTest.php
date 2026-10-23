<?php
namespace Tests\Feature;
use App\Actions\Academic\{ManagePrograms, ManageGrades};
use App\Models\{AcademicSemester, AcademicYear, GradePublication, GradeSheet, TeachingProgram, UserGroup};
use App\Services\GradeCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Storage};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\Support\AcademicFixtures;
use Tests\TestCase;
class AcademicGradesTest extends TestCase
{
    use RefreshDatabase, AcademicFixtures;
    protected function setUp(): void { parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 22)->setTime(9, 0)); Storage::fake('reports'); }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    public function test_only_the_nine_source_semesters_and_anonymous_subject_catalog_are_imported(): void
    {
        $source = json_decode(file_get_contents(database_path('reference/academic-catalog.json')), true);
        $this->assertSame('921bf93160eea570c2109d0852d1c5063883c4dbf70c0a8cb3b0ae4276c66454', $source['source_sha256']);
        $this->assertDatabaseCount('academic_semesters', 9); $units = collect($source['semesters'])->pluck('units')->flatten(1);
        $this->assertCount(75, $units); $this->assertCount(44, $units->pluck('components')->flatten(1));
        $this->assertSame([9], AcademicSemester::where('study_level_id', 5)->pluck('id')->all());
        foreach (['users', 'teaching_programs', 'grade_sheets', 'grade_publications', 'defenses'] as $table) { $this->assertDatabaseCount($table, 0); }
        $this->assertSame([3], DB::table('group_permission')->where('permission_code', 'grades.manage')->pluck('user_group_id')->all());
        $this->assertSame([8], DB::table('group_permission')->where('permission_code', 'grades.catalogs.manage')->pluck('user_group_id')->all());
    }
    public function test_grade_access_distinguishes_student_schooling_administrator_and_other_groups(): void
    {
        $this->get('/grades')->assertRedirect('/login');
        foreach ([1, 2, 4, 5, 6, 7, 9] as $group) { $this->signIn($this->person($group))->get('/grades')->assertForbidden(); $this->get('/grades/programs')->assertForbidden(); }
        $this->signIn($this->person(3))->get('/grades')->assertOk(); $this->get('/grades/programs')->assertOk(); $this->get('/my-grades')->assertForbidden();
        $this->signIn($this->person(1))->get('/my-grades')->assertOk();
        $bad = $this->person(1); $bad->groups()->attach(8); $this->signIn($bad)->get('/grades')->assertForbidden();
    }
    public function test_program_creation_copies_sql_and_retries_without_creating_duplicate_versions(): void
    {
        [$admin, $p, $data] = $this->program(false); $this->assertSame(27, array_sum(array_column($p->units, 'credits')));
        $this->assertSame($p->id, app(ManagePrograms::class)->create($admin, $data)->id);
        $this->signIn($admin)->post('/grades/programs', [...$data, 'operation_key' => (string) Str::uuid()])->assertSessionHasErrors('program');
        $this->post('/grades/programs', [...$data, 'semester_id' => 2])->assertSessionHasErrors('operation_key');
        $this->assertDatabaseCount('teaching_programs', 1); $this->get('/grades/programs/'.$p->id)->assertInertia(fn (Assert $v) => $v->where('program.status', 'draft')->has('warnings'));
        $this->signIn($this->person(3))->post('/grades/programs', $data)->assertForbidden();
    }
    public function test_credit_mismatch_blocks_publication_and_other_warnings_require_explicit_acknowledgment(): void
    {
        [$admin, $p] = $this->program(false, 4); $this->signIn($admin)->post('/grades/programs/'.$p->id.'/publish', ['revision' => 1, 'reason' => 'Contrôle du programme', 'warnings_confirmed' => true])->assertSessionHasErrors('units');
        app(ManagePrograms::class)->save($admin, $p, ['revision' => 1, 'units' => $this->units()]);
        $this->post('/grades/programs/'.$p->id.'/publish', ['revision' => 2, 'reason' => 'Contrôle du programme', 'warnings_confirmed' => false])->assertSessionHasErrors('warnings_confirmed');
        $this->post('/grades/programs/'.$p->id.'/publish', ['revision' => 2, 'reason' => 'Total fictif approuvé', 'warnings_confirmed' => true])->assertSessionHasNoErrors(); $this->assertSame('published', $p->fresh()->status);
    }
    public function test_program_editor_rejects_duplicate_subjects_invalid_credits_and_stale_revisions(): void
    {
        [$admin, $p] = $this->program(false); $u = $this->units(); $this->signIn($admin);
        $duplicate = [$u[0], $u[0]]; $this->put('/grades/programs/'.$p->id, ['revision' => 1, 'units' => $duplicate])->assertSessionHasErrors('units');
        $u[0]['components'][1]['code'] = 'A1'; $this->put('/grades/programs/'.$p->id, ['revision' => 1, 'units' => $u])->assertSessionHasErrors('units');
        $u = $this->units(); $u[0]['credits'] = 0; $this->put('/grades/programs/'.$p->id, ['revision' => 1, 'units' => $u])->assertSessionHasErrors('units.0.credits');
        $this->put('/grades/programs/'.$p->id, ['revision' => 99, 'units' => $this->units()])->assertSessionHasErrors('revision');
        $this->assertSame(1, $p->fresh()->revision);
    }
    public function test_new_program_version_retires_the_previous_without_rebinding_existing_grade_sheets(): void
    {
        [$school, , $sheet, $p] = $this->sheet(); $admin = $this->person(8); $a = app(ManagePrograms::class);
        $new = $a->create($admin, ['academic_year_id' => $p->academic_year_id, 'semester_id' => 1, 'operation_key' => (string) Str::uuid()]);
        $this->assertSame($p->units, $new->units); $this->assertSame(2, $new->number);
        $units = $new->units; $units[1]['name'] = 'Libellé corrigé'; $a->save($admin, $new, ['revision' => 1, 'units' => $units]); $a->publish($admin, $new, ['revision' => 2, 'reason' => 'Libellé corrigé après vérification', 'warnings_confirmed' => true]);
        $this->assertSame('retired', $p->fresh()->status); $this->assertNull($p->fresh()->active_slot); $this->assertSame($p->id, $sheet->fresh()->teaching_program_id);
        [, $e] = $this->enrollment(); $next = app(ManageGrades::class)->create($school, ['enrollment_id' => $e->id, 'semester_id' => 1]); $this->assertSame($new->id, $next->teaching_program_id);
        $this->signIn($admin)->put('/grades/programs/'.$p->id, ['revision' => $p->fresh()->revision, 'units' => $units])->assertSessionHasErrors('program');
    }
    public function test_programs_are_isolated_by_year_and_closed_years_reject_changes(): void
    {
        [$admin, $p] = $this->program(false); $y = new AcademicYear; $y->forceFill(['name' => '2027-2028', 'starts_on' => '2027-09-01', 'ends_on' => '2028-08-31', 'status' => 'pending'])->save();
        $a = app(ManagePrograms::class); $new = $a->create($admin, ['academic_year_id' => $y->id, 'semester_id' => 1, 'operation_key' => (string) Str::uuid()]); $this->assertSame(1, $new->number);
        $y->forceFill(['status' => 'closed'])->save(); $this->signIn($admin)->put('/grades/programs/'.$new->id, ['revision' => 1, 'units' => $this->units()])->assertSessionHasErrors('program');
        $this->post('/grades/programs', ['academic_year_id' => $y->id, 'semester_id' => 2, 'operation_key' => (string) Str::uuid()])->assertSessionHasErrors('academic_year_id');
        $this->get('/grades/programs?academic_year_id='.$p->academic_year_id)->assertInertia(fn (Assert $v) => $v->where('programs.total', 1));
    }
    public function test_weighted_averages_use_each_ue_once_and_missing_is_different_from_zero(): void
    {
        $a = app(GradeCalculator::class); $marks = ['ecue:A1' => 1000, 'ecue:A2' => 1800, 'ue:B' => 800]; $r = $a->calculate($this->units(), $marks);
        $this->assertTrue($r['complete']); $this->assertEquals(16, $r['units'][0]['average']); $this->assertEquals(13.33, $r['average']); $this->assertEquals(['major' => 16, 'minor' => 8], $r['groups']); $this->assertSame(4, $r['credits_at_least_ten']);
        $marks['ue:B'] = null; $r = $a->calculate($this->units(), $marks); $this->assertFalse($r['complete']); $this->assertNull($r['average']); $this->assertNull($r['groups']['minor']);
        $marks['ue:B'] = 0; $r = $a->calculate($this->units(), $marks); $this->assertTrue($r['complete']); $this->assertEquals(10.67, $r['average']);
        $this->assertSame(1234, GradeCalculator::score('12,34')); $this->assertSame(0, GradeCalculator::score('0')); $this->assertNull(GradeCalculator::score('')); $this->assertSame(2000, GradeCalculator::score('20.00'));
    }
    public function test_rounding_a_display_to_ten_does_not_increase_earned_credits(): void
    {
        $units = [['code' => 'A', 'name' => 'Test', 'credits' => 3, 'hours' => 30, 'components' => [['code' => 'a', 'name' => 'A', 'credits' => 1, 'hours' => 10], ['code' => 'b', 'name' => 'B', 'credits' => 2, 'hours' => 20]]]];
        $r = app(GradeCalculator::class)->calculate($units, ['ecue:a' => 999, 'ecue:b' => 1000]); $this->assertEquals(10, $r['average']); $this->assertSame(0, $r['credits_at_least_ten']);
    }
    public function test_grade_sheet_requires_an_active_matching_program_and_is_unique_per_enrollment_semester(): void
    {
        [$school, , $s] = $this->sheet(); $this->signIn($school)->post('/grades', ['enrollment_id' => $s->enrollment_id, 'semester_id' => 1])->assertRedirect('/grades/'.$s->id); $this->assertDatabaseCount('grade_sheets', 1);
        $this->post('/grades', ['enrollment_id' => $s->enrollment_id, 'semester_id' => 2])->assertSessionHasErrors('semester_id');
        [, $e] = $this->enrollment(5); $this->post('/grades', ['enrollment_id' => $e->id, 'semester_id' => 1])->assertSessionHasErrors('semester_id');
        $this->signIn($this->person(8))->post('/grades', ['enrollment_id' => $e->id, 'semester_id' => 1])->assertForbidden();
    }
    public function test_grade_edits_reject_extra_keys_missing_keys_bad_scores_and_old_forms(): void
    {
        [$school, , $s] = $this->sheet(); $this->signIn($school); $marks = $this->marks($s);
        $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => [...$marks, 'ue:A' => '20']])->assertSessionHasErrors('marks');
        unset($marks['ue:B']); $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $marks])->assertSessionHasErrors('marks');
        foreach (['-1', '20.01', '21', '1e1', '12.345', 'NaN'] as $value) { $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $this->marks($s, $value)])->assertSessionHasErrors('marks.ecue:A1'); }
        $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $this->marks($s, '12,75')])->assertSessionHasNoErrors();
        $this->assertSame(1275, $s->fresh()->marks['ue:B']); $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $this->marks($s)])->assertSessionHasErrors('revision');
    }
    public function test_student_cannot_see_draft_marks_or_another_students_bulletin(): void
    {
        [$school, $owner, $s] = $this->sheet(); $this->signIn($owner)->get('/my-grades')->assertInertia(fn (Assert $v) => $v->has('enrollments.data.0.sheets', 0)->where('statistics', null));
        $this->get('/my-grades/'.$s->id)->assertNotFound(); $this->get('/grades/'.$s->id)->assertForbidden(); $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $this->marks($s)])->assertForbidden();
        $this->publishSheet($school, $s); $this->get('/my-grades/'.$s->id)->assertInertia(fn (Assert $v) => $v->where('marks', null)->where('canManage', false)->where('result.average', 12)->where('publication.decision', 'pending'));
        $this->signIn($this->person(1))->get('/my-grades/'.$s->id)->assertNotFound(); $this->get('/my-grades')->assertInertia(fn (Assert $v) => $v->where('enrollments.total', 0));
    }
    public function test_publication_requires_complete_marks_and_explicit_pedagogical_decision(): void
    {
        [$school, , $s] = $this->sheet(); $this->signIn($school)->post('/grades/'.$s->id.'/publish', ['revision' => 1, 'decision' => 'validated', 'reason' => 'Délibération fictive'])->assertSessionHasErrors('marks');
        app(ManageGrades::class)->save($school, $s, ['revision' => 1, 'marks' => $this->marks($s, '0')]);
        $this->post('/grades/'.$s->id.'/publish', ['revision' => 2, 'decision' => 'invented', 'reason' => 'Délibération fictive'])->assertSessionHasErrors('decision');
        $this->post('/grades/'.$s->id.'/publish', ['revision' => 2, 'decision' => 'adjourned', 'reason' => 'Délibération fictive'])->assertSessionHasNoErrors();
        $this->assertSame('adjourned', $s->latestPublication->decision); $this->assertEquals(0, $s->latestPublication->result['average']);
        $this->put('/grades/'.$s->id, ['revision' => 3, 'marks' => $this->marks($s)])->assertSessionHasErrors('sheet');
    }
    public function test_correction_preserves_published_history_and_hides_new_draft_from_student(): void
    {
        [$school, $owner, $s] = $this->sheet(); $this->publishSheet($school, $s); $old = $s->fresh()->latestPublication;
        $a = app(ManageGrades::class); $a->reopen($school, $s, ['revision' => 3, 'reason' => 'Erreur de saisie à corriger']); $a->save($school, $s, ['revision' => 4, 'marks' => $this->marks($s, '18')]);
        $this->signIn($owner)->get('/my-grades/'.$s->id)->assertInertia(fn (Assert $v) => $v->where('result.average', 12)->where('marks', null)->where('publication.id', $old->id));
        $this->get('/my-grades')->assertInertia(fn (Assert $v) => $v->where('enrollments.data.0.sheets.0.average', 12)->where('enrollments.data.0.sheets.0.status', 'published'));
        $a->publish($school, $s, ['revision' => 5, 'decision' => 'validated', 'reason' => 'Correction vérifiée et validée']);
        $this->get('/my-grades/'.$s->id)->assertInertia(fn (Assert $v) => $v->where('result.average', 18)->has('publications', 2));
        $this->get('/my-grades/'.$s->id.'?publication='.$old->id)->assertInertia(fn (Assert $v) => $v->where('result.average', 12));
        $this->get('/my-grades/'.$s->id.'?publication=9999')->assertNotFound();
        $this->assertEquals(12, $old->fresh()->result['average']);
    }
    public function test_cancelled_archived_and_closed_year_records_cannot_be_edited(): void
    {
        [$school, , $s] = $this->sheet(); $e = $s->enrollment; $this->signIn($school);
        $e->forceFill(['cancelled_at' => now()])->save(); $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $this->marks($s)])->assertSessionHasErrors('enrollment');
        $e->forceFill(['cancelled_at' => null])->save(); $e->student->forceFill(['is_archived' => true])->save(); $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $this->marks($s)])->assertSessionHasErrors('enrollment');
        $e->student->forceFill(['is_archived' => false])->save(); $e->academicYear->forceFill(['status' => 'closed', 'current_slot' => null])->save(); $this->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $this->marks($s)])->assertSessionHasErrors('enrollment');
        $this->assertSame(1, $s->fresh()->revision);
    }
    public function test_multirole_student_cannot_modify_own_marks_and_permissions_are_rechecked_on_write(): void
    {
        [$school, $owner, $s] = $this->sheet(); $owner->types()->attach(4); $owner->groups()->attach(3);
        $this->signIn($owner)->put('/grades/'.$s->id, ['revision' => 1, 'marks' => $this->marks($s)])->assertForbidden();
        $this->get('/grades/'.$s->id)->assertInertia(fn (Assert $v) => $v->where('canManage', false)->where('marks', null));
        UserGroup::findOrFail(3)->permissions()->detach('grades.manage');
        try { app(ManageGrades::class)->save($school, $s, ['revision' => 1, 'marks' => $this->marks($s)]); $this->fail('Revoked permission accepted'); } catch (AuthorizationException) {}
        $this->assertSame(1, $s->fresh()->revision);
    }
    public function test_publication_failure_rolls_back_sheet_status_and_audit(): void
    {
        [$school, , $s] = $this->sheet(); app(ManageGrades::class)->save($school, $s, ['revision' => 1, 'marks' => $this->marks($s)]); $before = DB::table('academic_audits')->count();
        GradePublication::creating(fn () => throw new RuntimeException('Simulated publication failure'));
        try { app(ManageGrades::class)->publish($school, $s, ['revision' => 2, 'decision' => 'pending', 'reason' => 'Publication fictive']); $this->fail('Expected failure'); }
        catch (RuntimeException $e) { $this->assertSame('Simulated publication failure', $e->getMessage()); } finally { GradePublication::flushEventListeners(); }
        $this->assertSame('draft', $s->fresh()->status); $this->assertSame(2, $s->fresh()->revision); $this->assertDatabaseCount('grade_publications', 0); $this->assertDatabaseCount('academic_audits', $before);
    }
    public function test_grade_search_and_pagination_do_not_mix_other_years_or_students(): void
    {
        [$school, , $s] = $this->sheet(); for ($i = 0; $i < 20; $i++) { $this->enrollment(); }
        $this->signIn($school)->get('/grades?level_id=1')->assertInertia(fn (Assert $v) => $v->where('enrollments.total', 21)->has('enrollments.data', 20)->where('statistics.draft', 1));
        $this->get('/grades?page=2')->assertInertia(fn (Assert $v) => $v->has('enrollments.data', 1));
        $this->get('/grades?search='.$s->enrollment->student->card_number)->assertInertia(fn (Assert $v) => $v->where('enrollments.total', 1));
    }
    public function test_csrf_protects_programs_grade_edits_publications_and_corrections(): void
    {
        [$school, , $s, $p] = $this->sheet(); $this->app->instance('env', 'local'); $this->signIn($school);
        $this->post('/grades', [])->assertStatus(419); $this->put('/grades/'.$s->id, [])->assertStatus(419); $this->post('/grades/'.$s->id.'/publish', [])->assertStatus(419); $this->post('/grades/'.$s->id.'/reopen', [])->assertStatus(419);
        $this->signIn($this->person(8))->post('/grades/programs', [])->assertStatus(419); $this->put('/grades/programs/'.$p->id, [])->assertStatus(419); $this->post('/grades/programs/'.$p->id.'/publish', [])->assertStatus(419);
    }
}
