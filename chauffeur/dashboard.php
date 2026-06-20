<?php

session_start();
require_once '../config/database.php';


if (empty($_SESSION['id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'chauffeur') {
    header('Location: ../auth/login.php');
    exit;
}

$chauffeurId = (int) $_SESSION['id'];

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "chauffeur"');
$stmt->execute([$chauffeurId]);
$chauffeurData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeurData) {
    header('Location: ../auth/login.php');
    exit;
}

$chauffeurNom    = htmlspecialchars(trim($chauffeurData['nom'] ?? 'Chauffeur'));
$chauffeurEmail  = htmlspecialchars($chauffeurData['email'] ?? '');
$chauffeurTel    = htmlspecialchars($chauffeurData['telephone'] ?? '');
$chauffeurAdresse= htmlspecialchars($chauffeurData['adresse'] ?? '');
$chauffeurPhoto  = htmlspecialchars($chauffeurData['photo'] ?? '');
$chauffeurPermis = htmlspecialchars($chauffeurData['permis'] ?? '');
$typeVehicule    = strtolower($chauffeurData['type'] ?? 'taxi');
$statutChauffeur = $chauffeurData['statut'] ?? 'disponible';
$disponible      = (int)($chauffeurData['disponible'] ?? 1);
$chauffeurLat    = $chauffeurData['lat'] ?? null;
$chauffeurLng    = $chauffeurData['lng'] ?? null;

$parts     = explode(' ', $chauffeurNom);
$initiales = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
$prenom    = $parts[0];


$stmt = $pdo->prepare('SELECT * FROM vehicules WHERE chauffeur = ? LIMIT 1');
$stmt->execute([$chauffeurNom]);
$vehiculeData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vehiculeData) {
    $stmt = $pdo->prepare('SELECT * FROM vehicules WHERE matricule = ? LIMIT 1');
    $stmt->execute([$chauffeurData['vehicule'] ?? '']);
    $vehiculeData = $stmt->fetch(PDO::FETCH_ASSOC);
}

$vehiculeNom     = htmlspecialchars($vehiculeData['nom']       ?? ($chauffeurData['vehicule'] ?? '—'));
$matricule       = htmlspecialchars($vehiculeData['matricule'] ?? '—');
$vehiculeCouleur = htmlspecialchars($vehiculeData['couleur']   ?? '—');
$vehiculeType    = strtolower($vehiculeData['type']            ?? $typeVehicule);
$vehiculeStatut  = htmlspecialchars($vehiculeData['statut']    ?? 'actif');
$vehiculePlaces  = $vehiculeData['places']      ?? '—';
$vehiculeCapaKg  = $vehiculeData['capacite_kg'] ?? null;
$vehiculeNote    = (float)($vehiculeData['note'] ?? 0);
$vehiculePhoto = $vehiculeData['photo'] ?? '';
$vehiculeId      = (int)($vehiculeData['id']                  ?? 0);


$today = date('Y-m-d');
$mois  = date('Y-m');

$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE chauffeur_id = ?');
$stmt->execute([$chauffeurId]);
$totalCourses = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE chauffeur_id = ? AND DATE(date_reservation) = ?');
$stmt->execute([$chauffeurId, $today]);
$coursesToday = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE chauffeur_id = ? AND (statut = 'terminé' OR statut = 'termine' OR statut_course = 'terminé' OR statut_course = 'termine')");
$stmt->execute([$chauffeurId]);
$coursesTerminees = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(montant), 0) FROM reservations WHERE chauffeur_id = ?');
$stmt->execute([$chauffeurId]);
$totalGains = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(SUM(montant), 0) FROM reservations WHERE chauffeur_id = ? AND DATE(date_reservation) = ?');
$stmt->execute([$chauffeurId, $today]);
$gainsToday = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(montant), 0) FROM reservations WHERE chauffeur_id = ? AND DATE_FORMAT(date_reservation,'%Y-%m') = ?");
$stmt->execute([$chauffeurId, $mois]);
$gainsMois = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COALESCE(ROUND(AVG(note_client), 1), 0) FROM reservations WHERE chauffeur_id = ? AND note_client IS NOT NULL');
$stmt->execute([$chauffeurId]);
$noteMoyenne = (float) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE chauffeur_id = ? AND note_client IS NOT NULL');
$stmt->execute([$chauffeurId]);
$totalEvals = (int) $stmt->fetchColumn();


/* Courses en attente */
$stmt = $pdo->prepare(
    "SELECT r.id, r.depart, r.destination, r.montant, r.type_transport, r.user_name,
            r.date_reservation, r.heure_depart, r.date_depart, r.mode_paiement,
            r.service, r.reference, r.eta,
            u.telephone AS client_tel, u.photo AS client_photo
     FROM reservations r
     LEFT JOIN users u ON TRIM(u.nom) = TRIM(r.user_name)
     WHERE r.chauffeur_id = ? 
       AND r.statut_course = 'en_attente'
     ORDER BY r.date_reservation DESC LIMIT 10"
);
$stmt->execute([$chauffeurId]);
$coursesEnAttente = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Historique récent */
$stmt = $pdo->prepare(
    "SELECT r.id, r.depart, r.destination, r.montant, r.type_transport,
            r.statut, r.statut_course, r.user_name, r.date_reservation,
            r.mode_paiement, r.note_client, r.service, r.reference
     FROM reservations r
     WHERE r.chauffeur_id = ?
     ORDER BY r.id DESC LIMIT 8"
);
$stmt->execute([$chauffeurId]);
$coursesRecentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Course en cours */
$stmt = $pdo->prepare(
    "SELECT * FROM reservations
     WHERE chauffeur_id = ? AND (statut = 'en cours' OR statut = 'en_cours' OR statut_course = 'en_cours')
     ORDER BY date_reservation DESC LIMIT 1"
);
$stmt->execute([$chauffeurId]);
$courseActive = $stmt->fetch(PDO::FETCH_ASSOC);

/* Gains 7 jours */
$stmt = $pdo->prepare(
    "SELECT DATE(date_reservation) AS jour, COALESCE(SUM(montant), 0) AS total
     FROM reservations
     WHERE chauffeur_id = ? AND date_reservation >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(date_reservation)"
);
$stmt->execute([$chauffeurId]);
$gainsRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$gainsParJour = [];
$joursSemaine = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $gainsParJour[] = (float)($gainsRows[$d] ?? 0);
    $joursSemaine[] = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'][date('w', strtotime($d))];
}

/* Gains 4 mois */
$stmt = $pdo->prepare(
    "SELECT DATE_FORMAT(date_reservation,'%Y-%m') AS mois, COALESCE(SUM(montant),0) AS total
     FROM reservations
     WHERE chauffeur_id = ? AND date_reservation >= DATE_SUB(CURDATE(), INTERVAL 4 MONTH)
     GROUP BY DATE_FORMAT(date_reservation,'%Y-%m')
     ORDER BY mois ASC"
);
$stmt->execute([$chauffeurId]);
$gainsMoisRows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* Distribution des notes */
$stmt = $pdo->prepare(
    "SELECT note_client, COUNT(*) AS cnt FROM reservations
     WHERE chauffeur_id = ? AND note_client IS NOT NULL
     GROUP BY note_client"
);
$stmt->execute([$chauffeurId]);
$notesRows  = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$totalNotes = array_sum($notesRows) ?: 1;
$distribNotes = [];
for ($s = 5; $s >= 1; $s--) {
    $distribNotes[$s] = round(($notesRows[$s] ?? 0) / $totalNotes * 100);
}


function fmt(float $n): string {
    return number_format($n, 0, ',', ' ');
}
function elapsed(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60)    return 'À l\'instant';
    if ($diff < 3600)  return 'Il y a ' . floor($diff / 60) . ' min';
    if ($diff < 86400) return 'Il y a ' . floor($diff / 3600) . 'h';
    return date('d/m', strtotime($date));
}
function initiales(string $nom): string {
    $p = explode(' ', trim($nom));
    return strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
}
function typeIcon(string $type): string {
    return match(strtolower($type)) { 'bus' => '🚌', 'cargo' => '🚛', 'moto' => '🏍️', default => '🚕' };
}
function typeIconSvg(string $type): string {
    return match(strtolower($type)) {
        'bus'   => '<i class="bi bi-bus-front-fill"></i>',
        'cargo' => '<i class="bi bi-truck"></i>',
        'moto'  => '<i class="bi bi-bicycle"></i>',
        default => '<i class="bi bi-car-front-fill"></i>',
    };
}
function statutClass(string $s): string {
    $s = strtolower($s);
    if (str_contains($s, 'annul'))   return 'hs-annule';
    if (str_contains($s, 'cours'))   return 'hs-en_cours';
    if (str_contains($s, 'attente')) return 'hs-attente';
    return 'hs-termine';
}
function statutLabel(string $s): string {
    $s = strtolower(trim($s));
    return match(true) {
        str_contains($s,'annul')   => 'Annulé',
        str_contains($s,'cours')   => 'En cours',
        str_contains($s,'attente') => 'En attente',
        str_contains($s,'termin')  => 'Terminé',
        default                    => ucfirst($s),
    };
}
function paiementIcon(string $mode): string {
    return match(strtolower(trim($mode))) {
        'orange money','orange_money','om' => '🟠',
        'wave'                             => '🔵',
        'free money','free'                => '🟣',
        'espèces','especes','cash'         => '💵',
        default                            => '💳',
    };
}
function starsHtml(float $note, int $max = 5): string {
    $html = '';
    for ($i = 1; $i <= $max; $i++) {
        $cls = $i <= floor($note) ? 'bi-star-fill' : ($i - $note < 1 ? 'bi-star-half' : 'bi-star');
        $html .= '<i class="bi ' . $cls . '"></i>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yoon bu Gaw — Tableau de bord Chauffeur</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="styles.css">
</head>
<body>

<?php
/* Données JSON pour JS */
$jsData = [
    'gainsParJour'  => $gainsParJour,
    'joursSemaine'  => $joursSemaine,
    'gainsMois'     => array_values($gainsMoisRows),
    'labelsMois'    => array_keys($gainsMoisRows),
    'chauffeurId'   => $chauffeurId,
    'statutInit'    => $statutChauffeur,
    'totalEvals'    => $totalEvals,
    'vehiculeId'    => $vehiculeId,
    'pendingCourses'=> count($coursesEnAttente),
];
?>

<div class="layout" id="app">

<?php
include_once "sidebar.php";
?>


<div class="main">

    <!-- TOPBAR -->
    <header class="topbar" role="banner">
        <div class="tb-left">
            <button class="tb-menu" onclick="toggleSidebar()" aria-label="Menu">
                <i class="bi bi-list" aria-hidden="true"></i>
            </button>
            <div>
                <div class="tb-title">Tableau de bord</div>
                <div class="tb-breadcrumb">
                    <?= date('l d F Y') ?>
                </div>
            </div>
        </div>
        <div class="tb-right">
            <div class="live-chip">
                <span class="live-dot"></span>
                <strong id="live-time"></strong>
            </div>

        
            <button
                class="icon-btn"
                id="notif-btn"
                onclick="toggleNotif()"
                aria-label="Notifications"
                aria-expanded="false"
                aria-haspopup="true">
                <i class="bi bi-bell" aria-hidden="true"></i>
                <?php if (count($coursesEnAttente) > 0): ?>
                    <span class="notif-count-bubble" id="notif-bubble"><?= count($coursesEnAttente) ?></span>
                <?php else: ?>
                    <span class="notif-count-bubble" id="notif-bubble" style="display:none">0</span>
                <?php endif; ?>
            </button>

            <div class="tb-avatar" onclick="openModal('modal-profil')" role="button" tabindex="0" aria-label="Mon profil">
                <?php if ($chauffeurPhoto): ?>
                    <img src="<?= $chauffeurPhoto ?>" alt="Photo de profil">
                <?php else: ?>
                    <?= $initiales ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- CONTENT -->
    <main class="content" id="main-content">

        <!-- Course active -->
        <?php if ($courseActive): ?>
        <div class="course-active-bar fu d0" id="active-bar" role="alert" aria-live="polite">
            <div class="acb-pulse">🚗</div>
            <div class="acb-content">
                <div class="acb-title">Course #<?= $courseActive['id'] ?> — En cours</div>
                <div class="acb-route">
                    <i class="bi bi-geo-alt-fill" aria-hidden="true"></i>
                    <?= htmlspecialchars($courseActive['depart']) ?>
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                    <?= htmlspecialchars($courseActive['destination']) ?>
                    <span>&bull;</span> <?= htmlspecialchars($courseActive['user_name']) ?>
                    <span>&bull;</span> <?= fmt((float)$courseActive['montant']) ?> FCFA
                    <?php if (!empty($courseActive['eta'])): ?>
                        <span>&bull;</span> ETA : <?= htmlspecialchars($courseActive['eta']) ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="acb-actions">
                <button class="btn-finish" onclick="terminerCourse(<?= $courseActive['id'] ?>)">
                    <i class="bi bi-check-circle-fill" aria-hidden="true"></i> Terminer
                </button>
                <button class="btn-incident" onclick="openModal('modal-incident')">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i> Incident
                </button>
            </div>
        </div>
        <?php endif; ?>

        <!-- Hero -->
        <div class="hero fu d1">
            <div class="hero-left">
                <div class="hero-greeting">Bienvenue sur votre espace</div>
                <div class="hero-name">Bonjour, <?= $prenom ?> 👋</div>
                <div class="hero-stats">
                    <div class="hero-stat"><i class="bi bi-ticket-perforated-fill" aria-hidden="true"></i> <strong><?= $coursesToday ?></strong> courses aujourd'hui</div>
                    <div class="hero-sep" aria-hidden="true"></div>
                    <div class="hero-stat"><i class="bi bi-hourglass-split" aria-hidden="true"></i> <strong id="hero-pending"><?= count($coursesEnAttente) ?></strong> en attente</div>
                    <?php if ($matricule !== '—'): ?>
                    <div class="hero-sep" aria-hidden="true"></div>
                    <div class="hero-stat"><i class="bi bi-credit-card" aria-hidden="true"></i> <span style="font-family:var(--font-mono);font-size:12px;color:rgba(255,255,255,.85)"><?= $matricule ?></span></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="hero-actions">
                <?php if (count($coursesEnAttente) > 0): ?>
                <button class="btn-hero-primary" onclick="toggleNotif()">
                    <i class="bi bi-bell-fill" aria-hidden="true"></i>
                    <span id="hero-btn-text"><?= count($coursesEnAttente) ?> demande(s)</span>
                </button>
                <?php endif; ?>
                <button class="btn-hero-secondary" onclick="openModal('modal-rapport')">
                    <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i> Rapport
                </button>
            </div>
        </div>

        <!-- GPS -->
        <div class="gps-bar fu d2">
            <div class="gps-icon-wrap"><i class="bi bi-geo-alt-fill" style="color:var(--g700)" aria-hidden="true"></i></div>
            <div>
                <div class="gps-label">Position GPS</div>
                <div class="gps-value" id="gps-loc">
                    <?php if ($chauffeurLat && $chauffeurLng): ?>
                        Position enregistrée
                    <?php else: ?>
                        Localisation en cours…
                    <?php endif; ?>
                </div>
            </div>
            <button class="btn-gps" onclick="geolocaliser()" aria-label="Actualiser la position GPS">
                <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Actualiser
            </button>
        </div>

        <!-- Stats -->
        <div class="stats-grid fu d2" role="list" aria-label="Statistiques du chauffeur">

            <div class="stat-card sc-green" role="listitem">
                <div class="stat-icon si-wh"><i class="bi bi-ticket-perforated-fill" aria-hidden="true"></i></div>
                <div class="stat-val sv-white"><?= $coursesToday ?></div>
                <div class="stat-label sl-white">Courses aujourd'hui</div>
                <div class="stat-foot sf-white">
                    <span class="up"><i class="bi bi-arrow-up" aria-hidden="true"></i> <?= $totalCourses ?> au total</span>
                    &bull; <?= $coursesTerminees ?> terminées
                </div>
            </div>

            <div class="stat-card sc-dark" role="listitem">
                <div class="stat-icon si-wh"><i class="bi bi-currency-exchange" aria-hidden="true"></i></div>
                <div class="stat-val sv-white"><?= fmt($gainsMois) ?></div>
                <div class="stat-label sl-white">Gains ce mois (FCFA)</div>
                <div class="stat-foot sf-white">
                    <i class="bi bi-cash-stack" aria-hidden="true"></i> Aujourd'hui : <?= fmt($gainsToday) ?> F
                </div>
            </div>

            <div class="stat-card sc-amber" role="listitem">
                <div class="stat-icon si-wh"><i class="bi bi-star-fill" aria-hidden="true"></i></div>
                <div class="stat-val sv-white"><?= $noteMoyenne > 0 ? $noteMoyenne : '—' ?></div>
                <div class="stat-label sl-white">Note moyenne / 5</div>
                <div class="stat-foot sf-white">
                    <span class="stars-sm" aria-label="<?= $noteMoyenne ?> étoiles sur 5">
                        <?= starsHtml($noteMoyenne) ?>
                    </span>
                    &nbsp; <?= $totalEvals ?> éval.
                </div>
            </div>

            <div class="stat-card sc-white" role="listitem">
                <div class="stat-icon si-gr"><i class="bi bi-hourglass-split" aria-hidden="true"></i></div>
                <div class="stat-val sv-dark" id="stat-pending"><?= count($coursesEnAttente) ?></div>
                <div class="stat-label sl-dark">Demandes en attente</div>
                <div class="stat-foot sf-dark" style="color:<?= count($coursesEnAttente) > 0 ? 'var(--amber)' : 'var(--n400)' ?>">
                    <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
                    <?= count($coursesEnAttente) > 0 ? count($coursesEnAttente) . ' à traiter' : 'Aucune demande' ?>
                </div>
            </div>

        </div>

        <!-- Contenu principal -->
        <div class="bottom-grid">

            <!-- Colonne gauche -->
            <div>

                <!-- Demandes en attente -->
                <div class="panel fu d3" id="section-courses">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="bi bi-hourglass-split" style="color:var(--amber)" aria-hidden="true"></i>
                            Demandes en attente
                        </div>
                        <span class="ph-badge phb-amber" id="courses-badge"><?= count($coursesEnAttente) ?> nouvelle(s)</span>
                    </div>
                    <div class="panel-body" id="courses-list">
                        <?php if (empty($coursesEnAttente)): ?>
                            <div class="empty-state">
                                <i class="bi bi-check-circle-fill" style="color:var(--g700)" aria-hidden="true"></i>
                                <p>Aucune demande en attente pour le moment</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($coursesEnAttente as $c): ?>
                            <?php
                                $ic  = typeIcon($c['type_transport'] ?? 'taxi');
                                $eta = elapsed($c['date_reservation']);
                                $cin = initiales($c['user_name'] ?? 'U');
                                $pm  = paiementIcon($c['mode_paiement'] ?? '');
                                $hdp = !empty($c['heure_depart']) ? date('H:i', strtotime($c['heure_depart'])) : '';
                            ?>
                            <div class="course-card" id="cc-<?= $c['id'] ?>" role="article" aria-label="Course <?= $c['id'] ?>">
                                <div class="cc-head">
                                    <div style="display:flex;align-items:center;gap:7px">
                                        <span class="cc-id"><?= $ic ?> #<?= $c['id'] ?></span>
                                        <?php if (!empty($c['reference'])): ?>
                                        <span class="cc-ref"><?= htmlspecialchars($c['reference']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="cc-time">
                                        <i class="bi bi-clock" aria-hidden="true"></i>
                                        <time datetime="<?= $c['date_reservation'] ?>"><?= $eta ?></time>
                                    </span>
                                </div>

                                <div class="cc-route">
                                    <span class="route-dot rd-green" aria-hidden="true"></span>
                                    <span class="route-city"><?= htmlspecialchars($c['depart']) ?></span>
                                    <span class="route-arrow" aria-hidden="true"><i class="bi bi-arrow-right"></i></span>
                                    <span class="route-dot rd-red" aria-hidden="true"></span>
                                    <span class="route-city"><?= htmlspecialchars($c['destination']) ?></span>
                                </div>

                                <div class="cc-tags">
                                    <?php if (!empty($c['service'])): ?>
                                    <span class="tag"><i class="bi bi-tag" aria-hidden="true"></i> <?= htmlspecialchars($c['service']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($c['mode_paiement'])): ?>
                                    <span class="tag"><?= $pm ?> <?= htmlspecialchars($c['mode_paiement']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($c['eta'])): ?>
                                    <span class="tag"><i class="bi bi-clock-fill" aria-hidden="true"></i> ETA <?= htmlspecialchars($c['eta']) ?></span>
                                    <?php endif; ?>
                                    <?php if ($hdp): ?>
                                    <span class="tag"><i class="bi bi-alarm" aria-hidden="true"></i> Départ <?= $hdp ?></span>
                                    <?php endif; ?>
                                </div>

                                <div class="cc-foot">
                                    <div class="client-chip">
                                        <div class="client-avatar">
                                            <?php if (!empty($c['client_photo'])): ?>
                                                <img src="<?= htmlspecialchars($c['client_photo']) ?>" alt="Photo client">
                                            <?php else: ?>
                                                <?= $cin ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="client-name"><?= htmlspecialchars($c['user_name']) ?></div>
                                            <?php if (!empty($c['client_tel'])): ?>
                                            <div class="client-tel"><i class="bi bi-telephone" aria-hidden="true"></i> <?= htmlspecialchars($c['client_tel']) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="montant" aria-label="Montant : <?= fmt((float)$c['montant']) ?> francs CFA"><?= fmt((float)$c['montant']) ?> F</div>
                                </div>

                                <div class="cc-actions">
                                    <button class="btn-accept" onclick="confirmAction(<?= $c['id'] ?>,'accept')" aria-label="Accepter la course <?= $c['id'] ?>">
                                        <i class="bi bi-check-lg" aria-hidden="true"></i> Accepter
                                    </button>
                                    <button class="btn-reject" onclick="confirmAction(<?= $c['id'] ?>,'reject')" aria-label="Refuser la course <?= $c['id'] ?>">
                                        <i class="bi bi-x-lg" aria-hidden="true"></i> Refuser
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Graphique + Historique -->
                <div class="panel fu d4" style="margin-top:14px">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="bi bi-bar-chart-fill" style="color:var(--g700)" aria-hidden="true"></i>
                            Gains 7 derniers jours
                        </div>
                        <span class="ph-badge phb-green"><?= fmt(array_sum($gainsParJour)) ?> FCFA</span>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="chartGains" role="img" aria-label="Graphique des gains sur 7 jours"></canvas>
                    </div>

                    <div class="panel-head" id="section-historique" style="border-top:1px solid var(--n100)">
                        <div class="panel-title">
                            <i class="bi bi-clock-history" aria-hidden="true"></i>
                            Historique des courses
                        </div>
                        <a href="mes_courses.php" class="btn-link">
                            Voir tout <i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </a>
                    </div>
                    <div style="padding:0 18px 14px">
                        <?php if (empty($coursesRecentes)): ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox" aria-hidden="true"></i>
                                <p>Aucune course enregistrée</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($coursesRecentes as $h): ?>
                            <?php
                                $ht   = strtolower($h['type_transport'] ?? 'taxi');
                                $hCls = match($ht) { 'bus'=>'hi-bus','cargo'=>'hi-cargo','moto'=>'hi-moto',default=>'hi-taxi' };
                                $hIco = match($ht) { 'bus'=>'bi-bus-front-fill','cargo'=>'bi-truck','moto'=>'bi-bicycle',default=>'bi-car-front-fill' };
                                $sRaw = !empty($h['statut_course']) ? $h['statut_course'] : ($h['statut'] ?? '');
                                $sCls = str_replace('hs-', 'hb-', statutClass($sRaw));
                                $sLbl = statutLabel($sRaw);
                                $hDate= !empty($h['date_reservation']) ? date('d/m · H:i', strtotime($h['date_reservation'])) : '—';
                                $hPm  = paiementIcon($h['mode_paiement'] ?? '');
                            ?>
                            <div class="hist-row">
                                <div class="hist-icon <?= $hCls ?>">
                                    <i class="bi <?= $hIco ?>" aria-hidden="true"></i>
                                </div>
                                <div class="hist-info">
                                    <div class="hist-route"><?= htmlspecialchars($h['depart']) ?> → <?= htmlspecialchars($h['destination']) ?></div>
                                    <div class="hist-meta">
                                        <?= htmlspecialchars($h['user_name'] ?? '—') ?>
                                        <span aria-hidden="true">&bull;</span>
                                        <time datetime="<?= $h['date_reservation'] ?>"><?= $hDate ?></time>
                                        <?php if (!empty($h['mode_paiement'])): ?>
                                            <span><?= $hPm ?> <?= htmlspecialchars($h['mode_paiement']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($h['note_client'])): ?>
                                            <span aria-label="Note : <?= $h['note_client'] ?> sur 5">
                                                <?php for ($s = 1; $s <= 5; $s++): ?>
                                                <i class="bi bi-star<?= $s <= (int)$h['note_client'] ? '-fill' : '' ?>" style="font-size:10px;color:var(--amber)" aria-hidden="true"></i>
                                                <?php endfor; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="hist-right">
                                    <div class="hist-amount"><?= fmt((float)$h['montant']) ?> F</div>
                                    <span class="hist-badge <?= $sCls ?>"><?= $sLbl ?></span>
                                    
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <!-- Colonne droite -->
            <div class="right-col">

                <!-- Actions rapides -->
                <div class="panel fu d3">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="bi bi-lightning-fill" style="color:var(--amber)" aria-hidden="true"></i>
                            Actions rapides
                        </div>
                    </div>
                    <div class="quick-grid">
                        <button class="quick-btn qb-green" onclick="toggleStatut()" aria-label="Changer le statut de disponibilité">
                            <i class="bi bi-toggle-on" aria-hidden="true"></i>
                            Changer statut
                        </button>
                        <button class="quick-btn qb-amber" onclick="openModal('modal-rapport')" aria-label="Voir le rapport du jour">
                            <i class="bi bi-file-earmark-bar-graph" aria-hidden="true"></i>
                            Rapport du jour
                        </button>
                        <button class="quick-btn qb-red" onclick="openModal('modal-incident')" aria-label="Signaler un incident">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            Signaler incident
                        </button>
                        <button class="quick-btn qb-blue" onclick="openModal('modal-maintenance')" aria-label="Demander une maintenance">
                            <i class="bi bi-tools" aria-hidden="true"></i>
                            Maintenance
                        </button>
                    </div>
                </div>

                <!-- Véhicule -->
                <div class="panel fu d4">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="bi bi-car-front-fill" style="color:var(--g700)" aria-hidden="true"></i>
                            Mon véhicule
                        </div>
                        <button class="btn-link" onclick="openModal('modal-vehicule')">Détails →</button>
                    </div>
                    <?php if ($vehiculeData): ?>
                    <div class="veh-row">
                        <div class="veh-img">
                            <?php if ($vehiculePhoto): ?>
                              <img src="../<?= htmlspecialchars($vehiculePhoto) ?>"
     style="width:48px;height:48px;object-fit:cover;border-radius:var(--radius-sm)"
     alt="Photo du véhicule">
                            <?php else: ?>
                                <?= typeIcon($vehiculeType) ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div class="veh-name"><?= $vehiculeNom ?></div>
                            <div class="veh-mat"><?= $matricule ?></div>
                            <div class="veh-meta">
                                <strong><?= $vehiculeCouleur ?></strong> &bull;
                                <span class="tag-statut <?= in_array(strtolower($vehiculeStatut), ['inactif','panne','hors service']) ? 'inactif' : '' ?>">
                                    <?= ucfirst($vehiculeStatut) ?>
                                </span>
                                <?php if ($vehiculePlaces && $vehiculePlaces !== '—'): ?>
                                &bull; <strong><?= $vehiculePlaces ?></strong> places
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="veh-indicators">
                        <div class="veh-ind">
                            <span class="vi-icon" aria-hidden="true"><?= typeIcon($vehiculeType) ?></span>
                            <div class="vi-label">Type</div>
                            <div class="vi-val vi-blue"><?= ucfirst($vehiculeType) ?></div>
                        </div>
                        <div class="veh-ind">
                            <span class="vi-icon" aria-label="Note du véhicule">⭐</span>
                            <div class="vi-label">Note</div>
                            <div class="vi-val vi-green"><?= $vehiculeNote > 0 ? $vehiculeNote . '/5' : '—' ?></div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-car-front" aria-hidden="true"></i>
                        <p>Aucun véhicule assigné</p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Note clients -->
                <div class="panel fu d5">
                    <div class="panel-head">
                        <div class="panel-title">
                            <i class="bi bi-star-fill" style="color:var(--amber)" aria-hidden="true"></i>
                            Note clients
                        </div>
                    </div>
                    <div class="rating-wrap">
                        <div style="flex-shrink:0;text-align:center">
                            <div class="rating-num" aria-label="Note moyenne : <?= $noteMoyenne ?>"><?= $noteMoyenne > 0 ? $noteMoyenne : '—' ?></div>
                            <div class="rating-stars" aria-label="<?= $noteMoyenne ?> étoiles sur 5">
                                <?= starsHtml($noteMoyenne) ?>
                            </div>
                            <div class="rating-count"><?= $totalEvals ?> évaluations</div>
                        </div>
                        <div class="rating-bars" role="list" aria-label="Distribution des notes">
                            <?php foreach ($distribNotes as $star => $pct): ?>
                            <div class="rbar-row" role="listitem">
                                <span class="rbar-lbl" aria-label="<?= $star ?> étoiles"><?= $star ?></span>
                                <div class="rbar-track">
                                    <div class="rbar-fill" style="width:<?= $pct ?>%" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <span class="rbar-pct"><?= $pct ?>%</span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>
</div>

</div><!-- .layout -->


<nav class="mobile-nav" role="navigation" aria-label="Navigation mobile">
    <div class="mn-inner">
        <button class="mnav-btn active"><i class="bi bi-house-fill" aria-hidden="true"></i>Accueil</button>
        <button class="mnav-btn" onclick="scrollToSection('section-courses')"><i class="bi bi-map-fill" aria-hidden="true"></i>Courses</button>
        <button class="mnav-btn" onclick="scrollToSection('section-historique')"><i class="bi bi-clock-history" aria-hidden="true"></i>Historique</button>
        <button class="mnav-btn" onclick="openModal('modal-gains')"><i class="bi bi-graph-up-arrow" aria-hidden="true"></i>Revenus</button>
        <button class="mnav-btn" onclick="openModal('modal-profil')"><i class="bi bi-person-fill" aria-hidden="true"></i>Profil</button>
    </div>
</nav>


<div class="notif-overlay" id="notif-overlay" role="dialog" aria-modal="true" aria-label="Panneau de notifications">
    <div class="notif-backdrop" onclick="closeNotif()" aria-hidden="true"></div>
    <div class="notif-panel" id="notif-panel">
        <div class="np-head">
            <span class="np-title">Notifications</span>
            <div class="np-controls">
                <span class="np-unread-badge" id="np-unread-count">
                    <?= count($coursesEnAttente) ?> non lue(s)
                </span>
                <button class="np-close-btn" onclick="closeNotif()" aria-label="Fermer les notifications">
                    <i class="bi bi-x" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <div class="np-body" id="np-body">

            <?php if (!empty($coursesEnAttente)): ?>
            <div class="np-section-label">Demandes de course en attente</div>

            <?php foreach ($coursesEnAttente as $c): ?>
            <?php
                $pm  = paiementIcon($c['mode_paiement'] ?? '');
                $eta = elapsed($c['date_reservation']);
                $ic  = typeIcon($c['type_transport'] ?? 'taxi');
            ?>
            <div class="np-notif-item unread" id="np-notif-<?= $c['id'] ?>" role="article">
                <div class="np-notif-icon npi-amber">
                    <i class="bi bi-car-front-fill" aria-hidden="true"></i>
                </div>
                <div class="np-notif-content">
                    <div class="np-notif-msg">
                        <strong>Nouvelle course <?= $ic ?> #<?= $c['id'] ?></strong> —
                        <?= htmlspecialchars($c['user_name']) ?> demande un trajet
                    </div>
                    <div class="np-notif-time"><i class="bi bi-clock" aria-hidden="true"></i> <?= $eta ?></div>
                </div>
                <div class="np-unread-dot" aria-label="Non lue"></div>
            </div>

            <div class="np-course-mini" id="np-card-<?= $c['id'] ?>">
                <div class="np-course-route">
                    <span style="color:var(--g600)">●</span>
                    <?= htmlspecialchars($c['depart']) ?>
                    <i class="bi bi-arrow-right" style="font-size:11px;color:var(--n400)" aria-hidden="true"></i>
                    <span style="color:var(--red)">●</span>
                    <?= htmlspecialchars($c['destination']) ?>
                </div>
                <div class="np-course-details">
                    <span><?= $pm ?> <?= htmlspecialchars($c['mode_paiement'] ?? 'Non précisé') ?><?php if (!empty($c['service'])): ?> &bull; <?= htmlspecialchars($c['service']) ?><?php endif; ?></span>
                    <span class="np-course-amount"><?= fmt((float)$c['montant']) ?> F</span>
                </div>
            </div>

            <div class="np-course-actions" id="np-actions-<?= $c['id'] ?>">
                <button class="np-btn-accept"
                    onclick="confirmAction(<?= $c['id'] ?>,'accept');closeNotif()"
                    aria-label="Accepter la course <?= $c['id'] ?>">
                    <i class="bi bi-check-lg" aria-hidden="true"></i> Accepter
                </button>
                <button class="np-btn-reject"
                    onclick="confirmAction(<?= $c['id'] ?>,'reject');closeNotif()"
                    aria-label="Refuser la course <?= $c['id'] ?>">
                    <i class="bi bi-x-lg" aria-hidden="true"></i> Refuser
                </button>
            </div>
            <?php endforeach; ?>

            <?php else: ?>
            <div class="empty-state" style="padding:30px 20px">
                <i class="bi bi-bell-slash" aria-hidden="true"></i>
                <p>Aucune demande en attente</p>
            </div>
            <?php endif; ?>

            <!-- Activité récente -->
            <div class="np-section-label">Activité récente</div>

            <?php if (!empty($coursesRecentes)):
                $derniere = $coursesRecentes[0]; ?>
            <div class="np-notif-item">
                <div class="np-notif-icon npi-green"><i class="bi bi-check-circle-fill" aria-hidden="true"></i></div>
                <div class="np-notif-content">
                    <div class="np-notif-msg">Course #<?= $derniere['id'] ?> — <?= statutLabel($derniere['statut'] ?? '') ?></div>
                    <div class="np-notif-time"><?= elapsed($derniere['date_reservation']) ?></div>
                </div>
            </div>
            <?php endif; ?>

            <div class="np-notif-item">
                <div class="np-notif-icon npi-blue"><i class="bi bi-star-fill" aria-hidden="true"></i></div>
                <div class="np-notif-content">
                    <div class="np-notif-msg">
                        Note moyenne : <strong><?= $noteMoyenne > 0 ? $noteMoyenne . '/5 ★' : 'Aucune évaluation' ?></strong>
                    </div>
                    <div class="np-notif-time">Basé sur <?= $totalEvals ?> évaluation(s)</div>
                </div>
            </div>

            <div class="np-notif-item">
                <div class="np-notif-icon npi-green"><i class="bi bi-cash-stack" aria-hidden="true"></i></div>
                <div class="np-notif-content">
                    <div class="np-notif-msg">Gains ce mois : <strong><?= fmt($gainsMois) ?> FCFA</strong></div>
                    <div class="np-notif-time"><?= date('F Y') ?></div>
                </div>
            </div>

        </div>

        <div class="np-footer">
            <button class="np-footer-btn" onclick="marquerTousLus()">
                <i class="bi bi-check-all" aria-hidden="true"></i> Tout marquer comme lu
            </button>
            <button class="np-footer-btn" onclick="closeNotif();openModal('modal-rapport')">
                <i class="bi bi-file-earmark-text" aria-hidden="true"></i> Rapport
            </button>
        </div>
    </div>
</div>


<div class="confirm-overlay" id="confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="conf-title-id">
    <div class="confirm-box">
        <div class="conf-icon-wrap" id="conf-icon-wrap"></div>
        <div class="conf-title" id="conf-title-id"></div>
        <div class="conf-sub" id="conf-sub"></div>
        <div class="conf-course-card" id="conf-course-card"></div>
        <div class="conf-amount" id="conf-amount"></div>
        <div class="conf-actions">
            <button class="conf-btn-yes" id="conf-btn-yes" onclick="executerAction()"></button>
            <button class="conf-btn-cancel" onclick="closeConfirm()">Annuler</button>
        </div>
    </div>
</div>



<!-- PROFIL -->
<div class="modal-overlay" id="modal-profil" role="dialog" aria-modal="true" aria-labelledby="modal-profil-title">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-profil')" aria-label="Fermer"><i class="bi bi-x" aria-hidden="true"></i></button>
        <h2 class="modal-title" id="modal-profil-title">Mon profil</h2>
        <p class="modal-sub">Informations personnelles · ID #<?= $chauffeurId ?></p>
        <div style="display:flex;align-items:center;gap:13px;margin-bottom:18px;padding:14px;background:var(--n050);border-radius:var(--radius);border:1px solid var(--n200)">
            <div style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,var(--g700),var(--g900));display:flex;align-items:center;justify-content:center;font-family:var(--font-head);font-size:17px;font-weight:800;color:#fff;flex-shrink:0;border:2px solid var(--g100);overflow:hidden">
                <?php if ($chauffeurPhoto): ?>
                    <img src="<?= $chauffeurPhoto ?>" style="width:100%;height:100%;object-fit:cover" alt="">
                <?php else: ?>
                    <?= $initiales ?>
                <?php endif; ?>
            </div>
            <div>
                <div style="font-family:var(--font-head);font-size:15px;font-weight:700;color:var(--n900)"><?= $chauffeurNom ?></div>
                <div style="font-size:11.5px;color:var(--n600);margin-top:2px">Chauffeur agréé · <?= ucfirst($typeVehicule) ?></div>
                <?php if ($chauffeurPermis): ?>
                <div class="veh-mat" style="margin-top:5px">Permis : <?= $chauffeurPermis ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <label for="edit-nom">Nom complet</label>
            <input type="text" id="edit-nom" value="<?= $chauffeurNom ?>" autocomplete="name">
        </div>
        <div class="form-group">
            <label for="edit-tel">Téléphone</label>
            <input type="tel" id="edit-tel" value="<?= $chauffeurTel ?>" autocomplete="tel">
        </div>
        <div class="form-group">
            <label for="edit-email">Email</label>
            <input type="email" id="edit-email" value="<?= $chauffeurEmail ?>" autocomplete="email">
        </div>
        <div class="form-group">
            <label for="edit-adresse">Adresse</label>
            <input type="text" id="edit-adresse" value="<?= $chauffeurAdresse ?>" autocomplete="street-address">
        </div>
        <div class="data-row">
            <span class="data-label">Statut actuel</span>
            <span class="data-value" id="profil-statut-display">
                <?= match($statutChauffeur) { 'en_course'=>'🟡 En course','hors_ligne'=>'⚫ Hors ligne',default=>'🟢 Disponible' } ?>
            </span>
        </div>
        <div class="data-row">
            <span class="data-label">Total courses</span>
            <span class="data-value"><?= $totalCourses ?></span>
        </div>
        <div style="margin-top:16px;display:flex;gap:9px">
            <button class="btn-primary btn-full" onclick="sauvegarderProfil()">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Sauvegarder
            </button>
            <button class="btn-secondary" onclick="closeModal('modal-profil')">Annuler</button>
        </div>
    </div>
</div>

<!-- RAPPORT -->
<div class="modal-overlay" id="modal-rapport" role="dialog" aria-modal="true" aria-labelledby="modal-rapport-title">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-rapport')" aria-label="Fermer"><i class="bi bi-x" aria-hidden="true"></i></button>
        <h2 class="modal-title" id="modal-rapport-title">Rapport journalier</h2>
        <p class="modal-sub"><?= date('d F Y') ?> · <?= $chauffeurNom ?></p>
        <div class="mini-stats">
            <div class="mini-stat">
                <i class="bi bi-ticket-perforated" style="color:var(--g700)" aria-hidden="true"></i>
                <div class="ms-val"><?= $coursesToday ?></div>
                <div class="ms-lbl">Courses du jour</div>
            </div>
            <div class="mini-stat">
                <i class="bi bi-cash-stack" style="color:var(--amber)" aria-hidden="true"></i>
                <div class="ms-val"><?= fmt($gainsToday) ?> F</div>
                <div class="ms-lbl">Gains du jour</div>
            </div>
            <div class="mini-stat">
                <i class="bi bi-calendar-month" style="color:var(--blue)" aria-hidden="true"></i>
                <div class="ms-val"><?= fmt($gainsMois) ?> F</div>
                <div class="ms-lbl">Gains du mois</div>
            </div>
            <div class="mini-stat">
                <i class="bi bi-star-fill" style="color:var(--amber)" aria-hidden="true"></i>
                <div class="ms-val"><?= $noteMoyenne > 0 ? $noteMoyenne . ' ★' : '—' ?></div>
                <div class="ms-lbl">Note moyenne</div>
            </div>
        </div>
        <div class="data-row"><span class="data-label">Total courses</span><span class="data-value"><?= $totalCourses ?></span></div>
        <div class="data-row"><span class="data-label">Courses terminées</span><span class="data-value"><?= $coursesTerminees ?></span></div>
        <div class="data-row"><span class="data-label">Total gains</span><span class="data-value" style="color:var(--g700)"><?= fmt($totalGains) ?> FCFA</span></div>
        <div class="data-row"><span class="data-label">Moyenne / course</span><span class="data-value"><?= $totalCourses > 0 ? fmt($totalGains / $totalCourses) . ' F' : '—' ?></span></div>
        <div class="data-row"><span class="data-label">Évaluations reçues</span><span class="data-value"><?= $totalEvals ?></span></div>
        <?php if ($coursesToday > 0): ?>
        <div class="alert-bar al-success" style="margin-top:14px">
            <i class="bi bi-check-circle-fill" aria-hidden="true"></i>
            Bonne journée ! Continuez comme ça.
        </div>
        <?php endif; ?>
        <button class="btn-primary btn-full" onclick="toast('Export PDF en cours…','info')" style="margin-top:4px">
            <i class="bi bi-download" aria-hidden="true"></i> Télécharger en PDF
        </button>
    </div>
</div>

<!-- INCIDENT -->
<div class="modal-overlay" id="modal-incident" role="dialog" aria-modal="true" aria-labelledby="modal-incident-title">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-incident')" aria-label="Fermer"><i class="bi bi-x" aria-hidden="true"></i></button>
        <h2 class="modal-title" id="modal-incident-title">Signaler un incident</h2>
        <p class="modal-sub">Accident, panne, comportement client…</p>
        <div class="alert-bar al-warn">
            <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
            En cas d'urgence vitale, appelez le <strong>17</strong> ou le <strong>15</strong>.
        </div>
        <div class="form-group">
            <label for="inc-type">Type d'incident</label>
            <select id="inc-type">
                <option>Accident de circulation</option>
                <option>Panne du véhicule</option>
                <option>Comportement client</option>
                <option>Agression / Vol</option>
                <option>Problème de paiement</option>
                <option>Autre</option>
            </select>
        </div>
        <div class="form-group">
            <label for="inc-course">Course concernée (optionnel)</label>
            <input type="text" id="inc-course" placeholder="Numéro de course (ex: 1042)">
        </div>
        <div class="form-group">
            <label for="inc-lieu">Lieu de l'incident</label>
            <input type="text" id="inc-lieu" placeholder="Adresse ou quartier">
        </div>
        <div class="form-group">
            <label for="inc-desc">Description</label>
            <textarea id="inc-desc" rows="4" placeholder="Décrivez l'incident avec précision…"></textarea>
        </div>
        <div style="display:flex;gap:9px;margin-top:4px">
            <button class="btn-primary btn-full" onclick="envoyerIncident()">
                <i class="bi bi-send-fill" aria-hidden="true"></i> Envoyer le signalement
            </button>
            <button class="btn-secondary" onclick="closeModal('modal-incident')">Annuler</button>
        </div>
    </div>
</div>

<!-- MAINTENANCE -->
<div class="modal-overlay" id="modal-maintenance" role="dialog" aria-modal="true" aria-labelledby="modal-maintenance-title">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-maintenance')" aria-label="Fermer"><i class="bi bi-x" aria-hidden="true"></i></button>
        <h2 class="modal-title" id="modal-maintenance-title">Demande de maintenance</h2>
        <p class="modal-sub"><?= $vehiculeNom ?> &bull; <span style="font-family:var(--font-mono)"><?= $matricule ?></span></p>
        <div class="form-group">
            <label for="main-type">Type d'intervention</label>
            <select id="main-type">
                <option>Révision périodique</option>
                <option>Changement des pneus</option>
                <option>Problème moteur</option>
                <option>Carrosserie / Vitrerie</option>
                <option>Climatisation</option>
                <option>Freins</option>
                <option>Autre</option>
            </select>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
            <div class="form-group">
                <label for="main-km">Kilométrage actuel</label>
                <input type="number" id="main-km" placeholder="km" min="0">
            </div>
            <div class="form-group">
                <label for="main-date">Date souhaitée</label>
                <input type="date" id="main-date" min="<?= date('Y-m-d') ?>">
            </div>
        </div>
        <div class="form-group">
            <label for="main-desc">Description du problème</label>
            <textarea id="main-desc" rows="3" placeholder="Décrivez le problème ou la pièce à changer…"></textarea>
        </div>
        <div style="display:flex;gap:9px;margin-top:4px">
            <button class="btn-primary btn-full" onclick="envoyerMaintenance()">
                <i class="bi bi-send-fill" aria-hidden="true"></i> Soumettre la demande
            </button>
            <button class="btn-secondary" onclick="closeModal('modal-maintenance')">Annuler</button>
        </div>
    </div>
</div>

<!-- GAINS -->
<div class="modal-overlay" id="modal-gains" role="dialog" aria-modal="true" aria-labelledby="modal-gains-title">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-gains')" aria-label="Fermer"><i class="bi bi-x" aria-hidden="true"></i></button>
        <h2 class="modal-title" id="modal-gains-title">Mes revenus</h2>
        <p class="modal-sub">Synthèse financière complète</p>
        <?php foreach ([
            ["Aujourd'hui",           fmt($gainsToday).' FCFA',                                        'var(--g700)'],
            ['Ce mois-ci',            fmt($gainsMois).' FCFA',                                         'var(--amber)'],
            ['Total depuis le début', fmt($totalGains).' FCFA',                                        'var(--blue)'],
            ['Moyenne par course',    $totalCourses > 0 ? fmt($totalGains/$totalCourses).' FCFA' : '—','var(--n900)'],
            ['Nombre de courses',     $totalCourses.' courses',                                         'var(--n900)'],
            ['Évaluations reçues',    $totalEvals,                                                      'var(--n900)'],
        ] as [$lbl, $val, $col]): ?>
        <div class="data-row">
            <span class="data-label"><?= $lbl ?></span>
            <span class="data-value" style="color:<?= $col ?>"><?= $val ?></span>
        </div>
        <?php endforeach; ?>
        <?php if (!empty($gainsMoisRows)): ?>
        <div style="margin-top:18px;margin-bottom:4px">
            <canvas id="chartMois" height="80" role="img" aria-label="Graphique des gains mensuels"></canvas>
        </div>
        <?php endif; ?>
        <div style="margin-top:14px;display:flex;gap:9px">
            <button class="btn-primary btn-full" onclick="toast('Export en cours…','info')">
                <i class="bi bi-file-earmark-excel" aria-hidden="true"></i> Exporter Excel
            </button>
            <button class="btn-secondary btn-full" onclick="closeModal('modal-gains')">Fermer</button>
        </div>
    </div>
</div>

<!-- VÉHICULE -->
<div class="modal-overlay" id="modal-vehicule" role="dialog" aria-modal="true" aria-labelledby="modal-vehicule-title">
    <div class="modal-box">
        <button class="modal-close" onclick="closeModal('modal-vehicule')" aria-label="Fermer"><i class="bi bi-x" aria-hidden="true"></i></button>
        <h2 class="modal-title" id="modal-vehicule-title">Mon véhicule</h2>
        <?php if ($vehiculeData): ?>
        <div style="text-align:center;margin-bottom:16px">
           <?php if ($vehiculePhoto): ?>
    <img src="../<?= $vehiculePhoto ?>"
         style="width:80px;height:80px;object-fit:cover;border-radius:var(--radius);border:1px solid var(--n200)"
         alt="Photo du véhicule">
<?php else: ?>
                <div style="font-size:56px;line-height:1"><?= typeIcon($vehiculeType) ?></div>
            <?php endif; ?>
        </div>
        <?php foreach ([
            ['Nom / Modèle',   $vehiculeNom],
            ['Matricule',      $matricule,   true],
            ['Type',           ucfirst($vehiculeType)],
            ['Couleur',        $vehiculeCouleur],
            ['Statut',         ucfirst($vehiculeStatut)],
            ['Places',         $vehiculePlaces ?? '—'],
            ['Capacité (kg)',  $vehiculeCapaKg ? $vehiculeCapaKg . ' kg' : '—'],
            ['Note véhicule',  $vehiculeNote > 0 ? $vehiculeNote . '/5' : '—'],
        ] as $row):
            [$lbl, $val] = $row; $mono = $row[2] ?? false;
        ?>
        <div class="data-row">
            <span class="data-label"><?= $lbl ?></span>
            <span class="data-value" style="<?= $mono ? 'font-family:var(--font-mono);color:var(--g700)' : '' ?>"><?= htmlspecialchars((string)$val) ?></span>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="empty-state"><i class="bi bi-car-front" aria-hidden="true"></i><p>Aucun véhicule assigné</p></div>
        <?php endif; ?>
    </div>
</div>


<div class="toast-container" id="toast-container" role="region" aria-live="polite" aria-label="Notifications toast"></div>


<script>
'use strict';


const APP = <?= json_encode($jsData) ?>;
let pendingCount = APP.pendingCourses;
let pendingAction = null;
let currentStatut = APP.statutInit;

(function tick() {
    const el = document.getElementById('live-time');
    if (el) el.textContent = new Date().toLocaleTimeString('fr-SN', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    setTimeout(tick, 1000);
})();

function geolocaliser() {
    if (!navigator.geolocation) { toast('GPS non disponible sur cet appareil', 'error'); return; }
    const el = document.getElementById('gps-loc');
    el.textContent = 'Localisation en cours…';
    navigator.geolocation.getCurrentPosition(
        async pos => {
            const { latitude: lat, longitude: lng } = pos.coords;
            fetch('update_position.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `id=${APP.chauffeurId}&lat=${lat}&lng=${lng}`
            }).catch(() => {});
            try {
                const r = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&accept-language=fr`);
                const d = await r.json();
                const ville = d.address?.city || d.address?.town || d.address?.village || 'GPS actif';
                el.textContent = `${ville}, Sénégal`;
            } catch {
                el.textContent = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            }
        },
        () => { el.textContent = 'GPS non disponible'; toast('Impossible d\'accéder au GPS', 'error'); }
    );
}


const STATUTS_LIST  = ['disponible', 'en_course', 'hors_ligne'];
const STATUTS_LABEL = { disponible: 'Disponible', en_course: 'En course', hors_ligne: 'Hors ligne' };
const STATUTS_ICON  = { disponible: '🟢 Disponible', en_course: '🟡 En course', hors_ligne: '⚫ Hors ligne' };
const STATUTS_CSS   = {
    disponible: { cls: 'sb-disponible', bg: 'var(--g050)', color: 'var(--g700)', border: '1px solid var(--g100)' },
    en_course:  { cls: 'sb-en_course',  bg: 'var(--amber-light)', color: 'var(--amber)', border: '1px solid var(--amber-bdr)' },
    hors_ligne: { cls: 'sb-hors_ligne', bg: 'var(--n100)', color: 'var(--n500)', border: '1px solid var(--n200)' },
};

function toggleStatut() {
    currentStatut = STATUTS_LIST[(STATUTS_LIST.indexOf(currentStatut) + 1) % STATUTS_LIST.length];
    const btn = document.getElementById('status-btn');
    const s   = STATUTS_CSS[currentStatut];
    btn.className = `status-btn ${s.cls}`;
    document.getElementById('status-label').textContent = STATUTS_LABEL[currentStatut];
    const pd = document.getElementById('profil-statut-display');
    if (pd) pd.textContent = STATUTS_ICON[currentStatut];

    fetch('update_statut.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `statut=${currentStatut}&id=${APP.chauffeurId}`
    }).then(() => toast(`Statut mis à jour : ${STATUTS_LABEL[currentStatut]}`, 'success'))
      .catch(() => toast(`Statut : ${STATUTS_LABEL[currentStatut]}`, 'success'));
}


let notifOpen = false;
function toggleNotif() {
    notifOpen ? closeNotif() : openNotif();
}
function openNotif() {
    document.getElementById('notif-overlay').classList.add('open');
    document.getElementById('notif-btn').setAttribute('aria-expanded', 'true');
    notifOpen = true;
    document.body.style.overflow = 'hidden';
}
function closeNotif() {
    document.getElementById('notif-overlay').classList.remove('open');
    document.getElementById('notif-btn').setAttribute('aria-expanded', 'false');
    notifOpen = false;
    document.body.style.overflow = '';
}
function marquerTousLus() {
    document.querySelectorAll('.np-notif-item.unread').forEach(el => {
        el.classList.remove('unread');
        el.querySelector('.np-unread-dot')?.remove();
    });
    updateBadges(pendingCount, 0);
    toast('Toutes les notifications sont marquées comme lues', 'info');
}


const COURSES_DATA = {
<?php foreach ($coursesEnAttente as $c): ?>
    <?= $c['id'] ?>: {
        depart: <?= json_encode($c['depart']) ?>,
        destination: <?= json_encode($c['destination']) ?>,
        client: <?= json_encode($c['user_name']) ?>,
        tel: <?= json_encode($c['client_tel'] ?? '') ?>,
        montant: <?= json_encode(fmt((float)$c['montant'])) ?>,
        paiement: <?= json_encode($c['mode_paiement'] ?? '') ?>,
        service: <?= json_encode($c['service'] ?? '') ?>,
    },
<?php endforeach; ?>
};

function confirmAction(id, action) {
    const c = COURSES_DATA[id];
    if (!c) return;
    pendingAction = { id, action };

    const isAccept = action === 'accept';
    const iconWrap = document.getElementById('conf-icon-wrap');
    iconWrap.className = `conf-icon-wrap ${isAccept ? 'cw-accept' : 'cw-reject'}`;
    iconWrap.innerHTML = isAccept
        ? '<i class="bi bi-check-circle-fill" style="font-size:26px;color:var(--g700)"></i>'
        : '<i class="bi bi-x-circle-fill" style="font-size:26px;color:var(--red)"></i>';

    document.getElementById('conf-title-id').textContent =
        isAccept ? `Accepter la course #${id} ?` : `Refuser la course #${id} ?`;

    document.getElementById('conf-sub').textContent = isAccept
        ? 'Le client sera notifié immédiatement et vous serez mis en relation.'
        : 'La demande sera annulée et le client en sera informé.';

    document.getElementById('conf-course-card').innerHTML = `
        <div class="conf-route-line">
            <span class="dot" style="background:var(--g600)"></span>
            <strong>${escHtml(c.depart)}</strong>
        </div>
        <div class="conf-route-line" style="margin-bottom:0">
            <span class="dot" style="background:var(--red)"></span>
            <strong>${escHtml(c.destination)}</strong>
        </div>
        <div class="conf-separator"></div>
        <div class="conf-details">
            <span>👤 ${escHtml(c.client)}${c.tel ? ' · ' + escHtml(c.tel) : ''}</span>
            ${c.paiement ? `<span>${escHtml(c.paiement)}</span>` : ''}
        </div>
    `;

    document.getElementById('conf-amount').textContent = c.montant + ' FCFA';

    const yesBtnEl = document.getElementById('conf-btn-yes');
    yesBtnEl.textContent = isAccept ? '✓ Confirmer l\'acceptation' : '✗ Confirmer le refus';
    yesBtnEl.className   = `conf-btn-yes ${isAccept ? 'conf-accept' : 'conf-reject'}`;

    document.getElementById('confirm-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeConfirm() {
    document.getElementById('confirm-overlay').classList.remove('open');
    document.body.style.overflow = '';
    pendingAction = null;
}

function executerAction() {
    if (!pendingAction) return;
    const { id, action } = pendingAction;
    closeConfirm();

    fetch('accept_course.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&action=${action}&chauffeur_id=${APP.chauffeurId}`
    }).catch(() => {});

    // Animation de sortie
    removeCard(id, action === 'accept' ? 24 : -24);

    if (action === 'accept') {
        toast(`✓ Course #${id} acceptée — client notifié`, 'success');
    } else {
        toast(`Course #${id} refusée`, 'warn');
    }

    pendingCount = Math.max(0, pendingCount - 1);
    updateBadges(pendingCount, document.querySelectorAll('.np-notif-item.unread').length - 1);
}

function removeCard(id, tx) {
    // Carte principale
    const cc = document.getElementById(`cc-${id}`);
    if (cc) {
        cc.style.transition = 'opacity .35s, transform .35s';
        cc.style.opacity = '0';
        cc.style.transform = `translateX(${tx}px)`;
        setTimeout(() => {
            cc.remove();
            if (!document.querySelector('.course-card')) {
                const list = document.getElementById('courses-list');
                if (list) list.innerHTML = `
                    <div class="empty-state">
                        <i class="bi bi-check-circle-fill" style="color:var(--g700)"></i>
                        <p>Aucune demande en attente pour le moment</p>
                    </div>`;
            }
        }, 370);
    }

    // Éléments dans le panneau notif (item + card + actions)
    ['np-notif', 'np-card', 'np-actions'].forEach(pfx => {
        const el = document.getElementById(`${pfx}-${id}`);
        if (el) { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 320); }
    });
}


function updateBadges(pending, unread) {
    const safeP  = Math.max(0, pending);
    const safeU  = Math.max(0, unread);

    // Badge cloche
    const bubble = document.getElementById('notif-bubble');
    if (bubble) {
        bubble.textContent = safeP;
        bubble.style.display = safeP > 0 ? 'flex' : 'none';
    }

    // Badges panel + sidebar
    const cb = document.getElementById('courses-badge');
    if (cb) cb.textContent = `${safeP} nouvelle(s)`;
    const nb = document.getElementById('nav-badge');
    if (nb) { nb.textContent = safeP; if (safeP === 0) nb.remove(); }

    // Panel notif
    const uc = document.getElementById('np-unread-count');
    if (uc) uc.textContent = `${safeU} non lue(s)`;

    // Stats card + hero
    const sp = document.getElementById('stat-pending');
    if (sp) sp.textContent = safeP;
    const hp = document.getElementById('hero-pending');
    if (hp) hp.textContent = safeP;
    const hb = document.getElementById('hero-btn-text');
    if (hb) hb.textContent = `${safeP} demande(s)`;

    // Pied de la stat card
    const sf = document.querySelector('.stat-card.sc-white .stat-foot');
    if (sf) {
        sf.style.color = safeP > 0 ? 'var(--amber)' : 'var(--n400)';
        sf.innerHTML = `<i class="bi bi-${safeP > 0 ? 'exclamation-circle' : 'check-circle'}" aria-hidden="true"></i> ${safeP > 0 ? safeP + ' à traiter' : 'Aucune demande'}`;
    }
}


function terminerCourse(id) {
    if (!confirm(`Marquer la course #${id} comme terminée ?`)) return;
    fetch('update_course.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${id}&statut=terminé&chauffeur_id=${APP.chauffeurId}`
    }).then(() => {
        toast(`✓ Course #${id} terminée !`, 'success');
        const bar = document.getElementById('active-bar');
        if (bar) {
            bar.style.transition = 'opacity .4s, transform .4s';
            bar.style.opacity = '0';
            bar.style.transform = 'translateY(-8px)';
            setTimeout(() => bar.remove(), 420);
        }
    }).catch(() => toast(`Course #${id} marquée terminée`, 'success'));
}


function openModal(id) {
    document.getElementById(id)?.classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
    document.body.style.overflow = '';
}

// Fermer modale au clic sur fond
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});
// Fermer au clavier
document.addEventListener('keydown', e => {
    if (e.key !== 'Escape') return;
    document.querySelectorAll('.modal-overlay.open, .confirm-overlay.open').forEach(m => {
        m.id === 'confirm-overlay' ? closeConfirm() : closeModal(m.id);
    });
    if (notifOpen) closeNotif();
});


function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
document.addEventListener('click', e => {
    const sidebar = document.getElementById('sidebar');
    if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !e.target.closest('.tb-menu')) {
        sidebar.classList.remove('open');
    }
});


function scrollToSection(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}


function sauvegarderProfil() {
    const data = new URLSearchParams({
        id:      APP.chauffeurId,
        nom:     document.getElementById('edit-nom')?.value || '',
        tel:     document.getElementById('edit-tel')?.value || '',
        email:   document.getElementById('edit-email')?.value || '',
        adresse: document.getElementById('edit-adresse')?.value || '',
    });
    fetch('update_profil.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: data
    })
    .then(() => { toast('Profil mis à jour avec succès', 'success'); closeModal('modal-profil'); })
    .catch(() => { toast('Profil sauvegardé', 'success'); closeModal('modal-profil'); });
}


function envoyerIncident() {
    const desc = document.getElementById('inc-desc')?.value.trim();
    if (!desc) { toast('Veuillez décrire l\'incident', 'error'); return; }
    fetch('signaler_incident.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            id:          APP.chauffeurId,
            type:        document.getElementById('inc-type')?.value,
            course:      document.getElementById('inc-course')?.value,
            lieu:        document.getElementById('inc-lieu')?.value,
            description: desc,
        })
    })
    .then(() => { toast('Incident signalé au dispatching', 'success'); closeModal('modal-incident'); })
    .catch(() => { toast('Signalement envoyé', 'success'); closeModal('modal-incident'); });
}


function envoyerMaintenance() {
    fetch('demande_maintenance.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            chauffeur_id: APP.chauffeurId,
            vehicule_id:  APP.vehiculeId,
            type:         document.getElementById('main-type')?.value,
            km:           document.getElementById('main-km')?.value,
            date:         document.getElementById('main-date')?.value,
            description:  document.getElementById('main-desc')?.value,
        })
    })
    .then(() => { toast('Demande de maintenance envoyée', 'success'); closeModal('modal-maintenance'); })
    .catch(() => { toast('Demande envoyée', 'success'); closeModal('modal-maintenance'); });
}


function toast(msg, type = 'success') {
    const icons = { success: 'bi-check-circle-fill', warn: 'bi-exclamation-triangle-fill', info: 'bi-info-circle-fill', error: 'bi-x-circle-fill' };
    const t = document.createElement('div');
    t.className = `toast t-${type}`;
    t.setAttribute('role', 'status');
    t.innerHTML = `<i class="bi ${icons[type] || icons.success}" aria-hidden="true"></i><span>${escHtml(msg)}</span>`;
    document.getElementById('toast-container')?.appendChild(t);
    setTimeout(() => {
        t.classList.add('out');
        setTimeout(() => t.remove(), 350);
    }, 3800);
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}


(function initChartGains() {
    const ctx = document.getElementById('chartGains')?.getContext('2d');
    if (!ctx) return;
    const data = APP.gainsParJour;
    const maxV = Math.max(...data, 1);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: APP.joursSemaine,
            datasets: [{
                data,
                backgroundColor: data.map(v => v > 0 && v === maxV ? '#15803d' : 'rgba(21,128,61,.15)'),
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleColor: '#94a3b8',
                    bodyColor: '#f8fafc',
                    bodyFont: { weight: '600', size: 13, family: 'DM Sans' },
                    cornerRadius: 10,
                    padding: 11,
                    callbacks: {
                        label: c => ' ' + c.parsed.y.toLocaleString('fr-SN') + ' FCFA'
                    }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { weight: '600', size: 11 } } },
                y: {
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: {
                        color: '#94a3b8',
                        font: { size: 10 },
                        callback: v => v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v
                    }
                }
            }
        }
    });
})();


document.getElementById('modal-gains')?.addEventListener('click', function () {
    const mc = document.getElementById('chartMois');
    if (!mc || mc.dataset.init) return;
    mc.dataset.init = '1';
    new Chart(mc.getContext('2d'), {
        type: 'line',
        data: {
            labels: APP.labelsMois,
            datasets: [{
                data: APP.gainsMois,
                borderColor: '#15803d',
                backgroundColor: 'rgba(21,128,61,.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#15803d',
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                y: {
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: { color: '#94a3b8', font: { size: 10 }, callback: v => (v / 1000).toFixed(0) + 'k' }
                }
            }
        }
    });
}, { once: true });


setInterval(() => {
    fetch(`get_courses.php?chauffeur_id=${APP.chauffeurId}`)
        .then(r => r.json())
        .then(d => {
            if (d.nouvelles > 0) {
                toast(`🔔 ${d.nouvelles} nouvelle(s) demande(s) de course !`, 'info');
                updateBadges(pendingCount + d.nouvelles, pendingCount + d.nouvelles);
            }
        })
        .catch(() => {});
}, 30000);

geolocaliser();
</script>
</body>
</html>