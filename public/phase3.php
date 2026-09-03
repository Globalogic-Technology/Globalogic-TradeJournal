<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/phase3.php';
require dirname(__DIR__) . '/app/phase3_trades.php';
require dirname(__DIR__) . '/app/phase4.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$user = require_auth();

try {
    if (phase4_route($path, $method, $user)) {
        exit;
    }
    if (phase3_trade_route($path, $method, $user)) {
        exit;
    }
    if (!phase3_route($path, $method, $user)) {
        http_response_code(404);
        render('404', ['title' => 'Not found']);
    }
} catch (InvalidArgumentException $e) {
    flash('error', $e->getMessage());
    redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
} catch (PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        flash('error', 'A record with the same unique value already exists.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
    }
    error_log((string)$e);
    http_response_code(500);
    exit('A database error occurred.');
}
