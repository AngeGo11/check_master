import { Head, Link } from '@inertiajs/react';
import AppLayout from '../../layouts/app-layout';
import { DocumentNav, DocumentPagination } from '../../components/document-parts';
import { meetingDate } from '../../types/collaboration';
import type { Extract } from '../../types/documents';
import type { Page } from '../../types/students';
export default function Extracts({ entries }: { entries: Page<Extract> }) {
  return <AppLayout title="Mes comptes rendus" subtitle="Extraits individuels publiés"><Head title="Mes extraits de comptes rendus" /><div className="page-intro"><div><h1>Mes extraits de comptes rendus</h1><p>Retrouvez les synthèses publiées qui concernent votre rapport et leurs versions successives.</p></div></div><DocumentNav /><section className="panel schooling-section"><div className="table-scroll"><table className="data-table"><thead><tr><th>Compte rendu</th><th>Rapport</th><th>Publication</th><th>Action</th></tr></thead><tbody>{entries.data.map((e) => <tr key={e.id}><td>{e.reference}<span className="table-secondary">Version {e.number}</span></td><td>{e.snapshot.report_title}<span className="table-secondary">{e.snapshot.report_reference} · Version {e.snapshot.report_number}</span></td><td>{meetingDate(e.created_at)}</td><td><Link className="table-action" href={'/documents/extracts/' + e.id}>Consulter mon extrait</Link></td></tr>)}{!entries.data.length && <tr><td colSpan={4}>Aucun extrait publié pour votre rapport.</td></tr>}</tbody></table></div><DocumentPagination page={entries} label="Pages de mes extraits" /></section></AppLayout>;
}
