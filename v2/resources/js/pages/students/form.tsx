import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import AppLayout from '../../layouts/app-layout';
import type { StudentOptions } from '../../types/students';

type Record = { id: number; revision: number; card_number: string; last_name: string; first_name: string; email: string; date_of_birth: string | null; gender: string | null; study_level_id: number; promotion_id: number; student_status_id: number; is_archived: boolean };
type Props = StudentOptions & { student: Record | null };

export default function StudentForm({ student, levels, promotions, statuses }: Props) {
  const title = student ? 'Modifier un étudiant' : 'Ajouter un étudiant';
  const form = useForm({ card_number: student?.card_number ?? '', last_name: student?.last_name ?? '', first_name: student?.first_name ?? '', email: student?.email ?? '', date_of_birth: student?.date_of_birth ?? '', gender: student?.gender ?? '', study_level_id: String(student?.study_level_id ?? ''), promotion_id: String(student?.promotion_id ?? ''), student_status_id: String(student?.student_status_id ?? 1), is_archived: student?.is_archived ?? false, revision: student?.revision ?? 0 });
  function submit(event: FormEvent) {
    event.preventDefault();
    form.transform(({ revision, ...fields }) => student ? { ...fields, revision } : fields);
    if (student) form.put('/students/' + student.id);
    else form.post('/students');
  }
  const textFields = [
    { key: 'card_number', label: 'N° Carte', max: 50, type: 'text' },
    { key: 'email', label: 'Adresse e-mail de contact', max: 254, type: 'email' },
    { key: 'last_name', label: 'Nom', max: 100, type: 'text' },
    { key: 'first_name', label: 'Prénoms', max: 150, type: 'text' },
  ] as const;
  const choices = [
    { key: 'study_level_id', label: 'Niveau', options: levels },
    { key: 'promotion_id', label: 'Promotion', options: promotions },
    { key: 'student_status_id', label: 'Statut étudiant', options: statuses },
  ] as const;
  return <AppLayout title={title} subtitle="Gestion des dossiers étudiants"><Head title={title} />
    <p className="breadcrumb"><Link href="/students">Étudiants</Link><span>/</span><strong>{student ? 'Modification' : 'Ajout'}</strong></p>
    <div className="page-intro"><div><h1>{title}</h1><p>Renseignez l’identité et la situation pédagogique de l’étudiant.</p></div><Link href={student ? '/students/' + student.id : '/students'} className="button-secondary">Retour</Link></div>
    <form onSubmit={submit} className="panel management-form"><FormErrors errors={form.errors} />
      {form.errors.revision && <p className="field-hint"><a href={student ? '/students/' + student.id + '/edit' : '/students/create'} className="table-action">Recharger la fiche</a> Les saisies non enregistrées seront remplacées par la dernière version.</p>}
      <fieldset><legend>Informations personnelles</legend><div className="form-grid">
        {textFields.map(({ key, label, max, type }) => <div className="form-field" key={key}><label htmlFor={key}>{label} *</label><input id={key} name={key} type={type} required maxLength={max} autoComplete="off" value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} aria-invalid={Boolean(form.errors[key])} aria-describedby={form.errors[key] ? key + '-error' : undefined} />{form.errors[key] && <p className="field-error" id={key + '-error'}>{form.errors[key]}</p>}</div>)}
        <div className="form-field"><label htmlFor="date_of_birth">Date de naissance</label><input id="date_of_birth" type="date" value={form.data.date_of_birth} onChange={(e) => form.setData('date_of_birth', e.target.value)} aria-invalid={Boolean(form.errors.date_of_birth)} aria-describedby={form.errors.date_of_birth ? 'birth-error' : undefined} />{form.errors.date_of_birth && <p className="field-error" id="birth-error">{form.errors.date_of_birth}</p>}</div>
        <div className="form-field"><label htmlFor="gender">Sexe</label><select id="gender" value={form.data.gender} onChange={(e) => form.setData('gender', e.target.value)}><option value="">Non renseigné</option><option>Homme</option><option>Femme</option></select></div>
      </div></fieldset>
      <fieldset><legend>Situation pédagogique</legend><div className="form-grid">
        {choices.map(({ key, label, options }) => <div className="form-field" key={key}><label htmlFor={key}>{label} *</label><select id={key} required value={form.data[key]} onChange={(e) => form.setData(key, e.target.value)} aria-invalid={Boolean(form.errors[key])} aria-describedby={form.errors[key] ? key + '-error' : undefined}><option value="">Sélectionner</option>{options.map((option) => <option key={option.id} value={option.id}>{option.name}</option>)}</select>{form.errors[key] && <p className="field-error" id={key + '-error'}>{form.errors[key]}</p>}</div>)}
      </div><p className="field-hint student-form-note">Le statut « À cheval » décrit la situation de l’étudiant. Il ne vaut pas inscription aux matières à rattraper ni validation du paiement.</p></fieldset>
      {student && <fieldset><legend>Archivage du dossier</legend><label className="choice"><input type="checkbox" checked={form.data.is_archived} onChange={(e) => form.setData('is_archived', e.target.checked)} /><span>Archiver ce dossier</span></label><p className="field-hint student-form-note">Le dossier reste consultable et peut être réactivé. Le compte de connexion conserve son propre statut.</p></fieldset>}
      <p className="field-hint">* Champs obligatoires. Le rattachement à un compte de connexion se fait depuis la fiche, par une personne habilitée.</p>
      <div className="form-actions"><button className="button-primary" disabled={form.processing} aria-busy={form.processing}>{form.processing ? 'Enregistrement…' : 'Enregistrer le dossier'}</button><Link href={student ? '/students/' + student.id : '/students'} className="button-secondary">Annuler</Link></div>
    </form>
  </AppLayout>;
}
