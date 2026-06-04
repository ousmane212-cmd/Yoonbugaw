<?php
require_once "../config/database.php";

$stmt = $pdo->query("
    SELECT id, nom, couleur, chauffeur, lat, lng, disponible
    FROM users
    WHERE role='chauffeur'
");

$drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($drivers);