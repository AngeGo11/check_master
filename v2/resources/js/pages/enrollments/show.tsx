import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import { InputField, SelectField, money, dateLabel, paymentLabels, eligibilityLabels } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { SharedData } from '../../types';
import type { EnrollmentDetail, Payment, Internship, Eligibility } from '../../types/enrollments';

type Props = { enrollment: EnrollmentDetail; payments: Payment[]; internship: Internship | null; eligibility: Eligibility; personal: boolean; operationKey: string | null; today: string };
function ReasonForm({ enrollment, mode, payment, personal = false }: { enrollment: EnrollmentDetail; mode: 'void' | 'status' | 'remove' | 'review'; payment?: Payment; personal?: boolean }) {
  const form = useForm({ reason: '', revision: enrollment.revision });
  const base = (personal ? '/my-enrollments/' : '/enrollments/') + enrollment.id;
  const label = mode === 'void' ? 'Annuler cette écriture' : mode === 'status' ? enrollment.cancelled ? 'Rétablir l’inscription' : 'Annuler l’inscription' : mode === 'remove' ? 'Retirer la déclaration' : 'Consigner l’observation';
  function submit(event: FormEvent) {
    event.preventDefault();
    const options = { preserveScroll: true, onSuccess: () => form.reset('reason') };
    if (mode === 'void') form.patch(base + '/payments/' + payment!.id + '/void', options);
    if (mode === 'status') { form.transform((data) => ({ ...data, cancelled: !enrollment.cancelled })); form.patch(base + '/status', options); }
    if (mode === 'remove') form.delete(base + '/internship', options);
    if (mode === 'review') form.post(base + '/eligibility', options);
  }
  return <form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} />
    <InputField id={mode + '-reason-' + (payment?.id ?? enrollment.id)} label={mode === 'review' ? 'Observation de la personne habilitée' : 'Motif de la correction'} required minLength={5} maxLength={1000} value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} error={form.errors.reason} />
    {mode === 'void' && <p className="field-hint">Le reçu restera visible comme annulé. Cette correction d’écriture n’effectue aucun remboursement.</p>}
    <button className={mode === 'review' ? 'button-primary' : 'button-secondary'} disabled={form.processing}>{label}</button>
  </form>;
}
function PaymentForm({ enrollment, operationKey, today }: { enrollment: EnrollmentDetail; operationKey: string; today: string }) {
  const form = useForm({ amount: '', method: 'cash', cheque_number: '', purpose: 'Frais de scolarité', paid_on: today, operation_key: operationKey, revision: enrollment.revision });
  function submit(event: FormEvent) {
    event.preventDefault();
    form.transform((data) => ({ ...data, cheque_number: data.method === 'cheque' ? data.cheque_number : null }));
    form.post('/enrollments/' + enrollment.id + '/payments', { preserveScroll: true });
  }
  return <form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} /><div className="form-grid">
    <InputField id="amount" label="Montant reçu (FCFA)" type="number" required min={1} max={enrollment.balance} step={1} value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} error={form.errors.amount} />
    <SelectField id="method" label="Mode de paiement" value={form.data.method} onChange={(e) => form.setData('method', e.target.value)} error={form.errors.method}><option value="cash">Espèces</option><option value="cheque">Chèque</option></SelectField>
    {form.data.method === 'cheque' && <InputField id="cheque" label="N° du chèque" required maxLength={50} value={form.data.cheque_number} onChange={(e) => form.setData('cheque_number', e.target.value)} error={form.errors.cheque_number} />}
    <InputField id="paid-date" label="Date du paiement" type="date" required min={enrollment.enrolled_on} max={today} value={form.data.paid_on} onChange={(e) => form.setData('paid_on', e.target.value)} error={form.errors.paid_on} />
    <InputField id="purpose" label="Motif du règlement" required maxLength={255} value={form.data.purpose} onChange={(e) => form.setData('purpose', e.target.value)} error={form.errors.purpose} />
  </div><p className="field-hint">Enregistrez uniquement un paiement déjà reçu. Reste à payer : {money(enrollment.balance)}.</p><button className="button-primary" disabled={form.processing} aria-busy={form.processing}>Enregistrer le paiement</button></form>;
}
function InternshipForm({ enrollment, internship }: { enrollment: EnrollmentDetail; internship: Internship | null }) {
  const form = useForm({ company: internship?.company ?? '', title: internship?.title ?? '', starts_on: internship?.starts_on ?? '', ends_on: internship?.ends_on ?? '', evidence_reference: internship?.evidence_reference ?? '', revision: enrollment.revision });
  function submit(event: FormEvent) { event.preventDefault(); form.put('/my-enrollments/' + enrollment.id + '/internship', { preserveScroll: true }); }
  return <form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} /><div className="form-grid">
    <InputField id="company" label="Entreprise d’accueil" required maxLength={200} value={form.data.company} onChange={(e) => form.setData('company', e.target.value)} error={form.errors.company} />
    <InputField id="stage-title" label="Intitulé du stage" required maxLength={255} value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} error={form.errors.title} />
    <InputField id="stage-start" label="Début du stage" type="date" required value={form.data.starts_on} onChange={(e) => form.setData('starts_on', e.target.value)} error={form.errors.starts_on} />
    <InputField id="stage-end" label="Fin du stage" type="date" required min={form.data.starts_on || undefined} value={form.data.ends_on} onChange={(e) => form.setData('ends_on', e.target.value)} error={form.errors.ends_on} />
    <InputField id="evidence" label="Référence de la convention (facultatif)" maxLength={255} placeholder="Référence de la convention ou de l’attestation" value={form.data.evidence_reference} onChange={(e) => form.setData('evidence_reference', e.target.value)} error={form.errors.evidence_reference} />
  </div><p className="field-hint">Déclarez vous-même votre stage. Les critères de dépôt sont recalculés dès l’enregistrement.</p><button className="button-primary" disabled={form.processing}>Enregistrer mon stage</button></form>;
}
export default function ShowEnrollment({ enrollment: e, payments, internship, eligibility, personal, operationKey, today }: Props) {
  const page = usePage<SharedData & { errors: Record<string, string> }>();
  const permissions = page.props.auth.permissions;
  const base = personal ? '/my-enrollments' : '/enrollments';
  const canManage = !personal && permissions.includes('enrollments.manage');
  const canPay = !personal && permissions.includes('payments.manage');
  const canDeclare = personal && permissions.includes('internships.self');
  const canReview = !personal && permissions.includes('eligibility.review');
  return <AppLayout title="Détail de l’inscription" subtitle="Inscriptions et règlements"><Head title={'Inscription · ' + e.student.card_number} />
    <p className="breadcrumb"><Link href={base}>{personal ? 'Mes inscriptions' : 'Inscriptions et règlements'}</Link><span>/</span><strong>{e.student.card_number}</strong></p>
    <div className="page-intro"><div><h1>{e.student.last_name} {e.student.first_name}</h1><p>{e.year.name} · {e.level.name} · Carte {e.student.card_number}</p><p className="reference-cell">{e.reference} · Inscription du {dateLabel(e.enrolled_on)}</p></div><Link href={base} className="button-secondary">Retour à la liste</Link></div>
    {permissions.includes(personal ? 'progression.self' : 'progression.view') && <p><Link className="button-secondary" href={(personal ? '/my-progression/' : '/progression/') + e.id}>Bilan annuel et matières à reprendre</Link></p>}
    {page.props.errors.revision && <div><FormErrors errors={{ revision: page.props.errors.revision }} /><a className="table-action" href={base + '/' + e.id}>Recharger l’inscription</a></div>}
    {!e.editable && <p className="notice notice-neutral">Les nouvelles écritures sont indisponibles : vérifiez le statut de l’année, de l’inscription et du dossier étudiant.{e.cancelled && <> Inscription annulée : {e.cancellation_reason}</>}</p>}
    <section className="stats-grid enrollment-stats" aria-label="Situation du règlement">{[['Montant à payer', money(e.amount_due)], ['Total payé', money(e.paid_amount)], ['Reste à payer', money(e.balance)]].map(([label, value]) => <article className="stat-card stat-blue" key={label}><h2 className="stat-label">{label}</h2><p className="stat-value money-value">{value}</p><p className="stat-caption">{e.cancelled ? 'Inscription annulée' : paymentLabels[e.payment_status]}</p></article>)}</section>
    <section className="panel schooling-section"><h2>Historique des règlements</h2><div className="table-scroll" tabIndex={0} role="region" aria-label="Historique des règlements"><table className="data-table"><thead><tr>{['Reçu / Date', 'Montant', 'Mode / Motif', 'Statut', 'Actions'].map((label) => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>
      {payments.map((payment) => <tr key={payment.id}><td className="reference-cell">{payment.receipt_number}<span className="table-secondary">{dateLabel(payment.paid_on)}</span></td><td>{money(payment.amount)}</td><td>{payment.method === 'cash' ? 'Espèces' : 'Chèque ' + payment.cheque_number}<span className="table-secondary">{payment.purpose}</span></td><td>{payment.voided_at ? <><span className="status-tag status-inactive">Annulé</span><span className="table-secondary">{payment.void_reason}</span></> : <span className="status-tag">Enregistré</span>}</td><td><Link href={base + '/' + e.id + '/payments/' + payment.id + '/receipt'} className="table-action">Voir le reçu</Link>{canPay && !payment.voided_at && <details className="schooling-details"><summary>Corriger cette écriture</summary><ReasonForm key={e.revision} enrollment={e} payment={payment} mode="void" /></details>}</td></tr>)}
      {!payments.length && <tr><td colSpan={5}><div className="empty-state"><p>Aucun paiement enregistré.</p></div></td></tr>}
    </tbody></table></div>
    {canPay && e.editable && e.balance > 0 && <details className="schooling-details" open><summary>Enregistrer un règlement</summary><PaymentForm key={e.revision} enrollment={e} operationKey={operationKey!} today={today} /></details>}
    </section>
    <section className="panel schooling-section"><h2>Stage déclaré</h2>{internship ? <dl className="profile-details"><div><dt>Entreprise</dt><dd>{internship.company}</dd></div><div><dt>Intitulé</dt><dd>{internship.title}</dd></div><div><dt>Période</dt><dd>{dateLabel(internship.starts_on)} – {dateLabel(internship.ends_on)}</dd></div><div><dt>Référence de la convention</dt><dd>{internship.evidence_reference || 'Non renseignée'}</dd></div></dl> : <p>Aucun stage déclaré pour cette inscription.</p>}
      {canDeclare && e.editable && <details className="schooling-details" open={!internship}><summary>{internship ? 'Modifier la déclaration' : 'Déclarer un stage'}</summary><InternshipForm key={e.revision} enrollment={e} internship={internship} /></details>}
      {canDeclare && e.editable && internship && <details className="schooling-details"><summary>Retirer ma déclaration erronée</summary><ReasonForm key={e.revision} enrollment={e} mode="remove" personal /></details>}
    </section>
    <section className="panel schooling-section"><h2>Critères d’éligibilité</h2><p className="eligibility-status"><span className="status-tag">{eligibilityLabels[eligibility.status]}</span></p>
      <ul className="eligibility-criteria">{eligibility.criteria.map((criterion) => <li key={criterion.key}><span className={'criterion-mark' + (criterion.met ? ' is-met' : '')}>{criterion.met ? '✓' : '—'}</span><div><strong>{criterion.label} · {criterion.met ? 'Rempli' : 'Non rempli'}</strong><p>{criterion.detail}</p></div></li>)}</ul>
      {eligibility.review && <p className="notice notice-neutral">Dernière observation administrative : {eligibilityLabels[eligibility.review.result]}, le {dateLabel(eligibility.review.created_at)}. {eligibility.review.reason}{!eligibility.review.current && <><br />Observation antérieure à une modification. L’éligibilité affichée est calculée avec la situation actuelle.</>}</p>}
      <p className="field-hint">Dès que les trois critères sont remplis, le dépôt est disponible sans examen administratif préalable.</p>{personal && eligibility.status === 'eligible' && permissions.includes('reports.self') && <Link href="/my-reports" className="button-primary">Déposer mon rapport</Link>}
      {canReview && e.editable && <><p className="notice notice-neutral">Résultat des critères actuels : <strong>{eligibilityLabels[eligibility.computed_result]}</strong>. Le résultat est recalculé lors de l’enregistrement.</p><ReasonForm key={e.revision} enrollment={e} mode="review" /></>}
    </section>
    {canManage && <section className="panel schooling-section"><details><summary>{e.cancelled ? 'Rétablir l’inscription' : 'Annuler une inscription erronée'}</summary><ReasonForm key={e.revision} enrollment={e} mode="status" /></details></section>}
  </AppLayout>;
}
