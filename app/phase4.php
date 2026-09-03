<?php

declare(strict_types=1);

function phase4_tag_list(PDO $db, int $userId): array
{
    $s=$db->prepare('SELECT id,name FROM journal_tags WHERE user_id=? ORDER BY name');$s->execute([$userId]);return $s->fetchAll();
}
function phase4_trade_lookup(PDO $db,int $userId,int $tradeId): ?array
{
    $s=$db->prepare('SELECT t.*,a.name account_name,ts.name system_name,st.name strategy_name,sess.name session_name FROM trades t JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id LEFT JOIN trading_systems ts ON ts.id=t.trading_system_id AND ts.user_id=t.user_id LEFT JOIN strategies st ON st.id=t.strategy_id AND st.user_id=t.user_id LEFT JOIN trading_sessions sess ON sess.id=t.trading_session_id AND sess.user_id=t.user_id WHERE t.id=? AND t.user_id=?');$s->execute([$tradeId,$userId]);return $s->fetch()?:null;
}
function phase4_journal(PDO $db,int $userId,int $tradeId): array
{
    $s=$db->prepare('SELECT * FROM trade_journals WHERE trade_id=? AND user_id=?');$s->execute([$tradeId,$userId]);$journal=$s->fetch()?:[];
    $s=$db->prepare('SELECT t.id,t.name FROM journal_tags t JOIN trade_journal_tags jt ON jt.tag_id=t.id JOIN trade_journals j ON j.id=jt.journal_id WHERE j.trade_id=? AND j.user_id=? ORDER BY t.name');$s->execute([$tradeId,$userId]);$journal['tags']=$s->fetchAll();return $journal;
}
function phase4_score(?string $value,string $field): ?int
{
    if(trim((string)$value)==='')return null;$n=filter_var($value,FILTER_VALIDATE_INT);if($n===false||$n<1||$n>5)throw new InvalidArgumentException($field.' must be between 1 and 5.');return (int)$n;
}
function phase4_save_journal(PDO $db,int $userId,int $tradeId,array $input): void
{
    if(!phase4_trade_lookup($db,$userId,$tradeId))throw new InvalidArgumentException('The selected trade is invalid.');
    $confidence=phase4_score($input['confidence']??null,'Confidence');$execution=phase4_score($input['execution_quality']??null,'Execution quality');$discipline=phase4_score($input['discipline_score']??null,'Discipline score');
    $fields=['setup','market_context','thesis','entry_reason','exit_reason','emotion_before','emotion_after','mistakes','lessons','what_went_well','what_to_change'];$data=[];foreach($fields as $f)$data[$f]=trim((string)($input[$f]??''));
    $reviewed=($input['reviewed_at']??'')!==''?datetime_input($input['reviewed_at'],'Review time',false):null;$db->beginTransaction();
    try{
        $s=$db->prepare('SELECT id FROM trade_journals WHERE trade_id=? AND user_id=?');$s->execute([$tradeId,$userId]);$journalId=$s->fetchColumn();
        $values=[$data['setup'],$data['market_context'],$data['thesis'],$data['entry_reason'],$data['exit_reason'],$data['emotion_before'],$data['emotion_after'],$confidence,$execution,$discipline,$data['mistakes'],$data['lessons'],$data['what_went_well'],$data['what_to_change'],$reviewed];
        if($journalId){$db->prepare('UPDATE trade_journals SET setup=?,market_context=?,thesis=?,entry_reason=?,exit_reason=?,emotion_before=?,emotion_after=?,confidence=?,execution_quality=?,discipline_score=?,mistakes=?,lessons=?,what_went_well=?,what_to_change=?,reviewed_at=? WHERE id=? AND user_id=?')->execute([...$values,$journalId,$userId]);}
        else{$db->prepare('INSERT INTO trade_journals(user_id,trade_id,setup,market_context,thesis,entry_reason,exit_reason,emotion_before,emotion_after,confidence,execution_quality,discipline_score,mistakes,lessons,what_went_well,what_to_change,reviewed_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([$userId,$tradeId,...$values]);$journalId=(int)$db->lastInsertId();}
        $db->prepare('DELETE FROM trade_journal_tags WHERE journal_id=?')->execute([$journalId]);
        foreach(explode(',',(string)($input['tags']??'')) as $raw){$name=trim($raw);if($name==='')continue;if(strlen($name)>80)throw new InvalidArgumentException('Tag names must be 80 characters or fewer.');$s=$db->prepare('SELECT id FROM journal_tags WHERE user_id=? AND name=?');$s->execute([$userId,$name]);$tagId=$s->fetchColumn();if(!$tagId){$db->prepare('INSERT INTO journal_tags(user_id,name) VALUES(?,?)')->execute([$userId,$name]);$tagId=(int)$db->lastInsertId();}$db->prepare('INSERT INTO trade_journal_tags(journal_id,tag_id) VALUES(?,?)')->execute([$journalId,$tagId]);}
        $db->commit();
    }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
function phase4_route(string $path,string $method,array $user): bool
{
    $tradeId=null;if(preg_match('#^/trades/(\d+)/journal$#',$path,$m))$tradeId=(int)$m[1];elseif($path==='/trades'&&isset($_GET['journal']))$tradeId=filter_var($_GET['journal'],FILTER_VALIDATE_INT)?:null;if(!$tradeId)return false;
    $db=db();$userId=(int)$user['id'];$trade=phase4_trade_lookup($db,$userId,$tradeId);if(!$trade){http_response_code(404);render('404',['title'=>'Trade not found']);return true;}
    if($method==='POST'){verify_csrf();phase4_save_journal($db,$userId,$tradeId,$_POST);flash('success','Journal review saved.');redirect('/trades?journal='.$tradeId);}
    $journal=phase4_journal($db,$userId,$tradeId);render('trade_journal',['title'=>'Trade Journal','trade'=>$trade,'journal'=>$journal,'tags'=>phase4_tag_list($db,$userId)]);return true;
}
