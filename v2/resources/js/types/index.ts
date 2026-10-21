export type AccessOption = { id: number; name: string };
export type PermissionCode = 'users.view' | 'users.manage' | 'groups.view' | 'groups.manage';
export type User = {
  id: number; name: string; email: string; must_change_password: boolean;
  types: AccessOption[]; groups: AccessOption[];
};
export type SharedData = {
  [key: string]: unknown;
  auth: { user: User | null; permissions: PermissionCode[] };
  flash: { success: string | null };
};
