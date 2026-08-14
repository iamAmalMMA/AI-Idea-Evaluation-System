<?php

declare(strict_types=1);

if (ob_get_level() === 0) {
    ob_start();
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

$ideas = db_fetch_ideas();
$notifications = db_fetch_notifications();

/*
|--------------------------------------------------------------------------
| Backend API URL
|--------------------------------------------------------------------------
*/

$backendApiUrl = rtrim(
    env_value(
        'BACKEND_API_URL',
        'http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api'
    ),
    '/'
);

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

if (($_GET['action'] ?? '') === 'logout') {

    $ch = curl_init($backendApiUrl . '/logout.php');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT => 10,
    ]);

    curl_exec($ch);
    curl_close($ch);

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();

        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    redirect('login.php');
}

/*
|--------------------------------------------------------------------------
| POST Actions
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $formAction = trim((string)($_POST['form_action'] ?? ''));

    /*
    |--------------------------------------------------------------------------
    | Login
    |--------------------------------------------------------------------------
    */

    if ($formAction === 'login') {

        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            flash('يرجى إدخال البريد الإلكتروني وكلمة المرور');
            redirect('login.php');
        }

        $payload = json_encode([
            'email' => $email,
            'password' => $password,
        ], JSON_UNESCAPED_UNICODE);

        $ch = curl_init($backendApiUrl . '/login.php');

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json; charset=utf-8',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        curl_close($ch);

        if ($response === false || $curlError !== '') {
            flash('تعذر الاتصال بخدمة تسجيل الدخول');
            redirect('login.php');
        }

        $result = json_decode($response, true);

        if (
            $httpCode !== 200 ||
            !is_array($result) ||
            empty($result['success']) ||
            !isset($result['user']) ||
            !is_array($result['user'])
        ) {
            flash(
                is_array($result) && isset($result['error'])
                    ? (string)$result['error']
                    : 'البريد الإلكتروني أو كلمة المرور غير صحيحة'
            );

            redirect('login.php');
        }

        /*
         * The backend has verified the credentials.
         * Store the verified user in the frontend session.
         */

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (string)($result['user']['id'] ?? ''),
            'name' => (string)($result['user']['name'] ?? ''),
            'email' => (string)($result['user']['email'] ?? ''),
            'role' => (string)($result['user']['role'] ?? ''),
            'department' => (string)($result['user']['department'] ?? ''),
        ];

        redirect(url('dashboard'));
    }

    /*
    |--------------------------------------------------------------------------
    | Everything below this point requires a logged-in user
    |--------------------------------------------------------------------------
    */

    if (!current_user()) {
        redirect('login.php');
    }

    /*
    |--------------------------------------------------------------------------
    | Create / Edit Idea
    |--------------------------------------------------------------------------
    */

    if ($formAction === 'create_idea') {

        if (is_admin()) {
            flash('إضافة الأفكار متاحة للموظفين فقط');
            redirect(url('dashboard'));
        }

        $isDraft = ($_POST['submit_type'] ?? 'processing') === 'draft';

        $editingId = trim((string)($_POST['idea_id'] ?? ''));

        $title = trim((string)($_POST['title'] ?? ''));

        $departmentChoice = trim(
            (string)($_POST['department'] ?? '')
        );

        $categoryChoice = trim(
            (string)($_POST['category'] ?? '')
        );

        $departmentOther = trim(
            (string)($_POST['department_other'] ?? '')
        );

        $categoryOther = trim(
            (string)($_POST['category_other'] ?? '')
        );

        $department = $departmentChoice === '__other__'
            ? $departmentOther
            : $departmentChoice;

        $category = $categoryChoice === '__other__'
            ? $categoryOther
            : $categoryChoice;

        $description = trim(
            (string)($_POST['description'] ?? '')
        );

        $currentUser = current_user() ?? [];

        $currentUserId = (int)($currentUser['id'] ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Find idea being edited
        |--------------------------------------------------------------------------
        */

        $editingIdea = null;

        if ($editingId !== '') {

            foreach ($ideas as $candidate) {

                if (
                    (string)($candidate['id'] ?? '') !==
                    $editingId
                ) {
                    continue;
                }

                $ownsIdea =
                    (string)($candidate['employee_id'] ?? '') ===
                    (string)$currentUserId;

                $editableStatus =
                    (string)($candidate['status'] ?? '') === 'draft';

                if (!$ownsIdea || !$editableStatus) {
                    flash('لا يمكن تعديل هذه الفكرة');
                    redirect(url('ideas'));
                }

                $editingIdea = $candidate;

                break;
            }

            if ($editingIdea === null) {
                flash('الفكرة المطلوبة غير موجودة');
                redirect(url('ideas'));
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Validate submitted idea
        |--------------------------------------------------------------------------
        */

        if (!$isDraft) {

            if ($title === '') {
                flash('يرجى كتابة عنوان الفكرة');

                redirect(
                    url(
                        'new',
                        $editingId !== ''
                            ? ['id' => $editingId]
                            : []
                    )
                );
            }

            if (text_length($title) < 5) {
                flash('عنوان الفكرة قصير جدًا');

                redirect(
                    url(
                        'new',
                        $editingId !== ''
                            ? ['id' => $editingId]
                            : []
                    )
                );
            }

            if ($department === '') {
                flash('يرجى اختيار الإدارة أو كتابة إدارة أخرى');

                redirect(
                    url(
                        'new',
                        $editingId !== ''
                            ? ['id' => $editingId]
                            : []
                    )
                );
            }

            if ($category === '') {
                flash('يرجى اختيار التصنيف أو كتابة تصنيف آخر');

                redirect(
                    url(
                        'new',
                        $editingId !== ''
                            ? ['id' => $editingId]
                            : []
                    )
                );
            }

            if ($description === '') {
                flash('يرجى كتابة وصف واضح للفكرة');

                redirect(
                    url(
                        'new',
                        $editingId !== ''
                            ? ['id' => $editingId]
                            : []
                    )
                );
            }

            if (text_length($description) < 30) {
                flash('يرجى كتابة وصف أوضح للفكرة لا يقل عن 30 حرفًا');

                redirect(
                    url(
                        'new',
                        $editingId !== ''
                            ? ['id' => $editingId]
                            : []
                    )
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Save idea
        |--------------------------------------------------------------------------
        */

        $pdo = db();

        $pdo->beginTransaction();

        try {

            $storedStatus = $isDraft
                ? 'draft'
                : 'evaluated';

            /*
            |--------------------------------------------------------------------------
            | Update existing draft
            |--------------------------------------------------------------------------
            */

            if ($editingIdea !== null) {

                $ideaId = (int)$editingIdea['id'];

                $stmt = $pdo->prepare(
                    'UPDATE ideas
                     SET title=?,
                         description=?,
                         department=?,
                         department_is_other=?,
                         category=?,
                         category_is_other=?,
                         status=?,
                         score=NULL
                     WHERE id=? AND user_id=?'
                );

                $stmt->execute([
                    $title !== ''
                        ? $title
                        : 'مسودة بدون عنوان',

                    $description,

                    $department ?: null,

                    $departmentChoice === '__other__'
                        ? 1
                        : 0,

                    $category ?: null,

                    $categoryChoice === '__other__'
                        ? 1
                        : 0,

                    $storedStatus,

                    $ideaId,

                    $currentUserId,
                ]);

                $pdo
                    ->prepare(
                        'DELETE FROM evaluations WHERE idea_id=?'
                    )
                    ->execute([$ideaId]);

            } else {

                /*
                |--------------------------------------------------------------------------
                | Create new idea
                |--------------------------------------------------------------------------
                */

                $next = (int)$pdo
                    ->query(
                        'SELECT COALESCE(MAX(id),0)+1 FROM ideas'
                    )
                    ->fetchColumn();

                $ideaNumber =
                    'IDEA-' .
                    date('Y') .
                    '-' .
                    str_pad(
                        (string)$next,
                        4,
                        '0',
                        STR_PAD_LEFT
                    );

                $stmt = $pdo->prepare(
                    'INSERT INTO ideas
                    (
                        idea_number,
                        user_id,
                        title,
                        description,
                        department,
                        department_is_other,
                        category,
                        category_is_other,
                        status
                    )
                    VALUES (?,?,?,?,?,?,?,?,?)'
                );

                $stmt->execute([
                    $ideaNumber,

                    $currentUserId,

                    $title !== ''
                        ? $title
                        : 'مسودة بدون عنوان',

                    $description,

                    $department ?: null,

                    $departmentChoice === '__other__'
                        ? 1
                        : 0,

                    $category ?: null,

                    $categoryChoice === '__other__'
                        ? 1
                        : 0,

                    $storedStatus,
                ]);

                $ideaId = (int)$pdo->lastInsertId();
            }

            /*
            |--------------------------------------------------------------------------
            | AI Evaluation
            |--------------------------------------------------------------------------
            */

            if (!$isDraft) {

                $ideaForAi = [
                    'title' => $title,
                    'description' => $description,
                    'department' => $department,
                    'category' => $category,
                ];

                $result = evaluate_idea_with_ai($ideaForAi);

                $score = round(
                    max(
                        0,
                        min(
                            5,
                            (float)($result['score'] ?? 0)
                        )
                    ),
                    1
                );

                $ev = $result['evaluation'] ?? [];

                $stmt = $pdo->prepare(
                    'INSERT INTO evaluations
                    (
                        idea_id,
                        innovation,
                        feasibility,
                        sustainability,
                        cost,
                        business_value,
                        final_score,
                        strengths,
                        improvements,
                        feedback,
                        improved_title,
                        improved_description
                    )
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?)'
                );

                $stmt->execute([
                    $ideaId,

                    (float)($ev['innovation'] ?? 0),

                    (float)($ev['feasibility'] ?? 0),

                    (float)($ev['sustainability'] ?? 0),

                    (float)($ev['cost'] ?? 0),

                    (float)($ev['business_value'] ?? 0),

                    $score,

                    json_encode(
                        $ev['strengths'] ?? [],
                        JSON_UNESCAPED_UNICODE
                    ),

                    json_encode(
                        $ev['improvements'] ?? [],
                        JSON_UNESCAPED_UNICODE
                    ),

                    (string)($ev['feedback'] ?? ''),

                    (string)(
                        $ev['improvedTitle'] ?? $title
                    ),

                    (string)(
                        $ev['improvedDescription'] ??
                        $description
                    ),
                ]);

                $pdo
                    ->prepare(
                        'UPDATE ideas SET score=? WHERE id=?'
                    )
                    ->execute([
                        $score,
                        $ideaId
                    ]);

                db_add_notification(
                    null,
                    'admin',
                    $ideaId,
                    'فكرة جديدة تم تقييمها',
                    'تم إرسال فكرة جديدة وإكمال تقييمها الذكي وهي جاهزة للمراجعة.'
                );
            }

            $pdo->commit();

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $e;
        }

        flash(
            $isDraft
                ? 'تم حفظ الفكرة كمسودة بنجاح'
                : 'تم إرسال الفكرة وإكمال التقييم الذكي التجريبي'
        );

        redirect(
            $isDraft
                ? url('ideas')
                : url(
                    'details',
                    ['id' => (string)$ideaId]
                )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Idea Decision
    |--------------------------------------------------------------------------
    */

    if ($formAction === 'admin_idea_decision') {

        require_admin();

        $ideaId = (int)($_POST['idea_id'] ?? 0);

        $decision = trim(
            (string)($_POST['decision'] ?? '')
        );

        $returnTo = trim(
            (string)($_POST['return_to'] ?? 'details')
        );

        $stmt = db()->prepare(
            'SELECT id,user_id,status
             FROM ideas
             WHERE id=?'
        );

        $stmt->execute([$ideaId]);

        $idea = $stmt->fetch();

        if (!$idea) {
            flash('الفكرة غير موجودة');
            redirect(url('ideas'));
        }

        $currentStatus = (string)$idea['status'];

        if ($currentStatus === 'draft') {
            flash('لا يمكن اتخاذ قرار على فكرة ما زالت مسودة');
            redirect(url('ideas'));
        }

        $redirectAfter = static function () use (
            $returnTo,
            $ideaId
        ): never {

            redirect(
                $returnTo === 'ideas'
                    ? url('ideas')
                    : url(
                        'details',
                        ['id' => (string)$ideaId]
                    )
            );
        };

        if ($decision === 'rejected') {

            $newStatus = 'rejected';

            $message =
                'تم رفض فكرتك بعد مراجعتها من مدير النظام.';

        } elseif ($decision === 'approved') {

            if ($currentStatus !== 'evaluated') {
                flash(
                    'الترشيح للتنفيذ متاح فقط بعد اكتمال تقييم الفكرة'
                );

                $redirectAfter();
            }

            $newStatus = 'approved';

            $message =
                'تم ترشيح فكرتك للتنفيذ بعد مراجعتها من مدير النظام.';

        } else {

            flash('القرار غير صالح');

            $redirectAfter();
        }

        $stmt = db()->prepare(
            'UPDATE ideas
             SET status=?,
                 decision_by=?,
                 decision_at=NOW()
             WHERE id=?'
        );

        $stmt->execute([
            $newStatus,
            (int)(current_user()['id'] ?? 0),
            $ideaId,
        ]);

        db_add_notification(
            (int)$idea['user_id'],
            null,
            $ideaId,
            'تحديث حالة الفكرة',
            $message
        );

        flash(
            $newStatus === 'rejected'
                ? 'تم رفض الفكرة'
                : 'تم ترشيح الفكرة للتنفيذ'
        );

        $redirectAfter();
    }

    /*
    |--------------------------------------------------------------------------
    | Mark all notifications as read
    |--------------------------------------------------------------------------
    */

    if ($formAction === 'read_all') {

    $payload = json_encode([
        'action' => 'mark_all_read',
    ], JSON_UNESCAPED_UNICODE);

    /*
     * Release the PHP session lock before calling the backend.
     * The backend needs to open the same session to authenticate
     * the current user.
     */
    session_write_close();

    $ch = curl_init($backendApiUrl . '/notifications.php');

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_COOKIE => session_name() . '=' . session_id(),
        CURLOPT_TIMEOUT => 15,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    /*
     * Reopen the frontend session after the backend request completes.
     */
    session_start();

    if (
        $response === false ||
        $curlError !== '' ||
        $httpCode !== 200
    ) {
        flash('تعذر تحديث الإشعارات');
        redirect(url('notifications'));
    }

    $result = json_decode($response, true);

    if (
        !is_array($result) ||
        empty($result['success'])
    ) {
        flash(
            is_array($result) && isset($result['error'])
                ? (string)$result['error']
                : 'تعذر تحديث الإشعارات'
        );

        redirect(url('notifications'));
    }

    flash('تم تحديد جميع الإشعارات كمقروءة');

    redirect(url('notifications'));
}
}

/*
|--------------------------------------------------------------------------
| Load data for the current page
|--------------------------------------------------------------------------
*/

$ideas = db_fetch_ideas();

$notifications = db_fetch_notifications();

$visibleIdeas = current_user()
    ? user_ideas($ideas)
    : [];

$visibleNotifications = current_user()
    ? visible_notifications($notifications)
    : [];