<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contactez-nous | Yoon bu Gaw</title>
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
        .contact-box { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
        .btn-send { background: var(--primary-green); color: white; border: none; border-radius: 8px; padding: 12px 30px; font-weight: 600; width: 100%; }
        @media (min-width: 576px) { .btn-send { width: auto; } }
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
                <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
                <li class="nav-item"><a class="nav-link active" href="contact.php">Contact</a></li>
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
    <div class="row g-5">
        <div class="col-lg-5 text-center text-lg-start">
            <h1 class="fw-bold h2">Une question ? <br>Contactez-nous</h1>
            <p class="text-muted mt-3 small">Notre équipe support est disponible pour répondre à vos demandes.</p>
            
            <div class="mt-4 text-start mx-auto style-infos" style="max-width: 320px; margin-left: 0 !important;">
                <div class="d-flex align-items-center mb-3">
                    <div class="text-success me-3"><i class="fa-solid fa-phone"></i></div>
                    <div><h6 class="fw-bold mb-0 small">Téléphone</h6><span class="small text-muted">+221 33 800 00 00</span></div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="text-success me-3"><i class="fa-solid fa-envelope"></i></div>
                    <div><h6 class="fw-bold mb-0 small">Email</h6><span class="small text-muted">support@unchk.edu.sn</span></div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="text-success me-3"><i class="fa-solid fa-location-dot"></i></div>
                    <div><h6 class="fw-bold mb-0 small">Siège</h6><span class="small text-muted">Dakar, Sénégal</span></div>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="contact-box card p-4">
                <form action="#" method="POST">
                    <div class="row g-3 mb-3 row-cols-1 row-cols-sm-2">
                        <div class="col">
                            <label class="form-label small fw-bold">Nom complet</label>
                            <input type="text" class="form-control" placeholder="Ousmane Niang" required>
                        </div>
                        <div class="col">
                            <label class="form-label small fw-bold">Adresse Email</label>
                            <input type="email" class="form-control" placeholder="votre@email.com" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Sujet</label>
                        <input type="text" class="form-control" placeholder="Ex: Partenariat" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Message</label>
                        <textarea class="form-control" rows="4" placeholder="Votre message..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-send">Envoyer</button>
                </form>
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