import { Head, Link } from '@inertiajs/react';
import Brand from '../../components/brand';
import { money, dateLabel } from '../../components/schooling-fields';
import AppLayout from '../../layouts/app-layout';
import type { EnrollmentSummary, Payment } from '../../types/enrollments';

export default function Receipt({ enrollment: e, payment: p, personal }: { enrollment: EnrollmentSummary; payment: Payment; personal: boolean }) {
  return <AppLayout title="Reçu de règlement" subtitle="Scolarité"><Head title={p.receipt_number} />
    <div className="page-intro no-print"><h1>Reçu de règlement</h1><div className="form-actions"><Link href={(personal ? '/my-enrollments/' : '/enrollments/') + e.id} className="button-secondary">Retour à l’inscription</Link><button type="button" className="button-primary" onClick={() => window.print()}>Imprimer le reçu</button></div></div>
    <article className="panel receipt"><div className="receipt-heading"><Brand /><p>Université Félix Houphouët-Boigny · MIAGE</p></div><h2>Reçu de règlement</h2><p className="reference-cell">{p.receipt_number}</p>
      {p.voided_at && <div className="notice notice-error"><strong>REÇU ANNULÉ</strong><p>Le {dateLabel(p.voided_at)} · {p.void_reason}</p></div>}
      <dl className="profile-details"><div><dt>Étudiant</dt><dd>{e.student.last_name} {e.student.first_name}</dd></div><div><dt>N° de carte</dt><dd>{e.student.card_number}</dd></div><div><dt>Année / Niveau</dt><dd>{e.year.name} · {e.level.name}</dd></div><div><dt>Date du règlement</dt><dd>{dateLabel(p.paid_on)}</dd></div><div><dt>Montant enregistré</dt><dd><strong>{money(p.amount)}</strong></dd></div><div><dt>Mode de paiement</dt><dd>{p.method === 'cash' ? 'Espèces' : 'Chèque n° ' + p.cheque_number}</dd></div><div><dt>Motif</dt><dd>{p.purpose}</dd></div><div><dt>Inscription</dt><dd className="reference-cell">{e.reference}</dd></div></dl>
      <p className="receipt-total">Situation actuelle : {money(e.paid_amount)} payé(s) sur {money(e.amount_due)}. Reste : <strong>{money(e.balance)}</strong>.</p>
      <p className="field-hint">Ce document reprend une écriture enregistrée dans Check Master. Toute correction reste visible dans l’historique de l’inscription.</p>
    </article>
  </AppLayout>;
}
