<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

$currentUser = $_SESSION['user'] ?? null;

if (!is_array($currentUser)) {
    json_response([
        'error' => 'يجب تسجيل الدخول أولاً'
    ], 401);
}

$currentUserId = (int) ($currentUser['id'] ?? 0);
$currentUserRole = (string) ($currentUser['role'] ?? '');

if ($currentUserId <= 0 || $currentUserRole === '') {
    json_response([
        'error' => 'جلسة المستخدم غير صالحة'
    ], 401);
}

/*
|--------------------------------------------------------------------------
| GET — Return visible notifications
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {

        $stmt = db()->prepare(
            'SELECT *
             FROM notifications
             WHERE
                (user_id IS NULL OR user_id = ?)
                AND
                (recipient_role IS NULL OR recipient_role = ?)
             ORDER BY created_at DESC, id DESC'
        );

        $stmt->execute([
            $currentUserId,
            $currentUserRole,
        ]);

        $rows = $stmt->fetchAll();

        $notifications = array_map(
            static fn(array $n): array => [
                'id' => (string) $n['id'],
                'recipient_id' => $n['user_id'] === null
                    ? ''
                    : (string) $n['user_id'],
                'recipient_role' => (string) ($n['recipient_role'] ?? ''),
                'idea_id' => $n['idea_id'] === null
                    ? ''
                    : (string) $n['idea_id'],
                'title' => (string) $n['title'],
                'message' => (string) $n['message'],
                'read' => (bool) $n['is_read'],
                'time' => (string) $n['created_at'],
            ],
            $rows
        );

        json_response([
            'success' => true,
            'notifications' => $notifications,
            'count' => count($notifications),
        ]);

    } catch (Throwable $e) {

        error_log(
            'Notifications GET API error: ' .
            $e->getMessage()
        );

        json_response([
            'error' => 'تعذر جلب الإشعارات'
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| POST — Mark notifications as read
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        json_response([
            'error' => 'بيانات الطلب غير صالحة'
        ], 400);
    }

    $action = trim(
        (string) ($input['action'] ?? '')
    );

    try {

        /*
        |--------------------------------------------------------------------------
        | Mark all visible notifications as read
        |--------------------------------------------------------------------------
        */

        if ($action === 'mark_all_read') {

            $stmt = db()->prepare(
                'UPDATE notifications
                 SET is_read = 1
                 WHERE
                    is_read = 0
                    AND
                    (user_id IS NULL OR user_id = ?)
                    AND
                    (recipient_role IS NULL OR recipient_role = ?)'
            );

            $stmt->execute([
                $currentUserId,
                $currentUserRole,
            ]);

            json_response([
                'success' => true,
                'message' => 'تم تعليم جميع الإشعارات كمقروءة',
                'updated' => $stmt->rowCount(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Mark one visible notification as read
        |--------------------------------------------------------------------------
        */

        if ($action === 'mark_read') {

            $notificationId = trim(
                (string) ($input['notification_id'] ?? '')
            );

            if (
                $notificationId === '' ||
                !ctype_digit($notificationId) ||
                (int) $notificationId <= 0
            ) {
                json_response([
                    'error' => 'معرف الإشعار غير صالح'
                ], 400);
            }

            $stmt = db()->prepare(
                'UPDATE notifications
                 SET is_read = 1
                 WHERE
                    id = ?
                    AND
                    (user_id IS NULL OR user_id = ?)
                    AND
                    (recipient_role IS NULL OR recipient_role = ?)'
            );

            $stmt->execute([
                (int) $notificationId,
                $currentUserId,
                $currentUserRole,
            ]);

            json_response([
                'success' => true,
                'message' => 'تم تعليم الإشعار كمقروء',
                'updated' => $stmt->rowCount(),
            ]);
        }

        json_response([
            'error' => 'الإجراء المطلوب غير معروف'
        ], 400);

    } catch (Throwable $e) {

        error_log(
            'Notifications POST API error: ' .
            $e->getMessage()
        );

        json_response([
            'error' => 'تعذر تحديث الإشعارات'
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| Unsupported method
|--------------------------------------------------------------------------
*/

json_response([
    'error' => 'طريقة الطلب غير مسموح بها'
], 405);
