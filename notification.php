<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['reservation_id'])
    && isset($_POST['chauffeur_id'])
) {

    $reservationId = (int) $_POST['reservation_id'];
    $chauffeurId   = (int) $_POST['chauffeur_id'];

    /* ── 1. Mettre à jour la réservation ── */
    $update = $pdo->prepare("
        UPDATE reservations
        SET statut = 'accepté',
            chauffeur_id = ?
        WHERE id = ?
    ");
    $update->execute([$chauffeurId, $reservationId]);

    /* ── 2. Infos réservation + client ── */
    $res = $pdo->prepare("
        SELECT r.*, u.id AS client_user_id
        FROM reservations r
        LEFT JOIN users u ON u.nom = r.user_name
        WHERE r.id = ?
        LIMIT 1
    ");
    $res->execute([$reservationId]);
    $reservation = $res->fetch(PDO::FETCH_ASSOC);

    /* ── 3. Infos chauffeur + véhicule ── */
    $chauffeur = $pdo->prepare("
        SELECT u.nom, v.matricule, v.nom AS veh_nom, v.couleur
        FROM users u
        LEFT JOIN vehicules v ON v.chauffeur = u.nom
        WHERE u.id = ?
        LIMIT 1
    ");
    $chauffeur->execute([$chauffeurId]);
    $chauffeurData = $chauffeur->fetch(PDO::FETCH_ASSOC);

    $chauffeurName = $chauffeurData['nom'] ?? 'Chauffeur';
    $vehiculeInfo  = '';
    if (!empty($chauffeurData['veh_nom'])) {
        $vehiculeInfo = $chauffeurData['veh_nom'] . ' (' . $chauffeurData['matricule'] . ')';
    }

    /* ── 4. Notification client ── */
    $clientUserId = $reservation['client_user_id'] ?? null;
    if ($clientUserId) {
        $notifClient = $pdo->prepare("
            INSERT INTO notifications (user_id, reservation_id, type, titre, message)
            VALUES (?, ?, ?, ?, ?)
        ");
        $notifClient->execute([
            $clientUserId,
            $reservationId,
            'success',
            'Réservation acceptée ! 🎉',
            'Votre location ' . $reservation['depart']
            . ' a été confirmée. Chauffeur : ' . $chauffeurName
            . ($vehiculeInfo ? ' · ' . $vehiculeInfo : '') . '.'
        ]);
    }

    /* ── 5. Notification chauffeur ── */
    $notifChauffeur = $pdo->prepare("
        INSERT INTO notifications (user_id, reservation_id, type, titre, message)
        VALUES (?, ?, ?, ?, ?)
    ");
    $notifChauffeur->execute([
        $chauffeurId, $reservationId, 'info',
        'Nouvelle course assignée',
        'Réservation #' . $reservationId . ' · Location · Client : ' . $reservation['user_name'] . '.'
    ]);

    $_SESSION['success'] = "Réservation #$reservationId acceptée et assignée à $chauffeurName.";
    header("Location: notification.php");
    exit;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['reservation_id'])
    && isset($_POST['sans_chauffeur'])
) {
    $reservationId = (int) $_POST['reservation_id'];

    $pdo->prepare("
        UPDATE reservations SET statut = 'accepté' WHERE id = ?
    ")->execute([$reservationId]);

    /* Infos réservation + client */
    $res = $pdo->prepare("
        SELECT r.*, u.id AS client_user_id
        FROM reservations r
        LEFT JOIN users u ON u.nom = r.user_name
        WHERE r.id = ? LIMIT 1
    ");
    $res->execute([$reservationId]);
    $reservation = $res->fetch(PDO::FETCH_ASSOC);

    /* Notification client */
    if (!empty($reservation['client_user_id'])) {
        $pdo->prepare("
            INSERT INTO notifications (user_id, reservation_id, type, titre, message)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $reservation['client_user_id'],
            $reservationId,
            'success',
            'Location confirmée ! 🚗',
            'Votre location depuis ' . $reservation['depart']
            . ' a été confirmée. Le véhicule sera disponible à l\'heure convenue. Bonne route !'
        ]);
    }

    $_SESSION['success'] = "Réservation #$reservationId (location sans chauffeur) confirmée.";
    header("Location: notification.php");
    exit;
}


if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['refuser_id'])
) {
    $reservationId = (int) $_POST['refuser_id'];
    $raison = trim($_POST['raison_refus'] ?? "Réservation refusée par l'administration.");

    $pdo->prepare("
        UPDATE reservations SET statut = 'annulé', raison_annulation = ? WHERE id = ?
    ")->execute([$raison, $reservationId]);

    $res = $pdo->prepare("
        SELECT r.*, u.id AS client_user_id
        FROM reservations r
        LEFT JOIN users u ON u.nom = r.user_name
        WHERE r.id = ? LIMIT 1
    ");
    $res->execute([$reservationId]);
    $reservation = $res->fetch(PDO::FETCH_ASSOC);

    if (!empty($reservation['client_user_id'])) {
        $pdo->prepare("
            INSERT INTO notifications (user_id, reservation_id, type, titre, message)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $reservation['client_user_id'],
            $reservationId,
            'warning',
            'Réservation non disponible',
            'Votre demande ' . $reservation['depart']
            . ' → ' . $reservation['destination']
            . ' n\'a pas pu être traitée. ' . $raison
            . ' Un remboursement sera effectué si un paiement a été réalisé.'
        ]);
    }

    $_SESSION['success'] = "Réservation #$reservationId refusée.";
    header("Location: notification.php");
    exit;
}


$notifications = $pdo->query("
    SELECT r.*, u.id AS client_user_id
    FROM reservations r
    LEFT JOIN users u ON u.nom = r.user_name
    WHERE r.statut = 'en attente'
    ORDER BY r.id DESC
");


$stats = $pdo->query("
    SELECT
        SUM(CASE WHEN statut = 'en attente' THEN 1 ELSE 0 END) AS attente,
        SUM(CASE WHEN statut = 'accepté'    THEN 1 ELSE 0 END) AS accepte,
        SUM(CASE WHEN statut = 'annulé'     THEN 1 ELSE 0 END) AS annule,
        COUNT(*) AS total
    FROM reservations
")->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications Admin — Yoon bu Gaw</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}

body{
    background:#f1f5f9;
    font-family:'DM Sans',sans-serif;
    color:#0f172a;
    min-height:100vh;
}

.page-wrap{
    max-width:1000px;
    margin:0 auto;
    padding:24px 16px 60px;
}

/* TOPBAR */
.admin-topbar{
    background:#1d4ed8;
    border-radius:18px;
    padding:20px 24px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    flex-wrap:wrap;
    margin-bottom:28px;
}

.admin-topbar h2{
    display:flex;
    align-items:center;
    gap:10px;
    margin:0;
    color:#fff;
    font-size:20px;
    font-weight:700;
}

.admin-topbar p{
    margin:0;
    color:rgba(255,255,255,.8);
    font-size:13px;
}

.btn-back{
    display:flex;
    align-items:center;
    gap:6px;
    padding:9px 18px;
    background:rgba(255,255,255,.15);
    border:none;
    border-radius:12px;
    color:#fff;
    text-decoration:none;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
    transition:background .15s;
}

.btn-back:hover{
    background:rgba(255,255,255,.25);
    color:#fff;
}

/* STATS */
.stats-row{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
    margin-bottom:24px;
}

.stat-mini{
    background:#fff;
    border:.5px solid #e2e8f0;
    border-radius:14px;
    padding:14px 16px;
}

.stat-mini .val{
    font-size:22px;
    font-weight:700;
    line-height:1;
}

.stat-mini .lbl{
    margin-top:4px;
    color:#64748b;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.stat-mini.s-wait .val{color:#d97706;}
.stat-mini.s-ok .val{color:#16a34a;}
.stat-mini.s-no .val{color:#dc2626;}
.stat-mini.s-all .val{color:#1d4ed8;}

/* FLASH */
.flash{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:20px;
    padding:14px 18px;
    border:none;
    border-radius:14px;
    font-size:14px;
    font-weight:600;
}

.flash.ok{
    background:#f0fdf4;
    color:#166534;
    border-left:4px solid #16a34a;
}

/* CARD */
.res-card{
    background:#fff;
    border:.5px solid #e2e8f0;
    border-radius:20px;
    overflow:hidden;
    margin-bottom:20px;
    transition:box-shadow .2s;
}

.res-card:hover{
    box-shadow:0 6px 24px rgba(0,0,0,.07);
}

.res-card-head{
    display:flex;
    align-items:center;
    gap:14px;
    padding:18px 20px;
    border-bottom:.5px solid #f1f5f9;
}

.res-icon{
    width:52px;
    height:52px;
    display:flex;
    align-items:center;
    justify-content:center;
    flex-shrink:0;
    border-radius:14px;
    font-size:22px;
}

.res-icon.taxi{
    background:#fef9c3;
    color:#854d0e;
}

.res-icon.bus{
    background:#dbeafe;
    color:#1e40af;
}

.res-icon.cargo{
    background:#fee2e2;
    color:#b91c1c;
}

.res-icon.location{
    background:#f0fdf4;
    color:#166534;
}

.res-id{
    color:#0f172a;
    font-size:16px;
    font-weight:700;
}

.res-date{
    margin-top:2px;
    color:#64748b;
    font-size:12px;
}

.badge-wait{
    margin-left:auto;
    padding:5px 14px;
    background:#fef3c7;
    border-radius:20px;
    color:#92400e;
    font-size:12px;
    font-weight:600;
    white-space:nowrap;
}

/* LOCATION BADGE */
.loc-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin-bottom:12px;
    padding:4px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.loc-badge.avec{
    background:#f0fdf4;
    border:.5px solid #bbf7d0;
    color:#166534;
}

.loc-badge.sans{
    background:#f8fafc;
    border:.5px solid #cbd5e1;
    color:#475569;
}

.res-body{
    padding:18px 20px;
}

.info-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin-bottom:16px;
}

.info-cell{
    background:#f8fafc;
    border-radius:10px;
    padding:10px 12px;
}

.info-cell.full{
    grid-column:1/-1;
}

.info-cell .lbl{
    margin-bottom:3px;
    color:#94a3b8;
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.04em;
}

.info-cell .val{
    color:#0f172a;
    font-size:14px;
    font-weight:600;
}

.montant-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    margin-bottom:16px;
    padding:6px 12px;
    background:#eff6ff;
    border-radius:8px;
    color:#1d4ed8;
    font-size:14px;
    font-weight:700;
}

/* SECTION TITLE */
.loc-section-title{
    display:flex;
    align-items:center;
    gap:6px;
    margin-bottom:10px;
    color:#64748b;
    font-size:12px;
    font-weight:700;
    text-transform:uppercase;
    letter-spacing:.05em;
}

.loc-section-title i{
    font-size:14px;
}

/* ACTIONS */
.action-row{
    display:flex;
    align-items:flex-end;
    gap:10px;
    flex-wrap:wrap;
}

.form-select-styled{
    flex:1;
    min-width:200px;
    height:48px;
    padding:0 14px;
    background:#fff;
    border:.5px solid #cbd5e1;
    border-radius:12px;
    color:#0f172a;
    font-size:14px;
    cursor:pointer;
}

.form-select-styled:focus{
    outline:none;
    border-color:#3b82f6;
    box-shadow:0 0 0 3px rgba(59,130,246,.15);
}

.btn-accept{
    display:flex;
    align-items:center;
    gap:7px;
    height:48px;
    padding:0 20px;
    background:#16a34a;
    border:none;
    border-radius:12px;
    color:#fff;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    white-space:nowrap;
    transition:background .15s;
}

.btn-accept:hover{
    background:#15803d;
}

.btn-accept-sans{
    display:flex;
    align-items:center;
    gap:7px;
    height:48px;
    padding:0 20px;
    background:#0ea5e9;
    border:none;
    border-radius:12px;
    color:#fff;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    white-space:nowrap;
    transition:background .15s;
}

.btn-accept-sans:hover{
    background:#0284c7;
}

.btn-refuse{
    display:flex;
    align-items:center;
    gap:6px;
    height:48px;
    padding:0 16px;
    background:#fff;
    border:.5px solid #fecaca;
    border-radius:12px;
    color:#dc2626;
    font-size:14px;
    font-weight:600;
    cursor:pointer;
    transition:background .15s;
}

.btn-refuse:hover{
    background:#fef2f2;
}

/* DIVIDER */
.or-divider{
    display:flex;
    align-items:center;
    gap:10px;
    margin:14px 0;
    color:#94a3b8;
    font-size:12px;
    font-weight:600;
}

.or-divider::before,
.or-divider::after{
    content:'';
    flex:1;
    height:1px;
    background:#e2e8f0;
}

/* REFUSE */
.refuse-form{
    display:none;
    margin-top:12px;
    padding:14px;
    background:#fff5f5;
    border:.5px solid #fecaca;
    border-radius:12px;
}

.refuse-form textarea{
    width:100%;
    padding:10px;
    border:.5px solid #fecaca;
    border-radius:10px;
    resize:vertical;
    font-family:inherit;
    font-size:13px;
}

.refuse-form textarea:focus{
    outline:none;
    border-color:#f87171;
}

.btn-refuse-confirm{
    margin-top:10px;
    padding:8px 18px;
    background:#dc2626;
    border:none;
    border-radius:10px;
    color:#fff;
    font-size:13px;
    font-weight:600;
    cursor:pointer;
}

/* EMPTY */
.empty-state{
    padding:60px 20px;
    text-align:center;
    color:#94a3b8;
}

.empty-state i{
    display:block;
    margin-bottom:14px;
    font-size:56px;
    opacity:.4;
}

.empty-state h4{
    margin-bottom:6px;
    color:#64748b;
    font-size:17px;
    font-weight:700;
}

/* RESPONSIVE */
@media (max-width:640px){

    .stats-row{
        grid-template-columns:1fr 1fr;
    }

    .info-grid{
        grid-template-columns:1fr;
    }

    .info-cell.full{
        grid-column:1;
    }

    .action-row{
        flex-direction:column;
        align-items:stretch;
    }

    .btn-accept,
    .btn-accept-sans,
    .btn-refuse{
        justify-content:center;
    }
}
</style>
</head>
<body>

<?php include_once "sidebar.php"; ?>
<div class="main-content">
<?php include_once "header.php"; ?>

<div class="page-wrap">

  <!-- TOPBAR -->
  <div class="admin-topbar">
    <div>
      <h2><i class="bi bi-bell-fill"></i> Notifications Admin</h2>
      <p>Gestion des réservations en attente</p>
    </div>
    <a href="dashboard.php" class="btn-back"><i class="bi bi-arrow-left"></i> Dashboard</a>
  </div>

  <!-- FLASH -->
  <?php if (isset($_SESSION['success'])): ?>
    <div class="flash ok"><i class="bi bi-check-circle-fill"></i><?= htmlspecialchars($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stats-row">
    <div class="stat-mini s-wait"><div class="val"><?= $stats['attente'] ?></div><div class="lbl">En attente</div></div>
    <div class="stat-mini s-ok">  <div class="val"><?= $stats['accepte'] ?></div><div class="lbl">Acceptées</div></div>
    <div class="stat-mini s-no">  <div class="val"><?= $stats['annule']  ?></div><div class="lbl">Annulées</div></div>
    <div class="stat-mini s-all"> <div class="val"><?= $stats['total']   ?></div><div class="lbl">Total</div></div>
  </div>

  <!-- RÉSERVATIONS EN ATTENTE -->
  <?php if ($notifications->rowCount() > 0): ?>
    <?php while ($r = $notifications->fetch(PDO::FETCH_ASSOC)): ?>
      <?php
        $type   = strtolower($r['type_transport'] ?? 'taxi');
        $icones = [
          'taxi'     => 'bi-taxi-front-fill',
          'bus'      => 'bi-bus-front-fill',
          'cargo'    => 'bi-truck',
          'location' => 'bi-car-front-fill',
        ];
        $icone   = $icones[$type] ?? 'bi-car-front';
        $dateAff = !empty($r['date_reservation'])
          ? date('d M Y à H:i', strtotime($r['date_reservation'])) : '—';

        /* Détecter si location avec ou sans chauffeur */
        $isLocation    = ($type === 'location');
        $avecChauffeur = $isLocation && (
            stripos($r['chauffeur'] ?? '', 'avec') !== false ||
            !empty($r['chauffeur_id'])
        );
        /* Si chauffeur stocké n'est pas "Sans chauffeur" et n'est pas vide → avec chauffeur */
        $chauffeurVal = trim($r['chauffeur'] ?? '');
        $avecChauffeur = $isLocation && !empty($chauffeurVal)
                         && strtolower($chauffeurVal) !== 'sans chauffeur';
      ?>

      <div class="res-card">
        <div class="res-card-head">
          <div class="res-icon <?= $type ?>"><i class="bi <?= $icone ?>"></i></div>
          <div>
            <div class="res-id">Réservation #<?= $r['id'] ?></div>
            <div class="res-date"><i class="bi bi-clock" style="font-size:11px"></i> <?= $dateAff ?></div>
          </div>
          <span class="badge-wait"><i class="bi bi-hourglass-split"></i> En attente</span>
        </div>

        <div class="res-body">

          <!-- Montant -->
          <div class="montant-badge">
            <i class="bi bi-cash-stack"></i>
            <?= number_format($r['montant'] ?? 0, 0, ',', ' ') ?> FCFA
          </div>

          <!-- Badge avec/sans chauffeur pour location -->
          <?php if ($isLocation): ?>
            <?php if ($avecChauffeur): ?>
              <div class="loc-badge avec">
                <i class="bi bi-person-fill"></i> Avec chauffeur demandé
              </div>
            <?php else: ?>
              <div class="loc-badge sans">
                <i class="bi bi-car-front-fill"></i> Sans chauffeur (véhicule seul)
              </div>
            <?php endif; ?>
          <?php endif; ?>

          <!-- Infos grille -->
          <div class="info-grid">
            <div class="info-cell">
              <div class="lbl">Client</div>
              <div class="val"><i class="bi bi-person-fill" style="font-size:12px;color:#64748b"></i> <?= htmlspecialchars($r['user_name']) ?></div>
            </div>
            <div class="info-cell">
              <div class="lbl">Transport</div>
              <div class="val"><i class="bi <?= $icone ?>" style="font-size:12px;color:#64748b"></i> <?= ucfirst(htmlspecialchars($r['type_transport'] ?? '')) ?></div>
            </div>
            <div class="info-cell">
              <div class="lbl"><?= $isLocation ? 'Lieu de prise en charge' : 'Départ' ?></div>
              <div class="val">📍 <?= htmlspecialchars($r['depart']) ?></div>
            </div>
            <div class="info-cell">
              <div class="lbl"><?= $isLocation ? 'Lieu de restitution' : 'Destination' ?></div>
              <div class="val">🏁 <?= htmlspecialchars($r['destination']) ?></div>
            </div>
            <?php if (!empty($r['heure_depart'])): ?>
            <div class="info-cell full">
              <div class="lbl"><?= $isLocation ? 'Heure de prise en charge' : 'Heure de départ' ?></div>
              <div class="val"><i class="bi bi-clock" style="font-size:12px;color:#64748b"></i> <?= htmlspecialchars($r['heure_depart']) ?></div>
            </div>
            <?php endif; ?>
            <?php if (!empty($r['mode_paiement'])): ?>
            <div class="info-cell">
              <div class="lbl">Paiement</div>
              <div class="val"><?= htmlspecialchars($r['mode_paiement']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($isLocation && !empty($r['nb_places'])): ?>
            <div class="info-cell">
              <div class="lbl">Durée</div>
              <div class="val"><?= (int)$r['nb_places'] ?> jour(s)</div>
            </div>
            <?php elseif (!empty($r['nb_places']) && $r['nb_places'] > 1): ?>
            <div class="info-cell">
              <div class="lbl">Places</div>
              <div class="val"><?= (int)$r['nb_places'] ?> personne(s)</div>
            </div>
            <?php endif; ?>
            <?php if (!empty($r['matricule'])): ?>
            <div class="info-cell">
              <div class="lbl">Véhicule</div>
              <div class="val"><i class="bi bi-car-front" style="font-size:12px;color:#64748b"></i> <?= htmlspecialchars($r['matricule']) ?></div>
            </div>
            <?php endif; ?>
          </div>

          <!-- ═══ ACTIONS SELON TYPE ═══ -->

          <?php if ($isLocation && $avecChauffeur): ?>
            <!-- LOCATION AVEC CHAUFFEUR : assigner un chauffeur -->
            <div class="loc-section-title"><i class="bi bi-person-check-fill" style="color:#16a34a"></i> Assigner un chauffeur</div>
            <form method="POST">
              <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
              <div class="action-row">
                <select name="chauffeur_id" class="form-select-styled" required>
                  <option value="">— Choisir un chauffeur —</option>
                  <?php
                    $drivers = $pdo->query("SELECT id, nom FROM users WHERE role='chauffeur' ORDER BY nom");
                    while ($d = $drivers->fetch()):
                  ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option>
                  <?php endwhile; ?>
                </select>
                <button type="submit" class="btn-accept">
                  <i class="bi bi-check-circle-fill"></i> Accepter & Assigner
                </button>
                <button type="button" class="btn-refuse" onclick="toggleRefuse(<?= $r['id'] ?>)">
                  <i class="bi bi-x-circle"></i> Refuser
                </button>
              </div>
            </form>

          <?php elseif ($isLocation && !$avecChauffeur): ?>
            <!-- LOCATION SANS CHAUFFEUR : simple confirmation -->
            <div class="loc-section-title"><i class="bi bi-car-front-fill" style="color:#0ea5e9"></i> Location véhicule seul — aucun chauffeur requis</div>
            <div class="action-row">
              <form method="POST">
                <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
                <input type="hidden" name="sans_chauffeur" value="1">
                <button type="submit" class="btn-accept-sans">
                  <i class="bi bi-check-circle-fill"></i> Confirmer la location
                </button>
              </form>
              <button type="button" class="btn-refuse" onclick="toggleRefuse(<?= $r['id'] ?>)">
                <i class="bi bi-x-circle"></i> Refuser
              </button>
            </div>

          <?php else: ?>
            <!-- TAXI / BUS / CARGO : assigner chauffeur classique -->
            <form method="POST">
              <input type="hidden" name="reservation_id" value="<?= $r['id'] ?>">
              <div class="action-row">
                <select name="chauffeur_id" class="form-select-styled" required>
                  <option value="">— Choisir un chauffeur —</option>
                  <?php
                    $drivers = $pdo->query("SELECT id, nom FROM users WHERE role='chauffeur' ORDER BY nom");
                    while ($d = $drivers->fetch()):
                  ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nom']) ?></option>
                  <?php endwhile; ?>
                </select>
                <button type="submit" class="btn-accept">
                  <i class="bi bi-check-circle-fill"></i> Accepter & Assigner
                </button>
                <button type="button" class="btn-refuse" onclick="toggleRefuse(<?= $r['id'] ?>)">
                  <i class="bi bi-x-circle"></i> Refuser
                </button>
              </div>
            </form>
          <?php endif; ?>

          <!-- FORM REFUSER (collapse commun) -->
          <div class="refuse-form" id="refuse-<?= $r['id'] ?>">
            <form method="POST">
              <input type="hidden" name="refuser_id" value="<?= $r['id'] ?>">
              <label style="font-size:13px;font-weight:600;color:#dc2626;display:block;margin-bottom:6px">
                <i class="bi bi-chat-left-text"></i> Raison du refus (visible par le client)
              </label>
              <textarea name="raison_refus" rows="2"
                placeholder="Ex: Véhicule non disponible pour cette période…"
              ></textarea>
              <button type="submit" class="btn-refuse-confirm">
                <i class="bi bi-x-circle-fill"></i> Confirmer le refus
              </button>
            </form>
          </div>

        </div>
      </div>
    <?php endwhile; ?>
  <?php else: ?>
    <div class="empty-state">
      <i class="bi bi-bell-slash"></i>
      <h4>Aucune réservation en attente</h4>
      <p>Toutes les demandes ont été traitées.</p>
    </div>
  <?php endif; ?>

</div>
</div>

<script>
function toggleRefuse(id) {
  const el = document.getElementById('refuse-' + id);
  el.style.display = el.style.display === 'block' ? 'none' : 'block';
}
</script>
</body>
</html>