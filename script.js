/* particles.js v2.0.0 — MIT */
var pJS = function (e, t) { var a = document.querySelector("#" + e + " > .particles-js-canvas-el"); this.pJS = { canvas: { el: a, w: a.offsetWidth, h: a.offsetHeight }, particles: { number: { value: 80, density: { enable: !0, value_area: 800 } }, color: { value: "#ffffff" }, shape: { type: "circle", stroke: { width: 0, color: "#000000" }, polygon: { nb_sides: 5 }, image: { src: "", width: 100, height: 100 } }, opacity: { value: .5, random: !1, anim: { enable: !1, speed: 1, opacity_min: .1, sync: !1 } }, size: { value: 3, random: !0, anim: { enable: !1, speed: 40, size_min: .1, sync: !1 } }, line_linked: { enable: !0, distance: 150, color: "#ffffff", opacity: .4, width: 1 }, move: { enable: !0, speed: 4, direction: "none", random: !1, straight: !1, out_mode: "out", bounce: !1, attract: { enable: !1, rotateX: 600, rotateY: 1200 } } }, interactivity: { detect_on: "canvas", events: { onhover: { enable: !0, mode: "grab" }, onclick: { enable: !0, mode: "push" }, resize: !0 }, modes: { grab: { distance: 140, line_linked: { opacity: 1 } }, bubble: { distance: 400, size: 40, duration: 2, opacity: 8, speed: 3 }, repulse: { distance: 200, duration: .4 }, push: { particles_nb: 4 }, remove: { particles_nb: 2 } } }, retina_detect: !0, fn: { interact: {}, modes: {}, vendors: {} }, tmp: {} }; var s = this.pJS; t && Object.keys(t).forEach(function (e) { Object.keys(t[e]).forEach(function (a) { s[e][a] = t[e][a] }) }); s.fn.vendors.eventsListeners = function () { s.interactivity.detect_on = "window" === s.interactivity.detect_on ? window : s.canvas.el, "onhover" === s.interactivity.events.onhover.mode && s.interactivity.detect_on.addEventListener("mousemove", s.fn.interact.mouseMoveEvent), "onclick" === s.interactivity.events.onclick.mode && s.interactivity.detect_on.addEventListener("click", s.fn.interact.mouseClickEvent), s.interactivity.detect_on.addEventListener("mouseleave", s.fn.interact.mouseLeaveEvent), s.interactivity.events.resize && window.addEventListener("resize", s.fn.vendors.densityAutoParticles) }; s.fn.vendors.densityAutoParticles = function () { if (s.canvas.el && s.particles.number.density.enable) { s.canvas.w = s.canvas.el.offsetWidth; s.canvas.h = s.canvas.el.offsetHeight; s.canvas.el.width = s.canvas.w * (window.devicePixelRatio || 1); s.canvas.el.height = s.canvas.h * (window.devicePixelRatio || 1) } }; s.fn.vendors.checkBeforeDraw = function () { if ("image" === s.particles.shape.type) { var t = s.particles.shape.image.src; "" === t || void 0 === t ? console.log("Error pJS - No image.src") : s.fn.vendors.loadImg(t) } else s.fn.vendors.draw() }; s.fn.vendors.loadImg = function (t) { s.tmp.img_error = void 0; if ("" !== t) { var a = new Image; a.addEventListener("load", function () { s.tmp.img_obj = a; s.fn.vendors.draw() }), a.src = t } }; s.fn.vendors.draw = function () { s.canvas.ctx = s.canvas.el.getContext("2d"); s.fn.vendors.densityAutoParticles(); s.fn.particle.create(); s.fn.vendors.eventsListeners(); s.fn.vendors.update() }; s.fn.particle = { create: function () { for (var t = 0; t < s.particles.number.value; t++) { s.particles.array.push(new s.fn.particle.constructor(s.particles.color.value, s.particles.opacity.value)) } }, constructor: function (t, a) { this.radius = (Math.random() * s.particles.size.value) + 1; this.x = Math.random() * s.canvas.w; this.y = Math.random() * s.canvas.h; this.color = t; this.opacity = a; this.vx = (Math.random() - .5) * (s.particles.move.speed / 10); this.vy = (Math.random() - .5) * (s.particles.move.speed / 10) }, draw: function () { var t = this; s.canvas.ctx.beginPath(); s.canvas.ctx.arc(t.x, t.y, t.radius, 0, 2 * Math.PI, !1); s.canvas.ctx.fillStyle = "rgba(255,255,255," + t.opacity + ")"; s.canvas.ctx.fill() } }; s.fn.vendors.update = function () { s.canvas.ctx.clearRect(0, 0, s.canvas.w, s.canvas.h); s.particles.array.forEach(function (t) { t.x += t.vx; t.y += t.vy; if (t.x > s.canvas.w || t.x < 0) t.vx = -t.vx; if (t.y > s.canvas.h || t.y < 0) t.vy = -t.vy; t.draw(); s.particles.array.forEach(function (a) { var n = t.x - a.x, i = t.y - a.y, r = Math.sqrt(n * n + i * i); if (r < s.particles.line_linked.distance) { s.canvas.ctx.strokeStyle = "rgba(255,255,255," + (s.particles.line_linked.opacity - (r / s.canvas.line_linked_distance)) + ")"; s.canvas.ctx.lineWidth = s.particles.line_linked.width; s.canvas.ctx.beginPath(); s.canvas.ctx.moveTo(t.x, t.y); s.canvas.ctx.lineTo(a.x, a.y); s.canvas.ctx.stroke() } }) }); s.tmp.drawAnimFrame = requestAnimationFrame(s.fn.vendors.update) }; s.particles = { array: [] }; s.canvas.line_linked_distance = s.particles.line_linked.distance; s.fn.vendors.checkBeforeDraw() }; window.particlesJS = function (e, t) { new pJS(e, t) };

var member = null;

(function() {
  var t = localStorage.getItem("siteTheme") || "light";
  document.documentElement.setAttribute("data-theme", t);
})();

function applyTheme(theme) {
  document.documentElement.setAttribute("data-theme", theme);
  localStorage.setItem("siteTheme", theme);
  document.querySelectorAll(".sf-theme-toggle, .pf-theme-btn").forEach(function(btn) {
    var icon = btn.querySelector("i");
    if (icon) icon.className = theme === "dark" ? "fa-solid fa-sun" : "fa-solid fa-moon";
  });
}

function toggleTheme() {
  applyTheme(localStorage.getItem("siteTheme") === "dark" ? "light" : "dark");
}

window.addEventListener('scroll', function() {
  var heroBg = document.getElementById('hero-bg');
  if (!heroBg) return;
  var scrollY = window.scrollY;
  var heroEl = document.getElementById('hero');
  if (!heroEl) return;
  var heroH = heroEl.offsetHeight;
  if (scrollY <= heroH) {
    var scale = 1 + (scrollY / heroH) * 0.18;
    heroBg.style.transform = 'scale(' + scale + ') translateY(' + (scrollY * 0.15) + 'px)';
  }
}, { passive: true });

var revealObs = new IntersectionObserver(function(entries) {
  entries.forEach(function(e) {
    if (e.isIntersecting) {
      e.target.classList.add('visible');
      revealObs.unobserve(e.target);
    }
  });
}, { threshold: 0.12 });

document.querySelectorAll('.reveal, .reveal-left, .reveal-right').forEach(function(el) {
  revealObs.observe(el);
});

function showToast(msg) {
  var t = document.getElementById('toast');
  if (!t) return;
  t.textContent = msg;
  t.classList.add('show');
  setTimeout(function() { t.classList.remove('show'); }, 3000);
}

function openModal() { showToast('🚫 Not available right now !'); }
function closeModal() {
  var ov = document.getElementById('modal-ov');
  if (ov) ov.classList.remove('open');
}

function handleJoin() {
  var pre = document.getElementById('mf-pre').value.trim();
  var nom = document.getElementById('mf-nom').value.trim();
  var email = document.getElementById('mf-email').value.trim();
  var profil = document.getElementById('mf-profil').value;
  var domaine = document.getElementById('mf-domaine').value;
  if (!pre || !nom) { showToast('⚠️ Veuillez entrer votre prénom et nom.'); return; }
  member = { pre: pre, nom: nom, email: email, profil: profil, domaine: domaine, init: ((pre[0] || 'U') + (nom[0] || 'H')).toUpperCase() };
  closeModal();
  loginUser();
}

function loginUser() {
  var m = member;
  function set(id, val) { var el = document.getElementById(id); if (el) el.textContent = val; }
  function show(id) { var el = document.getElementById(id); if (el) el.style.display = 'flex'; }
  function hide(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }
  set('nav-av', m.init); set('nav-nm', m.pre);
  set('sb-av', m.init); set('sb-name', m.pre + ' ' + m.nom[0] + '.');
  set('sb-role', m.profil || 'Membre');
  set('p-av', m.init); set('p-nm', m.pre + ' ' + m.nom);
  set('p-rl', (m.profil || 'Membre') + ' — UHBC');
  set('pd-pre', m.pre); set('pd-nom', m.nom);
  set('pd-email', m.email || (m.pre.toLowerCase() + '@univ-chlef.dz'));
  set('pd-profil', m.profil || 'Membre');
  set('pd-chip', (m.domaine || 'IA').split('/')[0].trim());
  set('wn', m.pre);
  hide('nav-guest'); show('nav-logged');
  hide('nav-pub'); show('nav-mem');
  var p1 = document.getElementById('page1'); if (p1) p1.classList.remove('active');
  var p2 = document.getElementById('page2'); if (p2) p2.classList.add('active');
  showTab('t-overview');
  showToast('🎉 Bienvenue ' + m.pre + ' ! Vous êtes maintenant membre AI HOUSE.');
}

function handleLogout() {
  member = null;
  function show(id) { var el = document.getElementById(id); if (el) el.style.display = 'flex'; }
  function hide(id) { var el = document.getElementById(id); if (el) el.style.display = 'none'; }
  show('nav-guest'); hide('nav-logged');
  show('nav-pub'); hide('nav-mem');
  var p2 = document.getElementById('page2'); if (p2) p2.classList.remove('active');
  var p1 = document.getElementById('page1'); if (p1) p1.classList.add('active');
  window.scrollTo(0, 0);
  showToast('👋 Déconnecté. À bientôt !');
}

function goHome() {
  if (!member) {
    var p2 = document.getElementById('page2'); if (p2) p2.classList.remove('active');
    var p1 = document.getElementById('page1'); if (p1) p1.classList.add('active');
    window.scrollTo(0, 0);
  }
}

function showTab(id) {
  document.querySelectorAll('.dtab').forEach(function(t) { t.classList.remove('active'); });
  var tab = document.getElementById(id); if (tab) tab.classList.add('active');
  var map = { 't-overview': 0, 't-projects': 1, 't-events': 2, 't-activities': 3, 't-profile': 4, 't-resources': 5 };
  document.querySelectorAll('.sb-link').forEach(function(l) { l.classList.remove('active'); });
  var links = document.querySelectorAll('.sb-link');
  if (links[map[id]]) links[map[id]].classList.add('active');
}

function regEv(btn) {
  btn.textContent = '✅ Inscrit';
  btn.className = 'btn-reged';
  btn.disabled = true;
  showToast('✅ Inscription confirmée !');
}

function filterProj(btn, cat) {
  document.querySelectorAll('#pj-grid .pc').forEach(function(c) {
    c.style.display = (cat === 'all' || c.dataset.cat === cat) ? '' : 'none';
  });
  document.querySelectorAll('#proj-filters button').forEach(function(b) { b.classList.remove('active'); });
  btn.classList.add('active');
}

document.addEventListener('DOMContentLoaded', function() {
  var heroEl = document.getElementById('particles-hero');
  if (heroEl) {
    var canvas = document.createElement('canvas');
    canvas.className = 'particles-js-canvas-el';
    canvas.style.cssText = 'position:absolute;top:0;left:0;width:100%;height:100%';
    heroEl.appendChild(canvas);
    var ctx = canvas.getContext('2d'), W, H, particles = [];
    function resize() {
      var dpr = window.devicePixelRatio || 1;
      W = heroEl.offsetWidth; H = heroEl.offsetHeight;
      canvas.width = W * dpr; canvas.height = H * dpr;
      ctx.setTransform(1,0,0,1,0,0); ctx.scale(dpr, dpr);
    }
    function initParticles() {
      particles = [];
      for (var i = 0; i < 80; i++) {
        particles.push({ x: Math.random()*W, y: Math.random()*H, r: Math.random()*2+0.5,
          vx: (Math.random()-0.5)*0.8, vy: (Math.random()-0.5)*0.8, o: Math.random()*0.35+0.1 });
      }
    }
    function draw() {
      ctx.clearRect(0,0,W,H);
      for (var i = 0; i < particles.length; i++) {
        for (var j = i+1; j < particles.length; j++) {
          var dx = particles[i].x - particles[j].x, dy = particles[i].y - particles[j].y;
          var dist = Math.sqrt(dx*dx + dy*dy);
          if (dist < 130) {
            ctx.strokeStyle = 'rgba(255,255,255,' + (0.12*(1-dist/130)) + ')';
            ctx.lineWidth = 0.6; ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y); ctx.lineTo(particles[j].x, particles[j].y); ctx.stroke();
          }
        }
      }
      particles.forEach(function(p) {
        p.x += p.vx; p.y += p.vy;
        if (p.x < 0 || p.x > W) p.vx *= -1;
        if (p.y < 0 || p.y > H) p.vy *= -1;
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI*2);
        ctx.fillStyle = 'rgba(255,255,255,' + p.o + ')'; ctx.fill();
      });
      requestAnimationFrame(draw);
    }
    resize(); initParticles(); draw();
    var resizeTimer;
    window.addEventListener('resize', function() {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(function() { resize(); initParticles(); }, 150);
    });
  }

  var aiCanvas = document.getElementById('ai-bg');
  if (aiCanvas) {
    var actx = aiCanvas.getContext('2d'), aw, ah, blobs = [];
    function aresize() { aw = aiCanvas.width = window.innerWidth; ah = aiCanvas.height = window.innerHeight; }
    window.addEventListener('resize', aresize); aresize();
    function createBlob() {
      return { x: Math.random()*aw, y: Math.random()*ah, r: 200+Math.random()*150,
        vx: (Math.random()-0.5)*0.2, vy: (Math.random()-0.5)*0.2,
        color: Math.random()>0.5 ? 'rgba(37,168,101,0.25)' : 'rgba(255,125,46,0.25)' };
    }
    for (var bi = 0; bi < 5; bi++) blobs.push(createBlob());
    function adraw() {
      actx.clearRect(0,0,aw,ah);
      blobs.forEach(function(b) {
        b.x += b.vx; b.y += b.vy;
        if (b.x < -200 || b.x > aw+200) b.vx *= -1;
        if (b.y < -200 || b.y > ah+200) b.vy *= -1;
        var g = actx.createRadialGradient(b.x,b.y,0,b.x,b.y,b.r);
        g.addColorStop(0, b.color); g.addColorStop(1, 'transparent');
        actx.fillStyle = g; actx.beginPath(); actx.arc(b.x,b.y,b.r,0,Math.PI*2); actx.fill();
      });
      requestAnimationFrame(adraw);
    }
    adraw();
  }

  var langBtn = document.getElementById('langToggle');
  var langMenu = document.getElementById('langMenu');
  if (langBtn && langMenu) {
    langBtn.onclick = function() {
      langMenu.style.display = langMenu.style.display === 'flex' ? 'none' : 'flex';
    };
  }

  var modalOv = document.getElementById('modal-ov');
  if (modalOv) {
    modalOv.addEventListener('click', function(e) {
      if (e.target === this) closeModal();
    });
  }

  var yearEl = document.getElementById('year');
  if (yearEl) yearEl.textContent = new Date().getFullYear();

  var savedLang = localStorage.getItem('siteLang') || 'fr';
  setLang(savedLang);
  applyTheme(localStorage.getItem('siteTheme') || 'light');
});

window.i18n = {

  fr: {
    accueil: "Accueil",
    piliers: "Piliers",
    projets_evenements: "Projets & Événements",
    a_propos: "À propos",
    contact: "Contact",
    dashboard: "Dashboard",
    projets: "Projets",
    evenements: "Événements",
    activites: "Activités",

    join_us: "Join Us →",

    km: "KM",
    khalil: "Khalil",

    fr: "FR",
    en: "EN",
    ar: "AR",

    maison_de: "Maison de",
    intelligence: "Intelligence",
    artificielle: "Artificielle",

    un_espace_physique_et_virtuel_dedie_a_le:
      "Un espace physique et virtuel dédié à l'exploration, la recherche et l'innovation en IA — au service des étudiants, chercheurs et partenaires industriels.",

    rejoindre_la_communaute: "Rejoindre la communauté",
    decouvrir_nos_piliers: "Découvrir nos piliers",

    projets_actifs: "Projets actifs",
    membres: "Membres",
    evenements_an: "Événements / an",

    piliers_fondateurs: "Piliers fondateurs",
    defiler: "Défiler",

    ce_qui_nous_definit: "Ce qui nous définit",

    les_4_piliers_de:
      "Les 4 piliers de ",
    lai_house: "MAISON DE L'AI",
    notre_mission_repose_sur_quatre_axes_fon:
      "Notre mission repose sur quatre axes fondamentaux qui guident chaque initiative, projet et partenariat.",

    text: "🔬",
    recherche: "Recherche",

    conduire_des_travaux_avances_et_publier_:
      "Conduire des travaux avancés et publier dans les domaines prioritaires de l'IA.",

    text_1: "📚",
    education: "Education",

    ateliers_bootcamps_et_formations_continu:
      "Ateliers, bootcamps et formations continues pour développer les compétences IA.",

    text_2: "⚖️",
    ethique: "Éthique",

    promouvoir_une_ia_responsable_transparen:
      "Promouvoir une IA responsable, transparente et inclusive.",

    text_3: "💡",
    innovation: "Innovation",

    incuber_des_startups_et_connecter_la_rec:
      "Incuber des startups et connecter la recherche à l'industrie.",

    contenu_exclusif_membres: "Contenu exclusif membres",

    projets_evenements_activites:
      "Projets, Événements & Activités",

    rejoignez_la_communaute_ai_house_pour_ac:
      "Rejoignez la communauté AI HOUSE pour accéder aux projets de recherche, événements et ressources exclusives.",

    text_4: "🧪",
    projets_de_recherche: "Projets de Recherche",

    "8_projets_actifs": "8 projets actifs",

    membres_seulement: "🔒 Membres seulement",

    text_5: "🧠",

    resume_automatique_en_arabe:
      "Résumé automatique en arabe",

    nlp_master_2_ouvert: "NLP · Master 2 · Ouvert",

    text_6: "🔒",

    text_7: "👁️",

    detection_maladies_agricoles:
      "Détection maladies agricoles",

    vision_doctorat_ouvert:
      "Vision · Doctorat · Ouvert",

    text_8: "🔒",

    text_9: "📊",

    prediction_reussite_etudiante:
      "Prédiction réussite étudiante",

    ml_master_1_complet:
      "ML · Master 1 · Complet",

    text_10: "🔒",

    accedez_a_tous_les_projets_et_postulez:
      "👉 Accédez à tous les projets et postulez",

    join: "Join →",

    text_11: "📅",

    evenements_formations:
      "Événements & Formations",

    "4_evenements_ce_mois":
      "4 événements ce mois",

    membres_seulement_1:
      "🔒 Membres seulement",

    text_12: "🛠️",

    workshop_deep_learning:
      "Workshop Deep Learning",

    "10_mars_salle_204":
      "10 Mars · Salle 204",

    text_13: "🔒",

    text_14: "🎤",

    conference_ia_ethique:
      "Conférence IA & Éthique",

    "17_mars_amphi_principal":
      "17 Mars · Amphi Principal",

    text_15: "🔒",

    hackathon_ia_24h:
      "Hackathon IA — 24h",

    "24_mars_espace_innovation":
      "24 Mars · Espace Innovation",

    text_16: "🔒",

    inscrivezvous_aux_evenements:
      "👉 Inscrivez-vous aux événements",

    join_1: "Join →",


    recherche_appliquee: "🔬 Recherche appliquée",

    formation_continue: "🎓 Formation continue",

    partenariats_industriels: "🤝 Partenariats industriels",

    rayonnement_international: "🌍 Rayonnement international",



    fondee: "Fondée",

    a_propos_de_la_mia:
      "À propos de la MIA",

    un_ecosysteme_ia_au_cur_de_luniversite:
      "Un écosystème IA au cœur de l'université",
    a_propos_mia:
      "La Maison de l'IA (MIA) est une initiative de l'Université Hassiba Benbouali de Chlef visant à créer un hub d'excellence en intelligence artificielle.",

    mission_mia:
      "Notre mission : démocratiser l'IA en Algérie, former les talents de demain et produire une recherche de qualité internationale.",

    contact_us:
      "Contact Us",
    artificielle_1: "Artificielle",
    principal: "Principal",

    text_17: "🏠",
    text_18: "🧪",
    text_19: "📅",

    mon_espace: "Mon espace",

    text_20: "👤",
    text_21: "📚",

    km_1: "KM",

    khalil_m: "Khalil M.",

    etudiant_master:
      "Étudiant Master",

    deconnexion:
      "← Déconnexion",

    khalil_1:
      "Khalil",

    bienvenue_dans_votre_espace_ai_house_uhb:
      "Bienvenue dans votre espace AI HOUSE UHBC",

    mars_2025:
      "📅 Mars 2025",

    text_22: "🧪",

    projets_rejoints:
      "Projets rejoints",

    "1_ce_mois":
      "↑ +1 ce mois",

    text_23:
      "📅",

    evenements_inscrits:
      "Événements inscrits",

    prochain_10_mars:
      "Prochain: 10 mars",

    activites_totales:
      "Activités totales",

    derniere_hier:
      "Dernière: hier",

    text_24:
      "🏆",

    gold:
      "Gold",

    niveau_membre:
      "Niveau membre",

    mes_projets_recents:
      "Mes projets récents",

    voir_tout:
      "Voir tout →",

    nlp:
      "NLP",

    resume_automatique_en_arabe_1:
      "Résumé automatique en arabe",

    modele_transformer_pour_resumes_de_texte:
      "Modèle Transformer pour résumés de textes académiques arabes.",

    vision:
      "Vision",

    data:
      "Data",

    rl:
      "RL",

    chatbot_dorientation_universitaire:
      "Chatbot d'orientation universitaire",

    robot_pedagogique_autonome:
      "Robot pédagogique autonome",

    diagnostic_medical_par_imagerie:
      "Diagnostic médical par imagerie",

    mes_activites:
      "Mes Activités",

    mes_statistiques:
      "Mes statistiques",

    mon_profil:
      "Mon Profil",

    gerez_vos_informations_et_preferences:
      "Gérez vos informations et préférences",

    sauvegarder:
      "Sauvegarder",

    informations_personnelles:
      "Informations personnelles",

    prenom:
      "Prénom",

    nom:
      "Nom",

    email:
      "Email",

    profil:
      "Profil",

    domaines_dinteret:
      "Domaines d'intérêt",

    deep_learning:
      "Deep Learning",

    ethique_ia:
      "Éthique IA",

    python:
      "Python",

    transformers:
      "Transformers",

    ressources_membres:
      "Ressources membres",

    bibliotheque_ia:
      "Bibliothèque IA",

    cours_en_ligne_uhbc:
      "Cours en ligne UHBC",

    reseau_partenaires:
      "Réseau partenaires",

    rejoindre_lai_house:
      "Rejoindre l'AI HOUSE",

    acceder_a_mon_espace:
      "Accéder à mon espace →",
    maison_de_lia_universite_hassiba_be:
      "© 2026 Maison de l'IA — Université Hassiba Benbouali de Chlef",

    maison_de_ai:
      "Maison de AI",

    maison_de_ai_est_une_communaute_pour_les:
      "Maison de AI est une communauté pour les étudiants passionnés par l'intelligence artificielle, l'innovation et la technologie.",

    

    quick_links: "Liens rapides",

    profile: "Profil",

    projects: "Projets",

    events: "Événements",

    resources: "Ressources",
    activites_1:"Activites",

    dataset_arabic_nlp: "Jeu de données NLP arabe",

    pytorch_notebooks: "Notebooks PyTorch",

    gpu_cloud_access: "Accès au Cloud GPU",

    ai_library: "Bibliothèque IA",

    uhbc_online_courses: "Cours en ligne UHBC",

    partner_network: "Réseau de partenaires",

    contact_us:
      "Contact Us",

    artificielle_1:
      "© 2026 Maison de l'IA — Université Hassiba Benbouali de Chlef"

  },

  ar: {



    "10001200": "🕙 10:00–12:00",
    "10001300": "🕙 10:00–13:00",
    "14001700": "🕙 14:00–17:00",

    accueil: "الرئيسية",
    piliers: "الركائز",
    projets_evenements: "المشاريع والفعاليات",
    a_propos: "نبذة عنا",
    contact: "اتصل بنا",

    dashboard: "لوحة التحكم",
    projets: "المشاريع",
    evenements: "الفعاليات",
    activites: "الأنشطة",

    join_us: "انضم إلينا →",

    km: "KM",
    khalil: "خليل",

    fr: "FR",
    en: "EN",
    ar: "AR",

    maison_de: "بيت",
    intelligence: "الذكاء",
    artificielle: "الاصطناعي",

    un_espace_physique_et_virtuel_dedie_a_le:
      "فضاء مادي وافتراضي مخصص للاستكشاف والبحث والابتكار في الذكاء الاصطناعي لخدمة الطلاب والباحثين والشركاء الصناعيين.",

    rejoindre_la_communaute: "انضم إلى المجتمع",
    decouvrir_nos_piliers: "اكتشف ركائزنا",

    projets_actifs: "مشاريع نشطة",
    membres: "الأعضاء",
    evenements_an: "فعالية في السنة",

    piliers_fondateurs: "الركائز المؤسسة",
    defiler: "مرر",

    ce_qui_nous_definit: "ما يميزنا",

    les_4_piliers_de:
      "الركائز الأربعة",
    lai_house: "لبيت الذكاء الاصطناعي",

    notre_mission_repose_sur_quatre_axes_fon:
      "تعتمد مهمتنا على أربعة محاور أساسية توجه كل مبادرة ومشروع وشراكة.",

    text: "🔬",
    recherche: "البحث",

    conduire_des_travaux_avances_et_publier_:
      "إجراء أبحاث متقدمة ونشرها في مجالات الذكاء الاصطناعي.",

    text_1: "📚",
    education: "التعليم",

    ateliers_bootcamps_et_formations_continu:
      "ورشات تدريبية ومعسكرات تعليمية لتطوير مهارات الذكاء الاصطناعي.",

    text_2: "⚖️",
    ethique: "الأخلاقيات",

    promouvoir_une_ia_responsable_transparen:
      "تعزيز ذكاء اصطناعي مسؤول وشفاف.",

    text_3: "💡",
    innovation: "الابتكار",

    incuber_des_startups_et_connecter_la_rec:
      "احتضان الشركات الناشئة وربط البحث بالصناعة.",

    contenu_exclusif_membres: "محتوى حصري للأعضاء",

    projets_evenements_activites:
      "المشاريع والفعاليات والأنشطة",

    rejoignez_la_communaute_ai_house_pour_ac:
      "انضم إلى مجتمع AI House للوصول إلى المشاريع والفعاليات والموارد.",

    text_4: "🧪",

    projets_de_recherche: "مشاريع البحث",

    "8_projets_actifs": "8 مشاريع نشطة",

    membres_seulement: "🔒 للأعضاء فقط",

    text_5: "🧠",

    resume_automatique_en_arabe:
      "تلخيص تلقائي بالعربية",

    nlp_master_2_ouvert:
      "NLP · ماستر 2 · مفتوح",

    text_6: "🔒",

    text_7: "👁️",

    detection_maladies_agricoles:
      "كشف الأمراض الزراعية",

    vision_doctorat_ouvert:
      "الرؤية الحاسوبية · دكتوراه · مفتوح",

    text_8: "🔒",

    text_9: "📊",

    prediction_reussite_etudiante:
      "التنبؤ بنجاح الطلاب",

    ml_master_1_complet:
      "تعلم الآلة · ماستر 1 · مكتمل",

    text_10: "🔒",

    accedez_a_tous_les_projets_et_postulez:
      "الوصول إلى جميع المشاريع والتقديم",

    join: "انضم →",

    text_11: "📅",

    evenements_formations:
      "الفعاليات والتدريبات",

    "4_evenements_ce_mois":
      "4 فعاليات هذا الشهر",

    membres_seulement_1:
      "🔒 للأعضاء فقط",

    text_12: "🛠️",

    workshop_deep_learning:
      "ورشة التعلم العميق",

    "10_mars_salle_204":
      "10 مارس · القاعة 204",

    text_13: "🔒",

    text_14: "🎤",

    conference_ia_ethique:
      "مؤتمر الذكاء الاصطناعي والأخلاقيات",

    "17_mars_amphi_principal":
      "17 مارس · المدرج الرئيسي",

    text_15: "🔒",

    hackathon_ia_24h:
      "هاكاثون الذكاء الاصطناعي",

    "24_mars_espace_innovation":
      "24 مارس · فضاء الابتكار",

    text_16: "🔒",

    inscrivezvous_aux_evenements:
      "سجل في الفعاليات",

    join_1: "انضم →",

    recherche_appliquee: "🔬 البحث التطبيقي",

    formation_continue: "🎓 التكوين المستمر",

    partenariats_industriels: "🤝 الشراكات الصناعية",

    rayonnement_international: "🌍 الانتشار الدولي",
    fondee: "تأسست",

    a_propos_de_la_mia:
      "حول بيت الذكاء الاصطناعي",
    a_propos_mia:
      "بيت الذكاء الاصطناعي (MIA) هو مبادرة من جامعة حسيبة بن بوعلي بالشلف تهدف إلى إنشاء مركز تميز في مجال الذكاء الاصطناعي، يجمع بين الطلاب والباحثين والشركاء الصناعيين للعمل على مشاريع واقعية.",

    mission_mia:
      "مهمتنا: نشر ثقافة الذكاء الاصطناعي في الجزائر، تكوين مواهب المستقبل، وإنتاج أبحاث ذات جودة عالمية.",
    un_ecosysteme_ia_au_cur_de_luniversite:
      "نظام بيئي للذكاء الاصطناعي داخل الجامعة",
    artificielle_1: "الاصطناعي",
    principal: "الرئيسي",

    text_17: "🏠",
    text_18: "🧪",
    text_19: "📅",

    mon_espace: "مساحتي",

    text_20: "👤",
    text_21: "📚",

    km_1: "KM",

    khalil_m: "خليل م.",

    etudiant_master:
      "طالب ماستر",

    deconnexion:
      "تسجيل الخروج",

    khalil_1:
      "خليل",

    bienvenue_dans_votre_espace_ai_house_uhb:
      "مرحبًا بك في مساحة AI House الخاصة بك",

    mars_2025:
      "مارس 2025",

    text_22: "🧪",

    projets_rejoints:
      "المشاريع المنضم إليها",

    text_23: "📅",

    evenements_inscrits:
      "الفعاليات المسجل فيها",

    prochain_10_mars:
      "القادم: 10 مارس",

    activites_totales:
      "إجمالي الأنشطة",

    derniere_hier:
      "الأخير: أمس",

    text_24: "🏆",

    gold:
      "ذهبي",

    niveau_membre:
      "مستوى العضو",

    mes_projets_recents:
      "مشاريعي الأخيرة",

    voir_tout:
      "عرض الكل",

    nlp:
      "معالجة اللغة الطبيعية",

    vision:
      "الرؤية الحاسوبية",

    data:
      "البيانات",

    rl:
      "التعلم المعزز",

    chatbot_dorientation_universitaire:
      "روبوت توجيه جامعي",

    robot_pedagogique_autonome:
      "روبوت تعليمي ذاتي",

    diagnostic_medical_par_imagerie:
      "تشخيص طبي عبر الصور",

    mes_activites:
      "أنشطتي",

    mes_statistiques:
      "إحصائياتي",

    mon_profil:
      "ملفي الشخصي",

    gerez_vos_informations_et_preferences:
      "إدارة معلوماتك وتفضيلاتك",

    sauvegarder:
      "حفظ",

    informations_personnelles:
      "المعلومات الشخصية",

    prenom:
      "الاسم",

    nom:
      "اللقب",

    email:
      "البريد الإلكتروني",

    profil:
      "الملف الشخصي",

    domaines_dinteret:
      "مجالات الاهتمام",

    deep_learning:
      "التعلم العميق",

    ethique_ia:
      "أخلاقيات الذكاء الاصطناعي",

    python:
      "بايثون",

    transformers:
      "Transformers",

    ressources_membres:
      "موارد الأعضاء",

    bibliotheque_ia:
      "مكتبة الذكاء الاصطناعي",

    cours_en_ligne_uhbc:
      "دورات UHBC عبر الإنترنت",

    reseau_partenaires:
      "شبكة الشركاء",

    rejoindre_lai_house:
      "الانضمام إلى AI House",

    acceder_a_mon_espace:
      "الدخول إلى مساحتي →",

    _maison_de_lia_universite_hassiba_be:
      "© 2026 بيت الذكاء الاصطناعي — جامعة حسيبة بن بوعلي بالشلف",

    maison_de_ai:
      "بيت الذكاء الاصطناعي",

    maison_de_ai_est_une_communaute_pour_les:
      "بيت الذكاء الاصطناعي هو مجتمع للطلاب الشغوفين بالذكاء الاصطناعي والابتكار والتكنولوجيا.",

    quick_links:
      "روابط سريعة",

    profile:
      "الملف الشخصي",

    projects:
      "المشاريع",

    events:
      "الفعاليات",

    activites_1:
      "الأنشطة",

    resources:
      "الموارد",

    dataset_arabic_nlp:
      "بيانات NLP العربية",

    pytorch_notebooks:
      "دفاتر PyTorch",

    gpu_cloud_access:
      "الوصول إلى GPU Cloud",

    ai_library:
      "مكتبة الذكاء الاصطناعي",

    uhbc_online_courses:
      "دورات UHBC عبر الإنترنت",

    partner_network:
      "شبكة الشركاء",

    contact_us:
      "اتصل بنا",

    artificielle_1:
      "© 2026 Maison de l'IA — Université Hassiba Benbouali de Chlef",
    anne_1: "2026",

  },
  en: {

    "10": "10",
    "14": "14",
    "17": "17",
    "18": "18",
    "24": "24",
    "40": "40+",
    "120": "120",

    "1220": "👥 12/20",
    "2024": "2024",
    "2430": "👥 24/30",
    "3050": "👥 30/50",
    "4560": "👥 45/60",
    "80150": "👥 80/150",

    "10001200": "🕙 10:00–12:00",
    "10001300": "🕙 10:00–13:00",
    "14001700": "🕙 14:00–17:00",

    accueil: "Home",
    piliers: "Pillars",
    projets_evenements: "Projects & Events",
    a_propos: "About",
    contact: "Contact",

    dashboard: "Dashboard",
    projets: "Projects",
    evenements: "Events",
    activites: "Activities",

    join_us: "Join Us →",

    km: "KM",
    khalil: "Khalil",

    fr: "FR",
    en: "EN",
    ar: "AR",

    maison_de: "House of",
    intelligence: "Artificial",
    artificielle: "Intelligence",

    un_espace_physique_et_virtuel_dedie_a_le:
      "A physical and virtual space dedicated to exploration, research and innovation in AI for students, researchers and industry partners.",

    rejoindre_la_communaute: "Join the Community",
    decouvrir_nos_piliers: "Discover our pillars",

    projets_actifs: "Active Projects",
    membres: "Members",
    evenements_an: "Events / year",

    piliers_fondateurs: "Founding Pillars",
    defiler: "Scroll",

    ce_qui_nous_definit: "What defines us",

    les_4_piliers_de:
      "The 4 pillars of ",
    lai_house: "AI HOUSE",
    notre_mission_repose_sur_quatre_axes_fon:
      "Our mission is built on four fundamental pillars guiding every initiative and partnership.",

    text: "🔬",
    recherche: "Research",

    conduire_des_travaux_avances_et_publier_:
      "Conduct advanced research and publish in priority AI domains.",

    text_1: "📚",
    education: "Education",

    ateliers_bootcamps_et_formations_continu:
      "Workshops, bootcamps and training programs to develop AI skills.",

    text_2: "⚖️",
    ethique: "Ethics",

    promouvoir_une_ia_responsable_transparen:
      "Promoting responsible, transparent and inclusive AI.",

    text_3: "💡",
    innovation: "Innovation",

    incuber_des_startups_et_connecter_la_rec:
      "Incubating startups and connecting research with industry.",

    contenu_exclusif_membres:
      "Members Exclusive Content",

    projets_evenements_activites:
      "Projects, Events & Activities",

    rejoignez_la_communaute_ai_house_pour_ac:
      "Join the AI House community to access research projects, events and exclusive resources.",

    text_4: "🧪",

    projets_de_recherche:
      "Research Projects",

    "8_projets_actifs":
      "8 active projects",

    membres_seulement:
      "🔒 Members only",

    text_5: "🧠",

    resume_automatique_en_arabe:
      "Automatic Arabic Summarization",

    nlp_master_2_ouvert:
      "NLP · Master 2 · Open",

    text_6: "🔒",

    text_7: "👁️",

    detection_maladies_agricoles:
      "Agricultural Disease Detection",

    vision_doctorat_ouvert:
      "Computer Vision · PhD · Open",

    text_8: "🔒",

    text_9: "📊",

    prediction_reussite_etudiante:
      "Student Success Prediction",

    ml_master_1_complet:
      "Machine Learning · Master 1 · Full",

    text_10: "🔒",

    accedez_a_tous_les_projets_et_postulez:
      "Access all projects and apply",

    join: "Join →",
    un_ecosysteme_ia_au_cur_de_luniversite:"An artificial intelligence ecosystem within the university",
    recherche_appliquee: "🔬 Applied Research",

    formation_continue: "🎓 Continuous Training",

    partenariats_industriels: "🤝 Industry Partnerships",

    rayonnement_international: "🌍 International Outreach",


    text_11: "📅",

    evenements_formations:
      "Events & Training",

    "4_evenements_ce_mois":
      "4 events this month",

    membres_seulement_1:
      "🔒 Members only",

    text_12: "🛠️",

    workshop_deep_learning:
      "Deep Learning Workshop",

    "10_mars_salle_204":
      "March 10 · Room 204",

    text_13: "🔒",

    text_14: "🎤",

    conference_ia_ethique:
      "AI & Ethics Conference",

    "17_mars_amphi_principal":
      "March 17 · Main Auditorium",

    text_15: "🔒",

    hackathon_ia_24h:
      "AI Hackathon — 24h",

    "24_mars_espace_innovation":
      "March 24 · Innovation Space",

    text_16: "🔒",

    inscrivezvous_aux_evenements:
      "Register for events",

    join_1: "Join →",

    fondee:
      "Founded",

    a_propos_de_la_mia:
      "About MIA",
    a_propos_mia:
      "The AI House (MIA) is an initiative of Hassiba Benbouali University of Chlef aimed at creating a center of excellence in artificial intelligence, bringing together students, researchers, and industry partners around concrete projects.",

    mission_mia:
      "Our mission: to democratize AI in Algeria, train the talents of tomorrow, and produce internationally recognized research.",
    artificielle_1: "Artificial",
    principal: "Main",

    text_17: "🏠",
    text_18: "🧪",
    text_19: "📅",

    mon_espace: "My Space",

    text_20: "👤",
    text_21: "📚",

    km_1: "KM",

    khalil_m: "Khalil M.",

    etudiant_master:
      "Master Student",

    deconnexion:
      "Logout",

    khalil_1:
      "Khalil",

    bienvenue_dans_votre_espace_ai_house_uhb:
      "Welcome to your AI House UHBC space",

    mars_2025:
      "March 2025",

    text_22: "🧪",

    projets_rejoints:
      "Joined Projects",

    text_23: "📅",

    evenements_inscrits:
      "Registered Events",

    prochain_10_mars:
      "Next: March 10",

    activites_totales:
      "Total Activities",

    derniere_hier:
      "Last: Yesterday",

    text_24: "🏆",

    gold:
      "Gold",

    niveau_membre:
      "Member Level",

    mes_projets_recents:
      "My Recent Projects",

    voir_tout:
      "View All",

    nlp:
      "NLP",

    vision:
      "Vision",

    data:
      "Data",

    rl:
      "RL",

    chatbot_dorientation_universitaire:
      "University Orientation Chatbot",

    robot_pedagogique_autonome:
      "Autonomous Educational Robot",

    diagnostic_medical_par_imagerie:
      "Medical Image Diagnosis",

    mes_activites:
      "My Activities",

    mes_statistiques:
      "My Statistics",

    mon_profil:
      "My Profile",

    gerez_vos_informations_et_preferences:
      "Manage your information and preferences",

    sauvegarder:
      "Save",

    informations_personnelles:
      "Personal Information",

    prenom:
      "First Name",

    nom:
      "Last Name",

    email:
      "Email",

    profil:
      "Profile",

    domaines_dinteret:
      "Areas of Interest",

    deep_learning:
      "Deep Learning",

    ethique_ia:
      "AI Ethics",

    python:
      "Python",

    transformers:
      "Transformers",

    ressources_membres:
      "Member Resources",

    bibliotheque_ia:
      "AI Library",

    cours_en_ligne_uhbc:
      "UHBC Online Courses",

    reseau_partenaires:
      "Partner Network",

    rejoindre_lai_house:
      "Join AI House",

    acceder_a_mon_espace:
      "Access My Space →",
    _maison_de_lia_universite_hassiba_be:
      "© 2026 AI House — Hassiba Benbouali University of Chlef",

    maison_de_ai:
      "AI House",

    maison_de_ai_est_une_communaute_pour_les:
      "AI House is a community for students passionate about artificial intelligence, innovation, and technology.",

    quick_links:
      "Quick Links",

    profile:
      "Profile",

    projects:
      "Projects",

    events:
      "Events",

    activites_1:
      "Activities",

    resources:
      "Resources",

    dataset_arabic_nlp:
      "Arabic NLP Dataset",

    pytorch_notebooks:
      "PyTorch Notebooks",

    gpu_cloud_access:
      "GPU Cloud Access",

    ai_library:
      "AI Library",

    uhbc_online_courses:
      "UHBC Online Courses",

    partner_network:
      "Partner Network",

    contact_us:
      "Contact Us",

    artificielle_1:
      " © 2026 AI House — Hassiba Benbouali University of Chlef",




  }

}


function setLang(lang) {
  document.querySelectorAll('[data-i18n]').forEach(function(el) {
    var key = el.getAttribute('data-i18n');
    if (i18n[lang] && i18n[lang][key]) el.textContent = i18n[lang][key];
  });
  document.documentElement.lang = lang;
  document.body.dir = lang === 'ar' ? 'rtl' : 'ltr';
  document.body.style.direction = lang === 'ar' ? 'rtl' : 'ltr';
  document.body.classList.remove('lang-ar','lang-fr','lang-en');
  document.body.classList.add('lang-' + lang);
  var accent = document.querySelector('.accent');
  if (accent) accent.style.display = lang === 'ar' ? 'none' : 'inline';
  document.querySelectorAll('.sf-lang-btn, .pf-lang-btn').forEach(function(btn) {
    btn.classList.toggle('active', btn.dataset.lang === lang);
  });
  localStorage.setItem('siteLang', lang);
  var lm = document.getElementById('langMenu');
  if (lm) lm.style.display = 'none';
}