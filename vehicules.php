<?php
session_start();
require_once "../config/database.php";

/* ── Sécurité admin ─────────────────────────────────────────────── */
if (empty($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php'); exit;
}

$UPLOAD_DIR  = __DIR__ . '/../assets/vehicules/';
$UPLOAD_URL  = '../assets/vehicules/';
$MAX_SIZE     = 2 * 1024 * 1024; // 2 Mo
$ALLOWED     = ['image/jpeg','image/png','image/webp'];
$ALLOWED_EXT = ['jpg','jpeg','png','webp'];

if (!is_dir($UPLOAD_DIR)) mkdir($UPLOAD_DIR, 0755, true);

$msg = $msgType = '';


/* ── AJOUTER / MODIFIER véhicule ────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action    = $_POST['action'];
    $id        = (int)($_POST['id'] ?? 0);
    $matricule = htmlspecialchars(trim($_POST['matricule'] ?? ''));
    $nom       = htmlspecialchars(trim($_POST['nom']       ?? ''));
    $chauffeur = htmlspecialchars(trim($_POST['chauffeur'] ?? ''));
    $type      = in_array($_POST['type'] ?? '', ['taxi','bus','cargo','location']) ? $_POST['type'] : 'taxi';
    $couleur   = htmlspecialchars(trim($_POST['couleur']   ?? ''));
    $statut    = in_array($_POST['statut'] ?? '', ['disponible','indisponible','en service']) ? $_POST['statut'] : 'disponible';

    /* Traitement upload photo */
    $photoPath = $_POST['photo_actuelle'] ?? ''; 
    if (!empty($_FILES['photo']['name'])) {
        $file    = $_FILES['photo'];
        $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime    = mime_content_type($file['tmp_name']);

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = "Erreur lors de l'upload (code {$file['error']})"; $msgType = 'error';
        } elseif (!in_array($mime, $ALLOWED) || !in_array($ext, $ALLOWED_EXT)) {
            $msg = "Format non autorisé. Utilisez JPG, PNG ou WebP."; $msgType = 'error';
        } elseif ($file['size'] > $MAX_SIZE) {
            $msg = "Fichier trop lourd (max 2 Mo)."; $msgType = 'error';
        } else {
            $safeMat  = preg_replace('/[^a-zA-Z0-9]/', '_', $matricule);
            $filename = $safeMat . '_' . time() . '.' . $ext;
            $dest     = $UPLOAD_DIR . $filename;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                if ($photoPath && file_exists(__DIR__ . '/../' . $photoPath)) {
                    @unlink(__DIR__ . '/../' . $photoPath);
                }
                $photoPath = 'assets/vehicules/' . $filename;
            } else {
                $msg = "Impossible de déplacer le fichier. Vérifiez les permissions."; $msgType = 'error';
            }
        }
    }

    if (!$msg) { 
    if ($action === 'add') {

        // Chauffeur NON obligatoire
        if (empty($matricule) || empty($nom)) {

            $msg = "Matricule et nom sont obligatoires."; 
            $msgType = 'error';

        } else {

            $s = $pdo->prepare("
                INSERT INTO vehicules 
                (matricule, nom, photo, chauffeur, type, couleur, statut) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $s->execute([
                $matricule,
                $nom,
                $photoPath,
                $chauffeur, 
                $type,
                $couleur,
                $statut
            ]);

            $msg = "✅ Véhicule « $nom » ajouté avec succès."; 
            $msgType = 'success';
        }

    } elseif ($action === 'edit' && $id) {

        $s = $pdo->prepare("
            UPDATE vehicules 
            SET matricule=?, nom=?, photo=?, chauffeur=?, type=?, couleur=?, statut=? 
            WHERE id=?
        ");

        $s->execute([
            $matricule,
            $nom,
            $photoPath,
            $chauffeur,
            $type,
            $couleur,
            $statut,
            $id
        ]);

        $msg = "✅ Véhicule mis à jour."; 
        $msgType = 'success';
    }
}

    /* ── SUPPRIMER ───────────────────────────────────────────────── */
    if ($action === 'delete' && $id) {
        $s = $pdo->prepare("SELECT photo FROM vehicules WHERE id=?");
        $s->execute([$id]);
        $row = $s->fetch();
        if ($row && $row['photo'] && file_exists(__DIR__ . '/../' . $row['photo'])) {
            @unlink(__DIR__ . '/../' . $row['photo']);
        }
        $pdo->prepare("DELETE FROM vehicules WHERE id=?")->execute([$id]);
        $msg = "🗑️ Véhicule supprimé."; $msgType = 'success';
    }

    /* ── CHANGER STATUT (toggle rapide AJAX) ─────────────────────── */
    if ($action === 'toggle_statut' && isset($_POST['nouveau_statut'])) {
        $ns = in_array($_POST['nouveau_statut'],['disponible','indisponible','en service'])
            ? $_POST['nouveau_statut'] : 'disponible';
        $pdo->prepare("UPDATE vehicules SET statut=? WHERE id=?")->execute([$ns,$id]);
        echo json_encode(['success'=>true,'statut'=>$ns]); exit;
    }
}

/* ── Liste des chauffeurs pour le formulaire ─────────────────── */
$stmtChauffeurs = $pdo->query("SELECT nom FROM users WHERE role = 'chauffeur' AND statut = 'actif' ORDER BY nom ASC");
$listeChauffeurs = $stmtChauffeurs->fetchAll(PDO::FETCH_ASSOC);

/* ── Liste véhicules ────────────────────────────────────────────── */
$filterType   = $_GET['type']   ?? '';
$filterStatut = $_GET['statut'] ?? '';
$where = []; $params = [];
if ($filterType)   { $where[] = 'type=?';   $params[] = $filterType; }
if ($filterStatut) { $where[] = 'statut=?'; $params[] = $filterStatut; }
$sql = "SELECT * FROM vehicules" . ($where ? ' WHERE '.implode(' AND ',$where) : '') . " ORDER BY type, id DESC";
$stmt = $pdo->prepare($sql); $stmt->execute($params);
$vehicules = $stmt->fetchAll();

/* Totaux rapides */
$totaux = $pdo->query("SELECT statut, COUNT(*) as n FROM vehicules GROUP BY statut")->fetchAll(PDO::FETCH_KEY_PAIR);
$totalV = array_sum($totaux);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Gestion Véhicules · Yoon bu Gaw</title>
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<style>
:root{
  --green:#07882b;--green-lt:#e6f4ea;
  --blue:#0344a2;--blue-lt:#e8f0fe;
  --amber:#f78e0c;--amber-lt:#fff3e0;
  --red:#dc2626;--red-lt:#fee2e2;
  --gray:#64748b;--border:#e2e8f0;
  --bg:#f8fafc;--white:#fff;
  --radius:14px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:#1e293b}

/* Layout ajusté pour la sidebar responsive */
.main{
  padding: 28px;
  min-height: 100vh;
}

/* Topbar */
.topbars{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px;gap:16px}
.topbars h1{font-family:'Syne',sans-serif;font-size:22px;font-weight:800;margin-bottom:0}
.topbars p{font-size:13px;color:var(--gray);margin-top:2px;margin-bottom:0}

/* Stats adaptatives */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.stat{background:#fff;border-radius:var(--radius);padding:16px 18px;border:1px solid var(--border);display:flex;align-items:center;gap:12px}
.stat-ic{width:42px;height:42px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0}
.stat-ic.g{background:var(--green-lt);color:var(--green)}
.stat-ic.b{background:var(--blue-lt);color:var(--blue)}
.stat-ic.a{background:var(--amber-lt);color:var(--amber)}
.stat-ic.r{background:var(--red-lt);color:var(--red)}
.stat-val{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;line-height:1}
.stat-lbl{font-size:12px;color:var(--gray);margin-top:2px}

/* Message flash */
.flash{padding:12px 16px;border-radius:10px;margin-bottom:18px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:8px}
.flash.success{background:#dcfce7;color:#16a34a;border:1px solid #bbf7d0}
.flash.error{background:var(--red-lt);color:var(--red);border:1px solid #fecaca}

/* Toolbar */
.toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin-bottom:16px}
.toolbar-left{display:flex;gap:10px;flex:1;flex-wrap:wrap}
.filter-sel{padding:9px 12px;border:1px solid var(--border);border-radius:10px;font-size:13px;font-family:'DM Sans',sans-serif;background:#fff;color:#1e293b;outline:none;cursor:pointer}
.filter-sel:focus{border-color:var(--green)}
.btn-add{padding:10px 20px;background:var(--green);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;display:flex;align-items:center;gap:8px;font-family:'DM Sans',sans-serif;white-space:nowrap;text-decoration:none}
.btn-add:hover{background:#065f20;color:#fff}

/* Table */
.table-wrap{background:#fff;border-radius:var(--radius);border:1px solid var(--border);overflow:hidden}
.table-head-row{padding:14px 18px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.table-head-row h3{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;margin-bottom:0}
table{width:100%;border-collapse:collapse}
thead th{font-size:11px;text-transform:uppercase;letter-spacing:.07em;color:var(--gray);padding:10px 14px;text-align:left;background:var(--bg);border-bottom:1px solid var(--border);white-space:nowrap}
tbody tr{border-bottom:1px solid var(--border);transition:.12s}
tbody tr:hover{background:#f8fafc}
tbody tr:last-child{border-bottom:none}
td{padding:12px 14px;font-size:13px;vertical-align:middle;white-space:nowrap}

/* Photo cellule */
.veh-thumb{width:60px;height:46px;border-radius:8px;object-fit:cover;background:var(--bg);display:block}
.veh-thumb-ph{width:60px;height:46px;border-radius:8px;background:var(--bg);display:flex;align-items:center;justify-content:center;font-size:22px}

/* Badges */
.badge-pill{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;white-space:nowrap;cursor:pointer;transition:.12s}
.bp-dispo{background:#dcfce7;color:#16a34a}
.bp-indispo{background:var(--red-lt);color:var(--red)}
.bp-service{background:var(--blue-lt);color:var(--blue)}
.bp-taxi{background:var(--amber-lt);color:#b45309}
.bp-bus{background:var(--blue-lt);color:var(--blue)}
.bp-cargo{background:var(--green-lt);color:var(--green)}

/* Actions */
.td-actions{display:flex;gap:6px}
.btn-ic{width:30px;height:30px;border-radius:8px;border:1px solid var(--border);background:#fff;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;font-size:14px;color:var(--gray);transition:.12s}
.btn-ic:hover.edit{background:var(--blue-lt);border-color:var(--blue);color:var(--blue)}
.btn-ic:hover.del{background:var(--red-lt);border-color:var(--red);color:var(--red)}

/* MODAL FORMULAIRE */
#form-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1100;display:none;align-items:center;justify-content:center;padding:16px}
#form-overlay.open{display:flex}
.form-box{background:#fff;border-radius:20px;width:100%;max-width:560px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.18);max-height:92vh;display:flex;flex-direction:column}
.form-hd{padding:20px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-shrink:0}
.form-hd h3{font-family:'Syne',sans-serif;font-size:16px;font-weight:700;margin-bottom:0}
.form-close{width:32px;height:32px;border-radius:50%;border:1px solid var(--border);background:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:16px;color:var(--gray)}
.form-body{padding:24px 30px;overflow-y:auto;flex:1;max-height:calc(90vh - 140px);padding-bottom:30px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px}
.fg{display:flex;flex-direction:column;gap:6px}
.fg label{font-size:12px;font-weight:600;color:#475569;text-transform:uppercase;letter-spacing:.04em}
.fg input,.fg select{padding:10px 12px;border:1.5px solid var(--border);border-radius:10px;font-size:14px;font-family:'DM Sans',sans-serif;outline:none;transition:.15s;background:#fff}
.fg input:focus,.fg select:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(7,136,43,.08)}

/* Zone upload photo */
.upload-zone{border:2px dashed var(--border);border-radius:12px;padding:20px;text-align:center;cursor:pointer;transition:.18s;position:relative;background:var(--bg)}
.upload-zone:hover,.upload-zone.drag{border-color:var(--green);background:var(--green-lt)}
.upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upload-zone .uz-icon{font-size:32px;margin-bottom:8px;display:block}
.upload-zone .uz-title{font-weight:600;font-size:14px;color:#1e293b}
.upload-zone .uz-sub{font-size:12px;color:var(--gray);margin-top:4px}
#preview-wrap{margin-top:12px;display:none;text-align:center}
#preview-wrap img{width:120px;height:90px;object-fit:cover;border-radius:10px;border:2px solid var(--green)}
#preview-wrap .rm-photo{margin-top:6px;font-size:12px;color:var(--red);cursor:pointer;display:block}

.form-ft{padding:16px 24px;border-top:1px solid var(--border);display:flex;gap:10px;flex-shrink:0}
.btn-cancel{flex:1;padding:11px;border-radius:10px;border:1px solid var(--border);background:#fff;cursor:pointer;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif;color:#1e293b}
.btn-save{flex:2;padding:11px;border-radius:10px;background:var(--green);color:#fff;border:none;cursor:pointer;font-size:14px;font-weight:600;font-family:'DM Sans',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px}
.btn-save:hover{background:#065f20}

.empty-state{padding:50px 20px;text-align:center;color:var(--gray)}
.empty-state i{font-size:40px;display:block;margin-bottom:10px}


@media (max-width: 991px) {
  .main {
    padding: 75px 16px 20px; /* Décale le contenu sous le bouton burger */
  }
  .stats {
    grid-template-columns: repeat(2, 1fr); /* 2 colonnes sur tablette */
  }
}

@media (max-width: 768px) {
  .topbar {
    flex-direction: column;
    align-items: flex-start;
  }
  .btn-add {
    width: 100%;
    justify-content: center;
  }
  .toolbar-left {
    width: 100%;
  }
  .filter-sel {
    flex: 1;
  }
  .form-row {
    grid-template-columns: 1fr; /* Champs les uns sous les autres sur mobile */
    gap: 14px;
  }
}

@media (max-width: 480px) {
  .stats {
    grid-template-columns: 1fr; /* 1 seule colonne sur petit téléphone */
  }
}
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
}

.stat{
    display:flex;
    align-items:center;
    gap:15px;
    padding:20px;
    border-radius:15px;
    color:#fff;
    box-shadow:0 4px 15px rgba(0,0,0,.12);
}

/* Total véhicules */
.stat.total{
    background:linear-gradient(135deg,#2563eb,#1d4ed8);
}

/* Disponibles */
.stat.disponible{
    background:linear-gradient(135deg,#16a34a,#15803d);
}

/* En service */
.stat.service{
    background:linear-gradient(135deg,#f59e0b,#d97706);
}

/* Indisponibles */
.stat.indisponible{
    background:linear-gradient(135deg,#dc2626,#b91c1c);
}

.stat-ic{
    width:55px;
    height:55px;
    border-radius:50%;
    background:rgba(255,255,255,.2);
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:24px;
}

.stat-val{
    font-size:28px;
    font-weight:700;
}

.stat-lbl{
    font-size:14px;
    opacity:.9;
}
</style>
</head>
<body>

<?php
include_once "sidebar.php";
?>

<div class="main-content"> 
  <?php
include_once "header.php";
?>
    <div class="topbar topbars">
      <div>
        <h1>Gestion des véhicules</h1>
        <p><?= $totalV ?> véhicule<?= $totalV > 1 ? 's' : '' ?> enregistré<?= $totalV > 1 ? 's' : '' ?></p>
      </div>
      <button class="btn-add" onclick="ouvrirForm()"><i class="bi bi-plus-lg"></i> Ajouter un véhicule</button>
    </div>

    <?php if ($msg): ?>
      <div class="flash <?= $msgType ?>"><i class="bi bi-<?= $msgType==='success'?'check-circle-fill':'exclamation-triangle-fill' ?>"></i><?= $msg ?></div>
    <?php endif ?>

<div class="stats">

  <div class="stat total">
    <div class="stat-ic">
      <i class="bi bi-truck"></i>
    </div>
    <div>
      <div class="stat-val"><?= $totalV ?></div>
      <div class="stat-lbl">Total véhicules</div>
    </div>
  </div>

  <div class="stat disponible">
    <div class="stat-ic">
      <i class="bi bi-check-circle-fill"></i>
    </div>
    <div>
      <div class="stat-val"><?= $totaux['disponible'] ?? 0 ?></div>
      <div class="stat-lbl">Disponibles</div>
    </div>
  </div>

  <div class="stat service">
    <div class="stat-ic">
      <i class="bi bi-arrow-repeat"></i>
    </div>
    <div>
      <div class="stat-val"><?= $totaux['en service'] ?? 0 ?></div>
      <div class="stat-lbl">En service</div>
    </div>
  </div>

  <div class="stat indisponible">
    <div class="stat-ic">
      <i class="bi bi-x-circle-fill"></i>
    </div>
    <div>
      <div class="stat-val"><?= $totaux['indisponible'] ?? 0 ?></div>
      <div class="stat-lbl">Indisponibles</div>
    </div>
  </div>

</div>
    

    <form method="GET" class="toolbar">
      <div class="toolbar-left">
        <select name="type" class="filter-sel" onchange="this.form.submit()">
          <option value="">Tous les types</option>
          <option value="taxi"  <?= $filterType==='taxi' ?'selected':'' ?>>🚕 Taxi</option>
          <option value="bus"   <?= $filterType==='bus'  ?'selected':'' ?>>🚌 Bus</option>
          <option value="cargo" <?= $filterType==='cargo'?'selected':'' ?>>Direct Cargo</option>
           <option value="location" <?= $filterType==='location'?'selected':'' ?>>location</option>
        </select>
        <select name="statut" class="filter-sel" onchange="this.form.submit()">
          <option value="">Tous les statuts</option>
          <option value="disponible"   <?= $filterStatut==='disponible'   ?'selected':'' ?>>Disponible</option>
          <option value="en service"   <?= $filterStatut==='en service'   ?'selected':'' ?>>En service</option>
          <option value="indisponible" <?= $filterStatut==='indisponible' ?'selected':'' ?>>Indisponible</option>
        </select>
        <?php if ($filterType || $filterStatut): ?>
          <a href="vehicules.php" class="filter-sel text-center text-decoration-none bg-light">Réinitialiser</a>
        <?php endif ?>
      </div>
    </form>

    <div class="table-wrap">
      <div class="table-head-row">
        <h3>Liste des véhicules</h3>
        <span style="font-size:13px;color:var(--gray)"><?= count($vehicules) ?> résultat<?= count($vehicules)>1?'s':'' ?></span>
      </div>

      <?php if (!$vehicules): ?>
        <div class="empty-state"><i class="bi bi-truck"></i>Aucun véhicule trouvé. <a href="#" onclick="ouvrirForm()">Ajouter le premier</a></div>
      <?php else: ?>
      <div class="table-responsive"> <table>
          <thead>
            <tr>
               <th>N°</th>
              <th>Photo</th>
              <th>Matricule</th>
              <th>Nom / Modèle</th>
              <th>Chauffeur</th>
              <th>Type</th>
              <th>Couleur</th>
              <th>Statut</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php $numero = 1; ?>
          <?php foreach ($vehicules as $v):
            $typeClass = 'bp-'.$v['type'];
            $statCls   = match($v['statut']) {
              'disponible'   => 'bp-dispo',
              'en service'   => 'bp-service',
              default        => 'bp-indispo',
            };
            $emoji = match($v['type']) { 'bus'=>'🚌', 'cargo'=>'🚛', default=>'🚕' };
            $photoFile = !empty($v['photo']) ? '../'.$v['photo'] : '';
          ?>
            <tr id="row-<?= $v['id'] ?>">
                 <td><?= $numero++ ?></td>
              <td>
                <?php if ($photoFile): ?>
                  <img class="veh-thumb" src="<?= htmlspecialchars($photoFile) ?>" alt="<?= htmlspecialchars($v['nom']) ?>"
                       onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                  <div class="veh-thumb-ph" style="display:none"><?= $emoji ?></div>
                <?php else: ?>
                  <div class="veh-thumb-ph"><?= $emoji ?></div>
                <?php endif ?>
              </td>
              <td><span style="font-family:monospace;font-weight:700;font-size:13px"><?= htmlspecialchars($v['matricule']) ?></span></td>
              <td style="font-weight:600"><?= htmlspecialchars($v['nom']) ?></td>
              <td><?= htmlspecialchars($v['chauffeur']) ?></td>
              <td><span class="badge-pill <?= $typeClass ?>"><?= ucfirst($v['type']) ?></span></td>
              <td><?= htmlspecialchars($v['couleur']) ?></td>
              <td>
                <span class="badge-pill <?= $statCls ?>" id="badge-<?= $v['id'] ?>"
                      onclick="toggleStatut(<?= $v['id'] ?>,'<?= $v['statut'] ?>')"
                      title="Cliquer pour changer le statut">
                  <?= ucfirst($v['statut']) ?>
                </span>
              </td>
              <td>
                <div class="td-actions">
                  <button class="btn-ic edit" title="Modifier"
                    onclick='ouvrirForm(<?= json_encode($v, JSON_UNESCAPED_UNICODE) ?>)'>
                    <i class="bi bi-pencil-fill"></i>
                  </button>
                  <button class="btn-ic del" title="Supprimer"
                    onclick="supprimerVeh(<?= $v['id'] ?>,'<?= htmlspecialchars($v['nom'],ENT_QUOTES) ?>')">
                    <i class="bi bi-trash-fill"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php endif ?>
    </div>
  </div>
</div>

<div id="form-overlay">
<div class="form-box">
  <div class="form-hd">
    <h3 id="form-title">Ajouter un véhicule</h3>
    <button class="form-close" onclick="fermerForm()"><i class="bi bi-x-lg"></i></button>
  </div>

  <form method="POST" enctype="multipart/form-data" id="veh-form">
    <div class="form-body">
      <input type="hidden" name="action" id="f-action" value="add">
      <input type="hidden" name="id"     id="f-id"     value="">
      <input type="hidden" name="photo_actuelle" id="f-photo-actuelle" value="">

      <div class="form-row">
        <div class="fg">
          <label>Matricule *</label>
          <input type="text" name="matricule" id="f-matricule" placeholder="Ex: DK-1234-A" required>
        </div>
        <div class="fg">
          <label>Nom / Modèle *</label>
          <input type="text" name="nom" id="f-nom" placeholder="Ex: Toyota Corolla" required>
        </div>
      </div>

      <div class="form-row">
        <div class="fg">
          <label>Chauffeur *</label>
          <select name="chauffeur" id="f-chauffeur" >
            <option value="">-- Sélectionner un chauffeur --</option>
            <?php foreach ($listeChauffeurs as $ch): ?>
              <option value="<?= htmlspecialchars($ch['nom']) ?>"><?= htmlspecialchars($ch['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fg">
          <label>Couleur</label>
          <input type="text" name="couleur" id="f-couleur" placeholder="Ex: Jaune">
        </div>
      </div>

      <div class="form-row">
        <div class="fg">
          <label>Type de transport *</label>
          <select name="type" id="f-type">
            <option value="taxi">🚕 Taxi</option>
            <option value="bus">🚌 Bus</option>
            <option value="cargo">Direct Cargo</option>
            <option value="location">location</option>
          </select>
        </div>
        <div class="fg">
          <label>Statut</label>
          <select name="statut" id="f-statut">
            <option value="disponible">✅ Disponible</option>
            <option value="en service">🔵 En service</option>
            <option value="indisponible">❌ Indisponible</option>
          </select>
        </div>
      </div>

      <div class="fg" style="margin-top:4px">
        <label>Photo du véhicule</label>
        <div class="upload-zone" id="upload-zone">
          <input type="file" name="photo" id="f-photo" accept="image/jpeg,image/png,image/webp" onchange="previewPhoto(this)">
          <span class="uz-icon">📸</span>
          <div class="uz-title">Glissez une photo ici ou cliquez pour parcourir</div>
          <div class="uz-sub">JPG, PNG, WebP — max 2 Mo</div>
        </div>
        <div id="preview-wrap">
          <img id="preview-img" src="" alt="Aperçu">
          <span class="rm-photo" onclick="supprimerPreview()">✕ Supprimer la photo</span>
        </div>
      </div>
    </div>

    <div class="form-ft">
      <button type="button" class="btn-cancel" onclick="fermerForm()">Annuler</button>
      <button type="submit" class="btn-save"><i class="bi bi-floppy-fill"></i> Enregistrer</button>
    </div>
  </form>
</div>
</div>

<form method="POST" id="delete-form" style="display:none">
<input type="hidden" name="action" value="delete">
<input type="hidden" name="id" id="delete-id">
</form>
 <?php
include_once "footer.php";
?>
<script>
function ouvrirForm(v = null){
  document.getElementById('form-title').textContent = v ? 'Modifier le véhicule' : 'Ajouter un véhicule';
  document.getElementById('f-action').value    = v ? 'edit' : 'add';
  document.getElementById('f-id').value        = v ? v.id : '';
  document.getElementById('f-matricule').value = v ? v.matricule  : '';
  document.getElementById('f-nom').value       = v ? v.nom        : '';
  document.getElementById('f-chauffeur').value = v ? v.chauffeur  : '';
  document.getElementById('f-couleur').value   = v ? v.couleur    : '';
  document.getElementById('f-type').value      = v ? v.type       : 'taxi';
  document.getElementById('f-statut').value    = v ? v.statut     : 'disponible';
  document.getElementById('f-photo-actuelle').value = v ? (v.photo || '') : '';

  if(v && v.photo){
    document.getElementById('preview-img').src = '../' + v.photo;
    document.getElementById('preview-wrap').style.display = 'block';
  } else {
    supprimerPreview();
  }

  document.getElementById('form-overlay').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function fermerForm(){
  document.getElementById('form-overlay').classList.remove('open');
  document.body.style.overflow = '';
  document.getElementById('veh-form').reset();
  supprimerPreview();
}

document.getElementById('form-overlay').addEventListener('click', function(e){
  if(e.target === this) fermerForm();
});

function previewPhoto(input){
  if(!input.files || !input.files[0]) return;
  const file = input.files[0];
  if(file.size > 2*1024*1024){
    alert('⚠️ Fichier trop lourd (max 2 Mo)'); input.value=''; return;
  }
  const reader = new FileReader();
  reader.onload = e => {
    document.getElementById('preview-img').src = e.target.result;
    document.getElementById('preview-wrap').style.display = 'block';
  };
  reader.readAsDataURL(file);
}

function supprimerPreview(){
  document.getElementById('preview-wrap').style.display = 'none';
  document.getElementById('preview-img').src = '';
  const inp = document.getElementById('f-photo');
  if(inp) inp.value = '';
}

const uz = document.getElementById('upload-zone');
if (uz) {
  uz.addEventListener('dragover', e => { e.preventDefault(); uz.classList.add('drag'); });
  uz.addEventListener('dragleave',()=> uz.classList.remove('drag'));
  uz.addEventListener('drop', e => {
    e.preventDefault(); uz.classList.remove('drag');
    const inp = document.getElementById('f-photo');
    inp.files = e.dataTransfer.files;
    previewPhoto(inp);
  });
}

function supprimerVeh(id, nom){
  if(!confirm(`Supprimer le véhicule « ${nom} » ? La photo sera aussi supprimée.`)) return;
  document.getElementById('delete-id').value = id;
  document.getElementById('delete-form').submit();
}

const STATUTS = ['disponible','en service','indisponible'];
const STATUT_CLS = {disponible:'bp-dispo','en service':'bp-service',indisponible:'bp-indispo'};

function toggleStatut(id, statutActuel){
  const idx = STATUTS.indexOf(statutActuel);
  const nouveau = STATUTS[(idx + 1) % STATUTS.length];

  fetch('vehicules.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body: new URLSearchParams({action:'toggle_statut', id, nouveau_statut: nouveau})
  })
  .then(r => r.json())
  .then(d => {
    if(d.success){
      const badge = document.getElementById('badge-'+id);
      badge.textContent = nouveau.charAt(0).toUpperCase() + nouveau.slice(1);
      badge.className = 'badge-pill ' + (STATUT_CLS[nouveau] || 'bp-indispo');
      badge.onclick = () => toggleStatut(id, nouveau);
    }
  })
  .catch(()=> location.reload());
}
</script>
</body>
</html>