import { Head, usePage } from '@inertiajs/react';
import AppLayout from '../layouts/app-layout';
import type { SharedData } from '../types';

export default function Dashboard() {
  const { auth } = usePage<SharedData>().props;
  return (
    <AppLayout>
      <Head title="Tableau de bord" />
      <div className="page-intro">
        <span className="eyebrow">TABLEAU DE BORD</span>
        <h1>Bonjour, {auth.user?.name}.</h1>
        <p className="muted">Bienvenue dans votre espace Check Master.</p>
      </div>
      <section className="welcome-card" aria-labelledby="welcome-title">
        <div className="card-heading">
          <span className="badge"><span className="status-dot" /> Espace connecté</span>
          <span className="small-label">MASTER 2 · MIAGE</span>
        </div>
        <h2 id="welcome-title">Votre parcours, au même endroit.</h2>
        <p>Retrouvez ici le suivi de votre mémoire et les échanges liés à sa validation, à mesure que les services seront ouverts.</p>
        <ol className="journey" aria-label="Étapes du parcours prévu">
          <li><span>01</span><strong>Dépôt du mémoire</strong></li>
          <li><span>02</span><strong>Vérification du dossier</strong></li>
          <li><span>03</span><strong>Évaluation en commission</strong></li>
          <li><span>04</span><strong>Décision et compte rendu</strong></li>
        </ol>
      </section>
      <div className="dashboard-grid">
        <section className="panel" aria-labelledby="activity-title">
          <div className="panel-title"><h2 id="activity-title">Activité</h2><span className="muted">Votre suivi</span></div>
          <div className="empty-state">
            <span className="empty-icon" aria-hidden="true">↗</span>
            <h3>Votre espace est prêt.</h3>
            <p>Le dépôt et le suivi des mémoires seront disponibles prochainement.</p>
          </div>
        </section>
        <section className="panel profile-panel" aria-labelledby="profile-title">
          <span className="eyebrow">MON COMPTE</span>
          <h2 id="profile-title">{auth.user?.name}</h2>
          <p className="profile-email">{auth.user?.email}</p>
          <div className="profile-note"><span className="status-dot" />Connexion sécurisée par session</div>
        </section>
      </div>
    </AppLayout>
  );
}
