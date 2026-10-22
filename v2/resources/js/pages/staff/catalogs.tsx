import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '../../layouts/app-layout';
import FormErrors from '../../components/form-errors';
import { InputField } from '../../components/schooling-fields';
import { catalogLabels, type CatalogKind, type StaffCatalog } from '../../types/staff';
function CatalogForm({ kind, entry }: { kind: CatalogKind; entry?: StaffCatalog }) {
  const form = useForm({ kind, name: entry?.name ?? '', is_active: entry?.is_active ?? true, revision: entry?.revision ?? 0 });
  function submit(e: FormEvent) { e.preventDefault(); if (entry) form.put('/staff/catalogs/' + entry.id, { preserveScroll: true }); else form.post('/staff/catalogs', { preserveScroll: true }); }
  return <form className="management-form" onSubmit={submit}><FormErrors errors={form.errors} /><InputField id={'catalog-' + (entry?.id ?? kind)} label="Libellé" required maxLength={150} value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} /><label className="checkbox-label"><input type="checkbox" checked={form.data.is_active} onChange={(e) => form.setData('is_active', e.target.checked)} />Disponible pour de nouvelles attributions</label><button className="button-secondary" disabled={form.processing}>{entry ? 'Enregistrer' : 'Ajouter au catalogue'}</button></form>;
}
export default function Catalogs({ catalogs, canManage }: { catalogs: StaffCatalog[]; canManage: boolean }) {
  return <AppLayout title="Ressources humaines" subtitle="Grades, fonctions et spécialités"><Head title="Catalogues RH" /><p className="breadcrumb"><Link href="/staff">Ressources humaines</Link><span>/</span><strong>Catalogues</strong></p><div className="page-intro"><div><h1>Grades, fonctions et spécialités</h1><p>Les valeurs désactivées restent présentes dans les fiches et les historiques.</p></div></div>
    {(Object.keys(catalogLabels) as CatalogKind[]).map((kind) => <section className="panel schooling-section" key={kind}><h2>{catalogLabels[kind]}</h2><ul className="meeting-documents">{catalogs.filter((r) => r.kind === kind).map((r) => <li key={r.id}><strong>{r.name}</strong><span className={'status-tag ' + (r.is_active ? 'status-active' : 'status-inactive')}>{r.is_active ? 'Disponible' : 'Désactivé'}</span>{canManage && <details className="schooling-details"><summary>Modifier cette valeur</summary><CatalogForm key={r.revision} kind={kind} entry={r} /></details>}</li>)}</ul>{canManage && <details className="schooling-details"><summary>Ajouter une valeur</summary><CatalogForm key={kind + '-' + catalogs.length} kind={kind} /></details>}</section>)}<Link href="/staff" className="button-secondary">Retour aux ressources humaines</Link>
  </AppLayout>;
}
