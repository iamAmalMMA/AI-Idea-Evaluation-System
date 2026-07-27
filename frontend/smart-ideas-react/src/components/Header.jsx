import {
  Home, Plus, FileText, Star, BarChart3, Bell, Search, UserRound, Menu, X, LogOut
} from 'lucide-react';
import logo from '../assets/jeddah-logo.png';

const employeeItems = [
  ['dashboard', 'الرئيسية', Home], ['new', 'إضافة فكرة', Plus], ['ideas', 'أفكاري', FileText],
  ['top', 'أفضل الأفكار', Star], ['analytics', 'الإحصائيات', BarChart3], ['notifications', 'الإشعارات', Bell]
];
const adminItems = [
  ['dashboard', 'الرئيسية', Home], ['ideas', 'جميع الأفكار', FileText], ['top', 'أفضل الأفكار', Star],
  ['analytics', 'الإحصائيات', BarChart3], ['notifications', 'الإشعارات', Bell]
];

export default function Header({ page, onNavigate, user, unread, mobileOpen, setMobileOpen, onLogout }) {
  const items = user.role === 'admin' ? adminItems : employeeItems;
  return (
    <header className="site-header">
      <div className="header-inner">
        <button className="mobile-toggle" onClick={() => setMobileOpen(!mobileOpen)} aria-label="فتح القائمة">
          {mobileOpen ? <X size={22}/> : <Menu size={22}/>} 
        </button>
        <button className="brand-button" onClick={() => onNavigate('dashboard')} aria-label="الصفحة الرئيسية">
          <img src={logo} alt="شعار أمانة جدة" className="header-logo" />
        </button>
        <nav className={`main-nav ${mobileOpen ? 'open' : ''}`}>
          {items.map(([key, label, Icon]) => (
            <button key={key} className={`nav-link ${page === key ? 'active' : ''}`} onClick={() => { onNavigate(key); setMobileOpen(false); }}>
              <Icon size={20}/><span>{label}</span>
              {key === 'notifications' && unread > 0 && <b className="badge">{unread}</b>}
            </button>
          ))}
        </nav>
        <div className="header-tools">
          <button className="search-button" onClick={() => onNavigate('ideas')}><Search size={22}/><span>بحث</span></button>
          <div className="profile-chip">
            <div className="profile-avatar"><UserRound size={22}/></div>
            <div><strong>{user.name}</strong><small>{user.role === 'admin' ? 'مدير النظام' : 'موظف'}</small></div>
          </div>
          <button className="logout-icon" onClick={onLogout} title="تسجيل الخروج"><LogOut size={20}/></button>
        </div>
      </div>
    </header>
  );
}
