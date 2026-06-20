<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Méthode invalide'
    ]);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$action = $_POST['action'] ?? '';
$chauffeurId = isset($_POST['chauffeur_id']) ? (int)$_POST['chauffeur_id'] : 0;

if ($id <= 0 || $chauffeurId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Données invalides'
    ]);
    exit;
}

try {

    // Vérifier que la course existe
    $stmt = $pdo->prepare("
        SELECT * FROM reservations 
        WHERE id = ? AND chauffeur_id = ?
        LIMIT 1
    ");
    $stmt->execute([$id, $chauffeurId]);
    $course = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        echo json_encode([
            'success' => false,
            'message' => 'Course introuvable'
        ]);
        exit;
    }

    // ACCEPTER
    if ($action === 'accept') {

        $stmt = $pdo->prepare("
            UPDATE reservations
            SET 
                statut = 'en cours',
                statut_course = 'en_cours',
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => 'Course acceptée'
        ]);
        exit;
    }

    // REFUSER / ANNULER
    if ($action === 'reject') {

        $stmt = $pdo->prepare("
            UPDATE reservations
            SET 
                statut = 'annulé',
                statut_course = 'annule',
                chauffeur_id = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");

        $stmt->execute([$id]);

        echo json_encode([
            'success' => true,
            'message' => 'Course refusée'
        ]);
        exit;
    }

    echo json_encode([
        'success' => false,
        'message' => 'Action inconnue'
    ]);

} catch (PDOException $e) {

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}