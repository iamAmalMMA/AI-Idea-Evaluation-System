<?php
declare(strict_types=1);

if (ob_get_level() === 0) ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__.'/db.php';
require_once __DIR__.'/functions.php';

$ideas = db_fetch_ideas();
$notifications = db_fetch_notifications();

if (($_GET['action']??'')==='logout') { session_destroy(); redirect('login.php'); }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $formAction=trim((string)($_POST['form_action']??''));

    if ($formAction==='login') {
        $email=trim((string)($_POST['email']??''));
        $password=(string)($_POST['password']??'');
        $stmt=db()->prepare('SELECT id, name, email, password_hash, role, department FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user=$stmt->fetch();
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            flash('البريد الإلكتروني أو كلمة المرور غير صحيحة');
            redirect('login.php');
        }
        $_SESSION['user']=[
            'id'=>(string)$user['id'],
            'name'=>(string)$user['name'],
            'email'=>(string)$user['email'],
            'role'=>(string)$user['role'],
            'department'=>(string)($user['department']??''),
        ];
        redirect(url('dashboard'));
    }

    if (!current_user()) redirect('login.php');

    if ($formAction==='create_idea') {
        if (is_admin()) { flash('إضافة الأفكار متاحة للموظفين فقط'); redirect(url('dashboard')); }

        $isDraft=($_POST['submit_type']??'processing')==='draft';
        $editingId=trim((string)($_POST['idea_id']??''));
        $title=trim((string)($_POST['title']??''));
        $departmentChoice=trim((string)($_POST['department']??''));
        $categoryChoice=trim((string)($_POST['category']??''));
        $departmentOther=trim((string)($_POST['department_other']??''));
        $categoryOther=trim((string)($_POST['category_other']??''));
        $department=$departmentChoice==='__other__'?$departmentOther:$departmentChoice;
        $category=$categoryChoice==='__other__'?$categoryOther:$categoryChoice;
        $description=trim((string)($_POST['description']??''));
        $currentUser=current_user()??[];
        $currentUserId=(int)($currentUser['id']??0);

        $editingIdea=null;
        if ($editingId!=='') {
            foreach ($ideas as $candidate) {
                if ((string)($candidate['id']??'')!==$editingId) continue;
                $ownsIdea=(string)($candidate['employee_id']??'')===(string)$currentUserId;
                $editableStatus=(string)($candidate['status']??'')==='draft';
                if (!$ownsIdea || !$editableStatus) { flash('لا يمكن تعديل هذه الفكرة'); redirect(url('ideas')); }
                $editingIdea=$candidate;
                break;
            }
            if ($editingIdea===null) { flash('الفكرة المطلوبة غير موجودة'); redirect(url('ideas')); }
        }

        if (!$isDraft) {
            if ($title==='') { flash('يرجى كتابة عنوان الفكرة'); redirect(url('new',$editingId!==''?['id'=>$editingId]:[])); }
            if (text_length($title)<5) { flash('عنوان الفكرة قصير جدًا'); redirect(url('new',$editingId!==''?['id'=>$editingId]:[])); }
            if ($department==='') { flash('يرجى اختيار الإدارة أو كتابة إدارة أخرى'); redirect(url('new',$editingId!==''?['id'=>$editingId]:[])); }
            if ($category==='') { flash('يرجى اختيار التصنيف أو كتابة تصنيف آخر'); redirect(url('new',$editingId!==''?['id'=>$editingId]:[])); }
            if ($description==='') { flash('يرجى كتابة وصف واضح للفكرة'); redirect(url('new',$editingId!==''?['id'=>$editingId]:[])); }
            if (text_length($description)<30) { flash('يرجى كتابة وصف أوضح للفكرة لا يقل عن 30 حرفًا'); redirect(url('new',$editingId!==''?['id'=>$editingId]:[])); }
        }

        $pdo=db();
        $pdo->beginTransaction();
        try {
            $storedStatus=$isDraft?'draft':'evaluated';
            if ($editingIdea!==null) {
                $ideaId=(int)$editingIdea['id'];
                $stmt=$pdo->prepare('UPDATE ideas SET title=?, description=?, department=?, department_is_other=?, category=?, category_is_other=?, status=?, score=NULL WHERE id=? AND user_id=?');
                $stmt->execute([$title!==''?$title:'مسودة بدون عنوان',$description,$department?:null,$departmentChoice==='__other__'?1:0,$category?:null,$categoryChoice==='__other__'?1:0,$storedStatus,$ideaId,$currentUserId]);
                $pdo->prepare('DELETE FROM evaluations WHERE idea_id=?')->execute([$ideaId]);
            } else {
                $next=(int)$pdo->query('SELECT COALESCE(MAX(id),0)+1 FROM ideas')->fetchColumn();
                $ideaNumber='IDEA-'.date('Y').'-'.str_pad((string)$next,4,'0',STR_PAD_LEFT);
                $stmt=$pdo->prepare('INSERT INTO ideas (idea_number,user_id,title,description,department,department_is_other,category,category_is_other,status) VALUES (?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$ideaNumber,$currentUserId,$title!==''?$title:'مسودة بدون عنوان',$description,$department?:null,$departmentChoice==='__other__'?1:0,$category?:null,$categoryChoice==='__other__'?1:0,$storedStatus]);
                $ideaId=(int)$pdo->lastInsertId();
            }

            if (!$isDraft) {
                $ideaForAi=['title'=>$title,'description'=>$description,'department'=>$department,'category'=>$category];
                $result=evaluate_idea_with_ai($ideaForAi);
                $score=round(max(0,min(5,(float)($result['score']??0))),1);
                $ev=$result['evaluation']??[];
                $stmt=$pdo->prepare('INSERT INTO evaluations (idea_id,innovation,feasibility,sustainability,cost,business_value,final_score,strengths,improvements,feedback,improved_title,improved_description) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $ideaId,(float)($ev['innovation']??0),(float)($ev['feasibility']??0),(float)($ev['sustainability']??0),(float)($ev['cost']??0),(float)($ev['business_value']??0),$score,
                    json_encode($ev['strengths']??[],JSON_UNESCAPED_UNICODE),json_encode($ev['improvements']??[],JSON_UNESCAPED_UNICODE),(string)($ev['feedback']??''),(string)($ev['improvedTitle']??$title),(string)($ev['improvedDescription']??$description)
                ]);
                $pdo->prepare('UPDATE ideas SET score=? WHERE id=?')->execute([$score,$ideaId]);
                db_add_notification(null,'admin',$ideaId,'فكرة جديدة تم تقييمها','تم إرسال فكرة جديدة وإكمال تقييمها الذكي وهي جاهزة للمراجعة.');
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
        flash($isDraft?'تم حفظ الفكرة كمسودة بنجاح':'تم إرسال الفكرة وإكمال التقييم الذكي التجريبي');
        redirect($isDraft?url('ideas'):url('details',['id'=>(string)$ideaId]));
    }

    if ($formAction==='admin_idea_decision') {
        require_admin();
        $ideaId=(int)($_POST['idea_id']??0);
        $decision=trim((string)($_POST['decision']??''));
        $returnTo=trim((string)($_POST['return_to']??'details'));
        $stmt=db()->prepare('SELECT id,user_id,status FROM ideas WHERE id=?');
        $stmt->execute([$ideaId]);
        $idea=$stmt->fetch();
        if(!$idea){flash('الفكرة غير موجودة');redirect(url('ideas'));}
        $currentStatus=(string)$idea['status'];
        if($currentStatus==='draft'){flash('لا يمكن اتخاذ قرار على فكرة ما زالت مسودة');redirect(url('ideas'));}
        $redirectAfter=static function() use($returnTo,$ideaId):never { redirect($returnTo==='ideas'?url('ideas'):url('details',['id'=>(string)$ideaId])); };

        if($decision==='rejected'){
            $newStatus='rejected';$message='تم رفض فكرتك بعد مراجعتها من مدير النظام.';
        } elseif($decision==='approved'){
            if($currentStatus!=='evaluated'){flash('الترشيح للتنفيذ متاح فقط بعد اكتمال تقييم الفكرة');$redirectAfter();}
            $newStatus='approved';$message='تم ترشيح فكرتك للتنفيذ بعد مراجعتها من مدير النظام.';
        } else { flash('القرار غير صالح');$redirectAfter(); }

        $stmt=db()->prepare('UPDATE ideas SET status=?, decision_by=?, decision_at=NOW() WHERE id=?');
        $stmt->execute([$newStatus,(int)(current_user()['id']??0),$ideaId]);
        db_add_notification((int)$idea['user_id'],null,$ideaId,'تحديث حالة الفكرة',$message);
        flash($newStatus==='rejected'?'تم رفض الفكرة':'تم ترشيح الفكرة للتنفيذ');
        $redirectAfter();
    }

    if ($formAction==='read_all') {
        $user=current_user()??[];
        $id=(int)($user['id']??0);
        $role=(string)($user['role']??'');
        $stmt=db()->prepare('UPDATE notifications SET is_read=1 WHERE (user_id IS NULL OR user_id=?) AND (recipient_role IS NULL OR recipient_role=?)');
        $stmt->execute([$id,$role]);
        flash('تم تحديد جميع الإشعارات كمقروءة');redirect(url('notifications'));
    }
}

$ideas = db_fetch_ideas();
$notifications = db_fetch_notifications();
$visibleIdeas=current_user()?user_ideas($ideas):[];
$visibleNotifications=current_user()?visible_notifications($notifications):[];
