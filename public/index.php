<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
$path=rtrim(parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/','/')?:'/';
$method=$_SERVER['REQUEST_METHOD']??'GET';

try {
if($path==='/login'){
 if($method==='POST'){
  verify_csrf(); $email=strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??'');
  $s=db()->prepare('SELECT id,name,email,password_hash FROM users WHERE email=?'); $s->execute([$email]); $u=$s->fetch();
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||!$u||!password_verify($password,$u['password_hash'])){flash('error','Invalid email or password.');redirect('/login');}
  session_regenerate_id(true); $_SESSION['user_id']=(int)$u['id']; unset($_SESSION['_csrf']); redirect('/dashboard');
 }
 render('login',['title'=>'Login']); exit;
}
if($path==='/register'){
 if($method==='POST'){
  verify_csrf(); $name=trim((string)($_POST['name']??'')); $email=strtolower(trim((string)($_POST['email']??''))); $password=(string)($_POST['password']??'');
  if($name===''||strlen($name)>120)throw new InvalidArgumentException('Name is required.');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Enter a valid email.');
  if(strlen($password)<8)throw new InvalidArgumentException('Password must contain at least 8 characters.');
  db()->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT)]);
  session_regenerate_id(true);$_SESSION['user_id']=(int)db()->lastInsertId();unset($_SESSION['_csrf']);redirect('/dashboard');
 }
 render('register',['title'=>'Create account']);exit;
}
if($path==='/logout'){
 if($method!=='POST'){http_response_code(405);exit('Method not allowed.');} verify_csrf(); $_SESSION=[];session_destroy();redirect('/login');
}
$user=require_auth();

if($path==='/'||$path==='/dashboard'){
 $s=db()->prepare('SELECT a.*,COUNT(t.id) trade_count,COALESCE(SUM(CASE WHEN t.status="closed" THEN CASE WHEN t.side="long" THEN (t.exit_price-t.entry_price)*t.quantity-t.fees ELSE (t.entry_price-t.exit_price)*t.quantity-t.fees END ELSE 0 END),0) pnl FROM accounts a LEFT JOIN trades t ON t.account_id=a.id AND t.user_id=a.user_id WHERE a.user_id=? GROUP BY a.id ORDER BY a.name');
 $s->execute([$user['id']]);$accounts=$s->fetchAll();
 $s=db()->prepare('SELECT COUNT(*) closed_count,COALESCE(SUM(CASE WHEN side="long" THEN (exit_price-entry_price)*quantity-fees ELSE (entry_price-exit_price)*quantity-fees END),0) pnl FROM trades WHERE user_id=? AND status="closed"');
 $s->execute([$user['id']]);render('dashboard',['title'=>'Dashboard','accounts'=>$accounts,'summary'=>$s->fetch()]);exit;
}

if($path==='/accounts'){
 if($method==='POST'){
  verify_csrf();$action=$_POST['action']??'';$id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);
  $name=trim((string)($_POST['name']??''));$currency=strtoupper(trim((string)($_POST['currency']??'USD')));$balance=decimal_input($_POST['initial_balance']??null,'Initial balance')??0;
  if($name===''||strlen($name)>120||!preg_match('/^[A-Z]{3}$/',$currency)||$balance<0)throw new InvalidArgumentException('Invalid account values.');
  if($action==='create')db()->prepare('INSERT INTO accounts(user_id,name,currency,initial_balance) VALUES(?,?,?,?)')->execute([$user['id'],$name,$currency,$balance]);
  elseif($action==='update'&&$id)db()->prepare('UPDATE accounts SET name=?,currency=?,initial_balance=? WHERE id=? AND user_id=?')->execute([$name,$currency,$balance,$id,$user['id']]);
  elseif($action==='delete'&&$id)db()->prepare('DELETE FROM accounts WHERE id=? AND user_id=?')->execute([$id,$user['id']]);
  else throw new InvalidArgumentException('Unknown account action.');
  flash('success','Account saved.');redirect('/accounts');
 }
 $s=db()->prepare('SELECT * FROM accounts WHERE user_id=? ORDER BY name');$s->execute([$user['id']]);render('accounts',['title'=>'Accounts','accounts'=>$s->fetchAll()]);exit;
}

if($path==='/trades'){
 if($method==='POST'){
  verify_csrf();$action=$_POST['action']??'';
  if($action==='delete'){ $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);db()->prepare('DELETE FROM trades WHERE id=? AND user_id=?')->execute([$id,$user['id']]);flash('success','Trade deleted.');redirect('/trades');}
  if($action==='save'){
   $id=filter_var($_POST['id']??null,FILTER_VALIDATE_INT);$data=trade_form_data($_POST,$user);
   if($id){db()->prepare('UPDATE trades SET account_id=?,ticket=?,symbol=?,side=?,status=?,opened_at=?,closed_at=?,quantity=?,entry_price=?,stop_loss=?,take_profit=?,exit_price=?,fees=?,notes=? WHERE id=? AND user_id=?')->execute([...$data,$id,$user['id']]);}
   else{db()->prepare('INSERT INTO trades(account_id,ticket,symbol,side,status,opened_at,closed_at,quantity,entry_price,stop_loss,take_profit,exit_price,fees,notes,user_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([...$data,$user['id']]);}
   flash('success',$id?'Trade updated.':'Trade added.');redirect('/trades');
  }
  throw new InvalidArgumentException('Unknown trade action.');
 }
 $s=db()->prepare('SELECT id,name,currency FROM accounts WHERE user_id=? ORDER BY name');$s->execute([$user['id']]);$accounts=$s->fetchAll();
 $editTrade=null;$editId=filter_var($_GET['edit']??null,FILTER_VALIDATE_INT);
 if($editId){$s=db()->prepare('SELECT * FROM trades WHERE id=? AND user_id=?');$s->execute([$editId,$user['id']]);$editTrade=$s->fetch()?:null;}
 $where=['t.user_id=?'];$params=[$user['id']];$symbol=trim((string)($_GET['symbol']??''));$side=$_GET['side']??'';$status=$_GET['status']??'';
 if($symbol!==''){$where[]='t.symbol LIKE ?';$params[]='%'.$symbol.'%';}
 if(in_array($side,['long','short'],true)){$where[]='t.side=?';$params[]=$side;}
 if(in_array($status,['open','closed'],true)){$where[]='t.status=?';$params[]=$status;}
 $s=db()->prepare('SELECT COUNT(*) FROM trades t WHERE '.implode(' AND ',$where));$s->execute($params);$total=(int)$s->fetchColumn();
 $page=max(1,(int)($_GET['page']??1));$pages=max(1,(int)ceil($total/25));$page=min($page,$pages);$offset=($page-1)*25;
 $s=db()->prepare('SELECT t.*,a.name account_name,a.currency FROM trades t JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id WHERE '.implode(' AND ',$where).' ORDER BY t.opened_at DESC,t.id DESC LIMIT 25 OFFSET '.$offset);$s->execute($params);$trades=$s->fetchAll();
 foreach($trades as &$t)$t['pnl']=trade_pnl($t);unset($t);
 render('trades',['title'=>'Trades','accounts'=>$accounts,'editTrade'=>$editTrade,'trades'=>$trades,'filters'=>compact('symbol','side','status'),'page'=>$page,'pages'=>$pages,'total'=>$total]);exit;
}
http_response_code(404);render('404',['title'=>'Not found']);
} catch(InvalidArgumentException $e){flash('error',$e->getMessage());redirect($_SERVER['HTTP_REFERER']??'/dashboard');}
catch(PDOException $e){if((int)($e->errorInfo[1]??0)===1062){flash('error','That account name or ticket already exists.');redirect($_SERVER['HTTP_REFERER']??'/dashboard');}error_log((string)$e);http_response_code(500);exit('A database error occurred.');}
