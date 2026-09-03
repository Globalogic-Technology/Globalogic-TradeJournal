<?php
$v=$form?:[]; $edit=!empty($form); $defaults=$newTradeDefaults??[]; $selectedAccount=(int)($v['account_id']??$defaults['account_id']??0); $selectedSystem=(int)($v['trading_system_id']??$defaults['trading_system_id']??0); $selectedStrategy=(int)($v['strategy_id']??0); $selectedAsset=(int)($v['asset_id']??0); $selectedSession=(int)($v['trading_session_id']??0); $risk=$editRisk??null;
?>
<form method="post">
<input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
<input type="hidden" name="action" value="save">
<?php if($edit):?><input type="hidden" name="id" value="<?=e($v['id'])?>"><?php endif;?>
<div class="grid">
<p><label>Account</label><select name="account_id" required><?php foreach($accounts as $a):?><option value="<?=e($a['id'])?>" data-default-system="<?=e($a['default_system_id']??'')?>" <?=$selectedAccount===(int)$a['id']?'selected':''?>><?=e($a['name'])?> (<?=e($a['currency'])?>)</option><?php endforeach;?></select></p>
<p><label>Trading system</label><select name="trading_system_id"><option value="">None</option><?php foreach($systems as $s):?><option value="<?=e($s['id'])?>" <?=$selectedSystem===(int)$s['id']?'selected':''?>><?=e($s['name'])?> — risk <?=number_format((float)$s['ideal_risk'],2)?></option><?php endforeach;?></select></p>
<p><label>Strategy</label><select name="strategy_id"><option value="">None</option><?php foreach($strategies as $s):?><option value="<?=e($s['id'])?>" data-system="<?=e($s['trading_system_id'])?>" <?=$selectedStrategy===(int)$s['id']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></p>
<p><label>Asset</label><select name="asset_id"><option value="">None</option><?php foreach($assets as $a):?><option value="<?=e($a['id'])?>" data-symbol="<?=e($a['symbol'])?>" <?=$selectedAsset===(int)$a['id']?'selected':''?>><?=e($a['symbol'])?> — <?=e($a['name'])?></option><?php endforeach;?></select></p>
<p><label>Trading session</label><select name="trading_session_id"><option value="">None</option><?php foreach($sessions as $s):?><option value="<?=e($s['id'])?>" <?=$selectedSession===(int)$s['id']?'selected':''?>><?=e($s['name'])?> (<?=e($s['timezone'])?>)</option><?php endforeach;?></select></p>
<p><label>Ticket</label><input name="ticket" value="<?=e($v['ticket']??'')?>"></p>
<p><label>Symbol</label><input id="trade-symbol" name="symbol" value="<?=e($v['symbol']??'')?>" required></p>
<p><label>Side</label><select name="side"><option value="long" <?=($v['side']??'long')==='long'?'selected':''?>>Long</option><option value="short" <?=($v['side']??'')==='short'?'selected':''?>>Short</option></select></p>
<p><label>Status</label><select name="status"><option value="closed" <?=($v['status']??'closed')==='closed'?'selected':''?>>Closed</option><option value="open" <?=($v['status']??'')==='open'?'selected':''?>>Open</option></select></p>
<p><label>Opening time</label><input type="datetime-local" name="opened_at" value="<?=isset($v['opened_at'])?e(date('Y-m-d\\TH:i',strtotime($v['opened_at']))):''?>" required></p>
<p><label>Closing time</label><input type="datetime-local" name="closed_at" value="<?=!empty($v['closed_at'])?e(date('Y-m-d\\TH:i',strtotime($v['closed_at']))):''?>"></p>
<p><label>Quantity</label><input id="trade-quantity" type="number" step=".00000001" min=".00000001" name="quantity" value="<?=e($v['quantity']??'')?>" required></p>
<p><label>Entry price</label><input id="trade-entry" type="number" step=".0000000001" min="0" name="entry_price" value="<?=e($v['entry_price']??'')?>" required></p>
<p><label>Stop loss</label><input id="trade-stop" type="number" step=".0000000001" min="0" name="stop_loss" value="<?=e($v['stop_loss']??'')?>"></p>
<p><label>Take profit</label><input type="number" step=".0000000001" min="0" name="take_profit" value="<?=e($v['take_profit']??'')?>"></p>
<p><label>Exit price</label><input type="number" step=".0000000001" min="0" name="exit_price" value="<?=e($v['exit_price']??'')?>"></p>
<p><label>Fees</label><input type="number" step=".00000001" min="0" name="fees" value="<?=e($v['fees']??'0')?>"></p>
</div>
<p><label>Notes</label><textarea name="notes"><?=e($v['notes']??'')?></textarea></p>
<?php if($risk):?><div class="card"><strong>Risk calculation</strong><div class="grid"><p>Ideal risk: <strong><?=number_format($risk['ideal_risk'],2)?></strong></p><p>Actual risk: <strong><?= $risk['actual_risk']===null?'—':number_format($risk['actual_risk'],2)?></strong></p><p>Risk %: <strong><?= $risk['risk_percent']===null?'—':number_format($risk['risk_percent'],2).'%';?></strong></p><p>Position size: <strong><?= $risk['position_size']===null?'—':number_format($risk['position_size'],4)?></strong></p><p>Expected R: <strong><?= $risk['expected_r']===null?'—':number_format($risk['expected_r'],2).'R'?></strong></p><p>R multiple: <strong><?= $risk['r_multiple']===null?'—':number_format($risk['r_multiple'],2).'R'?></strong></p><p>Risk deviation: <strong><?= $risk['risk_deviation']===null?'—':number_format($risk['risk_deviation'],2).'%';?></strong></p><p>Balance after: <strong><?=number_format($risk['balance_after'],2)?></strong></p></div></div><?php endif;?>
<button><?=$edit?'Save changes':'Add trade'?></button><?php if($edit):?> <a href="/trades">Cancel</a><?php endif;?>
</form>
<script>
(function(){
 const account=document.querySelector('[name="account_id"]');
 const system=document.querySelector('[name="trading_system_id"]');
 const strategy=document.querySelector('[name="strategy_id"]');
 const asset=document.querySelector('[name="asset_id"]');
 const symbol=document.getElementById('trade-symbol');
 if(account&&system){account.addEventListener('change',function(){if(!system.value){const o=account.selectedOptions[0];if(o&&o.dataset.defaultSystem)system.value=o.dataset.defaultSystem;filterStrategies();}});}
 function filterStrategies(){if(!system||!strategy)return;const id=system.value;[...strategy.options].forEach(o=>{if(o.value)o.hidden=id!==''&&o.dataset.system!==id;});if(strategy.selectedOptions[0]&&strategy.selectedOptions[0].hidden)strategy.value='';}
 if(system&&strategy){system.addEventListener('change',filterStrategies);filterStrategies();}
 if(asset&&symbol){asset.addEventListener('change',function(){const o=asset.selectedOptions[0];if(o&&o.dataset.symbol&&!symbol.value)symbol.value=o.dataset.symbol;});}
})();
</script>
