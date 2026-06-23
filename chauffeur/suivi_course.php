<?php

session_start();
require_once '../config/database.php';

if (empty($_SESSION['id']) || $_SESSION['role'] !== 'chauffeur') {
    header('Location: ../auth/login.php');
    exit;
}

$chauffeurId = (int) $_SESSION['id'];
$courseId    = (int) ($_GET['id'] ?? 0);

if (!$courseId) {
    header('Location: mes_courses.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "chauffeur"');
$stmt->execute([$chauffeurId]);
$chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$chauffeur) { header('Location: ../auth/login.php'); exit; }

$chauffeurNom  = htmlspecialchars(trim($chauffeur['nom'] ?? 'Chauffeur'));
$chauffeurPhoto= htmlspecialchars($chauffeur['photo'] ?? '');
$parts         = explode(' ', $chauffeurNom);
$initiales     = strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):''));

$stmt = $pdo->prepare(
    "SELECT r.*,
            u.telephone AS client_tel,
            u.photo     AS client_photo,
            u.id        AS client_id,
            u.lat       AS client_lat,
            u.lng       AS client_lng
     FROM reservations r
LEFT JOIN users u ON u.id = r.client_id
WHERE r.id = ? AND r.chauffeur_id = ?"
);
$stmt->execute([$courseId, $chauffeurId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    header('Location: mes_courses.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT m.*, u.nom AS expediteur_nom, u.role AS expediteur_role
     FROM messages_course m
     JOIN users u ON u.id = m.expediteur_id
     WHERE m.course_id = ?
     ORDER BY m.created_at ASC"
);
$stmt->execute([$courseId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

$clientId   = (int)($course['client_id'] ?? 0);
$histoStats = ['total' => 0, 'note_moy' => 0];
$histoCourses = [];

if ($clientId) {
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total, ROUND(AVG(note_client),1) AS note_moy
         FROM reservations
         WHERE client_id = ? AND statut LIKE '%termin%'"
    );
    $stmt->execute([$clientId]);
    $histoStats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT depart, destination, montant, date_reservation, note_client
         FROM reservations
         WHERE client_id = ? AND statut LIKE '%termin%' AND id != ?
         ORDER BY id DESC LIMIT 3"
    );
    $stmt->execute([$clientId, $courseId]);
    $histoCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


function fmt(float $n): string { return number_format($n,0,',',' '); }
function paiIcon(string $m): string {
    return match(strtolower(trim($m))) {
        'orange money','om' => '🟠',
        'wave'              => '🔵',
        'free money','free' => '🟣',
        'espèces','cash'    => '💵',
        default             => '💳',
    };
}
function statutColor(string $s): string {
    $s = strtolower($s);
    if (str_contains($s,'annul'))   return '#FCEBEB;color:#A32D2D';
    if (str_contains($s,'cours'))   return '#E6F1FB;color:#185FA5';
    if (str_contains($s,'attente')) return '#FAEEDA;color:#BA7517';
    return '#EAF3DE;color:#3B6D11';
}
function initiales(string $nom): string {
    $p = explode(' ', trim($nom));
    return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));
}

$clientNom    = htmlspecialchars($course['user_name'] ?? $course['client_id'] ?? 'Client');
$clientIni    = initiales($clientNom);
$clientTel    = htmlspecialchars($course['client_tel'] ?? '');
$clientPhoto  = htmlspecialchars($course['client_photo'] ?? '');
$depart       = htmlspecialchars($course['depart'] ?? '');
$destination  = htmlspecialchars($course['destination'] ?? '');
$montant      = fmt((float)($course['montant'] ?? 0));
$paiement     = htmlspecialchars($course['mode_paiement'] ?? '');
$paiIcon      = paiIcon($paiement);
$statut       = htmlspecialchars($course['statut_course'] ?? $course['statut'] ?? '');
$statutBg     = statutColor($statut);
$ref          = htmlspecialchars($course['reference'] ?? '#'.$courseId);
$eta          = htmlspecialchars($course['eta'] ?? '');
$clientLat    = (float)($course['client_lat'] ?? 14.6937);
$clientLng    = (float)($course['client_lng'] ?? -17.4441);
$chauffeurLat = (float)($chauffeur['lat'] ?? 14.6901);
$chauffeurLng = (float)($chauffeur['lng'] ?? -17.4380);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Suivi Course <?= $ref ?> — Yoon bu Gaw</title>

<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="styles.css">

<style>
html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;
}
.layout, .main {
    width: 100%;
    height: 100%;
    min-height: 100vh;
}
* { box-sizing: border-box; }

.suivi-layout {
    display: grid;
    grid-template-columns: minmax(0,1fr) 390px;
    width: 100%;
    height: 100%;
    overflow: hidden;
}
.map-wrap { position: relative; min-width: 0; }
.side-panel {
    display: flex;
    flex-direction: column;
    background: #fff;
    border-left: 1px solid #e8e8e8;
    overflow: hidden;
    min-width: 0;
}


#map { width: 100%; height: 100%; z-index: 1; }

.map-badge-top {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 10;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}
.mbadge {
    background: rgba(255,255,255,.96);
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}
.mbadge-eta { background: #534AB7; color: #fff; border-color: #534AB7; }
.mbadge-routing {
    background: #1D9E75;
    color: #fff;
    border-color: #1D9E75;
    transition: opacity .3s;
}
.mbadge-routing.loading { opacity: .6; }


.route-progress {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    z-index: 20;
    background: transparent;
    pointer-events: none;
}
.route-progress-bar {
    height: 100%;
    width: 0;
    background: linear-gradient(90deg, #534AB7, #1D9E75);
    transition: width .4s ease;
    border-radius: 0 2px 2px 0;
}


.side-topbar {
    padding: 14px 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}
.back-btn {
    width: 34px; height: 34px;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #444;
    flex-shrink: 0;
}
.side-course-title { font-size: 14px; font-weight: 600; }
.side-course-ref { font-size: 11px; color: #888; }
.statut-badge {
    font-size: 11px;
    font-weight: 500;
    padding: 4px 10px;
    border-radius: 20px;
    background: <?= explode(';',$statutBg)[0] ?>;
    color: <?= substr($statutBg, strpos($statutBg,'color:')+6) ?>;
    white-space: nowrap;
    margin-left: auto;
}


.side-section { padding: 14px 16px; border-bottom: 1px solid #eee; }
.sec-label { font-size: 10px; text-transform: uppercase; color: #999; margin-bottom: 10px; }

/* ===== CLIENT ===== */
.client-row { display: flex; align-items: center; gap: 10px; }
.client-ava {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: #CECBF6;
    color: #3C3489;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    overflow: hidden;
    flex-shrink: 0;
}
.client-ava img { width: 100%; height: 100%; object-fit: cover; }
.client-name { font-size: 14px; font-weight: 600; }
.client-loc { font-size: 12px; color: #888; }
.client-actions { margin-left: auto; display: flex; gap: 6px; }
.cta-btn {
    width: 34px; height: 34px;
    border-radius: 8px;
    border: 1px solid #ddd;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    color: #555;
    background: #fff;
    cursor: pointer;
}
.cta-btn.green { background: #EAF3DE; color: #3B6D11; border-color: #c5dfa8; }

/* ===== ROUTE ===== */
.route-box { display: flex; flex-direction: column; gap: 6px; }
.route-line { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.dot { width: 10px; height: 10px; border-radius: 50%; }
.dot-g { background: #1D9E75; }
.dot-r { background: #E24B4A; }
.dashed-v { border-left: 2px dashed #ccc; height: 12px; }

.stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.stat-card { background: #f8f8f8; border-radius: 10px; padding: 10px; }
.stat-val { font-size: 17px; font-weight: 700; }
.stat-lbl { font-size: 11px; color: #888; }


.side-tabs { display: flex; border-bottom: 1px solid #eee; }
.stab {
    flex: 1;
    text-align: center;
    padding: 10px;
    font-size: 12px;
    cursor: pointer;
    border: none;
    background: none;
}
.stab.active {
    color: #534AB7;
    border-bottom: 2px solid #534AB7;
    font-weight: 600;
}
.tab-pane { display: none; flex-direction: column; flex: 1; overflow: hidden; }
.tab-pane.active { display: flex; }


.chat-area {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #f7f7f7;
}
.msg { display: flex; flex-direction: column; max-width: 85%; }
.msg.sent { align-self: flex-end; }
.msg.recv { align-self: flex-start; }
.bubble { padding: 8px 12px; border-radius: 14px; word-break: break-word; font-size: 13px; }
.bubble.sent { background: #534AB7; color: #fff; }
.bubble.recv { background: #fff; border: 1px solid #e8e8e8; }
.msg-time { font-size: 10px; color: #aaa; margin-top: 2px; text-align: right; }
.msg-day {
    text-align: center;
    font-size: 11px;
    color: #aaa;
    background: #eee;
    border-radius: 20px;
    padding: 3px 12px;
    align-self: center;
    margin: 4px 0;
}
.chat-input-bar {
    padding: 10px;
    display: flex;
    gap: 8px;
    border-top: 1px solid #eee;
}
.chat-input-bar input {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 8px 14px;
    font-size: 13px;
    outline: none;
}
.chat-input-bar input:focus { border-color: #534AB7; }
.send-btn {
    width: 38px; height: 38px;
    border-radius: 50%;
    border: none;
    background: #534AB7;
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}


#pane-quick {
    padding: 12px;
    gap: 8px;
    overflow-y: auto;
}
.quick-msg-btn {
    background: #f5f5f5;
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    cursor: pointer;
    text-align: left;
    transition: background .15s;
    width: 100%;
}
.quick-msg-btn:hover { background: #ECECFB; border-color: #534AB7; color: #534AB7; }

#pane-infos {
    padding: 12px;
    overflow-y: auto;
    gap: 10px;
}
.info-row {
    display: flex;
    justify-content: space-between;
    font-size: 13px;
    padding: 6px 0;
    border-bottom: 1px dashed #f0f0f0;
}
.info-lbl { color: #888; }
.info-val { font-weight: 600; }


.nav-instruction {
    position: absolute;
    bottom: 16px;
    left: 16px;
    right: 16px;
    z-index: 10;
    background: rgba(255,255,255,.97);
    border-radius: 12px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 4px 16px rgba(0,0,0,.15);
}
.nav-arrow-box {
    width: 40px; height: 40px;
    border-radius: 8px;
    background: #534AB7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
}
.nav-txt-main { font-size: 13px; font-weight: 600; }
.nav-txt-sub { font-size: 11px; color: #666; }


@media (max-width:992px) {
    .suivi-layout { grid-template-columns: 1fr; grid-template-rows: 50vh 50vh; }
    .side-panel { border-left: none; border-top: 1px solid #eee; }
}

@media (max-width:768px) {
    .suivi-layout { grid-template-columns: 1fr; grid-template-rows: 42vh 58vh; }
    .map-badge-top { top: 8px; left: 8px; right: 8px; }
    .mbadge { font-size: 11px; padding: 5px 8px; }
    .nav-instruction { left: 8px; right: 8px; bottom: 8px; padding: 10px; }
    .client-row { flex-wrap: wrap; }
    .client-actions { width: 100%; margin-top: 10px; margin-left: 0; }
}
@media (max-width:480px) {
    .suivi-layout { grid-template-rows: 38vh 62vh; }
    .stats-grid { grid-template-columns: 1fr; }
    .side-topbar { padding: 10px; }
    .stat-val { font-size: 15px; }
    .stab { font-size: 11px; padding: 8px; }
}
</style>
</head>
<body>
<div class="layout">
<?php include_once "sidebar.php"; ?>

<div class="main" style="padding:0;overflow:hidden">
    <header class="topbar">
        <div class="tb-left">
            <button class="tb-menu" onclick="toggleSidebar()" aria-label="Menu"><i class="bi bi-list"></i></button>
            <div>
                <div class="tb-title">Suivi Course</div>
                <div class="tb-breadcrumb"><?= date('l d F Y') ?></div>
            </div>
        </div>
        <div class="tb-right">
            <div class="tb-avatar" title="<?= $chauffeurNom ?>">
                <?php if ($chauffeurPhoto): ?>
                    <img src="<?= $chauffeurPhoto ?>" alt="">
                <?php else: ?>
                    <?= $initiales ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <div class="suivi-layout">

      
        <div class="map-wrap">

            <!-- Barre de progression routing -->
            <div class="route-progress">
                <div class="route-progress-bar" id="route-progress-bar"></div>
            </div>

            <div id="map"></div>

            <!-- Badges -->
            <div class="map-badge-top">
                <div class="mbadge">
                    <i class="bi bi-navigation-fill" style="color:#534AB7"></i>
                    Navigation active
                </div>
                <div class="mbadge mbadge-routing" id="badge-routing">
                    <i class="bi bi-map-fill"></i>
                    <span id="badge-routing-txt">Calcul itinéraire…</span>
                </div>
                <?php if ($eta): ?>
                    <div class="mbadge mbadge-eta">
                        <i class="bi bi-clock-fill"></i> ETA : <?= $eta ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Instruction navigation en bas -->
            <div class="nav-instruction" id="nav-instruction" style="display:none">
                <div class="nav-arrow-box" id="nav-arrow">
                    <i class="bi bi-arrow-up" id="nav-icon"></i>
                </div>
                <div>
                    <div class="nav-txt-main" id="nav-street">Calcul en cours…</div>
                    <div class="nav-txt-sub" id="nav-dist-next">—</div>
                </div>
            </div>
        </div>

        
        <div class="side-panel">

            <!-- Topbar -->
            <div class="side-topbar">
                <a href="mes_courses.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>
                <div style="flex:1;min-width:0">
                    <div class="side-course-title">Course <?= $ref ?></div>
                    <div class="side-course-ref"><?= $depart ?> → <?= $destination ?></div>
                </div>
                <span class="statut-badge"><?= ucfirst($statut) ?></span>
            </div>

            <!-- Client -->
            <div class="side-section">
                <div class="sec-label">Client</div>
                <div class="client-row">
                    <div class="client-ava">
                        <?php if ($clientPhoto): ?>
                            <img src="<?= $clientPhoto ?>" alt="">
                        <?php else: ?>
                            <?= $clientIni ?>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div class="client-name"><?= $clientNom ?></div>
                        <div class="client-loc">
                            <i class="bi bi-geo-alt-fill" style="color:#E24B4A;font-size:12px"></i>
                            <span id="client-dist">Calcul…</span>
                        </div>
                    </div>
                    <div class="client-actions">
                        <?php if ($clientTel): ?>
                            <a href="tel:<?= $clientTel ?>" class="cta-btn green" title="Appeler">
                                <i class="bi bi-telephone-fill"></i>
                            </a>
                        <?php endif; ?>
                        <button class="cta-btn" onclick="focusClient()" title="Centrer sur client">
                            <i class="bi bi-crosshair2"></i>
                        </button>
                        <button class="cta-btn" onclick="focusMe()" title="Ma position">
                            <i class="bi bi-geo-fill"></i>
                        </button>
                        <button class="cta-btn" onclick="fitRoute()" title="Vue itinéraire">
                            <i class="bi bi-arrows-fullscreen"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Itinéraire -->
            <div class="side-section">
                <div class="sec-label">Itinéraire</div>
                <div class="route-box">
                    <div class="route-line">
                        <span class="dot dot-g"></span>
                        <span><?= $depart ?></span>
                    </div>
                    <div class="route-line">
                        <span class="route-sep"><span class="dashed-v"></span></span>
                        <span style="font-size:11px;color:#aaa" id="route-info">—</span>
                    </div>
                    <div class="route-line">
                        <span class="dot dot-r"></span>
                        <span><?= $destination ?></span>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="side-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-val" id="stat-dist">—</div>
                        <div class="stat-lbl">Distance client</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val" id="stat-eta">—</div>
                        <div class="stat-lbl">Arrivée estimée</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val"><?= $montant ?> F</div>
                        <div class="stat-lbl">Montant</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-val"><?= $paiIcon ?> <?= $paiement ?: '—' ?></div>
                        <div class="stat-lbl">Paiement</div>
                    </div>
                </div>
            </div>

            <!-- Onglets -->
            <div class="side-tabs">
                <button class="stab active" onclick="switchTab('chat')">Messages</button>
                <button class="stab" onclick="switchTab('quick')">Rapides</button>
                <button class="stab" onclick="switchTab('infos')">Infos</button>
            </div>

            <!-- CHAT -->
            <div class="tab-pane active" id="pane-chat">
                <div class="chat-area" id="chat-area">
                    <div class="msg-day">Aujourd'hui · Course <?= $ref ?></div>
                    <?php foreach ($messages as $m): ?>
                        <?php $side = ($m['expediteur_role'] === 'chauffeur') ? 'sent' : 'recv'; ?>
                        <div class="msg <?= $side ?>">
                            <div class="bubble <?= $side ?>"><?= htmlspecialchars($m['contenu']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="chat-input-bar">
                    <input type="text" id="msg-input" placeholder="Écrire un message…"
                           onkeydown="if(event.key==='Enter')sendMsg()">
                    <button class="send-btn" onclick="sendMsg()">
                        <i class="bi bi-send-fill"></i>
                    </button>
                </div>
            </div>

            <!-- QUICK MESSAGES -->
            <div class="tab-pane" id="pane-quick">
                <?php
                $quickMsgs = [
                    "J'arrive dans quelques minutes ✅",
                    "Je suis devant vous 🚕",
                    "Je cherche une place pour me garer",
                    "Pouvez-vous sortir maintenant ?",
                    "Il y a des embouteillages, je serai en retard",
                    "Je suis bloqué au feu, j'arrive",
                    "Appelez-moi si vous ne me voyez pas 📞",
                    "Course démarrée, bonne route ! 🛣️",
                    "Nous approchons de la destination",
                    "Arrivée dans 2 minutes ⏱️",
                ];
                foreach ($quickMsgs as $qm): ?>
                    <button class="quick-msg-btn" onclick="sendQuick(<?= json_encode($qm) ?>)">
                        <?= htmlspecialchars($qm) ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- INFOS -->
            <div class="tab-pane" id="pane-infos">
                <div class="info-row">
                    <span class="info-lbl">Référence</span>
                    <span class="info-val"><?= $ref ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Type</span>
                    <span class="info-val"><?= htmlspecialchars($course['type_transport'] ?? '—') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Service</span>
                    <span class="info-val"><?= htmlspecialchars($course['service'] ?? '—') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Date réservation</span>
                    <span class="info-val"><?= htmlspecialchars($course['date_reservation'] ?? '—') ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Paiement</span>
                    <span class="info-val"><?= $paiIcon ?> <?= $paiement ?: '—' ?></span>
                </div>
                <div class="info-row">
                    <span class="info-lbl">Montant</span>
                    <span class="info-val"><?= $montant ?> FCFA</span>
                </div>
                <?php if ($course['note_client'] ?? ''): ?>
                <div class="info-row" style="flex-direction:column;gap:4px">
                    <span class="info-lbl">Note client</span>
                    <span class="info-val" style="font-size:12px;font-weight:400;color:#444">
                        <?= htmlspecialchars($course['note_client']) ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if ($histoStats['total'] > 0): ?>
                <div style="margin-top:10px;padding-top:10px;border-top:1px solid #f0f0f0">
                    <div class="sec-label">Historique client</div>
                    <div class="info-row">
                        <span class="info-lbl">Courses terminées</span>
                        <span class="info-val"><?= $histoStats['total'] ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-lbl">Note moyenne</span>
                        <span class="info-val">⭐ <?= $histoStats['note_moy'] ?? '—' ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /side-panel -->

    </div><!-- /suivi-layout -->

    <nav class="mobile-nav">
        <div class="mn-inner">
            <a class="mnav-item" href="dashboard.php"><i class="bi bi-house-fill"></i>Accueil</a>
            <a class="mnav-item active" href="mes_courses.php"><i class="bi bi-map-fill"></i>Courses</a>
            <a class="mnav-item" href="revenus.php"><i class="bi bi-graph-up-arrow"></i>Revenus</a>
            <a class="mnav-item" href="profil.php"><i class="bi bi-person-fill"></i>Profil</a>
        </div>
    </nav>
</div><!-- /main -->
</div><!-- /layout -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>

const COURSE_ID     = <?= (int)$courseId ?>;
const CLIENT_LAT    = <?= (float)$clientLat ?>;
const CLIENT_LNG    = <?= (float)$clientLng ?>;
const CHAUFFEUR_LAT = <?= (float)$chauffeurLat ?>;
const CHAUFFEUR_LNG = <?= (float)$chauffeurLng ?>;
const POLLING_MS    = 5000;


const map = L.map('map', { zoomControl: false }).setView(
    [(CLIENT_LAT + CHAUFFEUR_LAT) / 2, (CLIENT_LNG + CHAUFFEUR_LNG) / 2], 15
);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
}).addTo(map);

L.control.zoom({ position: 'bottomleft' }).addTo(map);


const iconDriver = L.divIcon({
    className: '',
    html: `<div style="
        width:42px;height:42px;border-radius:50%;
        background:#534AB7;border:3px solid #fff;
        display:flex;align-items:center;justify-content:center;
        font-size:20px;box-shadow:0 2px 10px rgba(83,74,183,.5)">🚕</div>`,
    iconSize: [42, 42],
    iconAnchor: [21, 21],
});

const iconClient = L.divIcon({
    className: '',
    html: `<div style="position:relative;width:42px;height:52px">
        <div style="
            position:absolute;top:0;left:0;
            width:42px;height:42px;border-radius:50%;
            background:#E24B4A;opacity:.2;
            animation:pulse 2s infinite"></div>
        <div style="
            position:absolute;top:0;left:0;
            width:42px;height:42px;border-radius:50%;
            background:#E24B4A;border:3px solid #fff;
            display:flex;align-items:center;justify-content:center;
            font-size:20px;box-shadow:0 2px 10px rgba(226,75,74,.5)">👤</div>
    </div>`,
    iconSize: [42, 42],
    iconAnchor: [21, 21],
});

const clientName = <?= json_encode($clientNom) ?>;

const markerDriver = L.marker([CHAUFFEUR_LAT, CHAUFFEUR_LNG], { icon: iconDriver })
    .addTo(map)
    .bindPopup('Vous — chauffeur');

const markerClient = L.marker([CLIENT_LAT, CLIENT_LNG], { icon: iconClient })
    .addTo(map)
    .bindPopup(clientName + ' — client');

/* Animation pulse CSS */
const styleEl = document.createElement('style');
styleEl.textContent = `@keyframes pulse {
    0%   { transform: scale(1); opacity: .2; }
    50%  { transform: scale(1.6); opacity: .05; }
    100% { transform: scale(1); opacity: .2; }
}`;
document.head.appendChild(styleEl);


let routePolyline  = null;   
let routeWalked    = null;   
let lastRouteCoords = [];    
let routeSteps     = [];     
let currentStepIdx = 0;
let isFetchingRoute= false;

const OSRM_BASE = 'https://router.project-osrm.org/route/v1/driving';


async function fetchRoute(fromLat, fromLng, toLat, toLng) {
    if (isFetchingRoute) return;
    isFetchingRoute = true;

    const badge = document.getElementById('badge-routing');
    const badgeTxt = document.getElementById('badge-routing-txt');
    const bar = document.getElementById('route-progress-bar');

    if (badge) badge.classList.add('loading');
    if (badgeTxt) badgeTxt.textContent = 'Calcul…';
    if (bar) bar.style.width = '40%';

    const url = `${OSRM_BASE}/${fromLng},${fromLat};${toLng},${toLat}`
              + `?overview=full&geometries=geojson&steps=true&annotations=false`;

    try {
        const res  = await fetch(url);
        const data = await res.json();

        if (data.code !== 'Ok' || !data.routes || !data.routes.length) {
            throw new Error('Pas d\'itinéraire OSRM');
        }

        const route    = data.routes[0];
        const coords   = route.geometry.coordinates; 
        const distM    = route.distance;            
        const durS     = route.duration;             

       
        const latLngs = coords.map(c => [c[1], c[0]]);
        lastRouteCoords = latLngs;

        
        if (routePolyline) map.removeLayer(routePolyline);
        routePolyline = L.polyline(latLngs, {
            color: '#534AB7',
            weight: 5,
            opacity: .85,
        }).addTo(map);

        
        map.fitBounds(routePolyline.getBounds(), { padding: [60, 60] });

        /* Étapes de navigation */
        routeSteps = [];
        if (route.legs && route.legs[0] && route.legs[0].steps) {
            routeSteps = route.legs[0].steps;
            currentStepIdx = 0;
            updateNavInstruction();
        }

       
        updateStatsFromRoute(distM, durS);

        
        if (badge) badge.classList.remove('loading');
        if (badgeTxt) {
            badgeTxt.textContent = fmtDist(distM) + ' · ' + fmtDurMin(durS);
        }
        if (bar) {
            bar.style.width = '100%';
            setTimeout(() => { bar.style.width = '0'; }, 600);
        }

    } catch (err) {
        console.warn('OSRM routing error, fallback ligne droite:', err);

        /* Fallback : ligne droite si OSRM échoue */
        if (routePolyline) map.removeLayer(routePolyline);
        routePolyline = L.polyline([[fromLat, fromLng], [toLat, toLng]], {
            color: '#534AB7',
            weight: 4,
            opacity: .65,
            dashArray: '8,5',
        }).addTo(map);

        if (badge) badge.classList.remove('loading');
        if (badgeTxt) badgeTxt.textContent = 'Ligne directe';
        if (bar) bar.style.width = '0';

        /* Stats haversine */
        const dist = haversine(fromLat, fromLng, toLat, toLng);
        updateStatsRaw(dist, dist / 50);
    }

    isFetchingRoute = false;
}


const MANEUVER_ICONS = {
    'turn-right'         : 'bi-arrow-turn-right',
    'turn-left'          : 'bi-arrow-turn-left',
    'turn-sharp-right'   : 'bi-arrow-90deg-right',
    'turn-sharp-left'    : 'bi-arrow-90deg-left',
    'turn-slight-right'  : 'bi-arrow-up-right',
    'turn-slight-left'   : 'bi-arrow-up-left',
    'continue'           : 'bi-arrow-up',
    'merge'              : 'bi-sign-merge-right',
    'ramp'               : 'bi-arrow-up',
    'fork'               : 'bi-sign-split',
    'end-of-road-right'  : 'bi-arrow-turn-right',
    'end-of-road-left'   : 'bi-arrow-turn-left',
    'depart'             : 'bi-geo-fill',
    'arrive'             : 'bi-flag-fill',
    'roundabout'         : 'bi-arrow-clockwise',
    'rotary'             : 'bi-arrow-clockwise',
    'roundabout-turn'    : 'bi-arrow-clockwise',
    'notification'       : 'bi-info-circle',
    'default'            : 'bi-arrow-up',
};

function getManeuverIcon(step) {
    if (!step || !step.maneuver) return MANEUVER_ICONS['default'];
    const type     = step.maneuver.type     || '';
    const modifier = step.maneuver.modifier || '';
    const key = modifier ? `${type}-${modifier}` : type;
    return MANEUVER_ICONS[key] || MANEUVER_ICONS[type] || MANEUVER_ICONS['default'];
}

function updateNavInstruction() {
    const box     = document.getElementById('nav-instruction');
    const iconEl  = document.getElementById('nav-icon');
    const street  = document.getElementById('nav-street');
    const distNext= document.getElementById('nav-dist-next');

    if (!routeSteps.length || currentStepIdx >= routeSteps.length) {
        if (box) box.style.display = 'none';
        return;
    }

    const step = routeSteps[currentStepIdx];
    const next = routeSteps[currentStepIdx + 1];

    const streetName = step.name || 'Continuer tout droit';
    const dist       = step.distance || 0;
    const iconClass  = getManeuverIcon(step);

    if (box)      box.style.display = 'flex';
    if (iconEl)   { iconEl.className = 'bi ' + iconClass; }
    if (street)   street.textContent = streetName;
    if (distNext) distNext.textContent = fmtDist(dist)
                  + (next && next.name ? ' · puis ' + next.name : '');
}


function advanceStep(myLat, myLng) {
    if (!routeSteps.length || currentStepIdx >= routeSteps.length) return;

    const step = routeSteps[currentStepIdx];
    if (!step.maneuver || !step.maneuver.location) return;

    const [stLng, stLat] = step.maneuver.location;
    const distToStep = haversine(myLat, myLng, stLat, stLng);

    /* Passer à l'étape suivante si on est à moins de 30m */
    if (distToStep < 30 && currentStepIdx < routeSteps.length - 1) {
        currentStepIdx++;
        updateNavInstruction();
    }
}


function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2
            + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function fmtDist(m) {
    return m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(1) + ' km';
}

function fmtDurMin(s) {
    const m = Math.ceil(s / 60);
    return m <= 1 ? '< 1 min' : m + ' min';
}

function updateStatsFromRoute(distM, durS) {
    const el1 = document.getElementById('stat-dist');
    const el2 = document.getElementById('stat-eta');
    const el3 = document.getElementById('client-dist');
    const el4 = document.getElementById('route-info');
    if (el1) el1.textContent = fmtDist(distM);
    if (el2) el2.textContent = fmtDurMin(durS);
    if (el3) el3.textContent = fmtDist(distM) + ' de vous';
    if (el4) el4.textContent = fmtDist(distM) + ' · ' + fmtDurMin(durS);
}

function updateStatsRaw(distM, speedMs) {
    const durS = distM / speedMs;
    updateStatsFromRoute(distM, durS);
}


let myLat = CHAUFFEUR_LAT, myLng = CHAUFFEUR_LNG;
let lastRouteFetch = 0;
const ROUTE_REFETCH_MS = 15000; /* Recalcule l'itinéraire toutes les 15 sec */

/* Calcul initial */
fetchRoute(myLat, myLng, CLIENT_LAT, CLIENT_LNG);

if (navigator.geolocation) {
    navigator.geolocation.watchPosition(pos => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        /* Déplacer le marqueur chauffeur */
        markerDriver.setLatLng([lat, lng]);

        myLat = lat;
        myLng = lng;

       
        advanceStep(myLat, myLng);

        /* Recalculer l'itinéraire OSRM toutes les 15s */
        const now = Date.now();
        if (now - lastRouteFetch >= ROUTE_REFETCH_MS) {
            lastRouteFetch = now;
            const cPos = markerClient.getLatLng();
            fetchRoute(myLat, myLng, cPos.lat, cPos.lng);
        }

        /* Envoyer position au serveur */
        sendMyPosition(lat, lng);

    }, err => {
        console.warn('GPS error:', err);
    }, {
        enableHighAccuracy: true,
        maximumAge: 3000,
        timeout: 10000,
    });
}

async function sendMyPosition(lat, lng) {
    try {
        await fetch('update_position.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: COURSE_ID, lat, lng, role: 'chauffeur' }),
        });
    } catch(e) {}
}


async function pollClientPosition() {
    try {
        const r = await fetch(`get_position.php?course_id=${COURSE_ID}&role=client`);
        const d = await r.json();

        if (d.lat && d.lng) {
            const lat = parseFloat(d.lat);
            const lng = parseFloat(d.lng);

            const old = markerClient.getLatLng();
            markerClient.setLatLng([lat, lng]);

            /* Recalculer si le client a bougé de > 20m */
            const moved = haversine(old.lat, old.lng, lat, lng);
            if (moved > 20) {
                fetchRoute(myLat, myLng, lat, lng);
            }
        }
    } catch(e) {}
}
setInterval(pollClientPosition, POLLING_MS);


function focusClient() { map.setView(markerClient.getLatLng(), 17); }
function focusMe()     { map.setView(markerDriver.getLatLng(), 17); }
function fitRoute() {
    if (lastRouteCoords.length > 1) {
        map.fitBounds(L.polyline(lastRouteCoords).getBounds(), { padding: [60, 60] });
    } else {
        map.fitBounds(L.latLngBounds(markerDriver.getLatLng(), markerClient.getLatLng()), { padding: [60, 60] });
    }
}


function switchTab(name) {
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
    const pane = document.getElementById('pane-' + name);
    if (pane) pane.classList.add('active');
    event.currentTarget.classList.add('active');
    if (name === 'chat') {
        const a = document.getElementById('chat-area');
        if (a) a.scrollTop = a.scrollHeight;
    }
}


let lastMsgId = <?= isset($messages) && !empty($messages) ? (int)end($messages)['id'] : 0 ?>;

function escHtml(s) {
    return String(s)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function appendMsg(txt, side) {
    const area = document.getElementById('chat-area');
    if (!area) return;
    const div = document.createElement('div');
    div.className = 'msg ' + side;
    const now = new Date().toLocaleTimeString([], { hour:'2-digit', minute:'2-digit' });
    div.innerHTML = `<div class="bubble ${side}">${escHtml(txt)}</div>
                     <div class="msg-time">${now}</div>`;
    area.appendChild(div);
    area.scrollTop = area.scrollHeight;
}

async function sendMsg() {
    const inp = document.getElementById('msg-input');
    const txt = inp.value.trim();
    if (!txt) return;
    inp.value = '';
    appendMsg(txt, 'sent');
    await fetch('send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ course_id: COURSE_ID, contenu: txt }),
    });
}

async function sendQuick(txt) {
    appendMsg(txt, 'sent');
    /* Revenir au chat */
    document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.stab').forEach(b => b.classList.remove('active'));
    document.getElementById('pane-chat').classList.add('active');
    document.querySelector('.stab').classList.add('active');
    const a = document.getElementById('chat-area');
    if (a) a.scrollTop = a.scrollHeight;

    await fetch('send_message.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ course_id: COURSE_ID, contenu: txt }),
    });
}

async function pollMessages() {
    try {
        const r = await fetch(`get_messages.php?course_id=${COURSE_ID}&after=${lastMsgId}`);
        const msgs = await r.json();
        msgs.forEach(m => {
            if (m.expediteur_role !== 'chauffeur') {
                appendMsg(m.contenu, 'recv');
            }
            if (m.id > lastMsgId) lastMsgId = m.id;
        });
    } catch(e) {}
}
setInterval(pollMessages, 3000);


(function() {
    const a = document.getElementById('chat-area');
    if (a) a.scrollTop = a.scrollHeight;
})();
</script>
</body>
</html>