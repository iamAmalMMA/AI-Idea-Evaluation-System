<?php
if (!current_user()) { redirect('login.php'); }
$toast = pull_flash();
?><!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e($pageTitle ?? 'منصة الأفكار الذكية') ?></title>
  <link rel="stylesheet" href="assets/css/global.css">
  <link rel="stylesheet" href="assets/css/php-extra.css">
</head>
<body>
<div class="app-shell">
<?php require __DIR__ . '/header.php'; ?>
<main>
