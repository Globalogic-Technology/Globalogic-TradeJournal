<?php

declare(strict_types=1);
namespace App\Services;
use PDO;

final class NotificationService
{
    public static function generate(PDO $db,int $userId): int
    {
        $created=0;
        $q=$db->prepare('SELECT t.id,t.symbol,t.closed_at FROM trades t WHERE t.user_id=? AND t.status="closed" AND t.closed_at IS NOT NULL AND NOT EXISTS (SELECT 1 FROM trade_journals j WHERE j.trade_id=t.id AND j.user_id=?) AND NOT EXISTS (SELECT 1 FROM journal_reviews r WHERE r.trade_id=t.id AND r.user_id=?) ORDER BY t.closed_at DESC LIMIT 100');$q->execute([$userId,$userId,$userId]);
        $ins=$db->prepare('INSERT INTO journal_reviews(user_id,trade_id,review_status,review_due_at) VALUES(?,?,"pending",?)');
        foreach($q->fetchAll() as $t){$due=date('Y-m-d H:i:s',strtotime((string)$t['closed_at'].' +1 day'));$ins->execute([$userId,(int)$t['id'],$due]);$created++;}
        $dueQ=$db->prepare('SELECT r.id,t.id trade_id,t.symbol,r.review_due_at FROM journal_reviews r INNER JOIN trades t ON t.id=r.trade_id WHERE r.user_id=? AND r.review_status="pending" AND r.review_due_at<=NOW() ORDER BY r.review_due_at DESC LIMIT 50');$dueQ->execute([$userId]);
        $exists=$db->prepare('SELECT id FROM user_notifications WHERE user_id=? AND type="journal_due" AND entity_id=? LIMIT 1');$add=$db->prepare('INSERT INTO user_notifications(user_id,type,title,message,entity_type,entity_id) VALUES(?,?,?,?,?,?)');
        foreach($dueQ->fetchAll() as $r){$exists->execute([$userId,(int)$r['trade_id']]);if(!$exists->fetchColumn()){$add->execute([$userId,'journal_due','Trade review due','Review trade #'.(int)$r['trade_id'].' ('.(string)$r['symbol'].').','trade',(int)$r['trade_id']]);$created++;}}
        if(class_exists(PeriodReviewService::class)){
            $reviews=PeriodReviewService::due($db,$userId);$exists=$db->prepare('SELECT id FROM user_notifications WHERE user_id=? AND type="period_review_due" AND entity_id=? LIMIT 1');
            foreach($reviews as $r){$exists->execute([$userId,(int)$r['id']]);if(!$exists->fetchColumn()){$label=ucfirst((string)$r['period_type']).' '.ucfirst((string)$r['review_type']).' Review';$add->execute([$userId,'period_review_due',$label.' required',$label.' is ready to complete for '.(string)$r['period_start'].' to '.(string)$r['period_end'].'.','period_review',(int)$r['id']]);$created++;}}
        }
        return $created;
    }
    public static function unread(PDO $db,int $userId):int{$s=$db->prepare('SELECT COUNT(*) FROM user_notifications WHERE user_id=? AND is_read=0');$s->execute([$userId]);return(int)$s->fetchColumn();}
}
require_once dirname(__DIR__).'/services/PeriodReviewService.php';
