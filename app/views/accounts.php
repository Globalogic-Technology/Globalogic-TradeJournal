<?php
$csvTemplates=$csvTemplates??[];$csvFields=class_exists('AccountCsvTemplateService')?AccountCsvTemplateService::FIELDS:[];
$defaultCsv=['symbol'=>'symbol','type'=>'type','opening_time'=>'opening_time_utc','closing_time'=>'closing_time_utc','quantity'=>'lots','entry_price'=>'opening_price','stop_loss'=>'stop_loss','take_profit'=>'take_profit','exit_price'=>'closing_price','profit'=>'profit_usd','fees'=>'commission_usd','close_reason'=>'close_reason','ticket'=>'ticket'];
$templatesByAccount=[];foreach($csvTemplates as $template)$templatesByAccount[(int)$template['account_id']][]=$template;
$editAccount=$editAccount??null;$editCsvTemplate=$editCsvTemplate??null;
?>
<div class="page-title">Accounts</div>
<p class="page-subtitle">Manage your trading accounts and the CSV formats used to import trades.</p>

<div class="card form-card">
  <div class="section-heading"><div><h2><?= $editAccount?'Edit account':'Add account' ?></h2><div class="sub">Create an account once, then reuse it when recording and importing trades.</div></div></div>
  <form method="post">
    <?=csrf_field()?>
    <input type="hidden" name="action" value="<?= $editAccount?'update':'create' ?>">
    <?php if($editAccount): ?><input type="hidden" name="id" value="<?=e($editAccount['id'])?>"><?php endif; ?>
    <div class="grid">
      <p><label>Name</label><input name="name" placeholder="Interactive Brokers" value="<?=e($editAccount['name']??'')?>" required></p>
      <p><label>Currency</label><input name="currency" value="<?=e($editAccount['currency']??'USD')?>" maxlength="3" required></p>
      <p><label>Initial balance</label><input name="initial_balance" type="number" step=".01" min="0" value="<?=e($editAccount['initial_balance']??'0')?>" required></p>
    </div>
    <div class="actions"><button><?= $editAccount?'Update account':'Add account' ?></button><?php if($editAccount): ?><a class="button secondary" href="/accounts">Cancel</a><?php endif; ?></div>
  </form>
</div>

<div class="card form-card">
  <div class="section-heading"><div><h2>Accounts list</h2><div class="sub"><?=count($accounts)?> account(s) configured.</div></div></div>
  <?php if(!$accounts): ?>
    <p class="muted">No accounts configured yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table><thead><tr><th>Account</th><th>Currency</th><th>Initial balance</th><th>CSV templates</th><th>Actions</th></tr></thead><tbody>
      <?php foreach($accounts as $a): $count=count($templatesByAccount[(int)$a['id']]??[]); ?>
        <tr>
          <td><strong><?=e($a['name'])?></strong></td>
          <td><span class="badge"><?=e($a['currency'])?></span></td>
          <td><?=number_format((float)$a['initial_balance'],2)?></td>
          <td><?=number_format($count)?></td>
          <td class="trade-actions">
            <a href="/accounts?edit_account=<?=e($a['id'])?>">Edit</a>
            <form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=e($a['id'])?>"><button class="danger">Delete</button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody></table>
    </div>
  <?php endif; ?>
</div>

<div class="card form-card">
  <div class="section-heading"><div><h2>Trade CSV Import</h2><div class="sub">Create and manage account-specific CSV templates. Each template maps broker headers to the journal's standard trade fields.</div></div></div>
  <?php if(!$accounts): ?><p class="muted">Create an account first, then configure its Trade CSV Import template.</p><?php endif; ?>
  <?php foreach($accounts as $a): $templates=$templatesByAccount[(int)$a['id']]??[]; $editing=$editCsvTemplate&&((int)$editCsvTemplate['account_id']===(int)$a['id']); ?>
    <div class="csv-account-block">
      <div class="section-heading"><div><h3><?=e($a['name'])?></h3><div class="sub"><?=count($templates)?> template(s) configured</div></div><span class="badge"><?=e($a['currency'])?></span></div>
      <?php if($editing): $t=$editCsvTemplate; ?>
        <div class="card">
          <div class="section-heading"><div><h3>Edit CSV template</h3><div class="sub">Update the mapping without creating another template.</div></div></div>
          <form method="post">
            <?=csrf_field()?><input type="hidden" name="action" value="csv_template_save"><input type="hidden" name="account_id" value="<?=e($a['id'])?>"><input type="hidden" name="template_id" value="<?=e($t['id'])?>">
            <div class="grid">
              <p><label>Template name</label><input name="template_name" value="<?=e($t['name'])?>" required></p>
              <p><label>CSV delimiter</label><select name="delimiter_char"><option value="," <?=$t['delimiter_char']===','?'selected':''?>>Comma (,)</option><option value=";" <?=$t['delimiter_char']===';'?'selected':''?>>Semicolon (;)</option><option value="|" <?=$t['delimiter_char']==='|'?'selected':''?>>Pipe (|)</option><option value="\t" <?=$t['delimiter_char']==="\t"?'selected':''?>>Tab</option></select></p>
              <p><label>Date/time zone</label><select name="date_timezone"><?php foreach(['UTC','America/New_York','America/Chicago','America/Los_Angeles','America/Sao_Paulo'] as $tz): ?><option value="<?=e($tz)?>" <?=$t['date_timezone']===$tz?'selected':''?>><?=e($tz)?></option><?php endforeach; ?></select></p>
              <p><label><input type="checkbox" name="has_header" value="1" <?=$t['has_header']?'checked':''?>> CSV has header row</label></p>
              <p><label><input type="checkbox" name="is_default" value="1" <?=$t['is_default']?'checked':''?>> Use as default template</label></p>
            </div>
            <h3>CSV column mapping</h3><p class="sub">Enter the exact CSV header for each journal field. Required fields are marked with *.</p>
            <div class="grid config-grid"><?php foreach($csvFields as $field=>$label): ?><p><label><?=e($label)?><?=in_array($field,['symbol','type','opening_time','closing_time','quantity','entry_price','exit_price','profit','fees','ticket'],true)?' *':''?></label><input name="mapping[<?=e($field)?>]" value="<?=e($t['mapping'][$field]??'')?>" placeholder="Exact CSV header name"></p><?php endforeach; ?></div>
            <div class="actions"><button>Save changes</button><a class="button secondary" href="/accounts">Cancel</a></div>
          </form>
        </div>
      <?php endif; ?>
      <?php if($templates): ?>
        <div class="table-wrap">
          <table><thead><tr><th>Template</th><th>Delimiter</th><th>Header</th><th>Time zone</th><th>Mapped fields</th><th>Actions</th></tr></thead><tbody>
          <?php foreach($templates as $t): $mapped=0;foreach(($t['mapping']??[]) as $column)if(trim((string)$column)!=='')$mapped++; ?>
            <tr>
              <td><strong><?=e($t['name'])?></strong> <?php if($t['is_default']): ?><span class="badge">Default</span><?php endif; ?></td>
              <td><?=e($t['delimiter_char']==="\t"?'Tab':$t['delimiter_char'])?></td>
              <td><?= $t['has_header']?'Yes':'No' ?></td>
              <td><?=e($t['date_timezone'])?></td>
              <td><?=number_format($mapped)?> / <?=number_format(count($csvFields))?></td>
              <td class="trade-actions">
                <a href="/accounts?edit_csv_template=<?=e($t['id'])?>">Edit</a>
                <form method="post" class="inline-form"><input type="hidden" name="_csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="csv_template_delete"><input type="hidden" name="template_id" value="<?=e($t['id'])?>"><button class="danger">Delete</button></form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody></table>
        </div>
      <?php else: ?><p class="muted">No CSV templates configured for this account.</p><?php endif; ?>
    </div>
  <?php endforeach; ?>

  <?php if($accounts && !$editCsvTemplate): ?>
    <div class="card">
      <div class="section-heading"><div><h3>Create Trade CSV template</h3><div class="sub">Choose an account and map its broker CSV headers.</div></div></div>
      <form method="post">
        <?=csrf_field()?><input type="hidden" name="action" value="csv_template_save"><input type="hidden" name="template_id" value="">
        <div class="grid">
          <p><label>Account</label><select name="account_id" required><?php foreach($accounts as $a): ?><option value="<?=e($a['id'])?>"><?=e($a['name'])?> (<?=e($a['currency'])?>)</option><?php endforeach; ?></select></p>
          <p><label>Template name</label><input name="template_name" value="Default CSV" required></p>
          <p><label>CSV delimiter</label><select name="delimiter_char"><option value=",">Comma (,)</option><option value=";">Semicolon (;)</option><option value="|">Pipe (|)</option><option value="\t">Tab</option></select></p>
          <p><label>Date/time zone</label><select name="date_timezone"><option>UTC</option><option>America/New_York</option><option>America/Chicago</option><option>America/Los_Angeles</option><option>America/Sao_Paulo</option></select></p>
          <p><label><input type="checkbox" name="has_header" value="1" checked> CSV has header row</label></p>
          <p><label><input type="checkbox" name="is_default" value="1" checked> Use as default template</label></p>
        </div>
        <h3>CSV column mapping</h3><p class="sub">The values below are the standard Exness-style example headers. Replace them with the exact headers from the selected account's CSV export.</p>
        <div class="grid config-grid"><?php foreach($csvFields as $field=>$label): ?><p><label><?=e($label)?><?=in_array($field,['symbol','type','opening_time','closing_time','quantity','entry_price','exit_price','profit','fees','ticket'],true)?' *':''?></label><input name="mapping[<?=e($field)?>]" value="<?=e($defaultCsv[$field]??'')?>" placeholder="Exact CSV header name"></p><?php endforeach; ?></div>
        <div class="actions"><button>Save CSV template</button></div>
      </form>
    </div>
  <?php endif; ?>
</div>
