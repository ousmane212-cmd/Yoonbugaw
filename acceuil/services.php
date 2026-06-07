<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nos Services | Yoon bu Gaw</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="services.css">
</head>
<body>

<?php include_once('../includes/header.php'); ?>

<!-- =============================================
     HERO
     ============================================= -->
<section class="hero">
  <div class="hero-grid"></div>
  <div class="hero-orb hero-orb-1"></div>
  <div class="hero-orb hero-orb-2"></div>
  <div class="hero-lines"></div>

  <div class="hero-inner">
    <div class="hero-content fade-up">
      <div class="hero-tag">
        <i class="fa-solid fa-location-dot" style="color:var(--gold-l);font-size:.7rem;"></i>
        Mobilité au Sénégal
      </div>

      <h1 class="hero-title">
        Voyagez avec<br>
        <em>élégance</em> &amp;<br>
        confiance
      </h1>

      <p class="hero-desc">
        Yoon bu Gaw simplifie vos déplacements urbains et interurbains partout au Sénégal — rapidement, sûrement, à prix juste.
      </p>

      <div class="hero-cta">
    <button class="btn-primary-gold" onclick="window.location.href='../auth/login.php'">
  Réserver maintenant
</button>
        <button class="btn-ghost-white">Découvrir nos services</button>
      </div>

      <div class="hero-badges-row">
        <div class="h-badge"><i class="fa-solid fa-shield-halved"></i>Chauffeurs vérifiés</div>
        <div class="h-badge"><i class="fa-solid fa-bolt"></i>Réservation instantanée</div>
        <div class="h-badge"><i class="fa-solid fa-headset"></i>Support 24/7</div>
      </div>
    </div>

    <div class="hero-visual">
      <div class="hero-card-stack">
        <!-- Floating badges -->
        <div class="float-badge float-badge-1">
          <i class="fa-solid fa-star"></i>
          <span>Note 4.9 / 5</span>
        </div>
        <div class="float-badge float-badge-2">
          <i class="fa-solid fa-shield-halved"></i>
          <span>100% sécurisé</span>
        </div>

        <div class="hero-main-card">
          <p class="hero-card-label">Satisfaction client</p>
          <div class="hero-rating-big">4.9<span>/5</span></div>
          <div class="hero-stars-row">★★★★★</div>
          <p class="hero-rating-sub">Basé sur 10 000+ avis vérifiés</p>

          <hr class="hero-card-divider">

          <div class="hero-stat-row">
            <div class="hero-stat-item">
              <strong id="h1">25K+</strong>
              <small>Clients</small>
            </div>
            <div class="hero-stat-item">
              <strong id="h2">1 200+</strong>
              <small>Chauffeurs</small>
            </div>
            <div class="hero-stat-item">
              <strong id="h3">40K+</strong>
              <small>Trajets</small>
            </div>
          </div>
        </div>
        <div class="hero-back-card"></div>
      </div>
    </div>
  </div>
</section>

<!-- =============================================
     STATS BAND
     ============================================= -->
<div class="stats-band">
  <div class="stats-inner">
    <div class="stat-item">
      <div class="stat-num" id="s1">0</div>
      <div class="stat-label">Clients satisfaits</div>
    </div>
    <div class="stat-item">
      <div class="stat-num" id="s2">0</div>
      <div class="stat-label">Chauffeurs actifs</div>
    </div>
    <div class="stat-item">
      <div class="stat-num" id="s3">0</div>
      <div class="stat-label">Trajets effectués</div>
    </div>
    <div class="stat-item">
      <div class="stat-num" id="s4">0</div>
      <div class="stat-label">Villes desservies</div>
    </div>
  </div>
</div>

<!-- =============================================
     SERVICES
     ============================================= -->
<section class="section services-section">
  <div class="section-inner">
    <div class="section-header centered fade-up">
      <p class="label-caps">Nos offres</p>
      <h2 class="section-title">Choisissez votre <em>service</em></h2>
      <p class="section-subtitle">Quatre solutions de mobilité adaptées à tous vos besoins, partout au Sénégal.</p>
    </div>

    <div class="services-grid fade-up">

      <!-- Covoiturage -->
      <div class="service-card">
        <div class="card-bg-num">01</div>
        <div class="card-icon-line">
          <div class="card-icon-wrap" style="background:var(--emerald);">
            <i class="fa-solid fa-user-group"></i>
          </div>
          <span class="card-icon-label">Économique</span>
        </div>
        <h3 class="card-title">Covoiturage</h3>
        <p class="card-desc">Partagez vos trajets avec d'autres voyageurs. Économique, convivial et écologique pour Dakar–Thiès, Dakar–Saint-Louis et bien d'autres liaisons.</p>
        <ul class="card-features">
          <li>Paiement sécurisé en ligne ou mobile money</li>
          <li>Suivi GPS en temps réel</li>
          <li>Notation bidirectionnelle passager / chauffeur</li>
        </ul>
        <button class="btn-card" onclick="window.location.href='../auth/login.php'">
          Trouver un trajet
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>

      <!-- Location véhicules -->
      <div class="service-card">
        <div class="card-bg-num">02</div>
        <div class="card-icon-line">
          <div class="card-icon-wrap" style="background:#1565C0;">
            <i class="fa-solid fa-bus-simple"></i>
          </div>
          <span class="card-icon-label">Groupes & Entreprises</span>
        </div>
        <h3 class="card-title">Location de Véhicules</h3>
        <p class="card-desc">Bus pour événements, sorties d'entreprise ou voyages de groupe. Véhicules modernes avec chauffeurs professionnels certifiés, clé en main.</p>
        <ul class="card-features">
          <li>Devis gratuit sous 24 heures</li>
          <li>Flotte de 50+ véhicules disponibles</li>
          <li>Chauffeur professionnel inclus</li>
        </ul>
        <button class="btn-card" onclick="window.location.href='../auth/login.php'">
          Demander un devis
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>

      <!-- Taxi -->
      <div class="service-card">
        <div class="card-bg-num">03</div>
        <div class="card-icon-line">
          <div class="card-icon-wrap" style="background:#C45200;">
            <i class="fa-solid fa-taxi"></i>
          </div>
          <span class="card-icon-label">Confort & Rapidité</span>
        </div>
        <h3 class="card-title">Réservation de Taxi</h3>
        <p class="card-desc">Commandez un taxi privé instantanément. Prix fixe connu à l'avance, aucun marchandage — confort, ponctualité et sécurité garantis.</p>
        <ul class="card-features">
          <li>Prise en charge en moins de 5 minutes</li>
          <li>Tarif affiché avant confirmation</li>
          <li>Véhicule climatisé et propre</li>
        </ul>
        <button class="btn-card" onclick="window.location.href='../auth/login.php'">
          Commander un taxi
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>

      <!-- Livraison -->
      <div class="service-card">
        <div class="card-bg-num">04</div>
        <div class="card-icon-line">
          <div class="card-icon-wrap" style="background:var(--ink);">
            <i class="fa-solid fa-box-open"></i>
          </div>
          <span class="card-icon-label">Livraison Express</span>
        </div>
        <h3 class="card-title">Livraison & Marchandises</h3>
        <p class="card-desc">Expédiez colis, meubles ou marchandises lourdes. Suivi en temps réel depuis l'application, partout au Sénégal, avec assurance incluse.</p>
        <ul class="card-features">
          <li>Assurance colis incluse d'office</li>
          <li>Livraison J+1 disponible</li>
          <li>Notification SMS à la livraison</li>
        </ul>
        <button class="btn-card" onclick="window.location.href='../auth/login.php'">
          Expédier un colis
          <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>

    </div>
  </div>
</section>

<!-- =============================================
     AVANTAGES
     ============================================= -->
<section class="section avantages-section">
  <div class="section-inner">
    <div class="section-header centered fade-up">
      <p class="label-caps">Nos avantages</p>
      <h2 class="section-title">Pourquoi choisir <em>Yoon bu Gaw</em> ?</h2>
    </div>
    <div class="features-grid fade-up">
      <div class="feature-box">
        <div class="feature-icon-wrap"><i class="fa-solid fa-shield-halved"></i></div>
        <h5>Sécurité</h5>
        <p>Chauffeurs vérifiés, assurés et notés par la communauté à chaque trajet.</p>
      </div>
      <div class="feature-box">
        <div class="feature-icon-wrap"><i class="fa-solid fa-bolt"></i></div>
        <h5>Rapidité</h5>
        <p>Réservation en quelques secondes depuis votre téléphone, n'importe où.</p>
      </div>
      <div class="feature-box">
        <div class="feature-icon-wrap"><i class="fa-solid fa-wallet"></i></div>
        <h5>Prix Justes</h5>
        <p>Tarifs transparents, affichés avant confirmation — aucune surprise.</p>
      </div>
      <div class="feature-box">
        <div class="feature-icon-wrap"><i class="fa-solid fa-headset"></i></div>
        <h5>Support 24/7</h5>
        <p>Notre équipe est disponible à toute heure pour vous accompagner.</p>
      </div>
      <div class="feature-box">
        <div class="feature-icon-wrap"><i class="fa-solid fa-mobile-screen-button"></i></div>
        <h5>Application Mobile</h5>
        <p>Gérez tout depuis votre smartphone — iOS et Android, simple et fluide.</p>
      </div>
    </div>
  </div>
</section>

<!-- =============================================
     COMMENT ÇA FONCTIONNE
     ============================================= -->
<section class="section process-section">
  <div class="section-inner">
    <div class="section-header centered fade-up">
      <p class="label-caps">Processus</p>
      <h2 class="section-title">Comment ça <em>fonctionne</em> ?</h2>
      <p class="section-subtitle">Quatre étapes simples pour voyager ou expédier en toute sérénité.</p>
    </div>
    <div class="steps-row fade-up">
      <div class="step-item">
        <div class="step-circle">1</div>
        <div class="step-icon-sm"><i class="fa-solid fa-mobile-screen-button"></i></div>
        <h5>Choisissez un service</h5>
        <p>Taxi, covoiturage, location ou livraison selon vos besoins du moment.</p>
      </div>
      <div class="step-item">
        <div class="step-circle">2</div>
        <div class="step-icon-sm"><i class="fa-solid fa-map-location-dot"></i></div>
        <h5>Indiquez votre trajet</h5>
        <p>Saisissez votre point de départ et votre destination en quelques secondes.</p>
      </div>
      <div class="step-item">
        <div class="step-circle">3</div>
        <div class="step-icon-sm"><i class="fa-solid fa-calendar-check"></i></div>
        <h5>Confirmez &amp; payez</h5>
        <p>Réservez en un clic, paiement mobile money, Wave ou carte bancaire.</p>
      </div>
      <div class="step-item">
        <div class="step-circle">4</div>
        <div class="step-icon-sm"><i class="fa-solid fa-car-side"></i></div>
        <h5>Profitez du voyage</h5>
        <p>Voyagez sereinement avec nos chauffeurs professionnels et certifiés.</p>
      </div>
    </div>
  </div>
</section>

<!-- =============================================
     VILLES
     ============================================= -->
<section class="section villes-section">
  <div class="section-inner">
    <div class="villes-layout">
      <div class="fade-up">
        <p class="label-caps" style="color:var(--gold);">Couverture nationale</p>
        <h2 class="section-title" style="color:#fff;margin-top:10px;">Nos villes <em style="color:var(--gold-l);">desservies</em></h2>
        <p class="section-subtitle" style="text-align:left;">De Dakar à Ziguinchor, notre réseau s'étend à travers tout le Sénégal. D'autres villes rejoignent la plateforme chaque mois.</p>

        <div class="cities-grid" style="margin-top:36px;">
          <div class="city-card"><span>📍</span><strong>Dakar</strong></div>
          <div class="city-card"><span>📍</span><strong>Thiès</strong></div>
          <div class="city-card"><span>📍</span><strong>Saint-Louis</strong></div>
          <div class="city-card"><span>📍</span><strong>Kaolack</strong></div>
          <div class="city-card"><span>📍</span><strong>Touba</strong></div>
          <div class="city-card"><span>📍</span><strong>Ziguinchor</strong></div>
          <div class="city-card"><span>📍</span><strong>Mbour</strong></div>
          <div class="city-card"><span>📍</span><strong>Tambacounda</strong></div>
        </div>
        <p style="color:rgba(255,255,255,.35);font-size:.8rem;margin-top:16px;">
          <i class="fa-solid fa-circle-info" style="margin-right:6px;color:var(--gold);font-size:.75rem;"></i>
          Et bien d'autres villes en cours d'ajout…
        </p>
      </div>

      <div class="map-placeholder fade-up">
        <div class="map-pin" style="top:30%;left:28%;">📍</div>
        <div class="map-pin" style="top:45%;left:40%;animation-delay:-.5s;">📍</div>
        <div class="map-pin" style="top:20%;left:55%;animation-delay:-1s;">📍</div>
        <div class="map-pin" style="top:60%;left:30%;animation-delay:-1.5s;">📍</div>
        <div class="map-pin" style="top:55%;left:60%;animation-delay:-2s;">📍</div>
        <span style="font-size:1rem;color:rgba(255,255,255,.3);position:relative;z-index:1;">Carte interactive</span>
      </div>
    </div>
  </div>
</section>

<!-- =============================================
     PARTENAIRES
     ============================================= -->
<section class="partners-section fade-up">
  <div class="partners-inner">
    <div class="partners-label-line">
      <span class="label-caps" style="color:var(--text-pale);white-space:nowrap;">Ils nous font confiance</span>
    </div>
    <div class="partners-logos">
      <div class="partner-pill"><i class="fa-solid fa-building-columns"></i>Wave</div>
      <div class="partner-pill"><i class="fa-solid fa-mobile"></i>Orange Money</div>
      <div class="partner-pill"><i class="fa-solid fa-briefcase"></i>Free Money</div>
      <div class="partner-pill"><i class="fa-solid fa-shield"></i>BNDE</div>
      <div class="partner-pill"><i class="fa-solid fa-landmark"></i>Ministère du Transport</div>
    </div>
  </div>
</section>

<!-- =============================================
     À PROPOS
     ============================================= -->
<section class="section about-section">
  <div class="section-inner">
    <div class="about-layout">
      <div class="fade-up">
        <div class="about-badge">
          <i class="fa-solid fa-seedling"></i>
          À propos de nous
        </div>
        <h2 class="about-title">
          Yoon bu <em>Gaw</em>,<br>
          la route qui nous<br>
          rapproche
        </h2>
        <p class="about-text">
          Yoon bu Gaw est une plateforme sénégalaise de mobilité et de transport conçue pour faciliter les déplacements des particuliers et des entreprises partout au Sénégal.
        </p>
        <p class="about-text">
          Que ce soit pour un trajet en taxi, du covoiturage, la location de véhicules ou le transport de marchandises, nous mettons à votre disposition des solutions modernes, sécurisées et accessibles à tous.
        </p>

        <div class="about-stats-row">
          <div class="a-stat">
            <div class="a-stat-num">25K+</div>
            <div class="a-stat-label">Clients actifs</div>
          </div>
          <div class="a-stat">
            <div class="a-stat-num">1 200+</div>
            <div class="a-stat-label">Chauffeurs</div>
          </div>
          <div class="a-stat">
            <div class="a-stat-num">40K+</div>
            <div class="a-stat-label">Trajets réalisés</div>
          </div>
        </div>

        <div class="trust-badges">
          <span class="trust-badge"><i class="fa-solid fa-certificate"></i>Entreprise certifiée</span>
          <span class="trust-badge"><i class="fa-solid fa-lock"></i>Paiement sécurisé</span>
          <span class="trust-badge"><i class="fa-solid fa-handshake"></i>100% Sénégalais</span>
        </div>

        <div class="about-cta">
          <a href="#" class="btn-emerald">
            <i class="fa-solid fa-envelope"></i>
            Nous contacter
          </a>
        </div>
      </div>

      <div class="about-img-block fade-up">
        <div class="about-img-accent"></div>
        <div class="about-img-frame">
          <img src="../assets/images/pub.png" alt="Yoon bu Gaw — Transport au Sénégal">
          <div class="about-img-overlay"></div>
        </div>
        <div class="about-float-card">
          <div class="afc-label">Satisfaction globale</div>
          <div class="afc-value">4.9 <span style="font-size:1rem;color:var(--gold-l);">★</span></div>
          <div class="afc-sub">10 000+ avis vérifiés</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =============================================
     TÉMOIGNAGES
     ============================================= -->
<section class="section testimonials-section">
  <div class="section-inner">
    <div class="section-header centered fade-up">
      <p class="label-caps">Témoignages</p>
      <h2 class="section-title">Ce que disent nos <em>clients</em></h2>
    </div>

    <div class="rating-hero fade-up">
      <div class="rating-hero-num">4.9<span>/5</span></div>
      <div class="rating-hero-stars">★★★★★</div>
      <div class="rating-hero-sub">Basé sur plus de 10 000 avis clients vérifiés</div>
    </div>

    <div class="testimonials-grid fade-up">
      <div class="testi-card">
        <span class="testi-quote-icon">"</span>
        <p class="testi-text">Service rapide et fiable. Le chauffeur était ponctuel et très professionnel. Je recommande vivement à toute personne cherchant un transport de qualité !</p>
        <div class="testi-author">
          <div class="testi-avatar">AF</div>
          <div>
            <span class="testi-name">Aminata F.</span>
            <span class="testi-route">Dakar → Thiès</span>
          </div>
          <div class="testi-stars">★★★★★</div>
        </div>
      </div>

      <div class="testi-card">
        <span class="testi-quote-icon">"</span>
        <p class="testi-text">Excellent pour les voyages interurbains. L'application est intuitive, le prix est imbattable et le suivi en temps réel me rassure totalement.</p>
        <div class="testi-author">
          <div class="testi-avatar">MD</div>
          <div>
            <span class="testi-name">Mamadou D.</span>
            <span class="testi-route">Dakar → Saint-Louis</span>
          </div>
          <div class="testi-stars">★★★★★</div>
        </div>
      </div>

      <div class="testi-card">
        <span class="testi-quote-icon">"</span>
        <p class="testi-text">Application très simple à utiliser. J'ai réservé mon taxi en moins de 2 minutes. Parfait pour les déplacements professionnels au quotidien !</p>
        <div class="testi-author">
          <div class="testi-avatar">FK</div>
          <div>
            <span class="testi-name">Fatou K.</span>
            <span class="testi-route">Plateau, Dakar</span>
          </div>
          <div class="testi-stars">★★★★★</div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- =============================================
     CTA FINAL
     ============================================= -->
<section class="cta-section">
  <div class="cta-inner fade-up">
    <p class="label-caps" style="color:var(--gold);margin-bottom:20px;">Prêt à démarrer ?</p>
    <h2 class="cta-title">
      Voyagez avec<br>
      <em>Yoon bu Gaw</em>
    </h2>

    <div class="deco-sep">
      <div class="deco-sep-dot"></div>
      <div class="deco-sep-dot"></div>
      <div class="deco-sep-dot"></div>
    </div>

    <p class="cta-sub">
      Rejoignez plus de 25 000 Sénégalais qui font confiance à notre plateforme chaque jour pour leurs déplacements.
    </p>

    <div class="cta-buttons">
      <a href="../auth/traitement_inscription.php" class="btn-cta-gold">
        <i class="fa-solid fa-user-plus" style="margin-right:8px;"></i>
        Créer un compte gratuit
      </a>
      <a href="../auth/login.php" class="btn-cta-outline">
        Voir nos tarifs
        <i class="fa-solid fa-arrow-right" style="margin-left:8px;"></i>
      </a>
    </div>
  </div>
</section>

<?php include_once('../includes/footer.php'); ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ---- Compteurs animés ---- */
function animateCounter(el, target, suffix) {
  let current = 0;
  const step = Math.ceil(target / 80);
  const timer = setInterval(() => {
    current = Math.min(current + step, target);
    el.textContent = current.toLocaleString('fr-FR') + suffix;
    if (current >= target) clearInterval(timer);
  }, 25);
}

const counters = [
  { id: 's1', val: 25000, suf: '+' },
  { id: 's2', val: 1200,  suf: '+' },
  { id: 's3', val: 40000, suf: '+' },
  { id: 's4', val: 8,     suf: ''  }
];

const statsObserver = new IntersectionObserver(entries => {
  entries.forEach(e => {
    if (e.isIntersecting) {
      counters.forEach(c => animateCounter(document.getElementById(c.id), c.val, c.suf));
      statsObserver.disconnect();
    }
  });
}, { threshold: .4 });
statsObserver.observe(document.querySelector('.stats-band'));

/* ---- Scroll fade-up ---- */
const fadeEls = document.querySelectorAll('.fade-up');
const fadeObserver = new IntersectionObserver(entries => {
  entries.forEach((e, i) => {
    if (e.isIntersecting) {
      // Stagger léger selon position dans la grille
      setTimeout(() => e.target.classList.add('visible'), i % 3 * 80);
      fadeObserver.unobserve(e.target);
    }
  });
}, { threshold: .12 });
fadeEls.forEach(el => fadeObserver.observe(el));
</script>
</body>
</html>