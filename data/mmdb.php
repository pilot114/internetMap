<?php

use InternetMap\MmdbLoader;
use InternetMap\Sqlite;

require '../vendor/autoload.php';

$loader = new MmdbLoader('./GeoLite2-City.mmdb', './GeoLite2-ASN.mmdb');

$db = new Sqlite('sqlite:internet.db');

foreach ($db->getLastWithoutCoords() as $row) {
    $data = $loader->get($row['ip']);
    if ($data !== null) {
        $db->fillGeoData($row['id'], [
            'lat' => $data['location']['latitude'],
            'lon' => $data['location']['longitude'],
            'accuracy' => $data['location']['accuracy_radius'],
            'time_zone' => $data['location']['time_zone'],
            'continent_code' => $data['continent']['code'],
            'continent_name' => $data['continent']['name'],
            'country_code' => $data['country']['code'],
            'country_name' => $data['country']['name'],
            'registered_country_code' => $data['registered_country']['code'],
            'registered_country_name' => $data['registered_country']['name'],
            'city_name' => $data['city'],
            'provider_code' => $data['provider']['code'] ?? null,
            'provider_name' => $data['provider']['name'] ?? null,
        ]);
    }
}

//var_dump($loader->extractMeta());

// SPX_ENABLED=1 SPX_FP_LIVE=1 SPX_BUILTINS=1 php api.php
