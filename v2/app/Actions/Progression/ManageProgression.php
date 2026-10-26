<?php
namespace App\Actions\Progression;
use App\Models\{AcademicYear, AnnualDecision, Enrollment, RetakeEvent, RetakeFee, RetakeSubject, User};
use App\Services\{AcademicAccess, AnnualProgression, CollaborationLock, GradeCalculator, ProgressionAccess};
use Illuminate\Support\{Carbon, Str};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class ManageProgression
{
    private function fail(string $field, string $message): never { throw ValidationException::withMessages([$field => $message]); }
    private function hash(array $data): string { return hash('sha256', json_encode($data, JSON_THROW_ON_ERROR)); }
    private function editable(User $actor, Enrollment $e): void { abort_unless(app(ProgressionAccess::class)->canEdit($actor, $e), 403); }

    public function publish(User $actor, Enrollment $target, array $data): AnnualDecision
    {
        return CollaborationLock::run($actor, 'progression.manage', function (User $actor) use ($target, $data) {
            $e = Enrollment::whereKey($target->id)->lockForUpdate()->firstOrFail(); $this->editable($actor, $e);
            $data['selected'] = array_values($data['selected'] ?? []); sort($data['selected']); $hash = $this->hash($data);
            if ($existing = AnnualDecision::where('operation_key', $data['operation_key'])->first()) {
                if ((int) $existing->enrollment_id !== $e->id || (int) $existing->decided_by !== $actor->id || ! hash_equals($existing->request_hash, $hash)) { $this->fail('operation_key', 'Cette référence de décision a déjà été utilisée.'); }
                return $existing;
            }
            $number = (int) $e->annualDecisions()->max('number');
            if ($number !== (int) $data['revision']) { $this->fail('revision', 'Une nouvelle décision existe. Rechargez le dossier.'); }
            if (Enrollment::whereIn('source_annual_decision_id', $e->annualDecisions()->select('id'))->exists()) { $this->fail('decision', 'Cette décision a déjà servi à une réinscription. Son historique et les frais associés sont conservés.'); }
            $snapshot = app(AnnualProgression::class)->build($e);
            if (! hash_equals($snapshot['fingerprint'], $data['source_fingerprint'])) { $this->fail('source_fingerprint', 'Les bulletins ou rattrapages ont changé. Rechargez le bilan.'); }
            if (! $snapshot['known_ready']) { $this->fail('decision', 'Publiez tous les bulletins des semestres connus et terminez les corrections en cours.'); }
            if ($snapshot['partial_catalog'] && ! $data['partial_confirmed']) { $this->fail('partial_confirmed', 'Le catalogue annuel est incomplet. Confirmez la décision explicite du jury avec sa référence de procès-verbal.'); }
            $choices = collect($snapshot['subjects'])->keyBy('key'); $selected = [];
            foreach ($data['selected'] as $key) { if (! $choices->has($key)) { $this->fail('selected', 'Une matière ne correspond plus à une publication de ce dossier.'); } $selected[] = $choices[$key]; }
            if ($data['outcome'] !== 'conditional' && $selected) { $this->fail('selected', 'Sélectionnez des matières uniquement pour un passage à cheval.'); }
            if ($data['outcome'] === 'passed' && $snapshot['outstanding']) { $this->fail('outcome', 'Les matières antérieures doivent être validées avant une année validée sans réserve.'); }
            if ($data['outcome'] === 'conditional' && ((! $selected && ! $snapshot['outstanding']) || (int) $e->study_level_id >= 5)) { $this->fail('outcome', 'Le passage à cheval exige des matières à reprendre et un niveau supérieur existant.'); }
            $decision = new AnnualDecision;
            $decision->forceFill(['enrollment_id' => $e->id, 'number' => $number + 1, 'outcome' => $data['outcome'], 'reason' => $data['reason'], 'reference' => $data['reference'],
                'partial_confirmed' => $snapshot['partial_catalog'] && $data['partial_confirmed'], 'snapshot' => $snapshot, 'selected_subjects' => $selected, 'source_fingerprint' => $snapshot['fingerprint'],
                'decided_by' => $actor->id, 'actor_name' => $actor->name, 'operation_key' => $data['operation_key'], 'request_hash' => $hash, 'created_at' => now()])->save();
            AcademicAccess::audit($actor, $number ? 'annual.corrected' : 'annual.published', ['annual_decision_id' => $decision->id, 'number' => $decision->number], $e->id);
            return $decision;
        });
    }

    public function register(User $actor, AnnualDecision $target, array $data): Enrollment
    {
        return CollaborationLock::run($actor, 'progression.manage', function (User $actor) use ($target, $data) {
            abort_unless($actor->can('enrollments.manage'), 403);
            $d = AnnualDecision::findOrFail($target->id); $e = Enrollment::whereKey($d->enrollment_id)->lockForUpdate()->firstOrFail(); $this->editable($actor, $e);
            $hash = $this->hash($data);
            if ($existing = $d->nextEnrollment) {
                $saved = $existing->progression_data;
                if (($saved['actor_id'] ?? null) !== $actor->id || ($saved['operation_key'] ?? null) !== $data['operation_key'] || ! hash_equals($saved['request_hash'] ?? '', $hash)) { $this->fail('operation_key', 'Une réinscription est déjà rattachée à cette décision. Consultez-la.'); }
                return $existing;
            }
            if ((int) $data['revision'] !== $d->number || (int) $e->annualDecisions()->max('id') !== $d->id) { $this->fail('revision', 'Utilisez la dernière décision annuelle publiée.'); }
            $service = app(AnnualProgression::class); $snapshot = $service->build($e);
            if (! hash_equals($d->source_fingerprint, $snapshot['fingerprint']) || ! $snapshot['known_ready']) { $this->fail('decision', 'Le bilan a changé depuis cette décision. Publiez une décision actualisée.'); }
            $year = AcademicYear::findOrFail($data['academic_year_id']); $student = $e->student()->lockForUpdate()->firstOrFail();
            if ($year->status !== 'current' || ! $year->starts_on->gt($e->academicYear->ends_on)) { $this->fail('academic_year_id', 'Choisissez une année en cours commençant après la fin de l’année d’origine.'); }
            if (Enrollment::where('student_id', $student->id)->where('id', '!=', $e->id)->whereHas('academicYear', fn ($q) => $q->whereDate('starts_on', '>', $e->academicYear->starts_on->format('Y-m-d')))->exists()) { $this->fail('decision', 'Une inscription plus récente existe déjà. Reprenez le dernier parcours de cet étudiant.'); }
            if ((int) $student->study_level_id !== (int) $e->study_level_id) { $this->fail('student', 'Le niveau actuel du dossier a changé. Vérifiez son parcours avant de réinscrire.'); }
            if (Enrollment::where('student_id', $student->id)->where('academic_year_id', $year->id)->exists()) { $this->fail('academic_year_id', 'Une inscription existe déjà pour cet étudiant et cette année, éventuellement annulée.'); }
            $date = Carbon::parse($data['enrolled_on']);
            if ($date->lt($year->starts_on) || $date->gt($year->ends_on) || $date->gt(today())) { $this->fail('enrolled_on', 'Choisissez une date dans la nouvelle année, sans dépasser aujourd’hui.'); }
            $quote = $service->quote($d, $year);
            if (! hash_equals($quote['fingerprint'], $data['quote_fingerprint'])) { $this->fail('quote_fingerprint', 'Le devis a changé. Rechargez le dossier pour vérifier les tarifs.'); }
            if (! $quote['level_id'] || $quote['total'] === null || $quote['total'] > 1000000000) { $this->fail('fees', 'Configurez les tarifs nécessaires pour ce niveau. Le total ne peut pas dépasser un milliard de FCFA.'); }
            $debts = $service->outstanding($e);
            if ($debts->contains(fn ($s) => (int) $s->source_level_id >= $quote['level_id'])) { $this->fail('subjects', 'Les matières à cheval doivent provenir d’un niveau antérieur.'); }
            if ($d->outcome === 'conditional') {
                foreach ($d->selected_subjects as $subject) {
                    $retake = new RetakeSubject;
                    $retake->forceFill(['student_id' => $student->id, 'origin_enrollment_id' => $e->id, 'grade_publication_id' => $subject['publication_id'],
                        'academic_semester_id' => $subject['semester_id'], 'source_level_id' => $e->study_level_id, 'subject_key' => $subject['subject_key'], 'name' => $subject['name'], 'credits' => $subject['credits']])->save();
                    $debts->push($retake);
                }
            }
            $status = $debts->isNotEmpty() ? 2 : ($d->outcome === 'repeat' ? 3 : 1);
            $new = new Enrollment; $new->forceFill(['reference' => 'REG-'.Str::ulid(), 'student_id' => $student->id, 'academic_year_id' => $year->id,
                'study_level_id' => $quote['level_id'], 'tuition_fee_id' => $quote['fee_id'], 'student_status_id' => $status, 'amount_due' => $quote['total'], 'enrolled_on' => $data['enrolled_on'],
                'source_annual_decision_id' => $d->id, 'progression_data' => [...$quote, 'actor_id' => $actor->id, 'operation_key' => $data['operation_key'], 'request_hash' => $hash]])->save();
            foreach ($debts as $retake) { DB::table('enrollment_retake')->insert(['enrollment_id' => $new->id, 'retake_subject_id' => $retake->id, 'amount' => $quote['unit_amount']]); }
            $student->forceFill(['study_level_id' => $quote['level_id'], 'student_status_id' => $status, 'revision' => $student->revision + 1])->save();
            AcademicAccess::audit($actor, 'progression.enrolled', ['annual_decision_id' => $d->id, 'source_enrollment_id' => $e->id, 'retake_count' => $debts->count()], $new->id);
            return $new;
        });
    }

    public function updateRetake(User $actor, Enrollment $target, int $id, array $data): void
    {
        CollaborationLock::run($actor, 'progression.manage', function (User $actor) use ($target, $id, $data) {
            $e = Enrollment::whereKey($target->id)->lockForUpdate()->firstOrFail(); $this->editable($actor, $e);
            $subject = $e->retakes()->where('retake_subjects.id', $id)->lockForUpdate()->firstOrFail();
            if ($e->academicYear->status !== 'current') { $this->fail('enrollment', 'Consignez le rattrapage dans son inscription de l’année en cours.'); }
            $hash = $this->hash($data); $existing = RetakeEvent::where('operation_key', $data['operation_key'])->first();
            if ($existing) {
                if ((int) $existing->enrollment_id !== $e->id || (int) $existing->retake_subject_id !== $id || (int) $existing->actor_user_id !== $actor->id || ! hash_equals($existing->request_hash, $hash)) { $this->fail('operation_key', 'Cette référence de saisie est déjà utilisée.'); }
                return;
            }
            AcademicAccess::revision($subject, (int) $data['revision']); $score = GradeCalculator::score($data['score'] ?? null);
            $event = new RetakeEvent; $event->forceFill(['retake_subject_id' => $id, 'enrollment_id' => $e->id, 'actor_user_id' => $actor->id, 'actor_name' => $actor->name,
                'from_status' => $subject->status, 'to_status' => $data['status'], 'score' => $score, 'reason' => $data['reason'], 'reference' => $data['reference'],
                'operation_key' => $data['operation_key'], 'request_hash' => $hash, 'created_at' => now()])->save();
            $subject->forceFill(['status' => $data['status'], 'score' => $score, 'revision' => $subject->revision + 1])->save();
            AcademicAccess::audit($actor, 'retake.result_recorded', ['retake_subject_id' => $id, 'retake_event_id' => $event->id], $e->id);
        });
    }

    public function saveFee(User $actor, array $data): void
    {
        CollaborationLock::run($actor, 'progression.fees', function (User $actor) use ($data) {
            abort_unless(app(ProgressionAccess::class)->fees($actor), 403);
            $year = AcademicYear::findOrFail($data['academic_year_id']); if ($year->status === 'closed') { $this->fail('academic_year_id', 'Cette année est clôturée.'); }
            $fee = RetakeFee::where('academic_year_id', $year->id)->where('study_level_id', $data['study_level_id'])->lockForUpdate()->first();
            if ((int) ($fee?->revision ?? 0) !== (int) $data['revision']) { $this->fail('revision', 'Le tarif a changé. Rechargez la page.'); }
            $fee ??= new RetakeFee; $fee->forceFill(['academic_year_id' => $year->id, 'study_level_id' => $data['study_level_id'], 'amount' => $data['amount'], 'revision' => $fee->exists ? $fee->revision + 1 : 1])->save();
            AcademicAccess::audit($actor, 'retake.fee_updated', ['retake_fee_id' => $fee->id]);
        });
    }
}
