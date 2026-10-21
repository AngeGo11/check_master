import type { InputHTMLAttributes, SelectHTMLAttributes } from 'react';

export function InputField({ label, error, id, ...props }: InputHTMLAttributes<HTMLInputElement> & { label: string; error?: string; id: string }) {
  return <div className="form-field"><label htmlFor={id}>{label}</label><input id={id} {...props} aria-invalid={Boolean(error)} aria-describedby={error ? id + '-error' : undefined} />{error && <p className="field-error" id={id + '-error'}>{error}</p>}</div>;
}
export function SelectField({ label, error, id, children, ...props }: SelectHTMLAttributes<HTMLSelectElement> & { label: string; error?: string; id: string }) {
  return <div className="form-field"><label htmlFor={id}>{label}</label><select id={id} {...props} aria-invalid={Boolean(error)} aria-describedby={error ? id + '-error' : undefined}>{children}</select>{error && <p className="field-error" id={id + '-error'}>{error}</p>}</div>;
}
export const money = (value: number) => new Intl.NumberFormat('fr-FR').format(value) + ' FCFA';
export const dateLabel = (value: string) => new Intl.DateTimeFormat('fr-FR', { timeZone: 'UTC' }).format(new Date(value));
export const paymentLabels = { paid: 'Tout payé', partial: 'Partiellement payé', unpaid: 'Rien payé' };
export const eligibilityLabels = { pending: 'À examiner', eligible: 'Éligible', ineligible: 'Non éligible' };
