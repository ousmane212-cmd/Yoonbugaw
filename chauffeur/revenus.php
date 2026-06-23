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
if (!$chauffeurData) { header('Location: ../auth/login.php'); exit; }

$chauffeurNom   = htmlspecialchars(trim($chauffeurData['nom'] ?? 'Chauffeur'));
$chauffeurPhoto = htmlspecialchars($chauffeurData['photo'] ?? '');
$typeVehicule   = strtolower($chauffeurData['type'] ?? 'taxi');
$statutChauffeur= $chauffeurData['statut'] ?? 'disponible';

$parts     = explode(' ', $chauffeurNom);
$initiales = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));
$prenom    = $parts[0];


$stmt = $pdo->prepare('SELECT * FROM vehicules WHERE chauffeur = ? LIMIT 1');
$stmt->execute([$chauffeurNom]);
$vehiculeData = $stmt->fetch(PDO::FETCH_ASSOC);
$vehiculeNom  = htmlspecialchars($vehiculeData['nom'] ?? ($chauffeurData['vehicule'] ?? '—'));
$matricule    = htmlspecialchars($vehiculeData['matricule'] ?? '—');


$periode = $_GET['periode'] ?? 'mois';
$annee   = (int)($_GET['annee'] ?? date('Y'));
$moisNum = (int)($_GET['mois'] ?? date('n'));

switch ($periode) {
    case 'jour':
        $dateDebut = date('Y-m-d');
        $dateFin   = date('Y-m-d');
        break;
    case 'semaine':
        $dateDebut = date('Y-m-d', strtotime('monday this week'));
        $dateFin   = date('Y-m-d', strtotime('sunday this week'));
        break;
    case 'annee':
        $dateDebut = "$annee-01-01";
        $dateFin   = "$annee-12-31";
        break;
    default: // mois
        $dateDebut = date("$annee-m-01", mktime(0,0,0,$moisNum,1,$annee));
        $dateFin   = date("$annee-m-t", mktime(0,0,0,$moisNum,1,$annee));
}


$stmt = $pdo->prepare('SELECT COALESCE(SUM(montant),0) FROM reservations WHERE chauffeur_id=?');
$stmt->execute([$chauffeurId]);
$totalGainsAll = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM reservations WHERE chauffeur_id=?');
$stmt->execute([$chauffeurId]);
$totalCoursesAll = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(montant),0) FROM reservations WHERE chauffeur_id=? AND DATE(date_reservation)=CURDATE()");
$stmt->execute([$chauffeurId]);
$gainsAujourdhui = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(montant),0) FROM reservations WHERE chauffeur_id=? AND DATE_FORMAT(date_reservation,'%Y-%m')=?");
$stmt->execute([$chauffeurId, date('Y-m')]);
$gainsMoisActuel = (float)$stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(montant),0) FROM reservations WHERE chauffeur_id=? AND YEAR(date_reservation)=?");
$stmt->execute([$chauffeurId, date('Y')]);
$gainsAnnee = (float)$stmt->fetchColumn();


$stmt = $pdo->prepare("SELECT COALESCE(SUM(montant),0), COUNT(*) FROM reservations WHERE chauffeur_id=? AND DATE(date_reservation) BETWEEN ? AND ?");
$stmt->execute([$chauffeurId, $dateDebut, $dateFin]);
[$gainsPeriode, $coursesPeriode] = $stmt->fetch(PDO::FETCH_NUM);
$gainsPeriode  = (float)$gainsPeriode;
$coursesPeriode = (int)$coursesPeriode;
$moyennePeriode = $coursesPeriode > 0 ? $gainsPeriode / $coursesPeriode : 0;


$stmt = $pdo->prepare("SELECT DATE(date_reservation) AS j, SUM(montant) AS t FROM reservations WHERE chauffeur_id=? AND date_reservation>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) GROUP BY DATE(date_reservation)");
$stmt->execute([$chauffeurId]);
$g7rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$gains7j = []; $labels7j = [];
for ($i=6;$i>=0;$i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $gains7j[]  = (float)($g7rows[$d] ?? 0);
    $labels7j[] = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'][date('w',strtotime($d))];
}


$stmt = $pdo->prepare("SELECT DATE_FORMAT(date_reservation,'%Y-%m') AS m, SUM(montant) AS t FROM reservations WHERE chauffeur_id=? AND date_reservation>=DATE_SUB(CURDATE(),INTERVAL 11 MONTH) GROUP BY m ORDER BY m ASC");
$stmt->execute([$chauffeurId]);
$gMoisRows  = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$gains12m   = array_values($gMoisRows);
$labels12m  = [];
$nomsMois   = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
foreach (array_keys($gMoisRows) as $ym) {
    [,$m] = explode('-', $ym);
    $labels12m[] = $nomsMois[(int)$m - 1];
}


$stmt = $pdo->prepare("SELECT mode_paiement, COUNT(*) as cnt, SUM(montant) as total FROM reservations WHERE chauffeur_id=? AND mode_paiement IS NOT NULL AND mode_paiement!='' GROUP BY mode_paiement ORDER BY cnt DESC");
$stmt->execute([$chauffeurId]);
$paiements = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT type_transport, COUNT(*) as cnt, SUM(montant) as total FROM reservations WHERE chauffeur_id=? GROUP BY type_transport ORDER BY cnt DESC");
$stmt->execute([$chauffeurId]);
$transports = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT destination, COUNT(*) as cnt, SUM(montant) as total FROM reservations WHERE chauffeur_id=? GROUP BY destination ORDER BY cnt DESC LIMIT 5");
$stmt->execute([$chauffeurId]);
$topDestinations = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT r.id, r.depart, r.destination, r.montant, r.mode_paiement, r.type_transport, r.statut, r.statut_course, r.user_name, r.date_reservation, r.note_client FROM reservations r WHERE r.chauffeur_id=? AND DATE(r.date_reservation) BETWEEN ? AND ? ORDER BY r.date_reservation DESC LIMIT 30");
$stmt->execute([$chauffeurId, $dateDebut, $dateFin]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);


$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE chauffeur_id=? AND statut_course='en_attente'");
$stmt->execute([$chauffeurId]);
$nbEnAttente = (int)$stmt->fetchColumn();


function fmt(float $n): string { return number_format($n, 0, ',', ' '); }
function elapsed(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60)    return 'À l\'instant';
    if ($diff < 3600)  return 'Il y a '.floor($diff/60).' min';
    if ($diff < 86400) return 'Il y a '.floor($diff/3600).'h';
    return date('d/m/Y', strtotime($date));
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
function statutClass(string $s): string {
    $s = strtolower($s);
    if (str_contains($s,'annul'))   return 'badge-annule';
    if (str_contains($s,'cours'))   return 'badge-encours';
    if (str_contains($s,'attente')) return 'badge-attente';
    return 'badge-termine';
}
function paiementColor(string $mode): string {
    return match(strtolower(trim($mode))) {
        'orange money','orange_money','om' => '#f97316',
        'wave'   => '#3b82f6',
        'free money','free' => '#8b5cf6',
        'espèces','especes','cash' => '#15803d',
        default  => '#6b7280',
    };
}
function typeIcon(string $type): string {
    return match(strtolower($type)) { 'bus'=>'🚌','cargo'=>'🚛','moto'=>'🏍️',default=>'🚕' };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Yoon bu Gaw — Mes Revenus</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=DM+Sans:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="revenus.css">

</head>
<body>

<div class="layout" id="app">


    <?php
include_once "sidebar.php";
?>
   
    <div class="main">

        <!-- TOPBAR -->
        <header class="topbar">
            <div class="tb-left">
                <button class="tb-menu" onclick="toggleSidebar()" aria-label="Menu"><i class="bi bi-list"></i></button>
                <div>
                    <div class="tb-title">Mes revenus</div>
                    <div class="tb-breadcrumb"><?= date('l d F Y') ?></div>
                </div>
            </div>
            <div class="tb-right">
                <a href="?periode=<?= $periode ?>&export=csv" class="icon-btn" title="Exporter CSV" onclick="toast('Export CSV en cours…','info');return false">
                    <i class="bi bi-download"></i>
                </a>
                <div class="tb-avatar" title="<?= $chauffeurNom ?>">
                    <?php if ($chauffeurPhoto): ?>
                        <img src="<?= $chauffeurPhoto ?>" alt="">
                    <?php else: ?>
                        <?= $initiales ?>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <div class="content">

            <!-- Hero -->
            <div class="page-hero">
                <div>
                    <div class="page-hero-title">💰 Mes revenus</div>
                    <div class="page-hero-sub">
                        <?= $chauffeurNom ?> &bull;
                        <?= fmt($totalGainsAll) ?> FCFA au total &bull;
                        <?= $totalCoursesAll ?> courses effectuées
                    </div>
                </div>
                <div class="page-hero-right">
                    <button class="btn-export btn-export-csv" onclick="toast('Export CSV en cours…','info')">
                        <i class="bi bi-filetype-csv"></i> CSV
                    </button>
                    <button class="btn-export btn-export-pdf" onclick="toast('Export PDF en cours…','info')">
                        <i class="bi bi-filetype-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <!-- Filtre période -->
            <div class="filter-bar">
                <span class="filter-label">Période :</span>
                <div class="periode-tabs">
                    <?php foreach (['jour'=>"Aujourd'hui",'semaine'=>'Semaine','mois'=>'Ce mois','annee'=>"Année"] as $k=>$v): ?>
                    <button class="periode-tab <?= $periode===$k?'active':'' ?>"
                        onclick="setPeriode('<?= $k ?>')"><?= $v ?></button>
                    <?php endforeach; ?>
                </div>
                <div class="filter-sep"></div>
                <select class="filter-select" id="sel-mois" onchange="setMois(this.value)" <?= $periode!=='mois'?'style="display:none"':'' ?>>
                    <?php
                    $moisNoms = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                    for ($m=1;$m<=12;$m++): ?>
                    <option value="<?= $m ?>" <?= $moisNum===$m?'selected':'' ?>><?= $moisNoms[$m-1] ?></option>
                    <?php endfor; ?>
                </select>
                <select class="filter-select" id="sel-annee" onchange="setAnnee(this.value)">
                    <?php for ($y=date('Y');$y>=date('Y')-3;$y--): ?>
                    <option value="<?= $y ?>" <?= $annee===$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor; ?>
                </select>
                <span style="font-size:12px;color:var(--n400);margin-left:auto">
                    <?= date('d/m/Y', strtotime($dateDebut)) ?> → <?= date('d/m/Y', strtotime($dateFin)) ?>
                </span>
            </div>

            <!-- Stats période -->
            <div class="stats-grid">
                <div class="stat-card sc-green">
                    <div class="stat-icon si-green"><i class="bi bi-cash-stack"></i></div>
                    <div class="stat-val"><?= fmt($gainsPeriode) ?></div>
                    <div class="stat-label">Gains sur la période (FCFA)</div>
                    <div class="stat-change up"><i class="bi bi-arrow-up"></i> <?= fmt($gainsAujourdhui) ?> F aujourd'hui</div>
                </div>
                <div class="stat-card sc-amber">
                    <div class="stat-icon si-amber"><i class="bi bi-ticket-perforated-fill"></i></div>
                    <div class="stat-val"><?= $coursesPeriode ?></div>
                    <div class="stat-label">Courses sur la période</div>
                    <div class="stat-change"><i class="bi bi-bar-chart"></i> <?= $totalCoursesAll ?> au total</div>
                </div>
                <div class="stat-card sc-blue">
                    <div class="stat-icon si-blue"><i class="bi bi-calculator"></i></div>
                    <div class="stat-val"><?= fmt($moyennePeriode) ?></div>
                    <div class="stat-label">Moyenne par course (FCFA)</div>
                    <div class="stat-change"><i class="bi bi-graph-up"></i> Sur <?= $coursesPeriode ?> course(s)</div>
                </div>
                <div class="stat-card sc-purple">
                    <div class="stat-icon si-purple"><i class="bi bi-graph-up-arrow"></i></div>
                    <div class="stat-val"><?= fmt($gainsAnnee) ?></div>
                    <div class="stat-label">Gains totaux <?= date('Y') ?> (FCFA)</div>
                    <div class="stat-change"><i class="bi bi-calendar-check"></i> Cumulé depuis janvier</div>
                </div>
            </div>

            <!-- Grid principal -->
            <div class="main-grid">

                <!-- Colonne gauche -->
                <div>

                    <!-- Graphique barres 7j -->
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">
                                <i class="bi bi-bar-chart-fill" style="color:var(--g700)"></i>
                                Gains des 7 derniers jours
                            </div>
                            <span class="ph-badge phb-green"><?= fmt(array_sum($gains7j)) ?> FCFA</span>
                        </div>
                        <div class="chart-wrap" style="height:220px">
                            <canvas id="chart7j" role="img" aria-label="Graphique des gains sur 7 jours"></canvas>
                        </div>
                    </div>

                    <!-- Graphique ligne 12 mois -->
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">
                                <i class="bi bi-graph-up" style="color:var(--blue)"></i>
                                Évolution sur 12 mois
                            </div>
                            <span class="ph-badge phb-blue"><?= fmt($gainsAnnee) ?> FCFA</span>
                        </div>
                        <div class="chart-wrap" style="height:220px">
                            <canvas id="chart12m" role="img" aria-label="Graphique des gains sur 12 mois"></canvas>
                        </div>
                    </div>

                    <!-- Transactions -->
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">
                                <i class="bi bi-list-ul" style="color:var(--n600)"></i>
                                Transactions
                                <span style="font-size:11px;color:var(--n400);font-weight:400">(<?= count($transactions) ?> résultats)</span>
                            </div>
                            <a href="mes_courses.php" style="font-size:12px;color:var(--g700);text-decoration:none;font-weight:500">
                                Voir tout <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                        <?php if (empty($transactions)): ?>
                            <div class="empty-state">
                                <i class="bi bi-inbox"></i>
                                <p>Aucune transaction sur cette période</p>
                            </div>
                        <?php else: ?>
                        <div style="overflow-x:auto">
                            <table class="tx-table">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Trajet</th>
                                        <th>Client</th>
                                        <th>Paiement</th>
                                        <th>Montant</th>
                                        <th>Statut</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transactions as $tx): ?>
                                    <?php
                                        $sRaw = !empty($tx['statut_course']) ? $tx['statut_course'] : ($tx['statut'] ?? '');
                                        $hDate = !empty($tx['date_reservation']) ? date('d/m · H:i', strtotime($tx['date_reservation'])) : '—';
                                    ?>
                                    <tr>
                                        <?php foreach ($transactions as $index => $tx): ?>
    <td style="font-family:var(--font-mono);font-size:11px;color:var(--n400)">
        <?= $index + 1 ?>
    </td>
<?php endforeach; ?>
                                        <td>
                                            <div class="tx-route">
                                                <?= typeIcon($tx['type_transport'] ?? 'taxi') ?>
                                                <?= htmlspecialchars(mb_strimwidth($tx['depart'],0,18,'…')) ?>
                                                <i class="bi bi-arrow-right" style="color:var(--n300);font-size:11px"></i>
                                                <?= htmlspecialchars(mb_strimwidth($tx['destination'],0,18,'…')) ?>
                                            </div>
                                        </td>
                                        <td class="tx-client"><?= htmlspecialchars($tx['user_name'] ?? '—') ?></td>
                                        <td>
                                            <?php if (!empty($tx['mode_paiement'])): ?>
                                            <span class="tx-pm">
                                                <span style="width:6px;height:6px;border-radius:50%;background:<?= paiementColor($tx['mode_paiement']) ?>;display:inline-block"></span>
                                                <?= htmlspecialchars($tx['mode_paiement']) ?>
                                            </span>
                                            <?php else: ?>
                                            <span style="color:var(--n300)">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="tx-amount"><?= fmt((float)$tx['montant']) ?> F</td>
                                        <td><span class="badge <?= statutClass($sRaw) ?>"><?= statutLabel($sRaw) ?></span></td>
                                        <td class="tx-time"><?= $hDate ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Colonne droite -->
                <div>

                    <!-- Objectif mensuel -->
                    <?php
                    $objectifMensuel = 500000;
                    $pctObjectif = min(100, round($gainsMoisActuel / $objectifMensuel * 100));
                    ?>
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">
                                <i class="bi bi-trophy-fill" style="color:var(--amber)"></i>
                                Objectif du mois
                            </div>
                            <span class="ph-badge phb-amber"><?= $pctObjectif ?>%</span>
                        </div>
                        <div class="objectif-wrap">
                            <div class="obj-label">
                                <span><?= fmt($gainsMoisActuel) ?> FCFA</span>
                                <span>/ <?= fmt($objectifMensuel) ?> FCFA</span>
                            </div>
                            <div class="obj-bar">
                                <div class="obj-fill" style="width:<?= $pctObjectif ?>%"></div>
                            </div>
                            <div class="obj-note">
                                <?php if ($pctObjectif >= 100): ?>
                                    🎉 Objectif atteint ce mois !
                                <?php else: ?>
                                    Il reste <?= fmt($objectifMensuel - $gainsMoisActuel) ?> FCFA pour atteindre l'objectif
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Résumé financier -->
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">
                                <i class="bi bi-wallet2" style="color:var(--g700)"></i>
                                Résumé financier
                            </div>
                        </div>
                        <div class="panel-body">
                            <?php
                            $rows = [
                                ["Aujourd'hui",            fmt($gainsAujourdhui).' F', true],
                                ['Ce mois',                fmt($gainsMoisActuel).' F', true],
                                ['Cette année ('.date('Y').')', fmt($gainsAnnee).' F', true],
                                ['Total cumulé',           fmt($totalGainsAll).' F', true],
                                ['Nombre de courses',      $totalCoursesAll, false],
                                ['Moy. / course',          fmt($totalCoursesAll>0 ? $totalGainsAll/$totalCoursesAll : 0).' F', false],
                            ];
                            foreach ($rows as [$lbl, $val, $green]): ?>
                            <div class="summary-row">
                                <span class="summary-label"><?= $lbl ?></span>
                                <span class="summary-val <?= $green?'green':'' ?>"><?= $val ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Répartition paiements -->
                    <?php
                    $pmColors = ['#f97316','#3b82f6','#8b5cf6','#15803d','#ec4899','#6b7280'];
                    $pmLabels = []; $pmData = []; $pmTotals = []; $pmColorArr = [];
                    $pmTotal = array_sum(array_column($paiements,'cnt')) ?: 1;
                    foreach ($paiements as $i=>$p) {
                        $pmLabels[] = $p['mode_paiement'] ?: 'Non précisé';
                        $pmData[]   = (int)$p['cnt'];
                        $pmTotals[] = (float)$p['total'];
                        $pmColorArr[] = $pmColors[$i % count($pmColors)];
                    }
                    ?>
                    <?php if (!empty($paiements)): ?>
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">
                                <i class="bi bi-credit-card-2-front" style="color:var(--blue)"></i>
                                Modes de paiement
                            </div>
                        </div>
                        <div class="donut-wrap">
                            <div class="donut-chart">
                                <canvas id="chartPm" role="img" aria-label="Répartition des modes de paiement"></canvas>
                                <div class="donut-center">
                                    <div class="donut-center-val"><?= $totalCoursesAll ?></div>
                                    <div class="donut-center-lbl">courses</div>
                                </div>
                            </div>
                            <div class="donut-legend">
                                <?php foreach ($paiements as $i=>$p): ?>
                                <div class="legend-item">
                                    <span class="legend-dot" style="background:<?= $pmColorArr[$i] ?>"></span>
                                    <span class="legend-name"><?= htmlspecialchars($p['mode_paiement'] ?: 'Non précisé') ?></span>
                                    <span class="legend-val"><?= $p['cnt'] ?></span>
                                    <span class="legend-pct"><?= round($p['cnt']/$pmTotal*100) ?>%</span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Top destinations -->
                    <?php if (!empty($topDestinations)): ?>
                    <div class="panel">
                        <div class="panel-head">
                            <div class="panel-title">
                                <i class="bi bi-geo-alt-fill" style="color:var(--red)"></i>
                                Top destinations
                            </div>
                        </div>
                        <div class="panel-body">
                            <?php $maxDest = max(array_column($topDestinations,'cnt')) ?: 1; ?>
                            <?php foreach ($topDestinations as $i=>$d): ?>
                            <div class="dest-row">
                                <div class="dest-rank <?= ['r1','r2','r3'][$i] ?? '' ?>"><?= $i+1 ?></div>
                                <div class="dest-info">
                                    <div class="dest-name"><?= htmlspecialchars($d['destination']) ?></div>
                                    <div class="dest-count"><?= $d['cnt'] ?> course(s)</div>
                                </div>
                                <div class="dest-bar-wrap">
                                    <div class="dest-bar-fill" style="width:<?= round($d['cnt']/$maxDest*100) ?>%"></div>
                                </div>
                                <div class="dest-amount"><?= fmt((float)$d['total']) ?> F</div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>

            </div>

        </div><!-- .content -->
    </div><!-- .main -->

</div><!-- .layout -->

<!-- Mobile nav -->
<nav class="mobile-nav">
    <div class="mn-inner">
        <a class="mnav-btn" href="dashboard.php"><i class="bi bi-house-fill"></i>Accueil</a>
        <a class="mnav-btn" href="dashboard.php#section-courses"><i class="bi bi-map-fill"></i>Courses</a>
        <a class="mnav-btn" href="mes_courses.php"><i class="bi bi-clock-history"></i>Historique</a>
        <a class="mnav-btn active" href="revenus.php"><i class="bi bi-graph-up-arrow"></i>Revenus</a>
        <button class="mnav-btn" onclick="toast('Profil',\'info\')"><i class="bi bi-person-fill"></i>Profil</button>
    </div>
</nav>

<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>


<script>
'use strict';

const APP = {
    gains7j:    <?= json_encode($gains7j) ?>,
    labels7j:   <?= json_encode($labels7j) ?>,
    gains12m:   <?= json_encode(array_map('floatval', $gains12m)) ?>,
    labels12m:  <?= json_encode($labels12m) ?>,
    pmData:     <?= json_encode($pmData) ?>,
    pmLabels:   <?= json_encode($pmLabels) ?>,
    pmColors:   <?= json_encode($pmColorArr) ?>,
    periode:    '<?= $periode ?>',
    moisNum:    <?= $moisNum ?>,
    annee:      <?= $annee ?>,
};


function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('open');
}
document.addEventListener('click', e => {
    const sb = document.getElementById('sidebar');
    if (sb.classList.contains('open') && !sb.contains(e.target) && !e.target.closest('.tb-menu')) {
        sb.classList.remove('open');
    }
});


function setPeriode(p) {
    const url = new URL(window.location);
    url.searchParams.set('periode', p);
    window.location = url;
}
function setMois(m) {
    const url = new URL(window.location);
    url.searchParams.set('mois', m);
    url.searchParams.set('periode', 'mois');
    window.location = url;
}
function setAnnee(a) {
    const url = new URL(window.location);
    url.searchParams.set('annee', a);
    window.location = url;
}
/* Afficher/masquer select mois selon période */
(function() {
    const sm = document.getElementById('sel-mois');
    document.querySelectorAll('.periode-tab').forEach(btn => {
        btn.addEventListener('click', () => {
            if (sm) sm.style.display = btn.textContent.trim() === 'Ce mois' ? '' : 'none';
        });
    });
})();


(function() {
    const ctx = document.getElementById('chart7j')?.getContext('2d');
    if (!ctx) return;
    const maxV = Math.max(...APP.gains7j, 1);
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: APP.labels7j,
            datasets: [{
                data: APP.gains7j,
                backgroundColor: APP.gains7j.map(v => v === maxV && v > 0 ? '#15803d' : 'rgba(21,128,61,.18)'),
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
                    bodyColor: '#f8fafc',
                    cornerRadius: 10,
                    padding: 10,
                    callbacks: { label: c => ' ' + c.parsed.y.toLocaleString('fr-SN') + ' FCFA' }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 11, weight: '600' } } },
                y: {
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: { color: '#94a3b8', font: { size: 10 }, callback: v => v >= 1000 ? (v/1000).toFixed(0)+'k' : v }
                }
            }
        }
    });
})();


(function() {
    const ctx = document.getElementById('chart12m')?.getContext('2d');
    if (!ctx || !APP.gains12m.length) return;
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: APP.labels12m,
            datasets: [{
                data: APP.gains12m,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37,99,235,.08)',
                borderWidth: 2,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#2563eb',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    bodyColor: '#f8fafc',
                    cornerRadius: 10,
                    padding: 10,
                    callbacks: { label: c => ' ' + c.parsed.y.toLocaleString('fr-SN') + ' FCFA' }
                }
            },
            scales: {
                x: { grid: { display: false }, ticks: { color: '#94a3b8', font: { size: 10 } } },
                y: {
                    grid: { color: 'rgba(0,0,0,.05)' },
                    ticks: { color: '#94a3b8', font: { size: 10 }, callback: v => (v/1000).toFixed(0)+'k' }
                }
            }
        }
    });
})();


(function() {
    const ctx = document.getElementById('chartPm')?.getContext('2d');
    if (!ctx || !APP.pmData.length) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: APP.pmLabels,
            datasets: [{
                data: APP.pmData,
                backgroundColor: APP.pmColors,
                borderWidth: 2,
                borderColor: '#fff',
                hoverOffset: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0f172a',
                    bodyColor: '#f8fafc',
                    cornerRadius: 10,
                    padding: 10,
                }
            }
        }
    });
})();


document.addEventListener('DOMContentLoaded', () => {
    const fill = document.querySelector('.obj-fill');
    if (fill) {
        const target = fill.style.width;
        fill.style.width = '0%';
        setTimeout(() => { fill.style.width = target; }, 300);
    }
});


function toast(msg, type = 'success') {
    const icons = { success:'bi-check-circle-fill', warn:'bi-exclamation-triangle-fill', info:'bi-info-circle-fill', error:'bi-x-circle-fill' };
    const t = document.createElement('div');
    t.className = `toast t-${type}`;
    t.innerHTML = `<i class="bi ${icons[type]||icons.success}"></i><span>${msg}</span>`;
    document.getElementById('toast-container').appendChild(t);
    setTimeout(() => { t.classList.add('out'); setTimeout(()=>t.remove(),350); }, 3800);
}
</script>
</body>
</html>