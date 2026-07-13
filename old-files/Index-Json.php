<?php

//==============================================================
//Bootstrap de la API
/*proceso de inicialización y configuración inicial que realiza una aplicación
 *para cargar todas sus dependencias, variables de entorno y servicios esenciales
 *antes de empezar a recibir y procesar peticiones HTTP.
 * */

use JetBrains\PhpStorm\NoReturn;

header("Content-type: application/json; charset=utf-8"); //Establecer que será JSON

//Obtener la operación (GET,POST,PUT,PATCH,DELETE).
$method = $_SERVER["REQUEST_METHOD"] ?? "GET";
$uriPath = parse_url($_SERVER["REQUEST_URI"] ?? "/", PHP_URL_PATH ?? "/");

$segments = array_values(array_filter(explode("/", trim($uriPath, "/"))));
/*
 * Si la url es http://localhost/api-games/games/5
 * entonces:
 *  $segments = [
 *    "api-games",
 *    "games",
 *    "5"
 * ];
 *
 * */
//=============================================================

//Detectar si nos mandan un /products o /products/2
function resolveRoute(array $segments): array{
    //Buscar la palabra "games" dentro del arreglo ($segments)
    $pos = array_search("games", $segments, true);
//    0 => api-games
//    1 => games
//    2 => 5

    if($pos === false){
        return [null, null];
    }

    $resource = "games";
    $id = $segments[$pos+1] ?? null;
    //Verifica si vino un id
    if($id !== null){
        //ctype_digit = ¿Todos los caracteres son números?
        if(!ctype_digit($id)){
            respondError(400, "El id debe ser numérico");
        }

        return [$resource, (int)$id]; // Convierte "5" en 5 ((int)$id).
        //Devuelve
//        [
//            "games",
//            5
//        ]
    }
    return [$resource, null];
}


//==================================================
//Devolver errores de forma mas "elegante"
#[NoReturn]
function respondJson(int $statusCode, $payload): void{
    http_response_code($statusCode); //Establece el código http a presentar(200,404,500,etc).
    // conversión de PHP a JSON
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

#[NoReturn]
function respondError(int $statusCode, string $message): void{
    respondJson($statusCode, ["error"=>$message]);
}
//====================================================
//Función para leer el contenido de una petición y devolver un arreglo
function readJsonBody(): array
{
    //Leer el cuerpo crudo de la petición
    $raw = file_get_contents("php://input"); // "php://input": Stream especial que PHP proporciona para acceder al cuerpo de la petición HTTP.
//    $raw = '{
//    "name":"Minecraft",
//    "price":500
//    }';

    if ($raw === false || trim($raw) === '') {
        respondError(400, "El cuerpo de la petición está vacío");
    }

    //Convertir JSON a PHP
    $data = json_decode($raw, true); //Sin el true, obtendría un objeto en vez de un arreglo.
    //Convierte:
//    [
//       "name"=>"Mouse",
//       "price"=>25
//    ]
//    a esto:
//    [
//      "name"=>"Mouse",
//      "price"=>25
//    ]
//    Ya no es texto, hora es un arreglo de PHP.

    //Verificar si hubo algún error en la decodificación.
    if (json_last_error() !== JSON_ERROR_NONE) {
        respondError(400, "JSON inválido " . json_last_error_msg());
    }
    //Verificar el tipo de dato, es decir, que el JSON represente un objeto.
    if (!is_array($data)) {
        respondError(400, "El JSON debe representar un objeto");
    }

    //Devolver el resultado
    return $data;
}

// Verificar en que directorio nos encontramos y el archivo donde se guardarán los productos
function storagePath(): string
{
    return __DIR__ . "/storage_games.json";
}
//Cargar los videojuegos.
function loadProducts(): array
{
    //obtiene la ubicación del archivo.
    $path = storagePath();
    //Por ejemplo: C:\xampp\htdocs\nexus-gaming\storage_products.json

    if (!file_exists($path)) {
        //Datos semilla (Si no existen datos, entonces se crean los siguientes)
        $seed = [
            ["id" => 1, "name" => "Minecraft", "price" => 600.00, "status" => "available"],
            ["id" => 2, "name" => "EA Sports FC 26", "price" => 250.00, "status" => "available"],
        ];

        $dir = dirname($path); //Devuelve C:\xampp\htdocs\nexus-gaming    Quita el nombre del archivo
        // Verifica que la carpeta tenga permisos de escritura para poder crear el archivo JSON.
        if (!is_writable($dir)) {
            respondError(500, "No se puede crear el archivo de almacenamiento. 
                      La carpeta '$dir' no tiene permisos de escritura. 
                      Asigna permisos de escritura a la carpeta para continuar.");
        }
        //Guardar el seed en el path
        file_put_contents($path, json_encode($seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $seed;
    }
    //Si el archivo ya existe:
    $content = file_get_contents($path);
    //De texto json a arreglo PHP
    $data = json_decode($content ? $content : "[]", true);

    //¿Lo que devolvió json_decode realmente es un arreglo?
    return is_array($data) ? $data : [];
}

function saveGames(array $products): void
{
    $path = storagePath();
    //Convierte el arreglo en JSON y lo guarda.
    file_put_contents($path, json_encode($products, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    @chmod($path, 0666); //todos pueden leer y escribir.
}

function findById(array $games, int $id): ?array
{
    foreach ($games as $game) {
        if ((int)$game["id"] === $id) {
            return $game;
        }
    }

    return null;
}

function nextId(array $games): int
{
    $maxId = 0;
    foreach ($games as $game) {
        $maxId = max($maxId, (int)($game["id"] ?? 0));
    }
    return $maxId + 1;
}

function validateProductPayload(array $data, bool $isCreate, bool $requireAllFields = false): array
{
    $errors = [];
    $mustHaveAll = $isCreate || $requireAllFields;
    $allowedStatus = ["available", "discontinued", "sold", "reserved"];
    $fields = [
        "name" => [
            "requiredMessage" => "El nombre es obligatorio",
            "rules" => function ($value) use (&$errors) {
                $value = trim((string)$value);

                if ($value === "") {
                    $errors[] = "El nombre no puede estar vacío";
                }

                if (mb_strlen($value) < 2) {
                    $errors[] = "El nombre debe tener al menos 2 caracteres";
                }
            }
        ],
        "price" => [
            "requiredMessage" => "El precio es obligatorio",
            "rules" => function ($value) use (&$errors) {

                if (!is_numeric($value)) {
                    $errors[] = "El precio debe ser númerico";
                }
                if ((float)$value <= 0) {
                    $errors[] = "El precio debe ser mayor a cero";
                }
            }
        ],
        "status" => [
            "requiredMessage" => "El status es obligatorio y debe contener uno de los siguientes: " . implode(", ",$allowedStatus),
            "rules" => function ($value) use ($allowedStatus, &$errors) {
                $value = trim((string)$value);

                if ($value === "") {
                    $errors[] = "El estado del videojuego no puede estar vacío.";
                }

                if (!in_array($value, $allowedStatus, true)) {
                    $errors[] = "Estado no disponible. El estado debe ser uno alguno de los siguientes: " . implode(", ",$allowedStatus);
                }

            }
        ]
    ];

    foreach ($fields as $field => $config) {

        if ($mustHaveAll && !array_key_exists($field, $data)) {
            $errors[] = $config["requiredMessage"];
            continue;
        }

        if (array_key_exists($field, $data)) {
            $config["rules"]($data[$field]);
        }
    }

    return $errors;
}



function findIndexsById(array $games, int $id): int
{
    foreach ($games as $index => $game) {
        if ((int)$game["id"] === $id) {
            return (int) $index;
        }
    }
    return -1;
}


//Flujo principal (handlers o manejadores)
//READ
try {

    [$resource, $resourceId] = resolveRoute($segments);  //["games", 2], ["games", null], [null, null]

    //Validación para la url
    if ($resource !== "games") {
        respondError(404, "Recurso no encontrado. Usa /games");
    }


    if ($method === "GET" && $resourceId === null) {
        $games = loadProducts();
        respondJson(200, $games);
    }
    if ($method === "GET" && $resourceId !== null) {
        $games = loadProducts();
        $game = findById($games, $resourceId);
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

        $games = loadProducts();
        $newGame =
            [
                "id" => nextId($games),
                "name" => trim((string)$payload["name"]),
                "price" => (float)$payload["price"],
                "status" => trim((string)$payload["status"] ?? "available")
            ];

        $games[] = $newGame; //Agrega un elemento al final del arreglo
        saveGames($games);
        respondJson(
            201,
            [
                "message" => "Producto creado correctamente",
                "data" => $newGame
            ]
        ); //201 Porque se creó un recurso.
    }

    //PUT para actualizar todas las propiedades  y PATCH para actualizar solo una propiedad
    if (($method === "PUT" || $method === "PATCH") && $resourceId !== null) {
        $payload = readJsonBody();
        $isCreate = false;
        $requireFields = ($method === "PUT");
        $errors = validateProductPayload($payload, $isCreate, $requireFields);
        if (count($errors) > 0) {
            respondJson(422, ["errors" => $errors]);
        }
        $games = loadProducts();
        $index = findIndexsById($games, $resourceId);
        if ($index === -1) {
            respondError(404, "Juego no encontrado");
        }
        $current = $games[$index];
        $updated = $current;
        if (array_key_exists("name", $payload)) {
            $updated["name"] = trim((string)$payload["name"]);
        }
        if (array_key_exists("price", $payload)) {
            $updated["price"] = (float)$payload["price"];
        }
        if (array_key_exists("status", $payload)) {
            $updated["status"] = trim((string)$payload["status"]);
        }

        $games[$index] = $updated;
        saveGames($games);
        respondJson(
            200,
            [
                "message" => "Juego actualizado correctamente",
                "data" => $updated
            ]
        );
    }

    if ($method === "DELETE" && $resourceId !== null) {
        $games = loadProducts();
        $index = findIndexsById($games, $resourceId);
        if ($index === -1) {
            respondError(404, "Juego no encontrado");
        }
        $deleted = $games[$index];

        //Método para eliminar un Juego por indice
        array_splice($games, $index, 1);
        saveGames($games);
        //204 no counter
        //respondJson(204,"");
        respondJson(
            200,
            [
                "message" => "Juego eliminado correctamente",
                "data" => $deleted
            ]
        );
    }

    respondError(405, "Método no permitido para esta ruta");
} catch (Exception $exception) {
    respondError(500, "Error interno " . $exception->getMessage());
}




