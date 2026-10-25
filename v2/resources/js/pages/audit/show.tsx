import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/app-layout';
import { auditDate, auditUrl, type AuditEvent, type AuditFilters } from '../../types/audit';

export default function AuditShow({ event: e, filters }: { event: AuditEvent & { details: { label: string; value: string }[] }; filters: AuditFilters }) {
  const back = auditUrl('/audit', filters);
  return <AppLayout title="Piste d’audit" subtitle="Détail d’une action enregistrée"><Head title={e.label} />
    <p className="breadcrumb"><Link href={back}>Piste d’audit</Link><span>/</span><strong>{e.key}</strong></p>
    <div className="page-intro"><div><h1>{e.label}</h1><p>{e.module} · <time dateTime={e.created_at}>{auditDate(e.created_at)}</time> (UTC)</p></div><Link className="button-secondary" href={back}>Retour au journal</Link></div>
    <section className="panel schooling-section"><h2>Action enregistrée</h2><dl className="audit-details">
      <div><dt>Utilisateur</dt><dd>{e.actor}{e.actor_id !== null && <> · compte #{e.actor_id}</>}</dd></div>
      <div><dt>Dossier concerné</dt><dd>{e.references.join(' · ') || 'Aucun dossier associé'}</dd></div>
      <div><dt>Référence de l’événement</dt><dd className="audit-code">{e.key}</dd></div>
      <div><dt>Code de l’action</dt><dd className="audit-code">{e.action}</dd></div>
      {e.details.map((d, index) => <div key={d.label + index}><dt>{d.label}</dt><dd>{d.value}</dd></div>)}
    </dl></section>
    <p className="field-hint">Le nom affiché est celui du compte actuel, ou le nom conservé lors d’une réclamation si le compte n’est plus disponible. Les contenus des messages, pièces jointes, notes et motifs privés restent dans leur dossier, soumis à leurs droits d’accès.</p>
  </AppLayout>;
}
