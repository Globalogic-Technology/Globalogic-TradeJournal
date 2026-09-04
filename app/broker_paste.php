<?php
declare(strict_types=1);
function broker_paste_route(string $method,array $user):void
{
    $db=db();
    $q=$db->prepare('SELECT id,name,currency FROM accounts WHERE user_id=? ORDER BY name');
    $q->execute([(int)$user['id']]);
    render('broker_paste',['title'=>'Quick Entry from Broker','accounts'=>$q->fetchAll()]);
}
