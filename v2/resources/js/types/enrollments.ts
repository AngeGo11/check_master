import type { AccessOption } from './index';
export type Year = AccessOption & { status: 'pending' | 'current' | 'closed' };
export type EnrollmentSummary = {
  id: number; reference: string; amount_due: number; enrolled_on: string; cancelled: boolean;
  paid_amount: number; balance: number; payment_status: 'paid' | 'partial' | 'unpaid'; year: Year; level: AccessOption;
  student: { id: number; card_number: string; last_name: string; first_name: string; is_archived: boolean };
};
export type EnrollmentDetail = EnrollmentSummary & { revision: number; cancellation_reason: string | null; editable: boolean };
export type Payment = { id: number; receipt_number: string; amount: number; method: 'cash' | 'cheque'; cheque_number: string | null; purpose: string; paid_on: string; voided_at: string | null; void_reason: string | null };
export type Internship = { company: string; title: string; starts_on: string; ends_on: string; evidence_reference: string };
export type Eligibility = {
  criteria: { key: string; label: string; met: boolean; detail: string }[]; context_valid: boolean;
  computed_result: 'eligible' | 'ineligible'; status: 'pending' | 'eligible' | 'ineligible';
  review: { id: number; result: 'eligible' | 'ineligible'; reason: string; created_at: string; current: boolean } | null;
};
