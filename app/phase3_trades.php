<?php

declare(strict_types=1);

use App\Services\TradingConfigurationService;

require_once dirname(__DIR__) . '/app/services/TradingConfigurationService.php';

function phase3_trade_lookup(PDO $db, int $userId, string $table, int $id): ?array
{
    $allowed = [
        'trading_systems' => 'id,name,ideal_risk,risk_tolerance',
        'strategies' => 'id,name,trading_system_id',
        'assets' => 'id,symbol,name,configuration',
        'trading_sessions' => 'id,name,start_time,end_time,timezone',
    ];
    if (!isset($allowed[$table]) || $id < 1) return null;
    $s = $db->prepare("SELECT {$allowed[$table]} FROM {$table} WHERE id=? AND user_id=?");
    $s->execute([$id, $userId]);
    return $s->fetch() ?: null;
}

function phase3_trade_form_data(array $input, array $user): array
{
    $data = trade_form_data($input, $user);
    $db = db();
    $userId = (int)$user['id'];

    $systemId = filter_var($input['trading_system_id'] ?? null, FILTER_VALIDATE_INT);
    $strategyId = filter_var($input['strategy_id'] ?? null, FILTER_VALIDATE_INT);
    $assetId = filter_var($input['asset_id'] ?? null, FILTER_VALIDATE_INT);
    $sessionId = filter_var($input['trading_session_id'] ?? null, FILTER_VALIDATE_INT);

    if ($systemId && !phase3_trade_lookup($db, $userId, 'trading_systems', $systemId)) {
        throw new InvalidArgumentException('The selected trading system is invalid.');
    }
    if ($strategyId) {
        $strategy = phase3_trade_lookup($db, $userId, 'strategies', $strategyId);
        if (!$strategy) throw new InvalidArgumentException('The selected strategy is invalid.');
        if ($systemId && (int)$strategy['trading_system_id'] !== $systemId) {
            throw new InvalidArgumentException('The selected strategy does not belong to the selected trading system.');
        }
        if (!$systemId) $systemId = (int)$strategy['trading_system_id'];
    }
    if ($assetId && !phase3_trade_lookup($db, $userId, 'assets', $assetId)) {
        throw new InvalidArgumentException('The selected asset is invalid.');
    }
    if ($sessionId && !phase3_trade_lookup($db, $userId, 'trading_sessions', $sessionId)) {
        throw new InvalidArgumentException('The selected trading session is invalid.');
    }

    return [
        ...$data,
        $systemId ?: null,
        $strategyId ?: null,
        $assetId ?: null,
        $sessionId ?: null,
    ];
}

function phase3_trade_route(string $path, string $method, array $user): bool
{
    if ($path !== '/trades') return false;

    $db = db();
    $userId = (int)$user['id'];

    if ($method === 'POST') {
        verify_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'delete') {
            $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$id) throw new InvalidArgumentException('Invalid trade.');
            $db->prepare('DELETE FROM trades WHERE id=? AND user_id=?')->execute([$id, $userId]);
            flash('success', 'Trade deleted.');
            redirect('/trades');
        }

        if ($action !== 'save') throw new InvalidArgumentException('Unknown trade action.');

        $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        $data = phase3_trade_form_data($_POST, $user);
        [$accountId,$ticket,$symbol,$side,$status,$opened,$closed,$qty,$entry,$sl,$tp,$exit,$fees,$notes,$systemId,$strategyId,$assetId,$sessionId] = $data;

        if ($id) {
            $stmt = $db->prepare(
                'UPDATE trades SET account_id=?,ticket=?,symbol=?,side=?,status=?,opened_at=?,closed_at=?,quantity=?,entry_price=?,stop_loss=?,take_profit=?,exit_price=?,fees=?,notes=?,trading_system_id=?,strategy_id=?,asset_id=?,trading_session_id=? WHERE id=? AND user_id=?'
            );
            $stmt->execute([$accountId,$ticket,$symbol,$side,$status,$opened,$closed,$qty,$entry,$sl,$tp,$exit,$fees,$notes,$systemId,$strategyId,$assetId,$sessionId,$id,$userId]);
            $savedId = $id;
        } else {
            $stmt = $db->prepare(
                'INSERT INTO trades(account_id,ticket,symbol,side,status,opened_at,closed_at,quantity,entry_price,stop_loss,take_profit,exit_price,fees,notes,trading_system_id,strategy_id,asset_id,trading_session_id,user_id) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([$accountId,$ticket,$symbol,$side,$status,$opened,$closed,$qty,$entry,$sl,$tp,$exit,$fees,$notes,$systemId,$strategyId,$assetId,$sessionId,$userId]);
            $savedId = (int)$db->lastInsertId();
        }

        flash('success', $id ? 'Trade updated.' : 'Trade added.');
        redirect('/trades?edit=' . $savedId);
    }

    $s = $db->prepare('SELECT * FROM accounts WHERE user_id=? ORDER BY name');
    $s->execute([$userId]);
    $accounts = $s->fetchAll();

    $s = $db->prepare('SELECT id,name,ideal_risk,risk_tolerance FROM trading_systems WHERE user_id=? ORDER BY name');
    $s->execute([$userId]);
    $systems = $s->fetchAll();

    $s = $db->prepare('SELECT id,name,trading_system_id FROM strategies WHERE user_id=? ORDER BY name');
    $s->execute([$userId]);
    $strategies = $s->fetchAll();

    $s = $db->prepare('SELECT id,symbol,name FROM assets WHERE user_id=? ORDER BY symbol');
    $s->execute([$userId]);
    $assets = $s->fetchAll();

    $s = $db->prepare('SELECT id,name,start_time,end_time,timezone FROM trading_sessions WHERE user_id=? ORDER BY name');
    $s->execute([$userId]);
    $sessions = $s->fetchAll();

    $editTrade = null;
    $editId = filter_var($_GET['edit'] ?? null, FILTER_VALIDATE_INT);
    if ($editId) {
        $s = $db->prepare('SELECT * FROM trades WHERE id=? AND user_id=?');
        $s->execute([$editId,$userId]);
        $editTrade = $s->fetch() ?: null;
    }

    $where = ['t.user_id=?'];
    $params = [$userId];
    $symbol = trim((string)($_GET['symbol'] ?? ''));
    $side = $_GET['side'] ?? '';
    $status = $_GET['status'] ?? '';
    $systemFilter = filter_var($_GET['trading_system_id'] ?? null, FILTER_VALIDATE_INT);
    $assetFilter = filter_var($_GET['asset_id'] ?? null, FILTER_VALIDATE_INT);

    if ($symbol !== '') { $where[]='t.symbol LIKE ?'; $params[]='%'.$symbol.'%'; }
    if (in_array($side,['long','short'],true)) { $where[]='t.side=?'; $params[]=$side; }
    if (in_array($status,['open','closed'],true)) { $where[]='t.status=?'; $params[]=$status; }
    if ($systemFilter) { $where[]='t.trading_system_id=?'; $params[]=$systemFilter; }
    if ($assetFilter) { $where[]='t.asset_id=?'; $params[]=$assetFilter; }

    $s = $db->prepare('SELECT COUNT(*) FROM trades t WHERE '.implode(' AND ',$where));
    $s->execute($params);
    $total=(int)$s->fetchColumn();
    $page=max(1,(int)($_GET['page']??1));
    $pages=max(1,(int)ceil($total/25));
    $page=min($page,$pages);
    $offset=($page-1)*25;

    $s=$db->prepare(
        'SELECT t.*,a.name account_name,a.currency,ts.name system_name,st.name strategy_name,ar.symbol asset_symbol,sess.name session_name
         FROM trades t
         JOIN accounts a ON a.id=t.account_id AND a.user_id=t.user_id
         LEFT JOIN trading_systems ts ON ts.id=t.trading_system_id AND ts.user_id=t.user_id
         LEFT JOIN strategies st ON st.id=t.strategy_id AND st.user_id=t.user_id
         LEFT JOIN assets ar ON ar.id=t.asset_id AND ar.user_id=t.user_id
         LEFT JOIN trading_sessions sess ON sess.id=t.trading_session_id AND sess.user_id=t.user_id
         WHERE '.implode(' AND ',$where).' ORDER BY t.opened_at DESC,t.id DESC LIMIT 25 OFFSET '.$offset
    );
    $s->execute($params);
    $trades=$s->fetchAll();
    foreach($trades as &$t){$t['pnl']=trade_pnl($t);} unset($t);

    render('trades',[
        'title'=>'Trades','accounts'=>$accounts,'systems'=>$systems,'strategies'=>$strategies,'assets'=>$assets,'sessions'=>$sessions,
        'editTrade'=>$editTrade,'trades'=>$trades,
        'filters'=>['symbol'=>$symbol,'side'=>$side,'status'=>$status,'trading_system_id'=>$systemFilter,'asset_id'=>$assetFilter],
        'page'=>$page,'pages'=>$pages,'total'=>$total
    ]);
    return true;
}
