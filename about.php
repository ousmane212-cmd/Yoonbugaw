<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>À Propos | Yoon bu Gaw</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #007D34; --dark-navy: #001529; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--dark-navy); }
        .navbar { background: #fff; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
        .nav-link { font-weight: 600; color: var(--dark-navy) !important; margin: 0 10px; }
        .nav-link.active { color: var(--primary-green) !important; border-bottom: 2px solid var(--primary-green); }
        .btn-outline-connexion { border: 1px solid #ddd; border-radius: 8px; padding: 8px 25px; font-weight: 600; text-decoration: none; color: var(--dark-navy); display: inline-block; text-align: center; }
        .btn-inscription { background: var(--primary-green); color: #fff !important; border-radius: 8px; padding: 8px 25px; font-weight: 600; text-decoration: none; display: inline-block; text-align: center; }
        .vision-card { border: none; border-radius: 15px; background: #f8f9fa; padding: 25px; height: 100%; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><img src="assets/images/logo.png" alt="Yoon bu Gaw" style="height: 45px;"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto text-center my-3 my-lg-0">
                <li class="nav-item"><a class="nav-link" href="index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link active" href="about.php">À propos</a></li>
                <li class="nav-item"><a class="nav-link" href="tarifs.php">Tarifs</a></li>
                <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>
            <div class="d-flex flex-column flex-lg-row align-items-stretch align-items-lg-center gap-2 gap-lg-3">
                <?php if ($is_logged_in): ?>
                    <a href="user/dashboard.php" class="btn-outline-connexion">Mon Espace</a>
                <?php else: ?>
                    <a href="login.php" class="btn-outline-connexion">Connexion</a>
                    <a href="register.php" class="btn-inscription">S'inscrire</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<section class="container py-5 px-4 px-md-3">
    <div class="row align-items-center g-4 g-lg-5">
        <div class="col-lg-6 text-center text-lg-start">
            <span class="text-success fw-bold small">NOTRE HISTOIRE</span>
            <h2 class="fw-bold mt-2 h1">Qui sommes-nous ?</h2>
            <p class="text-muted mt-3">
                Né de la volonté de moderniser le secteur du transport routier au Sénégal, <strong>Yoon bu Gaw</strong> est une solution numérique inclusive conçue pour interconnecter les villes et faciliter la mobilité de tous.
            </p>
            <p class="text-muted">
                Que vous soyez un étudiant de l'UNCHK cherchant à rentrer le week-end, un commerçant devant expédier sa marchandise, ou un professionnel ayant besoin d’un véhicule fiable, nous mettons la technologie au service de votre sécurité et de votre confort.
            </p>
        </div>
        <div class="col-lg-6">
            <div class="p-4 p-sm-5 bg-light rounded-4 text-center">
                <i class="fa-solid fa-map-location-dot fa-4x text-success mb-3"></i>
                <h4 class="fw-bold">Couverture Nationale</h4>
                <p class="text-muted small mb-0">De Dakar à Tambacounda, en passant par Touba et Saint-Louis, nous connectons tout le Sénégal.</p>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-4 row-cols-1 row-cols-md-3">
        <div class="col">
            <div class="vision-card">
                <i class="fa-solid fa-bullseye text-success fa-xl mb-3"></i>
                <h5 class="fw-bold h6">Notre Mission</h5>
                <p class="text-muted small mb-0">Rendre les déplacements accessibles, fluides et transparents pour chaque citoyen sénégalais grâce au numérique.</p>
            </div>
        </div>
        <div class="col">
            <div class="vision-card">
                <i class="fa-solid fa-eye text-success fa-xl mb-3"></i>
                <h5 class="fw-bold h6">Notre Vision</h5>
                <p class="text-muted small mb-0">Devenir le leader incontournable de la mobilité intelligente et éco-responsable en Afrique de l'Ouest.</p>
            </div>
        </div>
        <div class="col">
            <div class="vision-card">
                <i class="fa-solid fa-heart text-success fa-xl mb-3"></i>
                <h5 class="fw-bold h6">Nos Valeurs</h5>
                <p class="text-muted small mb-0">Sécurité stricte des passagers, transparence totale des prix appliqués et respect absolu de nos engagements.</p>
            </div>
        </div>
    </div>
</section>

<footer class="bg-light text-center py-4 border-top mt-5">
    <div class="container px-3">
        <p class="mb-1 small">&copy; 2026 Yoon bu Gaw. Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>