<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title ?? 'Trading Journal') ?> — <?= e(env('APP_NAME', 'Trading Journal')) ?></title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:system-ui,sans-serif;background:#f4f6f8;color:#18202a}nav{background:#111827;color:white;padding:14px 20px;display:flex;gap:18px;align-items:center;flex-wrap:wrap}nav a{color:white;text-decoration:none}.brand{font-weight:800;margin-right:auto}.nav-group{display:flex;gap:14px;align-items:center;flex-wrap:wrap}.container{max-width:1200px;margin:28px auto;padding:0 18px}.card{background:white;border:1px solid #dfe3e8;border-radius:10px;padding:18px;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}label{display:block;font-weight:600;font-size:.9rem;margin-bottom:5px}input,select,textarea{width:100%;padding:9px;border:1px solid #cbd2d9;border-radius:6px}textarea{min-height:80px}button{border:0;border-radius:6px;padding:9px 13px;cursor:pointer;background:#111827;color:white}.danger{background:#b42318!important}.secondary{background:#667085}.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}.table-wrap{overflow:auto}table{width:100%;border-collapse:collapse}th,td{padding:10px;border-bottom:1px solid #e5e7eb;text-align:left;white-space:nowrap}.flash{padding:12px;border-radius:7px;margin-bottom:15px}.success{background:#ecfdf3;color:#027a48}.error{background:#fef3f2;color:#b42318}.muted{color:#667085}.positive{color:#027a48}.negative{color:#b42318}.nav-section{font-size:.75rem;text-transform:uppercase;opacity:.65}
</style>
</head>
<body>
<nav>
<a class="brand" href="/dashboard"><?= e(env('APP_NAME', 'Trading Journal')) ?></a>
<?php if(current_user()): ?>
<div class="nav-group"><a href="/dashboard">Dashboard</a><a href="/accounts">Accounts</a><a href="/trades">Trades</a><a href="/analytics">Analytics</a><a href="/import">Import</a><a href="/imports">Import History</a><a href="/data-management">Data</a><a href="/export?format=json">Backup</a></div>
<div class="nav-group"><span class="nav-section">Configuration</span><a href="/systems">Systems</a><a href="/strategies">Strategies</a><a href="/assets">Assets</a><a href="/asset-fees">Fees</a><a href="/sessions">Sessions</a><a href="/risk-settings">Risk</a><a href="/account-settings">Account Config</a></div>
<form method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>"><button class="secondary">Logout</button></form>
<?php else: ?><a href="/login">Login</a><a href="/register">Register</a><?php endif; ?>
</nav>
<main class="container"><?php if($m=flash('success')):?><div class="flash success"><?=e($m)?></div><?php endif;?><?php if($m=flash('error')):?><div class="flash error"><?=e($m)?></div><?php endif;?><?php require $viewFile;?></main>
</body>
</html>
