<?php

declare(strict_types=1);
use App\Services\NotificationService;
use App\Services\PeriodReviewService;
require_once dirname(__DIR__).'/app/services/NotificationService.php';
require_once dirname(__DIR__).'/app/services/PeriodReviewService.php';

function phase8_route(string $path,string $method,array $user): bool
{
 $uid=(int)$user['id'];$db=db();NotificationService::generate($db,$uid);
 if($path==='/notifications'){
  if($method==='POST'){verify_csrf();$id=(int)($_POST['id']??0);$db->prepare('UPDATE user_notifications SET is_read=1,read_at=NOW() WHERE id=? AND user_id=?')->execute([$id,$uid]);redirect('/notifications'.(!empty($_POST['filter'])?'?filter='.rawurlencode((string)$_POST['filter']):''));}
  $filter=(string)($_GET['filter']??'all');$where='user_id=?';$params=[$uid];if(in_array($filter,['trade','day','week','month','year'],true)){if($filter==='trade')$where.=' AND entity_type="trade"';else $where.=' AND entity_type="period_review" AND title LIKE ?';$params[]=$filter==='trade'?null:'%'.ucfirst($filter).'%';if($filter==='trade')array_pop($params);} $s=$db->prepare('SELECT * FROM user_notifications WHERE '.$where.' ORDER BY created_at DESC LIMIT 100');$s->execute($params);render('notifications',['title'=>'Notifications','notifications'=>$s->fetchAll(),'filter'=>$filter,'unread'=>NotificationService::unread($db,$uid)]);return true;
 }
 if($path==='/review-queue'){
  $filter=(string)($_GET['filter']??'all');$rows=[];
  if($filter==='all'||$filter==='trade'){$s=$db->prepare('SELECT r.id,r.trade_id,r.review_status status,r.review_due_at due,t.ticket,t.symbol,t.side,t.closed_at FROM journal_reviews r INNER JOIN trades t ON t.id=r.trade_id AND t.user_id=r.user_id WHERE r.user_id=? ORDER BY CASE r.review_status WHEN "pending" THEN 0 WHEN "needs_followup" THEN 1 ELSE 2 END,r.review_due_at DESC');$s->execute([$uid]);foreach($s->fetchAll() as $r)$rows[]=['kind'=>'trade','id'=>(int)$r['id'],'label'=>'Trade','period'=>$r['symbol'].' #'.($r['ticket']?:$r['trade_id']),'date'=>$r['closed_at'],'due'=>$r['due'],'status'=>$r['status'],'url'=>'/review?id='.$r['trade_id']];}
  if($filter==='all'||in_array($filter,['day','week','month','year'],true)){PeriodReviewService::ensureCurrent($db,$uid);$s=$db->prepare('SELECT * FROM period_reviews WHERE user_id=? AND period_type=? ORDER BY CASE status WHEN "pending" THEN 0 ELSE 1 END,scheduled_at DESC');if($filter==='all'){$s=$db->prepare('SELECT * FROM period_reviews WHERE user_id=? ORDER BY CASE status WHEN "pending" THEN 0 ELSE 1 END,scheduled_at DESC');$s->execute([$uid]);}else$s->execute([$uid,$filter]);foreach($s->fetchAll() as $r)$rows[]=['kind'=>$r['period_type'],'id'=>(int)$r['id'],'label'=>ucfirst($r['period_type']).' '.ucfirst($r['review_type']),'period'=>$r['period_start'].' → '.$r['period_end'],'date'=>$r['period_start'],'due'=>$r['scheduled_at'],'status'=>$r['status'],'url'=>'/period-review/'.$r['id']];}
  usort($rows,fn($a,$b)=>($a['status']==='pending'?0:1)<=>($b['status']==='pending'?0:1) ?: strcmp((string)$b['due'],(string)$a['due']));render('review_queue',['title'=>'Review Queue','reviews'=>$rows,'filter'=>$filter]);return true;
 }
 if($path==='/review' || preg_match('#^/review/(\d+)$#',$path,$m)){
  $tradeId=$path==='/review'?(int)($_GET['id']??0):(int)$m[1];if($tradeId<=0)throw new InvalidArgumentException('Trade ID is required.');
  if($method==='POST'){verify_csrf();$status=(string)($_POST['review_status']??'pending');if(!in_array($status,['pending','reviewed','needs_followup'],true))throw new InvalidArgumentException('Invalid review status.');$note=trim((string)($_POST['review_note']??''));$due=trim((string)($_POST['review_due_at']??''));$due=$due!==''?date('Y-m-d H:i:s',strtotime($due)):null;$s=$db->prepare('INSERT INTO journal_reviews(user_id,trade_id,review_status,review_due_at,review_note,reviewed_at) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE review_status=VALUES(review_status),review_due_at=VALUES(review_due_at),review_note=VALUES(review_note),reviewed_at=VALUES(reviewed_at)');$s->execute([$uid,$tradeId,$status,$due,$note,$status==='reviewed'?date('Y-m-d H:i:s'):null]);flash('success','Review updated.');redirect('/review-queue');}
  $s=$db->prepare('SELECT t.*,r.review_status,r.review_due_at,r.review_note,r.reviewed_at FROM trades t LEFT JOIN journal_reviews r ON r.trade_id=t.id AND r.user_id=t.user_id WHERE t.id=? AND t.user_id=?');$s->execute([$tradeId,$uid]);$trade=$s->fetch();if(!$trade)throw new InvalidArgumentException('Trade not found.');render('review',['title'=>'Trade Review','trade'=>$trade]);return true;
 }
 return false;
}
