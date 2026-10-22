import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect, useState, type FormEvent } from 'react';
import AppLayout from '../../layouts/app-layout';
import FormErrors from '../../components/form-errors';
import { InputField, SelectField } from '../../components/schooling-fields';
import type { AccessOption } from '../../types';
import { categoryLabels, priorityLabels, type Contact, type MessageDraft } from '../../types/collaboration';
type Props = { draft: MessageDraft; selected: Contact[]; groups: AccessOption[]; limit: number };
export default function Compose(props: Props) { return <Composer key={(props.draft.id ?? 'new') + '-' + props.draft.revision} {...props} />; }
function Composer({ draft, selected: initial, groups, limit }: Props) {
  const [selected, setSelected] = useState(initial); const [search, setSearch] = useState(''); const [contacts, setContacts] = useState<Contact[]>([]); const [more, setMore] = useState(false); const [contactError, setContactError] = useState(''); const [loading, setLoading] = useState(false);
  const form = useForm({ subject: draft.subject, body: draft.body, priority: draft.priority, category: draft.category, revision: draft.revision, operation_key: draft.operation_key, source_id: draft.source_id, source_mode: draft.source_mode, recipient_ids: initial.map((x) => x.id), action: 'send' });
  const reply = draft.source_mode === 'reply';
  useEffect(() => {
    if (reply) return;
    const abort = new AbortController(); const timer = setTimeout(async () => {
      try { const response = await fetch('/messages/contacts?search=' + encodeURIComponent(search), { signal: abort.signal, headers: { Accept: 'application/json' } }); if (!response.ok) throw new Error(); const data = await response.json(); setContacts(data.users); setMore(data.more); setContactError(''); }
      catch { if (!abort.signal.aborted) setContactError('Impossible de charger les contacts. Rechargez la page.'); }
    }, 250);
    return () => { clearTimeout(timer); abort.abort(); };
  }, [search, reply]);
  function add(users: Contact[]) { setSelected((current) => [...new Map([...current, ...users].map((u) => [u.id, u])).values()]); setContactError(''); }
  async function addGroup(id: string) {
    if (!id || loading) return; setLoading(true);
    try { const response = await fetch('/messages/contacts?group_id=' + id, { headers: { Accept: 'application/json' } }); const data = await response.json(); if (!response.ok) { setContactError(data.message ?? 'Ce groupe est indisponible.'); return; } add(data.users); }
    catch { setContactError('Impossible de charger ce groupe.'); } finally { setLoading(false); }
  }
  function save(action: 'draft' | 'send') { form.transform((data) => ({ ...data, action, recipient_ids: selected.map((x) => x.id) })); if (draft.id) form.put('/messages/' + draft.id); else form.post('/messages'); }
  function submit(e: FormEvent) { e.preventDefault(); save('send'); }
  return <AppLayout title="Messagerie" subtitle="Rédiger un message"><Head title={draft.id ? 'Modifier le brouillon' : 'Nouveau message'} /><p className="breadcrumb"><Link href="/messages">Messagerie</Link><span>/</span><strong>{draft.id ? 'Brouillon' : reply ? 'Répondre' : 'Nouveau message'}</strong></p>
    <div className="page-intro"><div><h1>{reply ? 'Répondre à l’expéditeur' : draft.source_mode === 'forward' ? 'Transférer le message' : 'Nouveau message'}</h1><p>Vos échanges sont envoyés dans Check Master.</p></div></div>
    <section className="panel schooling-section"><form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} />
      <fieldset className="recipient-picker"><legend>Destinataires · {selected.length} / {limit}</legend><ul className="selected-contacts">{selected.map((u) => <li key={u.id}><span><strong>{u.name}</strong><small>{u.email}</small></span>{!reply && <button type="button" className="table-action" aria-label={'Retirer ' + u.name} onClick={() => setSelected(selected.filter((x) => x.id !== u.id))}>Retirer</button>}</li>)}</ul>
        {!reply && <><label className="form-field">Rechercher un contact<input type="search" maxLength={100} value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Nom ou adresse e-mail" /></label><div className="contact-results">{contacts.filter((u) => !selected.some((x) => x.id === u.id)).map((u) => <button type="button" key={u.id} onClick={() => add([u])}><strong>{u.name}</strong><small>{u.email}</small><span>Ajouter</span></button>)}</div>{more && <p className="field-hint">Précisez votre recherche pour voir d’autres contacts.</p>}{groups.length > 0 && <label className="form-field">Ajouter les membres d’un groupe<select value="" disabled={loading} onChange={(e) => void addGroup(e.target.value)}><option value="">Choisir un groupe…</option>{groups.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}</select></label>}</>}
        {contactError && <p className="field-error" role="alert">{contactError}</p>}{selected.length > limit && <p className="field-error" role="alert">Limite de {limit} destinataires dépassée. Retirez des contacts avant l’envoi.</p>}<p className="field-hint">Seules les personnes affichées ci-dessus recevront ce message. Vérifiez cette liste avant l’envoi.</p>
      </fieldset>
      <InputField id="subject" label="Objet" maxLength={255} value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} error={form.errors.subject} />
      <div className="form-grid"><SelectField id="category" label="Catégorie" value={form.data.category} onChange={(e) => form.setData('category', e.target.value)}>{Object.entries(categoryLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</SelectField><SelectField id="priority" label="Priorité" value={form.data.priority} onChange={(e) => form.setData('priority', e.target.value)}>{Object.entries(priorityLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</SelectField></div>
      <label className="form-field">Message<textarea rows={12} maxLength={20000} value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} aria-invalid={Boolean(form.errors.body)} /></label>
      <div className="form-actions"><button className="button-primary" disabled={form.processing || loading || !selected.length || selected.length > limit || !form.data.subject.trim() || !form.data.body.trim()} aria-busy={form.processing}>Envoyer à {selected.length} destinataire(s)</button><button type="button" className="button-secondary" disabled={form.processing || loading || selected.length > limit} onClick={() => save('draft')}>Enregistrer le brouillon</button><Link className="table-action" href="/messages">Retour à la messagerie</Link></div>
    </form></section>
  </AppLayout>;
}
