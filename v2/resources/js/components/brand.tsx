export default function Brand({ stacked = false }: { stacked?: boolean }) {
  return (
    <span className={'brand' + (stacked ? ' brand-stacked' : '')}>
      <span className="brand-mark">
        <img src="/images/logo-check-master.png" alt="" width={56} height={56} />
      </span>
      <span>CHECK MASTER</span>
    </span>
  );
}
