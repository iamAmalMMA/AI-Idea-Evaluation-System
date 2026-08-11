<?php
$allAnalyticsIdeas=$visibleIdeas;
$statusIdeas=$allAnalyticsIdeas;
$activeAnalyticsIdeas=array_values(array_filter($allAnalyticsIdeas,static fn(array $idea):bool=>(string)($idea['status']??'')!=='rejected'));
$evaluatedIdeas=array_values(array_filter($activeAnalyticsIdeas,'eligible_for_performance'));

// ثبّت ترتيب الحالات والتصنيفات حتى تكون الإحصائيات موحدة عند الموظف والمدير.
// تظهر الخيارات الرسمية حتى لو كانت قيمتها صفر، بينما تُجمع كل القيم المخصصة تحت «أخرى» في النهاية.
$statusOrder=['بانتظار التحليل','قيد التحليل','تم التقييم','مرفوضة','مرشحة للتنفيذ','مسودة'];
$statusCounts=array_fill_keys($statusOrder,0);
foreach($statusIdeas as $idea){
  $label=status_text((string)($idea['status']??''));
  if(array_key_exists($label,$statusCounts)) $statusCounts[$label]++;
}
$statusGroups=$statusCounts; // إظهار جميع الحالات دائمًا، حتى عندما تكون القيمة صفرًا.

$departmentOrder=array_merge(DEPARTMENT_OPTIONS,['أخرى']);
$categoryOrder=array_merge(CATEGORY_OPTIONS,['أخرى']);
$departmentGroups=array_fill_keys($departmentOrder,0);
$categoryGroups=array_fill_keys($categoryOrder,0);
foreach($activeAnalyticsIdeas as $idea){
  $department=analytics_group_label($idea,'department');
  $category=analytics_group_label($idea,'category');
  $departmentGroups[$department]=($departmentGroups[$department]??0)+1;
  $categoryGroups[$category]=($categoryGroups[$category]??0)+1;
}

$criterionLabels=['innovation'=>'الابتكار','feasibility'=>'سهولة التنفيذ','sustainability'=>'الاستدامة','cost'=>'التكلفة','business_value'=>'القيمة التجارية'];
$criterionAverages=[];
foreach($criterionLabels as $key=>$label){
  $values=[];
  foreach($evaluatedIdeas as $idea){$value=$idea['evaluation'][$key]??null;if(is_numeric($value))$values[]=(float)$value;}
  $criterionAverages[$key]=$values?round(array_sum($values)/count($values),1):0;
}

// ألوان الحالة مطابقة حرفيًا لخلفيات الشارات المستخدمة في جدول الأفكار.
// لا يتم تفتيحها أو تغميقها أو إضافة شفافية عليها.
$statusColors=[
 'مسودة'=>'#E5E7EB',
 'بانتظار التحليل'=>'#FEDF89',
 'قيد التحليل'=>'#FEC84B',
 'تم التقييم'=>'#B2DDFF',
 'مرفوضة'=>'#FECDCA',
 'مرشحة للتنفيذ'=>'#ABEFC6'
];
$statusTextColors=[
 'مسودة'=>'#384250',
 'بانتظار التحليل'=>'#B54708',
 'قيد التحليل'=>'#93370D',
 'تم التقييم'=>'#175CD3',
 'مرفوضة'=>'#B42318',
 'مرشحة للتنفيذ'=>'#067647'
];
$statusAccentColors=$statusTextColors;
$departmentPalette=['#1B8354','#80519F','#DBA102','#1570EF','#166A45','#6D428F','#384250'];
$makeDonut=static function(array $groups,array $palette,bool $named=false,array $accents=[]):array{
  $total=max(1,array_sum($groups));$stops=[];$legend=[];$cursor=0;$index=0;
  foreach($groups as $name=>$count){
    $pct=$count/$total*100;
    $color=$named?($palette[$name]??'#384250'):$palette[$index%count($palette)];
    $accent=$named?($accents[$name]??'#384250'):$color;
    $end=$cursor+$pct;
    $separator=min(.35,$pct/8);
    $visibleEnd=max($cursor,$end-$separator);
    $stops[]=$color.' '.$cursor.'% '.$visibleEnd.'%';
    if($separator>0)$stops[]='#FFFFFF '.$visibleEnd.'% '.$end.'%';
    $legend[]=[$name,$count,$color,$accent,round($pct)];
    $cursor=$end;$index++;
  }
  return [$groups?'conic-gradient('.implode(',',$stops).')':'conic-gradient(#E5E7EB 0 100%)',$legend];
};
$primaryGroups=is_admin()?$departmentGroups:$statusGroups;
[$primaryChart,$primaryLegend]=$makeDonut($primaryGroups,is_admin()?$departmentPalette:$statusColors,!is_admin(),!is_admin()?$statusAccentColors:[]);
$maxCategory=max(array_values($categoryGroups)?:[1]);

$approvedCount=count(array_filter($allAnalyticsIdeas,fn($i)=>(string)($i['status']??'')==='approved'));
$rejectedCount=count(array_filter($allAnalyticsIdeas,fn($i)=>(string)($i['status']??'')==='rejected'));
$waitingDecision=count(array_filter($allAnalyticsIdeas,fn($i)=>(string)($i['status']??'')==='evaluated'));
$centerTotal=array_sum($primaryGroups);
?>
<div class="page-container inner-page analytics-page">
  <div class="page-title analytics-title-only"><div><h1><?=is_admin()?'الإحصائيات والتحليلات':'إحصائياتي'?></h1></div></div>

  <div class="analytics-insight-grid">
    <section class="data-card analytics-visual-card">
      <div class="card-heading"><div><?=icon('pie',22)?><h2><?=is_admin()?'الأفكار حسب الإدارة':'توزيع أفكاري حسب الحالة'?></h2></div></div>
      <?php if(is_admin()):?>
      <div class="circular-chart-wrap">
        <div class="donut-chart" style="--donut-bg:<?=$primaryChart?>"><div><strong><?=$centerTotal?></strong><span>فكرة نشطة</span></div></div>
        <div class="donut-legend">
          <?php foreach($primaryLegend as [$name,$count,$color,$accent,$pct]):?><div><i style="background:<?=$color?>;border-color:<?=$accent?>"></i><span><?=e((string)$name)?></span><strong><?=$count?> <small><?=$pct?>%</small></strong></div><?php endforeach?>
          <?php if(!$primaryLegend):?><p class="empty-cell">لا توجد بيانات بعد.</p><?php endif?>
        </div>
      </div>
      <?php else:?>
      <div class="status-progress-summary">
        <div class="status-progress-total"><strong><?=$centerTotal?></strong><span>فكرة مقدمة</span></div>
        <div class="status-progress-list">
          <?php $statusTotal=max(1,array_sum($statusGroups));foreach($statusGroups as $name=>$count):$pct=round($count/$statusTotal*100);$fill=$statusColors[$name]??'#F3F4F6';$text=$statusTextColors[$name]??'#384250';?>
          <div class="status-progress-item">
            <span class="status-progress-name"><?=e((string)$name)?></span>
            <div class="status-progress-track"><i style="width:<?=$pct?>%;background:<?=$fill?>"></i></div>
            <strong class="status-progress-value"><?=$count?> <small><?=$pct?>%</small></strong>
          </div>
          <?php endforeach?>
          <?php if(!$statusGroups):?><p class="empty-cell">لا توجد بيانات بعد.</p><?php endif?>
        </div>
      </div>
      <?php endif?>
    </section>

    <section class="data-card criteria-analytics-card">
      <div class="card-heading"><div><?=icon('trend',22)?><h2>متوسط معايير التقييم من 5</h2></div></div>
      <p class="analytics-card-note"><?=is_admin()?'متوسط نتائج جميع الأفكار المؤهلة في المنصة.':'متوسط نتائج أفكارك المؤهلة في كل معيار.'?></p>
      <div class="analytics-criteria-list">
        <?php foreach($criterionLabels as $key=>$label):$value=$criterionAverages[$key];?>
          <div><span><?=e($label)?></span><div><i style="width:<?=min(100,$value/5*100)?>%"></i></div><strong><?=number_format($value,1)?></strong></div>
        <?php endforeach?>
      </div>
    </section>
  </div>

  <div class="analytics-insight-grid analytics-secondary-grid">
    <section class="data-card analytics-list-card">
      <div class="card-heading"><div><?=icon('file',22)?><h2>الأفكار حسب التصنيف</h2></div></div>
      <p class="analytics-card-note"><?=is_admin()?'يوضح أكثر مجالات الابتكار نشاطًا في المنصة.':'يساعد في معرفة أكثر مجالات أفكارك نشاطًا.'?></p>
      <div class="analytics-bars category-bars">
        <?php foreach($categoryGroups as $name=>$count):$pct=array_sum($categoryGroups)?round($count/array_sum($categoryGroups)*100):0;?>
          <div><header><span><?=e((string)$name)?></span><strong><?=$count?> <small><?=$pct?>%</small></strong></header><div><i style="width:<?=($count/$maxCategory*100)?>%"></i></div></div>
        <?php endforeach?>
        <?php if(!$categoryGroups):?><p class="empty-cell">لا توجد بيانات بعد.</p><?php endif?>
      </div>
    </section>

    <?php if(is_admin()):?>
    <section class="data-card analytics-list-card">
      <div class="card-heading"><div><?=icon('award',22)?><h2>مخرجات القرار الإداري</h2></div></div>
      <div class="decision-summary-grid">
        <div class="success"><span>مرشحة للتنفيذ</span><strong><?=$approvedCount?></strong><small>أفكار مقيمة رشحها المدير للتنفيذ</small></div>
        <div class="danger"><span>مرفوضة</span><strong><?=$rejectedCount?></strong><small>تم إيقاف استكمالها</small></div>
        <div class="neutral"><span>بانتظار قرار</span><strong><?=$waitingDecision?></strong><small>تم تقييمها ولم يصدر قرار</small></div>
      </div>
    </section>
    <?php endif?>
  </div>
</div>
