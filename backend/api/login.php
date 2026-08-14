<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode([
        'error' => 'طريقة الطلب غير مسموح بها'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode([
        'error' => 'بيانات الطلب غير صالحة'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$email = trim((string)($input['email'] ?? ''));
$password = (string)($input['password'] ?? '');

if ($email === '' || $password === '') {
    http_response_code(400);

    echo json_encode([
        'error' => 'البريد الإلكتروني وكلمة المرور مطلوبان'
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

try {
    $stmt = db()->prepare(
        'SELECT id, name, email, password_hash, role, department
         FROM users
         WHERE email = ?
         LIMIT 1'
    );

    $stmt->execute([$email]);

    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        http_response_code(401);

        echo json_encode([
            'error' => 'البريد الإلكتروني أو كلمة المرور غير صحيحة'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    session_regenerate_id(true);

    $_SESSION['user'] = [
        'id' => (string)$user['id'],
        'name' => (string)$user['name'],
        'email' => (string)$user['email'],
        'role' => (string)$user['role'],
        'department' => (string)($user['department'] ?? ''),
    ];

    echo json_encode([
        'success' => true,
        'user' => $_SESSION['user']
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);

    error_log('Login API error: ' . $e->getMessage());

    echo json_encode([
        'error' => 'حدث خطأ أثناء تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
}