<?php
require_once 'includes/config.php';
session_start();
if (isset($_GET['logout'])) { session_destroy(); }
if (isset($_SESSION['access'])) { header('Location: dashboard.php'); exit(); }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pl = check_request();
    if (!$pl) { $err = 'Invalid request'; }
    else {
        $email    = trim($pl['e'] ?? '');
        $password = $pl['k'] ?? '';
        $role     = $pl['role'] ?? 'student';
        $endpoint = $role === 'professor'
            ? 'http://173.249.28.246:8090/api/v1/professor/login'
            : 'http://173.249.28.246:8090/api/v1/student/login';

        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['email'=>$email,'password'=>$password]),CURLOPT_TIMEOUT=>15]);
        $body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
        $r=json_decode($body,true)??[];
        if($code===200&&isset($r['user']['access'])){$_SESSION['access']=$r['user']['access'];$_SESSION['refresh']=$r['user']['refresh'];header('Location: dashboard.php');exit();}
        $err=$r['error']??'Invalid credentials';
    }
}

$AES_KEY_HEX = bin2hex(AES_FINAL_KEY);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Sign In — AI House · Hassiba Benbouali</title>
<link rel="stylesheet" href="css/global.css"/>
<link rel="stylesheet" href="css/auth.css"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
<style>
.role-toggle{display:flex;gap:0;background:var(--fog);border:1.5px solid var(--line);border-radius:10px;padding:4px;margin-bottom:24px;}
.role-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:9px 14px;border:none;background:transparent;border-radius:7px;font-family:'DM Sans',system-ui,sans-serif;font-size:.85rem;font-weight:700;color:var(--muted);cursor:pointer;transition:.2s;}
.role-btn i{font-size:.82rem;}
.role-btn.active{background:var(--white);color:var(--dark);box-shadow:0 2px 8px rgba(0,0,0,.08);}
.role-btn:not(.active):hover{color:var(--text);}
</style>
</head>
<body class="auth-body">

<aside class="auth-panel">
  <div class="panel-noise"></div>
  <div class="panel-glow"></div>
  <div class="panel-glow2"></div>
  <div class="panel-inner">
    <div class="panel-logo">
      <img src="https://i.imgur.com/zl5jHaY.png" alt="University"/>
      <div>
        <div class="panel-logo-txt">AI House</div>
        <div class="panel-logo-sub">Université Hassiba Benbouali · Chlef</div>
      </div>
    </div>
    <div class="panel-copy">
      <h1>Welcome to<br/><span>AI House.</span></h1>
      <p>Showcase your projects, join events, and stay up to date with professor announcements.</p>
      <div class="panel-tags">
        <span class="panel-tag">Projects</span>
        <span class="panel-tag">Events</span>
        <span class="panel-tag">Announcements</span>
        <span class="panel-tag">Community</span>
      </div>
    </div>
    <div class="panel-foot">Student Portal · Algeria</div>
  </div>
</aside>

<div class="auth-form-wrap">
  <div class="auth-form-box">
    <h2>Sign in</h2>
    <p class="auth-sub">Access your student dashboard.</p>

    <div class="role-toggle">
      <button type="button" class="role-btn active" id="btn-student" onclick="setRole('student')">
        <i class="fa-solid fa-graduation-cap"></i> Student
      </button>
      <button type="button" class="role-btn" id="btn-professor" onclick="setRole('professor')">
        <i class="fa-solid fa-chalkboard-user"></i> Professor
      </button>
    </div>

    <?php if($err): ?>
      <div class="auth-err"><?= htmlspecialchars($err) ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="loginForm">
      <input type="hidden" name="_imadenc" id="_imadenc"/>
      <input type="hidden" name="_dok" id="_dok"/>
      <input type="hidden" id="f_role" value="student"/>

      <div class="auth-field">
        <label>Email</label>
        <input type="email" id="f_e" placeholder="student@univ-chlef.dz" autocomplete="email" required/>
      </div>

      <div class="auth-field">
        <label>Password</label>
        <div class="pw-row">
          <input type="password" id="f_k" placeholder="••••••••" autocomplete="current-password" required/>
          <button class="pw-eye" type="button" onclick="const i=document.getElementById('f_k');i.type=i.type==='password'?'text':'password'">
            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button class="auth-btn" type="submit">Continue</button>
    </form>

    <p style="text-align:right;margin-top:10px;margin-bottom:0">
      <a href="forgot_password.php" style="font-size:13px;color:var(--muted);text-decoration:none;font-weight:600;transition:color .2s" onmouseover="this.style.color='var(--green)'" onmouseout="this.style.color='var(--muted)'">Forgot password?</a>
    </p>

    <div class="auth-or"><span>or</span></div>
    <p class="auth-link">Don't have an account? <a href="register.php">Register</a></p>
  </div>
</div>

<script>
var _K = CryptoJS.enc.Hex.parse('<?= $AES_KEY_HEX ?>');
function _aes(obj) {
  var iv = CryptoJS.lib.WordArray.random(16);
  var enc = CryptoJS.AES.encrypt(JSON.stringify(obj), _K, {iv:iv,mode:CryptoJS.mode.CBC,padding:CryptoJS.pad.Pkcs7});
  return CryptoJS.enc.Base64.stringify(iv.concat(enc.ciphertext));
}
function setRole(role) {
  document.getElementById('f_role').value = role;
  document.getElementById('btn-student').classList.toggle('active', role === 'student');
  document.getElementById('btn-professor').classList.toggle('active', role === 'professor');
  document.querySelector('.auth-sub').textContent = role === 'professor'
    ? 'Access your professor dashboard.'
    : 'Access your student dashboard.';
}
document.getElementById('loginForm').addEventListener('submit', function(e) {
  e.preventDefault();
  document.getElementById('_imadenc').value = _aes({e:document.getElementById('f_e').value, k:document.getElementById('f_k').value, role:document.getElementById('f_role').value});
  document.getElementById('_dok').value = _aes({t:Date.now()});
  this.submit();
});
</script>
</body>
</html>