import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import { InputField, SelectField, money } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { AccessOption } from '../../types';

type Props = { years: AccessOption[]; levels: AccessOption[]; fees: { academic_year_id: number; study_level_id: number; amount: number }[]; today: string };
export default function CreateEnrollment({ years, levels, fees, today }: Props) {
  const form = useForm({ card_number: '', academic_year_id: String(years[0]?.id ?? ''), study_level_id: '', enrolled_on: today });
  const fee = fees.find((f) => f.academic_year_id === Number(form.data.academic_year_id) && f.study_level_id === Number(form.data.study_level_id));
  function submit(event: FormEvent) { event.preventDefault(); form.post('/enrollments'); }
  return <AppLayout title="Nouvelle inscription" subtitle="Inscriptions et règlements"><Head title="Nouvelle inscription" />
    <p className="breadcrumb"><Link href="/enrollments">Inscriptions et règlements</Link><span>/</span><strong>Nouvelle inscription</strong></p>
    <div className="page-intro"><div><h1>Inscrire un étudiant</h1><p>Utilisez le numéro de carte du dossier étudiant existant.</p></div></div>
    {!years.length && <p className="notice notice-neutral">Aucune année en cours. Demandez à la personne habilitée de configurer l’année et ses tarifs.</p>}
    <section className="panel"><form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} /><div className="form-grid">
      <InputField id="card" label="N° de carte étudiant" required maxLength={50} value={form.data.card_number} onChange={(e) => form.setData('card_number', e.target.value)} error={form.errors.card_number} />
      <SelectField id="year" label="Année académique" required value={form.data.academic_year_id} onChange={(e) => form.setData('academic_year_id', e.target.value)} error={form.errors.academic_year_id}><option value="">Choisir l’année</option>{years.map((y) => <option key={y.id} value={y.id}>{y.name}</option>)}</SelectField>
      <SelectField id="level" label="Niveau du dossier" required value={form.data.study_level_id} onChange={(e) => form.setData('study_level_id', e.target.value)} error={form.errors.study_level_id}><option value="">Choisir le niveau</option>{levels.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}</SelectField>
      <InputField id="date" label="Date d’inscription" type="date" max={today} required value={form.data.enrolled_on} onChange={(e) => form.setData('enrolled_on', e.target.value)} error={form.errors.enrolled_on} />
    </div><p className="notice notice-neutral">Montant à payer : <strong>{fee ? money(fee.amount) : 'Sélectionnez un niveau ayant un tarif.'}</strong><br />Ce parcours concerne les statuts Normal et Redoublant. Les inscriptions À cheval nécessitent les matières à rattraper.</p>
      <div className="form-actions"><button className="button-primary" disabled={form.processing || !years.length || !fee}>Enregistrer l’inscription</button><Link href="/enrollments" className="button-secondary">Retour à la liste</Link></div>
    </form></section>
  </AppLayout>;
}
