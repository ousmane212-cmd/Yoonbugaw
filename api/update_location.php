<?php
session_start();
require_once "../config/database.php";

/* ========================
   SÉCURITÉ
======================== */
header('Content-Type: application/json');

// Requête POST uniquement
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

// Session valide obligatoire
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'chauffeur') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

/* ========================
   VALIDATION
======================== */
$lat = filter_input(INPUT_POST, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_POST, 'lng', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lat === null || $lng === false || $lng === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Coordonnées invalides']);
    exit;
}

// Plages géographiques valides
if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Coordonnées hors limites']);
    exit;
}

/* ========================
   MISE À JOUR EN BASE
======================== */
$chauffeurId = (int) $_SESSION['id'];

try {
    /*
     * On utilise INSERT ... ON DUPLICATE KEY UPDATE pour :
     *  - insérer la ligne si le chauffeur n'en a pas encore
     *  - mettre à jour sinon
     * La table chauffeur_locations doit exister (voir SQL ci-dessous).
     */
    $stmt = $pdo->prepare("
        INSERT INTO chauffeur_locations (chauffeur_id, latitude, longitude, updated_at)
        VALUES (?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
            latitude   = VALUES(latitude),
            longitude  = VALUES(longitude),
            updated_at = NOW()
    ");

    $stmt->execute([$chauffeurId, $lat, $lng]);

    echo json_encode([
        'success'    => true,
        'chauffeur'  => $chauffeurId,
        'lat'        => $lat,
        'lng'        => $lng,
        'updated_at' => date('Y-m-d H:i:s'),
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Erreur base de données',
        // Ne jamais exposer $e->getMessage() en production
    ]);
}