<?php
require_once 'includes/config.php';
require_once 'includes/api_helper.php';
session_start();

// ── helper: make DELETE/POST curl call to Flask API ──
function api_delete(string $path): array {
    $token = $_SESSION['access'] ?? '';
    $ch = curl_init('http://173.249.28.246:8090/api/v1'.$path);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>'DELETE',CURLOPT_HTTPHEADER=>['Content-Type: application/json','Authorization: Bearer '.$token],CURLOPT_TIMEOUT=>15]);
    $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    return ['code'=>$code, 'data'=>json_decode($body,true)??[]];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pl = check_request();
    if (!$pl) {
        // Check if AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) { header('Content-Type: application/json'); echo json_encode(['error'=>'invalid']); exit(); }
        header('Location: dashboard.php?err=invalid'); exit();
    }
    $action = $pl['_action'] ?? '';
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

    // ── AJAX-only actions (return JSON) ──
    if ($action === 'toggle_like') {
        $r = api_post('/projects/'.(int)($pl['id']??0).'/like', []);
        header('Content-Type: application/json');
        echo json_encode(['liked'=>$r['data']['liked']??false,'like_count'=>$r['data']['like_count']??0,'error'=>$r['data']['error']??null]);
        exit();
    } elseif ($action === 'add_comment') {
        $r = api_post('/projects/'.(int)($pl['id']??0).'/comments', ['content'=>$pl['content']??'']);
        header('Content-Type: application/json');
        echo json_encode(['comment'=>$r['data']['comment']??null,'error'=>$r['data']['error']??null]);
        exit();
    } elseif ($action === 'delete_comment') {
        $r = api_delete('/projects/'.(int)($pl['project_id']??0).'/comments/'.(int)($pl['comment_id']??0));
        header('Content-Type: application/json');
        echo json_encode(['ok'=>($r['code']===200||$r['code']===204),'error'=>$r['data']['error']??null]);
        exit();
    } elseif ($action === 'toggle_follow') {
        $r = api_post('/users/'.(int)($pl['id']??0).'/follow', []);
        header('Content-Type: application/json');
        echo json_encode(['following'=>$r['data']['following']??false,'followers_count'=>$r['data']['followers_count']??0,'error'=>$r['data']['error']??null]);
        exit();
    } elseif ($action === 'load_comments') {
        $r = api_get('/projects/'.(int)($pl['id']??0).'/comments');
        header('Content-Type: application/json');
        echo json_encode($r['data']);
        exit();

    } elseif ($action === 'search') {
        $r = api_post('/search', ['name'=>$pl['name']??'']);
        header('Content-Type: application/json');
        echo json_encode($r['data']);
        exit();
    } elseif ($action === 'get_user_info') {
        $r = api_post('/info', ['id'=>(int)($pl['id']??0)]);
        header('Content-Type: application/json');
        echo json_encode($r['data']);
        exit();
    } elseif ($action === 'get_uni_posts') {
        $r = api_get('/getinfo');
        header('Content-Type: application/json');
        echo json_encode($r['data']);
        exit();
    } elseif ($action === 'get_uni_post') {
        $r = api_get('/getsuperinfo/'.(int)($pl['id']??0));
        header('Content-Type: application/json');
        echo json_encode($r['data']);
        exit();

    // ── Normal form actions (redirect) ──
    } elseif ($action === 'join_project') {
        $r = api_post('/projects/'.(int)($pl['id']??0).'/join', []);
        header('Location: dashboard.php?msg='.($r['code']===201 ? 'project_joined' : 'err_'.urlencode($r['data']['error']??'error')));
    } elseif ($action === 'create_project') {
        $data = [];
        foreach (['title','description','image','category','status','is_visible'] as $f)
            if (isset($pl[$f])) $data[$f] = $pl[$f];
        $r = api_post('/projects', $data);
        header('Location: dashboard.php?msg='.($r['code']===201 ? 'project_created' : 'err_'.urlencode($r['data']['error']??'error')));
    } elseif ($action === 'join_event') {
        $r = api_post('/events/'.(int)($pl['id']??0).'/join', []);
        header('Location: dashboard.php?msg='.($r['code']===201 ? 'event_joined' : 'err_'.urlencode($r['data']['error']??'error')));
    } elseif ($action === 'leave_event') {
        $r = api_delete('/events/'.(int)($pl['id']??0).'/quit');
        header('Location: dashboard.php?msg='.($r['code']===200||$r['code']===204 ? 'event_left' : 'err_left'));
    } else {
        header('Location: dashboard.php?err=unknown');
    }
    exit();
}

$me       = require_auth();
$initials = strtoupper(substr($me['firstname'],0,1) . substr($me['lastname'],0,1));

$projects      = api_get('/projects');
$announcements = api_get('/announcements');
$events        = api_get('/events');
$stats         = api_get('/stats');

$projects_data      = $projects['data']['projects']          ?? [];
$announcements_data = $announcements['data']['announcements'] ?? [];
$events_data        = $events['data']['events']              ?? [];
$stats_data         = $stats['data']                         ?? ['projects'=>0,'events'=>0];

$AES_KEY_HEX = bin2hex(AES_FINAL_KEY);
$msg_map = ['project_joined'=>'Projet rejoint !','project_created'=>'Projet publié avec succès !','event_joined'=>'Inscription confirmée !','event_left'=>'Désinscription effectuée.'];
$page_toast = '';
if (isset($_GET['msg'])) {
    $key = $_GET['msg'];
    if (isset($msg_map[$key])) $page_toast = $msg_map[$key];
    elseif (str_starts_with($key,'err_')) $page_toast = urldecode(substr($key,4));
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AI House UHBC — Espace Étudiants</title>
<link rel="stylesheet" href="css/global.css"/>
<link rel="stylesheet" href="css/dashboard.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
<style>
/* ─── STUDENT PROFILE MODAL ─── */
.sp-modal-box{background:var(--white);border-radius:16px;width:100%;max-width:520px;max-height:88vh;overflow-y:auto;box-shadow:0 24px 80px rgba(0,0,0,.2);animation:cardIn .35s cubic-bezier(.16,1,.3,1) both;}
.sp-modal-head{display:flex;align-items:center;justify-content:space-between;padding:18px 22px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--white);z-index:1;}
.sp-modal-title{font-family:'Fraunces',serif;font-weight:800;font-size:1rem;color:var(--dark);}
.sp-modal-body{padding:0;}
.sp-loading{display:flex;flex-direction:column;align-items:center;justify-content:center;height:220px;}
.sp-spinner{font-size:1.6rem;color:var(--green);}
.sp-hero{display:flex;align-items:center;gap:16px;padding:22px 22px 16px;border-bottom:1px solid var(--border);}
.sp-avatar{width:68px;height:68px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.1rem;overflow:hidden;flex-shrink:0;border:3px solid var(--green-m);}
.sp-name{font-family:'Fraunces',serif;font-weight:800;font-size:1.1rem;color:var(--dark);margin-bottom:5px;}
.sp-grade,.sp-domain{font-size:.78rem;color:var(--muted);display:flex;align-items:center;gap:5px;margin-top:3px;}
.sp-grade i,.sp-domain i{color:var(--green);font-size:.72rem;}
.sp-stats{display:flex;align-items:center;padding:16px 22px;border-bottom:1px solid var(--border);}
.sp-stat{flex:1;text-align:center;}
.sp-stat-val{font-family:'Fraunces',serif;font-weight:900;font-size:1.4rem;color:var(--green);}
.sp-stat-lbl{font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);font-weight:700;margin-top:2px;}
.sp-stat-div{width:1px;height:36px;background:var(--border);}
.sp-info-section{padding:16px 22px;border-bottom:1px solid var(--border);}
.sp-info-row{display:flex;align-items:flex-start;gap:12px;padding:8px 0;}
.sp-info-icon{width:32px;height:32px;border-radius:8px;background:var(--green-p);color:var(--green);display:flex;align-items:center;justify-content:center;font-size:.8rem;flex-shrink:0;}
.sp-info-label{font-size:.68rem;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);font-weight:700;margin-bottom:2px;}
.sp-info-val{font-size:.86rem;font-weight:600;color:var(--dark);}
.sp-projects-section{padding:16px 22px;}
.sp-section-title{font-family:'Fraunces',serif;font-weight:800;font-size:.95rem;color:var(--dark);margin-bottom:14px;display:flex;align-items:center;gap:8px;}
.sp-project-card{background:var(--bg);border:1.5px solid var(--border);border-radius:12px;padding:14px;margin-bottom:10px;}
.sp-proj-top{display:flex;align-items:center;gap:8px;margin-bottom:8px;}
.sp-owner-badge{font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:10px;background:var(--orange-p);color:var(--orange);}
.sp-contrib-badge{font-size:.68rem;font-weight:700;padding:3px 8px;border-radius:10px;background:var(--green-p);color:var(--green);}
.sp-proj-title{font-weight:700;font-size:.88rem;color:var(--dark);margin-bottom:5px;}
.sp-proj-desc{font-size:.78rem;color:var(--muted);line-height:1.55;margin-bottom:8px;}
.sp-proj-footer{display:flex;align-items:center;justify-content:space-between;}
.sp-no-projects{display:flex;align-items:center;gap:10px;padding:24px;font-size:.86rem;color:var(--muted);}
.sp-no-projects i{font-size:1.2rem;color:var(--green);}
/* TOAST */
.toast{position:fixed;bottom:24px;left:50%;transform:translateX(-50%) translateY(20px);background:var(--dark);color:#fff;padding:12px 22px;border-radius:12px;font-size:.85rem;font-weight:600;box-shadow:0 8px 32px rgba(0,0,0,.3);z-index:9999;opacity:0;pointer-events:none;transition:all .35s cubic-bezier(.16,1,.3,1);display:flex;align-items:center;gap:10px;}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast-icon{color:#22953f;}

/* ─── LINKEDIN-STYLE PROJECT CARD ─── */
.lk-card{background:var(--white);border:1.5px solid var(--border);border-radius:14px;margin-bottom:16px;overflow:hidden;transition:.25s;box-shadow:0 2px 10px rgba(27,110,63,.04);animation:cardUp .4s ease both;}
.lk-card:hover{border-color:var(--green-m);box-shadow:0 8px 32px rgba(27,110,63,.1);}
.lk-header{display:flex;align-items:center;gap:12px;padding:14px 16px 12px;cursor:pointer;transition:.15s;}
.lk-header:hover{background:var(--fog);}
.lk-body{padding:2px 16px 14px;}
.lk-meta-row{margin-top:10px;}
/* Project image */
.pc-img-wrap{width:100%;overflow:hidden;max-height:340px;background:var(--border);}
.pc-img{width:100%;height:100%;object-fit:cover;display:block;transition:transform .3s;}
.lk-card:hover .pc-img{transform:scale(1.02);}
.pc-img-ph{display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;gap:10px;font-size:.82rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;}
.pc-img-ph-nlp{background:linear-gradient(135deg,#eef6f1,#d0eadb);color:var(--green);}
.pc-img-ph-vision{background:linear-gradient(135deg,#eaf0fb,#c3d5f5);color:#3a5fc4;}
.pc-img-ph-data{background:linear-gradient(135deg,#f4eefb,#e2ccf7);color:#7c3aed;}
.pc-img-ph-rl{background:linear-gradient(135deg,var(--orange-p),#fde4cc);color:var(--orange);}
.pc-img-ph-ml{background:linear-gradient(135deg,#fff3cd,#fde68a);color:#b45309;}
.pc-img-ph-other{background:linear-gradient(135deg,var(--fog),var(--border));color:var(--muted);}
.pc-img-ph i{font-size:2rem;opacity:.5;}
/* Stats bar */
.lk-stats-bar{display:flex;align-items:center;justify-content:space-between;padding:7px 16px 5px;font-size:.78rem;color:var(--muted);}
.lk-stat-item{display:flex;align-items:center;gap:5px;}
.lk-divider{height:1px;background:var(--border);margin:0 16px;}
/* Action buttons */
.lk-actions{display:flex;gap:0;padding:4px 8px;}
.lk-action-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:9px 14px;border:none;background:none;border-radius:8px;font-family:inherit;font-size:.82rem;font-weight:700;color:var(--muted);cursor:pointer;transition:.15s;}
.lk-action-btn:hover{background:var(--fog);color:var(--text);}
.lk-action-btn.lk-liked{color:var(--orange);}
/* Comments panel */
.lk-comments-panel{border-top:1px solid var(--border);padding:12px 14px;}
.lk-cmt-input-row{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.lk-cmt-av{width:34px;height:34px;border-radius:50%;background:var(--green);color:#fff;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:800;overflow:hidden;flex-shrink:0;}
.lk-cmt-av img{width:100%;height:100%;object-fit:cover;border-radius:50%;}
.lk-cmt-input-wrap{flex:1;display:flex;align-items:center;gap:6px;background:var(--fog);border:1.5px solid var(--border);border-radius:24px;padding:6px 10px 6px 14px;}
.lk-cmt-input{flex:1;border:none;background:none;font-family:inherit;font-size:.82rem;color:var(--txt);outline:none;}
.lk-cmt-input::placeholder{color:var(--muted);}
.lk-cmt-send{width:30px;height:30px;border-radius:50%;background:var(--green);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.72rem;flex-shrink:0;transition:.15s;}
.lk-cmt-send:hover{background:var(--green-l);}
.lk-cmt-list{max-height:220px;overflow-y:auto;}
.lk-cmt-item{display:flex;gap:8px;margin-bottom:10px;}
.lk-cmt-bubble{background:var(--fog);border-radius:0 12px 12px 12px;padding:9px 13px;flex:1;}
.lk-cmt-author{font-size:.74rem;font-weight:800;color:var(--dark);margin-bottom:3px;}
.lk-cmt-text{font-size:.82rem;color:var(--text);line-height:1.5;}
.lk-cmt-meta{font-size:.7rem;color:var(--muted);margin-top:4px;}
.lk-cmt-del{color:var(--orange);cursor:pointer;font-weight:700;}
.lk-cmt-del:hover{text-decoration:underline;}
.lk-cmt-loading,.lk-cmt-empty{text-align:center;padding:12px;font-size:.8rem;color:var(--muted);}
/* Follow button in student modal */
.sp-follow-btn{display:inline-flex;align-items:center;gap:7px;margin-top:10px;padding:8px 18px;border-radius:8px;background:var(--green);color:#fff;border:none;font-family:inherit;font-size:.8rem;font-weight:700;cursor:pointer;transition:.2s;}
.sp-follow-btn:hover{background:var(--green-l);transform:translateY(-1px);}
.sp-follow-btn.sp-following{background:var(--white);color:var(--green);border:1.5px solid var(--green);}
.sp-follow-btn.sp-following:hover{background:var(--green-p);}
/* ─── PROJECT IMAGE UPLOAD ─── */
.proj-img-upload{position:relative;width:100%;height:160px;border:2px dashed var(--border);border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;overflow:hidden;transition:.2s;background:var(--fog);}
.proj-img-upload:hover{border-color:var(--green);background:var(--green-p);}
.proj-img-ph{display:flex;flex-direction:column;align-items:center;gap:6px;color:var(--muted);pointer-events:none;}
.proj-img-ph i{font-size:1.8rem;color:var(--green);opacity:.7;}
.proj-img-ph span{font-size:.82rem;font-weight:700;}
.proj-img-ph small{font-size:.7rem;color:var(--muted);}
.proj-img-clear{position:absolute;top:8px;right:8px;width:28px;height:28px;border-radius:50%;background:rgba(0,0,0,.6);border:none;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.75rem;z-index:2;transition:.15s;}
.proj-img-clear:hover{background:rgba(180,0,0,.8);}
/* MY EVENTS STYLES */
.my-events-title{font-family:'Fraunces',serif;font-weight:800;font-size:1rem;color:var(--dark);}
.my-event-item{display:flex;align-items:center;gap:14px;background:var(--white);border:1.5px solid var(--border);border-radius:12px;padding:14px;margin-bottom:10px;transition:.2s;}
.my-event-item:hover{border-color:var(--green-m);box-shadow:var(--shadow-md);}
.my-event-date{width:48px;height:48px;border-radius:10px;background:var(--green-p);border:1.5px solid var(--green-m);display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;}
.my-event-day{font-family:'Fraunces',serif;font-weight:900;font-size:1.1rem;color:var(--green);line-height:1;}
.my-event-month{font-size:.6rem;font-weight:700;color:var(--green);text-transform:uppercase;}
.my-event-info{flex:1;}
.my-event-name{font-size:.88rem;font-weight:700;color:var(--dark);margin-bottom:4px;}
.my-event-loc{font-size:.74rem;color:var(--muted);display:flex;align-items:center;gap:4px;}
.my-event-type{font-size:.68rem;font-weight:700;padding:4px 10px;border-radius:10px;flex-shrink:0;}
/* UNI POST styles */
.uni-post-header{display:flex;align-items:center;gap:8px;margin-bottom:6px;}
.uni-post-badge{font-size:.68rem;font-weight:700;color:var(--green);background:var(--green-p);padding:3px 8px;border-radius:10px;display:flex;align-items:center;gap:4px;}
.uni-post-date-badge{font-size:.68rem;color:var(--muted);display:flex;align-items:center;gap:4px;}
.uni-post-footer{padding:10px 18px;border-top:1px solid var(--border);background:var(--bg);}
.uni-post-read-btn{font-size:.76rem;font-weight:700;color:var(--green);display:flex;align-items:center;gap:6px;}
.uni-post-arrow{font-size:.6rem;transform:rotate(180deg);}
/* PROGRESS BAR segments */
.sv-prog-seg{flex:1;height:3px;background:rgba(255,255,255,.3);border-radius:2px;overflow:hidden;}
.sv-prog-fill{height:100%;background:#fff;width:0;border-radius:2px;}
.sv-prog-fill.instant{width:100%;transition:none;}
</style>
</head>
<body>

<!-- ─── NAV ─── -->
<nav class="top-nav">
  <a class="nav-logo" href="dashboard.php">
    <img src="https://i.imgur.com/zl5jHaY.png" alt="AI House UHBC">
    <span class="nav-logo-text">AI <span>House</span></span>
  </a>
  <div class="nav-search" id="nav-search-wrap">
    <i class="fa-solid fa-magnifying-glass nav-search-icon"></i>
    <input type="text" id="student-search" placeholder="Rechercher un étudiant…" autocomplete="off"
      oninput="handleSearch(this.value)" onfocus="openDropdown()" onblur="closeDropdownDelayed()">
    <button class="nav-search-clear" id="search-clear" onclick="clearSearch()">✕</button>
    <div class="search-dropdown" id="search-dropdown"></div>
  </div>
  <div class="nav-right">
    <button class="nav-notif mob-search-btn" id="mob-search-btn" onclick="openMobileSearch()" style="display:none">
      <i class="fa-solid fa-magnifying-glass"></i>
    </button>
    <div class="nav-notif">
      <i class="fa-regular fa-bell"></i>
      <?php if(count($announcements_data) > 0): ?><div class="notif-dot"></div><?php endif; ?>
    </div>
    <div class="nav-av" id="nav-av-btn" onclick="window.location.href='profile.php'">
      <?php if($me['image']): ?>
        <img src="<?= htmlspecialchars($me['image']) ?>" alt="">
      <?php else: ?>
        <?= htmlspecialchars($initials) ?>
      <?php endif; ?>
    </div>
    <button class="nav-btn-logout" onclick="window.location.href='logout.php'">
      <i class="fa-solid fa-arrow-right-from-bracket"></i> Déconnexion
    </button>
  </div>
</nav>

<!-- ─── MAIN LAYOUT ─── -->
<div class="main-layout">

  <!-- FEED -->
  <div class="feed-area">
    <div class="feed-header">
      <div><div class="feed-title">Espace <span>Étudiants</span></div></div>
      <button class="btn-create-project" onclick="openCreateProject()">
        <i class="fa-solid fa-plus"></i> Nouveau projet
      </button>
    </div>

    <!-- TABS -->
    <div class="feed-tabs">
      <button class="feed-tab active" onclick="switchTab('projects',this)">
        <i class="fa-solid fa-flask"></i> Projets
        <span class="feed-tab-count" id="tab-count-projects"><?= count($projects_data) ?></span>
      </button>
      <button class="feed-tab" onclick="switchTab('announcements',this)">
        <i class="fa-solid fa-bullhorn"></i> Annonces
        <span class="feed-tab-count" id="tab-count-ann"><?= count($announcements_data) ?></span>
      </button>
      <button class="feed-tab" onclick="switchTab('events',this)">
        <i class="fa-solid fa-calendar-days"></i> Événements
        <span class="feed-tab-count" id="tab-count-events"><?= count($events_data) ?></span>
      </button>
      <button class="feed-tab" onclick="switchTab('myevents',this)">
        <i class="fa-solid fa-ticket"></i> Mes événements
        <span class="feed-tab-count" id="tab-count-myevents">0</span>
      </button>
      <button class="feed-tab" onclick="switchTab('university',this)">
        <i class="fa-solid fa-university"></i> Université
      </button>
    </div>

    <!-- TAB: PROJECTS -->
    <div class="tab-panel active" id="panel-projects">
      <div class="feed-filters" id="feed-filters" style="margin-bottom:16px">
        <button class="ff-btn active" onclick="filterFeed(this,'all')">Tous</button>
        <button class="ff-btn" onclick="filterFeed(this,'nlp')">NLP</button>
        <button class="ff-btn" onclick="filterFeed(this,'vision')">Vision</button>
        <button class="ff-btn" onclick="filterFeed(this,'data')">Data</button>
        <button class="ff-btn" onclick="filterFeed(this,'rl')">RL</button>
        <button class="ff-btn" onclick="filterFeed(this,'ml')">ML</button>
        <button class="ff-btn" onclick="filterFeed(this,'other')">Autre</button>
      </div>
      <div id="feed-container"></div>
      <div class="load-more-wrap" id="load-more-wrap" style="display:none">
        <button class="btn-load-more" onclick="loadMore()"><i class="fa-solid fa-rotate"></i> Voir plus de projets</button>
      </div>
      <div class="empty-state" id="feed-empty" style="display:none">
        <div class="es-icon"><i class="fa-solid fa-flask"></i></div>
        <div class="es-title">Aucun projet trouvé</div>
        <div class="es-sub">Essayez un autre filtre ou revenez plus tard.</div>
      </div>
    </div>

    <!-- TAB: ANNOUNCEMENTS -->
    <div class="tab-panel" id="panel-announcements">
      <div id="ann-container"></div>
      <div class="empty-state" id="ann-empty" style="display:none">
        <div class="es-icon"><i class="fa-solid fa-bullhorn"></i></div>
        <div class="es-title">Aucune annonce</div>
      </div>
    </div>

    <!-- TAB: EVENTS -->
    <div class="tab-panel" id="panel-events">
      <div id="events-container"></div>
      <div class="empty-state" id="events-empty" style="display:none">
        <div class="es-icon"><i class="fa-solid fa-calendar-xmark"></i></div>
        <div class="es-title">Aucun événement</div>
      </div>
    </div>

    <!-- TAB: MY EVENTS -->
    <div class="tab-panel" id="panel-myevents">
      <div id="myevents-container"></div>
      <div class="empty-state" id="myevents-empty" style="display:none">
        <div class="es-icon"><i class="fa-solid fa-ticket"></i></div>
        <div class="es-title">Vous n'êtes inscrit à aucun événement</div>
      </div>
    </div>

    <!-- TAB: UNIVERSITY -->
    <div class="tab-panel" id="panel-university">
      <div id="uni-container">
        <div class="uni-loading" id="uni-loading">
          <div class="uni-skeleton-list">
            <?php for($i=0;$i<4;$i++): ?>
            <div class="uni-skel-item">
              <div class="skeleton" style="width:90px;height:90px;flex-shrink:0"></div>
              <div style="padding:14px 18px;flex:1"><div class="skeleton" style="height:12px;width:50%;margin-bottom:12px"></div><div class="skeleton" style="height:15px;width:90%;margin-bottom:8px"></div><div class="skeleton" style="height:13px;width:65%"></div></div>
            </div>
            <?php endfor; ?>
          </div>
        </div>
        <div id="uni-posts-list"></div>
      </div>
    </div>
  </div>

  <!-- SIDEBAR -->
  <div class="sidebar-area">
    <div class="s-card">
      <div class="profile-mini" onclick="window.location.href='profile.php'">
        <div class="pm-av">
          <?php if($me['image']): ?><img src="<?= htmlspecialchars($me['image']) ?>" alt=""><?php else: ?><?= htmlspecialchars($initials) ?><?php endif; ?>
        </div>
        <div>
          <div class="pm-name"><?= htmlspecialchars($me['firstname'] . ' ' . $me['lastname']) ?></div>
          <div class="pm-role"><?= htmlspecialchars(ucfirst($me['grade'] ?? '')) ?> · <?= htmlspecialchars($me['domain'] ?? '') ?></div>
        </div>
      </div>
      <div class="stats-row">
        <div class="stat-item">
          <div class="stat-val" id="my-proj-count">—</div>
          <div class="stat-lbl">Projets</div>
        </div>
        <div class="stat-item">
          <div class="stat-val"><?= $stats_data['events'] ?? 0 ?></div>
          <div class="stat-lbl">Événements</div>
        </div>
      </div>
    </div>

    <div class="s-card">
      <div class="s-card-head">
        <div class="s-card-title">Top Étudiants</div>
        <span style="font-size:.7rem;color:var(--muted);font-weight:600">ce mois</span>
      </div>
      <div id="top-students-list" style="padding:6px 0"></div>
    </div>

    <div class="s-card">
      <div class="s-card-head"><div class="s-card-title">Thématiques</div></div>
      <div class="tag-cloud">
        <span class="tag-pill active" onclick="filterByTag(this,'all')">Tous</span>
        <span class="tag-pill" onclick="filterByTag(this,'nlp')">NLP</span>
        <span class="tag-pill" onclick="filterByTag(this,'vision')">Vision</span>
        <span class="tag-pill" onclick="filterByTag(this,'data')">Data Science</span>
        <span class="tag-pill" onclick="filterByTag(this,'rl')">Renf. Learning</span>
        <span class="tag-pill" onclick="filterByTag(this,'ml')">Machine Learning</span>
      </div>
    </div>

    <div class="s-card">
      <div class="s-card-head">
        <div class="s-card-title">Prochains événements</div>
        <span style="font-size:.7rem;color:var(--muted);font-weight:600">cette semaine</span>
      </div>
      <div id="sidebar-events" style="padding:6px 0"></div>
    </div>
  </div>
</div>

<!-- ─── CREATE PROJECT MODAL ─── -->
<div class="modal-overlay" id="create-project-modal" onclick="handleModalOverlayClick(event)">
  <div class="modal-box">
    <div class="modal-head">
      <div class="modal-title"><i class="fa-solid fa-flask" style="color:var(--green);margin-right:8px"></i>Nouveau projet</div>
      <button class="modal-close" onclick="closeCreateProject()">✕</button>
    </div>
    <div class="modal-body">
      <div id="modal-err" style="display:none;background:#fff0f0;border:1px solid #fca5a5;border-radius:8px;padding:10px 14px;font-size:.82rem;color:#b91c1c;margin-bottom:16px"></div>
      <!-- Image upload -->
      <div class="form-group">
        <label class="form-label">Image du projet <span style="color:var(--muted);font-weight:400">(optionnel)</span></label>
        <div class="proj-img-upload" id="proj-img-upload" onclick="document.getElementById('proj-img-file').click()">
          <div class="proj-img-ph" id="proj-img-ph">
            <i class="fa-solid fa-image"></i>
            <span>Cliquez pour ajouter une image</span>
            <small>JPG, PNG, WEBP — max 5 Mo</small>
          </div>
          <img id="proj-img-preview" style="display:none;width:100%;height:100%;object-fit:cover;border-radius:10px;" alt="">
          <button class="proj-img-clear" id="proj-img-clear" style="display:none" onclick="clearProjImg(event)"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <input type="file" id="proj-img-file" accept="image/*" style="display:none" onchange="previewProjImg(this)">
        <input type="hidden" id="proj-img-b64" value="">
      </div>
      <div class="form-group"><label class="form-label">Titre <span style="color:var(--orange)">*</span></label><input type="text" class="form-input" id="proj-title" placeholder="Ex : Chatbot NLP en arabe dialectal…"></div>
      <div class="form-group"><label class="form-label">Description <span style="color:var(--muted);font-weight:400">(optionnel)</span></label><textarea class="form-textarea" id="proj-desc" placeholder="Décrivez l'objectif, les technologies utilisées…"></textarea></div>
      <div class="form-row">
        <div class="form-group" style="margin-bottom:0"><label class="form-label">Catégorie</label><select class="form-select" id="proj-category"><option value="nlp">NLP</option><option value="vision">Vision</option><option value="data">Data Science</option><option value="rl">Reinforcement Learning</option><option value="ml">Machine Learning</option><option value="other" selected>Autre</option></select></div>
        <div class="form-group" style="margin-bottom:0"><label class="form-label">Statut</label><select class="form-select" id="proj-status"><option value="open" selected>Ouvert</option><option value="done">Terminé</option></select></div>
      </div>
      <div class="form-group" style="margin-top:18px">
        <div class="form-toggle-row">
          <div><div class="form-toggle-label">Projet visible publiquement</div><div class="form-toggle-sub">Les autres étudiants peuvent voir votre projet.</div></div>
          <label class="toggle-switch"><input type="checkbox" id="proj-visible" checked><span class="toggle-slider"></span></label>
        </div>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn-cancel" onclick="closeCreateProject()">Annuler</button>
      <button class="btn-submit" id="btn-publish" onclick="submitCreateProject()"><i class="fa-solid fa-paper-plane"></i> Publier</button>
    </div>
  </div>
</div>

<!-- ─── STUDENT PROFILE MODAL ─── -->
<div class="modal-overlay" id="sp-modal" onclick="if(event.target===this)closeStudentProfile()">
  <div class="sp-modal-box">
    <div class="sp-modal-head">
      <div class="sp-modal-title"><i class="fa-solid fa-user" style="color:var(--green);margin-right:8px"></i>Profil étudiant</div>
      <button class="modal-close" onclick="closeStudentProfile()">✕</button>
    </div>
    <div class="sp-modal-body">
      <div class="sp-loading" id="sp-loading"><div class="sp-spinner"><i class="fa-solid fa-spinner fa-spin"></i></div><div style="font-size:.84rem;color:var(--muted);margin-top:12px">Chargement…</div></div>
      <div id="sp-content"></div>
    </div>
  </div>
</div>

<!-- ─── STORY VIEWER ─── -->
<div class="story-viewer" id="story-viewer">
  <div class="sv-bg"><img id="sv-bg-img" src="" alt=""></div>
  <div class="sv-progress" id="sv-progress"></div>
  <div class="sv-header">
    <div class="sv-av" id="sv-av"></div>
    <div class="sv-meta"><div class="sv-name" id="sv-name"></div><div class="sv-time" id="sv-time"></div></div>
    <button class="sv-close" onclick="closeStoryViewer()"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <div class="sv-img-ph" id="sv-img-ph"><i class="fa-solid fa-newspaper"></i></div>
  <img class="sv-main-img" id="sv-main-img" style="display:none" alt="">
  <div class="sv-body">
    <div class="sv-title" id="sv-title"></div>
    <div class="sv-date-row"><i class="fa-regular fa-calendar"></i><span id="sv-date-text"></span></div>
    <button class="sv-read-btn" id="sv-read-btn"><i class="fa-solid fa-book-open"></i> Lire plus</button>
  </div>
  <div class="sv-tap-zone sv-tap-left" onclick="storyPrev()"></div>
  <div class="sv-tap-zone sv-tap-right" onclick="storyNext()"></div>
</div>

<!-- ─── DRAWER ─── -->
<div class="drawer-overlay" id="drawer-overlay" onclick="closeDrawer()"></div>
<div class="drawer" id="post-drawer">
  <div class="drawer-head">
    <button class="drawer-back" onclick="closeDrawer()"><i class="fa-solid fa-arrow-left"></i></button>
    <div class="drawer-title" id="drawer-title">Actualité</div>
    <div style="width:34px"></div>
  </div>
  <div class="drawer-body" id="drawer-body">
    <div class="drawer-loading" id="drawer-loading"><div class="drawer-spinner"><i class="fa-solid fa-spinner fa-spin"></i></div><div style="font-size:.84rem;color:var(--muted);margin-top:10px">Chargement…</div></div>
    <div id="drawer-content" style="display:none"></div>
  </div>
</div>

<!-- ─── MOBILE BOTTOM NAV ─── -->
<nav class="mobile-nav" id="mobile-nav">
  <button class="mn-btn active" id="mn-home" onclick="mobileTab('projects',this)"><i class="fa-solid fa-flask"></i><span>Projets</span></button>
  <button class="mn-btn" id="mn-announcements" onclick="mobileTab('announcements',this)"><i class="fa-solid fa-bullhorn"></i><span>Annonces<?php if(count($announcements_data)>0): ?> (<?= count($announcements_data) ?>)<?php endif; ?></span></button>
  <button class="mn-btn-fab" onclick="openCreateProject()"><div class="mn-fab-circle"><i class="fa-solid fa-plus"></i></div><span>Nouveau</span></button>
  <button class="mn-btn" id="mn-events" onclick="mobileTab('events',this)"><i class="fa-solid fa-calendar-days"></i><span>Événements</span></button>
  <button class="mn-btn" id="mn-university" onclick="mobileTab('university',this)"><i class="fa-solid fa-university"></i><span>Université</span></button>
</nav>

<!-- ─── MOBILE SEARCH ─── -->
<div class="mob-search-overlay" id="mob-search-overlay">
  <div class="mob-search-bar">
    <button class="mob-search-back" onclick="closeMobileSearch()"><i class="fa-solid fa-arrow-left"></i></button>
    <div style="position:relative;flex:1">
      <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.85rem;pointer-events:none"></i>
      <input type="text" id="mob-search-input" placeholder="Rechercher un étudiant…" autocomplete="off"
        oninput="handleMobSearch(this.value)" style="width:100%;padding:10px 14px 10px 36px;border:1.5px solid var(--border);border-radius:10px;font-family:inherit;font-size:.9rem;outline:none;background:var(--bg)">
    </div>
  </div>
  <div class="mob-search-results" id="mob-search-results">
    <div class="sd-empty" style="padding:40px 20px"><i class="fa-solid fa-magnifying-glass" style="margin-right:8px"></i>Tapez un nom pour rechercher</div>
  </div>
</div>

<!-- ─── TOAST ─── -->
<div class="toast" id="toast"><i class="fa-solid fa-circle-check toast-icon"></i><span id="toast-msg"></span></div>

<script>
// ─── AES + DATA ───
var _K = CryptoJS.enc.Hex.parse('<?= $AES_KEY_HEX ?>');
function _aes(obj){var iv=CryptoJS.lib.WordArray.random(16);var enc=CryptoJS.AES.encrypt(JSON.stringify(obj),_K,{iv:iv,mode:CryptoJS.mode.CBC,padding:CryptoJS.pad.Pkcs7});return CryptoJS.enc.Base64.stringify(iv.concat(enc.ciphertext));}

const PROJECTS      = <?= json_encode($projects_data) ?>;
const ANNOUNCEMENTS = <?= json_encode($announcements_data) ?>;
const EVENTS        = <?= json_encode($events_data) ?>;
const ME            = <?= json_encode(['id'=>$me['id'],'firstname'=>$me['firstname'],'lastname'=>$me['lastname'],'image'=>$me['image'],'grade'=>$me['grade'],'domain'=>$me['domain']]) ?>;
const ACCESS_TOKEN  = <?= json_encode($_SESSION['access']) ?>;

const CAT_CLASS = {nlp:'cat-nlp',vision:'cat-vision',data:'cat-data',rl:'cat-rl',ml:'cat-ml',other:'cat-other'};
const CAT_LABEL = {nlp:'NLP',vision:'Vision',data:'Data',rl:'RL',ml:'ML',other:'Autre'};
const EVENT_TYPE_LABELS = {workshop:'Workshop',conference:'Conférence',competition:'Compétition',seminar:'Séminaire',other:'Événement'};
const EVENT_TYPE_CSS    = {workshop:'etype-workshop',conference:'etype-conference',competition:'etype-competition',seminar:'etype-seminar',other:'etype-other'};
const EVENT_TYPE_ICONS  = {workshop:'fa-screwdriver-wrench',conference:'fa-microphone-lines',competition:'fa-trophy',seminar:'fa-chalkboard-user',other:'fa-calendar-days'};

let currentFilter  = 'all';
let visibleCount   = 4;
// Initialize joinedEventIds from is_registered returned by API
let joinedEventIds = new Set(EVENTS.filter(e => e.is_registered).map(e => e.id));
let currentTab     = 'projects';

function _post(payload) {
  var f = document.createElement('form');
  f.method='POST';f.action='dashboard.php';f.style.display='none';
  var fi=document.createElement('input');fi.name='_imadenc';fi.value=_aes(payload);f.appendChild(fi);
  var fd=document.createElement('input');fd.name='_dok';fd.value=_aes({t:Date.now()});f.appendChild(fd);
  document.body.appendChild(f);f.submit();
}

function getInitials(f,l){return((f||'')[0]||'').toUpperCase()+((l||'')[0]||'').toUpperCase();}
function timeAgo(iso){const d=(Date.now()-new Date(iso))/1000;if(d<3600)return`il y a ${Math.floor(d/60)} min`;if(d<86400)return`il y a ${Math.floor(d/3600)} h`;if(d<172800)return'hier';return`il y a ${Math.floor(d/86400)} jours`;}
function showToast(msg){const t=document.getElementById('toast');document.getElementById('toast-msg').textContent=msg;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),3200);}

// ─── TABS ───
function switchTab(tab,btn){
  currentTab=tab;
  document.querySelectorAll('.feed-tab').forEach(b=>b.classList.remove('active'));btn.classList.add('active');
  document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));
  document.getElementById('panel-'+tab).classList.add('active');
  if(tab==='announcements')renderAnnouncements();
  if(tab==='events')renderEvents();
  if(tab==='myevents')renderMyEvents();
  if(tab==='university')loadUniversityInfo();
}

// ─── PROJECTS ───
function getFiltered(){return PROJECTS.filter(p=>currentFilter==='all'||p.category===currentFilter);}
function renderFeed(){
  const cont=document.getElementById('feed-container');
  const empty=document.getElementById('feed-empty');
  const loadBtn=document.getElementById('load-more-wrap');
  const projects=getFiltered();
  cont.innerHTML='';
  if(!projects.length){empty.style.display='block';loadBtn.style.display='none';return;}
  empty.style.display='none';
  projects.slice(0,visibleCount).forEach((p,i)=>cont.appendChild(buildProjectCard(p,i*80)));
  loadBtn.style.display=projects.length>visibleCount?'block':'none';
}
function loadMore(){visibleCount+=4;renderFeed();}
function filterFeed(btn,cat){currentFilter=cat;visibleCount=4;document.querySelectorAll('#feed-filters .ff-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');renderFeed();}
function filterByTag(pill,cat){currentFilter=cat;visibleCount=4;document.querySelectorAll('.tag-pill').forEach(t=>t.classList.remove('active'));pill.classList.add('active');renderFeed();}
function buildProjectCard(p,delay){
  const initials=getInitials(p.owner.firstname,p.owner.lastname);
  const catClass=CAT_CLASS[p.category]||'cat-other';
  const catLabel=CAT_LABEL[p.category]||p.category;
  const statusHtml=p.status==='open'&&!p.is_full?`<span class="pc-status st-open"><span class="status-dot"></span>Ouvert</span>`:p.is_full?`<span class="pc-status st-full"><span class="status-dot"></span>Complet</span>`:`<span class="pc-status st-done"><span class="status-dot"></span>Terminé</span>`;
  const avHtml=p.owner.image?`<img src="${p.owner.image}" alt="">`:initials;
  const liked=p._liked||false;
  const likeCount=p._like_count!=null?p._like_count:(p.like_count||0);
  const commentCount=p._comment_count!=null?p._comment_count:(p.comment_count||0);
  // Project image section — only show if image exists
  const imgSection=p.image
    ?`<div class="pc-img-wrap"><img class="pc-img" src="${p.image}" alt="" loading="lazy" onerror="this.closest('.pc-img-wrap').style.display='none'"></div>`
    :'';
  const el=document.createElement('div');
  el.className='project-card lk-card';el.style.animationDelay=delay+'ms';el.dataset.projectId=p.id;
  el.innerHTML=`
    <div class="lk-header" onclick="window.location.href='profile.php?id=${p.owner.id||0}'" style="cursor:pointer">
      <div class="pch-av">${avHtml}</div>
      <div class="pch-info">
        <div class="pch-name">${p.owner.firstname} ${p.owner.lastname}</div>
        <div class="pch-meta"><span>${p.owner.grade||'Étudiant'}</span><span class="pch-dot"></span><span>${timeAgo(p.created_at||new Date().toISOString())}</span></div>
      </div>
      <span class="pc-cat-badge ${catClass}">${catLabel}</span>
    </div>
    <div class="lk-body">
      <div class="pc-title">${p.title}</div>
      ${p.description?`<p class="pc-desc">${p.description}</p>`:''}
      <div class="lk-meta-row">${statusHtml}</div>
    </div>
    ${imgSection}
    <div class="lk-stats-bar">
      <span class="lk-stat-item"><i class="fa-solid fa-heart" style="color:var(--orange)"></i> <span class="lk-like-count">${likeCount}</span></span>
      <span class="lk-stat-item" style="cursor:pointer" onclick="toggleComments(this.closest('.lk-card').querySelector('.lk-action-btn:last-child'),${p.id})"><i class="fa-regular fa-comment" style="color:var(--muted)"></i> <span class="lk-cmt-count">${commentCount}</span></span>
    </div>
    <div class="lk-divider"></div>
    <div class="lk-actions">
      <button class="lk-action-btn${liked?' lk-liked':''}" onclick="toggleLike(this,${p.id})">
        <i class="${liked?'fa-solid':'fa-regular'} fa-heart"></i> J'aime
      </button>
      <button class="lk-action-btn" onclick="toggleComments(this,${p.id})">
        <i class="fa-regular fa-comment"></i> Commenter
      </button>
    </div>
    <div class="lk-comments-panel" id="cmt-panel-${p.id}" style="display:none">
      <div class="lk-cmt-input-row">
        <div class="lk-cmt-av">${ME.image?`<img src="${ME.image}" alt="">`:getInitials(ME.firstname,ME.lastname)}</div>
        <div class="lk-cmt-input-wrap">
          <input class="lk-cmt-input" id="cmt-input-${p.id}" type="text" placeholder="Écrire un commentaire…" onkeydown="if(event.key==='Enter')submitComment(${p.id})">
          <button class="lk-cmt-send" onclick="submitComment(${p.id})"><i class="fa-solid fa-paper-plane"></i></button>
        </div>
      </div>
      <div class="lk-cmt-list" id="cmt-list-${p.id}"><div class="lk-cmt-loading"><i class="fa-solid fa-spinner fa-spin"></i></div></div>
    </div>`;
  return el;
}

// ─── LIKE STATE — use is_liked from API (already returned by GET /projects) ───
// No localStorage needed — the server already tells us per-user like state
PROJECTS.forEach(p => {
  p._liked      = p.is_liked  ?? false;
  p._like_count = p.like_count ?? 0;
});

// ─── SECURE AJAX via PHP proxy ───
async function _postAjax(payload){
  const fd=new FormData();
  fd.append('_imadenc',_aes(payload));
  fd.append('_dok',_aes({t:Date.now()}));
  const r=await fetch('dashboard.php',{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body:fd});
  return r.json();
}

// ─── LIKE ───
async function toggleLike(btn, projectId){
  btn.disabled = true;
  try {
    const data = await _postAjax({_action:'toggle_like', id:projectId});
    if(data.error){showToast(data.error);return;}
    const liked = data.liked;
    const count = data.like_count ?? 0;
    btn.classList.toggle('lk-liked', liked);
    btn.innerHTML = `<i class="${liked?'fa-solid':'fa-regular'} fa-heart"></i> J'aime`;
    btn.closest('.lk-card').querySelector('.lk-like-count').textContent = count;
    const p = PROJECTS.find(x => x.id === projectId);
    if (p) { p._liked = liked; p._like_count = count; }
  } catch(e) {
    showToast('Erreur réseau');
  } finally {
    btn.disabled = false;
  }
}

// ─── COMMENTS ───
const _loadedComments={};
async function toggleComments(btn,projectId){
  const panel=document.getElementById('cmt-panel-'+projectId);
  const open=panel.style.display!=='none';
  panel.style.display=open?'none':'block';
  if(!open&&!_loadedComments[projectId]) await loadComments(projectId);
}
async function loadComments(projectId){
  const list=document.getElementById('cmt-list-'+projectId);
  list.innerHTML=`<div class="lk-cmt-loading"><i class="fa-solid fa-spinner fa-spin"></i></div>`;
  try{
    const data=await _postAjax({_action:'load_comments',id:projectId});
    _loadedComments[projectId]=true;
    const comments=data.comments||[];
    if(!comments.length){list.innerHTML=`<div class="lk-cmt-empty">Aucun commentaire. Soyez le premier !</div>`;return;}
    list.innerHTML=comments.map(cm=>{
      const au=cm.user||cm.author||{};
      const cav=au.image?`<img src="${au.image}" alt="">`:getInitials(au.firstname||'?',au.lastname||'');
      const isMe=au.id===ME.id;
      return`<div class="lk-cmt-item" id="cmt-${cm.id}">
        <div class="lk-cmt-av">${cav}</div>
        <div class="lk-cmt-bubble">
          <div class="lk-cmt-author">${au.firstname||''} ${au.lastname||''}</div>
          <div class="lk-cmt-text">${cm.content}</div>
          <div class="lk-cmt-meta">${timeAgo(cm.created_at)}${isMe?` · <span class="lk-cmt-del" onclick="deleteComment(${projectId},${cm.id})">Supprimer</span>`:''}</div>
        </div>
      </div>`;
    }).join('');
  }catch(e){list.innerHTML=`<div class="lk-cmt-empty">Erreur de chargement.</div>`;}
}
async function submitComment(projectId){
  const input=document.getElementById('cmt-input-'+projectId);
  const text=input.value.trim();
  if(!text) return;
  input.value='';
  try{
    const data=await _postAjax({_action:'add_comment',id:projectId,content:text});
    if(data.error){showToast(data.error);return;}
    const cm=data.comment;
    if(!cm) return;
    const list=document.getElementById('cmt-list-'+projectId);
    const cav=ME.image?`<img src="${ME.image}" alt="">`:getInitials(ME.firstname,ME.lastname);
    const newEl=document.createElement('div');
    newEl.className='lk-cmt-item';newEl.id='cmt-'+cm.id;
    newEl.innerHTML=`<div class="lk-cmt-av">${cav}</div><div class="lk-cmt-bubble"><div class="lk-cmt-author">${ME.firstname} ${ME.lastname}</div><div class="lk-cmt-text">${cm.content}</div><div class="lk-cmt-meta">À l'instant · <span class="lk-cmt-del" onclick="deleteComment(${projectId},${cm.id})">Supprimer</span></div></div>`;
    list.querySelector('.lk-cmt-empty')?.remove();
    list.appendChild(newEl);
    const card=document.querySelector(`.lk-card[data-project-id="${projectId}"]`);
    if(card){const el=card.querySelector('.lk-cmt-count');if(el)el.textContent=parseInt(el.textContent||0)+1;}
    const p=PROJECTS.find(x=>x.id===projectId);
    if(p) p._comment_count=(p._comment_count??p.comment_count??0)+1;
  }catch(e){showToast('Erreur réseau');}
}
async function deleteComment(projectId,commentId){
  if(!confirm('Supprimer ce commentaire ?')) return;
  try{
    const data=await _postAjax({_action:'delete_comment',project_id:projectId,comment_id:commentId});
    if(data.ok){
      document.getElementById('cmt-'+commentId)?.remove();
      const card=document.querySelector(`.lk-card[data-project-id="${projectId}"]`);
      if(card){const el=card.querySelector('.lk-cmt-count');if(el)el.textContent=Math.max(0,parseInt(el.textContent||0)-1);}
    } else {
      showToast(data.error||'Erreur suppression');
    }
  }catch(e){showToast('Erreur réseau');}
}

// ─── FOLLOW (from dashboard modal) ───
async function toggleFollow(userId,btn){
  try{
    const data=await _postAjax({_action:'toggle_follow',id:userId});
    if(data.error){showToast(data.error);return;}
    const following=data.following;
    btn.classList.toggle('sp-following',following);
    btn.innerHTML=following?`<i class="fa-solid fa-user-check"></i> Abonné`:`<i class="fa-solid fa-user-plus"></i> Suivre`;
    const countEl=document.getElementById('sp-followers-count');
    if(countEl) countEl.textContent=data.followers_count??parseInt(countEl.textContent||0)+(following?1:-1);
  }catch(e){showToast('Erreur réseau');}
}

// ─── TOP STUDENTS ───
function renderTopStudents(){
  const list=document.getElementById('top-students-list');
  const counts={};PROJECTS.forEach(p=>{const k=p.owner.id||p.owner.firstname+'_'+p.owner.lastname;counts[k]=(counts[k]||0)+1;});
  const sorted=Object.entries(counts).sort((a,b)=>b[1]-a[1]).slice(0,5);
  const ownerMap={};PROJECTS.forEach(p=>{const k=p.owner.id||p.owner.firstname+'_'+p.owner.lastname;ownerMap[k]=p.owner;});
  // Count my projects by ID
  const myCount=PROJECTS.filter(p=>p.owner.id&&ME.id?p.owner.id===ME.id:(p.owner.firstname===ME.firstname&&p.owner.lastname===ME.lastname)).length;
  document.getElementById('my-proj-count').textContent=myCount;
  if(!sorted.length){list.innerHTML='<div style="padding:12px 18px;font-size:.8rem;color:var(--muted)">Aucun projet encore.</div>';return;}
  list.innerHTML=sorted.map(([k,c],i)=>{const o=ownerMap[k];const av=o.image?`<img src="${o.image}" alt="">`:getInitials(o.firstname,o.lastname);return`<div class="top-student" onclick="window.location.href='profile.php?id=${o.id||''}'"><div class="ts-rank ${i===0?'rank-1':''}">${i+1}</div><div class="ts-av">${av}</div><div class="ts-info"><div class="ts-name">${o.firstname} ${o.lastname}</div><div class="ts-count">${c} projet${c>1?'s':''}</div></div><span class="ts-badge ${i===0?'tb-o':'tb-g'}">${(o.grade||'').split(' ')[0]||'—'}</span></div>`;}).join('');
}

// ─── ANNOUNCEMENTS ───
function renderAnnouncements(){
  const cont=document.getElementById('ann-container'),empty=document.getElementById('ann-empty');
  if(!ANNOUNCEMENTS.length){empty.style.display='block';cont.innerHTML='';return;}
  empty.style.display='none';
  cont.innerHTML=[...ANNOUNCEMENTS].sort((a,b)=>(b.is_pinned?1:0)-(a.is_pinned?1:0)).map((a,i)=>{
    const av=a.author?.image?`<img src="${a.author.image}" alt="">`:getInitials(a.author?.firstname||'P',a.author?.lastname||'');
    const img=a.image?`<img src="${a.image}" class="ann-img" alt="">`:''
    return`<div class="ann-card${a.is_pinned?' pinned':''}" style="animation-delay:${i*70}ms">${a.is_pinned?'<div class="ann-pin-bar"></div>':''}<div class="ann-header"><div class="ann-av">${av}</div><div class="ann-author-info"><div class="ann-author-name">${a.author?.firstname||''} ${a.author?.lastname||''}</div><div class="ann-author-meta"><span>Professeur</span><span class="pch-dot"></span><span>${timeAgo(a.created_at)}</span></div></div>${a.is_pinned?'<span class="ann-pin-badge"><i class="fa-solid fa-thumbtack"></i> Épinglée</span>':''}</div><div class="ann-body"><div class="ann-title">${a.title}</div><div class="ann-content">${a.content}</div>${img}</div></div>`;
  }).join('');
}

// ─── EVENTS ───
function renderEvents(){
  const cont=document.getElementById('events-container'),empty=document.getElementById('events-empty');
  if(!EVENTS.length){empty.style.display='block';cont.innerHTML='';return;}
  empty.style.display='none';
  cont.innerHTML=EVENTS.map((e,i)=>buildEventCard(e,i*80)).join('');
}
function buildEventCard(e,delay){
  const d=new Date(e.start_at);
  const dateStr=d.toLocaleDateString('fr-FR',{weekday:'long',day:'numeric',month:'long'});
  const timeStr=d.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit'});
  const capPct=e.capacity?Math.round((e.registered_count/e.capacity)*100):0;
  const capClass=capPct>=100?'cap-full':capPct>=80?'cap-warn':'';
  const typeLabel=EVENT_TYPE_LABELS[e.event_type]||'Événement';
  const typeCss=EVENT_TYPE_CSS[e.event_type]||'etype-other';
  const typeIcon=EVENT_TYPE_ICONS[e.event_type]||'fa-calendar-days';
  const joined=joinedEventIds.has(e.id);
  const imgContent=e.image?`<img src="${e.image}" alt="">`:
    `<div style="width:100%;height:100%;background:linear-gradient(135deg,var(--green) 0%,var(--dark) 100%);display:flex;align-items:center;justify-content:center;font-size:3rem;color:rgba(255,255,255,.25)"><i class="fa-solid ${typeIcon}"></i></div>`;
  const btnHtml=e.is_full&&!joined?`<button class="event-join-btn" disabled>Complet</button>`:joined?`<button class="event-join-btn joined-btn" onclick="_post({_action:'leave_event',id:${e.id}})"><i class="fa-solid fa-check"></i> Inscrit</button>`:`<button class="event-join-btn" onclick="_post({_action:'join_event',id:${e.id}})">S'inscrire</button>`;
  return`<div class="event-card${joined?' joined':''}" style="animation-delay:${delay}ms"><div class="event-img-wrap">${imgContent}<span class="event-type-badge ${typeCss}"><i class="fa-solid ${typeIcon}" style="margin-right:4px"></i>${typeLabel}</span><span class="event-joined-badge"><i class="fa-solid fa-check"></i> Inscrit</span></div><div class="event-body"><div class="event-title">${e.title}</div><div class="event-meta"><div class="event-meta-row"><i class="fa-solid fa-calendar"></i>${dateStr}</div><div class="event-meta-row"><i class="fa-regular fa-clock"></i>${timeStr}</div><div class="event-meta-row"><i class="fa-solid fa-location-dot"></i>${e.location||'—'}</div></div><div class="event-desc">${e.description||''}</div></div><div class="event-footer"><div class="event-capacity"><div class="event-cap-bar"><div class="event-cap-fill ${capClass}" style="width:${Math.min(capPct,100)}%"></div></div><span>${e.registered_count}${e.capacity?'/'+e.capacity:''} inscrits</span></div>${btnHtml}</div></div>`;
}
function renderMyEvents(){
  const cont=document.getElementById('myevents-container'),empty=document.getElementById('myevents-empty');
  const myList=EVENTS.filter(e=>joinedEventIds.has(e.id));
  if(!myList.length){empty.style.display='block';cont.innerHTML='';return;}
  empty.style.display='none';
  cont.innerHTML=myList.map(e=>{const d=new Date(e.start_at);const typeCss=EVENT_TYPE_CSS[e.event_type]||'etype-other';const typeLabel=EVENT_TYPE_LABELS[e.event_type]||'Événement';return`<div class="my-event-item"><div class="my-event-date"><div class="my-event-day">${d.getDate()}</div><div class="my-event-month">${d.toLocaleDateString('fr-FR',{month:'short'})}</div></div><div class="my-event-info"><div class="my-event-name">${e.title}</div><div class="my-event-loc"><i class="fa-solid fa-location-dot"></i>${e.location||'—'}</div></div><span class="my-event-type ${typeCss}">${typeLabel}</span></div>`;}).join('');
}
function renderSidebarEvents(){
  const el=document.getElementById('sidebar-events');
  const upcoming=EVENTS.filter(e=>!e.is_full).slice(0,3);
  if(!upcoming.length){el.innerHTML='<div style="padding:12px 18px;font-size:.8rem;color:var(--muted)">Aucun événement à venir.</div>';return;}
  el.innerHTML=upcoming.map(e=>{const d=new Date(e.start_at);return`<div class="upcoming-event"><div class="ue-date"><div class="ue-day">${d.getDate()}</div><div class="ue-month">${d.toLocaleDateString('fr-FR',{month:'short'})}</div></div><div class="ue-info"><div class="ue-name">${e.title.length>42?e.title.slice(0,42)+'…':e.title}</div><div class="ue-loc"><i class="fa-solid fa-location-dot" style="margin-right:3px"></i>${e.location||'—'}</div></div></div>`;}).join('');
}

// ─── SEARCH ───
let searchTimer=null,currentSearch='';
function handleSearch(val){currentSearch=val.trim();document.getElementById('search-clear').style.display=currentSearch?'block':'none';if(!currentSearch){closeDropdown();return;}document.getElementById('search-dropdown').innerHTML=`<div class="sd-empty"><i class="fa-solid fa-spinner fa-spin" style="margin-right:6px"></i>Recherche…</div>`;document.getElementById('search-dropdown').classList.add('open');clearTimeout(searchTimer);searchTimer=setTimeout(()=>doSearch(currentSearch),350);}
async function doSearch(q){try{const data=await _postAjax({_action:'search',name:q});const dd=document.getElementById('search-dropdown');if(!currentSearch)return;const results=data.results||[];if(!results.length){dd.innerHTML=`<div class="sd-empty">Aucun étudiant trouvé pour "<strong>${q}</strong>"</div>`;return;}const hl=str=>str.replace(new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'gi'),m=>`<span class="sd-highlight">${m}</span>`);dd.innerHTML=`<div class="sd-section"><div class="sd-label">Étudiants (${results.length})</div>${results.map(s=>`<div class="sd-item" onmousedown="window.location.href='profile.php?id=${s.id}'"><div class="sd-av">${s.image?`<img src="${s.image}" alt="">`:getInitials(s.firstname,s.lastname)}</div><div><div class="sd-name">${hl(s.firstname+' '+s.lastname)}</div><div class="sd-sub">Étudiant</div></div></div>`).join('')}</div>`;}catch(e){document.getElementById('search-dropdown').innerHTML=`<div class="sd-empty">Erreur de connexion</div>`;}}
function openDropdown(){if(currentSearch)document.getElementById('search-dropdown').classList.add('open');}
function closeDropdown(){document.getElementById('search-dropdown').classList.remove('open');}
function closeDropdownDelayed(){setTimeout(closeDropdown,200);}
function clearSearch(){document.getElementById('student-search').value='';currentSearch='';document.getElementById('search-clear').style.display='none';closeDropdown();}

// ─── MOBILE SEARCH ───
function openMobileSearch(){document.getElementById('mob-search-overlay').classList.add('open');document.body.style.overflow='hidden';setTimeout(()=>document.getElementById('mob-search-input').focus(),100);}
function closeMobileSearch(){document.getElementById('mob-search-overlay').classList.remove('open');document.getElementById('mob-search-input').value='';document.getElementById('mob-search-results').innerHTML=`<div class="sd-empty" style="padding:40px 20px">Tapez un nom pour rechercher</div>`;document.body.style.overflow='';}
let mobTimer=null;
function handleMobSearch(val){const q=val.trim();const res=document.getElementById('mob-search-results');if(!q){res.innerHTML=`<div class="sd-empty" style="padding:40px 20px">Tapez un nom pour rechercher</div>`;return;}res.innerHTML=`<div class="sd-empty" style="padding:40px 20px"><i class="fa-solid fa-spinner fa-spin" style="margin-right:8px"></i>Recherche…</div>`;clearTimeout(mobTimer);mobTimer=setTimeout(async()=>{try{const data=await _postAjax({_action:'search',name:q});const results=data.results||[];if(!results.length){res.innerHTML=`<div class="sd-empty" style="padding:40px 20px">Aucun étudiant trouvé</div>`;return;}const hl=str=>str.replace(new RegExp(q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'),'gi'),m=>`<span class="sd-highlight">${m}</span>`);res.innerHTML=`<div class="sd-section"><div class="sd-label" style="padding:12px 16px 6px">Étudiants (${results.length})</div>${results.map(s=>`<div class="sd-item" onclick="window.location.href='profile.php?id=${s.id}'"><div class="sd-av">${s.image?`<img src="${s.image}" alt="">`:getInitials(s.firstname,s.lastname)}</div><div style="flex:1"><div class="sd-name">${hl(s.firstname+' '+s.lastname)}</div><div class="sd-sub">Étudiant</div></div></div>`).join('')}</div>`;}catch(e){res.innerHTML=`<div class="sd-empty" style="padding:40px 20px">Erreur de connexion</div>`;}},350);}

// ─── CREATE PROJECT ───
function openCreateProject(){document.getElementById('create-project-modal').classList.add('open');document.body.style.overflow='hidden';}
function closeCreateProject(){
  document.getElementById('create-project-modal').classList.remove('open');
  document.body.style.overflow='';
  // reset image
  clearProjImg();
  document.getElementById('proj-title').value='';
  document.getElementById('proj-desc').value='';
  document.getElementById('modal-err').style.display='none';
}
function handleModalOverlayClick(e){if(e.target===document.getElementById('create-project-modal'))closeCreateProject();}

function previewProjImg(input){
  const f=input.files[0];
  if(!f) return;
  if(f.size > 5*1024*1024){ document.getElementById('modal-err').textContent='Image trop grande (max 5 Mo).';document.getElementById('modal-err').style.display='block';input.value='';return;}
  document.getElementById('modal-err').style.display='none';
  const rd=new FileReader();
  rd.onload=function(e){
    const b64=e.target.result; // full data URL e.g. "data:image/jpeg;base64,..."
    document.getElementById('proj-img-b64').value=b64;
    document.getElementById('proj-img-preview').src=b64;
    document.getElementById('proj-img-preview').style.display='block';
    document.getElementById('proj-img-ph').style.display='none';
    document.getElementById('proj-img-clear').style.display='flex';
  };
  rd.readAsDataURL(f);
}
function clearProjImg(e){
  if(e) e.stopPropagation();
  document.getElementById('proj-img-b64').value='';
  document.getElementById('proj-img-preview').style.display='none';
  document.getElementById('proj-img-preview').src='';
  document.getElementById('proj-img-ph').style.display='flex';
  document.getElementById('proj-img-clear').style.display='none';
  document.getElementById('proj-img-file').value='';
}

function submitCreateProject(){
  var title=document.getElementById('proj-title').value.trim();
  var errBox=document.getElementById('modal-err');
  if(!title){errBox.textContent='Le titre est requis.';errBox.style.display='block';return;}
  errBox.style.display='none';
  var payload={
    _action:'create_project',
    title:title,
    description:document.getElementById('proj-desc').value.trim()||null,
    category:document.getElementById('proj-category').value,
    status:document.getElementById('proj-status').value,
    is_visible:document.getElementById('proj-visible').checked
  };
  var imgB64=document.getElementById('proj-img-b64').value;
  if(imgB64) payload.image=imgB64;
  _post(payload);
}

// ─── STUDENT PROFILE MODAL ───
async function openStudentProfile(id){document.getElementById('sp-modal').classList.add('open');document.body.style.overflow='hidden';document.getElementById('sp-loading').style.display='flex';document.getElementById('sp-content').innerHTML='';try{const data=await _postAjax({_action:'get_user_info',id});document.getElementById('sp-loading').style.display='none';renderStudentProfile(data);}catch(e){document.getElementById('sp-loading').style.display='none';document.getElementById('sp-content').innerHTML=`<div class="empty-state"><div class="es-icon"><i class="fa-solid fa-wifi"></i></div><div class="es-title">Erreur de chargement</div></div>`;}}
function closeStudentProfile(){document.getElementById('sp-modal').classList.remove('open');document.body.style.overflow='';}
const GRADE_LABELS={licence:'Licence',master:'Master',doctorat:'Doctorat'};
const DOMAIN_LABELS={'intelligence artificielle':'Intelligence Artificielle','developpement web':'Développement Web','cyber securite':'Cyber Sécurité','reseaux et telecommunications':'Réseaux & Télécoms','systemes embarques':'Systèmes Embarqués','science des donnees':'Science des Données','genie logiciel':'Génie Logiciel','autre':'Autre'};
function renderStudentProfile(data){const u=data.user||{};const projects=data.projects||[];const initials=getInitials(u.firstname||'',u.lastname||'');const avatarHtml=u.image?`<img src="${u.image}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%">`:initials;const joinDate=u.created_at?new Date(u.created_at).toLocaleDateString('fr-FR',{year:'numeric',month:'long'}):'—';const owned=projects.filter(p=>p.role==='owner');const followersCount=data.followers_count??u.followers_count??0;const followingCount=data.following_count??u.following_count??0;const isFollowing=data.is_following||false;const isSelf=u.id===ME.id;const followBtn=!isSelf?`<button class="sp-follow-btn${isFollowing?' sp-following':''}" onclick="toggleFollow(${u.id},this)">${isFollowing?'<i class="fa-solid fa-user-check"></i> Abonné':'<i class="fa-solid fa-user-plus"></i> Suivre'}</button>`:'';document.getElementById('sp-content').innerHTML=`<div class="sp-hero"><div class="sp-avatar">${avatarHtml}</div><div class="sp-hero-info"><div class="sp-name">${u.firstname||''} ${u.lastname||''}</div><div class="sp-grade"><i class="fa-solid fa-graduation-cap"></i>${GRADE_LABELS[u.grade]||u.grade||'—'}</div><div class="sp-domain"><i class="fa-solid fa-microchip"></i>${DOMAIN_LABELS[u.domain]||u.domain||'—'}</div>${followBtn}</div></div><div class="sp-stats"><div class="sp-stat"><div class="sp-stat-val">${owned.length}</div><div class="sp-stat-lbl">Projets</div></div><div class="sp-stat-div"></div><div class="sp-stat"><div class="sp-stat-val" id="sp-followers-count">${followersCount}</div><div class="sp-stat-lbl">Abonnés</div></div><div class="sp-stat-div"></div><div class="sp-stat"><div class="sp-stat-val">${followingCount}</div><div class="sp-stat-lbl">Abonnements</div></div></div><div class="sp-info-section">${u.email?`<div class="sp-info-row"><div class="sp-info-icon"><i class="fa-solid fa-envelope"></i></div><div><div class="sp-info-label">Email</div><div class="sp-info-val">${u.email}</div></div></div>`:''}<div class="sp-info-row"><div class="sp-info-icon"><i class="fa-regular fa-calendar"></i></div><div><div class="sp-info-label">Membre depuis</div><div class="sp-info-val">${joinDate}</div></div></div></div>${projects.length?`<div class="sp-projects-section"><div class="sp-section-title"><i class="fa-solid fa-flask" style="color:var(--green)"></i>Projets (${projects.length})</div>${projects.map(p=>{const catClass=CAT_CLASS[p.category]||'cat-other';const catLabel=CAT_LABEL[p.category]||p.category;const isOwner=p.role==='owner';return`<div class="sp-project-card"><div class="sp-proj-top"><span class="pc-cat-badge ${catClass}">${catLabel}</span>${isOwner?`<span class="sp-owner-badge"><i class="fa-solid fa-crown"></i> Créateur</span>`:`<span class="sp-contrib-badge"><i class="fa-solid fa-users"></i> Contributeur</span>`}</div><div class="sp-proj-title">${p.title}</div>${p.description?`<div class="sp-proj-desc">${p.description}</div>`:''}</div>`;}).join('')}</div>`:`<div class="sp-no-projects"><i class="fa-solid fa-flask"></i><span>Aucun projet pour l'instant</span></div>`}`;}

// ─── UNIVERSITY ───
let uniLoaded=false,uniPosts=[];
async function loadUniversityInfo(){if(uniLoaded)return;document.getElementById('uni-loading').style.display='block';document.getElementById('uni-posts-list').innerHTML='';try{const data=await _postAjax({_action:'get_uni_posts'});uniLoaded=true;uniPosts=data.posts||[];document.getElementById('uni-loading').style.display='none';if(!uniPosts.length){document.getElementById('uni-posts-list').innerHTML=`<div class="empty-state"><div class="es-icon"><i class="fa-solid fa-university"></i></div><div class="es-title">Aucune actualité</div></div>`;return;}document.getElementById('uni-posts-list').innerHTML=uniPosts.map((p,i)=>{
  const imgHtml=p.image
    ?`<img class="uni-post-img" src="${p.image}" alt="" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"><div class="uni-post-img-ph" style="display:none"><i class="fa-solid fa-newspaper"></i></div>`
    :`<div class="uni-post-img-ph"><i class="fa-solid fa-newspaper"></i></div>`;
  return`<div class="uni-post-item" style="animation-delay:${i*70}ms" onclick="openPostDrawer(${p.id},'${p.title.replace(/'/g,"\\'")}'">${imgHtml}<div class="uni-post-body"><div class="uni-post-header"><span class="uni-post-badge"><i class="fa-solid fa-university"></i> UHBC</span><span class="uni-post-date-badge"><i class="fa-regular fa-calendar"></i>${p.date}</span></div><div class="uni-post-title">${p.title}</div></div><div class="uni-post-footer"><span class="uni-post-read-btn"><i class="fa-solid fa-book-open"></i> Lire <i class="fa-solid fa-chevron-right" style="font-size:.6rem"></i></span></div></div>`;
}).join('');}catch(e){document.getElementById('uni-loading').style.display='none';document.getElementById('uni-posts-list').innerHTML=`<div class="empty-state"><div class="es-icon"><i class="fa-solid fa-wifi"></i></div><div class="es-title">Erreur de chargement</div></div>`;}}

// ─── DRAWER ───
async function openPostDrawer(id,title){document.getElementById('drawer-title').textContent='Actualité';document.getElementById('drawer-content').style.display='none';document.getElementById('drawer-loading').style.display='flex';document.getElementById('drawer-overlay').classList.add('open');document.getElementById('post-drawer').classList.add('open');document.body.style.overflow='hidden';try{const data=await _postAjax({_action:'get_uni_post',id});document.getElementById('drawer-loading').style.display='none';document.getElementById('drawer-content').style.display='block';let html='';if(data.text)html+=`<div class="drawer-text">${data.text}</div>`;if(data.images?.length){html+=`<div class="drawer-imgs-title"><i class="fa-solid fa-images" style="color:var(--green)"></i> Photos (${data.images.length})</div><div class="drawer-img-grid">`;data.images.forEach(src=>{html+=`<img src="${src}" alt="" loading="lazy" onclick="window.open('${src}','_blank')" onerror="this.style.display='none'">`});html+=`</div>`;}if(data.pdf?.length){data.pdf.forEach((url,i)=>{const name=url.split('/').pop()||`document-${i+1}.pdf`;html+=`<div class="drawer-pdf-viewer"><div class="drawer-pdf-viewer-header"><div style="display:flex;align-items:center;gap:8px"><div class="drawer-pdf-icon"><i class="fa-solid fa-file-pdf"></i></div><div style="font-size:.82rem;font-weight:700;color:var(--text)">${name}</div></div><a href="${url}" target="_blank" rel="noopener" class="drawer-pdf-open-btn"><i class="fa-solid fa-arrow-up-right-from-square"></i> Ouvrir</a></div><iframe src="${url}" class="drawer-pdf-iframe" title="${name}"></iframe></div>`;});}if(!data.text&&!data.images?.length&&!data.pdf?.length)html=`<div class="empty-state"><div class="es-icon"><i class="fa-solid fa-file-circle-question"></i></div><div class="es-title">Contenu indisponible</div></div>`;document.getElementById('drawer-content').innerHTML=html;}catch(e){document.getElementById('drawer-loading').style.display='none';document.getElementById('drawer-content').style.display='block';document.getElementById('drawer-content').innerHTML=`<div class="empty-state"><div class="es-icon"><i class="fa-solid fa-wifi"></i></div><div class="es-title">Erreur de chargement</div></div>`;}}
function closeDrawer(){document.getElementById('drawer-overlay').classList.remove('open');document.getElementById('post-drawer').classList.remove('open');document.body.style.overflow='';}

// ─── STORY VIEWER ───
let storyItems=[],storyCurrent=0,storyTimer=null;const STORY_DURATION=5000;
function openStoryViewer(i){storyCurrent=i;document.getElementById('story-viewer').classList.add('open');document.body.style.overflow='hidden';renderStory(i);startStoryTimer();}
function closeStoryViewer(){clearTimeout(storyTimer);document.getElementById('story-viewer').classList.remove('open');document.body.style.overflow='';}
function renderStory(i){const s=storyItems[i];if(!s){closeStoryViewer();return;}document.getElementById('sv-progress').innerHTML=storyItems.map((_,j)=>`<div class="sv-prog-seg"><div class="sv-prog-fill${j<i?' instant':''}" id="svp-${j}"></div></div>`).join('');document.getElementById('sv-av').innerHTML=s.image&&s.type!=='uni'?`<img src="${s.image}" alt="">`:s.initials;document.getElementById('sv-name').textContent=s.source;document.getElementById('sv-time').textContent=s.date;document.getElementById('sv-title').textContent=s.title;document.getElementById('sv-date-text').textContent=s.date;const src=s.image||null;const bgImg=document.getElementById('sv-bg-img');const mainImg=document.getElementById('sv-main-img');const imgPh=document.getElementById('sv-img-ph');if(src){bgImg.src=src;mainImg.src=src;mainImg.style.display='block';imgPh.style.display='none';mainImg.onerror=()=>{mainImg.style.display='none';imgPh.style.display='flex';};}else{bgImg.src='';mainImg.style.display='none';imgPh.style.display='flex';imgPh.innerHTML=s.type==='ann'?'<i class="fa-solid fa-bullhorn"></i>':'<i class="fa-solid fa-university"></i>';}document.getElementById('sv-read-btn').onclick=()=>{closeStoryViewer();if(s.type==='uni')openPostDrawer(s.postId,s.title);else{document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));document.getElementById('panel-announcements').classList.add('active');renderAnnouncements();}};}
function startStoryTimer(){clearTimeout(storyTimer);const fill=document.getElementById('svp-'+storyCurrent);if(fill){fill.style.transition=`width ${STORY_DURATION}ms linear`;fill.style.width='100%';}storyTimer=setTimeout(()=>storyNext(),STORY_DURATION);}
function storyNext(){clearTimeout(storyTimer);if(storyCurrent<storyItems.length-1){storyCurrent++;renderStory(storyCurrent);startStoryTimer();}else closeStoryViewer();}
function storyPrev(){clearTimeout(storyTimer);if(storyCurrent>0){storyCurrent--;renderStory(storyCurrent);startStoryTimer();}}

// ─── MOBILE TABS ───
function mobileTab(tab,btn){document.querySelectorAll('.mn-btn').forEach(b=>b.classList.remove('active'));btn.classList.add('active');document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('active'));const panel=document.getElementById('panel-'+tab);if(panel)panel.classList.add('active');currentTab=tab;if(tab==='events')renderEvents();if(tab==='myevents')renderMyEvents();if(tab==='announcements')renderAnnouncements();if(tab==='university')loadUniversityInfo();window.scrollTo({top:0,behavior:'smooth'});}

// ─── INIT ───
window.addEventListener('DOMContentLoaded', () => {
  renderFeed();
  renderTopStudents();
  renderSidebarEvents();
  document.getElementById('tab-count-projects').textContent = PROJECTS.length;
  document.getElementById('tab-count-ann').textContent      = ANNOUNCEMENTS.length;
  document.getElementById('tab-count-events').textContent   = EVENTS.length;
  <?php if($page_toast): ?>showToast(<?= json_encode($page_toast) ?>);<?php endif; ?>
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') { closeCreateProject(); closeDrawer(); closeStoryViewer(); closeStudentProfile(); }
});
</script>
</body>
</html>