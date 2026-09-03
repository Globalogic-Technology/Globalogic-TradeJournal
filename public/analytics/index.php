<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/app/services/TradingConfigurationService.php';
require dirname(__DIR__, 2) . '/app/services/TradeRiskService.php';
require dirname(__DIR__, 2) . '/app/services/AnalyticsService.php';

$user = require_auth();
$filters = [
    'system_id' => filter_var($_GET['system_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
    'session_id' => filter_var($_GET['session_id'] ?? null, FILTER_VALIDATE_INT) ?: null,
    'date_from' => trim((string)($_GET['date_from'] ?? '')),
    'date_to' => trim((string)($_GET['date_to'] ?? '')),
];
foreach (['date_from','date_to'] as $key) {
    if ($filters[$key] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$key])) $filters[$key] = '';
}
try {
    $db = db();
    $stmt = $db->prepare('SELECT id,name FROM trading_systems WHERE user_id=? ORDER BY name');
    $stmt->execute([$user['id']]);
    $systems = $stmt->fetchAll();
    $stmt = $db->prepare('SELECT id,name FROM trading_sessions WHERE user_id=? ORDER BY name');
    $stmt->execute([$user['id']]);
    $sessions = $stmt->fetchAll();
    $analytics = (new \App\Services\AnalyticsService())->build($db, (int)$user['id'], $filters);
    render('analytics', ['title'=>'Analytics','analytics'=>$analytics,'systems'=>$systems,'sessions'=>$sessions,'filters'=>$filters]);
} catch (InvalidArgumentException $e) {
    flash('error', $e->getMessage());
    redirect('/analytics');
} catch (PDOException $e) {
    error_log((string)$e);
    http_response_code(500);
    exit('A database error occurred.');
}
