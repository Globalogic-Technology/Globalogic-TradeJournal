<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class TradeRiskService
{
    public function calculate(PDO $db, int $userId, array $trade, float $balanceBefore): array
    {
        $configService = new TradingConfigurationService();
        $risk = $configService->resolveRisk(
            $db,
            $userId,
            isset($trade['account_id']) ? (int)$trade['account_id'] : null,
            !empty($trade['trading_system_id']) ? (int)$trade['trading_system_id'] : null
        );

        $idealRisk = $risk ? (float)$risk['ideal_risk'] : 0.0;
        $riskTolerance = $risk ? (float)$risk['risk_tolerance'] : 0.0;
        $entry = (float)$trade['entry_price'];
        $stop = isset($trade['stop_loss']) && $trade['stop_loss'] !== null ? (float)$trade['stop_loss'] : null;
        $quantity = (float)$trade['quantity'];
        $pnl = trade_pnl($trade);

        $riskPerUnit = $stop !== null ? abs($entry - $stop) : null;
        $actualRisk = $riskPerUnit !== null ? $riskPerUnit * $quantity : null;
        $riskPercent = ($actualRisk !== null && $balanceBefore > 0) ? ($actualRisk / $balanceBefore) * 100 : null;
        $positionSize = ($idealRisk > 0 && $riskPerUnit !== null && $riskPerUnit > 0) ? $idealRisk / $riskPerUnit : null;
        $expectedR = ($pnl !== null && $idealRisk > 0) ? $pnl / $idealRisk : null;
        $rMultiple = ($pnl !== null && $actualRisk !== null && $actualRisk > 0) ? $pnl / $actualRisk : null;
        $riskDeviation = ($actualRisk !== null && $idealRisk > 0) ? (($actualRisk - $idealRisk) / $idealRisk) * 100 : null;
        $riskLimit = $idealRisk > 0 ? $idealRisk * (1 + ($riskTolerance / 100)) : null;
        $withinTolerance = $actualRisk === null || $riskLimit === null ? null : $actualRisk <= $riskLimit;
        $balanceAfter = $pnl === null ? $balanceBefore : $balanceBefore + $pnl;

        return [
            'ideal_risk' => $idealRisk,
            'risk_tolerance' => $riskTolerance,
            'actual_risk' => $actualRisk,
            'risk_percent' => $riskPercent,
            'position_size' => $positionSize,
            'expected_r' => $expectedR,
            'r_multiple' => $rMultiple,
            'risk_deviation' => $riskDeviation,
            'risk_limit' => $riskLimit,
            'within_tolerance' => $withinTolerance,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
        ];
    }
}
