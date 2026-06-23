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

$chauffeurNom  = htmlspecialchars(trim($chauffeurData['nom'] ?? 'Chauffeur'));
$chauffeurPhoto= htmlspecialchars($chauffeurData['photo'] ?? '');
$parts         = explode(' ', $chauffeurNom);
$initiales     = strtoupper(substr($parts[0],0,1).(isset($parts[1])?substr($parts[1],0,1):''));

$filterStatut  = $_GET['statut']  ?? '';
$filterType    = $_GET['type']    ?? '';
$filterPeriode = $_GET['periode'] ?? '';
$filterSearch  = trim($_GET['q']  ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 12;
$offset        = ($page - 1) * $perPage;

$where  = ['r.chauffeur_id = ?'];
$params = [$chauffeurId];

if ($filterStatut) {
    $where[]  = "(r.statut LIKE ? OR r.statut_course LIKE ?)";
    $params[] = "%$filterStatut%";
    $params[] = "%$filterStatut%";
}
if ($filterType) {
    $where[]  = "r.type_transport = ?";
    $params[] = $filterType;
}
if ($filterPeriode) {
    match($filterPeriode) {
        'today'  => ($where[] = "DATE(r.date_reservation) = CURDATE()"),
        'week'   => ($where[] = "r.date_reservation >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"),
        'month'  => ($where[] = "DATE_FORMAT(r.date_reservation,'%Y-%m') = DATE_FORMAT(CURDATE(),'%Y-%m')"),
        default  => null
    };
}
if ($filterSearch) {
    $where[]  = "(r.depart LIKE ? OR r.destination LIKE ? OR r.user_name LIKE ? OR r.reference LIKE ?)";
    $s = "%$filterSearch%";
    $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
}

$whereSQL = implode(' AND ', $where);

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM reservations r WHERE $whereSQL");
$stmtCount->execute($params);
$totalRows  = (int) $stmtCount->fetchColumn();
$totalPages = (int) ceil($totalRows / $perPage);

/* Courses paginées */
$stmtCourses = $pdo->prepare(
    "SELECT r.id, r.depart, r.destination, r.montant, r.type_transport, r.statut,
            r.statut_course, r.user_name, r.date_reservation, r.heure_depart,
            r.mode_paiement, r.note_client, r.service, r.reference, r.eta,
            u.telephone AS client_tel, u.photo AS client_photo
     FROM reservations r
     LEFT JOIN users u ON u.nom = r.user_name
     WHERE $whereSQL
     ORDER BY r.id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmtCourses->execute($params);
$courses = $stmtCourses->fetchAll(PDO::FETCH_ASSOC);

/* Stats rapides */
$stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(montant),0), COALESCE(ROUND(AVG(note_client),1),0) FROM reservations WHERE chauffeur_id = ?");
$stmt->execute([$chauffeurId]);
[$totalAll, $gainsAll, $noteAll] = $stmt->fetch(PDO::FETCH_NUM);

$stmt = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE chauffeur_id = ? AND DATE(date_reservation)=CURDATE()");
$stmt->execute([$chauffeurId]);
$todayCount = (int)$stmt->fetchColumn();

/* ── HELPERS ──────────────────────────────────────────────────── */
function fmt(float $n): string { return number_format($n,0,',',' '); }
function statutClass(string $s): string {
    $s = strtolower($s);
    if (str_contains($s,'annul'))   return 'hs-annule';
    if (str_contains($s,'cours'))   return 'hs-en_cours';
    if (str_contains($s,'attente')) return 'hs-attente';
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
function paiementIcon(string $m): string {
    return match(strtolower(trim($m))) {
        'orange money','orange_money','om' => '🟠',
        'wave'                             => '🔵',
        'free money','free'                => '🟣',
        'espèces','especes','cash'         => '💵',
        default                            => '💳',
    };
}
function typeIcon(string $t): string {
    return match(strtolower($t)) { 'bus'=>'🚌','cargo'=>'🚛','moto'=>'🏍️',default=>'🚕' };
}
function typeIconCls(string $t): string {
    return match(strtolower($t)) { 'bus'=>'hi-bus','cargo'=>'hi-cargo','moto'=>'hi-moto',default=>'hi-taxi' };
}
function typeIco(string $t): string {
    return match(strtolower($t)) { 'bus'=>'bi-bus-front-fill','cargo'=>'bi-truck','moto'=>'bi-bicycle',default=>'bi-car-front-fill' };
}
function initiales(string $nom): string {
    $p = explode(' ', trim($nom));
    return strtoupper(substr($p[0],0,1).(isset($p[1])?substr($p[1],0,1):''));
}
function elapsed(string $date): string {
    $diff = time() - strtotime($date);
    if ($diff < 60)    return 'À l\'instant';
    if ($diff < 3600)  return 'Il y a '.floor($diff/60).' min';
    if ($diff < 86400) return 'Il y a '.floor($diff/3600).'h';
    return date('d/m/Y', strtotime($date));
}

function buildUrl(array $override = []): string {
    $params = array_merge($_GET, $override);
    return '?' . http_build_query(array_filter($params, fn($v) => $v !== ''));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mes Courses — Yoon bu Gaw</title>

<link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="styles.css">


</head>
<body>
<div class="layout">

<?php
include_once "sidebar.php";
?>

<!-- MAIN -->
<div class="main">
    <header class="topbar">
        <div>
            <div class="tb-title">Mes Courses</div>
            <div class="tb-sub"><?= date('l d F Y') ?></div>
        </div>
        <div class="tb-right">
            <div class="icon-btn" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="bi bi-list"></i></div>
            <a class="icon-btn" href="#"><i class="bi bi-bell"></i></a>
            <div class="tb-avatar">
                <?php if ($chauffeurPhoto): ?>
                    <img src="<?= $chauffeurPhoto ?>" alt="">
                <?php else: ?>
                    <?= $initiales ?>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="content">

        <!-- Breadcrumb -->
        <div class="breadcrumb-bar">
            <a href="dashboard.php"><i class="bi bi-house-fill"></i> Tableau de bord</a>
            <i class="bi bi-chevron-right"></i>
            <span>Mes courses</span>
        </div>

        <!-- Page header -->
        <div class="page-header">
            <div>
                
                <p>Historique complet · <?= $totalAll ?> course(s) enregistrée(s) au total</p>
            </div>
            <div style="display:flex;gap:8px">
                <a href="<?= buildUrl(['page'=>1]) ?>" class="btn-reset" style="text-decoration:none">
                    <i class="bi bi-arrow-clockwise"></i> Actualiser
                </a>
                <button class="btn-filter" onclick="exportCSV()"><i class="bi bi-download"></i> Exporter</button>
            </div>
        </div>

        <!-- Mini stats -->
        <div class="mini-stats-row">
            <div class="ms-card">
                <div class="ms-icon si-green"><i class="bi bi-ticket-perforated-fill"></i></div>
                <div class="ms-val"><?= $todayCount ?></div>
                <div class="ms-lbl">Aujourd'hui</div>
            </div>
            <div class="ms-card">
                <div class="ms-icon si-amber"><i class="bi bi-currency-exchange"></i></div>
                <div class="ms-val"><?= fmt((float)$gainsAll) ?></div>
                <div class="ms-lbl">Gains totaux (FCFA)</div>
            </div>
            <div class="ms-card">
                <div class="ms-icon si-blue"><i class="bi bi-bar-chart-fill"></i></div>
                <div class="ms-val"><?= $totalAll > 0 ? fmt((float)$gainsAll / $totalAll) : '—' ?></div>
                <div class="ms-lbl">Moy. / course (FCFA)</div>
            </div>
            <div class="ms-card">
                <div class="ms-icon si-purple"><i class="bi bi-star-fill"></i></div>
                <div class="ms-val"><?= $noteAll > 0 ? $noteAll : '—' ?></div>
                <div class="ms-lbl">Note moyenne /5</div>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" action="mes_courses.php">
            <div class="filter-bar">
                <div class="search-wrap">
                    <i class="bi bi-search"></i>
                    <input type="text" name="q" value="<?= htmlspecialchars($filterSearch) ?>" placeholder="Départ, destination, client, référence…">
                </div>
                <select name="statut" class="filter-select">
                    <option value="">Tous statuts</option>
                    <option value="termin" <?= $filterStatut==='termin'?'selected':'' ?>>Terminé</option>
                    <option value="attente" <?= $filterStatut==='attente'?'selected':'' ?>>En attente</option>
                    <option value="cours" <?= $filterStatut==='cours'?'selected':'' ?>>En cours</option>
                    <option value="annul" <?= $filterStatut==='annul'?'selected':'' ?>>Annulé</option>
                </select>
                <select name="type" class="filter-select">
                    <option value="">Tous types</option>
                    <option value="taxi"  <?= $filterType==='taxi'?'selected':'' ?>>🚕 Taxi</option>
                    <option value="bus"   <?= $filterType==='bus'?'selected':'' ?>>🚌 Bus</option>
                    <option value="moto"  <?= $filterType==='moto'?'selected':'' ?>>🏍️ Moto</option>
                    <option value="cargo" <?= $filterType==='cargo'?'selected':'' ?>>🚛 Cargo</option>
                </select>
                <select name="periode" class="filter-select">
                    <option value="">Toutes périodes</option>
                    <option value="today" <?= $filterPeriode==='today'?'selected':'' ?>>Aujourd'hui</option>
                    <option value="week"  <?= $filterPeriode==='week'?'selected':'' ?>>7 derniers jours</option>
                    <option value="month" <?= $filterPeriode==='month'?'selected':'' ?>>Ce mois</option>
                </select>
                <button type="submit" class="btn-filter"><i class="bi bi-funnel-fill"></i> Filtrer</button>
                <?php if ($filterStatut || $filterType || $filterPeriode || $filterSearch): ?>
                <a href="mes_courses.php" class="btn-reset"><i class="bi bi-x-circle"></i> Réinitialiser</a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Result bar -->
        <div class="result-bar">
            <div class="result-count">
                <strong><?= $totalRows ?></strong> course(s) trouvée(s)
                <?php if ($filterSearch || $filterStatut || $filterType || $filterPeriode): ?>
                <span style="color:var(--green2)">· filtres actifs</span>
                <?php endif; ?>
                — page <?= $page ?> / <?= max(1,$totalPages) ?>
            </div>
            <div class="view-toggle">
                <button class="vt-btn active" id="btn-grid" onclick="setView('grid')" title="Grille"><i class="bi bi-grid-3x3-gap-fill"></i></button>
                <button class="vt-btn" id="btn-list" onclick="setView('list')" title="Liste"><i class="bi bi-list-ul"></i></button>
            </div>
        </div>

        <!-- Courses -->
        <?php if (empty($courses)): ?>
        <div class="empty-state">
            <span class="es-icon">🗺️</span>
            <h3>Aucune course trouvée</h3>
            <p>Modifiez les filtres ou lancez-vous sur la route !</p>
        </div>
        <?php else: ?>

        <!-- Vue grille (défaut) -->
        <div class="courses-grid" id="view-grid">
            <?php foreach ($courses as $i => $c):
                $sRaw = !empty($c['statut_course']) ? $c['statut_course'] : ($c['statut'] ?? '');
                $sCls = statutClass($sRaw);
                $sLbl = statutLabel($sRaw);
                $cin  = initiales($c['user_name'] ?? '—');
                $pm   = paiementIcon($c['mode_paiement'] ?? '');
                $dStr = !empty($c['date_reservation']) ? date('d/m/Y · H:i', strtotime($c['date_reservation'])) : '—';
                $style = "animation-delay:".($i * 0.04)."s";
            ?>
            <div class="course-card" style="<?= $style ?>">
                <div class="cc-head">
                    <div class="cc-id-wrap">
                        <span class="cc-id">#<?= $c['id'] ?> <?= typeIcon($c['type_transport'] ?? '') ?></span>
                        <?php if (!empty($c['reference'])): ?>
                        <span class="cc-ref"><?= htmlspecialchars($c['reference']) ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="hist-statut <?= $sCls ?>"><?= $sLbl ?></span>
                </div>

                <div class="cc-route">
                    <div class="route-line">
                        <span class="route-dot rd-g"></span>
                        <span class="route-name"><?= htmlspecialchars($c['depart']) ?></span>
                    </div>
                    <div class="route-divider"><div class="route-divider-line"></div></div>
                    <div class="route-line">
                        <span class="route-dot rd-r"></span>
                        <span class="route-name"><?= htmlspecialchars($c['destination']) ?></span>
                    </div>
                </div>

                <div class="cc-meta">
                    <span class="cc-tag"><i class="bi bi-calendar3"></i> <?= $dStr ?></span>
                    <?php if (!empty($c['mode_paiement'])): ?>
                    <span class="cc-tag"><?= $pm ?> <?= htmlspecialchars($c['mode_paiement']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($c['service'])): ?>
                    <span class="cc-tag"><i class="bi bi-tag"></i> <?= htmlspecialchars($c['service']) ?></span>
                    <?php endif; ?>
                    <?php if (!empty($c['note_client'])): ?>
                    <span class="cc-tag">
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                        <i class="bi bi-star<?= $s <= (int)$c['note_client'] ? '-fill' : '' ?> note-star"></i>
                        <?php endfor; ?>
                    </span>
                    <?php endif; ?>
                </div>

                <div class="cc-foot">
                    <div class="client-chip">
                        <div class="client-ava">
                            <?php if (!empty($c['client_photo'])): ?>
                                <img src="<?= htmlspecialchars($c['client_photo']) ?>" alt="">
                            <?php else: ?>
                                <?= $cin ?>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div><?= htmlspecialchars($c['user_name'] ?? '—') ?></div>
                            <?php if (!empty($c['client_tel'])): ?>
                            <div class="client-sub"><i class="bi bi-telephone"></i> <?= htmlspecialchars($c['client_tel']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="montant"><?= fmt((float)$c['montant']) ?> F</div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Vue liste (cachée par défaut) -->
        <div class="courses-list" id="view-list" style="display:none">
            <?php foreach ($courses as $i => $c):
                $sRaw = !empty($c['statut_course']) ? $c['statut_course'] : ($c['statut'] ?? '');
                $sCls = statutClass($sRaw);
                $sLbl = statutLabel($sRaw);
                $pm   = paiementIcon($c['mode_paiement'] ?? '');
                $dStr = !empty($c['date_reservation']) ? date('d/m/Y H:i', strtotime($c['date_reservation'])) : '—';
            ?>
            <div class="list-row" style="animation-delay:<?= $i*0.03 ?>s">
                <div class="list-icon <?= typeIconCls($c['type_transport'] ?? '') ?>">
                    <i class="bi <?= typeIco($c['type_transport'] ?? '') ?>"></i>
                </div>
                <div class="list-info">
                    <div class="list-route"><?= htmlspecialchars($c['depart']) ?> → <?= htmlspecialchars($c['destination']) ?></div>
                    <div class="list-meta">
                        <?php $i = 1; ?>

<?php foreach ($courses as $c): ?>
    <span><?= $i++ ?></span>
<?php endforeach; ?>
                        <span>&bull;</span>
                        <span><?= htmlspecialchars($c['user_name'] ?? '—') ?></span>
                        <span>&bull;</span>
                        <span><?= $dStr ?></span>
                        <?php if (!empty($c['mode_paiement'])): ?>
                        <span><?= $pm ?> <?= htmlspecialchars($c['mode_paiement']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($c['note_client'])): ?>
                        <span>
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                            <i class="bi bi-star<?= $s <= (int)$c['note_client'] ? '-fill' : '' ?> note-star"></i>
                            <?php endfor; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div style="flex-shrink:0">
                    <span class="hist-statut <?= $sCls ?>"><?= $sLbl ?></span>
                </div>
                <div class="list-right">
                    <div class="list-amt"><?= fmt((float)$c['montant']) ?> F</div>
                    <a href="suivi_course.php?id=<?= (int)$c['id'] ?>" class="btn btn-success btn-sm">
    <i class="bi bi-map"></i> Suivi live
</a>
                    
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-bar">
            <a href="<?= buildUrl(['page' => max(1,$page-1)]) ?>" class="pag-btn <?= $page<=1?'disabled':'' ?>">
                <i class="bi bi-chevron-left"></i>
            </a>
            <?php
            $start = max(1, $page - 2);
            $end   = min($totalPages, $page + 2);
            if ($start > 1): ?><a href="<?= buildUrl(['page'=>1]) ?>" class="pag-btn">1</a><?php if($start>2): ?><span style="padding:0 4px;color:var(--text3);font-size:13px">…</span><?php endif; endif;
            for ($p = $start; $p <= $end; $p++): ?>
            <a href="<?= buildUrl(['page' => $p]) ?>" class="pag-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
            <?php endfor;
            if ($end < $totalPages): if($end<$totalPages-1): ?><span style="padding:0 4px;color:var(--text3);font-size:13px">…</span><?php endif;
            ?><a href="<?= buildUrl(['page'=>$totalPages]) ?>" class="pag-btn"><?= $totalPages ?></a><?php endif; ?>
            <a href="<?= buildUrl(['page' => min($totalPages,$page+1)]) ?>" class="pag-btn <?= $page>=$totalPages?'disabled':'' ?>">
                <i class="bi bi-chevron-right"></i>
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </main>
</div>

<!-- MOBILE NAV -->
<nav class="mobile-nav">
    <div class="mn-inner">
        <a class="mnav-item" href="dashboard.php"><i class="bi bi-house-fill"></i>Accueil</a>
        <a class="mnav-item active" href="mes_courses.php"><i class="bi bi-map-fill"></i>Courses</a>
       
        <a class="mnav-item" href="revenus.php"><i class="bi bi-graph-up-arrow"></i>Revenus</a>
        <a class="mnav-item" href="profil.php"><i class="bi bi-person-fill"></i>Profil</a>
    </div>
</nav>

</div>

<script>
let currentView = localStorage.getItem('courses_view') || 'grid';
setView(currentView, false);

function setView(v, save = true) {
    currentView = v;
    if (save) localStorage.setItem('courses_view', v);
    document.getElementById('view-grid').style.display = v === 'grid' ? 'grid' : 'none';
    document.getElementById('view-list').style.display = v === 'list' ? 'flex'  : 'none';
    document.getElementById('btn-grid').classList.toggle('active', v === 'grid');
    document.getElementById('btn-list').classList.toggle('active', v === 'list');
}

function exportCSV() {
    const rows = [['ID','Départ','Destination','Client','Montant','Statut','Date']];
    document.querySelectorAll('.course-card, .list-row').forEach(card => {
        const id  = card.querySelector('.cc-id, .list-meta span')?.textContent?.replace(/\D/g,'') || '';
        rows.push([id, '…', '…', '…', '…', '…', '…']);
    });
    const csv  = rows.map(r => r.join(';')).join('\n');
    const link = document.createElement('a');
    link.href  = 'URL:data:text/csv;charset=utf-8,' + encodeURIComponent(csv);
    link.setAttribute('download','mes_courses_<?= date('Y-m-d') ?>.csv');
    link.click();
    // En production : pointer vers un endpoint PHP pour export CSV réel
    alert('Export CSV en cours de préparation…');
}
</script>
</body>
</html>