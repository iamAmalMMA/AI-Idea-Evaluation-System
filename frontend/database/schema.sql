CREATE DATABASE IF NOT EXISTS smart_ideas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE smart_ideas;

SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS evaluations;
DROP TABLE IF EXISTS ideas;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS=1;

-- =========================================================
-- Users
-- The current project has only employee and admin accounts.
-- =========================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('employee', 'admin') NOT NULL DEFAULT 'employee',
    department VARCHAR(150) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

-- =========================================================
-- Ideas
-- Matches the fields used by pages/new.php and pages/details.php.
-- Drafts belong only to their owner. Admin can approve/reject
-- non-draft ideas according to application rules.
-- =========================================================
CREATE TABLE ideas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_number VARCHAR(40) NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,

    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,

    department VARCHAR(150) NULL,
    department_is_other TINYINT(1) NOT NULL DEFAULT 0,
    category VARCHAR(150) NULL,
    category_is_other TINYINT(1) NOT NULL DEFAULT 0,

    status ENUM(
        'draft',
        'submitted',
        'processing',
        'evaluated',
        'approved',
        'rejected'
    ) NOT NULL DEFAULT 'draft',

    -- Cached overall AI score used by dashboards, analytics, and Top 5.
    score DECIMAL(2,1) NULL,

    -- Admin decision information.
    decision_by BIGINT UNSIGNED NULL,
    decision_at DATETIME NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_ideas_score
        CHECK (score IS NULL OR (score >= 0 AND score <= 5)),

    CONSTRAINT fk_ideas_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_ideas_decision_user
        FOREIGN KEY (decision_by) REFERENCES users(id)
        ON DELETE SET NULL
);

-- =========================================================
-- Evaluations
-- No revision workflow: exactly one AI evaluation per idea.
-- Matches docs/AI-CONTRACT.md and pages/details.php.
-- =========================================================
CREATE TABLE evaluations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    idea_id BIGINT UNSIGNED NOT NULL UNIQUE,

    innovation DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    feasibility DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    sustainability DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    cost DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    business_value DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    final_score DECIMAL(2,1) NOT NULL DEFAULT 0.0,

    strengths JSON NOT NULL,
    improvements JSON NOT NULL,

    -- Optional summary displayed by pages/details.php when available.
    feedback TEXT NULL,

    improved_title VARCHAR(255) NOT NULL,
    improved_description TEXT NOT NULL,

    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT chk_eval_innovation CHECK (innovation BETWEEN 0 AND 5),
    CONSTRAINT chk_eval_feasibility CHECK (feasibility BETWEEN 0 AND 5),
    CONSTRAINT chk_eval_sustainability CHECK (sustainability BETWEEN 0 AND 5),
    CONSTRAINT chk_eval_cost CHECK (cost BETWEEN 0 AND 5),
    CONSTRAINT chk_eval_business_value CHECK (business_value BETWEEN 0 AND 5),
    CONSTRAINT chk_eval_final_score CHECK (final_score BETWEEN 0 AND 5),

    CONSTRAINT fk_evaluations_idea
        FOREIGN KEY (idea_id) REFERENCES ideas(id)
        ON DELETE CASCADE
);

-- =========================================================
-- Notifications
-- Supports notifications for a specific user, a role, an idea,
-- or general notifications visible to everyone.
-- =========================================================
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    recipient_role ENUM('employee', 'admin') NULL,
    idea_id BIGINT UNSIGNED NULL,

    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_notifications_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_notifications_idea
        FOREIGN KEY (idea_id) REFERENCES ideas(id)
        ON DELETE SET NULL
);

-- =========================================================
-- Indexes used by the current pages and Top 5/analytics logic.
-- =========================================================
CREATE INDEX idx_ideas_user_status
    ON ideas(user_id, status);

CREATE INDEX idx_ideas_status_score
    ON ideas(status, score);

CREATE INDEX idx_ideas_created_at
    ON ideas(created_at);

CREATE INDEX idx_notifications_user_read
    ON notifications(user_id, is_read);

CREATE INDEX idx_notifications_role_read
    ON notifications(recipient_role, is_read);


-- =========================================================
-- Demo accounts for local XAMPP testing
-- Password for both accounts: 12345678
-- =========================================================
INSERT INTO users (name,email,password_hash,role,department) VALUES
('لمى الغامدي','lama@jeddah.gov.sa','$2y$12$ge65M4hBlvIdb7hSc1CBgOjDIQ3UYsduEfxSNqF05OoB1ccvFDRQa','employee','تقنية المعلومات'),
('سارة الحربي','sara@jeddah.gov.sa','$2y$12$ge65M4hBlvIdb7hSc1CBgOjDIQ3UYsduEfxSNqF05OoB1ccvFDRQa','employee','التشغيل والصيانة'),
('نورة القحطاني','noura@jeddah.gov.sa','$2y$12$ge65M4hBlvIdb7hSc1CBgOjDIQ3UYsduEfxSNqF05OoB1ccvFDRQa','employee','النظافة'),
('ريم الزهراني','reem@jeddah.gov.sa','$2y$12$ge65M4hBlvIdb7hSc1CBgOjDIQ3UYsduEfxSNqF05OoB1ccvFDRQa','employee','خدمة العملاء'),
('خالد العتيبي','khaled@jeddah.gov.sa','$2y$12$ge65M4hBlvIdb7hSc1CBgOjDIQ3UYsduEfxSNqF05OoB1ccvFDRQa','employee','المرافق العامة'),
('مدير النظام','admin@jeddah.gov.sa','$2y$12$ge65M4hBlvIdb7hSc1CBgOjDIQ3UYsduEfxSNqF05OoB1ccvFDRQa','admin','الإدارة العامة');

-- =========================================================
-- Demo data: 10 realistic ideas for presentations/testing
-- =========================================================
INSERT INTO ideas
(idea_number,user_id,title,description,department,category,status,score,decision_by,decision_at,created_at)
VALUES
('IDEA-2026-0001',1,'نظام ذكي لترشيد استهلاك الطاقة','منصة تعتمد على تحليل بيانات العدادات والحساسات لاكتشاف أنماط الاستهلاك غير الطبيعي في مباني الأمانة، وإرسال تنبيهات واقتراح إجراءات عملية لتقليل استهلاك الكهرباء والتكاليف التشغيلية.','تقنية المعلومات','التحول الرقمي','approved',4.9,6,'2026-08-07 10:30:00','2026-07-10 09:00:00'),
('IDEA-2026-0002',2,'التنبؤ بأعطال الطرق بالذكاء الاصطناعي','حل يستخدم صور الطرق وبيانات البلاغات والصيانة السابقة للتنبؤ بالمواقع الأكثر عرضة للتلف، بما يساعد فرق الصيانة على ترتيب الأولويات وتنفيذ الصيانة الوقائية قبل تفاقم المشكلة.','التشغيل والصيانة','الذكاء الاصطناعي','evaluated',4.8,NULL,NULL,'2026-07-12 10:15:00'),
('IDEA-2026-0003',3,'نظام ذكي لإدارة النفايات','استخدام حساسات لمراقبة مستوى امتلاء الحاويات وربطها بلوحة تحكم تقترح مسارات جمع محسنة، بهدف تقليل الرحلات غير الضرورية ورفع كفاءة النظافة وتحسين استجابة فرق التشغيل.','النظافة','المدن الذكية','approved',4.7,6,'2026-08-06 13:00:00','2026-07-14 11:20:00'),
('IDEA-2026-0004',2,'تحليل ازدحام مواقف السيارات','نظام يحلل إشغال المواقف في المناطق الحيوية ويعرض مستوى التوفر لحظيًا، مع لوحات تحليلية تساعد على تحديد أوقات الذروة ودعم قرارات التوسع أو إعادة توزيع المواقف.','النقل والمواقف','المدن الذكية','evaluated',4.6,NULL,NULL,'2026-07-16 08:45:00'),
('IDEA-2026-0005',4,'منصة ذكية لمتابعة البلاغات','منصة تجمع البلاغات وتصنفها آليًا حسب النوع والأولوية والموقع، ثم تقترح الجهة الأنسب لمعالجتها وتعرض مؤشرات عن زمن الاستجابة وجودة الإغلاق لتحسين تجربة المستفيد.','خدمة العملاء','التحول الرقمي','evaluated',4.5,NULL,NULL,'2026-07-18 12:10:00'),
('IDEA-2026-0006',5,'مراقبة جودة المرافق العامة','نظام موحد لتسجيل ومتابعة حالة المرافق العامة باستخدام تقارير ميدانية وصور دورية، مع تنبيهات للصيانة ومؤشرات تساعد في تقييم جودة المرافق وتحديد الاحتياجات ذات الأولوية.','المرافق العامة','تحسين الخدمات','evaluated',4.2,NULL,NULL,'2026-07-20 09:35:00'),
('IDEA-2026-0007',2,'تحسين جدولة فرق الصيانة','أداة تقترح جداول يومية لفرق الصيانة بناءً على الأولوية والموقع ونوع العطل وتوفر الموارد، بهدف تقليل وقت التنقل وزيادة عدد المهام المنجزة خلال اليوم.','التشغيل والصيانة','كفاءة الإنفاق','evaluated',4.0,NULL,NULL,'2026-07-23 14:25:00'),
('IDEA-2026-0008',3,'نظام ري ذكي للحدائق','ربط أنظمة الري ببيانات الرطوبة والطقس لتحديد كمية ووقت الري المناسب لكل منطقة خضراء، بما يساهم في تقليل هدر المياه والمحافظة على جودة المسطحات الخضراء.','الحدائق والتشجير','الاستدامة','rejected',3.6,6,'2026-08-05 11:00:00','2026-07-26 07:55:00'),
('IDEA-2026-0009',4,'تحليل رضا المستفيدين','تحليل آراء المستفيدين الواردة من الاستبيانات والقنوات الرقمية وتصنيف الملاحظات تلقائيًا إلى موضوعات متكررة تساعد الإدارات على اكتشاف نقاط التحسين ومتابعة تغير مستوى الرضا.','خدمة العملاء','تحليل البيانات','submitted',NULL,NULL,NULL,'2026-08-02 10:40:00'),
('IDEA-2026-0010',1,'مساعد رقمي لخدمات الموظفين','مساعد داخلي يجيب عن الأسئلة المتكررة المتعلقة بالإجراءات والأنظمة والخدمات الداخلية، ويوجه الموظف إلى النموذج أو الخدمة المناسبة لتقليل الوقت المستغرق في البحث والاستفسارات المتكررة.','الموارد البشرية','التحول الرقمي','processing',NULL,NULL,NULL,'2026-08-04 13:15:00');

INSERT INTO evaluations
(idea_id,innovation,feasibility,sustainability,cost,business_value,final_score,strengths,improvements,feedback,improved_title,improved_description)
VALUES
(1,4.9,4.8,5.0,4.7,5.0,4.9,
 JSON_ARRAY('خفض التكاليف التشغيلية','قابلية قياس الأثر','يدعم الاستدامة'),
 JSON_ARRAY('تحديد مصادر البيانات المطلوبة','تنفيذ تجربة أولية في مبنى واحد'),
 'الفكرة ذات أثر واضح وقابلة للتوسع بعد إثبات النتائج في نطاق تجريبي.',
 'منصة ذكية لتحسين كفاءة الطاقة في مباني الأمانة',
 'منصة تحليل ذكية تربط بيانات العدادات والحساسات لاكتشاف الهدر واقتراح إجراءات لتقليل استهلاك الطاقة مع لوحة مؤشرات لقياس الوفر.'),
(2,5.0,4.5,4.7,4.6,5.0,4.8,
 JSON_ARRAY('صيانة استباقية','تقليل الأعطال المفاجئة','استفادة قوية من البيانات التاريخية'),
 JSON_ARRAY('تجهيز بيانات صور مصنفة','تحديد دقة النموذج المستهدفة'),
 'الفكرة مبتكرة وتوفر قيمة تشغيلية عالية، ويعتمد نجاحها على جودة البيانات الميدانية.',
 'منصة تنبؤية لصيانة الطرق',
 'حل تنبؤي يوظف الصور والبلاغات وسجل الصيانة لتحديد احتمالية تلف الطرق وترتيب مواقع الصيانة حسب مستوى الخطورة.'),
(3,4.8,4.7,4.9,4.4,4.8,4.7,
 JSON_ARRAY('تحسين مسارات الجمع','خفض استهلاك الوقود','رفع مستوى النظافة'),
 JSON_ARRAY('تحديد نوع الحساسات','احتساب تكلفة التركيب والصيانة'),
 'حل عملي للمدن الذكية ويمكن تطبيقه تدريجيًا في المناطق الأعلى كثافة.',
 'إدارة ذكية للحاويات ومسارات جمع النفايات',
 'نظام يراقب امتلاء الحاويات ويرتب أولويات الجمع ويقترح المسار الأمثل للمركبات لتحسين الكفاءة وتقليل الرحلات.'),
(4,4.7,4.6,4.5,4.6,4.7,4.6,
 JSON_ARRAY('تحسين تجربة مستخدمي المواقف','دعم التخطيط','بيانات لحظية قابلة للتحليل'),
 JSON_ARRAY('دراسة توفر مصادر بيانات الإشغال','بدء تجربة في منطقة محددة'),
 'الفكرة مناسبة للبيئات ذات الكثافة العالية وتحتاج تحديد تقنية الرصد الأنسب.',
 'منصة تحليل وإدارة إشغال المواقف',
 'منصة تجمع بيانات إشغال المواقف وتعرض التوفر والذروة وتولد تقارير تساعد في التخطيط وتحسين توزيع السعة.'),
(5,4.6,4.8,4.4,4.3,4.7,4.5,
 JSON_ARRAY('تسريع توجيه البلاغ','رفع جودة المتابعة','تحسين مؤشرات الأداء'),
 JSON_ARRAY('توحيد تصنيفات البلاغات','تحديد آلية قياس جودة الإغلاق'),
 'تقدم قيمة مباشرة للمستفيد وللإدارات التشغيلية ويمكن دمجها مع القنوات الحالية.',
 'منصة ذكية لتصنيف ومتابعة البلاغات',
 'منصة موحدة تصنف البلاغات آليًا وتحدد الأولوية والجهة المختصة وتتابع زمن المعالجة وجودة الإغلاق عبر مؤشرات تشغيلية.'),
(6,4.0,4.5,4.3,4.0,4.2,4.2,
 JSON_ARRAY('توحيد متابعة المرافق','دعم الصيانة الوقائية','سهولة قياس الحالة'),
 JSON_ARRAY('تحديد نموذج موحد للتفتيش','ربط النظام بجدول الصيانة'),
 'فكرة عملية وقابلة للتنفيذ، ويمكن تعزيزها بإضافة مؤشرات جودة معيارية لكل نوع مرفق.',
 'نظام موحد لمراقبة جودة المرافق العامة',
 'نظام يسجل حالة المرافق ميدانيًا ويربط الصور والملاحظات بمؤشرات جودة وتنبيهات صيانة ولوحة متابعة للأولويات.'),
(7,4.1,4.4,4.0,3.8,3.9,4.0,
 JSON_ARRAY('رفع إنتاجية الفرق','تقليل وقت التنقل','تحسين توزيع المهام'),
 JSON_ARRAY('ربط الجدولة بتوفر الفرق','إضافة قيود الوقت والأولوية'),
 'الفكرة ذات قيمة تشغيلية جيدة وتحتاج تعريف قواعد الجدولة ومصادر البيانات بشكل أدق.',
 'جدولة ذكية لفرق الصيانة الميدانية',
 'أداة تخطيط يومي توزع أوامر العمل وفق الموقع والأولوية والمهارات وتوفر الفرق لتقليل التنقل ورفع الإنتاجية.'),
(8,4.0,3.7,4.8,2.9,3.6,3.6,
 JSON_ARRAY('تقليل استهلاك المياه','دعم أهداف الاستدامة'),
 JSON_ARRAY('خفض تكلفة الحساسات','تحديد خطة صيانة للأجهزة','دراسة العائد المالي'),
 'الأثر البيئي جيد لكن التكلفة والحاجة إلى تجهيزات ميدانية تقللان الجدوى الحالية.',
 'نظام ري مرن يعتمد على الرطوبة والطقس',
 'نظام يتحكم بجدولة الري وفق رطوبة التربة والطقس مع تطبيق تدريجي في الحدائق الأعلى استهلاكًا للمياه.' );

INSERT INTO notifications (user_id,recipient_role,idea_id,title,message,is_read,created_at) VALUES
(1,NULL,1,'تم ترشيح فكرتك للتنفيذ','تم ترشيح فكرة نظام ذكي لترشيد استهلاك الطاقة للتنفيذ بعد مراجعتها.',0,'2026-08-07 10:31:00'),
(3,NULL,3,'تم ترشيح فكرتك للتنفيذ','تم ترشيح فكرة نظام ذكي لإدارة النفايات للتنفيذ بعد مراجعتها.',0,'2026-08-06 13:01:00'),
(3,NULL,8,'تم تحديث حالة الفكرة','تمت مراجعة فكرة نظام الري الذكي واتخاذ القرار النهائي عليها.',1,'2026-08-05 11:01:00'),
(NULL,'admin',9,'فكرة جديدة بانتظار التحليل','تم استلام فكرة تحليل رضا المستفيدين وهي بانتظار اكتمال التحليل.',0,'2026-08-02 10:41:00');
