<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use DateTime;
use DateTimeZone;
use RuntimeException;

final class CsvImportService
{
    public const REQUIRED = ['symbol','type','opening_time_utc','closing_time_utc','lots','opening_price','closing_price','profit_usd','commission_usd','close_reason','ticket'];
    private const CSV_ESCAPE = '';

    public function preview(string $path): array
    {
        return $this->previewMapped($path, self::defaultMapping(), ',', true, 'UTC');
    }

    public function import(PDO $db, int $userId, int $accountId, string $path, string $filename): array
    {
        return $this->importMapped($db,$userId,$accountId,$path,$filename,self::defaultMapping(),',',true,'UTC');
    }

    public function previewMapped(string $path, array $mapping, string $delimiter=',', bool $hasHeader=true, string $timezone='UTC'): array
    {
        [$headers,$rows]=$this->readMapped($path,$mapping,$delimiter,$hasHeader,1000);
        $missing=[];foreach(array_values($mapping) as $column)if($column!==''&&!in_array(strtolower($column),$headers,true))$missing[]=$column;
        if($missing)throw new RuntimeException('CSV columns not found: '.implode(', ',$missing));
        $valid=0;$invalid=0;$examples=[];
        foreach($rows as $i=>$row){try{$this->normalizeMapped($row,$mapping,$timezone);$valid++;}catch(\Throwable $e){$invalid++;if(count($examples)<10)$examples[]='Row '.($hasHeader?$i+2:$i+1).': '.$e->getMessage();}}
        return ['headers'=>$headers,'sample_count'=>count($rows),'valid'=>$valid,'invalid'=>$invalid,'errors'=>$examples];
    }

    public function importMapped(PDO $db,int $userId,int $accountId,string $path,string $filename,array $mapping,string $delimiter=',',bool $hasHeader=true,string $timezone='UTC'): array
    {
        [$headers,$rows]=$this->readMapped($path,$mapping,$delimiter,$hasHeader,null);
        $missing=[];foreach(array_values($mapping) as $column)if($column!==''&&!in_array(strtolower($column),$headers,true))$missing[]=$column;
        if($missing)throw new RuntimeException('CSV columns not found: '.implode(', ',$missing));
        $find=$db->prepare('SELECT 1 FROM trades WHERE account_id=? AND ticket=? LIMIT 1');
        $insert=$db->prepare('INSERT INTO trades(user_id,account_id,ticket,symbol,side,status,opened_at,closed_at,quantity,entry_price,stop_loss,take_profit,exit_price,fees,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $result=['total_rows'=>count($rows),'imported_rows'=>0,'skipped_rows'=>0,'error_rows'=>0,'errors'=>[]];
        foreach($rows as $i=>$raw){try{$r=$this->normalizeMapped($raw,$mapping,$timezone);$find->execute([$accountId,$r['ticket']]);if($find->fetchColumn()){$result['skipped_rows']++;continue;}$insert->execute([$userId,$accountId,$r['ticket'],$r['symbol'],$r['side'],'closed',$r['opened_at'],$r['closed_at'],$r['quantity'],$r['entry'],$r['stop_loss'],$r['take_profit'],$r['exit'],$r['commission'],'Imported CSV source profit='.$r['profit'].'; close_reason='.$r['reason']]);$result['imported_rows']++;}catch(\Throwable $e){$result['error_rows']++;if(count($result['errors'])<20)$result['errors'][]='Row '.($hasHeader?$i+2:$i+1).': '.$e->getMessage();}}
        return $result;
    }

    public static function defaultMapping(): array
    {
        return ['symbol'=>'symbol','type'=>'type','opening_time'=>'opening_time_utc','closing_time'=>'closing_time_utc','quantity'=>'lots','entry_price'=>'opening_price','stop_loss'=>'stop_loss','take_profit'=>'take_profit','exit_price'=>'closing_price','profit'=>'profit_usd','fees'=>'commission_usd','close_reason'=>'close_reason','ticket'=>'ticket'];
    }

    private function readMapped(string $path,array $mapping,string $delimiter,bool $hasHeader,?int $limit): array
    {
        if(!in_array($delimiter,[',',';','|','\t'],true))throw new RuntimeException('Invalid CSV delimiter.');
        $fh=fopen($path,'rb');if(!$fh)throw new RuntimeException('Unable to read CSV file.');
        $headers=$hasHeader?fgetcsv($fh,0,$delimiter,'"',self::CSV_ESCAPE):[];
        if($hasHeader&&!$headers){fclose($fh);throw new RuntimeException('CSV file is empty.');}
        if($hasHeader)$headers=array_map(static fn($v)=>strtolower(trim((string)$v)),$headers);
        else{$max=max(0,...array_map('strlen',array_keys($mapping)));$headers=[];}
        $rows=[];$n=0;
        while(($values=fgetcsv($fh,0,$delimiter,'"',self::CSV_ESCAPE))!==false){if($limit!==null&&$n>=$limit)break;if(count($values)===1&&trim((string)$values[0])==='')continue;$row=[];if($hasHeader){foreach($headers as $i=>$header)$row[$header]=(string)($values[$i]??'');}else{foreach(array_values($mapping) as $i=>$column)if($column!=='')$row[strtolower($column)]=(string)($values[$i]??'');}$rows[]=$row;$n++;}
        fclose($fh);return [$headers,$rows];
    }

    private function normalizeMapped(array $row,array $mapping,string $timezone): array
    {
        $get=static function(string $field)use($row,$mapping):string{$column=strtolower(trim((string)($mapping[$field]??'')));return $column===''?'':trim((string)($row[$column]??''));};
        $ticket=$get('ticket');$symbol=$get('symbol');$type=strtolower($get('type'));$quantity=$this->number($get('quantity'));$entry=$this->number($get('entry_price'));$exit=$this->number($get('exit_price'));$profit=$this->number($get('profit'));$commission=$this->number($get('fees'));$sl=$get('stop_loss')!==''?$this->number($get('stop_loss')):null;$tp=$get('take_profit')!==''?$this->number($get('take_profit')):null;
        if(!$ticket||!$symbol||$quantity<=0||$entry<0||$exit<0)throw new RuntimeException('invalid ticket, symbol, quantity or price');
        if(!in_array($type,['buy','sell','long','short'],true))throw new RuntimeException('invalid trade type');
        $opened=$this->date($get('opening_time'),$timezone);$closed=$this->date($get('closing_time'),$timezone);
        return ['ticket'=>$ticket,'symbol'=>$symbol,'side'=>in_array($type,['buy','long'],true)?'long':'short','quantity'=>$quantity,'entry'=>$entry,'stop_loss'=>$sl,'take_profit'=>$tp,'exit'=>$exit,'profit'=>$profit,'commission'=>$commission,'reason'=>$get('close_reason'),'opened_at'=>$opened,'closed_at'=>$closed];
    }
    private function number($v):float{$s=str_replace([',','$',' '],'',trim((string)$v));if($s===''||!is_numeric($s))throw new RuntimeException('invalid numeric value');return(float)$s;}
    private function date($v,string $timezone):string{$s=trim((string)$v);if($s==='')throw new RuntimeException('missing date/time');$d=new DateTime($s,new DateTimeZone($timezone));return$d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
}
