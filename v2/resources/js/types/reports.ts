export type ReportStatus = 'submitted' | 'changes_requested' | 'approved';
export type ReportSummary = {
  id: number; reference: string; title: string; status: ReportStatus; revision: number; version_count: number; latest_submitted_at: string;
  student: { card_number: string; last_name: string; first_name: string }; year: { id: number; name: string };
};
export type ReportVersion = {
  id: number; number: number; title: string; size: number; created_at: string; year: string;
  decision: { decision: 'approved' | 'changes_requested'; comment: string; created_at: string; reviewer: string } | null;
};
export const reportLabels: Record<ReportStatus, string> = { submitted: 'En attente d’approbation', changes_requested: 'Correction demandée', approved: 'Approuvé' };
export const reportTone: Record<ReportStatus, string> = { submitted: 'status-inactive', changes_requested: 'status-inactive', approved: 'status-active' };
