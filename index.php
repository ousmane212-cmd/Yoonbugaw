<?php
session_start();
// Page d'accueil publique : accessible à tous, les restrictions s'appliquent au clic sur les actions
// Booléen encodé en JSON pour être facilement récupéré par le JavaScript
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoon bu Gaw | La route qui nous rapproche</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary-green: #007D34;
            --dark-navy: #001529;
            --accent-blue: #003366;
            --accent-orange: #FF9800;
            --text-gray: #4A4A4A;
            --light-bg: #F8F9FA;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: var(--dark-navy);
            background-color: #fff;
            overflow-x: hidden;
        }

        /* --- NAVIGATION --- */
        .navbar { background: #fff; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
        .navbar-brand img { height: 45px; }
        .nav-link { font-weight: 600; color: var(--dark-navy) !important; margin: 0 10px; }
        .nav-link.active { color: var(--primary-green) !important; border-bottom: 2px solid var(--primary-green); }
        .btn-outline-connexion { border: 1px solid #ddd; border-radius: 8px; padding: 8px 25px; font-weight: 600; text-decoration: none; color: var(--dark-navy); }
        .btn-inscription { background: var(--primary-green); color: #fff !important; border-radius: 8px; padding: 8px 25px; font-weight: 600; text-decoration: none; transition: 0.3s; }
        .btn-inscription:hover { background: #00642a; }

        /* --- HERO SECTION --- */
        .hero { padding: 60px 0; position: relative; }
        .hero-badge { 
            background: #E6F3EB; color: var(--primary-green); 
            padding: 6px 15px; border-radius: 6px; font-weight: 800; font-size: 12px; display: inline-block;
        }
        .hero-title { font-size: 3.5rem; font-weight: 800; line-height: 1.1; margin: 20px 0; }
        .hero-title span { color: var(--primary-green); }
        
        /* APPLICATION DU CLIP-PATH UNIQUEMENT SUR DESKTOP */
        .hero-img-container {
            position: relative;
            width: 100%;
            height: 500px;
            overflow: hidden;
            border-radius: 30px;
            background: #eee;
        }
        @media (min-width: 992px) {
            .hero-img-container {
                width: 115%;
                clip-path: path('M150 0 L1000 0 L1000 1000 L0 1000 C 100 800, 0 600, 150 500 C 300 400, 200 200, 150 0 Z');
                border-radius: 0;
            }
        }
        .hero-img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .btn-hero-primary { background: var(--primary-green); color: #fff; border-radius: 50px; padding: 12px 30px; font-weight: 700; border: none; transition: 0.3s; }
        .btn-hero-primary:hover { background: #00642a; color: #fff; }
        .btn-hero-secondary { border: 1px solid #ddd; border-radius: 50px; padding: 12px 30px; font-weight: 700; background: #fff; transition: 0.3s; }
        .btn-hero-secondary:hover { background: #f8f9fa; }

        /* --- SERVICES --- */
        .service-card {
            border: none; border-radius: 20px; padding: 40px 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05); transition: 0.3s; height: 100%;
            background: #fff;
        }
        .service-card:hover { transform: translateY(-10px); }
        .icon-circle {
            width: 60px; height: 60px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; margin: 0 auto 20px;
            font-size: 24px; color: #fff;
        }
        .icon-covoit { background: var(--primary-green); }
        .icon-loc { background: var(--accent-blue); }
        .icon-taxi { background: var(--accent-orange); }
        .icon-march { background: var(--dark-navy); }

        /* --- SECTION SOMBRE --- */
        .dark-section {
            background: var(--dark-navy); color: #fff;
            border-radius: 30px; padding: 60px 40px; margin: 50px 0;
        }
        .feature-icon { color: var(--primary-green); font-size: 32px; margin-bottom: 20px; }

        /* --- STATS --- */
        .stat-item { text-align: center; padding: 10px; }
        .stat-icon { color: var(--primary-green); font-size: 24px; margin-bottom: 10px; }
        .stat-number { font-size: 1.8rem; font-weight: 800; display: block; }
        .stat-label { color: var(--text-gray); font-size: 14px; }

        @media (max-width: 991px) {
            .hero-title { font-size: 2.5rem; }
            .hero-img-container { width: 100%; height: 350px; margin-top: 30px; }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="#"><img src="assets/images/logo.png" alt="Yoon bu Gaw"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link active" href="index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">À propos</a></li>
                <li class="nav-item"><a class="nav-link" href="tarifs.php">Tarifs</a></li>
                <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-3">
                <?php if ($is_logged_in): ?>
                    <a href="user/dashboard.php" class="btn-outline-connexion">Mon Espace</a>
                    <a href="logout.php" class="btn-inscription">Déconnexion</a>
                <?php else: ?>
                    <a href="login.php" class="btn-outline-connexion">Connexion</a>
                    <a href="register.php" class="btn-inscription">S'inscrire</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <span class="hero-badge">PLATEFORME DE TRANSPORT INTELLIGENTE</span>
                <h1 class="hero-title">La route qui <br><span>nous rapproche</span></h1>
                <p class="text-muted mb-4 fs-5">
                    Yoon bu Gaw vous accompagne au quotidien pour tous vos déplacements, livraisons et locations de véhicules partout au Sénégal.
                </p>
                <div class="d-flex gap-3 mb-5">
                    <button class="btn-hero-primary gated-link" data-href="user/mes_reservations.php">Réserver maintenant <i class="fa-solid fa-circle-arrow-right ms-2"></i></button>
                    <button class="btn-hero-secondary gated-link" data-href="user/louer_vehicule.php"><i class="fa-solid fa-car me-2"></i> Louer un véhicule</button>
                </div>
                <div class="d-flex align-items-center">
                    <div class="me-3">
                        <i class="fa-solid fa-circle-user fa-2x text-secondary"></i>
                        <i class="fa-solid fa-circle-user fa-2x text-secondary" style="margin-left:-15px"></i>
                        <i class="fa-solid fa-circle-user fa-2x text-secondary" style="margin-left:-15px"></i>
                    </div>
                    <span class="fw-bold text-success">+10K <span class="text-muted fw-normal">clients satisfaits</span></span>
                </div>
            </div>
            <div class="col-lg-6 p-0">
                <div class="hero-img-container">
                    <img src="assets/images/yoonu.jpg" alt="Buses et Statue" style="object-position: 50% 30%;">
                </div>
            </div>
        </div>
    </div>
</header>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h6 class="fw-bold text-muted mb-4">NOS SERVICES</h6>
        <div class="row g-4">
            <div class="col-md-3">
                <div class="service-card">
                    <div class="icon-circle icon-covoit"><i class="fa-solid fa-user-group"></i></div>
                    <h5 class="fw-bold">Covoiturage</h5>
                    <p class="small text-muted">Déplacements sûrs et confortables partout en ville et entre villes.</p>
                    <a href="#" class="text-success fw-bold text-decoration-none gated-link" data-href="user/reserver_covoiturage.php">Réserver →</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="service-card">
                    <div class="icon-circle icon-loc"><i class="fa-solid fa-bus"></i></div>
                    <h5 class="fw-bold">Location</h5>
                    <p class="small text-muted">Voyagez en toute sérénité avec nos cars modernes et climatisés.</p>
                    <a href="#" class="text-success fw-bold text-decoration-none gated-link" data-href="user/louer_car.php">Réserver →</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="service-card">
                    <div class="icon-circle icon-taxi"><i class="fa-solid fa-taxi"></i></div>
                    <h5 class="fw-bold">Taxi</h5>
                    <p class="small text-muted">Trouvez un taxi rapidement et arrivez à destination en toute sécurité.</p>
                    <a href="#" class="text-success fw-bold text-decoration-none gated-link" data-href="user/reserver_taxi.php">Réserver →</a>
                </div>
            </div>
            <div class="col-md-3">
                <div class="service-card">
                    <div class="icon-circle icon-march"><i class="fa-solid fa-box"></i></div>
                    <h5 class="fw-bold">Marchandises</h5>
                    <p class="small text-muted">Livraison rapide et fiable de vos colis partout au Sénégal.</p>
                    <a href="#" class="text-primary fw-bold text-decoration-none gated-link" data-href="user/expedier_colis.php">Expédier →</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="container">
    <div class="dark-section text-center">
        <h4 class="fw-bold mb-5">POURQUOI CHOISIR YOON BU GAW ?</h4>
        <div class="row g-4">
            <div class="col-md-3">
                <i class="fa-solid fa-shield-halved feature-icon"></i>
                <h6 class="fw-bold">Sécurité garantie</h6>
                <p class="small text-white-50">Chauffeurs qualifiés et vérifiés pour votre sécurité.</p>
            </div>
            <div class="col-md-3">
                <i class="fa-solid fa-hand-holding-dollar feature-icon"></i>
                <h6 class="fw-bold">Prix transparents</h6>
                <p class="small text-white-50">Des tarifs clairs et compétitifs sans frais cachés.</p>
            </div>
            <div class="col-md-3">
                <i class="fa-solid fa-clock-rotate-left feature-icon"></i>
                <h6 class="fw-bold">Suivi temps réel</h6>
                <p class="small text-white-50">Suivez vos trajets et vos livraisons en direct.</p>
            </div>
            <div class="col-md-3">
                <i class="fa-solid fa-credit-card feature-icon"></i>
                <h6 class="fw-bold">Paiement facile</h6>
                <p class="small text-white-50">Payez par Mobile Money ou carte bancaire.</p>
            </div>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row g-3">
        <div class="col-md col-6 stat-item">
            <i class="fa-solid fa-users stat-icon"></i>
            <span class="stat-number">+25 000</span>
            <span class="stat-label">Clients satisfaits</span>
        </div>
        <div class="col-md col-6 stat-item">
            <i class="fa-solid fa-user-tie stat-icon"></i>
            <span class="stat-number">+1 200</span>
            <span class="stat-label">Chauffeurs actifs</span>
        </div>
        <div class="col-md col-6 stat-item">
            <i class="fa-solid fa-car stat-icon"></i>
            <span class="stat-number">+850</span>
            <span class="stat-label">Véhicules</span>
        </div>
        <div class="col-md col-6 stat-item">
            <i class="fa-solid fa-calendar-check stat-icon"></i>
            <span class="stat-number">+40 000</span>
            <span class="stat-label">Trajets effectués</span>
        </div>
        <div class="col-md col-6 stat-item">
            <i class="fa-solid fa-truck-fast stat-icon"></i>
            <span class="stat-number">+5 000</span>
            <span class="stat-label">Livraisons</span>
        </div>
    </div>
</div>

<footer class="bg-light text-center py-4">
    <div class="container">
        <p class="mb-1">&copy; 2026 Yoon bu Gaw. Tous droits réservés.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="#" class="text-muted"><i class="fa-brands fa-facebook"></i></a>
            <a href="#" class="text-muted"><i class="fa-brands fa-twitter"></i></a>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Injection propre de l'état de connexion PHP vers JS
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;

    document.addEventListener("DOMContentLoaded", function() {
        const gatedLinks = document.querySelectorAll('.gated-link');

        gatedLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault(); // Stoppe l'action par défaut
                
                // Récupère l'URL cible définie dans l'attribut data-href
                const targetUrl = this.getAttribute('data-href');

                if (isLoggedIn) {
                    // Si connecté, redirige vers l'action
                    if (targetUrl) {
                        window.location.href = targetUrl;
                    }
                } else {
                    // Si non connecté, alerte et redirige vers la page de connexion
                    alert("Vous devez être connecté pour accéder à ce service.");
                    window.location.href = "login.php";
                }
            });
        });
    });
</script>
</body>
</html>