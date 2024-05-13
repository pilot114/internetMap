<?php

namespace InternetMap;

class Sqlite
{
    private \PDO $pdo;

    public function __construct(string $dbFile)
    {
        $this->pdo = new \PDO($dbFile);
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->createTablesIfNotExist();
    }

    public function fillGeoData(int $id, array $data): void
    {
        $params = [
            ':id' => $id
        ];
        $sqlParams = [];
        foreach ($data as $name => $value) {
            $params[":$name"] = $value;
            $sqlParams[] = "$name = :$name";
        }
        $sqlParams = implode(', ', $sqlParams);

        $stmt = $this->pdo->prepare("UPDATE ip SET $sqlParams WHERE id = :id");
        $stmt->execute($params);
    }

    public function getLastWithCoords(array $bounds): \Generator
    {
        $sql = "SELECT *, MAX(ts) AS ts
        FROM ip
        where
            lat between :s AND :n
          and
            lon between :w AND :e
        GROUP BY ip
        limit 500";

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':s', $bounds['_sw']['lat']);
        $stmt->bindParam(':n', $bounds['_ne']['lat']);
        $stmt->bindParam(':w', $bounds['_sw']['lng']);
        $stmt->bindParam(':e', $bounds['_ne']['lng']);
        $stmt->execute();

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function getLastWithoutCoords(): \Generator
    {
        $sql = "SELECT id, ip, MAX(ts) AS ts
        FROM ip
        where lat is null
        GROUP BY ip;";

        $stmt = $this->pdo->query($sql);
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            yield $row;
        }
    }

    public function insertIpAndTs(string $ip, int $ts): void
    {
        $existSql = "SELECT EXISTS (
        SELECT 1 FROM ip 
        WHERE ip = :ip AND ts = :ts
    );";
        $stmt = $this->pdo->prepare($existSql);
        $stmt->execute([':ip' => $ip, 'ts' => $ts]);
        $exists = (bool)$stmt->fetchColumn();

        if (!$exists) {
            $stmt = $this->pdo->prepare("INSERT INTO ip (ip, ts) VALUES (:ip, :ts)");
            $stmt->execute([':ip' => $ip, 'ts' => $ts]);
        }
    }

    private function createTablesIfNotExist(): void
    {
        $query = "CREATE TABLE IF NOT EXISTS ip (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip TEXT NOT NULL,
        ts TIMESTAMP NOT NULL,
        
        lat REAL,
        lon REAL,
        accuracy INTEGER,
        time_zone STRING,
        
        continent_code STRING,
        continent_name STRING,
        country_code STRING,
        country_name STRING,
        registered_country_code STRING,
        registered_country_name STRING,
        city_name STRING,
        provider_code INTEGER,
        provider_name STRING
    );";
        $this->pdo->exec($query);
    }
}