   <?php


require_once "../config/database.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$adminName = $_SESSION['nom'] ?? "Admin";

$totalReservations = $pdo->query("SELECT COUNT(*) as total FROM reservations")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$totalRevenus = $pdo->query("SELECT SUM(montant) as total FROM paiements")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$totalChauffeurs = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role='chauffeur'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$totalVehicules = $pdo->query("SELECT COUNT(*) as total FROM vehicules WHERE statut='disponible'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

$months = [];
$revenus = [];
$chartQuery = $pdo->query("
    SELECT MONTH(date_paiement) as mois, SUM(montant) as total
    FROM paiements
    GROUP BY MONTH(date_paiement)
    ORDER BY MONTH(date_paiement)
");
while ($row = $chartQuery->fetch(PDO::FETCH_ASSOC)) {
    $months[] = date("M", mktime(0,0,0,$row['mois'],10));
    $revenus[] = $row['total'];
}

$taxi        = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE type_transport='Taxi'")->fetch()['total'] ?? 0;
$voyageurs   = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE type_transport='Voyageurs'")->fetch()['total'] ?? 0;
$bus         = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE type_transport='Cars & Bus'")->fetch()['total'] ?? 0;
$marchandise = $pdo->query("SELECT COUNT(*) as total FROM reservations WHERE type_transport='Marchandises'")->fetch()['total'] ?? 0;

$reservations = $pdo->query("SELECT * FROM reservations ORDER BY id DESC LIMIT 5");

$topDrivers = $pdo->query("SELECT * FROM users WHERE role='chauffeur' ORDER BY id DESC LIMIT 3");

$notifCount = $pdo->query("SELECT COUNT(*) FROM notifications WHERE lu = 0")->fetchColumn();
$totalPending = $pdo->query("
    SELECT COUNT(*) 
    FROM reservations
    WHERE statut = 'en attente'
")->fetchColumn();
?>
   <header class="topbar">
  <div class="admin-header card card-box p-2 mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <div>
        <h4 class="mb-1">Bienvenue, <?= htmlspecialchars($adminName) ?> 👋</h4>
        <p class="text-muted mb-0">Voici ce qui se passe aujourd'hui.  <i class="bi bi-geo-alt-fill text-success"></i>
          <span id="admin-location">Localisation...</span></p>
        
      </div>
      <div class="d-flex align-items-center gap-3">
       <a href="notification.php" class="notif-icon">
    <i class="bi bi-bell-fill"></i>

    <?php if ($totalPending > 0): ?>
        <span class="notif-badge">
            <?= $totalPending ?>
        </span>
    <?php endif; ?>
</a>
       <div class="dropdown">
    
    <img 
        src="<?= !empty($_SESSION['photo']) 
            ? '../uploads/' . htmlspecialchars($_SESSION['photo']) 
            : '../assets/images/admin.png' ?>" 
        alt="Admin"
        class="rounded-circle border border-2 border-light shadow-sm dropdown-toggle"
        width="45"
        height="45"
        style="object-fit: cover; cursor: pointer;"
        data-bs-toggle="dropdown"
    >

    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item text-danger" href="../auth/logout.php">
                <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
        </li>
    </ul>

</div>
      </div>
    </div>
  </div>
    </header >
   <style>
.topbar{
    width:100%;
}

.admin-header{
    background:#fff;
    border:none;
    border-radius:0;
    box-shadow:0 2px 10px rgba(0,0,0,0.05);
}

.profile-pic{
    width:45px;
    height:45px;
    object-fit:cover;
    border-radius:0;
}
   </style><script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>