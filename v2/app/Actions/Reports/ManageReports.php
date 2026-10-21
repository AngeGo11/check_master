<?php
namespace App\Actions\Reports;

use App\Models\Enrollment;
use App\Models\Report;
use App\Models\ReportDecision;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Eligibility;
use App\Services\ReportFiles;
use Closure;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ManageReports
{
    public function __construct(private ReportFiles $files, private Eligibility $eligibility) {}

    public function submit(User $actor, array $data, UploadedFile $file): Report
    {
        $digest = $this->files->fingerprint($file);
        $storedPath = null;
        try {
            return $this->locked($actor, 'reports.self', function () use ($actor, $data, $file, $digest, &$storedPath) {
                abort_unless($actor->fresh()->types()->where('user_types.id', 1)->exists(), 403);
                $student = Student::where('user_id', $actor->id)->lockForUpdate()->firstOrFail();
                $enrollment = Enrollment::where('student_id', $student->id)->whereKey($data['enrollment_id'])->lockForUpdate()->firstOrFail();
                $existing = ReportVersion::where('operation_key', $data['operation_key'])->first();
                if ($existing) {
                    if ((int) $existing->submitted_by !== $actor->id || (int) $existing->report->student_id !== $student->id
                        || (int) $existing->enrollment_id !== $enrollment->id || $existing->title !== $data['title'] || ! hash_equals($existing->sha256, $digest)) {
                        throw ValidationException::withMessages(['operation_key' => 'Cette référence de dépôt a déjà été utilisée. Rechargez la page.']);
                    }
                    return $existing->report;
                }
                $report = Report::where('student_id', $student->id)->lockForUpdate()->first();
                $this->revision($report?->revision ?? 0, (int) $data['revision']);
                if ($report && $report->status !== 'changes_requested') {
                    throw ValidationException::withMessages(['document' => 'Un mémoire est déjà déposé. Une nouvelle version est possible après une demande de correction.']);
                }
                $decision = $this->requireEligibility($enrollment);
                $report ??= new Report;
                $number = $report->exists ? $report->version_count + 1 : 1;
                $report->forceFill(['reference' => $report->reference ?? 'MEM-'.Str::ulid(), 'student_id' => $student->id,
                    'enrollment_id' => $enrollment->id, 'title' => $data['title'], 'status' => 'submitted', 'version_count' => $number,
                    'revision' => $report->exists ? $report->revision + 1 : 1, 'latest_submitted_at' => now()])->save();
                $storedPath = 'versions/'.Str::uuid().'.pdf';
                $this->files->store($file, $storedPath);
                $version = new ReportVersion;
                $version->forceFill(['report_id' => $report->id, 'enrollment_id' => $enrollment->id, 'submitted_by' => $actor->id,
                    'eligibility_review_id' => $decision['review']['id'], 'number' => $number, 'title' => $data['title'],
                    'storage_path' => $storedPath, 'size' => $file->getSize(), 'sha256' => $digest,
                    'operation_key' => $data['operation_key'], 'created_at' => now()])->save();
                $this->audit($actor, $report, $version, 'report.submitted', ['number' => $number, 'eligibility_review_id' => $decision['review']['id']]);
                return $report;
            });
        } catch (Throwable $error) {
            // A rollback cannot undo filesystem writes. Never remove a path referenced by a committed version.
            if ($storedPath !== null && ! ReportVersion::where('storage_path', $storedPath)->exists()) { Storage::disk('reports')->delete($storedPath); }
            throw $error;
        }
    }

    public function review(User $actor, Report $target, array $data): void
    {
        $this->locked($actor, 'reports.review', function () use ($actor, $target, $data) {
            $report = Report::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->revision($report->revision, (int) $data['revision']);
            if ($report->status !== 'submitted') { throw ValidationException::withMessages(['decision' => 'Cette version a déjà reçu une décision.']); }
            $version = $report->latestVersion()->firstOrFail();
            abort_if((int) $version->submitted_by === $actor->id || (int) $report->student->user_id === $actor->id, 403, 'Vous ne pouvez pas examiner votre propre dépôt.');
            $eligibility = null;
            if ($data['decision'] === 'approved') {
                $eligibility = $this->requireEligibility($report->enrollment->fresh());
                if (! $this->files->intact($version)) { throw ValidationException::withMessages(['document' => 'Le document est indisponible ou ne correspond plus au dépôt. L’approbation est bloquée.']); }
            }
            $decision = new ReportDecision;
            $decision->forceFill(['report_id' => $report->id, 'report_version_id' => $version->id, 'reviewed_by' => $actor->id,
                'eligibility_review_id' => $eligibility['review']['id'] ?? null, 'decision' => $data['decision'], 'comment' => $data['comment'], 'created_at' => now()])->save();
            $report->forceFill(['status' => $data['decision'], 'revision' => $report->revision + 1])->save();
            $this->audit($actor, $report, $version, 'report.reviewed', ['decision_id' => $decision->id, 'decision' => $data['decision']]);
        });
    }

    private function locked(User $actor, string $permission, Closure $callback): mixed
    {
        return DB::transaction(function () use ($actor, $permission, $callback) {
            // Same lock order as access, student and schooling writes; permissions and eligibility cannot race.
            UserGroup::where('code', UserGroup::ADMINISTRATOR)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize($permission);
            return $callback();
        }, attempts: 1); // No automatic replay of a callback that writes a document to disk.
    }
    private function revision(int $current, int $submitted): void
    {
        if ($current !== $submitted) { throw ValidationException::withMessages(['revision' => 'Le mémoire a changé depuis l’ouverture de la page. Rechargez-le avant de réessayer.']); }
    }
    private function requireEligibility(Enrollment $enrollment): array
    {
        $result = $this->eligibility->evaluate($enrollment);
        if ($result['status'] !== 'eligible' || ! $result['context_valid'] || $result['computed_result'] !== 'eligible') {
            throw ValidationException::withMessages(['eligibility' => 'L’inscription doit avoir un examen d’éligibilité favorable et encore valide. Consultez le service de scolarité.']);
        }
        return $result;
    }
    private function audit(User $actor, Report $report, ReportVersion $version, string $action, array $metadata): void
    {
        DB::table('report_audits')->insert(['actor_user_id' => $actor->id, 'report_id' => $report->id, 'report_version_id' => $version->id,
            'action' => $action, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
