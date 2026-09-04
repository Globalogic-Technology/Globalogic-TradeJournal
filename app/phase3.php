<?php

declare(strict_types=1);

/**
 * Phase 3 configuration module. It deliberately stays outside the Phase 2 P&L/risk
 * calculation helpers so there is one source of truth for calculations.
 */
function phase3_route(string $path, string $method, array $user): bool
{
    $routes = [
        '/systems' => 'systems',
        '/strategies' => 'strategies',
        '/assets' => 'assets',
        '/asset-fees' => 'fees',
        '/sessions' => 'sessions',
        '/risk-settings' => 'risk',
        '/account-settings' => 'account',
    ];

    if (!isset($routes[$path])) return false;

    $resource = $routes[$path];
    if ($method === 'POST') {
        verify_csrf();
        phase3_save($resource, $user);
        return true;
    }

    phase3_render($resource, $user);
    return true;
}

function phase3_positive_decimal(string $value, string $label, float $max = PHP_FLOAT_MAX): float
{
    if ($value === '' || !is_numeric($value)) throw new InvalidArgumentException("$label must be numeric.");
    $number = (float)$value;
    if ($number < 0 || $number > $max) throw new InvalidArgumentException("$label is outside the allowed range.");
    return $number;
}

function phase3_user_row(string $table, int $id, int $userId): ?array
{
    $allowed = ['trading_systems','strategies','assets','asset_fees','trading_sessions','risk_settings','accounts'];
    if (!in_array($table, $allowed, true)) throw new InvalidArgumentException('Invalid configuration table.');
    $s = db()->prepare("SELECT * FROM {$table} WHERE id=? AND user_id=?");
    $s->execute([$id, $userId]);
    return $s->fetch() ?: null;
}

function phase3_save(string $resource, array $user): void
{
    $uid = (int)$user['id'];
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    $action = (string)($_POST['action'] ?? 'save');

    if ($resource === 'systems') {
        if ($action === 'delete' && $id) { phase3_user_row('trading_systems',$id,$uid) ?: throw new InvalidArgumentException('System not found.'); db()->prepare('DELETE FROM trading_systems WHERE id=? AND user_id=?')->execute([$id,$uid]); flash('success','Trading system deleted.'); redirect('/systems'); }
        $name=trim((string)($_POST['name']??'')); $description=trim((string)($_POST['description']??''));
        $ideal=phase3_positive_decimal(trim((string)($_POST['ideal_risk']??'')),'Ideal risk'); $tol=phase3_positive_decimal(trim((string)($_POST['risk_tolerance']??'')),'Risk tolerance',100);
        if ($name==='' || strlen($name)>120) throw new InvalidArgumentException('System name is required.');
        if ($id) { phase3_user_row('trading_systems',$id,$uid) ?: throw new InvalidArgumentException('System not found.'); db()->prepare('UPDATE trading_systems SET name=?,description=?,ideal_risk=?,risk_tolerance=? WHERE id=? AND user_id=?')->execute([$name,$description,$ideal,$tol,$id,$uid]); }
        else db()->prepare('INSERT INTO trading_systems(user_id,name,description,ideal_risk,risk_tolerance) VALUES(?,?,?,?,?)')->execute([$uid,$name,$description,$ideal,$tol]);
        flash('success','Trading system saved.'); redirect('/systems');
    }

    if ($resource === 'strategies') {
        if ($action === 'delete' && $id) { phase3_user_row('strategies',$id,$uid) ?: throw new InvalidArgumentException('Strategy not found.'); db()->prepare('DELETE FROM strategies WHERE id=? AND user_id=?')->execute([$id,$uid]); flash('success','Strategy deleted.'); redirect('/strategies'); }
        $systemId=filter_var($_POST['trading_system_id']??null,FILTER_VALIDATE_INT) ?: 0; $system=phase3_user_row('trading_systems',$systemId,$uid); if(!$system) throw new InvalidArgumentException('Select a valid trading system.');
        $name=trim((string)($_POST['name']??'')); $description=trim((string)($_POST['description']??'')); if($name===''||strlen($name)>120) throw new InvalidArgumentException('Strategy name is required.');
        if($id){phase3_user_row('strategies',$id,$uid) ?: throw new InvalidArgumentException('Strategy not found.'); db()->prepare('UPDATE strategies SET trading_system_id=?,name=?,description=? WHERE id=? AND user_id=?')->execute([$systemId,$name,$description,$id,$uid]);}
        else db()->prepare('INSERT INTO strategies(user_id,trading_system_id,name,description) VALUES(?,?,?,?)')->execute([$uid,$systemId,$name,$description]);
        flash('success','Strategy saved.'); redirect('/strategies');
    }

    if ($resource === 'assets') {
        if ($action === 'delete' && $id) { phase3_user_row('assets',$id,$uid) ?: throw new InvalidArgumentException('Asset not found.'); db()->prepare('DELETE FROM assets WHERE id=? AND user_id=?')->execute([$id,$uid]); flash('success','Asset deleted.'); redirect('/assets'); }
        $symbol=strtoupper(trim((string)($_POST['symbol']??''))); $name=trim((string)($_POST['name']??'')); $configuration=trim((string)($_POST['configuration']??''));
        if($symbol===''||strlen($symbol)>50||$name===''||strlen($name)>120) throw new InvalidArgumentException('Asset symbol and name are required.');
        if($configuration!==''){json_decode($configuration,true,512,JSON_THROW_ON_ERROR);}
        if($id){phase3_user_row('assets',$id,$uid) ?: throw new InvalidArgumentException('Asset not found.'); db()->prepare('UPDATE assets SET symbol=?,name=?,configuration=? WHERE id=? AND user_id=?')->execute([$symbol,$name,$configuration===''?null:$configuration,$id,$uid]);}
        else db()->prepare('INSERT INTO assets(user_id,symbol,name,configuration) VALUES(?,?,?,?)')->execute([$uid,$symbol,$name,$configuration===''?null:$configuration]);
        flash('success','Asset saved.'); redirect('/assets');
    }

    if ($resource === 'fees') {
        if ($action === 'delete' && $id) { phase3_user_row('asset_fees',$id,$uid) ?: throw new InvalidArgumentException('Fee not found.'); db()->prepare('DELETE FROM asset_fees WHERE id=? AND user_id=?')->execute([$id,$uid]); flash('success','Asset fee deleted.'); redirect('/asset-fees'); }
        $assetId=filter_var($_POST['asset_id']??null,FILTER_VALIDATE_INT)?:0; if(!phase3_user_row('assets',$assetId,$uid)) throw new InvalidArgumentException('Select a valid asset.');
        $type=trim((string)($_POST['fee_type']??'')); $amount=phase3_positive_decimal(trim((string)($_POST['fee_amount']??'')),'Fee amount'); $currency=strtoupper(trim((string)($_POST['fee_currency']??'USD')));
        if($type===''||strlen($type)>50||!preg_match('/^[A-Z]{3}$/',$currency)) throw new InvalidArgumentException('Fee type and a valid 3-letter currency are required.');
        if($id){phase3_user_row('asset_fees',$id,$uid) ?: throw new InvalidArgumentException('Fee not found.'); db()->prepare('UPDATE asset_fees SET asset_id=?,fee_type=?,fee_amount=?,fee_currency=? WHERE id=? AND user_id=?')->execute([$assetId,$type,$amount,$currency,$id,$uid]);}
        else db()->prepare('INSERT INTO asset_fees(user_id,asset_id,fee_type,fee_amount,fee_currency) VALUES(?,?,?,?,?)')->execute([$uid,$assetId,$type,$amount,$currency]);
        flash('success','Asset fee saved.'); redirect('/asset-fees');
    }

    if ($resource === 'sessions') {
        if ($action === 'delete' && $id) { phase3_user_row('trading_sessions',$id,$uid) ?: throw new InvalidArgumentException('Session not found.'); db()->prepare('DELETE FROM trading_sessions WHERE id=? AND user_id=?')->execute([$id,$uid]); flash('success','Trading session deleted.'); redirect('/sessions'); }
        $name=trim((string)($_POST['name']??'')); $start=(string)($_POST['start_time']??''); $end=(string)($_POST['end_time']??''); $tz=trim((string)($_POST['timezone']??'UTC'));
        if($name===''||strlen($name)>120||!preg_match('/^\d{2}:\d{2}$/',$start)||!preg_match('/^\d{2}:\d{2}$/',$end)||!in_array($tz,DateTimeZone::listIdentifiers(),true)) throw new InvalidArgumentException('Invalid trading session values.');
        if($id){phase3_user_row('trading_sessions',$id,$uid) ?: throw new InvalidArgumentException('Session not found.'); db()->prepare('UPDATE trading_sessions SET name=?,start_time=?,end_time=?,timezone=? WHERE id=? AND user_id=?')->execute([$name,$start,$end,$tz,$id,$uid]);}
        else db()->prepare('INSERT INTO trading_sessions(user_id,name,start_time,end_time,timezone) VALUES(?,?,?,?,?)')->execute([$uid,$name,$start,$end,$tz]);
        flash('success','Trading session saved.'); redirect('/sessions');
    }

    if ($resource === 'risk') {
        if ($action === 'delete' && $id) { phase3_user_row('risk_settings',$id,$uid) ?: throw new InvalidArgumentException('Risk setting not found.'); db()->prepare('DELETE FROM risk_settings WHERE id=? AND user_id=?')->execute([$id,$uid]); flash('success','Risk setting deleted.'); redirect('/risk-settings'); }
        $accountId=filter_var($_POST['account_id']??null,FILTER_VALIDATE_INT)?:null; $systemId=filter_var($_POST['trading_system_id']??null,FILTER_VALIDATE_INT)?:null;
        if($accountId!==null && !phase3_user_row('accounts',$accountId,$uid)) throw new InvalidArgumentException('Invalid account.');
        if($systemId!==null && !phase3_user_row('trading_systems',$systemId,$uid)) throw new InvalidArgumentException('Invalid trading system.');
        if($accountId===null && $systemId===null) throw new InvalidArgumentException('Choose an account or trading system scope.');
        $ideal=phase3_positive_decimal(trim((string)($_POST['ideal_risk']??'')),'Ideal risk'); $tol=phase3_positive_decimal(trim((string)($_POST['risk_tolerance']??'')),'Risk tolerance',100);
        if($id){phase3_user_row('risk_settings',$id,$uid) ?: throw new InvalidArgumentException('Risk setting not found.'); db()->prepare('UPDATE risk_settings SET account_id=?,trading_system_id=?,ideal_risk=?,risk_tolerance=? WHERE id=? AND user_id=?')->execute([$accountId,$systemId,$ideal,$tol,$id,$uid]);}
        else { $q=db()->prepare('SELECT id FROM risk_settings WHERE user_id=? AND ((account_id=? AND ? IS NOT NULL) OR (trading_system_id=? AND ? IS NOT NULL)) LIMIT 1'); $q->execute([$uid,$accountId,$accountId,$systemId,$systemId]); $existing=$q->fetchColumn(); if($existing){db()->prepare('UPDATE risk_settings SET account_id=?,trading_system_id=?,ideal_risk=?,risk_tolerance=? WHERE id=? AND user_id=?')->execute([$accountId,$systemId,$ideal,$tol,$existing,$uid]);}else db()->prepare('INSERT INTO risk_settings(user_id,account_id,trading_system_id,ideal_risk,risk_tolerance) VALUES(?,?,?,?,?)')->execute([$uid,$accountId,$systemId,$ideal,$tol]); }
        flash('success','Risk setting saved.'); redirect('/risk-settings');
    }

    if ($resource === 'account') {
        $accountId=filter_var($_POST['account_id']??null,FILTER_VALIDATE_INT)?:0; $account=phase3_user_row('accounts',$accountId,$uid); if(!$account) throw new InvalidArgumentException('Select a valid account.');
        $currency=strtoupper(trim((string)($_POST['currency']??$account['currency']))); $balance=phase3_positive_decimal(trim((string)($_POST['initial_balance']??$account['initial_balance'])),'Balance');
        $systemId=filter_var($_POST['default_system_id']??null,FILTER_VALIDATE_INT)?:null; if($systemId!==null && !phase3_user_row('trading_systems',$systemId,$uid)) throw new InvalidArgumentException('Invalid default system.');
        $ideal=phase3_positive_decimal(trim((string)($_POST['ideal_risk']??'0')),'Ideal risk'); $tol=phase3_positive_decimal(trim((string)($_POST['risk_tolerance']??'10')),'Risk tolerance',100);
        if(!preg_match('/^[A-Z]{3}$/',$currency)) throw new InvalidArgumentException('Currency must be a 3-letter code.');
        db()->prepare('UPDATE accounts SET currency=?,initial_balance=?,default_system_id=?,ideal_risk=?,risk_tolerance=? WHERE id=? AND user_id=?')->execute([$currency,$balance,$systemId,$ideal,$tol,$accountId,$uid]);
        flash('success','Account configuration saved.'); redirect('/account-settings');
    }
}

function phase3_render(string $resource, array $user): void
{
    $uid=(int)$user['id'];
    $data=['resource'=>$resource,'title'=>'Configuration'];
    switch($resource){
        case 'systems': $s=db()->prepare('SELECT * FROM trading_systems WHERE user_id=? ORDER BY name');$s->execute([$uid]);$data['rows']=$s->fetchAll();$data['form']=['action'=>'/systems','fields'=>['name','description','ideal_risk','risk_tolerance']];$data['title']='Trading Systems';break;
        case 'strategies': $s=db()->prepare('SELECT s.*,ts.name system_name FROM strategies s JOIN trading_systems ts ON ts.id=s.trading_system_id WHERE s.user_id=? ORDER BY ts.name,s.name');$s->execute([$uid]);$data['rows']=$s->fetchAll();$q=db()->prepare('SELECT id,name FROM trading_systems WHERE user_id=? ORDER BY name');$q->execute([$uid]);$data['systems']=$q->fetchAll();$data['form']=['action'=>'/strategies','fields'=>['trading_system_id','name','description']];$data['title']='Strategies';break;
        case 'assets': $s=db()->prepare('SELECT * FROM assets WHERE user_id=? ORDER BY symbol');$s->execute([$uid]);$data['rows']=$s->fetchAll();$data['form']=['action'=>'/assets','fields'=>['symbol','name','configuration']];$data['title']='Assets';break;
        case 'fees': $s=db()->prepare('SELECT f.*,a.symbol asset_symbol FROM asset_fees f JOIN assets a ON a.id=f.asset_id WHERE f.user_id=? ORDER BY a.symbol,f.fee_type');$s->execute([$uid]);$data['rows']=$s->fetchAll();$q=db()->prepare('SELECT id,symbol,name FROM assets WHERE user_id=? ORDER BY symbol');$q->execute([$uid]);$data['assets']=$q->fetchAll();$data['form']=['action'=>'/asset-fees','fields'=>['asset_id','fee_type','fee_amount','fee_currency']];$data['title']='Asset Fees';break;
        case 'sessions': $s=db()->prepare('SELECT * FROM trading_sessions WHERE user_id=? ORDER BY name');$s->execute([$uid]);$data['rows']=$s->fetchAll();$data['timezones']=DateTimeZone::listIdentifiers();$data['form']=['action'=>'/sessions','fields'=>['name','start_time','end_time','timezone']];$data['title']='Trading Sessions';break;
        case 'risk': $s=db()->prepare('SELECT r.*,a.name account_name,ts.name system_name FROM risk_settings r LEFT JOIN accounts a ON a.id=r.account_id LEFT JOIN trading_systems ts ON ts.id=r.trading_system_id WHERE r.user_id=? ORDER BY r.id DESC');$s->execute([$uid]);$data['rows']=$s->fetchAll();$q=db()->prepare('SELECT id,name FROM accounts WHERE user_id=? ORDER BY name');$q->execute([$uid]);$data['accounts']=$q->fetchAll();$q=db()->prepare('SELECT id,name FROM trading_systems WHERE user_id=? ORDER BY name');$q->execute([$uid]);$data['systems']=$q->fetchAll();$data['form']=['action'=>'/risk-settings','fields'=>['account_id','trading_system_id','ideal_risk','risk_tolerance']];$data['title']='Risk Settings';break;
        case 'account':
            $s=db()->prepare('SELECT a.*,ts.name default_system_name FROM accounts a LEFT JOIN trading_systems ts ON ts.id=a.default_system_id WHERE a.user_id=? ORDER BY a.name');
            $s->execute([$uid]);
            $data['rows']=$s->fetchAll();
            // The Account Configuration view expects $accounts for its account selector.
            // Use the same user-owned account rows that are displayed in the table.
            $data['accounts']=$data['rows'];
            $q=db()->prepare('SELECT id,name FROM trading_systems WHERE user_id=? ORDER BY name');
            $q->execute([$uid]);
            $data['systems']=$q->fetchAll();
            $data['form']=['action'=>'/account-settings','fields'=>['account_id','initial_balance','currency','default_system_id','ideal_risk','risk_tolerance']];
            $data['title']='Account Configuration';
            break;
    }
    render('phase3', $data);
}
