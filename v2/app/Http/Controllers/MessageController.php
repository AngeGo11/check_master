<?php
namespace App\Http\Controllers;
use App\Actions\Collaboration\ManageMessages;
use App\Http\Requests\{SaveMessageRequest, MessageStateRequest};
use App\Models\{InternalMessage, User, UserGroup};
use App\Services\CollaborationAccess;
use Illuminate\Http\{JsonResponse, RedirectResponse, Request};
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\{Inertia, Response};

class MessageController extends Controller
{
    public function __construct(private CollaborationAccess $access) {}
    public function index(Request $request): Response
    {
        $filters = $request->validate(['folder' => ['nullable', Rule::in(['inbox', 'unread', 'sent', 'drafts', 'archive', 'trash'])],
            'search' => ['nullable', 'string', 'max:150'], 'priority' => ['nullable', Rule::in(InternalMessage::PRIORITIES)],
            'category' => ['nullable', Rule::in(InternalMessage::CATEGORIES)], 'page' => ['nullable', 'integer', 'min:1']]);
        $actor = $request->user(); $folder = $filters['folder'] ?? 'inbox'; $counts = [];
        foreach (['inbox', 'unread', 'sent', 'drafts', 'archive', 'trash'] as $name) { $counts[$name] = $this->folder($actor, $name)->count(); }
        $query = $this->folder($actor, $folder)->with('recipients')
            ->when(trim($filters['search'] ?? '') !== '', fn ($q) => $q->where(fn ($q) => $q->where('subject', 'like', '%'.trim($filters['search']).'%')->orWhere('body', 'like', '%'.trim($filters['search']).'%')->orWhere('sender_name', 'like', '%'.trim($filters['search']).'%')))
            ->when($filters['priority'] ?? null, fn ($q, $value) => $q->where('priority', $value))
            ->when($filters['category'] ?? null, fn ($q, $value) => $q->where('category', $value));
        return Inertia::render('messages/index', ['messages' => $query->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($m) => $this->summary($m, $actor)),
            'counts' => $counts, 'filters' => [...$filters, 'folder' => $folder]]);
    }
    private function folder(User $actor, string $folder)
    {
        $q = $this->access->messages($actor);
        return match ($folder) {
            'drafts' => $q->where('sender_id', $actor->id)->where('status', 'draft')->where('sender_folder', '!=', 'trash'),
            'sent' => $q->where('sender_id', $actor->id)->where('status', 'sent')->where('sender_folder', 'sent'),
            'archive', 'trash' => $q->where(fn ($q) => $q->where(fn ($s) => $s->where('sender_id', $actor->id)->where('sender_folder', $folder))
                ->orWhereHas('recipients', fn ($r) => $r->where('user_id', $actor->id)->where('folder', $folder))),
            default => $q->where('status', 'sent')->whereHas('recipients', fn ($r) => $r->where('user_id', $actor->id)->where('folder', 'inbox')->when($folder === 'unread', fn ($r) => $r->whereNull('read_at'))),
        };
    }
    public function contacts(Request $request): JsonResponse
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'group_id' => ['nullable', 'integer', 'exists:user_groups,id']]);
        $q = $this->access->usersWith('messages.use')->where('id', '!=', $request->user()->id);
        if ($data['group_id'] ?? null) {
            abort_unless($request->user()->can('messages.broadcast'), 403);
            $q = $this->access->compatibleGroup($q, (int) $data['group_id']);
        } else {
            $search = trim($data['search'] ?? '');
            $q->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')));
        }
        $limit = ($data['group_id'] ?? null) ? 200 : 30;
        $users = $q->orderBy('name')->orderBy('id')->limit($limit + 1)->get(['id', 'name', 'email']);
        if (($data['group_id'] ?? null) && $users->count() > $limit) { return response()->json(['message' => 'Ce groupe dépasse 200 destinataires. Sélectionnez les contacts individuellement.'], 422); }
        return response()->json(['users' => $users->take($limit)->values(), 'more' => $users->count() > $limit], 200, ['Cache-Control' => 'private, no-store']);
    }
    public function create(Request $request, ManageMessages $action): Response { return $this->composer($request, $action); }
    public function edit(Request $request, InternalMessage $message, ManageMessages $action): Response
    {
        abort_unless((int) $message->sender_id === $request->user()->id && $message->status === 'draft' && $message->sender_folder !== 'trash', 404);
        return $this->composer($request, $action, $message);
    }
    private function composer(Request $request, ManageMessages $action, ?InternalMessage $draft = null): Response
    {
        $data = $request->validate(['source_id' => ['nullable', 'integer', 'min:1', 'required_with:source_mode'], 'source_mode' => ['nullable', Rule::in(['reply', 'forward']), 'required_with:source_id']]);
        $source = ! $draft && isset($data['source_id']) ? $action->source($request->user(), (int) $data['source_id']) : null;
        if ($source && $data['source_mode'] === 'reply') { abort_unless((int) $source->sender_id !== $request->user()->id && $source->kind === 'message', 422); }
        $ids = $draft?->draft_recipients ?? ($source && $data['source_mode'] === 'reply' ? [$source->sender_id] : []);
        $selected = User::whereIn('id', $ids)->get(['id', 'name', 'email']);
        $forward = $source && $data['source_mode'] === 'forward';
        return Inertia::render('messages/compose', ['draft' => $draft ? [...$draft->only('id', 'subject', 'body', 'priority', 'category', 'revision', 'source_id', 'source_mode'), 'operation_key' => $draft->operation_key] : [
            'id' => null, 'subject' => $source ? Str::limit(($forward ? 'Tr : ' : 'Re : ').$source->subject, 255, '') : '',
            'body' => $forward ? "Message transféré de ".$source->sender_name."\nObjet : ".$source->subject."\n\n".Str::limit($source->body, 18000, "\n[Extrait tronqué]") : '',
            'priority' => 'normal', 'category' => $source?->category ?? 'general', 'revision' => 0, 'operation_key' => (string) Str::uuid(), 'source_id' => $source?->id, 'source_mode' => $source ? $data['source_mode'] : null],
            'selected' => $selected, 'groups' => $request->user()->can('messages.broadcast') ? UserGroup::orderBy('id')->get(['id', 'name']) : [],
            'limit' => $request->user()->can('messages.broadcast') ? 200 : 20]);
    }
    public function store(SaveMessageRequest $request, ManageMessages $action): RedirectResponse { return $this->saved($action->save($request->user(), $request->validated())); }
    public function update(SaveMessageRequest $request, InternalMessage $message, ManageMessages $action): RedirectResponse { return $this->saved($action->save($request->user(), $request->validated(), $message)); }
    private function saved(InternalMessage $message): RedirectResponse
    {
        return redirect($message->status === 'draft' ? '/messages/'.$message->id.'/edit' : '/messages/'.$message->id)->with('success', $message->status === 'draft' ? 'Brouillon enregistré dans votre boîte.' : 'Message envoyé dans la messagerie interne.');
    }
    public function show(Request $request, InternalMessage $message): Response
    {
        $actor = $request->user(); $message = $this->access->messages($actor)->with('recipients')->findOrFail($message->id);
        $data = $this->summary($message, $actor); $history = []; $seen = [$message->id]; $cursor = $message;
        for ($i = 0; $i < 20 && $cursor->source_id; $i++) {
            if (in_array($cursor->source_id, $seen, true)) { break; }
            $parent = $this->access->messages($actor)->where('status', 'sent')->with('recipients')->find($cursor->source_id);
            if (! $parent || $this->summary($parent, $actor)['folder'] === 'trash') { break; }
            $history[] = $parent->only('id', 'subject', 'sender_name'); $seen[] = $parent->id; $cursor = $parent;
        }
        $meetingLink = $message->meeting_id && $actor->can('meetings.view') && $this->access->meetings($actor)->whereKey($message->meeting_id)->exists() ? '/meetings/'.$message->meeting_id : null;
        return Inertia::render('messages/show', ['message' => [...$data, 'body' => $message->body, 'history' => $history, 'meeting_link' => $meetingLink,
            'can_reply' => ! $data['is_sender'] && $message->kind === 'message' && $data['folder'] !== 'trash' && $this->access->usersWith('messages.use')->whereKey($message->sender_id)->exists()]]);
    }
    public function state(MessageStateRequest $request, InternalMessage $message, ManageMessages $action): RedirectResponse
    {
        $action->state($request->user(), $message, $request->validated('action'), (int) $request->validated('revision'));
        return back()->with('success', 'Votre boîte de messages a été mise à jour.');
    }
    private function summary(InternalMessage $m, User $actor): array
    {
        $sender = (int) $m->sender_id === $actor->id; $copy = $m->recipients->firstWhere('user_id', $actor->id);
        return [...$m->only('id', 'reference', 'subject', 'sender_name', 'status', 'kind', 'priority', 'category'),
            'sent_at' => $m->sent_at?->toIso8601String(), 'updated_at' => $m->updated_at->toIso8601String(),
            'preview' => Str::limit($m->body, 120), 'recipients' => $m->recipients->map(fn ($r) => ['name' => $r->name_snapshot]),
            'is_sender' => $sender, 'folder' => $sender ? $m->sender_folder : $copy->folder,
            'unread' => ! $sender && ! $copy->read_at, 'copy_revision' => $sender ? $m->sender_revision : $copy->revision];
    }
}
