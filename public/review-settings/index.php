<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require dirname(__DIR__,2).'/app/services/PeriodReviewService.php';
require dirname(__DIR__,2).'/app/phase12.php';
$user=require_auth();
try{phase12_route('/review-settings',$_SERVER['REQUEST_METHOD']??'GET',$user);}catch(InvalidArgumentException|RuntimeException $e){flash('error',$e->getMessage());redirect('/review-settings');}catch(PDOException $e){error_log((string)$e);http_response_code(500);exit('A database error occurred.');}
