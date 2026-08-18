<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = env_value(
        'DB_DSN',
        'mysql:host=127.0.0.1;dbname=smart_ideas;charset=utf8mb4'
    );

    $user = env_value('DB_USER', 'root') ?? 'root';
    $password = env_value('DB_PASSWORD', '') ?? '';

    try {
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $pdo;
    } catch (PDOException $e) {
        http_response_code(500);

        error_log('Database connection failed: ' . $e->getMessage());

        echo json_encode([
            'error' => 'تعذر الاتصال بقاعدة البيانات'
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }
}