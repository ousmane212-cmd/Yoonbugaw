<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Tarifs | Yoon bu Gaw</title>
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
        .table-container { background: white; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.03); padding: 20px; margin-bottom: 40px; border: 1px solid #eee; }
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
                <li class="nav-item"><a class="nav-link" href="about.php">À propos</a></li>
                <li class="nav-item"><a class="nav-link active" href="tarifs.php">Tarifs</a></li>
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
    <div class="text-center mb-5">
        <h1 class="fw-bold h2">Grille Tarifaire Transparente</h1>
        <p class="text-muted small">Pas de mauvaise surprise. Les prix indiqués sont fixes et validés lors de la commande.</p>
    </div>

    <h4 class="fw-bold text-success h5 mb-3"><i class="fa-solid fa-user-group me-2"></i> Covoiturage (Par passager)</h4>
    <div class="table-container table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr><th>Axe / Trajet</th><th>Véhicule</th><th>Tarif</th></tr>
            </thead>
            <tbody>
                <tr><td>Dakar ↔ Thiès</td><td>Berline</td><td>2 500 FCFA</td></tr>
                <tr><td>Dakar ↔ Mbour</td><td>Berline</td><td>3 500 FCFA</td></tr>
                <tr><td>Dakar ↔ Saint-Louis</td><td>SUV Comfort</td><td>7 000 FCFA</td></tr>
                <tr><td>Dakar ↔ Touba</td><td>Berline</td><td>5 000 FCFA</td></tr>
                <tr><td>Dakar ↔ Kaolack</td><td>Berline</td><td>5 000 FCFA</td></tr>

            </tbody>
        </table>
    </div>

    <h4 class="fw-bold text-primary h5 mb-3"><i class="fa-solid fa-box me-2"></i> Expédition de Colis</h4>
    <div class="table-container table-responsive">
        <table class="table table-hover align-middle mb-0 small">
            <thead class="table-light">
                <tr><th>Poids / Volume</th><th>Délai</th><th>Tarif base</th></tr>
            </thead>
            <tbody>
                <tr><td>Moins de 5kg</td><td>&lt; 24h</td><td>1 500 FCFA</td></tr>
                <tr><td>5kg à 20kg</td><td>&lt; 24h</td><td>3 000 FCFA</td></tr>
                <tr><td>Plus de 20kg</td><td>Sur mesure</td><td>Sur devis</td></tr>
            </tbody>
        </table>
    </div>
</section>

<footer class="bg-light text-center py-4 border-top">
    <div class="container px-3">
        <p class="mb-1 small">&copy; 2026 Yoon bu Gaw. Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>