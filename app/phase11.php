<?php

declare(strict_types=1);

function phase11_pnl_expr(string $alias='t'): string
{
    return "CASE WHEN {$alias}.status='closed' AND {$alias}.exit_price IS NOT NULL THEN CASE WHEN {$alias}.side='long' THEN ({$alias}.exit_price-{$alias}.entry_price)*{$alias}.quantity-{$alias}.fees ELSE ({$alias}.entry_price-{$alias}.exit_price)*{$alias}.quantity-{$alias}.fees END ELSE 0 END";
}

function phase11_route(string $path,string $method,array $user): bool
{
    $uid=(int)$user['id'];$db=db();
    if($path==='/goals'){
        if($method==='POST'){
            verify_csrf();
            $accountId=filter_var($_POST['account_id']??null,FILTER_VALIDATE_INT)?:0;
            $q=$db->prepare('SELECT id FROM accounts WHERE id=? AND user_id=?');$q->execute([$accountId,$uid]);
            if(!$q->fetchColumn())throw new InvalidArgumentException('Select a valid account.');
            $vals=[];
            foreach(['daily_goal','weekly_goal','monthly_goal','yearly_goal'] as $f){
                $v=trim((string)($_POST[$f]??''));
                if($v===''||!is_numeric($v)||!is_finite((float)$v))throw new InvalidArgumentException('All P&L goals must be numeric.');
                $v=(float)$v;if($v<0)throw new InvalidArgumentException('P&L goals cannot be negative.');$vals[]=$v;
            }
            $s=$db->prepare('INSERT INTO pnl_goals(user_id,account_id,daily_goal,weekly_goal,monthly_goal,yearly_goal) VALUES(?,?,?,?,?,?) ON DUPLICATE KEY UPDATE daily_goal=VALUES(daily_goal),weekly_goal=VALUES(weekly_goal),monthly_goal=VALUES(monthly_goal),yearly_goal=VALUES(yearly_goal)');
            $s->execute([$uid,$accountId,...$vals]);flash('success','P&L goals saved.');redirect('/goals');
        }
        $s=$db->prepare('SELECT g.*,a.name account_name,a.currency FROM pnl_goals g JOIN accounts a ON a.id=g.account_id WHERE g.user_id=? ORDER BY a.name');$s->execute([$uid]);$goals=$s->fetchAll();
        $s=$db->prepare('SELECT id,name,currency FROM accounts WHERE user_id=? ORDER BY name');$s->execute([$uid]);$accounts=$s->fetchAll();
        render('goals',['title'=>'P&L Goals','goals'=>$goals,'accounts'=>$accounts]);return true;
    }
    return false;
}

function phase11_period_pnl(PDO $db,int $uid,string $start,string $end,?int $accountId=null): float
{
    $where='t.user_id=? AND t.status="closed" AND t.closed_at>=? AND t.closed_at<?';$params=[$uid,$start,$end];
    if($accountId!==null){$where.=' AND t.account_id=?';$params[]=$accountId;}
    $s=$db->prepare('SELECT COALESCE(SUM('.phase11_pnl_expr().'),0) FROM trades t WHERE '.$where);$s->execute($params);return(float)$s->fetchColumn();
}

function phase11_performance(PDO $db,int $uid): array
{
    $now=new DateTimeImmutable('now');$today=$now->setTime(0,0,0);$tomorrow=$today->modify('+1 day');
    $weekStart=$today->modify('-'.((int)$today->format('N')-1).' days');$nextWeek=$weekStart->modify('+7 days');
    $monthStart=$today->modify('first day of this month');$nextMonth=$monthStart->modify('first day of next month');
    $yearStart=$today->setDate((int)$today->format('Y'),1,1);$nextYear=$yearStart->modify('+1 year');
    $s=$db->prepare('SELECT id,name,currency FROM accounts WHERE user_id=? ORDER BY name');$s->execute([$uid]);$accounts=$s->fetchAll();
    $accountPnl=[];
    foreach($accounts as $a){
        $id=(int)$a['id'];
        $accountPnl[$id]=[
            'today'=>phase11_period_pnl($db,$uid,$today->format('Y-m-d H:i:s'),$tomorrow->format('Y-m-d H:i:s'),$id),
            'week'=>phase11_period_pnl($db,$uid,$weekStart->format('Y-m-d H:i:s'),$nextWeek->format('Y-m-d H:i:s'),$id),
            'month'=>phase11_period_pnl($db,$uid,$monthStart->format('Y-m-d H:i:s'),$nextMonth->format('Y-m-d H:i:s'),$id),
            'year'=>phase11_period_pnl($db,$uid,$yearStart->format('Y-m-d H:i:s'),$nextYear->format('Y-m-d H:i:s'),$id)
        ];
    }
    $calendar=[];$days=(int)$nextMonth->modify('-1 day')->format('d');
    for($d=1;$d<=$days;$d++){$date=$monthStart->setDate((int)$monthStart->format('Y'),(int)$monthStart->format('m'),$d);$next=$date->modify('+1 day');$calendar[$date->format('Y-m-d')]=phase11_period_pnl($db,$uid,$date->format('Y-m-d H:i:s'),$next->format('Y-m-d H:i:s'));}
    $year=[];
    for($m=1;$m<=12;$m++){$start=$yearStart->setDate((int)$yearStart->format('Y'),$m,1);$end=$start->modify('+1 month');$year[$start->format('Y-m')]=phase11_period_pnl($db,$uid,$start->format('Y-m-d H:i:s'),$end->format('Y-m-d H:i:s'));}
    $s=$db->prepare('SELECT g.*,a.name account_name,a.currency FROM pnl_goals g JOIN accounts a ON a.id=g.account_id WHERE g.user_id=? ORDER BY a.name');$s->execute([$uid]);$goals=$s->fetchAll();
    $goalMap=[];
    foreach($goals as $g){$goalMap[(int)$g['account_id']]=$g;}
    return compact('accounts','accountPnl','calendar','year','goals','goalMap','today','weekStart','monthStart','yearStart');
}
