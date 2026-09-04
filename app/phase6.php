<?php

declare(strict_types=1);

use App\Services\CsvImportService;

require_once dirname(__DIR__) . '/app/services/CsvImportService.php';

function phase6_route(string $path, string $method, array $user): bool
{
    if ($path === '/import') {
        phase6_import($method, $user);
        return true;
    }
    if ($path === '/imports') {
        phase6_import_history($user);
        return true;
    }
    if ($path === '/data-management') {
        phase6_data_management($method, $user);
        return true;
    }
    return false;
}

function phase6_import(string $method, array $user): void
{
    $db=db(); $uid=(int)$user['id'];
    $s=$db->prepare('SELECT id,name,currency FROM accounts WHERE user_id=? ORDER BY name');$s->execute([$uid]);$accounts=$s->fetchAll();
    $result=null;$preview=null;
    if($method==='POST'){
        verify_csrf();$action=(string)($_POST['action']??'import');$accountId=(int)($_POST['account_id']??0);$check=$db->prepare('SELECT id FROM accounts WHERE id=? AND user_id=?');$check->execute([$accountId,$uid]);if(!$check->fetchColumn())throw new InvalidArgumentException('Invalid account.');
        $file=$_FILES['csv_file']??null;if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)throw new InvalidArgumentException('Please choose a CSV file.');if(($file['size']??0)>5*1024*1024)throw new InvalidArgumentException('CSV file is too large. Maximum size is 5 MB.');
        $service=new CsvImportService();
        if($action==='preview'){$preview=$service->preview($file['tmp_name']);}
        else{$result=$service->import($db,$uid,$accountId,$file['tmp_name'],(string)$file['name']);$status=$result['error_rows']>0&&$result['imported_rows']===0?'failed':'completed';$err=$result['errors']?implode(' ',$result['errors']):null;$db->prepare('INSERT INTO trade_imports(user_id,account_id,filename,total_rows,imported_rows,skipped_rows,error_rows,status,error_summary) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$uid,$accountId,(string)$file['name'],$result['total_rows'],$result['imported_rows'],$result['skipped_rows'],$result['error_rows'],$status,$err]);flash($result['error_rows']?'error':'success',"Imported {$result['imported_rows']} trade(s); skipped {$result['skipped_rows']} duplicate(s); {$result['error_rows']} error(s).");redirect('/import');}
    }
    render('import',['title'=>'Import Trades','accounts'=>$accounts,'preview'=>$preview,'result'=>$result]);
}

function phase6_import_history(array $user): void
{
    $s=db()->prepare('SELECT i.*,a.name account_name FROM trade_imports i INNER JOIN accounts a ON a.id=i.account_id AND a.user_id=i.user_id WHERE i.user_id=? ORDER BY i.created_at DESC LIMIT 100');$s->execute([(int)$user['id']]);render('imports',['title'=>'Import History','imports'=>$s->fetchAll()]);
}

function phase6_data_management(string $method,array $user): void
{
    if($method==='POST'){
        verify_csrf();$action=(string)($_POST['action']??'');
        if($action==='delete_all_trades'){$confirm=trim((string)($_POST['confirmation']??''));if($confirm!=='DELETE')throw new InvalidArgumentException('Type DELETE to confirm.');db()->prepare('DELETE FROM trades WHERE user_id=?')->execute([(int)$user['id']]);flash('success','All trades were deleted.');redirect('/data-management');}
        if($action==='delete_import_history'){$confirm=trim((string)($_POST['confirmation']??''));if($confirm!=='DELETE')throw new InvalidArgumentException('Type DELETE to confirm.');db()->prepare('DELETE FROM trade_imports WHERE user_id=?')->execute([(int)$user['id']]);flash('success','Import history was deleted.');redirect('/data-management');}
        throw new InvalidArgumentException('Unknown data-management action.');
    }
    $uid=(int)$user['id'];$s=db()->prepare('SELECT COUNT(*) FROM trades WHERE user_id=?');$s->execute([$uid]);$tradeCount=(int)$s->fetchColumn();$s=$db??null;$q=db()->prepare('SELECT COUNT(*) FROM trade_imports WHERE user_id=?');$q->execute([$uid]);$importCount=(int)$q->fetchColumn();render('data_management',['title'=>'Data Management','tradeCount'=>$tradeCount,'importCount'=>$importCount]);
}
