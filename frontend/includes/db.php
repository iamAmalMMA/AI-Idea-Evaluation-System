<?php
declare(strict_types=1);

function env_value(string $key, ?string $default = null): ?string {
    static $env = null;
    if ($env === null) {
        $env = [];
        $path = dirname(__DIR__) . '/.env';
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
                [$k, $v] = array_map('trim', explode('=', $line, 2));
                $env[$k] = trim($v, "\"'");
            }
        }
    }
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? $env[$key] ?? null;
    return ($value === null || $value === '') ? $default : (string)$value;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    $dsn = env_value('DB_DSN', 'mysql:host=127.0.0.1;dbname=smart_ideas;charset=utf8mb4');
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
        $message = htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        exit('<!doctype html><html lang="ar" dir="rtl"><meta charset="utf-8"><title>خطأ قاعدة البيانات</title><body style="font-family:Arial;padding:40px;line-height:1.8"><h2>تعذر الاتصال بقاعدة البيانات</h2><p>تأكدي أن MySQL يعمل من XAMPP وأنك استوردت <code>database/schema.sql</code> في phpMyAdmin.</p><pre style="direction:ltr;text-align:left;background:#f5f5f5;padding:16px;border-radius:8px">'.$message.'</pre></body></html>');
    }
}

function db_fetch_ideas(): array {
    $sql = "SELECT i.*, u.name AS employee,
                   e.innovation, e.feasibility, e.sustainability, e.cost, e.business_value,
                   e.final_score AS evaluation_final_score, e.strengths, e.improvements,
                   e.feedback, e.improved_title, e.improved_description
            FROM ideas i
            JOIN users u ON u.id = i.user_id
            LEFT JOIN evaluations e ON e.idea_id = i.id
            ORDER BY i.created_at DESC, i.id DESC";
    $rows = db()->query($sql)->fetchAll();
    $ideas = [];
    foreach ($rows as $row) {
        $evaluation = null;
        if ($row['innovation'] !== null) {
            $evaluation = [
                'innovation' => (float)$row['innovation'],
                'feasibility' => (float)$row['feasibility'],
                'sustainability' => (float)$row['sustainability'],
                'cost' => (float)$row['cost'],
                'business_value' => (float)$row['business_value'],
                'strengths' => json_decode((string)$row['strengths'], true) ?: [],
                'improvements' => json_decode((string)$row['improvements'], true) ?: [],
                'feedback' => (string)($row['feedback'] ?? ''),
                'improvedTitle' => (string)($row['improved_title'] ?? ''),
                'improvedDescription' => (string)($row['improved_description'] ?? ''),
            ];
        }
        $ideas[] = [
            'id' => (string)$row['id'],
            'number' => (string)$row['idea_number'],
            'employee' => (string)$row['employee'],
            'employee_id' => (string)$row['user_id'],
            'title' => (string)$row['title'],
            'description' => (string)$row['description'],
            'department' => (string)($row['department'] ?? ''),
            'department_is_other' => (bool)$row['department_is_other'],
            'category' => (string)($row['category'] ?? ''),
            'category_is_other' => (bool)$row['category_is_other'],
            'status' => (string)$row['status'],
            'score' => $row['score'] === null ? null : (float)$row['score'],
            'evaluation' => $evaluation,
            'decision_by' => $row['decision_by'] === null ? null : (string)$row['decision_by'],
            'decision_at' => $row['decision_at'],
            'date' => substr((string)$row['created_at'], 0, 10),
            'created_at' => (string)$row['created_at'],
            'updated_at' => (string)$row['updated_at'],
        ];
    }
    return $ideas;
}

function db_fetch_notifications(): array {
    $rows = db()->query("SELECT * FROM notifications ORDER BY created_at DESC, id DESC")->fetchAll();
    return array_map(static fn(array $n): array => [
        'id' => (string)$n['id'],
        'recipient_id' => $n['user_id'] === null ? '' : (string)$n['user_id'],
        'recipient_role' => (string)($n['recipient_role'] ?? ''),
        'idea_id' => $n['idea_id'] === null ? '' : (string)$n['idea_id'],
        'title' => (string)$n['title'],
        'message' => (string)$n['message'],
        'read' => (bool)$n['is_read'],
        'time' => (string)$n['created_at'],
    ], $rows);
}

function db_add_notification(?int $userId, ?string $role, ?int $ideaId, string $title, string $message): void {
    $stmt = db()->prepare('INSERT INTO notifications (user_id, recipient_role, idea_id, title, message) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$userId, $role ?: null, $ideaId, $title, $message]);
}
