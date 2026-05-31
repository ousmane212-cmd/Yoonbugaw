<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Services | Yoon bu Gaw</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary-green: #007D34; --dark-navy: #001529; --accent-blue: #003366; --accent-orange: #FF9800; --light-bg: #F8F9FA; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--dark-navy); }
        .navbar { background: #fff; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
        .nav-link { font-weight: 600; color: var(--dark-navy) !important; margin: 0 10px; }
        .nav-link.active { color: var(--primary-green) !important; border-bottom: 2px solid var(--primary-green); }
        .btn-outline-connexion { border: 1px solid #ddd; border-radius: 8px; padding: 8px 25px; font-weight: 600; text-decoration: none; color: var(--dark-navy); display: inline-block; text-align: center; }
        .btn-inscription { background: var(--primary-green); color: #fff !important; border-radius: 8px; padding: 8px 25px; font-weight: 600; text-decoration: none; display: inline-block; text-align: center; }
        
        .page-header { background: var(--light-bg); padding: 40px 0; border-bottom: 1px solid #eee; }
        .service-detailed-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); background: #fff; height: 100%; display: flex; flex-direction: column; justify-content: space-between; }
        .btn-service { background: var(--primary-green); color: white; border: none; border-radius: 8px; padding: 10px 20px; font-weight: 600; width: 100%; }
        @media (min-width: 576px) { .btn-service { width: auto; } }
        .btn-service:hover { background: #00642a; color: white; }
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
                <li class="nav-item"><a class="nav-link active" href="services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">À propos</a></li>
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

<header class="page-header text-center px-3">
    <div class="container">
        <h1 class="fw-bold display-6 display-md-5">Nos Services de Transport</h1>
        <p class="text-muted col-lg-6 mx-auto mb-0">Découvrez comment Yoon bu Gaw simplifie vos déplacements urbains et interurbains à travers le Sénégal.</p>
    </div>
</header>

<section class="container py-5 px-4 px-md-3">
    <div class="row g-4 row-cols-1 row-cols-md-2">
        <div class="col">
            <div class="card service-detailed-card p-4">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 rounded bg-success text-white me-3"><i class="fa-solid fa-user-group fa-xl"></i></div>
                        <h3 class="fw-bold h4 mb-0">Covoiturage</h3>
                    </div>
                    <p class="text-muted small">Partagez vos trajets avec d’autres voyageurs allant dans la même direction. Économique, convivial et écologique pour vos trajets Dakar-Thiès, Dakar-Saint-Louis, etc.</p>
                </div>
                <button class="btn btn-service gated-link mt-3 align-self-sm-start" data-href="user/reserver_covoiturage.php">Trouver un trajet</button>
            </div>
        </div>
        <div class="col">
            <div class="card service-detailed-card p-4">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 rounded bg-primary text-white me-3"><i class="fa-solid fa-bus fa-xl"></i></div>
                        <h3 class="fw-bold h4 mb-0">Location de Véhicules</h3>
                    </div>
                    <p class="text-muted small">Besoin d'un bus pour un événement familial, une sortie d'entreprise ou un voyage de groupe ? Louez des véhicules modernes avec chauffeurs professionnels certifiés.</p>
                </div>
                <button class="btn btn-service gated-link mt-3 align-self-sm-start" data-href="user/louer_car.php">Demander un devis</button>
            </div>
        </div>
        <div class="col">
            <div class="card service-detailed-card p-4">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 rounded text-white me-3" style="background: var(--accent-orange);"><i class="fa-solid fa-taxi fa-xl"></i></div>
                        <h3 class="fw-bold h4 mb-0">Réservation de Taxi</h3>
                    </div>
                    <p class="text-muted small">Commandez un taxi privé instantanément en ville. Un prix fixe connu à l'avance, aucun marchandage nécessaire, confort et sécurité garantis jusqu'à votre destination.</p>
                </div>
                <button class="btn btn-service gated-link mt-3 align-self-sm-start" data-href="user/reserver_taxi.php">Commander un taxi</button>
            </div>
        </div>
        <div class="col">
            <div class="card service-detailed-card p-4">
                <div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="p-3 rounded text-white me-3" style="background: var(--dark-navy);"><i class="fa-solid fa-box fa-xl"></i></div>
                        <h3 class="fw-bold h4 mb-0">Livraison & Marchandises</h3>
                    </div>
                    <p class="text-muted small">Expédiez vos colis, meubles ou marchandises lourdes en toute sérénité. Suivi en temps réel de votre livraison depuis l'application, partout au Sénégal.</p>
                </div>
                <button class="btn btn-service gated-link mt-3 align-self-sm-start" data-href="user/expedier_colis.php">Expédier un colis</button>
            </div>
        </div>
    </div>
</section>

<footer class="bg-light text-center py-4 border-top mt-auto">
    <div class="container px-3">
        <p class="mb-1 small">&copy; 2026 Yoon bu Gaw. Tous droits réservés.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;
    document.querySelectorAll('.gated-link').forEach(link => {
        link.addEventListener('click', function() {
            if (isLoggedIn) {
                window.location.href = this.getAttribute('data-href');
            } else {
                alert("Vous devez être connecté pour accéder à ce service.");
                window.location.href = "login.php";
            }
        });
    });
</script>
</body>
</html>