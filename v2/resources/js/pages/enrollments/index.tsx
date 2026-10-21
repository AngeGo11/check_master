import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import { InputField, SelectField, money, dateLabel, paymentLabels } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { AccessOption, SharedData } from '../../types';
import type { Page } from '../../types/students';
import type { EnrollmentSummary, Year } from '../../types/enrollments';

type Props = { enrollments: Page<EnrollmentSummary>; personal: boolean; years: Year[]; levels: AccessOption[];
  filters: { search?: string; academic_year_id?: number | null; study_level_id?: number; payment_status?: string; enrolled_on?: string; include_cancelled?: boolean };
  statistics: { total: number; paid: number; partial: number; unpaid: number } | null };
export default function Enrollments({ enrollments, personal, years, levels, filters, statistics }: Props) {
  const permissions = usePage<SharedData>().props.auth.permissions;
  const base = personal ? '/my-enrollments' : '/enrollments';
  const form = useForm({ search: filters.search ?? '', academic_year_id: String(filters.academic_year_id ?? ''), study_level_id: String(filters.study_level_id ?? ''), payment_status: filters.payment_status ?? '', enrolled_on: filters.enrolled_on ?? '', include_cancelled: Boolean(Number(filters.include_cancelled ?? 0)) });
  function submit(event: FormEvent) { event.preventDefault(); form.transform((data) => ({ ...data, include_cancelled: data.include_cancelled ? 1 : 0 })); form.get(base, { preserveState: true, replace: true }); }
  const title = personal ? 'Mes inscriptions' : 'Inscriptions et règlements';
  return <AppLayout title={title} subtitle="Suivi de la scolarité"><Head title={title} />
    <p className="breadcrumb"><Link href="/dashboard">Accueil</Link><span>/</span><strong>{title}</strong></p>
    <div className="page-intro"><div><h1>{title}</h1><p>{personal ? 'Consultez vos inscriptions, règlements et critères d’éligibilité.' : 'Retrouvez les inscriptions et la situation des paiements par année.'}</p></div>{!personal && permissions.includes('enrollments.manage') && <Link href="/enrollments/create" className="button-primary">Nouvelle inscription</Link>}</div>
    {statistics && <><section className="stats-grid" aria-label="Situation des inscriptions de l’année sélectionnée">
      {([['total', 'Total inscriptions', 'blue'], ['partial', 'Partiellement payé', 'orange'], ['paid', 'Tout payé', 'green'], ['unpaid', 'Rien payé', 'blue']] as const).map(([key, label, tone]) => <article className={'stat-card stat-' + tone} key={key}><h2 className="stat-label">{label}</h2><p className="stat-value">{statistics[key]}</p><p className="stat-caption">Année sélectionnée · hors annulations</p></article>)}
    </section>{!filters.academic_year_id && <p className="notice notice-neutral">Aucune année en cours. Sélectionnez une année configurée pour consulter son historique.</p>}</>}
    <section className="panel">{!personal && <form className="user-filters" onSubmit={submit}>
      <InputField id="search" label="Étudiant ou carte" maxLength={150} value={form.data.search} onChange={(e) => form.setData('search', e.target.value)} />
      <SelectField id="year" label="Année académique" value={form.data.academic_year_id} onChange={(e) => form.setData('academic_year_id', e.target.value)}><option value="">Année en cours</option>{years.map((y) => <option key={y.id} value={y.id}>{y.name}</option>)}</SelectField>
      <SelectField id="level" label="Niveau" value={form.data.study_level_id} onChange={(e) => form.setData('study_level_id', e.target.value)}><option value="">Tous les niveaux</option>{levels.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}</SelectField>
      <SelectField id="status" label="Situation du paiement" value={form.data.payment_status} onChange={(e) => form.setData('payment_status', e.target.value)}><option value="">Toutes les situations</option>{Object.entries(paymentLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</SelectField>
      <InputField id="date" label="Date d’inscription" type="date" value={form.data.enrolled_on} onChange={(e) => form.setData('enrolled_on', e.target.value)} />
      <label className="checkbox-label"><input type="checkbox" checked={form.data.include_cancelled} onChange={(e) => form.setData('include_cancelled', e.target.checked)} /> Inclure les inscriptions annulées</label>
      <div className="form-actions"><button className="button-primary" disabled={form.processing}>Filtrer</button><Link href={base} className="button-secondary">Réinitialiser</Link></div>
    </form>}<FormErrors errors={form.errors} />
    <div className="table-scroll" tabIndex={0} role="region" aria-label="Inscriptions, défilement horizontal possible"><table className="data-table"><caption className="sr-only">Liste des inscriptions et situation des règlements</caption><thead><tr>{['Étudiant / Carte', 'Année / Niveau', 'Référence', 'À payer', 'Total payé', 'Reste', 'Date', 'Statut', 'Actions'].map((label) => <th scope="col" key={label}>{label}</th>)}</tr></thead><tbody>
      {enrollments.data.map((e) => <tr key={e.id}><td><strong>{e.student.last_name} {e.student.first_name}</strong><span className="table-secondary">{e.student.card_number}</span></td><td>{e.year.name}<span className="table-secondary">{e.level.name}</span></td><td className="reference-cell">{e.reference}</td><td>{money(e.amount_due)}</td><td>{money(e.paid_amount)}</td><td>{money(e.balance)}</td><td>{dateLabel(e.enrolled_on)}</td><td><span className={'status-tag' + (e.cancelled ? ' status-inactive' : '')}>{e.cancelled ? 'Annulée' : paymentLabels[e.payment_status]}</span></td><td><Link href={base + '/' + e.id} className="table-action" aria-label={'Consulter l’inscription de ' + e.student.first_name + ' ' + e.student.last_name}>Consulter</Link></td></tr>)}
      {!enrollments.data.length && <tr><td colSpan={9}><div className="empty-state"><h3>Aucune inscription trouvée</h3><p>{personal ? 'Seules les inscriptions de votre dossier explicitement rattaché apparaissent ici.' : 'Vérifiez l’année et les filtres, ou enregistrez une inscription.'}</p></div></td></tr>}
    </tbody></table></div>
    <nav className="pagination" aria-label="Pages des inscriptions"><p>{enrollments.total} résultat(s) · Page {enrollments.current_page} sur {enrollments.last_page}</p><div>{enrollments.prev_page_url && <Link href={enrollments.prev_page_url} className="button-secondary">Précédent</Link>}{enrollments.next_page_url && <Link href={enrollments.next_page_url} className="button-secondary">Suivant</Link>}</div></nav>
    </section>
  </AppLayout>;
}
