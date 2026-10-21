export default function FormErrors({ errors }: { errors: Record<string, string | undefined> }) {
  const messages = [...new Set(Object.values(errors).filter(Boolean))];
  if (!messages.length) return null;
  return <div className="notice notice-error" role="alert"><p>Vérifiez les informations suivantes :</p><ul>{messages.map((message) => <li key={message}>{message}</li>)}</ul></div>;
}
