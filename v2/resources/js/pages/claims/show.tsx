import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '../../layouts/app-layout';
import FormErrors from '../../components/form-errors';
import { claimActions, claimEvents, claimStatuses, type ClaimSummary, type ClaimSubject } from '../../types/claims';
import { meetingDate } from '../../types/collaboration';
import type { Page } from '../../types/students';

type Claim = ClaimSummary & { revision: number; body: string; subjects: ClaimSubject[]; publication: { number: number; semester: string; href: string | null } | null; document: { href: string; size: number } | null };
type ClaimEvent = { id: number; actor_name: string; actor_role: string; action: string; from_status: string | null; to_status: string; body: string; assignee_name: string | null; created_at: string };
type Assignee = { id: number; name: string };
function ActionForm({ claim, actions, assignees, operationKey, path }: { claim: Claim; actions: string[]; assignees: Assignee[]; operationKey: string; path: string }) {
  const form = useForm({ revision: claim.revision, operation_key: operationKey, action: actions[0], body: '', assigned_to: '' });
  function submit(e: FormEvent) { e.preventDefault(); form.post('/claims/' + claim.id + '/actions', { preserveScroll: true }); }
  return <form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} />{form.errors.revision && <a className="table-action" href={path}>Recharger le dossier avant de réessayer</a>}
    <label className="form-field">Action<select value={form.data.action} onChange={(e) => form.setData({ ...form.data, action: e.target.value, assigned_to: '' })}>{actions.map((action) => <option key={action} value={action}>{claimActions[action]}</option>)}</select></label>
    {form.data.action === 'escalate' && <><label className="form-field">Responsable de filière destinataire<select required value={form.data.assigned_to} onChange={(e) => form.setData('assigned_to', e.target.value)}><option value="">Choisir une personne habilitée…</option>{assignees.map((u) => <option key={u.id} value={u.id}>{u.name}</option>)}</select></label><p className="field-hint">Le destinataire aura accès à ce dossier et à son justificatif. Une nouvelle transmission retire l’accès lié à l’affectation précédente.</p>{!assignees.length && <p className="notice notice-neutral">Aucun responsable de filière actif et habilité n’est disponible.</p>}</>}
    {form.data.action === 'resolve' && <p className="notice notice-neutral">Cette réponse clôt le traitement de la demande. Une correction de notes se fait séparément dans les évaluations, avec une nouvelle publication.</p>}
    {form.data.action === 'withdraw' && <p className="notice notice-neutral">La demande sera retirée du traitement. Son contenu et son historique resteront consultables.</p>}
    <label className="form-field">{form.data.action === 'resolve' ? 'Réponse finale et suite donnée' : form.data.action === 'comment' ? 'Votre complément' : 'Motif et commentaire'}<textarea required minLength={5} maxLength={5000} rows={5} value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} /></label><p className="field-hint">Ce texte sera visible par l’étudiant et les personnes habilitées au suivi.</p><button className="button-primary" disabled={form.processing || (form.data.action === 'escalate' && !form.data.assigned_to)}>{claimActions[form.data.action]}</button>
  </form>;
}

export default function ClaimShow({ claim: c, personal, events, actions, assignees, operationKey }: { claim: Claim; personal: boolean; events: Page<ClaimEvent>; actions: string[]; assignees: Assignee[]; operationKey: string }) {
  const base = personal ? '/my-claims' : '/claims';
  return <AppLayout title={personal ? 'Ma réclamation' : 'Suivi de réclamation'} subtitle={c.reference}><Head title={c.subject} /><p className="breadcrumb"><Link href={base}>Réclamations</Link><span>/</span><strong className="claim-reference">{c.reference}</strong></p>
    <div className="page-intro"><div><h1>{c.subject}</h1><p>{c.student} · {c.card_number} · {c.year} · {c.level}</p><p>Déposée le {meetingDate(c.created_at)}</p></div><span className="status-tag">{claimStatuses[c.status]}</span></div>
    <section className="panel schooling-section"><h2>Demande de l’étudiant</h2><p className="claim-body">{c.body}</p>{c.publication && <p>Bulletin : {c.publication.semester} · publication {c.publication.number}{c.publication.href && <> · <Link className="table-action" href={c.publication.href}>Consulter cette publication</Link></>}</p>}{!!c.subjects.length && <ul>{c.subjects.map((s) => <li key={s.key}>{s.label}</li>)}</ul>}{c.document && <p><a className="button-secondary" href={c.document.href}>Télécharger le justificatif PDF ({Math.max(1, Math.ceil(c.document.size / 1024))} Ko)</a></p>}{c.assignee && <p><strong>Responsable de filière affecté :</strong> {c.assignee}</p>}</section>
    {actions.length > 0 && <section className="panel schooling-section"><h2>{personal ? 'Compléter ma demande' : 'Traitement de la réclamation'}</h2><ActionForm key={c.id + '-' + c.revision} claim={c} actions={actions} assignees={assignees} operationKey={operationKey} path={base + '/' + c.id} /></section>}
    <section className="panel schooling-section"><h2>Historique et réponses</h2><p>Les réponses les plus récentes apparaissent en premier. Chaque étape est conservée.</p><ol className="claim-timeline">{events.data.map((e) => <li key={e.id}><div className="claim-event-heading"><h3>{claimEvents[e.action]}</h3><time dateTime={e.created_at}>{meetingDate(e.created_at)}</time></div><p className="field-hint">{e.actor_name} · {e.actor_role === 'student' ? 'Étudiant' : e.actor_role === 'schooling' ? 'Service scolarité' : 'Responsable de filière'}</p><p className="claim-body">{e.body}</p>{e.action === 'escalate' && e.assignee_name && <p>Destinataire : {e.assignee_name}</p>}<span className="status-tag">{claimStatuses[e.to_status]}</span></li>)}</ol><nav className="pagination" aria-label="Pages de l’historique"><p>{events.total} événement(s)</p><div>{events.prev_page_url && <Link className="button-secondary" href={events.prev_page_url}>Plus récents</Link>}{events.next_page_url && <Link className="button-secondary" href={events.next_page_url}>Plus anciens</Link>}</div></nav></section>
    <Link className="button-secondary" href={base}>Retour aux réclamations</Link>
  </AppLayout>;
}
