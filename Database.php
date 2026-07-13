<?php
declare(strict_types=1);


function getConnection(): PDO{
    $dsn = "mysql:host=localhost;dbname=nexus_gaming_php;charset=utf8mb4";
    $username = "root";
    $password = "";
    return new PDO($dsn, $username, $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, //si ocurre un error, envía una Exception en lugar de solo emitir un warning silencioso
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, //Define que por defecto, cuando se hace un fetch() o fetchAll(), los resultados se devuelvan como arreglos asociativos
        PDO::ATTR_EMULATE_PREPARES => false //Desactiva la emulación de consultas preparadas y obliga a PDO a usar prepared statements reales del motor MySQL.
    ]);
}