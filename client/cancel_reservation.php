<?php
/**
 * cancel_reservation.php
 * Annulation avec règle de remboursement à 2h.
 * POST: reservation_id, raison (optionnel)
 */
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

$userId   = $_SESSION['id']  ?? null;
$userName = $_SESSION['nom'] ?? null;
if (!$userId) { echo json_encode(['success'=>false,'message'=>'Non connecté']); exit; }

$reservationId = (int)($_POST['reservation_id'] ?? 0);
$raison        = trim($_POST['raison'] ?? 'Annulé par le client');

if (!$reservationId) {
    echo json_encode(['success'=>false,'message'=>'ID réservation manquant']); exit;
}

/* ── Récupérer la réservation ── */
$stmt = $pdo->prepare("
    SELECT * FROM reservations
    WHERE id = :id AND user_name = :uname
    LIMIT 1
");
$stmt->execute([':id'=>$reservationId,':uname'=>$userName]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$res) {
    echo json_encode(['success'=>false,'message'=>'Réservation introuvable']); exit;
}

$statut = strtolower($res['statut'] ?? '');
if (in_array($statut, ['annulé','annule','terminé','termine'])) {
    echo json_encode(['success'=>false,'message'=>'Cette réservation ne peut plus être annulée']); exit;
}

/* ══════════════════════════════════════
   RÈGLE REMBOURSEMENT (délai 2h)
   ─ < 2h depuis création → 100 %
   ─ chauffeur en route   → 85 % (frais 15 %)
   ─ non-présentation     → 0 %
══════════════════════════════════════ */
$montant       = (float)($res['montant'] ?? 0);
$dateCreation  = new DateTime($res['date_reservation']);
$maintenant    = new DateTime();
$diffMinutes   = ($maintenant->getTimestamp() - $dateCreation->getTimestamp()) / 60;

$tauxRemboursement = 0;
$messageRemb       = '';
$typeRemboursement = 'aucun';

if ($diffMinutes <= 120) {                        // dans les 2h
    $tauxRemboursement = 1.0;
    $messageRemb       = 'Annulation dans le délai de 2h — remboursement intégral';
    $typeRemboursement = 'integral';
} elseif (in_array($statut, ['en attente','accepté','accepte'])) {
    $tauxRemboursement = 0.85;
    $messageRemb       = 'Annulation hors délai — frais de 15 % appliqués';
    $typeRemboursement = 'partiel';
} else {
    $tauxRemboursement = 0;
    $messageRemb       = 'Annulation sans remboursement (chauffeur déjà en route ou non-présentation)';
    $typeRemboursement = 'aucun';
}

$montantRembourse = round($montant * $tauxRemboursement);

/* ── Mettre à jour la réservation ── */
$stmt = $pdo->prepare("
    UPDATE reservations
    SET statut              = 'annulé',
        raison_annulation   = :raison,
        montant_rembourse   = :remb,
        date_annulation     = NOW()
    WHERE id = :id
");
$stmt->execute([
    ':raison' => $raison,
    ':remb'   => $montantRembourse,
    ':id'     => $reservationId,
]);

/* ── Enregistrer le remboursement (table optionnelle) ── */
$pdo->exec("
    CREATE TABLE IF NOT EXISTS remboursements (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        reservation_id  INT         NOT NULL,
        user_id         INT         NOT NULL,
        montant         DECIMAL(12,2) NOT NULL,
        taux            DECIMAL(5,2) NOT NULL,
        type            VARCHAR(20) NOT NULL,
        statut          VARCHAR(30) NOT NULL DEFAULT 'en_attente',
        created_at      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

if ($montantRembourse > 0) {
    $stmt = $pdo->prepare("
        INSERT INTO remboursements (reservation_id, user_id, montant, taux, type, statut)
        VALUES (:rid, :uid, :montant, :taux, :type, 'en_attente')
    ");
    $stmt->execute([
        ':rid'    => $reservationId,
        ':uid'    => $userId,
        ':montant'=> $montantRembourse,
        ':taux'   => $tauxRemboursement * 100,
        ':type'   => $typeRemboursement,
    ]);
}

/* ── Envoyer une notification au client ── */
require_once 'notification_api.php';  // réutilise la logique d'insertion
$notifTitre   = $montantRembourse > 0
    ? 'Remboursement en cours — ' . number_format($montantRembourse, 0, ',', ' ') . ' FCFA'
    : 'Réservation annulée';
$notifMessage = $messageRemb . '. Mode : ' . ($res['mode_paiement'] ?? 'selon paiement initial') . '.';

$stmtN = $pdo->prepare("
    INSERT INTO notifications (user_id, reservation_id, type, titre, message)
    VALUES (:uid, :rid, :type, :titre, :msg)
");
$stmtN->execute([
    ':uid'   => $userId,
    ':rid'   => $reservationId,
    ':type'  => $montantRembourse > 0 ? 'success' : 'warning',
    ':titre' => $notifTitre,
    ':msg'   => $notifMessage,
]);

echo json_encode([
    'success'            => true,
    'message'            => $messageRemb,
    'montant_rembourse'  => $montantRembourse,
    'taux'               => $tauxRemboursement * 100,
    'type_remboursement' => $typeRemboursement,
    'delai_minutes'      => round($diffMinutes),
]);