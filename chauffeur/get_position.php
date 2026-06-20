<?php
require_once '../config/database.php';

$course_id = (int)($_GET['course_id'] ?? 0);
$role      = $_GET['role'] ?? '';

if (!$course_id) {
    exit(json_encode(['error' => 'missing id']));
}

if ($role === 'client') {

    $stmt = $pdo->prepare("
        SELECT client_lat AS lat, client_lng AS lng
        FROM reservations
        WHERE id = ?
    ");
    $stmt->execute([$course_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}

if ($role === 'chauffeur') {

    $stmt = $pdo->prepare("
        SELECT chauffeur_lat AS lat, chauffeur_lng AS lng
        FROM reservations
        WHERE id = ?
    ");
    $stmt->execute([$course_id]);
    echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
    exit;
}