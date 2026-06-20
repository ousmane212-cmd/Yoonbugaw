<?php

session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$userId   = (int) $_SESSION['id'];
$role     = $_SESSION['role'] ?? '';
$courseId = (int) ($_GET['course_id'] ?? 0);
$afterId  = (int) ($_GET['after'] ?? 0);

if (!$courseId) {
    echo json_encode([]);
    exit;
}

/* Vérification accès */
if ($role === 'chauffeur') {
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ? AND chauffeur_id = ?");
    $stmt->execute([$courseId, $userId]);
} else {
    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE id = ? AND client_id = ?");
    $stmt->execute([$courseId, $userId]);
}

if (!$stmt->fetch()) {
    echo json_encode([]);
    exit;
}

/* Messages */
$stmt = $pdo->prepare("
    SELECT m.id, m.contenu, m.created_at,
           u.nom  AS expediteur_nom,
           u.role AS expediteur_role
    FROM messages_course m
    JOIN users u ON u.id = m.expediteur_id
    WHERE m.course_id = ?
      AND m.id > ?
    ORDER BY m.id ASC
");

$stmt->execute([$courseId, $afterId]);
$msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* format heure */
foreach ($msgs as &$m) {
    $m['heure'] = date('H:i', strtotime($m['created_at']));
}

echo json_encode($msgs);