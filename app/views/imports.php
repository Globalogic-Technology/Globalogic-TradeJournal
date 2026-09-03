<?php $title='Import History'; ?>
<h1>Import History</h1>
<p>Previous CSV imports and their results.</p>
<div class="card"><table><thead><tr><th>Date</th><th>File</th><th>Account</th><th>Status</th><th>Total</th><th>Imported</th><th>Skipped</th><th>Errors</th></tr></thead><tbody><?php foreach($imports as $i):?><tr><td><?=e($i['created_at'])?></td><td><?=e($i['filename'])?></td><td><?=e($i['account_name'])?></td><td><?=e(ucfirst($i['status']))?></td><td><?=number_format((int)$i['total_rows'])?></td><td><?=number_format((int)$i['imported_rows'])?></td><td><?=number_format((int)$i['skipped_rows'])?></td><td><?=number_format((int)$i['error_rows'])?></td></tr><?php endforeach;if(!$imports):?><tr><td colspan="8">No imports yet.</td></tr><?php endif;?></tbody></table></div>
