<?php
require_once '../config/database.php';

$data = json_decode(file_get_contents("php://input"), true);

$course_id = (int)($data['course_id'] ?? 0);
$lat       = $data['lat'] ?? null;
$lng       = $data['lng'] ?? null;
$role      = $data['role'] ?? null;

if (!$course_id || $lat === null || $lng === null) {
    http_response_code(400);
    exit(json_encode(['error' => 'missing data']));
}

/* ── CHAUFFEUR ── */
if ($role === 'chauffeur') {

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET chauffeur_lat = ?,
            chauffeur_lng = ?,
            chauffeur_position_updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$lat, $lng, $course_id]);
}

/* ── CLIENT ── */
if ($role === 'client') {

    $stmt = $pdo->prepare("
        UPDATE reservations
        SET client_lat = ?,
            client_lng = ?,
            client_position_updated_at = NOW()
        WHERE id = ?
    ");

    $stmt->execute([$lat, $lng, $course_id]);
}

echo json_encode(['success' => true]);