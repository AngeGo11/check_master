import { Head, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Icon, { type IconName } from '../components/icon';
import AppLayout from '../layouts/app-layout';
import type { SharedData } from '../types';

const indicators: { label: string; icon: IconName; tone: string }[] = [
  { label: 'Étudiants inscrits', icon: 'graduate', tone: 'blue' },
  { label: 'Rapports soumis', icon: 'document', tone: 'green' },
  { label: 'Demandes de soutenance', icon: 'calendar', tone: 'orange' },
  { label: 'Enseignants', icon: 'users', tone: 'blue' },
];

function Panel({ title, subtitle, icon, children }: PropsWithChildren<{ title: string; subtitle?: string; icon: IconName }>) {
  return (
    <section className="panel" aria-label={title}>
      <div className="panel-heading">
        <span className="panel-icon"><Icon name={icon} /></span>
        <div><h2>{title}</h2>{subtitle && <p>{subtitle}</p>}</div>
      </div>
      {children}
    </section>
  );
}

function Unavailable({ children, compact = false }: PropsWithChildren<{ compact?: boolean }>) {
  return (
    <div className={'empty-state' + (compact ? ' empty-state-compact' : '')}>
      <span className="empty-icon"><Icon name="clock" /></span>
      <h3>Données indisponibles</h3>
      <p>{children}</p>
    </div>
  );
}

export default function Dashboard() {
  const { auth } = usePage<SharedData>().props;
  return (
    <AppLayout>
      <Head title="Tableau de bord" />
      <p className="breadcrumb">Accueil <span aria-hidden="true">/</span> <strong>Tableau de bord</strong></p>
      <div className="page-intro">
        <div><h1>Bienvenue, {auth.user?.name} !</h1><p>Retrouvez le suivi de votre activité académique.</p></div>
        <span className="badge"><Icon name="check" /> Compte connecté</span>
      </div>
      <section className="stats-grid" aria-label="Indicateurs généraux">
        {indicators.map(({ label, icon, tone }) => (
          <article className={'stat-card stat-' + tone} key={label}>
            <div className="stat-heading"><h2>{label}</h2><span className="stat-icon"><Icon name={icon} /></span></div>
            <p className="stat-value" aria-label="Valeur indisponible">—</p>
            <p className="stat-caption">Données indisponibles</p>
          </article>
        ))}
      </section>
      <p className="availability-note">Les indicateurs et le suivi académique seront disponibles à l’ouverture des services.</p>
      <div className="charts-grid">
        <Panel title="Évolution des étudiants" subtitle="Par promotion et niveau d’étude" icon="chart">
          <Unavailable>Les effectifs par promotion apparaîtront ici lorsque le service sera disponible.</Unavailable>
        </Panel>
        <Panel title="Répartition" subtitle="Types d’utilisateurs" icon="pie">
          <Unavailable>La répartition des utilisateurs sera disponible avec la gestion des rôles.</Unavailable>
        </Panel>
      </div>
      <div className="dashboard-section">
        <Panel title="Suivi des demandes de soutenance" subtitle="Évolution sur les 12 derniers mois" icon="calendar">
          <Unavailable>Le suivi des demandes apparaîtra ici dès l’ouverture du service des soutenances.</Unavailable>
        </Panel>
      </div>
      <div className="details-grid">
        <Panel title="Dernier rapport" icon="document">
          <Unavailable compact>Les informations du dernier rapport seront affichées à l’ouverture du service des rapports.</Unavailable>
        </Panel>
        <Panel title="Rapports en attente" icon="clock">
          <Unavailable compact>Les rapports à examiner apparaîtront ici selon vos droits d’accès.</Unavailable>
        </Panel>
        <Panel title="Commission d’évaluation" icon="users">
          <Unavailable compact>La composition de la commission sera disponible avec le service des évaluations.</Unavailable>
        </Panel>
      </div>
      <section id="mon-profil" className="panel profile-panel" aria-labelledby="profile-title" tabIndex={-1}>
        <span className="profile-icon"><Icon name="user" /></span>
        <div><h2 id="profile-title">Mon profil</h2><p className="profile-name">{auth.user?.name}</p><p className="profile-email">{auth.user?.email}</p></div>
        <span className="profile-status"><Icon name="check" /> Compte connecté</span>
      </section>
    </AppLayout>
  );
}
