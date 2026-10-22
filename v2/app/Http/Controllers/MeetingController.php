<?php
namespace App\Http\Controllers;
use App\Actions\Collaboration\{ManageMeetings, ManageMeetingDocuments};
use App\Http\Requests\{SaveMeetingRequest, MeetingReasonRequest, MeetingResponseRequest, MeetingDocumentRequest};
use App\Models\{Meeting, MeetingDocument, ReportRound};
use App\Services\{CollaborationAccess, MeetingFiles, MeetingCalendar, ReportAccess};
use Carbon\CarbonImmutable;
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\{Inertia, Response};
use Symfony\Component\HttpFoundation\StreamedResponse;

class MeetingController extends Controller
{
    public function __construct(private CollaborationAccess $access, private ReportAccess $reports) {}
    private function month(Request $request): array
    {
        $filters = $request->validate(['month' => ['nullable', 'date_format:Y-m'], 'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(['scheduled', 'ongoing', 'completed', 'cancelled'])], 'page' => ['nullable', 'integer', 'min:1']]);
        $filters['month'] ??= now(CollaborationAccess::TIMEZONE)->format('Y-m');
        $start = CarbonImmutable::createFromFormat('!Y-m', $filters['month'], CollaborationAccess::TIMEZONE);
        $q = $this->access->meetings($request->user())->where('starts_at', '>=', $start->utc())->where('starts_at', '<', $start->addMonth()->utc());
        return [$filters, $start, $q];
    }
    private function stateQuery($query, string $status)
    {
        return match ($status) {
            'cancelled' => $query->whereNotNull('cancelled_at'),
            'scheduled' => $query->whereNull('cancelled_at')->where('starts_at', '>', now()),
            'ongoing' => $query->whereNull('cancelled_at')->where('starts_at', '<=', now())->where('ends_at', '>', now()),
            'completed' => $query->whereNull('cancelled_at')->where('ends_at', '<=', now()),
        };
    }
    public function index(Request $request): Response
    {
        [$filters, $month, $scope] = $this->month($request); $statistics = [];
        foreach (['scheduled', 'ongoing', 'completed', 'cancelled'] as $state) { $statistics[$state] = $this->stateQuery(clone $scope, $state)->count(); }
        $scope->when(trim($filters['search'] ?? '') !== '', fn ($q) => $q->where(fn ($q) => $q->where('title', 'like', '%'.trim($filters['search']).'%')->orWhere('location', 'like', '%'.trim($filters['search']).'%')));
        if ($filters['status'] ?? null) { $this->stateQuery($scope, $filters['status']); }
        $calendar = (clone $scope)->with('organizer')->orderBy('starts_at')->orderBy('id')->limit(501)->get();
        return Inertia::render('meetings/index', ['meetings' => (clone $scope)->with('organizer')->orderBy('starts_at')->orderBy('id')->paginate(20)->withQueryString()->through(fn ($m) => $this->summary($m)),
            'calendar' => $calendar->take(500)->map(fn ($m) => $this->summary($m)), 'calendarTruncated' => $calendar->count() > 500,
            'statistics' => $statistics, 'filters' => $filters, 'timezone' => CollaborationAccess::TIMEZONE, 'canCreate' => $this->access->canOrganize($request->user())]);
    }
    public function create(Request $request): Response { abort_unless($this->access->canOrganize($request->user()), 403); return $this->form($request); }
    public function edit(Request $request, Meeting $meeting): Response
    {
        abort_unless($this->access->canManage($request->user(), $meeting), 403); abort_unless($meeting->state() === 'scheduled', 422);
        return $this->form($request, $meeting);
    }
    private function form(Request $request, ?Meeting $meeting = null): Response
    {
        $actor = $request->user(); $rounds = collect();
        if ($actor->can('reports.view') || $this->reports->canReadAssigned($actor)) {
            $reportIds = $this->reports->reports($actor)->select('student_reports.id');
            $rounds = ReportRound::with('version.report')->where('status', 'open')->whereHas('members', fn ($q) => $q->where('user_id', $actor->id))
                ->whereHas('version', fn ($q) => $q->whereIn('report_id', $reportIds))->orderByDesc('id')->limit(200)->get()
                ->map(fn ($r) => ['id' => $r->id, 'name' => $r->version->report->reference.' · '.$r->version->title.' · v'.$r->version->number]);
        }
        return Inertia::render('meetings/form', ['meeting' => $meeting ? [...$meeting->only('id', 'title', 'description', 'priority', 'location', 'revision', 'operation_key'),
            'starts_at' => $meeting->starts_at->setTimezone(CollaborationAccess::TIMEZONE)->format('Y-m-d\TH:i'), 'duration_minutes' => (int) $meeting->starts_at->diffInMinutes($meeting->ends_at),
            'participant_ids' => $meeting->participants()->whereNull('removed_at')->pluck('user_id'), 'round_ids' => $meeting->agenda()->pluck('report_round_id')] : null,
            'members' => $this->access->members()->get(['id', 'name']), 'rounds' => $rounds, 'operationKey' => (string) Str::uuid(), 'timezone' => CollaborationAccess::TIMEZONE]);
    }
    public function store(SaveMeetingRequest $request, ManageMeetings $action): RedirectResponse
    {
        $meeting = $action->save($request->user(), $request->validated()); return to_route('meetings.show', $meeting)->with('success', 'Réunion planifiée. Les invitations sont disponibles dans la messagerie interne.');
    }
    public function update(SaveMeetingRequest $request, Meeting $meeting, ManageMeetings $action): RedirectResponse
    {
        $action->save($request->user(), $request->validated(), $meeting); return to_route('meetings.show', $meeting)->with('success', 'Réunion mise à jour. Les participants sont invités à confirmer à nouveau.');
    }
    public function show(Request $request, Meeting $meeting): Response
    {
        $actor = $request->user(); $meeting = $this->access->meetings($actor)->with(['organizer', 'participants', 'agenda.round.version.report', 'documents'])->findOrFail($meeting->id);
        $canManage = $this->access->canManage($actor, $meeting); $member = $meeting->participants->whereNull('removed_at')->firstWhere('user_id', $actor->id);
        $agenda = $meeting->agenda->filter(function ($item) use ($actor) {
            return ($actor->can('reports.view') || $this->reports->canReadAssigned($actor)) && $this->reports->versionAllowed($actor, $item->round->version)
                && $this->reports->reports($actor)->whereKey($item->round->version->report_id)->exists();
        })->map(fn ($item) => ['id' => $item->id, 'title' => $item->round->version->title, 'reference' => $item->round->version->report->reference,
            'version' => $item->round->version->number, 'href' => '/reports/'.$item->round->version->report_id]);
        return Inertia::render('meetings/show', ['meeting' => [...$this->summary($meeting), 'description' => $meeting->description, 'revision' => $meeting->revision, 'cancellation_reason' => $meeting->cancellation_reason,
            'participants' => $meeting->participants->whereNull('removed_at')->map(fn ($p) => ['name' => $p->name_snapshot, 'response' => $p->response, 'is_you' => (int) $p->user_id === $actor->id])->values(),
            'my_response' => $member ? $member->only('response', 'response_revision') : null, 'agenda' => $agenda->values(),
            'documents' => $meeting->documents->map(fn ($d) => [...$d->only('id', 'title', 'size', 'withdrawal_reason'), 'created_at' => $d->created_at->toIso8601String(), 'withdrawn' => (bool) $d->withdrawn_at]),
            'activity' => DB::table('collaboration_audits as a')->leftJoin('users as u', 'u.id', '=', 'a.actor_user_id')->where('a.meeting_id', $meeting->id)
                ->where('a.action', 'like', 'meeting.%')->where('a.action', '!=', 'meeting.previous_schedule')->orderByDesc('a.id')->limit(50)->get(['a.id', 'a.action', 'u.name as actor', 'a.created_at'])],
            'canManage' => $canManage, 'canRespond' => (bool) $member && $meeting->state() === 'scheduled', 'operationKey' => (string) Str::uuid(), 'timezone' => CollaborationAccess::TIMEZONE]);
    }
    public function cancel(MeetingReasonRequest $request, Meeting $meeting, ManageMeetings $action): RedirectResponse { $action->cancel($request->user(), $meeting, $request->validated()); return back()->with('success', 'Réunion annulée. Les participants ont reçu une notification interne.'); }
    public function respond(MeetingResponseRequest $request, Meeting $meeting, ManageMeetings $action): RedirectResponse { $action->respond($request->user(), $meeting, $request->validated()); return back()->with('success', 'Votre réponse personnelle a été enregistrée.'); }
    public function upload(MeetingDocumentRequest $request, Meeting $meeting, ManageMeetingDocuments $action): RedirectResponse { $action->upload($request->user(), $meeting, $request->safe()->except('document'), $request->file('document')); return back()->with('success', 'Document ajouté à la réunion.'); }
    public function withdraw(MeetingReasonRequest $request, Meeting $meeting, MeetingDocument $document, ManageMeetingDocuments $action): RedirectResponse { $action->withdraw($request->user(), $meeting, $document, $request->validated()); return back()->with('success', 'Document retiré des téléchargements. Son historique est conservé.'); }
    public function download(Request $request, Meeting $meeting, MeetingDocument $document, MeetingFiles $files): StreamedResponse
    {
        $this->access->meetings($request->user())->findOrFail($meeting->id); abort_unless((int) $document->meeting_id === $meeting->id, 404); return $files->download($document);
    }
    public function calendar(Request $request, MeetingCalendar $calendar)
    {
        [$filters, $month, $scope] = $this->month($request);
        $meetings = $scope->orderBy('starts_at')->limit(501)->get(); abort_if($meetings->count() > 500, 422, 'Export limité à 500 réunions par mois.');
        return $calendar->download($meetings, 'planning-'.$filters['month'].'.ics');
    }
    public function event(Request $request, Meeting $meeting, MeetingCalendar $calendar)
    {
        $meeting = $this->access->meetings($request->user())->findOrFail($meeting->id); return $calendar->download(collect([$meeting]), $meeting->reference.'.ics');
    }
    private function summary(Meeting $m): array
    {
        return [...$m->only('id', 'reference', 'title', 'location', 'priority'), 'organizer' => $m->organizer->name, 'status' => $m->state(),
            'starts_at' => $m->starts_at->toIso8601String(), 'ends_at' => $m->ends_at->toIso8601String()];
    }
}
