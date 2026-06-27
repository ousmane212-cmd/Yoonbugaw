<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion - Yoon bu Gaw</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="login.css">
</head>

<body>

<div class="shell">
  <div class="orb1"></div>
  <div class="orb2"></div>

  <div class="card">

    <div class="panel-l">
      <div>
        <div class="brand-mark">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12c3-6 6-9 9-9s6 3 9 9"/><path d="M3 12c3 6 6 9 9 9s6-3 9-9"/></svg>
        </div>
        <div class="brand-name">Yoon Bu Gaw</div>
        <div class="brand-tagline">La route qui nous rapproche</div>
      </div>

      <div class="panel-l-bottom">
        <div class="stat-line">
          <span class="stat-num">12k</span>
          <span class="stat-unit">voyages</span>
        </div>
        <div class="divider-line"></div>
        <div class="trust-row">
          <span class="trust-dot"></span>
          <span class="trust-text">Plateforme active · Sénégal</span>
        </div>
      </div>
    </div>

    <div class="panel-r">

      <span class="eyebrow">
        <span class="eyebrow-dot"></span>
        Connexion sécurisée
      </span>
      <div class="form-title">Bon retour</div>
      <div class="form-sub">Accédez à votre espace personnel</div>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger text-center">
      <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
  <?php endif; ?>
<form action="login_traitement.php" method="POST">

    <label class="f-label">Email</label>
    <input class="f-field" type="email" name="email" placeholder="vous@exemple.com" required>

    <label class="f-label">Mot de passe</label>
    <input class="f-field" type="password" name="password" placeholder="••••••••" required>
 <div class="f-row">
        <label class="f-check">
          <input type="checkbox"> Se souvenir de moi
        </label>
        <span class="f-forgot">Mot de passe oublié ?</span>
      </div>
    <button class="btn-main" type="submit">
       Se connecter
        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
    </button>

  </form>

    

      <div class="sep"><span>ou</span></div>

      <div class="register-line">
        Pas encore membre ? <a href="traitement_inscription.php">Créer un compte</a>
      </div>

      <div class="security-row">
        <span class="sec-item"><i class="ti ti-shield-check"></i> SSL sécurisé</span>
        <span class="sec-item"><i class="ti ti-lock"></i> Données protégées</span>
        <span class="sec-item"><i class="ti ti-certificate"></i> Certifié</span>
      </div>

    </div>
  </div>
</div>

<script>
setTimeout(() => {
    let alert = document.querySelector('.alert');
    if(alert){
        alert.style.opacity = "0";
        setTimeout(() => alert.remove(), 500);
    }
}, 4000);
</script>

</body>
</html>