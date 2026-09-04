<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use Throwable;

final class AuditService
{
    public static function log(PDO $db, ?int $userId, string $eventType, ?string $entityType = null, ?int $entityId = null, ?string $message = null): void
    {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? null;
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
            $stmt = $db->prepare('INSERT INTO audit_log(user_id,event_type,entity_type,entity_id,message,ip_address,user_agent) VALUES(?,?,?,?,?,?,?)');
            $stmt->execute([$userId, substr($eventType, 0, 60), $entityType ? substr($entityType, 0, 60) : null, $entityId, $message ? substr($message, 0, 500) : null, $ip ? substr($ip, 0, 45) : null, $ua ? substr($ua, 0, 255) : null]);
        } catch (Throwable $e) {
            error_log('Audit log failed: ' . $e->getMessage());
        }
    }
}
