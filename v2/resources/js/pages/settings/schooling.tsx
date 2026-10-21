import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import { InputField, SelectField, money, dateLabel } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { AccessOption, SharedData } from '../../types';
import type { Year } from '../../types/enrollments';

type AcademicYear = Year & { starts_on: string; ends_on: string; revision: number; enrollments_count: number };
type Fee = { id: number; amount: number; revision: number; year: Year; level: AccessOption };
const yearLabels = { pending: 'En attente', current: 'En cours', closed: 'Terminée' };
function YearEditor({ year }: { year?: AcademicYear }) {
  const form = useForm({ name: year?.name ?? '', starts_on: year?.starts_on ?? '', ends_on: year?.ends_on ?? '', status: year?.status ?? 'pending', revision: year?.revision ?? 0 });
  function submit(event: FormEvent) {
    event.preventDefault();
    form.transform(({ revision, ...data }) => year ? { ...data, revision } : data);
    if (year) form.put('/settings/years/' + year.id, { preserveScroll: true });
    else form.post('/settings/years', { preserveScroll: true, onSuccess: () => form.reset() });
  }
  const id = 'year-' + (year?.id ?? 'new');
  return <form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} /><div className="form-grid">
    <InputField id={id + '-name'} label="Année académique" required maxLength={100} placeholder="2026-2027" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} error={form.errors.name} />
    <SelectField id={id + '-status'} label="Statut" value={form.data.status} onChange={(e) => form.setData('status', e.target.value as Year['status'])} error={form.errors.status}>{Object.entries(yearLabels).map(([key, label]) => <option key={key} value={key}>{label}</option>)}</SelectField>
    <InputField id={id + '-start'} label="Début" type="date" required readOnly={Boolean(year?.enrollments_count)} value={form.data.starts_on} onChange={(e) => form.setData('starts_on', e.target.value)} error={form.errors.starts_on} />
    <InputField id={id + '-end'} label="Fin" type="date" required readOnly={Boolean(year?.enrollments_count)} value={form.data.ends_on} onChange={(e) => form.setData('ends_on', e.target.value)} error={form.errors.ends_on} />
  </div><p className="field-hint">Passer une année « En cours » clôture l’année précédemment active. Les dates sont conservées dès la première inscription.</p><button className="button-primary" disabled={form.processing}>{year ? 'Enregistrer l’année' : 'Ajouter l’année'}</button></form>;
}
function FeeEditor({ fee, years, levels }: { fee?: Fee; years: AcademicYear[]; levels: AccessOption[] }) {
  const form = useForm({ academic_year_id: '', study_level_id: '', amount: fee ? String(fee.amount) : '', revision: fee?.revision ?? 0 });
  function submit(event: FormEvent) {
    event.preventDefault();
    form.transform(({ amount, revision, academic_year_id, study_level_id }) => fee ? { amount, revision } : { amount, academic_year_id, study_level_id });
    if (fee) form.put('/settings/fees/' + fee.id, { preserveScroll: true });
    else form.post('/settings/fees', { preserveScroll: true, onSuccess: () => form.reset() });
  }
  const id = 'fee-' + (fee?.id ?? 'new');
  return <form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} /><div className="form-grid">
    {!fee && <><SelectField id={id + '-year'} label="Année académique" required value={form.data.academic_year_id} onChange={(e) => form.setData('academic_year_id', e.target.value)}><option value="">Choisir l’année</option>{years.filter((y) => y.status !== 'closed').map((y) => <option key={y.id} value={y.id}>{y.name}</option>)}</SelectField>
      <SelectField id={id + '-level'} label="Niveau" required value={form.data.study_level_id} onChange={(e) => form.setData('study_level_id', e.target.value)}><option value="">Choisir le niveau</option>{levels.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}</SelectField></>}
    <InputField id={id + '-amount'} label="Montant de scolarité (FCFA)" type="number" min={1} max={1000000000} step={1} required value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} error={form.errors.amount} />
  </div><button className="button-primary" disabled={form.processing}>{fee ? 'Enregistrer le tarif' : 'Ajouter le tarif'}</button></form>;
}
export default function Schooling({ years, fees, levels }: { years: AcademicYear[]; fees: Fee[]; levels: AccessOption[] }) {
  const page = usePage<SharedData & { errors: Record<string, string> }>();
  const canManage = page.props.auth.permissions.includes('schooling.manage');
  return <AppLayout title="Années et tarifs" subtitle="Paramètres de scolarité"><Head title="Années et tarifs" />
    <p className="breadcrumb"><Link href="/settings">Paramètres généraux</Link><span>/</span><strong>Années et tarifs</strong></p>
    <div className="page-intro"><div><h1>Années académiques et frais de scolarité</h1><p>Retrouvez les années et le montant à payer pour chaque niveau.</p></div></div>
    {page.props.errors.revision && <div><FormErrors errors={{ revision: page.props.errors.revision }} /><a className="table-action" href="/settings/schooling">Recharger les paramètres</a></div>}
    <section className="panel schooling-section"><h2>Années académiques</h2>{!years.length && <p className="notice notice-neutral">Aucune année configurée. Ajoutez l’année à utiliser, puis passez-la « En cours ».</p>}
      {years.map((year) => <details className="schooling-details" key={year.id + '-' + year.revision}><summary><strong>{year.name}</strong> · {yearLabels[year.status]} · {year.enrollments_count} inscription(s)</summary>{canManage ? <YearEditor year={year} /> : <p>Du {dateLabel(year.starts_on)} au {dateLabel(year.ends_on)}</p>}</details>)}
      {canManage && <details className="schooling-details"><summary>Ajouter une année académique</summary><YearEditor /></details>}
    </section>
    <section className="panel schooling-section"><h2>Frais de scolarité par niveau</h2><p className="notice notice-neutral">Chaque inscription conserve le tarif appliqué à sa création. Une modification concerne les nouvelles inscriptions. Les frais spécifiques aux étudiants À cheval seront traités avec les matières à rattraper.</p>
      {!fees.length && <p>Aucun tarif enregistré.</p>}{fees.map((fee) => <details className="schooling-details" key={fee.id + '-' + fee.revision}><summary><strong>{fee.year.name} · {fee.level.name}</strong> · {money(fee.amount)}</summary>{canManage && fee.year.status !== 'closed' ? <FeeEditor fee={fee} years={years} levels={levels} /> : <p>Tarif en consultation.</p>}</details>)}
      {canManage && <details className="schooling-details"><summary>Ajouter un tarif</summary><FeeEditor years={years} levels={levels} /></details>}
    </section>
  </AppLayout>;
}
