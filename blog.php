<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog & Actualités | Yoon bu Gaw</title>
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
        .blog-card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); overflow: hidden; height: 100%; display: flex; flex-direction: column; justify-content: space-between; background: white; }
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
                <li class="nav-item"><a class="nav-link" href="tarifs.php">Tarifs</a></li>
                <li class="nav-item"><a class="nav-link active" href="blog.php">Blog</a></li>
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
    <h1 class="fw-bold h2 text-center text-sm-start mb-4">Le Mag' Yoon bu Gaw</h1>
    <div class="row g-4 row-cols-1 row-cols-sm-2 row-cols-lg-3">
        <div class="col">
            <div class="card blog-card">
                <div class="p-4 bg-light text-center"><i class="fa-solid fa-road fa-2x text-success"></i></div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-success small fw-bold">CONSEILS</span>
                        <h5 class="fw-bold h6 mt-1">5 astuces pour un covoiturage réussi</h5>
                        <p class="text-muted small">Découvrez comment passer un agréable trajet collectif entre Dakar et Saint-Louis.</p>
                    </div>
                    <a href="#" class="text-success text-decoration-none fw-bold small mt-2">Lire la suite →</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card blog-card">
                <div class="p-4 bg-light text-center"><i class="fa-solid fa-shield-halved fa-2x text-success"></i></div>
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-success small fw-bold">SÉCURITÉ</span>
                        <h5 class="fw-bold h6 mt-1">Vérification de nos chauffeurs</h5>
                        <p class="text-muted small">La sécurité est notre priorité. Zoom sur notre charte qualité stricte.</p>
                    </div>
                    <a href="#" class="text-success text-decoration-none fw-bold small mt-2">Lire la suite →</a>
                </div>
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