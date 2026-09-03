<?php
$esc = static fn($v) => e($v);
$resource = $resource ?? '';
$rows = $rows ?? [];
$fields = $form['fields'] ?? [];
$action = $form['action'] ?? '';
$editId = (int)($_GET['edit'] ?? 0);
$edit = null;
foreach ($rows as $r) if ((int)($r['id'] ?? 0) === $editId) { $edit = $r; break; }
$labels = [
    'trading_system_id'=>'Trading system','strategy_id'=>'Strategy','asset_id'=>'Asset','account_id'=>'Account','default_system_id'=>'Default system',
    'name'=>'Name','description'=>'Description','ideal_risk'=>'Ideal risk','risk_tolerance'=>'Risk tolerance (%)','symbol'=>'Symbol','configuration'=>'Configuration (JSON)',
    'fee_type'=>'Fee type','fee_amount'=>'Fee amount','fee_currency'=>'Fee currency','start_time'=>'Start time','end_time'=>'End time','timezone'=>'Time zone',
    'initial_balance'=>'Balance','currency'=>'Currency'
];
?>
<div class="card">
    <h1><?= $esc($title ?? 'Configuration') ?></h1>
    <p class="muted">Phase 3 configuration is stored per user and feeds account/system defaults. Calculated P&L remains handled by the existing Phase 2 engine.</p>
</div>

<div class="card">
    <h2><?= $edit ? 'Edit' : 'Add' ?> <?= $esc(rtrim($title ?? 'Configuration','s')) ?></h2>
    <form method="post" action="<?= $esc($action) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if($edit): ?><input type="hidden" name="id" value="<?= $edit['id'] ?>"><?php endif; ?>
        <div class="grid">
            <?php foreach($fields as $field): ?>
                <p>
                    <label><?= $esc($labels[$field] ?? ucwords(str_replace('_',' ',$field))) ?></label>
                    <?php if($field === 'description'): ?>
                        <textarea name="description"><?= $esc($edit['description'] ?? '') ?></textarea>
                    <?php elseif($field === 'configuration'): ?>
                        <textarea name="configuration" placeholder='{"contract_size":100000,"pip_size":0.0001}'><?= $esc($edit['configuration'] ?? '') ?></textarea>
                    <?php elseif($field === 'trading_system_id'): ?>
                        <select name="trading_system_id" required><option value="">Select...</option><?php foreach(($systems??[]) as $x): ?><option value="<?= $x['id'] ?>" <?=((int)($edit['trading_system_id']??0)===(int)$x['id'])?'selected':''?>><?= $esc($x['name']) ?></option><?php endforeach; ?></select>
                    <?php elseif($field === 'asset_id'): ?>
                        <select name="asset_id" required><option value="">Select...</option><?php foreach(($assets??[]) as $x): ?><option value="<?= $x['id'] ?>" <?=((int)($edit['asset_id']??0)===(int)$x['id'])?'selected':''?>><?= $esc($x['symbol'].' — '.$x['name']) ?></option><?php endforeach; ?></select>
                    <?php elseif($field === 'account_id'): ?>
                        <select name="account_id"><option value="">No account scope</option><?php foreach(($accounts??[]) as $x): ?><option value="<?= $x['id'] ?>" <?=((int)($edit['account_id']??0)===(int)$x['id'])?'selected':''?>><?= $esc($x['name']) ?></option><?php endforeach; ?></select>
                    <?php elseif($field === 'default_system_id'): ?>
                        <select name="default_system_id"><option value="">No default system</option><?php foreach(($systems??[]) as $x): ?><option value="<?= $x['id'] ?>" <?=((int)($edit['default_system_id']??0)===(int)$x['id'])?'selected':''?>><?= $esc($x['name']) ?></option><?php endforeach; ?></select>
                    <?php elseif($field === 'timezone'): ?>
                        <select name="timezone" required><?php foreach(($timezones??['UTC']) as $tz): ?><option value="<?= $esc($tz) ?>" <?= (($edit['timezone']??'UTC')===$tz)?'selected':'' ?>><?= $esc($tz) ?></option><?php endforeach; ?></select>
                    <?php elseif($field === 'start_time' || $field === 'end_time'): ?>
                        <input type="time" name="<?= $field ?>" value="<?= $esc(isset($edit[$field])?substr((string)$edit[$field],0,5):'') ?>" required>
                    <?php elseif($field === 'ideal_risk' || $field === 'fee_amount' || $field === 'initial_balance'): ?>
                        <input type="number" step="0.00000001" min="0" name="<?= $field ?>" value="<?= $esc($edit[$field] ?? '0') ?>" required>
                    <?php elseif($field === 'risk_tolerance'): ?>
                        <input type="number" step="0.01" min="0" max="100" name="risk_tolerance" value="<?= $esc($edit['risk_tolerance'] ?? '10') ?>" required>
                    <?php elseif($field === 'currency' || $field === 'fee_currency'): ?>
                        <input name="<?= $field ?>" maxlength="3" value="<?= $esc($edit[$field] ?? 'USD') ?>" required>
                    <?php else: ?>
                        <input name="<?= $field ?>" value="<?= $esc($edit[$field] ?? '') ?>" required>
                    <?php endif; ?>
                </p>
            <?php endforeach; ?>
        </div>
        <button><?= $edit ? 'Save changes' : 'Create' ?></button>
        <?php if($edit): ?> <a href="<?= $esc($action) ?>">Cancel</a><?php endif; ?>
    </form>
</div>

<div class="card table-wrap">
    <h2>Existing configuration</h2>
    <table>
        <thead><tr>
            <?php if($resource==='systems'): ?><th>Name</th><th>Ideal risk</th><th>Tolerance</th><th>Description</th>
            <?php elseif($resource==='strategies'): ?><th>Name</th><th>System</th><th>Description</th>
            <?php elseif($resource==='assets'): ?><th>Symbol</th><th>Name</th><th>Configuration</th>
            <?php elseif($resource==='fees'): ?><th>Asset</th><th>Type</th><th>Amount</th><th>Currency</th>
            <?php elseif($resource==='sessions'): ?><th>Name</th><th>Start</th><th>End</th><th>Time zone</th>
            <?php elseif($resource==='risk'): ?><th>Account</th><th>System</th><th>Ideal risk</th><th>Tolerance</th>
            <?php else: ?><th>Account</th><th>Balance</th><th>Currency</th><th>Default system</th><th>Ideal risk</th><th>Tolerance</th><?php endif; ?><th>Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach($rows as $r): ?>
            <tr>
            <?php if($resource==='systems'): ?><td><?= $esc($r['name']) ?></td><td><?= number_format((float)$r['ideal_risk'],2) ?></td><td><?= number_format((float)$r['risk_tolerance'],2) ?>%</td><td><?= $esc($r['description']) ?></td>
            <?php elseif($resource==='strategies'): ?><td><?= $esc($r['name']) ?></td><td><?= $esc($r['system_name']) ?></td><td><?= $esc($r['description']) ?></td>
            <?php elseif($resource==='assets'): ?><td><?= $esc($r['symbol']) ?></td><td><?= $esc($r['name']) ?></td><td><code><?= $esc($r['configuration']) ?></code></td>
            <?php elseif($resource==='fees'): ?><td><?= $esc($r['asset_symbol']) ?></td><td><?= $esc($r['fee_type']) ?></td><td><?= number_format((float)$r['fee_amount'],8) ?></td><td><?= $esc($r['fee_currency']) ?></td>
            <?php elseif($resource==='sessions'): ?><td><?= $esc($r['name']) ?></td><td><?= $esc(substr($r['start_time'],0,5)) ?></td><td><?= $esc(substr($r['end_time'],0,5)) ?></td><td><?= $esc($r['timezone']) ?></td>
            <?php elseif($resource==='risk'): ?><td><?= $esc($r['account_name'] ?? 'Global') ?></td><td><?= $esc($r['system_name'] ?? 'Global') ?></td><td><?= number_format((float)$r['ideal_risk'],2) ?></td><td><?= number_format((float)$r['risk_tolerance'],2) ?>%</td>
            <?php else: ?><td><?= $esc($r['name']) ?></td><td><?= number_format((float)$r['initial_balance'],2) ?></td><td><?= $esc($r['currency']) ?></td><td><?= $esc($r['default_system_name'] ?? '—') ?></td><td><?= number_format((float)$r['ideal_risk'],2) ?></td><td><?= number_format((float)$r['risk_tolerance'],2) ?>%</td><?php endif; ?>
                <td class="actions"><a href="<?= $esc($action) ?>?edit=<?= $r['id'] ?>">Edit</a><form method="post" action="<?= $esc($action) ?>" onsubmit="return confirm('Delete this configuration?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="danger">Delete</button></form></td>
            </tr>
        <?php endforeach; ?>
        <?php if(!$rows): ?><tr><td colspan="8" class="muted">No configuration records yet.</td></tr><?php endif; ?>
        </tbody>
    </table>
</div>
