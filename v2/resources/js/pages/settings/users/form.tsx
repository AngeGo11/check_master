import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../../components/form-errors';
import Icon from '../../../components/icon';
import AppLayout from '../../../layouts/app-layout';
import type { AccessOption, SharedData } from '../../../types';

type Group = AccessOption & { type_ids: number[] };
type Account = { id: number; name: string; email: string; is_active: boolean; must_change_password: boolean; type_ids: number[]; group_ids: number[] };
type Props = { account: Account | null; types: AccessOption[]; groups: Group[] };

export default function UserForm({ account, types, groups }: Props) {
  const { auth } = usePage<SharedData>().props;
  const title = account ? 'Modifier un utilisateur' : 'Ajouter un utilisateur';
  const form = useForm({ name: account?.name ?? '', email: account?.email ?? '', is_active: account?.is_active ?? true, type_ids: account?.type_ids ?? [], group_ids: account?.group_ids ?? [], password: '', password_confirmation: '' });
  const eligibleGroups = groups.filter((group) => group.type_ids.some((id) => form.data.type_ids.includes(id)));
  function toggleType(id: number) {
    const next = form.data.type_ids.includes(id) ? form.data.type_ids.filter((value) => value !== id) : [...form.data.type_ids, id];
    const eligible = groups.filter((group) => group.type_ids.some((type) => next.includes(type))).map((group) => group.id);
    form.setData((data) => ({ ...data, type_ids: next, group_ids: data.group_ids.filter((id) => eligible.includes(id)) }));
  }
  function submit(event: FormEvent) {
    event.preventDefault();
    form.transform(({ password, password_confirmation, ...fields }) => account ? fields : { ...fields, password, password_confirmation });
    if (account) form.put('/settings/users/' + account.id);
    else form.post('/settings/users', { onFinish: () => form.reset('password', 'password_confirmation') });
  }
  return <AppLayout title={title} subtitle="Gestion des utilisateurs du système">
    <Head title={title} />
    <p className="breadcrumb"><Link href="/settings">Paramètres généraux</Link><span>/</span><Link href="/settings/users">Utilisateurs</Link><span>/</span><strong>{account ? 'Modification' : 'Ajout'}</strong></p>
    <div className="page-intro"><div><h1>{title}</h1><p>Renseignez les informations du compte et ses accès.</p></div><Link href="/settings/users" className="button-secondary"><Icon name="arrow" /> Retour à la liste</Link></div>
    <form onSubmit={submit} className="panel management-form">
      <FormErrors errors={form.errors} />
      <fieldset><legend>Informations du compte</legend><div className="form-grid">
        <div className="form-field"><label htmlFor="name">Nom complet</label><input id="name" name="name" required maxLength={255} autoComplete="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} aria-invalid={Boolean(form.errors.name)} /></div>
        <div className="form-field"><label htmlFor="email">Adresse e-mail</label><input id="email" name="email" type="email" required maxLength={254} autoComplete="off" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} aria-invalid={Boolean(form.errors.email)} /></div>
        <div className="form-field"><label htmlFor="status">Statut du compte</label><select id="status" value={form.data.is_active ? 'active' : 'inactive'} disabled={account?.id === auth.user?.id} onChange={(e) => form.setData('is_active', e.target.value === 'active')}><option value="active">Actif</option><option value="inactive">Inactif</option></select><p className="field-hint">Un compte inactif ne peut pas se connecter.</p></div>
      </div></fieldset>
      <fieldset><legend>Types d’utilisateur</legend><p className="field-hint">Sélectionnez le ou les types correspondant à la personne.</p><div className="choice-grid">
        {types.map((type) => <label className="choice" key={type.id}><input type="checkbox" checked={form.data.type_ids.includes(type.id)} onChange={() => toggleType(type.id)} /><span>{type.name}</span></label>)}
      </div></fieldset>
      <fieldset><legend>Groupes utilisateurs</legend><p className="field-hint">Les groupes proposés correspondent aux types sélectionnés. Retirer un type retire les groupes qui ne lui correspondent plus.</p><div className="choice-grid">
        {eligibleGroups.map((group) => <label className="choice" key={group.id}><input type="checkbox" checked={form.data.group_ids.includes(group.id)} onChange={() => form.setData('group_ids', form.data.group_ids.includes(group.id) ? form.data.group_ids.filter((id) => id !== group.id) : [...form.data.group_ids, group.id])} /><span>{group.name}</span></label>)}
      </div>{!eligibleGroups.length && <p className="field-hint">Choisissez un type pour afficher les groupes disponibles.</p>}</fieldset>
      {!account && <fieldset><legend>Premier accès</legend><p className="field-hint">Définissez un mot de passe temporaire. L’utilisateur devra le personnaliser à sa première connexion. Aucun e-mail n’est envoyé automatiquement.</p><div className="form-grid">
        <div className="form-field"><label htmlFor="password">Mot de passe temporaire</label><input id="password" name="password" type="password" minLength={12} maxLength={255} required autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} /><p className="field-hint">12 caractères minimum.</p></div>
        <div className="form-field"><label htmlFor="password_confirmation">Confirmer le mot de passe</label><input id="password_confirmation" name="password_confirmation" type="password" minLength={12} maxLength={255} required autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} /></div>
      </div></fieldset>}
      <div className="form-actions"><button type="submit" className="button-primary" disabled={form.processing} aria-busy={form.processing}>{form.processing ? 'Enregistrement…' : 'Enregistrer'}</button><Link href="/settings/users" className="button-secondary">Annuler</Link></div>
    </form>
  </AppLayout>;
}
