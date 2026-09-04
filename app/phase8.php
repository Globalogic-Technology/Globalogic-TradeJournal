<?php

declare(strict_types=1);
use App\Services\NotificationService;
require_once dirname(__DIR__).'/app/services/NotificationService.php';

function phase8_route(string $path,string $method,array $user): bool
{
 $uid=(int)$user['id']; $db=db(); NotificationService::generate($db,$uid);
 if($path==='/notifications'){
  if($method==='POST'){verify_csrf();$id=(int)($_POST['id']??0);$a=$db->prepare('UPDATE user_notifications SET is_read=1,read_at=NOW() WHERE id=? AND user_id=?');$a->execute([$id,$uid]);redirect('/notifications');}
  $s=$db->prepare('SELECT * FROM user_notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 100');$s->execute([$uid]);render('notifications',['title'=>'Notifications','notifications'=>$s->fetchAll()]);return true;
 }
 if($path==='/review-queue'){
  $s=$db->prepare('SELECT r.*,t.ticket,t.symbol,t.side,t.status,t.opened_at,t.closed_at,t.entry_price,t.exit_price,t.quantity FROM journal_reviews r INNER JOIN trades t ON t.id=r.trade_id AND t.user_id=r.user_id WHERE r.user_id=? ORDER BY CASE r.review_status WHEN "pending" THEN 0 WHEN "needs_followup" THEN 1 ELSE 2 END,r.review_due_at DESC');$s->execute([$uid]);render('review_queue',['title'=>'Review Queue','reviews'=>$s->fetchAll()]);return true;
 }
 if(preg_match('#^/review/(\d+)$#',$path,$m)){
  $tradeId=(int)$m[1];
  if($method==='POST'){verify_csrf();$status=(string)($_POST['review_status']??'pending');if(!in_array($status,['pending','reviewed','needs_followup'],true))throw new InvalidArgumentException('Invalid review status.');$note=trim((string)($_POST['review_note']??''));$due=trim((string)($_POST['review_due_at']??''));$due=$due!==''?date('Y-m-d H:i:s',strtotime($due)):null;$s=$db->prepare('INSERT INTO journal_reviews(user_id,trade_id,review_status,review_due_at,review_note,reviewed_at) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE review_status=VALUES(review_status),review_due_at=VALUES(review_due_at),review_note=VALUES(review_note),reviewed_at=VALUES(reviewed_at)');$s->execute([$uid,$tradeId,$status,$due,$note,$status==='reviewed'?date('Y-m-d H:i:s'):null]);flash('success','Review updated.');redirect('/review-queue');}
  $s=$db->prepare('SELECT t.*,r.review_status,r.review_due_at,r.review_note,r.reviewed_at FROM trades t LEFT JOIN journal_reviews r ON r.trade_id=t.id AND r.user_id=t.user_id WHERE t.id=? AND t.user_id=?');$s->execute([$tradeId,$uid]);$trade=$s->fetch();if(!$trade)throw new InvalidArgumentException('Trade not found.');render('review',['title'=>'Trade Review','trade'=>$trade]);return true;
 }
 return false;
}
