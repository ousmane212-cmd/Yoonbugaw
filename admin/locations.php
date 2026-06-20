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

</head>
<body>
<div class="layout">

<?php include_once "sidebar.php"; ?>
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