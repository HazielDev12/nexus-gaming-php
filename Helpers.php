<?php
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

function mergedGameData(array $existing, array $payload): array{
    $merged = [];
    if (array_key_exists("name", $payload)) {
        $merged["name"] = trim((string)$payload["name"]);
    }else{
        $merged["name"] = trim((string)$existing["name"]);
    }
    if (array_key_exists("price", $payload)) {
        $merged["price"] = (float)$payload["price"];
    } else{
        $merged["price"] = (float)$existing["price"];
    }
    if (array_key_exists("status", $payload)) {
        $merged["status"] = trim((string)$payload["status"]);
    }else{
        $merged["status"] = trim((string)$existing["status"]);
    }

    return $merged;
}