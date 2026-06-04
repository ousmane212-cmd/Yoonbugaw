<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

/*
|--------------------------------------------------------------------------
| ACTIONS
|--------------------------------------------------------------------------
*/

$success = "";

if (isset($_POST['add_client'])) {

    $nom = $_POST['nom'];
    $email = $_POST['email'];
    $telephone = $_POST['telephone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    /* CHECK EMAIL */
    $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
    $check->execute([$email]);

    if ($check->fetch()) {
        $success = "❌ Cet email existe déjà.";
    } else {

        $photo = "";
        if (!empty($_FILES['photo']['name'])) {
            $photo = time() . "_" . $_FILES['photo']['name'];
            move_uploaded_file($_FILES['photo']['tmp_name'], "../uploads/" . $photo);
        }

        $stmt = $pdo->prepare("
            INSERT INTO users (nom,email,telephone,password,role,statut,photo)
            VALUES (?,?,?,?, 'client','actif',?)
        ");
        $stmt->execute([$nom,$email,$telephone,$password,$photo]);

        $success = "✅ Client ajouté.";
    }
}

/* UPDATE */
if (isset($_POST['update_client'])) {

    $stmt = $pdo->prepare("
        UPDATE users
        SET nom=?, email=?, telephone=?
        WHERE id=? AND role='client'
    ");
    $stmt->execute([
        $_POST['nom'],
        $_POST['email'],
        $_POST['telephone'],
        $_POST['id']
    ]);

    $success = "Client modifié.";
}

/* DELETE */
if (isset($_GET['delete'])) {

    $stmt = $pdo->prepare("DELETE FROM users WHERE id=? AND role='client'");
    $stmt->execute([$_GET['delete']]);

    $success = "Client supprimé.";
}

/* BLOCK */
if (isset($_GET['block'])) {

    $id = $_GET['block'];
    $status = $_GET['status'];

    $new = ($status == 'actif') ? 'bloque' : 'actif';

    $stmt = $pdo->prepare("UPDATE users SET statut=? WHERE id=? AND role='client'");
    $stmt->execute([$new,$id]);
}

/* SEARCH */
$search = $_GET['search'] ?? '';

if ($search) {
    $clients = $pdo->prepare("
        SELECT * FROM users
        WHERE role='client'
        AND (nom LIKE ? OR email LIKE ? OR telephone LIKE ?)
        ORDER BY id DESC
    ");
    $clients->execute(["%$search%","%$search%","%$search%"]);
} else {
    $clients = $pdo->query("SELECT * FROM users WHERE role='client' ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Clients - Yoon bu Gaw</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<link rel="stylesheet" href="style.css">
<style>
  /* Style spécifique pour harmoniser les miniatures d'images dans le tableau */
  .table td img {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
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
    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
        <div>
            <h3 class="mb-0 fw-bold">Gestion des clients</h3>
            <small class="text-muted">Administration plateforme</small>
        </div>

        <button class="btn btn-success px-4 py-2" data-bs-toggle="modal" data-bs-target="#add">
            <i class="bi bi-person-plus-fill me-2"></i> Ajouter client
        </button>
    </div>

    <?php if($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $success ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="card card-box border-0 shadow-sm p-2 mb-3">
        <form method="GET" class="d-flex">
            <div class="input-group">
                <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search text-muted"></i></span>
                <input class="form-control border-start-0" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Rechercher un client par son nom, email ou téléphone...">
            </div>
        </form>
    </div>

    <div class="card card-box border-0 shadow-sm p-3">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Photo</th>
                <th>Nom</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Statut</th>
                <th class="text-end">Actions</th>
            </tr>
            </thead>

            <tbody>
            <?php while($c=$clients->fetch(PDO::FETCH_ASSOC)): ?>
            <tr>
                <td>#<?= $c['id'] ?></td>

                <td>
                    <img src="<?= $c['photo'] ? '../uploads/'.$c['photo'] : '../assets/images/profile.jpg' ?>" alt="Profil">
                </td>

                <td class="fw-medium"><?= htmlspecialchars($c['nom']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['telephone']) ?></td>

                <td>
                    <?= $c['statut']=='actif'
                        ? '<span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1">Actif</span>'
                        : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1">Bloqué</span>' ?>
                </td>

                <td class="text-end">
                    <div class="d-inline-flex gap-1">
                        <button class="btn btn-primary btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#edit<?= $c['id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </button>

                        <a href="?block=<?= $c['id'] ?>&status=<?= $c['statut'] ?>"
                           class="btn btn-warning btn-sm">
                            <i class="bi bi-lock"></i>
                        </a>

                        <a href="?delete=<?= $c['id'] ?>"
                           onclick="return confirm('Voulez-vous vraiment supprimer ce client ?')"
                           class="btn btn-danger btn-sm">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>

            <div class="modal fade" id="edit<?= $c['id'] ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content p-3 border-0 shadow">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title fw-bold">Modifier le client</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form method="POST">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">

                                <div class="mb-2">
                                    <label class="form-label small text-muted">Nom complet</label>
                                    <input class="form-control" name="nom" value="<?= htmlspecialchars($c['nom']) ?>" required>
                                </div>
                                <div class="mb-2">
                                    <label class="form-label small text-muted">Adresse Email</label>
                                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($c['email']) ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small text-muted">Téléphone</label>
                                    <input class="form-control" name="telephone" value="<?= htmlspecialchars($c['telephone']) ?>" required>
                                </div>

                                <div class="d-flex justify-content-end gap-2 mt-4">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                                    <button class="btn btn-success" name="update_client">Enregistrer les modifications</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php endwhile; ?>
            </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="add" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-3 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Ajouter un nouveau client</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-2">
                        <input class="form-control" name="nom" placeholder="Nom complet" required>
                    </div>
                    <div class="mb-2">
                        <input type="email" class="form-control" name="email" placeholder="Adresse email" required>
                    </div>
                    <div class="mb-2">
                        <input class="form-control" name="telephone" placeholder="Numéro de Téléphone" required>
                    </div>
                    <div class="mb-2">
                        <input class="form-control" type="password" name="password" placeholder="Mot de passe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-muted">Photo de profil (Optionnel)</label>
                        <input class="form-control" type="file" name="photo">
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                        <button class="btn btn-success" name="add_client">Ajouter le client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
include_once "footer.php";
?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>