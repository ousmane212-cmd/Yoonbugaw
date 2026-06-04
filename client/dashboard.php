<?php
session_start();
require_once "../config/database.php";

$userName = $_SESSION['nom'] ?? 'Client';
$userId   = $_SESSION['id'] ?? null;
if (!$userId) { die("Utilisateur non connecté"); }

$parts     = explode(' ', trim($userName));
$initiales = strtoupper(
    substr($parts[0], 0, 1) .
    (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
);

/* Stats client */
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM reservations WHERE user_name = :uname");
$stmt->execute([':uname' => $userName]);
$totalReservations = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("SELECT SUM(montant) as total_depense FROM reservations WHERE user_name = :uname");
$stmt->execute([':uname' => $userName]);
$totalDepense = $stmt->fetch()['total_depense'] ?? 0;

$stmt = $pdo->prepare("SELECT COUNT(*) as total_cargo FROM reservations WHERE type_transport='cargo' AND user_name = :uname");
$stmt->execute([':uname' => $userName]);
$totalCargo = $stmt->fetch()['total_cargo'] ?? 0;

$stmt = $pdo->prepare("SELECT AVG(note_client) as moyenne_note FROM reservations WHERE user_name = :uname");
$stmt->execute([':uname' => $userName]);
$noteMoyenne = round($stmt->fetch()['moyenne_note'] ?? 0, 1);

$stmt = $pdo->prepare("SELECT id, depart, destination, montant, statut, type_transport, date_reservation FROM reservations WHERE user_name = :uname ORDER BY id DESC LIMIT 5");
$stmt->execute([':uname' => $userName]);
$recentReservations = $stmt->fetchAll();

/* ── Véhicules depuis la BDD */
$stmtV = $pdo->prepare("
    SELECT id, matricule, nom, photo, chauffeur, type, couleur, statut
    FROM vehicules
    WHERE statut = 'disponible'
    ORDER BY type, id
");
$stmtV->execute();
$allVehicules = $stmtV->fetchAll(PDO::FETCH_ASSOC);

$vehiculesByType = ['taxi' => [], 'bus' => [], 'cargo' => [], 'location' => []];
foreach ($allVehicules as $v) {
    $t = strtolower($v['type']);
    if (in_array($t, ['camionnette','camion','semi','semi-remorque'])) $t = 'cargo';
    if ($t === 'voiture' || $t === 'suv' || $t === 'berline') $t = 'location';
    if (!isset($vehiculesByType[$t])) $vehiculesByType[$t] = [];
    $vehiculesByType[$t][] = [
        'id'       => $v['id'],
        'matricule'=> $v['matricule'],
        'nom'      => $v['nom'],
        'photo'    => $v['photo'],
        'chauffeur'=> $v['chauffeur'],
        'couleur'  => $v['couleur'],
        'type'     => $t,
    ];
}
$vehiculesJson = json_encode($vehiculesByType, JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yoon bu Gaw — Dashboard Client</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="layout">

<?php include_once "header.php"; ?>

  <!-- MAIN -->
  <main class="main-content" style="padding-top:24px">

    <!-- STATS -->
    <div class="stats-grid">
      <div class="stat-card s1">
        <div class="stat-icon g"><i class="bi bi-ticket-perforated-fill"></i></div>
        <div class="stat-val"><?= $totalReservations ?></div>
        <div class="stat-lbl">Réservations effectuées</div>
        <div class="stat-change"><i class="bi bi-arrow-up-circle-fill"></i> Total enregistré</div>
      </div>
      <div class="stat-card s2">
        <div class="stat-icon a"><i class="bi bi-currency-exchange"></i></div>
        <div class="stat-val"><?= number_format($totalDepense, 0, ',', ' ') ?></div>
        <div class="stat-lbl">FCFA dépensés</div>
        <div class="stat-change"><i class="bi bi-cash-stack"></i> Total paiements</div>
      </div>
      <div class="stat-card s3">
        <div class="stat-icon b"><i class="bi bi-truck-front-fill"></i></div>
        <div class="stat-val"><?= $totalCargo ?></div>
        <div class="stat-lbl">Livraisons cargo</div>
        <div class="stat-change"><i class="bi bi-box-seam-fill"></i> Transport marchandises</div>
      </div>
      <div class="stat-card s4">
        <div class="stat-icon r"><i class="bi bi-star-fill"></i></div>
        <div class="stat-val"><?= $noteMoyenne ?></div>
        <div class="stat-lbl">Note moyenne</div>
        <div class="stat-change"><i class="bi bi-emoji-smile-fill"></i> Satisfaction clients</div>
      </div>
    </div>

    <!-- PANNEAU DE RÉSERVATION INTÉGRÉ -->
    <div class="search-panel" style="padding:24px">
      <div class="panel-title" style="margin-bottom:4px">Réserver un transport</div>
      <div class="panel-sub" style="margin-bottom:20px">Choisissez votre service, suivez les étapes et confirmez en quelques clics.</div>

      <!-- SERVICE TABS -->
      <div class="svc-tabs">
        <div class="svc-tab text-white" id="tab-location" onclick="switchService('location')" style="background:#07882b;">
          <span class="tab-icon"><i class="bi bi-car-front-fill"></i></span>Location auto
        </div>
        <div class="svc-tab active-bus text-white" id="tab-bus" onclick="switchService('bus')" style="background:#0000b8;">
          <span class="tab-icon"><i class="bi bi-bus-front-fill"></i></span>Car &amp; Bus
        </div>
        <div class="svc-tab text-white" id="tab-taxi" onclick="switchService('taxi')" style="background:#F78E0C;">
          <span class="tab-icon"><i class="bi bi-taxi-front-fill"></i></span>Taxis
        </div>
        <div class="svc-tab cargo text-white" id="tab-cargo" onclick="switchService('cargo')" style="background:#011728;">
          <span class="tab-icon"><i class="bi bi-truck-front-fill"></i></span>Transport cargo
        </div>
      </div>

      <!-- CONTENU DYNAMIQUE -->
      <div id="res-main-content"></div>
    </div>

    <!-- BOTTOM -->
    <div class="bottom-grid" style="margin-top:24px;padding-bottom:20px">
      <div class="panel">
        <div class="panel-head">
          <h3>Trajets récents</h3>
          <button class="btn-link" onclick="location.href='mes_trajets.php'">Voir tout</button>
        </div>
        <?php if (count($recentReservations) > 0): ?>
          <?php foreach ($recentReservations as $trajet): ?>
            <?php
              $transport  = strtolower($trajet['type_transport']);
              $icon       = 'bi-taxi-front-fill'; $classIcon = 'taxi';
              if ($transport === 'bus')      { $icon = 'bi-bus-front-fill';   $classIcon = 'bus'; }
              if ($transport === 'cargo')    { $icon = 'bi-truck-front-fill'; $classIcon = 'cargo'; }
              if ($transport === 'location') { $icon = 'bi-car-front-fill';   $classIcon = 'taxi'; }
              $montant      = number_format($trajet['montant'], 0, ',', ' ');
              $dateAffichee = !empty($trajet['date_reservation'])
                ? date('d M Y', strtotime($trajet['date_reservation'])) : 'Date inconnue';
              $statLower  = strtolower($trajet['statut'] ?? '');
              $bClass     = 'bp-s';
              if ($statLower === 'en attente') $bClass = 'bp-w';
              elseif (in_array($statLower, ['annulé','annule'])) $bClass = 'bp-r';
              elseif ($statLower === 'en cours') $bClass = 'bp-i';
            ?>
            <div class="trajet-item">
              <div class="ti-icon <?= $classIcon ?>"><i class="bi <?= $icon ?>"></i></div>
              <div>
                <div class="ti-route"><?= htmlspecialchars($trajet['depart']) ?> → <?= htmlspecialchars($trajet['destination']) ?></div>
                <div class="ti-date"><?= $dateAffichee ?> <span class="badge-pill <?= $bClass ?>"><?= htmlspecialchars(ucfirst($trajet['statut'])) ?></span></div>
              </div>
              <div class="ti-price"><?= $montant ?> F</div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div style="padding:20px;text-align:center;color:#64748b">
            <i class="bi bi-calendar-x" style="font-size:32px;display:block;margin-bottom:8px;opacity:.4"></i>
            Aucune réservation disponible
          </div>
        <?php endif; ?>
      </div>

      <div>
        <div class="promo-card" style="margin-bottom:20px">
          <div class="promo-tag"><i class="bi bi-stars"></i> Offre spéciale</div>
          <h3>-20% sur vos<br>trajets ce weekend</h3>
          <p>Valable sur taxis, bus et cargo partout au Sénégal</p>
          <div class="promo-actions">
            <button class="btn-promo white">Profiter maintenant</button>
            <button class="btn-promo ghost">En savoir plus</button>
          </div>
          <div class="promo-emoji"><img src="../assets/images/taxi.png" alt=""></div>
        </div>
        <div class="panel">
          <div class="panel-head"><h3>Destinations populaires</h3></div>
          <div style="display:flex;flex-wrap:wrap;gap:8px">
            <span class="quick-chip" onclick="quickStart('bus','Thiès – Ville')"><i class="bi bi-bus-front-fill"></i> Thiès</span>
            <span class="quick-chip" onclick="quickStart('bus','Kaolack – Ville')"><i class="bi bi-bus-front-fill"></i> Kaolack</span>
            <span class="quick-chip" onclick="quickStart('bus','Touba')"><i class="bi bi-bus-front-fill"></i> Touba</span>
            <span class="quick-chip" onclick="quickStart('bus','Saint-Louis – Ville')"><i class="bi bi-bus-front-fill"></i> Saint-Louis</span>
            <span class="quick-chip" onclick="quickStart('bus','Ziguinchor – Ville')"><i class="bi bi-bus-front-fill"></i> Ziguinchor</span>
            <span class="quick-chip" onclick="quickStart('bus','Tambacounda – Ville')"><i class="bi bi-bus-front-fill"></i> Tambacounda</span>
            <span class="quick-chip" onclick="quickStart('bus','Kédougou – Ville')"><i class="bi bi-bus-front-fill"></i> Kédougou</span>
            <span class="quick-chip" onclick="quickStart('bus','Matam')"><i class="bi bi-bus-front-fill"></i> Matam</span>
            <span class="quick-chip" onclick="quickStart('taxi','Dakar – Plateau')"><i class="bi bi-taxi-front-fill"></i> Plateau</span>
            <span class="quick-chip" onclick="quickStart('taxi','Dakar – Almadies')"><i class="bi bi-taxi-front-fill"></i> Almadies</span>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- MOBILE NAV -->
  <nav class="mobile-nav">
    <div class="mobile-nav-inner">
      <button class="mnav-item active"><i class="bi bi-house-fill"></i>Accueil</button>
      <button class="mnav-item" onclick="switchService('taxi')"><i class="bi bi-taxi-front-fill"></i>Taxis</button>
      <button class="mnav-item" onclick="switchService('bus')"><i class="bi bi-bus-front-fill"></i>Bus</button>
      <button class="mnav-item" onclick="switchService('cargo')"><i class="bi bi-truck-front-fill"></i>Cargo</button>
      <button class="mnav-item" onclick="switchService('location')"><i class="bi bi-car-front-fill"></i>Location</button>
    </div>
  </nav>
</div>

<script type="text/javascript">
    const VEHICULES_BDD = <?= $vehiculesJson ?>;
</script>Véhicules cargo disponibles
<script src="dashboard.js"></script>

</body>
</html>