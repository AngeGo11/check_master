import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import Icon from '../../components/icon';
import AppLayout from '../../layouts/app-layout';
import type { SharedData } from '../../types';
import type { Page, StudentOptions, StudentSummary } from '../../types/students';

type Filters = { search?: string; study_level_id?: number; promotion_id?: number; student_status_id?: number; archive?: string };
type Props = StudentOptions & { students: Page<StudentSummary>; filters: Filters; statistics: { total: number; archived: number } };

export default function Students({ students, filters, statistics, levels, promotions, statuses }: Props) {
  const { auth } = usePage<SharedData>().props;
  const form = useForm({ search: filters.search ?? '', study_level_id: String(filters.study_level_id ?? ''), promotion_id: String(filters.promotion_id ?? ''), student_status_id: String(filters.student_status_id ?? ''), archive: filters.archive ?? 'active' });
  function search(event: FormEvent) { event.preventDefault(); form.get('/students', { preserveState: true, replace: true }); }
  return <AppLayout title="Étudiants" subtitle="Gestion des dossiers étudiants">
    <Head title="Liste des étudiants" />
    <p className="breadcrumb"><Link href="/dashboard">Accueil</Link><span>/</span><strong>Étudiants</strong></p>
    <div className="page-intro"><div><h1>Liste des étudiants</h1><p>Retrouvez les étudiants par promotion, niveau et statut.</p></div>
      {auth.permissions.includes('students.manage') && <Link href="/students/create" className="button-primary"><Icon name="plus" /> Ajouter un étudiant</Link>}
    </div>
    <section className="stats-grid student-stats" aria-label="Suivi des étudiants">
      <article className="stat-card stat-blue"><h2 className="stat-label">Total étudiants</h2><p className="stat-value">{statistics.total}</p><p className="stat-caption">Dont {statistics.archived} dossier{statistics.archived > 1 ? 's' : ''} archivé{statistics.archived > 1 ? 's' : ''}</p></article>
      {[['En attente de validation', 'orange'], ['Étudiants validés', 'green'], ['Étudiants refusés', 'blue']].map(([label, tone]) => <article className={'stat-card stat-' + tone} key={label}><h2 className="stat-label">{label}</h2><p className="stat-value" aria-label="Valeur indisponible">—</p><p className="stat-caption">Suivi des rapports indisponible</p></article>)}
    </section>
    <section className="panel" aria-label="Recherche et liste des étudiants">
      <form className="user-filters" onSubmit={search}>
        <div className="form-field search-field"><label htmlFor="search">Rechercher un étudiant</label><input id="search" maxLength={150} placeholder="Nom, prénoms, carte, e-mail ou promotion" value={form.data.search} onChange={(e) => form.setData('search', e.target.value)} /></div>
        <div className="form-field"><label htmlFor="promotion">Promotion</label><select id="promotion" value={form.data.promotion_id} onChange={(e) => form.setData('promotion_id', e.target.value)}><option value="">Toutes les promotions</option>{promotions.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}</select></div>
        <div className="form-field"><label htmlFor="level">Niveau</label><select id="level" value={form.data.study_level_id} onChange={(e) => form.setData('study_level_id', e.target.value)}><option value="">Tous les niveaux</option>{levels.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}</select></div>
        <div className="form-field"><label htmlFor="status">Statut étudiant</label><select id="status" value={form.data.student_status_id} onChange={(e) => form.setData('student_status_id', e.target.value)}><option value="">Tous les statuts</option>{statuses.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}</select></div>
        <div className="form-field"><label htmlFor="archive">Dossiers</label><select id="archive" value={form.data.archive} onChange={(e) => form.setData('archive', e.target.value)}><option value="active">Non archivés</option><option value="archived">Archivés</option><option value="all">Tous les dossiers</option></select></div>
        <div className="form-actions"><button className="button-primary" disabled={form.processing} aria-busy={form.processing}>Filtrer</button><Link href="/students" className="button-secondary">Réinitialiser</Link></div>
      </form>
      <FormErrors errors={form.errors} />
      <div className="table-scroll" tabIndex={0} role="region" aria-label="Tableau des étudiants, défilement horizontal possible"><table className="data-table"><caption className="sr-only">Étudiants correspondant aux filtres</caption><thead><tr><th scope="col">Étudiant</th><th scope="col">N° Carte</th><th scope="col">Niveau</th><th scope="col">Promotion</th><th scope="col">Statut</th><th scope="col">Actions</th></tr></thead><tbody>
        {students.data.map((student) => <tr key={student.id}><td><strong>{student.last_name} {student.first_name}</strong><span className="table-secondary">{student.email}</span>{student.is_archived && <span className="status-tag status-inactive">Archivé</span>}</td><td>{student.card_number}</td><td>{student.study_level.name}</td><td>{student.promotion.name}</td><td><span className="status-tag">{student.student_status.name}</span></td><td><Link href={'/students/' + student.id} className="table-action" aria-label={'Consulter le dossier de ' + student.first_name + ' ' + student.last_name}>Consulter</Link></td></tr>)}
        {!students.data.length && <tr><td colSpan={6}><div className="empty-state"><h3>Aucun étudiant trouvé</h3><p>Vérifiez les filtres ou ajoutez un dossier si vous disposez des droits nécessaires.</p></div></td></tr>}
      </tbody></table></div>
      <nav className="pagination" aria-label="Pages des étudiants"><p>{students.total} résultat{students.total > 1 ? 's' : ''} · Page {students.current_page} sur {students.last_page}</p><div>{students.prev_page_url && <Link href={students.prev_page_url} className="button-secondary">Précédent</Link>}{students.next_page_url && <Link href={students.next_page_url} className="button-secondary">Suivant</Link>}</div></nav>
    </section>
  </AppLayout>;
}
