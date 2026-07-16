<?php

namespace App\Repositories;

use PDO;

class GameRepository{

    public function __construct(
        private PDO $pdo
    ){}

    public function getAllGames(): array{
        $sql = "SELECT id,name,price,status,created_at FROM games ORDER BY id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getGameById(int $id): ?array{
        $sql = "SELECT id,name,price,status,created_at FROM games WHERE id= :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id"=>$id]);
        $game = $stmt->fetch();
        return $game !== false ? $game : null;
    }
    public function createGame(array $data): int{
        $sql = "INSERT INTO games (name,price,status) VALUES (:name,:price,:status)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(
            [
                "name" => trim((string)$data["name"]),
                "price" => (float)$data["price"],
                "status" => trim((string)$data["status"] ?? "available")
            ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function updateGame(int $id, array $data): bool{
        $sql = "UPDATE games SET name= :name,price= :price,status= :status WHERE id= :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(
            [
                "id"=>$id,
                "name" => trim((string)$data["name"]),
                "price" => (float)$data["price"],
                "status" => trim((string)$data["status"])
            ]);
        return $stmt->rowCount() > 0;
    }

    public function deleteGame(int $id): bool{
        $sql = "DELETE FROM games WHERE id= :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(
            [
                "id"=>$id,
            ]);
        return $stmt->rowCount() > 0;
    }
}
