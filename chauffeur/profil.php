 
 <?php
 ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once '../config/database.php';

/* ── AUTH ───────────────────────────── */
if (empty($_SESSION['id']) || $_SESSION['role'] !== 'chauffeur') {
    header('Location: ../auth/login.php');
    exit;
}

$chauffeurId = (int) $_SESSION['id'];

/* ── CHAUFFEUR ─────────────────────── */
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'chauffeur'");
$stmt->execute([$chauffeurId]);
$chauffeur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$chauffeur) {
    header('Location: ../auth/login.php');
    exit;
}

/* ── STATS COURSES ─────────────────── */
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN statut LIKE '%termin%' THEN 1 ELSE 0 END) AS terminees,
        SUM(CASE WHEN statut LIKE '%annul%' THEN 1 ELSE 0 END) AS annulees,
        SUM(CASE WHEN statut LIKE '%cours%' OR statut = 'en_route' THEN 1 ELSE 0 END) AS en_cours
    FROM reservations
    WHERE chauffeur_id = ?
");
$stmt->execute([$chauffeurId]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

/* ── REVENUS ───────────────────────── */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(montant),0) AS revenus
    FROM reservations
    WHERE chauffeur_id = ? AND statut LIKE '%termin%'
");
$stmt->execute([$chauffeurId]);
$revenus = $stmt->fetchColumn();

/* ── REVENUS CE MOIS ────────────────── */
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(montant),0) AS revenus_mois
    FROM reservations
    WHERE chauffeur_id = ? AND statut LIKE '%termin%'
    AND MONTH(date_reservation) = MONTH(NOW()) AND YEAR(date_reservation) = YEAR(NOW())
");
$stmt->execute([$chauffeurId]);
$revenusMois = $stmt->fetchColumn();

/* ── DERNIÈRES COURSES ──────────────── */
$stmt = $pdo->prepare("
    SELECT r.*, 
           u.nom AS client_nom
    FROM reservations r
    LEFT JOIN users u ON u.id = r.client_id
    WHERE r.chauffeur_id = ?
    ORDER BY r.date_reservation DESC
    LIMIT 5
");
$stmt->execute([$chauffeurId]);
$dernieresCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* ── ÉVALUATION ─────────────────────── */
$stmt = $pdo->prepare("
    SELECT COALESCE(AVG(note_client), 0) AS note_moy, COUNT(note_client) AS nb_notes
    FROM reservations
    WHERE chauffeur_id = ? AND note_client IS NOT NULL
");
$stmt->execute([$chauffeurId]);
$eval = $stmt->fetch(PDO::FETCH_ASSOC);

/* ── HELPERS ────────────────────────── */
$nom      = htmlspecialchars($chauffeur['nom'] ?? '');
$tel      = htmlspecialchars($chauffeur['telephone'] ?? '');
$email    = htmlspecialchars($chauffeur['email'] ?? '');
$photo    = htmlspecialchars($chauffeur['photo'] ?? '');
$vehicule = htmlspecialchars($chauffeur['vehicule'] ?? '');
$plaque   = htmlspecialchars($chauffeur['plaque'] ?? '');
$statut   = $chauffeur['statut_dispo'] ?? 'disponible';

$taux = $stats['total'] > 0
    ? round(($stats['terminees'] / $stats['total']) * 100)
    : 0;

$noteMoy  = round((float)$eval['note_moy'], 1);
$nbNotes  = (int)$eval['nb_notes'];

function initiales($nom) {
    $p = explode(' ', trim($nom));
    return strtoupper(substr($p[0], 0, 1) . (isset($p[1]) ? substr($p[1], 0, 1) : ''));
}
$ini = initiales($nom);

function statutLabel($s) {
    return match($s) {
        'disponible'   => ['Disponible',    '#007D34', '#E6F4ED'],
        'en_course'    => ['En course',     '#B45309', '#FEF3C7'],
        'indisponible' => ['Indisponible',  '#B91C1C', '#FEE2E2'],
        default        => ['Inconnu',       '#64748B', '#F1F5F9'],
    };
}
[$sLabel, $sColor, $sBg] = statutLabel($statut);

function statutCourseLabel($s) {
    if (str_contains($s, 'termin')) return ['Terminée',    '#007D34'];
    if (str_contains($s, 'annul'))  return ['Annulée',     '#DC2626'];
    if (str_contains($s, 'cours') || $s === 'en_route') return ['En cours', '#B45309'];
    return ['En attente', '#64748B'];
}

function etoiles($n) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $n ? '★' : '☆';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil — <?= $nom ?> | Yoon bu Gaw</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=DM+Sans:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="profil.css">

</head>

<body>
     <?php include_once "sidebar.php"; ?>
<div class="page-wrap">

  
    <main class="main">

        <!-- En-tête page -->
        <div class="page-header">
            <div class="page-title">Mon <span>Profil</span></div>
            <div class="breadcrumb-pill">
                <i class="bi bi-house-fill"></i>
                <span>Tableau de bord</span>
                <i class="bi bi-chevron-right"></i>
                <span>Profil</span>
            </div>
        </div>

        <!-- Grille principale -->
        <div class="profil-grid">

            <!-- ─── COLONNE GAUCHE ─── -->
            <div>

                <!-- Carte identité -->
                <div class="card" style="margin-bottom:18px">
                    <div class="card-header-strip">
                        <span class="label">Chauffeur</span>
                        <span class="status-badge" style="background:<?= $sBg ?>;color:<?= $sColor ?>">
                            <span class="dot"></span>
                            <?= $sLabel ?>
                        </span>
                    </div>

                    <div class="avatar-wrap">
                        <div class="avatar">
                            <?php if ($photo): ?>
                                <img src="<?= $photo ?>" alt="Photo <?= $nom ?>">
                            <?php else: ?>
                                <?= $ini ?>
                            <?php endif; ?>
                        </div>
                        <div class="avatar-name"><?= $nom ?></div>
                        <div class="avatar-meta"><i class="bi bi-envelope" style="color:var(--green);margin-right:4px"></i><?= $email ?: '—' ?></div>
                        <div class="avatar-meta"><i class="bi bi-telephone" style="color:var(--green);margin-right:4px"></i><?= $tel ?: '—' ?></div>
                    </div>

                    <!-- Stat mini -->
                    <div class="stat-mini-grid">
                        <div class="stat-mini">
                            <span class="val"><?= (int)$stats['total'] ?></span>
                            <span class="lbl">Courses</span>
                        </div>
                        <div class="stat-mini green">
                            <span class="val"><?= (int)$stats['terminees'] ?></span>
                            <span class="lbl">Terminées</span>
                        </div>
                        <div class="stat-mini">
                            <span class="val"><?= (int)$stats['annulees'] ?></span>
                            <span class="lbl">Annulées</span>
                        </div>
                        <div class="stat-mini green">
                            <span class="val"><?= number_format($revenus, 0, ',', ' ') ?></span>
                            <span class="lbl">FCFA Totaux</span>
                        </div>
                    </div>

                    <div style="padding:0 22px 20px;display:flex;flex-direction:column;gap:0">
                        <button class="btn-primary-ybg" onclick="document.getElementById('modalEdit').classList.add('open')">
                            <i class="bi bi-pencil-square"></i> Modifier mon profil
                        </button>
                        <a href="../auth/logout.php" class="btn-ghost-ybg">
                            <i class="bi bi-box-arrow-right"></i> Déconnexion
                        </a>
                    </div>
                </div>

                <!-- Carte note moyenne -->
                <div class="card">
                    <div class="card-body-pad">
                        <div class="section-title"><i class="bi bi-star-fill"></i> Évaluation</div>
                        <div class="note-row">
                            <div class="note-big"><?= $noteMoy > 0 ? $noteMoy : '—' ?></div>
                            <div>
                                <div class="note-stars"><?= etoiles(round($noteMoy)) ?></div>
                                <div class="note-sub"><?= $nbNotes ?> avis client<?= $nbNotes > 1 ? 's' : '' ?></div>
                            </div>
                        </div>
                        <div style="margin-top:14px">
                            <div class="progress-label">
                                <span>Taux de réussite</span>
                                <span><?= $taux ?>%</span>
                            </div>
                            <div class="progress-track">
                                <div class="progress-fill" style="width:<?= $taux ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- ─── COLONNE DROITE ─── -->
            <div>

                <!-- Informations -->
                <div class="card" style="margin-bottom:18px">
                    <div class="card-body-pad">

                        <div class="section-title"><i class="bi bi-person-lines-fill"></i> Informations personnelles</div>

                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-person"></i> Nom complet</span>
                            <span class="info-value"><?= $nom ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-telephone"></i> Téléphone</span>
                            <span class="info-value"><?= $tel ?: '—' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-envelope"></i> Email</span>
                            <span class="info-value"><?= $email ?: '—' ?></span>
                        </div>

                        <div class="divider" style="margin:18px 0 18px"></div>

                        <div class="section-title"><i class="bi bi-truck"></i> Véhicule</div>

                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-car-front"></i> Modèle</span>
                            <span class="info-value"><?= $vehicule ?: '—' ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-credit-card-2-front"></i> Plaque</span>
                            <span class="info-value">
                                <?php if ($plaque): ?>
                                    <span style="background:var(--navy);color:var(--white);padding:3px 10px;border-radius:6px;font-family:var(--font);font-size:12px;letter-spacing:1px"><?= $plaque ?></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </span>
                        </div>

                        <div class="divider" style="margin:18px 0 18px"></div>

                        <div class="section-title"><i class="bi bi-bar-chart-line"></i> Performance ce mois</div>

                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-cash-coin"></i> Revenus du mois</span>
                            <span class="info-value" style="color:var(--green)"><?= number_format($revenusMois, 0, ',', ' ') ?> FCFA</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-check-circle"></i> Courses terminées</span>
                            <span class="info-value"><?= (int)$stats['terminees'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-x-circle"></i> Courses annulées</span>
                            <span class="info-value"><?= (int)$stats['annulees'] ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label"><i class="bi bi-shield-check"></i> Statut compte</span>
                            <span class="info-value">
                                <span style="background:var(--green-lt);color:var(--green);padding:3px 10px;border-radius:99px;font-size:12px;font-weight:700">Actif</span>
                            </span>
                        </div>

                    </div>
                </div>

                <!-- Dernières courses -->
                <div class="card">
                    <div class="card-body-pad">
                        <div class="section-title"><i class="bi bi-clock-history"></i> Dernières courses</div>

                        <?php if (empty($dernieresCourses)): ?>
                            <div style="text-align:center;padding:28px 0;color:var(--navy-50)">
                                <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;color:var(--border)"></i>
                                Aucune course enregistrée
                            </div>
                        <?php else: ?>
                            <div class="courses-section">
                                <?php foreach ($dernieresCourses as $c):
                                    [$cLabel, $cColor] = statutCourseLabel($c['statut'] ?? '');
                                    $date = !empty($c['date_reservation'])
                                        ? date('d M Y · H:i', strtotime($c['date_reservation']))
                                        : '—';
                                    $montant = number_format($c['montant'] ?? 0, 0, ',', ' ');
                                ?>
                                <div class="course-item">
                                    <div class="course-icon"><i class="bi bi-geo-alt-fill"></i></div>
                                    <div class="course-details">
                                        <div class="course-client"><?= htmlspecialchars($c['client_nom'] ?? 'Client') ?></div>
                                        <div class="course-date"><?= $date ?></div>
                                    </div>
                                    <div class="course-right">
                                        <div class="course-montant"><?= $montant ?> F</div>
                                        <span class="course-statut" style="background:<?= $cColor ?>22;color:<?= $cColor ?>"><?= $cLabel ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div style="margin-top:16px">
                                <a href="mes_courses.php" class="btn-ghost-ybg" style="margin-top:0">
                                    <i class="bi bi-list-ul"></i> Voir tout l'historique
                                </a>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- ─── MODAL MODIFIER PROFIL ─── -->
<div class="modal-overlay" id="modalEdit">
    <div class="modal-box">
        <div class="modal-head">
            <h4><i class="bi bi-pencil-square" style="margin-right:8px"></i>Modifier mon profil</h4>
            <button class="modal-close" onclick="document.getElementById('modalEdit').classList.remove('open')">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="POST" action="update_profil.php" enctype="multipart/form-data">
            <div class="modal-body">

                <div class="form-group">
                    <label class="form-label-ybg">Photo de profil</label>
                    <input type="file" name="photo" accept="image/*" class="form-input-ybg" style="padding:8px 12px">
                </div>

                <div class="form-group">
                    <label class="form-label-ybg">Nom complet</label>
                    <input type="text" name="nom" value="<?= $nom ?>" class="form-input-ybg" placeholder="Prénom Nom" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label-ybg">Téléphone</label>
                        <input type="tel" name="telephone" value="<?= $tel ?>" class="form-input-ybg" placeholder="+221 77...">
                    </div>
                    <div class="form-group">
                        <label class="form-label-ybg">Email</label>
                        <input type="email" name="email" value="<?= $email ?>" class="form-input-ybg" placeholder="email@...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label-ybg">Véhicule</label>
                        <input type="text" name="vehicule" value="<?= $vehicule ?>" class="form-input-ybg" placeholder="Renault Logan...">
                    </div>
                    <div class="form-group">
                        <label class="form-label-ybg">Plaque</label>
                        <input type="text" name="plaque" value="<?= $plaque ?>" class="form-input-ybg" placeholder="DK 1234 AB">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label-ybg">Disponibilité</label>
                    <select name="statut_dispo" class="form-input-ybg">
                        <option value="disponible"   <?= $statut === 'disponible'   ? 'selected' : '' ?>>Disponible</option>
                        <option value="en_course"    <?= $statut === 'en_course'    ? 'selected' : '' ?>>En course</option>
                        <option value="indisponible" <?= $statut === 'indisponible' ? 'selected' : '' ?>>Indisponible</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label-ybg">Nouveau mot de passe <span style="font-weight:400;color:var(--navy-50)">(laisser vide = inchangé)</span></label>
                    <input type="password" name="password" class="form-input-ybg" placeholder="••••••••">
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn-sm-ghost" onclick="document.getElementById('modalEdit').classList.remove('open')">Annuler</button>
                <button type="submit" class="btn-sm-primary"><i class="bi bi-check-lg"></i> Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<!-- ─── TOAST ─── -->
<div class="toast-ybg" id="toast">
    <i class="bi bi-check-circle-fill"></i>
    <span id="toastMsg">Profil mis à jour avec succès</span>
</div>

<script>
// Fermer modal en cliquant à l'extérieur
document.getElementById('modalEdit').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
});

// Toast si paramètre URL
const params = new URLSearchParams(window.location.search);
if (params.get('updated') === '1') {
    showToast('Profil mis à jour avec succès');
}
if (params.get('error') === '1') {
    showToast('Erreur lors de la mise à jour', true);
}

function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    if (isError) t.style.borderLeftColor = '#DC2626';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3500);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>