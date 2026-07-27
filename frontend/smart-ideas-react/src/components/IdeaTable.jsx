import { MoreVertical, Star } from 'lucide-react';

export const statusText = {
  draft: 'مسودة', submitted: 'قيد التقييم', processing: 'قيد التحليل', evaluated: 'تم التقييم', approved: 'معتمدة', rejected: 'مرفوضة'
};

export default function IdeaTable({ ideas, onOpen, compact = false }) {
  return (
    <div className="table-scroll">
      <table className="ideas-table">
        <thead><tr><th>عنوان الفكرة</th><th>الإدارة</th><th>الحالة</th><th>تاريخ الإرسال</th><th>التقييم</th><th>الإجراءات</th></tr></thead>
        <tbody>
          {ideas.map(idea => (
            <tr key={idea.id} onClick={() => onOpen(idea.id)}>
              <td><strong>{idea.title}</strong>{!compact && <small>{idea.number}</small>}</td>
              <td>{idea.department || 'غير محدد'}</td>
              <td><span className={`status status-${idea.status}`}>{statusText[idea.status]}</span></td>
              <td>{new Date(idea.date).toLocaleDateString('ar-SA', {year:'numeric', month:'long', day:'numeric'})}</td>
              <td>{idea.score ? <span className="table-score">{idea.score} <Star size={15} fill="currentColor"/></span> : '—'}</td>
              <td><button className="row-menu" onClick={(e) => {e.stopPropagation(); onOpen(idea.id);}} aria-label="عرض الفكرة"><MoreVertical size={20}/></button></td>
            </tr>
          ))}
          {!ideas.length && <tr><td colSpan="6" className="empty-cell">لا توجد أفكار مطابقة.</td></tr>}
        </tbody>
      </table>
    </div>
  );
}
