<?php
declare(strict_types=1);require dirname(__DIR__,2).'/app/bootstrap.php';require dirname(__DIR__,2).'/app/phase9.php';$u=require_auth();try{phase9_route('/broker-paste','GET',$u);}catch(PDOException $e){error_log((string)$e);http_response_code(500);exit('A database error occurred.');}
