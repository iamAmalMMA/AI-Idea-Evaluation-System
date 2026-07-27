import { seedIdeas, seedNotifications } from '../data/seed';

const BASE_URL = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api';
const USE_MOCK = (import.meta.env.VITE_USE_MOCK_API ?? 'true') === 'true';
const IDEAS_KEY = 'smart-ideas-react-data';
const NOTIFICATIONS_KEY = 'smart-ideas-react-notifications';

const wait = (ms = 350) => new Promise(resolve => setTimeout(resolve, ms));
const clone = value => JSON.parse(JSON.stringify(value));

function getLocal(key, fallback) {
  const stored = localStorage.getItem(key);
  if (stored) return JSON.parse(stored);
  localStorage.setItem(key, JSON.stringify(fallback));
  return clone(fallback);
}

function setLocal(key, value) {
  localStorage.setItem(key, JSON.stringify(value));
}

async function request(path, options = {}) {
  const token = localStorage.getItem('smart-ideas-token');
  const response = await fetch(`${BASE_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
      ...options.headers
    }
  });
  if (!response.ok) {
    const error = await response.json().catch(() => ({}));
    throw new Error(error.message || 'تعذر الاتصال بالخادم');
  }
  if (response.status === 204) return null;
  return response.json();
}

export const api = {
  async login(payload) {
    if (!USE_MOCK) return request('/auth/login', { method: 'POST', body: JSON.stringify(payload) });
    await wait();
    return {
      token: 'mock-token',
      user: { id: 'user-1', name: payload.role === 'admin' ? 'مدير النظام' : 'لمى الغامدي', email: payload.email, role: payload.role }
    };
  },
  async getIdeas() {
    if (!USE_MOCK) return request('/ideas');
    await wait(200);
    return getLocal(IDEAS_KEY, seedIdeas);
  },
  async getIdea(id) {
    if (!USE_MOCK) return request(`/ideas/${id}`);
    await wait(150);
    return getLocal(IDEAS_KEY, seedIdeas).find(x => x.id === id);
  },
  async createIdea(payload) {
    if (!USE_MOCK) return request('/ideas', { method: 'POST', body: JSON.stringify(payload) });
    await wait();
    const ideas = getLocal(IDEAS_KEY, seedIdeas);
    const idea = {
      ...payload,
      id: crypto.randomUUID(),
      number: `IDEA-2026-${String(ideas.length + 1).padStart(4, '0')}`,
      employee: 'لمى الغامدي',
      date: new Date().toISOString().slice(0, 10),
      score: null
    };
    ideas.unshift(idea);
    setLocal(IDEAS_KEY, ideas);
    return idea;
  },
  async evaluateIdea(id) {
    if (!USE_MOCK) return request(`/ideas/${id}/evaluate`, { method: 'POST' });
    await wait(1200);
    const ideas = getLocal(IDEAS_KEY, seedIdeas);
    const index = ideas.findIndex(x => x.id === id);
    if (index < 0) throw new Error('الفكرة غير موجودة');
    ideas[index] = {
      ...ideas[index], status: 'evaluated', score: 8.1,
      evaluation: {
        innovation: 8.3, feasibility: 7.8, need: 8.7, impact: 8.4, cost: 7.2, sustainability: 7.8, clarity: 8.5,
        strengths: ['الفكرة مرتبطة باحتياج واضح', 'لها أثر متوقع قابل للقياس'],
        improvements: ['تحديد الموارد المطلوبة', 'إضافة خطة تنفيذ مرحلية'],
        feedback: 'الفكرة جيدة وقابلة للتطوير، ويوصى بتجربة نموذج أولي على نطاق محدود.',
        improvedTitle: `${ideas[index].title} — نسخة مطورة`,
        improvedDescription: `صياغة محسنة للفكرة: ${ideas[index].description} مع تحديد خطوات تنفيذ ومؤشرات قياس واضحة.`
      }
    };
    setLocal(IDEAS_KEY, ideas);
    return ideas[index];
  },
  async getNotifications() {
    if (!USE_MOCK) return request('/notifications');
    await wait(150);
    return getLocal(NOTIFICATIONS_KEY, seedNotifications);
  },
  async markNotificationsRead() {
    if (!USE_MOCK) return request('/notifications/read-all', { method: 'POST' });
    const items = getLocal(NOTIFICATIONS_KEY, seedNotifications).map(n => ({ ...n, read: true }));
    setLocal(NOTIFICATIONS_KEY, items);
    return items;
  }
};
