<?php
/* ══════════════════════════════════════════════════════════════
   ajax/noter_chauffeur.php — Yoon bu Gaw
   Le client note le chauffeur après sa course
══════════════════════════════════════════════════════════════ */
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (empty($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

$userId   = (int) $_SESSION['id'];
$userName = $_SESSION['nom'] ?? '';
$body     = json_decode(file_get_contents('php://input'), true);
$courseId = (int)  ($body['course_id']   ?? 0);
$note     = (int)  ($body['note']        ?? 0);
$comment  = trim($body['commentaire']    ?? '');

if (!$courseId || $note < 1 || $note > 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

/* Vérifier que la course appartient bien à ce client */
$stmt = $pdo->prepare('SELECT chauffeur_id FROM reservations WHERE id = ? AND user_name = ?');
$stmt->execute([$courseId, $userName]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès interdit']);
    exit;
}

$chauffeurId = (int) $course['chauffeur_id'];

/* Enregistrer la note dans la réservation */
$stmt = $pdo->prepare('UPDATE reservations SET note_client = ? WHERE id = ?');
$stmt->execute([$note, $courseId]);

/* Recalculer la note moyenne du chauffeur */
$stmt = $pdo->prepare(
    'SELECT ROUND(AVG(note_client), 1) AS moy FROM reservations
     WHERE chauffeur_id = ? AND note_client IS NOT NULL'
);
$stmt->execute([$chauffeurId]);
$moy = (float) ($stmt->fetchColumn() ?? 0);

$pdo->prepare('UPDATE users SET note_moy = ? WHERE id = ?')->execute([$moy, $chauffeurId]);

/* Enregistrer le commentaire si fourni */
if ($comment && $chauffeurId) {
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO avis_chauffeurs (chauffeur_id, client_id, course_id, note, commentaire, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE note = VALUES(note), commentaire = VALUES(commentaire)"
        );
        $stmt->execute([$chauffeurId, $userId, $courseId, $note, $comment]);
    } catch (PDOException $e) {
        /* Table optionnelle — on ignore si elle n'existe pas */
    }
}

echo json_encode([
    'success'   => true,
    'note'      => $note,
    'note_moy'  => $moy,
]);