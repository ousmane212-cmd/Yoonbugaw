<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'chauffeur') {
    header("Location: ../auth/login.php");
    exit;
}

$chauffeurId = $_SESSION['id'];

/* Courses en attente */
$stmt = $pdo->prepare("
    SELECT *
    FROM reservations
    WHERE chauffeur_id = ?
    AND (statut='en attente' OR statut='en_attente')
    ORDER BY id DESC
");
$stmt->execute([$chauffeurId]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Demandes de Courses</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
body{
    background:#f5f7fb;
    font-family:Arial,sans-serif;
}

.container-box{
    max-width:1000px;
    margin:40px auto;
}

.card-course{
    background:#fff;
    border-radius:20px;
    padding:20px;
    margin-bottom:20px;
    box-shadow:0 5px 20px rgba(0,0,0,.08);
}

.route{
    font-size:18px;
    font-weight:bold;
    color:#111827;
}

.price{
    color:#16a34a;
    font-size:22px;
    font-weight:bold;
}

.client{
    color:#6b7280;
}

.btn-accept{
    background:#16a34a;
    color:#fff;
    border:none;
    padding:12px 22px;
    border-radius:12px;
}

.btn-cancel{
    background:#dc2626;
    color:#fff;
    border:none;
    padding:12px 22px;
    border-radius:12px;
}

.btn-accept:hover{
    background:#15803d;
}

.btn-cancel:hover{
    background:#b91c1c;
}
</style>
</head>
<body>

<div class="container-box">

    <h2 class="mb-4 fw-bold">
        🚖 Demandes de courses
    </h2>

    <?php if(empty($courses)): ?>
        <div class="alert alert-success">
            Aucune demande en attente.
        </div>
    <?php endif; ?>

    <?php foreach($courses as $course): ?>

        <div class="card-course">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <div class="route">
                        📍 <?= htmlspecialchars($course['depart']) ?>
                        →
                        <?= htmlspecialchars($course['destination']) ?>
                    </div>

                    <div class="client mt-2">
                        👤 Client :
                        <?= htmlspecialchars($course['user_name']) ?>
                    </div>

                    <div class="client">
                        🕒
                        <?= date('d/m/Y H:i', strtotime($course['date_reservation'])) ?>
                    </div>

                    <div class="client">
                        💳
                        <?= htmlspecialchars($course['mode_paiement']) ?>
                    </div>
                </div>

                <div class="price">
                    <?= number_format($course['montant'],0,',',' ') ?>
                    FCFA
                </div>
            </div>

            <div class="d-flex gap-3">

                <!-- ACCEPTER -->
                <form action="accept_course.php" method="POST">
                    <input type="hidden" name="id" value="<?= $course['id'] ?>">
                    <input type="hidden" name="chauffeur_id" value="<?= $chauffeurId ?>">
                    <input type="hidden" name="action" value="accept">

                    <button class="btn-accept">
                        ✅ Accepter
                    </button>
                </form>

                <!-- ANNULER -->
                <form action="accept_course.php" method="POST">
                    <input type="hidden" name="id" value="<?= $course['id'] ?>">
                    <input type="hidden" name="chauffeur_id" value="<?= $chauffeurId ?>">
                    <input type="hidden" name="action" value="reject">

                    <button class="btn-cancel">
                        ❌ Annuler
                    </button>
                </form>

            </div>
        </div>

    <?php endforeach; ?>

</div>

</body>
</html>