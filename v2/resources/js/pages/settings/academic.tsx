import { Head, Link, useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import FormErrors from '../../components/form-errors';
import AppLayout from '../../layouts/app-layout';
import type { AccessOption, SharedData } from '../../types';

type Promotion = AccessOption & { revision: number; students_count: number };

function PromotionEditor({ promotion }: { promotion?: Promotion }) {
  const form = useForm({ name: promotion?.name ?? '', revision: promotion?.revision ?? 0 });
  function submit(event: FormEvent) {
    event.preventDefault();
    form.transform(({ name, revision }) => promotion ? { name, revision } : { name });
    if (promotion) form.put('/settings/promotions/' + promotion.id, { preserveScroll: true });
    else form.post('/settings/promotions', { preserveScroll: true, onSuccess: () => form.reset() });
  }
  const id = 'promotion-' + (promotion?.id ?? 'new');
  return <form onSubmit={submit}><FormErrors errors={form.errors} /><div className="promotion-form-row"><div className="form-field"><label htmlFor={id}>{promotion ? 'Libellé de la promotion' : 'Nouvelle promotion'}</label><input id={id} required maxLength={100} placeholder="Ex. : 2026-2027" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} aria-invalid={Boolean(form.errors.name)} aria-describedby={form.errors.name ? id + '-error' : undefined} />{form.errors.name && <p id={id + '-error'} className="field-error">{form.errors.name}</p>}</div><button className="button-primary" disabled={form.processing} aria-busy={form.processing}>{form.processing ? 'Enregistrement…' : promotion ? 'Enregistrer' : 'Ajouter'}</button></div>
    {form.errors.revision && <a className="table-action" href="/settings/academic">Recharger les promotions</a>}
  </form>;
}

export default function Academic({ promotions, levels, statuses }: { promotions: Promotion[]; levels: AccessOption[]; statuses: AccessOption[] }) {
  const { auth } = usePage<SharedData>().props;
  const canManage = auth.permissions.includes('academic.manage');
  return <AppLayout title="Promotions et niveaux" subtitle="Paramètres pédagogiques"><Head title="Promotions et niveaux" />
    <p className="breadcrumb"><Link href="/settings">Paramètres généraux</Link><span>/</span><strong>Promotions et niveaux</strong></p>
    <div className="page-intro"><div><h1>Promotions et niveaux</h1><p>Retrouvez les références utilisées dans les dossiers étudiants.</p></div><Link href="/settings" className="button-secondary">Retour aux paramètres</Link></div>
    <div className="academic-grid"><section className="panel" aria-label="Liste des promotions"><h2 className="section-title">Promotions</h2>
      {canManage && <div className="promotion-create"><PromotionEditor /></div>}
      {promotions.map((promotion) => <section className="group-row" key={promotion.id}><div className="group-heading"><h3>{promotion.name}</h3><span className="status-tag">{promotion.students_count} dossier{promotion.students_count > 1 ? 's' : ''}</span></div>
        {canManage && <details className="permission-editor"><summary>Modifier la promotion {promotion.name}</summary><p className="field-hint">Le nouveau libellé apparaîtra sur tous les dossiers de cette promotion.</p><PromotionEditor key={promotion.id + ':' + promotion.revision} promotion={promotion} /></details>}
      </section>)}
    </section><div className="academic-catalogs">
      <section className="panel"><h2 className="section-title">Niveaux d’étude</h2><ul className="catalog-list">{levels.map((level) => <li key={level.id}>{level.name}</li>)}</ul></section>
      <section className="panel"><h2 className="section-title">Statuts étudiants</h2><ul className="catalog-list">{statuses.map((status) => <li key={status.id}>{status.name}</li>)}</ul><p className="field-hint">Ces statuts décrivent la situation pédagogique ; ils ne représentent pas une décision sur le mémoire.</p></section>
    </div></div>
  </AppLayout>;
}
