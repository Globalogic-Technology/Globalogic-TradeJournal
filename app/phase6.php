<?php

declare(strict_types=1);

use App\Services\CsvImportService;
use App\Services\AuditService;

require_once dirname(__DIR__) . '/app/services/CsvImportService.php';
require_once dirname(__DIR__) . '/app/services/AuditService.php';
require_once dirname(__DIR__) . '/app/services/AccountCsvTemplateService.php';

function phase6_route(string $path, string $method, array $user): bool
{
    if ($path === '/import') { phase6_import($method, $user); return true; }
    if ($path === '/imports') { phase6_import_history($user); return true; }
    if ($path === '/data-management') { phase6_data_management($method, $user); return true; }
    return false;
}

function phase6_import(string $method, array $user): void
{
    $db=db();$uid=(int)$user['id'];
    $s=$db->prepare('SELECT id,name,currency FROM accounts WHERE user_id=? ORDER BY name');$s->execute([$uid]);$accounts=$s->fetchAll();
    $templates=[];$q=$db->prepare('SELECT id,account_id,name,import_mode,delimiter_char,has_header,date_timezone,mapping_json,is_default FROM account_csv_templates WHERE user_id=? ORDER BY account_id,is_default DESC,name');$q->execute([$uid]);
    foreach($q->fetchAll() as $t){$t['mapping']=json_decode((string)$t['mapping_json'],true)?:[];$templates[]=$t;}
    $result=null;$preview=null;$selectedAccount=(int)($_POST['account_id']??$_GET['account_id']??0);$selectedTemplate=(int)($_POST['template_id']??$_GET['template_id']??0);
    if($selectedAccount&&$selectedTemplate){$q=$db->prepare('SELECT id FROM account_csv_templates WHERE id=? AND account_id=? AND user_id=?');$q->execute([$selectedTemplate,$selectedAccount,$uid]);if(!$q->fetchColumn())$selectedTemplate=0;}
    if(!$selectedTemplate&&$selectedAccount){$q=$db->prepare('SELECT id FROM account_csv_templates WHERE account_id=? AND user_id=? ORDER BY is_default DESC,id LIMIT 1');$q->execute([$selectedAccount,$uid]);$selectedTemplate=(int)($q->fetchColumn()?:0);}
    if($method==='POST'){
        verify_csrf();$action=(string)($_POST['action']??'import');$accountId=(int)($_POST['account_id']??0);$check=$db->prepare('SELECT id FROM accounts WHERE id=? AND user_id=?');$check->execute([$accountId,$uid]);if(!$check->fetchColumn())throw new InvalidArgumentException('Invalid account.');
        $templateId=(int)($_POST['template_id']??0);$q=$db->prepare('SELECT id,name,import_mode,delimiter_char,has_header,date_timezone,mapping_json FROM account_csv_templates WHERE id=? AND account_id=? AND user_id=?');$q->execute([$templateId,$accountId,$uid]);$template=$q->fetch();if(!$template)throw new InvalidArgumentException('Select a valid CSV template for this account.');$mapping=json_decode((string)$template['mapping_json'],true)?:[];
        $file=$_FILES['csv_file']??null;if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Please choose a CSV file.');if(($file['size']??0)>5*1024*1024)throw new InvalidArgumentException('CSV file is too large. Maximum size is 5 MB.');
        $service=new CsvImportService();$delimiter=(string)$template['delimiter_char'];$hasHeader=(bool)$template['has_header'];$timezone=(string)$template['date_timezone'];$mode=(string)($template['import_mode']??'standard');
        if($action==='preview'){$preview=$service->previewMapped($file['tmp_name'],$mapping,$delimiter,$hasHeader,$timezone,$mode);AuditService::log($db,$uid,'csv_preview','account',$accountId,'CSV preview requested using template '.$template['name']);}
        else{$result=$service->importMapped($db,$uid,$accountId,$file['tmp_name'],(string)$file['name'],$mapping,$delimiter,$hasHeader,$timezone,$mode);$status=$result['error_rows']>0&&$result['imported_rows']===0?'failed':'completed';$err=$result['errors']?implode(' ',$result['errors']):null;$db->prepare('INSERT INTO trade_imports(user_id,account_id,filename,total_rows,imported_rows,skipped_rows,error_rows,status,error_summary) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$uid,$accountId,(string)$file['name'],$result['total_rows'],$result['imported_rows'],$result['skipped_rows'],$result['error_rows'],$status,$err]);AuditService::log($db,$uid,'csv_import','account',$accountId,"Imported {$result['imported_rows']} trade(s) using template {$template['name']}; skipped {$result['skipped_rows']}; errors {$result['error_rows']}");flash($result['error_rows']?'error':'success',"Imported {$result['imported_rows']} trade(s); skipped {$result['skipped_rows']} duplicate(s); {$result['error_rows']} error(s).");redirect('/import');}
    }
    render('import',['title'=>'Import Trades','accounts'=>$accounts,'templates'=>$templates,'selectedAccount'=>$selectedAccount,'selectedTemplate'=>$selectedTemplate,'preview'=>$preview,'result'=>$result]);
}
function phase6_import_history(array $user): void{$s=db()->prepare('SELECT i.*,a.name account_name FROM trade_imports i INNER JOIN accounts a ON a.id=i.account_id AND a.user_id=i.user_id WHERE i.user_id=? ORDER BY i.created_at DESC LIMIT 100');$s->execute([(int)$user['id']]);render('imports',['title'=>'Import History','imports'=>$s->fetchAll()]);}
function phase6_data_management(string $method,array $user): void{
    $db=db();$uid=(int)$user['id'];
    if($method==='POST'){verify_csrf();$action=(string)($_POST['action']??'');$confirm=trim((string)($_POST['confirmation']??''));if($confirm!=='DELETE')throw new InvalidArgumentException('Type DELETE to confirm.');
        if($action==='delete_all_trades'){$stmt=$db->prepare('DELETE FROM trades WHERE user_id=?');$stmt->execute([$uid]);AuditService::log($db,$uid,'delete_all_trades','trade',null,'Deleted all trades; affected rows='.$stmt->rowCount());flash('success','All trades were deleted.');redirect('/data-management');}
        if($action==='delete_import_history'){$stmt=$db->prepare('DELETE FROM trade_imports WHERE user_id=?');$stmt->execute([$uid]);AuditService::log($db,$uid,'delete_import_history','import',null,'Deleted import history; affected rows='.$stmt->rowCount());flash('success','Import history was deleted.');redirect('/data-management');}
        throw new InvalidArgumentException('Unknown data-management action.');
    }
    $s=$db->prepare('SELECT COUNT(*) FROM trades WHERE user_id=?');$s->execute([$uid]);$tradeCount=(int)$s->fetchColumn();$q=$db->prepare('SELECT COUNT(*) FROM trade_imports WHERE user_id=?');$q->execute([$uid]);$importCount=(int)$q->fetchColumn();render('data_management',['title'=>'Data Management','tradeCount'=>$tradeCount,'importCount'=>$importCount]);
}
