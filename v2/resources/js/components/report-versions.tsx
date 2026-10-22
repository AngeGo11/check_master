import { Link } from '@inertiajs/react';
import { dateLabel } from './schooling-fields';
import type { ReportRound, ReportVersion } from '../types/reports';
import type { Page } from '../types/students';

export function CommissionRound({ round }: { round: ReportRound }) {
  const labels = { open: 'Avis en cours', approved: 'Unanimité obtenue', changes_requested: 'Correction demandée', superseded: 'Transmission remplacée' };
  return <div className="report-feedback"><h4>Transmission {round.number} · {labels[round.status]}</h4>
    <p>{round.approved} / {round.total} approbation(s) · {dateLabel(round.created_at)}</p>
    {round.reason && <p className="field-hint">{round.reason}</p>}
    <ul className="commission-members">{round.members.map((member, i) => <li key={i}>
      <strong>{member.name}{member.is_you ? ' (vous)' : ''}</strong><span className={'status-tag ' + (member.decision === 'approved' ? 'status-active' : 'status-inactive')}>{member.decision === 'approved' ? 'Approuvé' : member.decision === 'changes_requested' ? 'Correction demandée' : round.status === 'open' ? 'Avis attendu' : 'Sans avis avant clôture'}</span>
      {member.comment && <p className="report-comment">{member.comment}</p>}{member.decided_at && <p className="field-hint">{dateLabel(member.decided_at)}</p>}
    </li>)}</ul>
  </div>;
}
export default function ReportVersions({ reportId, versions, personal }: { reportId: number; versions: Page<ReportVersion>; personal: boolean }) {
  return <section className="panel schooling-section" id="report-history"><h2>Versions et commentaires</h2><p className="field-hint">Chaque transmission et chaque avis concernent la version indiquée. Les documents précédents restent conservés.</p>
    <ol className="report-history">{versions.data.map((version) => <li key={version.id}><div className="report-version-heading"><div><h3>Version {version.number} · {version.title}</h3><p>{dateLabel(version.created_at)} · {version.year} · {(version.size / 1024 / 1024).toLocaleString('fr-FR', { maximumFractionDigits: 2, minimumFractionDigits: 2 })} Mo</p></div>
      <a className="button-secondary" href={(personal ? '/my-reports/' : '/reports/') + reportId + '/versions/' + version.id + '/download'} aria-label={'Télécharger le PDF de la version ' + version.number}>Télécharger le PDF</a></div>
      {version.rounds.map((round) => <CommissionRound key={round.id} round={round} />)}
      {!version.rounds.length && <p className="field-hint">En attente de transmission par le chargé de communication.</p>}
      {version.legacy_decision && <div className="report-feedback"><h4>Ancien avis administratif</h4><p className="field-hint">Cet avis antérieur ne constitue pas une approbation de la commission.</p><p className="report-comment">{version.legacy_decision.comment}</p><p className="field-hint">{version.legacy_decision.reviewer} · {dateLabel(version.legacy_decision.created_at)}</p></div>}
    </li>)}</ol>
    <nav className="pagination" aria-label="Pages des versions"><p>{versions.total} version(s) · Page {versions.current_page} sur {versions.last_page}</p><div>{versions.prev_page_url && <Link href={versions.prev_page_url} className="button-secondary" preserveScroll>Précédent</Link>}{versions.next_page_url && <Link href={versions.next_page_url} className="button-secondary" preserveScroll>Suivant</Link>}</div></nav>
  </section>;
}
