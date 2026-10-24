import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '../../layouts/app-layout';
import FormErrors from '../../components/form-errors';
import type { ClaimSubject } from '../../types/claims';

type Publication = { id: number; enrollment_id: number; label: string; subjects: ClaimSubject[] };
export default function CreateClaim({ enrollments, publications, selectedPublication, operationKey }: {
  enrollments: { id: number; label: string }[]; publications: Publication[]; selectedPublication: number | null; operationKey: string;
}) {
  const selected = publications.find((p) => p.id === selectedPublication);
  const form = useForm({ enrollment_id: String(selected?.enrollment_id ?? (enrollments.length === 1 ? enrollments[0].id : '')), subject: '', body: '', grade_publication_id: String(selected?.id ?? ''), subject_keys: [] as string[], document: null as File | null, operation_key: operationKey });
  const publication = publications.find((p) => String(p.id) === form.data.grade_publication_id);
  function submit(e: FormEvent) { e.preventDefault(); form.post('/my-claims', { forceFormData: true }); }
  return <AppLayout title="Nouvelle réclamation" subtitle="Adressez votre demande à la scolarité"><Head title="Nouvelle réclamation" /><p className="breadcrumb"><Link href="/my-claims">Mes réclamations</Link><span>/</span><strong>Nouvelle demande</strong></p><div className="page-intro"><div><h1>Déposer une réclamation</h1><p>Précisez votre demande. Vous retrouverez chaque réponse dans son suivi.</p></div></div>
    {!enrollments.length ? <section className="panel schooling-section"><h2>Aucune inscription disponible</h2><p>Une inscription non annulée doit être rattachée à votre dossier étudiant actif. Contactez la scolarité pour faire vérifier votre dossier.</p><Link href="/my-claims" className="button-secondary">Retour au suivi</Link></section> : <section className="panel schooling-section"><form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} />
      <label className="form-field">Inscription concernée<select required value={form.data.enrollment_id} onChange={(e) => form.setData({ ...form.data, enrollment_id: e.target.value, grade_publication_id: '', subject_keys: [] })}><option value="">Choisir une inscription…</option>{enrollments.map((e) => <option key={e.id} value={e.id}>{e.label}</option>)}</select></label>
      <label className="form-field">Objet de la réclamation<input required minLength={5} maxLength={150} value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} placeholder="Ex. Vérification de la note en comptabilité" /></label>
      <label className="form-field">Bulletin publié concerné, si la demande porte sur une note<select value={form.data.grade_publication_id} onChange={(e) => form.setData({ ...form.data, grade_publication_id: e.target.value, subject_keys: [] })}><option value="">Sans bulletin joint à la demande</option>{publications.filter((p) => String(p.enrollment_id) === form.data.enrollment_id).map((p) => <option key={p.id} value={p.id}>{p.label}</option>)}</select></label>
      <p className="field-hint">Pour une note absente ou une demande administrative, décrivez les matières et la situation ci-dessous. Le bulletin choisi conserve sa version, même si une correction est publiée ensuite.</p>
      {publication && <fieldset className="claim-subjects"><legend>UE / ECUE concernées (1 à 20)</legend>{publication.subjects.map((s) => <label key={s.key}><input type="checkbox" checked={form.data.subject_keys.includes(s.key)} onChange={(e) => form.setData('subject_keys', e.target.checked ? [...form.data.subject_keys, s.key] : form.data.subject_keys.filter((k) => k !== s.key))} />{s.label}</label>)}</fieldset>}
      <label className="form-field">Description et résultat attendu<textarea required minLength={5} maxLength={5000} rows={7} value={form.data.body} onChange={(e) => form.setData('body', e.target.value)} /></label>
      <label className="form-field">Justificatif PDF (facultatif, 10 Mo maximum)<input type="file" accept="application/pdf,.pdf" onChange={(e) => form.setData('document', e.target.files?.[0] ?? null)} /></label>
      <p className="field-hint">La pièce jointe est accessible à vous et aux personnes habilitées à traiter votre demande. Les compléments et les réponses sont visibles dans le même historique.</p>
      {form.progress && <p role="status">Envoi : {form.progress.percentage}%</p>}<div className="form-actions"><button className="button-primary" disabled={form.processing || (!!publication && !form.data.subject_keys.length)}>Déposer auprès de la scolarité</button><Link className="button-secondary" href="/my-claims">Annuler</Link></div>
    </form></section>}
  </AppLayout>;
}
