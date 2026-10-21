<?php

namespace Tests\Feature;

use App\Actions\Reports\ManageReports;
use App\Actions\Schooling\ManageEnrollments;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Report;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\TuitionFee;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Eligibility;
use App\Services\ReportFiles;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use RuntimeException;
use Tests\TestCase;

class ReportDepositsTest extends TestCase
{
    use RefreshDatabase;

    private ?User $operator = null;

    protected function setUp(): void
    {
        parent::setUp(); $this->withoutVite(); $this->travelTo(Carbon::parse('2026-10-21 12:00:00'));
        Storage::fake('reports');
    }
    protected function tearDown(): void { $this->travelBack(); parent::tearDown(); }
    private function admin(): User
    {
        if (! $this->operator) {
            $this->operator = User::factory()->create();
            $this->operator->types()->attach(3); $this->operator->groups()->attach(8);
        }
        return $this->operator;
    }
    private function studentAccount(): User
    {
        $user = User::factory()->create(); $user->types()->attach(1); $user->groups()->attach(1); return $user;
    }
    private function delegate(array $permissions): User
    {
        $user = User::factory()->create(); $user->types()->attach(4); $user->groups()->attach(4);
        UserGroup::findOrFail(4)->permissions()->sync($permissions); return $user;
    }
    private function signIn(User $user): static
    {
        return $this->actingAs($user->fresh())->withSession(['auth_version' => $user->fresh()->auth_version]);
    }
    private function eligible(User $user, bool $reviewed = true): Enrollment
    {
        $year = AcademicYear::where('status', 'current')->first();
        if (! $year) {
            $year = new AcademicYear;
            $year->forceFill(['name' => '2026-2027', 'starts_on' => '2026-09-01', 'ends_on' => '2027-08-31', 'status' => 'current', 'current_slot' => 1])->save();
        }
        if (! TuitionFee::where('academic_year_id', $year->id)->exists()) {
            $fee = new TuitionFee; $fee->forceFill(['academic_year_id' => $year->id, 'study_level_id' => 5, 'amount' => 100000])->save();
        }
        $student = new Student;
        $student->forceFill(['card_number' => 'TEST-'.Str::ulid(), 'last_name' => 'Etudiant', 'first_name' => 'Fictif',
            'email' => Str::ulid().'@example.test', 'user_id' => $user->id, 'study_level_id' => 5, 'promotion_id' => 22, 'student_status_id' => 1])->save();
        $action = app(ManageEnrollments::class);
        $e = $action->create($this->admin(), ['card_number' => $student->card_number, 'academic_year_id' => $year->id, 'study_level_id' => 5, 'enrolled_on' => '2026-10-01']);
        $action->recordPayment($this->admin(), $e, ['amount' => 100000, 'method' => 'cash', 'cheque_number' => null,
            'purpose' => 'Scolarité fictive', 'paid_on' => '2026-10-20', 'operation_key' => (string) Str::uuid(), 'revision' => 1]);
        $action->saveInternship($this->admin(), $e, ['company' => 'Entreprise fictive', 'title' => 'Stage de test',
            'starts_on' => '2026-10-01', 'ends_on' => '2027-01-31', 'evidence_reference' => 'Convention fictive', 'revision' => 2]);
        if ($reviewed) { $action->review($this->admin(), $e, ['reason' => 'Critères vérifiés pour le test.', 'revision' => 3]); }
        return $e->fresh();
    }
    private function pdf(string $text = 'Memoire fictif', string $name = 'memoire.pdf'): UploadedFile
    {
        // A small, self-contained, one-page PDF; no historical document or personal data.
        $stream = "BT /F1 12 Tf 40 100 Td ($text) Tj ET";
        $objects = ['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 300 200] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>', '<< /Length '.strlen($stream).">>\nstream\n$stream\nendstream"];
        $pdf = "%PDF-1.4\n"; $offsets = [];
        foreach ($objects as $i => $object) { $offsets[] = strlen($pdf); $pdf .= ($i + 1)." 0 obj\n$object\nendobj\n"; }
        $xref = strlen($pdf); $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        foreach ($offsets as $offset) { $pdf .= sprintf("%010d 00000 n \n", $offset); }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
        return UploadedFile::fake()->createWithContent($name, $pdf);
    }
    private function data(Enrollment $e, array $overrides = []): array
    {
        return [...['enrollment_id' => $e->id, 'title' => 'Mémoire de démonstration', 'operation_key' => (string) Str::uuid(), 'revision' => 0], ...$overrides];
    }
    private function deposited(): array
    {
        $user = $this->studentAccount(); $e = $this->eligible($user);
        $report = app(ManageReports::class)->submit($user, $this->data($e), $this->pdf());
        return [$user, $e, $report];
    }
    private function reviewData(Report $report, array $overrides = []): array
    {
        return [...['decision' => 'changes_requested', 'comment' => 'Précisez la méthode et les sources.', 'revision' => $report->fresh()->revision], ...$overrides];
    }
    private function rejectSubmission(User $user, Enrollment $e, string $key): void
    {
        try { app(ManageReports::class)->submit($user, $this->data($e), $this->pdf()); $this->fail('An unavailable submission was accepted.'); }
        catch (ValidationException $error) { $this->assertArrayHasKey($key, $error->errors()); }
    }

    public function test_migration_grants_only_the_explicit_report_permissions_and_imports_nothing(): void
    {
        foreach (['student_reports', 'report_versions', 'report_decisions', 'report_audits'] as $table) { $this->assertDatabaseCount($table, 0); }
        $this->assertSame([], Storage::disk('reports')->allFiles());
        foreach (['reports.view', 'reports.review', 'reports.self'] as $code) {
            $this->assertSame([$code === 'reports.self' ? 1 : 8], DB::table('group_permission')->where('permission_code', $code)->pluck('user_group_id')->all());
        }
    }

    public function test_guest_and_unrelated_permissions_cannot_access_reports(): void
    {
        [$owner, $e, $r] = $this->deposited(); $v = $r->latestVersion;
        foreach (['/reports', '/my-reports', '/reports/'.$r->id, '/my-reports/'.$r->id.'/versions/'.$v->id.'/download'] as $url) { $this->get($url)->assertRedirect('/login'); }
        $this->signIn($this->delegate(['students.view', 'enrollments.view', 'payments.manage', 'eligibility.review']));
        foreach (['/reports', '/my-reports', '/reports/'.$r->id, '/reports/'.$r->id.'/versions/'.$v->id.'/download'] as $url) { $this->get($url)->assertForbidden(); }
        $this->post('/my-reports', [])->assertForbidden(); $this->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertForbidden();
    }

    public function test_report_reader_can_consult_and_download_but_cannot_review(): void
    {
        [, , $r] = $this->deposited(); $reader = $this->delegate(['reports.view']); $this->signIn($reader);
        $this->get('/reports')->assertOk();
        $this->get('/reports/'.$r->id)->assertInertia(fn (Assert $page) => $page->where('canReview', false));
        $this->get('/reports/'.$r->id.'/versions/'.$r->latestVersion->id.'/download')->assertOk();
        $this->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertForbidden();
        $this->assertDatabaseCount('report_decisions', 0);
    }

    public function test_personal_access_requires_both_student_type_and_permission(): void
    {
        $user = $this->studentAccount(); $this->signIn($user)->get('/my-reports')->assertOk();
        UserGroup::findOrFail(1)->permissions()->sync(['students.self']);
        $this->get('/my-reports')->assertForbidden(); $this->post('/my-reports', [])->assertForbidden();
        $staff = $this->delegate(['reports.self']); $this->signIn($staff)->get('/my-reports')->assertForbidden();
        $this->post('/my-reports', [])->assertForbidden();
    }

    public function test_matching_email_does_not_link_a_student_or_expose_a_report(): void
    {
        [, $e, $r] = $this->deposited(); $other = $this->studentAccount();
        $e->student->forceFill(['email' => $other->email])->save();
        $this->signIn($other)->get('/my-reports')->assertInertia(fn (Assert $page) => $page->where('student', null)->where('report', null)->has('enrollments', 0));
        $this->post('/my-reports', [...$this->data($e), 'document' => $this->pdf()])->assertNotFound();
        $this->get('/my-reports/'.$r->id.'/versions/'.$r->latestVersion->id.'/download')->assertNotFound();
    }

    public function test_student_deposits_a_private_pdf_without_invalidating_eligibility_or_sending_mail(): void
    {
        Mail::fake(); $user = $this->studentAccount(); $e = $this->eligible($user); $file = $this->pdf(name: 'nom-confidentiel.pdf');
        $digest = hash_file('sha256', $file->getRealPath()); $revision = $e->revision;
        $this->signIn($user)->post('/my-reports', [...$this->data($e), 'document' => $file])->assertRedirect('/my-reports')->assertSessionHasNoErrors();
        $r = Report::sole(); $v = $r->latestVersion;
        $this->assertSame($e->student_id, $r->student_id); $this->assertSame('submitted', $r->status); $this->assertSame(1, $r->version_count);
        $this->assertSame($digest, $v->sha256); $this->assertStringStartsWith('versions/', $v->storage_path);
        $this->assertStringNotContainsString('nom-confidentiel', $v->storage_path); Storage::disk('reports')->assertExists($v->storage_path);
        $this->assertSame('private', Storage::disk('reports')->getVisibility($v->storage_path));
        $this->assertSame($revision, $e->fresh()->revision); $this->assertSame('eligible', app(Eligibility::class)->evaluate($e->fresh())['status']);
        $this->assertDatabaseHas('report_audits', ['actor_user_id' => $user->id, 'action' => 'report.submitted']);
        $this->assertDatabaseCount('report_versions', 1); Mail::assertNothingSent(); Mail::assertNothingQueued();
    }

    public function test_student_cannot_use_another_students_enrollment_or_forge_owner_and_status(): void
    {
        $user = $this->studentAccount(); $own = $this->eligible($user); $foreign = $this->eligible($this->studentAccount());
        $this->signIn($user)->post('/my-reports', [...$this->data($foreign), 'document' => $this->pdf()])->assertNotFound();
        $this->post('/my-reports', [...$this->data($own), 'student_id' => $foreign->student_id, 'status' => 'approved', 'storage_path' => '../public/test.pdf', 'document' => $this->pdf()])->assertSessionHasErrors(['student_id', 'status', 'storage_path']);
        $this->assertDatabaseCount('student_reports', 0); $this->assertSame([], Storage::disk('reports')->allFiles());
    }

    public function test_met_criteria_are_insufficient_without_an_explicit_favorable_review(): void
    {
        $user = $this->studentAccount(); $e = $this->eligible($user, reviewed: false);
        $this->assertSame('eligible', app(Eligibility::class)->evaluate($e)['computed_result']);
        $this->signIn($user)->get('/my-reports')->assertInertia(fn (Assert $page) => $page->where('enrollments.0.can_submit', false));
        $this->post('/my-reports', [...$this->data($e), 'document' => $this->pdf()])->assertSessionHasErrors('eligibility');
        $this->assertDatabaseCount('student_reports', 0);
    }

    public function test_stale_review_archival_level_status_cancellation_and_closed_year_block_submission(): void
    {
        $user = $this->studentAccount(); $e = $this->eligible($user);
        $e->student->increment('revision'); $this->rejectSubmission($user, $e, 'eligibility');
        $e->student->decrement('revision');
        foreach (['is_archived' => true, 'study_level_id' => 4, 'student_status_id' => 2] as $key => $value) {
            $student = $e->student->fresh(); $original = $student->$key;
            $student->forceFill([$key => $value])->save(); $this->rejectSubmission($user, $e, 'eligibility');
            $student->forceFill([$key => $original])->save();
        }
        $e->forceFill(['cancelled_at' => now()])->save(); $this->rejectSubmission($user, $e, 'eligibility');
        $e->forceFill(['cancelled_at' => null])->save();
        $e->academicYear->forceFill(['status' => 'closed', 'current_slot' => null])->save(); $this->rejectSubmission($user, $e, 'eligibility');
        $this->assertDatabaseCount('report_versions', 0); $this->assertSame([], Storage::disk('reports')->allFiles());
    }

    public function test_non_pdf_content_wrong_extension_empty_and_oversized_uploads_are_rejected(): void
    {
        $user = $this->studentAccount(); $e = $this->eligible($user); $this->signIn($user);
        $files = [UploadedFile::fake()->createWithContent('renamed.pdf', '<html>Not a PDF</html>'),
            $this->pdf(name: 'memoire.txt'), UploadedFile::fake()->createWithContent('empty.pdf', ''), $this->pdf()->size(20481)];
        foreach ($files as $file) { $this->post('/my-reports', [...$this->data($e), 'document' => $file])->assertSessionHasErrors('document'); }
        $this->assertDatabaseCount('student_reports', 0); $this->assertSame([], Storage::disk('reports')->allFiles());
    }

    public function test_deposit_metadata_and_required_file_are_validated(): void
    {
        $user = $this->studentAccount(); $e = $this->eligible($user);
        $this->signIn($user)->post('/my-reports', [...$this->data($e), 'title' => '', 'operation_key' => 'invalid', 'revision' => -1])->assertSessionHasErrors(['title', 'document', 'operation_key', 'revision']);
        $this->post('/my-reports', [...$this->data($e), 'title' => str_repeat('x', 256), 'document' => $this->pdf()])->assertSessionHasErrors('title');
        $this->assertDatabaseCount('report_versions', 0);
    }

    public function test_retries_are_idempotent_even_after_a_decision_but_reusing_the_key_for_new_content_fails(): void
    {
        $user = $this->studentAccount(); $e = $this->eligible($user); $data = $this->data($e); $action = app(ManageReports::class);
        $r = $action->submit($user, $data, $this->pdf());
        $action->review($this->admin(), $r, $this->reviewData($r));
        $retried = $action->submit($user, $data, $this->pdf()); $this->assertSame($r->id, $retried->id);
        $this->assertSame('changes_requested', $retried->status); $this->assertDatabaseCount('report_versions', 1);
        $this->assertCount(1, Storage::disk('reports')->allFiles()); $this->assertDatabaseCount('report_audits', 2);
        foreach ([[$data, $this->pdf('Different content')], [[...$data, 'title' => 'Autre thème'], $this->pdf()]] as [$payload, $file]) {
            try { $action->submit($user, $payload, $file); $this->fail('Reused key was accepted for changed content.'); }
            catch (ValidationException $error) { $this->assertArrayHasKey('operation_key', $error->errors()); }
        }
        $other = $this->studentAccount(); $otherEnrollment = $this->eligible($other);
        $this->signIn($other)->post('/my-reports', [...$this->data($otherEnrollment), 'operation_key' => $data['operation_key'], 'document' => $this->pdf()])->assertSessionHasErrors('operation_key');
        $this->assertDatabaseCount('report_versions', 1);
    }

    public function test_a_second_submission_is_blocked_until_corrections_are_requested(): void
    {
        [$user, $e, $r] = $this->deposited(); $this->signIn($user);
        $this->post('/my-reports', [...$this->data($e, ['revision' => 0]), 'document' => $this->pdf()])->assertSessionHasErrors('revision');
        $this->post('/my-reports', [...$this->data($e, ['revision' => $r->revision]), 'document' => $this->pdf()])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('report_versions', 1); $this->assertCount(1, Storage::disk('reports')->allFiles());
    }

    public function test_correction_and_resubmission_preserve_old_documents_comments_and_titles(): void
    {
        [$user, $e, $r] = $this->deposited(); $first = $r->latestVersion; $firstBytes = Storage::disk('reports')->get($first->storage_path);
        $this->signIn($this->admin())->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertSessionHasNoErrors();
        $this->signIn($user)->get('/my-reports')->assertInertia(fn (Assert $page) => $page->where('report.status', 'changes_requested')->where('versions.data.0.decision.comment', 'Précisez la méthode et les sources.'));
        $this->post('/my-reports', [...$this->data($e, ['revision' => 2, 'title' => 'Thème corrigé']), 'document' => $this->pdf('Version corrigee')])->assertSessionHasNoErrors();
        $this->assertSame('submitted', $r->fresh()->status); $this->assertSame(3, $r->fresh()->revision); $this->assertSame(2, $r->fresh()->version_count);
        $this->assertSame('Mémoire de démonstration', $first->fresh()->title); $this->assertSame($firstBytes, Storage::disk('reports')->get($first->storage_path));
        $this->assertDatabaseHas('report_versions', ['report_id' => $r->id, 'number' => 2, 'title' => 'Thème corrigé']);
        $this->assertDatabaseHas('report_decisions', ['report_version_id' => $first->id, 'decision' => 'changes_requested']);
        $this->get('/my-reports/'.$r->id.'/versions/'.$first->id.'/download')->assertOk()->assertStreamedContent($firstBytes);
        $this->assertCount(2, Storage::disk('reports')->allFiles()); $this->assertDatabaseCount('report_audits', 3);
    }

    public function test_approval_records_the_reviewed_version_and_prevents_new_submissions(): void
    {
        [$user, $e, $r] = $this->deposited(); $data = $this->reviewData($r, ['decision' => 'approved', 'comment' => 'Document recevable administrativement.']);
        $this->signIn($this->admin())->post('/reports/'.$r->id.'/review', $data)->assertSessionHasNoErrors();
        $this->assertSame('approved', $r->fresh()->status);
        $this->assertDatabaseHas('report_decisions', ['report_version_id' => $r->latestVersion->id, 'eligibility_review_id' => $e->latestReview->id, 'reviewed_by' => $this->admin()->id, 'decision' => 'approved']);
        $this->post('/reports/'.$r->id.'/review', $data)->assertSessionHasErrors('revision');
        $this->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertSessionHasErrors('decision');
        $this->signIn($user)->post('/my-reports', [...$this->data($e, ['revision' => 2]), 'document' => $this->pdf()])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('report_decisions', 1); $this->assertDatabaseCount('report_versions', 1);
    }

    public function test_old_review_forms_cannot_review_a_new_version(): void
    {
        [$user, $e, $r] = $this->deposited(); $old = $this->reviewData($r); $action = app(ManageReports::class);
        $action->review($this->admin(), $r, $old);
        $action->submit($user, $this->data($e, ['revision' => 2]), $this->pdf('Second version'));
        $this->signIn($this->admin())->post('/reports/'.$r->id.'/review', [...$old, 'decision' => 'approved'])->assertSessionHasErrors('revision');
        $this->assertSame('submitted', $r->fresh()->status); $this->assertDatabaseCount('report_decisions', 1);
    }

    public function test_review_requires_an_explanatory_comment_and_cannot_choose_a_foreign_version(): void
    {
        [, , $r] = $this->deposited(); $this->signIn($this->admin());
        foreach (['', 'abc', str_repeat('x', 3001)] as $comment) { $this->post('/reports/'.$r->id.'/review', $this->reviewData($r, ['comment' => $comment]))->assertSessionHasErrors('comment'); }
        $this->post('/reports/'.$r->id.'/review', $this->reviewData($r, ['decision' => 'validated', 'report_version_id' => 999, 'status' => 'approved']))->assertSessionHasErrors(['decision', 'report_version_id', 'status']);
        $this->assertDatabaseCount('report_decisions', 0);
    }

    public function test_submitter_and_current_owner_cannot_review_their_own_file_with_a_second_role(): void
    {
        [$owner, $e, $r] = $this->deposited(); $owner->types()->attach(3); $owner->groups()->attach(8);
        $this->signIn($owner)->get('/reports/'.$r->id)->assertInertia(fn (Assert $page) => $page->where('canReview', false));
        $this->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertForbidden();
        $replacement = $this->studentAccount(); $replacement->types()->attach(3); $replacement->groups()->attach(8);
        $e->student->forceFill(['user_id' => $replacement->id])->save();
        $this->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertForbidden();
        $this->signIn($replacement)->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertForbidden();
        $this->assertDatabaseCount('report_decisions', 0);
    }

    public function test_approval_rechecks_eligibility_but_corrections_remain_possible(): void
    {
        [, $e, $r] = $this->deposited();
        app(ManageEnrollments::class)->removeInternship($this->admin(), $e, ['revision' => $e->revision, 'reason' => 'Justificatif incorrect.']);
        $this->signIn($this->admin())->post('/reports/'.$r->id.'/review', $this->reviewData($r, ['decision' => 'approved']))->assertSessionHasErrors('eligibility');
        $this->assertDatabaseCount('report_decisions', 0);
        $this->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertSessionHasNoErrors();
        $this->assertSame('changes_requested', $r->fresh()->status);
    }

    public function test_missing_truncated_or_modified_files_cannot_be_downloaded_or_approved(): void
    {
        foreach (['missing', 'truncated', 'changed'] as $case) {
            [$user, , $r] = $this->deposited(); $v = $r->latestVersion; $disk = Storage::disk('reports');
            if ($case === 'missing') { $disk->delete($v->storage_path); }
            else { $bytes = $disk->get($v->storage_path); $disk->put($v->storage_path, $case === 'truncated' ? substr($bytes, 0, 30) : str_replace('Memoire', 'MemoirX', $bytes)); }
            $this->signIn($user)->get('/my-reports/'.$r->id.'/versions/'.$v->id.'/download')->assertNotFound();
            $this->signIn($this->admin())->get('/reports/'.$r->id)->assertInertia(fn (Assert $page) => $page->where('documentAvailable', false));
            $this->post('/reports/'.$r->id.'/review', $this->reviewData($r, ['decision' => 'approved']))->assertSessionHasErrors('document');
            $this->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertSessionHasNoErrors();
        }
        $this->assertSame(0, DB::table('report_decisions')->where('decision', 'approved')->count());
    }

    public function test_database_failure_after_file_write_rolls_back_and_removes_only_the_new_file(): void
    {
        [, , $existing] = $this->deposited(); $existingPath = $existing->latestVersion->storage_path;
        $user = $this->studentAccount(); $e = $this->eligible($user);
        ReportVersion::creating(function () { throw new RuntimeException('Simulated database failure'); });
        try { app(ManageReports::class)->submit($user, $this->data($e), $this->pdf()); $this->fail('Expected database failure.'); }
        catch (RuntimeException $error) { $this->assertSame('Simulated database failure', $error->getMessage()); }
        finally { ReportVersion::flushEventListeners(); }
        $this->assertDatabaseCount('student_reports', 1); $this->assertDatabaseCount('report_versions', 1); $this->assertDatabaseCount('report_audits', 1);
        $this->assertSame([$existingPath], Storage::disk('reports')->allFiles());
    }

    public function test_storage_failure_does_not_leave_a_report_or_audit(): void
    {
        $user = $this->studentAccount(); $e = $this->eligible($user);
        $this->partialMock(ReportFiles::class, function ($mock) { $mock->shouldReceive('store')->once()->andThrow(ValidationException::withMessages(['document' => 'Stockage indisponible.'])); });
        $this->signIn($user)->post('/my-reports', [...$this->data($e), 'document' => $this->pdf()])->assertSessionHasErrors('document');
        $this->assertDatabaseCount('student_reports', 0); $this->assertDatabaseCount('report_versions', 0); $this->assertDatabaseCount('report_audits', 0);
        $this->assertSame([], Storage::disk('reports')->allFiles());
    }

    public function test_permissions_are_rechecked_inside_the_action_after_revocation(): void
    {
        [$user, $e, $r] = $this->deposited(); $reader = $this->delegate(['reports.view', 'reports.review']);
        UserGroup::findOrFail(4)->permissions()->sync(['reports.view']);
        try { app(ManageReports::class)->review($reader, $r, $this->reviewData($r)); $this->fail('Revoked review permission accepted.'); }
        catch (AuthorizationException) { $this->assertDatabaseCount('report_decisions', 0); }
        UserGroup::findOrFail(1)->permissions()->detach('reports.self');
        try { app(ManageReports::class)->submit($user, $this->data($e), $this->pdf()); $this->fail('Revoked submission permission accepted.'); }
        catch (AuthorizationException) { $this->assertDatabaseCount('report_versions', 1); }
        $this->signIn($this->admin())->put('/settings/groups/4', ['permissions' => ['reports.review']])->assertSessionHasErrors('permissions');
    }

    public function test_authorized_downloads_are_attachments_with_integrity_and_private_cache_headers(): void
    {
        [$user, , $r] = $this->deposited(); $v = $r->latestVersion; $bytes = Storage::disk('reports')->get($v->storage_path);
        $response = $this->signIn($user)->get('/my-reports/'.$r->id.'/versions/'.$v->id.'/download');
        $response->assertOk()->assertDownload($r->reference.'-v1.pdf')->assertHeader('Content-Type', 'application/pdf')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('Pragma', 'no-cache')->assertStreamedContent($bytes);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->get('/storage/reports/'.$v->storage_path)->assertNotFound();
    }

    public function test_students_cannot_download_each_others_versions_or_mix_report_and_version_ids(): void
    {
        [$owner, , $r] = $this->deposited(); [$other, , $foreign] = $this->deposited(); $v = $r->latestVersion; $otherVersion = $foreign->latestVersion;
        $this->signIn($other)->get('/my-reports/'.$r->id.'/versions/'.$v->id.'/download')->assertNotFound();
        $this->signIn($owner)->get('/my-reports/'.$r->id.'/versions/'.$otherVersion->id.'/download')->assertNotFound();
        $this->get('/reports/'.$r->id.'/versions/'.$v->id.'/download')->assertForbidden();
        $this->signIn($this->admin())->get('/reports/'.$r->id.'/versions/'.$otherVersion->id.'/download')->assertNotFound();
    }

    public function test_unlinking_the_account_or_revoking_its_type_immediately_removes_personal_access(): void
    {
        [$user, $e, $r] = $this->deposited(); $url = '/my-reports/'.$r->id.'/versions/'.$r->latestVersion->id.'/download';
        $this->signIn($user)->get($url)->assertOk(); $e->student->forceFill(['user_id' => null])->save();
        $this->get($url)->assertNotFound(); $this->get('/my-reports')->assertInertia(fn (Assert $page) => $page->where('report', null));
        $user->types()->detach(1); $this->get($url)->assertForbidden(); $this->get('/my-reports')->assertForbidden();
    }

    public function test_responses_do_not_expose_storage_paths_tokens_accounts_or_financial_details(): void
    {
        [$user, $e, $r] = $this->deposited();
        $this->signIn($user)->get('/my-reports')->assertInertia(fn (Assert $page) => $page->missing('student.email')->missing('student.user_id')
            ->missing('report.student_id')->missing('report.enrollment_id')->missing('versions.data.0.storage_path')->missing('versions.data.0.sha256')->missing('versions.data.0.operation_key')->missing('versions.data.0.submitted_by'));
        $payment = $e->payments()->first(); $payment->forceFill(['voided_at' => now()])->save();
        $this->signIn($this->delegate(['reports.view']))->get('/reports/'.$r->id)->assertInertia(fn (Assert $page) => $page->missing('report.student.email')->missing('report.student.user_id')
            ->missing('versions.data.0.storage_path')->missing('versions.data.0.sha256')->missing('eligibility.review')->missing('eligibility.amount_due')
            ->where('eligibility.criteria.0.met', false)->where('eligibility.criteria.0.detail', 'La scolarité de l’inscription doit être entièrement réglée.'));
    }

    public function test_directory_filters_and_year_statistics_use_the_latest_report_state(): void
    {
        [, $e, $pending] = $this->deposited(); [, , $correction] = $this->deposited(); [, $oldEnrollment, $approved] = $this->deposited();
        app(ManageReports::class)->review($this->admin(), $correction, $this->reviewData($correction));
        app(ManageReports::class)->review($this->admin(), $approved, $this->reviewData($approved, ['decision' => 'approved']));
        $year = new AcademicYear; $year->forceFill(['name' => '2025-2026', 'starts_on' => '2025-09-01', 'ends_on' => '2026-08-31', 'status' => 'closed'])->save();
        $oldEnrollment->forceFill(['academic_year_id' => $year->id])->save();
        $this->signIn($this->admin())->get('/reports')->assertInertia(fn (Assert $page) => $page->where('statistics', ['total' => 3, 'submitted' => 1, 'changes_requested' => 1, 'approved' => 1]));
        $this->get('/reports?status=submitted&academic_year_id='.$e->academic_year_id)->assertInertia(fn (Assert $page) => $page->has('reports.data', 1)->where('reports.data.0.id', $pending->id)->where('statistics.total', 2));
        $this->get('/reports?search='.$e->student->card_number.'&submitted_on=2026-10-21')->assertInertia(fn (Assert $page) => $page->has('reports.data', 1)->where('reports.data.0.id', $pending->id));
        $this->get('/reports?academic_year_id='.$year->id)->assertInertia(fn (Assert $page) => $page->has('reports.data', 1)->where('statistics.approved', 1));
        $this->get('/reports?status=validated')->assertSessionHasErrors('status');
        $this->get('/reports?submitted_on=invalid&page=0')->assertSessionHasErrors(['submitted_on', 'page']);
    }

    public function test_directory_is_paginated(): void
    {
        for ($i = 0; $i < 21; $i++) { $this->deposited(); }
        $this->signIn($this->admin())->get('/reports')->assertInertia(fn (Assert $page) => $page->has('reports.data', 20)->where('reports.total', 21));
        $this->get('/reports?page=2')->assertInertia(fn (Assert $page) => $page->has('reports.data', 1)->where('statistics.total', 21));
    }

    public function test_version_history_paginates_without_changing_the_current_status_or_version(): void
    {
        [$user, $e, $r] = $this->deposited(); $action = app(ManageReports::class);
        for ($i = 2; $i <= 11; $i++) {
            $action->review($this->admin(), $r, $this->reviewData($r));
            $action->submit($user, $this->data($e, ['revision' => $r->fresh()->revision]), $this->pdf('Version '.$i));
        }
        $this->signIn($user)->get('/my-reports')->assertInertia(fn (Assert $page) => $page->has('versions.data', 10)->where('versions.total', 11)->where('versions.data.0.number', 11));
        $this->get('/my-reports?page=2')->assertInertia(fn (Assert $page) => $page->has('versions.data', 1)->where('versions.data.0.number', 1)->where('report.status', 'submitted')->where('report.version_count', 11));
        $this->signIn($this->admin())->get('/reports/'.$r->id.'?page=2')->assertInertia(fn (Assert $page) => $page->where('canReview', true)->where('documentAvailable', true)->where('versions.data.0.number', 1));
    }

    public function test_dashboard_counts_and_latest_report_follow_permissions_and_ownership(): void
    {
        [$owner, , $r] = $this->deposited(); [, , $other] = $this->deposited();
        app(ManageReports::class)->review($this->admin(), $other, $this->reviewData($other));
        $this->signIn($this->admin())->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('reportCount', 2)->where('pendingReportCount', 1)->where('latestReport.reference', $other->reference));
        $this->signIn($owner)->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('reportCount', null)->where('pendingReportCount', null)->where('latestReport.reference', $r->reference)->where('latestReport.href', '/my-reports'));
        $this->signIn($this->delegate(['students.view']))->get('/dashboard')->assertInertia(fn (Assert $page) => $page->where('reportCount', null)->where('pendingReportCount', null)->where('latestReport', null));
    }

    public function test_submission_and_review_require_csrf_in_real_requests(): void
    {
        [$user, $e, $r] = $this->deposited(); $this->app->instance('env', 'local');
        $this->signIn($user)->post('/my-reports', [...$this->data($e), 'document' => $this->pdf()])->assertStatus(419);
        $this->signIn($this->admin())->post('/reports/'.$r->id.'/review', $this->reviewData($r))->assertStatus(419);
        $this->assertDatabaseCount('report_versions', 1); $this->assertDatabaseCount('report_decisions', 0);
    }

    public function test_upload_rate_limit_does_not_create_duplicate_versions(): void
    {
        $user = $this->studentAccount(); $e = $this->eligible($user); $data = $this->data($e); $this->signIn($user);
        for ($i = 0; $i < 6; $i++) { $this->post('/my-reports', [...$data, 'document' => $this->pdf()])->assertRedirect('/my-reports')->assertSessionHasNoErrors(); }
        $this->post('/my-reports', [...$data, 'document' => $this->pdf()])->assertStatus(429);
        $this->assertDatabaseCount('report_versions', 1); $this->assertCount(1, Storage::disk('reports')->allFiles());
    }
}
