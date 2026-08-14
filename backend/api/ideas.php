<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);

    echo json_encode(
        $data,
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| OPTIONS
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

/*
|--------------------------------------------------------------------------
| GET — Return all ideas
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    try {

        $sql = "SELECT
                    i.*,
                    u.name AS employee,
                    e.innovation,
                    e.feasibility,
                    e.sustainability,
                    e.cost,
                    e.business_value,
                    e.final_score AS evaluation_final_score,
                    e.strengths,
                    e.improvements,
                    e.feedback,
                    e.improved_title,
                    e.improved_description
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
                    'innovation' => (float) $row['innovation'],
                    'feasibility' => (float) $row['feasibility'],
                    'sustainability' => (float) $row['sustainability'],
                    'cost' => (float) $row['cost'],
                    'business_value' => (float) $row['business_value'],

                    'strengths' => json_decode(
                        (string) $row['strengths'],
                        true
                    ) ?: [],

                    'improvements' => json_decode(
                        (string) $row['improvements'],
                        true
                    ) ?: [],

                    'feedback' => (string) (
                        $row['feedback'] ?? ''
                    ),

                    'improvedTitle' => (string) (
                        $row['improved_title'] ?? ''
                    ),

                    'improvedDescription' => (string) (
                        $row['improved_description'] ?? ''
                    ),
                ];
            }

            $ideas[] = [
                'id' => (string) $row['id'],
                'number' => (string) $row['idea_number'],
                'employee' => (string) $row['employee'],
                'employee_id' => (string) $row['user_id'],

                'title' => (string) $row['title'],
                'description' => (string) $row['description'],

                'department' => (string) (
                    $row['department'] ?? ''
                ),

                'department_is_other' => (bool) (
                    $row['department_is_other']
                ),

                'category' => (string) (
                    $row['category'] ?? ''
                ),

                'category_is_other' => (bool) (
                    $row['category_is_other']
                ),

                'status' => (string) $row['status'],

                'score' => $row['score'] === null
                    ? null
                    : (float) $row['score'],

                'evaluation' => $evaluation,

                'decision_by' => $row['decision_by'] === null
                    ? null
                    : (string) $row['decision_by'],

                'decision_at' => $row['decision_at'],

                'date' => substr(
                    (string) $row['created_at'],
                    0,
                    10
                ),

                'created_at' => (string) $row['created_at'],
                'updated_at' => (string) $row['updated_at'],
            ];
        }

        json_response($ideas);

    } catch (Throwable $e) {

        error_log(
            'Ideas GET API error: ' .
            $e->getMessage()
        );

        json_response([
            'error' => 'تعذر جلب الأفكار'
        ], 500);
    }
}

/*
|--------------------------------------------------------------------------
| POST — Create / edit / evaluate idea
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */

    $currentUser = $_SESSION['user'] ?? null;

    if (
        !is_array($currentUser) ||
        empty($currentUser['id'])
    ) {
        json_response([
            'error' => 'يجب تسجيل الدخول أولاً'
        ], 401);
    }

    $currentUserId = (int) $currentUser['id'];

    /*
    |--------------------------------------------------------------------------
    | Parse request body
    |--------------------------------------------------------------------------
    */

    $input = json_decode(
        file_get_contents('php://input'),
        true
    );

    if (!is_array($input)) {
        json_response([
            'error' => 'بيانات الطلب غير صالحة'
        ], 400);
    }

    /*
    |--------------------------------------------------------------------------
    | Read fields
    |--------------------------------------------------------------------------
    */

    $ideaIdInput = trim(
        (string) ($input['idea_id'] ?? '')
    );

    $title = trim(
        (string) ($input['title'] ?? '')
    );

    $description = trim(
        (string) ($input['description'] ?? '')
    );

    $departmentChoice = trim(
        (string) ($input['department'] ?? '')
    );

    $categoryChoice = trim(
        (string) ($input['category'] ?? '')
    );

    $departmentOther = trim(
        (string) ($input['department_other'] ?? '')
    );

    $categoryOther = trim(
        (string) ($input['category_other'] ?? '')
    );

    $submitType = trim(
        (string) ($input['submit_type'] ?? 'processing')
    );

    $isDraft = $submitType === 'draft';

    /*
    |--------------------------------------------------------------------------
    | Resolve "Other"
    |--------------------------------------------------------------------------
    */

    $department = $departmentChoice === '__other__'
        ? $departmentOther
        : $departmentChoice;

    $category = $categoryChoice === '__other__'
        ? $categoryOther
        : $categoryChoice;

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($isDraft) {

        if ($title === '') {
            $title = 'مسودة بدون عنوان';
        }

    } else {

        if ($title === '') {
            json_response([
                'error' => 'يرجى كتابة عنوان الفكرة'
            ], 400);
        }

        if (mb_strlen($title) < 5) {
            json_response([
                'error' => 'عنوان الفكرة قصير جداً'
            ], 400);
        }

        if ($department === '') {
            json_response([
                'error' => 'يرجى اختيار الإدارة أو كتابة إدارة أخرى'
            ], 400);
        }

        if ($category === '') {
            json_response([
                'error' => 'يرجى اختيار التصنيف أو كتابة تصنيف آخر'
            ], 400);
        }

        if ($description === '') {
            json_response([
                'error' => 'يرجى كتابة وصف واضح للفكرة'
            ], 400);
        }

        if (mb_strlen($description) < 30) {
            json_response([
                'error' => 'يرجى كتابة وصف أوضح للفكرة لا يقل عن 30 حرفاً'
            ], 400);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Database
    |--------------------------------------------------------------------------
    */

    try {

        $pdo = db();

        $editingIdea = null;
        $ideaId = 0;
        $ideaNumber = '';

        /*
        |--------------------------------------------------------------------------
        | Check whether this is an existing draft
        |--------------------------------------------------------------------------
        */

        if ($ideaIdInput !== '') {

            if (!ctype_digit($ideaIdInput) || (int)$ideaIdInput <= 0) {
                json_response([
                    'error' => 'معرّف الفكرة غير صالح'
                ], 400);
            }

            $requestedIdeaId = (int) $ideaIdInput;

            $stmt = $pdo->prepare(
                'SELECT id, idea_number, user_id, status
                 FROM ideas
                 WHERE id = ?
                 LIMIT 1'
            );

            $stmt->execute([
                $requestedIdeaId
            ]);

            $editingIdea = $stmt->fetch();

            if (!$editingIdea) {
                json_response([
                    'error' => 'الفكرة المطلوبة غير موجودة'
                ], 404);
            }

            /*
            |--------------------------------------------------------------------------
            | Security: user must own the draft
            |--------------------------------------------------------------------------
            */

            if ((int)$editingIdea['user_id'] !== $currentUserId) {
                json_response([
                    'error' => 'لا يمكنك تعديل هذه الفكرة'
                ], 403);
            }

            /*
            |--------------------------------------------------------------------------
            | Security: only drafts can be edited
            |--------------------------------------------------------------------------
            */

            if ((string)$editingIdea['status'] !== 'draft') {
                json_response([
                    'error' => 'لا يمكن تعديل هذه الفكرة بعد إرسالها'
                ], 409);
            }

            $ideaId = (int)$editingIdea['id'];
            $ideaNumber = (string)$editingIdea['idea_number'];
        }

        /*
        |--------------------------------------------------------------------------
        | Start transaction
        |--------------------------------------------------------------------------
        */

        $pdo->beginTransaction();

        /*
        |--------------------------------------------------------------------------
        | Update existing draft
        |--------------------------------------------------------------------------
        */

        if ($editingIdea !== null) {

            $storedStatus = $isDraft
                ? 'draft'
                : 'processing';

            $stmt = $pdo->prepare(
                'UPDATE ideas
                 SET title = ?,
                     description = ?,
                     department = ?,
                     department_is_other = ?,
                     category = ?,
                     category_is_other = ?,
                     status = ?,
                     score = NULL,
                     decision_by = NULL,
                     decision_at = NULL
                 WHERE id = ?
                   AND user_id = ?'
            );

            $stmt->execute([
                $title,
                $description,
                $department !== ''
                    ? $department
                    : null,
                $departmentChoice === '__other__'
                    ? 1
                    : 0,
                $category !== ''
                    ? $category
                    : null,
                $categoryChoice === '__other__'
                    ? 1
                    : 0,
                $storedStatus,
                $ideaId,
                $currentUserId,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Remove old evaluation
            |--------------------------------------------------------------------------
            */

            $pdo
                ->prepare(
                    'DELETE FROM evaluations WHERE idea_id = ?'
                )
                ->execute([
                    $ideaId
                ]);

        } else {

            /*
            |--------------------------------------------------------------------------
            | Create new idea
            |--------------------------------------------------------------------------
            */

            $nextId = (int) $pdo
                ->query(
                    'SELECT COALESCE(MAX(id), 0) + 1 FROM ideas'
                )
                ->fetchColumn();

            $ideaNumber =
                'IDEA-' .
                date('Y') .
                '-' .
                str_pad(
                    (string) $nextId,
                    4,
                    '0',
                    STR_PAD_LEFT
                );

            $status = $isDraft
                ? 'draft'
                : 'processing';

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
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $ideaNumber,
                $currentUserId,
                $title,
                $description,
                $department !== ''
                    ? $department
                    : null,
                $departmentChoice === '__other__'
                    ? 1
                    : 0,
                $category !== ''
                    ? $category
                    : null,
                $categoryChoice === '__other__'
                    ? 1
                    : 0,
                $status,
            ]);

            $ideaId = (int) $pdo->lastInsertId();
        }

        /*
        |--------------------------------------------------------------------------
        | Draft: save and stop
        |--------------------------------------------------------------------------
        */

        if ($isDraft) {

            $pdo->commit();

            json_response([
                'success' => true,
                'message' => 'تم حفظ الفكرة كمسودة بنجاح',
                'idea_id' => (string) $ideaId,
                'idea_number' => $ideaNumber,
                'status' => 'draft',
            ], 200);
        }

        /*
        |--------------------------------------------------------------------------
        | AI Evaluation
        |--------------------------------------------------------------------------
        */

        $evaluateUrl =
            'http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api/evaluate.php';

        $aiPayload = json_encode(
            [
                'title' => $title,
                'description' => $description,
            ],
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES
        );

        $ch = curl_init($evaluateUrl);

        if ($ch === false) {
            throw new RuntimeException(
                'تعذر إنشاء اتصال بخدمة التقييم'
            );
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,

            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],

            CURLOPT_POSTFIELDS => $aiPayload,

            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 130,
        ]);

        $aiResponse = curl_exec($ch);

        $aiHttpCode = (int) curl_getinfo(
            $ch,
            CURLINFO_HTTP_CODE
        );

        $aiCurlError = curl_error($ch);

        curl_close($ch);

        if ($aiResponse === false) {

            throw new RuntimeException(
                'تعذر الاتصال بخدمة الذكاء الاصطناعي: ' .
                $aiCurlError
            );
        }

        if (
            $aiHttpCode < 200 ||
            $aiHttpCode >= 300
        ) {

            throw new RuntimeException(
                'خدمة الذكاء الاصطناعي أعادت HTTP ' .
                $aiHttpCode .
                ': ' .
                $aiResponse
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Decode AI response
        |--------------------------------------------------------------------------
        */

        $aiData = json_decode(
            $aiResponse,
            true
        );

        if (!is_array($aiData)) {

            throw new RuntimeException(
                'استجابة الذكاء الاصطناعي ليست JSON صالحاً'
            );
        }

        $evaluation =
            $aiData['evaluation'] ?? [];

        $scores =
            $evaluation['scores'] ?? [];

        $improvedProposal =
            $evaluation['improved_proposal'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | Extract evaluation
        |--------------------------------------------------------------------------
        */

        $innovation = (float) (
            $scores['innovation']['score'] ?? 0
        );

        $feasibility = (float) (
            $scores['feasibility']['score'] ?? 0
        );

        $businessValue = (float) (
            $scores['business_value']['score'] ?? 0
        );

        $sustainability = (float) (
            $scores['sustainability']['score'] ?? 0
        );

        $cost = (float) (
            $scores['cost']['score'] ?? 0
        );

        $overallScore = (float) (
            $evaluation['overall_score'] ?? 0
        );

        $strengths =
            $evaluation['strengths'] ?? [];

        $improvements =
            $evaluation['improvement_opportunities'] ?? [];

        $improvedTitle =
            (string) (
                $improvedProposal['suggested_title']
                ?? $title
            );

        $improvedDescription =
            (string) (
                $improvedProposal['suggested_description']
                ?? $description
            );

        /*
        |--------------------------------------------------------------------------
        | Validate score range
        |--------------------------------------------------------------------------
        */

        $overallScore = round(
            max(
                0,
                min(5, $overallScore)
            ),
            1
        );

        /*
        |--------------------------------------------------------------------------
        | Save evaluation
        |--------------------------------------------------------------------------
        */

        $evaluationStmt = $pdo->prepare(
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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $evaluationStmt->execute([
            $ideaId,

            round(
                max(0, min(5, $innovation)),
                1
            ),

            round(
                max(0, min(5, $feasibility)),
                1
            ),

            round(
                max(0, min(5, $sustainability)),
                1
            ),

            round(
                max(0, min(5, $cost)),
                1
            ),

            round(
                max(0, min(5, $businessValue)),
                1
            ),

            $overallScore,

            json_encode(
                $strengths,
                JSON_UNESCAPED_UNICODE
            ),

            json_encode(
                $improvements,
                JSON_UNESCAPED_UNICODE
            ),

            '',

            $improvedTitle,

            $improvedDescription,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update idea
        |--------------------------------------------------------------------------
        */

        $updateStmt = $pdo->prepare(
            'UPDATE ideas
             SET status = ?,
                 score = ?
             WHERE id = ?'
        );

        $updateStmt->execute([
            'evaluated',
            $overallScore,
            $ideaId,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Admin notification
        |--------------------------------------------------------------------------
        */

        $notificationStmt = $pdo->prepare(
            'INSERT INTO notifications
            (
                user_id,
                recipient_role,
                idea_id,
                title,
                message
            )
            VALUES (?, ?, ?, ?, ?)'
        );

        $notificationStmt->execute([
            null,
            'admin',
            $ideaId,
            'فكرة جديدة تم تقييمها',
            'تم إرسال فكرة جديدة وإكمال تقييمها الذكي وهي جاهزة للمراجعة.',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Commit
        |--------------------------------------------------------------------------
        */

        $pdo->commit();

        /*
        |--------------------------------------------------------------------------
        | Return result
        |--------------------------------------------------------------------------
        */

        json_response([
            'success' => true,

            'message' =>
                'تم إرسال الفكرة وإكمال التقييم الذكي',

            'idea_id' =>
                (string) $ideaId,

            'idea_number' =>
                $ideaNumber,

            'status' =>
                'evaluated',

            'score' =>
                $overallScore,

            'evaluation' => [
                'innovation' => $innovation,
                'feasibility' => $feasibility,
                'sustainability' => $sustainability,
                'cost' => $cost,
                'business_value' => $businessValue,

                'strengths' =>
                    $strengths,

                'improvements' =>
                    $improvements,

                'improvedTitle' =>
                    $improvedTitle,

                'improvedDescription' =>
                    $improvedDescription,
            ],
        ], 200);

    } catch (Throwable $e) {

        if (
            isset($pdo) &&
            $pdo instanceof PDO &&
            $pdo->inTransaction()
        ) {
            $pdo->rollBack();
        }

        error_log(
            'Ideas POST API error: ' .
            $e->getMessage()
        );

        /*
        |--------------------------------------------------------------------------
        | If AI failed, leave the idea as processing
        |--------------------------------------------------------------------------
        */

        if (
            isset($ideaId) &&
            $ideaId > 0
        ) {

            try {

                $pdo
                    ->prepare(
                        'UPDATE ideas
                         SET status = ?
                         WHERE id = ?'
                    )
                    ->execute([
                        'processing',
                        $ideaId,
                    ]);

            } catch (Throwable $ignored) {
                // Keep original error.
            }
        }

        json_response([
            'error' =>
                'تعذر إكمال تقييم الفكرة',

            'idea_id' =>
                isset($ideaId)
                    ? (string) $ideaId
                    : null,
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