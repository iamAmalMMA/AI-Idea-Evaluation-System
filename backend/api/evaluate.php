<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);

    echo json_encode(
        ['error' => 'طريقة الطلب غير مسموح بها'],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

if (!is_array($input)) {
    http_response_code(400);

    echo json_encode(
        ['error' => 'بيانات الطلب غير صالحة'],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

$title = trim((string)($input['title'] ?? ''));
$description = trim((string)($input['description'] ?? ''));

if ($title === '' && $description === '') {
    http_response_code(400);

    echo json_encode(
        ['error' => 'يرجى إدخال عنوان أو وصف الفكرة'],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

$aiApiUrl = env_value('AI_API_URL');

if (!$aiApiUrl) {
    http_response_code(500);

    echo json_encode(
        ['error' => 'لم يتم إعداد رابط خدمة الذكاء الاصطناعي'],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

$payload = json_encode(
    [
        'title' => $title,
        'description' => $description
    ],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$ch = curl_init($aiApiUrl);

if ($ch === false) {
    http_response_code(500);

    echo json_encode(
        ['error' => 'تعذر إنشاء اتصال بخدمة الذكاء الاصطناعي'],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Accept: application/json'
    ],
    CURLOPT_POSTFIELDS => $payload,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 120
]);

$response = curl_exec($ch);
$httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($response === false) {
    http_response_code(500);

    echo json_encode(
        [
            'error' => 'تعذر الاتصال بخدمة الذكاء الاصطناعي',
            'details' => $curlError
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

if ($httpCode < 200 || $httpCode >= 300) {
    http_response_code($httpCode);

    echo json_encode(
        [
            'error' => 'حدث خطأ داخل خدمة الذكاء الاصطناعي',
            'details' => $response
        ],
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

echo $response;