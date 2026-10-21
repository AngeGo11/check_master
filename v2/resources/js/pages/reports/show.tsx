import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import ReportVersions from '../../components/report-versions';
import { SelectField, dateLabel, eligibilityLabels } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { SharedData } from '../../types';
import type { Page } from '../../types/students';
import type { Eligibility } from '../../types/enrollments';
import type { ReportSummary, ReportVersion } from '../../types/reports';
import { reportLabels, reportTone } from '../../types/reports';

type Props = { report: ReportSummary; versions: Page<ReportVersion>; eligibility: Pick<Eligibility, 'status' | 'criteria' | 'context_valid'>; documentAvailable: boolean; canReview: boolean };
function ReviewForm({ report, canApprove }: { report: ReportSummary; canApprove: boolean }) {
  const form = useForm({ decision: canApprove ? 'approved' : 'changes_requested', comment: '', revision: report.revision });
  function submit(event: FormEvent) { event.preventDefault(); form.post('/reports/' + report.id + '/review', { preserveScroll: true }); }
  return <form className="management-form" onSubmit={submit}>
    <SelectField id="decision" label="Décision administrative" value={form.data.decision} onChange={(e) => form.setData('decision', e.target.value)} error={form.errors.decision}><option value="approved" disabled={!canApprove}>Approuver le mémoire</option><option value="changes_requested">Demander une correction</option></SelectField>
    <div className="form-field"><label htmlFor="review-comment">Commentaire visible par l’étudiant</label><textarea id="review-comment" required minLength={5} maxLength={3000} rows={5} value={form.data.comment} onChange={(e) => form.setData('comment', e.target.value)} aria-invalid={Boolean(form.errors.comment)} aria-describedby={form.errors.comment ? 'comment-error' : undefined} />{form.errors.comment && <p className="field-error" id="comment-error">{form.errors.comment}</p>}</div>
    <p className="field-hint">La décision porte sur la version {report.version_count}. Une approbation administrative prépare l’examen par la commission.</p>
    <button className="button-primary" disabled={form.processing} aria-busy={form.processing}>Enregistrer la décision</button>
  </form>;
}
export default function ShowReport({ report, versions, eligibility, documentAvailable, canReview }: Props) {
  const { errors } = usePage<SharedData & { errors: Record<string, string> }>().props;
  const canApprove = eligibility.status === 'eligible' && eligibility.context_valid && eligibility.criteria.every((c) => c.met) && documentAvailable;
  return <AppLayout title="Dossier du mémoire" subtitle="Approbation administrative"><Head title={report.title} />
    <p className="breadcrumb"><Link href="/reports">Rapports / Mémoires</Link><span>/</span><strong>{report.student.card_number}</strong></p>
    <div className="page-intro"><div><h1>{report.title}</h1><p>{report.student.last_name} {report.student.first_name} · {report.student.card_number} · {report.year.name}</p><p className="reference-cell">{report.reference} · Version {report.version_count} · {dateLabel(report.latest_submitted_at)}</p></div><span className={'status-tag ' + reportTone[report.status]}>{reportLabels[report.status]}</span></div>
    <FormErrors errors={errors} />{errors.revision && <a href={'/reports/' + report.id} className="table-action">Recharger le mémoire</a>}
    <section className="panel schooling-section"><h2>Situation de l’inscription</h2><p className="eligibility-status"><span className="status-tag">{eligibilityLabels[eligibility.status]}</span></p>
      <ul className="eligibility-criteria">{eligibility.criteria.map((criterion) => <li key={criterion.key}><span className={'criterion-mark' + (criterion.met ? ' is-met' : '')}>{criterion.met ? '✓' : '—'}</span><div><strong>{criterion.label} · {criterion.met ? 'Rempli' : 'Non rempli'}</strong><p>{criterion.detail}</p></div></li>)}</ul>
      {!canApprove && <p className="notice notice-neutral">L’approbation nécessite une éligibilité encore valide et un document disponible. Une demande de correction reste possible pour un mémoire en attente.</p>}
      {!documentAvailable && <p className="notice notice-error">Le PDF de la dernière version est indisponible ou altéré. Signalez le problème avant de poursuivre.</p>}
    </section>
    <ReportVersions reportId={report.id} versions={versions} personal={false} />
    {canReview && <section className="panel schooling-section"><h2>Examiner la dernière version</h2><p className="field-hint">Téléchargez et vérifiez la version {report.version_count} avant d’enregistrer votre décision.</p><ReviewForm key={report.revision} report={report} canApprove={canApprove} /></section>}
    <Link href="/reports" className="button-secondary">Retour aux mémoires</Link>
  </AppLayout>;
}
