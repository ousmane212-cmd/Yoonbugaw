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
        :root { --primary-green: #005f28; --dark-navy: #001529; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--dark-navy); }
        .navbar { background: #fff; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
        .nav-link { font-weight: 600; color: var(--dark-navy) !important; margin: 0 10px; }
        .nav-link.active { color: var(--primary-green) !important; border-bottom: 2px solid var(--primary-green); }
        .btn-outline-connexion { border: 1px solid #ddd; border-radius: 8px; padding: 8px 25px; font-weight: 600; text-decoration: none; color: var(--dark-navy); display: inline-block; text-align: center; }
        .btn-inscription { background: var(--primary-green); color: #fff !important; border-radius: 8px; padding: 8px 25px; font-weight: 600; text-decoration: none; display: inline-block; text-align: center; }
        .vision-card { border: none; border-radius: 15px; background: #f8f9fa; padding: 25px; height: 100%; }
        .vision-card{
    border:none;
    border-radius:20px;
    padding:30px;
    background:#fff;
    box-shadow:0 10px 30px rgba(0,0,0,0.06);
    transition:.3s;
}

.vision-card:hover{
    transform:translateY(-8px);
    box-shadow:0 20px 40px rgba(0,0,0,0.12);
}

.section-title{
    font-size:2.5rem;
    font-weight:800;
    color:#001529;
}
    </style>
</head>
<body>

<?php include_once('../includes/header.php'); ?>

<!-- HERO -->
<section class="py-5 text-white"
style="background:linear-gradient(135deg,#007D34,#00a84f);">

    <div class="container">
        <div class="row align-items-center g-5">

            <div class="col-lg-6">
                <span class="badge bg-light text-success mb-3 px-3 py-2">
                    Mobilité intelligente
                </span>

                <h1 class="display-4 fw-bold">
                    La route qui nous rapproche
                </h1>

                <p class="lead mt-4">
                    Yoon bu Gaw révolutionne le transport au Sénégal en
                    connectant voyageurs, chauffeurs et entreprises sur
                    une plateforme simple, rapide et sécurisée.
                </p>

                <div class="mt-4">
                    <a href="services.php" class="btn btn-light btn-lg me-2">
                        Découvrir nos services
                    </a>

                    <a href="contact.php" class="btn btn-outline-light btn-lg">
                        Nous contacter
                    </a>
                </div>
            </div>

            <div class="col-lg-6 text-center">
                <img src="../assets/images/2.png"
                     class="img-fluid"
                     alt="Yoon bu Gaw">
            </div>

        </div>
    </div>

</section>

<!-- QUI SOMMES NOUS -->
<section class="py-5">
    <div class="container">

        <div class="row align-items-center g-5">

            <div class="col-lg-6">
                <img src="../assets/images/about-team.png"
                     class="img-fluid rounded-4 shadow"
                     alt="Notre équipe">
            </div>

            <div class="col-lg-6">

                <span class="text-success fw-bold">
                    NOTRE HISTOIRE
                </span>

                <h2 class="fw-bold mt-2 mb-4">
                    Qui sommes-nous ?
                </h2>

                <p class="text-muted">
                    Né de la volonté de moderniser le transport au Sénégal,
                    Yoon bu Gaw est une plateforme numérique qui facilite
                    les déplacements des particuliers et des professionnels.
                </p>

                <p class="text-muted">
                    Nous proposons des solutions innovantes pour le
                    covoiturage, les taxis, la location de véhicules
                    et la livraison de marchandises.
                </p>

                <div class="row mt-4">

                    <div class="col-6">
                        <h3 class="fw-bold text-success">15K+</h3>
                        <small>Utilisateurs actifs</small>
                    </div>

                    <div class="col-6">
                        <h3 class="fw-bold text-success">500+</h3>
                        <small>Chauffeurs partenaires</small>
                    </div>

                </div>

            </div>

        </div>

    </div>
</section>

<!-- MISSION VISION VALEURS -->
<section class="py-5 bg-light">
    <div class="container">

        <div class="text-center mb-5">
            <span class="text-success fw-bold">
                NOS FONDAMENTAUX
            </span>

            <h2 class="fw-bold">
                Mission, Vision & Valeurs
            </h2>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-bullseye fa-3x text-success mb-3"></i>

                    <h5 class="fw-bold">
                        Notre Mission
                    </h5>

                    <p class="text-muted">
                        Offrir des solutions de mobilité modernes,
                        accessibles et fiables à tous les Sénégalais.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-eye fa-3x text-success mb-3"></i>

                    <h5 class="fw-bold">
                        Notre Vision
                    </h5>

                    <p class="text-muted">
                        Devenir la référence du transport intelligent
                        en Afrique de l'Ouest.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-heart fa-3x text-success mb-3"></i>

                    <h5 class="fw-bold">
                        Nos Valeurs
                    </h5>

                    <p class="text-muted">
                        Sécurité, transparence, innovation
                        et satisfaction client.
                    </p>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- IMPACT -->
<section class="py-5">
    <div class="container">

        <div class="text-center mb-5">
            <span class="text-success fw-bold">
                NOTRE IMPACT
            </span>

            <h2 class="fw-bold">
                Nous rapprochons les Sénégalais
            </h2>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-road fa-3x text-success mb-3"></i>

                    <h3 class="fw-bold">
                        250 000+
                    </h3>

                    <p class="text-muted">
                        Kilomètres parcourus chaque mois.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-users fa-3x text-success mb-3"></i>

                    <h3 class="fw-bold">
                        15 000+
                    </h3>

                    <p class="text-muted">
                        Voyageurs satisfaits.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-leaf fa-3x text-success mb-3"></i>

                    <h3 class="fw-bold">
                        Éco Responsable
                    </h3>

                    <p class="text-muted">
                        Réduction des émissions grâce au covoiturage.
                    </p>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- POURQUOI NOUS -->
<section class="py-5 bg-light">
    <div class="container">

        <div class="text-center mb-5">
            <span class="text-success fw-bold">
                NOS ATOUTS
            </span>

            <h2 class="fw-bold">
                Pourquoi choisir Yoon bu Gaw ?
            </h2>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-shield-alt fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold">Sécurité</h5>
                    <p class="text-muted">
                        Chauffeurs vérifiés et trajets sécurisés.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-money-bill-wave fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold">Prix transparents</h5>
                    <p class="text-muted">
                        Aucun frais caché.
                    </p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="vision-card text-center">
                    <i class="fas fa-clock fa-3x text-success mb-3"></i>
                    <h5 class="fw-bold">Rapidité</h5>
                    <p class="text-muted">
                        Réservez en quelques clics.
                    </p>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- TEMOIGNAGES -->
<section class="py-5">
    <div class="container">

        <div class="text-center mb-5">
            <span class="text-success fw-bold">
                AVIS CLIENTS
            </span>

            <h2 class="fw-bold">
                Ce qu'ils disent de nous
            </h2>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="vision-card">
                    ⭐⭐⭐⭐⭐
                    <p class="mt-3">
                        Grâce à Yoon bu Gaw je voyage facilement entre Dakar et Saint-Louis.
                    </p>
                    <strong>Awa Ndiaye</strong>
                </div>
            </div>

            <div class="col-md-4">
                <div class="vision-card">
                    ⭐⭐⭐⭐⭐
                    <p class="mt-3">
                        Application rapide et très pratique.
                    </p>
                    <strong>Mamadou Fall</strong>
                </div>
            </div>

            <div class="col-md-4">
                <div class="vision-card">
                    ⭐⭐⭐⭐⭐
                    <p class="mt-3">
                        Excellent service de livraison partout au Sénégal.
                    </p>
                    <strong>Fatou Diop</strong>
                </div>
            </div>

        </div>

    </div>
</section>

<!-- CTA -->
<section class="py-5 text-center text-white"
style="background:linear-gradient(135deg,#007D34,#00a84f);">

    <div class="container">

        <h2 class="fw-bold mb-3">
            Rejoignez Yoon bu Gaw dès aujourd'hui
        </h2>

        <p class="mb-4">
            Simplifiez vos déplacements et profitez de nos services.
        </p>

        <?php if($is_logged_in): ?>
            <a href="#"
               class="btn btn-light btn-lg">
                Accéder à mon espace
            </a>
        <?php else: ?>
            <a href="../auth/login.php"
               class="btn btn-light btn-lg">
                Créer un compte
            </a>
        <?php endif; ?>

    </div>

</section>

<?php include_once('../includes/footer.php'); ?>

</body>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>