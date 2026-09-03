<?php
$v=$form?:[];
$edit=!empty($form);
$defaults=$newTradeDefaults??[];
$selectedAccount=(int)($v['account_id']??$defaults['account_id']??0);
$selectedSystem=(int)($v['trading_system_id']??$defaults['trading_system_id']??0);
$selectedStrategy=(int)($v['strategy_id']??0);
$selectedAsset=(int)($v['asset_id']??0);
$selectedSession=(int)($v['trading_session_id']??0);
if(!$selectedSystem && $selectedAccount){foreach($accounts as $a){if((int)$a['id']===$selectedAccount){$selectedSystem=(int)($a['default_system_id']??0);break;}}}
$risk=$editRisk??null;
$initialRisk=(float)($risk['ideal_risk']??0);
$initialBalance=(float)($risk['balance_before']??0);
?>
<form method="post" id="trade-form">
<input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
<input type="hidden" name="action" value="save">
<?php if($edit):?><input type="hidden" name="id" value="<?=e($v['id'])?>"><?php endif;?>
<div class="grid">
<p><label>Account</label><select name="account_id" required><?php foreach($accounts as $a):?><option value="<?=e($a['id'])?>" data-default-system="<?=e($a['default_system_id']??'')?>" data-ideal-risk="<?=e($a['ideal_risk']??0)?>" data-balance="<?=e($a['initial_balance']??0)?>" <?=$selectedAccount===(int)$a['id']?'selected':''?>><?=e($a['name'])?> (<?=e($a['currency'])?>)</option><?php endforeach;?></select></p>
<p><label>Trading system</label><select name="trading_system_id"><option value="">None</option><?php foreach($systems as $s):?><option value="<?=e($s['id'])?>" data-ideal-risk="<?=e($s['ideal_risk'])?>" data-risk-tolerance="<?=e($s['risk_tolerance'])?>" <?=$selectedSystem===(int)$s['id']?'selected':''?>><?=e($s['name'])?> — risk <?=number_format((float)$s['ideal_risk'],2)?></option><?php endforeach;?></select></p>
<p><label>Strategy</label><select name="strategy_id"><option value="">None</option><?php foreach($strategies as $s):?><option value="<?=e($s['id'])?>" data-system="<?=e($s['trading_system_id'])?>" <?=$selectedStrategy===(int)$s['id']?'selected':''?>><?=e($s['name'])?></option><?php endforeach;?></select></p>
<p><label>Asset</label><select name="asset_id"><option value="">None</option><?php foreach($assets as $a):?><option value="<?=e($a['id'])?>" data-symbol="<?=e($a['symbol'])?>" data-configuration="<?=e(json_encode($a['configuration']??null))?>" <?=$selectedAsset===(int)$a['id']?'selected':''?>><?=e($a['symbol'])?> — <?=e($a['name'])?></option><?php endforeach;?></select></p>
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
<p><label>Exit price</label><input id="trade-exit" type="number" step=".0000000001" min="0" name="exit_price" value="<?=e($v['exit_price']??'')?>"></p>
<p><label>Fees</label><input id="trade-fees" type="number" step=".00000001" min="0" name="fees" value="<?=e($v['fees']??'0')?>"></p>
</div>
<p><label>Notes</label><textarea name="notes"><?=e($v['notes']??'')?></textarea></p>
<div class="card" id="risk-card">
<strong>Risk calculation</strong>
<div class="grid">
<p>Ideal risk: <strong id="risk-ideal"><?=number_format($initialRisk,2)?></strong></p>
<p>Actual risk: <strong id="risk-actual"><?= $risk && $risk['actual_risk']!==null?number_format($risk['actual_risk'],2):'—'?></strong></p>
<p>Risk %: <strong id="risk-percent"><?= $risk && $risk['risk_percent']!==null?number_format($risk['risk_percent'],2).'%':'—'?></strong></p>
<p>Position size: <strong id="risk-position"><?= $risk && $risk['position_size']!==null?number_format($risk['position_size'],4):'—'?></strong></p>
<p>Expected R: <strong id="risk-expected"><?= $risk && $risk['expected_r']!==null?number_format($risk['expected_r'],2).'R':'—'?></strong></p>
<p>R multiple: <strong id="risk-multiple"><?= $risk && $risk['r_multiple']!==null?number_format($risk['r_multiple'],2).'R':'—'?></strong></p>
<p>Risk deviation: <strong id="risk-deviation"><?= $risk && $risk['risk_deviation']!==null?number_format($risk['risk_deviation'],2).'%':'—'?></strong></p>
<p>Balance after: <strong id="risk-balance"><?=number_format($risk['balance_after']??$initialBalance,2)?></strong></p>
</div>
<p id="risk-help" style="margin-bottom:0"></p>
</div>
<button><?=$edit?'Save changes':'Add trade'?></button><?php if($edit):?> <a href="/trades">Cancel</a><?php endif;?>
</form>
<script>
(function(){
 const form=document.getElementById('trade-form');
 const account=form.querySelector('[name="account_id"]');
 const system=form.querySelector('[name="trading_system_id"]');
 const strategy=form.querySelector('[name="strategy_id"]');
 const asset=form.querySelector('[name="asset_id"]');
 const symbol=document.getElementById('trade-symbol');
 const quantity=document.getElementById('trade-quantity');
 const entry=document.getElementById('trade-entry');
 const stop=document.getElementById('trade-stop');
 const exit=document.getElementById('trade-exit');
 const fees=document.getElementById('trade-fees');
 const side=form.querySelector('[name="side"]');
 const el={ideal:document.getElementById('risk-ideal'),actual:document.getElementById('risk-actual'),percent:document.getElementById('risk-percent'),position:document.getElementById('risk-position'),expected:document.getElementById('risk-expected'),multiple:document.getElementById('risk-multiple'),deviation:document.getElementById('risk-deviation'),balance:document.getElementById('risk-balance'),help:document.getElementById('risk-help')};
 function num(v){const n=parseFloat(v);return Number.isFinite(n)?n:null;}
 function fmt(n,d){return Number.isFinite(n)?n.toLocaleString(undefined,{minimumFractionDigits:d,maximumFractionDigits:d}):'—';}
 function selectedIdeal(){const so=system.selectedOptions[0];if(system.value&&so)return num(so.dataset.idealRisk)||0;const ao=account.selectedOptions[0];return ao?num(ao.dataset.idealRisk)||0:0;}
 function config(){const o=asset.selectedOptions[0];if(!o||!o.value)return {contract_size:1,point_value:1};try{const c=JSON.parse(o.dataset.configuration||'null');return c&&typeof c==='object'?c:{contract_size:1,point_value:1};}catch(e){return {contract_size:1,point_value:1};}}
 function filterStrategies(){if(!strategy)return;const id=system.value;[...strategy.options].forEach(o=>{if(o.value)o.hidden=id!==''&&o.dataset.system!==id;});if(strategy.selectedOptions[0]&&strategy.selectedOptions[0].hidden)strategy.value='';}
 function recalc(){
   const q=num(quantity.value), en=num(entry.value), sl=num(stop.value), ex=num(exit.value), fee=num(fees.value)||0;
   const ideal=selectedIdeal(), balance=num(account.selectedOptions[0]?.dataset.balance)||0;
   const c=config(), contract=num(c.contract_size)||1, point=num(c.point_value)||1, multiplier=Math.max(0,contract*point);
   el.ideal.textContent=fmt(ideal,2);
   const riskPerUnit=(en!==null&&sl!==null)?Math.abs(en-sl)*multiplier:null;
   const actual=(riskPerUnit!==null&&q!==null)?riskPerUnit*q:null;
   const riskPct=(actual!==null&&balance>0)?actual/balance*100:null;
   const position=(ideal>0&&riskPerUnit!==null&&riskPerUnit>0)?ideal/riskPerUnit:null;
   let pnl=null;
   if(en!==null&&ex!==null&&q!==null){pnl=((side.value==='short'?(en-ex):(ex-en))*q)-fee;}
   const expected=(pnl!==null&&ideal>0)?pnl/ideal:null;
   const multiple=(pnl!==null&&actual!==null&&actual>0)?pnl/actual:null;
   const deviation=(actual!==null&&ideal>0)?(actual-ideal)/ideal*100:null;
   const after=pnl===null?balance:balance+pnl;
   el.actual.textContent=fmt(actual,2);el.percent.textContent=riskPct===null?'—':fmt(riskPct,2)+'%';el.position.textContent=fmt(position,4);el.expected.textContent=expected===null?'—':fmt(expected,2)+'R';el.multiple.textContent=multiple===null?'—':fmt(multiple,2)+'R';el.deviation.textContent=deviation===null?'—':fmt(deviation,2)+'%';el.balance.textContent=fmt(after,2);
   if(ideal<=0) el.help.textContent='Configure an Ideal Risk on the selected trading system or account to calculate Expected R and Risk Deviation.';
   else if(sl===null||en===null||q===null) el.help.textContent='Enter Quantity, Entry price and Stop loss to calculate Actual Risk.';
   else el.help.textContent='';
 }
 function applyAccountDefault(){if(!system.value){const o=account.selectedOptions[0];if(o&&o.dataset.defaultSystem){system.value=o.dataset.defaultSystem;}}filterStrategies();recalc();}
 account.addEventListener('change',applyAccountDefault);
 system.addEventListener('change',function(){filterStrategies();recalc();});
 strategy.addEventListener('change',recalc);
 asset.addEventListener('change',function(){const o=asset.selectedOptions[0];if(o&&o.dataset.symbol&&!symbol.value)symbol.value=o.dataset.symbol;recalc();});
 [quantity,entry,stop,exit,fees,symbol].forEach(x=>x.addEventListener('input',recalc));
 side.addEventListener('change',recalc);
 filterStrategies();recalc();
})();
</script>
