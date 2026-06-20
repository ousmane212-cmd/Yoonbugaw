
<?php
require_once '../config/database.php';

/* ── 1. AUTH ──────────────────────────────────────────────────── */
if (empty($_SESSION['id']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'chauffeur') {
    header('Location: ../auth/login.php');
    exit;
}

$chauffeurId = (int) $_SESSION['id'];

/* ── 2. DONNÉES CHAUFFEUR ─────────────────────────────────────── */
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

/* ── 3. VÉHICULE ─────────────────────────────────────────────── */
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

/* ── 4. STATISTIQUES ─────────────────────────────────────────── */
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
$libelleStatut = 'Disponible';

if ($statutChauffeur === 'en_course') {
    $libelleStatut = 'En course';
} elseif ($statutChauffeur === 'hors_ligne') {
    $libelleStatut = 'Hors ligne';
}
?>

<aside class="sidebar" id="sidebar">

    <div class="sb-logo">
        <div class="logo-circle">
            <i class="bi bi-car-front-fill"></i>
        </div>

        <div>
            <div class="sb-app-name">Yoon bu Gaw</div>
            <div class="sb-tagline">Espace Chauffeur</div>
        </div>
    </div>

    <div class="sb-driver">

        <div class="sb-driver-row">

            <div class="sb-avatar">
                <?php if (!empty($chauffeurPhoto)): ?>
                    <img src="<?= htmlspecialchars($chauffeurPhoto) ?>" alt="Photo">
                <?php else: ?>
                    <?= htmlspecialchars($initiales) ?>
                <?php endif; ?>
            </div>

            <div class="sb-info">
                <div class="sb-name">
                    <?= htmlspecialchars($chauffeurNom) ?>
                </div>

                <div class="sb-vehicle">
                    <i class="bi bi-car-front-fill"></i>
                    <?= htmlspecialchars($vehiculeNom) ?>
                </div>

                <button
                    class="status-btn sb-<?= htmlspecialchars($statutChauffeur) ?>"
                    id="status-btn"
                    onclick="toggleStatut()">

                    <span class="status-dot"></span>
                    <span id="status-label">
                        <?= $libelleStatut ?>
                    </span>

                </button>
            </div>

        </div>

    </div>

    <nav class="sb-nav">

        <span class="nav-section">Principal</span>

        <a href="dashboard.php" class="nav-item active">
            <i class="bi bi-house-fill"></i>
            Tableau de bord
        </a>

        <a href="mes_courses.php" class="nav-item">
            <i class="bi bi-map-fill"></i>
            Mes Courses
        </a>

       <a class="nav-item" href="#"><i class="bi bi-bell-fill"></i> Notifications</a>

        <a href="revenus.php" class="nav-item">
            <i class="bi bi-cash-stack"></i>
            Revenus
        </a>

        <span class="nav-section">Mon compte</span>

        <a href="profil.php" class="nav-item">
            <i class="bi bi-person-circle"></i>
            Mon profil
        </a>

        <a href="vehicule.php" class="nav-item">
            <i class="bi bi-car-front"></i>
            Mon véhicule
        </a>

        

        <a href="#" class="nav-item">
            <i class="bi bi-gear-fill"></i>
            Paramètres
        </a>

    </nav>

    <div class="sb-footer">

        <a href="../auth/logout.php" class="nav-item logout-btn">
            <i class="bi bi-box-arrow-right"></i>
            Déconnexion
        </a>

    </div>

</aside>

<style>
    :root{
  --sidebar-width: 220px;
  --bg: #f0f4f9;
  --sidebar-bg: #0d1b2a;
  --accent: #16a34a;
  --card-radius: 16px;
  --shadow: 0 2px 16px rgba(0,0,0,0.07);
}
.sidebar {
    width: 260px;
   background: var(--sidebar-bg);
    border-right: 0.5px solid rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
    overflow-y: auto;
    z-index: 100;
    transition: transform 0.25s ease;
}

/* ── Logo ─────────────────────────────────────────────────────────── */
.sb-logo {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 20px 20px 16px;
    text-decoration: none;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.08);
}
.sb-logo img {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    object-fit: contain;
}
.sb-app-name {
    font-size: 14px;
    font-weight: 600;
    color: #fff;
}
.sb-tagline {
    font-size: 11px;
    color: #888780;
    margin-top: 1px;
}

/* ── Bloc chauffeur ───────────────────────────────────────────────── */
.sb-driver {
    padding: 14px 16px;
    border-bottom: 0.5px solid rgba(0, 0, 0, 0.08);
}
.sb-driver-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.sb-avatar {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #1D9E75;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    font-weight: 600;
    flex-shrink: 0;
    overflow: hidden;
}
.sb-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.sb-name {
    font-size: 14px;
    font-weight: 600;
    color: #fff;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.sb-vehicle {
    font-size: 12px;
    color: #888780;
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 4px;
}

/* ── Bouton statut ────────────────────────────────────────────────── */
.status-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-top: 8px;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 500;
    border: none;
    cursor: pointer;
    transition: opacity 0.15s;
}
.status-btn:hover { opacity: 0.8; }
.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
    opacity: 0.7;
}
.sb-disponible  { background: #EAF3DE; color: #3B6D11; }
.sb-en_course   { background: #FAEEDA; color: #854F0B; }
.sb-hors_ligne  { background: #F1EFE8; color: #5F5E5A; }

/* ── Navigation ───────────────────────────────────────────────────── */
.sb-nav {
    flex: 1;
    padding: 12px 10px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.nav-section {
    font-size: 10px;
    font-weight: 600;
    color: #b4b2a9;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    padding: 10px 8px 4px;
}
.nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 10px;
    border-radius: 8px;
    font-size: 13px;
    color: #5f5e5a;
    text-decoration: none;
    background: none;
    border: none;
    cursor: pointer;
    width: 100%;
    text-align: left;
    transition: background 0.12s, color 0.12s;
    position: relative;
}
.nav-item:hover {
    background: #f5f4f0;
    color: #fff;
}
.nav-item.active {
    background: #EAF3DE;
    color: #27500A;
    font-weight: 600;
}
.nav-item i { font-size: 16px; flex-shrink: 0; }

/* Badge cours en attente */
.nav-count {
    margin-left: auto;
    background: #D85A30;
    color: #ffffff;
    font-size: 10px;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}

/* ── Footer / Déconnexion ─────────────────────────────────────────── */
.sb-footer {
    padding: 10px 10px 16px;
    border-top: 0.5px solid rgba(0, 0, 0, 0.08);
}
.sb-footer .nav-item { color: #888780; }
.sb-footer .nav-item:hover {
    color: #A32D2D;
    background: #FCEBEB;
}

/* ── Mobile : sidebar cachée par défaut ──────────────────────────── */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
    }
    .sidebar.active {
        transform: translateX(0);
    }
}
</style>