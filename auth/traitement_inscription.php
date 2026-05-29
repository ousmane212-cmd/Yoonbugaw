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

<style>
*{box-sizing:border-box;margin:0;padding:0}

.shell{
  min-height:800px;
  display:flex;align-items:center;justify-content:center;
  background:#071510;
  padding:32px 20px;
  position:relative;overflow:hidden;
}

.orb1{position:absolute;width:420px;height:420px;border-radius:50%;background:#0d4225;opacity:.3;top:-140px;right:-100px;filter:blur(90px);pointer-events:none;}
.orb2{position:absolute;width:300px;height:300px;border-radius:50%;background:#1a6640;opacity:.18;bottom:-80px;left:40px;filter:blur(70px);pointer-events:none;}

.card{
  width:100%;max-width:920px;
  display:grid;grid-template-columns:1fr 1.5fr;
  border-radius:24px;overflow:hidden;
  position:relative;z-index:2;
  border:1px solid rgba(255,255,255,0.07);
}

.panel-l{
  background:#0b2918;
  padding:52px 38px;
  display:flex;flex-direction:column;justify-content:space-between;
  position:relative;overflow:hidden;
}

.panel-l::after{
  content:'';position:absolute;inset:0;
  background:url("data:image/svg+xml,%3Csvg width='400' height='400' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='320' cy='340' r='280' fill='none' stroke='rgba(255,255,255,0.03)' stroke-width='60'/%3E%3Ccircle cx='320' cy='340' r='180' fill='none' stroke='rgba(255,255,255,0.03)' stroke-width='35'/%3E%3C/svg%3E") right bottom/380px 380px no-repeat;
  pointer-events:none;
}

.brand-mark{width:48px;height:48px;border:1.5px solid rgba(255,255,255,0.15);border-radius:14px;display:flex;align-items:center;justify-content:center;margin-bottom:20px;}
.brand-mark svg{width:22px;height:22px;stroke:rgba(255,255,255,0.7);fill:none;stroke-width:1.8;}
.brand-name{font-family:'Cormorant Garamond',serif;font-size:26px;font-weight:600;color:#fff;letter-spacing:.5px;line-height:1.1;margin-bottom:8px;}
.brand-tagline{font-family:'Outfit',sans-serif;font-size:11px;font-weight:300;color:rgba(255,255,255,0.35);letter-spacing:2px;text-transform:uppercase;}

.steps{position:relative;z-index:1;margin-top:auto;}

.step-item{display:flex;align-items:flex-start;gap:14px;margin-bottom:22px;}
.step-num{
  width:28px;height:28px;min-width:28px;
  border-radius:50%;
  background:rgba(255,255,255,0.08);
  border:1px solid rgba(255,255,255,0.15);
  display:flex;align-items:center;justify-content:center;
  font-family:'Outfit',sans-serif;font-size:11px;font-weight:500;color:rgba(255,255,255,0.5);
}
.step-num.active{background:#198754;border-color:#198754;color:#fff;}
.step-text{font-family:'Outfit',sans-serif;font-size:12px;font-weight:300;color:rgba(255,255,255,0.35);line-height:1.5;padding-top:5px;}
.step-text strong{display:block;font-weight:500;color:rgba(255,255,255,0.65);margin-bottom:1px;}

.divider-line{width:1px;height:18px;background:rgba(255,255,255,0.08);margin:0 13px;position:relative;left:1px;}

.trust-row{display:flex;align-items:center;gap:8px;}
.trust-dot{width:6px;height:6px;border-radius:50%;background:#22c55e;}
.trust-text{font-family:'Outfit',sans-serif;font-size:11px;color:rgba(255,255,255,0.35);letter-spacing:.5px;}

.panel-r{
  background:#f8f7f4;
  padding:48px 48px;
  display:flex;flex-direction:column;justify-content:center;
}

.eyebrow{display:inline-flex;align-items:center;gap:7px;background:#e8f5ee;border:1px solid #b6dfc5;border-radius:99px;padding:5px 14px;font-family:'Outfit',sans-serif;font-size:11px;font-weight:500;color:#166534;letter-spacing:.5px;margin-bottom:20px;width:fit-content;}
.eyebrow-dot{width:5px;height:5px;border-radius:50%;background:#16a34a;}

.form-title{font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:600;color:#0d1f14;line-height:1.1;margin-bottom:4px;}
.form-sub{font-family:'Outfit',sans-serif;font-size:13px;font-weight:300;color:#8a9490;margin-bottom:28px;letter-spacing:.2px;}

.f-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.f-group{margin-bottom:14px;}

.f-label{font-family:'Outfit',sans-serif;font-size:10px;font-weight:500;letter-spacing:1.8px;text-transform:uppercase;color:#6b7a72;display:block;margin-bottom:7px;}

.f-wrap{position:relative;}
.f-ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);font-size:15px;color:#aab6ae;pointer-events:none;}

.f-field{
  width:100%;height:48px;
  background:#fff;border:1.5px solid #e2e8e4;border-radius:11px;
  padding:0 14px 0 40px;
  font-family:'Outfit',sans-serif;font-size:13px;font-weight:400;color:#0d1f14;
  outline:none;transition:border-color .2s;
}
.f-field::placeholder{color:#c5cdc8;font-weight:300;}
.f-field:focus{border-color:#198754;background:#fff;}

.f-field-full{padding-left:14px;}

.btn-main{
  width:100%;height:52px;
  background:#0b2918;border:none;border-radius:13px;
  font-family:'Outfit',sans-serif;font-size:12px;font-weight:500;
  letter-spacing:2px;text-transform:uppercase;color:#fff;
  cursor:pointer;display:flex;align-items:center;justify-content:center;gap:12px;
  transition:background .25s,transform .15s;
  margin-top:8px;
}
.btn-main svg{width:15px;height:15px;stroke:#fff;fill:none;stroke-width:2.2;}
.btn-main:hover{background:#198754;transform:translateY(-1px);}

.terms{font-family:'Outfit',sans-serif;font-size:11px;font-weight:300;color:#b0bab5;text-align:center;margin-top:12px;line-height:1.6;}
.terms a{color:#198754;}

.sep{display:flex;align-items:center;gap:14px;margin:18px 0;}
.sep::before,.sep::after{content:'';flex:1;height:1px;background:#ebebeb;}
.sep span{font-family:'Outfit',sans-serif;font-size:11px;color:#c5cdc8;letter-spacing:.5px;}

.login-line{text-align:center;font-family:'Outfit',sans-serif;font-size:13px;font-weight:300;color:#8a9490;}
.login-line a{color:#198754;font-weight:500;}

@media(max-width:600px){
  .card{grid-template-columns:1fr;}
  .panel-l{padding:32px 28px;min-height:auto;}
  .panel-r{padding:36px 24px;}
  .f-grid-2{grid-template-columns:1fr;}
}
</style>

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