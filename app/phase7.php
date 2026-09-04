<?php

declare(strict_types=1);

use App\Services\AuditService;
require_once dirname(__DIR__) . '/app/services/AuditService.php';

function phase7_route(string $path, string $method, array $user): bool
{
    if ($path !== '/audit-log') return false;
    if ($method !== 'GET') { http_response_code(405); exit('Method not allowed.'); }
    $uid=(int)$user['id'];
    $event=trim((string)($_GET['event']??''));
    $limit=min(200,max(10,(int)($_GET['limit']??100)));
    $where=['user_id=?'];$params=[$uid];
    if($event!==''){$where[]='event_type=?';$params[]=$event;}
    $sql='SELECT * FROM audit_log WHERE '.implode(' AND ',$where).' ORDER BY created_at DESC,id DESC LIMIT '.$limit;
    $s=db()->prepare($sql);$s->execute($params);
    $logs=$s->fetchAll();
    $s=db()->prepare('SELECT DISTINCT event_type FROM audit_log WHERE user_id=? ORDER BY event_type');$s->execute([$uid]);$events=$s->fetchAll(PDO::FETCH_COLUMN);
    render('audit_log',['title'=>'Audit Log','logs'=>$logs,'events'=>$events,'filterEvent'=>$event,'limit'=>$limit]);
    return true;
}
