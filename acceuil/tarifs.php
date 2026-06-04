<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nos Tarifs | Yoon bu Gaw</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">

 <style>
  @import url('https://fonts.googleapis.com/css2?family=Sora:wght@400;600;800&family=DM+Sans:wght@400;500&display=swap');
  *{box-sizing:border-box;margin:0;padding:0;}
  .pg{font-family:'Sora',sans-serif;color:#0a1628;background:#fff;}
  .hero{background:#005f28;padding:64px 32px 56px;text-align:center;position:relative;overflow:hidden;}
  .hero h1{font-size:32px;font-weight:800;color:#fff;letter-spacing:-0.5px;position:relative;}
  .hero p{color:#a8f0c6;font-size:15px;margin-top:10px;position:relative;font-family:'DM Sans',sans-serif;}
  .badge-strip{display:flex;gap:10px;justify-content:center;margin-top:20px;position:relative;flex-wrap:wrap;}
  .badge-strip span{background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.25);border-radius:100px;padding:6px 16px;font-size:12px;font-weight:600;}
  .features{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;padding:32px;}
  .feat{background:#f7faf8;border:1px solid #d4ead9;border-radius:16px;padding:22px 18px;text-align:center;}
  .feat-icon{width:44px;height:44px;background:#e6f4ec;border-radius:12px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;color:#007D34;font-size:20px;}
  .feat h4{font-size:14px;font-weight:700;margin-bottom:6px;color:#0a1628;}
  .feat p{font-size:12px;color:#6b7e8f;font-family:'DM Sans',sans-serif;line-height:1.5;}
  .section{padding:0 32px 32px;}
  .sec-head{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
  .sec-head-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0;}
  .sec-head h3{font-size:18px;font-weight:800;letter-spacing:-0.3px;}
  .green-sec .sec-head-icon{background:#e6f4ec;color:#007D34;}
  .green-sec h3{color:#007D34;}
  .blue-sec .sec-head-icon{background:#e8f0fd;color:#2563eb;}
  .blue-sec h3{color:#2563eb;}
  .price-grid{display:grid;gap:8px;}
  .price-row{display:grid;grid-template-columns:1fr auto auto;align-items:center;gap:12px;padding:14px 18px;border-radius:12px;background:#f9fbfa;border:1px solid #e8f0ea;transition:.2s;}
  .blue-sec .price-row{background:#f6f8fd;border-color:#dde8fb;}
  .price-row:hover{transform:translateX(4px);}
  .price-row .route{font-size:14px;font-weight:600;color:#0a1628;}
  .price-row .sub{font-size:11px;color:#8a9aaa;font-family:'DM Sans',sans-serif;margin-top:1px;}
  .price-row .vehicle{font-size:11px;background:#e6f4ec;color:#1a6b3a;border-radius:100px;padding:3px 10px;font-weight:600;white-space:nowrap;}
  .blue-sec .price-row .vehicle{background:#e8f0fd;color:#1e4fa8;}
  .price-row .price{font-size:15px;font-weight:800;color:#007D34;white-space:nowrap;}
  .blue-sec .price-row .price{color:#2563eb;}
  .divider{height:1px;background:#e8f0ea;margin:4px 32px 28px;}
  .map-section{padding:0 32px 32px;}
  .map-head{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
  .map-head-icon{width:38px;height:38px;border-radius:10px;background:#fff3e0;color:#e65100;display:flex;align-items:center;justify-content:center;font-size:17px;}
  .map-head h3{font-size:18px;font-weight:800;color:#e65100;letter-spacing:-0.3px;}
  .map-canvas{background:#eaf3de;border-radius:16px;border:1px solid #c0dd97;padding:20px;position:relative;overflow:hidden;}
  .map-svg{width:100%;height:280px;}
  .city-dot{cursor:pointer;}
  .route-line{stroke:#007D34;stroke-width:2;stroke-dasharray:6 3;fill:none;opacity:0.6;}
  .route-line.active{stroke:#007D34;stroke-width:2.5;stroke-dasharray:none;opacity:1;}
  .city-circle{fill:#007D34;}
  .city-label{font-family:'Sora',sans-serif;font-size:11px;font-weight:700;fill:#27500A;}
  .city-hub{fill:#fff;stroke:#007D34;stroke-width:2.5;}
  .info-box{background:#fff;border:1px solid #c0dd97;border-radius:12px;padding:14px 16px;margin-top:14px;display:none;}
  .info-box.show{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
  .info-route{font-size:14px;font-weight:700;color:#007D34;}
  .info-detail{font-size:12px;color:#5a7a5a;font-family:'DM Sans',sans-serif;}
  .info-price{font-size:20px;font-weight:800;color:#007D34;white-space:nowrap;}
  .promo-section{padding:0 32px 32px;}
  .promo-head{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
  .promo-head-icon{width:38px;height:38px;border-radius:10px;background:#faeeda;color:#854F0B;display:flex;align-items:center;justify-content:center;font-size:17px;}
  .promo-head h3{font-size:18px;font-weight:800;color:#854F0B;letter-spacing:-0.3px;}
  .promo-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px;}
  .promo-card{border-radius:14px;padding:18px;border:1px solid;}
  .promo-card.amber{background:#faeeda;border-color:#FAC775;}
  .promo-card.green{background:#eaf3de;border-color:#c0dd97;}
  .promo-card.blue{background:#e6f1fb;border-color:#B5D4F4;}
  .promo-card.pink{background:#fbeaf0;border-color:#F4C0D1;}
  .promo-tag{font-size:11px;font-weight:700;border-radius:100px;padding:3px 10px;display:inline-block;margin-bottom:8px;}
  .amber .promo-tag{background:#FAC775;color:#633806;}
  .green .promo-tag{background:#c0dd97;color:#27500A;}
  .blue .promo-tag{background:#B5D4F4;color:#0C447C;}
  .pink .promo-tag{background:#F4C0D1;color:#72243E;}
  .promo-card h4{font-size:13px;font-weight:700;margin-bottom:4px;}
  .amber h4{color:#412402;}
  .green h4{color:#173404;}
  .blue h4{color:#042C53;}
  .pink h4{color:#4B1528;}
  .promo-card p{font-size:12px;font-family:'DM Sans',sans-serif;line-height:1.5;}
  .amber p{color:#854F0B;}
  .green p{color:#3B6D11;}
  .blue p{color:#185FA5;}
  .pink p{color:#993556;}
  .promo-code{font-family:monospace;font-size:13px;font-weight:700;background:rgba(255,255,255,0.6);padding:4px 10px;border-radius:6px;margin-top:8px;display:inline-block;}
  .plans-section{padding:0 32px 32px;}
  .plans-head{display:flex;align-items:center;gap:10px;margin-bottom:18px;}
  .plans-head-icon{width:38px;height:38px;border-radius:10px;background:#eeedfe;color:#534AB7;display:flex;align-items:center;justify-content:center;font-size:17px;}
  .plans-head h3{font-size:18px;font-weight:800;color:#534AB7;letter-spacing:-0.3px;}
  .plans-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
  .plan-card{border-radius:16px;padding:22px;border:1.5px solid;}
  .plan-basic{background:#f9fbfa;border-color:#d4ead9;}
  .plan-premium{background:#eeedfe;border-color:#AFA9EC;position:relative;}
  .plan-badge{font-size:11px;font-weight:700;background:#534AB7;color:#fff;border-radius:100px;padding:3px 12px;display:inline-block;margin-bottom:12px;}
  .plan-name{font-size:16px;font-weight:800;margin-bottom:4px;}
  .plan-basic .plan-name{color:#27500A;}
  .plan-premium .plan-name{color:#3C3489;}
  .plan-price{font-size:26px;font-weight:800;margin-bottom:14px;}
  .plan-basic .plan-price{color:#007D34;}
  .plan-premium .plan-price{color:#534AB7;}
  .plan-price span{font-size:13px;font-weight:500;opacity:0.6;}
  .plan-features{list-style:none;display:flex;flex-direction:column;gap:8px;}
  .plan-features li{font-size:13px;font-family:'DM Sans',sans-serif;display:flex;align-items:center;gap:8px;}
  .plan-basic .plan-features li{color:#3B6D11;}
  .plan-premium .plan-features li{color:#3C3489;}
  .plan-features li i{font-size:15px;flex-shrink:0;}
  .plan-basic .plan-features li i{color:#007D34;}
  .plan-premium .plan-features li i{color:#534AB7;}
  .plan-features li.no{opacity:0.4;}
  .faq{padding:0 32px 32px;}
  .faq h2{font-size:18px;font-weight:800;margin-bottom:18px;color:#0a1628;}
  .faq-item{border:1px solid #e2ebe4;border-radius:12px;margin-bottom:10px;overflow:hidden;}
  .faq-q{padding:16px 18px;font-size:14px;font-weight:600;cursor:pointer;display:flex;justify-content:space-between;align-items:center;color:#0a1628;user-select:none;}
  .faq-q:hover{background:#f7faf8;}
  .faq-a{padding:0 18px 16px;font-size:13px;color:#6b7e8f;font-family:'DM Sans',sans-serif;line-height:1.6;display:none;}
  .faq-a.open{display:block;}
  .faq-chevron{font-size:14px;color:#007D34;transition:.2s;}
  .faq-chevron.rot{transform:rotate(180deg);}
  .cta{margin:0 32px 32px;background:#0a1628;border-radius:20px;padding:32px;text-align:center;}
  .cta h2{font-size:22px;font-weight:800;color:#fff;margin-bottom:8px;}
  .cta p{color:#7a95b0;font-size:13px;margin-bottom:20px;font-family:'DM Sans',sans-serif;}
  .cta-btn{display:inline-block;background:#007D34;color:#fff;border:none;border-radius:100px;padding:12px 32px;font-size:14px;font-weight:700;font-family:'Sora',sans-serif;cursor:pointer;}
  .cta-btn:hover{background:#00a84f;}
  .note-var{font-size:11px;color:#8a9aaa;font-style:italic;margin-top:4px;font-family:'DM Sans',sans-serif;}
</style>
</head>

<body>

<?php include_once('../includes/header.php'); ?>

<div class="pg">

  <div class="hero">
    <h1>Nos Tarifs Transparents</h1>
    <p>Pas de surprise — tout est confirmé avant la réservation</p>
    <div class="badge-strip">
      <span>Prix fixes</span>
      <span>Réservation instantanée</span>
      <span>Chauffeurs vérifiés</span>
    </div>
  </div>

  <div class="features">
    <div class="feat">
      <div class="feat-icon"><i class="ti ti-lock" aria-hidden="true"></i></div>
      <h4>Prix Fixes</h4>
      <p>Le prix affiché est le prix final, sans frais cachés.</p>
    </div>
    <div class="feat">
      <div class="feat-icon"><i class="ti ti-bolt" aria-hidden="true"></i></div>
      <h4>Rapide</h4>
      <p>Réservation instantanée en quelques secondes.</p>
    </div>
    <div class="feat">
      <div class="feat-icon"><i class="ti ti-shield-check" aria-hidden="true"></i></div>
      <h4>Sécurisé</h4>
      <p>Chauffeurs vérifiés et trajet suivi en temps réel.</p>
    </div>
  </div>

  <div class="divider"></div>

  <div class="section green-sec">
    <div class="sec-head">
      <div class="sec-head-icon"><i class="ti ti-users" aria-hidden="true"></i></div>
      <h3>Covoiturage</h3>
    </div>
    <div class="price-grid">
      <div class="price-row">
        <div><div class="route">Dakar ↔ Thiès</div></div>
        <div class="vehicle">Berline</div>
        <div class="price">2 500 F</div>
      </div>
      <div class="price-row">
        <div><div class="route">Dakar ↔ Mbour</div></div>
        <div class="vehicle">Berline</div>
        <div class="price">3 500 F</div>
      </div>
      <div class="price-row">
        <div><div class="route">Dakar ↔ Saint-Louis</div></div>
        <div class="vehicle">SUV</div>
        <div class="price">7 000 F</div>
      </div>
      <div class="price-row">
        <div><div class="route">Dakar ↔ Touba</div></div>
        <div class="vehicle">Berline</div>
        <div class="price">5 000 F</div>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <div class="section blue-sec">
    <div class="sec-head">
      <div class="sec-head-icon"><i class="ti ti-package" aria-hidden="true"></i></div>
      <h3>Livraison & Colis</h3>
    </div>
    <div class="price-grid">
      <div class="price-row">
        <div><div class="route">Moins de 5 kg</div><div class="sub">Livraison en 24h</div></div>
        <div class="vehicle">Standard</div>
        <div class="price">1 500 F</div>
      </div>
      <div class="price-row">
        <div><div class="route">5 à 20 kg</div><div class="sub">Livraison en 24h</div></div>
        <div class="vehicle">Standard</div>
        <div class="price">3 000 F</div>
      </div>
      <div class="price-row">
        <div><div class="route">Plus de 20 kg</div><div class="sub">Délai sur devis</div></div>
        <div class="vehicle">Spécial</div>
        <div class="price">Devis</div>
      </div>
    </div>
    <div class="note-var">* Les colis lourds (+20 kg) sont traités sur devis selon la destination et le poids exact.</div>
  </div>

  <div class="divider"></div>

  <div class="map-section">
    <div class="map-head">
      <div class="map-head-icon"><i class="ti ti-map-pin" aria-hidden="true"></i></div>
      <h3>Carte des trajets disponibles</h3>
    </div>
    <div class="map-canvas">
      <svg class="map-svg" viewBox="0 0 580 280" id="mapSvg">
        <line class="route-line" id="l-dk-th" x1="130" y1="155" x2="215" y2="130"/>
        <line class="route-line" id="l-dk-mb" x1="130" y1="155" x2="190" y2="210"/>
        <line class="route-line" id="l-dk-sl" x1="130" y1="155" x2="260" y2="40"/>
        <line class="route-line" id="l-dk-tb" x1="130" y1="155" x2="340" y2="115"/>

        <g class="city-dot" onclick="selectCity('dakar', 'Dakar (Hub)', '', '')">
          <circle cx="130" cy="155" r="14" class="city-hub"/>
          <circle cx="130" cy="155" r="7" class="city-circle"/>
          <text x="130" y="179" text-anchor="middle" class="city-label">DAKAR</text>
          <text x="130" y="191" text-anchor="middle" style="font-family:'DM Sans';font-size:10px;fill:#3B6D11;">Hub principal</text>
        </g>

        <g class="city-dot" onclick="selectCity('thies','Dakar ↔ Thiès','Berline • 1h30','2 500 F')">
          <circle cx="215" cy="130" r="9" class="city-circle" opacity="0.8"/>
          <text x="215" y="116" text-anchor="middle" class="city-label">THIÈS</text>
        </g>

        <g class="city-dot" onclick="selectCity('mbour','Dakar ↔ Mbour','Berline • 1h45','3 500 F')">
          <circle cx="190" cy="210" r="9" class="city-circle" opacity="0.8"/>
          <text x="190" y="232" text-anchor="middle" class="city-label">MBOUR</text>
        </g>

        <g class="city-dot" onclick="selectCity('saintlouis','Dakar ↔ Saint-Louis','SUV • 4h','7 000 F')">
          <circle cx="260" cy="40" r="9" class="city-circle" opacity="0.8"/>
          <text x="275" y="36" text-anchor="start" class="city-label">SAINT-LOUIS</text>
        </g>

        <g class="city-dot" onclick="selectCity('touba','Dakar ↔ Touba','Berline • 3h','5 000 F')">
          <circle cx="340" cy="115" r="9" class="city-circle" opacity="0.8"/>
          <text x="340" y="101" text-anchor="middle" class="city-label">TOUBA</text>
        </g>

        <text x="480" y="255" style="font-family:'DM Sans';font-size:10px;fill:#639922;opacity:0.7;">Cliquez sur une ville</text>
      </svg>
      <div class="info-box" id="infoBox">
        <div>
          <div class="info-route" id="infoRoute"></div>
          <div class="info-detail" id="infoDetail"></div>
        </div>
        <div class="info-price" id="infoPrice"></div>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <div class="promo-section">
    <div class="promo-head">
      <div class="promo-head-icon"><i class="ti ti-tag" aria-hidden="true"></i></div>
      <h3>Promotions & réductions</h3>
    </div>
    <div class="promo-grid">
      <div class="promo-card amber">
        <div class="promo-tag">Nouveau client</div>
        <h4>-20% sur votre 1er trajet</h4>
        <p>Offre valable pour toute première réservation sur la plateforme.</p>
        <div class="promo-code">BIENVENUE20</div>
      </div>
      <div class="promo-card green">
        <div class="promo-tag">Parrainage</div>
        <h4>500 F par ami parrainé</h4>
        <p>Invitez un ami et gagnez 500 F crédités sur votre prochain trajet.</p>
        <div class="promo-code">PARRAIN500</div>
      </div>
      <div class="promo-card blue">
        <div class="promo-tag">Groupe</div>
        <h4>-15% dès 3 places</h4>
        <p>Réservez 3 places ou plus sur le même trajet et bénéficiez d'un tarif groupe.</p>
        <div class="promo-code">GROUPE15</div>
      </div>
      <div class="promo-card pink">
        <div class="promo-tag">Week-end</div>
        <h4>Tarif réduit vendredi-dimanche</h4>
        <p>Profitez de réductions automatiques sur certains trajets les week-ends.</p>
        <div class="promo-code">Auto appliqué</div>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <div class="plans-section">
    <div class="plans-head">
      <div class="plans-head-icon"><i class="ti ti-award" aria-hidden="true"></i></div>
      <h3>Comparer les formules</h3>
    </div>
    <div class="plans-grid">
      <div class="plan-card plan-basic">
        <div class="plan-name">Basic</div>
        <div class="plan-price">Gratuit<span> / toujours</span></div>
        <ul class="plan-features">
          <li><i class="ti ti-check" aria-hidden="true"></i> Réservation en ligne</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Trajets interurbains</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Suivi du chauffeur</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Paiement sécurisé</li>
          <li class="no"><i class="ti ti-x" aria-hidden="true"></i> Priorité de réservation</li>
          <li class="no"><i class="ti ti-x" aria-hidden="true"></i> Support prioritaire</li>
          <li class="no"><i class="ti ti-x" aria-hidden="true"></i> Annulation flexible</li>
        </ul>
      </div>
      <div class="plan-card plan-premium">
        <div class="plan-badge">Recommandé</div>
        <div class="plan-name">Premium</div>
        <div class="plan-price">2 500 F<span> / mois</span></div>
        <ul class="plan-features">
          <li><i class="ti ti-check" aria-hidden="true"></i> Réservation en ligne</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Trajets interurbains</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Suivi du chauffeur</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Paiement sécurisé</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Priorité de réservation</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Support prioritaire 24/7</li>
          <li><i class="ti ti-check" aria-hidden="true"></i> Annulation flexible</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="divider"></div>

  <div class="faq">
    <h2>Questions fréquentes</h2>
    <div class="faq-item">
      <div class="faq-q" onclick="toggle(this)">Les prix peuvent-ils changer après la réservation ?<i class="ti ti-chevron-down faq-chevron" aria-hidden="true"></i></div>
      <div class="faq-a">Non. Le tarif est verrouillé au moment de la réservation. Aucun frais supplémentaire ne peut s'appliquer après confirmation.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q" onclick="toggle(this)">Puis-je annuler ma réservation ?<i class="ti ti-chevron-down faq-chevron" aria-hidden="true"></i></div>
      <div class="faq-a">Oui. Les membres Premium bénéficient d'une annulation flexible sans frais. Les membres Basic peuvent annuler selon les conditions du trajet.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q" onclick="toggle(this)">Comment utiliser un code promo ?<i class="ti ti-chevron-down faq-chevron" aria-hidden="true"></i></div>
      <div class="faq-a">Entrez votre code lors de la confirmation de réservation. La réduction est appliquée automatiquement avant le paiement.</div>
    </div>
    <div class="faq-item">
      <div class="faq-q" onclick="toggle(this)">La formule Premium est-elle sans engagement ?<i class="ti ti-chevron-down faq-chevron" aria-hidden="true"></i></div>
      <div class="faq-a">Oui, vous pouvez résilier à tout moment. L'abonnement reste actif jusqu'à la fin de la période payée.</div>
    </div>
  </div>

  <div class="cta">
    <h2>Prêt à voyager ou envoyer ?</h2>
    <p>Réservez en moins d'une minute, simple et sécurisé</p>
    <button class="cta-btn" onclick="sendPrompt('Je veux réserver un trajet sur Yoon bu Gaw')">Réserver maintenant →</button>
  </div>

</div>


<?php include_once('../includes/footer.php'); ?>
<script>
function toggle(el){
  const a=el.nextElementSibling,c=el.querySelector('.faq-chevron');
  a.classList.toggle('open');c.classList.toggle('rot');
}

const routes={
  thies:{line:'l-dk-th',route:'Dakar ↔ Thiès',detail:'Berline • 1h30 environ',price:'2 500 F'},
  mbour:{line:'l-dk-mb',route:'Dakar ↔ Mbour',detail:'Berline • 1h45 environ',price:'3 500 F'},
  saintlouis:{line:'l-dk-sl',route:'Dakar ↔ Saint-Louis',detail:'SUV • 4h environ',price:'7 000 F'},
  touba:{line:'l-dk-tb',route:'Dakar ↔ Touba',detail:'Berline • 3h environ',price:'5 000 F'},
};

function selectCity(id,route,detail,price){
  document.querySelectorAll('.route-line').forEach(l=>l.classList.remove('active'));
  const box=document.getElementById('infoBox');
  if(id==='dakar'){box.classList.remove('show');return;}
  const r=routes[id];
  if(!r)return;
  document.getElementById(r.line).classList.add('active');
  document.getElementById('infoRoute').textContent=r.route;
  document.getElementById('infoDetail').textContent=r.detail;
  document.getElementById('infoPrice').textContent=r.price;
  box.classList.add('show');
}
</script>

</body>
</html>