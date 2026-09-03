<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

final class AnalyticsService
{
    public function build(PDO $db, int $userId, array $filters = []): array
    {
        $where = ['t.user_id = ?', 't.status = "closed"', 't.exit_price IS NOT NULL'];
        $params = [$userId];
        if (!empty($filters['system_id'])) { $where[] = 't.trading_system_id = ?'; $params[] = (int)$filters['system_id']; }
        if (!empty($filters['session_id'])) { $where[] = 't.trading_session_id = ?'; $params[] = (int)$filters['session_id']; }
        if (!empty($filters['date_from'])) { $where[] = 'DATE(t.closed_at) >= ?'; $params[] = $filters['date_from']; }
        if (!empty($filters['date_to'])) { $where[] = 'DATE(t.closed_at) <= ?'; $params[] = $filters['date_to']; }

        $sql = 'SELECT t.*, a.name account_name, a.currency, a.initial_balance,
                       COALESCE(ts.name, "Unassigned") system_name,
                       COALESCE(st.name, "Unassigned") strategy_name,
                       COALESCE(se.name, "Unassigned") session_name,
                       COALESCE(asset.configuration, NULL) asset_configuration
                FROM trades t
                INNER JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id
                LEFT JOIN trading_systems ts ON ts.id=t.trading_system_id AND ts.user_id=t.user_id
                LEFT JOIN strategies st ON st.id=t.strategy_id AND st.user_id=t.user_id
                LEFT JOIN trading_sessions se ON se.id=t.trading_session_id AND se.user_id=t.user_id
                LEFT JOIN assets asset ON asset.id=t.asset_id AND asset.user_id=t.user_id
                WHERE '.implode(' AND ', $where).' ORDER BY t.account_id, t.closed_at ASC, t.id ASC';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $trades = $stmt->fetchAll();

        $riskService = new TradeRiskService();
        $accountBalances = [];
        $rows = [];
        foreach ($trades as $trade) {
            $accountId = (int)$trade['account_id'];
            if (!array_key_exists($accountId, $accountBalances)) $accountBalances[$accountId] = (float)$trade['initial_balance'];
            $pnl = $this->pnl($trade);
            $trade['pnl'] = $pnl;
            $risk = $riskService->calculate($db, $userId, $trade, $accountBalances[$accountId]);
            $trade['risk'] = $risk;
            $trade['result'] = $pnl > 0 ? 'Win' : ($pnl < 0 ? 'Loss' : 'Breakeven');
            $trade['weekday'] = date('l', strtotime((string)$trade['closed_at']));
            $trade['gross_pnl'] = $pnl + (float)$trade['fees'];
            $accountBalances[$accountId] += $pnl;
            $rows[] = $trade;
        }

        $pnls = array_map(static fn($r) => (float)$r['pnl'], $rows);
        $wins = array_values(array_filter($pnls, static fn($v) => $v > 0));
        $losses = array_values(array_filter($pnls, static fn($v) => $v < 0));
        $grossProfit = array_sum($wins);
        $grossLoss = abs(array_sum($losses));
        $count = count($pnls);
        $netPnl = array_sum($pnls);
        $winRate = $count ? count($wins) / $count * 100 : null;
        $profitFactor = $grossLoss > 0 ? $grossProfit / $grossLoss : null;
        $avgWin = $wins ? $grossProfit / count($wins) : null;
        $avgLoss = $losses ? $grossLoss / count($losses) : null;
        $expectancy = $count ? $netPnl / $count : null;

        $equity = $peak = $maxDrawdown = 0.0;
        $equityCurve = [];
        foreach ($rows as $r) {
            $equity += (float)$r['pnl'];
            $peak = max($peak, $equity);
            $dd = $peak - $equity;
            $maxDrawdown = max($maxDrawdown, $dd);
            $equityCurve[] = ['date' => date('Y-m-d', strtotime((string)$r['closed_at'])), 'trade_id' => (int)$r['id'], 'equity' => round($equity, 8)];
        }

        $rValues = array_values(array_filter(array_map(static function($r) {
            return $r['risk']['expected_r'] === null ? null : (float)$r['risk']['expected_r'];
        }, $rows), static fn($v) => $v !== null));
        $sharpe = $this->sharpe($rValues);

        return [
            'trades' => $rows,
            'summary' => [
                'count'=>$count,'wins'=>count($wins),'losses'=>count($losses),'breakeven'=>$count-count($wins)-count($losses),
                'net_pnl'=>$netPnl,'gross_profit'=>$grossProfit,'gross_loss'=>$grossLoss,'win_rate'=>$winRate,
                'profit_factor'=>$profitFactor,'avg_win'=>$avgWin,'avg_loss'=>$avgLoss,'expectancy'=>$expectancy,
                'max_drawdown'=>$maxDrawdown,'sharpe'=>$sharpe,'fees'=>array_sum(array_map(static fn($r)=>(float)$r['fees'],$rows)),
            ],
            'equity_curve'=>$equityCurve,
            'by_system'=>$this->group($rows, 'system_name'),
            'by_strategy'=>$this->group($rows, 'strategy_name'),
            'by_session'=>$this->group($rows, 'session_name'),
            'by_weekday'=>$this->group($rows, 'weekday'),
            'risk'=>$this->riskSummary($rows),
            'fee_impact'=>$this->feeImpact($rows),
        ];
    }

    private function pnl(array $t): float
    {
        $gross = $t['side'] === 'long'
            ? ((float)$t['exit_price'] - (float)$t['entry_price']) * (float)$t['quantity']
            : ((float)$t['entry_price'] - (float)$t['exit_price']) * (float)$t['quantity'];
        return $gross - (float)$t['fees'];
    }

    private function sharpe(array $values): ?float
    {
        $n=count($values); if ($n<2) return null;
        $mean=array_sum($values)/$n; $sum=0.0;
        foreach($values as $v) $sum += ($v-$mean)**2;
        $std=sqrt($sum/($n-1)); return $std>0 ? $mean/$std : null;
    }

    private function group(array $rows, string $key): array
    {
        $groups=[];
        foreach($rows as $r){$name=(string)$r[$key]; if(!isset($groups[$name]))$groups[$name]=['name'=>$name,'trades'=>0,'wins'=>0,'losses'=>0,'net_pnl'=>0.0,'fees'=>0.0,'r_sum'=>0.0,'r_count'=>0,'risk_dev_sum'=>0.0,'risk_dev_count'=>0]; $g=&$groups[$name]; $g['trades']++; $g['net_pnl']+=(float)$r['pnl']; $g['fees']+=(float)$r['fees']; if($r['pnl']>0)$g['wins']++; elseif($r['pnl']<0)$g['losses']++; if($r['risk']['expected_r']!==null){$g['r_sum']+=(float)$r['risk']['expected_r'];$g['r_count']++;} if($r['risk']['risk_deviation']!==null){$g['risk_dev_sum']+=(float)$r['risk']['risk_deviation'];$g['risk_dev_count']++;} unset($g); }
        foreach($groups as &$g){$g['win_rate']=$g['trades']?$g['wins']/$g['trades']*100:null;$g['avg_r']=$g['r_count']?$g['r_sum']/$g['r_count']:null;$g['avg_risk_dev']=$g['risk_dev_count']?$g['risk_dev_sum']/$g['risk_dev_count']:null;unset($g['r_sum'],$g['r_count'],$g['risk_dev_sum'],$g['risk_dev_count']);} unset($g);
        usort($groups, static fn($a,$b)=>$b['net_pnl']<=>$a['net_pnl']); return array_values($groups);
    }

    private function riskSummary(array $rows): array
    {
        $actual=0.0;$ideal=0.0;$dev=[];$over=0;$count=0;$r=[];
        foreach($rows as $x){$z=$x['risk']; if($z['actual_risk']!==null){$actual+=(float)$z['actual_risk'];$count++;if($z['ideal_risk']>0&&$z['actual_risk']>$z['ideal_risk'])$over++;} if($z['ideal_risk']>0)$ideal+=(float)$z['ideal_risk']; if($z['risk_deviation']!==null)$dev[]=(float)$z['risk_deviation']; if($z['r_multiple']!==null)$r[]=(float)$z['r_multiple'];}
        return ['avg_actual_risk'=>$count?$actual/$count:null,'avg_ideal_risk'=>$count?$ideal/$count:null,'avg_risk_deviation'=>$dev?array_sum($dev)/count($dev):null,'over_risk_trades'=>$over,'risk_tracked_trades'=>$count,'avg_r_multiple'=>$r?array_sum($r)/count($r):null];
    }

    private function feeImpact(array $rows): array
    {
        $fees=0.0;$gross=0.0;$net=0.0; foreach($rows as $r){$fees+=(float)$r['fees'];$gross+=(float)$r['gross_pnl'];$net+=(float)$r['pnl'];}
        return ['fees'=>$fees,'gross_pnl'=>$gross,'net_pnl'=>$net,'fee_pct_of_gross_profit'=>$gross>0?$fees/$gross*100:null,'avg_fee_per_trade'=>$rows?$fees/count($rows):null];
    }
}
