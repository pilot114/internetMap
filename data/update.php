<?php

require '../vendor/autoload.php';

use InternetMap\Sqlite;

function getLine($file): \Generator
{
    $line = null;
    while ($line !== false) {
       $line = fgets($file);
       yield $line;
    }
}

function getIps(string $fileName): \Generator
{
    $file = fopen($fileName, 'r');
    foreach (getLine($file) as $line) {
        if (preg_match('#(open|closed) (\w+) (\d+) (\d+\.\d+\.\d+\.\d+) (\d+)#', $line, $matches)) {
//            $protocol = $matches[2];
//            $port = $matches[3];
            $ip = $matches[4];
            $ts = $matches[5];
            yield $ip => $ts;
        }
    }
    fclose($file);
}

$fileName = './scan.txt';

$db = new Sqlite('sqlite:internet.db');

foreach (getIps($fileName) as $ip => $ts) {
    $db->insertIpAndTs($ip, $ts);
}


