import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/app-layout';
import FormErrors from '../../components/form-errors';
import { dateLabel } from '../../components/schooling-fields';
import { categoryLabels, priorityLabels, type MessageDetail } from '../../types/collaboration';
import type { SharedData } from '../../types';
function MailActions({ message: m }: { message: MessageDetail }) {
  const form = useForm({ action: '', revision: m.copy_revision });
  function change(action: string) { form.transform((data) => ({ ...data, action })); form.patch('/messages/' + m.id + '/state', { preserveScroll: true }); }
  return <div className="message-actions"><FormErrors errors={form.errors} />
    {!m.is_sender && m.status === 'sent' && <button type="button" className="button-secondary" disabled={form.processing} onClick={() => change(m.unread ? 'read' : 'unread')}>{m.unread ? 'Marquer comme lu' : 'Marquer comme non lu'}</button>}
    {m.folder === 'archive' || m.folder === 'trash' ? <button type="button" className="button-secondary" disabled={form.processing} onClick={() => change('restore')}>Restaurer dans ma boîte</button> : m.status === 'sent' && <button type="button" className="button-secondary" disabled={form.processing} onClick={() => change('archive')}>Archiver pour moi</button>}
    {m.folder !== 'trash' && <button type="button" className="button-secondary" disabled={form.processing} onClick={() => change('trash')}>Mettre dans ma corbeille</button>}
  </div>;
}
export default function ShowMessage({ message: m }: { message: MessageDetail }) {
  const { errors } = usePage<SharedData & { errors: Record<string, string> }>().props;
  return <AppLayout title="Messagerie" subtitle="Détail du message"><Head title={m.subject || 'Brouillon'} /><p className="breadcrumb"><Link href="/messages">Messagerie</Link><span>/</span><strong>{m.status === 'draft' ? 'Brouillon' : 'Message'}</strong></p>
    <div className="page-intro"><div><h1>{m.subject || 'Sans objet'}</h1><p>{m.reference} · {dateLabel(m.sent_at ?? m.updated_at)}</p></div><span className="status-tag">{m.status === 'draft' ? 'Brouillon privé' : m.kind !== 'message' ? 'Notification de réunion' : priorityLabels[m.priority]}</span></div>
    <FormErrors errors={errors} />{errors.revision && <a className="table-action" href={'/messages/' + m.id}>Recharger le message</a>}
    <section className="panel schooling-section"><dl className="profile-details"><div><dt>De</dt><dd>{m.sender_name}</dd></div><div><dt>À</dt><dd>{m.recipients.map((r) => r.name).join(', ') || 'Brouillon non envoyé'}</dd></div><div><dt>Catégorie</dt><dd>{categoryLabels[m.category]}</dd></div><div><dt>Lecture</dt><dd>{m.is_sender ? 'Votre copie' : m.unread ? 'Non lu dans votre boîte' : 'Lu dans votre boîte'}</dd></div></dl>
      <div className="message-body">{m.body}</div>{m.meeting_link && <Link href={m.meeting_link} className="button-primary">Consulter la réunion et répondre</Link>}
      <div className="form-actions">{m.can_reply && <Link className="button-primary" href={'/messages/create?source_id=' + m.id + '&source_mode=reply'}>Répondre à l’expéditeur</Link>}{m.status === 'sent' && m.folder !== 'trash' && <Link className="button-secondary" href={'/messages/create?source_id=' + m.id + '&source_mode=forward'}>Transférer</Link>}{m.status === 'draft' && m.folder !== 'trash' && <Link className="button-primary" href={'/messages/' + m.id + '/edit'}>Reprendre le brouillon</Link>}</div>
      <MailActions key={m.copy_revision} message={m} /><p className="field-hint">Le classement et la corbeille concernent uniquement votre copie.</p>
    </section>
    {m.history.length > 0 && <section className="panel schooling-section"><h2>Historique accessible de cet échange</h2><ul className="meeting-agenda">{m.history.map((h) => <li key={h.id}><Link href={'/messages/' + h.id}>{h.subject}</Link><span>{h.sender_name}</span></li>)}</ul></section>}
    <Link href="/messages" className="button-secondary">Retour à ma messagerie</Link>
  </AppLayout>;
}
