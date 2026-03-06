<?php
require_once 'includes/config.php';
require_once 'includes/api_helper.php';
session_start();

$me = require_auth();
$meInitials = strtoupper(substr($me['firstname'],0,1) . substr($me['lastname'],0,1));
$AES_KEY_HEX = bin2hex(AES_FINAL_KEY);

$err = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pl = check_request();
    if (!$pl) { $err = 'Requête invalide.'; }
    else {
        $payload = [];
        foreach (['firstname','lastname','email','phone','grade','domain','image','password'] as $f)
            if (isset($pl[$f]) && $pl[$f] !== '') $payload[$f] = $pl[$f];
        $ch = curl_init('http://127.0.0.1:5000/api/v1/me');
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>'PUT',CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.($_SESSION['access']??'')],CURLOPT_POSTFIELDS=>json_encode($payload),CURLOPT_TIMEOUT=>15]);
        $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $r = json_decode($body, true) ?? [];
        if ($code === 200) { header('Location: profile.php?saved=1'); exit(); }
        $err = $r['error'] ?? 'Erreur lors de la mise à jour.';
    }
}

if (isset($_GET['saved'])) $success = 'Profil mis à jour avec succès !';

$targetId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$isOwnProfile = !$targetId;

// Follow state defaults
$isFollowing    = false;
$followersCount = 0;
$followingCount = 0;

if ($isOwnProfile) {
    $subject  = $me;
    // Try all possible ID field names the API might use
    $myId = (int)($me['id'] ?? $me['user_id'] ?? $me['student_id'] ?? 0);

    if ($myId > 0) {
        // Fetch projects via /info with own ID — same endpoint used for other profiles
        $infoRes  = api_post('/info', ['id' => $myId]);
        $projects = $infoRes['data']['projects'] ?? [];
        // All returned projects for own profile are owned
        $projects = array_map(fn($p) => array_merge($p, ['role'=>'owner']), $projects);
        $followersCount = (int)($infoRes['data']['followers_count'] ?? $infoRes['data']['user']['followers_count'] ?? 0);
        $followingCount = (int)($infoRes['data']['following_count'] ?? $infoRes['data']['user']['following_count'] ?? 0);
    } else {
        // Fallback: scan all projects by name
        $allProj  = api_get('/projects');
        $allProjects_data = $allProj['data']['projects'] ?? [];
        $fn = trim($me['firstname'] ?? '');
        $ln = trim($me['lastname']  ?? '');
        $projects = array_values(array_filter($allProjects_data, fn($p) =>
            strtolower(trim($p['owner']['firstname'] ?? '')) === strtolower($fn) &&
            strtolower(trim($p['owner']['lastname']  ?? '')) === strtolower($ln)
        ));
        $projects = array_map(fn($p) => array_merge($p, ['role'=>'owner']), $projects);
        $followersCount = 0;
        $followingCount = 0;
    }
} else {
    $res = api_post('/info', ['id' => $targetId]);
    if ($res['code'] !== 200) {
        header('Location: main.php');
        exit();
    }
    $subject  = $res['data']['user']     ?? [];
    $projects = $res['data']['projects'] ?? [];
    // Follow state — try multiple places the API might put it
    $isFollowing    = (bool)($res['data']['is_following']
                   ?? $res['data']['user']['is_following']
                   ?? false);
    $followersCount = (int)($res['data']['followers_count']
                   ?? $res['data']['user']['followers_count']
                   ?? 0);
    $followingCount = (int)($res['data']['following_count']
                   ?? $res['data']['user']['following_count']
                   ?? 0);
}

$displayName     = htmlspecialchars(($subject['firstname']??'').' '.($subject['lastname']??''));
$displayInitials = strtoupper(substr($subject['firstname']??'',0,1).substr($subject['lastname']??'',0,1));
$joinYear        = isset($subject['created_at']) ? date('Y', strtotime($subject['created_at'])) : '—';

$owned   = array_values(array_filter($projects, fn($p) => ($p['role']??'') === 'owner'));
$contrib = array_values(array_filter($projects, fn($p) => ($p['role']??'') !== 'owner'));

$catClass = ['nlp'=>'cat-nlp','vision'=>'cat-vision','data'=>'cat-data','rl'=>'cat-rl','ml'=>'cat-ml','other'=>'cat-other'];
$catLabel = ['nlp'=>'NLP','vision'=>'Vision','data'=>'Data','rl'=>'RL','ml'=>'ML','other'=>'Autre'];

$domainLabels = ['intelligence artificielle'=>'Intelligence Artificielle','developpement web'=>'Développement Web','cyber securite'=>'Cyber Sécurité','reseaux et telecommunications'=>'Réseaux & Télécoms','systemes embarques'=>'Systèmes Embarqués','science des donnees'=>'Science des Données','genie logiciel'=>'Génie Logiciel','autre'=>'Autre'];
$domainDisplay = htmlspecialchars($domainLabels[$subject['domain']??''] ?? ucwords($subject['domain']??'—'));
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Profil — <?= $displayName ?> · AI House</title>
<link rel="stylesheet" href="css/global.css"/>
<link rel="stylesheet" href="css/dashboard.css"/>
<link rel="stylesheet" href="css/profile.css"/>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,600;0,9..144,700;0,9..144,800;0,9..144,900;1,9..144,400;1,9..144,700&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
<style>
:root{
  --green:#1b6e3f;--green-l:#22953f;--green-p:#eef6f1;--green-m:#d0eadb;
  --orange:#d95f0a;--orange-l:#f07020;--orange-p:#fef3ec;
  --dark:#1a2820;--text:#2c3e30;--muted:#7a9484;
  --white:#ffffff;--bg:#f7faf8;--border:#ddeae2;
  --nav-h:72px;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Plus Jakarta Sans',sans-serif;background:var(--bg);color:var(--text);}

/* ─── NAV ─── */
nav{position:fixed;top:0;left:0;right:0;z-index:200;display:flex;align-items:center;justify-content:space-between;padding:0 5%;height:var(--nav-h);background:var(--white);border-bottom:2px solid var(--border);box-shadow:0 2px 16px rgba(27,110,63,.06);}
.nav-logo{display:flex;align-items:center;gap:12px;text-decoration:none}
.nav-logo img{height:40px;width:auto}
.nav-logo-text{font-family:'Fraunces',serif;font-weight:800;font-size:1rem;color:var(--dark)}
.nav-logo-text span{color:var(--green)}
.back-btn{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:8px;border:1.5px solid var(--border);background:var(--white);font-family:'Plus Jakarta Sans',sans-serif;font-size:.82rem;font-weight:700;color:var(--text);cursor:pointer;transition:.2s;text-decoration:none;}
.back-btn:hover{border-color:var(--green);color:var(--green);background:var(--green-p)}

/* ─── COVER ─── */
.profile-cover{height:200px;background:linear-gradient(135deg,var(--green) 0%,var(--green-l) 50%,var(--dark) 100%);position:relative;overflow:hidden;margin-top:var(--nav-h);}
.profile-cover::after{content:'';position:absolute;inset:0;background-image:url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E");background-size:180px;opacity:.05;}
.cover-circle{position:absolute;right:-60px;bottom:-60px;width:280px;height:280px;border-radius:50%;border:40px solid rgba(255,255,255,.06);}
.cover-circle2{position:absolute;right:60px;bottom:-100px;width:200px;height:200px;border-radius:50%;border:30px solid rgba(255,255,255,.04);}

/* ─── CONTAINER ─── */
.profile-container{max-width:900px;margin:0 auto;padding:0 5% 80px}
.profile-info-bar{display:flex;align-items:flex-end;justify-content:space-between;margin-top:-54px;margin-bottom:28px;flex-wrap:wrap;gap:14px;position:relative;}
.profile-avatar-big{width:108px;height:108px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:900;font-size:1.8rem;border:4px solid var(--white);box-shadow:0 6px 24px rgba(27,110,63,.2);overflow:hidden;flex-shrink:0;}
.profile-avatar-big img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.profile-actions{display:flex;gap:10px;padding-bottom:6px;flex-wrap:wrap;align-items:center;}
.btn-edit{padding:9px 22px;border-radius:8px;font-size:.8rem;font-weight:700;background:var(--green);color:#fff;border:none;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:7px;}
.btn-edit:hover{background:var(--green-l);box-shadow:0 4px 14px rgba(27,110,63,.28);transform:translateY(-1px)}
.btn-logout-p{padding:9px 18px;border-radius:8px;font-size:.8rem;font-weight:700;background:var(--white);color:var(--text);border:1.5px solid var(--border);cursor:pointer;transition:.2s;display:flex;align-items:center;gap:7px;}
.btn-logout-p:hover{border-color:#fca5a5;color:#b91c1c;background:#fff0f0}
.viewing-badge{display:flex;align-items:center;gap:7px;font-size:.78rem;font-weight:700;color:var(--muted);background:var(--bg);border:1.5px solid var(--border);padding:7px 14px;border-radius:8px;}
.btn-follow{padding:9px 22px;border-radius:8px;font-size:.8rem;font-weight:700;background:var(--green);color:#fff;border:none;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:7px;}
.btn-follow:hover{background:var(--green-l);box-shadow:0 4px 14px rgba(27,110,63,.28);transform:translateY(-1px)}
.btn-follow.following{background:var(--white);color:var(--green);border:1.5px solid var(--green);}
.btn-follow.following:hover{background:var(--green-p);}

/* ─── PROFILE CARD ─── */
.profile-main-info{background:var(--white);border:1.5px solid var(--border);border-radius:14px;padding:24px;margin-bottom:24px}
.p-fullname{font-family:'Fraunces',serif;font-weight:900;font-size:1.7rem;color:var(--dark);margin-bottom:6px}
.p-domain{font-size:.9rem;color:var(--green);font-weight:700;margin-bottom:12px;display:flex;align-items:center;gap:6px}
.p-meta-chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:16px}
.p-meta-chip{display:flex;align-items:center;gap:6px;font-size:.76rem;font-weight:600;color:var(--muted);background:var(--bg);padding:6px 12px;border-radius:20px;border:1px solid var(--border);}
.p-stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:0;margin-top:16px;padding-top:16px;border-top:1px solid var(--border)}
.p-stat{text-align:center;padding:8px 0}
.p-stat-val{font-family:'Fraunces',serif;font-weight:900;font-size:1.5rem;color:var(--green)}
.p-stat-lbl{font-size:.7rem;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;font-weight:700;margin-top:2px}

/* ─── SECTION TABS ─── */
.section-tabs{display:flex;gap:6px;margin-bottom:18px;background:var(--bg);border:1.5px solid var(--border);border-radius:12px;padding:4px;}
.stab{flex:1;padding:9px 14px;border-radius:8px;font-size:.8rem;font-weight:700;border:none;background:none;cursor:pointer;color:var(--muted);transition:.2s;display:flex;align-items:center;justify-content:center;gap:7px;font-family:'Plus Jakarta Sans',sans-serif;}
.stab:hover{color:var(--text);background:var(--white)}
.stab.active{background:var(--white);color:var(--green);box-shadow:0 2px 8px rgba(27,110,63,.1)}
.stab-count{background:var(--green-p);color:var(--green);font-size:.66rem;font-weight:800;padding:2px 7px;border-radius:10px}
.stab.active .stab-count{background:var(--green);color:#fff}
.stab-panel{display:none}.stab-panel.active{display:block}

/* ─── PROJECT CARD ─── */
@keyframes cardUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.project-card{background:var(--white);border:1.5px solid var(--border);border-radius:16px;margin-bottom:16px;overflow:hidden;transition:.25s;box-shadow:0 2px 10px rgba(27,110,63,.04);animation:cardUp .4s ease both;}
.project-card:hover{border-color:var(--green-m);box-shadow:0 8px 32px rgba(27,110,63,.1);transform:translateY(-2px)}
.pc-accent{height:5px;background:linear-gradient(90deg,var(--green) 0%,var(--green-l) 100%);display:none;}
.project-card:hover .pc-accent{display:block}
.pc-top-bar{display:flex;align-items:center;justify-content:space-between;padding:14px 18px 0;}
.pc-cat-badge{padding:4px 11px;border-radius:20px;font-size:.7rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;}
.cat-nlp{background:var(--green-p);color:var(--green)}.cat-vision{background:#eaf0fb;color:#3a5fc4}.cat-data{background:#f4eefb;color:#7c3aed}.cat-rl{background:var(--orange-p);color:var(--orange)}.cat-ml{background:#fff3cd;color:#b45309}.cat-other{background:var(--bg);color:var(--muted)}
.role-badge-owner{font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:10px;background:#fff3cd;color:#b45309;display:inline-flex;align-items:center;gap:4px}
.role-badge-contrib{font-size:.68rem;font-weight:700;padding:3px 9px;border-radius:10px;background:var(--green-p);color:var(--green);display:inline-flex;align-items:center;gap:4px}
.pc-body{padding:14px 18px}
.pc-title{font-family:'Fraunces',serif;font-weight:800;font-size:1rem;color:var(--dark);margin-bottom:6px;line-height:1.3}
.pc-desc{font-size:.83rem;line-height:1.7;color:var(--muted)}
.pc-footer{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid var(--border);background:var(--bg);}
.pc-status{display:flex;align-items:center;gap:6px;font-size:.74rem;font-weight:700}
.status-dot{width:7px;height:7px;border-radius:50%}
.st-open .status-dot{background:#22953f}.st-open{color:#22953f}
.st-full .status-dot{background:var(--orange)}.st-full{color:var(--orange)}
.st-done .status-dot{background:var(--muted)}.st-done{color:var(--muted)}

/* ─── EDIT MODAL ─── */
.modal-overlay{position:fixed;inset:0;z-index:800;background:rgba(26,40,32,.55);backdrop-filter:blur(4px);display:flex;align-items:center;justify-content:center;padding:24px;opacity:0;pointer-events:none;transition:opacity .25s;}
.modal-overlay.open{opacity:1;pointer-events:all}
.modal-box{background:var(--white);border-radius:20px;width:100%;max-width:520px;max-height:90vh;overflow-y:auto;box-shadow:0 24px 80px rgba(26,40,32,.28);transform:translateY(24px) scale(.97);transition:.3s cubic-bezier(.4,0,.2,1);}
.modal-overlay.open .modal-box{transform:translateY(0) scale(1)}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:22px 24px 18px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--white);z-index:2;border-radius:20px 20px 0 0;}
.modal-title{font-family:'Fraunces',serif;font-weight:800;font-size:1.1rem;color:var(--dark)}
.modal-close{width:34px;height:34px;border-radius:8px;border:1.5px solid var(--border);background:none;cursor:pointer;font-size:1rem;color:var(--muted);display:flex;align-items:center;justify-content:center;transition:.2s}
.modal-close:hover{border-color:var(--text);color:var(--text);background:var(--bg)}
.modal-body{padding:22px 24px}
.modal-foot{display:flex;align-items:center;justify-content:flex-end;gap:10px;padding:16px 24px 20px;border-top:1px solid var(--border);}
.form-group{margin-bottom:18px}
.form-label{display:block;font-size:.78rem;font-weight:700;color:var(--text);margin-bottom:6px;letter-spacing:.02em}
.form-label span{color:var(--muted);font-weight:500}
.form-input,.form-select{width:100%;padding:10px 14px;border-radius:9px;border:1.5px solid var(--border);background:var(--bg);font-family:'Plus Jakarta Sans',sans-serif;font-size:.85rem;color:var(--text);outline:none;transition:.2s;appearance:none;-webkit-appearance:none;}
.form-input:focus,.form-select:focus{border-color:var(--green);background:var(--white);box-shadow:0 0 0 3px rgba(27,110,63,.08)}
.form-select{background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7a8d' stroke-width='1.8' fill='none' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");background-repeat:no-repeat;background-position:right 14px center;padding-right:36px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.btn-cancel{padding:9px 20px;border-radius:8px;border:1.5px solid var(--border);background:none;font-family:'Plus Jakarta Sans',sans-serif;font-size:.82rem;font-weight:700;color:var(--muted);cursor:pointer;transition:.2s}
.btn-cancel:hover{border-color:var(--text);color:var(--text)}
.btn-save{padding:9px 22px;border-radius:8px;background:var(--green);color:#fff;border:none;font-family:'Plus Jakarta Sans',sans-serif;font-size:.82rem;font-weight:700;cursor:pointer;transition:.2s;display:flex;align-items:center;gap:7px}
.btn-save:hover{background:var(--green-l);box-shadow:0 4px 14px rgba(27,110,63,.28);transform:translateY(-1px)}
.btn-save:disabled{background:var(--muted);cursor:default;transform:none}
.av-edit-wrap{display:flex;flex-direction:column;align-items:center;gap:10px;margin-bottom:20px}
.av-edit-circle{width:80px;height:80px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-family:'Fraunces',serif;font-weight:900;font-size:1.4rem;overflow:hidden;border:3px solid var(--green-m);cursor:pointer;transition:.2s;}
.av-edit-circle:hover{border-color:var(--green)}
.av-edit-circle img{width:100%;height:100%;object-fit:cover;border-radius:50%}
.av-edit-hint{font-size:.74rem;color:var(--muted)}

/* ─── EMPTY STATE & TOAST ─── */
.empty-state{text-align:center;padding:50px 20px;background:var(--white);border:1.5px solid var(--border);border-radius:16px;}
.es-icon{font-size:2.2rem;margin-bottom:14px;color:var(--muted)}
.es-title{font-family:'Fraunces',serif;font-weight:800;font-size:1.05rem;color:var(--dark);margin-bottom:6px}
.es-sub{font-size:.84rem;color:var(--muted)}
.toast{position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(80px);background:var(--dark);color:#fff;padding:12px 24px;border-radius:10px;font-size:.84rem;font-weight:600;z-index:9999;transition:transform .3s ease,opacity .3s ease;opacity:0;box-shadow:0 8px 28px rgba(0,0,0,.2);display:flex;align-items:center;gap:8px;white-space:nowrap;}
.toast.show{transform:translateX(-50%) translateY(0);opacity:1}
.toast-icon{color:#6ee8a2}

/* ─── MOBILE ─── */
@media(max-width:768px){
  nav{padding:0 14px}
  .profile-container{padding:0 14px 60px}
  .profile-cover{height:150px}
  .profile-info-bar{margin-top:-44px}
  .profile-avatar-big{width:88px;height:88px;font-size:1.4rem}
  .p-fullname{font-size:1.35rem}
  .p-stats-row{grid-template-columns:repeat(2,1fr)}
  .section-tabs{flex-wrap:wrap}
  .form-row{grid-template-columns:1fr}
}
</style>
</head>
<body>

<!-- ─── NAV ─── -->
<nav>
  <a class="nav-logo" href="main.php">
    <img src="https://i.imgur.com/zl5jHaY.png" alt="AI House UHBC">
    <span class="nav-logo-text">AI <span>House</span></span>
  </a>
  <a class="back-btn" href="main.php">
    <i class="fa-solid fa-arrow-left"></i> Retour
  </a>
</nav>

<!-- ─── COVER ─── -->
<div class="profile-cover">
  <div class="cover-circle"></div>
  <div class="cover-circle2"></div>
</div>

<!-- ─── PROFILE CONTENT ─── -->
<div class="profile-container">

  <div class="profile-info-bar">
    <div class="profile-avatar-big">
      <?php if(!empty($subject['image'])): ?>
        <img src="<?= htmlspecialchars($subject['image']) ?>" alt="">
      <?php else: ?>
        <?= htmlspecialchars($displayInitials) ?>
      <?php endif; ?>
    </div>
    <div class="profile-actions">
      <?php if($isOwnProfile): ?>
        <button class="btn-edit" onclick="openEditModal()">
          <i class="fa-solid fa-pen"></i> Modifier le profil
        </button>
        <button class="btn-logout-p" onclick="window.location.href='student_login.php?logout=1'">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion
        </button>
      <?php else: ?>
        <?php $targetUserId = $subject['id'] ?? $targetId; ?>
        <button class="btn-follow<?= $isFollowing ? ' following' : '' ?>" id="follow-btn" onclick="toggleFollow(<?= (int)$targetUserId ?>)">
          <?php if($isFollowing): ?>
            <i class="fa-solid fa-user-check"></i> Abonné
          <?php else: ?>
            <i class="fa-solid fa-user-plus"></i> Suivre
          <?php endif; ?>
        </button>
      <?php endif; ?>
    </div>
  </div>

  <!-- MAIN INFO CARD -->
  <div class="profile-main-info">
    <div class="p-fullname"><?= $displayName ?></div>
    <div class="p-domain"><i class="fa-solid fa-microchip"></i> <?= $domainDisplay ?></div>
    <div class="p-meta-chips">
      <span class="p-meta-chip"><i class="fa-solid fa-graduation-cap"></i> <?= htmlspecialchars(ucfirst($subject['grade']??'—')) ?></span>
      <?php if(!empty($subject['email'])): ?>
        <span class="p-meta-chip"><i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($subject['email']) ?></span>
      <?php endif; ?>
      <?php if(!empty($subject['phone'])): ?>
        <span class="p-meta-chip"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($subject['phone']) ?></span>
      <?php endif; ?>
      <span class="p-meta-chip"><i class="fa-solid fa-map-marker-alt"></i> UHBC, Chlef</span>
    </div>
    <div class="p-stats-row">
      <div class="p-stat">
        <div class="p-stat-val"><?= count($owned) ?></div>
        <div class="p-stat-lbl">Projets</div>
      </div>
      <div class="p-stat" style="border-left:1px solid var(--border)">
        <div class="p-stat-val" id="followers-count"><?= $followersCount ?></div>
        <div class="p-stat-lbl">Abonnés</div>
      </div>
      <div class="p-stat" style="border-left:1px solid var(--border)">
        <div class="p-stat-val"><?= $followingCount ?></div>
        <div class="p-stat-lbl">Abonnements</div>
      </div>
      <div class="p-stat" style="border-left:1px solid var(--border);border-right:none">
        <div class="p-stat-val"><?= $joinYear ?></div>
        <div class="p-stat-lbl">Depuis</div>
      </div>
    </div>
  </div>

  <!-- PROJECT SECTION TABS -->
  <?php if(!empty($projects)): ?>
  <div class="section-tabs">
    <button class="stab active" onclick="switchStab('owned',this)">
      <i class="fa-solid fa-crown"></i> Mes projets
      <span class="stab-count"><?= count($owned) ?></span>
    </button>
    <button class="stab" onclick="switchStab('contrib',this)">
      <i class="fa-solid fa-users"></i> Contributions
      <span class="stab-count"><?= count($contrib) ?></span>
    </button>
    <button class="stab" onclick="switchStab('all',this)">
      <i class="fa-solid fa-flask"></i> Tous
      <span class="stab-count"><?= count($projects) ?></span>
    </button>
  </div>
  <?php endif; ?>

  <!-- OWNED PROJECTS -->
  <div class="stab-panel active" id="panel-owned">
    <?php if(empty($owned)): ?>
      <div class="empty-state">
        <div class="es-icon"><i class="fa-solid fa-flask"></i></div>
        <div class="es-title">Aucun projet créé</div>
        <div class="es-sub"><?= $isOwnProfile ? 'Revenez au feed pour créer votre premier projet !' : 'Cet étudiant n\'a pas encore créé de projet.' ?></div>
      </div>
    <?php else: ?>
      <?php foreach($owned as $i => $p): ?>
        <?= renderProjectCard($p, $catClass, $catLabel, $i) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- CONTRIB PROJECTS -->
  <div class="stab-panel" id="panel-contrib">
    <?php if(empty($contrib)): ?>
      <div class="empty-state">
        <div class="es-icon"><i class="fa-solid fa-users"></i></div>
        <div class="es-title">Aucune contribution</div>
        <div class="es-sub"><?= $isOwnProfile ? 'Rejoignez des projets depuis le feed !' : 'Cet étudiant ne contribue à aucun projet.' ?></div>
      </div>
    <?php else: ?>
      <?php foreach($contrib as $i => $p): ?>
        <?= renderProjectCard($p, $catClass, $catLabel, $i) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- ALL PROJECTS -->
  <div class="stab-panel" id="panel-all">
    <?php if(empty($projects)): ?>
      <div class="empty-state">
        <div class="es-icon"><i class="fa-solid fa-flask"></i></div>
        <div class="es-title">Aucun projet</div>
        <div class="es-sub"><?= $isOwnProfile ? 'Créez ou rejoignez un projet depuis le feed !' : 'Cet étudiant n\'est associé à aucun projet.' ?></div>
      </div>
    <?php else: ?>
      <?php foreach($projects as $i => $p): ?>
        <?= renderProjectCard($p, $catClass, $catLabel, $i) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div><!-- /profile-container -->

<?php if($isOwnProfile): ?>
<div class="modal-overlay" id="edit-modal" onclick="if(event.target===this)closeEditModal()">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-title"><i class="fa-solid fa-pen" style="color:var(--green);margin-right:8px"></i>Modifier le profil</div>
      <button class="modal-close" onclick="closeEditModal()">✕</button>
    </div>
    <form id="editForm" method="POST" action="profile.php">
      <input type="hidden" name="_imadenc" id="_imadenc">
      <input type="hidden" name="_dok" id="_dok">
      <div class="modal-body">
        <?php if($err): ?><div style="background:#fff0f0;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:.82rem;color:#b91c1c;margin-bottom:16px"><?= htmlspecialchars($err) ?></div><?php endif; ?>
        <div class="av-edit-wrap">
          <div class="av-edit-circle" id="av-edit-circle" onclick="document.getElementById('av-file').click()">
            <?php if(!empty($me['image'])): ?>
              <img src="<?= htmlspecialchars($me['image']) ?>" alt="" id="av-edit-img">
            <?php else: ?>
              <span id="av-edit-initials"><?= htmlspecialchars($meInitials) ?></span>
            <?php endif; ?>
          </div>
          <input type="file" id="av-file" accept="image/*" style="display:none" onchange="previewAvatar(this)">
          <span class="av-edit-hint"><i class="fa-solid fa-camera"></i> Cliquez pour changer la photo</span>
        </div>
        <input type="hidden" id="f-image" value="">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Prénom</label>
            <input type="text" class="form-input" id="f-fn" value="<?= htmlspecialchars($me['firstname']) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Nom</label>
            <input type="text" class="form-input" id="f-ln" value="<?= htmlspecialchars($me['lastname']) ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Email</label>
          <input type="email" class="form-input" id="f-email" value="<?= htmlspecialchars($me['email']??'') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Téléphone</label>
          <input type="tel" class="form-input" id="f-phone" value="<?= htmlspecialchars($me['phone']??'') ?>">
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Grade</label>
            <select class="form-select" id="f-grade">
              <option value="licence"  <?= ($me['grade']==="licence") ?"selected":"" ?>>Licence</option>
              <option value="master"   <?= ($me['grade']==="master")  ?"selected":"" ?>>Master</option>
              <option value="doctorat" <?= ($me['grade']==="doctorat")?"selected":"" ?>>Doctorat</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Domaine</label>
            <select class="form-select" id="f-domain">
              <?php
              $domains = ['intelligence artificielle'=>'Intelligence Artificielle','developpement web'=>'Développement Web','cyber securite'=>'Cyber Sécurité','reseaux et telecommunications'=>'Réseaux et Télécommunications','systemes embarques'=>'Systèmes Embarqués','science des donnees'=>'Science des Données','genie logiciel'=>'Génie Logiciel','autre'=>'Autre'];
              foreach($domains as $val => $label):
                $sel = ($me['domain']===$val) ? 'selected' : '';
              ?>
              <option value="<?= htmlspecialchars($val) ?>" <?= $sel ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Nouveau mot de passe <span>(laisser vide pour ne pas changer)</span></label>
          <input type="password" class="form-input" id="f-pw" placeholder="6 caractères minimum">
        </div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn-cancel" onclick="closeEditModal()">Annuler</button>
        <button type="submit" class="btn-save"><i class="fa-solid fa-floppy-disk"></i> Enregistrer</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<div class="toast" id="toast">
  <i class="fa-solid fa-circle-check toast-icon"></i>
  <span id="toast-msg"></span>
</div>

<script>
var _K = CryptoJS.enc.Hex.parse('<?= $AES_KEY_HEX ?>');
function _aes(obj){var iv=CryptoJS.lib.WordArray.random(16);var enc=CryptoJS.AES.encrypt(JSON.stringify(obj),_K,{iv:iv,mode:CryptoJS.mode.CBC,padding:CryptoJS.pad.Pkcs7});return CryptoJS.enc.Base64.stringify(iv.concat(enc.ciphertext));}
const ACCESS_TOKEN = <?= json_encode($_SESSION['access'] ?? '') ?>;
// DEBUG — check browser console to see all fields returned by /me:
/* ME_DATA = <?= json_encode($me) ?> */

function showToast(msg){var t=document.getElementById('toast');document.getElementById('toast-msg').textContent=msg;t.classList.add('show');setTimeout(function(){t.classList.remove('show');},3200);}

function switchStab(panel,btn){document.querySelectorAll('.stab').forEach(function(b){b.classList.remove('active');});document.querySelectorAll('.stab-panel').forEach(function(p){p.classList.remove('active');});btn.classList.add('active');document.getElementById('panel-'+panel).classList.add('active');}

async function toggleFollow(userId){
  const btn=document.getElementById('follow-btn');
  if(!btn) return;
  try{
    const r=await fetch(`http://127.0.0.1:5000/api/v1/users/${userId}/follow`,{method:'POST',headers:{'Content-Type':'application/json','Authorization':'Bearer '+ACCESS_TOKEN}});
    const data=await r.json();
    const following=data.following;
    btn.classList.toggle('following',following);
    btn.innerHTML=following?'<i class="fa-solid fa-user-check"></i> Abonné':'<i class="fa-solid fa-user-plus"></i> Suivre';
    const countEl=document.getElementById('followers-count');
    if(countEl) countEl.textContent=data.followers_count??Math.max(0,parseInt(countEl.textContent||0)+(following?1:-1));
  }catch(e){showToast('Erreur réseau');}
}

<?php if($isOwnProfile): ?>
<?php if($success): ?>window.addEventListener('DOMContentLoaded',function(){showToast('<?= $success ?>');});<?php endif; ?>
<?php if($err): ?>window.addEventListener('DOMContentLoaded',function(){openEditModal();});<?php endif; ?>

function openEditModal(){document.getElementById('edit-modal').classList.add('open');document.body.style.overflow='hidden';}
function closeEditModal(){document.getElementById('edit-modal').classList.remove('open');document.body.style.overflow='';}

function previewAvatar(input){
  var f=input.files[0]; if(!f) return;
  var rd=new FileReader();
  rd.onload=function(e){
    document.getElementById('f-image').value=e.target.result;
    var circle=document.getElementById('av-edit-circle');
    var img=document.getElementById('av-edit-img');
    if(!img){img=document.createElement('img');img.id='av-edit-img';img.style.cssText='width:100%;height:100%;object-fit:cover;border-radius:50%';var init=document.getElementById('av-edit-initials');if(init)init.style.display='none';circle.appendChild(img);}
    img.src=e.target.result;img.style.display='block';
  };
  rd.readAsDataURL(f);
}

document.getElementById('editForm').addEventListener('submit', function(e) {
  e.preventDefault();
  var pl={
    firstname: document.getElementById('f-fn').value,
    lastname:  document.getElementById('f-ln').value,
    email:     document.getElementById('f-email').value,
    phone:     document.getElementById('f-phone').value,
    grade:     document.getElementById('f-grade').value,
    domain:    document.getElementById('f-domain').value
  };
  var img=document.getElementById('f-image').value;
  if(img) pl.image=img;
  var pw=document.getElementById('f-pw').value;
  if(pw) pl.password=pw;
  document.getElementById('_imadenc').value=_aes(pl);
  document.getElementById('_dok').value=_aes({t:Date.now()});
  this.submit();
});
<?php endif; ?>
</script>
</body>
</html>
<?php
function renderProjectCard(array $p, array $catClass, array $catLabel, int $i): string {
    $cc = $catClass[$p['category']] ?? 'cat-other';
    $cl = $catLabel[$p['category']] ?? htmlspecialchars($p['category']);
    $isOwner = ($p['role'] ?? '') === 'owner';
    if ($p['status']==="open" && !$p['is_full']) $statusHtml = '<span class="pc-status st-open"><span class="status-dot"></span>Ouvert</span>';
    elseif ($p['is_full']) $statusHtml = '<span class="pc-status st-full"><span class="status-dot"></span>Complet</span>';
    else $statusHtml = '<span class="pc-status st-done"><span class="status-dot"></span>Terminé</span>';
    $roleBadge = $isOwner ? '<span class="role-badge-owner"><i class="fa-solid fa-crown"></i> Créateur</span>' : '<span class="role-badge-contrib"><i class="fa-solid fa-users"></i> Contributeur</span>';
    $members = ($p['member_count'] ?? 0).($p['max_members'] ? '/'.$p['max_members'] : '').' membres';
    $title = htmlspecialchars($p['title'] ?? '');
    $desc  = htmlspecialchars($p['description'] ?? '');
    return '<div class="project-card" style="animation-delay:'.($i*70).'ms">
      <div class="pc-accent"></div>
      <div class="pc-top-bar"><span class="pc-cat-badge '.$cc.'">'.  $cl.'</span>'.$roleBadge.'</div>
      <div class="pc-body"><div class="pc-title">'.$title.'</div>'.($desc ? '<p class="pc-desc">'.$desc.'</p>' : '').'</div>
      <div class="pc-footer"><div style="display:flex;align-items:center;gap:12px">'.$statusHtml.'<span style="font-size:.74rem;color:var(--muted)"><i class="fa-solid fa-users" style="margin-right:3px"></i>'.$members.'</span></div></div>
    </div>';
}
?>