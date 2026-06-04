<?php
session_start();
require_once "../config/database.php";


if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

/* ── Statistiques locations ── */
$stmt = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE type_transport = 'location'");
$totalLocations = $stmt->fetch()['total'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as actives FROM reservations WHERE type_transport = 'location' AND statut = 'en cours'");
$locActives = $stmt->fetch()['actives'] ?? 0;

$stmt = $pdo->query("SELECT SUM(montant) as revenus FROM reservations WHERE type_transport = 'location'");
$revenusLoc = $stmt->fetch()['revenus'] ?? 0;

$stmt = $pdo->query("SELECT COUNT(*) as disponibles FROM vehicules WHERE type IN ('voiture','suv','berline') AND statut = 'disponible'");
$vecsDispos = $stmt->fetch()['disponibles'] ?? 0;

/* ── Réservations location avec filtre ── */
$search  = trim($_GET['search'] ?? '');
$statut  = $_GET['statut'] ?? '';
$sort    = $_GET['sort'] ?? 'id';
$order   = strtoupper($_GET['order'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
$page    = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

$allowed_sorts = ['id','user_name','depart','destination','montant','statut','date_reservation'];
$sort = in_array($sort, $allowed_sorts) ? $sort : 'id';

$where = "WHERE type_transport = 'location'";
$params = [];
if ($search) {
    $where .= " AND (user_name LIKE :s OR depart LIKE :s2 OR destination LIKE :s3 OR matricule LIKE :s4)";
    $params[':s'] = $params[':s2'] = $params[':s3'] = $params[':s4'] = "%$search%";
}
if ($statut) {
    $where .= " AND statut = :statut";
    $params[':statut'] = $statut;
}

$stmtCount = $pdo->prepare("SELECT COUNT(*) as total FROM reservations $where");
$stmtCount->execute($params);
$totalRows = $stmtCount->fetch()['total'] ?? 0;
$totalPages = ceil($totalRows / $perPage);

$stmtRows = $pdo->prepare("SELECT * FROM reservations $where ORDER BY $sort $order LIMIT $perPage OFFSET $offset");
$stmtRows->execute($params);
$reservations = $stmtRows->fetchAll();

/* ── Véhicules disponibles pour location ── */
$stmtVec = $pdo->prepare("
    SELECT * FROM vehicules
    WHERE type IN ('voiture','suv','berline','minibus')
    ORDER BY statut ASC, nom ASC
");
$stmtVec->execute();
$vehicules = $stmtVec->fetchAll();

/* ── Actions POST ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = intval($_POST['id'] ?? 0);

    if ($action === 'update_statut' && $id) {
        $s = $_POST['new_statut'] ?? '';
        $allowed = ['en attente','en cours','confirmé','annulé','terminé'];
        if (in_array($s, $allowed)) {
            $pdo->prepare("UPDATE reservations SET statut = ? WHERE id = ? AND type_transport = 'location'")->execute([$s, $id]);
        }
    }
    if ($action === 'delete' && $id) {
        $pdo->prepare("DELETE FROM reservations WHERE id = ? AND type_transport = 'location'")->execute([$id]);
    }
    header("Location: locations.php?search=".urlencode($search)."&statut=".urlencode($statut)."&sort=$sort&order=$order&page=$page");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Gestion des Locations</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* ══════════════════════════════════════════
   ROOT & BASE
══════════════════════════════════════════ */
:root {
  --bg:        #0b0f1a;
  --bg2:       #111827;
  --bg3:       #1a2236;
  --border:    #1e2d45;
  --text:      #e2e8f0;
  --muted:     #64748b;
  --accent:    #07882b;
  --accent2:   #0ea5e9;
  --purple:    #8b5cf6;
  --amber:     #f59e0b;
  --danger:    #ef4444;
  --success:   #22c55e;
  --warn:      #f59e0b;
  --info:      #38bdf8;
  --font-h:    'Syne', sans-serif;
  --font-b:    'DM Sans', sans-serif;
  --radius:    14px;
  --radius-sm: 8px;
  --shadow:    0 4px 24px rgba(0,0,0,.35);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
html { scroll-behavior: smooth; }

body {
  font-family: var(--font-b);
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
}

/* ══════════════════════════════════════════
   LAYOUT
══════════════════════════════════════════ */
.layout { display: flex; width: 100%; min-height: 100vh; }

/* SIDEBAR */
.sidebar {
  width: 240px;
  min-height: 100vh;
  background: var(--bg2);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: fixed;
  top: 0; left: 0;
  z-index: 100;
  padding: 0 0 20px;
}
.sidebar-logo {
  padding: 24px 20px 20px;
  border-bottom: 1px solid var(--border);
  margin-bottom: 12px;
}
.sidebar-logo .brand {
  font-family: var(--font-h);
  font-size: 18px;
  font-weight: 800;
  color: #fff;
  letter-spacing: -.02em;
}
.sidebar-logo .brand span { color: var(--accent); }
.sidebar-logo .sub {
  font-size: 11px;
  color: var(--muted);
  margin-top: 2px;
}
.nav-section {
  font-size: 10px;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .08em;
  padding: 12px 20px 6px;
}
.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 20px;
  font-size: 13.5px;
  font-weight: 500;
  color: var(--muted);
  text-decoration: none;
  border-radius: 0;
  transition: all .18s;
  border-left: 3px solid transparent;
  cursor: pointer;
}
.nav-item:hover { color: var(--text); background: rgba(255,255,255,.04); }
.nav-item.active {
  color: #fff;
  background: rgba(7,136,43,.12);
  border-left-color: var(--accent);
}
.nav-item i { font-size: 16px; width: 20px; text-align: center; }

/* MAIN */
.main { margin-left: 240px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }

/* TOPBAR */
.topbar {
  height: 62px;
  background: var(--bg2);
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  position: sticky;
  top: 0;
  z-index: 50;
}
.topbar-left h1 {
  font-family: var(--font-h);
  font-size: 16px;
  font-weight: 700;
  color: #fff;
}
.topbar-left p { font-size: 12px; color: var(--muted); }
.topbar-right { display: flex; align-items: center; gap: 12px; }
.topbar-avatar {
  width: 36px; height: 36px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  display: grid; place-items: center;
  font-family: var(--font-h);
  font-size: 13px;
  font-weight: 700;
  color: #fff;
}
.topbar-badge {
  background: rgba(7,136,43,.15);
  color: var(--accent);
  border: 1px solid rgba(7,136,43,.3);
  border-radius: 20px;
  font-size: 11.5px;
  font-weight: 600;
  padding: 3px 10px;
}

/* CONTENT */
.content { padding: 26px 28px 40px; }

/* ══════════════════════════════════════════
   STAT CARDS
══════════════════════════════════════════ */
.stats-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 16px; margin-bottom: 24px; }

.stat-card {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  position: relative;
  overflow: hidden;
  transition: transform .2s;
}
.stat-card:hover { transform: translateY(-2px); }
.stat-card::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 3px;
}
.stat-card.s1::before { background: linear-gradient(90deg, var(--accent), #0ea5e9); }
.stat-card.s2::before { background: linear-gradient(90deg, var(--purple), #ec4899); }
.stat-card.s3::before { background: linear-gradient(90deg, var(--amber), #f97316); }
.stat-card.s4::before { background: linear-gradient(90deg, var(--success), #14b8a6); }

.stat-icon {
  width: 40px; height: 40px;
  border-radius: var(--radius-sm);
  display: grid; place-items: center;
  font-size: 18px;
  margin-bottom: 12px;
}
.stat-icon.g { background: rgba(7,136,43,.15); color: var(--accent); }
.stat-icon.p { background: rgba(139,92,246,.15); color: var(--purple); }
.stat-icon.a { background: rgba(245,158,11,.15); color: var(--amber); }
.stat-icon.b { background: rgba(34,197,94,.15); color: var(--success); }

.stat-val { font-family: var(--font-h); font-size: 26px; font-weight: 800; color: #fff; line-height: 1; }
.stat-lbl { font-size: 12px; color: var(--muted); margin-top: 4px; }
.stat-change { font-size: 11px; color: var(--muted); margin-top: 8px; display: flex; align-items: center; gap: 4px; }

/* ══════════════════════════════════════════
   PANEL
══════════════════════════════════════════ */
.panel {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  overflow: hidden;
  margin-bottom: 24px;
}
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid var(--border);
  flex-wrap: wrap;
  gap: 12px;
}
.panel-header h2 {
  font-family: var(--font-h);
  font-size: 15px;
  font-weight: 700;
  color: #fff;
  display: flex;
  align-items: center;
  gap: 8px;
}
.panel-header h2 i { color: var(--accent); }

/* ══════════════════════════════════════════
   FILTERS
══════════════════════════════════════════ */
.filters {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  align-items: center;
}
.search-box {
  position: relative;
}
.search-box i {
  position: absolute;
  left: 10px; top: 50%;
  transform: translateY(-50%);
  color: var(--muted);
  font-size: 14px;
}
.search-box input {
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  font-family: var(--font-b);
  font-size: 13px;
  padding: 8px 12px 8px 32px;
  width: 220px;
  outline: none;
  transition: border-color .18s;
}
.search-box input:focus { border-color: var(--accent2); }
.search-box input::placeholder { color: var(--muted); }

.f-select {
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  font-family: var(--font-b);
  font-size: 13px;
  padding: 8px 12px;
  outline: none;
  cursor: pointer;
}
.f-select:focus { border-color: var(--accent2); }
.f-select option { background: var(--bg2); }

.btn-sm {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  font-family: var(--font-b);
  font-size: 12.5px;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: all .18s;
  text-decoration: none;
}
.btn-accent  { background: var(--accent);  color: #fff; }
.btn-accent:hover  { background: #06701f; color: #fff; }
.btn-ghost   { background: transparent; border: 1px solid var(--border); color: var(--muted); }
.btn-ghost:hover { color: var(--text); border-color: var(--muted); }
.btn-danger  { background: rgba(239,68,68,.15); color: var(--danger); border: 1px solid rgba(239,68,68,.3); }
.btn-danger:hover { background: var(--danger); color: #fff; }
.btn-info    { background: rgba(56,189,248,.1); color: var(--info); border: 1px solid rgba(56,189,248,.25); }
.btn-info:hover { background: var(--info); color: #000; }

/* ══════════════════════════════════════════
   TABLE
══════════════════════════════════════════ */
.tbl-wrap { overflow-x: auto; }

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
thead th {
  background: var(--bg3);
  padding: 12px 16px;
  text-align: left;
  font-family: var(--font-h);
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: .05em;
  border-bottom: 1px solid var(--border);
  white-space: nowrap;
}
thead th a {
  color: inherit;
  text-decoration: none;
  display: flex;
  align-items: center;
  gap: 4px;
}
thead th a:hover { color: var(--text); }
tbody tr {
  border-bottom: 1px solid rgba(30,45,69,.7);
  transition: background .15s;
}
tbody tr:hover { background: rgba(255,255,255,.025); }
tbody tr:last-child { border-bottom: none; }
td { padding: 13px 16px; vertical-align: middle; }

.td-id    { font-family: var(--font-h); font-size: 12px; color: var(--muted); font-weight: 700; }
.td-user  { font-weight: 600; color: #fff; }
.td-sub   { font-size: 11.5px; color: var(--muted); margin-top: 2px; }
.td-route { font-weight: 500; color: var(--text); }
.td-arrow { color: var(--muted); margin: 0 4px; font-size: 12px; }
.td-price { font-family: var(--font-h); font-weight: 700; color: var(--accent); }
.td-mat   { font-size: 11.5px; color: var(--accent2); font-family: monospace; }
.td-date  { font-size: 12px; color: var(--muted); white-space: nowrap; }
.td-actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }

/* BADGES STATUT */
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 600;
  white-space: nowrap;
}
.badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.badge-attente  { background: rgba(245,158,11,.12); color: var(--amber); }
.badge-encours  { background: rgba(56,189,248,.12); color: var(--info); }
.badge-confirme { background: rgba(34,197,94,.12);  color: var(--success); }
.badge-annule   { background: rgba(239,68,68,.12);  color: var(--danger); }
.badge-termine  { background: rgba(100,116,139,.15); color: var(--muted); }

/* ══════════════════════════════════════════
   PAGINATION
══════════════════════════════════════════ */
.pagination-wrap {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 16px 22px;
  border-top: 1px solid var(--border);
  font-size: 12.5px;
  color: var(--muted);
  flex-wrap: wrap;
  gap: 10px;
}
.pag-btns { display: flex; gap: 6px; }
.pag-btn {
  width: 32px; height: 32px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: transparent;
  color: var(--muted);
  font-size: 13px;
  display: grid; place-items: center;
  cursor: pointer;
  text-decoration: none;
  transition: all .15s;
}
.pag-btn:hover { border-color: var(--accent2); color: var(--accent2); }
.pag-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
.pag-btn.disabled { opacity: .35; pointer-events: none; }

/* ══════════════════════════════════════════
   VÉHICULES GRID
══════════════════════════════════════════ */
.veh-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 14px;
  padding: 20px 22px;
}
.veh-card {
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 16px;
  position: relative;
  transition: all .2s;
}
.veh-card:hover { border-color: rgba(14,165,233,.4); transform: translateY(-2px); }
.veh-card-top { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
.veh-emoji { font-size: 28px; }
.veh-photo {
  width: 52px; height: 52px;
  border-radius: var(--radius-sm);
  object-fit: cover;
  border: 1px solid var(--border);
}
.veh-name { font-family: var(--font-h); font-size: 14px; font-weight: 700; color: #fff; }
.veh-mat  { font-size: 11px; color: var(--accent2); font-family: monospace; margin-top: 2px; }
.veh-detail { font-size: 12px; color: var(--muted); margin-top: 2px; }
.veh-status {
  position: absolute;
  top: 12px; right: 12px;
  font-size: 10px;
  font-weight: 700;
  padding: 3px 8px;
  border-radius: 20px;
}
.veh-status.dispo  { background: rgba(34,197,94,.12); color: var(--success); }
.veh-status.indispo{ background: rgba(239,68,68,.12);  color: var(--danger); }
.veh-status.cours  { background: rgba(56,189,248,.12); color: var(--info); }

.veh-actions { display: flex; gap: 6px; margin-top: 12px; }
.veh-price { font-family: var(--font-h); font-size: 15px; font-weight: 800; color: var(--accent); margin-top: 8px; }

/* ══════════════════════════════════════════
   MODAL
══════════════════════════════════════════ */
.modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0,0,0,.65);
  backdrop-filter: blur(4px);
  z-index: 999;
  align-items: center;
  justify-content: center;
}
.modal-overlay.open { display: flex; }
.modal-box {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  width: 100%;
  max-width: 500px;
  padding: 28px;
  position: relative;
  box-shadow: var(--shadow);
  animation: modalIn .2s ease;
}
@keyframes modalIn { from { opacity:0; transform: translateY(16px); } to { opacity:1; transform: translateY(0); } }
.modal-title { font-family: var(--font-h); font-size: 17px; font-weight: 800; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.modal-title i { color: var(--accent); }
.modal-close {
  position: absolute; top: 16px; right: 16px;
  background: transparent; border: none;
  color: var(--muted); font-size: 20px;
  cursor: pointer; transition: color .15s;
}
.modal-close:hover { color: var(--text); }

.fg { margin-bottom: 16px; }
.fg label { display: block; font-size: 11.5px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .04em; margin-bottom: 6px; }
.fg input, .fg select, .fg textarea {
  width: 100%;
  background: var(--bg3);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text);
  font-family: var(--font-b);
  font-size: 13.5px;
  padding: 10px 12px;
  outline: none;
  transition: border-color .18s;
}
.fg input:focus, .fg select:focus, .fg textarea:focus { border-color: var(--accent2); }
.fg input::placeholder, .fg textarea::placeholder { color: var(--muted); }
.fg select option { background: var(--bg2); }

.row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 4px; }
.btn-full { width: 100%; justify-content: center; }

/* ALERT TOAST */
.toast-wrap { position: fixed; bottom: 24px; right: 24px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; }
.toast {
  background: var(--bg2);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 12px 18px;
  font-size: 13.5px;
  font-weight: 500;
  color: var(--text);
  display: flex;
  align-items: center;
  gap: 10px;
  box-shadow: var(--shadow);
  animation: toastIn .3s ease;
  min-width: 260px;
}
@keyframes toastIn { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
.toast.success { border-left: 3px solid var(--success); }
.toast.error   { border-left: 3px solid var(--danger); }

/* EMPTY STATE */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--muted);
}
.empty-state i { font-size: 48px; margin-bottom: 12px; opacity: .3; display: block; }
.empty-state p { font-size: 14px; }

/* SORT ICON */
.sort-icon { font-size: 10px; }

/* CONFIRM DELETE */
.confirm-box { text-align: center; padding: 8px 0 16px; }
.confirm-box .bi { font-size: 44px; color: var(--danger); margin-bottom: 12px; display: block; }
.confirm-box p { font-size: 14px; color: var(--muted); }
.confirm-box strong { color: var(--text); }

/* SCROLLBAR */
::-webkit-scrollbar { width: 5px; height: 5px; }
::-webkit-scrollbar-track { background: var(--bg); }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 10px; }

/* RESPONSIVE */
@media (max-width: 1100px) {
  .stats-row { grid-template-columns: repeat(2,1fr); }
}
@media (max-width: 768px) {
  .sidebar { display: none; }
  .main { margin-left: 0; }
  .stats-row { grid-template-columns: 1fr 1fr; }
  .content { padding: 16px; }
}
</style>
</head>
<body>
<div class="layout">

<!-- ══════════════ SIDEBAR ══════════════ -->
<aside class="sidebar">
  <div class="sidebar-logo">
    <div class="brand">Yoon bu <span>Gaw</span></div>
    <div class="sub">Espace Administrateur</div>
  </div>

  <div class="nav-section">Principal</div>
  <a href="index.php" class="nav-item"><i class="bi bi-grid-fill"></i> Tableau de bord</a>
  <a href="reservations.php" class="nav-item"><i class="bi bi-ticket-perforated-fill"></i> Réservations</a>
  <a href="clients.php" class="nav-item"><i class="bi bi-people-fill"></i> Clients</a>

  <div class="nav-section">Transport</div>
  <a href="taxis.php" class="nav-item"><i class="bi bi-taxi-front-fill"></i> Taxis</a>
  <a href="bus.php" class="nav-item"><i class="bi bi-bus-front-fill"></i> Bus & Cars</a>
  <a href="locations.php" class="nav-item active"><i class="bi bi-car-front-fill"></i> Locations</a>
  <a href="cargo.php" class="nav-item"><i class="bi bi-truck"></i> Cargo</a>
  <a href="vehicules.php" class="nav-item"><i class="bi bi-ev-front-fill"></i> Véhicules</a>

  <div class="nav-section">Gestion</div>
  <a href="paiements.php" class="nav-item"><i class="bi bi-credit-card-fill"></i> Paiements</a>
  <a href="rapports.php" class="nav-item"><i class="bi bi-bar-chart-fill"></i> Rapports</a>
  <a href="parametres.php" class="nav-item"><i class="bi bi-gear-fill"></i> Paramètres</a>
  <a href="../logout.php" class="nav-item" style="margin-top:auto"><i class="bi bi-box-arrow-left"></i> Déconnexion</a>
</aside>

<!-- ══════════════ MAIN ══════════════ -->
<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <h1><i class="bi bi-car-front-fill" style="color:var(--accent);margin-right:6px"></i>Gestion des Locations</h1>
      <p>Réservations · Véhicules · Suivi en temps réel</p>
    </div>
    <div class="topbar-right">
      <span class="topbar-badge"><i class="bi bi-circle-fill" style="font-size:7px;color:var(--accent)"></i> <?= $vecsDispos ?> véhicules disponibles</span>
      <div class="topbar-avatar">AD</div>
    </div>
  </div>

  <!-- CONTENT -->
  <div class="content">

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-card s1">
        <div class="stat-icon g"><i class="bi bi-car-front-fill"></i></div>
        <div class="stat-val"><?= $totalLocations ?></div>
        <div class="stat-lbl">Total locations</div>
        <div class="stat-change"><i class="bi bi-calendar3"></i> Toutes périodes</div>
      </div>
      <div class="stat-card s2">
        <div class="stat-icon p"><i class="bi bi-activity"></i></div>
        <div class="stat-val"><?= $locActives ?></div>
        <div class="stat-lbl">En cours</div>
        <div class="stat-change"><i class="bi bi-arrow-clockwise"></i> Actuellement actives</div>
      </div>
      <div class="stat-card s3">
        <div class="stat-icon a"><i class="bi bi-currency-exchange"></i></div>
        <div class="stat-val"><?= number_format($revenusLoc, 0, ',', ' ') ?></div>
        <div class="stat-lbl">FCFA de revenus</div>
        <div class="stat-change"><i class="bi bi-cash-stack"></i> Total encaissé</div>
      </div>
      <div class="stat-card s4">
        <div class="stat-icon b"><i class="bi bi-ev-front-fill"></i></div>
        <div class="stat-val"><?= $vecsDispos ?></div>
        <div class="stat-lbl">Véhicules disponibles</div>
        <div class="stat-change"><i class="bi bi-check-circle-fill"></i> Prêts à louer</div>
      </div>
    </div>

    <!-- TABLE DES RÉSERVATIONS -->
    <div class="panel">
      <div class="panel-header">
        <h2><i class="bi bi-table"></i> Réservations de location</h2>
        <div class="filters">
          <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">
            <input type="hidden" name="order" value="<?= $order ?>">
            <div class="search-box">
              <i class="bi bi-search"></i>
              <input type="text" name="search" placeholder="Client, ville, matricule…" value="<?= htmlspecialchars($search) ?>">
            </div>
            <select class="f-select" name="statut">
              <option value="">Tous les statuts</option>
              <option value="en attente"  <?= $statut==='en attente'?'selected':'' ?>>En attente</option>
              <option value="en cours"    <?= $statut==='en cours'?'selected':'' ?>>En cours</option>
              <option value="confirmé"    <?= $statut==='confirmé'?'selected':'' ?>>Confirmé</option>
              <option value="annulé"      <?= $statut==='annulé'?'selected':'' ?>>Annulé</option>
              <option value="terminé"     <?= $statut==='terminé'?'selected':'' ?>>Terminé</option>
            </select>
            <button type="submit" class="btn-sm btn-ghost"><i class="bi bi-funnel-fill"></i> Filtrer</button>
            <?php if($search||$statut): ?>
              <a href="locations.php" class="btn-sm btn-ghost"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
          </form>
          <button class="btn-sm btn-accent" onclick="openModal('modal-add')">
            <i class="bi bi-plus-lg"></i> Nouvelle location
          </button>
        </div>
      </div>

      <div class="tbl-wrap">
        <?php if (count($reservations) > 0): ?>
        <table>
          <thead>
            <tr>
              <th><a href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&sort=id&order=<?= ($sort==='id'&&$order==='ASC')?'DESC':'ASC' ?>">
                # <i class="bi bi-arrow-down-up sort-icon"></i>
              </a></th>
              <th><a href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&sort=user_name&order=<?= ($sort==='user_name'&&$order==='ASC')?'DESC':'ASC' ?>">
                Client <i class="bi bi-arrow-down-up sort-icon"></i>
              </a></th>
              <th>Trajet / Période</th>
              <th>Véhicule</th>
              <th><a href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&sort=montant&order=<?= ($sort==='montant'&&$order==='ASC')?'DESC':'ASC' ?>">
                Montant <i class="bi bi-arrow-down-up sort-icon"></i>
              </a></th>
              <th>Statut</th>
              <th><a href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&sort=date_reservation&order=<?= ($sort==='date_reservation'&&$order==='ASC')?'DESC':'ASC' ?>">
                Date <i class="bi bi-arrow-down-up sort-icon"></i>
              </a></th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($reservations as $r):
              $stat = strtolower($r['statut'] ?? '');
              $bClass = 'badge-attente';
              $bIcon  = 'bi-clock';
              if ($stat === 'en cours')  { $bClass = 'badge-encours';  $bIcon = 'bi-play-circle-fill'; }
              if ($stat === 'confirmé')  { $bClass = 'badge-confirme'; $bIcon = 'bi-check-circle-fill'; }
              if ($stat === 'annulé')    { $bClass = 'badge-annule';   $bIcon = 'bi-x-circle-fill'; }
              if ($stat === 'terminé')   { $bClass = 'badge-termine';  $bIcon = 'bi-flag-fill'; }
              $dateAff = !empty($r['date_reservation']) ? date('d/m/Y', strtotime($r['date_reservation'])) : '—';
            ?>
            <tr>
              <td><span class="td-id">#<?= $r['id'] ?></span></td>
              <td>
                <div class="td-user"><?= htmlspecialchars($r['user_name'] ?? '—') ?></div>
                <?php if(!empty($r['chauffeur'])): ?>
                  <div class="td-sub"><i class="bi bi-person-fill"></i> <?= htmlspecialchars($r['chauffeur']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="td-route">
                  <?= htmlspecialchars($r['depart'] ?? '—') ?>
                  <span class="td-arrow">→</span>
                  <?= htmlspecialchars($r['destination'] ?? '—') ?>
                </div>
                <?php if(!empty($r['heure_depart'])): ?>
                  <div class="td-sub"><i class="bi bi-clock"></i> <?= htmlspecialchars($r['heure_depart']) ?></div>
                <?php endif; ?>
              </td>
              <td>
                <div class="td-mat"><?= htmlspecialchars($r['matricule'] ?? '—') ?></div>
                <?php if(!empty($r['nb_places'])): ?>
                  <div class="td-sub"><?= $r['nb_places'] ?> place(s)</div>
                <?php endif; ?>
              </td>
              <td><div class="td-price"><?= number_format($r['montant'] ?? 0, 0, ',', ' ') ?> <small style="font-size:10px;color:var(--muted)">F</small></div></td>
              <td><span class="badge <?= $bClass ?>"><i class="bi <?= $bIcon ?>" style="font-size:9px;margin-right:2px"></i><?= ucfirst(htmlspecialchars($r['statut'] ?? '—')) ?></span></td>
              <td><span class="td-date"><?= $dateAff ?></span></td>
              <td>
                <div class="td-actions">
                  <button class="btn-sm btn-info" onclick='openEditModal(<?= json_encode($r) ?>)' title="Modifier">
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <button class="btn-sm btn-danger" onclick="openDeleteModal(<?= $r['id'] ?>)" title="Supprimer">
                    <i class="bi bi-trash3-fill"></i>
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php else: ?>
          <div class="empty-state">
            <i class="bi bi-car-front"></i>
            <p>Aucune réservation de location trouvée<?= $search ? " pour «&nbsp;$search&nbsp;»" : '' ?>.</p>
          </div>
        <?php endif; ?>
      </div>

      <!-- PAGINATION -->
      <div class="pagination-wrap">
        <span><?= $totalRows ?> résultat(s) · Page <?= $page ?> / <?= max(1,$totalPages) ?></span>
        <div class="pag-btns">
          <a class="pag-btn<?= $page<=1?' disabled':'' ?>" href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&sort=<?= $sort ?>&order=<?= $order ?>&page=<?= $page-1 ?>">
            <i class="bi bi-chevron-left"></i>
          </a>
          <?php for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
          <a class="pag-btn<?= $p===$page?' active':'' ?>" href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&sort=<?= $sort ?>&order=<?= $order ?>&page=<?= $p ?>"><?= $p ?></a>
          <?php endfor; ?>
          <a class="pag-btn<?= $page>=$totalPages?' disabled':'' ?>" href="?search=<?= urlencode($search) ?>&statut=<?= urlencode($statut) ?>&sort=<?= $sort ?>&order=<?= $order ?>&page=<?= $page+1 ?>">
            <i class="bi bi-chevron-right"></i>
          </a>
        </div>
      </div>
    </div>

    <!-- VÉHICULES DISPONIBLES -->
    <div class="panel">
      <div class="panel-header">
        <h2><i class="bi bi-ev-front-fill"></i> Parc de véhicules — Location</h2>
        <button class="btn-sm btn-accent" onclick="openModal('modal-veh')">
          <i class="bi bi-plus-lg"></i> Ajouter un véhicule
        </button>
      </div>
      <?php if (count($vehicules) > 0): ?>
      <div class="veh-grid">
        <?php foreach ($vehicules as $v):
          $statLow = strtolower($v['statut'] ?? 'disponible');
          $sClass  = 'dispo';
          $sLabel  = 'Disponible';
          if ($statLow === 'indisponible' || $statLow === 'en maintenance') { $sClass = 'indispo'; $sLabel = 'Indisponible'; }
          if ($statLow === 'en location' || $statLow === 'en cours')        { $sClass = 'cours';   $sLabel = 'En location'; }
          $typeLabel = ucfirst($v['type'] ?? '—');
        ?>
        <div class="veh-card">
          <span class="veh-status <?= $sClass ?>"><?= $sLabel ?></span>
          <div class="veh-card-top">
            <?php if (!empty($v['photo'])): ?>
              <img class="veh-photo" src="../<?= htmlspecialchars($v['photo']) ?>" alt="<?= htmlspecialchars($v['nom']) ?>" onerror="this.style.display='none'">
            <?php else: ?>
              <div class="veh-emoji">🚗</div>
            <?php endif; ?>
            <div>
              <div class="veh-name"><?= htmlspecialchars($v['nom']) ?></div>
              <div class="veh-mat"><?= htmlspecialchars($v['matricule']) ?></div>
              <div class="veh-detail"><?= $typeLabel ?> · <?= htmlspecialchars($v['couleur'] ?? '') ?></div>
            </div>
          </div>
          <?php if (!empty($v['chauffeur'])): ?>
            <div class="veh-detail"><i class="bi bi-person-fill" style="color:var(--muted)"></i> <?= htmlspecialchars($v['chauffeur']) ?></div>
          <?php endif; ?>
          <div class="veh-actions">
            <button class="btn-sm btn-ghost btn-full" onclick="toggleVehStatus(<?= $v['id'] ?>, '<?= $sClass === 'dispo' ? 'indisponible' : 'disponible' ?>')">
              <?= $sClass === 'dispo' ? '<i class="bi bi-lock-fill"></i> Bloquer' : '<i class="bi bi-unlock-fill"></i> Libérer' ?>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-ev-front"></i>
          <p>Aucun véhicule de location enregistré.</p>
        </div>
      <?php endif; ?>
    </div>

  </div><!-- /content -->
</div><!-- /main -->
</div><!-- /layout -->

<!-- ══════════════ MODALS ══════════════ -->

<!-- MODAL MODIFIER STATUT -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modal-edit')"><i class="bi bi-x-lg"></i></button>
    <div class="modal-title"><i class="bi bi-pencil-fill"></i> Modifier la réservation</div>
    <form method="POST">
      <input type="hidden" name="action" value="update_statut">
      <input type="hidden" name="id" id="edit-id">
      <div class="fg">
        <label>Client</label>
        <input type="text" id="edit-client" readonly style="opacity:.6">
      </div>
      <div class="row2">
        <div class="fg">
          <label>Départ</label>
          <input type="text" id="edit-depart" readonly style="opacity:.6">
        </div>
        <div class="fg">
          <label>Destination</label>
          <input type="text" id="edit-dest" readonly style="opacity:.6">
        </div>
      </div>
      <div class="row2">
        <div class="fg">
          <label>Montant (FCFA)</label>
          <input type="text" id="edit-montant" readonly style="opacity:.6">
        </div>
        <div class="fg">
          <label>Nouveau statut</label>
          <select name="new_statut" id="edit-statut">
            <option value="en attente">En attente</option>
            <option value="en cours">En cours</option>
            <option value="confirmé">Confirmé</option>
            <option value="annulé">Annulé</option>
            <option value="terminé">Terminé</option>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-sm btn-ghost" onclick="closeModal('modal-edit')">Annuler</button>
        <button type="submit" class="btn-sm btn-accent"><i class="bi bi-check-lg"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL SUPPRIMER -->
<div class="modal-overlay" id="modal-delete">
  <div class="modal-box" style="max-width:380px">
    <button class="modal-close" onclick="closeModal('modal-delete')"><i class="bi bi-x-lg"></i></button>
    <div class="confirm-box">
      <i class="bi bi-exclamation-triangle-fill"></i>
      <div class="modal-title" style="justify-content:center">Supprimer la réservation ?</div>
      <p>Cette action est <strong>irréversible</strong>. La réservation sera définitivement supprimée.</p>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delete-id">
      <div class="modal-actions">
        <button type="button" class="btn-sm btn-ghost" onclick="closeModal('modal-delete')">Annuler</button>
        <button type="submit" class="btn-sm btn-danger"><i class="bi bi-trash3-fill"></i> Supprimer</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL AJOUTER RÉSERVATION -->
<div class="modal-overlay" id="modal-add">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modal-add')"><i class="bi bi-x-lg"></i></button>
    <div class="modal-title"><i class="bi bi-plus-circle-fill"></i> Nouvelle réservation location</div>
    <form method="POST" action="save_reservation.php">
      <input type="hidden" name="type_transport" value="location">
      <div class="row2">
        <div class="fg">
          <label>Nom du client</label>
          <input type="text" name="user_name" placeholder="Prénom Nom" required>
        </div>
        <div class="fg">
          <label>Matricule véhicule</label>
          <input type="text" name="matricule" placeholder="DK-XXXX-YY">
        </div>
      </div>
      <div class="row2">
        <div class="fg">
          <label>Lieu de prise en charge</label>
          <input type="text" name="depart" placeholder="Ex: Dakar – Plateau" required>
        </div>
        <div class="fg">
          <label>Destination</label>
          <input type="text" name="destination" placeholder="Ex: Mbour">
        </div>
      </div>
      <div class="row2">
        <div class="fg">
          <label>Date de début</label>
          <input type="date" name="date_debut" required>
        </div>
        <div class="fg">
          <label>Date de fin</label>
          <input type="date" name="date_fin">
        </div>
      </div>
      <div class="row2">
        <div class="fg">
          <label>Montant (FCFA)</label>
          <input type="number" name="montant" placeholder="Ex: 75000" min="0" required>
        </div>
        <div class="fg">
          <label>Mode de paiement</label>
          <select name="mode_paiement">
            <option value="wave">Wave</option>
            <option value="om">Orange Money</option>
            <option value="card">Carte bancaire</option>
            <option value="espèces">Espèces</option>
          </select>
        </div>
      </div>
      <div class="fg">
        <label>Statut initial</label>
        <select name="statut">
          <option value="en attente">En attente</option>
          <option value="confirmé">Confirmé</option>
          <option value="en cours">En cours</option>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-sm btn-ghost" onclick="closeModal('modal-add')">Annuler</button>
        <button type="submit" class="btn-sm btn-accent"><i class="bi bi-check-lg"></i> Créer la réservation</button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL AJOUTER VÉHICULE -->
<div class="modal-overlay" id="modal-veh">
  <div class="modal-box">
    <button class="modal-close" onclick="closeModal('modal-veh')"><i class="bi bi-x-lg"></i></button>
    <div class="modal-title"><i class="bi bi-plus-circle-fill"></i> Ajouter un véhicule</div>
    <form method="POST" action="save_vehicule.php" enctype="multipart/form-data">
      <input type="hidden" name="type" value="voiture">
      <div class="row2">
        <div class="fg">
          <label>Nom du véhicule</label>
          <input type="text" name="nom" placeholder="Ex: Toyota Yaris" required>
        </div>
        <div class="fg">
          <label>Matricule</label>
          <input type="text" name="matricule" placeholder="DK-XXXX-YY" required>
        </div>
      </div>
      <div class="row2">
        <div class="fg">
          <label>Type</label>
          <select name="type">
            <option value="voiture">Voiture / Citadine</option>
            <option value="suv">SUV</option>
            <option value="berline">Berline Premium</option>
            <option value="minibus">Minibus</option>
          </select>
        </div>
        <div class="fg">
          <label>Couleur</label>
          <input type="text" name="couleur" placeholder="Ex: Blanche">
        </div>
      </div>
      <div class="fg">
        <label>Nom du chauffeur (optionnel)</label>
        <input type="text" name="chauffeur" placeholder="Prénom Nom">
      </div>
      <div class="fg">
        <label>Photo du véhicule</label>
        <input type="file" name="photo" accept="image/*">
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-sm btn-ghost" onclick="closeModal('modal-veh')">Annuler</button>
        <button type="submit" class="btn-sm btn-accent"><i class="bi bi-check-lg"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>

<!-- TOAST -->
<div class="toast-wrap" id="toast-wrap"></div>

<script>
/* ── MODALS ── */
function openModal(id) {
  document.getElementById(id).classList.add('open');
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
}

/* Fermer au clic hors de la box */
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) overlay.classList.remove('open');
  });
});

/* ── MODAL EDIT ── */
function openEditModal(data) {
  document.getElementById('edit-id').value      = data.id;
  document.getElementById('edit-client').value  = data.user_name || '';
  document.getElementById('edit-depart').value  = data.depart || '';
  document.getElementById('edit-dest').value    = data.destination || '';
  document.getElementById('edit-montant').value = Number(data.montant || 0).toLocaleString('fr-SN') + ' FCFA';
  const sel = document.getElementById('edit-statut');
  sel.value = data.statut || 'en attente';
  openModal('modal-edit');
}

/* ── MODAL DELETE ── */
function openDeleteModal(id) {
  document.getElementById('delete-id').value = id;
  openModal('modal-delete');
}

/* ── TOGGLE STATUT VÉHICULE ── */
function toggleVehStatus(id, newStatus) {
  fetch('toggle_vehicule.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ id, statut: newStatus })
  })
  .then(r => r.json())
  .then(d => {
    if (d.success) {
      showToast('Statut mis à jour avec succès', 'success');
      setTimeout(() => location.reload(), 1000);
    } else {
      showToast('Erreur : ' + (d.message || 'Impossible de mettre à jour'), 'error');
    }
  })
  .catch(() => {
    /* Mode démo sans backend */
    showToast('Statut modifié (mode démo)', 'success');
  });
}

/* ── TOAST ── */
function showToast(msg, type = 'success') {
  const wrap = document.getElementById('toast-wrap');
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'}" style="color:var(--${type === 'success' ? 'success' : 'danger'})"></i> ${msg}`;
  wrap.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

/* ── AUTO-TOAST sur action POST ── */
<?php if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['msg'])): ?>
  showToast('<?= addslashes($_GET['msg']) ?>', '<?= $_GET['type'] ?? 'success' ?>');
<?php endif; ?>

/* ── KEYBOARD ESC ── */
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
  }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>