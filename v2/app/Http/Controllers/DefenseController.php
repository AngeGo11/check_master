<?php
namespace App\Http\Controllers;
use App\Actions\Academic\ManageDefenses;
use App\Http\Requests\DefenseRequest;
use App\Models\{Defense, Report};
use App\Services\{DefenseAccess, ReportFiles};
use Illuminate\Http\Request;
use Illuminate\Support\{Str, Facades\DB};
use Illuminate\Validation\Rule;
use Inertia\Inertia;
class DefenseController extends Controller
{
    public function index(Request $r)
    {
        $a = app(DefenseAccess::class); $q = $a->visible($r->user());
        $f = $r->validate(['status' => ['nullable', Rule::in(['requested', 'scheduled', 'completed', 'cancelled'])], 'search' => ['nullable', 'string', 'max:150'], 'page' => ['nullable', 'integer', 'min:1']]);
        $stats = []; foreach (['requested', 'scheduled', 'completed', 'cancelled'] as $status) { $stats[$status] = (clone $q)->where('status', $status)->count(); }
        if ($f['status'] ?? null) { $q->where('status', $f['status']); }
        if ($search = trim($f['search'] ?? '')) { $q->where(fn ($q) => $q->where('reference', 'like', '%'.$search.'%')->orWhereHas('student', fn ($q) => $q->where('last_name', 'like', '%'.$search.'%')->orWhere('first_name', 'like', '%'.$search.'%')->orWhere('card_number', 'like', '%'.$search.'%'))); }
        $ownReport = $a->own($r->user()) ? Report::whereHas('student', fn ($q) => $q->where('user_id', $r->user()->id))->first() : null;
        $candidate = $ownReport && ! Defense::where('report_id', $ownReport->id)->exists() ? [...$ownReport->only('id', 'title', 'revision'), 'readiness' => $a->readiness($ownReport)] : null;
        return Inertia::render('defenses/index', ['defenses' => $q->with(['student', 'version', 'enrollment.academicYear'])->orderByDesc('requested_at')->orderByDesc('id')->paginate(20)->withQueryString()->through(fn ($d) => $this->summary($d)), 'statistics' => $stats, 'filters' => $f, 'candidate' => $candidate, 'studentSpace' => $a->own($r->user()), 'canManage' => $a->canManage($r->user()), 'operationKey' => (string) Str::uuid()]);
    }
    public function show(Request $r, Defense $defense)
    {
        $a = app(DefenseAccess::class); $d = $a->visible($r->user())->whereKey($defense->id)->firstOrFail(); $manager = $a->canManage($r->user()) && (int) $d->student->user_id !== $r->user()->id;
        return Inertia::render('defenses/show', ['defense' => [...$this->summary($d), ...$d->only('revision', 'instructions', 'outcome', 'result_comment'), 'score' => $d->score === null ? null : $d->score / 100, 'ends_at' => $d->ends_at?->toIso8601String(), 'result_at' => $d->result_at?->toIso8601String(), 'jurors' => $d->currentJurors()->get(['user_id', 'name_snapshot', 'role'])], 'canManage' => $manager, 'canAssess' => $a->canAssess($r->user(), $d), 'canWithdraw' => $a->own($r->user()) && (int) $d->student->user_id === $r->user()->id && $d->status === 'requested', 'candidates' => $manager ? $a->jurors()->orderBy('name')->get(['id', 'name']) : [], 'readiness' => $a->readiness($d->report),
            'activity' => DB::table('academic_audits as a')->join('users as u', 'a.actor_user_id', '=', 'u.id')->where('a.defense_id', $d->id)->orderByDesc('a.id')->limit(30)->get(['a.id', 'a.action', 'u.name as actor', 'a.created_at', 'a.metadata'])->map(fn ($row) => ['id' => $row->id, 'action' => $row->action, 'actor' => $row->actor, 'created_at' => $row->created_at, 'reason' => json_decode($row->metadata, true)['reason'] ?? null])]);
    }
    public function store(DefenseRequest $r, ManageDefenses $a) { $d = $a->request($r->user(), $r->validated()); return to_route('defenses.show', $d)->with('success', 'Demande de soutenance enregistrée.'); }
    public function schedule(DefenseRequest $r, Defense $defense, ManageDefenses $a) { $a->schedule($r->user(), $defense, $r->validated()); return back()->with('success', 'Soutenance planifiée. La convocation est consultable par l’étudiant et son jury.'); }
    public function cancel(DefenseRequest $r, Defense $defense, ManageDefenses $a) { $a->cancel($r->user(), $defense, $r->validated()); return back()->with('success', 'Soutenance annulée ; son historique est conservé.'); }
    public function result(DefenseRequest $r, Defense $defense, ManageDefenses $a) { $a->result($r->user(), $defense, $r->validated()); return back()->with('success', 'Résultat du jury enregistré dans le suivi de la soutenance.'); }
    public function report(Request $r, Defense $defense, ReportFiles $files) { $d = app(DefenseAccess::class)->visible($r->user())->whereKey($defense->id)->firstOrFail(); return $files->download($d->version); }
    private function summary(Defense $d): array { return [...$d->only('id', 'reference', 'status', 'location'), 'student' => $d->student->last_name.' '.$d->student->first_name, 'card_number' => $d->student->card_number, 'title' => $d->version->title, 'year' => $d->enrollment->academicYear->name, 'requested_at' => $d->requested_at->toIso8601String(), 'starts_at' => $d->starts_at?->toIso8601String()]; }
}
