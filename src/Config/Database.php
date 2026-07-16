<?php
declare(strict_types=1);

namespace App\Config;

use PDO;

class Database{
    public static function getConnection(): PDO{
        // Extraemos las variables usando $_ENV
        $host = $_ENV['DB_HOST'];
        $db = $_ENV['DB_DATABASE'];
        $username = $_ENV['DB_USERNAME'];
        $password = $_ENV['DB_PASSWORD'];
        $port = $_ENV['DB_PORT'];

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        return new PDO($dsn, $username, $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //si ocurre un error, envía una Exception en lugar de solo emitir un warning silencioso
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //Define que por defecto, cuando se hace un fetch() o fetchAll(), los resultados se devuelvan como arreglos asociativos
            PDO::ATTR_EMULATE_PREPARES => false //Desactiva la emulación de consultas preparadas y obliga a PDO a usar prepared statements reales del motor MySQL.
        ]);
    }
}
