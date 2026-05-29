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

<style>
*{box-sizing:border-box;margin:0;padding:0}

.shell{
  min-height:660px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:#071510;
  padding:32px 20px;
  position:relative;
  overflow:hidden;
}

.orb1{
  position:absolute;
  width:380px;height:380px;
  border-radius:50%;
  background:#0d4225;
  opacity:.35;
  top:-120px;right:-80px;
  filter:blur(80px);
  pointer-events:none;
}

.orb2{
  position:absolute;
  width:260px;height:260px;
  border-radius:50%;
  background:#1a6640;
  opacity:.2;
  bottom:-60px;left:60px;
  filter:blur(60px);
  pointer-events:none;
}

.card{
  width:100%;max-width:860px;
  display:grid;
  grid-template-columns:1fr 1.4fr;
  border-radius:24px;
  overflow:hidden;
  position:relative;
  z-index:2;
  border:1px solid rgba(255,255,255,0.07);
}

/* ─── LEFT ─── */
.panel-l{
  background:#0b2918;
  padding:52px 40px;
  display:flex;
  flex-direction:column;
  justify-content:space-between;
  position:relative;
  overflow:hidden;
}

.panel-l::after{
  content:'';
  position:absolute;
  inset:0;
  background:url("data:image/svg+xml,%3Csvg width='400' height='400' xmlns='http://www.w3.org/2000/svg'%3E%3Ccircle cx='320' cy='320' r='280' fill='none' stroke='rgba(255,255,255,0.03)' stroke-width='60'/%3E%3Ccircle cx='320' cy='320' r='180' fill='none' stroke='rgba(255,255,255,0.03)' stroke-width='40'/%3E%3C/svg%3E") right bottom / 380px 380px no-repeat;
  pointer-events:none;
}

.brand-mark{
  width:48px;height:48px;
  border:1.5px solid rgba(255,255,255,0.15);
  border-radius:14px;
  display:flex;align-items:center;justify-content:center;
  margin-bottom:20px;
}

.brand-mark svg{
  width:22px;height:22px;
  stroke:rgba(255,255,255,0.7);
  fill:none;stroke-width:1.8;
}

.brand-name{
  font-family:'Cormorant Garamond',serif;
  font-size:26px;font-weight:600;
  color:#fff;
  letter-spacing:.5px;
  line-height:1.1;
  margin-bottom:8px;
}

.brand-tagline{
  font-family:'Outfit',sans-serif;
  font-size:12px;font-weight:300;
  color:rgba(255,255,255,0.38);
  letter-spacing:2px;
  text-transform:uppercase;
}

.panel-l-bottom{
  position:relative;z-index:1;
}

.stat-line{
  display:flex;align-items:flex-end;gap:8px;
  margin-bottom:18px;
}

.stat-num{
  font-family:'Cormorant Garamond',serif;
  font-size:42px;font-weight:400;font-style:italic;
  color:#fff;line-height:1;
}

.stat-unit{
  font-family:'Outfit',sans-serif;
  font-size:11px;color:rgba(255,255,255,0.35);
  letter-spacing:1.5px;text-transform:uppercase;
  padding-bottom:6px;
}

.divider-line{
  width:36px;height:1.5px;
  background:rgba(255,255,255,0.15);
  margin-bottom:14px;
  border-radius:2px;
}

.trust-row{
  display:flex;align-items:center;gap:8px;
}

.trust-dot{
  width:6px;height:6px;
  border-radius:50%;
  background:#22c55e;
}

.trust-text{
  font-family:'Outfit',sans-serif;
  font-size:11px;color:rgba(255,255,255,0.4);
  letter-spacing:.5px;
}

/* ─── RIGHT ─── */
.panel-r{
  background:#f8f7f4;
  padding:52px 48px;
  display:flex;
  flex-direction:column;
  justify-content:center;
}

.eyebrow{
  display:inline-flex;align-items:center;gap:7px;
  background:#e8f5ee;
  border:1px solid #b6dfc5;
  border-radius:99px;
  padding:5px 14px;
  font-family:'Outfit',sans-serif;
  font-size:11px;font-weight:500;
  color:#166534;
  letter-spacing:.5px;
  margin-bottom:22px;
  width:fit-content;
}

.eyebrow-dot{width:5px;height:5px;border-radius:50%;background:#16a34a;}

.form-title{
  font-family:'Cormorant Garamond',serif;
  font-size:34px;font-weight:600;
  color:#0d1f14;
  line-height:1.1;
  margin-bottom:4px;
}

.form-sub{
  font-family:'Outfit',sans-serif;
  font-size:13px;font-weight:300;
  color:#8a9490;
  margin-bottom:34px;
  letter-spacing:.2px;
}

.f-label{
  font-family:'Outfit',sans-serif;
  font-size:10px;font-weight:500;
  letter-spacing:1.8px;
  text-transform:uppercase;
  color:#6b7a72;
  display:block;
  margin-bottom:8px;
}

.f-wrap{
  position:relative;
  margin-bottom:18px;
}

.f-ico{
  position:absolute;
  left:15px;top:50%;
  transform:translateY(-50%);
  font-size:16px;
  color:#aab6ae;
  pointer-events:none;
}

.f-field{
  width:100%;height:52px;
  background:#fff;
  border:1.5px solid #e2e8e4;
  border-radius:12px;
  padding:0 16px 0 44px;
  font-family:'Outfit',sans-serif;
  font-size:14px;font-weight:400;
  color:#0d1f14;
  outline:none;
  transition:border-color .2s,background .2s;
}

.f-field::placeholder{color:#c5cdc8;font-weight:300;}
.f-field:focus{border-color:#198754;background:#fff;}

.f-row{
  display:flex;justify-content:space-between;align-items:center;
  margin-bottom:28px;
}

.f-check{
  display:flex;align-items:center;gap:8px;
  font-family:'Outfit',sans-serif;
  font-size:12px;font-weight:300;color:#8a9490;
  cursor:pointer;
}

.f-check input{accent-color:#198754;width:13px;height:13px;}

.f-forgot{
  font-family:'Outfit',sans-serif;
  font-size:12px;font-weight:500;
  color:#198754;cursor:pointer;
}

.btn-main{
  width:100%;height:54px;
  background:#0b2918;
  border:none;border-radius:14px;
  font-family:'Outfit',sans-serif;
  font-size:13px;font-weight:500;
  letter-spacing:2px;text-transform:uppercase;
  color:#fff;cursor:pointer;
  display:flex;align-items:center;justify-content:center;gap:12px;
  transition:background .25s,transform .15s;
  position:relative;overflow:hidden;
}

.btn-main::before{
  content:'';
  position:absolute;
  left:-100%;top:0;
  width:100%;height:100%;
  background:rgba(255,255,255,0.06);
  transition:left .3s;
}

.btn-main:hover{background:#198754;transform:translateY(-1px);}
.btn-main:hover::before{left:0;}

.btn-main svg{width:16px;height:16px;stroke:#fff;fill:none;stroke-width:2.2;}

.sep{
  display:flex;align-items:center;gap:14px;
  margin:22px 0;
}

.sep::before,.sep::after{content:'';flex:1;height:1px;background:#ebebeb;}

.sep span{
  font-family:'Outfit',sans-serif;
  font-size:11px;color:#c5cdc8;letter-spacing:.5px;
}

.register-line{
  text-align:center;
  font-family:'Outfit',sans-serif;
  font-size:13px;font-weight:300;color:#8a9490;
}

.register-line a{color:#198754;font-weight:500;}

.security-row{
  display:flex;align-items:center;justify-content:center;gap:18px;
  margin-top:22px;
}

.sec-item{
  display:flex;align-items:center;gap:5px;
  font-family:'Outfit',sans-serif;
  font-size:10px;color:#b0bab5;letter-spacing:.3px;
}

.sec-item i{font-size:13px;color:#b0bab5;}

@media(max-width:580px){
  .card{grid-template-columns:1fr;}
  .panel-l{padding:32px 28px;min-height:auto;}
  .panel-r{padding:36px 28px;}
}
</style>

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