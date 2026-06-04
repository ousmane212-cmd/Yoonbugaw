 
<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$adminName = $_SESSION['nom'] ?? "Admin";

/* ════════════════════════════════════════
   STATS PRINCIPALES
════════════════════════════════════════ */
$totalReservations = $pdo->query("
    SELECT COUNT(*) FROM reservations
")->fetchColumn() ?? 0;

/* Revenus depuis reservations (pas paiements) */
$totalRevenus = $pdo->query("
    SELECT COALESCE(SUM(montant), 0)
    FROM reservations
    WHERE statut NOT IN ('annulé','annule')
")->fetchColumn() ?? 0;

$totalChauffeurs = $pdo->query("
    SELECT COUNT(*) FROM users WHERE role = 'chauffeur'
")->fetchColumn() ?? 0;

$totalVehicules = $pdo->query("
    SELECT COUNT(*) FROM vehicules WHERE statut = 'disponible'
")->fetchColumn() ?? 0;

$notifCount = $pdo->query("
    SELECT COUNT(*) FROM notifications WHERE lu = 0
")->fetchColumn() ?? 0;

$totalPending = $pdo->query("
    SELECT COUNT(*) FROM reservations WHERE statut = 'en attente'
")->fetchColumn() ?? 0;

/* ════════════════════════════════════════
   GRAPHIQUE 1 : Revenus par mois (12 derniers mois)
   Source : table reservations (colonne montant)
════════════════════════════════════════ */
$chartQuery = $pdo->query("
    SELECT
        DATE_FORMAT(date_reservation, '%Y-%m')  AS mois_key,
        DATE_FORMAT(date_reservation, '%b %Y')  AS mois_label,
        COALESCE(SUM(montant), 0)               AS total
    FROM reservations
    WHERE
        statut NOT IN ('annulé','annule')
        AND date_reservation >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mois_key, mois_label
    ORDER BY mois_key ASC
");

$months  = [];
$revenus = [];
while ($row = $chartQuery->fetch(PDO::FETCH_ASSOC)) {
    $months[]  = $row['mois_label'];
    $revenus[] = (float) $row['total'];
}

if (empty($months)) {
    $months  = ['Aucune donnée'];
    $revenus = [0];
}

/* ════════════════════════════════════════
   GRAPHIQUE 2 : Réservations par type_transport
   Valeurs réelles en BDD : taxi, bus, cargo, location
════════════════════════════════════════ */
$typesQuery = $pdo->query("
    SELECT
        LOWER(TRIM(type_transport)) AS type,
        COUNT(*)                    AS nb
    FROM reservations
    GROUP BY LOWER(TRIM(type_transport))
");

$countByType = ['taxi' => 0, 'bus' => 0, 'cargo' => 0, 'location' => 0];
while ($row = $typesQuery->fetch(PDO::FETCH_ASSOC)) {
    $t = $row['type'];
    if (isset($countByType[$t])) {
        $countByType[$t] = (int) $row['nb'];
    }
}

$taxi        = $countByType['taxi'];
$bus         = $countByType['bus'];
$marchandise = $countByType['cargo'];
$voyageurs   = $countByType['location'];

/* Donut dynamique : exclure les types à 0 */
$donutLabels = [];
$donutData   = [];
$donutColors = [];
$typeConfig  = [
    'taxi'     => ['label' => 'Taxi',     'color' => '#f59e0b'],
    'location' => ['label' => 'Location', 'color' => '#16a34a'],
    'bus'      => ['label' => 'Bus',      'color' => '#00008b'],
    'cargo'    => ['label' => 'Cargo',    'color' => '#374151'],
];
foreach ($typeConfig as $key => $cfg) {
    if ($countByType[$key] > 0) {
        $donutLabels[] = $cfg['label'];
        $donutData[]   = $countByType[$key];
        $donutColors[] = $cfg['color'];
    }
}
if (empty($donutData)) {
    $donutLabels = ['Aucune donnée'];
    $donutData   = [1];
    $donutColors = ['#e2e8f0'];
}

/* ════════════════════════════════════════
   ACTIVITÉS RÉCENTES (vraies données)
════════════════════════════════════════ */
$activites = $pdo->query("
    SELECT
        id,
        user_name,
        type_transport,
        montant,
        statut,
        date_reservation
    FROM reservations
    ORDER BY id DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

/* ════════════════════════════════════════
   RÉSERVATIONS RÉCENTES
════════════════════════════════════════ */
$reservations = $pdo->query("
    SELECT * FROM reservations ORDER BY id DESC LIMIT 5
");

/* ════════════════════════════════════════
   TOP CHAUFFEURS (avec nb de courses)
════════════════════════════════════════ */
$topDrivers = $pdo->query("
    SELECT
        u.id,
        u.nom,
        u.email,
        COUNT(r.id) AS nb_courses
    FROM users u
    LEFT JOIN reservations r ON r.chauffeur = u.nom
    WHERE u.role = 'chauffeur'
    GROUP BY u.id, u.nom, u.email
    ORDER BY nb_courses DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

/* ════════════════════════════════════════
   VARIATION MOIS PRÉCÉDENT (tendances)
════════════════════════════════════════ */
function getPct($pdo, $col, $table, $where = '') {

    $total = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();

    $value = $pdo->query("
        SELECT COALESCE($col, 0)
        FROM $table
        WHERE 1=1 $where
    ")->fetchColumn();

    if ($total == 0) {
        return 0; // 👈 évite la division par zéro
    }

    return round(($value / $total) * 100, 2);
}

$trendRes = getPct($pdo, 'COUNT(*)', 'reservations');
$trendRev = getPct($pdo, 'SUM(montant)', 'reservations', "AND statut NOT IN ('annulé','annule')");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — YOON bu Gaw</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;600;700&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include_once "sidebar.php"; ?>

<div class="main-content">

  <?php include_once "header.php"; ?>

  <!-- ── STATS ── -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
      <div class="stat-card text-white" style="background:#07882b">
        <div class="stat-icon"><i class="bi bi-calendar-check-fill"></i></div>
        <div class="stat-info">
          <h6>Réservations</h6>
          <div class="stat-number"><?= number_format($totalReservations, 0, ',', ' ') ?></div>
          <div class="stat-trend"><?= $trendRes ?> ce mois</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card text-white" style="background:#0344a2">
        <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
        <div class="stat-info">
          <h6>Revenus</h6>
          <div class="stat-number"><?= number_format($totalRevenus, 0, ',', ' ') ?> F</div>
          <div class="stat-trend"><?= $trendRev ?> ce mois</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card text-white" style="background:#f78e0c">
        <div class="stat-icon" style="background:rgba(0,0,0,0.1)"><i class="bi bi-person-fill"></i></div>
        <div class="stat-info">
          <h6>Chauffeurs actifs</h6>
          <div class="stat-number"><?= $totalChauffeurs ?></div>
          <div class="stat-trend">Total enregistrés</div>
        </div>
      </div>
    </div>
    <div class="col-6 col-lg-3">
      <div class="stat-card bg-dark text-white">
        <div class="stat-icon"><i class="bi bi-car-front-fill"></i></div>
        <div class="stat-info">
          <h6>Véhicules disponibles</h6>
          <div class="stat-number"><?= $totalVehicules ?></div>
          <div class="stat-trend">Statut : disponible</div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── CHARTS + ACTIVITÉS ── -->
  <div class="row g-3 mb-4">

    <!-- Revenus Chart -->
    <div class="col-lg-5">
      <div class="card-box p-4 h-100">
        <h5>Revenus Mensuels</h5>
        <div class="chart-wrap">
          <canvas id="revenusChart"></canvas>
        </div>
      </div>
    </div>

    <!-- Donut -->
    <div class="col-lg-3">
      <div class="card-box p-4 h-100">
        <h5>Réservations par service</h5>
        <div class="service-doughnut-wrap">
          <div class="service-canvas-box">
            <canvas id="serviceChart"></canvas>
          </div>
          <div class="service-legend">
            <div class="legend-item">
              <span class="legend-dot" style="background:#f59e0b"></span>
              <div><strong>Taxi</strong><small><?= $taxi ?></small></div>
            </div>
            <div class="legend-item">
              <span class="legend-dot" style="background:#16a34a"></span>
              <div><strong>Location</strong><small><?= $voyageurs ?></small></div>
            </div>
            <div class="legend-item">
              <span class="legend-dot" style="background:#00008b"></span>
              <div><strong>Bus</strong><small><?= $bus ?></small></div>
            </div>
            <div class="legend-item">
              <span class="legend-dot" style="background:#374151"></span>
              <div><strong>Cargo</strong><small><?= $marchandise ?></small></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Activités récentes (vraies données) -->
    <div class="col-lg-4">
      <div class="card-box p-4 h-100">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Activités récentes</h5>
          <a href="reservations.php" class="small text-primary">Voir toutes</a>
        </div>
        <?php if (!empty($activites)): ?>
          <?php foreach ($activites as $act):
            $type = strtolower($act['type_transport'] ?? '');
            $icons = ['taxi'=>'bi-taxi-front-fill','bus'=>'bi-bus-front-fill','cargo'=>'bi-truck','location'=>'bi-car-front-fill'];
            $icon  = $icons[$type] ?? 'bi-calendar-check';
            $dateAct = !empty($act['date_reservation'])
              ? date('d/m/Y H:i', strtotime($act['date_reservation'])) : '—';
          ?>
          <div class="activity-item">
            <div class="activity-icon"><i class="bi <?= $icon ?>"></i></div>
            <div class="activity-info">
              <strong>Rés. #<?= $act['id'] ?> — <?= htmlspecialchars($act['user_name']) ?></strong>
              <div class="time">
                <?= ucfirst($type) ?> ·
                <?= number_format($act['montant'] ?? 0, 0, ',', ' ') ?> FCFA ·
                <?= $dateAct ?>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted small">Aucune activité récente.</p>
        <?php endif; ?>
      </div>
    </div>

  </div>

  <!-- ── TABLE + CHAUFFEURS ── -->
  <div class="row g-3 mb-4">

    <!-- Réservations Table -->
    <div class="col-lg-8">
      <div class="card-box p-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">Réservations récentes</h5>
          <a href="reservations.php" class="btn btn-primary btn-sm">Voir tout</a>
        </div>
        <div class="table-responsive">
          <table class="table table-hover mb-0">
            <thead>
              <tr>
                <th>ID</th><th>Client</th><th>Service</th>
                <th>Départ</th><th>Destination</th><th>Statut</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($r = $reservations->fetch(PDO::FETCH_ASSOC)): ?>
              <tr>
                <td>#<?= $r['id'] ?></td>
                <td><?= htmlspecialchars($r['user_name']) ?></td>
                <td><?= ucfirst(htmlspecialchars($r['type_transport'])) ?></td>
                <td><?= htmlspecialchars($r['depart']) ?></td>
                <td><?= htmlspecialchars($r['destination']) ?></td>
                <td>
                  <?php
                    $s = strtolower($r['statut'] ?? '');
                    $cls = match(true) {
                        in_array($s,['confirmé','confirmée','confirmed']) => 'status-confirmed',
                        in_array($s,['en cours'])                        => 'status-ongoing',
                        in_array($s,['annulé','annule','cancelled'])     => 'status-cancelled',
                        in_array($s,['accepté','accepte'])               => 'status-confirmed',
                        in_array($s,['terminé','termine'])               => 'status-ongoing',
                        default                                           => 'status-pending',
                    };
                  ?>
                  <span class="badge-status <?= $cls ?>"><?= ucfirst(htmlspecialchars($r['statut'])) ?></span>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Top Chauffeurs -->
<div class="col-lg-4">

    <div class="card-box p-4">

        <h5>Top Chauffeurs</h5>

        <?php if (!empty($topDrivers)): ?>

            <?php foreach ($topDrivers as $d): ?>

                <?php
                    $nbCourses = (int)($d['nb_courses'] ?? 0);

                    // Initiales
                    $mots = explode(' ', $d['nom']);
                    $initiales = strtoupper(
                        substr($mots[0], 0, 1) .
                        substr($mots[1] ?? '', 0, 1)
                    );

                    // Couleurs
                    $colors = [
                        'var(--green)',
                        'var(--blue)',
                        'var(--orange)',
                        '#8b5cf6'
                    ];

                    $color = $colors[array_rand($colors)];
                ?>

                <div class="driver-item d-flex align-items-center justify-content-between mb-3">

                    <div class="d-flex align-items-center gap-3">

                        <!-- Avatar -->
                        <div class="driver-av"
                             style="
                                width:45px;
                                height:45px;
                                border-radius:50%;
                                background:<?= $color ?>;
                                color:white;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                                font-weight:bold;
                             ">
                            <?= $initiales ?>
                        </div>

                        <!-- Infos -->
                        <div>
                            <strong>
                                <?= htmlspecialchars($d['nom']) ?>
                            </strong>

                            <div class="trips text-muted small">
                                <?= $nbCourses ?> course(s)
                            </div>
                        </div>

                    </div>

                    <!-- Badge -->
                    <span class="rating badge bg-light text-dark">

                        ★
                        <?= $nbCourses > 0
                            ? $nbCourses . ' trajets'
                            : 'Nouveau'
                        ?>

                    </span>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p class="text-muted small">
                Aucun chauffeur enregistré.
            </p>

        <?php endif; ?>

    </div>

</div>

  </div>

  <!-- ── ACTIONS RAPIDES ── -->
  <div class="card card-box p-4 mb-4">
    <h5 class="mb-4">Actions rapides</h5>
    <div class="row">
      <div class="col-md-6">
        <div class="d-flex flex-wrap gap-2 justify-content-between">
          <button class="quick-action" onclick="location.href='clients.php?action=add'">
            <i class="bi bi-person-plus-fill" style="color:#07882b"></i>
            <strong>Ajouter un utilisateur</strong>
          </button>
          <button class="quick-action" onclick="location.href='chauffeurs.php?action=add'">
            <i class="bi bi-person-badge-fill"></i>
            <strong>Ajouter un chauffeur</strong>
          </button>
          <button class="quick-action" onclick="location.href='vehicules.php?action=add'">
            <i class="bi bi-car-front-fill"></i>
            <strong>Ajouter un véhicule</strong>
          </button>
          <button class="quick-action" onclick="location.href='reservations.php?action=add'">
            <i class="bi bi-calendar2-date" style="color:#F5276C"></i>
            <strong>Nouvelle réservation</strong>
          </button>
          <button class="quick-action" onclick="location.href='notification.php'">
            <i class="bi bi-bell-fill" style="color:#f59e0b"></i>
            <strong>Notifications <?= $totalPending > 0 ? "($totalPending)" : '' ?></strong>
          </button>
          <button class="quick-action" onclick="location.href='#'">
            <i class="bi bi-bar-chart-fill"></i>
            <strong>Rapport des revenus</strong>
          </button>
        </div>
      </div>
      <div class="col-md-6 text-center">
        <div class="logo-box">
          <img src="../assets/images/3.jpg" alt="Yoon bu Gaw">
        </div>
      </div>
    </div>
  </div>

  <div class="text-center mb-4">
    <img src="../assets/images/pid.png" alt="Yoon bu Gaw" class="w-100" style="border-radius:16px">
  </div>

</div><!-- /main-content -->

<?php include_once "footer.php"; ?>

<script>
/* ════════════════════════════════
   DONNÉES 100% DYNAMIQUES depuis PHP
════════════════════════════════ */
const months      = <?= json_encode($months,       JSON_UNESCAPED_UNICODE) ?>;
const revenus     = <?= json_encode($revenus) ?>;
const donutLabels = <?= json_encode($donutLabels,  JSON_UNESCAPED_UNICODE) ?>;
const donutData   = <?= json_encode($donutData) ?>;
const donutColors = <?= json_encode($donutColors) ?>;

/* ── Graphique 1 : Revenus mensuels ── */
new Chart(document.getElementById('revenusChart'), {
  type: 'line',
  data: {
    labels: months,
    datasets: [{
      label: 'Revenus (FCFA)',
      data: revenus,
      borderColor: '#16a34a',
      backgroundColor: 'rgba(22,163,74,.08)',
      fill: true,
      tension: 0.4,
      pointBackgroundColor: '#16a34a',
      pointBorderColor: '#fff',
      pointRadius: 5,
      borderWidth: 3
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: true },
      tooltip: {
        callbacks: {
          label: ctx => ' ' + ctx.parsed.y.toLocaleString('fr-SN') + ' FCFA'
        }
      }
    },
    scales: {
      y: {
        beginAtZero: true,
        ticks: { callback: v => v.toLocaleString('fr-SN') + ' F' }
      }
    }
  }
});

/* ── Graphique 2 : Donut services ── */
new Chart(document.getElementById('serviceChart'), {
  type: 'doughnut',
  data: {
    labels: donutLabels,
    datasets: [{
      data: donutData,
      backgroundColor: donutColors,
      borderWidth: 0
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    cutout: '70%',
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ' ' + ctx.label + ' : ' + ctx.parsed + ' réservation(s)'
        }
      }
    }
  }
});

/* ── Géolocalisation ── */
if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(async pos => {
    try {
      const r = await fetch(
        `https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`
      );
      const d = await r.json();
      const ville = d.address.city || d.address.town || d.address.village || 'Votre position';
      const el = document.getElementById('admin-location');
      if (el) el.textContent = ville + ', Sénégal';
    } catch(e) {}
  });
}
</script>
</body>
</html>





