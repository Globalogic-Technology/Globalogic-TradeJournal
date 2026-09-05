<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
require dirname(__DIR__, 2) . '/app/services/AccountCsvTemplateService.php';

$user = require_auth();
$db = db();
$uid = (int)$user['id'];

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        verify_csrf();
        $action = (string)($_POST['action'] ?? '');

        if ($action === 'csv_template_save') {
            $accountId = (int)($_POST['account_id'] ?? 0);
            $id = filter_var($_POST['template_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            $mapping = is_array($_POST['mapping'] ?? null) ? $_POST['mapping'] : [];
            AccountCsvTemplateService::save(
                $db,
                $uid,
                $accountId,
                $id,
                trim((string)($_POST['template_name'] ?? '')),
                (string)($_POST['delimiter_char'] ?? ','),
                isset($_POST['has_header']),
                trim((string)($_POST['date_timezone'] ?? 'UTC')),
                $mapping,
                isset($_POST['is_default'])
            );
            flash('success', 'CSV template saved.');
            redirect('/accounts');
        }

        if ($action === 'csv_template_delete') {
            AccountCsvTemplateService::delete($db, $uid, (int)($_POST['template_id'] ?? 0));
            flash('success', 'CSV template deleted.');
            redirect('/accounts');
        }

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
        $name = trim((string)($_POST['name'] ?? ''));
        $currency = strtoupper(trim((string)($_POST['currency'] ?? 'USD')));
        $balance = decimal_input($_POST['initial_balance'] ?? null, 'Initial balance') ?? 0;
        if ($name === '' || strlen($name) > 120 || !preg_match('/^[A-Z]{3}$/', $currency) || $balance < 0) {
            throw new InvalidArgumentException('Invalid account values.');
        }
        if ($action === 'create') {
            $db->prepare('INSERT INTO accounts(user_id,name,currency,initial_balance) VALUES(?,?,?,?)')->execute([$uid,$name,$currency,$balance]);
            flash('success', 'Account created.');
        } elseif ($action === 'update' && $id) {
            $db->prepare('UPDATE accounts SET name=?,currency=?,initial_balance=? WHERE id=? AND user_id=?')->execute([$name,$currency,$balance,$id,$uid]);
            flash('success', 'Account updated.');
        } elseif ($action === 'delete' && $id) {
            $db->prepare('DELETE FROM accounts WHERE id=? AND user_id=?')->execute([$id,$uid]);
            flash('success', 'Account deleted.');
        } else {
            throw new InvalidArgumentException('Unknown account action.');
        }
        redirect('/accounts');
    }

    $s = $db->prepare('SELECT * FROM accounts WHERE user_id=? ORDER BY name');
    $s->execute([$uid]);
    $accounts = $s->fetchAll();
    $t = $db->prepare('SELECT id,account_id,name,delimiter_char,has_header,date_timezone,mapping_json,is_default FROM account_csv_templates WHERE user_id=? ORDER BY account_id,is_default DESC,name');
    $t->execute([$uid]);
    $templates = $t->fetchAll();
    foreach ($templates as &$template) {
        $template['mapping'] = json_decode((string)$template['mapping_json'], true) ?: [];
    }
    unset($template);
    render('accounts', ['title'=>'Accounts','accounts'=>$accounts,'csvTemplates'=>$templates]);
} catch (InvalidArgumentException $e) {
    flash('error', $e->getMessage());
    redirect('/accounts');
} catch (Throwable $e) {
    error_log((string)$e);
    if (db()->inTransaction()) db()->rollBack();
    if (!headers_sent()) http_response_code(500);
    exit('An unexpected error occurred.');
}
