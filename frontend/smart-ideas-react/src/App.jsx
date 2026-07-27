import { useEffect, useState } from 'react';
import Header from './components/Header';
import Login from './pages/Login';
import Dashboard from './pages/Dashboard';
import Ideas from './pages/Ideas';
import NewIdea from './pages/NewIdea';
import TopIdeas from './pages/TopIdeas';
import Analytics from './pages/Analytics';
import Notifications from './pages/Notifications';
import IdeaDetails from './pages/IdeaDetails';
import { api } from './services/api';

const SESSION_KEY='smart-ideas-react-session';
export default function App(){
 const [session,setSession]=useState(()=>JSON.parse(localStorage.getItem(SESSION_KEY)||'null'));
 const [page,setPage]=useState('dashboard'); const [ideas,setIdeas]=useState([]); const [notifications,setNotifications]=useState([]);
 const [selectedId,setSelectedId]=useState(null); const [loading,setLoading]=useState(false); const [error,setError]=useState(''); const [mobileOpen,setMobileOpen]=useState(false); const [toast,setToast]=useState('');
 const showToast=(m)=>{setToast(m);setTimeout(()=>setToast(''),2600)};
 useEffect(()=>{if(session){Promise.all([api.getIdeas(),api.getNotifications()]).then(([a,b])=>{setIdeas(a);setNotifications(b)}).catch(e=>showToast(e.message));}},[session]);
 const login=async(payload)=>{setLoading(true);setError('');try{const result=await api.login(payload);localStorage.setItem('smart-ideas-token',result.token);localStorage.setItem(SESSION_KEY,JSON.stringify(result.user));setSession(result.user);}catch(e){setError(e.message)}finally{setLoading(false)}};
 const logout=()=>{localStorage.removeItem(SESSION_KEY);localStorage.removeItem('smart-ideas-token');setSession(null);setPage('dashboard')};
 const navigate=p=>{setPage(p);setSelectedId(null);window.scrollTo({top:0,behavior:'smooth'})};
 const openIdea=id=>{setSelectedId(id);setPage('details');window.scrollTo({top:0,behavior:'smooth'})};
 const createIdea=async(payload)=>{setLoading(true);try{const idea=await api.createIdea(payload);setIdeas(prev=>[idea,...prev]);showToast(payload.status==='draft'?'تم حفظ الفكرة كمسودة':'تم إرسال الفكرة للتقييم');if(payload.status!=='draft'){openIdea(idea.id);api.evaluateIdea(idea.id).then(updated=>{setIdeas(prev=>prev.map(i=>i.id===updated.id?updated:i));showToast('اكتمل تقييم الفكرة')});}else navigate('ideas');}finally{setLoading(false)}};
 const readAll=async()=>{const items=await api.markNotificationsRead();setNotifications(items);showToast('تم تحديد الإشعارات كمقروءة')};
 if(!session)return <Login onLogin={login} loading={loading} error={error}/>;
 const selected=ideas.find(i=>i.id===selectedId);
 let content;
 if(page==='dashboard')content=<Dashboard ideas={ideas} notifications={notifications} onNavigate={navigate} onOpen={openIdea}/>;
 else if(page==='ideas')content=<Ideas ideas={ideas} onOpen={openIdea} onNavigate={navigate} isAdmin={session.role==='admin'}/>;
 else if(page==='new')content=<NewIdea onCreate={createIdea} loading={loading}/>;
 else if(page==='top')content=<TopIdeas ideas={ideas} onOpen={openIdea}/>;
 else if(page==='analytics')content=<Analytics ideas={ideas}/>;
 else if(page==='notifications')content=<Notifications items={notifications} onReadAll={readAll}/>;
 else if(page==='details')content=<IdeaDetails idea={selected} onBack={()=>navigate('ideas')}/>;
 return <div className="app-shell"><Header page={page} onNavigate={navigate} user={session} unread={notifications.filter(n=>!n.read).length} mobileOpen={mobileOpen} setMobileOpen={setMobileOpen} onLogout={logout}/><main>{content}</main>{toast&&<div className="toast">{toast}</div>}</div>;
}
