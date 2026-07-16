<?php

declare(strict_types=1);

use App\Config\Database;
use App\src\Core\Router;

require_once __DIR__ . '/../vendor/autoload.php';

header("Content-type: application/json; charset=utf-8"); //Establecer que será JSON

try{
    $pdo = Database::getConnection();

//Obtener la operación (GET,POST,PUT,PATCH,DELETE).
    $method = $_SERVER["REQUEST_METHOD"] ?? "GET";
    $uriPath = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH ?? "/");
    $segments = array_values(array_filter(explode("/", trim($uriPath, "/"))));

    //echo "Conexión exitosa";
    [$resource, $resourceId] = Router::resolveRoute($segments);  //["games", 2], ["games", null], [null, null]

    //Validación para la url
    if ($resource !== "games") {
        respondError(404, "Recurso no encontrado. Usa /games");
    }

    if ($method === "GET" && $resourceId === null) {
        $games = getAllGames($pdo);
        respondJson(200, $games);
    }

    if ($method === "GET" && $resourceId !== null) {
        if($resourceId <= 0){
            respondError(400, "El id debe ser un número válido");
        }
        $game = getGameById($pdo, $resourceId);
        if ($game === null) {
            respondError(404, "Juego no encontrado");
        }
        respondJson(200, $game);
    }

    if ($method === "POST" && $resourceId === null) {
        $payload = readJsonBody();
        $errors = validateProductPayload($payload, isCreate: true);
        if (count($errors) > 0) {
            respondJson(422, ["errors" => $errors]); //422 Porque el json es correcto pero los datos no
        }
        $newId = createGame($pdo, $payload);
        $newGame = getGameById($pdo, $newId);
        respondJson(
            201,
            [
                "message" => "Producto creado correctamente",
                "data" => $newGame
            ]
        ); //201 Porque se creó un recurso.
    }


    if (($method === "PUT" || $method === "PATCH") && $resourceId !== null) {
        if($resourceId <= 0){
            respondError(400, "El id debe ser un número válido");
        }
        $existing = getGameById($pdo, $resourceId);
        if ($existing === null) {
            respondError(404, "Juego no encontrado");
        }
        $payload = readJsonBody();
        $isCreate = false;
        $requireFields = ($method === "PUT");
        $errors = validateProductPayload($payload, $isCreate, $requireFields);
        if (count($errors) > 0) {
            respondJson(422, ["errors" => $errors]);
        }

        $merged = mergedGameData($existing, $payload);
        updateGame($pdo, $resourceId, $merged);
        $updated = getGameById($pdo, $existing["id"]);
        respondJson(
            200,
            [
                "message" => "Juego actualizado correctamente",
                "data" => $updated
            ]
        );
    }

    //DELETE
    if ($method === "DELETE" && $resourceId !== null) {
        if($resourceId <= 0){
            respondError(400, "El id debe ser un número válido");
        }
        $existing = getGameById($pdo, $resourceId);
        if ($existing === null) {
            respondError(404, "Juego no encontrado");
        }

        $deleted = deleteGame($pdo, $resourceId);

        if (!$deleted) {
            respondError(409, "No se pudo eliminar el juego");
        }
        respondJson(
            200,
            [
                "message" => "Juego eliminado correctamente",
                "data" => $existing
            ]
        );

    }
}catch(PDOException $e){
    respondError(500, "Error de conexión " . $e->getMessage());
} catch (Exception $exception) {
    respondError(500, "Error interno " . $exception->getMessage());
}