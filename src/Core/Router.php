<?php

declare(strict_types=1);

namespace App\src\Core;

class Router{
    public static function resolveRoute(array $segments): array{
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
}