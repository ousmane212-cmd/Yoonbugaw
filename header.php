<?php


require_once "../config/database.php";

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$adminName = $_SESSION['nom'] ?? "Admin";

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