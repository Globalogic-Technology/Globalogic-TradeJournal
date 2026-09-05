<?php

declare(strict_types=1);

use PDO;
use RuntimeException;

final class AccountCsvTemplateService
{
    public const FIELDS = [
        'symbol' => 'Symbol',
        'type' => 'Type / Side',
        'opening_time' => 'Opening time',
        'closing_time' => 'Closing time',
        'quantity' => 'Quantity / Lots',
        'entry_price' => 'Entry price',
        'stop_loss' => 'Stop loss',
        'take_profit' => 'Take profit',
        'exit_price' => 'Exit price',
        'profit' => 'Profit',
        'fees' => 'Fees / Commission',
        'close_reason' => 'Close reason',
        'ticket' => 'Ticket / Trade ID',
    ];

    public static function normalizeMapping(string $json): array
    {
        $mapping = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($mapping)) throw new RuntimeException('Invalid CSV field mapping.');
        $result = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $value = trim((string)($mapping[$field] ?? ''));
            $result[$field] = $value;
        }
        foreach (['symbol','type','opening_time','closing_time','quantity','entry_price','exit_price','profit','fees','ticket'] as $required) {
            if ($result[$required] === '') throw new RuntimeException(self::FIELDS[$required].' mapping is required.');
        }
        return $result;
    }

    public static function save(PDO $db, int $userId, int $accountId, ?int $id, string $name, string $delimiter, bool $hasHeader, string $timezone, array $mapping, bool $default): int
    {
        if ($name === '' || strlen($name) > 120) throw new RuntimeException('Template name is required.');
        if (!in_array($delimiter, [',',';','|','\t'], true)) throw new RuntimeException('Invalid CSV delimiter.');
        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) throw new RuntimeException('Invalid CSV time zone.');
        $owned = $db->prepare('SELECT id FROM accounts WHERE id=? AND user_id=?');
        $owned->execute([$accountId,$userId]);
        if (!$owned->fetchColumn()) throw new RuntimeException('Invalid account.');
        $json=json_encode(self::normalizeMapping(json_encode($mapping, JSON_THROW_ON_ERROR)), JSON_THROW_ON_ERROR);
        $db->beginTransaction();
        try {
            if ($default) $db->prepare('UPDATE account_csv_templates SET is_default=0 WHERE account_id=? AND user_id=?')->execute([$accountId,$userId]);
            if ($id) {
                $q=$db->prepare('SELECT id FROM account_csv_templates WHERE id=? AND account_id=? AND user_id=?');
                $q->execute([$id,$accountId,$userId]);
                if (!$q->fetchColumn()) throw new RuntimeException('CSV template not found.');
                $db->prepare('UPDATE account_csv_templates SET name=?,delimiter_char=?,has_header=?,date_timezone=?,mapping_json=?,is_default=? WHERE id=? AND account_id=? AND user_id=?')->execute([$name,$delimiter,$hasHeader?1:0,$timezone,$json,$default?1:0,$id,$accountId,$userId]);
                $saved=(int)$id;
            } else {
                $db->prepare('INSERT INTO account_csv_templates(user_id,account_id,name,delimiter_char,has_header,date_timezone,mapping_json,is_default) VALUES(?,?,?,?,?,?,?,?)')->execute([$userId,$accountId,$name,$delimiter,$hasHeader?1:0,$timezone,$json,$default?1:0]);
                $saved=(int)$db->lastInsertId();
            }
            $db->commit();
            return $saved;
        } catch (Throwable $e) { if($db->inTransaction())$db->rollBack(); throw $e; }
    }

    public static function delete(PDO $db, int $userId, int $id): void
    {
        $s=$db->prepare('DELETE FROM account_csv_templates WHERE id=? AND user_id=?');
        $s->execute([$id,$userId]);
        if($s->rowCount()===0) throw new RuntimeException('CSV template not found.');
    }
}
