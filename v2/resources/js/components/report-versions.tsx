import { Link } from '@inertiajs/react';
import { dateLabel } from './schooling-fields';
import type { ReportVersion } from '../types/reports';
import { reportLabels, reportTone } from '../types/reports';
import type { Page } from '../types/students';

export default function ReportVersions({ reportId, versions, personal }: { reportId: number; versions: Page<ReportVersion>; personal: boolean }) {
  return <section className="panel schooling-section" id="report-history"><h2>Versions et commentaires</h2><p className="field-hint">Chaque retour concerne la version indiquée. Les documents précédents restent consultables.</p>
    <ol className="report-history">{versions.data.map((version) => <li key={version.id}><div className="report-version-heading"><div><h3>Version {version.number} · {version.title}</h3><p>{dateLabel(version.created_at)} · {version.year} · {(version.size / 1024 / 1024).toLocaleString('fr-FR', { maximumFractionDigits: 2, minimumFractionDigits: 2 })} Mo</p></div>
      <a className="button-secondary" href={(personal ? '/my-reports/' : '/reports/') + reportId + '/versions/' + version.id + '/download'} aria-label={'Télécharger le PDF de la version ' + version.number}>Télécharger le PDF</a></div>
      {version.decision ? <div className="report-feedback"><span className={'status-tag ' + reportTone[version.decision.decision]}>{reportLabels[version.decision.decision]}</span><p className="report-comment">{version.decision.comment}</p><p className="field-hint">{version.decision.reviewer} · {dateLabel(version.decision.created_at)}</p></div> : <p className="field-hint">En attente d’un retour du service habilité.</p>}
    </li>)}</ol>
    <nav className="pagination" aria-label="Pages des versions"><p>{versions.total} version(s) · Page {versions.current_page} sur {versions.last_page}</p><div>{versions.prev_page_url && <Link href={versions.prev_page_url} className="button-secondary" preserveScroll>Précédent</Link>}{versions.next_page_url && <Link href={versions.next_page_url} className="button-secondary" preserveScroll>Suivant</Link>}</div></nav>
  </section>;
}
