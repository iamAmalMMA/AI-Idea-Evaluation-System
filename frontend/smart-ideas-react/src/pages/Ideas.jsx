import { useMemo, useState } from 'react';
import { Search, Plus, SlidersHorizontal } from 'lucide-react';
import PageTitle from '../components/PageTitle';
import IdeaTable from '../components/IdeaTable';

export default function Ideas({ ideas, onOpen, onNavigate, isAdmin }) {
  const [query,setQuery]=useState(''); const [status,setStatus]=useState('');
  const filtered=useMemo(()=>ideas.filter(i=>(!query || `${i.title} ${i.number} ${i.department}`.toLowerCase().includes(query.toLowerCase())) && (!status || i.status===status)),[ideas,query,status]);
  return <div className="page-container inner-page">
    <PageTitle title={isAdmin?'جميع الأفكار':'أفكاري'} description="استعرض الأفكار وحالاتها ونتائج التقييم." action={!isAdmin&&<button className="primary-button" onClick={()=>onNavigate('new')}><Plus size={19}/>إضافة فكرة</button>}/>
    <section className="data-card">
      <div className="filters-bar">
        <div className="search-field"><Search size={19}/><input placeholder="ابحث بالعنوان أو رقم الفكرة..." value={query} onChange={e=>setQuery(e.target.value)}/></div>
        <div className="select-field"><SlidersHorizontal size={18}/><select value={status} onChange={e=>setStatus(e.target.value)}><option value="">كل الحالات</option><option value="draft">مسودة</option><option value="submitted">قيد التقييم</option><option value="processing">قيد التحليل</option><option value="evaluated">تم التقييم</option><option value="approved">معتمدة</option></select></div>
      </div>
      <IdeaTable ideas={filtered} onOpen={onOpen}/>
    </section>
  </div>;
}
