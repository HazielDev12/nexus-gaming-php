<?php
declare(strict_types=1);


function getConnection(): PDO{
    $dsn = "mysql:host=localhost;dbname=nexus_gaming_php;charset=utf8mb4;port=3306";
    $username = "root";
    $password = "";
    return new PDO($dsn, $username, $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false //Protección para sql inyection
    ]);
}