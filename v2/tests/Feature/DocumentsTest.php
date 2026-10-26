<?php
namespace Tests\Feature;
use App\Actions\Documents\ManageDocuments;
use App\Actions\Reports\ManageReports;
use App\Actions\Schooling\ManageEnrollments;
use App\Models\{DocumentEvent, PersonalArchive, Report, ReportVersion, User, UserGroup, WorkDocument, WorkDocumentVersion};
use App\Services\{DocumentAccess, DocumentFiles, RoleDashboard};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Mail, Storage};
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AcademicFixtures;
use Tests\TestCase;
class DocumentsTest extends TestCase
{
    use RefreshDatabase, AcademicFixtures;
    protected function setUp(): void
    {
        parent::setUp(); $this->withoutVite(); $this->travelTo(now()->setDate(2026, 10, 26)->setTime(9, 0));
        Storage::fake('documents'); Storage::fake('reports'); Mail::fake();
    }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function data(array $overrides = []): array
    {
        return [...['kind' => 'personal', 'format' => 'text', 'revision' => 0, 'operation_key' => (string) Str::uuid(), 'title' => 'Mon document fictif', 'reason' => 'Version initiale de démonstration.',
            'sections' => [['heading' => 'Présentation', 'body' => 'Étude fictive, accents et confidentialité.', 'style' => 'paragraph']], 'entries' => []], ...$overrides];
    }
    private function personal(int $level = 5, string $format = 'text'): array
    {
        [$owner, $enrollment] = $this->enrollment($level); $data = $this->data(['format' => $format, 'sections' => $format === 'pdf' ? [] : $this->data()['sections']]);
        $d = app(ManageDocuments::class)->save($owner, $data, upload: $format === 'pdf' ? $this->pdf() : null);
        return [$owner, $d, $data, $enrollment];
    }
    private function minutesData(array $rounds, array $overrides = []): array
    {
        return $this->data(['kind' => 'minutes', 'title' => 'Titre interne du compte rendu', 'sections' => [['heading' => 'Notes des services', 'body' => 'SECRET_SERVICES', 'style' => 'paragraph']],
            'entries' => array_map(fn ($r) => ['round_id' => $r->id, 'source_fingerprint' => app(DocumentAccess::class)->sourceFingerprint($r->fresh()), 'body' => 'Synthèse individuelle du rapport '.$r->id.'.'], $rounds), ...$overrides]);
    }
    private function minutes(bool $two = false): array
    {
        $writer = $this->person(9); [$report, $round, $student] = $this->acceptedReport(); $rounds = [$round]; $second = null;
        if ($two) { $second = $this->acceptedReport(); $rounds[] = $second[1]; $second[0]->student->forceFill(['last_name' => 'AUTRE_DOSSIER_CONFIDENTIEL'])->save(); }
        $data = $this->minutesData($rounds); $d = app(ManageDocuments::class)->save($writer, $data);
        return [$writer, $d, $student, $round, $data, $second];
    }
    private function publication(WorkDocument $d, array $overrides = []): array { return [...['revision' => $d->fresh()->revision, 'operation_key' => (string) Str::uuid(), 'publication_reference' => 'PV-FICTIF-2026', 'reason' => 'Publication vérifiée pour la démonstration.'], ...$overrides]; }
    private function bookmark(string $type, int $id, array $overrides = []): array { return [...['source_type' => $type, 'source_id' => $id, 'revision' => 0, 'active' => true, 'operation_key' => (string) Str::uuid()], ...$overrides]; }

    public function test_role_matrix_and_direct_routes_protect_personal_drafts(): void
    {
        [$owner, $d] = $this->personal(); $v = $d->latestVersion;
        $this->get('/documents')->assertRedirect('/login');
        foreach ([2, 3, 8, 9] as $group) { $this->signIn($this->person($group))->get('/documents')->assertOk(); $this->get('/documents/'.$d->id)->assertNotFound(); $this->get('/documents/versions/'.$v->id.'/download')->assertNotFound(); }
        foreach ([4, 5, 6, 7] as $group) { $this->signIn($this->person($group))->get('/documents')->assertForbidden(); $this->get('/documents/create?kind=minutes')->assertForbidden(); $this->get('/my-archives')->assertOk(); }
        $this->signIn($owner)->get('/documents/'.$d->id)->assertOk();
        $this->assertDatabaseCount('permissions', 52); $this->assertDatabaseCount('group_permission', 100);
    }
    public function test_wrong_groups_bad_grants_and_missing_prerequisites_do_not_open_the_workspace(): void
    {
        $student = $this->person(1); $student->groups()->attach(9); $this->signIn($student)->get('/documents/create?kind=minutes')->assertForbidden();
        UserGroup::findOrFail(5)->permissions()->attach(['documents.view', 'documents.minutes']); $teacher = $this->person(5);
        $this->signIn($teacher)->get('/documents')->assertForbidden();
        $admin = $this->person(8); $this->signIn($admin)->put('/settings/groups/5', ['permissions' => ['documents.minutes', 'reports.assigned']])->assertSessionHasErrors('permissions');
        $this->put('/settings/groups/9', ['permissions' => ['documents.minutes']])->assertSessionHasErrors('permissions');
        UserGroup::findOrFail(1)->permissions()->detach('reports.self'); $this->signIn($student)->get('/documents')->assertForbidden();
    }
    public function test_inactive_temporary_password_and_csrf_checks_apply_to_document_writes(): void
    {
        $u = $this->person(1, ['is_active' => false]); $this->signIn($u)->get('/documents')->assertRedirect('/login');
        $this->signIn($this->person(1, ['must_change_password' => true]))->get('/documents')->assertRedirect('/password/change');
        [$owner] = $this->enrollment(); $this->signIn($owner); $this->app['env'] = 'local';
        $this->post('/documents', $this->data())->assertStatus(419); $this->assertDatabaseCount('work_documents', 0);
    }
    public function test_student_can_prepare_before_eligibility_without_creating_a_report(): void
    {
        [$owner, $d] = $this->personal(1);
        $this->signIn($owner)->get('/documents/'.$d->id)->assertInertia(fn (Assert $p) => $p->where('canEdit', true)->where('canDeposit', false)->where('available', true)->missing('version.storage_path')->missing('version.sha256'));
        $this->get('/documents/versions/'.$d->latestVersion->id.'/download')->assertOk()->assertDownload();
        $this->assertDatabaseCount('student_reports', 0); $this->assertDatabaseCount('work_document_versions', 1); Mail::assertNothingSent();
    }
    public function test_saved_versions_are_immutable_idempotent_and_reject_stale_forms(): void
    {
        [$owner, $d, $data] = $this->personal(); $old = $d->latestVersion; $bytes = Storage::disk('documents')->get($old->storage_path);
        $this->signIn($owner)->post('/documents', array_reverse($data, true))->assertSessionHasNoErrors(); $this->assertDatabaseCount('work_documents', 1);
        $this->post('/documents', [...$data, 'title' => 'Autre document'])->assertSessionHasErrors('operation_key');
        $new = $this->data(['revision' => 1, 'title' => 'Version corrigée']); $this->post('/documents/'.$d->id.'/versions', $new)->assertSessionHasNoErrors();
        $this->post('/documents/'.$d->id.'/versions', [...$new, 'operation_key' => (string) Str::uuid()])->assertSessionHasErrors('revision');
        $this->assertDatabaseCount('work_document_versions', 2); $this->assertSame($bytes, Storage::disk('documents')->get($old->storage_path));
        $this->get('/documents/'.$d->id.'?version='.$old->id)->assertInertia(fn (Assert $p) => $p->where('version.title', 'Mon document fictif')->where('history.total', 2));
    }
    public function test_pdf_uploads_preserve_bytes_and_reject_spoofed_or_oversized_files(): void
    {
        [$owner, $d] = $this->personal(format: 'pdf'); $v = $d->latestVersion;
        $this->assertSame(hash('sha256', Storage::disk('documents')->get($v->storage_path)), $v->sha256);
        $this->signIn($owner)->post('/documents/'.$d->id.'/versions', [...$this->data(['revision' => 1, 'format' => 'pdf', 'sections' => []]), 'document' => UploadedFile::fake()->createWithContent('faux.pdf', '<script>alert(1)</script>')])->assertSessionHasErrors('document');
        $this->post('/documents', [...$this->data(['format' => 'pdf', 'sections' => []]), 'document' => UploadedFile::fake()->create('grand.pdf', 20481, 'application/pdf')])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('work_document_versions', 1);
    }
    public function test_pdf_formdata_without_empty_array_keys_can_be_imported(): void
    {
        [$owner] = $this->enrollment(); $data = $this->data(['format' => 'pdf']); unset($data['sections'], $data['entries']);
        $this->signIn($owner)->post('/documents', [...$data, 'document' => $this->pdf()])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseCount('work_document_versions', 1); $this->assertSame([], WorkDocumentVersion::firstOrFail()->sections);
    }
    public function test_input_limits_and_forged_publication_fields_cannot_change_document_state(): void
    {
        [$owner] = $this->enrollment(); $data = $this->data(['sections' => array_fill(0, 11, ['heading' => 'Long', 'body' => str_repeat('x', 5000), 'style' => 'paragraph'])]);
        $this->signIn($owner)->post('/documents', $data)->assertSessionHasErrors('sections');
        $this->post('/documents', [...$this->data(), 'owner_user_id' => 999, 'published_at' => now()->toIso8601String(), 'storage_path' => '/etc/passwd'])->assertSessionHasNoErrors();
        $v = WorkDocumentVersion::firstOrFail(); $this->assertNull($v->published_at); $this->assertSame($owner->id, (int) $v->document->owner_user_id);
        $this->assertStringStartsWith('versions/', $v->storage_path);
    }
    public function test_storage_failure_rolls_back_metadata_and_leaves_no_partial_document(): void
    {
        [$owner] = $this->enrollment(); Storage::disk('documents');
        Storage::shouldReceive('disk')->with('documents')->andReturn($disk = \Mockery::mock()); $disk->shouldReceive('put')->once()->andReturn(false); $disk->shouldReceive('delete')->once()->andReturn(true);
        $this->signIn($owner)->post('/documents', $this->data())->assertSessionHasErrors('document');
        $this->assertDatabaseCount('work_documents', 0); $this->assertDatabaseCount('work_document_versions', 0); $this->assertDatabaseCount('document_events', 0);
    }
    public function test_database_failure_after_file_storage_removes_only_the_uncommitted_file(): void
    {
        [$owner, $d] = $this->personal(); $old = $d->latestVersion; $fail = true;
        DB::listen(function ($query) use (&$fail) { if ($fail && str_contains($query->sql, 'insert into "document_events"')) { $fail = false; throw new \RuntimeException('Simulated journal failure'); } });
        try { app(ManageDocuments::class)->save($owner, $this->data(['revision' => 1]), $d); $this->fail('Failure expected'); }
        catch (\RuntimeException $e) { $this->assertSame('Simulated journal failure', $e->getMessage()); }
        $this->assertDatabaseCount('work_document_versions', 1); $this->assertCount(1, Storage::disk('documents')->allFiles()); $this->assertTrue(app(DocumentFiles::class)->intact($old));
    }
    public function test_native_pdf_generation_escapes_text_and_keeps_unicode_and_long_content(): void
    {
        $data = ['reference' => 'DOC-TEST', 'number' => 1, 'title' => 'Mémoire éligibilité — démonstration', 'kind' => 'personal', 'published' => false, 'publicationReference' => null,
            'actor' => 'Auteur fictif', 'date' => '26/10/2026', 'sections' => [['heading' => 'Texte sûr', 'style' => 'paragraph', 'body' => '<img src="file:///etc/passwd"><script>alert(1)</script>'.str_repeat("\nÉlève, cœur et études.", 80)]], 'entries' => [], 'extract' => false];
        $html = view('documents.pdf', $data)->render(); $this->assertStringNotContainsString('<script>', $html); $this->assertStringContainsString('&lt;img', $html);
        $bytes = app(DocumentFiles::class)->render($data); $this->assertStringStartsWith('%PDF-', $bytes); $this->assertGreaterThan(1000, strlen($bytes));
    }
    public function test_only_a_member_of_a_closed_commission_can_prepare_its_minutes(): void
    {
        $writer = $this->person(9); [$report, $round] = $this->transmittedReport(); $data = $this->minutesData([$round]);
        $this->signIn($writer)->post('/documents', $data)->assertNotFound();
        app(ManageReports::class)->vote($writer, $report, $round, ['decision' => 'approved', 'comment' => null]); $data = $this->minutesData([$round->fresh()]);
        $this->signIn($this->person(9))->post('/documents', $data)->assertNotFound();
        $this->signIn($writer)->post('/documents', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('work_documents', 1);
    }
    public function test_a_commission_writer_cannot_write_about_their_own_student_record(): void
    {
        [$writer, $d, $student, $round] = $this->minutes();
        $round->version->report->student->forceFill(['user_id' => $writer->id])->save(); $writer->types()->attach(1); $writer->groups()->attach(1);
        $this->signIn($writer)->post('/documents/'.$d->id.'/versions', $this->minutesData([$round], ['revision' => 1]))->assertForbidden();
        $this->post('/documents/'.$d->id.'/publish', $this->publication($d))->assertForbidden();
    }
    public function test_minutes_drafts_are_author_private_and_sources_are_checked_again(): void
    {
        [$writer, $d, $student, $round, $data] = $this->minutes();
        foreach ([$student, $this->person(8), $this->person(3), $this->person(2)] as $u) { $this->signIn($u)->get('/documents/'.$d->id)->assertNotFound(); }
        $this->signIn($writer)->post('/documents/'.$d->id.'/versions', $this->minutesData([$round], ['revision' => 1, 'entries' => [[...$data['entries'][0], 'source_fingerprint' => str_repeat('0', 64)]]]))->assertSessionHasErrors('entries');
        $this->assertDatabaseCount('work_document_versions', 1);
    }
    public function test_publication_creates_a_new_immutable_version_and_retries_are_idempotent(): void
    {
        [$writer, $d] = $this->minutes(); $draft = $d->latestVersion; $hash = $draft->sha256; $data = $this->publication($d);
        $this->signIn($writer)->post('/documents/'.$d->id.'/publish', $data)->assertSessionHasNoErrors();
        $this->post('/documents/'.$d->id.'/publish', $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('work_document_versions', 2); $this->assertNull($draft->fresh()->published_at); $this->assertSame($hash, $draft->fresh()->sha256);
        $published = $d->fresh()->latestVersion; $this->assertNotNull($published->published_at); $this->assertSame(2, $published->number);
        $this->signIn($this->person(8))->get('/documents/'.$d->id)->assertInertia(fn (Assert $p) => $p->where('version.id', $published->id)->where('canEdit', false)->where('history.total', 1));
    }
    public function test_schooling_reads_publications_without_receiving_private_source_report_access(): void
    {
        [$writer, $d, $student, $round] = $this->minutes(); $draft = $d->latestVersion; $school = $this->person(3);
        $this->assertFalse($school->can('reports.view')); $this->signIn($school)->get('/documents/'.$d->id)->assertNotFound();
        $published = app(ManageDocuments::class)->publish($writer, $d, $this->publication($d));
        $this->get('/documents/'.$d->id)->assertInertia(fn (Assert $p) => $p->where('version.id', $published->id)->where('canEdit', false)->where('history.total', 1));
        $this->get('/documents/versions/'.$published->id.'/download')->assertOk()->assertDownload();
        $this->get('/documents/versions/'.$draft->id.'/download')->assertNotFound();
        $this->get('/documents/report-versions/'.$round->report_version_id.'/download')->assertNotFound();
        $this->post('/my-archives', $this->bookmark('document', $published->id))->assertSessionHasNoErrors();
        UserGroup::findOrFail(3)->permissions()->detach('documents.view');
        $this->get('/documents/versions/'.$published->id.'/download')->assertNotFound();
        $this->get('/my-archives')->assertInertia(fn (Assert $p) => $p->where('archives.total', 0));
    }
    public function test_student_extract_never_contains_another_report_or_common_notes_or_internal_title(): void
    {
        [$writer, $d, $student, $round, , $second] = $this->minutes(true); $v = app(ManageDocuments::class)->publish($writer, $d, $this->publication($d));
        $entry = $v->entries()->where('report_version_id', $round->report_version_id)->firstOrFail(); $other = $v->entries()->where('report_version_id', $second[1]->report_version_id)->firstOrFail();
        $response = $this->signIn($student)->get('/documents/extracts/'.$entry->id)->assertOk()->assertInertia(fn (Assert $p) => $p->where('entry.body', $entry->body)->missing('entry.sections')->missing('entry.entries')->missing('entry.title'));
        foreach (['SECRET_SERVICES', 'AUTRE_DOSSIER_CONFIDENTIEL', 'Titre interne du compte rendu'] as $text) { $this->assertStringNotContainsString($text, $response->getContent()); }
        $this->get('/documents/extracts/'.$other->id)->assertNotFound(); $this->get('/documents/'.$d->id)->assertNotFound();
        $this->get('/documents/versions/'.$v->id.'/download')->assertNotFound();
        $this->get('/documents/extracts')->assertInertia(fn (Assert $p) => $p->where('entries.total', 1));
    }
    public function test_extract_pdf_receives_only_the_single_authorized_report(): void
    {
        [$writer, $d, $student, $round] = $this->minutes(true); $v = app(ManageDocuments::class)->publish($writer, $d, $this->publication($d));
        $entry = $v->entries()->where('report_version_id', $round->report_version_id)->firstOrFail();
        $this->partialMock(DocumentFiles::class, function ($mock) use ($entry) { $mock->shouldReceive('render')->once()->withArgs(function ($data) use ($entry) {
            $this->assertSame([], $data['sections']); $this->assertCount(1, $data['entries']); $this->assertSame($entry->body, $data['entries'][0]['body']); $this->assertSame('Extrait individuel du compte rendu', $data['title']); return true;
        })->andReturn("%PDF-1.4\n%Extrait fictif\n%%EOF"); });
        $response = $this->signIn($student)->get('/documents/extracts/'.$entry->id.'/download')->assertOk()->assertDownload(); $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control')); $this->assertStringStartsWith('%PDF-', $response->streamedContent());
    }
    public function test_unpublished_correction_does_not_replace_the_last_publication_for_readers(): void
    {
        [$writer, $d, $student, $round] = $this->minutes(); $published = app(ManageDocuments::class)->publish($writer, $d, $this->publication($d));
        app(ManageDocuments::class)->save($writer, $this->minutesData([$round], ['revision' => 2, 'title' => 'CORRECTION_NON_PUBLIEE']), $d->fresh());
        $this->signIn($this->person(8))->get('/documents/'.$d->id)->assertInertia(fn (Assert $p) => $p->where('version.id', $published->id)->where('history.total', 1));
        $this->get('/documents')->assertInertia(fn (Assert $p) => $p->where('documents.data.0.title', $published->title));
        $this->get('/documents/'.$d->id.'?version='.$d->fresh()->latestVersion->id)->assertNotFound();
    }
    public function test_changed_source_or_duplicate_report_prevents_publishing_false_minutes(): void
    {
        [$writer, $d, $student, $round, $data] = $this->minutes();
        $this->signIn($writer)->post('/documents', [...$data, 'operation_key' => (string) Str::uuid(), 'entries' => [$data['entries'][0], $data['entries'][0]]])->assertSessionHasErrors('entries.0.round_id');
        $round->members()->first()->vote->forceFill(['decision' => 'changes_requested'])->save();
        $this->post('/documents/'.$d->id.'/publish', $this->publication($d))->assertSessionHasErrors('entries');
        $this->assertDatabaseCount('work_document_versions', 1);
    }
    public function test_own_archives_are_idempotent_reversible_and_do_not_delete_sources(): void
    {
        [$owner, $d] = $this->personal(); $v = $d->latestVersion; $data = $this->bookmark('document', $v->id);
        $this->signIn($owner)->post('/my-archives', $data)->assertSessionHasNoErrors(); $this->post('/my-archives', $data)->assertSessionHasNoErrors(); $this->assertDatabaseCount('personal_archives', 1);
        $this->post('/my-archives', $this->bookmark('document', $v->id, ['revision' => 1, 'active' => false]))->assertSessionHasNoErrors();
        $this->get('/my-archives')->assertInertia(fn (Assert $p) => $p->where('archives.total', 0)); $this->get('/my-archives?state=removed')->assertInertia(fn (Assert $p) => $p->where('archives.total', 1));
        $this->post('/my-archives', $this->bookmark('document', $v->id, ['revision' => 2]))->assertSessionHasNoErrors();
        $this->assertTrue(app(DocumentFiles::class)->intact($v)); $this->assertDatabaseCount('work_document_versions', 1);
        $this->post('/my-archives', $this->bookmark('document', $v->id, ['revision' => 1]))->assertSessionHasErrors('revision');
    }
    public function test_another_user_cannot_archive_read_or_remove_someones_private_document(): void
    {
        [$owner, $d] = $this->personal(); app(ManageDocuments::class)->archive($owner, $this->bookmark('document', $d->latestVersion->id));
        $this->signIn($this->person(1))->post('/my-archives', $this->bookmark('document', $d->latestVersion->id, ['active' => false, 'revision' => 1]))->assertNotFound();
        $this->get('/my-archives')->assertInertia(fn (Assert $p) => $p->where('archives.total', 0)); $this->assertTrue(PersonalArchive::firstOrFail()->active);
    }
    public function test_archived_reports_remain_bound_to_live_commission_permissions(): void
    {
        [$writer, $d, $student, $round] = $this->minutes(); $v = $round->version;
        app(ManageDocuments::class)->archive($writer, $this->bookmark('report', $v->id));
        $this->signIn($writer)->get('/my-archives')->assertInertia(fn (Assert $p) => $p->where('archives.total', 1));
        UserGroup::findOrFail(9)->permissions()->detach('reports.assigned');
        $this->get('/my-archives')->assertInertia(fn (Assert $p) => $p->where('archives.total', 0)); $this->get('/documents/report-versions/'.$v->id.'/download')->assertNotFound();
        $this->assertDatabaseCount('personal_archives', 1);
    }
    public function test_excerpts_can_be_archived_without_granting_access_to_the_full_minutes(): void
    {
        [$writer, $d, $student, $round] = $this->minutes(true); $v = app(ManageDocuments::class)->publish($writer, $d, $this->publication($d)); $entry = $v->entries()->where('report_version_id', $round->report_version_id)->firstOrFail();
        $this->signIn($student)->post('/my-archives', $this->bookmark('extract', $entry->id))->assertSessionHasNoErrors();
        $response = $this->get('/my-archives')->assertInertia(fn (Assert $p) => $p->where('archives.data.0.title', 'Extrait individuel du compte rendu')->where('archives.data.0.href', '/documents/extracts/'.$entry->id));
        $this->assertStringNotContainsString('SECRET_SERVICES', $response->getContent()); $this->post('/my-archives', $this->bookmark('document', $v->id))->assertNotFound();
    }
    public function test_relinking_a_student_account_does_not_transfer_private_working_documents(): void
    {
        [$owner, $d] = $this->personal(); app(ManageDocuments::class)->archive($owner, $this->bookmark('document', $d->latestVersion->id)); $other = $this->person(1);
        $d->student->forceFill(['user_id' => $other->id])->save();
        foreach ([$owner, $other] as $u) { $this->signIn($u)->get('/documents/'.$d->id)->assertNotFound(); $this->get('/my-archives')->assertInertia(fn (Assert $p) => $p->where('archives.total', 0)); }
    }
    public function test_missing_or_corrupt_file_cannot_be_downloaded_published_or_deposited(): void
    {
        [$owner, $d] = $this->personal(); $v = $d->latestVersion; Storage::disk('documents')->put($v->storage_path, 'tampered');
        $this->signIn($owner)->get('/documents/versions/'.$v->id.'/download')->assertNotFound();
        $this->post('/documents/versions/'.$v->id.'/deposit', ['enrollment_id' => 1, 'revision' => 0, 'operation_key' => (string) Str::uuid()])->assertNotFound();
        [$writer, $minutes] = $this->minutes(); Storage::disk('documents')->delete($minutes->latestVersion->storage_path);
        $this->signIn($writer)->post('/documents/'.$minutes->id.'/publish', $this->publication($minutes))->assertNotFound();
    }
    public function test_deposit_uses_existing_eligibility_and_preserves_the_private_source_pdf(): void
    {
        [$owner, $d, , $enrollment] = $this->personal(); $v = $d->latestVersion; $data = ['enrollment_id' => $enrollment->id, 'revision' => 0, 'operation_key' => (string) Str::uuid()];
        $this->signIn($owner)->post('/documents/versions/'.$v->id.'/deposit', $data)->assertSessionHasErrors(); $this->assertDatabaseCount('student_reports', 0);
        $a = app(ManageEnrollments::class); $a->recordPayment($this->person(8), $enrollment, ['amount' => 100000, 'method' => 'cash', 'cheque_number' => null, 'purpose' => 'Test', 'paid_on' => '2026-10-26', 'revision' => 1, 'operation_key' => (string) Str::uuid()]);
        $a->saveInternship($owner, $enrollment, ['company' => 'Entreprise fictive', 'title' => 'Stage futur déclaré', 'starts_on' => '2026-12-01', 'ends_on' => '2027-03-01', 'revision' => 2]);
        $this->get('/documents/'.$d->id)->assertInertia(fn (Assert $p) => $p->where('canDeposit', true));
        $this->post('/documents/versions/'.$v->id.'/deposit', $data)->assertRedirect('/my-reports'); $this->post('/documents/versions/'.$v->id.'/deposit', $data)->assertRedirect('/my-reports');
        $this->assertDatabaseCount('student_reports', 1); $this->assertDatabaseCount('report_versions', 1); $this->assertSame('submitted', Report::firstOrFail()->status);
        $this->assertSame($v->sha256, ReportVersion::firstOrFail()->sha256); $this->assertTrue(app(DocumentFiles::class)->intact($v)); Mail::assertNothingSent();
    }
    public function test_document_permission_is_rechecked_when_depositing_a_saved_version(): void
    {
        [$owner, $d, , $e] = $this->personal(); UserGroup::findOrFail(1)->permissions()->detach('documents.self');
        $this->signIn($owner)->post('/documents/versions/'.$d->latestVersion->id.'/deposit', ['enrollment_id' => $e->id, 'revision' => 0, 'operation_key' => (string) Str::uuid()])->assertForbidden();
        $this->assertDatabaseCount('student_reports', 0);
    }
    public function test_dashboard_and_audit_expose_role_scoped_counts_and_metadata_only(): void
    {
        [$writer, $d, $student] = $this->minutes(); app(ManageDocuments::class)->publish($writer, $d, $this->publication($d));
        $this->signIn($student)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.minute_extracts.value', 1)->where('dashboard.actions.documents.href', '/documents?kind=personal'));
        $admin = $this->person(8); $this->signIn($admin)->get('/dashboard')->assertInertia(fn (Assert $p) => $p->where('dashboard.cards.minutes.value', 1));
        $this->get('/audit?module=documents')->assertInertia(fn (Assert $p) => $p->where('events.total', 2));
        $event = DocumentEvent::where('action', 'document.published')->firstOrFail(); $response = $this->get('/audit/documents/'.$event->id)->assertOk();
        $this->assertStringNotContainsString('SECRET_SERVICES', $response->getContent());
        $student->types()->attach(3); $student->groups()->attach(8);
        $this->assertArrayNotHasKey('minutes', app(RoleDashboard::class)->build($student->fresh(), 'student')['cards']);
    }
    public function test_history_filters_and_pagination_use_only_authorized_versions(): void
    {
        [$owner, $d] = $this->personal();
        $this->partialMock(DocumentFiles::class, fn ($m) => $m->shouldReceive('render')->andReturn("%PDF-1.4\n% Test de pagination\n%%EOF"));
        for ($i = 1; $i <= 12; $i++) { app(ManageDocuments::class)->save($owner, $this->data(['revision' => $i, 'title' => 'Version '.$i]), $d->fresh()); }
        $this->signIn($owner)->get('/documents/'.$d->id.'?history_page=2')->assertInertia(fn (Assert $p) => $p->where('history.total', 13)->has('history.data', 3));
        $this->get('/documents?search=Version%2012&kind=personal')->assertInertia(fn (Assert $p) => $p->where('documents.total', 1));
        $this->get('/documents?kind=invalid')->assertSessionHasErrors('kind'); $this->get('/documents/'.$d->id.'?version=999')->assertNotFound();
    }
}
