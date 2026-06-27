<?php
/* ════════════════════════════════════════════════════════════════
   suivi_client.php — Yoon bu Gaw · Espace Client
   Suivi temps réel de la course : position chauffeur + messagerie
════════════════════════════════════════════════════════════════ */

session_start();
require_once '../config/database.php';

/* ── AUTHENTIFICATION ─────────────────────────────────────────── */
if (empty($_SESSION['id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$userId   = (int) $_SESSION['id'];
$userName = trim($_SESSION['nom'] ?? '');

$courseId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$courseId) {
    header('Location: dashboard.php');
    exit;
}

/* ── COURSE + CHAUFFEUR ───────────────────────────────────────── */
$stmt = $pdo->prepare("
    SELECT
        r.*,
        u.nom        AS chauffeur_nom,
        u.telephone  AS chauffeur_tel,
        u.photo      AS chauffeur_photo,
        u.lat        AS chauffeur_lat_global,
        u.lng        AS chauffeur_lng_global,
        u.note_moy   AS chauffeur_note
    FROM reservations r
    LEFT JOIN users u ON u.id = r.chauffeur_id
    WHERE r.id = ?
      AND r.client_id = ?
    LIMIT 1
");
$stmt->execute([$courseId, $userId]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

/* ── CORRECTION : redirection si la course n'existe pas ────────── */
if (!$courseId) {
    header('Location: dashboard.php');
    exit;
}

/* ── CLIENT ───────────────────────────────────────────────────── */
$stmt = $pdo->prepare("
    SELECT id, nom, photo, lat, lng
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$client = $stmt->fetch(PDO::FETCH_ASSOC);

$clientNom   = htmlspecialchars(trim($client['nom'] ?? $userName));
$clientPhoto = htmlspecialchars($client['photo'] ?? '');
$parts       = preg_split('/\s+/', $clientNom);
$clientIni   = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));

/* ── MESSAGES ─────────────────────────────────────────────────── */
$stmt = $pdo->prepare("
    SELECT
        m.id,
        m.contenu,
        m.created_at,
        u.nom  AS expediteur_nom,
        u.role AS expediteur_role
    FROM messages_course m
    INNER JOIN users u ON u.id = m.expediteur_id
    WHERE m.course_id = ?
    ORDER BY m.id ASC
");
$stmt->execute([$courseId]);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Messages du chauffeur → lus */
$stmt = $pdo->prepare("
    UPDATE messages_course
    SET lu = 1
    WHERE course_id = ?
      AND expediteur_id != ?
");
$stmt->execute([$courseId, $userId]);

/* ── CORRECTION : lastMsgId safe même si $messages est vide ─────── */
$lastMsgId = 0;
if (!empty($messages)) {
    $lastMsg   = end($messages);
    $lastMsgId = (int)($lastMsg['id'] ?? 0);
}

/* ── CHAUFFEUR ────────────────────────────────────────────────── */
$chauffeurNom   = htmlspecialchars($course['chauffeur_nom']   ?? 'Votre chauffeur');
$chauffeurPhoto = htmlspecialchars($course['chauffeur_photo'] ?? '');
$chauffeurTel   = htmlspecialchars($course['chauffeur_tel']   ?? '');
$chauffeurNote  = (float)($course['chauffeur_note']           ?? 0);

/*
| Position chauffeur — priorité :
| 1. position enregistrée dans reservations (colonnes chauffeur_lat / chauffeur_lng)
| 2. position globale du compte chauffeur
| 3. coordonnées par défaut (Dakar)
*/
$chauffeurLat = (float)(
    /* CORRECTION : isset() explicite pour éviter les notices PHP 8+ */
    isset($course['chauffeur_lat'])        && $course['chauffeur_lat']        !== null ? $course['chauffeur_lat']        :
    (isset($course['chauffeur_lat_global']) && $course['chauffeur_lat_global'] !== null ? $course['chauffeur_lat_global'] : 14.6937)
);
$chauffeurLng = (float)(
    isset($course['chauffeur_lng'])        && $course['chauffeur_lng']        !== null ? $course['chauffeur_lng']        :
    (isset($course['chauffeur_lng_global']) && $course['chauffeur_lng_global'] !== null ? $course['chauffeur_lng_global'] : -17.4441)
);

$parts2      = preg_split('/\s+/', $chauffeurNom);
$chauffeurIni = strtoupper(substr($parts2[0] ?? '', 0, 1) . substr($parts2[1] ?? '', 0, 1));

/* ── POSITION CLIENT ──────────────────────────────────────────── */
$clientLat = (float)($client['lat'] ?? 14.6901);
$clientLng = (float)($client['lng'] ?? -17.4380);

/* ── INFOS COURSE ─────────────────────────────────────────────── */
$depart      = htmlspecialchars($course['depart']       ?? '');
$destination = htmlspecialchars($course['destination']  ?? '');
$montant     = number_format((float)($course['montant'] ?? 0), 0, ',', ' ');
$paiement    = htmlspecialchars($course['mode_paiement'] ?? '');
$statut      = htmlspecialchars($course['statut_course'] ?? $course['statut'] ?? 'En cours');
$ref         = htmlspecialchars($course['reference']    ?? ('#' . $courseId));
$eta         = htmlspecialchars($course['eta']          ?? '');
$matricule   = htmlspecialchars($course['matricule']    ?? '');
$typeVeh     = htmlspecialchars($course['type_transport'] ?? 'taxi');

/* ── HELPERS ──────────────────────────────────────────────────── */
function paiIcon(string $m): string
{
    return match (strtolower(trim($m))) {
        'orange money', 'om' => '🟠',
        'wave'               => '🔵',
        'free money', 'free' => '🟣',
        'espèces', 'cash'    => '💵',
        default              => '💳',
    };
}

function typeIcon(string $t): string
{
    return match (strtolower(trim($t))) {
        'bus'   => 'bi-bus-front-fill',
        'cargo' => 'bi-truck-front-fill',
        'moto'  => 'bi-bicycle',
        default => 'bi-taxi-front-fill',
    };
}

function statutBg(string $s): string
{
    $s = strtolower($s);
    if (str_contains($s, 'annul'))   return 'background:#FCEBEB;color:#A32D2D';
    if (str_contains($s, 'cours'))   return 'background:#E6F1FB;color:#185FA5';
    if (str_contains($s, 'attente')) return 'background:#FAEEDA;color:#BA7517';
    return 'background:#EAF3DE;color:#3B6D11';
}

$paiIco  = paiIcon($paiement);
$typeIco = typeIcon($typeVeh);
$statBg  = statutBg($statut);
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

<style>
/* ══ Reset & base ══ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Outfit', sans-serif;
    background: #f3f4f6;
    color: #1a1a1a;
    overflow: hidden;
    height: 100vh;
}

/* ══ Animation pulse chauffeur ══ */
/* CORRECTION : définie une seule fois ici, supprimée du JS */
@keyframes drvpulse {
    0%   { transform: scale(1); opacity: .7; }
    100% { transform: scale(2.8); opacity: 0; }
}

/* ══ Layout principal ══ */
.suivi-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    height: 100vh;
}
@media (max-width: 860px) {
    .suivi-layout {
        grid-template-columns: 1fr;
        grid-template-rows: 50vh 1fr;
        overflow: hidden;
    }
    body { overflow: hidden; }
}

/* ══ Carte ══ */
.map-wrap { position: relative; overflow: hidden; }
#map { width: 100%; height: 100%; }

.map-top {
    position: absolute;
    top: 12px; left: 12px; right: 12px;
    z-index: 10;
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    pointer-events: none;
}
.mbadge {
    background: rgba(255,255,255,.96);
    border: 1px solid rgba(0,0,0,.08);
    border-radius: 10px;
    padding: 6px 12px;
    font-size: 12px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 2px 10px rgba(0,0,0,.12);
    pointer-events: all;
}
.mbadge-eta  { background: #185FA5; color: #fff; border-color: #185FA5; }
.mbadge-dist { background: #1D9E75; color: #fff; border-color: #1D9E75; }

.map-ctrl {
    position: absolute;
    right: 12px; bottom: 80px;
    z-index: 10;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.map-ctrl-btn {
    width: 38px; height: 38px;
    background: rgba(255,255,255,.96);
    border: 1px solid rgba(0,0,0,.1);
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    font-size: 16px;
    color: #444;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
    transition: background .15s;
}
.map-ctrl-btn:hover { background: #f5f5f5; }

.driver-bar {
    position: absolute;
    bottom: 12px; left: 12px; right: 60px;
    z-index: 10;
    background: rgba(255,255,255,.97);
    border-radius: 14px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,.15);
}
.driver-ava {
    width: 38px; height: 38px;
    border-radius: 50%;
    background: #CECBF6;
    color: #3C3489;
    font-size: 13px; font-weight: 600;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; overflow: hidden;
}
.driver-ava img { width: 100%; height: 100%; object-fit: cover; }
.driver-name { font-size: 13px; font-weight: 600; }
.driver-sub  { font-size: 11px; color: #888; }
.driver-tel  {
    margin-left: auto;
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #EAF3DE;
    border: 1px solid #c0dd97;
    display: flex; align-items: center; justify-content: center;
    color: #3B6D11; font-size: 15px;
    text-decoration: none;
    flex-shrink: 0;
}
.driver-tel:hover { background: #d0e8a8; }

/* ══ Panel latéral ══ */
.side-panel {
    display: flex;
    flex-direction: column;
    background: #fff;
    border-left: 1px solid #e8e8e8;
    overflow: hidden;
}

.side-top {
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    display: flex;
    align-items: center;
    gap: 10px;
    flex-shrink: 0;
}
.back-btn {
    width: 34px; height: 34px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    background: transparent;
    color: #555;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px;
    text-decoration: none;
    flex-shrink: 0;
    transition: background .15s;
}
.back-btn:hover { background: #f5f5f5; }
.top-info  { flex: 1; min-width: 0; }
.top-title { font-size: 14px; font-weight: 600; }
.top-ref   { font-size: 11px; color: #999; }
.statut-pill {
    font-size: 11px; font-weight: 600;
    padding: 3px 10px;
    border-radius: 20px;
    <?= $statBg ?>;
    white-space: nowrap;
    margin-left: auto;
    flex-shrink: 0;
}

.s-section {
    padding: 12px 16px;
    border-bottom: 1px solid #f0f0f0;
    flex-shrink: 0;
}
.s-label {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #bbb;
    margin-bottom: 8px;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
}
.stat-c {
    background: #f8f8f8;
    border-radius: 8px;
    padding: 8px 10px;
    text-align: center;
}
.stat-v { font-size: 15px; font-weight: 700; margin-bottom: 1px; }
.stat-l { font-size: 10px; color: #999; }

.route-wrap { display: flex; flex-direction: column; gap: 5px; }
.route-line { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.rdot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.rdot-g { background: #1D9E75; }
.rdot-r { background: #E24B4A; }
.rdash  { border-left: 2px dashed #ddd; height: 12px; margin-left: 4px; }

.tabs-bar {
    display: flex;
    border-bottom: 1px solid #eee;
    flex-shrink: 0;
}
.tab {
    flex: 1;
    padding: 9px 6px;
    font-size: 12px;
    text-align: center;
    cursor: pointer;
    color: #aaa;
    border-bottom: 2px solid transparent;
    transition: all .2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}
.tab.active { color: #185FA5; border-bottom-color: #185FA5; font-weight: 600; }

.tab-pane { display: none; flex-direction: column; flex: 1; overflow: hidden; min-height: 0; }
.tab-pane.active { display: flex; }

/* ══ Chat ══ */
.chat-area {
    flex: 1;
    overflow-y: auto;
    padding: 12px 14px;
    background: #f7f8fa;
    display: flex;
    flex-direction: column;
    gap: 7px;
    min-height: 0;
}
.msg { display: flex; flex-direction: column; max-width: 84%; }
.msg.sent  { align-self: flex-end;   align-items: flex-end; }
.msg.recv  { align-self: flex-start; align-items: flex-start; }
.bubble {
    padding: 8px 12px;
    border-radius: 14px;
    font-size: 13px;
    line-height: 1.45;
    word-break: break-word;
}
.bubble.sent { background: #185FA5; color: #fff; border-bottom-right-radius: 3px; }
.bubble.recv { background: #fff; color: #1a1a1a; border: 1px solid #e8e8e8; border-bottom-left-radius: 3px; }
.msg-time { font-size: 10px; color: #bbb; margin-top: 3px; }
.msg-day  { text-align: center; font-size: 11px; color: #ccc; margin: 4px 0; }

.quick-row {
    padding: 7px 12px 4px;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
    background: #fff;
    border-top: 1px solid #f0f0f0;
    flex-shrink: 0;
}
.qs {
    font-size: 11px; padding: 4px 10px;
    border-radius: 20px; border: 1px solid #ddd;
    background: transparent; color: #555;
    cursor: pointer; white-space: nowrap;
}
.qs:hover { background: #f0f5ff; border-color: #b5d4f4; }

.chat-bar {
    padding: 8px 12px;
    display: flex;
    gap: 8px;
    align-items: center;
    background: #fff;
    border-top: 1px solid #eee;
    flex-shrink: 0;
}
.chat-bar input {
    flex: 1;
    border: 1px solid #ddd;
    border-radius: 20px;
    padding: 7px 14px;
    font-size: 13px;
    font-family: 'Outfit', sans-serif;
    outline: none;
    background: #f8f8f8;
}
.chat-bar input:focus { border-color: #185FA5; background: #fff; }
.send-btn {
    width: 34px; height: 34px;
    border-radius: 50%;
    background: #185FA5;
    border: none;
    cursor: pointer;
    color: #fff;
    font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    transition: background .15s, opacity .15s;
}
.send-btn:hover    { background: #0C447C; }
.send-btn:disabled { opacity: .5; cursor: not-allowed; }

/* Toast notification */
.notif-toast {
    position: fixed;
    top: 16px; left: 50%;
    transform: translateX(-50%) translateY(-80px);
    background: #185FA5; color: #fff;
    padding: 10px 18px;
    border-radius: 24px;
    font-size: 13px; font-weight: 500;
    z-index: 9999;
    box-shadow: 0 4px 20px rgba(0,0,0,.2);
    transition: transform .35s cubic-bezier(.34,1.56,.64,1);
    display: flex; align-items: center; gap: 8px;
    white-space: nowrap;
}
.notif-toast.show { transform: translateX(-50%) translateY(0); }

/* ══ Pane "Infos" ══ */
.infos-pane { overflow-y: auto; flex: 1; }
.info-section { padding: 12px 16px; border-bottom: 1px solid #f0f0f0; }
.stars { display: flex; gap: 3px; font-size: 17px; margin-bottom: 3px; }

.action-btn {
    display: flex; align-items: center; gap: 8px;
    width: 100%; padding: 9px 12px;
    border-radius: 8px; font-size: 13px;
    cursor: pointer; margin-bottom: 7px;
    border: 1px solid #ddd; background: transparent;
    color: #555; text-align: left;
    transition: background .15s;
}
.action-btn:hover { background: #f8f8f8; }
.action-btn.danger { border-color: #f7c1c1; background: #FCEBEB; color: #A32D2D; }
.action-btn.danger:hover { background: #fad9d9; }

.note-form { display: flex; flex-direction: column; gap: 8px; }
.stars-pick { display: flex; gap: 6px; }
.star-pick {
    font-size: 24px; cursor: pointer;
    color: #ddd; transition: color .15s, transform .1s;
}
.star-pick.lit { color: #EF9F27; }
.star-pick:hover { transform: scale(1.2); }
.note-textarea {
    border: 1px solid #ddd; border-radius: 8px;
    padding: 8px 10px; font-size: 13px;
    font-family: 'Outfit', sans-serif;
    resize: vertical; min-height: 60px; outline: none;
}
.note-textarea:focus { border-color: #185FA5; }
.note-submit {
    padding: 8px 16px; border-radius: 8px;
    background: #185FA5; color: #fff; border: none;
    font-size: 13px; cursor: pointer;
    font-family: 'Outfit', sans-serif; font-weight: 500;
}
.note-submit:hover     { background: #0C447C; }
.note-submit:disabled  { opacity: .5; cursor: not-allowed; }

.unread-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #E24B4A; display: inline-block; margin-left: 4px;
    animation: blink 1.5s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

.mobile-btm {
    display: none;
    position: fixed; bottom: 0; left: 0; right: 0;
    background: #fff; border-top: 1px solid #eee;
    padding: 8px 0 env(safe-area-inset-bottom);
    z-index: 100;
}
@media (max-width: 860px) {
    .mobile-btm { display: flex; }
    .side-panel { padding-bottom: 56px; }
}
.mnav-item {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; gap: 2px;
    font-size: 10px; color: #aaa;
    cursor: pointer; padding: 6px 0;
    border: none; background: transparent; text-decoration: none;
}
.mnav-item.active { color: #185FA5; }
.mnav-item i { font-size: 20px; }

.leaflet-control-attribution { font-size: 10px; }
</style>
</head>
<body>

<div class="notif-toast" id="notif-toast">
    <i class="bi bi-chat-dots-fill"></i>
    <span id="notif-text">Nouveau message</span>
</div>

<div class="suivi-layout">

    <!-- ═══════════════ CARTE ═══════════════ -->
    <div class="map-wrap">
        <div id="map"></div>

        <div class="map-top">
            <div class="mbadge">
                <i class="bi bi-taxi-front-fill" style="color:#185FA5"></i>
                <?= $chauffeurNom ?>
            </div>
            <?php if ($eta): ?>
            <div class="mbadge mbadge-eta">
                <i class="bi bi-clock-fill"></i> Arrivée : <?= $eta ?>
            </div>
            <?php endif; ?>
            <div class="mbadge mbadge-dist" id="dist-badge" style="display:none">
                <i class="bi bi-geo-alt-fill"></i>
                <span id="dist-badge-txt">Calcul…</span>
            </div>
        </div>

        <div class="map-ctrl">
            <div class="map-ctrl-btn" onclick="centerOnDriver()" title="Voir le chauffeur">
                <i class="bi bi-taxi-front-fill"></i>
            </div>
            <div class="map-ctrl-btn" onclick="centerOnMe()" title="Ma position">
                <i class="bi bi-geo-fill" style="color:#E24B4A"></i>
            </div>
            <div class="map-ctrl-btn" onclick="fitBoth()" title="Voir les deux">
                <i class="bi bi-arrows-angle-expand"></i>
            </div>
        </div>

        <div class="driver-bar">
            <div class="driver-ava">
                <?php if ($chauffeurPhoto): ?>
                    <img src="<?= $chauffeurPhoto ?>" alt="">
                <?php else: ?>
                    <?= $chauffeurIni ?>
                <?php endif; ?>
            </div>
            <div>
                <div class="driver-name"><?= $chauffeurNom ?></div>
                <div class="driver-sub">
                    <i class="bi bi-<?= $typeIco ?>"></i>
                    <?= $matricule ?: ucfirst($typeVeh) ?>
                    <?php if ($chauffeurNote > 0): ?>
                    · <i class="bi bi-star-fill" style="color:#EF9F27;font-size:10px"></i>
                    <?= number_format($chauffeurNote, 1) ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($chauffeurTel): ?>
            <a href="tel:<?= $chauffeurTel ?>" class="driver-tel" title="Appeler le chauffeur">
                <i class="bi bi-telephone-fill"></i>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ═══════════════ PANEL ═══════════════ -->
    <div class="side-panel">

        <div class="side-top">
            <a href="dashboard.php" class="back-btn"><i class="bi bi-arrow-left"></i></a>
            <div class="top-info">
                <div class="top-title">Votre course</div>
                <div class="top-ref"><?= $ref ?> · #<?= $courseId ?></div>
            </div>
            <span class="statut-pill"><?= ucfirst($statut) ?></span>
        </div>

        <div class="s-section">
            <div class="stats-row">
                <div class="stat-c">
                    <div class="stat-v" style="color:#1D9E75" id="stat-dist">—</div>
                    <div class="stat-l">Distance</div>
                </div>
                <div class="stat-c">
                    <div class="stat-v" style="color:#185FA5" id="stat-eta">—</div>
                    <div class="stat-l">ETA</div>
                </div>
                <div class="stat-c">
                    <div class="stat-v" style="color:#BA7517"><?= $montant ?> F</div>
                    <div class="stat-l">Montant</div>
                </div>
                <div class="stat-c">
                    <div class="stat-v" style="color:#534AB7;font-size:13px"><?= $paiIco ?></div>
                    <div class="stat-l"><?= $paiement ?: '—' ?></div>
                </div>
            </div>
        </div>

        <div class="s-section">
            <div class="s-label">Itinéraire</div>
            <div class="route-wrap">
                <div class="route-line">
                    <span class="rdot rdot-g"></span>
                    <span><?= $depart ?></span>
                </div>
                <div class="route-line">
                    <span style="padding-left:4px"><span class="rdash"></span></span>
                    <span style="font-size:11px;color:#bbb" id="route-detail">Calcul en cours…</span>
                </div>
                <div class="route-line">
                    <span class="rdot rdot-r"></span>
                    <span><?= $destination ?></span>
                </div>
            </div>
        </div>

        <div class="tabs-bar">
            <div class="tab active" id="tab-chat" onclick="switchTab('chat')">
                <i class="bi bi-chat-dots-fill"></i> Messages
                <span id="unread-dot" class="unread-dot" style="display:none"></span>
            </div>
            <div class="tab" id="tab-infos" onclick="switchTab('infos')">
                <i class="bi bi-info-circle-fill"></i> Infos
            </div>
            <div class="tab" id="tab-note" onclick="switchTab('note')">
                <i class="bi bi-star-fill"></i> Note
            </div>
        </div>

        <!-- ── Pane : Chat ── -->
        <div class="tab-pane active" id="pane-chat">
            <div class="chat-area" id="chat-area">
                <div class="msg-day">Aujourd'hui · Course <?= $ref ?></div>
                <?php foreach ($messages as $m):
                    $side = ($m['expediteur_role'] === 'client') ? 'sent' : 'recv';
                    $hm   = date('H:i', strtotime($m['created_at']));
                ?>
                <div class="msg <?= $side ?>">
                    <div class="bubble <?= $side ?>"><?= htmlspecialchars($m['contenu']) ?></div>
                    <div class="msg-time"><?= $hm ?></div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($messages)): ?>
                <div class="msg-day" style="margin-top:24px">
                    Envoyez un message à votre chauffeur
                </div>
                <?php endif; ?>
            </div>

            <div class="quick-row">
                <button class="qs" onclick="quickSend('Je suis prêt, vous pouvez venir.')">✅ Prêt</button>
                <button class="qs" onclick="quickSend('Je suis devant l\'entrée principale.')">📍 Entrée</button>
                <button class="qs" onclick="quickSend('Combien de temps encore ?')">⏱ Durée ?</button>
                <button class="qs" onclick="quickSend('Je vous attends à l\'ombre.')">🌳 À l'ombre</button>
                <button class="qs" onclick="quickSend('Ok, je vous vois !')">👋 Je vous vois</button>
            </div>

            <div class="chat-bar">
                <input type="text" id="msg-input" placeholder="Écrire au chauffeur…"
                       onkeydown="if(event.key==='Enter')sendMsg()">
                <button class="send-btn" id="send-btn" onclick="sendMsg()">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>

        <!-- ── Pane : Infos ── -->
        <div class="tab-pane" id="pane-infos">
            <div class="infos-pane">
                <div class="info-section">
                    <div class="s-label">Votre chauffeur</div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px">
                        <div class="driver-ava" style="width:46px;height:46px;font-size:15px">
                            <?php if ($chauffeurPhoto): ?>
                                <img src="<?= $chauffeurPhoto ?>" alt="">
                            <?php else: ?>
                                <?= $chauffeurIni ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-size:14px;font-weight:600"><?= $chauffeurNom ?></div>
                            <div style="font-size:12px;color:#888">
                                <?= $matricule ? $matricule.' · ' : '' ?><?= ucfirst($typeVeh) ?>
                            </div>
                        </div>
                        <?php if ($chauffeurNote > 0): ?>
                        <div style="margin-left:auto;text-align:center">
                            <div style="font-size:18px;font-weight:700;color:#EF9F27"><?= number_format($chauffeurNote,1) ?></div>
                            <div style="font-size:10px;color:#bbb">/ 5</div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="stars">
                        <?php $note = (int)round($chauffeurNote);
                        for ($s = 1; $s <= 5; $s++): ?>
                        <i class="bi bi-star<?= $s <= $note ? '-fill' : '' ?>"
                           style="color:<?= $s <= $note ? '#EF9F27' : '#ddd' ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="info-section">
                    <div class="s-label">Détails de la course</div>
                    <table style="width:100%;font-size:13px;border-collapse:collapse">
                        <tr><td style="color:#aaa;padding:4px 0"><i class="bi bi-hash"></i> Référence</td>
                            <td style="text-align:right;font-weight:500"><?= $ref ?></td></tr>
                        <tr><td style="color:#aaa;padding:4px 0"><i class="bi bi-geo-fill"></i> Départ</td>
                            <td style="text-align:right"><?= $depart ?></td></tr>
                        <tr><td style="color:#aaa;padding:4px 0"><i class="bi bi-geo-alt-fill"></i> Arrivée</td>
                            <td style="text-align:right"><?= $destination ?></td></tr>
                        <tr><td style="color:#aaa;padding:4px 0"><i class="bi bi-cash-coin"></i> Montant</td>
                            <td style="text-align:right;font-weight:600;color:#185FA5"><?= $montant ?> FCFA</td></tr>
                        <tr><td style="color:#aaa;padding:4px 0"><i class="bi bi-wallet2"></i> Paiement</td>
                            <td style="text-align:right"><?= $paiIco ?> <?= $paiement ?: '—' ?></td></tr>
                    </table>
                </div>

                <div class="info-section">
                    <div class="s-label">Actions</div>
                    <?php if ($chauffeurTel): ?>
                    <a href="tel:<?= $chauffeurTel ?>" class="action-btn" style="text-decoration:none">
                        <i class="bi bi-telephone-fill"></i> Appeler le chauffeur (<?= $chauffeurTel ?>)
                    </a>
                    <?php endif; ?>
                    <button class="action-btn" onclick="signaler()">
                        <i class="bi bi-exclamation-triangle-fill"></i> Signaler un problème
                    </button>
                    <button class="action-btn danger" onclick="annuler()">
                        <i class="bi bi-x-circle-fill"></i> Annuler la course
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Pane : Note ── -->
        <div class="tab-pane" id="pane-note">
            <div class="infos-pane">
                <div class="info-section">
                    <div class="s-label">Notez votre chauffeur</div>
                    <div class="note-form" id="note-form">
                        <div style="font-size:13px;color:#666;margin-bottom:4px">
                            Comment s'est passée votre course avec <strong><?= $chauffeurNom ?></strong> ?
                        </div>
                        <div class="stars-pick" id="stars-pick">
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <span class="star-pick" data-val="<?= $s ?>" onclick="pickStar(<?= $s ?>)">★</span>
                            <?php endfor; ?>
                        </div>
                        <textarea class="note-textarea" id="note-commentaire"
                            placeholder="Laissez un commentaire (optionnel)…"></textarea>
                        <button class="note-submit" id="note-submit-btn" onclick="submitNote()">
                            <i class="bi bi-send-fill"></i> Envoyer ma note
                        </button>
                    </div>
                    <div id="note-done" style="display:none;text-align:center;padding:20px">
                        <i class="bi bi-patch-check-fill" style="font-size:40px;color:#1D9E75;display:block;margin-bottom:8px"></i>
                        <div style="font-size:14px;font-weight:600;color:#1D9E75">Merci pour votre avis !</div>
                        <div style="font-size:12px;color:#aaa;margin-top:4px">Votre note a bien été enregistrée.</div>
                    </div>
                </div>
                <div class="info-section">
                    <div class="s-label">Besoin d'aide ?</div>
                    <a href="support.php" class="action-btn" style="text-decoration:none">
                        <i class="bi bi-headset"></i> Contacter le support Yoon bu Gaw
                    </a>
                    <a href="mes_trajets.php" class="action-btn" style="text-decoration:none">
                        <i class="bi bi-list-ul"></i> Voir tous mes trajets
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>

<nav class="mobile-btm">
    <button class="mnav-item active" onclick="window.scrollTo(0,0)">
        <i class="bi bi-map-fill"></i>Carte
    </button>
    <button class="mnav-item" onclick="switchTab('chat')">
        <i class="bi bi-chat-dots-fill"></i>Messages
    </button>
    <button class="mnav-item" onclick="switchTab('infos')">
        <i class="bi bi-info-circle-fill"></i>Infos
    </button>
    <button class="mnav-item" onclick="switchTab('note')">
        <i class="bi bi-star-fill"></i>Note
    </button>
</nav>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
/* ── Config PHP → JS ─────────────────────────────────────── */
const COURSE_ID      = <?= $courseId ?>;
const DRIVER_LAT     = <?= $chauffeurLat ?>;
const DRIVER_LNG     = <?= $chauffeurLng ?>;
const CLIENT_LAT_INI = <?= $clientLat ?>;
const CLIENT_LNG_INI = <?= $clientLng ?>;
const DRIVER_NOM     = <?= json_encode($chauffeurNom) ?>;
const CLIENT_NOM     = <?= json_encode($clientNom) ?>;

/* ── Carte Leaflet ───────────────────────────────────────── */
const map = L.map('map', { zoomControl: false }).setView(
    [(DRIVER_LAT + CLIENT_LAT_INI) / 2, (DRIVER_LNG + CLIENT_LNG_INI) / 2], 14
);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19,
}).addTo(map);

L.control.zoom({ position: 'topright' }).addTo(map);

/* ── Icônes ──────────────────────────────────────────────── */
const iconDriver = L.divIcon({
    className: '',
    html: `<div style="position:relative;width:42px;height:42px">
             <div style="position:absolute;inset:0;border-radius:50%;background:rgba(24,95,165,.2);animation:drvpulse 2s ease-out infinite"></div>
             <div style="position:absolute;inset:0;border-radius:50%;background:#185FA5;border:3px solid #fff;display:flex;align-items:center;justify-content:center;font-size:20px;box-shadow:0 2px 10px rgba(0,0,0,.3)">🚕</div>
           </div>`,
    iconSize: [42, 42], iconAnchor: [21, 21],
});

const iconClient = L.divIcon({
    className: '',
    html: `<div style="width:36px;height:36px;border-radius:50%;background:#E24B4A;border:3px solid #fff;display:flex;align-items:center;justify-content:center;font-size:18px;box-shadow:0 2px 10px rgba(0,0,0,.3)">📍</div>`,
    iconSize: [36, 36], iconAnchor: [18, 36],
});

/* CORRECTION : animation définie dans le CSS, plus injectée ici */

const markerDriver = L.marker([DRIVER_LAT, DRIVER_LNG], { icon: iconDriver })
    .addTo(map).bindPopup(`<b>${DRIVER_NOM}</b><br>Votre chauffeur`);

const markerClient = L.marker([CLIENT_LAT_INI, CLIENT_LNG_INI], { icon: iconClient })
    .addTo(map).bindPopup(`<b>${CLIENT_NOM}</b><br>Votre position`);

/* ── Route ───────────────────────────────────────────────── */
let routeLine = null;

/* CORRECTION : ne redessine la route que si la position a changé */
let lastRouteKey = '';
function drawRoute(dLat, dLng, cLat, cLng) {
    const key = `${dLat.toFixed(5)},${dLng.toFixed(5)},${cLat.toFixed(5)},${cLng.toFixed(5)}`;
    if (key === lastRouteKey) return;
    lastRouteKey = key;
    if (routeLine) map.removeLayer(routeLine);
    routeLine = L.polyline([[dLat, dLng], [cLat, cLng]], {
        color: '#185FA5', weight: 4, opacity: .7, dashArray: '8,5',
    }).addTo(map);
}

drawRoute(DRIVER_LAT, DRIVER_LNG, CLIENT_LAT_INI, CLIENT_LNG_INI);
fitBoth();

/* ── Distance haversine ──────────────────────────────────── */
function haversine(lat1, lng1, lat2, lng2) {
    const R = 6371000;
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLng = (lng2 - lng1) * Math.PI / 180;
    const a = Math.sin(dLat/2)**2
            + Math.cos(lat1 * Math.PI/180) * Math.cos(lat2 * Math.PI/180) * Math.sin(dLng/2)**2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}
function fmtDist(m)  { return m < 1000 ? Math.round(m) + ' m' : (m / 1000).toFixed(1) + ' km'; }
function fmtEta(m)   { const mins = Math.ceil(m / 50 / 60); return mins <= 1 ? '< 1 min' : mins + ' min'; }

function updateStats(dLat, dLng, cLat, cLng) {
    const d = haversine(dLat, dLng, cLat, cLng);
    document.getElementById('stat-dist').textContent     = fmtDist(d);
    document.getElementById('stat-eta').textContent      = fmtEta(d);
    document.getElementById('route-detail').textContent  = fmtDist(d) + ' · ' + fmtEta(d);
    document.getElementById('dist-badge-txt').textContent = fmtDist(d) + ' du chauffeur';
    document.getElementById('dist-badge').style.display  = 'flex';
}

updateStats(DRIVER_LAT, DRIVER_LNG, CLIENT_LAT_INI, CLIENT_LNG_INI);

/* ── Helpers carte ───────────────────────────────────────── */
function centerOnDriver() { map.setView(markerDriver.getLatLng(), 16); }
function centerOnMe()     { map.setView(markerClient.getLatLng(), 16); }
function fitBoth() {
    map.fitBounds(
        L.latLngBounds([markerDriver.getLatLng(), markerClient.getLatLng()]),
        { padding: [60, 60] }
    );
}

/* ── GPS client ──────────────────────────────────────────── */
let myLat = CLIENT_LAT_INI, myLng = CLIENT_LNG_INI;

/* CORRECTION : throttle de l'envoi GPS (1 envoi toutes les 5 s max) */
let lastPosSent = 0;

if ('geolocation' in navigator) {
    navigator.geolocation.watchPosition(pos => {
        myLat = pos.coords.latitude;
        myLng = pos.coords.longitude;
        markerClient.setLatLng([myLat, myLng]);
        updateStats(markerDriver.getLatLng().lat, markerDriver.getLatLng().lng, myLat, myLng);
        drawRoute(markerDriver.getLatLng().lat, markerDriver.getLatLng().lng, myLat, myLng);

        const now = Date.now();
        if (now - lastPosSent > 5000) {
            lastPosSent = now;
            sendMyPos(myLat, myLng);
        }
    }, err => console.warn('GPS client:', err), { enableHighAccuracy: true, maximumAge: 4000 });
}

async function sendMyPos(lat, lng) {
    try {
        await fetch('update_position.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ course_id: COURSE_ID, lat, lng, role: 'client' }),
        });
    } catch (e) {}
}

/* ── Polling position chauffeur ──────────────────────────── */
/* CORRECTION : AbortController pour éviter l'accumulation de requêtes */
let driverPollCtrl = null;

async function pollDriverPos() {
    if (document.hidden) return; /* CORRECTION : pause si onglet invisible */
    if (driverPollCtrl) driverPollCtrl.abort();
    driverPollCtrl = new AbortController();
    try {
        const r = await fetch(`get_position.php?course_id=${COURSE_ID}&role=chauffeur`, {
            signal: driverPollCtrl.signal,
        });
        const d = await r.json();
        if (d.lat && d.lng) {
            markerDriver.setLatLng([d.lat, d.lng]);
            drawRoute(d.lat, d.lng, myLat, myLng);
            updateStats(d.lat, d.lng, myLat, myLng);
        }
    } catch (e) {
        if (e.name !== 'AbortError') console.warn('pollDriverPos:', e);
    }
}
setInterval(pollDriverPos, 4000);

/* ── Onglets ─────────────────────────────────────────────── */
function switchTab(id) {
    ['chat', 'infos', 'note'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('active', t === id);
        document.getElementById('pane-' + t).classList.toggle('active', t === id);
    });
    if (id === 'chat') {
        document.getElementById('unread-dot').style.display = 'none';
        unreadCount = 0;
        document.getElementById('chat-area').scrollTop = 99999;
    }
    document.querySelectorAll('.mnav-item').forEach((b, i) => {
        b.classList.toggle('active', ['', 'chat', 'infos', 'note'][i] === id || (i === 0 && id === ''));
    });
}

/* ── Chat ────────────────────────────────────────────────── */
let unreadCount = 0;
let lastMsgId   = <?= $lastMsgId /* CORRECTION : calculé proprement côté PHP */ ?>;

/* CORRECTION : AudioContext réutilisable — évite la fuite mémoire */
let audioCtx = null;
function getAudioCtx() {
    if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    return audioCtx;
}

function now() {
    const d = new Date();
    return String(d.getHours()).padStart(2, '0') + ':' + String(d.getMinutes()).padStart(2, '0');
}

function escHtml(s) {
    return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function appendMsg(txt, side) {
    const area = document.getElementById('chat-area');
    const div  = document.createElement('div');
    div.className = 'msg ' + side;
    div.innerHTML = `<div class="bubble ${side}">${escHtml(txt)}</div><div class="msg-time">${now()}</div>`;
    area.appendChild(div);
    area.scrollTop = area.scrollHeight;
}

function showToast(txt) {
    const toast = document.getElementById('notif-toast');
    document.getElementById('notif-text').textContent = txt;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 4000);

    if (Notification.permission === 'granted') {
        new Notification('Yoon bu Gaw — Votre chauffeur', { body: txt, icon: '../assets/images/logo.png' });
    }

    /* CORRECTION : AudioContext réutilisé, plus recréé à chaque fois */
    try {
        const ctx  = getAudioCtx();
        const osc  = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.connect(gain); gain.connect(ctx.destination);
        osc.frequency.value = 880; gain.gain.value = 0.15;
        osc.start(); osc.stop(ctx.currentTime + 0.18);
    } catch (e) {}
}

/* CORRECTION : flag d'envoi en cours pour éviter le double-envoi */
let isSending = false;

async function sendMsg() {
    if (isSending) return;
    const inp = document.getElementById('msg-input');
    const btn = document.getElementById('send-btn');
    const txt = inp.value.trim();
    if (!txt) return;

    isSending = true;
    btn.disabled = true;
    inp.value = '';
    appendMsg(txt, 'sent');

    try {
        await fetch('send_message.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ course_id: COURSE_ID, contenu: txt }),
        });
    } catch (e) {
        console.warn('sendMsg:', e);
    } finally {
        isSending    = false;
        btn.disabled = false;
    }
}

function quickSend(txt) {
    document.getElementById('msg-input').value = txt;
    sendMsg();
}

/* CORRECTION : AbortController pour le polling messages */
let msgPollCtrl = null;

async function pollMessages() {
    if (document.hidden) return; /* CORRECTION : pause si onglet invisible */
    if (msgPollCtrl) msgPollCtrl.abort();
    msgPollCtrl = new AbortController();
    try {
        const r = await fetch(`get_messages.php?course_id=${COURSE_ID}&after=${lastMsgId}`, {
            signal: msgPollCtrl.signal,
        });
        const msgs = await r.json();
        if (!Array.isArray(msgs)) return;
        msgs.forEach(m => {
            if (m.expediteur_role !== 'client') {
                appendMsg(m.contenu, 'recv');
                unreadCount++;
                showToast(DRIVER_NOM + ' : ' + m.contenu.substring(0, 50) + (m.contenu.length > 50 ? '…' : ''));
                if (!document.getElementById('tab-chat').classList.contains('active')) {
                    document.getElementById('unread-dot').style.display = 'inline-block';
                }
            }
            /* CORRECTION : parseInt avec base 10 explicite */
            lastMsgId = Math.max(lastMsgId, parseInt(m.id, 10));
        });
    } catch (e) {
        if (e.name !== 'AbortError') console.warn('pollMessages:', e);
    }
}
setInterval(pollMessages, 3000);

/* Scroll initial */
document.getElementById('chat-area').scrollTop = 99999;

/* Notification navigateur */
if (Notification.permission === 'default') {
    Notification.requestPermission();
}

/* ── Note chauffeur ──────────────────────────────────────── */
let noteStar  = 0;
let noteSubmitted = false; /* CORRECTION : protection contre le double-submit */

function pickStar(n) {
    noteStar = n;
    document.querySelectorAll('.star-pick').forEach((s, i) => {
        s.classList.toggle('lit', i < n);
    });
}

async function submitNote() {
    if (noteSubmitted) return;
    if (!noteStar) { alert('Choisissez une note entre 1 et 5 étoiles.'); return; }

    const btn         = document.getElementById('note-submit-btn');
    const commentaire = document.getElementById('note-commentaire').value.trim();

    noteSubmitted = true;
    btn.disabled  = true;

    try {
        await fetch('noter_chauffeur.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/json' },
            body:    JSON.stringify({ course_id: COURSE_ID, note: noteStar, commentaire }),
        });
    } catch (e) {
        console.warn('submitNote:', e);
        /* CORRECTION : si l'envoi échoue, permettre de réessayer */
        noteSubmitted = false;
        btn.disabled  = false;
        return;
    }

    document.getElementById('note-form').style.display = 'none';
    document.getElementById('note-done').style.display = 'block';
}

/* ── Actions ─────────────────────────────────────────────── */
function signaler() {
    if (confirm('Signaler un problème pour cette course ?')) {
        window.location = `signaler.php?id=${COURSE_ID}`;
    }
}
function annuler() {
    if (confirm('Êtes-vous sûr de vouloir annuler cette course ?')) {
        window.location = `annuler_course.php?id=${COURSE_ID}`;
    }
}
</script>
</body>
</html>