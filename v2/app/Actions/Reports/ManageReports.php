<?php
namespace App\Actions\Reports;

use App\Models\Enrollment;
use App\Models\Report;
use App\Models\ReportRound;
use App\Models\ReportRoundMember;
use App\Models\ReportVote;
use App\Models\ReportVersion;
use App\Models\Student;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Eligibility;
use App\Services\ReportFiles;
use App\Services\ReportAccess;
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
    public function __construct(private ReportFiles $files, private Eligibility $eligibility, private ReportAccess $access) {}

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
                    'eligibility_review_id' => null, 'eligibility_snapshot' => ['criteria' => $decision['criteria'], 'fingerprint' => $decision['fingerprint']], 'number' => $number, 'title' => $data['title'],
                    'storage_path' => $storedPath, 'size' => $file->getSize(), 'sha256' => $digest,
                    'operation_key' => $data['operation_key'], 'created_at' => now()])->save();
                $this->audit($actor, $report, $version, 'report.submitted', ['number' => $number, 'eligibility_fingerprint' => $decision['fingerprint']]);
                return $report;
            });
        } catch (Throwable $error) {
            // A rollback cannot undo filesystem writes. Never remove a path referenced by a committed version.
            if ($storedPath !== null && ! ReportVersion::where('storage_path', $storedPath)->exists()) { Storage::disk('reports')->delete($storedPath); }
            throw $error;
        }
    }

    public function forward(User $actor, Report $target, array $data): ReportRound
    {
        return $this->locked($actor, 'reports.forward', function () use ($actor, $target, $data) {
            abort_unless($this->access->canForward($actor->fresh()), 403);
            $report = Report::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $this->revision($report->revision, (int) $data['revision']);
            if (! in_array($report->status, ['submitted', 'under_review'], true)) {
                throw ValidationException::withMessages(['report' => 'Seul un dépôt en attente peut être transmis. Une correction exige une nouvelle version.']);
            }
            $version = $report->latestVersion()->firstOrFail();
            abort_if((int) $version->submitted_by === $actor->id || (int) $report->student->user_id === $actor->id, 403);
            $roster = $this->access->roster();
            if ($roster->isEmpty()) { throw ValidationException::withMessages(['committee' => 'Aucun membre actif et compatible dans le groupe Commission de validation.']); }
            $fingerprint = $this->access->fingerprint($roster->modelKeys());
            if (! hash_equals($fingerprint, $data['committee_fingerprint'])) {
                throw ValidationException::withMessages(['committee' => 'La composition de la commission a changé. Rechargez le dossier.']);
            }
            // A temporary password cannot silently remove an expected voter; the member must
            // personalize it before voting. Check the group's grants independently of that password.
            foreach (['reports.assigned', 'reports.vote'] as $permission) {
                if (! DB::table('group_permission')->where('user_group_id', 9)->where('permission_code', $permission)->exists()) {
                    throw ValidationException::withMessages(['committee' => 'Rétablissez les droits de consultation et de vote du groupe Commission de validation.']);
                }
            }
            if ($roster->contains(fn ($member) => $member->id === (int) $version->submitted_by || $member->id === (int) $report->student->user_id)) {
                throw ValidationException::withMessages(['committee' => 'Un membre de la commission est le déposant ou le titulaire de ce dossier. Corrigez cette affectation avant transmission.']);
            }
            if (! $this->files->intact($version)) { throw ValidationException::withMessages(['document' => 'Le PDF est indisponible ou altéré. La transmission est bloquée.']); }
            $previous = $version->latestRound()->first();
            if ($report->status === 'under_review') {
                if (! $previous || $previous->status !== 'open' || hash_equals($fingerprint, $this->access->fingerprint($previous->members()->pluck('user_id')->all()))) {
                    throw ValidationException::withMessages(['committee' => 'Cette version a déjà été transmise à cette commission.']);
                }
                if (mb_strlen(trim($data['reason'] ?? '')) < 5) { throw ValidationException::withMessages(['reason' => 'Indiquez pourquoi la composition de la commission doit être actualisée.']); }
                $previous->forceFill(['status' => 'superseded', 'closed_at' => now()])->save();
            }
            $round = new ReportRound;
            $round->forceFill(['report_version_id' => $version->id, 'forwarded_by' => $actor->id, 'number' => ($previous?->number ?? 0) + 1,
                'status' => 'open', 'reason' => $data['reason'] ?? null, 'created_at' => now()])->save();
            foreach ($roster as $user) {
                $member = new ReportRoundMember;
                $member->forceFill(['report_round_id' => $round->id, 'user_id' => $user->id, 'name_snapshot' => $user->name])->save();
            }
            $report->forceFill(['status' => 'under_review', 'revision' => $report->revision + 1])->save();
            $this->audit($actor, $report, $version, 'report.forwarded', ['round_id' => $round->id, 'member_ids' => $roster->modelKeys(), 'superseded_round_id' => $previous?->id]);
            return $round;
        });
    }

    public function vote(User $actor, Report $target, ReportRound $targetRound, array $data): void
    {
        $this->locked($actor, 'reports.vote', function () use ($actor, $target, $targetRound, $data) {
            abort_unless($this->access->canVote($actor->fresh()), 403);
            $report = Report::whereKey($target->id)->lockForUpdate()->firstOrFail();
            $round = ReportRound::whereKey($targetRound->id)->whereHas('version', fn ($q) => $q->where('report_id', $report->id))->firstOrFail();
            $version = $round->version;
            $member = $round->members()->where('user_id', $actor->id)->firstOrFail();
            abort_if((int) $version->submitted_by === $actor->id || (int) $report->student->user_id === $actor->id, 403);
            $existing = $member->vote;
            if ($existing) {
                if ($existing->decision === $data['decision'] && $existing->comment === ($data['comment'] ?? null)) { return; }
                throw ValidationException::withMessages(['decision' => 'Votre avis sur cette transmission est déjà enregistré.']);
            }
            if ($report->status !== 'under_review' || $round->status !== 'open' || $report->latestVersion()->value('report_versions.id') !== $version->id) {
                throw ValidationException::withMessages(['round' => 'Cette transmission est clôturée ou remplacée. Rechargez le dossier.']);
            }
            if (! hash_equals($this->access->fingerprint($round->members()->pluck('user_id')->all()), $this->access->fingerprint($this->access->roster()->modelKeys()))) {
                throw ValidationException::withMessages(['committee' => 'La composition de la commission a changé. Le chargé de communication doit relancer la transmission ; tous les avis seront redemandés.']);
            }
            if (! in_array($data['decision'], ['approved', 'changes_requested'], true)) { throw ValidationException::withMessages(['decision' => 'Choisissez une décision valide.']); }
            if ($data['decision'] === 'changes_requested' && mb_strlen(trim($data['comment'] ?? '')) < 5) { throw ValidationException::withMessages(['comment' => 'Expliquez la correction demandée.']); }
            if ($data['decision'] === 'approved' && ! $this->files->intact($version)) { throw ValidationException::withMessages(['document' => 'Le PDF est indisponible ou altéré. L’approbation est bloquée.']); }
            $vote = new ReportVote;
            $vote->forceFill(['report_round_member_id' => $member->id, 'decision' => $data['decision'], 'comment' => $data['comment'] ?? null, 'created_at' => now()])->save();
            $status = 'under_review';
            if ($data['decision'] === 'changes_requested') { $status = 'changes_requested'; }
            elseif ($round->members()->exists() && ! $round->members()->whereDoesntHave('vote', fn ($q) => $q->where('decision', 'approved'))->exists()) { $status = 'accepted'; }
            if ($status !== 'under_review') { $round->forceFill(['status' => $status === 'accepted' ? 'approved' : 'changes_requested', 'closed_at' => now()])->save(); }
            $report->forceFill(['status' => $status, 'revision' => $report->revision + 1])->save();
            $this->audit($actor, $report, $version, 'report.voted', ['round_id' => $round->id, 'vote_id' => $vote->id, 'decision' => $data['decision'], 'status' => $status]);
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
            throw ValidationException::withMessages(['eligibility' => 'Les critères de dépôt doivent être remplis : scolarité soldée, niveau Master 2 et stage déclaré sur une inscription active.']);
        }
        return $result;
    }
    private function audit(User $actor, Report $report, ReportVersion $version, string $action, array $metadata): void
    {
        DB::table('report_audits')->insert(['actor_user_id' => $actor->id, 'report_id' => $report->id, 'report_version_id' => $version->id,
            'action' => $action, 'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
