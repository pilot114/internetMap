<?php

use InternetMap\MmdbLoader;
use InternetMap\Sqlite;

require './vendor/autoload.php';

$db = new Sqlite('sqlite:internet.db');

$bounds = json_decode($_GET['bounds'] ?? '', true);

$items = [];
foreach ($db->getLastWithCoords($bounds) as $item) {
    $items[] = $item;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');

echo json_encode(['items' => $items], JSON_PRETTY_PRINT);
