<?php
session_start();
require_once "../config/database.php";

$userName = $_SESSION['nom'] ?? null;
$userId   = $_SESSION['id']  ?? null;
if (!$userId) { header('Location: ../auth/login.php'); exit; }

$parts    = explode(' ', trim($userName));
$initiales = strtoupper(
    substr($parts[0], 0, 1) .
    (isset($parts[1]) ? substr($parts[1], 0, 1) : '')
);

/* ── Filtres GET ────────────────────────────────────────────────── */
$filterType   = $_GET['type']   ?? '';
$filterStatut = $_GET['statut'] ?? '';
$filterSearch = trim($_GET['q'] ?? '');
$page         = max(1, (int)($_GET['page'] ?? 1));
$perPage      = 10;
$offset       = ($page - 1) * $perPage;

/* ── Construction requête dynamique ────────────────────────────── */
$where  = ['user_name = :uname'];
$params = [':uname' => $userName];

if ($filterType && in_array($filterType, ['taxi','bus','cargo'])) {
    $where[]           = 'type_transport = :type';
    $params[':type']   = $filterType;
}
if ($filterStatut) {
    $where[]             = 'statut = :statut';
    $params[':statut']   = $filterStatut;
}
if ($filterSearch) {
    $where[]             = '(destination LIKE :q OR depart LIKE :q OR reference LIKE :q OR chauffeur LIKE :q)';
    $params[':q']        = '%' . $filterSearch . '%';
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

/* ── Total pour pagination ──────────────────────────────────────── */
$stmtTotal = $pdo->prepare("SELECT COUNT(*) as n FROM reservations $whereSQL");
$stmtTotal->execute($params);
$totalRows = (int)$stmtTotal->fetch()['n'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));

/* ── Données page courante ──────────────────────────────────────── */
$params[':limit']  = $perPage;
$params[':offset'] = $offset;
$stmt = $pdo->prepare("
    SELECT id, reference, type_transport, service, depart, destination,
           montant, statut, chauffeur, matricule, heure_depart, mode_paiement,
           note_client, date_reservation
    FROM reservations
    $whereSQL
    ORDER BY id DESC
    LIMIT :limit OFFSET :offset
");
// PDO nécessite bindValue pour LIMIT/OFFSET
foreach ($params as $k => $v) {
    if ($k === ':limit' || $k === ':offset') {
        $stmt->bindValue($k, $v, PDO::PARAM_INT);
    } else {
        $stmt->bindValue($k, $v);
    }
}
$stmt->execute();
$reservations = $stmt->fetchAll();

/* ── Stats rapides ──────────────────────────────────────────────── */
$stmtStats = $pdo->prepare("
    SELECT
        COUNT(*) as total,
        SUM(montant) as depenses,
        SUM(CASE WHEN statut='confirmé' OR statut='termine' THEN 1 ELSE 0 END) as termines,
        SUM(CASE WHEN statut='annulé' OR statut='annule' THEN 1 ELSE 0 END) as annules,
        SUM(CASE WHEN statut='en attente' THEN 1 ELSE 0 END) as attente
    FROM reservations WHERE user_name = :uname
");
$stmtStats->execute([':uname' => $userName]);
$stats = $stmtStats->fetch();

function fmt(float $n): string {
    return number_format($n, 0, ',', ' ');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes Trajets — Yoon bu Gaw</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="trajets.css">
</head>
<body>
<div class="layout">

<?php
include_once "haeder.php";
?>

 

  <!-- MAIN -->
  <main class="main-content">
    <div class="topbar-left">
      <h2>Mes Trajets</h2>
      <p>Historique complet de vos réservations</p>
    </div>
   

    <!-- STATS MINI -->
    <div class="mini-stats">
      <div class="mstat">
        <div class="mstat-icon g"><i class="bi bi-ticket-perforated-fill"></i></div>
        <div>
          <div class="mstat-val"><?= $stats['total'] ?></div>
          <div class="mstat-lbl">Réservations totales</div>
        </div>
      </div>
      <div class="mstat">
        <div class="mstat-icon b"><i class="bi bi-cash-stack"></i></div>
        <div>
          <div class="mstat-val"><?= fmt((float)($stats['depenses'] ?? 0)) ?></div>
          <div class="mstat-lbl">FCFA dépensés</div>
        </div>
      </div>
      <div class="mstat">
        <div class="mstat-icon g"><i class="bi bi-check-circle-fill"></i></div>
        <div>
          <div class="mstat-val"><?= $stats['termines'] ?></div>
          <div class="mstat-lbl">Trajets terminés</div>
        </div>
      </div>
      <div class="mstat">
        <div class="mstat-icon a"><i class="bi bi-hourglass-split"></i></div>
        <div>
          <div class="mstat-val"><?= $stats['attente'] ?></div>
          <div class="mstat-lbl">En attente</div>
        </div>
      </div>
    </div>

    <!-- FILTRES -->
    <form method="GET" action="mes_trajets.php" class="filters-bar">
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" name="q" placeholder="Rechercher destination, référence, chauffeur…" value="<?= htmlspecialchars($filterSearch) ?>">
      </div>
      <select name="type" class="filter-select">
        <option value="">Tous les types</option>
        <option value="taxi"  <?= $filterType==='taxi'  ?'selected':'' ?>>🚕 Taxi</option>
        <option value="bus"   <?= $filterType==='bus'   ?'selected':'' ?>>🚌 Bus</option>
        <option value="cargo" <?= $filterType==='cargo' ?'selected':'' ?>>🚛 Cargo</option>
      </select>
      <select name="statut" class="filter-select">
        <option value="">Tous les statuts</option>
        <option value="en attente" <?= $filterStatut==='en attente' ?'selected':'' ?>>En attente</option>
        <option value="confirmé"   <?= $filterStatut==='confirmé'   ?'selected':'' ?>>Confirmé</option>
        <option value="en cours"   <?= $filterStatut==='en cours'   ?'selected':'' ?>>En cours</option>
        <option value="annulé"     <?= $filterStatut==='annulé'     ?'selected':'' ?>>Annulé</option>
      </select>
      <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
      <?php if ($filterType || $filterStatut || $filterSearch): ?>
        <a href="mes_trajets.php" class="btn-reset">Réinitialiser</a>
      <?php endif ?>
    </form>

    <!-- TABLE -->
    <div class="table-wrap">
      <div class="table-head">
        <h3>Réservations</h3>
        <span><?= $totalRows ?> résultat<?= $totalRows > 1 ? 's' : '' ?></span>
      </div>

      <?php if (count($reservations) === 0): ?>
        <div class="empty-state">
          <div class="ei">🗺️</div>
          <h4>Aucun trajet trouvé</h4>
          <p>Modifiez vos filtres ou effectuez votre première réservation depuis le dashboard.</p>
        </div>
      <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Réf.</th>
            <th>Type</th>
            <th>Trajet</th>
            <th>Chauffeur</th>
            <th>Départ</th>
            <th>Statut</th>
            <th>Montant</th>
            <th>Date</th>
            <th>Action</th>
            <th>Suivre</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reservations as $r):
            $tl     = strtolower($r['type_transport']);
            $icon   = 'bi-car-front-fill'; $cls = 'taxi';
            if ($tl === 'bus')   { $icon = 'bi-bus-front-fill'; $cls = 'bus'; }
            if ($tl === 'cargo') { $icon = 'bi-truck';           $cls = 'cargo'; }
            $sl     = strtolower($r['statut'] ?? '');
            $bCls   = 'bp-s';
            if ($sl === 'en attente') $bCls = 'bp-w';
            elseif (in_array($sl, ['annulé','annule'])) $bCls = 'bp-r';
            elseif ($sl === 'en cours') $bCls = 'bp-i';
            $dateAff = !empty($r['date_reservation'])
              ? date('d/m/Y H:i', strtotime($r['date_reservation'])) : '—';
            $canCancel = in_array($sl, ['en attente']);
            // Données JSON pour le modal détail
            $json = htmlspecialchars(json_encode([
              'ref'       => $r['reference'] ?: '#'.$r['id'],
              'service'   => $r['service'],
              'depart'    => $r['depart'],
              'dest'      => $r['destination'],
              'chauffeur' => $r['chauffeur'],
              'mat'       => $r['matricule'],
              'heure'     => $r['heure_depart'],
              'paiement'  => $r['mode_paiement'],
              'montant'   => fmt((float)$r['montant']).' FCFA',
              'statut'    => ucfirst($r['statut']),
              'date'      => $dateAff,
              'note'      => $r['note_client'] ?: '—',
            ]), ENT_QUOTES);
          ?>
          <tr>
            <td data-label="Référence"><span class="td-ref"><?= htmlspecialchars($r['reference'] ?: '#'.$r['id']) ?></span></td>
            <td data-label="Type"><span class="td-type-icon <?= $cls ?>"><i class="bi <?= $icon ?>"></i></span></td>
            <td data-label="Trajet">
              <div class="td-route">
                <?= htmlspecialchars($r['destination']) ?>
                <span><?= htmlspecialchars($r['depart']) ?></span>
              </div>
            </td>
            <td data-label="Chauffeur"><?= htmlspecialchars($r['chauffeur'] ?: '—') ?></td>
            <td data-label="Heure départ"><?= htmlspecialchars($r['heure_depart'] ?: '—') ?></td>
            <td data-label="Statut"><span class="badge-pill <?= $bCls ?>"><?= htmlspecialchars(ucfirst($r['statut'])) ?></span></td>
            <td data-label="Montant"><span class="td-montant"><?= fmt((float)$r['montant']) ?> F</span></td>
            <td data-label="Date"><span class="td-date"><?= $dateAff ?></span></td>
            <td data-label="Actions">
              <div class="td-actions">
                <button class="btn-eye" title="Voir détails" onclick='ouvrirDetail(<?= $json ?>)'><i class="bi bi-eye"></i></button>
                <?php if ($canCancel): ?>
                  <button class="btn-cancel" title="Annuler" onclick="annuler(<?= $r['id'] ?>)"><i class="bi bi-x-lg"></i></button>
                <?php endif ?>
                
              </div>
             
            </td>
            <td>
    <a href="tracking.php?reservation_id=<?= $r['id'] ?>" class="btn-track">
        Suivre
    </a>
</td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <div class="pagination-wrap">
        <span class="pag-info">
          Page <?= $page ?> / <?= $totalPages ?> — <?= $totalRows ?> résultat<?= $totalRows > 1 ? 's' : '' ?>
        </span>
        <div class="pag-btns">
          <?php
            $qs = http_build_query(array_merge($_GET, ['page' => $page - 1]));
          ?>
          <a class="pag-btn <?= $page <= 1 ? 'disabled' : '' ?>" href="?<?= $qs ?>"><i class="bi bi-chevron-left"></i></a>

          <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++):
            $qs2 = http_build_query(array_merge($_GET, ['page' => $p]));
          ?>
            <a class="pag-btn <?= $p === $page ? 'active' : '' ?>" href="?<?= $qs2 ?>"><?= $p ?></a>
          <?php endfor ?>

          <?php
            $qs3 = http_build_query(array_merge($_GET, ['page' => $page + 1]));
          ?>
          <a class="pag-btn <?= $page >= $totalPages ? 'disabled' : '' ?>" href="?<?= $qs3 ?>"><i class="bi bi-chevron-right"></i></a>
        </div>
      </div>
      <?php endif ?>
    </div>

  </main>
</div>

<!-- ── MODAL DÉTAIL ───────────────────────────────────────────────── -->
<div id="detail-overlay">
  <div class="detail-box">
    <div class="detail-hd">
      <h3>Détail de la réservation</h3>
      <button class="detail-close" onclick="fermerDetail()"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="detail-body">
      <div class="det-row"><span class="det-label">N° référence</span><span class="det-val mono green" id="d-ref">—</span></div>
      <div class="det-row"><span class="det-label">Service</span><span class="det-val" id="d-service">—</span></div>
      <div class="det-row"><span class="det-label">Départ</span><span class="det-val" id="d-depart">—</span></div>
      <div class="det-row"><span class="det-label">Destination</span><span class="det-val" id="d-dest">—</span></div>
      <div class="det-row"><span class="det-label">Chauffeur</span><span class="det-val" id="d-chauffeur">—</span></div>
      <div class="det-row"><span class="det-label">Matricule</span><span class="det-val mono" id="d-mat">—</span></div>
      <div class="det-row"><span class="det-label">Heure de départ</span><span class="det-val green" id="d-heure">—</span></div>
      <div class="det-row"><span class="det-label">Mode de paiement</span><span class="det-val" id="d-paiement">—</span></div>
      <div class="det-row"><span class="det-label">Montant</span><span class="det-val green" id="d-montant">—</span></div>
      <div class="det-row"><span class="det-label">Statut</span><span class="det-val" id="d-statut">—</span></div>
      <div class="det-row"><span class="det-label">Date réservation</span><span class="det-val" id="d-date">—</span></div>
      <div class="det-row"><span class="det-label">Note client</span><span class="det-val" id="d-note">—</span></div>
    </div>
    <div class="detail-ft">
      <button class="btn-close-det" onclick="fermerDetail()">Fermer</button>
      <button class="btn-dl" onclick="imprimerTicket()"><i class="bi bi-download"></i> Télécharger ticket</button>
    </div>
  </div>
</div>

<script>
/* ── Modal détail ───────────────────────────────────────────────── */
let currentDetail = null;

function ouvrirDetail(data){
  currentDetail = data;
  document.getElementById('d-ref').textContent      = data.ref;
  document.getElementById('d-service').textContent  = data.service   || '—';
  document.getElementById('d-depart').textContent   = data.depart    || '—';
  document.getElementById('d-dest').textContent     = data.dest      || '—';
  document.getElementById('d-chauffeur').textContent= data.chauffeur || '—';
  document.getElementById('d-mat').textContent      = data.mat       || '—';
  document.getElementById('d-heure').textContent    = data.heure     || '—';
  document.getElementById('d-paiement').textContent = data.paiement  || '—';
  document.getElementById('d-montant').textContent  = data.montant   || '—';
  document.getElementById('d-statut').textContent   = data.statut    || '—';
  document.getElementById('d-date').textContent     = data.date      || '—';
  document.getElementById('d-note').textContent     = data.note      || '—';
  document.getElementById('detail-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function fermerDetail(){
  document.getElementById('detail-overlay').classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('detail-overlay').addEventListener('click', function(e){
  if(e.target === this) fermerDetail();
});

/* ── Impression ticket simple ───────────────────────────────────── */
function imprimerTicket(){
  if(!currentDetail) return;
  const d = currentDetail;
  const content = `
    <html><head><title>Ticket ${d.ref}</title>
    <style>
      body{font-family:monospace;padding:30px;max-width:400px;margin:auto}
      h2{font-size:18px;margin-bottom:16px;text-align:center}
      .row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px dashed #ccc;font-size:13px}
      .label{color:#666}.val{font-weight:bold;text-align:right}
      .ref{text-align:center;font-size:22px;letter-spacing:2px;margin:16px 0;padding:10px;border:2px dashed #333}
    </style></head><body>
    <h2>🚗 Yoon bu Gaw — Ticket</h2>
    <div class="ref">${d.ref}</div>
    <div class="row"><span class="label">Service</span><span class="val">${d.service||'—'}</span></div>
    <div class="row"><span class="label">Trajet</span><span class="val">${d.depart} → ${d.dest}</span></div>
    <div class="row"><span class="label">Chauffeur</span><span class="val">${d.chauffeur||'—'}</span></div>
    <div class="row"><span class="label">Matricule</span><span class="val">${d.mat||'—'}</span></div>
    <div class="row"><span class="label">Départ</span><span class="val">${d.heure||'—'}</span></div>
    <div class="row"><span class="label">Paiement</span><span class="val">${d.paiement||'—'}</span></div>
    <div class="row"><span class="label">Montant</span><span class="val">${d.montant}</span></div>
    <div class="row"><span class="label">Statut</span><span class="val">${d.statut}</span></div>
    <div class="row"><span class="label">Date</span><span class="val">${d.date}</span></div>
    <p style="text-align:center;margin-top:20px;font-size:11px;color:#999">Présentez ce ticket au chauffeur — Yoon bu Gaw</p>
    </body></html>`;
  const w = window.open('','_blank','width=500,height=700');
  w.document.write(content);
  w.document.close();
  w.print();
}

/* ── Annulation ─────────────────────────────────────────────────── */
function annuler(id){
  if(!confirm('Annuler cette réservation ?')) return;
  fetch('cancel_reservation.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id='+id
  })
  .then(r => r.json())
  .then(d => {
    if(d.success) location.reload();
    else alert('Erreur : ' + (d.message || 'impossible d\'annuler'));
  })
  .catch(() => alert('Erreur réseau'));
}
</script>
</body>
</html>