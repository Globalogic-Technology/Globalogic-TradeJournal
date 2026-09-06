<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require dirname(__DIR__,2).'/app/services/PeriodReviewService.php';
require dirname(__DIR__,2).'/app/phase12.php';
$user=require_auth();
try{phase12_route('/review-status',$_SERVER['REQUEST_METHOD']??'GET',$user);}catch(Throwable $e){http_response_code(500);header('Content-Type: application/json; charset=utf-8');echo json_encode(['ok'=>false]);}
