# منصة الأفكار الذكية — أمانة جدة

مشروع Frontend كامل باستخدام React + Vite، بتصميم عربي RTL وهوية خضراء، وجاهز للربط مع Backend.

## التشغيل

1. ثبتي Node.js LTS.
2. افتحي مجلد المشروع في VS Code.
3. افتحي Terminal داخل المجلد.
4. نفذي:

```bash
npm install
npm run dev
```

ثم افتحي الرابط الذي يظهر، غالبًا `http://localhost:5173`.

## الحساب التجريبي

يمكن إدخال أي بريد وكلمة مرور واختيار موظف أو مدير النظام.

## الربط بالـ Backend

انسخي `.env.example` إلى ملف جديد اسمه `.env` ثم غيّري:

```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_USE_MOCK_API=false
```

جميع طلبات السيرفر موجودة في:

`src/services/api.js`

## المسارات المطلوبة من الـ Backend

- `POST /auth/login`
- `GET /ideas`
- `GET /ideas/:id`
- `POST /ideas`
- `POST /ideas/:id/evaluate`
- `GET /notifications`
- `POST /notifications/read-all`

يجب أن يبقى مفتاح الذكاء الاصطناعي داخل الـ Backend فقط، ولا يوضع في React.
