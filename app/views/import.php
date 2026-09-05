<?php $title='Import Trades';$templates=$templates??[];$selectedAccount=(int)($selectedAccount??0);$selectedTemplate=(int)($selectedTemplate??0); ?>
<h1>Import Trades</h1>
<p>Choose the account first. Its default Trade CSV template is selected automatically, so each broker/account can use a different CSV format.</p>
<form method="post" enctype="multipart/form-data" class="card" id="csv-import-form">
<?=csrf_field()?>
<div class="grid">
<label>Account<select name="account_id" id="csv-account" required><option value="">Select account</option><?php foreach($accounts as $account):?><option value="<?= (int)$account['id'] ?>" <?=((int)$account['id']===$selectedAccount)?'selected':''?>><?=e($account['name'])?> (<?=e($account['currency'])?>)</option><?php endforeach;?></select></label>
<label>Trade CSV template<select name="template_id" id="csv-template" required><option value="">Select account first</option></select></label>
</div>
<label>CSV file<input type="file" name="csv_file" accept=".csv,text/csv" required></label>
<div id="csv-template-info" class="sub"></div>
<p>Maximum size: 5 MB. The importer converts the selected account template into the journal's standard trade fields. Stop loss and take profit are optional mappings.</p>
<div class="actions"><button name="action" value="preview" type="submit" class="secondary">Preview CSV</button><button name="action" value="import" type="submit">Import CSV</button></div>
</form>
<?php if(!empty($preview)):?><div class="card"><h2>Preview</h2><p>Rows inspected: <strong><?=number_format((int)$preview['sample_count'])?></strong> · Valid: <strong><?=number_format((int)$preview['valid'])?></strong> · Invalid: <strong><?=number_format((int)$preview['invalid'])?></strong></p><?php if($preview['errors']):?><ul><?php foreach($preview['errors'] as $x):?><li><?=e($x)?></li><?php endforeach;?></ul><?php endif;?><p class="muted">Preview inspects up to 1,000 rows. No trades are written by Preview.</p></div><?php endif;?>
<script nonce="<?=e(csp_nonce())?>">
(function(){const data=<?=json_encode($templates,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>,account=document.getElementById('csv-account'),template=document.getElementById('csv-template'),info=document.getElementById('csv-template-info'),wanted=<?=json_encode($selectedTemplate)?>;function load(){const id=Number(account.value);template.innerHTML='<option value="">Select template</option>';data.filter(t=>Number(t.account_id)===id).forEach(t=>{const o=document.createElement('option');o.value=t.id;o.textContent=t.name+(Number(t.is_default)?' — Default':'');if(Number(t.id)===Number(wanted))o.selected=true;template.appendChild(o);});show();}function show(){const t=data.find(x=>Number(x.id)===Number(template.value));info.textContent=t?('Delimiter: '+(t.delimiter_char==='\t'?'Tab':t.delimiter_char)+' · Time zone: '+t.date_timezone+' · '+(Number(t.has_header)?'Header row enabled':'No header row')):'';}account.addEventListener('change',function(){template.value='';load();});template.addEventListener('change',show);load();})();
</script>
