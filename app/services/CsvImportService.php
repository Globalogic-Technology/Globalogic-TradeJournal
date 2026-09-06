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

    public function previewMapped(string $path, array $mapping, string $delimiter=',', bool $hasHeader=true, string $timezone='UTC', string $mode='standard'): array
    {
        if ($mode === 'interactive_brokers_orders') return $this->previewInteractiveBrokers($path,$delimiter,$hasHeader,$timezone);
        [$headers,$rows]=$this->readMapped($path,$mapping,$delimiter,$hasHeader,1000);
        $missing=[];foreach(array_values($mapping) as $column)if($column!==''&&!in_array(strtolower($column),$headers,true))$missing[]=$column;
        if($missing)throw new RuntimeException('CSV columns not found: '.implode(', ',$missing));
        $valid=0;$invalid=0;$examples=[];
        foreach($rows as $i=>$row){try{$this->normalizeMapped($row,$mapping,$timezone);$valid++;}catch(\Throwable $e){$invalid++;if(count($examples)<10)$examples[]='Row '.($hasHeader?$i+2:$i+1).': '.$e->getMessage();}}
        return ['headers'=>$headers,'sample_count'=>count($rows),'valid'=>$valid,'invalid'=>$invalid,'errors'=>$examples,'mode'=>$mode];
    }

    public function importMapped(PDO $db,int $userId,int $accountId,string $path,string $filename,array $mapping,string $delimiter=',',bool $hasHeader=true,string $timezone='UTC',string $mode='standard'): array
    {
        if ($mode === 'interactive_brokers_orders') return $this->importInteractiveBrokers($db,$userId,$accountId,$path,$filename,$delimiter,$hasHeader,$timezone);
        [$headers,$rows]=$this->readMapped($path,$mapping,$delimiter,$hasHeader,null);
        $missing=[];foreach(array_values($mapping) as $column)if($column!==''&&!in_array(strtolower($column),$headers,true))$missing[]=$column;
        if($missing)throw new RuntimeException('CSV columns not found: '.implode(', ',$missing));
        $find=$db->prepare('SELECT 1 FROM trades WHERE account_id=? AND ticket=? LIMIT 1');
        $insert=$db->prepare('INSERT INTO trades(user_id,account_id,ticket,symbol,side,status,opened_at,closed_at,quantity,entry_price,stop_loss,take_profit,exit_price,fees,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $result=['total_rows'=>count($rows),'imported_rows'=>0,'skipped_rows'=>0,'error_rows'=>0,'errors'=>[]];
        foreach($rows as $i=>$raw){try{$r=$this->normalizeMapped($raw,$mapping,$timezone);$find->execute([$accountId,$r['ticket']]);if($find->fetchColumn()){$result['skipped_rows']++;continue;}$insert->execute([$userId,$accountId,$r['ticket'],$r['symbol'],$r['side'],'closed',$r['opened_at'],$r['closed_at'],$r['quantity'],$r['entry'],$r['stop_loss'],$r['take_profit'],$r['exit'],$r['commission'],'Imported CSV source profit='.$r['profit'].'; close_reason='.$r['reason']]);$result['imported_rows']++;}catch(\Throwable $e){$result['error_rows']++;if(count($result['errors'])<20)$result['errors'][]='Row '.($hasHeader?$i+2:$i+1).': '.$e->getMessage();}}
        return $result;
    }

    private function previewInteractiveBrokers(string $path,string $delimiter,bool $hasHeader,string $timezone): array
    {
        [$headers,$rows]=$this->readRaw($path,$delimiter,$hasHeader,1000);
        $this->assertIbkrHeaders($headers);
        try{$trades=$this->normalizeInteractiveBrokers($rows,$timezone);return ['headers'=>$headers,'sample_count'=>count($rows),'valid'=>count($trades),'invalid'=>0,'errors'=>[],'mode'=>'interactive_brokers_orders','generated_trades'=>array_slice($trades,0,10)];}
        catch(\Throwable $e){return ['headers'=>$headers,'sample_count'=>count($rows),'valid'=>0,'invalid'=>1,'errors'=>[$e->getMessage()],'mode'=>'interactive_brokers_orders','generated_trades'=>[]];}
    }

    private function importInteractiveBrokers(PDO $db,int $userId,int $accountId,string $path,string $filename,string $delimiter,bool $hasHeader,string $timezone): array
    {
        [$headers,$rows]=$this->readRaw($path,$delimiter,$hasHeader,null);$this->assertIbkrHeaders($headers);$trades=$this->normalizeInteractiveBrokers($rows,$timezone);
        $find=$db->prepare('SELECT 1 FROM trades WHERE account_id=? AND ticket=? LIMIT 1');
        $insert=$db->prepare('INSERT INTO trades(user_id,account_id,ticket,symbol,side,status,opened_at,closed_at,quantity,entry_price,stop_loss,take_profit,exit_price,fees,notes) VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
        $result=['total_rows'=>count($rows),'imported_rows'=>0,'skipped_rows'=>0,'error_rows'=>0,'errors'=>[]];
        foreach($trades as $trade){try{$find->execute([$accountId,$trade['ticket']]);if($find->fetchColumn()){$result['skipped_rows']++;continue;}$insert->execute([$userId,$accountId,$trade['ticket'],$trade['symbol'],$trade['side'],$trade['status'],$trade['opened_at'],$trade['closed_at'],$trade['quantity'],$trade['entry'],$trade['stop_loss'],$trade['take_profit'],$trade['exit'],$trade['fees'],$trade['notes']]);$result['imported_rows']++;}catch(\Throwable $e){$result['error_rows']++;if(count($result['errors'])<20)$result['errors'][]=$trade['ticket'].': '.$e->getMessage();}}
        return $result;
    }

    private function normalizeInteractiveBrokers(array $rows,string $timezone): array
    {
        $positions=[];$pending=[];$trades=[];$sequence=0;
        foreach($rows as $row){
            $symbol=trim((string)($row['symbol']??''));$side=strtolower(trim((string)($row['side']??'')));$type=strtolower(trim((string)($row['type']??'')));$status=strtolower(trim((string)($row['status']??'')));$qty=$this->numberOptional($row['quantity']??'');$avg=$this->numberOptional($row['avg fill price']??'');$limit=$this->numberOptional($row['limit price']??'');$stop=$this->numberOptional($row['stop price']??'');$tp=$this->numberOptional($row['take profit']??'');$sl=$this->numberOptional($row['stop loss']??'');$time=$this->date((string)($row['last update time']??''),$timezone);$order=(string)($row['order id']??'');
            if($symbol===''||!in_array($side,['buy','sell'],true))continue;
            $isTp=str_contains($type,'take profit');$isSl=str_contains($type,'stop loss');$filled=$status==='filled';$positionSide=$side==='buy'?'long':'short';$key=$symbol.'|'.$positionSide;
            if($isTp||$isSl){
                $protectPrice=$isSl?($sl??$stop):($tp??$limit??$avg);
                if($protectPrice!==null)$this->applyProtection($positions,$pending,$key,$isSl?'stop_loss':'take_profit',$protectPrice);
                if(!$filled||$qty===null||$qty<=0||$avg===null)continue;
                $remaining=$qty;
                while($remaining>0){
                    $idx=$this->findPosition($positions,$key);if($idx===null)break;
                    $p=&$positions[$idx];$closeQty=min($remaining,$p['quantity']);$exitPrice=$avg;$profit=$p['side']==='long'?($exitPrice-$p['entry'])*$closeQty:($p['entry']-$exitPrice)*$closeQty;
                    $sequence++;$trades[]=['ticket'=>$p['ticket'].'-'.$order.'-'.$sequence,'symbol'=>$p['symbol'],'side'=>$p['side'],'status'=>'closed','opened_at'=>$p['opened_at'],'closed_at'=>$time,'quantity'=>$closeQty,'entry'=>$p['entry'],'stop_loss'=>$p['stop_loss'],'take_profit'=>$p['take_profit'],'exit'=>$exitPrice,'fees'=>0.0,'notes'=>'Imported Interactive Brokers Orders CSV; exit='.$type.'; source profit calculated from fill prices; entry_order='.$p['entry_order'].'; exit_order='.$order];
                    $p['quantity']-=$closeQty;$remaining-=$closeQty;if($p['quantity']<=0)array_splice($positions,$idx,1);unset($p);
                }
                continue;
            }
            if(!$filled||$qty===null||$qty<=0||$avg===null)continue;
            $position=['symbol'=>$symbol,'side'=>$positionSide,'quantity'=>$qty,'entry'=>$avg,'opened_at'=>$time,'entry_order'=>$order,'ticket'=>'IB-'.$order,'stop_loss'=>$sl,'take_profit'=>$tp];
            $this->applyPendingToPosition($position,$pending,$key);$positions[]=$position;
        }
        foreach($positions as $p){$sequence++;$trades[]=['ticket'=>$p['ticket'].'-OPEN-'.$sequence,'symbol'=>$p['symbol'],'side'=>$p['side'],'status'=>'open','opened_at'=>$p['opened_at'],'closed_at'=>null,'quantity'=>$p['quantity'],'entry'=>$p['entry'],'stop_loss'=>$p['stop_loss'],'take_profit'=>$p['take_profit'],'exit'=>null,'fees'=>0.0,'notes'=>'Imported Interactive Brokers Orders CSV; open position inferred from filled order.'];}
        usort($trades,static fn($a,$b)=>strcmp((string)$a['opened_at'],(string)$b['opened_at']));return $trades;
    }

    private function applyProtection(array &$positions,array &$pending,string $key,string $field,float $price): void
    {
        $idx=$this->findPosition($positions,$key);if($idx!==null){$positions[$idx][$field]=$price;return;}$pending[$key][$field]=$price;
    }
    private function applyPendingToPosition(array &$position,array &$pending,string $key): void
    {if(isset($pending[$key])){foreach($pending[$key] as $field=>$value)$position[$field]=$value;unset($pending[$key]);}}
    private function findPosition(array $positions,string $key):?int{foreach($positions as $i=>$p)if($p['symbol'].'|'.$p['side']===$key)return$i;return null;}

    private function assertIbkrHeaders(array $headers): void
    {
        $required=['symbol','side','type','quantity','avg fill price','limit price','stop price','take profit','stop loss','status','last update time','instruction','duration','order id'];$missing=[];foreach($required as $h)if(!in_array($h,$headers,true))$missing[]=$h;if($missing)throw new RuntimeException('Interactive Brokers Orders CSV columns not found: '.implode(', ',$missing));
    }

    private function readRaw(string $path,string $delimiter,bool $hasHeader,?int $limit): array
    {
        if(!in_array($delimiter,[',',';','|','\t'],true))throw new RuntimeException('Invalid CSV delimiter.');$fh=fopen($path,'rb');if(!$fh)throw new RuntimeException('Unable to read CSV file.');$headers=$hasHeader?fgetcsv($fh,0,$delimiter,'"',self::CSV_ESCAPE):[];if($hasHeader&&!$headers){fclose($fh);throw new RuntimeException('CSV file is empty.');}$headers=$hasHeader?array_map(static fn($v)=>strtolower(trim((string)$v)),$headers):[];$rows=[];$n=0;while(($values=fgetcsv($fh,0,$delimiter,'"',self::CSV_ESCAPE))!==false){if($limit!==null&&$n>=$limit)break;if(count($values)===1&&trim((string)$values[0])==='')continue;$row=[];foreach($headers as $i=>$header)$row[$header]=(string)($values[$i]??'');$rows[]=$row;$n++;}fclose($fh);return[$headers,$rows];
    }

    public static function defaultMapping(): array
    {return ['symbol'=>'symbol','type'=>'type','opening_time'=>'opening_time_utc','closing_time'=>'closing_time_utc','quantity'=>'lots','entry_price'=>'opening_price','stop_loss'=>'stop_loss','take_profit'=>'take_profit','exit_price'=>'closing_price','profit'=>'profit_usd','fees'=>'commission_usd','close_reason'=>'close_reason','ticket'=>'ticket'];}
    private function readMapped(string $path,array $mapping,string $delimiter,bool $hasHeader,?int $limit): array
    {$fh=fopen($path,'rb');if(!$fh)throw new RuntimeException('Unable to read CSV file.');$headers=$hasHeader?fgetcsv($fh,0,$delimiter,'"',self::CSV_ESCAPE):[];if($hasHeader&&!$headers){fclose($fh);throw new RuntimeException('CSV file is empty.');}$headers=$hasHeader?array_map(static fn($v)=>strtolower(trim((string)$v)),$headers):[];$rows=[];$n=0;while(($values=fgetcsv($fh,0,$delimiter,'"',self::CSV_ESCAPE))!==false){if($limit!==null&&$n>=$limit)break;if(count($values)===1&&trim((string)$values[0])==='')continue;$row=[];if($hasHeader){foreach($headers as $i=>$header)$row[$header]=(string)($values[$i]??'');}else{foreach(array_values($mapping) as $i=>$column)if($column!=='')$row[strtolower($column)]=(string)($values[$i]??'');}$rows[]=$row;$n++;}fclose($fh);return[$headers,$rows];}
    private function normalizeMapped(array $row,array $mapping,string $timezone): array
    {$get=static function(string $field)use($row,$mapping):string{$column=strtolower(trim((string)($mapping[$field]??''));return $column===''?'':trim((string)($row[$column]??''));};$ticket=$get('ticket');$symbol=$get('symbol');$type=strtolower($get('type'));$quantity=$this->number($get('quantity'));$entry=$this->number($get('entry_price'));$exit=$this->number($get('exit_price'));$profit=$this->number($get('profit'));$commission=$this->number($get('fees'));$sl=$get('stop_loss')!==''?$this->number($get('stop_loss')):null;$tp=$get('take_profit')!==''?$this->number($get('take_profit')):null;if(!$ticket||!$symbol||$quantity<=0||$entry<0||$exit<0)throw new RuntimeException('invalid ticket, symbol, quantity or price');if(!in_array($type,['buy','sell','long','short'],true))throw new RuntimeException('invalid trade type');$opened=$this->date($get('opening_time'),$timezone);$closed=$this->date($get('closing_time'),$timezone);return ['ticket'=>$ticket,'symbol'=>$symbol,'side'=>in_array($type,['buy','long'],true)?'long':'short','quantity'=>$quantity,'entry'=>$entry,'stop_loss'=>$sl,'take_profit'=>$tp,'exit'=>$exit,'profit'=>$profit,'commission'=>$commission,'reason'=>$get('close_reason'),'opened_at'=>$opened,'closed_at'=>$closed];}
    private function number($v):float{$s=str_replace([',','$',' '],'',trim((string)$v));if($s===''||!is_numeric($s))throw new RuntimeException('invalid numeric value');return(float)$s;}
    private function numberOptional($v):?float{$s=str_replace([',','$',' '],'',trim((string)$v));if($s===''||!is_numeric($s))return null;return(float)$s;}
    private function date($v,string $timezone):string{$s=trim((string)$v);if($s==='')throw new RuntimeException('missing date/time');$d=new DateTime($s,new DateTimeZone($timezone));return$d->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');}
}
