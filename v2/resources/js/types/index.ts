export type User = { id: number; name: string; email: string };
export type SharedData = {
  [key: string]: unknown;
  auth: { user: User | null };
};
