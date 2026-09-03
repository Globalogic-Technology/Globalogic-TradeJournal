<?php

declare(strict_types=1);


require dirname(__DIR__) . '/app/bootstrap.php';
$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';


try {
    if ($path === '/login') {
        if ($method === 'POST') {
            verify_csrf();
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            $s = db()->prepare('SELECT id,name,email,password_hash FROM users WHERE email=?');
            $s->execute([$email]);
            $u = $s->fetch();
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !$u || !password_verify($password, $u['password_hash'])) {
                flash('error', 'Invalid email or password.');
                redirect('/login');
            }
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$u['id'];
            unset($_SESSION['_csrf']);
            redirect('/dashboard');
        }
        render('login', ['title' => 'Login']);
        exit;
    }
    if ($path === '/register') {
        if ($method === 'POST') {
            verify_csrf();
            $name = trim((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            if ($name === '' || strlen($name) > 120) throw new InvalidArgumentException('Name is required.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) throw new InvalidArgumentException('Enter a valid email.');
            if (strlen($password) < 8) throw new InvalidArgumentException('Password must contain at least 8 characters.');
            db()->prepare('INSERT INTO users(name,email,password_hash) VALUES(?,?,?)')->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)db()->lastInsertId();
            unset($_SESSION['_csrf']);
            redirect('/dashboard');
        }
        render('register', ['title' => 'Create account']);
        exit;
    }
    if ($path === '/logout') {
        if ($method !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
        verify_csrf();
        $_SESSION = [];
        session_destroy();
        redirect('/login');
    }
    $user = require_auth();

    if ($path === '/' || $path === '/dashboard') {
        $s = db()->prepare('SELECT a.*,COUNT(t.id) trade_count,COALESCE(SUM(CASE WHEN t.status="closed" THEN CASE WHEN t.side="long" THEN (t.exit_price-t.entry_price)*t.quantity-t.fees ELSE (t.entry_price-t.exit_price)*t.quantity-t.fees END ELSE 0 END),0) pnl FROM accounts a LEFT JOIN trades t ON t.account_id=a.id AND t.user_id=a.user_id WHERE a.user_id=? GROUP BY a.id ORDER BY a.name');
        $s->execute([$user['id']]);
        $accounts = $s->fetchAll();
        $s = db()->prepare('SELECT COUNT(*) closed_count,COALESCE(SUM(CASE WHEN side="long" THEN (exit_price-entry_price)*quantity-fees ELSE (entry_price-exit_price)*quantity-fees END),0) pnl FROM trades WHERE user_id=? AND status="closed"');
        $s->execute([$user['id']]);
        render('dashboard', ['title' => 'Dashboard', 'accounts' => $accounts, 'summary' => $s->fetch()]);
        exit;
    }

    if ($path === '/accounts') {
        if ($method === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? '';
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            $name = trim((string)($_POST['name'] ?? ''));
            $currency = strtoupper(trim((string)($_POST['currency'] ?? 'USD')));
            $balance = decimal_input($_POST['initial_balance'] ?? null, 'Initial balance') ?? 0;
            if ($name === '' || strlen($name) > 120 || !preg_match('/^[A-Z]{3}$/', $currency) || $balance < 0) throw new InvalidArgumentException('Invalid account values.');
            if ($action === 'create') db()->prepare('INSERT INTO accounts(user_id,name,currency,initial_balance) VALUES(?,?,?,?)')->execute([$user['id'], $name, $currency, $balance]);
            elseif ($action === 'update' && $id) db()->prepare('UPDATE accounts SET name=?,currency=?,initial_balance=? WHERE id=? AND user_id=?')->execute([$name, $currency, $balance, $id, $user['id']]);
            elseif ($action === 'delete' && $id) db()->prepare('DELETE FROM accounts WHERE id=? AND user_id=?')->execute([$id, $user['id']]);
            else throw new InvalidArgumentException('Unknown account action.');
            flash('success', 'Account saved.');
            redirect('/accounts');
        }
        $s = db()->prepare('SELECT * FROM accounts WHERE user_id=? ORDER BY name');
        $s->execute([$user['id']]);
        render('accounts', ['title' => 'Accounts', 'accounts' => $s->fetchAll()]);
        exit;
    }

    if ($path === '/trades') {
        if ($method === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? '';
            if ($action === 'delete') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                db()->prepare('DELETE FROM trades WHERE id=? AND user_id=?')->execute([$id, $user['id']]);
                flash('success', 'Trade deleted.');
                redirect('/trades');
            }
            if ($action === 'save') {
                $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
                $data = trade_form_data($_POST, $user);
                if ($id) {
                    db()->prepare('UPDATE trades SET account_id=?,ticket=?,symbol=?,side=?,status=?,opened_at=?,closed_at=?,quantity=?,entry_price=?,stop_loss=?,take_profit=?,exit_price=?,fees=?,notes=? WHERE id=? AND user_id=?')->execute([...$data, $id, $user['id']]);
                } else {
                    db()->prepare('INSERT INTO trades(account_id,ticket,symbol,side,status,opened_at,closed_at,quantity,entry_price,stop_loss,take_profit,exit_price,fees,notes,user_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)')->execute([...$data, $user['id']]);
                }
                flash('success', $id ? 'Trade updated.' : 'Trade added.');
                redirect('/trades');
            }
            throw new InvalidArgumentException('Unknown trade action.');
        }
        $s = db()->prepare('SELECT id,name,currency FROM accounts WHERE user_id=? ORDER BY name');
        $s->execute([$user['id']]);
        $accounts = $s->fetchAll();
        $editTrade = null;
        $editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
        if ($editId) {
            $s = db()->prepare('SELECT * FROM trades WHERE id=? AND user_id=?');
            $s->execute([$editId, $user['id']]);
            $editTrade = $s->fetch() ?: null;
        }
        $where = ['t.user_id=?'];
        $params = [$user['id']];
        $symbol = trim((string)($_GET['symbol'] ?? ''));
        $side = $_GET['side'] ?? '';
        $status = $_GET['status'] ?? '';
        if ($symbol !== '') {
            $where[] = 't.symbol LIKE ?';
            $params[] = '%' . $symbol . '%';
        }
        if (in_array($side, ['long', 'short'], true)) {
            $where[] = 't.side=?';
            $params[] = $side;
        }
        if (in_array($status, ['open', 'closed'], true)) {
            $where[] = 't.status=?';
            $params[] = $status;
        }
        $s = db()->prepare('SELECT COUNT(*) FROM trades t WHERE ' . implode(' AND ', $where));
        $s->execute($params);
        $total = (int)$s->fetchColumn();
        $page = max(1, (int)($_GET['page'] ?? 1));
        $pages = max(1, (int)ceil($total / 25));
        $page = min($page, $pages);
        $offset = ($page - 1) * 25;
        $s = db()->prepare('SELECT t.*,a.name account_name,a.currency FROM trades t JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id WHERE ' . implode(' AND ', $where) . ' ORDER BY t.opened_at DESC,t.id DESC LIMIT 25 OFFSET ' . $offset);
        $s->execute($params);
        $trades = $s->fetchAll();
        foreach ($trades as &$t) $t['pnl'] = trade_pnl($t);
        unset($t);
        render('trades', ['title' => 'Trades', 'accounts' => $accounts, 'editTrade' => $editTrade, 'trades' => $trades, 'filters' => compact('symbol', 'side', 'status'), 'page' => $page, 'pages' => $pages, 'total' => $total]);
        exit;
    }

    if ($path === '/analytics') {
        require_auth();
        $stmt = db()->prepare(
            'SELECT t.* FROM trades t
         INNER JOIN accounts a ON a.id = t.account_id AND a.user_id = t.user_id
         WHERE t.user_id = ? AND t.status = "closed" AND t.exit_price IS NOT NULL
         ORDER BY t.closed_at ASC, t.id ASC'
        );
        $stmt->execute([current_user()['id']]);
        $trades = $stmt->fetchAll();

        $pnls = array_values(array_filter(
            array_map('trade_pnl', $trades),
            static fn($v) => $v !== null
        ));
        $count = count($pnls);
        $wins = count(array_filter($pnls, static fn($v) => $v > 0));
        $losses = count(array_filter($pnls, static fn($v) => $v < 0));
        $netPnl = array_sum($pnls);
        $grossProfit = array_sum(array_filter($pnls, static fn($v) => $v > 0));
        $grossLoss = abs(array_sum(array_filter($pnls, static fn($v) => $v < 0)));
        $winRate = $count ? ($wins / $count) * 100 : null;
        $profitFactor = $grossLoss > 0 ? $grossProfit / $grossLoss : null;

        $equity = $peak = $maxDrawdown = 0.0;
        foreach ($pnls as $pnl) {
            $equity += $pnl;
            $peak = max($peak, $equity);
            $maxDrawdown = max($maxDrawdown, $peak - $equity);
        }

        $sharpe = null;
        if ($count >= 2) {
            $mean = $netPnl / $count;
            $sumSq = 0.0;
            foreach ($pnls as $pnl) $sumSq += ($pnl - $mean) ** 2;
            $std = sqrt($sumSq / ($count - 1));
            if ($std > 0) $sharpe = $mean / $std;
        }

        render('analytics', compact(
            'count',
            'wins',
            'losses',
            'netPnl',
            'grossProfit',
            'grossLoss',
            'winRate',
            'profitFactor',
            'maxDrawdown',
            'sharpe'
        ));
        exit;
    }

    if ($path === '/export') {
        require_auth();
        if (($_GET['format'] ?? '') !== 'json') {
            http_response_code(400);
            exit('Unsupported export format.');
        }

        $stmt = db()->prepare(
            'SELECT t.*, a.name AS account_name, a.currency AS account_currency
         FROM trades t
         INNER JOIN accounts a ON a.id = t.account_id AND a.user_id = t.user_id
         WHERE t.user_id = ?
         ORDER BY t.opened_at ASC, t.id ASC'
        );
        $stmt->execute([current_user()['id']]);

        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="trading-journal-backup-' . date('Y-m-d') . '.json"');
        echo json_encode([
            'version' => 1,
            'exported_at' => gmdate('c'),
            'trades' => $stmt->fetchAll(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        exit;
    }

    if ($path === '/import') {
        require_auth();

        $stmt = db()->prepare(
            'SELECT id, name, currency FROM accounts WHERE user_id = ? ORDER BY name'
        );
        $stmt->execute([current_user()['id']]);
        $accounts = $stmt->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $accountId = (int)($_POST['account_id'] ?? 0);
            $file = $_FILES['csv_file'] ?? null;

            $check = db()->prepare('SELECT id FROM accounts WHERE id = ? AND user_id = ?');
            $check->execute([$accountId, current_user()['id']]);
            if (!$check->fetchColumn()) {
                flash('error', 'Invalid account.');
                redirect('/import');
            }

            if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                flash('error', 'Please choose a CSV file.');
                redirect('/import');
            }
            if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
                flash('error', 'CSV file is too large. Maximum size is 5 MB.');
                redirect('/import');
            }

            $fh = fopen($file['tmp_name'], 'rb');
            $headers = $fh ? fgetcsv($fh) : false;
            $required = [
                'symbol',
                'type',
                'opening_time_utc',
                'closing_time_utc',
                'lots',
                'opening_price',
                'closing_price',
                'profit_usd',
                'commission_usd',
                'close_reason',
                'ticket'
            ];

            if (!$headers) {
                if ($fh) fclose($fh);
                flash('error', 'The CSV file is empty or unreadable.');
                redirect('/import');
            }

            $headers = array_map(static fn($v) => strtolower(trim((string)$v)), $headers);
            $missing = array_values(array_diff($required, $headers));
            if ($missing) {
                fclose($fh);
                flash('error', 'Missing columns: ' . implode(', ', $missing));
                redirect('/import');
            }

            $findDuplicate = db()->prepare(
                'SELECT 1 FROM trades WHERE account_id = ? AND ticket = ? LIMIT 1'
            );
            $insert = db()->prepare(
                'INSERT INTO trades
             (user_id, account_id, ticket, symbol, side, status, opened_at, closed_at,
              quantity, entry_price, exit_price, fees, notes)
             VALUES (?, ?, ?, ?, ?, "closed", ?, ?, ?, ?, ?, ?, ?)'
            );

            $inserted = $skipped = 0;
            $errors = [];
            $line = 1;

            while (($values = fgetcsv($fh)) !== false) {
                $line++;
                if (count($values) === 1 && trim((string)$values[0]) === '') continue;

                $row = array_combine($headers, array_pad($values, count($headers), ''));
                $ticket = trim((string)($row['ticket'] ?? ''));
                $symbol = trim((string)($row['symbol'] ?? ''));
                $type = strtolower(trim((string)($row['type'] ?? '')));

                try {
                    $opened = new DateTime((string)$row['opening_time_utc'], new DateTimeZone('UTC'));
                    $closed = new DateTime((string)$row['closing_time_utc'], new DateTimeZone('UTC'));
                    $openedAt = $opened->format('Y-m-d H:i:s');
                    $closedAt = $closed->format('Y-m-d H:i:s');

                    $lots = (float)str_replace([',', '$'], '', trim((string)$row['lots']));
                    $entry = (float)str_replace([',', '$'], '', trim((string)$row['opening_price']));
                    $exit = (float)str_replace([',', '$'], '', trim((string)$row['closing_price']));
                    $profit = (float)str_replace([',', '$'], '', trim((string)$row['profit_usd']));
                    $commission = (float)str_replace([',', '$'], '', trim((string)$row['commission_usd']));

                    if (
                        !$ticket || !$symbol || $lots <= 0 || $entry < 0 || $exit < 0 ||
                        !in_array($type, ['buy', 'sell', 'long', 'short'], true)
                    ) {
                        throw new RuntimeException('invalid required value');
                    }

                    $findDuplicate->execute([$accountId, $ticket]);
                    if ($findDuplicate->fetchColumn()) {
                        $skipped++;
                        continue;
                    }

                    $side = in_array($type, ['buy', 'long'], true) ? 'long' : 'short';
                    $reason = trim((string)($row['close_reason'] ?? ''));
                    $notes = 'Imported CSV source profit_usd=' . $profit;
                    if ($reason !== '') $notes .= '; close_reason=' . $reason;

                    $insert->execute([
                        current_user()['id'],
                        $accountId,
                        $ticket,
                        $symbol,
                        $side,
                        $openedAt,
                        $closedAt,
                        $lots,
                        $entry,
                        $exit,
                        $commission,
                        $notes
                    ]);
                    $inserted++;
                } catch (Throwable $e) {
                    if (count($errors) < 20) {
                        $errors[] = "Line {$line}: " . $e->getMessage();
                    }
                }
            }
            fclose($fh);

            if ($inserted || $skipped) {
                flash('success', "Imported {$inserted} trade(s); skipped {$skipped} duplicate(s).");
            }
            if ($errors) {
                flash('error', implode(' ', $errors));
            }
            redirect('/import');
        }

        render('import', ['accounts' => $accounts]);
        exit;
    }

    http_response_code(404);
    render('404', ['title' => 'Not found']);
} catch (InvalidArgumentException $e) {
    flash('error', $e->getMessage());
    redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
} catch (PDOException $e) {
    if ((int)($e->errorInfo[1] ?? 0) === 1062) {
        flash('error', 'That account name or ticket already exists.');
        redirect($_SERVER['HTTP_REFERER'] ?? '/dashboard');
    }
    error_log((string)$e);
    http_response_code(500);
    exit('A database error occurred.');
}
