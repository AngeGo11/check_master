export type AuditFilters = { module?: string; action?: string; actor?: string; unattributed?: boolean | string; from?: string; to?: string; page?: number };
export type AuditEvent = { key: string; source: string; id: number; module: string; actor_id: number | null; actor: string; action: string; label: string; references: string[]; created_at: string };
export function auditUrl(path: string, filters: AuditFilters): string {
  const query = new URLSearchParams();
  Object.entries(filters).forEach(([key, value]) => { if (value !== undefined && value !== '' && value !== false) query.set(key, value === true ? '1' : String(value)); });
  return path + (query.size ? '?' + query.toString() : '');
}
export function auditDate(value: string | null): string {
  return value ? new Intl.DateTimeFormat('fr-FR', { dateStyle: 'short', timeStyle: 'medium', timeZone: 'UTC' }).format(new Date(value)) : 'Aucune action';
}
