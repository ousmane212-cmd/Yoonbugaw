<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);

// ── Catégories disponibles ──────────────────────────────────
$categories = [
    'Tout'         => 48,
    'Conseils'     => 18,
    'Sécurité'     => 12,
    'Mobilité'     => 9,
    'Actualités'   => 6,
    'Chauffeurs'   => 3,
    'Destinations' => 0,
];
$filtre_actif = isset($_GET['cat']) ? $_GET['cat'] : 'Tout';

// ── Article à la une (statique, à remplacer par BDD si besoin) ─
$article_une = [
    'icon'       => 'ti-route',
    'badge'      => 'À la une',
    'titre'      => 'Le transport interurbain au Sénégal : état des lieux 2026',
    'categorie'  => 'MOBILITÉ',
    'extrait'    => 'Un panorama complet des solutions de transport entre les grandes villes sénégalaises. De Dakar à Saint-Louis, en passant par Thiès et Ziguinchor, découvrez les options disponibles, les tarifs pratiqués et les initiatives de modernisation en cours.',
    'auteur'     => 'Aminata Kane',
    'initiales'  => 'AK',
    'date'       => '3 juin 2026',
    'lecture'    => '8 min de lecture',
    'vues'       => '3,4k',
    'likes'      => '142',
    'slug'       => 'transport-interurbain-senegal-2026',
];

// ── Grille d'articles ────────────────────────────────────────
$articles = [
   [
    'icon'      => 'ti-road',
    'categorie' => 'CONSEILS',
    'titre'     => '5 astuces pour un covoiturage réussi entre Dakar et Saint-Louis',
    'extrait'   => 'Découvrez comment préparer un trajet collectif agréable et sécurisé sur cet axe très fréquenté.',
    'date'      => '28 mai 2026',
    'slug'      => 'astuces-covoiturage-dakar-saint-louis',
],

[
    'icon'      => 'ti-shield-check',
    'categorie' => 'SÉCURITÉ',
    'titre'     => 'Comment nous sélectionnons et vérifions nos chauffeurs partenaires',
    'extrait'   => 'Notre processus de vérification en 7 étapes pour garantir votre sécurité à bord de chaque véhicule.',
    'date'      => '20 mai 2026',
    'slug'      => 'verification-chauffeurs-partenaires',
],

[
    'icon'      => 'ti-map-pin',
    'categorie' => 'DESTINATIONS',
    'titre'     => 'Guide complet : rejoindre la Casamance depuis Dakar',
    'extrait'   => 'Routes, horaires, tarifs et conseils pratiques pour un voyage en Casamance sans stress.',
    'date'      => '15 mai 2026',
    'slug'      => 'guide-casamance-depuis-dakar',
],

[
    'icon'      => 'ti-leaf',
    'categorie' => 'MOBILITÉ',
    'titre'     => 'Covoiturage et empreinte carbone : ce que vous devez savoir',
    'extrait'   => "Réduire vos trajets solo, c'est aussi agir pour l'environnement. Chiffres et témoignages à l'appui.",
    'date'      => '10 mai 2026',
    'slug'      => 'covoiturage-empreinte-carbone',
],

[
    'icon'      => 'ti-star',
    'categorie' => 'CHAUFFEURS',
    'titre'     => 'Devenir chauffeur partenaire : avantages, revenus et témoignages',
    'extrait'   => "Ils ont rejoint Yoon bu Gaw il y a 6 mois. Découvrez leurs retours d'expérience concrets.",
    'date'      => '5 mai 2026',
    'slug'      => 'devenir-chauffeur-partenaire',
],

[
    'icon'      => 'ti-device-mobile',
    'categorie' => 'ACTUALITÉS',
    'titre'     => 'Yoon bu Gaw lance le suivi en temps réel de vos trajets',
    'extrait'   => 'Une nouvelle fonctionnalité pour partager votre position avec vos proches pendant vos déplacements.',
    'date'      => '1 mai 2026',
    'slug'      => 'suivi-temps-reel-trajets',
],
];

// ── Catégories avec barres de progression ────────────────────
$categories_stats = [
    ['rang' => 1, 'nom' => 'Conseils voyage',  'pct' => 90, 'nb' => 18],
    ['rang' => 2, 'nom' => 'Sécurité',          'pct' => 70, 'nb' => 12],
    ['rang' => 3, 'nom' => 'Destinations',      'pct' => 55, 'nb' => 9],
    ['rang' => 4, 'nom' => 'Actualités',        'pct' => 40, 'nb' => 6],
    ['rang' => 5, 'nom' => 'Chauffeurs',        'pct' => 20, 'nb' => 3],
];

// ── Articles les plus lus ────────────────────────────────────
$articles_populaires = [
    ['titre' => 'Transport interurbain au Sénégal : état des lieux 2026',      'vues' => '3,4k'],
    ['titre' => '5 astuces pour un covoiturage réussi Dakar–Saint-Louis',       'vues' => '2,1k'],
    ['titre' => 'Devenir chauffeur partenaire : avantages et témoignages',      'vues' => '1,8k'],
    ['titre' => 'Guide complet : Rejoindre la Casamance depuis Dakar',          'vues' => '1,2k'],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog & Actualités | Yoon bu Gaw</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ═══════════════════════════════════════════════════
           Variables & Reset
        ═══════════════════════════════════════════════════ */
        :root {
            --green:      #005f28;
            --green-lite: rgba(0, 125, 52, 0.08);
            --green-glow: #005f28;
            --navy:       #001529;
            --navy-mid:   #005f28;
            --white:      #ffffff;
            --bg:         #f5f7f5;
            --border:     rgba(0, 0, 0, 0.08);
            --text:       #1a1a2e;
            --muted:      #6b7280;
            --radius-sm:  8px;
            --radius-md:  12px;
            --radius-lg:  16px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            font-size: 14px;
            line-height: 1.6;
        }

        /* ═══════════════════════════════════════════════════
           Hero Banner
        ═══════════════════════════════════════════════════ */
        .blog-hero {
            background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 100%);
            padding: 56px 0 48px;
            position: relative;
            overflow: hidden;
        }
        .blog-hero::before {
            content: '';
            position: absolute;
            top: -70px; right: -70px;
            width: 260px; height: 260px;
            border-radius: 50%;
            background: var(--green-glow);
        }
        .blog-hero::after {
            content: '';
            position: absolute;
            bottom: -50px; left: 32%;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: rgba(0, 125, 52, 0.06);
        }

        .hero-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(0, 125, 52, 0.25);
            color: #6ee092;
            font-size: 11px;
            font-weight: 600;
            padding: 5px 14px;
            border-radius: 20px;
            margin-bottom: 18px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
        }

        .blog-hero h1 {
            font-size: 32px;
            font-weight: 700;
            color: var(--white);
            line-height: 1.25;
            margin-bottom: 12px;
        }
        .blog-hero p {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.6);
            max-width: 460px;
            margin-bottom: 28px;
        }

        /* Barre de recherche */
        .hero-search {
            display: flex;
            gap: 8px;
            max-width: 460px;
        }
        .hero-search input {
            flex: 1;
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: var(--radius-sm);
            padding: 11px 16px;
            color: var(--white);
            font-size: 13px;
            outline: none;
            font-family: inherit;
            transition: border-color .2s;
        }
        .hero-search input::placeholder { color: rgba(255, 255, 255, 0.38); }
        .hero-search input:focus { border-color: rgba(110, 224, 146, 0.5); }
        .hero-search button {
            background: var(--green);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            padding: 11px 22px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: opacity .2s;
        }
        .hero-search button:hover { opacity: .85; }

        /* Statistiques hero */
        .hero-stats {
            display: flex;
            gap: 32px;
            margin-top: 32px;
            position: relative;
            z-index: 1;
        }
        .stat-item .stat-num {
            font-size: 24px;
            font-weight: 700;
            color: var(--white);
        }
        .stat-item .stat-lbl {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.48);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* ═══════════════════════════════════════════════════
           Contenu principal
        ═══════════════════════════════════════════════════ */
        .blog-body { padding: 36px 0 48px; }

        /* ── Barre de filtres ─────────────────────────────── */
        .filter-bar {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            align-items: center;
            margin-bottom: 28px;
        }
        .filter-tag {
            font-size: 12px;
            font-weight: 500;
            padding: 7px 16px;
            border-radius: 20px;
            border: 1px solid var(--border);
            color: var(--muted);
            cursor: pointer;
            background: var(--white);
            text-decoration: none;
            transition: all .18s;
            font-family: inherit;
        }
        .filter-tag:hover { border-color: var(--green); color: var(--green); }
        .filter-tag.active {
            background: var(--green);
            color: var(--white);
            border-color: var(--green);
        }
        .filter-count {
            font-size: 12px;
            color: var(--muted);
            margin-left: auto;
        }

        /* ── Article à la une ─────────────────────────────── */
        .featured-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            overflow: hidden;
            margin-bottom: 28px;
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.05);
        }
        .feat-visual {
            background: linear-gradient(145deg, var(--navy) 0%, var(--navy-mid) 100%);
            padding: 36px;
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            min-height: 240px;
            position: relative;
            overflow: hidden;
        }
        .feat-visual::after {
            content: '';
            position: absolute;
            bottom: -30px; right: -30px;
            width: 120px; height: 120px;
            border-radius: 50%;
            background: rgba(0, 125, 52, 0.15);
        }
        .feat-icon {
            width: 60px; height: 60px;
            background: rgba(0, 125, 52, 0.25);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }
        .feat-icon i { font-size: 28px; color: #6ee092; }
        .feat-badge {
            display: inline-block;
            background: rgba(0, 125, 52, 0.3);
            color: #6ee092;
            font-size: 10px;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 10px;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .feat-visual h2 {
            font-size: 19px;
            font-weight: 700;
            color: var(--white);
            line-height: 1.4;
            position: relative;
            z-index: 1;
        }
        .feat-body {
            padding: 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .tag-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            font-size: 11px;
            font-weight: 700;
            color: var(--green);
            background: var(--green-lite);
            padding: 4px 12px;
            border-radius: 10px;
            letter-spacing: 0.4px;
            margin-bottom: 14px;
        }
        .feat-body > div > p {
            font-size: 13px;
            color: var(--muted);
            line-height: 1.75;
            margin-bottom: 0;
        }
        .feat-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 24px;
        }
        .author-av {
            width: 34px; height: 34px;
            border-radius: 50%;
            background: var(--green-lite);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            color: var(--green);
            flex-shrink: 0;
        }
        .meta-info { font-size: 11px; color: var(--muted); }
        .meta-info strong { color: var(--text); font-weight: 600; display: block; }
        .meta-stats {
            margin-left: auto;
            display: flex;
            gap: 10px;
        }
        .meta-stats span {
            font-size: 11px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .btn-lire {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: var(--green);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            margin-top: 20px;
            border: 1px solid rgba(0, 125, 52, 0.3);
            padding: 9px 18px;
            border-radius: var(--radius-sm);
            transition: background .18s;
        }
        .btn-lire:hover { background: var(--green-lite); color: var(--green); }

        /* ── Grille d'articles ────────────────────────────── */
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 18px;
            margin-bottom: 28px;
        }
        .article-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            transition: box-shadow .2s, transform .2s;
        }
        .article-card:hover {
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        .card-visual {
            padding: 28px;
            background: #f8faf8;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 90px;
        }
        .card-visual i { font-size: 34px; color: var(--green); }
        .card-content { padding: 18px 18px 0; flex: 1; }
        .card-tag {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            color: var(--green);
            background: var(--green-lite);
            padding: 3px 9px;
            border-radius: 6px;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .card-title {
            font-size: 13px;
            font-weight: 600;
            color: var(--text);
            line-height: 1.5;
            margin-bottom: 8px;
        }
        .card-excerpt {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.65;
        }
        .card-foot {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-top: 1px solid var(--border);
            padding: 11px 18px;
            margin-top: 14px;
        }
        .card-date {
            font-size: 11px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .card-date i { font-size: 12px; }
        .card-link {
            font-size: 12px;
            font-weight: 600;
            color: var(--green);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .card-link:hover { text-decoration: underline; }

        /* ── Newsletter ───────────────────────────────────── */
        .newsletter-block {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 28px 32px;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 28px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
        }
        .nl-icon {
            width: 54px; height: 54px;
            background: var(--green-lite);
            border-radius: var(--radius-md);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .nl-icon i { font-size: 24px; color: var(--green); }
        .nl-text h3 { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .nl-text p  { font-size: 13px; color: var(--muted); }
        .nl-form {
            display: flex;
            gap: 8px;
            margin-left: auto;
            flex-shrink: 0;
        }
        .nl-form input {
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 16px;
            font-size: 13px;
            outline: none;
            width: 220px;
            font-family: inherit;
            color: var(--text);
            transition: border-color .2s;
        }
        .nl-form input:focus { border-color: var(--green); }
        .nl-form button {
            background: var(--green);
            color: var(--white);
            border: none;
            border-radius: var(--radius-sm);
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: opacity .2s;
        }
        .nl-form button:hover { opacity: .85; }

        /* ── Section stats + articles populaires ─────────── */
        .sidebar-layout {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 20px;
            margin-bottom: 28px;
        }
        .stats-card,
        .popular-card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 22px;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .section-title i { font-size: 15px; color: var(--green); }

        /* Barres catégories */
        .topic-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 9px 0;
            border-bottom: 1px solid var(--border);
        }
        .topic-row:last-child { border-bottom: none; }
        .topic-num  { font-size: 11px; color: var(--muted); width: 18px; }
        .topic-name { font-size: 13px; color: var(--text); flex: 1; }
        .bar-wrap   { width: 80px; height: 4px; background: #e8f0e9; border-radius: 2px; }
        .bar-fill   { height: 4px; background: var(--green); border-radius: 2px; }
        .topic-nb   { font-size: 11px; color: var(--muted); white-space: nowrap; }

        /* Articles populaires */
        .pop-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
        }
        .pop-item:last-child { border-bottom: none; }
        .pop-rank {
            font-size: 20px;
            font-weight: 700;
            color: rgba(0, 125, 52, 0.18);
            width: 22px;
            flex-shrink: 0;
            line-height: 1;
        }
        .pop-title { font-size: 12px; color: var(--text); line-height: 1.55; margin-bottom: 3px; }
        .pop-views {
            font-size: 11px;
            color: var(--muted);
            display: flex;
            align-items: center;
            gap: 3px;
        }
        .pop-views i { font-size: 11px; }

        /* ── Pagination ───────────────────────────────────── */
        .pagination-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }
        .page-btn {
            width: 36px; height: 36px;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
            background: var(--white);
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-family: inherit;
            transition: all .18s;
        }
        .page-btn:hover { border-color: var(--green); color: var(--green); }
        .page-btn.active {
            background: var(--green);
            color: var(--white);
            border-color: var(--green);
        }
        .page-sep { font-size: 13px; color: var(--muted); padding: 0 2px; }

        /* ═══════════════════════════════════════════════════
           Responsive
        ═══════════════════════════════════════════════════ */
        @media (max-width: 992px) {
            .featured-card  { grid-template-columns: 1fr; }
            .articles-grid  { grid-template-columns: repeat(2, 1fr); }
            .sidebar-layout { grid-template-columns: 1fr; }
            .nl-form        { display: none; } /* simplifié mobile */
        }
        @media (max-width: 576px) {
            .articles-grid { grid-template-columns: 1fr; }
            .blog-hero h1  { font-size: 24px; }
            .hero-stats    { gap: 20px; }
            .newsletter-block { flex-wrap: wrap; }
        }
    </style>
</head>
<body>

<?php include_once('../includes/header.php'); ?>

<!-- ╔═══════════════════════════════════════════════════════╗ -->
<!-- ║  HERO BANNER                                          ║ -->
<!-- ╚═══════════════════════════════════════════════════════╝ -->
<section class="blog-hero">
    <div class="container" style="position:relative; z-index:1;">

        <div class="hero-label">
            <i class="ti ti-news" aria-hidden="true"></i>
            Le Mag' Yoon bu Gaw
        </div>

        <h1>Actualités, conseils &amp;<br>guides de voyage</h1>

        <p>
            Restez informé sur le transport au Sénégal : sécurité,
            covoiturage, mobilité durable et bien plus encore.
        </p>

        <form class="hero-search" method="GET" action="blog.php">
            <input
                type="text"
                name="q"
                placeholder="Rechercher un article..."
                value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                autocomplete="off"
            >
            <button type="submit">
                <i class="ti ti-search" aria-hidden="true"></i>
                Rechercher
            </button>
        </form>

        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-num">48</div>
                <div class="stat-lbl">Articles</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">6</div>
                <div class="stat-lbl">Catégories</div>
            </div>
            <div class="stat-item">
                <div class="stat-num">12k</div>
                <div class="stat-lbl">Lecteurs</div>
            </div>
        </div>

    </div>
</section>

<!-- ╔═══════════════════════════════════════════════════════╗ -->
<!-- ║  CORPS DE PAGE                                        ║ -->
<!-- ╚═══════════════════════════════════════════════════════╝ -->
<main class="blog-body">
<div class="container">

    <!-- ── Barre de filtres / catégories ───────────────────── -->
    <nav class="filter-bar" aria-label="Filtrer par catégorie">
        <?php foreach ($categories as $label => $nb): ?>
            <a
                href="blog.php?cat=<?= urlencode($label) ?>"
                class="filter-tag <?= ($filtre_actif === $label) ? 'active' : '' ?>"
            >
                <?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>
        <span class="filter-count">48 articles au total</span>
    </nav>

    <!-- ── Article à la une ─────────────────────────────────── -->
    <article class="featured-card">

        <div class="feat-visual">
            <div class="feat-icon">
                <i class="ti <?= htmlspecialchars($article_une['icon']) ?>" aria-hidden="true"></i>
            </div>
            <span class="feat-badge"><?= htmlspecialchars($article_une['badge']) ?></span>
            <h2><?= htmlspecialchars($article_une['titre']) ?></h2>
        </div>

        <div class="feat-body">
            <div>
                <div class="tag-pill">
                    <i class="ti ti-tag" style="font-size:11px" aria-hidden="true"></i>
                    <?= htmlspecialchars($article_une['categorie']) ?>
                </div>
                <p><?= htmlspecialchars($article_une['extrait']) ?></p>
            </div>

            <div>
                <div class="feat-meta">
                    <div class="author-av"><?= htmlspecialchars($article_une['initiales']) ?></div>
                    <div class="meta-info">
                        <strong><?= htmlspecialchars($article_une['auteur']) ?></strong>
                        <?= htmlspecialchars($article_une['date']) ?> &middot; <?= htmlspecialchars($article_une['lecture']) ?>
                    </div>
                    <div class="meta-stats">
                        <span>
                            <i class="ti ti-eye" aria-hidden="true"></i>
                            <?= htmlspecialchars($article_une['vues']) ?>
                        </span>
                        <span>
                            <i class="ti ti-heart" aria-hidden="true"></i>
                            <?= htmlspecialchars($article_une['likes']) ?>
                        </span>
                    </div>
                </div>

                <a href="article.php?slug=<?= urlencode($article_une['slug']) ?>" class="btn-lire">
                    Lire l'article
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>

    </article>

    <!-- ── Grille 6 articles ────────────────────────────────── -->
    <div class="articles-grid">
        <?php foreach ($articles as $a): ?>
        <article class="article-card">

            <div class="card-visual">
                <i class="ti <?= htmlspecialchars($a['icon']) ?>" aria-hidden="true"></i>
            </div>

            <div class="card-content">
                <span class="card-tag"><?= htmlspecialchars($a['categorie']) ?></span>
                <div class="card-title"><?= htmlspecialchars($a['titre']) ?></div>
                <div class="card-excerpt"><?= htmlspecialchars($a['extrait']) ?></div>
            </div>

            <div class="card-foot">
                <span class="card-date">
                    <i class="ti ti-calendar" aria-hidden="true"></i>
                    <?= htmlspecialchars($a['date']) ?>
                </span>
                <a href="article.php?slug=<?= urlencode($a['slug']) ?>" class="card-link">
                    Lire
                    <i class="ti ti-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

        </article>
        <?php endforeach; ?>
    </div>

    <!-- ── Bloc newsletter ──────────────────────────────────── -->
    <div class="newsletter-block">
        <div class="nl-icon">
            <i class="ti ti-mail" aria-hidden="true"></i>
        </div>
        <div class="nl-text">
            <h3>Newsletter Yoon bu Gaw</h3>
            <p>Recevez les meilleurs articles directement dans votre boîte mail, chaque semaine.</p>
        </div>
        <form class="nl-form" method="POST" action="newsletter.php">
            <input
                type="email"
                name="email"
                placeholder="votre@email.com"
                required
            >
            <button type="submit">S'abonner</button>
        </form>
    </div>

    <!-- ── Catégories + Articles populaires ─────────────────── -->
    <div class="sidebar-layout">

        <!-- Catégories avec barres -->
        <div class="stats-card">
            <div class="section-title">
                <i class="ti ti-chart-bar" aria-hidden="true"></i>
                Catégories populaires
            </div>
            <?php foreach ($categories_stats as $c): ?>
            <div class="topic-row">
                <span class="topic-num"><?= $c['rang'] ?></span>
                <span class="topic-name"><?= htmlspecialchars($c['nom']) ?></span>
                <div class="bar-wrap">
                    <div class="bar-fill" style="width:<?= $c['pct'] ?>%"></div>
                </div>
                <span class="topic-nb"><?= $c['nb'] ?> articles</span>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Articles les plus lus -->
        <div class="popular-card">
            <div class="section-title">
                <i class="ti ti-flame" aria-hidden="true"></i>
                Articles les plus lus
            </div>
            <?php foreach ($articles_populaires as $i => $p): ?>
            <div class="pop-item">
                <span class="pop-rank"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                <div>
                    <div class="pop-title"><?= htmlspecialchars($p['titre']) ?></div>
                    <div class="pop-views">
                        <i class="ti ti-eye" aria-hidden="true"></i>
                        <?= htmlspecialchars($p['vues']) ?> vues
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>

    <!-- ── Pagination ───────────────────────────────────────── -->
    <nav class="pagination-bar" aria-label="Pagination">
        <button class="page-btn" aria-label="Page précédente">
            <i class="ti ti-chevron-left" aria-hidden="true"></i>
        </button>
        <button class="page-btn active" aria-current="page">1</button>
        <button class="page-btn">2</button>
        <button class="page-btn">3</button>
        <span class="page-sep">&hellip;</span>
        <button class="page-btn">8</button>
        <button class="page-btn" aria-label="Page suivante">
            <i class="ti ti-chevron-right" aria-hidden="true"></i>
        </button>
    </nav>

</div><!-- /.container -->
</main>

<?php include_once('../includes/footer.php'); ?>

<!-- ╔═══════════════════════════════════════════════════════╗ -->
<!-- ║  SCRIPTS                                              ║ -->
<!-- ╚═══════════════════════════════════════════════════════╝ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Filtres de catégorie (highlight actif) ─────────────────
document.querySelectorAll('.filter-tag').forEach(btn => {
    btn.addEventListener('click', function () {
        // La navigation se fait via href — l'état actif est géré côté PHP.
        // Ce bloc peut servir pour un filtrage AJAX futur.
    });
});

// ── Pagination (placeholder) ───────────────────────────────
document.querySelectorAll('.page-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        if (this.querySelector('i')) return; // boutons prev/next
        document.querySelectorAll('.page-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});
</script>

</body>
</html>
