<?php



function getAllGames(PDO $pdo): array{
    $sql = "SELECT id,name,price,status,created_at FROM games ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function getGameById(PDO $pdo, int $id): ?array{
    $sql = "SELECT id,name,price,status,created_at FROM games WHERE id= :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["id"=>$id]);
    $game = $stmt->fetch();
    return $game !== false ? $game : null;
}
function createGame(PDO $pdo, array $data): int{
    $sql = "INSERT INTO games (name,price,status) VALUES (:name,:price,:status)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(
        [
            "name" => trim((string)$data["name"]),
            "price" => (float)$data["price"],
            "status" => trim((string)$data["status"] ?? "available")
        ]);
    return (int)$pdo->lastInsertId();
}

function updateGame(PDO $pdo, int $id, array $data): bool{
    $sql = "UPDATE games SET name= :name,price= :price,status= :status WHERE id= :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(
        [
            "id"=>$id,
            "name" => trim((string)$data["name"]),
            "price" => (float)$data["price"],
            "status" => trim((string)$data["status"])
        ]);
    return $stmt->rowCount() > 0;
}

function deleteGame(PDO $pdo, int $id): bool{
    $sql = "DELETE FROM games WHERE id= :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(
        [
            "id"=>$id,
        ]);
    return $stmt->rowCount() > 0;
}
