import { Head } from '@inertiajs/react';
import AppLayout from '../../layouts/app-layout';
import { ArchiveButton, DocumentNav, ReportContext } from '../../components/document-parts';
import { meetingDate } from '../../types/collaboration';
import type { Bookmark, Extract } from '../../types/documents';
export default function ExtractShow({ entry: e, archive }: { entry: Extract; archive: Bookmark | null }) {
  return <AppLayout title="Mon extrait de compte rendu" subtitle={e.reference}><Head title="Extrait individuel" /><DocumentNav /><div className="page-intro"><div><h1>Extrait individuel du compte rendu</h1><p>{e.reference} · Version {e.number} · {meetingDate(e.created_at)}</p></div></div><section className="panel schooling-section"><div className="document-actions"><a className="button-primary" href={'/documents/extracts/' + e.id + '/download'}>Télécharger mon extrait PDF</a><ArchiveButton key={archive?.revision} bookmark={archive} /></div><p>Publié par {e.actor_name} · Référence : {e.publication_reference}</p><ReportContext snapshot={e.snapshot} /><p className="document-body">{e.body}</p></section></AppLayout>;
}
