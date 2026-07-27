export default function StatCard({ icon: Icon, label, value, hint }) {
  return (
    <article className="stat-card">
      <div className="stat-icon"><Icon size={27}/></div>
      <div className="stat-label">{label}</div>
      <div className="stat-value">{value}</div>
      <div className="stat-hint">{hint}</div>
    </article>
  );
}
