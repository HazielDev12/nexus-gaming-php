<?php

namespace App\src\Http;

class Response{
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
}
