<?php
declare(strict_types=1);
function db(): PDO {
    static $pdo=null;
    if ($pdo instanceof PDO) return $pdo;
    $dsn=sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        env('DB_HOST','127.0.0.1'),env('DB_PORT','3306'),env('DB_DATABASE','trading_journal'));
    try {
        $pdo=new PDO($dsn,env('DB_USERNAME','root'),env('DB_PASSWORD',''),[
            PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES=>false
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        exit('Database connection failed.');
    }
}
