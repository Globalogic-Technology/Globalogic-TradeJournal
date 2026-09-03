<?php
declare(strict_types=1);
require_once dirname(__DIR__).'/config/config.php';
require_once dirname(__DIR__).'/config/database.php';

ini_set('session.use_strict_mode','1');
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax');
session_name('trading_journal');
session_start();

function e(mixed $v): string { return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function redirect(string $path): never { header('Location: '.$path); exit; }

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32));
    return $_SESSION['_csrf'];
}
function verify_csrf(): void {
    $token=$_POST['_csrf']??'';
    if (!is_string($token)||!hash_equals($_SESSION['_csrf']??'',$token)) {
        http_response_code(419); exit('Invalid CSRF token.');
    }
}
function current_user(): ?array {
    static $loaded=false,$user=null;
    if ($loaded) return $user;
    $loaded=true;
    $id=$_SESSION['user_id']??null;
    if (!$id||!ctype_digit((string)$id)) return null;
    $s=db()->prepare('SELECT id,name,email FROM users WHERE id=?');
    $s->execute([(int)$id]);
    return $user=$s->fetch()?:null;
}
function require_auth(): array {
    $u=current_user();
    if (!$u) redirect('/login');
    return $u;
}
function flash(string $type, ?string $message=null): ?string {
    if ($message!==null) { $_SESSION['_flash'][$type]=$message; return null; }
    $v=$_SESSION['_flash'][$type]??null; unset($_SESSION['_flash'][$type]); return $v;
}
function render(string $view,array $data=[]): void {
    extract($data,EXTR_SKIP);
    $viewFile=dirname(__DIR__).'/app/views/'.$view.'.php';
    if (!is_file($viewFile)) { http_response_code(500); exit('View not found.'); }
    require dirname(__DIR__).'/app/views/layout.php';
}
function decimal_input(?string $v,string $field,bool $required=true): ?float {
    $v=trim((string)$v);
    if ($v==='') { if($required) throw new InvalidArgumentException("$field is required."); return null; }
    if (!is_numeric($v)) throw new InvalidArgumentException("$field must be numeric.");
    return (float)$v;
}
function datetime_input(?string $v,string $field,bool $required=true): ?string {
    $v=trim((string)$v);
    if ($v==='') { if($required) throw new InvalidArgumentException("$field is required."); return null; }
    foreach (['Y-m-d\\TH:i','Y-m-d H:i:s'] as $format) {
        $dt=DateTime::createFromFormat($format,$v);
        if ($dt!==false) return $dt->format('Y-m-d H:i:s');
    }
    throw new InvalidArgumentException("$field is invalid.");
}
function trade_pnl(array $t): ?float {
    if (($t['status']??'')!=='closed'||$t['exit_price']===null) return null;
    $gross=$t['side']==='long'
        ?((float)$t['exit_price']-(float)$t['entry_price'])*(float)$t['quantity']
        :((float)$t['entry_price']-(float)$t['exit_price'])*(float)$t['quantity'];
    return $gross-(float)$t['fees'];
}
function trade_form_data(array $s,array $user): array {
    $accountId=filter_var($s['account_id']??null,FILTER_VALIDATE_INT);
    if(!$accountId||$accountId<1) throw new InvalidArgumentException('A valid account is required.');
    $q=db()->prepare('SELECT id FROM accounts WHERE id=? AND user_id=?');
    $q->execute([$accountId,$user['id']]);
    if(!$q->fetch()) throw new InvalidArgumentException('The selected account is invalid.');

    $ticket=trim((string)($s['ticket']??''));
    $symbol=strtoupper(trim((string)($s['symbol']??'')));
    $side=$s['side']??''; $status=$s['status']??'';
    if($ticket!==''&&strlen($ticket)>100) throw new InvalidArgumentException('Ticket is too long.');
    if($symbol===''||strlen($symbol)>50) throw new InvalidArgumentException('Symbol is required and must be 50 characters or fewer.');
    if(!in_array($side,['long','short'],true)||!in_array($status,['open','closed'],true)) throw new InvalidArgumentException('Invalid trade side or status.');

    $opened=datetime_input($s['opened_at']??null,'Opening time');
    $closed=datetime_input($s['closed_at']??null,'Closing time',false);
    $qty=decimal_input($s['quantity']??null,'Quantity');
    $entry=decimal_input($s['entry_price']??null,'Entry price');
    $sl=decimal_input($s['stop_loss']??null,'Stop loss',false);
    $tp=decimal_input($s['take_profit']??null,'Take profit',false);
    $exit=decimal_input($s['exit_price']??null,'Exit price',false);
    $fees=decimal_input($s['fees']??null,'Fees',false)??0;
    $notes=trim((string)($s['notes']??''));

    if($qty<=0||$entry<0||$fees<0) throw new InvalidArgumentException('Quantity must be positive; entry price and fees cannot be negative.');
    foreach([$sl,$tp,$exit] as $x) if($x!==null&&$x<0) throw new InvalidArgumentException('Prices cannot be negative.');
    if($status==='closed'&&($exit===null||$closed===null)) throw new InvalidArgumentException('Closed trades require exit price and closing time.');
    if($status==='open'&&$exit!==null) throw new InvalidArgumentException('Open trades cannot have an exit price.');
    if($closed!==null&&strtotime($closed)<strtotime($opened)) throw new InvalidArgumentException('Closing time cannot be before opening time.');

    return [$accountId,$ticket===''?null:$ticket,$symbol,$side,$status,$opened,$closed,$qty,$entry,$sl,$tp,$exit,$fees,$notes];
}
set_exception_handler(function(Throwable $e):void{ error_log((string)$e); http_response_code(500); exit('An unexpected error occurred.'); });
