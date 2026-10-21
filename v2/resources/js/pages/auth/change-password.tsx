import { Head, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import AppLayout from '../../layouts/app-layout';
import type { SharedData } from '../../types';

export default function ChangePassword() {
  const { auth } = usePage<SharedData>().props;
  const form = useForm({ current_password: '', password: '', password_confirmation: '' });
  function submit(event: FormEvent) {
    event.preventDefault();
    form.patch('/password', { onFinish: () => form.reset() });
  }
  return <AppLayout title="Modifier mon mot de passe" subtitle="Sécurité du compte">
    <Head title="Modifier mon mot de passe" /><div className="page-intro"><div><h1>{auth.user?.must_change_password ? 'Personnalisez votre mot de passe' : 'Modifier mon mot de passe'}</h1><p>{auth.user?.must_change_password ? 'Choisissez votre mot de passe personnel pour accéder à votre espace.' : 'Les autres sessions seront déconnectées après la modification.'}</p></div></div>
    <form className="panel management-form password-form" onSubmit={submit}>
      <FormErrors errors={form.errors} />
      <div className="form-field"><label htmlFor="current_password">{auth.user?.must_change_password ? 'Mot de passe temporaire' : 'Mot de passe actuel'}</label><input id="current_password" name="current_password" type="password" required maxLength={255} autoComplete="current-password" value={form.data.current_password} onChange={(e) => form.setData('current_password', e.target.value)} /></div>
      <div className="form-field"><label htmlFor="password">Nouveau mot de passe</label><input id="password" name="password" type="password" required minLength={12} maxLength={255} autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} /><p className="field-hint">12 caractères minimum, différent du mot de passe actuel.</p></div>
      <div className="form-field"><label htmlFor="password_confirmation">Confirmer le nouveau mot de passe</label><input id="password_confirmation" name="password_confirmation" type="password" required minLength={12} maxLength={255} autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} /></div>
      <button type="submit" className="button-primary" disabled={form.processing} aria-busy={form.processing}>{form.processing ? 'Enregistrement…' : 'Enregistrer mon mot de passe'}</button>
    </form>
  </AppLayout>;
}
