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

}
