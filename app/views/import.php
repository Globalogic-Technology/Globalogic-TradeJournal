<?php $title = 'Import Trades'; ?>



<h1>Import Trades</h1>
<p>Import an Exness-style CSV. Duplicate tickets are skipped per account.</p>

<form method="post" enctype="multipart/form-data" class="card">
    <?= csrf_field() ?>
    <label>Account
        <select name="account_id" required>
            <option value="">Select account</option>
            <?php foreach ($accounts as $account): ?>
                <option value="<?= (int)$account['id'] ?>">
                    <?= e($account['name']) ?> (<?= e($account['currency']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>CSV file
        <input type="file" name="csv_file" accept=".csv,text/csv" required>
    </label>

    <p><strong>Required:</strong>
        symbol, type, opening_time_utc, closing_time_utc, lots,
        opening_price, closing_price, profit_usd, commission_usd,
        close_reason, ticket
    </p>
    <p>Maximum size: 5 MB. Source profit and close reason are retained in notes; journal P&amp;L uses its own entry/exit/quantity/fees calculation.</p>

    <button type="submit">Import CSV</button>
</form>