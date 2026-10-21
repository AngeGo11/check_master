import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import AppLayout from '../../layouts/app-layout';
import type { SharedData } from '../../types';
import type { StudentDetail } from '../../types/students';

type Account = { id: number; name: string; email: string; is_active: boolean } | null;
type Props = { student: StudentDetail | null; personal: boolean; canLink: boolean; account: Account };

function AccountLink({ student, account }: { student: StudentDetail; account: Account }) {
  const form = useForm({ account_email: account?.email ?? '', revision: student.revision });
  function submit(event: FormEvent) {
    event.preventDefault();
    form.transform((data) => ({ ...data, account_email: data.account_email.trim() || null }));
    form.put('/students/' + student.id + '/account', { preserveScroll: true });
  }
  return <section className="panel student-account-panel"><h2>Compte de connexion</h2>
    {account ? <p className="field-hint"><strong>{account.name}</strong> · {account.email} · {account.is_active ? 'Actif' : 'Inactif'}</p> : <p className="field-hint">Aucun compte n’est rattaché à ce dossier.</p>}
    <form className="student-link-form" onSubmit={submit}><FormErrors errors={form.errors} />
      <div className="form-field"><label htmlFor="account_email">Adresse e-mail du compte à rattacher</label><input id="account_email" type="email" maxLength={254} autoComplete="off" value={form.data.account_email} onChange={(e) => form.setData('account_email', e.target.value)} aria-invalid={Boolean(form.errors.account_email)} aria-describedby="account-link-help" /><p className="field-hint" id="account-link-help">Vérifiez que ce compte appartient bien à cet étudiant. Il doit être actif et de type Étudiant. Laissez le champ vide pour retirer le rattachement.</p></div>
      {form.errors.revision && <a className="table-action" href={'/students/' + student.id}>Recharger la fiche</a>}
      <div className="form-actions"><button className="button-primary" disabled={form.processing} aria-busy={form.processing}>{form.processing ? 'Enregistrement…' : 'Enregistrer le rattachement'}</button><Link className="button-secondary" href="/settings/users">Gérer les comptes</Link></div>
    </form>
  </section>;
}

export default function StudentShow({ student, personal, canLink, account }: Props) {
  const { auth } = usePage<SharedData>().props;
  const title = personal ? 'Mon dossier étudiant' : 'Dossier étudiant';
  const birthDate = student?.date_of_birth ? student.date_of_birth.split('-').reverse().join('/') : 'Non renseignée';
  return <AppLayout title={title} subtitle="Informations personnelles et pédagogiques"><Head title={title} />
    <p className="breadcrumb"><Link href={personal ? '/dashboard' : '/students'}>{personal ? 'Accueil' : 'Étudiants'}</Link><span>/</span><strong>{title}</strong></p>
    <div className="page-intro"><div><h1>{student ? student.last_name + ' ' + student.first_name : title}</h1><p>{student ? 'N° Carte : ' + student.card_number : 'Retrouvez les informations de votre scolarité.'}</p></div>
      {student && !personal && auth.permissions.includes('students.manage') && <Link href={'/students/' + student.id + '/edit'} className="button-primary">Modifier le dossier</Link>}
    </div>
    {!student ? <section className="panel"><div className="empty-state"><h2>Aucun dossier rattaché</h2><p>Contactez la scolarité pour faire rattacher votre dossier à votre compte.</p></div></section> : <>
      {student.is_archived && <p className="notice notice-neutral" role="status">Ce dossier est archivé. Ses informations sont conservées.</p>}
      <section className="panel student-detail-panel" aria-label="Informations du dossier"><h2>Informations personnelles</h2><dl className="profile-details">
        {[['Nom', student.last_name], ['Prénoms', student.first_name], ['N° Carte', student.card_number], ['E-mail de contact', student.email], ['Date de naissance', birthDate], ['Sexe', student.gender ?? 'Non renseigné']].map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}
      </dl><h2>Situation pédagogique</h2><dl className="profile-details">
        {[['Niveau', student.study_level.name], ['Promotion', student.promotion.name], ['Statut étudiant', student.student_status.name]].map(([label, value]) => <div key={label}><dt>{label}</dt><dd>{value}</dd></div>)}
      </dl><p className="field-hint">Le suivi des notes, des règlements et de l’éligibilité au dépôt de mémoire sera disponible avec les services correspondants.</p>
      {personal && <p className="field-hint">Pour faire corriger une information, contactez la scolarité.</p>}</section>
      {canLink && <AccountLink key={student.id + ':' + student.revision} student={student} account={account} />}
    </>}
  </AppLayout>;
}
