import { Head, Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Icon, { type IconName } from '../components/icon';
import AppLayout from '../layouts/app-layout';
import type { SharedData } from '../types';
import { reportLabels, type ReportStatus } from '../types/reports';

const indicators: { label: string; icon: IconName; tone: string }[] = [
  { label: 'Étudiants enregistrés', icon: 'graduate', tone: 'blue' },
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

export default function Dashboard({ studentCount, studentsByLevel, reportCount, pendingReportCount, latestReport, reportScope }: { reportScope: 'global' | 'commission' | null; studentCount: number | null; studentsByLevel: { name: string; total: number }[] | null; reportCount: number | null; pendingReportCount: number | null; latestReport: { title: string; status: ReportStatus; reference: string; href: string } | null }) {
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
        {indicators.map(({ label, icon, tone }, index) => (
          <article className={'stat-card stat-' + tone} key={label}>
            <div className="stat-heading"><h2>{label}</h2><span className="stat-icon"><Icon name={icon} /></span></div>
            <p className="stat-value" aria-label={(index === 0 && studentCount !== null) || (index === 1 && reportCount !== null) ? undefined : 'Valeur indisponible'}>{index === 0 && studentCount !== null ? studentCount : index === 1 && reportCount !== null ? reportCount : '—'}</p>
            <p className="stat-caption">{index === 0 && studentCount !== null ? 'Dossiers non archivés' : index === 1 && reportCount !== null ? reportScope === 'commission' ? 'Versions transmises à ma commission' : 'Mémoires déposés, toutes les années' : 'Données indisponibles'}</p>
          </article>
        ))}
      </section>
      <p className="availability-note">Les indicateurs disponibles correspondent aux dossiers enregistrés et à vos droits de consultation.</p>
      <div className="charts-grid">
        <Panel title="Évolution des étudiants" subtitle="Effectifs actuels par niveau d’étude" icon="chart">
          {studentsByLevel === null ? <Unavailable>Les effectifs sont réservés aux personnes habilitées à consulter les dossiers étudiants.</Unavailable> : studentsByLevel.length ? <><ul className="level-counts">{studentsByLevel.map((level) => <li key={level.name}><span>{level.name}</span><strong>{level.total}</strong></li>)}</ul><p className="field-hint">Situation actuelle des dossiers non archivés.</p><Link href="/students" className="table-action">Consulter les étudiants</Link></> : <div className="empty-state"><h3>Aucun dossier étudiant</h3><p>Les effectifs apparaîtront dès l’enregistrement des premiers dossiers.</p><Link href="/students" className="table-action">Consulter les étudiants</Link></div>}
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
          <div className="empty-state empty-state-compact">{latestReport ? <><h3>{latestReport.title}</h3><p>{reportLabels[latestReport.status]}</p><Link href={latestReport.href} className="table-action">Consulter le mémoire</Link></> : <><h3>Aucun mémoire disponible</h3><p>Les dépôts accessibles à votre compte apparaîtront ici.</p></>}</div>
        </Panel>
        <Panel title="Rapports en attente" icon="clock">
          {pendingReportCount !== null ? <div className="empty-state empty-state-compact"><h3>{pendingReportCount} mémoire(s) en attente</h3><p>{reportScope === 'commission' ? 'En cours d’examen par la commission' : 'À transmettre ou en cours d’examen'}</p><Link href="/reports" className="table-action">Consulter les dépôts</Link></div> : <Unavailable compact>Les rapports à examiner sont réservés aux personnes habilitées.</Unavailable>}
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
