export type Subject = { code: string; name: string; credits: number; hours: number };
export type Unit = Subject & { components: Subject[] };
export type ProgramSummary = { id: number; number: number; status: 'draft' | 'published' | 'retired'; year: string; semester: string; level: string; credits: number; unit_count: number };
export type ProgramDetail = ProgramSummary & { revision: number; units: Unit[]; review_note: string | null };
export type EnrollmentSummary = { id: number; reference: string; study_level_id: number; student: string; card_number: string; year: string; level: string; cancelled: boolean };
export type GradeResult = { complete: boolean; average: number | null; credits: number; credits_at_least_ten: number; groups: { major: number | null; minor: number | null }; units: { code: string; name: string; credits: number; group: string; average: number | null; subjects: (Subject & { key: string; score: number | null })[] }[] };
export type Publication = { id: number; number: number; decision: string; comment: string; created_at: string };
export const gradeDecisions: Record<string, string> = { pending: 'En attente de délibération', validated: 'Validé', authorized: 'Autorisé', adjourned: 'Ajourné' };
export const programStates = { draft: 'Brouillon', published: 'Version active', retired: 'Version précédente' };
export function mark(value: number | null) { return value === null ? '—' : value.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
export const defenseStates: Record<string, string> = { requested: 'En attente de planification', scheduled: 'Planifiée', completed: 'Terminée', cancelled: 'Annulée' };
export const juryRoles: Record<string, string> = { president: 'Président', examiner: 'Examinateur', supervisor: 'Encadrant' };
export type Readiness = { ready: boolean; approved: boolean; eligible: boolean; document_available: boolean };
export type DefenseSummary = { id: number; reference: string; status: string; student: string; card_number: string; title: string; year: string; requested_at: string; starts_at: string | null; location: string | null };
export type DefenseDetail = DefenseSummary & { revision: number; ends_at: string | null; instructions: string | null; outcome: string | null; score: number | null; result_comment: string | null; result_at: string | null; jurors: { user_id: number; name_snapshot: string; role: string }[] };
