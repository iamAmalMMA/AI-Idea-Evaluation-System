<?php
declare(strict_types=1);

const DEPARTMENT_OPTIONS = ['تقنية المعلومات','خدمة المستفيدين','التخطيط والتطوير','المشاريع','الموارد البشرية','الإدارة العامة'];
const CATEGORY_OPTIONS = ['التحول الرقمي','تحسين الخدمات البلدية','تجربة المستفيد','الاستدامة والبيئة','المدن الذكية','خفض التكاليف'];

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function text_lower(string $value): string { return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value); }
function text_length(string $value): int { return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value); }
function text_excerpt(string $value, int $limit=150): string { $value=trim($value); if(text_length($value)<=$limit) return $value; return (function_exists('mb_substr')?mb_substr($value,0,$limit,'UTF-8'):substr($value,0,$limit)).'…'; }
function redirect(string $url): never { header('Location: ' . $url); exit; }
function url(string $page='dashboard', array $params=[]): string {
    $routes=['dashboard'=>'dashboard.php','ideas'=>'ideas.php','new'=>'new_idea.php','top'=>'top_ideas.php','analytics'=>'analytics.php','notifications'=>'notifications.php','details'=>'idea_details.php'];
    $file=$routes[$page]??'dashboard.php';
    return $params ? $file.'?'.http_build_query($params) : $file;
}
function current_user(): ?array { return $_SESSION['user'] ?? null; }
function is_admin(): bool { return (current_user()['role'] ?? '') === 'admin'; }
function require_admin(): void { if (!is_admin()) { flash('هذه الصفحة متاحة لمدير متابعة الأفكار فقط'); redirect(url('dashboard')); } }
function user_ideas(array $ideas): array {
    // المسودات خاصة بصاحبها ولا تظهر لمدير النظام.
    if (is_admin()) {
        return array_values(array_filter($ideas, static fn(array $idea): bool => (string)($idea['status'] ?? '') !== 'draft'));
    }
    $user=current_user();
    $id=(string)($user['id']??'');
    $name=(string)($user['name']??'');
    return array_values(array_filter($ideas, static function(array $idea) use($id,$name): bool {
        $ownerId=(string)($idea['employee_id']??'');
        return $ownerId!=='' ? $ownerId===$id : (string)($idea['employee']??'')===$name;
    }));
}
function eligible_for_performance(array $idea): bool {
    return is_numeric($idea['score']??null)
        && in_array((string)($idea['status']??''), ['evaluated','approved'], true);
}
function top_five_ideas(array $ideas): array {
    $ranked=array_values(array_filter($ideas,'eligible_for_performance'));
    usort($ranked, fn($a,$b)=>($b['score']<=>$a['score']) ?: strcmp($b['date']??'', $a['date']??''));
    return array_slice($ranked,0,5);
}
function analytics_group_label(array $idea, string $field): string {
    $value=trim((string)($idea[$field]??''));
    if ($value==='') return 'أخرى';
    $flag=(bool)($idea[$field.'_is_other']??false);
    $allowed=$field==='department'?DEPARTMENT_OPTIONS:CATEGORY_OPTIONS;
    return ($flag || !in_array($value,$allowed,true)) ? 'أخرى' : $value;
}
function visible_notifications(array $notifications): array {
    $user=current_user();
    if(!$user) return [];
    $id=(string)($user['id']??'');
    $role=(string)($user['role']??'');
    return array_values(array_filter($notifications,static function(array $notification) use($id,$role):bool {
        $recipient=(string)($notification['recipient_id']??'');
        $recipientRole=(string)($notification['recipient_role']??'');
        if($recipient!=='' && $recipient!==$id) return false;
        if($recipientRole!=='' && $recipientRole!==$role) return false;
        return true;
    }));
}
function status_text(string $status): string {
    return ['draft'=>'مسودة','submitted'=>'بانتظار التحليل','processing'=>'قيد التحليل','evaluated'=>'تم التقييم','approved'=>'مرشحة للتنفيذ','rejected'=>'مرفوضة'][$status]??$status;
}
function ar_date(string $date): string {
    $months=[1=>'يناير','فبراير','مارس','أبريل','مايو','يونيو','يوليو','أغسطس','سبتمبر','أكتوبر','نوفمبر','ديسمبر'];
    $ts=strtotime($date); if(!$ts) return $date;
    return date('j',$ts).' '.$months[(int)date('n',$ts)].' '.date('Y',$ts);
}
function evaluate_idea_with_ai(array $idea): array {
    $apiUrl = 'http://localhost/AiProject/AI-Idea-Evaluation-System/backend/api/evaluate.php';

    if (!$apiUrl) {
        throw new RuntimeException('لم يتم إعداد رابط خدمة الذكاء الاصطناعي.');
    }

    $payload = [
        'title' => (string)($idea['title'] ?? ''),
        'description' => (string)($idea['description'] ?? ''),
    ];

    $ch = curl_init($apiUrl);

    if ($ch === false) {
        throw new RuntimeException('تعذر إنشاء اتصال بخدمة الذكاء الاصطناعي.');
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        ),
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 120,
    ]);

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        throw new RuntimeException(
            'تعذر الاتصال بخدمة الذكاء الاصطناعي: ' . $curlError
        );
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        throw new RuntimeException(
            'خدمة الذكاء الاصطناعي أعادت خطأ HTTP ' . $httpCode . ': ' . $response
        );
    }

    $data = json_decode($response, true);

    if (!is_array($data)) {
        throw new RuntimeException(
            'استجابة الذكاء الاصطناعي ليست JSON صالحًا.'
        );
    }

    /*
     * The AI API returns:
     *
     * evaluation.scores.innovation.score
     * evaluation.scores.feasibility.score
     * evaluation.scores.business_value.score
     * evaluation.scores.sustainability.score
     * evaluation.scores.cost.score
     * evaluation.overall_score
     * evaluation.strengths
     * evaluation.improvement_opportunities
     * evaluation.improved_proposal
     *
     * The existing PHP application expects a flatter structure,
     * so we convert the AI response here.
     */

    $evaluation = $data['evaluation'] ?? [];
    $scores = $evaluation['scores'] ?? [];
    $improvedProposal = $evaluation['improved_proposal'] ?? [];

    return [
        'score' => (float)($evaluation['overall_score'] ?? 0),

        'evaluation' => [
            'innovation' => (float)($scores['innovation']['score'] ?? 0),
            'feasibility' => (float)($scores['feasibility']['score'] ?? 0),
            'sustainability' => (float)($scores['sustainability']['score'] ?? 0),
            'cost' => (float)($scores['cost']['score'] ?? 0),
            'business_value' => (float)($scores['business_value']['score'] ?? 0),

            'strengths' => $evaluation['strengths'] ?? [],

            'improvements' =>
                $evaluation['improvement_opportunities'] ?? [],

            'improvedTitle' =>
                (string)($improvedProposal['suggested_title'] ?? $payload['title']),

            'improvedDescription' =>
                (string)($improvedProposal['suggested_description'] ?? $payload['description']),
        ],
    ];
}
function demo_ai_evaluation(array $idea): array {
    // تقييم تجريبي جاهز للاستبدال لاحقًا بواجهة API حقيقية للذكاء الاصطناعي.
    $text=trim(implode(' ',[(string)($idea['title']??''),(string)($idea['description']??'')]));
    $base=3.4 + min(1.0, text_length($text)/900);
    $jitter=(hexdec(substr(md5($text),0,2))%41)/100-0.2;
    $criteria=[
        'innovation'=>max(0,min(5,round($base+$jitter,1))),
        'feasibility'=>max(0,min(5,round($base-0.1,1))),
        'sustainability'=>max(0,min(5,round($base,1))),
        'cost'=>max(0,min(5,round($base-0.3,1))),
        'business_value'=>max(0,min(5,round($base+0.2,1))),
    ];
    $score=round(array_sum($criteria)/count($criteria),1);
    return [
        'score'=>$score,
        'evaluation'=>$criteria+[
            'strengths'=>['تعالج احتياجًا واضحًا ومحددًا','تتضمن حلًا يمكن قياس أثره ومتابعته'],
            'improvements'=>['إضافة مؤشرات نجاح قابلة للقياس','توضيح الموارد وخطة التنفيذ المرحلية'],
                        'improvedTitle'=>'نسخة مطورة: '.($idea['title']??''),
            'improvedDescription'=>'صياغة محسنة للفكرة: '.($idea['description']??'').' مع تنفيذ مرحلي يبدأ بنطاق تجريبي، وتحديد المسؤوليات والموارد ومؤشرات قياس الأثر.',
        ]
    ];
}
function icon(string $name, int $size=20): string {
    $p=[
      'home'=>'<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10.5V20h14v-9.5"/><path d="M9 20v-6h6v6"/>','plus'=>'<path d="M12 5v14M5 12h14"/>','file'=>'<path d="M6 2h9l3 3v17H6z"/><path d="M14 2v5h5M9 12h6M9 16h6"/>','star'=>'<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1.1 6.2-5.6-3-5.6 3 1.1-6.2L3 9.6l6.2-.9z"/>','chart'=>'<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>','bell'=>'<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>','search'=>'<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>','user'=>'<circle cx="12" cy="8" r="4"/><path d="M4 21c1-5 4-7 8-7s7 2 8 7"/>','logout'=>'<path d="M10 17l5-5-5-5M15 12H3M15 3h5v18h-5"/>','lightbulb'=>'<path d="M9 18h6M10 22h4M8 14a7 7 0 1 1 8 0c-1 1-1 2-1 4H9c0-2 0-3-1-4"/>','clock'=>'<circle cx="12" cy="12" r="9"/><path d="M12 7v6l4 2"/>','check'=>'<path d="m4 12 5 5L20 6"/>','award'=>'<circle cx="12" cy="8" r="5"/><path d="m9 13-2 9 5-3 5 3-2-9"/>','share'=>'<circle cx="18" cy="5" r="2"/><circle cx="6" cy="12" r="2"/><circle cx="18" cy="19" r="2"/><path d="m8 11 8-5M8 13l8 5"/>','arrow-left'=>'<path d="m15 18-6-6 6-6"/>','arrow-right'=>'<path d="m9 18 6-6-6-6"/>','save'=>'<path d="M5 3h12l2 2v16H5z"/><path d="M8 3v6h8V3M8 21v-7h8v7"/>','send'=>'<path d="m3 3 18 9-18 9 4-9zM7 12h14"/>','filter'=>'<path d="M4 6h16M7 12h10M10 18h4"/>','more'=>'<circle cx="12" cy="5" r="1"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/>','wrench'=>'<path d="M14 6a4 4 0 0 0-5 5L3 17l4 4 6-6a4 4 0 0 0 5-5l-3 3-4-4z"/>','pie'=>'<path d="M12 2v10h10A10 10 0 1 1 12 2"/><path d="M15 2a7 7 0 0 1 7 7h-7z"/>','trend'=>'<path d="m3 17 6-6 4 4 8-8M15 7h6v6"/>','mail'=>'<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>','lock'=>'<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>','close'=>'<path d="M6 6l12 12M18 6 6 18"/>'
    ];
    $body=$p[$name]??$p['file'];
    return '<svg width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$body.'</svg>';
}
function flash(string $message): void { $_SESSION['toast']=$message; }
function pull_flash(): string { $m=$_SESSION['toast']??''; unset($_SESSION['toast']); return $m; }
function nav_items(): array {
    if (is_admin()) return [['dashboard','الرئيسية','home'],['ideas','جميع الأفكار','file'],['top','أفضل 5 أفكار','star'],['analytics','الإحصائيات','chart']];
    return [['dashboard','الرئيسية','home'],['new','إضافة فكرة','plus'],['ideas','أفكاري','file'],['analytics','إحصائياتي','chart']];
}
