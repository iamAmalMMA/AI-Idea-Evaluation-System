import { Lightbulb, Clock3, ClipboardCheck, Star, Award, Bell, Share2, ChevronLeft, FileText } from 'lucide-react';
import Hero from '../components/Hero';
import StatCard from '../components/StatCard';
import IdeaTable from '../components/IdeaTable';

export default function Dashboard({ ideas, notifications, onNavigate, onOpen }) {
  const total = ideas.length;
  const processing = ideas.filter(i => i.status === 'processing').length;
  const evaluated = ideas.filter(i => ['evaluated','approved'].includes(i.status)).length;
  const top = ideas.filter(i => i.score).sort((a,b)=>b.score-a.score).slice(0,5).length;
  const approved = ideas.filter(i => i.status === 'approved').length;
  return <>
    <Hero />
    <section className="dashboard-content page-container">
      <aside className="dashboard-side">
        <button className="share-button"><Share2 size={20}/> مشاركة الصفحة <span>⌄</span></button>
        <div className="notification-panel">
          <div className="panel-title"><Bell size={20}/><h3>آخر الإشعارات</h3></div>
          {notifications.slice(0,2).map(n=><div className="notification-preview" key={n.id}><i></i><div><strong>{n.title}</strong><p>{n.message}</p><small>{n.time}</small></div></div>)}
          <button className="text-button" onClick={()=>onNavigate('notifications')}>عرض جميع الإشعارات <ChevronLeft size={18}/></button>
        </div>
      </aside>
      <div className="dashboard-main">
        <div className="stats-row">
          <StatCard icon={Lightbulb} label="إجمالي الأفكار" value={total} hint="↑ 3+ هذا الأسبوع"/>
          <StatCard icon={Clock3} label="قيد التحليل" value={processing} hint="2+ هذا الأسبوع"/>
          <StatCard icon={ClipboardCheck} label="تم التقييم" value={evaluated} hint="1+ هذا الأسبوع"/>
          <StatCard icon={Star} label="أفضل الأفكار" value={top} hint="تم اختيارها"/>
          <StatCard icon={Award} label="الأفكار المعتمدة" value={approved} hint="1+ هذا الأسبوع"/>
        </div>
        <section className="data-card latest-card">
          <div className="card-heading"><div><FileText size={22}/><h2>آخر الأفكار</h2></div></div>
          <IdeaTable ideas={ideas.slice(0,5)} onOpen={onOpen} compact/>
          <button className="text-button table-footer" onClick={()=>onNavigate('ideas')}>عرض جميع الأفكار <ChevronLeft size={18}/></button>
        </section>
      </div>
    </section>
  </>;
}
