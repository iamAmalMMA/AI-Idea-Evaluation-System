<?php
require __DIR__ . '/includes/bootstrap.php';
$id=(string)($_GET['id']??'');
$routeIdea=current(array_filter($ideas,fn($i)=>(string)($i['id']??'')===$id))?:null;
if($routeIdea&&!is_admin()){
    $user=current_user();
    $ownsById=(string)($routeIdea['employee_id']??'')!==''&&(string)($routeIdea['employee_id']??'')===(string)($user['id']??'');
    $ownsByName=(string)($routeIdea['employee']??'')===(string)($user['name']??'');
    if(!$ownsById&&!$ownsByName){flash('لا يمكنك عرض فكرة تخص مستخدمًا آخر');redirect(url('ideas'));}
}
$page = 'details';
$pageTitle = 'تفاصيل الفكرة | منصة الأفكار الذكية';
require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/pages/details.php';
require __DIR__ . '/includes/layout_bottom.php';
