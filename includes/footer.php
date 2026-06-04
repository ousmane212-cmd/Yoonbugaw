<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoon bu Gaw | La route qui nous rapproche</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<footer class="footer">

    <div class="container">

        <div class="row g-5">

            <!-- Logo -->
            <div class="col-lg-4">
                <img src="assets/images/logo.png" alt="Yoon bu Gaw" class="footer-logo">

                <p class="footer-about">
                    Yoon bu Gaw est votre plateforme de mobilité intelligente au Sénégal.
                    Réservez vos trajets, expédiez vos colis et louez des véhicules en toute simplicité.
                </p>

                <div class="footer-social">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-x-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin-in"></i></a>
                </div>
            </div>

            <!-- Liens -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Navigation</h5>
                <ul class="footer-links">
                    <li><a href="index.php">Accueil</a></li>
                    <li><a href="acceuil/services.php">Services</a></li>
                    <li><a href="acceuil/about.php">À propos</a></li>
                    <li><a href="acceuil/blog.php">Blog</a></li>
                    <li><a href="acceuil/contact.php">Contact</a></li>
                </ul>
            </div>

            <!-- Services -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Services</h5>
                <ul class="footer-links">
                    <li><a href="#">Covoiturage</a></li>
                    <li><a href="#">Taxi</a></li>
                    <li><a href="#">Location</a></li>
                    <li><a href="#">Livraison</a></li>
                </ul>
            </div>

            <!-- Contact -->
            <div class="col-lg-4">
                <h5 class="footer-title">Contact</h5>

                <div class="footer-contact mb-3">
                    <i class="fas fa-map-marker-alt"></i>
                    Dakar, Sénégal
                </div>

                <div class="footer-contact mb-3">
                    <i class="fas fa-phone"></i>
                    +221 77 000 00 00
                </div>

                <div class="footer-contact mb-4">
                    <i class="fas fa-envelope"></i>
                    contact@yoonbugaw.sn
                </div>

                <div class="footer-newsletter">
                    <h6 class="fw-bold mb-3">Newsletter</h6>

                    <form class="d-flex gap-2">
                        <input type="email"
                               class="form-control"
                               placeholder="Votre email">

                        <button type="submit">
                            OK
                        </button>
                    </form>
                </div>
            </div>

        </div>

        <div class="footer-bottom">
            © 2026 Yoon bu Gaw • Tous droits réservés • La route qui nous rapproche 🇸🇳
        </div>

    </div>

</footer>
<style>
    /* FOOTER PREMIUM */
.footer {
    background: linear-gradient(135deg, #001529 0%, #002a4a 100%);
    color: #fff;
    padding-top: 70px;
}

.footer-logo {
    width: 70px;
    margin-bottom: 15px;
}

.footer-about {
    color: rgba(255,255,255,.7);
    line-height: 1.8;
    font-size: .9rem;
}

.footer-title {
    font-size: 1rem;
    font-weight: 800;
    margin-bottom: 20px;
    color: #fff;
}

.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-links li {
    margin-bottom: 12px;
}

.footer-links a {
    color: rgba(255,255,255,.7);
    text-decoration: none;
    transition: .3s;
}

.footer-links a:hover {
    color: #4ade80;
    padding-left: 5px;
}

.footer-contact {
    color: rgba(255,255,255,.7);
    font-size: .9rem;
}

.footer-contact i {
    color: #4ade80;
    width: 25px;
}

.footer-social {
    display: flex;
    gap: 12px;
    margin-top: 20px;
}

.footer-social a {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: rgba(255,255,255,.08);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    transition: .3s;
}

.footer-social a:hover {
    background: #007D34;
    transform: translateY(-3px);
}

.footer-newsletter {
    background: rgba(255,255,255,.05);
    padding: 20px;
    border-radius: 15px;
}

.footer-newsletter input {
    border: none;
    border-radius: 10px;
    padding: 12px;
}

.footer-newsletter button {
    background: #007D34;
    border: none;
    color: #fff;
    border-radius: 10px;
    padding: 12px 18px;
    font-weight: 700;
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,.1);
    margin-top: 50px;
    padding: 20px 0;
    text-align: center;
    color: rgba(255,255,255,.6);
    font-size: .85rem;
}
</style>