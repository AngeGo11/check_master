<?php

namespace App\Services;

use App\Models\{Permission, StudentClaim};
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class AuditTrail
{
    public const EXPORT_LIMIT = 10000;
    // Table and column identifiers are exclusively server-owned, never request values.
    public const SOURCES = [
        'documents' => ['table' => 'document_events', 'label' => 'Documents et archives', 'targets' => ['work_document_id' => 'Document', 'work_document_version_id' => 'Version', 'personal_archive_id' => 'Archive personnelle']],
        'access' => ['table' => 'access_audits', 'label' => 'Comptes et accès', 'targets' => ['target_user_id' => 'Compte', 'target_group_id' => 'Groupe']],
        'students' => ['table' => 'student_audits', 'label' => 'Dossiers étudiants', 'targets' => ['student_id' => 'Étudiant', 'promotion_id' => 'Promotion']],
        'schooling' => ['table' => 'enrollment_audits', 'label' => 'Inscriptions et règlements', 'targets' => ['enrollment_id' => 'Inscription']],
        'reports' => ['table' => 'report_audits', 'label' => 'Rapports et mémoires', 'targets' => ['report_id' => 'Rapport', 'report_version_id' => 'Version']],
        'collaboration' => ['table' => 'collaboration_audits', 'label' => 'Réunions et messagerie', 'targets' => ['meeting_id' => 'Réunion', 'message_id' => 'Message']],
        'staff' => ['table' => 'staff_audits', 'label' => 'Ressources humaines', 'targets' => ['staff_profile_id' => 'Agent', 'staff_reference_id' => 'Référentiel']],
        'academic' => ['table' => 'academic_audits', 'label' => 'Notes, parcours et soutenances', 'targets' => ['enrollment_id' => 'Inscription', 'teaching_program_id' => 'Programme', 'defense_id' => 'Soutenance']],
        'claims' => ['table' => 'claim_events', 'label' => 'Réclamations', 'targets' => ['student_claim_id' => 'Réclamation']],
    ];
    private const ACTIONS = [
        'document.created' => 'Document créé', 'document.saved' => 'Version de document enregistrée', 'document.published' => 'Compte rendu publié', 'document.archive_changed' => 'Archive personnelle modifiée',
        'annual.published' => 'Décision annuelle publiée', 'annual.corrected' => 'Décision annuelle corrigée', 'progression.enrolled' => 'Réinscription après délibération', 'retake.result_recorded' => 'Résultat de rattrapage consigné', 'retake.fee_updated' => 'Tarif de rattrapage modifié',
        'user.created' => 'Compte créé', 'user.updated' => 'Compte modifié', 'user.password.changed' => 'Mot de passe modifié',
        'group.permissions.updated' => 'Permissions modifiées', 'administrator.granted.from.console' => 'Administration accordée depuis la console',
        'student.created' => 'Dossier étudiant créé', 'student.updated' => 'Dossier étudiant modifié', 'student.account.updated' => 'Compte étudiant rattaché',
        'promotion.created' => 'Promotion créée', 'promotion.updated' => 'Promotion modifiée',
        'year.created' => 'Année créée', 'year.updated' => 'Année modifiée', 'year.closed' => 'Année clôturée',
        'fee.created' => 'Tarif créé', 'fee.updated' => 'Tarif modifié', 'enrollment.created' => 'Inscription créée',
        'enrollment.cancelled' => 'Inscription annulée', 'enrollment.restored' => 'Inscription rétablie',
        'payment.recorded' => 'Règlement enregistré', 'payment.voided' => 'Règlement annulé',
        'internship.saved' => 'Stage déclaré ou modifié', 'internship.removed' => 'Stage retiré', 'eligibility.reviewed' => 'Éligibilité examinée',
        'report.submitted' => 'Rapport déposé', 'report.forwarded' => 'Rapport transmis à la commission', 'report.voted' => 'Avis de commission enregistré', 'report.reviewed' => 'Rapport examiné (historique)',
        'message.sent' => 'Message envoyé', 'message.draft_saved' => 'Brouillon enregistré', 'message.read' => 'Message marqué lu',
        'message.unread' => 'Message marqué non lu', 'message.archive' => 'Message archivé', 'message.trash' => 'Message mis à la corbeille',
        'message.restore' => 'Message restauré', 'message.meeting_notice' => 'Notification de réunion créée',
        'meeting.created' => 'Réunion créée', 'meeting.updated' => 'Réunion modifiée', 'meeting.previous_schedule' => 'Ancien horaire conservé',
        'meeting.cancelled' => 'Réunion annulée', 'meeting.responded' => 'Réponse à une réunion',
        'meeting.document_uploaded' => 'Document de réunion ajouté', 'meeting.document_withdrawn' => 'Document de réunion retiré',
        'staff.created' => 'Fiche agent créée', 'staff.updated' => 'Fiche agent modifiée', 'staff.archived' => 'Agent archivé', 'staff.restored' => 'Agent rétabli',
        'staff.account_updated' => 'Compte agent rattaché', 'staff.assignment_changed' => 'Affectation modifiée',
        'staff.photo_updated' => 'Photo remplacée', 'staff.photo_removed' => 'Photo retirée', 'catalog.created' => 'Référentiel RH créé', 'catalog.updated' => 'Référentiel RH modifié',
        'grades.created' => 'Feuille de notes créée', 'grades.saved' => 'Notes enregistrées', 'grades.published' => 'Bulletin publié', 'grades.reopened' => 'Notes rouvertes',
        'program.created' => 'Programme créé', 'program.updated' => 'Programme modifié', 'program.published' => 'Programme publié',
        'defense.requested' => 'Soutenance demandée', 'defense.scheduled' => 'Soutenance planifiée', 'defense.cancelled' => 'Soutenance annulée',
        'defense.result_recorded' => 'Résultat de soutenance enregistré', 'defense.result_corrected' => 'Résultat de soutenance corrigé',
        'submitted' => 'Réclamation déposée', 'review' => 'Réclamation prise en charge', 'comment' => 'Réponse à une réclamation',
        'escalate' => 'Réclamation transmise à la filière', 'resolve' => 'Réclamation traitée', 'reopen' => 'Réclamation rouverte', 'withdraw' => 'Réclamation retirée',
    ];

    public function query(array $filters = []): Builder
    {
        $union = null;
        foreach (self::SOURCES as $source => $definition) {
            if (($filters['module'] ?? null) && $filters['module'] !== $source) { continue; }
            $name = $source === 'claims' ? 'coalesce(u.name, e.actor_name)' : 'u.name';
            $q = DB::table($definition['table'].' as e')->leftJoin('users as u', 'u.id', '=', 'e.actor_user_id')
                ->selectRaw('? as source, e.id as event_id, e.actor_user_id, '.$name.' as actor_name, e.action, e.created_at', [$source]);
            $columns = array_keys($definition['targets']);
            for ($i = 0; $i < 3; $i++) { $q->selectRaw((isset($columns[$i]) ? 'e.'.$columns[$i] : 'null').' as target_'.$i); }
            if ($filters['action'] ?? null) { $q->where('e.action', $filters['action']); }
            if ($filters['from'] ?? null) { $q->where('e.created_at', '>=', $filters['from'].' 00:00:00'); }
            if ($filters['to'] ?? null) { $q->where('e.created_at', '<', CarbonImmutable::createFromFormat('!Y-m-d', $filters['to'], 'UTC')->addDay()->format('Y-m-d H:i:s')); }
            if ($filters['unattributed'] ?? false) { $q->whereNull('e.actor_user_id'); }
            if ($actor = trim($filters['actor'] ?? '')) {
                if (preg_match('/^#([0-9]+)$/', $actor, $match)) { $q->where('e.actor_user_id', $match[1]); }
                else { $q->whereRaw($name." like ? escape '!'", ['%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $actor).'%']); }
            }
            if ($union === null) { $union = $q; } else { $union->unionAll($q); }
        }
        abort_unless($union, 422);
        return DB::query()->fromSub($union, 'events');
    }

    public function ordered(Builder $query): Builder { return $query->orderByDesc('created_at')->orderBy('source')->orderByDesc('event_id'); }

    public function statistics(Builder $query): array
    {
        $s = (clone $query)->selectRaw('count(*) as total, count(distinct actor_user_id) as actors, count(distinct source) as modules, max(created_at) as last_at')->first();
        return ['total' => (int) $s->total, 'actors' => (int) $s->actors, 'modules' => (int) $s->modules, 'last_at' => $s->last_at ? $this->date($s->last_at) : null];
    }

    public function actions(): array
    {
        return $this->query()->select('source', 'action')->distinct()->orderBy('action')->get()
            ->filter(fn ($e) => preg_match('/^[a-z][a-z0-9._-]{0,99}$/', $e->action))
            ->map(fn ($e) => ['module' => $e->source, 'value' => $e->action, 'label' => self::ACTIONS[$e->action] ?? $e->action])->values()->all();
    }

    public function summary(object $row): array
    {
        $d = self::SOURCES[$row->source]; $references = [];
        foreach (array_values($d['targets']) as $i => $label) { if ($row->{'target_'.$i} !== null) { $references[] = $label.' #'.(int) $row->{'target_'.$i}; } }
        return ['key' => $row->source.':'.$row->event_id, 'source' => $row->source, 'id' => (int) $row->event_id, 'module' => $d['label'],
            'actor_id' => $row->actor_user_id !== null ? (int) $row->actor_user_id : null,
            'actor' => $row->actor_name ?? ($row->action === 'administrator.granted.from.console' ? 'Console système' : 'Compte indisponible'),
            'action' => preg_match('/^[a-z][a-z0-9._-]{0,99}$/', $row->action) ? $row->action : 'unknown',
            'label' => self::ACTIONS[$row->action] ?? 'Action enregistrée', 'references' => $references, 'created_at' => $this->date($row->created_at)];
    }

    public function detail(string $source, int $id): array
    {
        abort_unless(isset(self::SOURCES[$source]), 404);
        $row = $this->query(['module' => $source])->where('event_id', $id)->first(); abort_unless($row, 404);
        $event = DB::table(self::SOURCES[$source]['table'])->where('id', $id)->first();
        // Never serialize the raw record: old/new grades, private text, hashes and paths stay private.
        $metadata = isset($event->metadata) ? json_decode($event->metadata, true) : [];
        $metadata = is_array($metadata) ? $metadata : [];
        $details = [];
        $add = function (string $label, string $value) use (&$details) { $details[] = compact('label', 'value'); };
        foreach (['work_document_version_id' => 'Version du document de travail', 'annual_decision_id' => 'Décision annuelle', 'source_enrollment_id' => 'Inscription d’origine', 'retake_subject_id' => 'Matière à reprendre', 'retake_event_id' => 'Résultat de rattrapage', 'retake_fee_id' => 'Tarif de rattrapage', 'year_id' => 'Année', 'fee_id' => 'Tarif', 'payment_id' => 'Règlement', 'internship_id' => 'Stage', 'review_id' => 'Examen',
            'round_id' => 'Commission', 'vote_id' => 'Avis', 'document_id' => 'Document', 'photo_id' => 'Photo', 'sheet_id' => 'Feuille de notes',
            'publication_id' => 'Publication', 'reference_id' => 'Référentiel', 'user_id' => 'Compte rattaché', 'previous_user_id' => 'Ancien compte'] as $key => $label) {
            if (isset($metadata[$key]) && is_numeric($metadata[$key]) && (int) $metadata[$key] > 0) { $add($label, '#'.(int) $metadata[$key]); }
        }
        $fields = ['name' => 'Nom', 'email' => 'Adresse e-mail', 'is_active' => 'Activation', 'is_archived' => 'Archivage', 'last_name' => 'Nom',
            'first_name' => 'Prénom', 'card_number' => 'Carte étudiant', 'date_of_birth' => 'Date de naissance', 'gender' => 'Civilité',
            'study_level_id' => 'Niveau', 'promotion_id' => 'Promotion', 'student_status_id' => 'Statut étudiant', 'phone' => 'Téléphone',
            'address' => 'Adresse', 'kind' => 'Catégorie professionnelle', 'registration_number' => 'Matricule'];
        foreach (['fields_changed', 'identity_fields_changed'] as $key) {
            $changed = array_values(array_unique(array_map(fn ($k) => $fields[$k], array_filter(is_array($metadata[$key] ?? null) ? $metadata[$key] : [], fn ($k) => is_string($k) && isset($fields[$k])))));
            if ($changed) { $add('Champs modifiés', implode(', ', $changed)); }
        }
        if ($source === 'access' && $row->action === 'group.permissions.updated') {
            foreach (['before' => 'Permissions avant', 'after' => 'Permissions après'] as $key => $label) {
                $values = array_intersect(is_array($metadata[$key] ?? null) ? array_filter($metadata[$key], 'is_string') : [], Permission::CODES);
                $add($label, $values ? implode(', ', $values) : 'Aucune');
            }
        }
        if ($source === 'access' && in_array($row->action, ['user.created', 'user.updated'], true)) {
            foreach ($row->action === 'user.created' ? ['Compte' => $metadata] : ['Avant' => $metadata['before'] ?? [], 'Après' => $metadata['after'] ?? []] as $label => $state) {
                if (! is_array($state)) { continue; }
                if (isset($state['is_active']) && is_bool($state['is_active'])) { $add($label.' · activation', $state['is_active'] ? 'Actif' : 'Inactif'); }
                foreach (['type_ids' => 'types', 'group_ids' => 'groupes'] as $key => $kind) {
                    if (isset($state[$key]) && is_array($state[$key])) { $ids = array_filter($state[$key], fn ($v) => is_int($v) && $v > 0); $add($label.' · '.$kind, $ids ? implode(', ', array_map(fn ($v) => '#'.$v, $ids)) : 'Aucun'); }
                }
            }
        }
        if (isset($metadata['recipient_count']) && is_int($metadata['recipient_count'])) { $add('Nombre de destinataires', (string) $metadata['recipient_count']); }
        if ($source === 'reports' && is_array($metadata['member_ids'] ?? null)) { $add('Membres de commission', (string) count($metadata['member_ids'])); }
        if ($source === 'claims') {
            if (isset(StudentClaim::STATUSES[$event->from_status ?? ''])) { $add('Statut avant', StudentClaim::STATUSES[$event->from_status]); }
            if (isset(StudentClaim::STATUSES[$event->to_status])) { $add('Statut après', StudentClaim::STATUSES[$event->to_status]); }
            if ($event->assigned_to) { $add('Responsable affecté', '#'.(int) $event->assigned_to); }
        }
        return [...$this->summary($row), 'details' => $details];
    }

    private function date(string $date): string { return CarbonImmutable::parse($date, 'UTC')->toIso8601String(); }

    public static function csvCell(string $value): string
    {
        // Neutralize spreadsheet formulas, including a leading space, tab or newline before the operator.
        return preg_match('/^[\s\x{FEFF}]*[=+@-]|^[\t\r\n]/u', $value) ? "'".$value : $value;
    }
}
