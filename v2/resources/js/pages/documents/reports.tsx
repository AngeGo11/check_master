import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '../../layouts/app-layout';
import { ArchiveButton, DocumentNav, DocumentPagination } from '../../components/document-parts';
import { meetingDate } from '../../types/collaboration';
import type { Bookmark } from '../../types/documents';
import type { Page } from '../../types/students';
export default function ReportDocuments({ versions, filters }: { versions: Page<{ id: number; title: string; reference: string; number: number; created_at: string; archive: Bookmark | null }>; filters: { search?: string } }) {
  const [search, setSearch] = useState(filters.search ?? '');
  return <AppLayout title="Documents étudiants" subtitle="Versions des rapports accessibles"><Head title="Rapports accessibles" /><div className="page-intro"><div><h1>Rapports accessibles</h1><p>Consultez les versions autorisées et ajoutez-les à vos archives personnelles.</p></div></div><DocumentNav /><section className="panel schooling-section"><form className="collaboration-filters" onSubmit={(e) => { e.preventDefault(); router.get('/documents/reports', { search }); }}><label>Titre ou référence<input maxLength={150} value={search} onChange={(e) => setSearch(e.target.value)} /></label><button className="button-secondary">Rechercher</button></form><div className="table-scroll"><table className="data-table"><thead><tr><th>Rapport</th><th>Version</th><th>Date du dépôt</th><th>Actions</th></tr></thead><tbody>{versions.data.map((v) => <tr key={v.id}><td>{v.title}<span className="table-secondary">{v.reference}</span></td><td>{v.number}</td><td>{meetingDate(v.created_at)}</td><td><div className="document-actions"><a className="table-action" href={'/documents/report-versions/' + v.id + '/download'}>Télécharger</a><ArchiveButton key={v.archive?.revision} bookmark={v.archive} /></div></td></tr>)}{!versions.data.length && <tr><td colSpan={4}>Aucune version accessible pour cette recherche.</td></tr>}</tbody></table></div><DocumentPagination page={versions} label="Pages des versions de rapports" /></section></AppLayout>;
}
