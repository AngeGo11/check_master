import type { AccessOption } from './index';

export type StudentSummary = {
  id: number; card_number: string; last_name: string; first_name: string; email: string; is_archived: boolean;
  study_level: AccessOption; promotion: AccessOption; student_status: AccessOption;
};
export type StudentDetail = StudentSummary & { revision: number; date_of_birth: string | null; gender: string | null };
export type StudentOptions = { levels: AccessOption[]; promotions: AccessOption[]; statuses: AccessOption[] };
export type Page<T> = { data: T[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null };
