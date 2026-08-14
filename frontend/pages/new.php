<?php
if(is_admin()) redirect(url('dashboard'));
$editingId=trim((string)($_GET['id']??''));
$editIdea=null;
if($editingId!==''){
  foreach($visibleIdeas as $candidate){
    if((string)($candidate['id']??'')===$editingId && (string)($candidate['status']??'')==='draft'){$editIdea=$candidate;break;}
  }
  if(!$editIdea){flash('الفكرة المطلوبة غير موجودة أو لا يمكنك تعديلها');redirect(url('ideas'));}
}
$formTitle=(string)($editIdea['title']??'');if($formTitle==='مسودة بدون عنوان')$formTitle='';
$formDepartment=(string)($editIdea['department']??'');$departmentIsOther=(bool)($editIdea['department_is_other']??false)||($formDepartment!==''&&!in_array($formDepartment,DEPARTMENT_OPTIONS,true));
$formCategory=(string)($editIdea['category']??'');$categoryIsOther=(bool)($editIdea['category_is_other']??false)||($formCategory!==''&&!in_array($formCategory,CATEGORY_OPTIONS,true));
$formDescription=(string)($editIdea['description']??'');
?>
<div class="page-container inner-page">
  <div class="page-title"><div><h1><?=$editIdea?'إكمال المسودة':'إضافة فكرة جديدة'?></h1><p><?=$editIdea?'عدّلي البيانات الناقصة ثم احفظي المسودة أو أرسليها للتحليل الذكي.':'أدخلي المعلومات الأساسية، وسيحلل الذكاء الاصطناعي الفكرة ويقيّمها وفق معايير موحدة.'?></p></div></div>
  <section class="form-card idea-form" data-idea-form data-current-idea-id="<?=e($editingId)?>">
    <form novalidate data-api-form>
      <input type="hidden" name="form_action" value="create_idea"><input type="hidden" name="idea_id" value="<?=e($editingId)?>">
      <div class="steps"><div class="active"><b>1</b><span>بيانات الفكرة</span></div><div><b>2</b><span>تفاصيل الفكرة</span></div><div><b>3</b><span>المراجعة والإرسال</span></div></div>
      <div class="form-step" data-step="1"><div class="form-grid">
        <label class="full">عنوان الفكرة *<input name="title" maxlength="120" minlength="5" required value="<?=e($formTitle)?>" placeholder="اكتبي عنوانًا واضحًا ومختصرًا للفكرة"></label>
        <label><span class="field-label-row"><span>الإدارة المختصة *</span><span class="field-info" tabindex="0">i<span class="field-tooltip">الجهة المرتبطة بتنفيذ الفكرة أو الاستفادة منها.</span></span></span>
          <select name="department" required data-other-select="department"><option value="">اختاري الإدارة المختصة</option><?php foreach(DEPARTMENT_OPTIONS as $option):?><option value="<?=e($option)?>" <?=!$departmentIsOther&&$formDepartment===$option?'selected':''?>><?=e($option)?></option><?php endforeach?><option value="__other__" <?=$departmentIsOther?'selected':''?>>أخرى</option></select>
          <input class="other-field <?=$departmentIsOther?'':'hidden'?>" name="department_other" data-other-input="department" value="<?=e($departmentIsOther?$formDepartment:'')?>" placeholder="اكتبي اسم الإدارة">
          <small class="field-choice-help">الجهة المرتبطة بتنفيذ الفكرة أو الاستفادة منها.</small></label>
        <label><span class="field-label-row"><span>تصنيف الفكرة *</span><span class="field-info" tabindex="0">i<span class="field-tooltip">المجال الذي تنتمي إليه الفكرة.</span></span></span>
          <select name="category" required data-other-select="category"><option value="">اختاري تصنيف الفكرة</option><?php foreach(CATEGORY_OPTIONS as $option):?><option value="<?=e($option)?>" <?=!$categoryIsOther&&$formCategory===$option?'selected':''?>><?=e($option)?></option><?php endforeach?><option value="__other__" <?=$categoryIsOther?'selected':''?>>أخرى</option></select>
          <input class="other-field <?=$categoryIsOther?'':'hidden'?>" name="category_other" data-other-input="category" value="<?=e($categoryIsOther?$formCategory:'')?>" placeholder="اكتبي تصنيف الفكرة">
          <small class="field-choice-help">المجال الذي تنتمي إليه الفكرة.</small></label>
      </div></div>
      <div class="form-step hidden" data-step="2"><div class="form-grid"><label class="full">وصف الفكرة *<textarea name="description" minlength="30" maxlength="2000" required placeholder="اشرحي المشكلة أو الفرصة، الحل المقترح، والأثر المتوقع."><?=e($formDescription)?></textarea><small class="field-hint">يجب ألا يقل الوصف عن 30 حرفًا عند الإرسال للتحليل.</small></label><div class="idea-writing-guide full"><h3>ما الذي يُفضل توضيحه في الوصف؟</h3><div class="guide-items"><div><strong>المشكلة أو الفرصة</strong><p>ما الوضع الحالي الذي تحتاج الفكرة إلى تحسينه؟</p></div><div><strong>الحل المقترح</strong><p>كيف ستعمل الفكرة لمعالجة المشكلة؟</p></div><div><strong>الأثر المتوقع</strong><p>ما الفائدة المتوقعة من تطبيق الفكرة؟</p></div></div></div></div></div>
      <div class="form-step hidden" data-step="3"><div class="review-box" data-review><div class="review-check"><?=icon('check',30)?></div><h2>مراجعة بيانات الفكرة</h2><dl><div><dt>عنوان الفكرة</dt><dd data-review-title>—</dd></div><div><dt>الإدارة والتصنيف</dt><dd data-review-meta>—</dd></div><div><dt>وصف الفكرة</dt><dd data-review-description>—</dd></div></dl><p class="ai-note">بعد الإرسال، يقيّم الذكاء الاصطناعي المعايير الخمسة من 5، ويحسب التقييم النهائي، ويستخرج نقاط القوة وفرص التحسين، ويقترح عنوانًا ووصفًا محسنين.</p></div></div>
      <div class="form-actions"><button class="secondary-button hidden" type="button" data-prev><?=icon('arrow-right',18)?>السابق</button><span></span><button class="ghost-button" type="submit" name="submit_type" value="draft" formnovalidate data-draft><?=icon('save',18)?><?=$editIdea?'تحديث المسودة':'حفظ كمسودة'?></button><button class="primary-button" type="button" data-next>التالي<?=icon('arrow-left',18)?></button><button class="primary-button hidden" type="submit" name="submit_type" value="processing" data-send><?=icon('send',18)?>إرسال للتحليل الذكي</button></div>
    </form>
  </section>
</div>
