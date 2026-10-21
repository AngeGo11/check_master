import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import { InputField, SelectField, dateLabel } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { AccessOption } from '../../types';
import type { Page } from '../../types/students';
import type { ReportSummary, ReportStatus } from '../../types/reports';
import { reportLabels, reportTone } from '../../types/reports';

type Props = { reports: Page<ReportSummary>; years: AccessOption[]; statistics: Record<ReportStatus | 'total', number>;
  filters: { search?: string; status?: ReportStatus; academic_year_id?: number; submitted_on?: string } };
export default function Reports({ reports, years, statistics, filters }: Props) {
  const form = useForm({ search: filters.search ?? '', status: filters.status ?? '', academic_year_id: String(filters.academic_year_id ?? ''), submitted_on: filters.submitted_on ?? '' });
  function submit(event: FormEvent) { event.preventDefault(); form.get('/reports', { replace: true, preserveState: true }); }
  return <AppLayout title="Rapports / Mémoires" subtitle="Consultation et approbation administrative"><Head title="Rapports / Mémoires" />
    <p className="breadcrumb"><Link href="/dashboard">Accueil</Link><span>/</span><strong>Rapports / Mémoires</strong></p>
    <div className="page-intro"><div><h1>Mémoires déposés</h1><p>Consultez les documents, leurs versions et les retours adressés aux étudiants.</p></div></div>
    <section className="stats-grid" aria-label="Suivi des mémoires">{([['total', 'Total mémoires', 'blue'], ['submitted', 'En attente d’approbation', 'orange'], ['approved', 'Approuvés', 'green'], ['changes_requested', 'Corrections demandées', 'blue']] as const).map(([key, label, tone]) => <article className={'stat-card stat-' + tone} key={key}><h2 className="stat-label">{label}</h2><p className="stat-value">{statistics[key]}</p><p className="stat-caption">{filters.academic_year_id ? 'Année sélectionnée' : 'Toutes les années'} · dernière version</p></article>)}</section>
    <section className="panel"><form className="user-filters" onSubmit={submit}>
      <InputField id="report-search" label="Étudiant, carte ou thème" maxLength={150} value={form.data.search} onChange={(e) => form.setData('search', e.target.value)} />
      <SelectField id="report-year" label="Année" value={form.data.academic_year_id} onChange={(e) => form.setData('academic_year_id', e.target.value)}><option value="">Toutes les années</option>{years.map((y) => <option key={y.id} value={y.id}>{y.name}</option>)}</SelectField>
      <SelectField id="report-status" label="Statut" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}><option value="">Tous les statuts</option>{Object.entries(reportLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</SelectField>
      <InputField id="report-date" label="Date du dernier dépôt" type="date" value={form.data.submitted_on} onChange={(e) => form.setData('submitted_on', e.target.value)} />
      <div className="form-actions"><button className="button-primary" disabled={form.processing}>Filtrer</button><Link href="/reports" className="button-secondary">Réinitialiser</Link></div>
    </form><FormErrors errors={form.errors} />
    <div className="table-scroll" tabIndex={0} role="region" aria-label="Liste des mémoires"><table className="data-table"><thead><tr>{['Étudiant / Carte', 'Thème du mémoire', 'Année', 'Dernier dépôt', 'Version', 'Statut', 'Actions'].map((label) => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>
      {reports.data.map((r) => <tr key={r.id}><td><strong>{r.student.last_name} {r.student.first_name}</strong><span className="table-secondary">{r.student.card_number}</span></td><td>{r.title}<span className="table-secondary reference-cell">{r.reference}</span></td><td>{r.year.name}</td><td>{dateLabel(r.latest_submitted_at)}</td><td>{r.version_count}</td><td><span className={'status-tag ' + reportTone[r.status]}>{reportLabels[r.status]}</span></td><td><Link href={'/reports/' + r.id} className="table-action" aria-label={'Consulter le mémoire de ' + r.student.first_name + ' ' + r.student.last_name}>Consulter</Link></td></tr>)}
      {!reports.data.length && <tr><td colSpan={7}><div className="empty-state"><h3>Aucun mémoire trouvé</h3><p>Les dépôts apparaîtront ici lorsqu’un étudiant éligible aura soumis son document.</p></div></td></tr>}
    </tbody></table></div>
    <nav className="pagination" aria-label="Pages des mémoires"><p>{reports.total} résultat(s) · Page {reports.current_page} sur {reports.last_page}</p><div>{reports.prev_page_url && <Link href={reports.prev_page_url} className="button-secondary">Précédent</Link>}{reports.next_page_url && <Link href={reports.next_page_url} className="button-secondary">Suivant</Link>}</div></nav>
    </section>
  </AppLayout>;
}
