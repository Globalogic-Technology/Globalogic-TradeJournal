<?php $title = 'Analytics'; ?>

<h1>Analytics</h1>
<p>Performance across your closed trades.</p>

<div class="stats-grid">
    <div class="card"><span>Closed trades</span><strong><?= (int)$count ?></strong></div>
    <div class="card"><span>Win rate</span><strong><?= $winRate === null ? '—' : number_format($winRate, 2) . '%' ?></strong></div>
    <div class="card"><span>Net P&amp;L</span><strong><?= number_format($netPnl, 2) ?></strong></div>
    <div class="card"><span>Profit factor</span><strong><?= $profitFactor === null ? '—' : number_format($profitFactor, 2) ?></strong></div>
    <div class="card"><span>Max drawdown</span><strong><?= number_format($maxDrawdown, 2) ?></strong></div>
    <div class="card"><span>Trade Sharpe</span><strong><?= $sharpe === null ? '—' : number_format($sharpe, 3) ?></strong></div>
</div>

<div class="card">
    <p>Wins: <?= (int)$wins ?> · Losses: <?= (int)$losses ?></p>
    <p>Gross profit: <?= number_format($grossProfit, 2) ?> · Gross loss: <?= number_format($grossLoss, 2) ?></p>
    <p>Sharpe is trade-level and uses sample standard deviation; it is not annualized.</p>
</div>