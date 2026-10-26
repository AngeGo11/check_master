<?php

namespace App\Services;

use App\Models\{AcademicYear, Defense, Enrollment, GradeSheet, Report, StaffProfile, Student, StudentClaim, User};
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RoleDashboard
{
    // Explicit precedence only chooses the initial view; a multi-group user can switch views.
    private const SPACES = [
        'platform-administrator' => ['Administration', 'Supervisez les comptes, les accès et l’activité de la plateforme.'],
        'schooling' => ['Scolarité', 'Traitez les inscriptions, les règlements, les bulletins et les soutenances.'],
        'communications' => ['Communication', 'Suivez les dépôts et transmettez les mémoires à la commission.'],
        'validation-committee' => ['Commission de validation', 'Retrouvez vos avis à rendre et vos prochaines séances.'],
        'program-manager' => ['Responsable de filière', 'Suivez les soutenances auxquelles vous êtes affecté et les résultats de vos jurys.'],
        'level-manager' => ['Responsable de niveau', 'Retrouvez les décisions de jury à consigner et les séances qui vous concernent.'],
        'teacher' => ['Enseignant', 'Préparez vos prochains jurys et consultez votre fiche professionnelle.'],
        'secretary' => ['Secrétariat', 'Retrouvez les dossiers étudiants et les demandes reçues dans votre messagerie.'],
        'student' => ['Mon parcours étudiant', 'Suivez votre scolarité, votre stage, vos notes et votre mémoire.'],
    ];
    private const REPORT_LABELS = ['submitted' => 'À transmettre', 'under_review' => 'En commission', 'changes_requested' => 'Correction demandée', 'accepted' => 'Accepté à l’unanimité'];
    private const DEFENSE_LABELS = ['requested' => 'Demande déposée', 'scheduled' => 'Planifiée', 'completed' => 'Résultat enregistré', 'cancelled' => 'Annulée'];
    private const PAID = '(select coalesce(sum(amount), 0) from tuition_payments where tuition_payments.enrollment_id = enrollments.id and voided_at is null)';
    private User $user;
    private array $permissions = [];
    private array $data = [];

    public function build(User $user, ?string $selected = null): array
    {
        $this->user = $user->fresh();
        $this->permissions = $this->user->permissionCodes();
        $this->user->load(['types', 'groups.types']);
        $compatible = $this->user->groups->filter(fn ($g) => array_intersect($g->types->modelKeys(), $this->user->types->modelKeys()))->pluck('code')->all();
        $spaces = [];
        foreach (self::SPACES as $key => [$label]) { if (in_array($key, $compatible, true)) { $spaces[] = ['key' => $key, 'label' => $label]; } }
        if (! $spaces) { $spaces[] = ['key' => 'personal', 'label' => 'Mon espace']; }
        $space = $selected ?? $spaces[0]['key'];
        abort_unless(in_array($space, array_column($spaces, 'key'), true), 403);
        [$title, $description] = self::SPACES[$space] ?? ['Mon espace', 'Retrouvez votre profil et les services ouverts à votre compte.'];
        $this->data = ['space' => $space, 'spaces' => $spaces, 'title' => $title, 'description' => $description, 'cards' => [], 'actions' => [], 'panels' => [], 'journey' => null];
        match ($space) {
            'platform-administrator' => $this->administrator(),
            'schooling' => $this->schooling(),
            'communications' => $this->communications(),
            'validation-committee' => $this->committee(),
            'student' => $this->student(),
            'secretary' => $this->secretary(),
            'teacher', 'level-manager', 'program-manager' => $this->teaching($space),
            default => null,
        };
        $this->progression($space);
        $this->claims($space);
        $this->mailbox();
        $this->action('profile', 'Mon profil', '/profile', 'user');
        return $this->data;
    }

    private function can(string $permission): bool { return in_array($permission, $this->permissions, true); }
    private function card(string $key, string $label, int|string $value, string $caption, ?string $href = null, string $icon = 'document', string $tone = 'blue'): void
    {
        $this->data['cards'][$key] = compact('key', 'label', 'value', 'caption', 'href', 'icon', 'tone');
    }
    private function action(string $key, string $label, string $href, string $icon = 'document'): void
    {
        $this->data['actions'][$key] = compact('key', 'label', 'href', 'icon');
    }
    private function panel(string $key, string $title, string $description, array $items, string $empty, ?string $href = null, string $kind = 'list'): void
    {
        $this->data['panels'][$key] = compact('key', 'title', 'description', 'items', 'empty', 'href', 'kind');
    }
    private function item(int|string $id, string $title, string $detail, ?string $href = null, ?string $date = null, int|string|null $value = null): array
    {
        return compact('id', 'title', 'detail', 'href', 'date', 'value');
    }

    private function progression(string $space): void
    {
        $access = app(ProgressionAccess::class); $personal = $space === 'student';
        if ($personal ? ! $access->personal($this->user) : (! in_array($space, ['schooling', 'platform-administrator'], true) || ! $access->view($this->user))) { return; }
        $path = $personal ? '/my-progression' : '/progression'; $q = $access->query($this->user, $personal);
        $this->action('progression', $personal ? 'Mon parcours annuel' : 'Bilans annuels et rattrapages', $path, 'graduate');
        $pending = (clone $q)->whereHas('retakes', fn ($q) => $q->where('status', '!=', 'validated'))->whereHas('academicYear', fn ($q) => $q->where('status', 'current'))->whereNull('cancelled_at');
        $this->card('retakes', $personal ? 'Mes inscriptions avec rattrapage' : 'Inscriptions avec rattrapage', $pending->count(), 'Année en cours · matières restant à valider', $path, 'graduate', 'orange');
        $recent = $q->with(['student', 'academicYear', 'latestAnnualDecision'])->whereHas('annualDecisions')->orderByDesc('academic_year_id')->orderByDesc('id')->limit(5)->get();
        $this->panel('progression', 'Décisions annuelles', 'Dernières années disposant d’une décision publiée.', $recent->map(fn ($e) => $this->item($e->id, $personal ? $e->academicYear->name : $e->student->last_name.' '.$e->student->first_name,
            \App\Models\AnnualDecision::OUTCOMES[$e->latestAnnualDecision->outcome], $path.'/'.$e->id, $e->latestAnnualDecision->created_at->toIso8601String()))->all(), 'Aucune décision publiée.', $path);
    }

    private function claims(string $space): void
    {
        $access = app(ClaimAccess::class); $scope = null; $path = '/claims';
        if ($space === 'student' && $access->personal($this->user)) { $scope = $access->query($this->user, 'personal'); $path = '/my-claims'; }
        if (in_array($space, ['platform-administrator', 'schooling'], true) && $access->global($this->user)) { $scope = $access->query($this->user, 'service'); }
        if ($space === 'program-manager' && $access->assigned($this->user)) { $scope = $access->query($this->user, 'assigned'); $path = '/claims?space=assigned'; }
        if (! $scope) { return; }
        $open = (clone $scope)->whereIn('status', StudentClaim::OPEN);
        $this->card('claims', $space === 'student' ? 'Mes réclamations en cours' : 'Réclamations en cours', (clone $open)->count(), $space === 'program-manager' ? 'Dossiers qui vous sont transmis' : 'Toutes les années', $path, 'document', 'orange');
        $rows = $space === 'student' ? $scope->orderByDesc('updated_at')->orderByDesc('id') : $open->oldest('created_at')->orderBy('id');
        $this->panel('claims', $space === 'student' ? 'Suivi de mes réclamations' : 'Réclamations à suivre', $space === 'student' ? 'Dernières demandes et réponses.' : 'Les demandes les plus anciennes dans votre périmètre.',
            $rows->limit(5)->get()->map(fn ($c) => $this->item($c->id, $c->subject, StudentClaim::STATUSES[$c->status], ($space === 'student' ? '/my-claims/' : '/claims/').$c->id, $c->updated_at->toIso8601String()))->all(),
            'Aucune réclamation à afficher.', $path);
        $this->action('claims', $space === 'student' ? 'Mes réclamations' : 'Suivre les réclamations', $path);
    }

    private function administrator(): void
    {
        if ($this->can('audit.view')) { $this->action('audit', 'Consulter la piste d’audit', '/audit', 'clock'); }
        if ($this->can('users.view')) {
            $this->card('users', 'Comptes actifs', User::where('is_active', true)->count(), 'Tous les groupes', '/settings/users', 'users');
            $groups = DB::table('group_user as m')->join('user_groups as g', 'g.id', '=', 'm.user_group_id')
                ->join('users as u', 'u.id', '=', 'm.user_id')->join('user_group_user_type as c', 'c.user_group_id', '=', 'g.id')
                ->join('type_user as t', fn ($j) => $j->on('t.user_id', '=', 'u.id')->on('t.user_type_id', '=', 'c.user_type_id'))
                ->where('u.is_active', true)->groupBy('g.id', 'g.name')->orderBy('g.id')
                ->selectRaw('g.id, g.name, count(distinct u.id) as total')->get();
            $this->panel('groups', 'Comptes par groupe', 'Un compte présent dans plusieurs groupes est compté dans chacun. Seuls les rattachements compatibles sont inclus.',
                $groups->map(fn ($g) => $this->item($g->id, $g->name, 'Comptes actifs', value: (int) $g->total))->all(), 'Aucun compte actif rattaché.', '/settings/users', 'distribution');
            $this->panel('accounts', 'Suivi des accès', 'État actuel des comptes.',
                [$this->item('inactive', 'Comptes désactivés', 'Accès à la plateforme suspendu', value: User::where('is_active', false)->count()),
                 $this->item('password', 'Mots de passe à personnaliser', 'Comptes actifs avec mot de passe temporaire', value: User::where('is_active', true)->where('must_change_password', true)->count())],
                '', '/settings/users');
            $this->action('users', $this->can('users.manage') ? 'Gérer les comptes' : 'Consulter les comptes', '/settings/users', 'users');
        }
        if ($this->can('groups.view')) { $this->action('groups', 'Groupes et permissions', '/settings/groups', 'lock'); }
        $this->studentsOverview();
        if (app(StaffAccess::class)->canView($this->user)) {
            $this->card('teachers', 'Enseignants', StaffProfile::where('kind', 'teacher')->where('is_archived', false)->count(), 'Fiches non archivées', '/staff', 'users', 'green');
            $this->action('staff', 'Ressources humaines', '/staff', 'users');
        }
        if ($this->can('reports.view')) { $this->reportsOverview(Report::query(), 'Mémoires récents'); }
        if (app(DefenseAccess::class)->canView($this->user)) { $this->defenseAgenda(Defense::query(), 'Soutenances à venir'); }
        $this->meetings(true);
    }

    private function studentsOverview(bool $recent = false): void
    {
        if (! $this->can('students.view')) { return; }
        $students = Student::where('is_archived', false);
        $this->card('students', 'Dossiers étudiants', (clone $students)->count(), 'Dossiers non archivés', '/students', 'graduate');
        $levels = (clone $students)->join('study_levels as l', 'l.id', '=', 'students.study_level_id')
            ->groupBy('l.id', 'l.name')->orderBy('l.id')->selectRaw('l.id, l.name, count(*) as total')->get();
        $this->panel('levels', 'Effectifs par niveau', 'Situation actuelle des dossiers non archivés.',
            $levels->map(fn ($l) => $this->item($l->id, $l->name, 'Étudiants', value: (int) $l->total))->all(), 'Aucun dossier étudiant.', '/students', 'distribution');
        if ($recent) {
            $rows = (clone $students)->with('studyLevel')->latest('id')->limit(5)->get();
            $this->panel('students', 'Derniers dossiers enregistrés', 'Retrouver un dossier pour renseigner un étudiant.',
                $rows->map(fn ($s) => $this->item($s->id, $s->last_name.' '.$s->first_name, $s->card_number.' · '.$s->studyLevel->name, '/students/'.$s->id))->all(),
                'Aucun dossier enregistré.', '/students');
        }
        $this->action('students', 'Consulter les étudiants', '/students', 'graduate');
    }

    private function schooling(): void
    {
        $year = AcademicYear::where('status', 'current')->first();
        if ($this->can('enrollments.view')) {
            $scope = Enrollment::where('academic_year_id', $year?->id)->whereNull('cancelled_at');
            $caption = $year?->name ?? 'Aucune année en cours';
            $this->card('enrollments', 'Inscriptions actives', (clone $scope)->count(), $caption, '/enrollments', 'graduate');
            $due = (clone $scope)->whereRaw(self::PAID.' < amount_due')->whereHas('student', fn ($q) => $q->where('is_archived', false));
            $this->card('tuition_due', 'Scolarités à régulariser', (clone $due)->count(), $caption.' · hors dossiers archivés', '/enrollments', 'clock', 'orange');
            $this->panel('tuition', 'Règlements à suivre', 'Premières inscriptions avec un solde restant ; paiements annulés exclus.',
                $due->with('student')->withPaidAmount()->oldest('enrolled_on')->orderBy('id')->limit(5)->get()->map(fn ($e) =>
                    $this->item($e->id, $e->student->last_name.' '.$e->student->first_name, $e->reference, '/enrollments/'.$e->id, value: $this->money(max(0, $e->amount_due - $e->paidAmount()))))->all(),
                $year ? 'Aucune scolarité à régulariser.' : 'Configurez une année en cours pour suivre les inscriptions.', '/enrollments');
            $this->action('enrollments', $this->can('enrollments.manage') ? 'Gérer les inscriptions' : 'Consulter les inscriptions', '/enrollments', 'graduate');
        }
        if (app(AcademicAccess::class)->canView($this->user)) {
            $sheets = GradeSheet::where('status', 'draft')->whereHas('enrollment', fn ($q) => $q->where('academic_year_id', $year?->id)->whereNull('cancelled_at')->whereHas('student', fn ($s) => $s->where('is_archived', false)));
            $this->card('grade_drafts', 'Bulletins en préparation', (clone $sheets)->count(), $year?->name ?? 'Aucune année en cours', '/grades', 'document');
            $this->panel('grade_drafts', 'Bulletins à finaliser', 'Brouillons et corrections de l’année en cours.',
                $sheets->with(['enrollment.student', 'semester'])->oldest('updated_at')->orderBy('id')->limit(5)->get()->map(fn ($s) =>
                    $this->item($s->id, $s->enrollment->student->last_name.' '.$s->enrollment->student->first_name, $s->semester->name, '/grades/'.$s->id))->all(),
                'Aucun bulletin en préparation.', '/grades');
            $this->action('grades', app(AcademicAccess::class)->canManage($this->user) ? 'Saisir les notes' : 'Consulter les bulletins', '/grades');
        }
        if (app(DefenseAccess::class)->canView($this->user)) {
            $pending = Defense::where('status', 'requested');
            $this->card('defenses_requested', 'Soutenances à planifier', (clone $pending)->count(), 'Demandes déposées · toutes les années', '/defenses?status=requested', 'calendar', 'orange');
            $this->defenseList('defenses_requested', 'Demandes de soutenance', 'Traitez les demandes les plus anciennes.', $pending->oldest('requested_at')->orderBy('id'), '/defenses?status=requested');
            $this->defenseAgenda(Defense::query(), 'Planning des soutenances');
            $this->action('defenses', app(DefenseAccess::class)->canManage($this->user) ? 'Planifier les soutenances' : 'Consulter les soutenances', '/defenses', 'calendar');
        }
        if ($this->can('schooling.view')) { $this->action('schooling', 'Années et tarifs', '/settings/schooling', 'settings'); }
    }

    private function reportsOverview(Builder $scope, string $title): void
    {
        $this->card('reports', 'Mémoires accessibles', (clone $scope)->count(), 'Toutes les années', '/reports', 'document');
        $this->card('reports_pending', 'Mémoires en attente', (clone $scope)->whereIn('status', ['submitted', 'under_review'])->count(), 'À transmettre ou en commission', '/reports', 'clock', 'orange');
        $this->reportList('reports', $title, 'Dernières versions accessibles à votre compte.', (clone $scope)->orderByDesc('latest_submitted_at')->orderByDesc('id'));
    }

    private function reportList(string $key, string $title, string $description, Builder $query): void
    {
        $this->panel($key, $title, $description,
            $query->limit(5)->get()->map(fn ($r) => $this->item($r->id, $r->title, $r->reference.' · '.self::REPORT_LABELS[$r->status], '/reports/'.$r->id, $r->latest_submitted_at->toIso8601String()))->all(),
            'Aucun mémoire dans cette sélection.', '/reports');
    }

    private function communications(): void
    {
        if ($this->can('reports.view')) {
            foreach (['submitted' => ['to_forward', 'À transmettre'], 'under_review' => ['under_review', 'En commission'], 'changes_requested' => ['corrections', 'Corrections demandées'], 'accepted' => ['accepted', 'Acceptés à l’unanimité']] as $status => [$key, $label]) {
                $this->card($key, $label, Report::where('status', $status)->count(), 'Mémoires · toutes les années', '/reports?status='.$status, $status === 'submitted' ? 'clock' : 'document', $status === 'submitted' ? 'orange' : 'blue');
            }
            $this->reportList('to_forward', 'Dépôts à transmettre', 'La transmission ouvre l’examen par tous les membres de la commission.', Report::where('status', 'submitted')->oldest('latest_submitted_at')->orderBy('id'));
            $this->reportList('reports', 'Suivi des mémoires', 'Retrouvez les derniers dépôts et corrections.', Report::orderByDesc('latest_submitted_at')->orderByDesc('id'));
            $this->action('reports', app(ReportAccess::class)->canForward($this->user) ? 'Transmettre les mémoires' : 'Consulter les mémoires', '/reports');
        }
        if ($this->can('students.view')) { $this->action('students', 'Annuaire étudiant', '/students', 'graduate'); }
    }

    private function committee(): void
    {
        $access = app(ReportAccess::class);
        if ($access->canReadAssigned($this->user)) {
            // Keep the selected commission view scoped even for an administrator who is also a member.
            $assigned = Report::whereHas('latestVersion.rounds.members', fn ($q) => $q->where('user_id', $this->user->id));
            $this->reportsOverview($assigned, 'Mémoires reçus en commission');
            if ($access->canVote($this->user)) {
                $pending = (clone $assigned)->where('status', 'under_review')
                    ->whereHas('student', fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $this->user->id))
                    ->whereHas('latestVersion', fn ($q) => $q->where('submitted_by', '!=', $this->user->id)->whereHas('latestRound', fn ($q) => $q->where('status', 'open')
                        ->whereHas('members', fn ($q) => $q->where('user_id', $this->user->id)->whereDoesntHave('vote'))));
                $this->card('votes_due', 'Mes avis à rendre', (clone $pending)->count(), 'Transmission actuelle · sans avis déjà enregistré', '/reports', 'check', 'orange');
                $this->reportList('votes_due', 'Votre avis est attendu', 'Chaque membre rend son propre avis. L’acceptation exige l’unanimité.', $pending->oldest('latest_submitted_at')->orderBy('id'));
            }
            $this->action('reports', 'Examiner mes mémoires', '/reports');
        }
        $this->meetings();
        $this->jury();
    }

    private function secretary(): void
    {
        $this->studentsOverview(true);
        if ($this->can('students.view')) {
            $this->card('unlinked_students', 'Dossiers sans compte', Student::where('is_archived', false)->whereNull('user_id')->count(), 'Rattachement à faire vérifier par un gestionnaire habilité', '/students', 'user', 'orange');
        }
        $this->professionalProfile();
    }

    private function teaching(string $space): void
    {
        $this->jury($space);
        $this->professionalProfile();
    }

    private function jury(string $space = 'validation-committee'): void
    {
        if (! app(DefenseAccess::class)->assigned($this->user)) { return; }
        $scope = Defense::whereHas('currentJurors', fn ($q) => $q->where('user_id', $this->user->id));
        $this->card('jury_upcoming', 'Mes jurys à venir', (clone $scope)->where('status', 'scheduled')->where('ends_at', '>', now())->count(), 'Uniquement vos affectations actuelles', '/defenses', 'calendar');
        $this->card('jury_completed', 'Mes jurys terminés', (clone $scope)->where('status', 'completed')->count(), 'Résultat consigné', '/defenses?status=completed', 'check', 'green');
        if ($this->can('defenses.assess')) {
            $due = (clone $scope)->where('status', 'scheduled')->where('ends_at', '<=', now())->whereHas('student', fn ($q) => $q->whereNull('user_id')->orWhere('user_id', '!=', $this->user->id))
                ->whereHas('currentJurors', fn ($q) => $q->where('user_id', $this->user->id)->where('role', 'president'));
            $this->card('results_due', 'Résultats à consigner', (clone $due)->count(), 'Séances terminées que vous présidez', '/defenses', 'document', 'orange');
            $this->defenseList('results_due', $space === 'level-manager' ? 'Décisions de jury à finaliser' : 'Résultats attendus de votre présidence', 'La saisie est réservée au président après la fin du créneau.', $due->orderBy('ends_at')->orderBy('id'));
        }
        $this->defenseAgenda($scope, $space === 'teacher' ? 'Préparer mes prochains jurys' : 'Mes prochaines soutenances');
        if ($space === 'program-manager') {
            $items = []; foreach (['validated' => 'Validées', 'adjourned' => 'Ajournées', 'absent' => 'Absences'] as $outcome => $label) {
                $items[] = $this->item($outcome, $label, 'Jurys auxquels vous êtes affecté', value: (clone $scope)->where('status', 'completed')->where('outcome', $outcome)->count());
            }
            $this->panel('jury_outcomes', 'Bilan de mes jurys', 'Toutes les années · uniquement les soutenances de vos affectations.', $items, '', '/defenses?status=completed', 'distribution');
        }
        $this->action('jury', 'Consulter mes jurys', '/defenses', 'calendar');
    }

    private function defenseAgenda(Builder $scope, string $title): void
    {
        $this->defenseList('defenses', $title, 'Horaires d’Abidjan · séances en cours ou à venir.', (clone $scope)->where('status', 'scheduled')->where('ends_at', '>', now())->orderBy('starts_at')->orderBy('id'));
    }
    private function defenseList(string $key, string $title, string $description, Builder $query, string $href = '/defenses'): void
    {
        $this->panel($key, $title, $description, $query->with(['student', 'version'])->limit(5)->get()->map(fn ($d) =>
            $this->item($d->id, $d->student->last_name.' '.$d->student->first_name, $d->version->title.' · '.self::DEFENSE_LABELS[$d->status].($d->location ? ' · '.$d->location : ''), '/defenses/'.$d->id, $d->starts_at?->toIso8601String()))->all(),
            'Aucune soutenance dans cette sélection.', $href);
    }

    private function meetings(bool $global = false): void
    {
        if (! $this->can('meetings.view')) { return; }
        if (! $global && ! $this->user->belongsToCompatibleGroup('validation-committee')) { return; }
        $q = app(CollaborationAccess::class)->meetings($this->user);
        if (! $global) { $q->where(fn ($q) => $q->where('created_by', $this->user->id)->orWhereHas('participants', fn ($p) => $p->where('user_id', $this->user->id)->whereNull('removed_at'))); }
        $q->whereNull('cancelled_at')->where('ends_at', '>', now());
        $pending = (clone $q)->where('starts_at', '>', now())->whereHas('participants', fn ($p) => $p->where('user_id', $this->user->id)->whereNull('removed_at')->where('response', 'pending'));
        $this->card('meeting_replies', 'Invitations à confirmer', $pending->count(), 'Vos réponses personnelles', '/meetings', 'calendar', 'orange');
        $this->panel('meetings', $global ? 'Réunions à venir' : 'Mes prochaines réunions', 'Horaires d’Abidjan · invitations accessibles à votre compte.',
            $q->orderBy('starts_at')->orderBy('id')->limit(5)->get()->map(fn ($m) => $this->item($m->id, $m->title, $m->location, '/meetings/'.$m->id, $m->starts_at->toIso8601String()))->all(),
            'Aucune réunion à venir.', '/meetings');
        $this->action('meetings', app(CollaborationAccess::class)->canOrganize($this->user) ? 'Organiser une réunion' : 'Consulter les réunions', app(CollaborationAccess::class)->canOrganize($this->user) ? '/meetings/create' : '/meetings', 'calendar');
    }

    private function professionalProfile(): void
    {
        if (! $this->can('hr.self') || ! $this->user->types->contains(fn ($t) => in_array($t->id, [2, 3, 4], true))) { return; }
        $p = app(StaffAccess::class)->own($this->user);
        $this->panel('professional_profile', 'Ma fiche professionnelle', 'Vos attributions et informations professionnelles.',
            $p ? [$this->item($p->id, $p->last_name.' '.$p->first_name, $p->is_archived ? 'Fiche archivée' : ($p->position ?: 'Fiche rattachée à votre compte'), '/my-staff-record')] : [],
            'Aucune fiche rattachée à votre compte. Contactez l’administration pour faire vérifier le rattachement.', '/my-staff-record');
        $this->action('professional_profile', 'Ma fiche professionnelle', '/my-staff-record', 'user');
    }

    private function mailbox(): void
    {
        if (! $this->can('messages.use')) { return; }
        $access = app(CollaborationAccess::class);
        $q = $access->messages($this->user)->where('status', 'sent')->whereHas('recipients', fn ($q) => $q->where('user_id', $this->user->id)->where('folder', 'inbox')->whereNull('read_at'));
        $this->card('unread', 'Messages non lus', (clone $q)->count(), 'Votre boîte de réception', '/messages', 'mail');
        $this->panel('messages', 'Messages à lire', 'Votre boîte personnelle, quel que soit l’espace sélectionné.',
            $q->orderByDesc('id')->limit(5)->get()->map(fn ($m) => $this->item($m->id, $m->subject, 'Non lu', '/messages/'.$m->id))->all(),
            'Votre boîte de réception est à jour.', '/messages');
        $this->action('messages', 'Ouvrir la messagerie', '/messages', 'mail');
    }

    private function student(): void
    {
        $own = fn ($q) => $q->where('user_id', $this->user->id);
        $e = null; $eligibility = null;
        if ($this->can('students.self')) {
            $student = Student::where('user_id', $this->user->id)->with('studyLevel')->first();
            $e = Enrollment::whereHas('student', $own)->whereNull('cancelled_at')->whereHas('academicYear', fn ($q) => $q->where('status', 'current'))->with(['student', 'academicYear', 'internship'])->withPaidAmount()->latest('id')->first();
            $this->panel('student_record', 'Mon dossier étudiant', 'Votre dossier explicitement rattaché au compte.',
                $student ? [$this->item($student->id, $student->last_name.' '.$student->first_name, $student->card_number.' · '.$student->studyLevel->name.($student->is_archived ? ' · Archivé' : ''), '/my-student-record')] : [],
                'Aucun dossier rattaché. Contactez la scolarité pour faire vérifier votre compte.', '/my-student-record');
            if ($e) {
                $eligibility = app(Eligibility::class)->evaluate($e);
                $this->card('tuition', 'Reste à payer', $this->money(max(0, $e->amount_due - $e->paidAmount())), $e->academicYear->name, '/my-enrollments/'.$e->id, 'graduate');
                $this->card('internship', 'Mon stage', $e->internship ? 'Déclaré' : 'À déclarer', $e->academicYear->name, '/my-enrollments/'.$e->id, 'document');
            } else {
                $this->panel('enrollment', 'Mon inscription', 'Année en cours.', [], 'Aucune inscription active pour l’année en cours. Contactez la scolarité.', '/my-enrollments');
            }
            $this->action('enrollment', $this->can('internships.self') ? 'Mon inscription et mon stage' : 'Mes inscriptions', '/my-enrollments', 'graduate');
        }
        $report = $this->can('reports.self') ? Report::whereHas('student', $own)->first() : null;
        if ($this->can('reports.self')) {
            $this->card('my_report', 'Mon mémoire', $report ? self::REPORT_LABELS[$report->status] : 'Non déposé', 'Votre dépôt et les avis de la commission', '/my-reports');
            $this->panel('my_report', 'Suivi de mon mémoire', 'La communication transmet votre dépôt ; chaque membre de commission doit l’approuver.',
                $report ? [$this->item($report->id, $report->title, $report->reference.' · '.self::REPORT_LABELS[$report->status], '/my-reports')] : [], 'Aucun mémoire déposé.', '/my-reports');
            $canDeposit = $eligibility && $eligibility['computed_result'] === 'eligible' && (! $report || $report->status === 'changes_requested');
            $this->action('report', $canDeposit ? ($report ? 'Déposer ma correction' : 'Déposer mon mémoire') : 'Suivre mon mémoire', '/my-reports');
            if ($eligibility) {
                $this->data['journey'] = ['title' => 'Préparer mon dépôt', 'eligible' => $eligibility['computed_result'] === 'eligible', 'context_valid' => $eligibility['context_valid'],
                    'criteria' => array_map(fn ($c) => ['key' => $c['key'], 'label' => $c['label'], 'met' => $c['met']], $eligibility['criteria']),
                    'description' => $canDeposit ? 'Les critères sont réunis : vous pouvez déposer votre document immédiatement.' : ($report ? 'Retrouvez la prochaine étape dans le suivi de votre mémoire.' : 'Complétez les critères de votre inscription pour pouvoir déposer.'),
                    'href' => '/my-reports', 'label' => $canDeposit ? 'Déposer mon document' : 'Consulter mon mémoire'];
            }
        }
        if ($this->can('grades.self')) {
            $sheets = GradeSheet::whereHas('enrollment.student', $own)->whereHas('publications');
            $this->card('my_grades', 'Bulletins publiés', (clone $sheets)->count(), 'Vos résultats consultables', '/my-grades', 'document', 'green');
            $this->panel('my_grades', 'Mes derniers bulletins', 'Seules les publications sont affichées, y compris lorsqu’une correction est en préparation.',
                $sheets->with(['latestPublication', 'semester', 'enrollment.academicYear'])->orderByDesc('id')->limit(5)->get()->map(fn ($s) =>
                    $this->item($s->id, $s->semester->name.' · '.$s->enrollment->academicYear->name, 'Publication n° '.$s->latestPublication->number, '/my-grades/'.$s->id,
                        $s->latestPublication->created_at->toIso8601String(), number_format($s->latestPublication->result['average'], 2, ',', ' ').'/20'))->all(),
                'Aucun bulletin publié pour le moment.', '/my-grades');
            $this->action('grades', 'Mes notes et bulletins', '/my-grades');
        }
        if (app(DefenseAccess::class)->own($this->user)) {
            $defenses = Defense::whereHas('student', $own);
            $d = (clone $defenses)->latest('id')->first();
            $this->card('my_defense', 'Ma soutenance', $d ? self::DEFENSE_LABELS[$d->status] : 'Aucune demande', 'Votre candidature et votre jury', '/defenses', 'calendar');
            $this->defenseAgenda($defenses, 'Ma prochaine soutenance');
            $ready = $report && ! $d && app(DefenseAccess::class)->readiness($report)['ready'];
            $this->action('defense', $ready ? 'Demander ma soutenance' : 'Suivre ma soutenance', '/defenses', 'calendar');
        }
    }

    private function money(int $value): string { return number_format($value, 0, ',', ' ').' FCFA'; }
}
