<?php
session_start();
require_once "../config/database.php";


if (empty($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}


function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function money($n){
    return number_format((float)$n, 0, ',', ' ');
}


$search = trim($_GET['q'] ?? '');
$statut = trim($_GET['statut'] ?? '');
$type   = trim($_GET['type'] ?? '');

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(user_name LIKE :q 
                OR depart LIKE :q 
                OR destination LIKE :q
                OR chauffeur LIKE :q)";
    $params[':q'] = "%$search%";
}

if ($statut !== '') {
    $where[] = "statut = :statut";
    $params[':statut'] = $statut;
}

if ($type !== '') {
    $where[] = "type_transport = :type";
    $params[':type'] = $type;
}

$whereSQL = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';


$sql = "
SELECT 
    id,
    user_name,
    type_transport,
    service,
    depart,
    destination,
    chauffeur,
    matricule,
    montant,
    statut,
    mode_paiement,
    heure_depart,
    date_reservation
FROM reservations
$whereSQL
ORDER BY id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();


$stats = $pdo->query("
SELECT
    COUNT(*) total,
    SUM(montant) depenses,
    SUM(CASE WHEN statut='confirmé' THEN 1 ELSE 0 END) confirmes,
    SUM(CASE WHEN statut='en attente' THEN 1 ELSE 0 END) attente,
    SUM(CASE WHEN statut='annulé' OR statut='annule' THEN 1 ELSE 0 END) annules
FROM reservations
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Réservations · Yoon bu Gaw</title>

<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>

:root {
  --green: #07882b;
  --green-lt: #e6f4ea;

  --blue: #0344a2;
  --blue-lt: #e8f0fe;

  --amber: #f78e0c;
  --amber-lt: #fff3e0;

  --red: #dc2626;
  --red-lt: #fee2e2;

  --gray: #64748b;
  --border: #e2e8f0;

  --bg: #f8fafc;
  --white: #fff;

  --radius: 14px;
}

* {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: #1e293b;
}


.wrapper {
  padding: 28px;
  min-height: 100vh;
}


.topbar {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 24px;
  gap: 16px;
}

.topbar h1 {
  font-family: 'Syne', sans-serif;
  font-size: 24px;
  font-weight: 800;
  margin-bottom: 0;
}

.topbar p {
  font-size: 13px;
  color: var(--gray);
  margin-top: 2px;
  margin-bottom: 0;
}

.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 24px;
}

.card-stat {
  background: #fff;
  border-radius: var(--radius);
  padding: 16px 18px;
  border: 1px solid var(--border);
  display: flex;
  align-items: center;
  gap: 14px;
}

.stat-ic {
  width: 44px;
  height: 44px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  flex-shrink: 0;
}

.stat-ic.g {
  background: var(--green-lt);
  color: var(--green);
}

.stat-ic.b {
  background: var(--blue-lt);
  color: var(--blue);
}

.stat-ic.a {
  background: var(--amber-lt);
  color: var(--amber);
}

.stat-ic.r {
  background: var(--red-lt);
  color: var(--red);
}

.card-stat h3 {
  font-size: 12px;
  color: var(--gray);
  font-weight: 500;
  margin-bottom: 2px;
}

.card-stat .value {
  font-family: 'Syne', sans-serif;
  font-size: 22px;
  font-weight: 700;
  line-height: 1;
}


.filters {
  background: #fff;
  padding: 18px;
  border-radius: var(--radius);
  margin-bottom: 20px;
  border: 1px solid var(--border);
}

.form-control,
.form-select {
  padding: 10px 14px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-size: 14px;
  background: #white;
  outline: none;
  transition: .15s;
}

.form-control:focus,
.form-select:focus {
  border-color: var(--green);
  box-shadow: 0 0 0 3px rgba(7, 136, 43, .06);
}

.btn-filter {
  padding: 10px 20px;
  background: #1e293b;
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 600;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  transition: .15s;
}

.btn-filter:hover {
  background: #0f172a;
  color: #fff;
}

.table-box {
  background: #fff;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  overflow: hidden;
}

.table-head-row {
  padding: 14px 18px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.table-head-row h2 {
  font-family: 'Syne', sans-serif;
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 0;
}

table {
  width: 100%;
  border-collapse: collapse;
}

thead th {
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .07em;
  color: var(--gray);
  padding: 12px 14px;
  text-align: left;
  background: var(--bg);
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}

tbody tr {
  border-bottom: 1px solid var(--border);
  transition: .12s;
}

tbody tr:hover {
  background: #f8fafc;
}

tbody tr:last-child {
  border-bottom: none;
}

td {
  padding: 12px 14px;
  font-size: 13px;
  vertical-align: middle;
  white-space: nowrap;
}


.badge-pill {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}

.badge-wait {
  background: var(--amber-lt);
  color: #b45309;
}

.badge-ok {
  background: var(--green-lt);
  color: var(--green);
}

.badge-run {
  background: var(--blue-lt);
  color: var(--blue);
}

.badge-cancel {
  background: var(--red-lt);
  color: var(--red);
}

.badge-transport {
  background: #f1f5f9;
  color: #475569;
  border: 1px solid var(--border);
}


@media (max-width: 991px) {
  .wrapper {
    padding: 75px 16px 20px;
  }

  .stats {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .topbar {
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .topbar .btn {
    width: 100%;
    text-align: center;
  }

  .filters {
    padding: 14px;
  }

  .btn-filter {
    width: 100%;
  }
}

@media (max-width: 520px) {
  .stats {
    grid-template-columns: 1fr;
  }
}
</style>
</head>

<body>

<?php include_once "sidebar.php"; ?>

<div class="main-content">
    <div class="wrapper">

        <div class="topbar">
            <div>
                <h1><i class="bi bi-calendar-check text-success"></i> Gestion des Réservations</h1>
                <p>Suivi et validation en temps réel des trajets</p>
            </div>
            <a href="dashboard.php" class="btn btn-dark btn-filter text-decoration-none">
                <i class="bi bi-arrow-left"></i> Dashboard
            </a>
        </div>

        <div class="stats">
            <div class="card-stat">
                <div class="stat-ic b"><i class="bi bi-list-ul"></i></div>
                <div>
                    <h3>Total demandes</h3>
                    <div class="value"><?= (int)($stats['total'] ?? 0) ?></div>
                </div>
            </div>

            <div class="card-stat">
                <div class="stat-ic g"><i class="bi bi-wallet2"></i></div>
                <div>
                    <h3>Volume d'affaires</h3>
                    <div class="value text-success"><?= money($stats['depenses'] ?? 0) ?> F</div>
                </div>
            </div>

            <div class="card-stat">
                <div class="stat-ic g"><i class="bi bi-check-circle-fill"></i></div>
                <div>
                    <h3>Confirmées</h3>
                    <div class="value text-success"><?= (int)($stats['confirmes'] ?? 0) ?></div>
                </div>
            </div>

            <div class="card-stat">
                <div class="stat-ic a"><i class="bi bi-hourglass-split"></i></div>
                <div>
                    <h3>En attente</h3>
                    <div class="value text-warning"><?= (int)($stats['attente'] ?? 0) ?></div>
                </div>
            </div>
        </div>

        <form method="GET" class="filters row g-3">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control" value="<?= e($search) ?>" placeholder="Rechercher un client, trajet ou chauffeur...">
            </div>

            <div class="col-md-3">
                <select name="type" class="form-select">
                    <option value="">Tous les types de transport</option>
                    <option value="taxi" <?= $type=='taxi'?'selected':'' ?>>🚕 Taxi</option>
                    <option value="bus" <?= $type=='bus'?'selected':'' ?>>🚌 Bus</option>
                    <option value="cargo" <?= $type=='cargo'?'selected':'' ?>>🚛 Direct Cargo</option>
                </select>
            </div>

            <div class="col-md-3">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="en attente" <?= $statut=='en attente'?'selected':'' ?>>En attente</option>
                    <option value="confirmé" <?= $statut=='confirmé'?'selected':'' ?>>Confirmé</option>
                    <option value="en cours" <?= $statut=='en cours'?'selected':'' ?>>En cours</option>
                    <option value="annulé" <?= $statut=='annulé'?'selected':'' ?>>Annulé</option>
                </select>
            </div>

            <div class="col-md-2">
                <button class="btn btn-filter w-100" type="submit">
                    <i class="bi bi-search"></i> Filtrer
                </button>
            </div>
        </form>

        <div class="table-box">
            <div class="table-head-row">
                <h2>Liste des réservations</h2>
                <span class="badge bg-light text-dark fw-medium" style="font-size:13px; border:1px solid var(--border)"><?= count($reservations) ?> résultat<?= count($reservations)>1?'s':'' ?></span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead>
                        <tr>
                            <th>N°</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Itinéraire / Trajet</th>
                            <th>Chauffeur</th>
                            <th>Heure Départ</th>
                            <th>Montant</th>
                            <th>Paiement</th>
                            <th>Statut</th>
                            <th>Date Demande</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(count($reservations) === 0): ?>
                        <tr>
                            <td colspan="10" class="text-center p-5 text-muted">
                                <i class="bi bi-calendar-x d-block fs-2 mb-2"></i> Aucune réservation enregistrée pour ces critères.
                            </td>
                        </tr>
                        <?php endif; ?>
<?php $numero = 1; ?>
                        <?php foreach($reservations as $r):
                            $stat = strtolower($r['statut'] ?? '');
                            $badge = match($stat) {
                                'en attente' => 'badge-wait',
                                'confirmé'   => 'badge-ok',
                                'en cours'   => 'badge-run',
                                'annulé', 'annule' => 'badge-cancel',
                                default => 'badge-ok'
                            };

                            $emoji = match(strtolower($r['type_transport'] ?? '')) { 
                                'bus'=>'🚌 Bus', 
                                'cargo'=>'🚛 Cargo', 
                                default=>'🚕 Taxi' 
                            };

                            $date = !empty($r['date_reservation']) ? date('d/m/Y à H:i', strtotime($r['date_reservation'])) : '—';
                        ?>
                        <tr>
                            <td><?= $numero++ ?></td>
                            <td class="fw-semibold"><?= e($r['user_name']) ?></td>
                            <td><span class="badge-pill badge-transport"><?= $emoji ?></span></td>
                            <td>
                                <span class="fw-medium text-dark"><?= e($r['depart']) ?></span> 
                                <i class="bi bi-arrow-right-short text-muted mx-1"></i> 
                                <span class="fw-medium text-dark"><?= e($r['destination']) ?></span>
                            </td>
                            <td><i class="bi bi-person text-muted me-1"></i><?= e($r['chauffeur'] ?? '—') ?></td>
                            <td><i class="bi bi-clock text-muted me-1"></i><?= e($r['heure_depart'] ?? '—') ?></td>
                            <td class="fw-bold text-dark"><?= money($r['montant'] ?? 0) ?> F</td>
                            <td><span class="text-uppercase small fw-medium text-secondary"><?= e($r['mode_paiement'] ?? '—') ?></span></td>
                            <td>
                                <span class="badge-pill <?= $badge ?>">
                                    <?= ucfirst($r['statut'] ?? '') ?>
                                </span>
                            </td>
                            <td class="text-muted text-sm"><?= $date ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
<?php
include_once "footer.php";
?>
</body>
</html>