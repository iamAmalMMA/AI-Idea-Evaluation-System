export default function Hero({ title = 'منصة الأفكار الذكية', subtitle = 'منصة أمانة جدة لتمكين الموظفين من تقديم أفكار مبتكرة، وتحليلها باستخدام الذكاء الاصطناعي، واختيار أفضل الأفكار للتنفيذ.' }) {
  return (
    <section className="hero-section">
      <div className="hero-art" aria-hidden="true">
        <svg viewBox="0 0 650 230" role="presentation">
          <g fill="none" stroke="currentColor" strokeWidth="4" opacity=".65">
            <path d="M0 205h650M30 205v-55h55v55M44 150v-55h27v55M112 205v-95h65v95M127 110V72h34v38M203 205v-44h58v44M223 161v-48h18v48M290 205v-110h53v110M306 95V62h21v33M374 205v-58h40v58M390 147v-35M444 205v-94h65v94M461 111V76h30v35M539 205v-49h63v49"/>
            <path d="M517 205v-120c0-40 55-40 55 0v120M544 45V8M526 25h36"/>
            <path d="M78 205c8-28 30-28 38 0M175 205c9-35 35-35 44 0M342 205c7-25 26-25 33 0M601 205c8-31 33-31 41 0"/>
          </g>
        </svg>
      </div>
      <div className="hero-copy">
        <div className="breadcrumb">الرئيسية <span>‹</span> منصة الأفكار الذكية</div>
        <h1>{title}</h1>
        <p>{subtitle}</p>
      </div>
    </section>
  );
}
