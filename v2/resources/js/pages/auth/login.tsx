import { Head, useForm } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import Brand from '../../components/brand';
import Icon from '../../components/icon';

export default function Login() {
  const form = useForm({ email: '', password: '', remember: false });
  const [showPassword, setShowPassword] = useState(false);
  const [showHelp, setShowHelp] = useState(false);

  function submit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    form.post('/login', {
      onFinish: () => {
        form.reset('password');
        setShowPassword(false);
      },
    });
  }

  return (
    <>
      <Head title="Connexion" />
      <main className="login-page">
        <section className="login-story" aria-labelledby="story-title">
          <div className="story-content">
            <div className="academic-symbol"><span><Icon name="graduate" /></span></div>
            <h2 id="story-title">Gestion simplifiée</h2>
            <p>Une plateforme dédiée à la gestion des commissions de validation et au suivi de votre parcours académique.</p>
            <ul className="story-features">
              <li><Icon name="document" /> Rapports et soutenances</li>
              <li><Icon name="users" /> Commissions de validation</li>
              <li><Icon name="graduate" /> Suivi académique</li>
            </ul>
          </div>
          <p className="story-footer">Université Félix Houphouët-Boigny · MIAGE</p>
        </section>
        <section className="login-form-section" aria-labelledby="login-title">
          <div className="login-form-wrap">
            <Brand stacked />
            <h1 id="login-title">Bienvenue !</h1>
            <p className="login-subtitle">Connectez-vous à votre plateforme de gestion de commission de validation.</p>
            <form onSubmit={submit} className="login-card">
              <div className="form-field">
                <label htmlFor="email">Adresse e-mail</label>
                <div className="input-wrap">
                  <Icon name="mail" />
                  <input id="email" name="email" type="email" autoComplete="username" autoFocus required maxLength={254}
                    placeholder="Votre adresse e-mail"
                    value={form.data.email} onChange={(e) => form.setData('email', e.target.value)}
                    aria-invalid={Boolean(form.errors.email)} aria-describedby={form.errors.email ? 'email-error' : undefined} />
                </div>
                {form.errors.email && <p id="email-error" className="field-error" role="alert">{form.errors.email}</p>}
              </div>
              <div className="form-field">
                <label htmlFor="password">Mot de passe</label>
                <div className="input-wrap">
                  <Icon name="lock" />
                  <input id="password" name="password" type={showPassword ? 'text' : 'password'} autoComplete="current-password"
                    required maxLength={255} placeholder="Votre mot de passe" className="password-input"
                    value={form.data.password} onChange={(e) => form.setData('password', e.target.value)}
                    aria-invalid={Boolean(form.errors.password)} aria-describedby={form.errors.password ? 'password-error' : undefined} />
                  <button type="button" className="password-toggle" onClick={() => setShowPassword(!showPassword)}
                    aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'} aria-pressed={showPassword}>
                    <Icon name={showPassword ? 'eye-off' : 'eye'} />
                  </button>
                </div>
                {form.errors.password && <p id="password-error" className="field-error" role="alert">{form.errors.password}</p>}
              </div>
              <div className="login-options">
                <label className="checkbox-label">
                  <input type="checkbox" name="remember" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />
                  Se souvenir de moi
                </label>
                <button type="button" className="text-button" aria-expanded={showHelp} aria-controls="password-help"
                  onClick={() => setShowHelp(!showHelp)}>Mot de passe oublié ?</button>
              </div>
              <p id="password-help" className="login-help" hidden={!showHelp}>
                Contactez votre référent pédagogique ou l’administration pour obtenir de l’aide et récupérer votre accès.
              </p>
              <button className="button-primary" type="submit" disabled={form.processing} aria-busy={form.processing}>
                <Icon name="lock" /> {form.processing ? 'Connexion en cours…' : 'Se connecter'}
              </button>
            </form>
            <p className="login-footer">CHECK MASTER · Plateforme de gestion académique</p>
          </div>
        </section>
      </main>
    </>
  );
}
