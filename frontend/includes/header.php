<?php
$user=current_user();
$unread=count(array_filter($visibleNotifications??[],fn($n)=>empty($n['read'])));
$searchIdeas=array_map(static fn(array $idea): array=>[
  'id'=>(string)($idea['id']??''),
  'number'=>(string)($idea['number']??''),
  'title'=>(string)($idea['title']??''),
  'description'=>(string)($idea['description']??''),
  'department'=>(string)($idea['department']??''),
  'category'=>(string)($idea['category']??''),
  'employee'=>(string)($idea['employee']??''),
  'url'=>url('details',['id'=>(string)($idea['id']??'')]),
],$visibleIdeas??[]);
?>
<header class="site-header"><div class="header-inner">
<button class="mobile-toggle" type="button" data-menu-toggle aria-label="فتح القائمة">☰</button>
<a class="brand-button" href="<?=url('dashboard')?>"><img src="assets/images/jeddah-logo.png" alt="شعار أمانة جدة" class="header-logo"></a>
<nav class="main-nav" data-main-nav><?php foreach(nav_items() as [$key,$label,$ico]): ?><a class="nav-link <?=$page===$key?'active':''?>" href="<?=url($key)?>"><?=icon($ico)?><span><?=e($label)?></span><?php if($key==='notifications'&&$unread>0):?><b class="badge"><?=$unread?></b><?php endif?></a><?php endforeach?></nav>
<div class="header-tools">
<button type="button" class="search-button" data-global-search-open><?=icon('search',22)?><span>بحث</span></button>
<a class="header-notification-button" href="<?=url('notifications')?>" title="الإشعارات" aria-label="الإشعارات"><?=icon('bell',22)?><?php if($unread>0):?><b class="badge"><?=$unread?></b><?php endif?></a>
<div class="profile-chip"><div class="profile-avatar"><?=icon('user',22)?></div><div><strong><?=e((string)($user['name']??''))?></strong><small><?=is_admin()?'مدير متابعة الأفكار':'موظف مقدم أفكار'?></small></div></div>
<a class="logout-icon" href="index.php?action=logout" title="تسجيل الخروج"><?=icon('logout',20)?></a>
</div></div></header>
<div class="global-search-modal hidden" data-global-search-modal aria-hidden="true">
  <div class="global-search-backdrop" data-global-search-close></div>
  <section class="global-search-box" role="dialog" aria-modal="true" aria-label="البحث في الأفكار">
    <div class="global-search-head"><div><h2>البحث في الأفكار</h2><p><?=is_admin()?'ابحث في جميع أفكار الموظفين':'ابحث داخل أفكارك فقط'?></p></div><button type="button" class="modal-close" data-global-search-close aria-label="إغلاق"><?=icon('close',22)?></button></div>
    <div class="global-search-input"><?=icon('search',21)?><input type="search" placeholder="اكتب عنوان الفكرة أو رقمها أو كلمة من الوصف..." data-global-search-input autocomplete="off"></div>
    <div class="global-search-results" data-global-search-results><div class="search-empty">ابدأ بكتابة كلمة للبحث.</div></div>
  </section>
</div>
<script type="application/json" id="global-search-data"><?=json_encode($searchIdeas,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?></script>
