<?php
require_once "../config/database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nom      = trim($_POST['nom']);
    $email    = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $adresse  = trim($_POST['adresse']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    if (
        empty($nom) ||
        empty($email) ||
        empty($telephone) ||
        empty($adresse) ||
        empty($password)
    ) {
        $message = "Tous les champs sont obligatoires.";
    }
    elseif ($password !== $confirm) {
        $message = "Les mots de passe ne correspondent pas.";
    }
    else {

        // vérifier email
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $message = "Cet email existe déjà.";
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $insert = $pdo->prepare("
                INSERT INTO users (nom, email, telephone, adresse, password, role)
                VALUES (?, ?, ?, ?, ?, 'client')
            ");

            $insert->execute([
                $nom,
                $email,
                $telephone,
                $adresse,
                $hash
            ]);

            $message = "Compte client créé avec succès.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Inscription - Yoon bu Gaw</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">


<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Outfit:wght@300;400;500&display=swap" rel="stylesheet">

<link rel="stylesheet" href="inscription.css">

</head>

<body>

<div class="shell">
  <div class="orb1"></div><div class="orb2"></div>

  <div class="card">

    <div class="panel-l">
      <div>
        <div class="brand-mark">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M3 12c3-6 6-9 9-9s6 3 9 9"/><path d="M3 12c3 6 6 9 9 9s6-3 9-9"/></svg>
        </div>
        <div class="brand-name">Yoon Bu Gaw</div>
        <div class="brand-tagline">La route qui nous rapproche</div>
      </div>

      <div class="steps">
        <div class="step-item">
          <div class="step-num active">1</div>
          <div class="step-text"><strong>Créer votre compte</strong>Remplissez le formulaire</div>
        </div>
        <div class="divider-line"></div>
        <div class="step-item">
          <div class="step-num">2</div>
          <div class="step-text"><strong>Vérification</strong>Votre email confirmé</div>
        </div>
        <div class="divider-line"></div>
        <div class="step-item">
          <div class="step-num">3</div>
          <div class="step-text"><strong>Commencer</strong>Réservez votre trajet</div>
        </div>

        <div style="margin-top:28px">
          <div class="trust-row">
            <span class="trust-dot"></span>
            <span class="trust-text">Inscription gratuite · Sécurisée</span>
          </div>
        </div>
      </div>
    </div>

    <div class="panel-r">

      <span class="eyebrow"><span class="eyebrow-dot"></span>Nouveau compte</span>

      <div class="form-title">Rejoignez-nous</div>
      <div class="form-sub">Créez votre espace en quelques secondes</div>
<?php if(!empty($message)): ?>

                <div class="alert alert-info">
                    <?= htmlspecialchars($message) ?>
                </div>

            <?php endif; ?>

            <form method="POST">
      <div class="f-grid-2">
        <div class="f-group">
          <label class="f-label">Nom complet</label>
          <div class="f-wrap">
            <i class="ti ti-user f-ico" aria-hidden="true"></i>
            <input class="f-field" type="text" name="nom" placeholder="Votre nom" required>
          </div>
        </div>
        <div class="f-group">
          <label class="f-label">Téléphone</label>
          <div class="f-wrap">
            <i class="ti ti-phone f-ico" aria-hidden="true"></i>
            <input class="f-field" type="text" name="telephone" placeholder="+221 7X XXX XX XX" required>
          </div>
        </div>
      </div>

      <div class="f-group">
        <label class="f-label">Adresse e-mail</label>
        <div class="f-wrap">
          <i class="ti ti-mail f-ico" aria-hidden="true"></i>
          <input class="f-field" type="email" name="email" placeholder="vous@exemple.com" required>
        </div>
      </div>

      <div class="f-group">
        <label class="f-label">Adresse</label>
        <div class="f-wrap">
          <i class="ti ti-map-pin f-ico" aria-hidden="true"></i>
          <input class="f-field" type="text" name="adresse" placeholder="Votre quartier, ville" required>
        </div>
      </div>

      <div class="f-grid-2">
        <div class="f-group">
          <label class="f-label">Mot de passe</label>
          <div class="f-wrap">
            <i class="ti ti-lock f-ico" aria-hidden="true"></i>
            <input class="f-field" type="password" name="password" placeholder="••••••••" required>
          </div>
        </div>
        <div class="f-group">
          <label class="f-label">Confirmer</label>
          <div class="f-wrap">
            <i class="ti ti-lock-check f-ico" aria-hidden="true"></i>
            <input class="f-field" type="password" name="confirm_password" placeholder="••••••••" required>
          </div>
        </div>
      </div>

      <button type="submit" class="btn-main">
        Créer mon compte
        <svg viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
      </button>
</form>
      <p class="terms">En continuant, vous acceptez nos <a href="#">conditions d'utilisation</a> et notre <a href="#">politique de confidentialité</a>.</p>

      <div class="sep"><span>ou</span></div>

      <div class="login-line">Déjà membre ? <a href="login.php">Se connecter</a></div>

    </div>
  </div>
</div>


</body>
</html>