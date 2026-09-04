<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/app/phase3.php';
$user = require_auth();

try {
    phase3_route('/account-settings', $_SERVER['REQUEST_METHOD'] ?? 'GET', $user);
} catch (InvalidArgumentException $e) {
    flash('error', $e->getMessage());
    redirect('/account-settings');
} catch (PDOException $e) {
    error_log((string)$e);
    flash('error', 'Unable to save account configuration. Please check the submitted values.');
    redirect('/account-settings');
}
