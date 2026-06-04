<?php
session_start();
require_once "../config/database.php";

$userId   = $_SESSION['id']  ?? null;
$userName = $_SESSION['nom'] ?? null;

if (!$userId) {
    header('Location: ../login.php');
    exit;
}

$reservationId = (int)($_GET['reservation_id'] ?? 0);
if (!$reservationId) {
    die('Réservation invalide.');
}

$stmt = $pdo->prepare("
    SELECT
        r.*,
        v.matricule,
        v.nom     AS veh_nom,
        v.chauffeur,
        v.couleur,
        v.photo   AS veh_photo
    FROM reservations r
    LEFT JOIN vehicules v ON v.matricule = r.matricule
    WHERE r.id = :id
      AND r.user_name = :uname
    LIMIT 1
");
$stmt->execute([':id' => $reservationId, ':uname' => $userName]);
$res = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$res) {
    die('Réservation introuvable.');
}

$parts    = explode(' ', trim($res['chauffeur'] ?? 'X X'));
$initiale1 = strtoupper(substr($parts[0] ?? 'X', 0, 1));
$initiale2 = strtoupper(substr($parts[1] ?? 'X', 0, 1));
$initiales = $initiale1 . $initiale2;

$statut      = strtolower(trim($res['statut'] ?? ''));
$pillClass   = match($statut) {
    'en cours'  => 'enroute',
    'arrivé', 'arrive' => 'arrive',
    default     => 'attente',
};

$montantFormate = number_format($res['montant'] ?? 0, 0, ',', ' ');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suivi trajet — Yoon bu Gaw</title>

<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="style.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="trajets.css">


</style>
</head>
<body>
  <div class="layout">

<?php include_once "haeder.php"; ?>
<main class="main-content">
    <div class="topbar-left">

<div class="tk-header">
  <a href="dashboard.php" class="tk-back" title="Retour">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div>
    <div class="tk-title">Suivi en direct</div>
    <div class="tk-sub text-white">
      <?= htmlspecialchars($res['depart']) ?> → <?= htmlspecialchars($res['destination']) ?>
    </div>
  </div>
  <span class="status-pill <?= $pillClass ?>" id="status-pill">
    <?= htmlspecialchars(ucfirst($res['statut'] ?? 'En attente')) ?>
  </span>
</div>


<div id="map"></div>


<div class="eta-bar">
  <div>
    <div class="eta-label">Arrivée estimée</div>
    <div class="eta-val" id="eta-val">Calcul…</div>
  </div>
  <div class="eta-right" id="dist-val">—</div>
</div>


<div class="section">
  <div class="stops-row">
    <div class="stop-col">
      <div class="stop-dot"></div>
      <div class="stop-line"></div>
      <div class="stop-name"><?= htmlspecialchars(explode(',', $res['depart'])[0]) ?></div>
      <div class="stop-txt"><?= htmlspecialchars($res['depart']) ?></div>
    </div>
    <div class="stop-col" style="padding-left:14px">
      <div class="stop-dot dest"></div>
      <div class="stop-name"><?= htmlspecialchars(explode(',', $res['destination'])[0]) ?></div>
      <div class="stop-txt"><?= htmlspecialchars($res['destination']) ?></div>
    </div>
  </div>
</div>

<div class="gap-sm"></div>


<div class="section" style="padding-top:0">
  <div class="drv-card">

    <!-- TOP : avatar + nom + note -->
    <div class="drv-top">
      <?php if (!empty($res['veh_photo'])): ?>
        <img
          src="../<?= htmlspecialchars($res['veh_photo']) ?>"
          class="drv-photo"
          onerror="this.outerHTML='<div class=\'drv-av\'><?= $initiales ?></div>'"
          alt="Photo véhicule"
        >
      <?php else: ?>
        <div class="drv-av"><?= $initiales ?></div>
      <?php endif; ?>

      <div style="flex:1">
        <div class="drv-name text-white"><?= htmlspecialchars($res['chauffeur'] ?? 'Chauffeur') ?></div>
        <div class="drv-sub text-white">
          <?= htmlspecialchars($res['veh_nom'] ?? '') ?>
          <?php if (!empty($res['matricule'])): ?>
            · <?= htmlspecialchars($res['matricule']) ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="drv-rating">
        <span class="drv-stars">★</span>
        <span class="drv-score" id="drv-rating">4.7</span>
      </div>
    </div>

    <div class="info-grid">
      <div class="info-cell">
        <div class="ilabel">Départ</div>
        <div class="ival text-white"><?= htmlspecialchars($res['depart']) ?></div>
      </div>
      <div class="info-cell">
        <div class="ilabel">Destination</div>
        <div class="ival text-white"><?= htmlspecialchars($res['destination']) ?></div>
      </div>
      <div class="info-cell">
        <div class="ilabel">Arrivée estimée</div>
        <div class="ival accent" id="eta-card">Calcul…</div>
      </div>
      <div class="info-cell">
        <div class="ilabel">Montant</div>
        <div class="ival text-white"><?= $montantFormate ?> FCFA</div>
      </div>
    </div>

    
    <div class="prog-zone">
      <div class="prog-label">
        <span>Progression du trajet</span>
        <span style="color:var(--accent)" id="prog-pct">—</span>
      </div>
      <div class="prog-track">
        <div class="prog-fill" id="prog-fill" style="width:0%"></div>
      </div>
    </div>

    
    <div class="action-row">
      <button class="btn-act primary" onclick="centerMap()">
        <i class="bi bi-geo-alt-fill"></i>
        Centrer
      </button>
      <a class="btn-act" href="tel:+221<?= preg_replace('/\D/', '', $res['chauffeur'] ?? '') ?>">
        <i class="bi bi-telephone-fill"></i>
        Appeler
      </a>
      <button class="btn-act danger" onclick="openCancel()">
        <i class="bi bi-x-circle"></i>
        Annuler
      </button>
    </div>

  </div>
</div>

<div style="height:24px"></div>


<div class="cancel-modal" id="cancel-modal" onclick="handleModalBg(event)">
  <div class="cancel-sheet">
    <div class="modal-handle"></div>
    <div class="modal-title">Annuler la réservation ?</div>
    <div class="modal-ref">
      Réf. #<?= $reservationId ?> · <?= htmlspecialchars($res['depart']) ?> → <?= htmlspecialchars($res['destination']) ?>
    </div>

    <div class="refund-box ok" id="refund-ok">
      <i class="bi bi-check-circle-fill"></i>
      <div>
        <strong>Remboursement intégral</strong> — annulation dans le délai de 2h.<br>
        Vous recevrez <strong><?= $montantFormate ?> FCFA</strong> sur votre mode de paiement initial.
      </div>
    </div>

    <div class="refund-box warn" id="refund-warn" style="display:none">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div>
        <strong>Frais d'annulation (15%)</strong> — délai de 2h dépassé.<br>
        Remboursement estimé : <strong id="refund-partiel-val">—</strong> FCFA.
      </div>
    </div>

    <div class="cancel-options" id="cancel-options">
      <div class="cancel-opt sel" onclick="selectRaison(this, 'Changement de plan')">
        <div class="radio-dot"></div>Changement de plan
      </div>
      <div class="cancel-opt" onclick="selectRaison(this, 'Problème personnel')">
        <div class="radio-dot"></div>Problème personnel
      </div>
      <div class="cancel-opt" onclick="selectRaison(this, 'Chauffeur trop lent')">
        <div class="radio-dot"></div>Chauffeur trop lent
      </div>
      <div class="cancel-opt" onclick="selectRaison(this, 'Autre')">
        <div class="radio-dot"></div>Autre raison
      </div>
    </div>

    <button class="btn-cancel-confirm" onclick="confirmCancel()">
      Confirmer l'annulation
    </button>
    <button class="btn-cancel-close" onclick="closeCancel()">
      ← Retour au suivi
    </button>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast">
  <i class="bi bi-check-circle-fill" style="color:var(--green)"></i>
  <span id="toast-msg">Action effectuée</span>
</div>
</div>
 </main>
          </div>


<script>
/* ── Données PHP → JS ── */
const RES_ID    = <?= (int)$reservationId ?>;
const MONTANT   = <?= (float)($res['montant'] ?? 0) ?>;
const DATE_RES  = '<?= addslashes($res['date_reservation'] ?? '') ?>';
const LAT_INIT  = <?= (float)($res['lat_chauffeur'] ?? 14.692) ?>;
const LNG_INIT  = <?= (float)($res['lng_chauffeur'] ?? -17.446) ?>;
const LAT_DEST  = <?= (float)($res['lat_destination'] ?? 14.720) ?>;
const LNG_DEST  = <?= (float)($res['lng_destination'] ?? -17.490) ?>;


let currentRaison = 'Changement de plan';


const map = L.map('map', { zoomControl: true }).setView(
  [LAT_INIT || 14.692, LNG_INIT || -17.446], 14
);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '© OpenStreetMap',
  maxZoom: 19
}).addTo(map);


const carIcon = L.divIcon({
  html: `<div style="
    width:44px;height:44px;border-radius:50%;
    background:#f4c842;
    display:flex;align-items:center;justify-content:center;
    font-size:22px;
    box-shadow:0 0 0 8px rgba(244,200,66,.2),0 0 0 16px rgba(244,200,66,.08);
  ">🚕</div>`,
  iconSize: [44,44], iconAnchor: [22,22], className: ''
});


const destIcon = L.divIcon({
  html: `<div style="position:relative;width:20px;height:20px">
    <div style="
      position:absolute;inset:0;border-radius:50%;
      background:rgba(239,68,68,.25);
      animation:pulse 1.8s ease-out infinite;
    "></div>
    <div style="
      position:absolute;top:50%;left:50%;
      transform:translate(-50%,-50%);
      width:12px;height:12px;border-radius:50%;
      background:#ef4444;border:2px solid #fff;
    "></div>
  </div>
  <style>
    @keyframes pulse{
      0%{transform:scale(1);opacity:.9}
      100%{transform:scale(2.5);opacity:0}
    }
  </style>`,
  iconSize: [20,20], iconAnchor: [10,10], className: ''
});

const markerCar  = L.marker([LAT_INIT || 14.692, LNG_INIT || -17.446], {icon: carIcon}).addTo(map);
const markerDest = L.marker([LAT_DEST || 14.720, LNG_DEST || -17.490], {icon: destIcon})
  .addTo(map)
  .bindPopup('<?= addslashes(htmlspecialchars($res['destination'])) ?>');

let routeLine = null;

function centerMap() {
  map.panTo(markerCar.getLatLng(), { animate: true });
}

/* Dessine / rafraîchit la ligne de route */
function drawRoute(fromLat, fromLng) {
  if (routeLine) map.removeLayer(routeLine);
  routeLine = L.polyline(
    [[fromLat, fromLng], [LAT_DEST || 14.720, LNG_DEST || -17.490]],
    { color: '#f4c842', weight: 3, opacity: .7, dashArray: '6 6' }
  ).addTo(map);
}
drawRoute(LAT_INIT || 14.692, LNG_INIT || -17.446);


function fetchPosition() {
  fetch('get_driver_position.php?reservation_id=' + RES_ID)
    .then(r => r.json())
    .then(d => {
      if (d.lat && d.lng) {
        markerCar.setLatLng([d.lat, d.lng]);
        drawRoute(d.lat, d.lng);

        const eta = d.eta_minutes ? d.eta_minutes + ' min' : '—';
        document.getElementById('eta-val').textContent  = eta;
        document.getElementById('eta-card').textContent = eta;

        if (d.distance_km) {
          document.getElementById('dist-val').textContent = '~' + parseFloat(d.distance_km).toFixed(1) + ' km restants';
        }

        /* Barre de progression */
        if (d.progress_pct !== undefined) {
          const pct = Math.min(100, Math.max(0, Math.round(d.progress_pct)));
          document.getElementById('prog-fill').style.width = pct + '%';
          document.getElementById('prog-pct').textContent  = pct + '%';
        }
      }

      /* Statut */
      if (d.statut) {
        const pill = document.getElementById('status-pill');
        const s    = d.statut.toLowerCase();
        pill.textContent = d.statut;
        pill.className   = 'status-pill ' + (s === 'en cours' ? 'enroute' : s === 'arrivé' ? 'arrive' : 'attente');
      }
    })
    .catch(() => {});
}

setInterval(fetchPosition, 10000);
fetchPosition();


function selectRaison(el, raison) {
  document.querySelectorAll('.cancel-opt').forEach(o => o.classList.remove('sel'));
  el.classList.add('sel');
  currentRaison = raison;
}

function openCancel() {
  const now      = new Date();
  const start    = new Date(DATE_RES);
  const diffMin  = (now - start) / 60000;

  if (diffMin <= 120) {
    document.getElementById('refund-ok').style.display   = 'flex';
    document.getElementById('refund-warn').style.display = 'none';
  } else {
    document.getElementById('refund-ok').style.display   = 'none';
    document.getElementById('refund-warn').style.display = 'flex';
    document.getElementById('refund-partiel-val').textContent =
      Math.round(MONTANT * 0.85).toLocaleString('fr-SN');
  }

  document.getElementById('cancel-modal').classList.add('open');
}

function closeCancel() {
  document.getElementById('cancel-modal').classList.remove('open');
}


function handleModalBg(e) {
  if (e.target === document.getElementById('cancel-modal')) closeCancel();
}

function confirmCancel() {
  const fd = new FormData();
  fd.append('reservation_id', RES_ID);
  fd.append('raison', currentRaison);

  fetch('cancel_reservation.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      closeCancel();
      if (d.success) {
        showToast(d.montant_rembourse > 0
          ? '✓ Annulé · Remboursement : ' + d.montant_rembourse.toLocaleString('fr-SN') + ' FCFA'
          : '✓ Réservation annulée');
        setTimeout(() => location.href = 'index.php', 2600);
      } else {
        showToast('❌ ' + (d.message || 'Erreur lors de l\'annulation'));
      }
    })
    .catch(() => showToast('❌ Erreur réseau'));
}


function showToast(msg) {
  const t = document.getElementById('toast');
  document.getElementById('toast-msg').textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3200);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>