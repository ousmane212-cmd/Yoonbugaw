<?php
/**
 * get_driver_position.php
 * Retourne la position GPS du chauffeur + statut + ETA pour le tracking temps réel.
 * GET: ?reservation_id=xxx
 *
 * En production : le chauffeur met à jour sa position via l'app chauffeur.
 * Ici on lit la table vehicules.lat_chauffeur / lng_chauffeur.
 */
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

$userId   = $_SESSION['id']  ?? null;
$userName = $_SESSION['nom'] ?? null;
if (!$userId) { echo json_encode(['error'=>'Non connecté']); exit; }

$reservationId = (int)($_GET['reservation_id'] ?? 0);
if (!$reservationId) { echo json_encode(['error'=>'ID manquant']); exit; }

$stmt = $pdo->prepare("
    SELECT v.lat_chauffeur, v.lng_chauffeur, v.eta_minutes, r.statut
    FROM reservations r
    LEFT JOIN vehicules v ON v.matricule = r.matricule
    WHERE r.id = :id AND r.user_name = :uname
    LIMIT 1
");
$stmt->execute([':id'=>$reservationId,':uname'=>$userName]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) { echo json_encode(['error'=>'Introuvable']); exit; }

echo json_encode([
    'lat'         => $row['lat_chauffeur'] ? (float)$row['lat_chauffeur'] : null,
    'lng'         => $row['lng_chauffeur'] ? (float)$row['lng_chauffeur'] : null,
    'eta_minutes' => $row['eta_minutes']   ? (int)$row['eta_minutes']    : null,
    'statut'      => ucfirst($row['statut'] ?? 'En attente'),
]);