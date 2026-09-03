<h1>Trades</h1>
<?php if($editTrade):?><div class="card"><h2>Edit trade #<?=e($editTrade['id'])?></h2><?php $form=$editTrade; include __DIR__.'/trade_form.php';?></div><?php endif;?>
<div class="card"><h2>Add trade</h2><?php $form=null;include __DIR__.'/trade_form.php';?></div>
<div class="card">
<h2>Trade history (<?=e($total)?>)</h2>
<form method="get" class="grid">
<p><label>Symbol</label><input name="symbol" value="<?=e($filters['symbol'])?>"></p>
<p><label>Side</label><select name="side"><option value="">All</option><option value="long" <?=$filters['side']==='long'?'selected':''?>>Long</option><option value="short" <?=$filters['side']==='short'?'selected':''?>>Short</option></select></p>
<p><label>Status</label><select name="status"><option value="">All</option><option value="open" <?=$filters['status']==='open'?'selected':''?>>Open</option><option value="closed" <?=$filters['status']==='closed'?'selected':''?>>Closed</option></select></p>
<p><label>System</label><select name="trading_system_id"><option value="">All</option><?php foreach($systems as $s):?><option value="<?=e($s['id'])?>" <?=((int)($filters['trading_system_id']??0)===(int)$s['id'])?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></p>
<p style="align-self:end"><button>Filter</button></p>
</form>
<div class="table-wrap"><table class="trade-history"><tr><th>Ticket</th><th>Symbol</th><th>Side</th><th>Status</th><th>System</th><th>Strategy</th><th>Session</th><th>Opened</th><th>Quantity</th><th>Entry</th><th>Exit</th><th>P&amp;L</th><th>Ideal Risk</th><th>Actual Risk</th><th>Expected R</th><th>R Multiple</th><th>Risk Dev.</th><th>Balance After</th><th>Journal</th><th></th></tr>
<?php foreach($trades as $t):$r=$t['risk'];?><tr>
<td><?=e($t['ticket']??'')?></td><td><?=e($t['symbol'])?></td><td><?=e($t['side'])?></td><td><?=e($t['status'])?></td><td><?=e($t['system_name']??'—')?></td><td><?=e($t['strategy_name']??'—')?></td><td><?=e($t['session_name']??'—')?></td><td><?=e($t['opened_at'])?></td><td class="quantity"><?=number_format((float)$t['quantity'],4,'.','')?></td><td><?=number_format((float)$t['entry_price'],5,'.','')?></td><td><?=$t['exit_price']===null?'—':number_format((float)$t['exit_price'],5,'.','')?></td><td class="<?=($t['pnl']===null?'':($t['pnl']>=0?'positive':'negative'))?>"><?=$t['pnl']===null?'—':number_format($t['pnl'],2)?></td>
<td><?=number_format($r['ideal_risk'],2)?></td><td><?=$r['actual_risk']===null?'—':number_format($r['actual_risk'],2)?></td><td><?=$r['expected_r']===null?'—':number_format($r['expected_r'],2).'R'?></td><td><?=$r['r_multiple']===null?'—':number_format($r['r_multiple'],2).'R'?></td><td><?=$r['risk_deviation']===null?'—':number_format($r['risk_deviation'],2).'%';?></td><td><?=number_format($r['balance_after'],2)?></td>
<td><a href="/trades?journal=<?=e($t['id'])?>">Review</a></td>
<td><a href="?edit=<?=e($t['id'])?>">Edit</a> <form method="post" style="display:inline" onsubmit="return confirm('Delete trade?')"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($t['id'])?>"><button class="danger">Delete</button></form></td>
</tr><?php endforeach;?>
<?php if(!$trades):?><tr><td colspan="20" class="muted">No trades found.</td></tr><?php endif;?></table></div>
<?php if($pages>1):?><p><?php for($p=1;$p<=$pages;$p++):?><a href="?<?=http_build_query(array_merge($filters,['page'=>$p]))?>"><?=$p?></a> <?php endfor;?></p><?php endif;?>
</div>
<style>
.trade-history th,.trade-history td{vertical-align:middle}
.trade-history th:nth-child(9),.trade-history td:nth-child(9){min-width:100px;text-align:right}
.trade-history .quantity{font-variant-numeric:tabular-nums}
</style>
