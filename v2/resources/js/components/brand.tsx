export default function Brand() {
  return (
    <span className="brand">
      <svg aria-hidden="true" viewBox="0 0 36 36" className="brand-mark" fill="none">
        <rect width="36" height="36" rx="10" fill="currentColor" />
        <path d="m10 18 5 5 11-11" stroke="white" strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
      </svg>
      <span>Check<span className="brand-light">Master</span></span>
    </span>
  );
}
