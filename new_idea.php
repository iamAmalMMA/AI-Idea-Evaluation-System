<?php
require __DIR__ . '/includes/bootstrap.php';
if (is_admin()) { flash('إضافة الأفكار متاحة للموظفين فقط'); redirect(url('dashboard')); }
$page = 'new';
$pageTitle = 'إضافة فكرة | منصة الأفكار الذكية';
require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/pages/new.php';
require __DIR__ . '/includes/layout_bottom.php';
