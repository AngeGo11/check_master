export type AccessOption = { id: number; name: string };
export type PermissionCode = 'users.view' | 'users.manage' | 'groups.view' | 'groups.manage' | 'students.view' | 'students.manage' | 'students.self' | 'academic.view' | 'academic.manage' | 'schooling.view' | 'schooling.manage' | 'enrollments.view' | 'enrollments.manage' | 'payments.manage' | 'eligibility.review' | 'reports.view' | 'reports.self' | 'internships.self' | 'reports.forward' | 'reports.assigned' | 'reports.vote' | 'messages.use' | 'messages.broadcast' | 'meetings.view' | 'meetings.manage' | 'hr.view' | 'hr.manage' | 'hr.catalogs.manage' | 'hr.self' | 'grades.view' | 'grades.manage' | 'grades.self' | 'grades.catalogs.manage' | 'defenses.view' | 'defenses.manage' | 'defenses.self' | 'defenses.assigned' | 'defenses.assess' | 'claims.self' | 'claims.view' | 'claims.manage' | 'claims.assigned' | 'audit.view' | 'audit.export';
export type User = {
  id: number; name: string; email: string; must_change_password: boolean;
  types: AccessOption[]; groups: AccessOption[];
};
export type SharedData = {
  [key: string]: unknown;
  auth: { user: User | null; permissions: PermissionCode[] };
  unreadMessageCount: number;
  flash: { success: string | null };
};
