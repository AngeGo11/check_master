import { Head, Link, usePage } from '@inertiajs/react';
import Icon, { type IconName } from '../components/icon';
import AppLayout from '../layouts/app-layout';
import type { SharedData } from '../types';
import { meetingDate } from '../types/collaboration';

type Card = { key: string; label: string; value: string | number; caption: string; href: string | null; icon: IconName; tone: string };
type Item = { id: string | number; title: string; detail: string; href: string | null; date: string | null; value: string | number | null };
type PanelData = { key: string; title: string; description: string; items: Item[]; empty: string; href: string | null; kind: 'list' | 'distribution' };
type DashboardData = {
  space: string; spaces: { key: string; label: string }[]; title: string; description: string;
  cards: Record<string, Card>; actions: Record<string, { key: string; label: string; href: string; icon: IconName }>;
  panels: Record<string, PanelData>;
  journey: { title: string; eligible: boolean; context_valid: boolean; description: string; criteria: { key: string; label: string; met: boolean }[]; href: string; label: string } | null;
};
const priorities: Record<string, string[]> = {
  'platform-administrator': ['accounts', 'groups', 'levels', 'reports', 'defenses', 'meetings', 'messages'],
  schooling: ['defenses_requested', 'grade_drafts', 'tuition', 'defenses', 'messages'],
  communications: ['to_forward', 'reports', 'messages'],
  'validation-committee': ['votes_due', 'meetings', 'reports', 'results_due', 'defenses', 'messages'],
  teacher: ['defenses', 'professional_profile', 'results_due', 'messages'],
  'level-manager': ['results_due', 'defenses', 'messages', 'professional_profile'],
  'program-manager': ['jury_outcomes', 'results_due', 'defenses', 'professional_profile', 'messages'],
  secretary: ['students', 'messages', 'levels', 'professional_profile'],
  student: ['my_report', 'my_grades', 'defenses', 'student_record', 'enrollment', 'messages'],
};
function Panel({ panel: p }: { panel: PanelData }) {
  const maximum = Math.max(1, ...p.items.map((i) => typeof i.value === 'number' ? i.value : 0));
  return <section className="panel role-dashboard-panel" aria-labelledby={'panel-' + p.key}>
    <div className="panel-heading"><span className="panel-icon"><Icon name={p.kind === 'distribution' ? 'chart' : 'document'} /></span><div><h2 id={'panel-' + p.key}>{p.title}</h2><p>{p.description}</p></div></div>
    {p.items.length ? <ul className={'dashboard-items' + (p.kind === 'distribution' ? ' dashboard-distribution' : '')}>{p.items.map((item) => <li key={item.id}>
      <div className="dashboard-item-heading">{item.href ? <Link className="table-action" href={item.href}>{item.title}</Link> : <strong>{item.title}</strong>}{item.value !== null && <span className="dashboard-item-value">{item.value}</span>}</div>
      <p>{item.detail}</p>{item.date && <time dateTime={item.date}>{meetingDate(item.date)} · Abidjan</time>}
      {p.kind === 'distribution' && typeof item.value === 'number' && <div className="dashboard-bar" aria-hidden="true"><span style={{ width: (item.value / maximum * 100) + '%' }} /></div>}
    </li>)}</ul> : <div className="empty-state empty-state-compact"><span className="empty-icon"><Icon name="check" /></span><p>{p.empty}</p></div>}
    {p.href && <Link href={p.href} className="table-action dashboard-panel-link">Tout consulter <span aria-hidden="true">→</span></Link>}
  </section>;
}
export default function Dashboard({ dashboard: d }: { dashboard: DashboardData }) {
  const { auth } = usePage<SharedData>().props;
  const order = priorities[d.space] ?? [];
  const panels = Object.values(d.panels).sort((a, b) => {
    const position = (key: string) => order.includes(key) ? order.indexOf(key) : order.length;
    return position(a.key) - position(b.key);
  });
  return <AppLayout title={d.title} subtitle="Tableau de bord Check Master"><Head title={d.title} />
    <p className="breadcrumb">Accueil <span aria-hidden="true">/</span> <strong>{d.title}</strong></p>
    <div className="page-intro"><div><h1>Bienvenue, {auth.user?.name} !</h1><p>{d.description}</p></div><span className="badge"><Icon name="user" /> {d.title}</span></div>
    {d.spaces.length > 1 && <nav className="dashboard-spaces" aria-label="Choisir votre espace"><p>Mes espaces</p><div>{d.spaces.map((space) => <Link key={space.key} href={'/dashboard?space=' + encodeURIComponent(space.key)} aria-current={d.space === space.key ? 'page' : undefined} className={d.space === space.key ? 'button-primary' : 'button-secondary'}>{space.label}</Link>)}</div></nav>}
    {Object.keys(d.cards).length > 0 && <section className="stats-grid role-dashboard-stats" aria-label={'Indicateurs — ' + d.title}>{Object.values(d.cards).map((c) => <article className={'stat-card stat-' + c.tone} key={c.key}><div className="stat-heading"><h2>{c.label}</h2><span className="stat-icon"><Icon name={c.icon} /></span></div><p className={'stat-value' + (typeof c.value === 'string' ? ' dashboard-value-text' : '')}>{c.value}</p><p className="stat-caption">{c.caption}</p>{c.href && <Link href={c.href} className="table-action dashboard-card-link" aria-label={c.label + ' — consulter'}>Consulter <span aria-hidden="true">→</span></Link>}</article>)}</section>}
    <section className="panel dashboard-shortcuts" aria-labelledby="dashboard-shortcuts-title"><div className="panel-heading"><span className="panel-icon"><Icon name="arrow" /></span><div><h2 id="dashboard-shortcuts-title">Accès rapides</h2><p>Les actions utiles à votre espace.</p></div></div><div>{Object.values(d.actions).map((a) => <Link className="button-secondary" key={a.key} href={a.href}><Icon name={a.icon} /> {a.label}</Link>)}</div></section>
    {d.journey && <section className="panel dashboard-journey" aria-labelledby="journey-title"><div className="panel-heading"><span className="panel-icon"><Icon name="graduate" /></span><div><h2 id="journey-title">{d.journey.title}</h2><p>{d.journey.description}</p></div></div><ul>{d.journey.criteria.map((c) => <li key={c.key} className={c.met ? 'criterion-met' : ''}><Icon name={c.met ? 'check' : 'clock'} /><span>{c.label}<small>{c.met ? 'Critère rempli' : 'À compléter'}</small></span></li>)}</ul>{!d.journey.context_valid && <p className="notice notice-neutral">Votre inscription ou votre dossier doit être régularisé auprès de la scolarité avant le dépôt.</p>}<div className="form-actions"><span className={'status-tag ' + (d.journey.eligible ? 'status-active' : 'status-inactive')}>{d.journey.eligible ? 'Critères réunis' : 'Dépôt non disponible'}</span><Link className="button-primary" href={d.journey.href}>{d.journey.label}</Link></div></section>}
    {panels.length ? <div className="role-dashboard-grid">{panels.map((p) => <Panel key={p.key} panel={p} />)}</div> : <section className="panel"><div className="empty-state"><h2>Votre espace est prêt</h2><p>Les services s’afficheront selon les droits accordés à votre compte. Contactez l’administration si un accès attendu manque.</p></div></section>}
    <section id="mon-profil" className="panel profile-panel" aria-labelledby="profile-title" tabIndex={-1}><span className="profile-icon"><Icon name="user" /></span><div><h2 id="profile-title">Mon profil</h2><p className="profile-name">{auth.user?.name}</p><p className="profile-email">{auth.user?.email}</p></div><Link href="/profile" className="table-action">Consulter mon profil</Link></section>
  </AppLayout>;
}
