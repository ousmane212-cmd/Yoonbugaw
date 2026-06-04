=<?php
session_start();
require_once "../config/database.php";

header('Content-Type: application/json');

try {

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Méthode invalide']);
        exit;
    }

    if (!isset($_SESSION['id'])) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non connecté']);
        exit;
    }

    $userId = (int) $_SESSION['id'];
    $user   = trim($_SESSION['nom']);

    $type_transport = trim($_POST['type_transport'] ?? '');
    $service        = trim($_POST['service']        ?? '');
    $depart         = trim($_POST['depart']         ?? 'Dakar');
    $destination    = trim($_POST['destination']    ?? '');
    $montant        = floatval($_POST['montant']    ?? 0);
    $matricule      = trim($_POST['matricule']      ?? '');
    $chauffeur      = trim($_POST['chauffeur']      ?? '');
    $heure_depart   = trim($_POST['heure_depart']   ?? 'Dès maintenant');
    $mode_paiement  = trim($_POST['mode_paiement']  ?? 'wave');

    
    if ($type_transport === 'location' && empty($destination)) {
        $destination = $depart;
    }

    /* Validation */
    if (empty($type_transport) || $montant <= 0) {
        echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']);
        exit;
    }

    if (in_array($type_transport, ['taxi', 'bus', 'cargo']) && empty($destination)) {
        echo json_encode(['success' => false, 'message' => 'Champs obligatoires manquants']);
        exit;
    }

    /* INSERT sans user_id (colonne inexistante dans la table) */
    $stmt = $pdo->prepare("
        INSERT INTO reservations
            (user_name, type_transport, service, depart, destination,
             montant, matricule, chauffeur, heure_depart, mode_paiement,
             statut, date_reservation)
        VALUES
            (:user_name, :type_transport, :service, :depart, :destination,
             :montant, :matricule, :chauffeur, :heure_depart, :mode_paiement,
             'en attente', NOW())
    ");

    $ok = $stmt->execute([
        ':user_name'      => $user,
        ':type_transport' => $type_transport,
        ':service'        => $service,
        ':depart'         => $depart,
        ':destination'    => $destination,
        ':montant'        => $montant,
        ':matricule'      => $matricule,
        ':chauffeur'      => $chauffeur,
        ':heure_depart'   => $heure_depart,
        ':mode_paiement'  => $mode_paiement,
    ]);

    if ($ok) {

        $reservationId = $pdo->lastInsertId();

        /* Notification — on tente, mais si la table n'a pas user_id non plus on ignore */
        try {
            $notif = $pdo->prepare("
                INSERT INTO notifications (user_id, reservation_id, type, titre, message)
                VALUES (?, ?, ?, ?, ?)
            ");
            $notif->execute([
                $userId,
                $reservationId,
                'info',
                'Réservation en attente',
                'Votre demande ' . $type_transport . ' est en cours de traitement.'
            ]);
        } catch (Exception $e) {
            // Notification non critique, on continue même si ça échoue
        }

        echo json_encode([
            'success' => true,
            'message' => 'Réservation enregistrée',
            'ref'     => 'YBG-' . strtoupper(substr(md5(time()), 0, 8))
        ]);

    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de l\'enregistrement']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur SQL : ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>