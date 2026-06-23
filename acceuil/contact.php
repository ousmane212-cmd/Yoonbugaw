<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact | Yoon bu Gaw</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root{
            --green:#005f28;
            --navy:#001529;
            --bg:#f8f9fa;
        }

        body{
            font-family:'Plus Jakarta Sans',sans-serif;
            color:var(--navy);
            background:#fff;
        }

        /* HERO */
        .contact-hero{
            background: linear-gradient(135deg, var(--green), #00a84f);
            color:#fff;
            padding:80px 20px;
            text-align:center;
        }

        .contact-hero h1{
            font-weight:800;
        }

        /* CARD */
        .contact-card{
            border:none;
            border-radius:20px;
            box-shadow:0 10px 30px rgba(0,0,0,0.08);
        }

        .info-box{
            background:var(--bg);
            border-radius:15px;
            padding:15px;
            margin-bottom:15px;
        }

        .icon-box{
            width:45px;
            height:45px;
            display:flex;
            align-items:center;
            justify-content:center;
            border-radius:10px;
            background:var(--green);
            color:#fff;
            margin-right:12px;
        }

        .btn-send{
            background:var(--green);
            color:#fff;
            font-weight:700;
            padding:10px 20px;
            border-radius:10px;
        }

        .btn-send:hover{
            background:#00642a;
        }

        iframe{
            border-radius:15px;
        }

        .faq-item{
            border:none;
            border-radius:12px;
            margin-bottom:10px;
        }
    </style>
</head>

<body>

<?php include_once('../includes/header.php'); ?>

<!-- HERO -->
<section class="contact-hero">
    <h1>Contactez-nous</h1>
    <p class="mb-0">Une équipe disponible 24h/24 pour vous accompagner</p>
</section>

<!-- CONTACT SECTION -->
<section class="container py-5">

    <div class="row g-5">

        <!-- INFOS -->
        <div class="col-lg-4">

            <h3 class="fw-bold mb-4">Nos coordonnées</h3>

            <div class="info-box d-flex align-items-center">
                <div class="icon-box"><i class="fa-solid fa-phone"></i></div>
                <div>
                    <small class="text-muted">Téléphone</small><br>
                    <strong>+221 33 800 00 00</strong>
                </div>
            </div>

            <div class="info-box d-flex align-items-center">
                <div class="icon-box"><i class="fa-solid fa-envelope"></i></div>
                <div>
                    <small class="text-muted">Email</small><br>
                    <strong>support@yoonbugaw.sn</strong>
                </div>
            </div>

            <div class="info-box d-flex align-items-center">
                <div class="icon-box"><i class="fa-solid fa-location-dot"></i></div>
                <div>
                    <small class="text-muted">Adresse</small><br>
                    <strong>Dakar, Sénégal</strong>
                </div>
            </div>

            <div class="mt-4">
                <h5 class="fw-bold">Nous suivre</h5>
                <div class="d-flex gap-2 mt-2">
                    <a class="btn btn-outline-success btn-sm"><i class="fab fa-facebook"></i></a>
                    <a class="btn btn-outline-success btn-sm"><i class="fab fa-instagram"></i></a>
                    <a class="btn btn-outline-success btn-sm"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

        </div>

        <!-- FORM -->
        <div class="col-lg-8">

            <div class="card contact-card p-4">

                <h3 class="fw-bold mb-3">Envoyez un message</h3>

                <form method="POST">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Sujet</label>
                            <input type="text" class="form-control">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" rows="5"></textarea>
                        </div>

                        <div class="col-12">
                            <button class="btn btn-send w-100">Envoyer le message</button>
                        </div>
                    </div>

                </form>

            </div>

        </div>

    </div>

</section>

<!-- MAP -->
<section class="container mb-5">
    <h3 class="fw-bold mb-3">Notre localisation</h3>
    <iframe 
        src="https://www.google.com/maps/embed?pb=!1m18..."
        width="100%" height="300" style="border:0;" allowfullscreen>
    </iframe>
</section>




<?php include_once('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>