<?php
require_once "../config/database.php";

function distance($lat1, $lon1, $lat2, $lon2) {
    $earth = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);

    $c = 2 * atan2(sqrt($a), sqrt(1-$a));

    return $earth * $c; // km
}

/*
|-----------------------------------
| 1. RÉCUPÉRER COURSE EN ATTENTE
|-----------------------------------
*/
$res = $pdo->query("
    SELECT * FROM reservations
    WHERE chauffeur_id IS NULL
    AND statut = 'en attente'
    ORDER BY id DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

if (!$res) exit;

/*
|-----------------------------------
| 2. CHAUFFEURS DISPONIBLES
|-----------------------------------
*/
$drivers = $pdo->query("
    SELECT * FROM users
    WHERE role = 'chauffeur'
    AND disponible = 1
")->fetchAll(PDO::FETCH_ASSOC);

if (!$drivers) exit;

/*
|-----------------------------------
| 3. SCORE INTELLIGENT
|-----------------------------------
*/
$bestDriver = null;
$bestScore = -INF;

$clientLat = $res['lat'] ?? 0;
$clientLng = $res['lng'] ?? 0;

foreach ($drivers as $d) {

    if (!$d['lat'] || !$d['lng']) continue;

    $dist = distance($clientLat, $clientLng, $d['lat'], $d['lng']);

    // 🎯 SCORE INTELLIGENT
    // plus proche = mieux
    $distanceScore = max(0, 100 - ($dist * 10));

    // ⭐ note chauffeur (sur 5)
    $noteScore = ($d['note'] ?? 5) * 20;

    // 🟢 bonus disponibilité
    $availabilityScore = 50;

    // ⏱ pénalité si loin
    $timePenalty = $dist * 5;

    $score = $distanceScore + $noteScore + $availabilityScore - $timePenalty;

    if ($score > $bestScore) {
        $bestScore = $score;
        $bestDriver = $d;
    }
}

if (!$bestDriver) {
    exit("Aucun chauffeur trouvé");
}

/*
|-----------------------------------
| 4. ESTIMATION TEMPS (simple)
|-----------------------------------
*/
$speed = 40; // km/h moyenne ville
$eta = round(($dist / $speed) * 60); // minutes

/*
|-----------------------------------
| 5. ASSIGNATION
|-----------------------------------
*/
$stmt = $pdo->prepare("
    UPDATE reservations
    SET chauffeur_id = :cid,
        statut = 'confirmée',
        statut_course = 'assignée',
        eta = :eta
    WHERE id = :rid
");

$stmt->execute([
    ':cid' => $bestDriver['id'],
    ':rid' => $res['id'],
    ':eta' => $eta
]);

/*
|-----------------------------------
| 6. NOTIFICATION CHAUFFEUR
|-----------------------------------
*/
$notif = $pdo->prepare("
    INSERT INTO notifications (user_id, titre, message)
    VALUES (:uid, :titre, :msg)
");

$notif->execute([
    ':uid' => $bestDriver['id'],
    ':titre' => 'Nouvelle course',
    ':msg' => 'Une course vous a été assignée. ETA: '.$eta.' min'
]);

