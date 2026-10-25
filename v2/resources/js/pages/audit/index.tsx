import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import AppLayout from '../../layouts/app-layout';
import FormErrors from '../../components/form-errors';
import { auditDate, auditUrl, type AuditEvent, type AuditFilters } from '../../types/audit';
import type { Page } from '../../types/students';

type Option = { value: string; label: string; module?: string };
export default function AuditIndex({ events, statistics, filters, modules, actions, canExport, exportLimit }: {
  events: Page<AuditEvent>; statistics: { total: number; actors: number; modules: number; last_at: string | null };
  filters: AuditFilters; modules: Option[]; actions: Option[]; canExport: boolean; exportLimit: number;
}) {
  const { errors } = usePage().props;
  const [form, setForm] = useState<AuditFilters>(filters);
  function filter(e: FormEvent) { e.preventDefault(); router.get(auditUrl('/audit', form)); }
  const set = (key: keyof AuditFilters, value: string | boolean) => setForm((f) => ({ ...f, [key]: value, ...(key === 'module' ? { action: '' } : {}) }));
  return <AppLayout title="Piste d’audit" subtitle="Historique des actions enregistrées"><Head title="Piste d’audit" />
    <p className="breadcrumb"><Link href="/dashboard">Accueil</Link><span>/</span><strong>Piste d’audit</strong></p>
    <div className="page-intro"><div><h1>Journal des actions</h1><p>Retrouvez qui est intervenu, quand et sur quel dossier.</p></div>{canExport && <a className="button-primary" href={auditUrl('/audit/export', filters)}>Exporter la sélection CSV</a>}</div>
    <section className="stats-grid" aria-label="Indicateurs de la sélection">
      {[['Actions enregistrées', statistics.total], ['Utilisateurs identifiés', statistics.actors], ['Modules concernés', statistics.modules]].map(([label, value]) => <article className="stat-card stat-blue" key={label}><h2 className="stat-label">{label}</h2><p className="stat-value">{value}</p><p className="stat-caption">Selon les filtres appliqués</p></article>)}
      <article className="stat-card stat-green"><h2 className="stat-label">Dernière action</h2><p className="audit-last-date">{auditDate(statistics.last_at)}</p><p className="stat-caption">Heure d’Abidjan · UTC</p></article>
    </section>
    <section className="panel schooling-section"><h2>Rechercher dans le journal</h2><FormErrors errors={errors} />
      <form className="collaboration-filters audit-filters" onSubmit={filter}>
        <label>Module<select value={form.module ?? ''} onChange={(e) => set('module', e.target.value)}><option value="">Tous les modules</option>{modules.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}</select></label>
        <label>Action<select value={form.action ?? ''} onChange={(e) => set('action', e.target.value)}><option value="">Toutes les actions</option>{actions.filter((o) => !form.module || o.module === form.module).map((o) => <option key={o.module + ':' + o.value} value={o.value}>{o.label}</option>)}</select></label>
        <label>Utilisateur<input value={form.actor ?? ''} onChange={(e) => set('actor', e.target.value)} maxLength={100} placeholder="Nom ou identifiant #42" /></label>
        <label>Du<input type="date" value={form.from ?? ''} onChange={(e) => set('from', e.target.value)} /></label>
        <label>Au, inclus<input type="date" value={form.to ?? ''} min={form.from || undefined} onChange={(e) => set('to', e.target.value)} /></label>
        <label className="audit-checkbox"><input type="checkbox" checked={!!form.unattributed} onChange={(e) => set('unattributed', e.target.checked)} /> Sans compte associé uniquement</label>
        <button className="button-secondary">Appliquer les filtres</button><Link className="table-action" href="/audit">Réinitialiser</Link>
      </form>
      <p className="field-hint">Dates et heures d’Abidjan (UTC). Les indicateurs et l’export portent sur la sélection appliquée, toutes pages comprises.</p>
      <div className="table-scroll" role="region" tabIndex={0} aria-label="Historique des actions"><table className="data-table"><thead><tr><th>Date et heure</th><th>Utilisateur</th><th>Module</th><th>Action</th><th>Dossier</th><th>Détail</th></tr></thead><tbody>
        {events.data.map((e) => <tr key={e.key}><td><time dateTime={e.created_at}>{auditDate(e.created_at)}</time></td><td>{e.actor}{e.actor_id !== null && <Link className="table-secondary table-action" href={auditUrl('/audit', { ...filters, actor: '#' + e.actor_id, unattributed: false })}>Compte #{e.actor_id}</Link>}</td><td>{e.module}</td><td><strong>{e.label}</strong><span className="table-secondary audit-code">{e.key}</span></td><td>{e.references.join(' · ') || '—'}</td><td><Link className="table-action" href={auditUrl('/audit/' + e.source + '/' + e.id, { ...filters, page: events.current_page })}>Consulter</Link></td></tr>)}
        {!events.data.length && <tr><td colSpan={6}>Aucune action enregistrée pour cette sélection.</td></tr>}
      </tbody></table></div>
      <nav className="pagination" aria-label="Pages du journal"><p>{events.total} action(s) · page {events.current_page} sur {events.last_page}</p><div>{events.prev_page_url && <Link className="button-secondary" href={events.prev_page_url}>Précédent</Link>}{events.next_page_url && <Link className="button-secondary" href={events.next_page_url}>Suivant</Link>}</div></nav>
      {canExport && <p className="field-hint">Export UTF-8 pour tableur, jusqu’à {exportLimit.toLocaleString('fr-FR')} actions. Au-delà, affinez les filtres : aucune ligne n’est tronquée.</p>}
    </section>
    <p className="field-hint">Ce journal couvre les opérations métier enregistrées dans la v2. Les connexions et tentatives refusées ne sont pas comptabilisées. Les anciens journaux n’ont pas été importés.</p>
  </AppLayout>;
}
