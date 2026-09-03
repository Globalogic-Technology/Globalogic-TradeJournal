<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Phase 3 configuration resolver.
 *
 * This class only resolves persisted configuration. It does not calculate P&L,
 * Expected R, position sizing, or risk deviation, so the Phase 2 calculation
 * implementation remains the single source of truth for derived trade metrics.
 */
final class TradingConfigurationService
{
    public function resolveRisk(\PDO $db, int $userId, ?int $accountId = null, ?int $systemId = null): ?array
    {
        if ($accountId !== null) {
            $stmt = $db->prepare(
                'SELECT ideal_risk, risk_tolerance, account_id, trading_system_id
                 FROM risk_settings
                 WHERE user_id=? AND account_id=?
                 ORDER BY trading_system_id IS NULL, id DESC LIMIT 1'
            );
            $stmt->execute([$userId, $accountId]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        if ($systemId !== null) {
            $stmt = $db->prepare(
                'SELECT ideal_risk, risk_tolerance, account_id, trading_system_id
                 FROM risk_settings
                 WHERE user_id=? AND trading_system_id=?
                 ORDER BY account_id IS NULL, id DESC LIMIT 1'
            );
            $stmt->execute([$userId, $systemId]);
            $row = $stmt->fetch();
            if ($row) return $row;

            $stmt = $db->prepare('SELECT ideal_risk, risk_tolerance, id AS trading_system_id FROM trading_systems WHERE id=? AND user_id=?');
            $stmt->execute([$systemId, $userId]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        if ($accountId !== null) {
            $stmt = $db->prepare('SELECT ideal_risk, risk_tolerance, id AS account_id, default_system_id AS trading_system_id FROM accounts WHERE id=? AND user_id=?');
            $stmt->execute([$accountId, $userId]);
            $row = $stmt->fetch();
            if ($row) return $row;
        }

        return null;
    }

    public function resolveAssetFee(\PDO $db, int $userId, int $assetId, string $feeType = 'commission'): ?array
    {
        $stmt = $db->prepare(
            'SELECT f.* FROM asset_fees f
             INNER JOIN assets a ON a.id=f.asset_id AND a.user_id=f.user_id
             WHERE f.user_id=? AND f.asset_id=? AND f.fee_type=? LIMIT 1'
        );
        $stmt->execute([$userId, $assetId, $feeType]);
        return $stmt->fetch() ?: null;
    }
}
