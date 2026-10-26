import { Link, useForm, usePage } from '@inertiajs/react';
import FormErrors from './form-errors';
import type { SharedData } from '../types';
import type { Bookmark, Section, SourceSnapshot } from '../types/documents';
import type { Page } from '../types/students';
export function DocumentNav() {
  const { auth } = usePage<SharedData>().props;
  return <nav className="document-navigation" aria-label="Rubriques documentaires">
    {auth.permissions.some((p) => ['documents.self', 'documents.view', 'documents.minutes'].includes(p)) && <Link className="button-secondary" href="/documents">Documents</Link>}
    {auth.permissions.includes('documents.self') && <Link className="button-secondary" href="/documents/extracts">Mes extraits de comptes rendus</Link>}
    {auth.permissions.includes('documents.archive') && <><Link className="button-secondary" href="/documents/reports">Rapports accessibles</Link><Link className="button-secondary" href="/my-archives">Mes archives</Link></>}
  </nav>;
}
export function ArchiveButton({ bookmark: b, restore = false }: { bookmark: Bookmark | null; restore?: boolean }) {
  const form = useForm({ source_type: b?.source_type ?? '', source_id: b?.source_id ?? 0, revision: b?.revision ?? 0, active: !(b?.active ?? false), operation_key: b?.operation_key ?? '' });
  if (!b) return null;
  return <form onSubmit={(e) => { e.preventDefault(); form.post('/my-archives', { preserveScroll: true }); }}><FormErrors errors={form.errors} /><button className="button-secondary" disabled={form.processing}>{b.active ? 'Retirer de mes archives' : restore ? 'Restaurer dans mes archives' : 'Archiver cette version'}</button></form>;
}
export function DocumentText({ sections }: { sections: Section[] }) { return <>{sections.map((s, i) => <section key={i} className="document-section">{s.heading && <h2>{s.heading}</h2>}{s.style === 'list' ? <ul>{s.body.split('\n').filter((v) => v.trim()).map((v, n) => <li key={n}>{v}</li>)}</ul> : <p className={'document-body' + (s.style === 'quote' ? ' document-quote' : '')}>{s.body}</p>}</section>)}</>; }
export function ReportContext({ snapshot: s }: { snapshot: SourceSnapshot }) { return <div className="document-source"><strong>{s.student}</strong><p>{s.card_number} · {s.year}</p><p>{s.report_reference} · Version {s.report_number} · {s.report_title}</p><p>Commission {s.round_number} : <strong>{s.status === 'approved' ? 'Accepté à l’unanimité' : 'Correction demandée'}</strong> · {s.approved}/{s.members} approbations</p></div>; }
export function DocumentPagination({ page, label }: { page: Pick<Page<unknown>, 'total' | 'prev_page_url' | 'next_page_url'>; label: string }) { return <nav className="pagination" aria-label={label}><p>{page.total} résultat(s)</p><div>{page.prev_page_url && <Link className="button-secondary" href={page.prev_page_url}>Précédent</Link>}{page.next_page_url && <Link className="button-secondary" href={page.next_page_url}>Suivant</Link>}</div></nav>; }
