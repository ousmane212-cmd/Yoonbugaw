<?php
session_start();
require_once '../config/database.php';

/* ================= AUTH ================= */
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'chauffeur') {
    header('Location: ../auth/login.php');
    exit;
}

$chauffeurId = (int) $_SESSION['id'];

/* ================= BASE URL ================= */
$base_url = "http://localhost/ton_projet/"; // 🔥 CHANGE ICI

/* ================= CHAUFFEUR ================= */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$chauffeurId]);
$chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeur) {
    die("Chauffeur introuvable");
}

$nom       = $chauffeur['nom'];
$permis    = $chauffeur['permis'];
$type      = $chauffeur['type'];
$telephone = $chauffeur['telephone'];
$initiales = strtoupper(substr($nom, 0, 2));

$chauffeurPhoto = $chauffeur['photo'] ?? '';

/* ================= VEHICULE ================= */
$stmt = $pdo->prepare("SELECT * FROM vehicules WHERE chauffeur = ? LIMIT 1");
$stmt->execute([$nom]);
$vehicule = $stmt->fetch(PDO::FETCH_ASSOC);

$vehiculePhoto = $vehicule['photo']     ?? '';
$vehiculeNom   = $vehicule['nom']       ?? 'Aucun véhicule';
$matricule     = $vehicule['matricule'] ?? '—';
$couleur       = $vehicule['couleur']   ?? '—';
$statut        = $vehicule['statut']    ?? '—';
$carburant     = $vehicule['carburant'] ?? '—';
$capacite      = $vehicule['capacite']  ?? '—';
$kilometrage   = $vehicule['kilometrage'] ?? '—';
$prochain_ct   = $vehicule['prochain_ct'] ?? '—';

/* ================= STATS DU MOIS ================= */

$stmt->execute([$chauffeurId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$totalCourses = $stats['total_courses'] ?? 0;
$totalRevenus = number_format($stats['total_revenus'] ?? 0, 0, ',', ' ');
$noteMoyenne  = number_format($stats['note_moyenne'] ?? 0, 1);
$totalKm      = number_format($stats['total_km'] ?? 0, 0, ',', ' ');

/* ================= STATUT CSS CLASS ================= */
$statusLabel = htmlspecialchars($statut);
$statusClass = 'status-inactive';
$statusLower  = strtolower($statut);
if (str_contains($statusLower, 'disponible') || str_contains($statusLower, 'actif')) {
    $statusClass = 'status-active';
} elseif (str_contains($statusLower, 'course') || str_contains($statusLower, 'maintenance')) {
    $statusClass = 'status-maintenance';
}
$couleurHex = '#6b7280';

if (!empty($couleur)) {
    $mapCouleurs = [
        'blanc' => '#FFFFFF',
        'noir' => '#000000',
        'gris' => '#808080',
        'rouge' => '#EF4444',
        'bleu' => '#3B82F6',
        'vert' => '#22C55E',
        'jaune' => '#EAB308',
        'orange' => '#F97316',
        'marron' => '#92400E'
    ];

    $couleurHex = $mapCouleurs[strtolower(trim($couleur))] ?? '#6b7280';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon Véhicule — Yoon bu Gaw</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root {
    --green:      #007D34;
    --green-lt:   #00A347;
    --green-dim:  #005524;
    --green-bg:   #E8F5EC;
    --navy:       #001529;
    --navy-mid:   #0A2540;
    --gold:       #C9973A;
    --gold-lt:    #E6B86A;
    --gold-bg:    #FBF3E3;
    --amber:      #B45309;
    --amber-bg:   #FEF3C7;
    --red:        #991B1B;
    --red-bg:     #FEE2E2;

    --bg:         #F0F2F5;
    --surface:    #FFFFFF;
    --surface2:   #F8F9FA;
    --border:     rgba(0,0,0,0.07);
    --border-md:  rgba(0,0,0,0.12);
    --text1:      #111827;
    --text2:      #4B5563;
    --text3:      #9CA3AF;

    --r-sm: 8px; --r-md: 12px; --r-lg: 16px; --r-xl: 24px;
    --shadow-sm: 0 1px 3px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
}

body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text1); min-height: 100vh; }

/* ── TOPBAR ── */
.topbar {
    background: var(--navy);
    padding: 0 20px;
    height: 56px;
    display: flex; align-items: center; justify-content: space-between;
    position: sticky; top: 0; z-index: 100;
}
.brand { display: flex; align-items: center; gap: 10px; }
.brand-mark {
    width: 32px; height: 32px; border-radius: 8px;
    background: var(--green); display: flex; align-items: center; justify-content: center;
}
.brand-mark .bi { font-size: 16px; color: #fff; }
.brand-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 15px; font-weight: 700; color: #fff; letter-spacing: -0.3px;
}
.brand-name span { color: var(--gold-lt); }
.topbar-pill {
    display: flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.15);
    padding: 5px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 500; color: #fff;
}
.topbar-pill .dot {
    width: 7px; height: 7px; border-radius: 50%;
    background: #4ade80; box-shadow: 0 0 6px #4ade80;
}

/* ── LAYOUT ── */
.page-wrap { display: flex; min-height: calc(100vh - 56px); }
.main { flex: 1; padding: 24px 16px 100px; max-width: 860px; margin: 0 auto; width: 100%; }

/* ── HERO CARD ── */
.hero-card {
    border-radius: var(--r-xl);
    overflow: hidden;
    margin-bottom: 20px;
    box-shadow: var(--shadow-md);
    position: relative;
}

.vehicle-hero {
    height: 230px;
    background: linear-gradient(160deg, var(--navy) 0%, var(--navy-mid) 60%, #0d3456 100%);
    position: relative;
    overflow: hidden;
    display: flex; align-items: flex-end;
}

.vehicle-hero-img {
    position: absolute; inset: 0;
    width: 100%; height: 100%; object-fit: cover;
    opacity: 0.75;
}

.vehicle-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(to top, rgba(0,21,41,0.9) 0%, rgba(0,21,41,0.2) 60%, transparent 100%);
}

.vehicle-hero-bg-decor {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
}
.vehicle-hero-bg-decor .bi { font-size: 120px; color: rgba(255,255,255,0.04); }

.vehicle-hero-content {
    position: relative; z-index: 2;
    padding: 20px 22px; width: 100%;
    display: flex; align-items: flex-end; justify-content: space-between;
}

.vehicle-title-block .vehicle-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 24px; font-weight: 700; color: #fff;
    letter-spacing: -0.5px; line-height: 1.1;
    text-shadow: 0 2px 8px rgba(0,0,0,0.4);
}
.vehicle-title-block .vehicle-sub {
    font-size: 13px; color: rgba(255,255,255,0.55);
    margin-top: 4px; font-weight: 400;
}

.status-badge {
    display: flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 600; padding: 6px 14px;
    border-radius: 20px; letter-spacing: 0.02em;
    backdrop-filter: blur(8px); white-space: nowrap;
}
.status-active     { background: rgba(0,163,71,0.25); border: 1px solid rgba(0,163,71,0.5); color: #4ade80; }
.status-maintenance{ background: rgba(201,151,58,0.25); border: 1px solid rgba(201,151,58,0.5); color: var(--gold-lt); }
.status-inactive   { background: rgba(153,27,27,0.25); border: 1px solid rgba(153,27,27,0.4); color: #f87171; }
.status-active .sdot { background: #4ade80; box-shadow: 0 0 5px #4ade80; }
.status-maintenance .sdot { background: var(--gold-lt); }
.status-inactive .sdot { background: #f87171; }
.sdot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

/* Vehicle info stripe */
.vehicle-info-stripe {
    background: var(--surface);
    border-top: 1px solid var(--border);
    display: flex; flex-wrap: wrap;
}
.vi-item {
    flex: 1; min-width: 100px;
    padding: 14px 16px;
    border-right: 1px solid var(--border);
    display: flex; align-items: center; gap: 10px;
}
.vi-item:last-child { border-right: none; }
.vi-icon {
    width: 34px; height: 34px; border-radius: var(--r-sm);
    background: var(--green-bg);
    display: flex; align-items: center; justify-content: center;
    color: var(--green); font-size: 16px; flex-shrink: 0;
}
.vi-icon.gold { background: var(--gold-bg); color: var(--gold); }
.vi-icon.amber { background: var(--amber-bg); color: var(--amber); }
.vi-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.07em; color: var(--text3); font-weight: 500; margin-bottom: 2px; }
.vi-val { font-size: 14px; font-weight: 600; color: var(--text1); font-family: 'Plus Jakarta Sans', sans-serif; }

/* ── SECTION HEADER ── */
.section-hd {
    display: flex; align-items: center; gap: 8px;
    margin: 22px 0 12px;
}
.section-hd .line {
    flex: 1; height: 1px; background: var(--border-md);
}
.section-hd span {
    font-size: 11px; font-weight: 600; text-transform: uppercase;
    letter-spacing: 0.1em; color: var(--text3); white-space: nowrap;
}

/* ── CHAUFFEUR CARD ── */
.driver-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-lg);
    padding: 18px 20px;
    display: flex; align-items: center; gap: 16px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 20px;
}
.avatar {
    width: 64px; height: 64px; border-radius: 50%;
    background: var(--navy);
    border: 3px solid var(--green);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 20px; font-weight: 700; color: #fff;
    overflow: hidden; flex-shrink: 0;
}
.avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.driver-name {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 16px; font-weight: 700; color: var(--text1);
    letter-spacing: -0.3px; margin-bottom: 3px;
}
.driver-role { font-size: 12px; color: var(--text3); margin-bottom: 10px; }
.chips { display: flex; flex-wrap: wrap; gap: 6px; }
.chip {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11px; font-weight: 500; color: var(--text2);
    background: var(--surface2); border: 1px solid var(--border-md);
    padding: 4px 10px; border-radius: 20px;
}
.chip .bi { color: var(--green); font-size: 12px; }

/* ── STATS GRID ── */
.stats-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 10px; margin-bottom: 20px;
}
.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    padding: 16px 14px 14px;
    position: relative; overflow: hidden;
    box-shadow: var(--shadow-sm);
}
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0;
    height: 3px; background: var(--green);
    border-radius: var(--r-md) var(--r-md) 0 0;
}
.stat-icon-wrap {
    width: 36px; height: 36px; border-radius: var(--r-sm);
    background: var(--green-bg); color: var(--green);
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; margin-bottom: 10px;
}
.stat-val {
    font-family: 'Plus Jakarta Sans', sans-serif;
    font-size: 22px; font-weight: 700; color: var(--text1);
    letter-spacing: -0.8px; line-height: 1;
    display: block; margin-bottom: 4px;
}
.stat-lbl { font-size: 11px; color: var(--text3); font-weight: 500; }

/* ── DETAILS GRID ── */
.details-grid {
    display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 10px; padding: 18px 20px 20px;
}
.detail-item {
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: var(--r-md);
    padding: 12px 14px;
}
.detail-lbl {
    font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em;
    color: var(--text3); font-weight: 600; margin-bottom: 6px;
}
.detail-val {
    font-size: 14px; font-weight: 600; color: var(--text1);
    display: flex; align-items: center; gap: 7px;
}
.detail-val .bi { font-size: 14px; color: var(--text3); }
.detail-val.warn { color: var(--amber); }
.color-swatch {
    width: 14px; height: 14px; border-radius: 50%;
    border: 1.5px solid rgba(0,0,0,0.12); flex-shrink: 0;
}

/* ── MOBILE NAV ── */
.mobile-nav {
    position: fixed; bottom: 0; left: 0; right: 0;
    background: var(--surface); border-top: 1px solid var(--border);
    padding: 8px 0 env(safe-area-inset-bottom, 10px); z-index: 200;
    backdrop-filter: blur(12px);
}
.mn-inner { display: flex; justify-content: space-around; }
.mnav-item {
    display: flex; flex-direction: column; align-items: center; gap: 3px;
    font-size: 10px; font-weight: 500; color: var(--text3);
    text-decoration: none; padding: 6px 12px; border-radius: 10px;
    transition: color 0.15s, background 0.15s;
}
.mnav-item .bi { font-size: 20px; display: block; }
.mnav-item.active { color: var(--green); background: var(--green-bg); }
.mnav-item:not(.active):hover { color: var(--text2); background: var(--surface2); }

/* ── RESPONSIVE ── */
@media (max-width: 580px) {
    .vehicle-hero { height: 200px; }
    .vehicle-title-block .vehicle-name { font-size: 20px; }
    .stats-grid { grid-template-columns: repeat(2, 1fr); }
    .details-grid { grid-template-columns: repeat(2, 1fr); }
    .vehicle-info-stripe { flex-direction: column; }
    .vi-item { border-right: none; border-bottom: 1px solid var(--border); }
    .vi-item:last-child { border-bottom: none; }
    .driver-card { gap: 12px; }
}
@media (max-width: 400px) {
    .details-grid { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="brand">
        <div class="brand-mark"><i class="bi bi-car-front-fill"></i></div>
        <span class="brand-name">Yoon bu <span>Gaw</span></span>
    </div>
    <div class="topbar-pill">
        <span class="dot"></span>
        En ligne
    </div>
</div>

<div class="page-wrap">
<?php include_once "sidebar.php"; ?>

<main class="main">

   
    <div class="hero-card">
        <div class="vehicle-hero">
            <?php if ($vehiculePhoto): ?>
                <img class="vehicle-hero-img" src="../<?= htmlspecialchars($vehiculePhoto) ?>" alt="<?= htmlspecialchars($vehiculeNom) ?>">
            <?php endif; ?>
            <div class="vehicle-hero-overlay"></div>
            <?php if (!$vehiculePhoto): ?>
                <div class="vehicle-hero-bg-decor"><i class="bi bi-car-front-fill"></i></div>
            <?php endif; ?>
            <div class="vehicle-hero-content">
                <div class="vehicle-title-block">
                    <p class="vehicle-name"><?= htmlspecialchars($vehiculeNom) ?></p>
                    <p class="vehicle-sub"><?= htmlspecialchars($matricule) ?></p>
                </div>
                <?php if ($statut !== '—'): ?>
                <div class="status-badge <?= $statusClass ?>">
                    <span class="sdot"></span>
                    <?= htmlspecialchars($statut) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info stripe -->
        <div class="vehicle-info-stripe">
            <?php if ($carburant !== '—'): ?>
            <div class="vi-item">
                <div class="vi-icon"><i class="bi bi-fuel-pump-fill"></i></div>
                <div>
                    <div class="vi-label">Carburant</div>
                    <div class="vi-val"><?= htmlspecialchars($carburant) ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($capacite !== '—'): ?>
            <div class="vi-item">
                <div class="vi-icon gold"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="vi-label">Capacité</div>
                    <div class="vi-val"><?= htmlspecialchars($capacite) ?> places</div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($kilometrage !== '—'): ?>
            <div class="vi-item">
                <div class="vi-icon"><i class="bi bi-speedometer2"></i></div>
                <div>
                    <div class="vi-label">Kilométrage</div>
                    <div class="vi-val"><?= number_format((int)$kilometrage, 0, ',', ' ') ?> km</div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($prochain_ct !== '—'): ?>
            <div class="vi-item">
                <div class="vi-icon amber"><i class="bi bi-calendar-check-fill"></i></div>
                <div>
                    <div class="vi-label">Prochain CT</div>
                    <div class="vi-val" style="color:var(--amber)"><?= htmlspecialchars($prochain_ct) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── CHAUFFEUR ── -->
    <div class="section-hd"><span>Chauffeur</span><div class="line"></div></div>

    <div class="driver-card">
        <div class="avatar">
            <?php if ($chauffeurPhoto): ?>
                <img src="<?= $base_url . htmlspecialchars($chauffeurPhoto) ?>" alt="<?= htmlspecialchars($nom) ?>">
            <?php else: ?>
                <?= $initiales ?>
            <?php endif; ?>
        </div>
        <div>
            <p class="driver-name"><?= htmlspecialchars($nom) ?></p>
            <p class="driver-role">Chauffeur professionnel · Yoon bu Gaw</p>
            <div class="chips">
                <?php if ($permis): ?>
                    <span class="chip"><i class="bi bi-card-heading"></i> Permis <?= htmlspecialchars($permis) ?></span>
                <?php endif; ?>
                <?php if ($telephone): ?>
                    <span class="chip"><i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($telephone) ?></span>
                <?php endif; ?>
                <?php if ($type): ?>
                    <span class="chip"><i class="bi bi-car-front-fill"></i> <?= htmlspecialchars($type) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── STATS ── -->
    <div class="section-hd"><span>Ce mois-ci</span><div class="line"></div></div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon-wrap"><i class="bi bi-signpost-2-fill"></i></div>
            <span class="stat-val"><?= $totalCourses ?></span>
            <span class="stat-lbl">Courses</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon-wrap"><i class="bi bi-cash-coin"></i></div>
            <span class="stat-val"><?= $totalRevenus ?></span>
            <span class="stat-lbl">Revenus FCFA</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon-wrap"><i class="bi bi-star-fill"></i></div>
            <span class="stat-val"><?= $noteMoyenne ?></span>
            <span class="stat-lbl">Note moyenne</span>
        </div>
        <div class="stat-card">
            <div class="stat-icon-wrap"><i class="bi bi-speedometer"></i></div>
            <span class="stat-val"><?= $totalKm ?></span>
            <span class="stat-lbl">km parcourus</span>
        </div>
    </div>

   
    <div class="section-hd"><span>Fiche véhicule</span><div class="line"></div></div>

    <div style="background:var(--surface);border:1px solid var(--border);border-radius:var(--r-lg);box-shadow:var(--shadow-sm);overflow:hidden;">
        <div class="details-grid">
            <div class="detail-item">
                <div class="detail-lbl">Matricule</div>
                <div class="detail-val"><i class="bi bi-upc-scan"></i><?= htmlspecialchars($matricule) ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-lbl">Couleur</div>
                <div class="detail-val">
                    <span class="color-swatch" style="background:<?= $couleurHex ?>;<?= $couleurHex === '#FFFFFF' ? 'border-color:rgba(0,0,0,0.25)' : '' ?>"></span>
                    <?= htmlspecialchars($couleur) ?>
                </div>
            </div>
            <?php if ($carburant !== '—'): ?>
            <div class="detail-item">
                <div class="detail-lbl">Carburant</div>
                <div class="detail-val"><i class="bi bi-fuel-pump"></i><?= htmlspecialchars($carburant) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($capacite !== '—'): ?>
            <div class="detail-item">
                <div class="detail-lbl">Capacité</div>
                <div class="detail-val"><i class="bi bi-people"></i><?= htmlspecialchars($capacite) ?> passagers</div>
            </div>
            <?php endif; ?>
            <?php if ($kilometrage !== '—'): ?>
            <div class="detail-item">
                <div class="detail-lbl">Kilométrage</div>
                <div class="detail-val"><i class="bi bi-speedometer"></i><?= number_format((int)$kilometrage, 0, ',', ' ') ?> km</div>
            </div>
            <?php endif; ?>
            <?php if ($prochain_ct !== '—'): ?>
            <div class="detail-item">
                <div class="detail-lbl">Prochain CT</div>
                <div class="detail-val warn"><i class="bi bi-calendar-check" style="color:var(--amber)"></i><?= htmlspecialchars($prochain_ct) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

</main>
</div>
</body>
</html>