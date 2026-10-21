import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../../components/form-errors';
import Icon from '../../../components/icon';
import AppLayout from '../../../layouts/app-layout';
import type { AccessOption, SharedData } from '../../../types';

type Account = { id: number; name: string; email: string; is_active: boolean; must_change_password: boolean; types: AccessOption[]; groups: AccessOption[] };
type Props = {
  users: { data: Account[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null };
  filters: { search?: string; type_id?: string; group_id?: string; status?: string };
  types: AccessOption[]; groups: AccessOption[];
  statistics: { total: number; active: number; inactive: number };
};

export default function Users({ users, filters, types, groups, statistics }: Props) {
  const { auth } = usePage<SharedData>().props;
  const canManage = auth.permissions.includes('users.manage');
  const form = useForm({ search: filters.search ?? '', type_id: String(filters.type_id ?? ''), group_id: String(filters.group_id ?? ''), status: filters.status ?? '' });
  function search(event: FormEvent) {
    event.preventDefault();
    form.get('/settings/users', { preserveState: true, replace: true });
  }
  return <AppLayout title="Liste des utilisateurs" subtitle="Gestion des utilisateurs du système">
    <Head title="Liste des utilisateurs" />
    <p className="breadcrumb"><Link href="/settings">Paramètres généraux</Link><span>/</span><strong>Utilisateurs</strong></p>
    <div className="page-intro"><div><h1>Liste des utilisateurs</h1><p>Retrouvez les comptes et leurs accès à Check Master.</p></div>
      {canManage && <Link href="/settings/users/create" className="button-primary"><Icon name="plus" /> Ajouter un utilisateur</Link>}
    </div>
    <div className="stats-grid account-stats">
      {[['Total des utilisateurs', statistics.total, 'blue'], ['Utilisateurs actifs', statistics.active, 'green'], ['Utilisateurs inactifs', statistics.inactive, 'orange']].map(([label, value, tone]) =>
        <section className={'stat-card stat-' + tone} key={label}><h2 className="stat-label">{label}</h2><p className="stat-value">{value}</p></section>)}
    </div>
    <section className="panel users-panel" aria-label="Recherche et liste des utilisateurs">
      <form className="user-filters" onSubmit={search}>
        <div className="form-field search-field"><label htmlFor="search">Rechercher</label><input id="search" type="search" maxLength={150} placeholder="Nom ou adresse e-mail" value={form.data.search} onChange={(e) => form.setData('search', e.target.value)} /></div>
        <div className="form-field"><label htmlFor="type-filter">Type</label><select id="type-filter" value={form.data.type_id} onChange={(e) => form.setData('type_id', e.target.value)}><option value="">Tous les types</option>{types.map((type) => <option key={type.id} value={type.id}>{type.name}</option>)}</select></div>
        <div className="form-field"><label htmlFor="group-filter">Groupe</label><select id="group-filter" value={form.data.group_id} onChange={(e) => form.setData('group_id', e.target.value)}><option value="">Tous les groupes</option>{groups.map((group) => <option key={group.id} value={group.id}>{group.name}</option>)}</select></div>
        <div className="form-field"><label htmlFor="status-filter">Statut</label><select id="status-filter" value={form.data.status} onChange={(e) => form.setData('status', e.target.value)}><option value="">Tous les statuts</option><option value="active">Actif</option><option value="inactive">Inactif</option></select></div>
        <div className="filter-actions"><button type="submit" className="button-primary" disabled={form.processing}><Icon name="search" /> Rechercher</button><Link href="/settings/users" className="text-button">Réinitialiser</Link></div>
      </form>
      <FormErrors errors={form.errors} />
      <div className="table-scroll" role="region" aria-label="Liste des utilisateurs" tabIndex={0}>
        <table className="data-table"><thead><tr><th scope="col">Utilisateur</th><th scope="col">Type</th><th scope="col">Groupe</th><th scope="col">Statut</th>{canManage && <th scope="col">Actions</th>}</tr></thead>
          <tbody>{users.data.map((account) => <tr key={account.id}>
            <td><strong>{account.name}</strong><span className="table-secondary">{account.email}</span>{account.must_change_password && <span className="table-secondary">Mot de passe à personnaliser</span>}</td>
            <td>{account.types.map((type) => type.name).join(', ') || 'Non attribué'}</td>
            <td>{account.groups.map((group) => group.name).join(', ') || 'Aucun groupe'}</td>
            <td><span className={'status-tag ' + (account.is_active ? 'status-active' : 'status-inactive')}>{account.is_active ? 'Actif' : 'Inactif'}</span></td>
            {canManage && <td><Link href={'/settings/users/' + account.id + '/edit'} className="table-action" aria-label={'Modifier le compte de ' + account.name}>Modifier</Link></td>}
          </tr>)}</tbody>
        </table>
      </div>
      {!users.data.length && <div className="empty-state"><h2>Aucun utilisateur trouvé</h2><p>Essayez une autre recherche ou modifiez les filtres.</p></div>}
      <nav className="pagination" aria-label="Pagination des utilisateurs"><span>{users.total} résultat{users.total > 1 ? 's' : ''} · Page {users.current_page} sur {users.last_page}</span><div>{users.prev_page_url && <Link href={users.prev_page_url} className="button-secondary" aria-label="Page précédente">Précédent</Link>}{users.next_page_url && <Link href={users.next_page_url} className="button-secondary" aria-label="Page suivante">Suivant</Link>}</div></nav>
    </section>
  </AppLayout>;
}
