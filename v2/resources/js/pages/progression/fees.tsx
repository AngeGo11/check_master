import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '../../layouts/app-layout';
import FormErrors from '../../components/form-errors';
import { money } from '../../components/schooling-fields';
type Year = { id: number; name: string; status: string }; type Level = { id: number; name: string };
type Fee = { id: number; academic_year_id: number; study_level_id: number; amount: number; revision: number; year: string; level: string; status: string };
function Editor({ fee, years, levels }: { fee?: Fee; years: Year[]; levels: Level[] }) {
  const form = useForm({ academic_year_id: fee ? String(fee.academic_year_id) : '', study_level_id: fee ? String(fee.study_level_id) : '', amount: fee ? String(fee.amount) : '', revision: fee?.revision ?? 0 });
  function submit(e: FormEvent) { e.preventDefault(); form.post('/settings/retake-fees', { preserveScroll: true }); }
  return <form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} /><div className="form-grid"><label>Année<select required disabled={!!fee} value={form.data.academic_year_id} onChange={(e) => form.setData('academic_year_id', e.target.value)}><option value="">Choisir une année</option>{years.filter((y) => y.status !== 'closed').map((y) => <option key={y.id} value={y.id}>{y.name}</option>)}</select></label><label>Niveau d’accueil<select required disabled={!!fee} value={form.data.study_level_id} onChange={(e) => form.setData('study_level_id', e.target.value)}><option value="">Choisir un niveau</option>{levels.map((l) => <option key={l.id} value={l.id}>{l.name}</option>)}</select></label><label>Supplément par matière (FCFA)<input required type="number" min={0} max={1000000000} step={1} value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} /></label></div><button className="button-primary" disabled={form.processing}>{fee ? 'Modifier le tarif' : 'Ajouter le tarif'}</button></form>;
}
export default function RetakeFees({ fees, years, levels }: { fees: Fee[]; years: Year[]; levels: Level[] }) {
  return <AppLayout title="Tarifs de rattrapage" subtitle="Supplément par matière à reprendre"><Head title="Tarifs de rattrapage" /><p className="breadcrumb"><Link href="/settings">Paramètres</Link><span>/</span><strong>Tarifs de rattrapage</strong></p><div className="page-intro"><div><h1>Frais du parcours à cheval</h1><p>Montant total = scolarité du niveau d’accueil + supplément × nombre de matières à reprendre.</p></div><Link className="button-secondary" href="/settings/schooling">Années et scolarités</Link></div>
    <section className="panel schooling-section"><h2>Suppléments configurés</h2><p>Le tarif est figé à la réinscription. Valider ensuite une matière ne modifie pas le montant dû. Un tarif nul doit être configuré explicitement pour une reprise sans supplément.</p>{!fees.length && <p>Aucun supplément configuré.</p>}{fees.map((f) => <details className="schooling-details" key={f.id + '-' + f.revision}><summary>{f.year} · {f.level} · {money(f.amount)} par matière</summary>{f.status === 'closed' ? <p>Année clôturée, tarif conservé.</p> : <Editor fee={f} years={years} levels={levels} />}</details>)}<details className="schooling-details"><summary>Configurer un supplément</summary><Editor years={years} levels={levels} /></details></section>
  </AppLayout>;
}
