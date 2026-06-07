
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoon bu Gaw | La route qui nous rapproche</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="acceuil/style.css">
       
</head>
<body>

<!-- ═══ BANNIÈRE PROMOTIONNELLE ═══ -->
<div class="promo-banner" id="promoBanner">
    <div class="container">
        <div class="promo-inner">
            <div class="promo-left">
                <div class="promo-badge-icon">🎉</div>
                <div>
                    <div class="promo-title">Offre de lancement — 30% de réduction sur votre 1er trajet !</div>
                    <div class="promo-sub">Code : <strong>YOON30</strong> · Valable pour tout nouveau client inscrit</div>
                </div>
            </div>
            <div class="promo-countdown">
                <div class="countdown-unit">
                    <span class="num" id="cdH">12</span>
                    <span class="lbl">Heures</span>
                </div>
                <span class="countdown-sep">:</span>
                <div class="countdown-unit">
                    <span class="num" id="cdM">45</span>
                    <span class="lbl">Min</span>
                </div>
                <span class="countdown-sep">:</span>
                <div class="countdown-unit">
                    <span class="num" id="cdS">00</span>
                    <span class="lbl">Sec</span>
                </div>
            </div>
            <a href="auth/login.php" class="btn-promo">Profiter de l'offre →</a>
            <button class="promo-close" onclick="document.getElementById('promoBanner').style.display='none'" aria-label="Fermer">✕</button>
        </div>
    </div>
</div>

<!-- ═══ NAVBAR ═══ -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="brand-wrap navbar-brand" href="index.php">
            <img src="assets/images/logo.png" alt="Yoon bu Gaw logo" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">Yoon bu <span>Gaw</span></span>
                <span class="brand-tagline">La route qui nous rapproche</span>
            </div>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link active" href="index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="acceuil/services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="acceuil/about.php">À propos</a></li>
                <li class="nav-item"><a class="nav-link" href="acceuil/tarifs.php">Tarifs</a></li>
                <li class="nav-item"><a class="nav-link" href="acceuil/blog.php">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="acceuil/contact.php">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                
                  
               
                    <a href="auth/login.php"    class="btn-connexion">Connexion</a>
                    <a href="auth/traitement_inscription.php" class="btn-inscrire">S'inscrire</a>
              
            </div>
        </div>
    </div>
</nav>
<!-- ═══ HERO ═══ -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-5">
                <span class="hero-badge">Plateforme de transport intelligente</span>
                <h1 class="hero-title">
                    La route qui<br>
                    <span class="green">nous rapproche</span>
                </h1>
                <p class="hero-desc">
                    Yoon bu Gaw vous accompagne au quotidien pour tous vos déplacements, livraisons et locations de véhicules partout au Sénégal.
                </p>
                <div class="d-flex flex-wrap gap-3 mb-4">
                    <button class="btn-primary-hero gated-link" data-href="auth/login.php">
                        Réserver maintenant <i class="fa-solid fa-circle-arrow-right"></i>
                    </button>
                    <button class="btn-secondary-hero gated-link" data-href="auth/login.php">
                        <i class="fa-solid fa-car"></i> Louer un véhicule
                    </button>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-stack">
                        <div class="av"><img src="assets/images/p1.jpg" alt="" onerror="this.style.background='#CBD5E1'"></div>
                        <div class="av"><img src="assets/images/p2.jpg" alt="" onerror="this.style.background='#9CA3AF'"></div>
                        <div class="av"><img src="assets/images/p3.jpg" alt="" onerror="this.style.background='#6B7280'"></div>
                    </div>
                    <span class="fw-bold">+10K <span class="fw-normal text-muted">clients satisfaits</span></span>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="hero-visual">
                    <div class="hero-img-wrap">
                        <img src="assets/images/baner.jpeg" alt="Flotte de véhicules Yoon bu Gaw devant le Monument de la Renaissance Africaine">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══ CORPS PRINCIPAL : services + téléphone ═══ -->
<div class="central-wrap">
    <div class="central-content">

        <!-- NOS SERVICES -->
        <section class="services-section">
            <div class="container">
                <p class="section-label text-center mb-2">Nos services</p>
                <h2 class="section-title text-center"></h2>
                <div class="row g-4">
                    <div class="col-md-3 col-6">
                        <div class="svc-card">
                            <div class="svc-icon ic-green"><i class="fa-solid fa-user-group"></i></div>
                            <h5>Covoiturage</h5>
                            <p>Déplacements sûrs et confortables partout en ville et entre villes.</p>
                            <a href="#" class="svc-link g gated-link" data-href="user/reserver_covoiturage.php">Réserver →</a>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="svc-card">
                            <div class="svc-icon ic-blue"><i class="fa-solid fa-bus"></i></div>
                            <h5>Location de Véhicules</h5>
                            <p>Voyagez en toute sérénité avec nos cars modernes et climatisés.</p>
                            <a href="#" class="svc-link b gated-link" data-href="auth/login.php">Réserver →</a>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="svc-card">
                            <div class="svc-icon ic-orange"><i class="fa-solid fa-taxi"></i></div>
                            <h5>Taxi</h5>
                            <p>Trouvez un taxi rapidement et arrivez à destination en toute sécurité.</p>
                            <a href="#" class="svc-link o gated-link" data-href="auth/login.php">Réserver →</a>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="svc-card">
                            <div class="svc-icon ic-navy"><i class="fa-solid fa-box"></i></div>
                            <h5>Marchandises</h5>
                            <p>Livraison rapide et fiable de vos colis et marchandises partout au Sénégal.</p>
                            <a href="#" class="svc-link n gated-link" data-href="auth/login.php">Expédier →</a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- POURQUOI NOUS CHOISIR -->
        <section class="why-section">
            <div class="container">
                <div class="why-card">
                    <h3>POURQUOI CHOISIR YOON BU GAW ?</h3>
                    <div class="row g-4">
                        <div class="col-md-3 col-6">
                            <div class="why-feature">
                                <div class="why-icon-wrap"><i class="fa-solid fa-shield-halved why-icon"></i></div>
                                <h6>Sécurité garantie</h6>
                                <p>Des chauffeurs qualifiés et vérifiés pour votre sécurité.</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="why-feature">
                                <div class="why-icon-wrap"><i class="fa-solid fa-hand-holding-dollar why-icon"></i></div>
                                <h6>Prix transparents</h6>
                                <p>Des tarifs clairs et compétitifs sans frais cachés.</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="why-feature">
                                <div class="why-icon-wrap"><i class="fa-solid fa-clock-rotate-left why-icon"></i></div>
                                <h6>Suivi en temps réel</h6>
                                <p>Suivez vos trajets et vos livraisons en temps réel.</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6">
                            <div class="why-feature">
                                <div class="why-icon-wrap"><i class="fa-solid fa-credit-card why-icon"></i></div>
                                <h6>Paiement sécurisé</h6>
                                <p>Payez en toute sécurité par Mobile Money ou carte bancaire.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- STATS -->
        <section class="stats-section">
            <div class="container">
                <div class="row g-3 text-center">
                    <div class="col stat-item">
                        <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                        <span class="stat-num">+25 000</span>
                        <div class="stat-lbl">Clients satisfaits</div>
                    </div>
                    <div class="col stat-item">
                        <div class="stat-icon"><i class="fa-solid fa-user-tie"></i></div>
                        <span class="stat-num">+1 200</span>
                        <div class="stat-lbl">Chauffeurs actifs</div>
                    </div>
                    <div class="col stat-item">
                        <div class="stat-icon"><i class="fa-solid fa-car"></i></div>
                        <span class="stat-num">+850</span>
                        <div class="stat-lbl">Véhicules disponibles</div>
                    </div>
                    <div class="col stat-item">
                        <div class="stat-icon"><i class="fa-solid fa-calendar-check"></i></div>
                        <span class="stat-num">+40 000</span>
                        <div class="stat-lbl">Trajets effectués</div>
                    </div>
                    <div class="col stat-item">
                        <div class="stat-icon"><i class="fa-solid fa-truck-fast"></i></div>
                        <span class="stat-num">+5 000</span>
                        <div class="stat-lbl">Livraisons réussies</div>
                    </div>
                </div>
            </div>
        </section>

    </div><!-- /central-content -->

    <!-- MAQUETTE TÉLÉPHONE -->
    <div class="phone-col d-none d-lg-flex">
        <img src="assets/images/phone.png" alt="Application mobile Yoon bu Gaw">
    </div>
</div><!-- /central-wrap -->

<!-- ═══════════════════════════════════════════════
     1. SECTION TÉMOIGNAGES
═══════════════════════════════════════════════ -->
<section class="testimonials-section">
    <div class="container">
        <p class="section-label text-center mb-2">Ce qu'ils disent</p>
        <h2 class="section-title text-center">Avis de nos clients <span style="color:var(--green)">satisfaits</span></h2>
        <div class="row g-4">
            <!-- Témoignage 1 -->
            <div class="col-md-4">
                <div class="testi-card">
                    <i class="fa-solid fa-quote-right testi-quote-icon"></i>
                    <div class="testi-stars">★★★★★</div>
                    <p class="testi-text">
                        "Yoon bu Gaw a vraiment changé ma façon de me déplacer à Dakar. Rapide, fiable et les chauffeurs sont toujours très courtois. Je recommande vivement !"
                    </p>
                    <div class="testi-author">
                        <div class="testi-av">AF</div>
                        <div>
                            <div class="testi-name">Aminata Faye <span class="testi-tag">Covoiturage</span></div>
                            <div class="testi-role">Employée de banque, Dakar</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Témoignage 2 -->
            <div class="col-md-4">
                <div class="testi-card">
                    <i class="fa-solid fa-quote-right testi-quote-icon"></i>
                    <div class="testi-stars">★★★★★</div>
                    <p class="testi-text">
                        "J'utilise le service de livraison de marchandises pour mon commerce. Les délais sont respectés et les colis arrivent toujours en parfait état. Très professionnel."
                    </p>
                    <div class="testi-author">
                        <div class="testi-av">MD</div>
                        <div>
                            <div class="testi-name">Moustapha Diallo <span class="testi-tag">Livraison</span></div>
                            <div class="testi-role">Commerçant, Thiès</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Témoignage 3 -->
            <div class="col-md-4">
                <div class="testi-card">
                    <i class="fa-solid fa-quote-right testi-quote-icon"></i>
                    <div class="testi-stars">★★★★☆</div>
                    <p class="testi-text">
                        "Le service taxi est excellent. J'ai pu réserver en quelques secondes depuis l'application. Les prix sont transparents et il n'y a jamais de mauvaises surprises."
                    </p>
                    <div class="testi-author">
                        <div class="testi-av">SN</div>
                        <div>
                            <div class="testi-name">Sokhna Ndiaye <span class="testi-tag">Taxi</span></div>
                            <div class="testi-role">Étudiante, Saint-Louis</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Témoignage 4 -->
            <div class="col-md-4">
                <div class="testi-card">
                    <i class="fa-solid fa-quote-right testi-quote-icon"></i>
                    <div class="testi-stars">★★★★★</div>
                    <p class="testi-text">
                        "Nous avons loué un car pour notre séminaire d'entreprise. Tout s'est passé parfaitement : véhicule climatisé, chauffeur ponctuel. Nous repasserons !"
                    </p>
                    <div class="testi-author">
                        <div class="testi-av">IB</div>
                        <div>
                            <div class="testi-name">Ibrahima Ba <span class="testi-tag" style="background:#EEF2FF;color:#3730a3">Location</span></div>
                            <div class="testi-role">Directeur RH, Ziguinchor</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Témoignage 5 -->
            <div class="col-md-4">
                <div class="testi-card">
                    <i class="fa-solid fa-quote-right testi-quote-icon"></i>
                    <div class="testi-stars">★★★★★</div>
                    <p class="testi-text">
                        "Le suivi en temps réel de ma livraison est une fonctionnalité que j'adore. Je sais exactement où se trouve mon colis à chaque instant. Bravo à toute l'équipe !"
                    </p>
                    <div class="testi-author">
                        <div class="testi-av">FK</div>
                        <div>
                            <div class="testi-name">Fatou Konaté <span class="testi-tag">Livraison</span></div>
                            <div class="testi-role">Artisane, Kaolack</div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Témoignage 6 -->
            <div class="col-md-4">
                <div class="testi-card">
                    <i class="fa-solid fa-quote-right testi-quote-icon"></i>
                    <div class="testi-stars">★★★★★</div>
                    <p class="testi-text">
                        "Grâce au covoiturage Yoon bu Gaw, je fais Dakar-Touba chaque semaine en toute sécurité. L'économie réalisée est significative. Je n'utilise plus que ça !"
                    </p>
                    <div class="testi-author">
                        <div class="testi-av">OS</div>
                        <div>
                            <div class="testi-name">Ousmane Sarr <span class="testi-tag">Covoiturage</span></div>
                            <div class="testi-role">Enseignant, Touba</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════
     2. SECTION BLOG / ACTUALITÉS
═══════════════════════════════════════════════ -->
<section class="blog-section">
    <div class="container">
        <p class="section-label text-center mb-2">Blog & Actualités</p>
        <h2 class="section-title text-center">Les dernières <span style="color:var(--green)">nouvelles</span></h2>
        <div class="row g-4">
            <!-- Article 1 -->
            <div class="col-md-4">
                <div class="blog-card">
                    <div class="blog-img" style="background: linear-gradient(135deg, #007D34 0%, #005f28 100%);">
                        <i class="fa-solid fa-road blog-img-icon"></i>
                        <span class="blog-cat">Transport</span>
                    </div>
                    <div class="blog-body">
                        <div class="blog-meta">
                            <span><i class="fa-regular fa-calendar"></i> 28 mai 2026</span>
                            <span><i class="fa-regular fa-clock"></i> 4 min</span>
                        </div>
                        <h3 class="blog-title">Yoon bu Gaw étend sa couverture à Ziguinchor et Kolda</h3>
                        <p class="blog-excerpt">Bonne nouvelle pour le sud du Sénégal ! Nos services de covoiturage et de livraison sont désormais disponibles dans deux nouvelles villes.</p>
                        <a href="#" class="blog-link">Lire l'article →</a>
                    </div>
                </div>
            </div>
            <!-- Article 2 -->
            <div class="col-md-4">
                <div class="blog-card">
                    <div class="blog-img" style="background: linear-gradient(135deg, #003366 0%, #001529 100%);">
                        <i class="fa-solid fa-mobile-screen-button blog-img-icon"></i>
                        <span class="blog-cat">Application</span>
                    </div>
                    <div class="blog-body">
                        <div class="blog-meta">
                            <span><i class="fa-regular fa-calendar"></i> 20 mai 2026</span>
                            <span><i class="fa-regular fa-clock"></i> 3 min</span>
                        </div>
                        <h3 class="blog-title">Nouvelle mise à jour de l'application : suivi GPS amélioré</h3>
                        <p class="blog-excerpt">La version 2.4 de l'application Yoon bu Gaw apporte un suivi GPS en temps réel encore plus précis et une interface repensée pour plus de fluidité.</p>
                        <a href="#" class="blog-link">Lire l'article →</a>
                    </div>
                </div>
            </div>
            <!-- Article 3 -->
            <div class="col-md-4">
                <div class="blog-card">
                    <div class="blog-img" style="background: linear-gradient(135deg, #FF9800 0%, #e65100 100%);">
                        <i class="fa-solid fa-handshake blog-img-icon"></i>
                        <span class="blog-cat">Partenariat</span>
                    </div>
                    <div class="blog-body">
                        <div class="blog-meta">
                            <span><i class="fa-regular fa-calendar"></i> 12 mai 2026</span>
                            <span><i class="fa-regular fa-clock"></i> 5 min</span>
                        </div>
                        <h3 class="blog-title">Yoon bu Gaw s'associe à Wave pour les paiements mobiles</h3>
                        <p class="blog-excerpt">Un nouveau partenariat stratégique avec Wave Sénégal pour simplifier encore davantage vos paiements. Réglez vos courses en un clic depuis votre portefeuille Wave.</p>
                        <a href="#" class="blog-link">Lire l'article →</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="acceuil/blog.php" class="btn-secondary-hero" style="display:inline-flex;">
                Voir tous les articles <i class="fa-solid fa-arrow-right ms-2"></i>
            </a>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════
     3. SECTION PARTENAIRES / LOGOS
═══════════════════════════════════════════════ -->
<section class="partners-section">
    <div class="container">
        <p class="section-label text-center mb-2">Ils nous font confiance</p>
        <h2 class="section-title text-center mb-4" style="font-size:1.25rem;">Nos partenaires <span style="color:var(--green)">& intégrations</span></h2>
        <div class="partners-track-wrap">
            <div class="partners-track">
                <!-- Série 1 -->
                <div class="partner-logo"><i class="fa-solid fa-mobile-alt"></i> Orange Money</div>
                <div class="partner-logo"><i class="fa-solid fa-wave-square"></i> Wave</div>
                <div class="partner-logo"><i class="fa-brands fa-cc-visa"></i> Visa</div>
                <div class="partner-logo"><i class="fa-solid fa-building-columns"></i> CBAO</div>
                <div class="partner-logo"><i class="fa-solid fa-truck"></i> DHL Sénégal</div>
                <div class="partner-logo"><i class="fa-solid fa-gas-pump"></i> Total Energies</div>
                <div class="partner-logo"><i class="fa-solid fa-hotel"></i> Terrou-Bi</div>
                <div class="partner-logo"><i class="fa-solid fa-plane"></i> Air Sénégal</div>
                <!-- Série 2 (copie pour boucle infinie) -->
                <div class="partner-logo"><i class="fa-solid fa-mobile-alt"></i> Orange Money</div>
                <div class="partner-logo"><i class="fa-solid fa-wave-square"></i> Wave</div>
                <div class="partner-logo"><i class="fa-brands fa-cc-visa"></i> Visa</div>
                <div class="partner-logo"><i class="fa-solid fa-building-columns"></i> CBAO</div>
                <div class="partner-logo"><i class="fa-solid fa-truck"></i> DHL Sénégal</div>
                <div class="partner-logo"><i class="fa-solid fa-gas-pump"></i> Total Energies</div>
                <div class="partner-logo"><i class="fa-solid fa-hotel"></i> Terrou-Bi</div>
                <div class="partner-logo"><i class="fa-solid fa-plane"></i> Air Sénégal</div>
            </div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════
     4. SECTION TÉLÉCHARGEMENT APPLICATION
═══════════════════════════════════════════════ -->
<section class="app-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 app-content">
                <span class="app-badge">📱 Application mobile</span>
                <h2 class="app-title">
                    Toute la mobilité<br>
                    sénégalaise dans<br>
                    votre <span class="green">poche</span>
                </h2>
                <p class="app-desc">
                    Téléchargez l'application Yoon bu Gaw et gérez tous vos déplacements, livraisons et locations depuis votre smartphone. Disponible sur iOS et Android.
                </p>
                <ul class="app-features">
                    <li><i class="fa-solid fa-check-circle"></i> Réservation en moins de 30 secondes</li>
                    <li><i class="fa-solid fa-check-circle"></i> Suivi GPS en temps réel de vos trajets</li>
                    <li><i class="fa-solid fa-check-circle"></i> Paiement sécurisé : Wave, Orange Money, carte</li>
                    <li><i class="fa-solid fa-check-circle"></i> Historique complet et factures téléchargeables</li>
                    <li><i class="fa-solid fa-check-circle"></i> Notifications instantanées à chaque étape</li>
                </ul>
                <div class="app-store-btns">
                    <a href="#" class="store-btn">
                        <i class="fa-brands fa-apple"></i>
                        <div class="store-btn-text">
                            <span class="sub">Télécharger sur</span>
                            <span class="main">App Store</span>
                        </div>
                    </a>
                    <a href="#" class="store-btn">
                        <i class="fa-brands fa-google-play"></i>
                        <div class="store-btn-text">
                            <span class="sub">Disponible sur</span>
                            <span class="main">Google Play</span>
                        </div>
                    </a>
                </div>
                <div class="qr-wrap mt-4">
                    <div class="qr-box">
                        <!-- QR code SVG simplifié -->
                        <svg viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
                            <rect x="2" y="2" width="20" height="20" fill="none" stroke="#000" stroke-width="2"/>
                            <rect x="6" y="6" width="12" height="12" fill="#000"/>
                            <rect x="28" y="2" width="20" height="20" fill="none" stroke="#000" stroke-width="2"/>
                            <rect x="32" y="6" width="12" height="12" fill="#000"/>
                            <rect x="2" y="28" width="20" height="20" fill="none" stroke="#000" stroke-width="2"/>
                            <rect x="6" y="32" width="12" height="12" fill="#000"/>
                            <rect x="28" y="28" width="4" height="4" fill="#000"/>
                            <rect x="34" y="28" width="4" height="4" fill="#000"/>
                            <rect x="40" y="28" width="8" height="4" fill="#000"/>
                            <rect x="28" y="34" width="8" height="4" fill="#000"/>
                            <rect x="38" y="34" width="10" height="4" fill="#000"/>
                            <rect x="28" y="40" width="4" height="8" fill="#000"/>
                            <rect x="34" y="44" width="8" height="4" fill="#000"/>
                            <rect x="44" y="40" width="4" height="8" fill="#000"/>
                        </svg>
                    </div>
                    <div class="qr-text">
                        <div class="qr-title">Scanner pour télécharger</div>
                        <div class="qr-sub">Pointez votre appareil photo sur le code QR<br>pour accéder directement à l'application</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 d-flex justify-content-center">
                <div class="app-phones-wrap">
                    <div class="app-phone-main">
                        <div class="app-phone-screen">
                             <img src="assets/images/phone.png" alt="Application mobile Yoon bu Gaw" width='176px'>
                        </div>
                    </div>
                    <div class="app-phone-sec">
                        <div class="app-phone-screen">
                            <img src="assets/images/phone.png" alt="Application mobile Yoon bu Gaw" width='170px'>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>



<!-- ═══ FOOTER ═══ -->
<?php include_once('includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const isLoggedIn = <?php echo json_encode($is_logged_in); ?>;

    document.querySelectorAll('.gated-link').forEach(el => {
        el.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('data-href');
            if (isLoggedIn) {
                if (url) window.location.href = url;
            } else {
                alert('Vous devez être connecté pour accéder à ce service.');
                window.location.href = 'auth/login.php';
            }
        });
    });

    // ── Compte à rebours bannière promo ──
    (function() {
        let total = 12 * 3600 + 45 * 60; // 12h45m en secondes
        const h = document.getElementById('cdH');
        const m = document.getElementById('cdM');
        const s = document.getElementById('cdS');
        if (!h) return;
        setInterval(() => {
            if (total <= 0) { total = 0; return; }
            total--;
            const hh = Math.floor(total / 3600);
            const mm = Math.floor((total % 3600) / 60);
            const ss = total % 60;
            h.textContent = String(hh).padStart(2,'0');
            m.textContent = String(mm).padStart(2,'0');
            s.textContent = String(ss).padStart(2,'0');
        }, 1000);
    })();
</script>
</body>
</html>
