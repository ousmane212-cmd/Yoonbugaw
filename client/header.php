<?php

require_once "../config/database.php";

$userName = $_SESSION['nom'] ?? 'Client';
$userId   = $_SESSION['id'] ?? null;
if (!$userId) { die("Utilisateur non connecté"); }

$parts     = explode(' ', trim($userName));
$initiales = strtoupper(
    substr($parts[0], 0, 1) .
    (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
);

/* ── Stats client ───────────────────────────────────────────────── */
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

/* ── Véhicules depuis la BDD, groupés par type ──────────────────── */
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
<link rel="stylesheet" href="style.css">

</head>
<body>
<button class="menu-toggle" onclick="toggleSidebar()">
  <i class="bi bi-list"></i>
</button>
 
  <aside class="sidebar">
    <div class="sidebar-header">
     
  <div class="logo-wrap">
    <img src="../assets/images/logo.png" alt="Yoon bu Gaw">
  </div>
    </div>
    <div class="nav-section">
      <div class="nav-label">Menu principal</div>
      <a href="dashboard.php" class="nav-item active"><i class="bi bi-house-fill"></i> Accueil</a>
      <a href="dashboard.php" class="nav-item" onclick="switchService('taxi');return false;"><i class="bi bi-taxi-front-fill"></i> Taxis ville</a>
      <a href="dashboard.php" class="nav-item" onclick="switchService('bus');return false;"><i class="bi bi-bus-front-fill"></i> Bus interurbains</a>
      <a href="dashboard.php" class="nav-item" onclick="switchService('cargo');return false;"><i class="bi bi-truck"></i> Transport cargo</a>
      <a href="dashboard.php" class="nav-item" onclick="switchService('location');return false;"><i class="bi bi-car-front-fill"></i> Location auto</a>
    </div>
    <div class="nav-section">
      <div class="nav-label">Mon compte</div>
      <a href="mes_trajets.php" class="nav-item"><i class="bi bi-clock-history"></i> Mes trajets</a>
      <a href="#" class="nav-item"><i class="bi bi-credit-card-fill"></i> Paiements</a>
      <a href="#" class="nav-item "><i class="bi bi-bell-fill"></i> Notifications </a>
      
    </div>
    <div class="sidebar-footer">
      <div class="user-card">
        <div class="avatar-sm"><?= htmlspecialchars($initiales) ?></div>
        <div>
          <div class="user-name"><?= htmlspecialchars($userName) ?></div>
          <div class="user-role">Client</div>
          <a href="../auth/logout.php" class="nav-item" style="padding:6px 0;margin-top:4px">
            <i class="bi bi-box-arrow-right"></i> Déconnexion
          </a>
        </div>
      </div>
    </div>
  </aside>

  <!-- MOBILE HEADER -->
  <div class="mobile-header">
    <div class="mh-top">
      <div class="mh-user">
        <h4>Bonjour, <?= htmlspecialchars($userName) ?> 👋</h4>
        <p>Où allez-vous aujourd'hui ?</p>
      </div>
      <div class="mh-avatar"><?= htmlspecialchars($initiales) ?></div>
    </div>
   <div class="mh-loc">
  <span class="loc-dot"></span>
  <i class="bi bi-geo-alt-fill" style="font-size:12px"></i>
  <span id="mobile-loc">Localisation...</span>
</div>
  </div>

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <h2>Dashboard Client</h2>
      <p>Bonjour, <?= htmlspecialchars($userName) ?> 👋</p>
    </div>
    <div class="topbar-right">
     
  <div class="loc-pill">
    <span class="loc-dot"></span>
    <i class="bi bi-geo-alt-fill" style="font-size:13px"></i>
    <span id="desktop-loc">Localisation...</span>
  </div>

      <div class="notif-btn"><i class="bi bi-bell-fill"></i></div>
      <div class="avatar-sm" style="width:40px;height:40px;cursor:pointer;font-size:14px"><?= htmlspecialchars($initiales) ?></div>
    </div>
  </header>

 <script src="notifications_widget.js"></script>

  <script>
document.addEventListener("DOMContentLoaded", function () {
  const sidebar = document.querySelector(".sidebar");

  window.toggleSidebar = function () {
    sidebar.classList.toggle("active");
  };
});
</script>
<script>
const mobileLoc = document.getElementById("mobile-loc");
const desktopLoc = document.getElementById("desktop-loc");

function updateLocation(text) {
    if (mobileLoc) mobileLoc.textContent = text;
    if (desktopLoc) desktopLoc.textContent = text;
}

if (!navigator.geolocation) {

    updateLocation("GPS non supporté");

} else {

    updateLocation("Recherche GPS...");

    navigator.geolocation.watchPosition(

        async (position) => {

            const lat = position.coords.latitude;
            const lon = position.coords.longitude;

            console.log("Latitude:", lat);
            console.log("Longitude:", lon);

            try {

                const response = await fetch(
                    `https://api.bigdatacloud.net/data/reverse-geocode-client?latitude=${lat}&longitude=${lon}&localityLanguage=fr`
                );

                const data = await response.json();

                console.log(data);

                const city =
                    data.city ||
                    data.locality ||
                    data.principalSubdivision ||
                    "Ville inconnue";

                const country =
                    data.countryName || "";

                updateLocation(`📍 ${city}, ${country}`);

            } catch (error) {

                console.error(error);
                updateLocation("Erreur localisation");

            }

        },

        (error) => {

            console.error(error);

            switch(error.code) {

                case error.PERMISSION_DENIED:
                    updateLocation("Permission refusée");
                    break;

                case error.POSITION_UNAVAILABLE:
                    updateLocation("Position indisponible");
                    break;

                case error.TIMEOUT:
                    updateLocation("Temps dépassé");
                    break;

                default:
                    updateLocation("Erreur GPS");
            }

        },

        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }
    );
}
</script>
<style>
  .sidebar .logo-wrap img{width:110px;height:auto;object-fit:contain}
</style>