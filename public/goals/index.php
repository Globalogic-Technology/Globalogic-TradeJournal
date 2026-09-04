<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require dirname(__DIR__,2).'/app/phase11.php';
$user=require_auth();
$path='/goals';$method=$_SERVER['REQUEST_METHOD']??'GET';
try{if(!phase11_route($path,$method,$user)){http_response_code(404);render('404',['title'=>'Not found']);}}catch(InvalidArgumentException $e){flash('error',$e->getMessage());redirect('/goals');}catch(PDOException $e){if((int)($e->errorInfo[1]??0)===1062){flash('error','A goal profile already exists for this account.');redirect('/goals');}error_log((string)$e);http_response_code(500);exit('A database error occurred.');}
