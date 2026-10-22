import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import ReportVersions, { CommissionRound } from '../../components/report-versions';
import { InputField, SelectField, dateLabel, eligibilityLabels } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { SharedData, AccessOption } from '../../types';
import type { Page } from '../../types/students';
import type { Eligibility } from '../../types/enrollments';
import type { ReportSummary, ReportVersion, ReportRound } from '../../types/reports';
import { reportLabels, reportTone } from '../../types/reports';

type Props = { report: ReportSummary; versions: Page<ReportVersion>; eligibility: Pick<Eligibility, 'status' | 'criteria' | 'context_valid'>; documentAvailable: boolean;
  currentRound: ReportRound | null; rosterChanged: boolean; canForward: boolean; canVote: boolean; committee: AccessOption[]; committeeFingerprint: string | null };
function ForwardForm({ report, committee, fingerprint, restart, available }: { report: ReportSummary; committee: AccessOption[]; fingerprint: string; restart: boolean; available: boolean }) {
  const form = useForm({ revision: report.revision, committee_fingerprint: fingerprint, reason: '' });
  function submit(event: FormEvent) { event.preventDefault(); form.post('/reports/' + report.id + '/forward', { preserveScroll: true }); }
  return <form className="management-form" onSubmit={submit}><p>La version {report.version_count} sera transmise à l’ensemble des {committee.length} membres actifs de la commission.</p>
    <ul className="commission-roster">{committee.map((member) => <li key={member.id}>{member.name}</li>)}</ul>
    {!committee.length && <p className="notice notice-neutral">Aucun membre actif. L’administrateur doit affecter les comptes au groupe Commission de validation.</p>}
    {restart && <><p className="notice notice-neutral">La composition a changé. Cette nouvelle transmission conservera l’historique et demandera à tous les membres un nouvel avis.</p><InputField id="forward-reason" label="Motif de la nouvelle transmission" required minLength={5} maxLength={1000} value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} error={form.errors.reason} /></>}
    <button className="button-primary" disabled={form.processing || !available || !committee.length} aria-busy={form.processing}>{restart ? 'Relancer la transmission à tous les membres' : 'Transmettre à la commission'}</button>
  </form>;
}
function VoteForm({ report, round, available }: { report: ReportSummary; round: ReportRound; available: boolean }) {
  const form = useForm({ decision: available ? 'approved' : 'changes_requested', comment: '' });
  function submit(event: FormEvent) { event.preventDefault(); form.post('/reports/' + report.id + '/rounds/' + round.id + '/vote', { preserveScroll: true }); }
  return <form className="management-form" onSubmit={submit}>
    <SelectField id="decision" label="Mon avis sur la version transmise" value={form.data.decision} onChange={(e) => form.setData('decision', e.target.value)} error={form.errors.decision}><option value="approved" disabled={!available}>J’approuve ce rapport</option><option value="changes_requested">Je demande une correction</option></SelectField>
    <div className="form-field"><label htmlFor="vote-comment">Commentaire {form.data.decision === 'changes_requested' ? '(obligatoire)' : '(facultatif)'}</label><textarea id="vote-comment" required={form.data.decision === 'changes_requested'} minLength={5} maxLength={3000} rows={5} value={form.data.comment} onChange={(e) => form.setData('comment', e.target.value)} aria-invalid={Boolean(form.errors.comment)} aria-describedby={form.errors.comment ? 'comment-error' : undefined} />{form.errors.comment && <p className="field-error" id="comment-error">{form.errors.comment}</p>}</div>
    <p className="field-hint">Votre avis concerne la version {report.version_count}, transmission {round.number}. Il est personnel et visible par l’étudiant. L’approbation de chaque membre est nécessaire.</p>
    <button className="button-primary" disabled={form.processing} aria-busy={form.processing}>Enregistrer mon avis</button>
  </form>;
}
export default function ShowReport({ report, versions, eligibility, documentAvailable, currentRound, rosterChanged, canForward, canVote, committee, committeeFingerprint }: Props) {
  const { errors } = usePage<SharedData & { errors: Record<string, string> }>().props;
  return <AppLayout title="Dossier du mémoire" subtitle="Transmission et avis de la commission"><Head title={report.title} />
    <p className="breadcrumb"><Link href="/reports">Rapports / Mémoires</Link><span>/</span><strong>{report.student.card_number}</strong></p>
    <div className="page-intro"><div><h1>{report.title}</h1><p>{report.student.last_name} {report.student.first_name} · {report.student.card_number} · {report.year.name}</p><p className="reference-cell">{report.reference} · Version {report.version_count} · {dateLabel(report.latest_submitted_at)}</p></div><span className={'status-tag ' + reportTone[report.status]}>{reportLabels[report.status]}</span></div>
    <FormErrors errors={errors} />{(errors.revision || errors.committee || errors.round) && <a href={'/reports/' + report.id} className="table-action">Recharger le mémoire</a>}
    {rosterChanged && <p className="notice notice-neutral">La composition de la commission a changé. Les nouveaux avis sont suspendus jusqu’à une nouvelle transmission par le chargé de communication.</p>}
    {!documentAvailable && <p className="notice notice-error">Le PDF de la dernière version est indisponible ou altéré. La transmission et l’approbation sont bloquées.</p>}
    {currentRound && <section className="panel schooling-section"><h2>Suivi de la dernière transmission</h2><CommissionRound round={currentRound} /></section>}
    {canForward && <section className="panel schooling-section"><h2>Transmission par le chargé de communication</h2><ForwardForm key={report.revision} report={report} committee={committee} fingerprint={committeeFingerprint!} restart={rosterChanged} available={documentAvailable} /></section>}
    <ReportVersions reportId={report.id} versions={versions} personal={false} />
    {canVote && currentRound && <section className="panel schooling-section"><h2>Mon avis de membre de commission</h2><p className="field-hint">Téléchargez et vérifiez la version {report.version_count} avant de donner votre avis.</p><VoteForm key={currentRound.id} report={report} round={currentRound} available={documentAvailable} /></section>}
    <section className="panel schooling-section"><h2>Situation actuelle de l’inscription</h2><p className="eligibility-status"><span className="status-tag">{eligibilityLabels[eligibility.status]}</span></p><p className="field-hint">Les critères sont contrôlés automatiquement au dépôt. Les avis de commission portent sur la version transmise.</p>
      <ul className="eligibility-criteria">{eligibility.criteria.map((criterion) => <li key={criterion.key}><span className={'criterion-mark' + (criterion.met ? ' is-met' : '')}>{criterion.met ? '✓' : '—'}</span><div><strong>{criterion.label} · {criterion.met ? 'Rempli' : 'Non rempli'}</strong><p>{criterion.detail}</p></div></li>)}</ul>
    </section><Link href="/reports" className="button-secondary">Retour aux mémoires</Link>
  </AppLayout>;
}
