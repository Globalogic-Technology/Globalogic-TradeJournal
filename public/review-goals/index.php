<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require dirname(__DIR__,2).'/app/services/PeriodReviewService.php';
require dirname(__DIR__,2).'/app/phase12.php';
$user=require_auth();
try {
    phase12_route('/review-goals',$_SERVER['REQUEST_METHOD']??'GET',$user);
} catch (Throwable $e) {
    error_log((string)$e);
    flash('error',$e->getMessage());
    redirect('/review-goals');
}
