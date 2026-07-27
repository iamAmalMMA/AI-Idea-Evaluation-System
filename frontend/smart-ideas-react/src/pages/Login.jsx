import { useState } from 'react';
import { Mail, LockKeyhole, LogIn } from 'lucide-react';
import logo from '../assets/jeddah-logo.png';

export default function Login({ onLogin, loading, error }) {
  const [form, setForm] = useState({ email: 'lama@jeddah.gov.sa', password: '12345678', role: 'employee' });
  const submit = e => { e.preventDefault(); onLogin(form); };
  return (
    <main className="login-page">
      <section className="login-panel">
        <img src={logo} className="login-logo" alt="شعار أمانة جدة"/>
        <div className="login-heading"><span>منصة الأفكار الذكية</span><h1>مرحبًا بك</h1><p>سجّل الدخول لتقديم الأفكار ومتابعة نتائج تقييمها.</p></div>
        <form onSubmit={submit}>
          <label>البريد الإلكتروني<div className="input-icon"><Mail size={19}/><input type="email" value={form.email} onChange={e=>setForm({...form,email:e.target.value})} required/></div></label>
          <label>كلمة المرور<div className="input-icon"><LockKeyhole size={19}/><input type="password" value={form.password} onChange={e=>setForm({...form,password:e.target.value})} required/></div></label>
          <label>نوع الحساب<select value={form.role} onChange={e=>setForm({...form,role:e.target.value})}><option value="employee">موظف</option><option value="admin">مدير النظام</option></select></label>
          {error && <div className="form-error">{error}</div>}
          <button className="primary-button login-button" disabled={loading}><LogIn size={19}/>{loading ? 'جاري الدخول...' : 'تسجيل الدخول'}</button>
        </form>
        <small>نسخة تجريبية — يمكن استخدام أي بريد وكلمة مرور.</small>
      </section>
      <section className="login-visual"><div><h2>فكرة اليوم، أثر الغد</h2><p>بيئة موحدة لالتقاط الأفكار الواعدة وتحويلها إلى مبادرات قابلة للتنفيذ.</p></div></section>
    </main>
  );
}
