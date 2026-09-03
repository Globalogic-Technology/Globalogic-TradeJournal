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
        [$headers, $rows] = $this->read($path, 1000);
        $missing = array_values(array_diff(self::REQUIRED, $headers));
        if ($missing) throw new RuntimeException('Missing columns: '.implode(', ', $missing));
        $valid=0;$invalid=0;$examples=[];
        foreach($rows as $i=>$row){try{$this->normalize($row);$valid++;}catch(\Throwable $e){$invalid++;if(count($examples)<10)$examples[]='Row '.($i+2).': '.$e->getMessage();}}
        return ['headers'=>$headers,'sample_count'=>count($rows),'valid'=>$valid,'invalid'=>$invalid,'errors'=>$examples];
    }

    public function import(PDO $db, int $userId, int $accountId, string $path, string $filename): array
    {
        [$headers, $rows] = $this->read($path);
        $missing=array_values(array_diff(self::REQUIRED,$headers)); if($missing) throw new RuntimeException('Missing columns: '.implode(', ', $missing));
        $find=$db->prepare('SELECT 1 FROM trades WHERE account_id=? AND ticket=? LIMIT 1');
        $insert=$db->prepare('INSERT INTO trades(user_id,account_id,ticket,symbol,side,status,opened_at,closed_at,quantity,entry_price,exit_price,fees,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $result=['total_rows'=>count($rows),'imported_rows'=>0,'skipped_rows'=>0,'error_rows'=>0,'errors'=>[]];
        foreach($rows as $i=>$raw){try{$r=$this->normalize($raw);$find->execute([$accountId,$r['ticket']]);if($find->fetchColumn()){ $result['skipped_rows']++; continue; }$insert->execute([$userId,$accountId,$r['ticket'],$r['symbol'],$r['side'],'closed',$r['opened_at'],$r['closed_at'],$r['quantity'],$r['entry'],$r['exit'],$r['commission'],'Imported CSV source profit_usd='.$r['profit'].'; close_reason='.$r['reason']]);$result['imported_rows']++;}catch(\Throwable $e){$result['error_rows']++;if(count($result['errors'])<20)$result['errors'][]='Row '.($i+2).': '.$e->getMessage();}}
        return $result;
    }

    private function read(string $path, ?int $limit=null): array
    {
        $fh=fopen($path,'rb');if(!$fh)throw new RuntimeException('Unable to read CSV file.');
        $headers=fgetcsv($fh, 0, ',', '"', self::CSV_ESCAPE);if(!$headers){fclose($fh);throw new RuntimeException('CSV file is empty.');}
        $headers=array_map(static fn($v)=>strtolower(trim((string)$v)), $headers);$rows=[];$n=0;
        while(($values=fgetcsv($fh, 0, ',', '"', self::CSV_ESCAPE))!==false){if($limit!==null&&$n>=$limit)break;if(count($values)===1&&trim((string)$values[0])==='')continue;$rows[]=array_combine($headers,array_pad($values,count($headers),''));$n++;}
        fclose($fh);return [$headers,$rows];
    }

    private function normalize(array $r): array
    {
        $ticket=trim((string)($r['ticket']??''));$symbol=trim((string)($r['symbol']??''));$type=strtolower(trim((string)($r['type']??'')));
        $lots=$this->number($r['lots']??null);$entry=$this->number($r['opening_price']??null);$exit=$this->number($r['closing_price']??null);$profit=$this->number($r['profit_usd']??null);$commission=$this->number($r['commission_usd']??null);
        if(!$ticket||!$symbol||$lots<=0||$entry<0||$exit<0)throw new RuntimeException('invalid ticket, symbol, quantity or price');
        if(!in_array($type,['buy','sell','long','short'],true))throw new RuntimeException('invalid trade type');
        $opened=$this->date($r['opening_time_utc']??null);$closed=$this->date($r['closing_time_utc']??null);
        return ['ticket'=>$ticket,'symbol'=>$symbol,'side'=>in_array($type,['buy','long'],true)?'long':'short','quantity'=>$lots,'entry'=>$entry,'exit'=>$exit,'profit'=>$profit,'commission'=>$commission,'reason'=>trim((string)($r['close_reason']??'')),'opened_at'=>$opened,'closed_at'=>$closed];
    }
    private function number($v): float { $s=str_replace([',','$',' '],'',trim((string)$v));if($s===''||!is_numeric($s))throw new RuntimeException('invalid numeric value');return (float)$s; }
    private function date($v): string {$s=trim((string)$v);if($s==='')throw new RuntimeException('missing date/time');$d=new DateTime($s,new DateTimeZone('UTC'));return $d->format('Y-m-d H:i:s');}
}
