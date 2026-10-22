export type ReportStatus = 'submitted' | 'under_review' | 'changes_requested' | 'accepted';
export type ReportSummary = {
  id: number; reference: string; title: string; status: ReportStatus; revision: number; version_count: number; latest_submitted_at: string;
  student: { card_number: string; last_name: string; first_name: string }; year: { id: number; name: string };
};
export type ReportRound = {
  id: number; number: number; status: 'open' | 'approved' | 'changes_requested' | 'superseded'; reason: string | null;
  created_at: string; total: number; approved: number;
  members: { name: string; is_you: boolean; decision: 'approved' | 'changes_requested' | null; comment: string | null; decided_at: string | null }[];
};
export type ReportVersion = {
  id: number; number: number; title: string; size: number; created_at: string; year: string; rounds: ReportRound[];
  legacy_decision: { decision: string; comment: string; created_at: string; reviewer: string } | null;
};
export const reportLabels: Record<ReportStatus, string> = { submitted: 'À transmettre', under_review: 'En commission', changes_requested: 'Correction demandée', accepted: 'Accepté à l’unanimité' };
export const reportTone: Record<ReportStatus, string> = { submitted: 'status-inactive', under_review: 'status-inactive', changes_requested: 'status-inactive', accepted: 'status-active' };
