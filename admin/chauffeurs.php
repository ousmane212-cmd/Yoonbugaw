<?php
session_start();
require_once "../config/database.php";

/* =========================
   SÉCURITÉ ADMIN
========================= */
if (empty($_SESSION['id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

/* =========================
   VARIABLES
========================= */
$msg = '';
$erreur = '';

/* =========================
   AJOUT CHAUFFEUR
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {

    $nom        = trim($_POST['nom'] ?? '');
    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telephone  = trim($_POST['telephone'] ?? '');
    $adresse    = trim($_POST['adresse'] ?? '');
    $password   = $_POST['password'] ?? '';
    $permis     = trim($_POST['permis'] ?? '');

    if (!$nom || !$email || !$password || !$permis) {

        $erreur = "Veuillez remplir tous les champs obligatoires.";

    } else {

        try {

            $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
            $check->execute([$email]);

            if ($check->fetch()) {

                $erreur = "Cette adresse email existe déjà.";

            } else {

                $hash = password_hash($password, PASSWORD_BCRYPT);

                $stmt = $pdo->prepare("
                    INSERT INTO users
                    (nom,email,telephone,adresse,password,role,statut,permis)
                    VALUES
                    (?,?,?,?,?,?,?,?)
                ");

                $stmt->execute([
                    $nom,
                    $email,
                    $telephone,
                    $adresse,
                    $hash,
                    'chauffeur',
                    'actif',
                    $permis
                ]);

                $msg = "Chauffeur ajouté avec succès.";

            }

        } catch (PDOException $e) {

            $erreur = "Erreur : " . $e->getMessage();

        }
    }
}

/* =========================
   MODIFICATION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit') {

    $id         = (int)($_POST['id'] ?? 0);
    $nom        = trim($_POST['nom'] ?? '');
    $email      = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telephone  = trim($_POST['telephone'] ?? '');
    $adresse    = trim($_POST['adresse'] ?? '');
    $permis     = trim($_POST['permis'] ?? '');

    if (!$id || !$nom || !$email || !$permis) {

        $erreur = "Veuillez remplir tous les champs obligatoires.";

    } else {

        try {

            $stmt = $pdo->prepare("
 UPDATE users SET
nom=?,
email=?,
telephone=?,
adresse=?,
permis=?,
statut=?
WHERE id=? AND role='chauffeur'
            ");

          
               $stmt->execute([
    $nom,
    $email,
    $telephone,
    $adresse,
    $permis,
    $_POST['statut'],
    $id
]);
            

            $msg = "Chauffeur modifié avec succès.";

        } catch (PDOException $e) {

            $erreur = "Erreur : " . $e->getMessage();

        }
    }
}

/* =========================
   SUPPRESSION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {

    $id = (int)($_POST['id'] ?? 0);

    if ($id > 0) {

        $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND role='chauffeur'");
        $stmt->execute([$id]);

        $msg = "Chauffeur supprimé avec succès.";
    }
}

/* =========================
   FILTRES
========================= */
$search = $_GET['search'] ?? '';
$statut_filtre = $_GET['statut'] ?? '';

$sql = "SELECT * FROM users WHERE role='chauffeur'";
$params = [];

if (!empty($search)) {
    $sql .= " AND nom LIKE ?";
    $params[] = "%$search%";
}

if (!empty($statut_filtre)) {
    $sql .= " AND statut=?";
    $params[] = $statut_filtre;
}

$sql .= " ORDER BY id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$chauffeurs = $stmt->fetchAll();

/* =========================
   KPI
========================= */
$countTotal = count($chauffeurs);
$countActif = 0;
$countOccupe = 0;
$countSuspendu = 0;

foreach ($chauffeurs as $c) {

   if (($c['statut'] ?? '') === 'actif') {
    $countActif++;
}

    if (($c['statut'] ?? '') === 'occupe') {
        $countOccupe++;
    }

    if (($c['statut'] ?? '') === 'suspendu') {
        $countSuspendu++;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gestion Chauffeurs</title>

    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap"
          rel="stylesheet">

    <style>

        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family:'DM Sans',sans-serif;
            background:#f8fafc;
            color:#1e293b;
        }

        .container{
            padding:30px;
        }

        .topbar{
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:25px;
        }

        .topbar h1{
            font-size:28px;
            font-weight:700;
        }

        .btn-add{
            background:#16a34a;
            color:#fff;
            border:none;
            padding:12px 18px;
            border-radius:10px;
            cursor:pointer;
            font-weight:600;
        }

        .stats{
            display:grid;
            grid-template-columns:repeat(4,1fr);
            gap:20px;
            margin-bottom:25px;
        }

        .card{
            background:#fff;
            padding:20px;
            border-radius:16px;
            border:1px solid #e2e8f0;
        }

        .card h2{
            font-size:26px;
            margin-bottom:6px;
        }

        .toolbar{
            background:#fff;
            padding:15px;
            border-radius:16px;
            margin-bottom:25px;
            border:1px solid #e2e8f0;
        }

        .toolbar form{
            display:flex;
            gap:10px;
        }

        .toolbar input,
        .toolbar select{
            padding:10px;
            border:1px solid #cbd5e1;
            border-radius:10px;
            width:220px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            background:#fff;
            border-radius:16px;
            overflow:hidden;
        }

        th{
            background:#f1f5f9;
            padding:15px;
            text-align:left;
            font-size:13px;
        }

        td{
            padding:15px;
            border-top:1px solid #e2e8f0;
        }

        .badge{
            padding:6px 12px;
            border-radius:30px;
            font-size:12px;
            font-weight:600;
        }

        .dispo{
            background:#dcfce7;
            color:#166534;
        }

        .occupe{
            background:#dbeafe;
            color:#1d4ed8;
        }

        .suspendu{
            background:#fee2e2;
            color:#dc2626;
        }

        .actions{
            display:flex;
            gap:10px;
        }

        .btn-icon{
            width:35px;
            height:35px;
            border:none;
            border-radius:8px;
            cursor:pointer;
        }

        .edit{
            background:#dbeafe;
            color:#2563eb;
        }

        .delete{
            background:#fee2e2;
            color:#dc2626;
        }

        .flash{
            padding:14px;
            border-radius:10px;
            margin-bottom:20px;
            font-weight:600;
        }

        .success{
            background:#dcfce7;
            color:#166534;
        }

        .error{
            background:#fee2e2;
            color:#dc2626;
        }

        /* MODAL */

        #overlay{
            position:fixed;
            inset:0;
            background:rgba(0,0,0,.5);
            display:none;
            justify-content:center;
            align-items:center;
            z-index:1000;
        }

        #overlay.active{
            display:flex;
        }

        .modal{
            width:100%;
            max-width:550px;
            background:#fff;
            border-radius:18px;
            overflow:hidden;
        }

        .modal-header{
            padding:20px;
            border-bottom:1px solid #e2e8f0;
            display:flex;
            justify-content:space-between;
            align-items:center;
        }

        .modal-body{
            padding:20px;
        }

        .row{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:15px;
            margin-bottom:15px;
        }

        .group{
            display:flex;
            flex-direction:column;
            gap:6px;
        }

        .group input{
            padding:11px;
            border:1px solid #cbd5e1;
            border-radius:10px;
        }

        .modal-footer{
            padding:20px;
            border-top:1px solid #e2e8f0;
            display:flex;
            gap:10px;
        }

        .btn{
            flex:1;
            padding:12px;
            border:none;
            border-radius:10px;
            cursor:pointer;
            font-weight:600;
        }

        .cancel{
            background:#e2e8f0;
        }

        .save{
            background:#16a34a;
            color:#fff;
        }

        @media(max-width:768px){

            .stats{
                grid-template-columns:1fr 1fr;
            }

            .row{
                grid-template-columns:1fr;
            }

            .toolbar form{
                flex-direction:column;
            }

            .toolbar input,
            .toolbar select{
                width:100%;
            }
        }
.actif{
    background:#dcfce7;
    color:#166534;
}
    </style>

</head>

<body>
<!-- SIDEBAR -->
<?php
include_once "sidebar.php";
?>

<!-- MAIN -->
<div class="main-content">

  <!-- Admin Header -->

<div class="container">

    <div class="topbar">

        <div>
            <h1>Gestion des Chauffeurs</h1>
        </div>

        <button class="btn-add" onclick="openForm()">
            <i class="bi bi-plus-lg"></i>
            Ajouter
        </button>

    </div>

    <?php if($msg): ?>
        <div class="flash success">
            <?= $msg ?>
        </div>
    <?php endif; ?>

    <?php if($erreur): ?>
        <div class="flash error">
            <?= $erreur ?>
        </div>
    <?php endif; ?>

    <div class="stats">

        <div class="card">
            <h2><?= $countTotal ?></h2>
            <p>Total</p>
        </div>

        <div class="card">
          <h2><?= $countActif ?></h2>
<p>Actifs</p>
        </div>

        <div class="card">
            <h2><?= $countOccupe ?></h2>
            <p>Occupés</p>
        </div>

        <div class="card">
            <h2><?= $countSuspendu ?></h2>
            <p>Suspendus</p>
        </div>

    </div>

    <div class="toolbar">

        <form method="GET">

            <input type="text"
                   name="search"
                   placeholder="Rechercher..."
                   value="<?= htmlspecialchars($search) ?>">

            <select name="statut">

                <option value="">Tous</option>

                <option value="diactif"
                    <?= $statut_filtre === 'actif' ? 'selected' : '' ?>>
                    Disponible
                </option>

                <option value="occupe"
                    <?= $statut_filtre === 'occupe' ? 'selected' : '' ?>>
                    Occupé
                </option>

                <option value="suspendu"
                    <?= $statut_filtre === 'suspendu' ? 'selected' : '' ?>>
                    Suspendu
                </option>

            </select>

            <button class="btn-add" type="submit">
                Filtrer
            </button>

        </form>

    </div>

    <table>

        <thead>

        <tr>
            <th>Nom</th>
            <th>Email</th>
            <th>Téléphone</th>
            <th>Permis</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>

        </thead>

        <tbody>

        <?php if(empty($chauffeurs)): ?>

            <tr>
                <td colspan="6">Aucun chauffeur trouvé.</td>
            </tr>

        <?php endif; ?>

        <?php foreach($chauffeurs as $c): ?>

            <tr>

                <td><?= htmlspecialchars($c['nom']) ?></td>

                <td><?= htmlspecialchars($c['email']) ?></td>

                <td><?= htmlspecialchars($c['telephone']) ?></td>

                <td><?= htmlspecialchars($c['permis']) ?></td>

                <td>

                    <span class="badge <?= $c['statut'] ?>">
                        <?= ucfirst($c['statut']) ?>
                    </span>

                </td>

                <td>

                    <div class="actions">

                        <button class="btn-icon edit"
                                onclick='openForm(<?= json_encode($c, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>

                            <i class="bi bi-pencil"></i>

                        </button>

                        <button class="btn-icon delete"
                                onclick="deleteDriver(<?= $c['id'] ?>)">

                            <i class="bi bi-trash"></i>

                        </button>

                    </div>

                </td>

            </tr>

        <?php endforeach; ?>

        </tbody>

    </table>

</div>

<!-- MODAL -->

<div id="overlay">

    <div class="modal">

        <div class="modal-header">

            <h3 id="modal-title">Ajouter Chauffeur</h3>

            <button onclick="closeForm()">
                ✕
            </button>

        </div>

        <form method="POST">

            <input type="hidden" name="action" id="form-action" value="add">
            <input type="hidden" name="id" id="form-id">

            <div class="modal-body">

                <div class="row">

                    <div class="group">
                        <label>Nom</label>
                        <input type="text" name="nom" id="nom" required>
                    </div>

                    <div class="group">
                        <label>Email</label>
                        <input type="email" name="email" id="email" required>
                    </div>

                </div>

                <div class="row">

                    <div class="group">
                        <label>Téléphone</label>
                        <input type="text" name="telephone" id="telephone">
                    </div>

                    <div class="group">
                        <label>Permis</label>
                        <input type="text" name="permis" id="permis" required>
                    </div>

                </div>

                <div class="group" style="margin-bottom:15px;">

                    <label>Adresse</label>

                    <input type="text" name="adresse" id="adresse">

                </div>
                <div class="group" style="margin-bottom:15px;">

    <label>Statut</label>

    <select name="statut" id="statut">

        <option value="actif">Actif</option>
        <option value="occupe">Occupé</option>
        <option value="suspendu">Suspendu</option>

    </select>

</div>

                <div class="group">

                    <label>Mot de passe</label>

                    <input type="password" name="password" id="password">

                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn cancel"
                        onclick="closeForm()">

                    Annuler

                </button>

                <button type="submit"
                        class="btn save">

                    Enregistrer

                </button>

            </div>

        </form>

    </div>

</div>

<form method="POST" id="delete-form" style="display:none;">

    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="delete-id">

</form>

<script>

    const overlay = document.getElementById('overlay');

    function openForm(data = null){

        overlay.classList.add('active');

        if(data){

            document.getElementById('modal-title').innerText = 'Modifier Chauffeur';

            document.getElementById('form-action').value = 'edit';

            document.getElementById('form-id').value = data.id;

            document.getElementById('nom').value = data.nom || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('telephone').value = data.telephone || '';
            document.getElementById('adresse').value = data.adresse || '';
            document.getElementById('permis').value = data.permis || '';
document.getElementById('statut').value = data.statut || 'actif';
            document.getElementById('password').required = false;

        }else{

            document.getElementById('modal-title').innerText = 'Ajouter Chauffeur';

            document.getElementById('form-action').value = 'add';

            document.getElementById('form-id').value = '';
document.getElementById('statut').value = 'actif';
            document.getElementById('nom').value = '';
            document.getElementById('email').value = '';
            document.getElementById('telephone').value = '';
            document.getElementById('adresse').value = '';
            document.getElementById('permis').value = '';
            document.getElementById('password').value = '';

            document.getElementById('password').required = true;

        }
    }

    function closeForm(){

        overlay.classList.remove('active');

    }

    function deleteDriver(id){

        if(confirm('Supprimer ce chauffeur ?')){

            document.getElementById('delete-id').value = id;

            document.getElementById('delete-form').submit();

        }
    }

</script>

</body>
</html>