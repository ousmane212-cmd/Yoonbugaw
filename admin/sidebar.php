<button class="menu-toggle" id="menuToggle">
  <i class="bi bi-list"></i>
</button>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="sidebar" id="sidebar">

  <div class="logo-wrap">
    <img src="../assets/images/logo.png" alt="Yoon bu Gaw">
  </div>

  <nav>
    <a href="dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="clients.php" class="nav-item"><i class="bi bi-people-fill"></i> Clients</a>
    <a href="chauffeurs.php"><i class="bi bi-person-badge"></i> Chauffeurs</a>
    <a href="vehicules.php"><i class="bi bi-car-front-fill"></i> Véhicules</a>
    <a href="reservations.php"><i class="bi bi-calendar-check"></i> Réservations</a>
    
    <a href="#"><i class="bi bi-car-front"></i> Locations</a>
    <a href="#"><i class="bi bi-bar-chart"></i> Rapports</a>
    <a href="#"><i class="bi bi-credit-card"></i> Paiements</a>
    <a href="../auth/logout.php" style="margin-top:auto">
      <i class="bi bi-box-arrow-right"></i> Déconnexion
    </a>
  </nav>

  <div class="sidebar-promo">
    <img src="../assets/images/2.png" alt="">
    <div class="promo-text">
      <p>La plateforme de transport intelligente au Sénégal</p>
      <button class="btn btn-light btn-sm">En savoir plus</button>
    </div>
  </div>

</div>

<style>
:root{
  --sidebar-width: 220px;
  --bg: #f0f4f9;
  --sidebar-bg: #0d1b2a;
  --accent: #16a34a;
  --card-radius: 16px;
  --shadow: 0 2px 16px rgba(0,0,0,0.07);
}
*{box-sizing:border-box}
body{background:var(--bg);font-family:'DM Sans',sans-serif;margin:0}

/* BOUTON TOGGLE MOBILE (Masqué par défaut sur PC) */
.menu-toggle {
  display: none;
  position: fixed;
  top: 15px;
  left: 15px;
  width: 42px;
  height: 42px;
  background: var(--sidebar-bg);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-size: 24px;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  z-index: 1050;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  transition: background 0.2s;
}
.menu-toggle:hover {
  background: var(--accent);
}

/* OVERLAY (Flou d'arrière-plan mobile) */
.sidebar-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  backdrop-filter: blur(3px);
  z-index: 999;
  opacity: 0;
  transition: opacity 0.3s ease;
}
.sidebar-overlay.active {
  display: block;
  opacity: 1;
}

/* SIDEBAR STRUCTURE */
.sidebar{
  width: var(--sidebar-width);
  min-height: 100vh;
  background: var(--sidebar-bg);
  color: #fff;
  position: fixed;
  top: 0;
  left: 0;
  display: flex;
  flex-direction: column;
  padding: 16px 12px;
  overflow-y: auto;
  z-index: 1000;
  transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.sidebar .logo-wrap{display:flex;align-items:center;justify-content:center;padding:8px 0 20px}
.sidebar .logo-wrap img{width:110px;height:auto;object-fit:contain}
.sidebar nav{flex:1;display:flex;flex-direction:column}
.sidebar a{
  color:#b8c8d8;text-decoration:none;display:flex;align-items:center;gap:10px;
  padding:9px 12px;border-radius:10px;margin-bottom:4px;
  font-family:'DM Sans',sans-serif;font-weight:500;font-size:14px;
  transition:background .2s,color .2s;
}
.sidebar a i{font-size:16px;flex-shrink:0}
.sidebar a:hover,.sidebar a.active{background:var(--accent);color:#fff}

/* Sidebar promo */
.sidebar-promo{margin-top:auto;padding-top:16px}
.sidebar-promo img{width:100%;border-radius:10px 10px 0 0;display:block}
.sidebar-promo .promo-text{background:var(--accent);border-radius:0 0 10px 10px;padding:10px 12px;text-align:center}
.sidebar-promo .promo-text p{font-size:12px;color:#fff;margin:0 0 8px;line-height:1.4}
.sidebar-promo .promo-text .btn{font-size:12px;padding:4px 14px}

/* MAIN CONTENT */
.main-content{
  margin-left: var(--sidebar-width);
  padding: 28px 24px;
  min-height: 100vh;
  transition: margin 0.3s ease;
}

/* CARDS ET ÉLÉMENTS DIVERS */
.stat-card{
  border:none;border-radius:var(--card-radius);padding:20px 18px;
  box-shadow:var(--shadow);display:flex;align-items:center;gap:16px;height:100%;
}
.stat-icon{
  width:52px;height:52px;border-radius:14px;background:rgba(255,255,255,0.2);
  display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:24px;
}
.stat-info{flex:1}
.stat-info h6{font-size:13px;margin:0 0 4px;opacity:.9;font-weight:500}
.stat-number{font-family:'Sora',sans-serif;font-size:24px;font-weight:700;line-height:1;margin-bottom:4px}
.stat-trend{font-size:12px;opacity:.85}

.notif-icon{
  position:relative;width:42px;height:42px;background:#f1f5f9;
  border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;
}
.notif-icon i{font-size:18px;color:#0d6efd}
.profile-pic{width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid #0d6efd}

.notif-badge{
  position:absolute;top:-5px;right:-5px;background:red;color:#fff;
  min-width:20px;height:20px;border-radius:50%;display:flex;
  align-items:center;justify-content:center;font-size:11px;
  font-weight:700;border:2px solid #fff;padding:2px;
}

/* ══════════════════════════════════════════
   NOUVEAU BLOC RESPONSIVE SÉCURISÉ (Mobiles & Tablettes)
══════════════════════════════════════════ */
@media(max-width: 991px){
  .menu-toggle {
    display: flex; /* Le bouton burger apparaît */
  }
  
  .sidebar {
    position: fixed;
    top: 0;
    left: 0;
    transform: translateX(-100%); /* Masqué complètement à gauche du champ visuel */
    box-shadow: none;
  }
  
  /* Classe injectée par JavaScript à l'ouverture */
  .sidebar.active {
    transform: translateX(0); /* Entre en glissant vers la droite */
    box-shadow: 4px 0 24px rgba(0,0,0,0.25);
  }
  
  .main-content {
    margin-left: 0; /* Prend tout l'espace libre sur mobile */
    padding: 75px 16px 20px; /* Ajout d'un padding-top pour ne pas être caché sous le bouton burger */
  }
}

@media(max-width: 576px){
  .topbar h2{font-size:18px}
  .stat-number{font-size:20px}
}
</style>

<script>
document.addEventListener("DOMContentLoaded", function() {
  const menuToggle = document.getElementById('menuToggle');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebarOverlay');

  // Fonction d'ouverture / fermeture au clic sur le bouton burger
  if (menuToggle && sidebar && sidebarOverlay) {
    menuToggle.addEventListener('click', function() {
      sidebar.classList.toggle('active');
      sidebarOverlay.classList.toggle('active');
    });

    // Fermeture automatique en cliquant en dehors du menu (sur le voile d'ombrage)
    sidebarOverlay.addEventListener('click', function() {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
    });
  }
});
</script>