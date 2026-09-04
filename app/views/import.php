<?php $title='Import Trades'; ?>
<h1>Import Trades</h1>
<p>Import an Exness-style CSV with validation, duplicate detection and a preview before writing trades.</p>
<form method="post" enctype="multipart/form-data" class="card">
<?=csrf_field()?>
<label>Account<select name="account_id" required><option value="">Select account</option><?php foreach($accounts as $account):?><option value="<?= (int)$account['id'] ?>"><?=e($account['name'])?> (<?=e($account['currency'])?>)</option><?php endforeach;?></select></label>
<label>CSV file<input type="file" name="csv_file" accept=".csv,text/csv" required></label>
<p><strong>Required:</strong> symbol, type, opening_time_utc, closing_time_utc, lots, opening_price, closing_price, profit_usd, commission_usd, close_reason, ticket</p>
<p>Maximum size: 5 MB. The broker's source profit is retained in notes; journal P&amp;L remains calculated from entry/exit, quantity and fees.</p>
<div class="actions"><button name="action" value="preview" type="submit" class="secondary">Preview CSV</button><button name="action" value="import" type="submit">Import CSV</button></div>
</form>
<?php if(!empty($preview)):?><div class="card"><h2>Preview</h2><p>Rows inspected: <strong><?=number_format((int)$preview['sample_count'])?></strong> · Valid: <strong><?=number_format((int)$preview['valid'])?></strong> · Invalid: <strong><?=number_format((int)$preview['invalid'])?></strong></p><?php if($preview['errors']):?><ul><?php foreach($preview['errors'] as $x):?><li><?=e($x)?></li><?php endforeach;?></ul><?php endif;?><p class="muted">Preview inspects up to 1,000 rows. No trades are written by Preview.</p></div><?php endif;?>
