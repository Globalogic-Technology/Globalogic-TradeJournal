<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/app/phase3_trades.php';
$user = require_auth();
try { phase3_trade_route('/trades', $_SERVER['REQUEST_METHOD'] ?? 'GET', $user); }
catch (InvalidArgumentException $e) { flash('error', $e->getMessage()); redirect('/dashboard'); }
catch (PDOException $e) { error_log((string)$e); http_response_code(500); exit('A database error occurred.'); }
