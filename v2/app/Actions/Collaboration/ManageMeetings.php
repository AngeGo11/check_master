<?php
namespace App\Actions\Collaboration;
use App\Models\{Meeting, MeetingParticipant, MeetingAgendaItem, ReportRound, User};
use App\Services\{CollaborationAccess, CollaborationAudit, CollaborationLock, ReportAccess};
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ManageMeetings
{
    public function __construct(private CollaborationAccess $access, private ManageMessages $messages, private ReportAccess $reports) {}
    public function save(User $actor, array $data, ?Meeting $target = null): Meeting
    {
        return CollaborationLock::run($actor, 'meetings.manage', function (User $actor) use ($data, $target) {
            abort_unless($this->access->canOrganize($actor), 403);
            $ids = array_values(array_unique(array_map('intval', [...$data['participant_ids'], $actor->id]))); sort($ids);
            if (count($ids) > 200) { throw ValidationException::withMessages(['participant_ids' => 'La réunion est limitée à 200 participants, organisateur compris.']); }
            $roundIds = array_map('intval', $data['round_ids'] ?? []); sort($roundIds);
            $hash = hash('sha256', json_encode([$data['title'], $data['description'] ?? null, $data['priority'], $data['location'], $data['starts_at'], (int) $data['duration_minutes'], $ids, $roundIds], JSON_THROW_ON_ERROR));
            $meeting = $target ? Meeting::whereKey($target->id)->lockForUpdate()->firstOrFail() : Meeting::where('operation_key', $data['operation_key'])->lockForUpdate()->first();
            if ($meeting && ! $target) {
                if ((int) $meeting->created_by === $actor->id && hash_equals($meeting->request_hash, $hash)) { return $meeting; }
                throw ValidationException::withMessages(['operation_key' => 'Cette référence de création a déjà été utilisée.']);
            }
            $oldIds = []; $oldTitle = null;
            if ($meeting) {
                abort_unless($this->access->canManage($actor, $meeting), 403);
                $this->revision($meeting, $data['revision']); $this->scheduled($meeting);
                $oldIds = $meeting->participants()->whereNull('removed_at')->pluck('user_id')->all(); $oldTitle = $meeting->title;
                CollaborationAudit::record($actor, 'meeting.previous_schedule', $meeting->id, metadata: [
                    'starts_at' => $meeting->starts_at->toIso8601String(), 'ends_at' => $meeting->ends_at->toIso8601String(),
                    'responses' => $meeting->participants()->whereNull('removed_at')->get(['user_id', 'response', 'responded_at'])->toArray()]);
            }
            $members = $this->access->members()->whereIn('id', $ids)->get();
            if ($members->count() !== count($ids)) { throw ValidationException::withMessages(['participant_ids' => 'Choisissez des membres actifs de la commission disposant de l’accès aux réunions.']); }
            $start = CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $data['starts_at'], CollaborationAccess::TIMEZONE)->utc();
            if ($start->lte(now())) { throw ValidationException::withMessages(['starts_at' => 'La réunion doit commencer dans le futur.']); }
            $end = $start->addMinutes((int) $data['duration_minutes']);
            $this->noConflict($ids, $start, $end, $meeting?->id);
            $this->checkAgenda($actor, $roundIds, $ids);
            $new = ! $meeting; $meeting ??= new Meeting;
            $meeting->forceFill(['reference' => $meeting->reference ?? 'REU-'.Str::ulid(), 'created_by' => $actor->id,
                'operation_key' => $meeting->operation_key ?? $data['operation_key'], 'request_hash' => $meeting->request_hash ?? $hash,
                'title' => $data['title'], 'description' => $data['description'] ?? null, 'priority' => $data['priority'], 'location' => $data['location'],
                'starts_at' => $start, 'ends_at' => $end, 'revision' => $new ? 1 : $meeting->revision + 1])->save();
            $meeting->participants()->whereNotIn('user_id', $ids)->whereNull('removed_at')->update(['removed_at' => now()]);
            foreach ($members as $member) {
                $participant = $meeting->participants()->where('user_id', $member->id)->first() ?? new MeetingParticipant;
                $participant->forceFill(['meeting_id' => $meeting->id, 'user_id' => $member->id, 'name_snapshot' => $member->name,
                    'response' => 'pending', 'responded_at' => null, 'removed_at' => null, 'response_revision' => $participant->exists ? $participant->response_revision + 1 : 1])->save();
            }
            $meeting->agenda()->delete();
            foreach ($roundIds as $id) { $item = new MeetingAgendaItem; $item->forceFill(['meeting_id' => $meeting->id, 'report_round_id' => $id])->save(); }
            CollaborationAudit::record($actor, $new ? 'meeting.created' : 'meeting.updated', $meeting->id, metadata: ['participant_ids' => $ids, 'round_ids' => $roundIds, 'reason' => $data['reason'] ?? null]);
            $this->messages->meetingNotice($actor, $meeting->id, $ids, $new ? 'meeting_invitation' : 'meeting_updated',
                ($new ? 'Invitation : ' : 'Réunion modifiée : ').$meeting->title,
                $meeting->title."\n".$start->setTimezone(CollaborationAccess::TIMEZONE)->format('d/m/Y H:i')." (heure d’Abidjan)\n".$meeting->location."\nMerci de confirmer ou refuser votre participation dans la fiche de réunion.");
            $removed = array_values(array_diff($oldIds, $ids));
            $this->messages->meetingNotice($actor, $meeting->id, $removed, 'meeting_removed', 'Invitation retirée', 'Votre invitation à la réunion « '.$oldTitle.' » a été retirée.');
            return $meeting;
        });
    }
    public function cancel(User $actor, Meeting $target, array $data): void
    {
        CollaborationLock::run($actor, 'meetings.manage', function (User $actor) use ($target, $data) {
            $meeting = Meeting::whereKey($target->id)->lockForUpdate()->firstOrFail(); abort_unless($this->access->canManage($actor, $meeting), 403);
            $this->revision($meeting, $data['revision']);
            if (! in_array($meeting->state(), ['scheduled', 'ongoing'], true)) { throw ValidationException::withMessages(['meeting' => 'Cette réunion est déjà clôturée.']); }
            $meeting->forceFill(['cancelled_at' => now(), 'cancellation_reason' => $data['reason'], 'revision' => $meeting->revision + 1])->save();
            CollaborationAudit::record($actor, 'meeting.cancelled', $meeting->id, metadata: ['reason' => $data['reason']]);
            $this->messages->meetingNotice($actor, $meeting->id, $meeting->participants()->whereNull('removed_at')->pluck('user_id')->all(), 'meeting_cancelled',
                'Réunion annulée : '.$meeting->title, $meeting->title."\nMotif : ".$data['reason']);
        });
    }
    public function respond(User $actor, Meeting $target, array $data): void
    {
        CollaborationLock::run($actor, 'meetings.view', function (User $actor) use ($target, $data) {
            $meeting = $this->access->meetings($actor)->whereKey($target->id)->lockForUpdate()->firstOrFail();
            $member = $meeting->participants()->where('user_id', $actor->id)->whereNull('removed_at')->firstOrFail();
            $this->revision($meeting, $data['revision']); $this->scheduled($meeting);
            if ($member->response_revision !== (int) $data['response_revision']) { throw ValidationException::withMessages(['response_revision' => 'Votre réponse a changé. Rechargez la réunion.']); }
            if ($data['response'] === 'accepted') { $this->noConflict([$actor->id], $meeting->starts_at, $meeting->ends_at, $meeting->id); }
            if ($member->response === $data['response']) { return; }
            $member->forceFill(['response' => $data['response'], 'responded_at' => now(), 'response_revision' => $member->response_revision + 1])->save();
            CollaborationAudit::record($actor, 'meeting.responded', $meeting->id, metadata: ['response' => $data['response']]);
        });
    }
    public function revision(Meeting $meeting, int $revision): void
    {
        if ($meeting->revision !== $revision) { throw ValidationException::withMessages(['revision' => 'La réunion a changé. Rechargez-la avant de réessayer.']); }
    }
    private function scheduled(Meeting $meeting): void
    {
        if ($meeting->state() !== 'scheduled') { throw ValidationException::withMessages(['meeting' => 'Une réunion commencée ou annulée ne peut plus être reprogrammée et n’accepte plus de réponses.']); }
    }
    private function noConflict(array $ids, $start, $end, ?int $except): void
    {
        if (\App\Models\Defense::where('status', 'scheduled')->where('starts_at', '<', $end)->where('ends_at', '>', $start)->whereHas('currentJurors', fn ($q) => $q->whereIn('user_id', $ids))->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'Un participant siège déjà à un jury de soutenance sur ce créneau.']);
        }
        if (Meeting::whereNull('cancelled_at')->where('starts_at', '<', $end)->where('ends_at', '>', $start)
            ->when($except, fn ($q) => $q->where('id', '!=', $except))
            ->whereHas('participants', fn ($q) => $q->whereIn('user_id', $ids)->whereNull('removed_at')->where('response', '!=', 'declined'))->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'Un participant est déjà invité à une autre réunion sur ce créneau. Choisissez un autre horaire.']);
        }
    }
    private function checkAgenda(User $actor, array $roundIds, array $ids): void
    {
        $rounds = ReportRound::with(['version.report', 'members'])->whereIn('id', $roundIds)->get();
        if ($rounds->count() !== count($roundIds)) { throw ValidationException::withMessages(['round_ids' => 'Une transmission est introuvable.']); }
        foreach ($rounds as $round) {
            $report = $round->version->report;
            if (! ($actor->can('reports.view') || $this->reports->canReadAssigned($actor)) || ! $this->reports->reports($actor)->whereKey($report->id)->exists()
                || $report->status !== 'under_review' || $round->status !== 'open' || $report->latestVersion->latestRound->id !== $round->id
                || array_diff($ids, $round->members->pluck('user_id')->all())
                || $this->access->usersWith('reports.assigned')->whereIn('id', $ids)->count() !== count($ids)) {
                throw ValidationException::withMessages(['round_ids' => 'Chaque participant doit déjà avoir reçu cette version en commission. Une réunion ne transmet ni ne valide un mémoire.']);
            }
        }
    }
}
