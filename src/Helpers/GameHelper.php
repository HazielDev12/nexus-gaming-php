<?php

namespace App\Helpers;

class GameHelper{
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