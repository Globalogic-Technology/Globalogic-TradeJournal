<?php
$esc=static fn($v)=>e($v);
$resource=$resource??'';$rows=$rows??[];$fields=$form['fields']??[];$action=$form['action']??'';
$editId=(int)($_GET['edit']??0);$edit=null;
foreach($rows as $r) if((int)($r['id']??0)===$editId){$edit=$r;break;}
$labels=['trading_system_id'=>'Trading system','strategy_id'=>'Strategy','asset_id'=>'Asset','account_id'=>'Account','default_system_id'=>'Default system','name'=>'Name','description'=>'Description','ideal_risk'=>'Ideal risk','risk_tolerance'=>'Risk tolerance (%)','symbol'=>'Symbol','configuration'=>'Configuration (JSON)','fee_type'=>'Fee type','fee_amount'=>'Fee amount','fee_currency'=>'Fee currency','start_time'=>'Start time','end_time'=>'End time','timezone'=>'Time zone','initial_balance'=>'Balance','currency'=>'Currency'];
$fieldClass=static function(string $f):string{return in_array($f,['description','configuration'],true)?'wide':'';};
$isAccount=$resource==='account';
$accounts=$accounts??[];$systems=$systems??[];
?>
<div class="page-title"><?= $esc($title??'Configuration') ?></div>
<p class="page-subtitle">Configure the values used by your trading journal and risk calculations.</p>
<div class="card form-card">
  <div class="section-heading">
    <div><h2><?= $edit?'Edit':'Configure' ?> <?= $esc(rtrim($title??'Configuration','s')) ?></h2><div class="sub"><?= $isAccount?'Select an account to load its current configuration, then save your changes.':'Keep related settings together so configuration can be updated quickly.' ?></div></div>
    <?php if($edit):?><a class="secondary-link" href="<?= $esc($action) ?>">New</a><?php endif;?>
  </div>
  <form method="post" action="<?= $esc($action) ?>" id="configuration-form">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="save">
    <?php if($edit):?><input type="hidden" name="id" value="<?=e($edit['id'])?>"><?php endif;?>
    <div class="grid config-grid">
      <?php foreach($fields as $field):?>
        <p class="<?= $fieldClass($field) ?>">
          <label><?= $esc($labels[$field]??ucwords(str_replace('_',' ',$field))) ?></label>
          <?php if($field==='description'):?>
            <textarea name="description" placeholder="Optional description..."><?= $esc($edit['description']??'') ?></textarea>
          <?php elseif($field==='configuration'):?>
            <textarea name="configuration" placeholder='{"contract_size":100000,"pip_size":0.0001}'><?= $esc($edit['configuration']??'') ?></textarea>
          <?php elseif($field==='trading_system_id'):?>
            <select name="trading_system_id" required><option value="">Select...</option><?php foreach($systems as $x):?><option value="<?=$x['id']?>" <?=((int)($edit['trading_system_id']??0)===(int)$x['id'])?'selected':''?>><?=$esc($x['name'])?></option><?php endforeach;?></select>
          <?php elseif($field==='asset_id'):?>
            <select name="asset_id" required><option value="">Select...</option><?php foreach(($assets??[]) as $x):?><option value="<?=$x['id']?>" <?=((int)($edit['asset_id']??0)===(int)$x['id'])?'selected':''?>><?=$esc($x['symbol'].' — '.$x['name'])?></option><?php endforeach;?></select>
          <?php elseif($field==='account_id'):?>
            <select name="account_id" id="account-config-account" required>
              <option value="">Select account...</option>
              <?php foreach($accounts as $x):?><option value="<?=$x['id']?>" <?=((int)($edit['id']??0)===(int)$x['id']||((int)($edit['account_id']??0)===(int)$x['id']))?'selected':''?>><?=$esc($x['name'])?></option><?php endforeach;?>
            </select>
          <?php elseif($field==='default_system_id'):?>
            <select name="default_system_id" id="account-config-default-system"><option value="">No default system</option><?php foreach($systems as $x):?><option value="<?=$x['id']?>" <?=((int)($edit['default_system_id']??0)===(int)$x['id'])?'selected':''?>><?=$esc($x['name'])?></option><?php endforeach;?></select>
          <?php elseif($field==='timezone'):?>
            <select name="timezone" required><?php foreach(($timezones??['UTC']) as $tz):?><option value="<?=$esc($tz)?>" <?= (($edit['timezone']??'UTC')===$tz)?'selected':''?>><?=$esc($tz)?></option><?php endforeach;?></select>
          <?php elseif($field==='start_time'||$field==='end_time'):?>
            <input type="time" name="<?=$field?>" value="<?=$esc(isset($edit[$field])?substr((string)$edit[$field],0,5):'')?>" required>
          <?php elseif($field==='ideal_risk'||$field==='fee_amount'||$field==='initial_balance'):?>
            <input type="number" step="0.00000001" min="0" name="<?=$field?>" value="<?=$esc($edit[$field]??'0')?>" required>
          <?php elseif($field==='risk_tolerance'):?>
            <input type="number" step="0.01" min="0" max="100" name="risk_tolerance" value="<?=$esc($edit['risk_tolerance']??'10')?>" required>
          <?php elseif($field==='currency'||$field==='fee_currency'):?>
            <input name="<?=$field?>" maxlength="3" value="<?=$esc($edit[$field]??'USD')?>" required>
          <?php else:?>
            <input name="<?=$field?>" value="<?=$esc($edit[$field]??'')?>" required>
          <?php endif;?>
        </p>
      <?php endforeach;?>
    </div>
    <div class="actions"><button><?= $edit?'Save changes':'Save configuration' ?></button><?php if($edit):?><a class="secondary-link" href="<?= $esc($action) ?>">Cancel</a><?php endif;?></div>
  </form>
</div>
<?php if($isAccount):
  $accountMap=[];
  foreach($accounts as $a){
    $accountMap[(string)$a['id']]=[
      'id'=>(int)$a['id'],
      'name'=>(string)$a['name'],
      'currency'=>(string)($a['currency']??'USD'),
      'initial_balance'=>(float)($a['initial_balance']??0),
      'default_system_id'=>$a['default_system_id']??null,
      'ideal_risk'=>(float)($a['ideal_risk']??0),
      'risk_tolerance'=>(float)($a['risk_tolerance']??10)
    ];
  }
?>
<script nonce="<?=e(csp_nonce())?>">
(function(){
  const accounts=<?=json_encode($accountMap,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
  const select=document.getElementById('account-config-account');
  if(!select)return;
  const form=document.getElementById('configuration-form');
  const field=name=>form?.querySelector('[name="'+name+'"]');
  function loadAccount(){
    const account=accounts[select.value];
    if(!account)return;
    const balance=field('initial_balance'),currency=field('currency'),system=field('default_system_id'),ideal=field('ideal_risk'),tolerance=field('risk_tolerance');
    if(balance)balance.value=account.initial_balance;
    if(currency)currency.value=account.currency;
    if(system)system.value=account.default_system_id==null?'':String(account.default_system_id);
    if(ideal)ideal.value=account.ideal_risk;
    if(tolerance)tolerance.value=account.risk_tolerance;
  }
  select.addEventListener('change',loadAccount);
  if(select.value)loadAccount();
})();
</script>
<?php endif;?>
<div class="card table-wrap">
  <div class="section-heading"><div><h2>Existing configuration</h2><div class="sub"><?= $isAccount?'Select Edit to open an account, or use the account selector above to load it immediately.':'Click Edit to load a record into the form above.' ?></div></div></div>
  <table><thead><tr>
    <?php if($resource==='systems'):?><th>Name</th><th>Ideal risk</th><th>Tolerance</th><th>Description</th>
    <?php elseif($resource==='strategies'):?><th>Name</th><th>System</th><th>Description</th>
    <?php elseif($resource==='assets'):?><th>Symbol</th><th>Name</th><th>Configuration</th>
    <?php elseif($resource==='fees'):?><th>Asset</th><th>Type</th><th>Amount</th><th>Currency</th>
    <?php elseif($resource==='sessions'):?><th>Name</th><th>Start</th><th>End</th><th>Time zone</th>
    <?php elseif($resource==='risk'):?><th>Account</th><th>System</th><th>Ideal risk</th><th>Tolerance</th>
    <?php else:?><th>Account</th><th>Balance</th><th>Currency</th><th>Default system</th><th>Ideal risk</th><th>Tolerance</th><?php endif;?><th>Actions</th>
  </tr></thead><tbody>
  <?php foreach($rows as $r):?><tr>
    <?php if($resource==='systems'):?><td><?=$esc($r['name'])?></td><td><?=number_format((float)$r['ideal_risk'],2)?></td><td><?=number_format((float)$r['risk_tolerance'],2)?>%</td><td><?=$esc($r['description'])?></td>
    <?php elseif($resource==='strategies'):?><td><?=$esc($r['name'])?></td><td><?=$esc($r['system_name'])?></td><td><?=$esc($r['description'])?></td>
    <?php elseif($resource==='assets'):?><td><?=$esc($r['symbol'])?></td><td><?=$esc($r['name'])?></td><td><code><?=$esc($r['configuration'])?></code></td>
    <?php elseif($resource==='fees'):?><td><?=$esc($r['asset_symbol'])?></td><td><?=$esc($r['fee_type'])?></td><td><?=number_format((float)$r['fee_amount'],8)?></td><td><?=$esc($r['fee_currency'])?></td>
    <?php elseif($resource==='sessions'):?><td><?=$esc($r['name'])?></td><td><?=$esc(substr($r['start_time'],0,5))?></td><td><?=$esc(substr($r['end_time'],0,5))?></td><td><?=$esc($r['timezone'])?></td>
    <?php elseif($resource==='risk'):?><td><?=$esc($r['account_name']??'Global')?></td><td><?=$esc($r['system_name']??'Global')?></td><td><?=number_format((float)$r['ideal_risk'],2)?></td><td><?=number_format((float)$r['risk_tolerance'],2)?>%</td>
    <?php else:?><td><?=$esc($r['name'])?></td><td><?=number_format((float)$r['initial_balance'],2)?></td><td><?=$esc($r['currency'])?></td><td><?=$esc($r['default_system_name']??'—')?></td><td><?=number_format((float)$r['ideal_risk'],2)?></td><td><?=number_format((float)$r['risk_tolerance'],2)?>%</td><?php endif;?>
    <td class="actions"><a href="<?=$esc($action)?>?edit=<?=$r['id']?>">Edit</a><form method="post" action="<?=$esc($action)?>" onsubmit="return confirm('Delete this configuration?')"><?=csrf_field()?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="danger">Delete</button></form></td>
  </tr><?php endforeach;?>
  <?php if(!$rows):?><tr><td colspan="8" class="muted">No configuration records yet.</td></tr><?php endif;?>
  </tbody></table>
</div>
