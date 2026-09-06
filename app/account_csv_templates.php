<?php

declare(strict_types=1);

require_once dirname(__DIR__).'/app/services/AccountCsvTemplateService.php';

function account_csv_template_default_mapping(): array
{
    return [
        'symbol'=>'symbol','type'=>'type','opening_time'=>'opening_time_utc','closing_time'=>'closing_time_utc',
        'quantity'=>'lots','entry_price'=>'opening_price','stop_loss'=>'stop_loss','take_profit'=>'take_profit',
        'exit_price'=>'closing_price','profit'=>'profit_usd','fees'=>'commission_usd','close_reason'=>'close_reason','ticket'=>'ticket'
    ];
}

function account_csv_templates_route(string $path,string $method,array $user): bool
{
    if($path!=='/account-csv-templates')return false;
    if($method!=='POST'){redirect('/accounts');return true;}
    verify_csrf();
    $db=db();$uid=(int)$user['id'];$action=(string)($_POST['action']??'');
    try{
        if($action==='delete'){
            AccountCsvTemplateService::delete($db,$uid,(int)($_POST['id']??0));
            flash('success','Trade CSV template deleted.');
        }elseif($action==='save'){
            $mapping=[];foreach(array_keys(AccountCsvTemplateService::FIELDS) as $field)$mapping[$field]=(string)($_POST['mapping'][$field]??'');
            AccountCsvTemplateService::save($db,$uid,(int)($_POST['account_id']??0),filter_var($_POST['id']??null,FILTER_VALIDATE_INT)?:null,trim((string)($_POST['name']??'')),(string)($_POST['delimiter_char']??','),(bool)($_POST['has_header']??0),trim((string)($_POST['date_timezone']??'UTC')),$mapping,isset($_POST['is_default']));
            flash('success','Trade CSV template saved.');
        }else{throw new InvalidArgumentException('Unknown CSV template action.');}
    }catch(InvalidArgumentException|RuntimeException $e){flash('error',$e->getMessage());}
    redirect('/accounts');
}

function account_csv_templates_data(array $user): array
{
    $uid=(int)$user['id'];$db=db();
    $q=$db->prepare('SELECT id,account_id,name,delimiter_char,has_header,date_timezone,mapping_json,is_default,created_at,updated_at FROM account_csv_templates WHERE user_id=? ORDER BY account_id,is_default DESC,name');
    $q->execute([$uid]);$templates=$q->fetchAll();
    foreach($templates as &$t){$t['mapping']=json_decode((string)$t['mapping_json'],true)?:[];}unset($t);
    return $templates;
}
