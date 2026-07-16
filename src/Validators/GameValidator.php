<?php

namespace App\src\Validators;

class GameValidator{

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

}