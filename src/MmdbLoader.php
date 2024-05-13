<?php

namespace InternetMap;

use MaxMind\Db\Reader;
use MaxMind\Db\Reader\InvalidDatabaseException;

class MmdbLoader
{
    private Reader $cityReader;
    private Reader $providerReader;

    private string $locale = 'en';

    /**
     * @throws InvalidDatabaseException|\ErrorException
     */
    public function __construct(
        string $cityFile,
        string $providerFile,
    ) {
        $this->providerReader = new Reader($providerFile);
        $this->cityReader = new Reader($cityFile);

//        set_error_handler(function($errNo, $errStr, $errFile, $errLine) {
//            $msg = "$errStr in $errFile on line $errLine";
//            throw new \ErrorException($msg, $errNo);
//        });
    }

    public function extractMeta(): array
    {
        $cityMeta = $this->cityReader->metadata();
        $providerMeta = $this->providerReader->metadata();

        return [
            $cityMeta->description[$this->locale] => [
                'date' => date('Y-m-d H:i:s', $cityMeta->buildEpoch),
                'count' => $cityMeta->nodeCount,
            ],
            $providerMeta->description[$this->locale] => [
                'date' => date('Y-m-d H:i:s', $providerMeta->buildEpoch),
                'count' => $providerMeta->nodeCount,
            ],
        ];
    }

    public function getCityNodes(): \Generator
    {
        foreach ($this->cityReader->getNodes() as $node) {
            $data = $this->convertIpData($node);
            yield $data;
        }

        foreach ($this->providerReader->getNodes() as $node) {
            yield $node;
        }
    }

    public function get(string $ip): ?array
    {
        $ipData = $this->cityReader->get($ip);
        if ($ipData === null) {
            return null;
        }
        if (!isset($ipData['location'])) {
            return null;
        }

        $data = $this->convertIpData($ipData);

        $provider = $this->providerReader->get($ip);

        if (isset($provider['autonomous_system_number'])) {
            $data['provider'] = [
                'code' => $provider['autonomous_system_number'],
                'name' => $provider['autonomous_system_organization'],
            ];
        } else {
            $data['provider'] = null;
        }

        return $data;
    }

    public function convertIpData(array $ipData): array
    {
        $data = [
            'location' => $ipData['location'],
            'continent' => [
                'code' => $ipData['continent']['code'],
                'name' => $ipData['continent']['names'][$this->locale],
            ],
        ];

        if (isset($ipData['country']['names'][$this->locale])) {
            $data['country'] = [
                'code' => $ipData['country']['iso_code'],
                'name' => $ipData['country']['names'][$this->locale],
            ];
        } else {
            $data['country'] = null;
        }

        if (isset($ipData['registered_country']['names'][$this->locale])) {
            $data['registered_country'] = [
                'code' => $ipData['registered_country']['iso_code'],
                'name' => $ipData['registered_country']['names'][$this->locale],
            ];
        } else {
            $data['registered_country'] = null;
        }

        if (isset($ipData['city']['names'][$this->locale])) {
            $data['city'] = $ipData['city']['names'][$this->locale];
        } else {
            $data['city'] = null;
        }
        return $data;
    }


    /**
     * @throws \Exception
     */
    public function __destruct()
    {
        $this->cityReader->close();
        $this->providerReader->close();
    }
}