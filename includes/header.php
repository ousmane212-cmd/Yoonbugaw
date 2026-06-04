<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yoon bu Gaw | La route qui nous rapproche</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

    <style>
        :root {
            --green:       #007D34;
            --green-light: #E6F3EB;
            --green-dark:  #005f28;
            --navy:        #001529;
            --blue:        #003366;
            --orange:      #FF9800;
            --gray:        #6B7280;
            --bg:          #F8F9FA;
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; color: var(--navy); background: #fff; overflow-x: hidden; margin: 0; }

       /* ===== NAVBAR PREMIUM ===== */
.navbar{
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    z-index: 9999;

    background: rgba(255,255,255,0.98);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);

    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
    box-shadow: 0 4px 20px rgba(0,0,0,.08);
}

.brand-wrap{
    display:flex;
    align-items:center;
    gap:12px;
    text-decoration:none;
}

.brand-logo{
    width:55px;
    height:55px;
    object-fit:contain;
    transition:.3s;
}

.brand-wrap:hover .brand-logo{
    transform:scale(1.08);
}

.brand-name{
    font-size:1.3rem;
    font-weight:800;
    color:var(--navy);
}

.brand-name span{
    color:var(--green);
}

.brand-tagline{
    font-size:.65rem;
    color:var(--gray);
    font-weight:600;
    letter-spacing:1px;
    text-transform:uppercase;
}

/* Liens menu */
.nav-link{
    font-weight:700;
    font-size:.9rem;
    color:var(--navy)!important;
    margin:0 3px;
    padding:10px 16px!important;
    border-radius:10px;
    transition:all .3s ease;
    position:relative;
}

.nav-link:hover{
    background:var(--green-light);
    color:var(--green)!important;
}

.nav-link.active{
    color:var(--green)!important;
    background:var(--green-light);
}

.nav-link.active::after{
    content:'';
    position:absolute;
    left:15%;
    bottom:5px;
    width:70%;
    height:3px;
    background:var(--green);
    border-radius:50px;
}

/* Bouton connexion */
.btn-connexion{
    border:2px solid #d1d5db;
    border-radius:12px;
    padding:10px 22px;
    font-weight:700;
    text-decoration:none;
    color:var(--navy);
    transition:.3s;
}

.btn-connexion:hover{
    border-color:var(--green);
    color:var(--green);
    transform:translateY(-2px);
}

/* Bouton inscription */
.btn-inscrire{
    background:linear-gradient(
        135deg,
        var(--green),
        var(--green-dark)
    );
    color:#fff!important;
    border-radius:12px;
    padding:10px 24px;
    font-weight:700;
    text-decoration:none;
    transition:.3s;
    box-shadow:0 8px 20px rgba(0,125,52,.25);
}

.btn-inscrire:hover{
    transform:translateY(-2px);
    box-shadow:0 12px 30px rgba(0,125,52,.35);
}

/* Menu mobile */
.navbar-toggler{
    border:none!important;
    box-shadow:none!important;
}

.navbar-toggler:focus{
    box-shadow:none!important;
}

@media(max-width:991px){

    .navbar-collapse{
        margin-top:15px;
        background:#fff;
        padding:20px;
        border-radius:15px;
        box-shadow:0 10px 30px rgba(0,0,0,.08);
    }

    .navbar-nav{
        margin-bottom:15px;
    }

    .nav-link{
        padding:12px!important;
    }

    .d-flex.align-items-center.gap-2{
        flex-direction:column;
        width:100%;
    }

    .btn-connexion,
    .btn-inscrire{
        width:100%;
        text-align:center;
    }
}
    </style>
</head>

<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">

    <div class="container">
        <a class="brand-wrap navbar-brand" href="index.php">
            <img src="../assets/images/logo.png" alt="Yoon bu Gaw logo" class="brand-logo">
            <div class="brand-text">
                <span class="brand-name">Yoon bu <span>Gaw</span></span>
                <span class="brand-tagline">La route qui nous rapproche</span>
            </div>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav mx-auto gap-1">
                <li class="nav-item"><a class="nav-link active" href="../index.php">Accueil</a></li>
                <li class="nav-item"><a class="nav-link" href="services.php">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">À propos</a></li>
                <li class="nav-item"><a class="nav-link" href="tarifs.php">Tarifs</a></li>
                <li class="nav-item"><a class="nav-link" href="blog.php">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="contact.php">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                
                  
               
                    <a href="../auth/login.php"    class="btn-connexion">Connexion</a>
                    <a href="../auth/traitement_inscription.php" class="btn-inscrire">S'inscrire</a>
              
            </div>
        </div>
    </div>
</nav>
<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>