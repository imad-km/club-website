<?php
require_once 'includes/config.php';
session_start();
if (isset($_SESSION['access'])) { header('Location: dashboard.php'); exit(); }

// ── Determine which step we're on ───────────────────────────────────────────
// step = 'email' | 'otp' | 'reset' | 'done'
$step  = $_POST['step'] ?? 'email';
$err   = '';
$ok    = '';
$email = '';

// ── Role: student (can extend to professor later) ───────────────────────────
$role     = 'student';
$api_base = 'http://127.0.0.1:5000/api/v1';

function api_call(string $path, array $payload): array {
    global $api_base;
    $ch = curl_init($api_base . $path);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 15,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'data' => json_decode($body, true) ?? []];
}

// ── Handle POST submissions ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pl = check_request();
    if (!$pl) { $err = 'Invalid request.'; }
    else {

        // STEP 1 — send OTP to email
        if ($step === 'email') {
            $email = trim($pl['email'] ?? '');
            if (!$email) { $err = 'Please enter your email.'; $step = 'email'; }
            else {
                $r = api_call("/$role/forgot-password", ['email' => $email]);
                // API always returns 200 (vague response) – we move on regardless
                if ($r['code'] === 200) {
                    $step = 'otp';
                } else {
                    $err  = $r['data']['error'] ?? 'Something went wrong. Try again.';
                    $step = 'email';
                }
            }

        // STEP 2 — verify OTP
        } elseif ($step === 'otp') {
            $email = trim($pl['email'] ?? '');
            $otp   = trim($pl['otp']   ?? '');
            if (!$email || !$otp) { $err = 'Invalid request.'; $step = 'otp'; }
            else {
                $r = api_call("/$role/verify-reset-otp", ['email' => $email, 'otp' => $otp]);
                if ($r['code'] === 200) {
                    $step = 'reset';
                } else {
                    $err  = $r['data']['error'] ?? 'Invalid or expired code.';
                    $step = 'otp';
                }
            }

        // STEP 3 — set new password
        } elseif ($step === 'reset') {
            $email    = trim($pl['email']    ?? '');
            $newpw    = $pl['newpw']          ?? '';
            $confirmpw = $pl['confirmpw']     ?? '';
            if (!$email || !$newpw) { $err = 'Invalid request.'; $step = 'reset'; }
            elseif (strlen($newpw) < 6) { $err = 'Password must be at least 6 characters.'; $step = 'reset'; }
            elseif ($newpw !== $confirmpw)  { $err = 'Passwords do not match.'; $step = 'reset'; }
            else {
                $r = api_call("/$role/reset-password", ['email' => $email, 'new_password' => $newpw]);
                if ($r['code'] === 200) {
                    $step = 'done';
                    $ok   = 'Password reset successfully! You can now sign in.';
                } else {
                    $err  = $r['data']['error'] ?? 'Reset failed. Please start again.';
                    $step = 'reset';
                }
            }
        }
    }
}

$AES_KEY_HEX = bin2hex(AES_FINAL_KEY);

// ── Derive step meta (title, subtitle, icon) ────────────────────────────────
$meta = [
    'email' => ['title' => 'Forgot password?',    'sub' => 'Enter your email and we\'ll send you a reset code.',         'icon' => '🔐'],
    'otp'   => ['title' => 'Check your email',    'sub' => 'Enter the 6-digit code we sent you. It expires in 2 minutes.','icon' => '📬'],
    'reset' => ['title' => 'Set new password',    'sub' => 'Choose a strong password for your account.',                  'icon' => '🔑'],
    'done'  => ['title' => 'All done!',           'sub' => 'Your password has been reset successfully.',                   'icon' => '✅'],
];
$m = $meta[$step] ?? $meta['email'];
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Reset Password — AI House · Hassiba Benbouali</title>
<link rel="stylesheet" href="css/global.css"/>
<link rel="stylesheet" href="css/auth.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
<style>
/* ── Step indicator ── */
.steps-bar{display:flex;align-items:center;gap:0;margin-bottom:32px;}
.step-item{display:flex;align-items:center;gap:8px;flex:1;}
.step-circle{
  width:30px;height:30px;border-radius:50%;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;
  font-size:.75rem;font-weight:800;transition:.3s;
}
.step-circle.done{background:var(--green);color:#fff;}
.step-circle.active{background:var(--dark);color:#fff;box-shadow:0 0 0 3px rgba(27,110,63,.2);}
.step-circle.pending{background:var(--border);color:var(--muted);}
.step-label{font-size:.68rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;white-space:nowrap;}
.step-label.done{color:var(--green);}
.step-label.active{color:var(--dark);}
.step-label.pending{color:var(--muted);}
.step-line{flex:1;height:2px;background:var(--border);margin:0 8px;border-radius:2px;min-width:16px;}
.step-line.done{background:var(--green);}

/* ── Icon header ── */
.fp-icon-wrap{
  width:64px;height:64px;border-radius:50%;
  background:var(--green-p);
  display:flex;align-items:center;justify-content:center;
  font-size:1.7rem;margin-bottom:18px;
  animation:scalePop .45s cubic-bezier(.16,1,.3,1) both;
}

/* ── OTP inputs ── */
.otp-row{display:flex;gap:10px;justify-content:center;margin:22px 0 6px;}
.otp-digit{
  width:50px;height:58px;text-align:center;
  font-size:22px;font-weight:700;font-family:'Sora',sans-serif;
  border:1.5px solid var(--line);border-radius:10px;
  color:var(--txt);background:#fff;outline:none;
  transition:border .2s,box-shadow .2s,transform .15s;
}
.otp-digit:focus{border-color:var(--green);box-shadow:0 0 0 3px rgba(26,122,74,.10);transform:translateY(-2px);}
.otp-digit.filled{border-color:var(--green);}

/* ── Resend hint ── */
.resend-row{text-align:center;font-size:13px;color:var(--muted);margin-top:14px;}
.resend-row a{color:var(--green);font-weight:600;cursor:pointer;}

/* ── Password strength bar ── */
.pw-strength{margin-top:8px;height:4px;border-radius:2px;background:var(--border);overflow:hidden;}
.pw-strength-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;}
.pw-hint{font-size:.7rem;color:var(--muted);margin-top:5px;}

/* ── Done card ── */
.done-card{text-align:center;padding:12px 0 8px;}
.done-icon{font-size:3rem;margin-bottom:16px;display:block;}
.done-title{font-family:'Libre Baskerville',serif;font-size:1.3rem;color:var(--txt);margin-bottom:8px;}
.done-sub{font-size:.9rem;color:var(--muted);line-height:1.65;margin-bottom:28px;}

/* ── Responsive ── */
@media(max-width:400px){.otp-digit{width:40px;height:50px;font-size:18px;}}
</style>
</head>
<body class="auth-body">

<!-- ── Decorative left panel ── -->
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
      <h1>Secure<br/><span>Reset.</span></h1>
      <p>We'll send a one-time code to your email to confirm your identity before letting you set a new password.</p>
      <div class="panel-tags">
        <span class="panel-tag">1 — Email</span>
        <span class="panel-tag">2 — OTP</span>
        <span class="panel-tag">3 — New password</span>
      </div>
    </div>
    <div class="panel-foot">Student Portal · Algeria</div>
  </div>
</aside>

<!-- ── Form side ── -->
<div class="auth-form-wrap">
  <div class="auth-form-box">

    <!-- Step progress bar -->
    <?php
      $steps = [
        ['label' => 'Email',    'key' => 'email'],
        ['label' => 'OTP',      'key' => 'otp'],
        ['label' => 'Password', 'key' => 'reset'],
      ];
      $order  = ['email' => 0, 'otp' => 1, 'reset' => 2, 'done' => 3];
      $cur    = $order[$step] ?? 0;
    ?>
    <?php if ($step !== 'done'): ?>
    <div class="steps-bar" style="margin-bottom:28px">
      <?php foreach($steps as $i => $s):
        $state = $i < $cur ? 'done' : ($i === $cur ? 'active' : 'pending');
        $num   = $i < $cur ? '✓' : ($i + 1);
      ?>
        <div class="step-item">
          <div class="step-circle <?= $state ?>"><?= $num ?></div>
          <span class="step-label <?= $state ?>"><?= $s['label'] ?></span>
        </div>
        <?php if ($i < count($steps) - 1): ?>
          <div class="step-line <?= $i < $cur ? 'done' : '' ?>"></div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Icon + heading -->
    <div class="fp-icon-wrap"><?= $m['icon'] ?></div>
    <h2 style="font-family:'Libre Baskerville',serif;font-size:1.6rem;color:var(--txt);margin-bottom:6px"><?= $m['title'] ?></h2>
    <p class="auth-sub"><?= htmlspecialchars($m['sub']) ?></p>

    <!-- Error / success notices -->
    <?php if ($err): ?><div class="auth-err" style="margin-bottom:20px"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($ok  && $step !== 'done'): ?><div class="auth-ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>

    <?php /* ════════════════ STEP 1: EMAIL ════════════════ */ ?>
    <?php if ($step === 'email'): ?>
    <form method="POST" id="fpForm">
      <input type="hidden" name="step" value="email"/>
      <input type="hidden" name="_imadenc" id="_imadenc"/>
      <input type="hidden" name="_dok"     id="_dok"/>

      <div class="auth-field">
        <label>Your email address</label>
        <input type="email" id="f_email" placeholder="student@univ-chlef.dz" autocomplete="email" required/>
      </div>

      <button class="auth-btn" type="submit">
        Send reset code &nbsp;→
      </button>
    </form>

    <p class="auth-link" style="margin-top:22px">
      Remember it? <a href="login.php">Back to Sign in</a>
    </p>

    <?php /* ════════════════ STEP 2: OTP ════════════════ */ ?>
    <?php elseif ($step === 'otp'): ?>
    <form method="POST" id="otpForm">
      <input type="hidden" name="step"     value="otp"/>
      <input type="hidden" name="_imadenc" id="otp_imadenc"/>
      <input type="hidden" name="_dok"     id="otp_dok"/>
      <input type="hidden" id="hidden_email" value="<?= htmlspecialchars($email) ?>"/>

      <div class="otp-row">
        <?php for ($i = 0; $i < 6; $i++): ?>
          <input class="otp-digit" type="text" maxlength="1" inputmode="numeric"
                 id="d<?= $i ?>"
                 onkeyup="otpNav(this,<?= $i ?>)"
                 oninput="this.classList.toggle('filled',this.value!=='')"/>
        <?php endfor; ?>
      </div>

      <button class="auth-btn" type="submit" style="margin-top:10px">
        Verify code
      </button>
    </form>

    <p class="resend-row">
      Didn't receive it?
      <a onclick="resendOtp()">Resend code</a>
    </p>
    <p class="auth-link" style="margin-top:8px">
      Wrong email? <a href="forgot_password.php">Start over</a>
    </p>

    <?php /* ════════════════ STEP 3: NEW PASSWORD ════════════════ */ ?>
    <?php elseif ($step === 'reset'): ?>
    <form method="POST" id="resetForm">
      <input type="hidden" name="step"     value="reset"/>
      <input type="hidden" name="_imadenc" id="r_imadenc"/>
      <input type="hidden" name="_dok"     id="r_dok"/>
      <input type="hidden" id="r_email"   value="<?= htmlspecialchars($email) ?>"/>

      <div class="auth-field">
        <label>New password</label>
        <div class="pw-row">
          <input type="password" id="f_newpw" placeholder="At least 6 characters"
                 autocomplete="new-password" required
                 oninput="checkStrength(this.value)"/>
          <button class="pw-eye" type="button"
                  onclick="const i=document.getElementById('f_newpw');i.type=i.type==='password'?'text':'password'">
            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
        <div class="pw-strength"><div class="pw-strength-fill" id="pw-bar" style="width:0%"></div></div>
        <div class="pw-hint" id="pw-hint">Enter a password</div>
      </div>

      <div class="auth-field">
        <label>Confirm new password</label>
        <div class="pw-row">
          <input type="password" id="f_confirmpw" placeholder="Repeat password"
                 autocomplete="new-password" required/>
          <button class="pw-eye" type="button"
                  onclick="const i=document.getElementById('f_confirmpw');i.type=i.type==='password'?'text':'password'">
            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button class="auth-btn" type="submit">Set new password</button>
    </form>

    <?php /* ════════════════ STEP 4: DONE ════════════════ */ ?>
    <?php elseif ($step === 'done'): ?>
    <div class="done-card">
      <span class="done-icon">🎉</span>
      <div class="done-title">Password updated!</div>
      <div class="done-sub">Your password has been reset successfully.<br/>You can now sign in with your new password.</div>
      <a href="login.php" class="auth-btn" style="display:inline-flex;text-decoration:none;justify-content:center">
        Go to Sign in &nbsp;→
      </a>
    </div>
    <?php endif; ?>

  </div>
</div>

<script>
var _K = CryptoJS.enc.Hex.parse('<?= $AES_KEY_HEX ?>');
function _aes(obj) {
  var iv  = CryptoJS.lib.WordArray.random(16);
  var enc = CryptoJS.AES.encrypt(JSON.stringify(obj), _K, {iv:iv, mode:CryptoJS.mode.CBC, padding:CryptoJS.pad.Pkcs7});
  return CryptoJS.enc.Base64.stringify(iv.concat(enc.ciphertext));
}

/* ── STEP 1: email form ── */
var fpForm = document.getElementById('fpForm');
if (fpForm) {
  fpForm.addEventListener('submit', function(e) {
    e.preventDefault();
    var email = document.getElementById('f_email').value.trim();
    document.getElementById('_imadenc').value = _aes({email: email});
    document.getElementById('_dok').value     = _aes({t: Date.now()});
    fpForm.submit();
  });
}

/* ── STEP 2: OTP navigation ── */
function otpNav(el, idx) {
  var val = el.value.replace(/\D/g, '');
  el.value = val ? val[0] : '';
  el.classList.toggle('filled', el.value !== '');

  // Paste handling — spread digits across fields
  if (val.length > 1) {
    var digits = val.slice(0, 6).split('');
    digits.forEach(function(d, i) {
      var field = document.getElementById('d' + (idx + i));
      if (field) { field.value = d; field.classList.add('filled'); }
    });
    var last = document.getElementById('d' + Math.min(idx + digits.length, 5));
    if (last) last.focus();
    return;
  }
  if (el.value && idx < 5) document.getElementById('d' + (idx + 1))?.focus();
  if (!el.value && idx > 0) document.getElementById('d' + (idx - 1))?.focus();
}

// Allow pasting full OTP
document.querySelectorAll('.otp-digit').forEach(function(inp) {
  inp.addEventListener('paste', function(e) {
    e.preventDefault();
    var text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
    text.split('').forEach(function(d, i) {
      var f = document.getElementById('d' + i);
      if (f) { f.value = d; f.classList.add('filled'); }
    });
    var last = document.getElementById('d' + (text.length - 1));
    if (last) last.focus();
  });
});

var otpForm = document.getElementById('otpForm');
if (otpForm) {
  document.getElementById('d0')?.focus();
  otpForm.addEventListener('submit', function(e) {
    e.preventDefault();
    var otp   = [0,1,2,3,4,5].map(function(i){ return document.getElementById('d'+i)?.value||''; }).join('');
    var email = document.getElementById('hidden_email').value;
    if (otp.length < 6) {
      alert('Please enter the full 6-digit code.');
      return;
    }
    document.getElementById('otp_imadenc').value = _aes({email: email, otp: otp});
    document.getElementById('otp_dok').value     = _aes({t: Date.now()});
    otpForm.submit();
  });
}

/* Resend OTP — submit a fake "email" step form */
function resendOtp() {
  var email = document.getElementById('hidden_email')?.value;
  if (!email) return;
  var f = document.createElement('form');
  f.method = 'POST'; f.action = ''; f.style.display = 'none';
  var s  = document.createElement('input'); s.name='step';     s.value='email';       f.appendChild(s);
  var im = document.createElement('input'); im.name='_imadenc';im.value=_aes({email:email}); f.appendChild(im);
  var dk = document.createElement('input'); dk.name='_dok';    dk.value=_aes({t:Date.now()}); f.appendChild(dk);
  document.body.appendChild(f); f.submit();
}

/* ── STEP 3: reset form ── */
var resetForm = document.getElementById('resetForm');
if (resetForm) {
  resetForm.addEventListener('submit', function(e) {
    e.preventDefault();
    var newpw     = document.getElementById('f_newpw').value;
    var confirmpw = document.getElementById('f_confirmpw').value;
    var email     = document.getElementById('r_email').value;
    if (newpw.length < 6) { alert('Password must be at least 6 characters.'); return; }
    if (newpw !== confirmpw) { alert('Passwords do not match.'); return; }
    document.getElementById('r_imadenc').value = _aes({email:email, newpw:newpw, confirmpw:confirmpw});
    document.getElementById('r_dok').value     = _aes({t: Date.now()});
    resetForm.submit();
  });
}

/* ── Password strength meter ── */
function checkStrength(pw) {
  var bar  = document.getElementById('pw-bar');
  var hint = document.getElementById('pw-hint');
  if (!bar) return;
  var score = 0;
  if (pw.length >= 6)  score++;
  if (pw.length >= 10) score++;
  if (/[A-Z]/.test(pw)) score++;
  if (/[0-9]/.test(pw)) score++;
  if (/[^A-Za-z0-9]/.test(pw)) score++;
  var levels = [
    {pct:'0%',   bg:'var(--border)', label:'Enter a password'},
    {pct:'25%',  bg:'#e53e3e',       label:'Too weak'},
    {pct:'50%',  bg:'var(--orange)', label:'Fair'},
    {pct:'75%',  bg:'#d69e2e',       label:'Good'},
    {pct:'100%', bg:'var(--green)',   label:'Strong'},
  ];
  var l = levels[Math.min(score, 4)];
  bar.style.width      = l.pct;
  bar.style.background = l.bg;
  hint.textContent     = l.label;
  hint.style.color     = l.bg;
}
</script>
</body>
</html>
