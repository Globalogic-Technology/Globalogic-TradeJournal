<?php
declare(strict_types=1);
function env(string $key, ?string $default=null): ?string {
    static $values=null;
    if ($values===null) {
        $values=[];
        $file=dirname(__DIR__).'/.env';
        if (is_file($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) as $line) {
                $line=trim($line);
                if ($line==='' || $line[0]==='#' || !str_contains($line,'=')) continue;
                [$k,$v]=explode('=',$line,2);
                $values[trim($k)]=trim($v," \t\n\r\0\x0B\"'");
            }
        }
    }
    return $values[$key]??$default;
}
