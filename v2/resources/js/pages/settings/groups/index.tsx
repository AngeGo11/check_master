import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../../components/form-errors';
import AppLayout from '../../../layouts/app-layout';
import type { AccessOption, PermissionCode, SharedData } from '../../../types';

type Permission = { code: PermissionCode; name: string };
type Group = AccessOption & { users_count: number; protected: boolean; types: AccessOption[]; permissions: PermissionCode[] };

function GroupRow({ group, permissions, canManage }: { group: Group; permissions: Permission[]; canManage: boolean }) {
  const form = useForm({ permissions: group.permissions });
  function submit(event: FormEvent) { event.preventDefault(); form.put('/settings/groups/' + group.id, { preserveScroll: true }); }
  return <section className="group-row" aria-label={group.name}>
    <div className="group-heading"><div><h2>{group.name}</h2><p>{group.types.map((type) => type.name).join(' · ')}</p></div><span className="status-tag">{group.users_count} compte{group.users_count > 1 ? 's' : ''}</span></div>
    <ul className="permission-list">{group.permissions.map((code) => <li key={code}>{permissions.find((permission) => permission.code === code)?.name}</li>)}</ul>
    {!group.permissions.length && <p className="field-hint">Aucun droit de gestion des comptes.</p>}
    {group.protected && <p className="field-hint">Les droits de ce groupe sont protégés pour conserver l’administration de la plateforme.</p>}
    {canManage && !group.protected && <details className="permission-editor"><summary>Modifier les permissions</summary><form onSubmit={submit}>
      <FormErrors errors={form.errors} /><fieldset><legend>Autorisations pour ce groupe</legend><div className="choice-grid">
        {permissions.map((permission) => <label className="choice" key={permission.code}><input type="checkbox" checked={form.data.permissions.includes(permission.code)} onChange={() => form.setData('permissions', form.data.permissions.includes(permission.code) ? form.data.permissions.filter((code) => code !== permission.code) : [...form.data.permissions, permission.code])} /><span>{permission.name}</span></label>)}
      </div></fieldset><p className="field-hint">La gestion des comptes permet aussi d’attribuer leurs groupes. La configuration des permissions permet de déléguer ces droits.</p><button type="submit" className="button-primary" disabled={form.processing} aria-busy={form.processing}>{form.processing ? 'Enregistrement…' : 'Enregistrer les permissions'}</button>
    </form></details>}
  </section>;
}

export default function Groups({ groups, permissions }: { groups: Group[]; permissions: Permission[] }) {
  const { auth } = usePage<SharedData>().props;
  return <AppLayout title="Groupes utilisateurs" subtitle="Types de comptes et permissions">
    <Head title="Groupes utilisateurs" /><p className="breadcrumb"><Link href="/settings">Paramètres généraux</Link><span>/</span><strong>Groupes utilisateurs</strong></p>
    <div className="page-intro"><div><h1>Liste des groupes utilisateurs</h1><p>Les permissions ci-dessous concernent la gestion des comptes et des accès.</p></div><Link href="/settings" className="button-secondary">Retour aux paramètres</Link></div>
    <div className="panel groups-panel">{groups.map((group) => <GroupRow key={group.id} group={group} permissions={permissions} canManage={auth.permissions.includes('groups.manage')} />)}</div>
  </AppLayout>;
}
