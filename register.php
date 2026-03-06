<?php
require_once 'includes/config.php';
session_start();
if (isset($_SESSION['access'])) { header('Location: dashboard.php'); exit(); }

$err   = '';
$ok    = '';
$step  = $_POST['step'] ?? 'form';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step === 'form') {
        $pl = check_request();
        if (!$pl) { $err = 'Invalid request'; $step = 'form'; }
        else {
            $email = trim($pl['e'] ?? '');
            $pw    = $pl['k'] ?? '';
            if (strlen($pw) < 6) { $err = 'Password must be at least 6 characters'; $step = 'form'; }
            else {
                $data = array_filter(['firstname'=>trim($pl['fn']??''),'lastname'=>trim($pl['ln']??''),'email'=>$email,'phone'=>trim($pl['ph']??''),'password'=>$pw,'image'=>$pl['img']??null,'grade'=>strtolower(trim($pl['gr']??'')),'domain'=>strtolower(trim($pl['dm']??''))], fn($v) => $v !== '' && $v !== null);
                $ch = curl_init('http://127.0.0.1:5000/api/v1/student/pre-register');
                curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode($data),CURLOPT_TIMEOUT=>15]);
                $body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
                $r=json_decode($body,true)??[];
                if($code===200){$step='otp';}
                else{$err=$r['error']??'Registration failed';$step='form';}
            }
        }
    } elseif ($step === 'otp') {
        $pl = check_request();
        if (!$pl) { $err = 'Invalid request'; $step = 'otp'; }
        else {
            $email = trim($pl['email']??'');
            $otp   = trim($pl['otp']??'');
            $ch = curl_init('http://127.0.0.1:5000/api/v1/student/verify-otp');
            curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_HTTPHEADER=>['Content-Type: application/json'],CURLOPT_POSTFIELDS=>json_encode(['email'=>$email,'otp'=>$otp]),CURLOPT_TIMEOUT=>15]);
            $body=curl_exec($ch);$code=curl_getinfo($ch,CURLINFO_HTTP_CODE);curl_close($ch);
            $r=json_decode($body,true)??[];
            if($code===201){$ok='Account created! You can now sign in.';$step='done';}
            else{$err=$r['error']??'Invalid OTP';$step='otp';}
        }
    }
}

$AES_KEY_HEX = bin2hex(AES_FINAL_KEY);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Register — AI House · Hassiba Benbouali</title>
<link rel="stylesheet" href="css/global.css"/>
<link rel="stylesheet" href="css/auth.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
</head>
<body class="register-body">

<div class="reg-card">
  <div class="reg-head">
    <?php if ($step !== 'otp' && $step !== 'done'): ?>
    <div class="av-ring" onclick="document.getElementById('imgInput').click()">
      <div class="av-circle" id="avC">
        <div class="av-ph">
          <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span>Photo</span>
        </div>
        <img id="avPreview" alt=""/>
      </div>
      <div class="av-badge"><svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg></div>
    </div>
    <?php else: ?>
    <img class="reg-head-logo" src="https://i.imgur.com/zl5jHaY.png" alt="University"/>
    <?php endif; ?>
    <h1><?= $step === 'otp' ? 'Check your email' : 'Create account' ?></h1>
    <p><?= $step === 'otp' ? 'Enter the 6-digit code we sent you' : 'AI House — Université Hassiba Benbouali de Chlef' ?></p>
  </div>

  <div class="reg-body">
    <?php if ($err): ?><div class="auth-err"><?= htmlspecialchars($err) ?></div><?php endif; ?>
    <?php if ($ok): ?><div class="auth-ok"><?= htmlspecialchars($ok) ?> <a href="login.php">Sign in →</a></div><?php endif; ?>

    <?php if ($step === 'form'): ?>
    <form method="POST" action="" id="regForm">
      <input type="hidden" name="step" value="form"/>
      <input type="hidden" name="_imadenc" id="_imadenc"/>
      <input type="hidden" name="_dok" id="_dok"/>
      <input type="hidden" id="imgB64"/>
      <input type="file" id="imgInput" accept="image/*" onchange="pickImg(this)" style="display:none"/>

      <div class="reg-row2">
        <div class="reg-field"><label>First Name</label><input type="text" id="f_fn" placeholder="Ahmed" required/></div>
        <div class="reg-field"><label>Last Name</label><input type="text" id="f_ln" placeholder="Benali" required/></div>
      </div>
      <div class="reg-field"><label>Email</label><input type="email" id="f_e" placeholder="student@univ-chlef.dz" required/></div>
      <div class="reg-field"><label>Phone</label><input type="tel" id="f_ph" placeholder="+213 5XX XXX XXX" required/></div>
      <div class="reg-row2">
        <div class="reg-field">
          <label>Grade</label>
          <select id="f_gr" required>
            <option value="" disabled selected>Select grade</option>
            <option value="licence">Licence</option>
            <option value="master">Master</option>
            <option value="doctorat">Doctorat</option>
          </select>
        </div>
        <div class="reg-field">
          <label>Domain</label>
          <select id="f_dm" required>
            <option value="" disabled selected>Select domain</option>
            <option value="intelligence artificielle">Intelligence Artificielle</option>
            <option value="developpement web">Développement Web</option>
            <option value="cyber securite">Cyber Sécurité</option>
            <option value="reseaux et telecommunications">Réseaux et Télécommunications</option>
            <option value="systemes embarques">Systèmes Embarqués</option>
            <option value="science des donnees">Science des Données</option>
            <option value="genie logiciel">Génie Logiciel</option>
            <option value="autre">Autre</option>
          </select>
        </div>
      </div>
      <div class="reg-field"><label>Password</label>
        <div class="pw-row">
          <input type="password" id="f_k" placeholder="At least 6 characters" required/>
          <button class="pw-eye" type="button" onclick="const i=document.getElementById('f_k');i.type=i.type==='password'?'text':'password'">
            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>
      <button class="auth-btn" type="submit">Continue</button>
    </form>
    <p class="auth-link" style="margin-top:18px">Already have an account? <a href="login.php">Sign in</a></p>

    <?php elseif ($step === 'otp'): ?>
    <div class="auth-ok" style="line-height:1.7">
      A 6-digit code was sent to <strong><?= htmlspecialchars($email) ?></strong>. It expires in 2 minutes.
    </div>
    <form method="POST" action="" id="otpForm">
      <input type="hidden" name="step" value="otp"/>
      <input type="hidden" name="_imadenc" id="otp_imadenc"/>
      <input type="hidden" name="_dok" id="otp_dok"/>
      <input type="hidden" id="otpEmail" value="<?= htmlspecialchars($email) ?>"/>
      <div class="otp-inputs">
        <input type="text" maxlength="1" inputmode="numeric" id="d0" onkeyup="otpNav(this,null,'d1')"/>
        <input type="text" maxlength="1" inputmode="numeric" id="d1" onkeyup="otpNav(this,'d0','d2')"/>
        <input type="text" maxlength="1" inputmode="numeric" id="d2" onkeyup="otpNav(this,'d1','d3')"/>
        <input type="text" maxlength="1" inputmode="numeric" id="d3" onkeyup="otpNav(this,'d2','d4')"/>
        <input type="text" maxlength="1" inputmode="numeric" id="d4" onkeyup="otpNav(this,'d3','d5')"/>
        <input type="text" maxlength="1" inputmode="numeric" id="d5" onkeyup="otpNav(this,'d4',null)"/>
      </div>
      <button class="auth-btn" type="submit">Verify &amp; Create Account</button>
    </form>
    <p class="auth-link" style="margin-top:16px">Wrong email? <a href="register.php">Start over</a></p>
    <?php endif; ?>
  </div>
</div>

<script>
var _K = CryptoJS.enc.Hex.parse('<?= $AES_KEY_HEX ?>');
function _aes(obj) {
  var iv = CryptoJS.lib.WordArray.random(16);
  var enc = CryptoJS.AES.encrypt(JSON.stringify(obj), _K, {iv:iv,mode:CryptoJS.mode.CBC,padding:CryptoJS.pad.Pkcs7});
  return CryptoJS.enc.Base64.stringify(iv.concat(enc.ciphertext));
}
function pickImg(input) {
  var f = input.files[0]; if (!f) return;
  var rd = new FileReader();
  rd.onload = function(e) {
    document.getElementById('imgB64').value = e.target.result;
    var img = document.getElementById('avPreview'); img.src = e.target.result; img.style.display = 'block';
    document.querySelector('.av-ph').style.display = 'none';
  };
  rd.readAsDataURL(f);
}
var regForm = document.getElementById('regForm');
if (regForm) {
  regForm.addEventListener('submit', function(e) {
    e.preventDefault();
    document.getElementById('_imadenc').value = _aes({fn:document.getElementById('f_fn').value,ln:document.getElementById('f_ln').value,e:document.getElementById('f_e').value,ph:document.getElementById('f_ph').value,gr:document.getElementById('f_gr').value,dm:document.getElementById('f_dm').value,k:document.getElementById('f_k').value,img:document.getElementById('imgB64').value||null});
    document.getElementById('_dok').value = _aes({t:Date.now()});
    regForm.submit();
  });
}
var otpForm = document.getElementById('otpForm');
if (otpForm) {
  document.getElementById('d0')?.focus();
  otpForm.addEventListener('submit', function(e) {
    e.preventDefault();
    var otp = ['d0','d1','d2','d3','d4','d5'].map(id=>document.getElementById(id).value).join('');
    document.getElementById('otp_imadenc').value = _aes({email:document.getElementById('otpEmail').value, otp:otp});
    document.getElementById('otp_dok').value = _aes({t:Date.now()});
    otpForm.submit();
  });
}
function otpNav(el, prevId, nextId) {
  if (el.value.length === 1 && nextId) document.getElementById(nextId)?.focus();
  if (el.value === '' && prevId)       document.getElementById(prevId)?.focus();
}
</script>
</body>
</html>
