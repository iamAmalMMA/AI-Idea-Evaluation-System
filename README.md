# منصة الأفكار الذكية

نسخة Frontend/Prototype بلغة PHP جاهزة للعرض والربط مع Backend وAI حقيقي.

## البداية السريعة
1. انسخ المجلد إلى `C:\xampp\htdocs\AI-Idea-Evaluation`.
2. شغّل Apache من XAMPP.
3. افتح `http://localhost/AI-Idea-Evaluation/`.
4. افتح المجلد الرئيسي نفسه في VS Code عبر **File → Open Folder**.

## ملفات البدء للمطور
- `includes/bootstrap.php`: معالجة النماذج والحالات وقرارات المدير.
- `includes/functions.php`: الدوال العامة، Top 5، وحدود خدمة AI.
- `pages/`: واجهات الصفحات.
- `assets/`: CSS وJavaScript والصور.
- `database/schema.sql`: مخطط قاعدة البيانات المقترح.
- `docs/BACKEND-HANDOFF.md`: تعليمات الربط.
- `docs/AI-CONTRACT.md`: عقد مدخلات ومخرجات AI.

> البيانات الحالية تجريبية ومحفوظة في `data/*.json`، ويجب استبدالها بمستودع بيانات حقيقي عند الربط.
