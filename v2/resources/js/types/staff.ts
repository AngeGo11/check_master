import type { Page } from './students';
export type StaffKind = 'teacher' | 'administrative';
export type CatalogKind = 'grade' | 'function' | 'specialty';
export type StaffCatalog = { id: number; kind: CatalogKind; name: string; is_active: boolean; revision: number };
export type StaffSummary = { id: number; reference: string; kind: StaffKind; last_name: string; first_name: string; email: string; position: string | null; is_archived: boolean; linked: boolean; current_assignments: { id: number; kind: 'grade' | 'function'; name: string; since: string }[]; specialties: { id: number; name: string; is_active: boolean }[] };
export type StaffDetail = StaffSummary & { revision: number; phone: string | null; gender: string | null; date_of_birth: string | null; hired_on: string | null; photo_url: string | null; assignments: Page<{ id: number; kind: 'grade' | 'function'; name_snapshot: string; assigned_on: string; ended_on: string | null; current: boolean }>; activity: { id: number; action: string; actor: string | null; created_at: string }[] };
export type StaffDraft = { id: number; kind: StaffKind; last_name: string; first_name: string; email: string; phone: string | null; gender: string | null; date_of_birth: string | null; hired_on: string | null; position: string | null; specialty_ids: number[]; revision: number };
export const staffLabels: Record<StaffKind, string> = { teacher: 'Enseignants', administrative: 'Personnel administratif' };
export const catalogLabels: Record<CatalogKind, string> = { grade: 'Grades', function: 'Fonctions', specialty: 'Spécialités' };
export function staffDate(value: string | null) { return value ? new Intl.DateTimeFormat('fr-FR', { timeZone: 'UTC', dateStyle: 'medium' }).format(new Date(value + 'T12:00:00Z')) : 'Non renseignée'; }
