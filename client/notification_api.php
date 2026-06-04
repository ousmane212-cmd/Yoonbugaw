<?php
/**
 * notification_api.php
 * Actions: get | mark_read | send (admin/système)
 * Appel: POST notification_api.php
 */
session_start();
require_once "../config/database.php";
header('Content-Type: application/json');

$userId = $_SESSION['id'] ?? null;
if (!$userId) { echo json_encode(['success'=>false,'message'=>'Non connecté']); exit; }

$action = $_POST['action'] ?? $_GET['action'] ?? 'get';

/* ── Création de la table si elle n'existe pas encore ── */
$pdo->exec("
  CREATE TABLE IF NOT EXISTS notifications (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT          NOT NULL,
    reservation_id INT        NULL,
    type         VARCHAR(50)  NOT NULL DEFAULT 'info',
    titre        VARCHAR(255) NOT NULL,
    message      TEXT         NOT NULL,
    lu           TINYINT(1)   NOT NULL DEFAULT 0,
    created_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* ══════════════════════════════════════
   GET — récupérer les notifications
══════════════════════════════════════ */
if ($action === 'get') {
    $limit = (int)($_GET['limit'] ?? 20);
    $stmt = $pdo->prepare("
        SELECT n.*, r.depart, r.destination, r.type_transport, r.statut
        FROM notifications n
        LEFT JOIN reservations r ON r.id = n.reservation_id
        WHERE n.user_id = :uid
        ORDER BY n.created_at DESC
        LIMIT :lim
    ");
    $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $unread = array_reduce($rows, fn($c,$r) => $c + (int)!$r['lu'], 0);
    echo json_encode(['success'=>true,'notifications'=>$rows,'unread'=>$unread]);
    exit;
}

/* ══════════════════════════════════════
   MARK_READ — marquer comme lue(s)
══════════════════════════════════════ */
if ($action === 'mark_read') {
    $notifId = $_POST['notif_id'] ?? null;  // null = toutes
    if ($notifId) {
        $stmt = $pdo->prepare("UPDATE notifications SET lu=1 WHERE id=:id AND user_id=:uid");
        $stmt->execute([':id'=>$notifId,':uid'=>$userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE notifications SET lu=1 WHERE user_id=:uid");
        $stmt->execute([':uid'=>$userId]);
    }
    echo json_encode(['success'=>true]);
    exit;
}

/* ══════════════════════════════════════
   SEND — créer une notification (interne / cron)
══════════════════════════════════════ */
if ($action === 'send') {
    $targetUserId   = (int)($_POST['target_user_id'] ?? $userId);
    $reservationId  = $_POST['reservation_id'] ? (int)$_POST['reservation_id'] : null;
    $type           = $_POST['type']    ?? 'info';    // info | success | warning | danger
    $titre          = trim($_POST['titre']   ?? '');
    $message        = trim($_POST['message'] ?? '');

    if (!$titre || !$message) {
        echo json_encode(['success'=>false,'message'=>'Titre et message requis']); exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, reservation_id, type, titre, message)
        VALUES (:uid, :rid, :type, :titre, :msg)
    ");
    $stmt->execute([
        ':uid'   => $targetUserId,
        ':rid'   => $reservationId,
        ':type'  => $type,
        ':titre' => $titre,
        ':msg'   => $message,
    ]);
    echo json_encode(['success'=>true,'id'=>$pdo->lastInsertId()]);
    exit;
}

echo json_encode(['success'=>false,'message'=>'Action inconnue']);