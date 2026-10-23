import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import Icon from '../../components/icon';
import ReportVersions from '../../components/report-versions';
import { InputField, SelectField, dateLabel, eligibilityLabels } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { SharedData } from '../../types';
import type { Page } from '../../types/students';
import type { ReportSummary, ReportVersion } from '../../types/reports';
import { reportLabels, reportTone } from '../../types/reports';

type EnrollmentChoice = { id: number; reference: string; year: string; level: string; eligibility: keyof typeof eligibilityLabels; can_submit: boolean };
type Props = { student: ReportSummary['student'] | null; report: ReportSummary | null; versions: Page<ReportVersion> | null; enrollments: EnrollmentChoice[]; operationKey: string };
function DepositForm({ report, enrollments, operationKey }: Pick<Props, 'report' | 'enrollments' | 'operationKey'>) {
  const form = useForm<{ title: string; enrollment_id: string; document: File | null; revision: number; operation_key: string }>({ title: report?.title ?? '', enrollment_id: String(enrollments.find((e) => e.can_submit)?.id ?? ''), document: null, revision: report?.revision ?? 0, operation_key: operationKey });
  function submit(event: FormEvent) { event.preventDefault(); form.post('/my-reports', { forceFormData: true, preserveScroll: true }); }
  return <section className="panel schooling-section" id="report-upload"><h2>{report ? 'Déposer la version corrigée' : 'Déposer mon mémoire'}</h2>
    <form className="management-form" onSubmit={submit}>
      <InputField id="report-title" label="Thème du mémoire" required maxLength={255} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} error={form.errors.title} />
      <SelectField id="report-enrollment" label="Inscription concernée" required value={form.data.enrollment_id} onChange={(e) => form.setData('enrollment_id', e.target.value)} error={form.errors.enrollment_id}><option value="">Choisir une inscription éligible</option>{enrollments.map((e) => <option key={e.id} value={e.id} disabled={!e.can_submit}>{e.year} · {e.level} · {eligibilityLabels[e.eligibility]}</option>)}</SelectField>
      <InputField id="report-file" label="Mémoire au format PDF" type="file" required accept=".pdf,application/pdf" onChange={(e) => {
        const file = e.target.files?.[0] ?? null;
        form.setData('document', file);
        if (file && file.size > 20 * 1024 * 1024) form.setError('document', 'Le PDF ne doit pas dépasser 20 Mo.');
        else form.clearErrors('document');
      }} error={form.errors.document} />
      <p className="field-hint">PDF uniquement, 20 Mo maximum. Vérifiez votre document avant l’envoi : chaque version déposée est conservée.</p>
      {form.progress && <div role="status"><label htmlFor="upload-progress">Transfert du mémoire : {form.progress.percentage ?? 0} %</label><progress id="upload-progress" max={100} value={form.progress.percentage ?? 0} /></div>}
      <button className="button-primary" disabled={form.processing || !form.data.document || !enrollments.some((e) => e.can_submit && String(e.id) === form.data.enrollment_id) || form.data.document.size > 20 * 1024 * 1024} aria-busy={form.processing}>{form.processing ? 'Dépôt en cours…' : report ? 'Soumettre la nouvelle version' : 'Soumettre mon mémoire'}</button>
    </form>
  </section>;
}
export default function OwnReport({ student, report, versions, enrollments, operationKey }: Props) {
  const { auth, errors } = usePage<SharedData & { errors: Record<string, string> }>().props;
  const editable = Boolean(student) && (!report || report.status === 'changes_requested');
  const eligible = enrollments.some((e) => e.can_submit);
  return <AppLayout title="Mon rapport / Mémoire" subtitle="Dépôt et suivi du mémoire"><Head title="Mon rapport / Mémoire" />
    <p className="breadcrumb"><Link href="/dashboard">Accueil</Link><span>/</span><strong>Rapport / Mémoire</strong></p>
    <div className="page-intro"><div><h1>Gestion du rapport / Mémoire</h1><p>{student ? student.last_name + ' ' + student.first_name + ' · Carte ' + student.card_number : 'Retrouvez votre dépôt et les retours du service habilité.'}</p></div></div>
    <FormErrors errors={errors} />{errors.revision && <a href="/my-reports" className="table-action">Recharger le dossier</a>}
    <div className="report-actions">
      <section className="panel settings-card"><span className="panel-icon"><Icon name="document" /></span><h2>{report ? 'Nouvelle version' : 'Création du rapport'}</h2><p>Déposez votre mémoire ou la version corrigée demandée.</p>{editable && eligible ? <a href="#report-upload" className="button-primary">{report ? 'Déposer la correction' : 'Déposer mon mémoire'}</a> : <button type="button" className="button-secondary" disabled>Dépôt indisponible</button>}</section>
      <section className="panel settings-card"><span className="panel-icon"><Icon name="clock" /></span><h2>Statut du dépôt</h2><p>{report ? reportLabels[report.status] : 'Aucun rapport soumis'}</p>{report && <a href="#report-status" className="button-primary">Vérifier le statut</a>}</section>
      <section className="panel settings-card"><span className="panel-icon"><Icon name="mail" /></span><h2>Commentaires</h2><p>Suivez les avis de chaque membre de la commission sur vos versions.</p>{report ? <a href="#report-history" className="button-primary">Consulter les retours</a> : <p className="field-hint">Les retours apparaîtront après votre dépôt.</p>}</section>
    </div>
    {!student && <p className="notice notice-neutral">Aucun dossier étudiant n’est rattaché à votre compte. Demandez au service de scolarité de vérifier ce rattachement.</p>}
    {student && editable && !eligible && <p className="notice notice-neutral">Le dépôt nécessite une inscription avec une scolarité soldée, le niveau Master 2 et un stage déclaré. {auth.permissions.includes('students.self') && <Link href="/my-enrollments" className="table-action">Consulter mes inscriptions</Link>}</p>}
    {report && <section className="panel schooling-section" id="report-status"><h2>État du mémoire</h2><span className={'status-tag ' + reportTone[report.status]}>{reportLabels[report.status]}</span><h3 className="report-title">{report.title}</h3><p className="field-hint">{report.reference} · Version {report.version_count} · {report.year.name} · Dépôt du {dateLabel(report.latest_submitted_at)}</p>
      <p className="report-status-help">{report.status === 'submitted' ? 'Votre document est déposé. Le chargé de communication doit le transmettre à la commission.' : report.status === 'under_review' ? 'Votre document a été transmis. L’approbation de chaque membre de la commission est nécessaire.' : report.status === 'changes_requested' ? 'Consultez le commentaire, corrigez votre document, puis soumettez une nouvelle version.' : 'Votre mémoire est accepté : tous les membres de la commission ont approuvé cette version.'}</p>
    </section>}
    {report?.status === 'accepted' && auth.permissions.includes('defenses.self') && <p className="no-print"><Link className="button-primary" href="/defenses">Préparer ma soutenance</Link></p>}
    {editable && <DepositForm key={report?.revision ?? 0} report={report} enrollments={enrollments} operationKey={operationKey} />}
    {report && versions && <ReportVersions reportId={report.id} versions={versions} personal />}
  </AppLayout>;
}
