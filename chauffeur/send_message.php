<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

/* Auth */
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'chauffeur') {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$chauffeurId = (int) $_SESSION['id'];

/* JSON input */
$raw  = file_get_contents('php://input');
$body = json_decode($raw, true);

if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalide']);
    exit;
}

$courseId = (int) ($body['course_id'] ?? 0);
$contenu  = trim($body['contenu'] ?? '');

if (!$courseId || mb_strlen($contenu) < 1) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

/* Vérifier course */
$stmt = $pdo->prepare('SELECT id FROM reservations WHERE id = ? AND chauffeur_id = ?');
$stmt->execute([$courseId, $chauffeurId]);

if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès interdit']);
    exit;
}

/* Insert message */
$stmt = $pdo->prepare(
    "INSERT INTO messages_course (course_id, expediteur_id, contenu, created_at, lu)
     VALUES (?, ?, ?, NOW(), 0)"
);
$stmt->execute([$courseId, $chauffeurId, $contenu]);

$newId = $pdo->lastInsertId();

echo json_encode([
    'success' => true,
    'id'      => $newId,
    'heure'   => date('H:i'),
]);