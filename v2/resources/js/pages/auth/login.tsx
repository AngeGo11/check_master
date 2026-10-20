import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Brand from '../../components/brand';

export default function Login() {
  const form = useForm({ email: '', password: '', remember: false });

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    form.post('/login', { onFinish: () => form.reset('password') });
  }

  return (
    <>
      <Head title="Connexion" />
      <main className="login-page">
        <section className="login-story" aria-label="Check Master">
          <Brand />
          <div className="story-content">
            <span className="eyebrow">UNIVERSITÉ FÉLIX HOUPHOUËT-BOIGNY</span>
            <h1>Chaque mémoire<br />mérite un suivi<br /><em>à la hauteur.</em></h1>
            <p>Un espace commun pour les étudiants, les enseignants et l’administration du Master MIAGE.</p>
          </div>
          <p className="story-footer">Clarté. Rigueur. Confiance.</p>
        </section>
        <section className="login-form-section" aria-labelledby="login-title">
          <div className="login-form-wrap">
            <span className="eyebrow">VOTRE ESPACE UNIVERSITAIRE</span>
            <h2 id="login-title">Heureux de vous retrouver.</h2>
            <p className="muted">Connectez-vous à votre compte Check Master.</p>
            <form onSubmit={submit} className="login-form">
              <div className="form-field">
                <label htmlFor="email">Adresse e-mail</label>
                <input id="email" name="email" type="email" autoComplete="username" autoFocus required maxLength={254}
                  value={form.data.email} onChange={(e) => form.setData('email', e.target.value)}
                  aria-invalid={Boolean(form.errors.email)} aria-describedby={form.errors.email ? 'email-error' : undefined} />
                {form.errors.email && <p id="email-error" className="field-error" role="alert">{form.errors.email}</p>}
              </div>
              <div className="form-field">
                <label htmlFor="password">Mot de passe</label>
                <input id="password" name="password" type="password" autoComplete="current-password" required maxLength={255}
                  value={form.data.password} onChange={(e) => form.setData('password', e.target.value)}
                  aria-invalid={Boolean(form.errors.password)} aria-describedby={form.errors.password ? 'password-error' : undefined} />
                {form.errors.password && <p id="password-error" className="field-error" role="alert">{form.errors.password}</p>}
              </div>
              <label className="checkbox-label">
                <input type="checkbox" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />
                Rester connecté sur cet appareil
              </label>
              <button className="button-primary" type="submit" disabled={form.processing} aria-busy={form.processing}>
                {form.processing ? 'Connexion en cours…' : 'Accéder à mon espace'}
                <span aria-hidden="true">↗</span>
              </button>
            </form>
            <p className="login-help">Votre compte est fourni par l’administration. En cas de difficulté, contactez votre référent pédagogique.</p>
          </div>
        </section>
      </main>
    </>
  );
}
