<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require dirname(__DIR__,2).'/app/account_csv_templates.php';
$user=require_auth();
account_csv_templates_route('/account-csv-templates',$_SERVER['REQUEST_METHOD']??'GET',$user);
