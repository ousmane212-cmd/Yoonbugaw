<?php
/* ══════════════════════════════════════════════════════════════════
   suivi_course.php — Yoon bu Gaw · Espace Chauffeur
   Suivi temps réel : localisation client + messagerie
══════════════════════════════════════════════════════════════════ */
session_start();
require_once '../config/database.php';

/* ── AUTH ─────────────────────────────────────────────────────── */
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

/* ── CHAUFFEUR ────────────────────────────────────────────────── */
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "chauffeur"');
$stmt->execute([$chauffeurId]);
$chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$chauffeur) { header('Location: ../auth/login.php'); exit; }

$chauffeurNom  = htmlspecialchars(trim($chauffeur['nom'] ?? 'Chauffeur'));
$chauffeurPhoto= htmlspecialchars($chauffeur['photo'] ?? '');
$parts         = explode(' ', $chauffeurNom);
$initiales     = strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):''));

/* ── COURSE ───────────────────────────────────────────────────── */
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

/* ── MESSAGES ─────────────────────────────────────────────────── */
$stmt = $pdo->prepare(
    "SELECT m.*, u.nom AS expediteur_nom, u.role AS expediteur_role
     FROM messages_course m
     JOIN users u ON u.id = m.expediteur_id
     WHERE m.course_id = ?
     ORDER BY m.created_at ASC"
);
$stmt->execute([$courseId]); // ✅ correction ici
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── HISTORIQUE CLIENT ────────────────────────────────────────── */
$clientId = (int)($course['client_id'] ?? 0);
$histoStats = ['total' => 0, 'note_moy' => 0];

if ($clientId) {

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) AS total, ROUND(AVG(note_client),1) AS note_moy
         FROM reservations
         WHERE client_id = ? AND statut LIKE '%termin%'"
    );
    $stmt->execute([$clientId]); // ✅ correction ici
    $histoStats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare(
        "SELECT depart, destination, montant, date_reservation, note_client
         FROM reservations
         WHERE client_id = ? AND statut LIKE '%termin%' AND id != ?
         ORDER BY id DESC LIMIT 3"
    );
    $stmt->execute([$clientId, $courseId]); // ✅ correction ici
    $histoCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/* ── HELPERS ──────────────────────────────────────────────────── */
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

$clientNom = htmlspecialchars($course['user_name'] ?? $course['client_id'] ?? 'Client');
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
/* ── Layout ── */
.suivi-layout {
    display: grid;
    grid-template-columns: 1fr 390px;
    height: calc(100vh - 0px);
    overflow: hidden;
}
@media (max-width: 900px) {
    .suivi-layout { grid-template-columns: 1fr; grid-template-rows: 45vh 1fr; }
}

/* ── Carte ── */
#map {
    width: 100%;
    height: 100%;
    z-index: 1;
}
.map-wrap { position: relative; flex: 1; }

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
    font-family: 'Outfit', sans-serif;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}
.mbadge-eta {
    background: #534AB7;
    color: #fff;
    border-color: #534AB7;
}

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
    font-family: 'Outfit', sans-serif;
}
.nav-arrow-box {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: #534AB7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #fff;
    font-size: 20px;
    flex-shrink: 0;
}
.nav-txt-main { font-size: 13px; font-weight: 600; color: #1a1a1a; }
.nav-txt-sub  { font-size: 11px; color: #666; margin-top: 2px; }

/* ── Panel latéral ── */
.side-panel {
    display: flex;
    flex-direction: column;
    background: var(--bs-body-bg, #fff);
    border-left: 1px solid #e8e8e8;
    overflow: hidden;
}

.side-topbar {
    padding: 14px 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
}
.side-topbar .back-btn {
    width: 34px; height: 34px;
    border-radius: 8px;
    border: 1px solid #ddd;
    background: transparent;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    color: #444;
    text-decoration: none;
    flex-shrink: 0;
}
.side-topbar .back-btn:hover { background: #f5f5f5; }
.side-course-title { font-size: 14px; font-weight: 600; }
.side-course-ref   { font-size: 11px; color: #888; }
.statut-badge {
    font-size: 11px; font-weight: 500;
    padding: 3px 10px;
    border-radius: 20px;
    background: <?= explode(';',$statutBg)[0] ?>;
    color: <?= substr($statutBg, strpos($statutBg,'color:')+6) ?>;
    white-space: nowrap;
    margin-left: auto;
}

.side-section {
    padding: 14px 16px;
    border-bottom: 1px solid #eee;
}
.sec-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #999;
    margin-bottom: 10px;
}

/* Client row */
.client-row { display: flex; align-items: center; gap: 10px; }
.client-ava {
    width: 42px; height: 42px;
    border-radius: 50%;
    background: #CECBF6;
    color: #3C3489;
    font-size: 14px; font-weight: 600;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.client-ava img { width: 100%; height: 100%; object-fit: cover; }
.client-name   { font-size: 14px; font-weight: 600; }
.client-loc    { font-size: 12px; color: #888; display: flex; align-items: center; gap: 4px; margin-top: 2px; }
.client-actions { margin-left: auto; display: flex; gap: 6px; }
.cta-btn {
    width: 32px; height: 32px;
    border-radius: 8px;
    border: 1px solid #ddd;
    background: transparent;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: #555; cursor: pointer; text-decoration: none;
}
.cta-btn:hover { background: #f5f5f5; }
.cta-btn.green { background: #EAF3DE; border-color: #c0dd97; color: #3B6D11; }

/* Route */
.route-box { display: flex; flex-direction: column; gap: 6px; }
.route-line { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.dot-g { background: #1D9E75; }
.dot-r { background: #E24B4A; }
.route-sep { padding: 0 0 0 4px; }
.dashed-v { border-left: 2px dashed #ccc; height: 12px; margin-left: 0; }

/* Stats */
.stats-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.stat-card  { background: #f8f8f8; border-radius: 8px; padding: 10px 12px; }
.stat-val   { font-size: 17px; font-weight: 700; margin-bottom: 2px; }
.stat-lbl   { font-size: 11px; color: #888; }

/* Onglets */
.side-tabs { display: flex; border-bottom: 1px solid #eee; }
.stab {
    flex: 1; padding: 10px 6px;
    font-size: 12px; text-align: center;
    cursor: pointer; color: #888;
    border-bottom: 2px solid transparent;
    transition: all .2s;
}
.stab.active { color: #534AB7; border-bottom-color: #534AB7; font-weight: 600; }

.tab-pane { display: none; flex-direction: column; flex: 1; overflow: hidden; }
.tab-pane.active { display: flex; }

/* Chat */
.chat-area {
    flex: 1;
    overflow-y: auto;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    background: #f7f7f7;
}
.msg { display: flex; flex-direction: column; max-width: 82%; }
.msg.sent  { align-self: flex-end;   align-items: flex-end; }
.msg.recv  { align-self: flex-start; align-items: flex-start; }
.bubble {
    padding: 8px 12px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.45;
    word-break: break-word;
}
.bubble.sent { background: #534AB7; color: #fff; border-bottom-right-radius: 4px; }
.bubble.recv { background: #fff;    color: #1a1a1a; border: 1px solid #e8e8e8; border-bottom-left-radius: 4px; }
.msg-time   { font-size: 10px; color: #aaa; margin-top: 3px; }
.msg-day    { text-align: center; font-size: 11px; color: #bbb; margin: 4px 0; }

.quick-suggest {
    padding: 8px 12px 4px;
    display: flex; gap: 6px; flex-wrap: wrap;
    background: #fff;
    border-top: 1px solid #f0f0f0;
}
.qs-btn {
    font-size: 11px; padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid #ddd;
    background: transparent;
    color: #555;
    cursor: pointer;
    white-space: nowrap;
}
.qs-btn:hover { background: #f5f5f5; }

.chat-input-bar {
    padding: 10px 12px;
    display: flex; gap: 8px; align-items: center;
    background: #fff;
    border-top: 1px solid #eee;
}
.chat-input-bar input {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 7px 14px;
    font-size: 13px;
    font-family: 'Outfit', sans-serif;
    outline: none;
    background: #f8f8f8;
}
.chat-input-bar input:focus { border-color: #534AB7; background: #fff; }
.send-btn {
    width: 36px; height: 36px;
    border-radius: 50%;
    background: #534AB7;
    border: none; cursor: pointer;
    color: #fff; font-size: 16px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.send-btn:hover { background: #3C3489; }

/* Rapides & Infos */
.quick-list-pane {
    overflow-y: auto;
    padding: 12px 16px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.qmsg-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 12px;
    border: 1px solid #eee;
    border-radius: 8px;
    background: #fafafa;
    cursor: pointer;
    font-size: 13px;
    color: #333;
    text-align: left;
    transition: background .15s;
}
.qmsg-btn:hover { background: #f0eeff; border-color: #c5bff5; }
.qmsg-emoji { font-size: 18px; flex-shrink: 0; }

.infos-pane { overflow-y: auto; }
.info-section { padding: 14px 16px; border-bottom: 1px solid #eee; }
.stars { display: flex; gap: 3px; font-size: 18px; margin-bottom: 4px; }
.action-btn {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 10px 12px;
    border-radius: 8px;
    font-size: 13px; cursor: pointer; margin-bottom: 8px;
    border: 1px solid #ddd;
    background: transparent; color: #555;
    text-align: left;
}
.action-btn.danger { border-color: #f7c1c1; background: #FCEBEB; color: #A32D2D; }
.action-btn.success { border-color: #c0dd97; background: #EAF3DE; color: #3B6D11; }

/* Marker perso */
.marker-driver, .marker-client {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px;
    border: 3px solid #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.25);
}
.marker-driver { background: #534AB7; }
.marker-client { background: #E24B4A; }

/* Pulse animation */
@keyframes pulse {
    0%   { transform: scale(1); opacity: .8; }
    100% { transform: scale(2.5); opacity: 0; }
}
.pulse-ring {
    position: absolute;
    width: 36px; height: 36px;
    border-radius: 50%;
    background: rgba(226, 74, 74, .3);
    animation: pulse 2s ease-out infinite;
    top: 0; left: 0;
}
.suivi-layout {
    display: grid;
    grid-template-columns: 1fr 390px;
}
html, body {
    height: 100%;
    margin: 0;
    overflow: hidden;
}

.layout, .main {
    height: 100%;
}
</style>
</head>
<div class="layout">
<?php include_once "sidebar.php"; ?>

<div class="main" style="padding:0;overflow:hidden">

    <div class="suivi-layout">

        <!-- ═══════════════ CARTE ═══════════════ -->
        <div class="map-wrap" style="position:relative">
            <div id="map"></div>

            <div class="map-badge-top">
                <div class="mbadge">
                    <i class="bi bi-navigation-fill" style="color:#534AB7"></i>
                    Navigation active
                </div>

                <div class="mbadge">
                    <i class="bi bi-person-fill" style="color:#E24B4A"></i>
                    Client localisé
                </div>

                <?php if ($eta): ?>
                    <div class="mbadge mbadge-eta">
                        <i class="bi bi-clock-fill"></i> ETA : <?= $eta ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="nav-instruction" id="nav-instr">
                <div class="nav-arrow-box">
                    <i class="bi bi-arrow-up"></i>
                </div>
                <div>
                    <div class="nav-txt-main">Continuez tout droit</div>
                    <div class="nav-txt-sub" id="nav-sub">
                        Calcul de l'itinéraire en cours…
                    </div>
                </div>
            </div>
        </div>

        <!-- ═══════════════ PANEL ═══════════════ -->
        <div class="side-panel">

            <!-- Topbar -->
            <div class="side-topbar">
                <a href="mes_courses.php" class="back-btn">
                    <i class="bi bi-arrow-left"></i>
                </a>

                <div style="flex:1;min-width:0">
                    <div class="side-course-title">Course <?= $ref ?></div>
                    <div class="side-course-ref">#<?= $courseId ?></div>
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
                            <a href="tel:<?= $clientTel ?>" class="cta-btn green">
                                <i class="bi bi-telephone-fill"></i>
                            </a>
                        <?php endif; ?>

                        <button class="cta-btn" onclick="focusClient()">
                            <i class="bi bi-crosshair2"></i>
                        </button>

                        <button class="cta-btn" onclick="focusMe()">
                            <i class="bi bi-geo-fill"></i>
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
                <div class="stab active" onclick="switchTab('chat')">Messages</div>
                <div class="stab" onclick="switchTab('quick')">Rapides</div>
                <div class="stab" onclick="switchTab('infos')">Infos</div>
            </div>

            <!-- CHAT -->
            <div class="tab-pane active" id="pane-chat">
                <div class="chat-area" id="chat-area">
                    <div class="msg-day">Aujourd'hui · Course <?= $ref ?></div>

                    <?php foreach ($messages as $m): ?>
                        <?php $side = ($m['expediteur_role'] === 'chauffeur') ? 'sent' : 'recv'; ?>
                        <div class="msg <?= $side ?>">
                            <div class="bubble <?= $side ?>">
                                <?= htmlspecialchars($m['contenu']) ?>
                            </div>
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

        </div><!-- side-panel -->

    </div><!-- suivi-layout -->

</div><!-- main -->

</div><!-- layout -->

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
/* ── Config ─────────────────────────────────────────────────── */
const COURSE_ID    = <?= (int)$courseId ?>;
const CLIENT_LAT   = <?= (float)$clientLat ?>;
const CLIENT_LNG   = <?= (float)$clientLng ?>;
const CHAUFFEUR_LAT= <?= (float)$chauffeurLat ?>;
const CHAUFFEUR_LNG= <?= (float)$chauffeurLng ?>;
const POLLING_MS   = 5000;

/* ── Carte Leaflet ─────────────────────────────────────────── */
const map = L.map('map', { zoomControl: false }).setView(
    [(CLIENT_LAT + CHAUFFEUR_LAT)/2, (CLIENT_LNG + CHAUFFEUR_LNG)/2], 15
);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors',
    maxZoom: 19,
}).addTo(map);

L.control.zoom({ position: 'bottomleft' }).addTo(map);

/* Icônes */
const iconDriver = L.divIcon({
    className: '',
    html: '<div style="width:38px;height:38px;border-radius:50%;background:#534AB7;border:3px solid #fff;display:flex;align-items:center;justify-content:center;font-size:18px">🚕</div>',
    iconSize: [38, 38],
    iconAnchor: [19, 19],
});

const iconClient = L.divIcon({
    className: '',
    html: `<div style="position:relative;width:38px;height:38px">
        <div style="position:absolute;width:38px;height:38px;border-radius:50%;background:#E24B4A;opacity:.25"></div>
        <div style="position:absolute;width:38px;height:38px;border-radius:50%;background:#E24B4A;border:3px solid #fff;display:flex;align-items:center;justify-content:center">📍</div>
    </div>`,
    iconSize: [38, 38],
    iconAnchor: [19, 38],
});

const clientName = <?= json_encode($clientNom) ?>;

/* MARKERS */
const markerDriver = L.marker([CHAUFFEUR_LAT, CHAUFFEUR_LNG], { icon: iconDriver })
    .addTo(map)
    .bindPopup('Vous — chauffeur');

const markerClient = L.marker([CLIENT_LAT, CLIENT_LNG], { icon: iconClient })
    .addTo(map)
    .bindPopup(clientName + ' — client');

/* ROUTE */
let routeLine = null;

function drawRoute(coords) {
    if (routeLine) map.removeLayer(routeLine);
    routeLine = L.polyline(coords, {
        color: '#534AB7',
        weight: 4,
        opacity: .75,
        dashArray: '8,5'
    }).addTo(map);

    map.fitBounds(routeLine.getBounds(), { padding: [60, 60] });
}

drawRoute([[CHAUFFEUR_LAT, CHAUFFEUR_LNG], [CLIENT_LAT, CLIENT_LNG]]);

/* DISTANCE */
function haversine(lat1,lng1,lat2,lng2){
    const R=6371000;
    const dLat=(lat2-lat1)*Math.PI/180;
    const dLng=(lng2-lng1)*Math.PI/180;
    const a=Math.sin(dLat/2)**2 +
        Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLng/2)**2;
    return R*2*Math.atan2(Math.sqrt(a),Math.sqrt(1-a));
}

function fmtDist(m){
    return m<1000 ? Math.round(m)+' m' : (m/1000).toFixed(1)+' km';
}

function fmtEta(m){
    const mins = Math.ceil(m/50/60);
    return mins<=1 ? '< 1 min' : mins+' min';
}

function updateStats(dLat,dLng,cLat,cLng){
    const dist = haversine(dLat,dLng,cLat,cLng);

    const el1 = document.getElementById('stat-dist');
    const el2 = document.getElementById('stat-eta');
    const el3 = document.getElementById('client-dist');
    const el4 = document.getElementById('route-info');

    if (el1) el1.textContent = fmtDist(dist);
    if (el2) el2.textContent = fmtEta(dist);
    if (el3) el3.textContent = fmtDist(dist) + ' de vous';
    if (el4) el4.textContent = fmtDist(dist) + ' · ' + fmtEta(dist);
}

updateStats(CHAUFFEUR_LAT, CHAUFFEUR_LNG, CLIENT_LAT, CLIENT_LNG);

/* GPS */
let myLat = CHAUFFEUR_LAT, myLng = CHAUFFEUR_LNG;

if (navigator.geolocation) {
    navigator.geolocation.watchPosition(pos=>{
        myLat = pos.coords.latitude;
        myLng = pos.coords.longitude;

        markerDriver.setLatLng([myLat,myLng]);

        updateStats(myLat,myLng,
            markerClient.getLatLng().lat,
            markerClient.getLatLng().lng
        );

        drawRoute([
            [myLat,myLng],
            [markerClient.getLatLng().lat, markerClient.getLatLng().lng]
        ]);

        sendMyPosition(myLat,myLng);
    }, null, {enableHighAccuracy:true});
}

async function sendMyPosition(lat,lng){
    try{
        await fetch('update_position.php',{
            method:'POST',
            headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                course_id: COURSE_ID,
                lat: lat,
                lng: lng,
                role:'chauffeur'
            })
        });
    }catch(e){}
}

/* CLIENT */
async function pollClientPosition(){
    try{
        const r = await fetch(`get_position.php?course_id=${COURSE_ID}&role=client`);
        const d = await r.json();

        if(d.lat && d.lng){
            const lat = parseFloat(d.lat);
            const lng = parseFloat(d.lng);

            markerClient.setLatLng([lat,lng]);

            updateStats(myLat,myLng,lat,lng);

            drawRoute([[myLat,myLng],[lat,lng]]);
        }
    }catch(e){}
}
setInterval(pollClientPosition,POLLING_MS);

/* FOCUS */
function focusClient(){ map.setView(markerClient.getLatLng(),17); }
function focusMe(){ map.setView(markerDriver.getLatLng(),17); }

/* ── MESSAGES SAFE ───────────────────────── */
let lastMsgId = <?= isset($messages) && !empty($messages) ? (int)end($messages)['id'] : 0 ?>;

function escHtml(s){
    return String(s)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;');
}

function appendMsg(txt,side){
    const area=document.getElementById('chat-area');
    if(!area) return;

    const div=document.createElement('div');
    div.className='msg '+side;
    div.innerHTML=`<div class="bubble ${side}">${escHtml(txt)}</div>
    <div class="msg-time">${new Date().toLocaleTimeString([], {hour:'2-digit',minute:'2-digit'})}</div>`;

    area.appendChild(div);
    area.scrollTop=area.scrollHeight;
}

async function sendMsg(){
    const inp=document.getElementById('msg-input');
    const txt=inp.value.trim();
    if(!txt) return;

    inp.value='';
    appendMsg(txt,'sent');

    await fetch('send_message.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({course_id:COURSE_ID,contenu:txt})
    });
}

/* POLL MESSAGES */
async function pollMessages(){
    try{
        const r = await fetch(`get_messages.php?course_id=${COURSE_ID}&after=${lastMsgId}`);
        const msgs = await r.json();

        msgs.forEach(m=>{
            if(m.expediteur_role !== 'chauffeur'){
                appendMsg(m.contenu,'recv');
            }
            if(m.id > lastMsgId) lastMsgId = m.id;
        });
    }catch(e){}
}
setInterval(pollMessages,3000);
</script>
</body>
</html>