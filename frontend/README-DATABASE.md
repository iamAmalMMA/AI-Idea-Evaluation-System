# تشغيل نسخة MySQL على XAMPP

1. شغّل Apache و MySQL من XAMPP.
2. افتح http://localhost/phpmyadmin
3. اختر Import واستورد الملف: database/schema.sql
4. الملف ينشئ قاعدة smart_ideas والجداول وحسابين تجريبيين تلقائياً.
5. شغّل المشروع من مجلده:
   C:\xampp\php\php.exe -S localhost:8000
6. افتح http://localhost:8000

حساب الموظف:
lama@jeddah.gov.sa
12345678

حساب المدير:
admin@jeddah.gov.sa
12345678

الاتصال الافتراضي مناسب لإعداد XAMPP المعتاد (root بدون كلمة مرور). إذا كان MySQL لديك بكلمة مرور، انسخ .env.example إلى .env وعدّل DB_PASSWORD.

ملاحظة: ملفات JSON القديمة موجودة كمرجع فقط ولم تعد مستخدمة في تشغيل التطبيق.
