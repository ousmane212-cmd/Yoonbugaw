<?php
session_start();
require_once "../config/database.php";

$userName = $_SESSION['nom'] ?? 'Client';
$userId   = $_SESSION['id'] ?? null;

if (!$userId) {
    die("Utilisateur non connecté");
}

/* Initiales */
$parts = explode(' ', trim($userName));
$initiales = strtoupper(
    substr($parts[0], 0, 1) .
    (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
);
 

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM reservations
    WHERE client_id = :id
");
$stmt->execute([':id' => $userId]);
$totalReservations = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->prepare("
    SELECT SUM(montant) as total_depense
    FROM reservations
    WHERE client_id = :id
");
$stmt->execute([':id' => $userId]);
$totalDepense = $stmt->fetch()['total_depense'] ?? 0;

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total_cargo
    FROM reservations
    WHERE type_transport='cargo'
    AND client_id = :id
");
$stmt->execute([':id' => $userId]);
$totalCargo = $stmt->fetch()['total_cargo'] ?? 0;

$stmt = $pdo->prepare("
    SELECT AVG(note_client) as moyenne_note
    FROM reservations
    WHERE client_id = :id
");
$stmt->execute([':id' => $userId]);
$noteMoyenne = round($stmt->fetch()['moyenne_note'] ?? 0, 1);

/* Dernières réservations */
$stmt = $pdo->prepare("
    SELECT id, depart, destination, montant, statut, type_transport, date_reservation
    FROM reservations
    WHERE client_id = :id
    ORDER BY id DESC
    LIMIT 5
");
$stmt->execute([':id' => $userId]);
$recentReservations = $stmt->fetchAll();
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
<style>
  .btn-suivre {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 14px;
  border-radius: 999px;
  background: linear-gradient(135deg, #0ea5e9, #0284c7);
  color: #fff;
  font-family: 'DM Sans', sans-serif;
  font-size: 12.5px;
  font-weight: 600;
  text-decoration: none;
  white-space: nowrap;
  box-shadow: 0 2px 8px rgba(14, 165, 233, 0.35);
  position: relative;
  transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.btn-suivre::before {
  content: '';
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.7);
  animation: pulse-live 1.6s infinite;
}

.btn-suivre:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(14, 165, 233, 0.45);
  color: #fff;
}

@keyframes pulse-live {
  0%   { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.6); }
  70%  { box-shadow: 0 0 0 6px rgba(255, 255, 255, 0); }
  100% { box-shadow: 0 0 0 0 rgba(255, 255, 255, 0); }
}

@media (max-width: 480px) {
  .btn-suivre {
    font-size: 11.5px;
    padding: 5px 10px;
  }
}
</style>
</head>
<body>
<div class="layout">

<?php include_once "header.php"; ?>

 
  <main class="main-content" style="padding-top:24px">

   
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

      
      <div id="res-main-content"></div>
    </div>

   
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
              <?php if (strtolower(trim($trajet['statut'])) === 'en cours'): ?>
  <a class="btn-suivre" href="suivi_client.php?id=<?= $trajet['id'] ?>">🗺 Suivre</a>
<?php endif; ?>
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
<?php
/* Véhicules cargo disponibles */
$stmtVeh = $pdo->prepare("SELECT * FROM vehicules WHERE type = 'cargo' AND statut = 'disponible'");
$stmtVeh->execute();
$vehiculesJson = json_encode($stmtVeh->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
?>
<script type="text/javascript">
    const VEHICULES_BDD = <?= $vehiculesJson ?>;
</script>
<script type="text/javascript" src="dashboard.js"></script>

<?php
$stmt = $pdo->prepare("SELECT id FROM reservations WHERE client_id = :id AND statut_course = 'en cours' ORDER BY id DESC LIMIT 1");
$stmt->execute([':id' => $userId]);
$courseActive = $stmt->fetch();
?>
<script>
<?php if ($courseActive): ?>
const ACTIVE_COURSE_ID = <?= (int)$courseActive['id'] ?>;
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(pos => {
        fetch('ajax/update_position.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                course_id: ACTIVE_COURSE_ID,
                lat: pos.coords.latitude,
                lng: pos.coords.longitude,
                role: 'client'
            })
        }).catch(e => console.warn('GPS client:', e));
    }, null, { enableHighAccuracy: true });
}
<?php endif; ?>
</script>
</body>
</html>