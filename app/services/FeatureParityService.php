<?php

declare(strict_types=1);

namespace App\Services;

final class FeatureParityService
{
    public const GRADES = ['A++++','A+++','A++','A+','A','B','C','D','E','F'];
    public const GRADE_MULTIPLIERS = [
        'A++++'=>2.5,'A+++'=>2.0,'A++'=>1.25,'A+'=>1.0,'A'=>0.8,
        'B'=>0.5,'C'=>0.3,'D'=>0.1,'E'=>0.05,'F'=>0.01,
    ];

    public static function gradeMultiplier(?string $grade): float
    {
        return self::GRADE_MULTIPLIERS[$grade ?? ''] ?? 1.0;
    }

    public static function adjustedIdealRisk(float $baseRisk, ?string $grade): float
    {
        return $baseRisk * self::gradeMultiplier($grade);
    }

    public static function outcome(?float $r): string
    {
        if ($r === null) return 'Breakeven';
        if ($r > 0.1) return 'Win';
        if ($r < -0.1) return 'Loss';
        return 'Breakeven';
    }

    public static function idealStopLoss(float $entry, string $side, float $quantity, float $idealRisk, float $fee = 0, float $multiplier = 1): float
    {
        if ($quantity <= 0 || $idealRisk <= 0 || $multiplier <= 0) return $entry;
        $risk = max(0.0, $idealRisk - $fee);
        $distance = $risk / ($quantity * $multiplier);
        return $side === 'short' ? $entry + $distance : $entry - $distance;
    }
}
