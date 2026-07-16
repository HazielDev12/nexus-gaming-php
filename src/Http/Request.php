<?php

namespace App\Http;

class Request{

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

}