
const SVC_ICONS = {
  taxi:     '<i class="bi bi-taxi-front-fill"></i>',
  bus:      '<i class="bi bi-bus-front-fill"></i>',
  cargo:    '<i class="bi bi-truck-front-fill"></i>',
  location: '<i class="bi bi-car-front-fill"></i>',
};

/* Véhicules statiques de fallback si BDD vide */
const VEHICULES_FALLBACK = {
  taxi:[
    {id:1,nom:'Toyota Corolla',mat:'DK-4521-AB',chauffeur:'Ibrahima Diallo',couleur:'Blanche',type:'clando',prix:1200},
    {id:2,nom:'Renault Logan',mat:'DK-7834-CD',chauffeur:'Moussa Gueye',couleur:'Jaune',type:'ddd',prix:2500},
    {id:3,nom:'Hyundai Accent',mat:'DK-1102-EF',chauffeur:'Aminata Seck',couleur:'Blanche',type:'vtc',prix:4500},
  ],
  bus:[
    {id:5,nom:'Dakar Dem Dikk',mat:'DD-2021-AA',chauffeur:'Serigne Mbaye',couleur:'Bleue',places_total:55,classe:'standard',prix_base:3000},
    {id:6,nom:'Les Rapides du Sahel',mat:'LRS-441-BB',chauffeur:'Cheikh Diop',couleur:'Verte',places_total:35,classe:'vip',prix_base:7500},
    {id:7,nom:'Trans-Afric Express',mat:'TAE-109-CC',chauffeur:'Modou Ndiaye',couleur:'Blanche',places_total:50,classe:'vip',prix_base:8500},
  ],
  cargo:[
    {id:8,nom:'Camionnette Bâchée',mat:'DK-9901-CA',chauffeur:'Papa Diop',couleur:'Blanche',capacite:'1T',prix_km:80,base:15000},
    {id:9,nom:'Camion 10 Tonnes',mat:'DK-5543-CB',chauffeur:'Lamine Sarr',couleur:'Rouge',capacite:'10T',prix_km:120,base:35000},
    {id:10,nom:'Semi-Remorque 30T',mat:'DK-2210-CC',chauffeur:'Abdoulaye Ba',couleur:'Bleue',capacite:'30T',prix_km:180,base:80000},
  ],
  location:[
    {id:11,nom:'Toyota Yaris',mat:'DK-3301-LA',type:'Citadine',places:5,prix_jour:25000,caution:100000},
    {id:12,nom:'Renault Duster',mat:'DK-7712-LB',type:'SUV',places:5,prix_jour:45000,caution:200000},
    {id:13,nom:'Mercedes Class E',mat:'DK-0099-LC',type:'Berline premium',places:5,prix_jour:80000,caution:350000},
    {id:14,nom:'Minibus 15 places',mat:'DK-5588-LD',type:'Minibus',places:15,prix_jour:60000,caution:250000},
  ]
};


function getVehicules(type) {
  const fromBDD = (VEHICULES_BDD[type] || []).map((v, i) => {
    const base = {
      id:        v.id || (1000 + i),
      nom:       v.nom,
      mat:       v.matricule,
      chauffeur: v.chauffeur,
      couleur:   v.couleur,
      photo:     v.photo,
    };

    if (type === 'cargo') {
      /* Lire les champs BDD s'ils existent, sinon deviner selon le nom */
      let capacite = v.capacite || null;
      let prix_km  = parseInt(v.prix_km)  || 0;
      let prixBase = parseInt(v.prix_base) || parseInt(v.base) || 0;
      const nomLower = (v.nom || '').toLowerCase();

      if (!prix_km || !prixBase) {
        if (nomLower.includes('semi') || nomLower.includes('30t') || nomLower.includes('30 t')) {
          capacite = capacite || '30T'; prix_km = prix_km || 180; prixBase = prixBase || 80000;
        } else if (nomLower.includes('10t') || nomLower.includes('10 t') || nomLower.includes('camion')) {
          capacite = capacite || '10T'; prix_km = prix_km || 120; prixBase = prixBase || 35000;
        } else {
          capacite = capacite || '1T';  prix_km = prix_km || 80;  prixBase = prixBase || 15000;
        }
      }
      capacite = capacite || '1T';

      return { ...base, type: 'cargo', capacite, prix_km, base: prixBase };
    }

    if (type === 'location') {
      /* Lire les champs BDD s'ils existent, sinon deviner selon le nom */
      let prix_jour   = parseInt(v.prix_jour)    || 0;
      let caution     = parseInt(v.caution)       || 0;
      let places      = parseInt(v.places)        || 5;
      let locType     = v.type_vehicule || v.categorie || v.type || null;
      const nomLower  = (v.nom || '').toLowerCase();

      if (!prix_jour) {
        if (nomLower.includes('mercedes') || nomLower.includes('bmw') || nomLower.includes('audi') ||
            nomLower.includes('premium')  || nomLower.includes('class e') || nomLower.includes('classe')) {
          prix_jour = 80000; caution = 350000; locType = locType || 'Berline premium';
        } else if (nomLower.includes('suv') || nomLower.includes('4x4') || nomLower.includes('duster') ||
                   nomLower.includes('rav4') || nomLower.includes('hilux') || nomLower.includes('land')) {
          prix_jour = 45000; caution = 200000; locType = locType || 'SUV';
        } else if (nomLower.includes('minibus') || nomLower.includes('van') ||
                   nomLower.includes('15 place') || nomLower.includes('bus')) {
          prix_jour = 60000; caution = 250000; places = places > 5 ? places : 15; locType = locType || 'Minibus';
        } else {
          prix_jour = 25000; caution = 100000; locType = locType || 'Citadine';
        }
      }

      return { ...base, type: locType, places, prix_jour, caution };
    }

    if (type === 'taxi') {
      return {
        ...base,
        type:  v.type_taxi || v.type || 'clando',
        prix:  parseInt(v.prix) || 2000,
        couleur: v.couleur,
      };
    }

    if (type === 'bus') {
      return {
        ...base,
        prix_base:    parseInt(v.prix_base) || 3000,
        places_total: parseInt(v.places_total) || 50,
        classe:       v.classe || 'standard',
        couleur:      v.couleur,
      };
    }

    return base;
  });

  return fromBDD.length > 0 ? fromBDD : (VEHICULES_FALLBACK[type] || []);
}

const SVC_COLORS = { taxi:'amber', bus:'blue', cargo:'amber', location:'green' };
const SVC_NAMES  = { taxi:'Taxis en ville', bus:'Bus interurbains', cargo:'Transport cargo', location:'Location automobile' };
const PAY_LABELS = { wave:'Wave Mobile', om:'Orange Money', card:'Carte bancaire' };

const VILLES = [
  'Dakar – Plateau','Dakar – Médina','Dakar – Gorée','Dakar – Gueule Tapée',
  'Dakar – HLM','Dakar – Grand Dakar','Dakar – Biscuiterie','Dakar – Fass Colobane',
  'Dakar – Almadies','Dakar – Ngor','Dakar – Ouakam','Dakar – Yoff','Dakar – Mermoz',
  'Dakar – Sacré-Cœur','Dakar – Point E','Dakar – Fann','Dakar – Liberté',
  'Dakar – Grand Yoff','Dakar – Parcelles Assainies','Dakar – Cambérène',
  'Dakar – Pikine','Dakar – Guinaw Rails','Dakar – Thiaroye','Dakar – Yeumbeul',
  'Dakar – Keur Massar','Dakar – Bambilor','Dakar – Guédiawaye','Dakar – Sam Notaire',
  'Dakar – Rufisque','Dakar – Bargny','Dakar – Diamniadio','Dakar – Sébikhotane',
  'Dakar – Sangalkam','Dakar – Yène','Dakar – Toubab Dialaw',
  'Thiès – Ville','Thiès – Diacksao','Thiès – Nguinth',
  'Mbour','Saly','Nguékhokh','Sindia','Popenguine','Joal-Fadiouth','Nianing',
  'Tivaouane','Pout','Khombole','Mékhé','Thilmakha','Mérina Dakhar',
  'Kayar','Fissel','Ndiaganiao','Sessène','Notto Gouye Diama',
  'Thiénaba','Taïba Ndiaye','Pambal',
  'Diourbel – Ville','Touba','Mbacké','Bambey','Ndindy',
  'Saint-Louis – Ville','Saint-Louis – Sor','Saint-Louis – Île',
  'Dagana','Podor','Richard-Toll','Rosso-Sénégal','Ndioum',
  'Matam','Kanel','Bakel','Ourossogui',
  'Louga – Ville','Kébémer','Linguère','Dahra',
  'Kaolack – Ville','Nioro du Rip','Guinguinéo','Ndoffane',
  'Fatick – Ville','Foundiougne','Gossas','Sokone','Toubacouta',
  'Kaffrine – Ville','Koungheul','Birkelane','Malem-Hoddar',
  'Kolda – Ville','Vélingara','Médina Yoro Foulah',
  'Sédhiou – Ville','Goudomp','Bounkiling','Marsassoum',
  'Ziguinchor – Ville','Bignona','Oussouye','Kabrousse','Kafountine',
  'Tambacounda – Ville','Goudiry','Koumpentoum','Saraya','Kidira',
  'Kédougou – Ville','Salémata','Fongolimbi',
];

const BUS_DESTINATIONS = {
  'Dakar – Pikine':15,'Dakar – Guédiawaye':18,'Dakar – Thiaroye':22,
  'Dakar – Yeumbeul':28,'Dakar – Keur Massar':32,'Dakar – Bambilor':38,
  'Dakar – Rufisque':45,'Dakar – Bargny':52,'Dakar – Diamniadio':35,
  'Dakar – Sébikhotane':42,'Dakar – Sangalkam':48,
  'Thiès – Ville':70,'Mbour':82,'Saly':90,'Nguékhokh':72,'Sindia':65,
  'Popenguine':95,'Joal-Fadiouth':114,'Tivaouane':95,'Pout':55,
  'Khombole':90,'Mékhé':110,'Thilmakha':118,'Kayar':55,
  'Fissel':105,'Ndiaganiao':90,'Notto Gouye Diama':80,
  'Diourbel – Ville':142,'Touba':194,'Mbacké':188,'Bambey':148,
  'Saint-Louis – Ville':260,'Dagana':290,'Podor':320,
  'Richard-Toll':295,'Rosso-Sénégal':308,'Ndioum':345,
  'Matam':500,'Kanel':540,'Bakel':620,'Ourossogui':520,
  'Louga – Ville':185,'Kébémer':150,'Linguère':280,'Dahra':250,
  'Kaolack – Ville':195,'Nioro du Rip':245,'Guinguinéo':210,'Ndoffane':225,
  'Fatick – Ville':185,'Foundiougne':200,'Gossas':185,'Sokone':205,'Toubacouta':245,
  'Kaffrine – Ville':250,'Koungheul':285,'Birkelane':270,'Malem-Hoddar':310,
  'Kolda – Ville':530,'Vélingara':480,'Médina Yoro Foulah':560,
  'Sédhiou – Ville':420,'Goudomp':440,'Bounkiling':400,'Marsassoum':390,
  'Ziguinchor – Ville':488,'Bignona':460,'Oussouye':510,'Kafountine':480,
  'Tambacounda – Ville':468,'Goudiry':550,'Koumpentoum':380,'Saraya':590,'Kidira':650,
  'Kédougou – Ville':700,'Salémata':720,'Fongolimbi':740,
};

const HEURES_TAXI  = ['Immédiat','Dans 15 min','Dans 30 min','Dans 1h','Programmer'];
const HEURES_BUS_M = ['05:30','06:00','06:30','07:00','07:30','08:00','09:00','10:00','11:00'];
const HEURES_BUS_S = ['14:00','15:00','16:00','17:00','18:00','19:00','20:00','21:00'];
const HEURES_CARGO = ['Immédiat','Dans 2h','Demain matin','Demain après-midi','Programmer'];
const HEURES_LOC   = ['08:00','09:00','10:00','11:00','12:00','14:00','16:00','18:00'];

const TAXI_CHIPS = [
  ['Plateau','Dakar – Plateau'],['Médina','Dakar – Médina'],['Gorée','Dakar – Gorée'],
  ['Gueule Tapée','Dakar – Gueule Tapée'],['HLM','Dakar – HLM'],['Grand Dakar','Dakar – Grand Dakar'],
  ['Almadies','Dakar – Almadies'],['Ngor','Dakar – Ngor'],['Ouakam','Dakar – Ouakam'],
  ['Yoff','Dakar – Yoff'],['Mermoz','Dakar – Mermoz'],['Grand Yoff','Dakar – Grand Yoff'],
  ['Parcelles Assainies','Dakar – Parcelles Assainies'],['Cambérène','Dakar – Cambérène'],
  ['Pikine','Dakar – Pikine'],['Thiaroye','Dakar – Thiaroye'],['Yeumbeul','Dakar – Yeumbeul'],
  ['Keur Massar','Dakar – Keur Massar'],['Guédiawaye','Dakar – Guédiawaye'],
  ['Rufisque','Dakar – Rufisque'],['Bargny','Dakar – Bargny'],['Diamniadio','Dakar – Diamniadio'],
  ['Sébikhotane','Dakar – Sébikhotane'],['Bambilor','Dakar – Bambilor'],
  ['Thiès','Thiès – Ville'],['Tivaouane','Tivaouane'],['Mbour','Mbour'],['Saly','Saly'],
  ['Nguékhokh','Nguékhokh'],['Sindia','Sindia'],['Popenguine','Popenguine'],['Joal-Fadiouth','Joal-Fadiouth'],
  ['Pout','Pout'],['Khombole','Khombole'],['Mékhé','Mékhé'],['Kayar','Kayar'],
  ['Diourbel','Diourbel – Ville'],['Touba','Touba'],['Mbacké','Mbacké'],['Bambey','Bambey'],
  ['Saint-Louis','Saint-Louis – Ville'],['Dagana','Dagana'],['Podor','Podor'],['Richard-Toll','Richard-Toll'],
  ['Matam','Matam'],['Kanel','Kanel'],['Bakel','Bakel'],['Ourossogui','Ourossogui'],
  ['Kaolack','Kaolack – Ville'],['Nioro du Rip','Nioro du Rip'],['Guinguinéo','Guinguinéo'],
  ['Fatick','Fatick – Ville'],['Foundiougne','Foundiougne'],['Gossas','Gossas'],['Sokone','Sokone'],
  ['Kaffrine','Kaffrine – Ville'],['Koungheul','Koungheul'],['Birkelane','Birkelane'],
  ['Louga','Louga – Ville'],['Kébémer','Kébémer'],['Linguère','Linguère'],['Dahra','Dahra'],
  ['Kolda','Kolda – Ville'],['Vélingara','Vélingara'],
  ['Ziguinchor','Ziguinchor – Ville'],['Bignona','Bignona'],['Oussouye','Oussouye'],
  ['Sédhiou','Sédhiou – Ville'],['Goudomp','Goudomp'],['Bounkiling','Bounkiling'],
  ['Tambacounda','Tambacounda – Ville'],['Goudiry','Goudiry'],['Kidira','Kidira'],
  ['Kédougou','Kédougou – Ville'],['Salémata','Salémata'],
];

let S = {
  service:'location', step:1,
  depart:'', dest:'', heure:'', veh:null, pay:'wave',
  nb_places:1, seats_sel:[], periode:'matin', bus_date:'',
  nom_bagage:'', poids:'', nb_colis:1, nature:'',
  date_debut:'', date_fin:'', nb_jours:1,
  avec_chauffeur:false, loc_type:'Tous', loc_notes:'',
  montant:0,
};


function fmt(n){ return Math.round(n).toLocaleString('fr-SN'); }
function uid(){ return 'YBG-'+Math.random().toString(36).substr(2,8).toUpperCase(); }
function color(){ return SVC_COLORS[S.service]; }

/* Styles de fond par onglet */
const TAB_BG = {
  taxi:     'background:#F78E0C',
  bus:      'background:#0000b8',
  cargo:    'background:#011728',
  location: 'background:#07882b',
};

function switchService(s){
  S.service=s; S.step=1; S.veh=null; S.pay='wave';
  S.depart=''; S.dest=''; S.heure='';
  S.seats_sel=[]; S.nb_places=1; S.montant=0;

  ['taxi','bus','cargo','location'].forEach(x=>{
    const el=document.getElementById('tab-'+x);
    if(!el) return;
    el.className='svc-tab text-white';
    el.setAttribute('style', TAB_BG[x]);
    if(x===s) el.classList.add('active-'+SVC_COLORS[x]);
  });

  document.querySelectorAll('.mnav-item').forEach(b=>b.classList.remove('active'));
  const mnavMap={taxi:'mnav-taxi',bus:'mnav-bus',cargo:'mnav-cargo',location:'mnav-location'};
  const activeBtn=document.getElementById(mnavMap[s]);
  if(activeBtn) activeBtn.classList.add('active');

  render();
  document.getElementById('res-main-content')?.scrollIntoView({behavior:'smooth',block:'start'});
}

function quickStart(service, dest){
  switchService(service);
  setTimeout(()=>{ S.dest=dest; render(); }, 100);
}

function calcMontant(){
  if(!S.veh) return;
  const v=S.veh;
  if(S.service==='taxi')         S.montant = v.prix || 2000;
  else if(S.service==='bus')     S.montant = (v.prix_base||3000) * S.nb_places;
  else if(S.service==='cargo')   S.montant = (v.base||15000) + (v.prix_km||80) * (BUS_DESTINATIONS[S.dest]||100);
  else if(S.service==='location'){
    let total = (v.prix_jour||25000) * S.nb_jours;
    if(S.avec_chauffeur) total += 5000 * S.nb_jours;
    S.montant = total;
  }
}

function gotoStep(n){ S.step=n; render(); }
function changeNb(d){ S.nb_places=Math.max(1,Math.min(10,S.nb_places+d)); calcMontant(); render(); }

function calcJours(){
  if(S.date_debut && S.date_fin){
    const ms = new Date(S.date_fin) - new Date(S.date_debut);
    S.nb_jours = Math.max(1, Math.round(ms/86400000));
  }
  if(S.veh) calcMontant();
  render();
}

function selHeure(h){ S.heure=h; render(); }

function selPay(p){
  S.pay=p;
  document.querySelectorAll('.res-pay-card').forEach(c=>c.classList.remove('sel'));
  document.getElementById('pay-'+p)?.classList.add('sel');
  const pw=document.getElementById('phone-wrap');
  const cw=document.getElementById('card-wrap');
  if(pw) pw.style.display = p!=='card' ? 'block' : 'none';
  if(cw) cw.style.display = p==='card'  ? 'block' : 'none';
}

function selSeat(n){
  const idx=S.seats_sel.indexOf(n);
  if(idx>=0) S.seats_sel.splice(idx,1);
  else if(S.seats_sel.length < S.nb_places) S.seats_sel.push(n);
  render();
}

function selVeh(id){
  const vehs=getVehicules(S.service);
  S.veh=vehs.find(v=>v.id===id)||null;
  calcMontant(); render();
}

/* ── AUTOCOMPLETE ── */
function showAc(id){
  const el=document.getElementById(id);
  const q=el?el.value:'';
  const list=document.getElementById('ac-'+id);
  if(!list) return;
  const m=VILLES.filter(v=>v.toLowerCase().includes(q.toLowerCase())).slice(0,6);
  if(!q||!m.length){ list.style.display='none'; return; }
  list.innerHTML=m.map(v=>`<div class="res-ac-item" onmousedown="pickAc('${id}','${v.replace(/'/g,"\\'")}')">${v}</div>`).join('');
  list.style.display='block';
}
function hideAc(id,ms){ setTimeout(()=>{ const l=document.getElementById('ac-'+id); if(l) l.style.display='none'; },ms); }
function pickAc(id,val){
  const el=document.getElementById(id);
  if(el) el.value=val;
  hideAc(id,0);
  if(id==='inp-depart') S.depart=val;
  if(id==='inp-dest')   S.dest=val;
  updateMapVisu();
}

/* ── MAP VISUEL ── */
function mapHtml(){
  if(!S.depart||!S.dest) return '';
  return `<div class="res-map-box">
    <div class="res-map-badge"><i class="bi bi-geo-alt-fill"></i> ${S.depart} → ${S.dest}</div>
    <div class="res-map-dot-a"></div>
    <div class="res-map-dot-b"></div>
    <div class="res-map-line"></div>
    <i class="bi bi-map" style="font-size:28px;opacity:.12"></i>
    <span style="font-size:12px;font-weight:600;color:#94a3b8">Trajet visualisé</span>
  </div>`;
}
function updateMapVisu(){
  const m=document.getElementById('map-visu');
  if(m) m.innerHTML=mapHtml();
}


function stepsBar(labels){
  const c=color();
  return `<div class="res-steps">${labels.map((l,i)=>{
    const n=i+1, done=n<S.step, active=n===S.step;
    return `<div class="res-step${active?' active-'+c:done?' done':''}">
      <div class="res-step-num">${done?'<i class="bi bi-check-lg"></i>':n}</div><br>${l}
    </div>`;
  }).join('')}</div>`;
}

function acHtml(id,ph,val=''){
  return `<div class="res-ac-wrap">
    <input type="text" id="${id}" placeholder="${ph}" value="${val}"
      oninput="showAc('${id}')" onblur="hideAc('${id}',300)" autocomplete="off">
    <div class="res-ac-list" id="ac-${id}" style="display:none"></div>
  </div>`;
}

function heuresHtml(list,extra=''){
  return `<div class="time-grid">${list.map(h=>`
    <div class="time-btn${S.heure===h?' sel-t '+extra:''}" onclick="selHeure('${h}')">
      <i class="bi bi-clock"></i> ${h}
    </div>`).join('')}</div>`;
}

function vehIconHtml(service){
  const icons={
    taxi:     '<i class="bi bi-taxi-front-fill" style="font-size:40px"></i>',
    bus:      '<i class="bi bi-bus-front-fill" style="font-size:40px"></i>',
    cargo:    '<i class="bi bi-truck-front-fill" style="font-size:40px"></i>',
    location: '<i class="bi bi-car-front-fill" style="font-size:40px"></i>',
  };
  return icons[service] || '<i class="bi bi-car-front" style="font-size:40px"></i>';
}

/* ── Cartes véhicules génériques (taxi, bus, cargo) ── */
function vehiculesHtml() {
  const vehs = getVehicules(S.service);
  const c = color();

  if (!vehs.length) {
    return `
      <div style="display:flex;flex-wrap:wrap;gap:12px;">
        Aucun véhicule disponible pour ce service.
      </div>
    `;
  }

  return `
    <div style="display:flex;flex-wrap:wrap;gap:12px;">
      ${vehs.map(v => {

        const sel = S.veh && S.veh.id === v.id;

        let prixDisplay = '';

        if (S.service === 'taxi') {
          prixDisplay = fmt(v.prix || 2000) + ' FCFA';
        }
        else if (S.service === 'bus') {
          prixDisplay = fmt(v.prix_base || 3000) + ' FCFA/pers';
        }
        else if (S.service === 'cargo') {
          prixDisplay = fmt(v.base || 15000) + ' F + ' + (v.prix_km || 80) + ' F/km';
        }
        else {
          prixDisplay = fmt(v.prix_jour || 25000) + ' FCFA/jour';
        }

        const priceCls =
          c === 'blue' ? 'res-val-blue' :
          c === 'amber' ? 'res-val-amber' :
          'res-val-green';

        const detail =
          S.service === 'bus'
            ? (v.classe || 'standard').toUpperCase() + ' · ' + (v.places_total || 50) + ' places'
          : S.service === 'cargo'
            ? (v.capacite || '—') + ' · ' + v.couleur
          : S.service === 'location'
            ? (v.type || '—') + ' · ' + (v.places || 5) + ' places'
          : (v.type || 'taxi').toUpperCase() + ' · ' + v.couleur;

        const photoHtml = v.photo
          ? `<img class="res-veh-photo"
                  src="../${v.photo}"
                  alt="${v.nom}"
                  onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
          : '';

        const placeholderStyle = v.photo ? 'display:none' : '';

        return `
          <div
            class="res-veh-card${sel ? ' sel-' + c : ''}"
            style="
              width:calc(33.333% - 8px);
              min-width:220px;
              box-sizing:border-box;
            "
            onclick="selVeh(${v.id})"
          >

            <span class="res-veh-badge">
              <i class="bi bi-circle-fill"
                 style="font-size:7px;color:#22c55e;vertical-align:middle"></i>
              Disponible
            </span>

            ${photoHtml}

            <div class="res-veh-emoji" style="${placeholderStyle}">
              ${vehIconHtml(S.service)}
            </div>

            <div class="res-veh-body">

              <div class="res-veh-name">
                ${v.nom}
              </div>

              <div class="res-veh-detail">
                <i class="bi bi-card-text"></i>
                ${v.mat} · ${detail}
              </div>

              ${S.service !== 'location'
                ? `
                  <div class="res-veh-detail">
                    <i class="bi bi-person-fill"></i>
                    ${v.chauffeur || '—'}
                  </div>
                `
                : ''
              }

              <div class="res-veh-price ${priceCls}">
                ${prixDisplay}
              </div>

            </div>

            <div class="res-veh-check">
              ${sel ? '<i class="bi bi-check-circle-fill"></i>' : ''}
            </div>

          </div>
        `;
      }).join('')}
    </div>
  `;
}
function vehiculesLocationHtml() {
  const vehs = getVehicules('location');
  const c = color();

  if (!vehs.length) {
    return `
      <div style="display:flex;flex-wrap:wrap;gap:12px;">
        Aucun véhicule disponible.
      </div>
    `;
  }

  return `
    <div style="display:flex;flex-wrap:wrap;gap:12px;">
      ${vehs.map(v => {

        const prixTotal = (v.prix_jour || 25000) * S.nb_jours;
        const sel = S.veh && S.veh.id === v.id;

        const photoHtml = v.photo
          ? `<img class="res-veh-photo"
                  src="../${v.photo}"
                  alt="${v.nom}"
                  onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">`
          : '';

        const placeholderStyle = v.photo ? 'display:none' : '';

        return `
          <div
            class="res-veh-card${sel ? ' sel-' + c : ''}"
            style="
              width:calc(33.333% - 8px);
              min-width:220px;
              box-sizing:border-box;
            "
            onclick="selVeh(${v.id})"
          >

            <span class="res-veh-badge">
              <i class="bi bi-circle-fill"
                 style="font-size:7px;color:#22c55e;vertical-align:middle"></i>
              Disponible
            </span>

            ${photoHtml}

            <div class="res-veh-emoji" style="${placeholderStyle}">
              <i class="bi bi-car-front-fill" style="font-size:40px"></i>
            </div>

            <div class="res-veh-body">

              <div class="res-veh-name">
                ${v.nom}
              </div>

              <div class="res-veh-detail">
                <i class="bi bi-card-text"></i>
                ${v.mat} · ${v.type || '—'} ·
                <i class="bi bi-people-fill"></i>
                ${v.places || 5} places
              </div>

              <div class="res-veh-detail">
                <i class="bi bi-shield-fill-check"></i>
                Caution : ${fmt(v.caution || 100000)} FCFA
              </div>

              <div class="res-veh-price res-val-green">
                ${fmt(v.prix_jour || 25000)} F/j ·
                Total <strong>${fmt(prixTotal)}</strong> FCFA
              </div>

            </div>

            <div class="res-veh-check">
              ${sel ? '<i class="bi bi-check-circle-fill"></i>' : ''}
            </div>

          </div>
        `;
      }).join('')}
    </div>
  `;
}

function seatsHtml(total){
  const taken=[3,7,11,15,18,22,25,30,34,38,41,45];
  let html='';
  for(let i=1;i<=total;i++){
    const t=taken.includes(i), sel=S.seats_sel.includes(i);
    html+=`<div class="seat${t?' taken':sel?' sel-seat':''}" onclick="${t?'':'selSeat('+i+')'}">${i}</div>`;
  }
  return `<div class="seat-grid">${html}</div>`;
}

function payHtml(){
  return `<div class="res-pay-grid">
    <div class="res-pay-card${S.pay==='wave'?' sel':''}" id="pay-wave" onclick="selPay('wave')">
      <span class="res-pay-icon"><i class="fa-brands fa-wave-square" style="color:#1A8BEF;font-size:26px"></i></span>
      <div class="res-pay-name">Wave</div><div class="res-pay-sub">Mobile</div>
    </div>
    <div class="res-pay-card${S.pay==='om'?' sel':''}" id="pay-om" onclick="selPay('om')">
      <span class="res-pay-icon"><i class="fa-brands fa-app-store" style="color:#FF6600;font-size:26px"></i></span>
      <div class="res-pay-name">Orange Money</div><div class="res-pay-sub">Mobile</div>
    </div>
    <div class="res-pay-card${S.pay==='card'?' sel':''}" id="pay-card" onclick="selPay('card')">
      <span class="res-pay-icon"><i class="bi bi-credit-card-fill" style="color:#3b82f6;font-size:26px"></i></span>
      <div class="res-pay-name">Carte bancaire</div><div class="res-pay-sub">Visa/MC</div>
    </div>
  </div>
  <div id="phone-wrap" class="res-fg" ${S.pay==='card'?'style="display:none"':''}>
    <label><i class="bi bi-phone-fill"></i> Numéro de téléphone</label>
    <input type="tel" id="pay-phone" placeholder="77 123 45 67" maxlength="14">
  </div>
  <div id="card-wrap" class="res-fg" ${S.pay!=='card'?'style="display:none"':''}>
    <label><i class="bi bi-credit-card-2-front-fill"></i> Numéro de carte</label>
    <input type="text" id="pay-card-num" placeholder="XXXX XXXX XXXX XXXX" maxlength="19">
  </div>`;
}

function recuHtml(){
  const ref=uid();
  const c=color();
  const valCls=`res-val-${c}`;
  const rows=[
    ['<i class="bi bi-hash"></i> N° réservation',`<span class="res-tk-mono">${ref}</span>`],
    ['<i class="bi bi-grid-fill"></i> Service',SVC_NAMES[S.service]],
    ['<i class="bi bi-arrow-left-right"></i> Trajet / Période',
      S.service==='location'?`${S.date_debut} → ${S.date_fin} (${S.nb_jours}j)`:`${S.depart} → ${S.dest}`],
    ['<i class="bi bi-car-front-fill"></i> Véhicule',S.veh?S.veh.nom+' ('+S.veh.mat+')':'—'],
    ...(S.service!=='location'?[['<i class="bi bi-person-fill"></i> Chauffeur',S.veh?.chauffeur||'—']]:[]),
    ['<i class="bi bi-clock-fill"></i> Départ / Prise en charge',S.heure||'Selon accord'],
    ...(S.service==='bus'?[
      ['<i class="bi bi-grid-3x3"></i> Sièges','N° '+S.seats_sel.join(', ')],
      ['<i class="bi bi-people-fill"></i> Passagers',S.nb_places+' pers.']
    ]:[]),
    ...(S.service==='cargo'?[
      ['<i class="bi bi-box-seam-fill"></i> Bagage',S.nom_bagage||'—'],
      ['<i class="bi bi-speedometer2"></i> Poids',S.poids?S.poids+' kg':'—']
    ]:[]),
    ...(S.service==='location'?[
      ['<i class="bi bi-calendar-range-fill"></i> Durée',S.nb_jours+' jour(s)'],
      ['<i class="bi bi-person-badge-fill"></i> Chauffeur',S.avec_chauffeur?'Inclus':'Non'],
      ['<i class="bi bi-shield-fill-check"></i> Caution',fmt(S.veh?.caution||0)+' FCFA']
    ]:[]),
    ['<i class="bi bi-wallet2"></i> Mode de paiement',PAY_LABELS[S.pay]||S.pay],
    ['<i class="bi bi-cash-coin"></i> Montant total',`<span class="${valCls}" style="font-size:15px">${fmt(S.montant)} FCFA</span>`],
  ];
  return `<div class="res-alert res-alert-success"><i class="bi bi-check-circle-fill"></i> Réservation confirmée ! Votre véhicule est en route.</div>
  <div class="res-ticket">${rows.map(([l,v])=>`<div class="res-ticket-row"><span class="res-tk-label">${l}</span><span class="res-tk-val">${v}</span></div>`).join('')}</div>
  <div class="res-actions">
    <button class="res-btn res-btn-ghost" onclick="switchService('${S.service}')"><i class="bi bi-plus-circle"></i> Nouvelle réservation</button>
    <button class="res-btn res-btn-${c}" onclick="location.href='mes_trajets.php'">Voir mes trajets <i class="bi bi-arrow-right"></i></button>
  </div>`;
}

function payer(){
  const btn=document.getElementById('pay-btn');
  if(btn){ btn.disabled=true; btn.innerHTML='<i class="bi bi-arrow-repeat spin"></i> Traitement…'; }
  const depart=S.depart||'Dakar';
  fetch('save_reservation.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:new URLSearchParams({
      type_transport: S.service,
      service:        SVC_NAMES[S.service],
      depart:         depart,
      destination:    S.dest || (S.service==='location'?S.depart:''),
      montant:        S.montant,
      matricule:      S.veh?.mat || S.veh?.matricule || '',
      chauffeur:      S.veh?.chauffeur || '',
      heure_depart:   S.heure || 'Selon accord',
      mode_paiement:  S.pay,
      nb_places:      S.nb_places,
      seats:          S.seats_sel.join(','),
      date_debut:     S.date_debut || '',
      date_fin:       S.date_fin   || '',
      nb_jours:       S.nb_jours   || 1,
      avec_chauffeur: S.avec_chauffeur ? '1' : '0',
      nom_bagage:     S.nom_bagage || '',
      poids:          S.poids      || '',
    })
  })
  .then(r=>r.json())
  .then(data=>{
    if(data.success){
      const maxStep={taxi:5,bus:6,cargo:6,location:5};
      S.step=maxStep[S.service]; render();
    } else {
      if(btn){ btn.disabled=false; btn.innerHTML='<i class="bi bi-lock-fill"></i> Payer maintenant'; }
      alert('❌ '+(data.message||'Erreur lors de la réservation'));
    }
  })
  .catch(()=>{
    const maxStep={taxi:5,bus:6,cargo:6,location:5};
    S.step=maxStep[S.service]; render();
  });
}


function renderTaxi(){
  const c=color();
  if(S.step===1) return `
    ${stepsBar(['Trajet','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-taxi-front-fill" style="color:#F78E0C"></i> Votre trajet en taxi</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Saisissez le départ et la destination</p>
    <div class="res-fg"><label><i class="bi bi-geo-fill"></i> Point de départ</label>${acHtml('inp-depart','Ex: Dakar – Plateau…',S.depart)}</div>
    <div class="res-fg"><label><i class="bi bi-geo-alt-fill"></i> Destination</label>${acHtml('inp-dest','Ex: Almadies, Thiès…',S.dest)}</div>
    <div id="map-visu">${mapHtml()}</div>
    <div style="margin-bottom:16px">
      <div style="font-size:11px;font-weight:700;color:#64748b;margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em"><i class="bi bi-lightning-charge-fill" style="color:#F78E0C"></i> Destinations rapides</div>
      <div style="display:flex;flex-wrap:wrap;gap:7px">
        ${TAXI_CHIPS.map(([label,dest])=>
          `<span class="quick-chip" onclick="pickAc('inp-dest','${dest.replace(/'/g,"\\'")}');S.dest='${dest.replace(/'/g,"\\'")}';updateMapVisu();render()"><i class="bi bi-taxi-front-fill"></i> ${label}</span>`
        ).join('')}
      </div>
    </div>
    <div class="res-actions">
      <button class="res-btn res-btn-${c} res-btn-full" onclick="S.depart=document.getElementById('inp-depart').value||S.depart;S.dest=document.getElementById('inp-dest').value||S.dest;gotoStep(2)" ${!S.depart||!S.dest?'disabled':''}>Choisir l'heure <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===2) return `
    ${stepsBar(['Trajet','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-clock-fill" style="color:#F78E0C"></i> Heure de départ</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Quand souhaitez-vous partir ?</p>
    <div class="res-fg"><label><i class="bi bi-alarm-fill"></i> Sélectionnez une heure</label>${heuresHtml(HEURES_TAXI,c)}</div>
    ${S.heure==='Programmer'?`<div class="res-fg"><label><i class="bi bi-calendar-event-fill"></i> Date et heure précise</label><input type="datetime-local" id="custom-time" onchange="S.heure=this.value;render()"></div>`:''}
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(1)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="gotoStep(3)" ${!S.heure?'disabled':''}>Choisir un taxi <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===3) return `
    ${stepsBar(['Trajet','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-taxi-front-fill" style="color:#F78E0C"></i> Taxis disponibles</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px"><i class="bi bi-arrow-left-right"></i> ${S.depart} → ${S.dest} · <i class="bi bi-clock"></i> ${S.heure}</p>
    ${vehiculesHtml()}
    ${S.veh?`<div class="res-info-strip">
      <div class="res-info-item"><span class="res-label"><i class="bi bi-geo-fill"></i> Départ</span><span class="res-val">${S.depart}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-geo-alt-fill"></i> Destination</span><span class="res-val">${S.dest}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-clock-fill"></i> Heure</span><span class="res-val res-val-${c}">${S.heure}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-cash-coin"></i> Montant</span><span class="res-val res-val-${c}">${fmt(S.montant)} FCFA</span></div>
    </div>`:''}
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(2)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="gotoStep(4)" ${!S.veh?'disabled':''}>Payer <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===4) return `
    ${stepsBar(['Trajet','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-wallet2" style="color:#F78E0C"></i> Paiement</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Montant total : <strong>${fmt(S.montant)} FCFA</strong></p>
    ${payHtml()}
    <div class="res-info-strip">
      <div class="res-info-item"><span class="res-label"><i class="bi bi-taxi-front-fill"></i> Véhicule</span><span class="res-val">${S.veh.nom}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-card-text"></i> Matricule</span><span class="res-val">${S.veh.mat}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-person-fill"></i> Chauffeur</span><span class="res-val">${S.veh.chauffeur||'—'}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-cash-coin"></i> Montant</span><span class="res-val res-val-${c}">${fmt(S.montant)} FCFA</span></div>
    </div>
    <button class="res-btn res-btn-${c} res-btn-full" id="pay-btn" onclick="payer()"><i class="bi bi-lock-fill"></i> Payer maintenant</button>
    <div class="res-actions" style="margin-top:8px">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(3)"><i class="bi bi-arrow-left"></i> Retour</button>
    </div>`;

  if(S.step===5) return `<div style="padding:10px 0">
    <div class="res-confirm-icon"><i class="bi bi-patch-check-fill" style="font-size:52px;color:#22c55e"></i></div>
    <div class="res-confirm-title">Réservation confirmée !</div>
    <div class="res-confirm-sub">Votre taxi est en route. Présentez ce reçu au chauffeur.</div>
    ${recuHtml()}</div>`;
  return '';
}


function renderBus(){
  const c=color();
  if(S.step===1) return `
    ${stepsBar(['Trajet','Date & Heure','Sièges','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-bus-front-fill" style="color:#0000b8"></i> Bus interurbain</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Trajet, nombre de places et destination</p>
    <div class="res-fg"><label><i class="bi bi-geo-fill"></i> Point de départ</label>${acHtml('inp-depart','Ville de départ',S.depart)}</div>
    <div class="res-fg"><label><i class="bi bi-geo-alt-fill"></i> Destination</label>
      <select id="sel-dest" onchange="S.dest=this.value;updateMapVisu();render()">
        <option value="">-- Choisir la destination --</option>
        ${Object.keys(BUS_DESTINATIONS).map(d=>`<option value="${d}" ${S.dest===d?'selected':''}>${d}</option>`).join('')}
      </select>
    </div>
    <div id="map-visu">${mapHtml()}</div>
    <div class="res-fg"><label><i class="bi bi-people-fill"></i> Nombre de places</label>
      <div class="num-picker">
        <div class="np-btn" onclick="changeNb(-1)"><i class="bi bi-dash-lg"></i></div>
        <div class="np-val">${S.nb_places}</div>
        <div class="np-btn" onclick="changeNb(1)"><i class="bi bi-plus-lg"></i></div>
        <span style="font-size:13px;color:#64748b">personne(s)</span>
      </div>
    </div>
    <div class="res-actions">
      <button class="res-btn res-btn-${c} res-btn-full" onclick="S.depart=document.getElementById('inp-depart').value||S.depart;gotoStep(2)" ${!S.depart||!S.dest?'disabled':''}>Choisir la date <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===2) return `
    ${stepsBar(['Trajet','Date & Heure','Sièges','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-calendar-event-fill" style="color:#0000b8"></i> Date et heure</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">${S.depart||'Dakar'} → ${S.dest} · <i class="bi bi-people-fill"></i> ${S.nb_places} place(s)</p>
    <div class="res-fg"><label><i class="bi bi-calendar3"></i> Date de départ</label>
      <input type="date" id="bus-date" value="${S.bus_date||''}" min="${new Date().toISOString().split('T')[0]}" onchange="S.bus_date=this.value">
    </div>
    <div class="res-fg"><label><i class="bi bi-sun-fill"></i> Période</label>
      <div class="res-toggle">
        <div class="res-toggle-opt${S.periode==='matin'?' active':''}" onclick="S.periode='matin';render()"><i class="bi bi-sunrise-fill"></i> Matin</div>
        <div class="res-toggle-opt${S.periode==='soir'?' active':''}" onclick="S.periode='soir';render()"><i class="bi bi-moon-stars-fill"></i> Soir</div>
      </div>
      ${heuresHtml(S.periode==='matin'?HEURES_BUS_M:HEURES_BUS_S,c)}
    </div>
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(1)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="gotoStep(3)" ${!S.heure?'disabled':''}>Choisir sièges <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===3) return `
    ${stepsBar(['Trajet','Date & Heure','Sièges','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-grid-3x3" style="color:#0000b8"></i> Choisissez vos sièges</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:12px">Sélectionnez ${S.nb_places} siège(s) · <strong>${S.seats_sel.length}/${S.nb_places}</strong> choisi(s)</p>
    <div class="res-alert res-alert-warn"><i class="bi bi-square"></i> Libre &nbsp;·&nbsp; <i class="bi bi-square-fill" style="color:#0000b8"></i> Votre choix &nbsp;·&nbsp; <i class="bi bi-x-square-fill" style="color:#94a3b8"></i> Occupé</div>
    ${seatsHtml(50)}
    <div class="res-actions" style="margin-top:14px">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(2)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="gotoStep(4)" ${S.seats_sel.length<S.nb_places?'disabled':''}>Choisir bus <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===4) return `
    ${stepsBar(['Trajet','Date & Heure','Sièges','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-bus-front-fill" style="color:#0000b8"></i> Bus disponibles</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">${S.depart||'Dakar'} → ${S.dest} · <i class="bi bi-clock"></i> ${S.heure}</p>
    ${vehiculesHtml()}
    ${S.veh?`<div class="res-info-strip">
      <div class="res-info-item"><span class="res-label"><i class="bi bi-grid-3x3"></i> Sièges</span><span class="res-val res-val-${c}">N° ${S.seats_sel.join(', ')}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-people-fill"></i> Passagers</span><span class="res-val">${S.nb_places} pers.</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-clock-fill"></i> Départ</span><span class="res-val res-val-${c}">${S.heure}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-cash-coin"></i> Total</span><span class="res-val res-val-${c}">${fmt(S.montant)} FCFA</span></div>
    </div>`:''}
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(3)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="gotoStep(5)" ${!S.veh?'disabled':''}>Payer <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===5) return `
    ${stepsBar(['Trajet','Date & Heure','Sièges','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-wallet2" style="color:#0000b8"></i> Paiement</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px"><i class="bi bi-people-fill"></i> ${S.nb_places} place(s) · Total : <strong>${fmt(S.montant)} FCFA</strong></p>
    ${payHtml()}
    <div class="res-info-strip">
      <div class="res-info-item"><span class="res-label"><i class="bi bi-bus-front-fill"></i> Bus</span><span class="res-val">${S.veh.nom}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-grid-3x3"></i> Sièges</span><span class="res-val res-val-${c}">N° ${S.seats_sel.join(', ')}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-person-fill"></i> Chauffeur</span><span class="res-val">${S.veh.chauffeur||'—'}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-cash-coin"></i> Total</span><span class="res-val res-val-${c}">${fmt(S.montant)} FCFA</span></div>
    </div>
    <button class="res-btn res-btn-${c} res-btn-full" id="pay-btn" onclick="payer()"><i class="bi bi-lock-fill"></i> Payer maintenant</button>
    <div class="res-actions" style="margin-top:8px">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(4)"><i class="bi bi-arrow-left"></i> Retour</button>
    </div>`;

  if(S.step===6) return `<div style="padding:10px 0">
    <div class="res-confirm-icon"><i class="bi bi-patch-check-fill" style="font-size:52px;color:#22c55e"></i></div>
    <div class="res-confirm-title">Places réservées !</div>
    <div class="res-confirm-sub">Vos billets sont confirmés. Présentez ce reçu à l'embarquement.</div>
    ${recuHtml()}</div>`;
  return '';
}


function renderCargo(){
  const c=color();
  if(S.step===1) return `
    ${stepsBar(['Trajet','Bagage','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-truck-front-fill" style="color:#F59E0B"></i> Transport de marchandises</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Expédition de colis, marchandises, matériaux</p>
    <div class="res-fg"><label><i class="bi bi-geo-fill"></i> Lieu de chargement</label>${acHtml('inp-depart','Lieu de chargement',S.depart)}</div>
    <div class="res-fg"><label><i class="bi bi-geo-alt-fill"></i> Destination</label>
      <select id="sel-dest" onchange="S.dest=this.value;updateMapVisu();render()">
        <option value="">-- Choisir la destination --</option>
        ${Object.keys(BUS_DESTINATIONS).map(d=>`<option value="${d}" ${S.dest===d?'selected':''}>${d}</option>`).join('')}
      </select>
    </div>
    <div id="map-visu">${mapHtml()}</div>
    <div class="res-actions">
      <button class="res-btn res-btn-${c} res-btn-full" onclick="S.depart=document.getElementById('inp-depart').value||S.depart;gotoStep(2)" ${!S.depart||!S.dest?'disabled':''}>Décrire le bagage <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===2) return `
    ${stepsBar(['Trajet','Bagage','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-box-seam-fill" style="color:#F59E0B"></i> Description du bagage</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px"><i class="bi bi-arrow-left-right"></i> ${S.depart} → ${S.dest}</p>
    <div class="res-fg"><label><i class="bi bi-tag-fill"></i> Nom / nature du bagage</label>
      <input type="text"
  id="inp-bagage"
  placeholder="Ex: sacs de riz, colis, matériaux…"
  value="${S.nom_bagage}"
  oninput="S.nom_bagage=this.value; render()">
    </div>
    <div class="res-row2">
      <div class="res-fg"><label><i class="bi bi-speedometer2"></i> Poids estimé (kg)</label>
        <input type="number"
  id="inp-poids"
  placeholder="Ex: 500"
  value="${S.poids}"
  min="1"
  oninput="S.poids=this.value; render()">
      </div>
      <div class="res-fg"><label><i class="bi bi-boxes"></i> Nombre de colis</label>
        <input type="number" id="inp-ncolis" placeholder="Ex: 10"
          value="${S.nb_colis||1}" min="1" oninput="S.nb_colis=this.value; render()">
      </div>
    </div>
    <div class="res-fg"><label><i class="bi bi-list-ul"></i> Nature de la marchandise</label>
      <select id="sel-nature" onchange="S.nature=this.value">
        <option value="">-- Sélectionner --</option>
        ${['Alimentaire','Matériaux de construction','Mobilier','Électroménager','Textile','Animaux','Produits fragiles','Autre']
          .map(n=>`<option value="${n}" ${S.nature===n?'selected':''}>${n}</option>`).join('')}
      </select>
    </div>
    ${S.poids?`<div class="price-range"><i class="bi bi-truck-front-fill"></i> Estimation : entre <strong>${fmt(15000+80*(BUS_DESTINATIONS[S.dest]||100))}</strong> et <strong>${fmt(80000+180*(BUS_DESTINATIONS[S.dest]||100))}</strong> FCFA selon le véhicule</div>`:''}
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(1)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="S.nom_bagage=document.getElementById('inp-bagage').value||S.nom_bagage;S.poids=document.getElementById('inp-poids').value||S.poids;gotoStep(3)" ${!S.nom_bagage||!S.poids?'disabled':''}>Choisir l'heure <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===3) return `
    ${stepsBar(['Trajet','Bagage','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-clock-fill" style="color:#F59E0B"></i> Heure d'enlèvement</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Quand souhaitez-vous le chargement ?</p>
    <div class="res-fg"><label><i class="bi bi-alarm-fill"></i> Disponibilité</label>${heuresHtml(HEURES_CARGO,c)}</div>
    ${S.heure==='Programmer'?`<div class="res-fg"><label><i class="bi bi-calendar-event-fill"></i> Date et heure précise</label><input type="datetime-local" onchange="S.heure=this.value;render()"></div>`:''}
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(2)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="gotoStep(4)" ${!S.heure?'disabled':''}>Voir les véhicules <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===4) return `
    ${stepsBar(['Trajet','Bagage','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-truck-front-fill" style="color:#F59E0B"></i> Véhicules cargo disponibles</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">${S.depart} → ${S.dest} · <i class="bi bi-speedometer2"></i> ${S.poids} kg · <i class="bi bi-clock"></i> ${S.heure}</p>
    ${vehiculesHtml()}
    ${S.veh?`<div class="res-info-strip">
      <div class="res-info-item"><span class="res-label"><i class="bi bi-box-seam-fill"></i> Bagage</span><span class="res-val">${S.nom_bagage}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-speedometer2"></i> Poids</span><span class="res-val">${S.poids} kg</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-diagram-3-fill"></i> Capacité</span><span class="res-val">${S.veh.capacite||'—'}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-cash-coin"></i> Total estimé</span><span class="res-val res-val-${c}">${fmt(S.montant)} FCFA</span></div>
    </div>`:''}
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(3)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="gotoStep(5)" ${!S.veh?'disabled':''}>Payer <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===5) return `
    ${stepsBar(['Trajet','Bagage','Heure','Véhicule','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-wallet2" style="color:#F59E0B"></i> Paiement</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Montant estimé : <strong>${fmt(S.montant)} FCFA</strong></p>
    ${payHtml()}
    <div class="res-info-strip">
      <div class="res-info-item"><span class="res-label"><i class="bi bi-truck-front-fill"></i> Véhicule</span><span class="res-val">${S.veh.nom}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-diagram-3-fill"></i> Capacité</span><span class="res-val">${S.veh.capacite||'—'}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-person-fill"></i> Chauffeur</span><span class="res-val">${S.veh.chauffeur||'—'}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-cash-coin"></i> Total</span><span class="res-val res-val-${c}">${fmt(S.montant)} FCFA</span></div>
    </div>
    <button class="res-btn res-btn-${c} res-btn-full" id="pay-btn" onclick="payer()"><i class="bi bi-lock-fill"></i> Confirmer et payer</button>
    <div class="res-actions" style="margin-top:8px">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(4)"><i class="bi bi-arrow-left"></i> Retour</button>
    </div>`;

  if(S.step===6) return `<div style="padding:10px 0">
    <div class="res-confirm-icon"><i class="bi bi-box-seam-fill" style="font-size:52px;color:#F59E0B"></i></div>
    <div class="res-confirm-title">Expédition confirmée !</div>
    <div class="res-confirm-sub">Votre marchandise est prise en charge. Conservez ce reçu.</div>
    ${recuHtml()}</div>`;
  return '';
}


function renderLocation(){
  const c=color();   /* 'green' */
  const today=new Date().toISOString().split('T')[0];

  if(S.step===1) return `
    ${stepsBar(['Période','Véhicule','Options','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-car-front-fill" style="color:#07882b"></i> Location de véhicule</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Choisissez vos dates et votre véhicule</p>
    <div class="res-row2">
      <div class="res-fg"><label><i class="bi bi-calendar-event-fill"></i> Date de début</label>
        <input type="date" id="loc-debut" value="${S.date_debut||''}" min="${today}"
          onchange="S.date_debut=this.value;calcJours()">
      </div>
      <div class="res-fg"><label><i class="bi bi-calendar-check-fill"></i> Date de fin</label>
        <input type="date" id="loc-fin" value="${S.date_fin||''}" min="${S.date_debut||today}"
          onchange="S.date_fin=this.value;calcJours()">
      </div>
    </div>
    ${S.nb_jours>0&&S.date_debut&&S.date_fin
      ? `<div class="res-alert res-alert-success"><i class="bi bi-calendar-range-fill"></i> Durée : <strong>${S.nb_jours} jour(s)</strong></div>`
      : ''}
    <div class="res-fg"><label><i class="bi bi-geo-fill"></i> Lieu de prise en charge</label>${acHtml('inp-depart','Ex: Dakar – Plateau…',S.depart)}</div>
    <div class="res-fg"><label><i class="bi bi-funnel-fill"></i> Type de véhicule</label>
      <div class="res-toggle">
        ${['Tous','Citadine','SUV','Premium','Minibus'].map(t=>
          `<div class="res-toggle-opt${(S.loc_type||'Tous')===t?' active':''}" onclick="S.loc_type='${t}';render()">${t}</div>`
        ).join('')}
      </div>
    </div>
    <div class="res-actions">
      <button class="res-btn res-btn-${c} res-btn-full"
        onclick="S.depart=document.getElementById('inp-depart').value||S.depart;gotoStep(2)"
        ${!S.date_debut||!S.date_fin||!S.depart?'disabled':''}>Voir les véhicules <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===2){
    /* Filtrer selon le type choisi */
    let vehs = getVehicules('location');
    if(S.loc_type && S.loc_type!=='Tous'){
      const f=S.loc_type.toLowerCase();
      const filtered=vehs.filter(v=>(v.type||'').toLowerCase().includes(f));
      if(filtered.length) vehs=filtered;
    }
    const c2=color();
    const cardsHtml = vehs.length
      ? vehs.map(v=>{
          const prixTotal = (v.prix_jour||25000)*S.nb_jours;
          const sel       = S.veh && S.veh.id===v.id;
          const photoHtml = v.photo ? `<img class="res-veh-photo" src="../${v.photo}" alt="${v.nom}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">` : '';
          const pStyle    = v.photo ? 'display:none' : '';
          return `<div class="res-veh-card${sel?' sel-'+c2:''}" onclick="selVeh(${v.id})">
            <span class="res-veh-badge"><i class="bi bi-circle-fill" style="font-size:7px;color:#22c55e;vertical-align:middle"></i> Disponible</span>
            ${photoHtml}
            <div class="res-veh-emoji" style="${pStyle}"><i class="bi bi-car-front-fill" style="font-size:40px"></i></div>
            <div class="res-veh-body">
              <div class="res-veh-name">${v.nom}</div>
              <div class="res-veh-detail"><i class="bi bi-card-text"></i> ${v.mat} · ${v.type||'—'} · <i class="bi bi-people-fill"></i> ${v.places||5} places</div>
              <div class="res-veh-detail"><i class="bi bi-shield-fill-check"></i> Caution : ${fmt(v.caution||100000)} FCFA</div>
              <div class="res-veh-price res-val-${c2}">${fmt(v.prix_jour||25000)} F/j · <strong>Total ${fmt(prixTotal)} FCFA</strong></div>
            </div>
            <div class="res-veh-check">${sel?'<i class="bi bi-check-circle-fill"></i>':''}</div>
          </div>`;
        }).join('')
      : `<div class="veh-empty-state"><i class="bi bi-car-front" style="font-size:32px"></i> Aucun véhicule disponible pour ce type.</div>`;

    return `
    ${stepsBar(['Période','Véhicule','Options','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-car-front-fill" style="color:#07882b"></i> Choisissez votre véhicule</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px"><i class="bi bi-calendar-range-fill"></i> ${S.nb_jours} jour(s) · à partir du ${S.date_debut} · <i class="bi bi-geo-fill"></i> ${S.depart}</p>
    ${cardsHtml}
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(1)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c2}" onclick="gotoStep(3)" ${!S.veh?'disabled':''}>Options <i class="bi bi-arrow-right"></i></button>
    </div>`;
  }

  if(S.step===3) return `
    ${stepsBar(['Période','Véhicule','Options','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-toggles" style="color:#07882b"></i> Options supplémentaires</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px"><i class="bi bi-car-front-fill"></i> ${S.veh?.nom} · <i class="bi bi-calendar-range-fill"></i> ${S.nb_jours} jour(s)</p>
    <div class="res-fg"><label><i class="bi bi-person-badge-fill"></i> Avec ou sans chauffeur ?</label>
      <div class="res-toggle">
        <div class="res-toggle-opt${!S.avec_chauffeur?' active':''}" onclick="S.avec_chauffeur=false;calcMontant();render()"><i class="bi bi-car-front-fill"></i> Sans chauffeur</div>
        <div class="res-toggle-opt${S.avec_chauffeur?' active':''}" onclick="S.avec_chauffeur=true;calcMontant();render()"><i class="bi bi-person-badge-fill"></i> Avec chauffeur (+5 000 F/j)</div>
      </div>
    </div>
    <div class="res-fg"><label><i class="bi bi-clock-fill"></i> Heure de prise en charge</label>${heuresHtml(HEURES_LOC,c)}</div>
    <div class="res-fg"><label><i class="bi bi-chat-text-fill"></i> Notes particulières (optionnel)</label>
      <textarea id="loc-notes" placeholder="Demandes spéciales, adresse précise…" rows="3"
        style="resize:vertical" oninput="S.loc_notes=this.value">${S.loc_notes||''}</textarea>
    </div>
    <div class="res-info-strip">
      <div class="res-info-item"><span class="res-label"><i class="bi bi-car-front-fill"></i> Véhicule</span><span class="res-val">${S.veh.nom}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-calendar-range-fill"></i> Durée</span><span class="res-val">${S.nb_jours}j</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-person-badge-fill"></i> Chauffeur</span><span class="res-val">${S.avec_chauffeur?'Oui (+'+fmt(5000*S.nb_jours)+' F)':'Non'}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-cash-coin"></i> Total</span><span class="res-val res-val-${c}">${fmt(S.montant)} FCFA</span></div>
    </div>
    <div class="res-actions">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(2)"><i class="bi bi-arrow-left"></i> Retour</button>
      <button class="res-btn res-btn-${c}" onclick="gotoStep(4)" ${!S.heure?'disabled':''}>Payer <i class="bi bi-arrow-right"></i></button>
    </div>`;

  if(S.step===4) return `
    ${stepsBar(['Période','Véhicule','Options','Paiement','Reçu'])}
    <p style="font-size:16px;font-weight:700;margin-bottom:4px"><i class="bi bi-wallet2" style="color:#07882b"></i> Paiement de la location</p>
    <p style="font-size:13px;color:#64748b;margin-bottom:16px">Total : <strong>${fmt(S.montant)} FCFA</strong> + caution ${fmt(S.veh?.caution||0)} FCFA</p>
    ${payHtml()}
    <div class="res-info-strip">
      <div class="res-info-item"><span class="res-label"><i class="bi bi-car-front-fill"></i> Véhicule</span><span class="res-val">${S.veh.nom}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-calendar-range-fill"></i> Période</span><span class="res-val">${S.date_debut} → ${S.date_fin}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-person-badge-fill"></i> Chauffeur</span><span class="res-val">${S.avec_chauffeur?'Inclus':'Non'}</span></div>
      <div class="res-info-item"><span class="res-label"><i class="bi bi-cash-coin"></i> Total</span><span class="res-val res-val-${c}">${fmt(S.montant)} FCFA</span></div>
    </div>
    <div class="res-alert res-alert-warn"><i class="bi bi-info-circle-fill"></i> Caution de ${fmt(S.veh?.caution||0)} FCFA débloquée à la restitution</div>
    <button class="res-btn res-btn-${c} res-btn-full" id="pay-btn" onclick="payer()"><i class="bi bi-lock-fill"></i> Confirmer la location</button>
    <div class="res-actions" style="margin-top:8px">
      <button class="res-btn res-btn-ghost" onclick="gotoStep(3)"><i class="bi bi-arrow-left"></i> Retour</button>
    </div>`;

  if(S.step===5) return `<div style="padding:10px 0">
    <div class="res-confirm-icon"><i class="bi bi-patch-check-fill" style="font-size:52px;color:#07882b"></i></div>
    <div class="res-confirm-title">Location confirmée !</div>
    <div class="res-confirm-sub">Votre véhicule vous attend. Présentez ce reçu à notre agence.</div>
    ${recuHtml()}</div>`;
  return '';
}


function render(){
  const map={taxi:renderTaxi,bus:renderBus,cargo:renderCargo,location:renderLocation};
  const container=document.getElementById('res-main-content');
  if(!container) return;
  container.innerHTML=map[S.service]();
}


if(navigator.geolocation){
  navigator.geolocation.getCurrentPosition(async pos=>{
    try{
      const r=await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${pos.coords.latitude}&lon=${pos.coords.longitude}`);
      const d=await r.json();
      const ville=d.address.city||d.address.town||d.address.village||'Dakar';
      ['desktop-loc','mobile-loc'].forEach(id=>{
        const el=document.getElementById(id); if(el) el.textContent=ville+', Sénégal';
      });
    }catch(e){}
  },()=>{});
}

switchService('location');

document.querySelectorAll('.mnav-item').forEach(b=>{
  b.addEventListener('click',()=>{
    document.querySelectorAll('.mnav-item').forEach(x=>x.classList.remove('active'));
    b.classList.add('active');
  });
});

