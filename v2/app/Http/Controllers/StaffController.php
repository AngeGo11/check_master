<?php
namespace App\Http\Controllers;
use App\Actions\HumanResources\{ManageStaff, ManageStaffPhotos, ManageStaffCatalog};
use App\Http\Requests\{SaveStaffRequest, StaffChangeRequest, StaffCatalogRequest};
use App\Models\{StaffProfile, StaffReference, User};
use App\Services\{StaffAccess, StaffPhotos, CollaborationAccess};
use Illuminate\Http\{Request, RedirectResponse};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\{Inertia, Response};
class StaffController extends Controller
{
    public function __construct(private StaffAccess $access) {}
    public function index(Request $request): Response
    {
        abort_unless($this->access->canView($request->user()), 403);
        $filters = $request->validate(['kind' => ['nullable', Rule::in(['teacher', 'administrative'])], 'search' => ['nullable', 'string', 'max:150'],
            'archive' => ['nullable', Rule::in(['active', 'archived', 'all'])], 'account' => ['nullable', Rule::in(['linked', 'unlinked'])],
            'grade_id' => ['nullable', 'integer', Rule::exists('staff_references', 'id')->where('kind', 'grade')],
            'function_id' => ['nullable', 'integer', Rule::exists('staff_references', 'id')->where('kind', 'function')],
            'specialty_id' => ['nullable', 'integer', Rule::exists('staff_references', 'id')->where('kind', 'specialty')], 'page' => ['nullable', 'integer', 'min:1']]);
        $filters['kind'] ??= 'teacher'; $filters['archive'] ??= 'active'; $base = StaffProfile::where('kind', $filters['kind']);
        $statistics = ['total' => (clone $base)->count(), 'active' => (clone $base)->where('is_archived', false)->count(), 'archived' => (clone $base)->where('is_archived', true)->count(), 'linked' => (clone $base)->whereNotNull('user_id')->count()];
        $q = $base->with(['currentAssignments.reference', 'specialties'])->when($filters['archive'] !== 'all', fn ($q) => $q->where('is_archived', $filters['archive'] === 'archived'));
        if ($filters['account'] ?? null) { $filters['account'] === 'linked' ? $q->whereNotNull('user_id') : $q->whereNull('user_id'); }
        $search = trim($filters['search'] ?? '');
        $q->when($search !== '', fn ($q) => $q->where(fn ($q) => $q->where('last_name', 'like', '%'.$search.'%')->orWhere('first_name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')->orWhere('reference', 'like', '%'.$search.'%')));
        foreach (['grade', 'function'] as $kind) { if ($filters[$kind.'_id'] ?? null) { $q->whereHas('currentAssignments', fn ($q) => $q->where('kind', $kind)->where('staff_reference_id', $filters[$kind.'_id'])); } }
        if ($filters['specialty_id'] ?? null) { $q->whereHas('specialties', fn ($q) => $q->where('staff_references.id', $filters['specialty_id'])); }
        return Inertia::render('staff/index', ['profiles' => $q->orderBy('last_name')->orderBy('first_name')->orderBy('id')->paginate(20)->withQueryString()->through(fn ($p) => $this->summary($p)),
            'filters' => $filters, 'statistics' => $statistics, 'catalogs' => $this->catalogs(), 'canManage' => $this->access->canManage($request->user()), 'canCatalog' => $this->access->canCatalog($request->user())]);
    }
    public function create(Request $request): Response
    {
        abort_unless($this->access->canManage($request->user()), 403); $data = $request->validate(['kind' => ['nullable', Rule::in(['teacher', 'administrative'])]]);
        return Inertia::render('staff/form', ['profile' => null, 'kind' => $data['kind'] ?? 'teacher', 'catalogs' => $this->catalogs(), 'operationKey' => (string) Str::uuid()]);
    }
    public function edit(Request $request, StaffProfile $staff): Response
    {
        abort_unless($this->access->canManage($request->user()), 403); abort_if($staff->is_archived, 422);
        return Inertia::render('staff/form', ['profile' => [...$staff->only('id', 'kind', 'revision', ...StaffProfile::FIELDS), 'date_of_birth' => $staff->date_of_birth?->format('Y-m-d'), 'hired_on' => $staff->hired_on?->format('Y-m-d'), 'specialty_ids' => $staff->specialties()->pluck('staff_references.id')],
            'kind' => $staff->kind, 'catalogs' => $this->catalogs(), 'operationKey' => (string) Str::uuid()]);
    }
    public function store(SaveStaffRequest $request, ManageStaff $action): RedirectResponse { $p = $action->save($request->user(), $request->validated()); return to_route('staff.show', $p)->with('success', 'Fiche professionnelle créée.'); }
    public function update(SaveStaffRequest $request, StaffProfile $staff, ManageStaff $action): RedirectResponse { $action->save($request->user(), $request->validated(), $staff); return to_route('staff.show', $staff)->with('success', 'Fiche professionnelle mise à jour.'); }
    public function show(Request $request, StaffProfile $staff): Response { abort_unless($this->access->canView($request->user()), 403); return $this->detail($request, $staff, false); }
    public function own(Request $request): Response { return $this->detail($request, $this->access->own($request->user()), true); }
    private function detail(Request $request, ?StaffProfile $p, bool $personal): Response
    {
        $request->validate(['history_page' => ['nullable', 'integer', 'min:1']]);
        $actor = $request->user(); $canManage = ! $personal && $this->access->canManage($actor); $canLink = $canManage && $actor->can('users.manage');
        $profile = null; $account = null; $meetings = collect();
        if ($p) {
            $p->load(['currentAssignments.reference', 'specialties', 'user.types', 'user.groups.types', 'currentPhoto']);
            $linked = $p->user; $compatible = $linked && $this->access->compatible($linked, $p->kind);
            $roles = $linked && $compatible ? $linked->groups->filter(fn ($g) => $g->types->modelKeys() && array_intersect($g->types->modelKeys(), $linked->types->modelKeys()))->map(fn ($g) => $g->only('id', 'name'))->values() : collect();
            if ($canLink && $linked) { $account = [...$linked->only('id', 'name', 'email', 'is_active'), 'compatible' => (bool) $compatible, 'roles' => $roles]; }
            $profile = [...$this->summary($p), ...$p->only('revision', 'phone', 'gender'), 'date_of_birth' => $p->date_of_birth?->format('Y-m-d'), 'hired_on' => $p->hired_on?->format('Y-m-d'),
                'photo_url' => $p->currentPhoto ? ($personal ? '/my-staff-record/photo' : '/staff/'.$p->id.'/photo').'?v='.$p->currentPhoto->id : null,
                'assignments' => $p->assignments()->orderByDesc('id')->paginate(20, ['*'], 'history_page')->withQueryString()->through(fn ($a) => [
                    ...$a->only('id', 'kind', 'name_snapshot'), 'assigned_on' => $a->assigned_on->format('Y-m-d'), 'ended_on' => $a->ended_on?->format('Y-m-d'), 'current' => (bool) $a->current_slot]),
                'activity' => $personal ? [] : DB::table('staff_audits as a')->leftJoin('users as u', 'u.id', '=', 'a.actor_user_id')->where('a.staff_profile_id', $p->id)->orderByDesc('a.id')->limit(30)->get(['a.id', 'a.action', 'u.name as actor', 'a.created_at'])->map(fn ($a) => ['id' => $a->id, 'action' => $a->action, 'actor' => $a->actor, 'created_at' => \Carbon\CarbonImmutable::parse($a->created_at, 'UTC')->toIso8601String()])];
            if ($linked && $compatible && $actor->can('meetings.view')) {
                $meetings = app(CollaborationAccess::class)->meetings($actor)->whereNull('cancelled_at')->where('ends_at', '>', now())
                    ->whereHas('participants', fn ($q) => $q->where('user_id', $linked->id)->whereNull('removed_at'))->orderBy('starts_at')->limit(10)->get(['id', 'title', 'starts_at'])
                    ->map(fn ($m) => ['id' => $m->id, 'title' => $m->title, 'starts_at' => $m->starts_at->toIso8601String()]);
            }
        }
        return Inertia::render('staff/show', ['profile' => $profile, 'personal' => $personal, 'canManage' => $canManage, 'canLink' => $canLink, 'account' => $account,
            'catalogs' => $canManage ? $this->catalogs() : [], 'meetings' => $meetings, 'operationKey' => (string) Str::uuid()]);
    }
    public function archive(StaffChangeRequest $request, StaffProfile $staff, ManageStaff $action): RedirectResponse { $action->archive($request->user(), $staff, $request->validated()); return back()->with('success', $request->boolean('is_archived') ? 'Fiche archivée. Le compte et ses responsabilités restent gérés séparément.' : 'Fiche restaurée.'); }
    public function account(StaffChangeRequest $request, StaffProfile $staff, ManageStaff $action): RedirectResponse { $action->link($request->user(), $staff, $request->validated()); return back()->with('success', 'Rattachement du compte mis à jour.'); }
    public function assignment(StaffChangeRequest $request, StaffProfile $staff, ManageStaff $action): RedirectResponse { $action->assignment($request->user(), $staff, $request->validated()); return back()->with('success', 'Attribution enregistrée dans l’historique.'); }
    public function uploadPhoto(StaffChangeRequest $request, StaffProfile $staff, ManageStaffPhotos $action): RedirectResponse { $action->upload($request->user(), $staff, $request->safe()->except('photo'), $request->file('photo')); return back()->with('success', 'Photo mise à jour.'); }
    public function removePhoto(StaffChangeRequest $request, StaffProfile $staff, ManageStaffPhotos $action): RedirectResponse { $action->remove($request->user(), $staff, (int) $request->validated('revision')); return back()->with('success', 'Photo retirée.'); }
    public function photo(Request $request, StaffProfile $staff, StaffPhotos $photos) { abort_unless($this->access->canView($request->user()), 403); return $photos->show($staff->currentPhoto()->firstOrFail()); }
    public function ownPhoto(Request $request, StaffPhotos $photos) { $p = $this->access->own($request->user()); abort_unless($p, 404); return $photos->show($p->currentPhoto()->firstOrFail()); }
    public function referenceIndex(Request $request): Response
    {
        abort_unless($this->access->canView($request->user()), 403);
        return Inertia::render('staff/catalogs', ['catalogs' => $this->catalogs(), 'canManage' => $this->access->canCatalog($request->user())]);
    }
    public function referenceStore(StaffCatalogRequest $request, ManageStaffCatalog $action): RedirectResponse { $action->save($request->user(), $request->validated()); return back()->with('success', 'Valeur ajoutée au catalogue.'); }
    public function referenceUpdate(StaffCatalogRequest $request, StaffReference $reference, ManageStaffCatalog $action): RedirectResponse { $action->save($request->user(), $request->validated(), $reference); return back()->with('success', 'Catalogue mis à jour.'); }
    private function catalogs() { return StaffReference::orderBy('kind')->orderBy('name')->get(['id', 'kind', 'name', 'is_active', 'revision']); }
    private function summary(StaffProfile $p): array
    {
        return [...$p->only('id', 'reference', 'kind', 'last_name', 'first_name', 'email', 'position', 'is_archived'), 'linked' => $p->user_id !== null,
            'current_assignments' => $p->currentAssignments->map(fn ($a) => ['id' => $a->id, 'kind' => $a->kind, 'name' => $a->reference->name, 'since' => $a->assigned_on->format('Y-m-d')]),
            'specialties' => $p->specialties->map(fn ($r) => $r->only('id', 'name', 'is_active'))];
    }
}
