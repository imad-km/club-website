<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['access'])) { header('Location: ../index.php'); exit(); }

define('_A', 'http://173.249.28.246:8090/api/v1');
$tok = $_SESSION['access'];
$AES_KEY_HEX = bin2hex(AES_FINAL_KEY);

function api(string $ep, string $m = 'GET', $b = null): array {
    global $tok;
    $ch = curl_init(_A . '/' . ltrim($ep, '/'));
    $h = ['Content-Type: application/json', 'Authorization: Bearer ' . $tok];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_CUSTOMREQUEST=>$m,CURLOPT_HTTPHEADER=>$h,CURLOPT_TIMEOUT=>15]);
    if ($b !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($b));
    $res = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
    $r = json_decode($res, true) ?? []; $r['_c'] = $code; return $r;
}

/** Fire multiple GET requests in parallel and return [key => decoded_array]. */
function api_multi(array $endpoints): array {
    global $tok;
    $mh      = curl_multi_init();
    $handles = [];
    foreach ($endpoints as $key => $ep) {
        $ch = curl_init(_A . '/' . ltrim($ep, '/'));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Authorization: Bearer ' . $tok],
            CURLOPT_TIMEOUT        => 15,
        ]);
        curl_multi_add_handle($mh, $ch);
        $handles[$key] = $ch;
    }
    $running = null;
    do { curl_multi_exec($mh, $running); curl_multi_select($mh); } while ($running > 0);
    $results = [];
    foreach ($handles as $key => $ch) {
        $res           = curl_multi_getcontent($ch);
        $code          = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $r             = json_decode($res, true) ?? [];
        $r['_c']       = $code;
        $results[$key] = $r;
        curl_multi_remove_handle($mh, $ch);
        curl_close($ch);
    }
    curl_multi_close($mh);
    return $results;
}

$flash = '';
$flashType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['_act'] ?? '';

    if ($act === 'logout') {
    session_destroy(); header('Location: /index.php'); exit();
    }

    if ($act === 'upd') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $b = array_filter(['firstname'=>trim($pl['fn']??''),'lastname'=>trim($pl['ln']??''),'phone'=>trim($pl['ph']??''),'image'=>$pl['img']??null],fn($v)=>$v!==''&&$v!==null);
            $r = api('me','PUT',$b);
            $flash = $r['_c']===200 ? 'Profile updated successfully.' : ($r['error']??'Update failed');
            $flashType = $r['_c']===200 ? 'ok' : 'err';
        }
    }

    if ($act === 'pwd') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $p=$pl['p']??'';$c=$pl['c']??'';
            if (strlen($p)<6) { $flash='Password too short.';$flashType='err'; }
            elseif ($p!==$c) { $flash='Passwords do not match.';$flashType='err'; }
            else {
                $r=api('me','PUT',['password'=>$p]);
                $flash=$r['_c']===200?'Password updated.':($r['error']??'Update failed');
                $flashType=$r['_c']===200?'ok':'err';
            }
        }
    }

    if ($act === 'invite') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $em=trim($pl['e']??'');$nm=trim($pl['n']??'');
            if(!$em){$flash='Email required.';$flashType='err';}
            else{
                $r=api('admin/invite','POST',['name'=>$nm,'email'=>$em]);
                $flash=$r['_c']===200?'Invitation sent to '.$em.'.':($r['error']??'Failed to send');
                $flashType=$r['_c']===200?'ok':'err';
            }
        }
    }

    if ($act === 'annonce') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $title      = trim($pl['title']   ?? '');
            $content    = trim($pl['content'] ?? '');
            $image      = $pl['image']     ?? null;
            $is_pinned  = !empty($pl['is_pinned']);
            $is_visible = isset($pl['is_visible']) ? (bool)$pl['is_visible'] : true;
            if (!$title || !$content) { $flash='Title and content are required.'; $flashType='err'; }
            else {
                $body = ['title'=>$title,'content'=>$content,'is_pinned'=>$is_pinned,'is_visible'=>$is_visible];
                if ($image) $body['image'] = $image;
                $r = api('announcements','POST',$body);
                $flash = $r['_c']===200||$r['_c']===201 ? 'Announcement published.' : ($r['error']??'Failed to publish');
                $flashType = ($r['_c']===200||$r['_c']===201) ? 'ok' : 'err';
            }
        }
    }

    if ($act === 'event') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $title      = trim($pl['title']       ?? '');
            $desc       = $pl['description'] ?? null;
            $location   = $pl['location']    ?? null;
            $image      = $pl['image']       ?? null;
            $start_at   = trim($pl['start_at']    ?? '');
            $end_at     = $pl['end_at']      ?? null;
            $event_type = trim($pl['event_type']  ?? 'other');
            $capacity   = isset($pl['capacity']) && $pl['capacity'] !== '' ? (int)$pl['capacity'] : null;
            $is_visible = isset($pl['is_visible']) ? (bool)$pl['is_visible'] : true;
            if (!$title || !$start_at) { $flash='Title and start date are required.'; $flashType='err'; }
            else {
                $body = ['title'=>$title,'start_at'=>$start_at,'event_type'=>$event_type,'is_visible'=>$is_visible];
                if ($desc)     $body['description'] = $desc;
                if ($location) $body['location']    = $location;
                if ($image)    $body['image']        = $image;
                if ($end_at)   $body['end_at']       = $end_at;
                if ($capacity) $body['capacity']     = $capacity;
                $r = api('events','POST',$body);
                $flash = $r['_c']===200||$r['_c']===201 ? 'Event created.' : ($r['error']??'Failed to create event');
                $flashType = ($r['_c']===200||$r['_c']===201) ? 'ok' : 'err';
            }
        }
    }

    if ($act === 'edit_annonce') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $id      = (int)($pl['id'] ?? 0);
            $title   = trim($pl['title']   ?? '');
            $content = trim($pl['content'] ?? '');
            if (!$id || !$title || !$content) { $flash='All fields are required.'; $flashType='err'; }
            else {
                $r = api('announcements/'.$id,'PUT',['title'=>$title,'content'=>$content]);
                $flash = $r['_c']===200 ? 'Announcement updated.' : ($r['error']??'Update failed');
                $flashType = $r['_c']===200 ? 'ok' : 'err';
            }
        }
    }

    if ($act === 'del_annonce') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $flash='Invalid ID.'; $flashType='err'; }
        else {
            $r = api('announcements/'.$id,'DELETE');
            $flash = $r['_c']===200 ? 'Announcement deleted.' : ($r['error']??'Delete failed');
            $flashType = $r['_c']===200 ? 'ok' : 'err';
        }
    }

    if ($act === 'edit_event') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $id       = (int)($pl['id']          ?? 0);
            $title    = trim($pl['title']         ?? '');
            $desc     = trim($pl['description']   ?? '');
            $location = trim($pl['location']      ?? '');
            $start_at = trim($pl['start_at']      ?? '');
            $end_at   = trim($pl['end_at']        ?? '');
            $capacity = isset($pl['capacity']) && $pl['capacity'] !== '' ? (int)$pl['capacity'] : null;
            if (!$id || !$title || !$start_at) { $flash='ID, title and start date are required.'; $flashType='err'; }
            else {
                $body = ['title'=>$title,'description'=>$desc,'location'=>$location,'start_at'=>$start_at,'end_at'=>$end_at?:null];
                if ($capacity) $body['capacity'] = $capacity;
                $r = api('events/'.$id,'PUT',$body);
                $flash = $r['_c']===200 ? 'Event updated.' : ($r['error']??'Update failed');
                $flashType = $r['_c']===200 ? 'ok' : 'err';
            }
        }
    }

    if ($act === 'del_event') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { $flash='Invalid ID.'; $flashType='err'; }
        else {
            $r = api('events/'.$id,'DELETE');
            $flash = $r['_c']===200 ? 'Event deleted.' : ($r['error']??'Delete failed');
            $flashType = $r['_c']===200 ? 'ok' : 'err';
        }
    }

    if ($act === 'ban_student') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $identify = trim($pl['identify'] ?? '');
            $reason   = trim($pl['reason']   ?? '');
            if (!$identify || !$reason) { $flash='Identify and reason are required.'; $flashType='err'; }
            else {
                $r = api('admin/ban','POST',['identify'=>$identify,'reason'=>$reason]);
                $flash = $r['_c']===200 ? 'Student banned successfully.' : ($r['error']??'Ban failed');
                $flashType = $r['_c']===200 ? 'ok' : 'err';
            }
        }
    }

    if ($act === 'unban_student') {
        $pl = check_request();
        if (!$pl) { $flash = 'Invalid request.'; $flashType = 'err'; }
        else {
            $identify = trim($pl['identify'] ?? '');
            if (!$identify) { $flash='Identify is required.'; $flashType='err'; }
            else {
                $r = api('admin/unban','POST',['identify'=>$identify]);
                $flash = $r['_c']===200 ? 'Student unbanned successfully.' : ($r['error']??'Unban failed');
                $flashType = $r['_c']===200 ? 'ok' : 'err';
            }
        }
    }

    $_SESSION['flash']     = $flash;
    $_SESSION['flashType'] = $flashType;
    header('Location: index.php' . (isset($_GET['pg']) ? '?pg='.urlencode($_GET['pg']) : '')); exit();
}

if (isset($_SESSION['flash'])) {
    $flash     = $_SESSION['flash'];
    $flashType = $_SESSION['flashType'] ?? 'ok';
    unset($_SESSION['flash'], $_SESSION['flashType']);
}

$pg = $_GET['pg'] ?? 'Members';

// Build the set of endpoints needed for the active page — always fetch /me
$endpoints = ['me' => 'me'];
if ($pg === 'Members')  $endpoints['Members'] = 'admin/fetch-all';
if ($pg === 'annonces')  $endpoints['annonces'] = 'announcements';
if ($pg === 'events')    $endpoints['events']   = 'events';

$data     = api_multi($endpoints);
$me       = $data['me'] ?? ['_c' => 500];
if ($me['_c'] === 401) { session_destroy(); header('Location: login.php'); exit(); }

$Members = $data['Members']['Members'] ?? [];
$annonces = $data['annonces']['announcements'] ?? [];
$events   = $data['events']['events'] ?? [];

$firstName = $me['firstname'] ?? '';
$lastName  = $me['lastname']  ?? '';
$fullName  = trim($firstName . ' ' . $lastName);
$initials  = strtoupper(substr($firstName,0,1).substr($lastName,0,1)) ?: '?';

// Domain label prettifier
function domainLabel(string $d): string {
    $map = [
        'intelligence artificielle'       => 'AI',
        'developpement web'               => 'Web Dev',
        'cyber securite'                  => 'Cybersec',
        'reseaux et telecommunications'   => 'Networks',
        'systemes embarques'              => 'Embedded',
        'science des donnees'             => 'Data Sci',
        'genie logiciel'                  => 'Software Eng',
        'autre'                           => 'Other',
    ];
    return $map[strtolower($d)] ?? ucfirst($d);
}
function gradeLabel(string $g): string {
    return ucfirst($g);
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Hassiba Ben Bouali — Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet"/>
<style>
*,::before,::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --dark:#0d1117;--dark2:#161b22;--dark3:#21262d;
  --green:#1a7a4a;--green2:#22a360;--green3:#4dd88a;
  --gl:rgba(26,122,74,.08);--glt:rgba(26,122,74,.12);
  --amber:#c85a00;--al:rgba(200,90,0,.08);
  --blue:#2563eb;--bl:rgba(37,99,235,.08);
  --purple:#7c3aed;--pl:rgba(124,58,237,.08);
  --rose:#e11d48;--rl:rgba(225,29,72,.08);
  --line:#e1e8ef;--line2:#30363d;
  --txt:#111820;--muted:#6b7a8d;--fog:#f6f8fa;--white:#ffffff;
  --sidebar:260px;
}
body{display:flex;min-height:100vh;background:var(--fog);font-family:'Sora',sans-serif;color:var(--txt);}
@keyframes spin{to{transform:rotate(360deg)}}
@keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
@keyframes slideLeft{from{opacity:0;transform:translateX(-24px)}to{opacity:1;transform:translateX(0)}}
@keyframes statPop{from{opacity:0;transform:translateY(16px) scale(.95)}to{opacity:1;transform:translateY(0) scale(1)}}
@keyframes rowIn{from{opacity:0;transform:translateX(-10px)}to{opacity:1;transform:translateX(0)}}
@keyframes cardIn{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
@keyframes glowPulse{0%,100%{opacity:.4}50%{opacity:.75}}

/* ── SIDEBAR ── */
.sb{width:var(--sidebar);flex-shrink:0;background:var(--dark);height:100vh;position:fixed;top:0;left:0;display:flex;flex-direction:column;overflow:hidden;z-index:100;}
.sb::before{content:'';position:absolute;width:340px;height:340px;border-radius:50%;background:radial-gradient(circle,rgba(26,122,74,.22) 0%,transparent 70%);top:-120px;left:-90px;pointer-events:none;animation:glowPulse 4s ease infinite;}
.sb-head{padding:22px 20px;border-bottom:1px solid var(--line2);display:flex;align-items:center;gap:11px;position:relative;z-index:1;animation:slideLeft .6s cubic-bezier(.16,1,.3,1) both;}
.sb-head img{width:36px;height:36px;border-radius:9px;object-fit:cover;border:1px solid rgba(255,255,255,.12);}
.sb-brand-name{font-family:'Libre Baskerville',serif;font-size:13px;line-height:1.3;color:#fff;}
.sb-brand-sub{font-size:10px;color:rgba(255,255,255,.35);margin-top:3px;letter-spacing:.06em;}
.sb-nav{flex:1;padding:16px 12px;display:flex;flex-direction:column;gap:2px;position:relative;z-index:1;overflow-y:auto;}
.nav-sec{font-size:9px;font-weight:600;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.25);padding:12px 8px 6px;}
.nav-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;cursor:pointer;text-decoration:none;font-size:13px;font-weight:500;color:rgba(255,255,255,.5);transition:all .18s;animation:slideLeft .5s cubic-bezier(.16,1,.3,1) both;}
.nav-item svg{width:16px;height:16px;flex-shrink:0;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.nav-item:hover{background:rgba(255,255,255,.06);color:rgba(255,255,255,.85);}
.nav-item.active{background:rgba(26,122,74,.18);color:var(--green3);}
.nav-item.active svg{stroke:var(--green3);}
.nbadge{margin-left:auto;background:var(--amber);color:#fff;font-size:10px;font-weight:700;padding:1px 7px;border-radius:20px;}
.sb-foot{padding:14px 12px;border-top:1px solid var(--line2);position:relative;z-index:1;}
.sb-prof{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:8px;background:rgba(255,255,255,.04);margin-bottom:8px;}
.sb-av{width:32px;height:32px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1.5px solid rgba(255,255,255,.15);}
.sb-av-ph{width:32px;height:32px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green2));display:flex;align-items:center;justify-content:center;color:#fff;font-size:11px;font-weight:700;flex-shrink:0;}
.sb-prof-name{font-size:12px;font-weight:600;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:148px;}
.sb-prof-role{font-size:10px;color:rgba(255,255,255,.35);margin-top:1px;}
.sb-logout{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;border:1px solid rgba(239,68,68,.2);background:transparent;color:rgba(239,68,68,.7);font-family:'Sora',sans-serif;font-size:12px;font-weight:500;width:100%;cursor:pointer;transition:all .18s;}
.sb-logout:hover{background:rgba(239,68,68,.08);color:#f87171;}
.sb-logout svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.sb-back-btn{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;border:1px solid rgba(77,216,138,.2);background:transparent;color:rgba(77,216,138,.7);font-family:'Sora',sans-serif;font-size:12px;font-weight:500;width:100%;cursor:pointer;transition:all .18s;text-decoration:none;margin-bottom:6px;}
.sb-back-btn:hover{background:rgba(26,122,74,.15);color:var(--green3);}
.sb-back-btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.sb-invite-btn{display:flex;align-items:center;justify-content:center;gap:6px;width:100%;padding:9px 12px;border-radius:8px;border:1px solid rgba(77,216,138,.25);background:rgba(26,122,74,.15);color:var(--green3);font-family:'Sora',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all .18s;margin-bottom:8px;}
.sb-invite-btn:hover{background:rgba(26,122,74,.28);border-color:rgba(77,216,138,.45);}
.sb-invite-btn svg{width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}

/* ── MAIN ── */
.main{flex:1;display:flex;flex-direction:column;min-width:0;margin-left:var(--sidebar);}
.topbar{background:var(--white);border-bottom:1px solid var(--line);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:50;}
.tb-title{font-family:'Libre Baskerville',serif;font-size:19px;color:var(--txt);}
.tb-sub{font-size:12px;color:var(--muted);margin-top:1px;}
.tb-right{display:flex;align-items:center;gap:10px;}
.search{display:flex;align-items:center;gap:8px;background:var(--fog);border:1.5px solid var(--line);border-radius:8px;padding:8px 14px;width:220px;transition:border .2s;}
.search:focus-within{border-color:var(--green);}
.search svg{width:14px;height:14px;fill:none;stroke:var(--muted);stroke-width:2;stroke-linecap:round;flex-shrink:0;}
.search input{border:none;background:none;outline:none;font-size:13px;font-family:'Sora',sans-serif;color:var(--txt);width:100%;}
.search input::placeholder{color:#aab4be;}
.hbtn{display:none;width:36px;height:36px;background:var(--fog);border:1px solid var(--line);border-radius:8px;align-items:center;justify-content:center;cursor:pointer;}
.hbtn svg{width:18px;height:18px;fill:none;stroke:var(--txt);stroke-width:2;stroke-linecap:round;}
.add-btn{display:inline-flex;align-items:center;gap:6px;padding:9px 16px;border:none;border-radius:8px;background:var(--dark);color:#fff;font-family:'Sora',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;}
.add-btn:hover{background:var(--dark2);box-shadow:0 4px 14px rgba(13,17,23,.2);transform:translateY(-1px);}
.add-btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round;}

/* ── CONTENT ── */
.content{flex:1;padding:28px;animation:fadeIn .4s cubic-bezier(.16,1,.3,1) .08s both;}
.flash{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:20px;font-weight:500;}
.flash.ok{background:#f0fdf4;border:1px solid #86efac;color:#15803d;}
.flash.err{background:#fff0f0;border:1px solid #fca5a5;color:#b91c1c;}

/* ── STATS ── */
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:24px;}
.stat{background:var(--white);border:1px solid var(--line);border-radius:12px;padding:20px;animation:statPop .5s cubic-bezier(.16,1,.3,1) both;}
.stat:nth-child(1){animation-delay:.05s}.stat:nth-child(2){animation-delay:.13s}.stat:nth-child(3){animation-delay:.21s}.stat:nth-child(4){animation-delay:.29s}
.stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px;}
.stat-icon svg{width:18px;height:18px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.stat-icon.g{background:var(--gl);color:var(--green);}
.stat-icon.b{background:var(--bl);color:var(--blue);}
.stat-icon.p{background:var(--pl);color:var(--purple);}
.stat-icon.a{background:var(--al);color:var(--amber);}
.stat-val{font-family:'Libre Baskerville',serif;font-size:28px;font-weight:700;color:var(--txt);line-height:1;margin-bottom:3px;}
.stat-lbl{font-size:11px;font-weight:500;color:var(--muted);letter-spacing:.04em;}

/* ── TABLE ── */
.sec-bar{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;flex-wrap:wrap;gap:10px;}
.sec-title{font-family:'Libre Baskerville',serif;font-size:17px;color:var(--txt);}
.sec-hint{font-size:12px;color:var(--muted);margin-top:2px;}
.chips{display:flex;gap:6px;flex-wrap:wrap;}
.chip{padding:5px 12px;border:1.5px solid var(--line);border-radius:6px;font-size:11px;font-weight:600;color:var(--muted);background:var(--white);cursor:pointer;transition:all .18s;}
.chip.on{border-color:var(--green);background:var(--gl);color:var(--green);}
.tbl-wrap{background:var(--white);border:1px solid var(--line);border-radius:12px;overflow:hidden;}
table{width:100%;border-collapse:collapse;}
thead tr{border-bottom:1px solid var(--line);background:var(--fog);}
th{padding:11px 18px;font-size:10px;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);text-align:left;white-space:nowrap;}
tbody tr{border-bottom:1px solid #f0f4f8;transition:background .15s;}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:#fafcff;}
td{padding:12px 18px;font-size:13px;color:var(--txt);}
.stu-row{display:flex;align-items:center;gap:10px;}
.stu-av{width:30px;height:30px;border-radius:50%;object-fit:cover;flex-shrink:0;border:1px solid var(--line);}
.stu-ph{width:30px;height:30px;border-radius:50%;background:linear-gradient(135deg,var(--green),var(--green2));display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:700;flex-shrink:0;}
.stu-name{font-weight:600;font-size:13px;}
.stu-email{font-size:11px;color:var(--muted);margin-top:1px;}
.badge-pill{display:inline-flex;padding:2px 9px;border-radius:100px;font-size:10px;font-weight:600;white-space:nowrap;}
.badge-student{background:var(--al);color:var(--amber);border:1px solid rgba(200,90,0,.15);}
.badge-professor{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.badge-researcher{background:#eff6ff;color:#2563eb;border:1px solid #bfdbfe;}
.badge-company{background:var(--pl);color:var(--purple);border:1px solid rgba(124,58,237,.2);}
.badge-domain{background:var(--fog);color:var(--muted);border:1px solid var(--line);}
.inv-btn{display:inline-flex;align-items:center;gap:5px;padding:5px 11px;border:1.5px solid var(--line);border-radius:6px;background:transparent;color:var(--muted);font-family:'Sora',sans-serif;font-size:11px;font-weight:600;cursor:pointer;transition:all .18s;}
.inv-btn:hover{border-color:var(--green);color:var(--green);background:var(--gl);}
.inv-btn svg{width:12px;height:12px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.empty-state{display:flex;flex-direction:column;align-items:center;padding:56px 20px;color:var(--muted);}
.empty-state svg{width:36px;height:36px;fill:none;stroke:var(--muted);stroke-width:1.5;opacity:.35;margin-bottom:12px;}
.empty-state p{font-size:14px;}

/* ── ANNONCES / EVENTS GRID ── */
.cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px;}
.card{background:var(--white);border:1px solid var(--line);border-radius:14px;padding:22px 24px;animation:cardIn .4s cubic-bezier(.16,1,.3,1) both;transition:box-shadow .2s,transform .2s;display:flex;flex-direction:column;gap:10px;}
.card:hover{box-shadow:0 6px 24px rgba(0,0,0,.07);transform:translateY(-2px);}
.card-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.card-icon svg{width:16px;height:16px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.card-icon.ann{background:var(--pl);color:var(--purple);}
.card-icon.evt{background:var(--bl);color:var(--blue);}
.card-head{display:flex;align-items:flex-start;gap:12px;}
.card-meta{flex:1;min-width:0;}
.card-title{font-family:'Libre Baskerville',serif;font-size:15px;font-weight:700;color:var(--txt);line-height:1.3;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.card-date{font-size:11px;color:var(--muted);}
.card-body{font-size:13px;color:var(--muted);line-height:1.7;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
.card-footer{display:flex;align-items:center;gap:8px;margin-top:4px;flex-wrap:wrap;}
.card-tag{padding:2px 9px;border-radius:100px;font-size:10px;font-weight:600;}
.card-tag.location{background:var(--bl);color:var(--blue);border:1px solid rgba(37,99,235,.15);}
.card-tag.event-date{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.card-actions{display:flex;gap:6px;margin-left:auto;flex-shrink:0;}
.ca-btn{display:inline-flex;align-items:center;gap:4px;padding:4px 10px;border-radius:6px;font-family:'Sora',sans-serif;font-size:11px;font-weight:600;cursor:pointer;transition:all .18s;border:1.5px solid;}
.ca-edit{border-color:var(--line);background:transparent;color:var(--muted);}
.ca-edit:hover{border-color:var(--green);color:var(--green);background:var(--gl);}
.ca-ban{border-color:rgba(225,29,72,.25);background:transparent;color:var(--rose);}
.ca-ban:hover{background:var(--rl);border-color:var(--rose);}
.ca-unban{border-color:rgba(26,122,74,.25);background:transparent;color:var(--green);}
.ca-unban:hover{background:var(--gl);border-color:var(--green);}
.status-pill{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:100px;font-size:10px;font-weight:700;white-space:nowrap;}
.status-pill svg{width:9px;height:9px;fill:none;stroke:currentColor;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.status-active{background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;}
.status-banned{background:rgba(225,29,72,.08);color:var(--rose);border:1px solid rgba(225,29,72,.2);}
.stu-av-banned{opacity:.55;filter:grayscale(60%);}
.stu-ph-banned{background:linear-gradient(135deg,#e11d48,#be123c)!important;opacity:.7;}
.stu-name-banned{color:var(--rose)!important;text-decoration:line-through;text-decoration-color:rgba(225,29,72,.4);}
.stu-ban-reason{display:flex;align-items:center;gap:4px;font-size:10px;color:var(--rose);margin-top:3px;opacity:.8;}
.stu-ban-reason svg{width:9px;height:9px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;flex-shrink:0;}

.ca-del{border-color:rgba(225,29,72,.2);background:transparent;color:var(--rose);}
.ca-del:hover{background:var(--rl);border-color:var(--rose);}
.ca-btn svg{width:11px;height:11px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.img-pick{display:flex;align-items:center;gap:12px;padding:10px 14px;border:1.5px dashed var(--line);border-radius:8px;cursor:pointer;transition:border .2s,background .2s;background:var(--fog);}
.img-pick:hover{border-color:var(--green);background:var(--gl);}
.img-pick-thumb{width:44px;height:44px;border-radius:7px;object-fit:cover;border:1px solid var(--line);background:var(--white);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden;}
.img-pick-thumb svg{width:18px;height:18px;fill:none;stroke:var(--muted);stroke-width:1.5;stroke-linecap:round;stroke-linejoin:round;}
.img-pick-lbl{font-size:12px;color:var(--muted);line-height:1.5;}
.img-pick-lbl strong{display:block;font-size:13px;color:var(--txt);font-weight:600;}
.img-pick-clear{margin-left:auto;padding:3px 8px;border:1px solid var(--line);border-radius:5px;font-size:10px;font-weight:600;color:var(--rose);background:transparent;cursor:pointer;flex-shrink:0;}
.img-pick-clear:hover{background:var(--rl);}
.evt-type-row{display:grid;grid-template-columns:1fr 1fr;gap:10px;}
.type-chip{padding:8px 10px;border:1.5px solid var(--line);border-radius:7px;font-size:12px;font-weight:600;color:var(--muted);background:var(--white);cursor:pointer;transition:all .18s;text-align:center;}
.type-chip.sel{border-color:var(--blue);background:var(--bl);color:var(--blue);}
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:8px 0;}
.toggle-row label{font-size:12px;font-weight:600;color:var(--txt);}
.tog{position:relative;width:38px;height:22px;flex-shrink:0;}
.tog input{opacity:0;width:0;height:0;}
.tog-sl{position:absolute;inset:0;background:#d1d5db;border-radius:22px;cursor:pointer;transition:.2s;}
.tog-sl::before{content:'';position:absolute;width:16px;height:16px;left:3px;top:3px;background:#fff;border-radius:50%;transition:.2s;}
.tog input:checked+.tog-sl{background:var(--green);}
.tog input:checked+.tog-sl::before{transform:translateX(16px);}

/* ── SETTINGS ── */
.sett{max-width:520px;margin:0 auto;display:flex;flex-direction:column;gap:16px;}
.s-card{background:var(--white);border:1px solid var(--line);border-radius:12px;padding:28px 32px;}
.s-card.center{text-align:center;}
.s-av-wrap{position:relative;width:84px;height:84px;margin:0 auto 16px;cursor:pointer;}
.s-av-c{width:84px;height:84px;border-radius:50%;border:2.5px solid var(--line);overflow:hidden;display:flex;align-items:center;justify-content:center;transition:border-color .2s;background:var(--fog);}
.s-av-c:hover{border-color:var(--green);}
.s-av-init{font-family:'Libre Baskerville',serif;font-size:26px;font-weight:700;color:var(--green);}
.s-av-badge{position:absolute;bottom:2px;right:2px;width:22px;height:22px;border-radius:50%;background:var(--amber);border:2px solid #fff;display:flex;align-items:center;justify-content:center;}
.s-av-badge svg{width:10px;height:10px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round;}
.s-card h3{font-family:'Libre Baskerville',serif;font-size:17px;color:var(--txt);margin-bottom:4px;}
.s-card .s-sub{font-size:13px;color:var(--muted);margin-bottom:22px;}
.s-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.ff{margin-bottom:16px;}
.ff label{display:block;font-size:11px;font-weight:600;letter-spacing:.1em;text-transform:uppercase;color:var(--txt);margin-bottom:8px;}
.ff input,.ff textarea{width:100%;padding:11px 14px;border:1.5px solid var(--line);border-radius:8px;font-size:14px;font-family:'Sora',sans-serif;color:var(--txt);background:var(--fog);outline:none;transition:border .2s,box-shadow .2s;}
.ff textarea{resize:vertical;min-height:90px;}
.ff input:focus,.ff textarea:focus{border-color:var(--green);box-shadow:0 0 0 3px var(--gl);background:#fff;}
.ff input::placeholder,.ff textarea::placeholder{color:#c2cad4;}
.save-btn{display:inline-flex;align-items:center;gap:6px;padding:11px 24px;border:none;border-radius:8px;background:var(--dark);color:#fff;font-family:'Sora',sans-serif;font-size:13px;font-weight:600;cursor:pointer;transition:all .2s;}
.save-btn:hover{background:var(--dark2);box-shadow:0 4px 14px rgba(13,17,23,.2);transform:translateY(-1px);}
.save-btn svg{width:14px;height:14px;fill:none;stroke:currentColor;stroke-width:2.2;stroke-linecap:round;stroke-linejoin:round;}
.danger-zone{border:1px solid #fecdd3;border-radius:12px;padding:22px 28px;background:#fff5f5;}
.danger-zone h3{font-size:13px;font-weight:700;color:#be123c;margin-bottom:6px;}
.danger-zone p{font-size:13px;color:var(--muted);margin-bottom:16px;}
.d-btn{padding:9px 18px;border:1.5px solid #f87171;border-radius:7px;background:transparent;color:#e11d48;font-family:'Sora',sans-serif;font-size:12px;font-weight:600;cursor:pointer;transition:all .18s;}
.d-btn:hover{background:#e11d48;color:#fff;}

/* ── MODAL ── */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:500;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(3px);}
.modal-bg.hidden{display:none;}
.modal{background:#fff;border-radius:14px;padding:32px;width:100%;max-width:440px;box-shadow:0 20px 60px rgba(0,0,0,.2);animation:fadeIn .25s ease;overflow:hidden;box-sizing:border-box;}
.modal *{box-sizing:border-box;max-width:100%;}
.modal h3{font-family:'Libre Baskerville',serif;font-size:19px;color:var(--txt);margin-bottom:6px;}
.modal p{font-size:13px;color:var(--muted);margin-bottom:20px;}
.modal-actions{display:flex;gap:8px;justify-content:flex-end;margin-top:4px;}
.modal-actions button{padding:10px 18px;border-radius:8px;font-family:'Sora',sans-serif;font-size:12px;font-weight:600;cursor:pointer;border:1.5px solid;transition:all .18s;}
.mc{border-color:var(--line);background:transparent;color:var(--muted);}
.mc:hover{background:var(--fog);}
.ms{border-color:var(--green);background:var(--green);color:#fff;}
.ms:hover{background:var(--green2);}
.mob-ov{position:fixed;inset:0;background:rgba(0,0,0,.35);z-index:99;display:none;}
.mob-ov.on{display:block;}
#imgSettInput{display:none;}

@media(max-width:900px){
  .sb{left:-270px;transition:left .28s;z-index:100;}.sb.open{left:0;}
  .main{margin-left:0;}.hbtn{display:flex;}.topbar{padding:0 18px;}.content{padding:20px 16px;}
  .s-row{grid-template-columns:1fr;}.stats{grid-template-columns:1fr 1fr;}
}
@media(max-width:480px){.stats{grid-template-columns:1fr;}.cards-grid{grid-template-columns:1fr;}}
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.2.0/crypto-js.min.js"></script>
</head>
<body>
<div class="mob-ov" id="mobOv" onclick="sbClose()"></div>

<nav class="sb" id="sb">
  <div class="sb-head">
    <img src="https://media.licdn.com/dms/image/v2/C560BAQG5Hzzq4B9K9w/company-logo_200_200/company-logo_200_200/0/1630631565390/universit_hassiba_ben_bouali_chlef_logo?e=2147483647&v=beta&t=qV8g0KEAoEEfn9AnpTykEdqYr1Pdl0xMBkFI26KihQ4" alt="Logo"/>
    <div><div class="sb-brand-name">Hassiba Ben Bouali</div><div class="sb-brand-sub">University of Chlef</div></div>
  </div>
  <div class="sb-nav">
    <div class="nav-sec">Menu</div>
    <a href="?pg=Members" class="nav-item <?=$pg==='Members'?'active':''?>">
      <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      Members
      <span class="nbadge"><?=count($Members)?></span>
    </a>
    <a href="?pg=annonces" class="nav-item <?=$pg==='annonces'?'active':''?>">
      <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      Announcements
      <?php if(count($annonces)>0): ?><span class="nbadge"><?=count($annonces)?></span><?php endif; ?>
    </a>
    <a href="?pg=events" class="nav-item <?=$pg==='events'?'active':''?>">
      <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      Events
      <?php if(count($events)>0): ?><span class="nbadge"><?=count($events)?></span><?php endif; ?>
    </a>
    <a href="?pg=settings" class="nav-item <?=$pg==='settings'?'active':''?>">
      <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a2 2 0 0 1 0 2.83L17.66 9.17A7 7 0 0 1 19 12a7 7 0 0 1-1.34 2.83l1.41 1.41a2 2 0 0 1-2.83 2.83l-1.41-1.41A7 7 0 0 1 12 19a7 7 0 0 1-2.83-1.34l-1.41 1.41a2 2 0 0 1-2.83-2.83l1.41-1.41A7 7 0 0 1 5 12a7 7 0 0 1 1.34-2.83L4.93 7.76a2 2 0 0 1 2.83-2.83l1.41 1.41A7 7 0 0 1 12 5a7 7 0 0 1 2.83 1.34l1.41-1.41a2 2 0 0 1 2.83 0z"/></svg>
      Settings
    </a>
  </div>
  <div class="sb-foot">
    <button class="sb-invite-btn" type="button" onclick="openInv('','')">
      <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,12 2,6"/></svg>
      Send Invitation
    </button>
    <div class="sb-prof">
      <?php if(!empty($me['image'])): ?>
        <img src="<?=htmlspecialchars($me['image'])?>" class="sb-av" alt=""/>
      <?php else: ?>
        <div class="sb-av-ph"><?=htmlspecialchars($initials)?></div>
      <?php endif; ?>
      <div><div class="sb-prof-name"><?=htmlspecialchars($fullName)?></div><div class="sb-prof-role">Admin</div></div>
    </div>
    <a href="/dashboard.php" class="sb-back-btn">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/><line x1="9" y1="12" x2="21" y2="12"/></svg>
      Back to Dashboard
    </a>
    <form method="POST" action="">
      <input type="hidden" name="_act" value="logout"/>
      <button class="sb-logout" type="submit">
        <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Sign out
      </button>
    </form>
  </div>
</nav>

<div class="main">
  <header class="topbar">
    <div style="display:flex;align-items:center;gap:12px;">
      <button class="hbtn" onclick="sbOpen()"><svg viewBox="0 0 24 24"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg></button>
      <div>
        <?php
          $titles = ['Members'=>'Members','annonces'=>'Announcements','events'=>'Events','settings'=>'Settings'];
          $subs   = ['Members'=>'Enrolled faculty members','annonces'=>'Posts for your Members','events'=>'Upcoming & past events','settings'=>'Manage your account'];
        ?>
        <div class="tb-title"><?=htmlspecialchars($titles[$pg]??'Dashboard')?></div>
        <div class="tb-sub"><?=htmlspecialchars($subs[$pg]??'')?></div>
      </div>
    </div>
    <div class="tb-right">
      <?php if($pg==='Members'): ?>
      <div class="search">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" id="q" placeholder="Search Members…" oninput="filterStu(this.value)"/>
      </div>
      <?php elseif($pg==='annonces'): ?>
      <button class="add-btn" onclick="document.getElementById('annModal').classList.remove('hidden')">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Announcement
      </button>
      <?php elseif($pg==='events'): ?>
      <button class="add-btn" onclick="document.getElementById('evtModal').classList.remove('hidden')">
        <svg viewBox="0 0 24 24"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        New Event
      </button>
      <?php endif; ?>
    </div>
  </header>

  <div class="content">
    <?php if($flash): ?>
    <div class="flash <?=htmlspecialchars($flashType)?>"><?=htmlspecialchars($flash)?></div>
    <?php endif; ?>

    <?php /* ══════════════════════ Members ══════════════════════ */ ?>
    <?php if($pg==='Members'): ?>
    <div class="stats">
      <div class="stat"><div class="stat-icon g"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div><div class="stat-val"><?=count($Members)?></div><div class="stat-lbl">Total Members</div></div>
      <?php
        $gradeCount  = array_count_values(array_column($Members,'grade'));
        $domCount    = array_count_values(array_column($Members,'domain'));
        $topDomain   = $domCount ? array_search(max($domCount),$domCount) : '—';
        $bannedCount = count(array_filter($Members, fn($s)=>!empty($s['is_banned'])));
      ?>
      <div class="stat"><div class="stat-icon b"><svg viewBox="0 0 24 24"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg></div><div class="stat-val"><?=$gradeCount['Professor']??0?></div><div class="stat-lbl">Professor</div></div>
      <div class="stat"><div class="stat-icon p"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></div><div class="stat-val"><?=$gradeCount['Student']??0?></div><div class="stat-lbl">Student</div></div>
      <div class="stat"><div class="stat-icon a"><svg viewBox="0 0 24 24"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg></div><div class="stat-val"><?=$gradeCount['Researcher']??0?></div><div class="stat-lbl">Researcher</div></div>
      <div class="stat"><div class="stat-icon" style="background:rgba(225,29,72,.08);color:var(--rose);"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg></div><div class="stat-val" style="color:var(--rose)"><?=$bannedCount?></div><div class="stat-lbl">Banned</div></div>
    </div>
    <div class="sec-bar">
      <div><div class="sec-title">All Members</div><div class="sec-hint" id="stuHint"><?=count($Members)?> member<?=count($Members)!==1?'s':''?> enrolled</div></div>
      <div class="chips">
        <button class="chip on" onclick="setChip('all',this)">All</button>
        <button class="chip" onclick="setChip('Student',this)">Student</button>
        <button class="chip" onclick="setChip('Professor',this)">Professor</button>
        <button class="chip" onclick="setChip('Researcher',this)">Researcher</button>
        <button class="chip" onclick="setChip('Company manager',this)">Company Manager</button>
        <button class="chip" onclick="setChip('photo',this)">Has Photo</button>
        <button class="chip" onclick="setChip('banned',this)" style="border-color:rgba(225,29,72,.2);color:var(--rose);" id="bannedChip">Banned <?php if($bannedCount>0): ?><span style="background:var(--rose);color:#fff;font-size:9px;padding:0 5px;border-radius:8px;margin-left:3px;"><?=$bannedCount?></span><?php endif; ?></button>
      </div>
    </div>
    <div class="tbl-wrap">
      <?php if(empty($Members)): ?>
      <div class="empty-state"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><p>No Members found</p></div>
      <?php else: ?>
      <table id="stuTbl">
        <thead><tr><th>Student</th><th>Phone</th><th>Grade</th><th>Domain</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody id="stuBody">
          <?php foreach($Members as $i=>$s):
            $fn       = htmlspecialchars($s['firstname']??'');
            $ln       = htmlspecialchars($s['lastname']??'');
            $full     = trim($fn.' '.$ln);
            $ini      = strtoupper(substr($s['firstname']??'?',0,1).substr($s['lastname']??'',0,1));
            $email    = htmlspecialchars($s['email']??'');
            $phone    = htmlspecialchars($s['phone']??'');
            $img      = htmlspecialchars($s['image']??'');
            $grade    = $s['grade']??'';
            $domain   = $s['domain']??'';
            $identify = htmlspecialchars($s['identify']??'');
            $isBanned = !empty($s['is_banned']);
            $banReason= htmlspecialchars($s['ban_reason']??'');
            $gradeClass = 'badge-'.strtolower(str_replace(' manager','',$grade));
            $rowStyle = $isBanned ? 'background:rgba(225,29,72,.04);' : '';
          ?>
          <tr style="animation:rowIn .35s cubic-bezier(.16,1,.3,1) <?=$i*.04?>s both;<?=$rowStyle?>">
            <td><div class="stu-row">
              <?php if($img): ?><img src="<?=$img?>" class="stu-av<?=$isBanned?' stu-av-banned':''?>" alt="" onerror="this.style.display='none'"/>
              <?php else: ?><div class="stu-ph<?=$isBanned?' stu-ph-banned':''?>"><?=$ini?></div><?php endif; ?>
              <div>
                <div class="stu-name<?=$isBanned?' stu-name-banned':''?>"><?=$full?></div>
                <div class="stu-email"><?=$email?></div>
                <?php if($isBanned && $banReason): ?><div class="stu-ban-reason"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg><?=htmlspecialchars(mb_strimwidth($banReason,0,48,'…'))?></div><?php endif; ?>
              </div>
            </div></td>
            <td style="color:var(--muted)"><?=$phone?:'—'?></td>
            <td><?php if($grade): ?><span class="badge-pill <?=htmlspecialchars($gradeClass)?>"><?=htmlspecialchars(gradeLabel($grade))?></span><?php else: ?>—<?php endif; ?></td>
            <td><?php if($domain): ?><span class="badge-pill badge-domain"><?=htmlspecialchars(domainLabel($domain))?></span><?php else: ?>—<?php endif; ?></td>
            <td>
              <?php if($isBanned): ?>
                <span class="status-pill status-banned"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Banned</span>
              <?php else: ?>
                <span class="status-pill status-active"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Active</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if($isBanned): ?>
                <button class="ca-btn ca-unban" type="button" onclick="confirmUnban('<?=$identify?>','<?=$full?>')">
                  <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>Unban
                </button>
              <?php else: ?>
                <button class="ca-btn ca-ban" type="button" onclick="openBanModal('<?=$identify?>','<?=$full?>')">
                  <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Ban
                </button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>

    <?php /* ══════════════════════ ANNONCES ══════════════════════ */ ?>
    <?php elseif($pg==='annonces'): ?>
    <div class="stats">
      <div class="stat"><div class="stat-icon p"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div><div class="stat-val"><?=count($annonces)?></div><div class="stat-lbl">Total Announcements</div></div>
    </div>
    <?php if(empty($annonces)): ?>
    <div class="empty-state" style="background:var(--white);border:1px solid var(--line);border-radius:12px;">
      <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      <p>No announcements yet — create your first one</p>
    </div>
    <?php else: ?>
    <div class="cards-grid">
      <?php foreach($annonces as $i=>$a): ?>
      <div class="card" style="animation-delay:<?=$i*.06?>s">
        <div class="card-head">
          <div class="card-icon ann"><svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg></div>
          <div class="card-meta">
            <div class="card-title"><?=htmlspecialchars($a['title']??'Untitled')?></div>
            <div class="card-date"><?=htmlspecialchars(isset($a['created_at']) ? date('d M Y, H:i', strtotime($a['created_at'])) : '')?></div>
          </div>
          <div class="card-actions">
            <button class="ca-btn ca-edit js-edit-ann" type="button" data-id="<?=(int)($a['id']??0)?>" data-title="<?=htmlspecialchars($a['title']??'',ENT_QUOTES)?>" data-content="<?=htmlspecialchars($a['content']??'',ENT_QUOTES)?>"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit</button>
            <button class="ca-btn ca-del" type="button" onclick="confirmDelAnn(<?=(int)($a['id']??0)?>)"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>Delete</button>
          </div>
        </div>
        <div class="card-body"><?=htmlspecialchars($a['content']??'')?></div>
        <?php if(!empty($a['author'])): ?>
        <div class="card-footer">
          <span style="font-size:11px;color:var(--muted);">by <?=htmlspecialchars(trim(($a['author']['firstname']??'').(' '.($a['author']['lastname']??''))))?></span>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php /* ══════════════════════ EVENTS ══════════════════════ */ ?>
    <?php elseif($pg==='events'): ?>
    <div class="stats">
      <div class="stat"><div class="stat-icon b"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div><div class="stat-val"><?=count($events)?></div><div class="stat-lbl">Total Events</div></div>
    </div>
    <?php if(empty($events)): ?>
    <div class="empty-state" style="background:var(--white);border:1px solid var(--line);border-radius:12px;">
      <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
      <p>No events yet — create your first one</p>
    </div>
    <?php else: ?>
    <div class="cards-grid">
      <?php foreach($events as $i=>$e): ?>
      <div class="card" style="animation-delay:<?=$i*.06?>s">
        <div class="card-head">
          <div class="card-icon evt"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></div>
          <div class="card-meta">
            <div class="card-title"><?=htmlspecialchars($e['title']??'Untitled')?></div>
            <div class="card-date"><?=htmlspecialchars(!empty($e['event_type']) ? ucfirst($e['event_type']) : '')?></div>
          </div>
          <div class="card-actions">
            <button class="ca-btn ca-edit js-edit-evt" type="button" data-id="<?=(int)($e['id']??0)?>" data-title="<?=htmlspecialchars($e['title']??'',ENT_QUOTES)?>" data-description="<?=htmlspecialchars($e['description']??'',ENT_QUOTES)?>" data-location="<?=htmlspecialchars($e['location']??'',ENT_QUOTES)?>" data-start="<?=htmlspecialchars($e['start_at']??'',ENT_QUOTES)?>" data-end="<?=htmlspecialchars($e['end_at']??'',ENT_QUOTES)?>" data-capacity="<?=(int)($e['capacity']??0)?>"><svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>Edit</button>
            <button class="ca-btn ca-del" type="button" onclick="confirmDelEvt(<?=(int)($e['id']??0)?>)"><svg viewBox="0 0 24 24"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>Delete</button>
          </div>
        </div>
        <?php if(!empty($e['description'])): ?>
        <div class="card-body"><?=htmlspecialchars($e['description'])?></div>
        <?php endif; ?>
        <div class="card-footer">
          <?php if(!empty($e['start_at'])): ?><span class="card-tag event-date"><svg style="width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;display:inline;vertical-align:middle;margin-right:3px;" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><?=htmlspecialchars(date('d M Y, H:i', strtotime($e['start_at'])))?></span><?php endif; ?>
          <?php if(!empty($e['end_at'])): ?><span class="card-tag event-date" style="background:#f0f4ff;color:var(--blue);border-color:#bfdbfe;">→ <?=htmlspecialchars(date('d M Y, H:i', strtotime($e['end_at'])))?></span><?php endif; ?>
          <?php if(!empty($e['location'])): ?><span class="card-tag location"><svg style="width:10px;height:10px;fill:none;stroke:currentColor;stroke-width:2;display:inline;vertical-align:middle;margin-right:3px;" viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg><?=htmlspecialchars($e['location'])?></span><?php endif; ?>
          <?php if(isset($e['registered_count'])&&isset($e['capacity'])&&$e['capacity']): ?><span class="card-tag" style="background:var(--fog);color:var(--muted);border:1px solid var(--line);"><?=$e['registered_count']?>/<?=$e['capacity']?> registered</span><?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php /* ══════════════════════ SETTINGS ══════════════════════ */ ?>
    <?php elseif($pg==='settings'): ?>
    <div class="sett">
      <div class="s-card center">
        <form method="POST" action="" id="infoForm">
          <input type="hidden" name="_act" value="upd"/>
          <input type="hidden" name="_imadenc" id="encUpd"/>
          <input type="hidden" name="_dok" id="dokUpd"/>
          <input type="hidden" id="imgB64Sett"/>
          <input type="file" id="imgSettInput" accept="image/*" onchange="pickSettImg(this)"/>
          <div class="s-av-wrap" onclick="document.getElementById('imgSettInput').click()">
            <div class="s-av-c" id="sAvC">
              <?php if(!empty($me['image'])): ?>
                <img src="<?=htmlspecialchars($me['image'])?>" style="width:100%;height:100%;object-fit:cover;" alt=""/>
              <?php else: ?>
                <div class="s-av-init"><?=htmlspecialchars($initials)?></div>
              <?php endif; ?>
            </div>
            <div class="s-av-badge"><svg viewBox="0 0 24 24"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>
          </div>
          <h3>Personal Info</h3>
          <p class="s-sub">Update your profile details</p>
          <div class="s-row">
            <div class="ff"><label>First Name</label><input type="text" id="sett_fn" value="<?=htmlspecialchars($me['firstname']??'')?>"/></div>
            <div class="ff"><label>Last Name</label><input type="text" id="sett_ln" value="<?=htmlspecialchars($me['lastname']??'')?>"/></div>
          </div>
          <div class="ff"><label>Email</label><div style="padding:11px 15px;border:1.5px solid var(--line);border-radius:8px;font-size:14px;font-family:Sora,sans-serif;color:var(--muted);background:#f0f0f0;cursor:not-allowed;display:flex;align-items:center;gap:8px;"><svg style="width:14px;height:14px;flex-shrink:0;fill:none;stroke:var(--muted);stroke-width:2;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg><?=htmlspecialchars($me["email"]??"")?></div></div>
          <div class="ff"><label>Phone</label><input type="tel" id="sett_ph" value="<?=htmlspecialchars($me['phone']??'')?>"/></div>
          <button class="save-btn" type="submit"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Save Changes</button>
        </form>
      </div>
      <div class="s-card">
        <form method="POST" action="" id="pwdForm">
          <input type="hidden" name="_act" value="pwd"/>
          <input type="hidden" name="_imadenc" id="encPwd"/>
          <input type="hidden" name="_dok" id="dokPwd"/>
          <h3>Change Password</h3>
          <p class="s-sub">Choose a strong new password</p>
          <div class="ff"><label>New Password</label><input type="password" id="sett_p" placeholder="At least 6 characters"/></div>
          <div class="ff"><label>Confirm Password</label><input type="password" id="sett_c" placeholder="Repeat password"/></div>
          <button class="save-btn" type="submit"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Update Password</button>
        </form>
      </div>
      <div class="danger-zone">
        <h3>Sign Out</h3>
        <p>End your current session on this device.</p>
        <form method="POST" action="">
          <input type="hidden" name="_act" value="logout"/>
          <button class="d-btn" type="submit">Sign Out</button>
        </form>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── INVITE MODAL ── -->
<div class="modal-bg hidden" id="invModal">
  <div class="modal">
    <h3>Send Invitation</h3>
    <p>Invite a student to join via email.</p>
    <form method="POST" action="" id="invForm">
      <input type="hidden" name="_act" value="invite"/>
      <input type="hidden" name="_imadenc" id="encInv"/>
      <input type="hidden" name="_dok" id="dokInv"/>
      <div class="ff"><label>Name</label><input type="text" id="invName" placeholder="Student full name"/></div>
      <div class="ff"><label>Email</label><input type="email" id="invEmail" placeholder="student@email.com" required/></div>
      <div class="modal-actions">
        <button type="button" class="mc" onclick="closeModal('invModal')">Cancel</button>
        <button type="submit" class="ms">Send Invite</button>
      </div>
    </form>
  </div>
</div>

<!-- ── ANNONCE MODAL ── -->
<div class="modal-bg hidden" id="annModal">
  <div class="modal" style="max-width:480px;">
    <h3>New Announcement</h3>
    <p>Publish a message visible to all Members.</p>
    <form method="POST" action="?pg=annonces" id="annForm">
      <input type="hidden" name="_act" value="annonce"/>
      <input type="hidden" name="_imadenc" id="encAnn"/>
      <input type="hidden" name="_dok" id="dokAnn"/>
      <input type="hidden" id="ann_img_b64"/>
      <input type="file" id="ann_img_input" accept="image/*" style="display:none" onchange="pickImg(this,'ann_img_b64','ann_img_thumb','ann_img_lbl','ann_img_clear')"/>
      <div class="ff"><label>Title</label><input type="text" id="ann_title" placeholder="Announcement title" required/></div>
      <div class="ff"><label>Content</label><textarea id="ann_content" placeholder="Write your announcement here…" required></textarea></div>
      <div class="ff">
        <label>Image (optional)</label>
        <div class="img-pick" onclick="document.getElementById('ann_img_input').click()">
          <div class="img-pick-thumb" id="ann_img_thumb"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
          <div class="img-pick-lbl"><strong id="ann_img_lbl">Click to upload image</strong>PNG, JPG, WebP</div>
          <button type="button" class="img-pick-clear" id="ann_img_clear" style="display:none" onclick="event.stopPropagation();clearImg('ann_img_b64','ann_img_thumb','ann_img_lbl','ann_img_clear')">✕ Remove</button>
        </div>
      </div>
      <div class="toggle-row"><label>Pin announcement</label><label class="tog"><input type="checkbox" id="ann_pinned"/><span class="tog-sl"></span></label></div>
      <div class="toggle-row"><label>Visible to Members</label><label class="tog"><input type="checkbox" id="ann_visible" checked/><span class="tog-sl"></span></label></div>
      <div class="modal-actions">
        <button type="button" class="mc" onclick="closeModal('annModal')">Cancel</button>
        <button type="submit" class="ms">Publish</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EVENT MODAL ── -->
<div class="modal-bg hidden" id="evtModal">
  <div class="modal" style="max-width:480px;">
    <h3>New Event</h3>
    <p>Schedule an event for your Members.</p>
    <form method="POST" action="?pg=events" id="evtForm">
      <input type="hidden" name="_act" value="event"/>
      <input type="hidden" name="_imadenc" id="encEvt"/>
      <input type="hidden" name="_dok" id="dokEvt"/>
      <input type="hidden" id="evt_img_b64"/>
      <input type="file" id="evt_img_input" accept="image/*" style="display:none" onchange="pickImg(this,'evt_img_b64','evt_img_thumb','evt_img_lbl','evt_img_clear')"/>
      <div class="ff"><label>Title</label><input type="text" id="evt_title" placeholder="Event title" required/></div>
      <div class="ff"><label>Description</label><textarea id="evt_desc" placeholder="Optional description…"></textarea></div>
      <div class="ff">
        <label>Event Type</label>
        <div class="evt-type-row" id="evtTypeRow">
          <button type="button" class="type-chip sel" data-type="other" onclick="selectType(this)">Other</button>
          <button type="button" class="type-chip" data-type="workshop" onclick="selectType(this)">Workshop</button>
          <button type="button" class="type-chip" data-type="seminar" onclick="selectType(this)">Seminar</button>
          <button type="button" class="type-chip" data-type="conference" onclick="selectType(this)">Conference</button>
        </div>
        <input type="hidden" id="evt_type_val" value="other"/>
      </div>
      <div class="ff"><label>Start</label><input type="datetime-local" id="evt_start_at" required style="width:100%;min-width:0;"/></div>
      <div class="ff"><label>End (optional)</label><input type="datetime-local" id="evt_end_at" style="width:100%;min-width:0;"/></div>
      <div class="ff"><label>Location</label><input type="text" id="evt_location" placeholder="Room / online…"/></div>
      <div class="ff"><label>Capacity (optional)</label><input type="number" id="evt_capacity" placeholder="Max attendees" min="1"/></div>
      <div class="ff">
        <label>Image (optional)</label>
        <div class="img-pick" onclick="document.getElementById('evt_img_input').click()">
          <div class="img-pick-thumb" id="evt_img_thumb"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
          <div class="img-pick-lbl"><strong id="evt_img_lbl">Click to upload image</strong>PNG, JPG, WebP</div>
          <button type="button" class="img-pick-clear" id="evt_img_clear" style="display:none" onclick="event.stopPropagation();clearImg('evt_img_b64','evt_img_thumb','evt_img_lbl','evt_img_clear')">✕ Remove</button>
        </div>
      </div>
      <div class="toggle-row"><label>Visible to Members</label><label class="tog"><input type="checkbox" id="evt_visible" checked/><span class="tog-sl"></span></label></div>
      <div class="modal-actions">
        <button type="button" class="mc" onclick="closeModal('evtModal')">Cancel</button>
        <button type="submit" class="ms">Create Event</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT ANNOUNCEMENT MODAL ── -->
<div class="modal-bg hidden" id="editAnnModal">
  <div class="modal">
    <h3>Edit Announcement</h3>
    <p>Update the title or content of this announcement.</p>
    <form method="POST" action="?pg=annonces" id="editAnnForm">
      <input type="hidden" name="_act" value="edit_annonce"/>
      <input type="hidden" name="_imadenc" id="encEditAnn"/>
      <input type="hidden" name="_dok" id="dokEditAnn"/>
      <div class="ff"><label>Title</label><input type="text" id="eann_title" placeholder="Announcement title" required/></div>
      <div class="ff"><label>Content</label><textarea id="eann_content" placeholder="Write your announcement here…" required></textarea></div>
      <div class="modal-actions">
        <button type="button" class="mc" onclick="closeModal('editAnnModal')">Cancel</button>
        <button type="submit" class="ms">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ── EDIT EVENT MODAL ── -->
<div class="modal-bg hidden" id="editEvtModal">
  <div class="modal">
    <h3>Edit Event</h3>
    <p>Update the details of this event.</p>
    <form method="POST" action="?pg=events" id="editEvtForm">
      <input type="hidden" name="_act" value="edit_event"/>
      <input type="hidden" name="_imadenc" id="encEditEvt"/>
      <input type="hidden" name="_dok" id="dokEditEvt"/>
      <div class="ff"><label>Title</label><input type="text" id="eevt_title" placeholder="Event title" required/></div>
      <div class="ff"><label>Description</label><textarea id="eevt_desc" placeholder="Optional description…"></textarea></div>
      <div class="ff"><label>Start</label><input type="datetime-local" id="eevt_start_at" required style="width:100%;min-width:0;"/></div>
      <div class="ff"><label>End (optional)</label><input type="datetime-local" id="eevt_end_at" style="width:100%;min-width:0;"/></div>
      <div class="ff"><label>Location</label><input type="text" id="eevt_location" placeholder="Room / online…"/></div>
      <div class="ff"><label>Capacity (optional)</label><input type="number" id="eevt_capacity" placeholder="Max attendees" min="1"/></div>
      <div class="modal-actions">
        <button type="button" class="mc" onclick="closeModal('editEvtModal')">Cancel</button>
        <button type="submit" class="ms">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<!-- ── CONFIRM DELETE MODAL ── -->
<div class="modal-bg hidden" id="delModal">
  <div class="modal" style="max-width:360px;">
    <h3 id="delModalTitle">Delete?</h3>
    <p id="delModalMsg">This action cannot be undone.</p>
    <div class="modal-actions">
      <button type="button" class="mc" onclick="closeModal('delModal')">Cancel</button>
      <button type="button" id="delConfirmBtn" class="ms" style="background:var(--rose);border-color:var(--rose);">Delete</button>
    </div>
  </div>
</div>

<!-- hidden delete forms -->
<form method="POST" action="?pg=annonces" id="delAnnForm" style="display:none">
  <input type="hidden" name="_act" value="del_annonce"/>
  <input type="hidden" name="id" id="delAnnId"/>
</form>
<form method="POST" action="?pg=events" id="delEvtForm" style="display:none">
  <input type="hidden" name="_act" value="del_event"/>
  <input type="hidden" name="id" id="delEvtId"/>
</form>

<!-- ── BAN MODAL ── -->
<div class="modal-bg hidden" id="banModal">
  <div class="modal">
    <h3>Ban Student</h3>
    <p id="banModalMsg">This student will be blocked from accessing the platform.</p>
    <form method="POST" action="" id="banForm">
      <input type="hidden" name="_act" value="ban_student"/>
      <input type="hidden" name="_imadenc" id="encBan"/>
      <input type="hidden" name="_dok" id="dokBan"/>
      <div class="ff">
        <label>Reason for ban</label>
        <textarea id="banReason" placeholder="Describe why this student is being banned…" style="width:100%;padding:11px 14px;border:1.5px solid var(--line);border-radius:8px;font-size:13px;font-family:'Sora',sans-serif;color:var(--txt);background:var(--fog);outline:none;resize:vertical;min-height:80px;transition:border .2s,box-shadow .2s;" required></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="mc" onclick="closeModal('banModal')">Cancel</button>
        <button type="submit" class="ms" style="border-color:var(--rose);background:var(--rose);">
          <svg style="width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>
          Confirm Ban
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── UNBAN CONFIRM MODAL ── -->
<div class="modal-bg hidden" id="unbanModal">
  <div class="modal">
    <h3>Unban Student</h3>
    <p id="unbanModalMsg">This student will regain full access to the platform.</p>
    <form method="POST" action="" id="unbanForm">
      <input type="hidden" name="_act" value="unban_student"/>
      <input type="hidden" name="_imadenc" id="encUnban"/>
      <input type="hidden" name="_dok" id="dokUnban"/>
      <div class="modal-actions">
        <button type="button" class="mc" onclick="closeModal('unbanModal')">Cancel</button>
        <button type="submit" class="ms">
          <svg style="width:13px;height:13px;fill:none;stroke:currentColor;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
          Confirm Unban
        </button>
      </div>
    </form>
  </div>
</div>

<script>
var _K = CryptoJS.enc.Hex.parse('<?= $AES_KEY_HEX ?>');
function _aes(obj) {
  var iv = CryptoJS.lib.WordArray.random(16);
  var enc = CryptoJS.AES.encrypt(JSON.stringify(obj), _K, {iv:iv,mode:CryptoJS.mode.CBC,padding:CryptoJS.pad.Pkcs7});
  return CryptoJS.enc.Base64.stringify(iv.concat(enc.ciphertext));
}

/* ── Sidebar mobile ── */
function sbOpen(){document.getElementById('sb').classList.add('open');document.getElementById('mobOv').classList.add('on');}
function sbClose(){document.getElementById('sb').classList.remove('open');document.getElementById('mobOv').classList.remove('on');}

/* ── Modals ── */
function closeModal(id){document.getElementById(id).classList.add('hidden');}
document.querySelectorAll('.modal-bg').forEach(function(el){el.addEventListener('click',function(e){if(e.target===this)this.classList.add('hidden');});});

function openInv(email,name){
  document.getElementById('invEmail').value = email || '';
  document.getElementById('invName').value  = name  || '';
  document.getElementById('invModal').classList.remove('hidden');
  setTimeout(function(){ document.getElementById(email ? 'invName' : 'invEmail').focus(); }, 80);
}

/* ── AES form submissions ── */
document.getElementById('infoForm')?.addEventListener('submit',function(e){
  e.preventDefault();
  document.getElementById('encUpd').value = _aes({fn:document.getElementById('sett_fn').value,ln:document.getElementById('sett_ln').value,ph:document.getElementById('sett_ph').value,img:document.getElementById('imgB64Sett').value||null});
  document.getElementById('dokUpd').value = _aes({t:Date.now()});
  this.submit();
});
document.getElementById('pwdForm')?.addEventListener('submit',function(e){
  e.preventDefault();
  document.getElementById('encPwd').value = _aes({p:document.getElementById('sett_p').value,c:document.getElementById('sett_c').value});
  document.getElementById('dokPwd').value = _aes({t:Date.now()});
  this.submit();
});
document.getElementById('invForm')?.addEventListener('submit',function(e){
  e.preventDefault();
  document.getElementById('encInv').value = _aes({n:document.getElementById('invName').value,e:document.getElementById('invEmail').value});
  document.getElementById('dokInv').value = _aes({t:Date.now()});
  this.submit();
});
document.getElementById('annForm')?.addEventListener('submit',function(e){
  e.preventDefault();
  var payload = {
    title:      document.getElementById('ann_title').value,
    content:    document.getElementById('ann_content').value,
    is_pinned:  document.getElementById('ann_pinned').checked,
    is_visible: document.getElementById('ann_visible').checked,
  };
  var img = document.getElementById('ann_img_b64').value;
  if (img) payload.image = img;
  document.getElementById('encAnn').value = _aes(payload);
  document.getElementById('dokAnn').value = _aes({t:Date.now()});
  this.submit();
});
document.getElementById('evtForm')?.addEventListener('submit',function(e){
  e.preventDefault();
  var startRaw = document.getElementById('evt_start_at').value;
  var endRaw   = document.getElementById('evt_end_at').value;
  var cap      = document.getElementById('evt_capacity').value;
  var payload  = {
    title:       document.getElementById('evt_title').value,
    description: document.getElementById('evt_desc').value || null,
    start_at:    startRaw ? new Date(startRaw).toISOString() : '',
    location:    document.getElementById('evt_location').value || null,
    event_type:  document.getElementById('evt_type_val').value || 'other',
    is_visible:  document.getElementById('evt_visible').checked,
  };
  if (endRaw) payload.end_at   = new Date(endRaw).toISOString();
  if (cap)    payload.capacity = parseInt(cap);
  var img = document.getElementById('evt_img_b64').value;
  if (img) payload.image = img;
  document.getElementById('encEvt').value = _aes(payload);
  document.getElementById('dokEvt').value = _aes({t:Date.now()});
  this.submit();
});

/* ── Image picker helpers ── */
function pickImg(input, b64Id, thumbId, lblId, clearId) {
  var f = input.files[0]; if (!f) return;
  var rd = new FileReader();
  rd.onload = function(e) {
    document.getElementById(b64Id).value = e.target.result;
    var thumb = document.getElementById(thumbId);
    thumb.innerHTML = '<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover;" alt=""/>';
    document.getElementById(lblId).textContent = f.name.length > 22 ? f.name.slice(0,20)+'…' : f.name;
    document.getElementById(clearId).style.display = 'block';
    input.value = '';
  };
  rd.readAsDataURL(f);
}
function clearImg(b64Id, thumbId, lblId, clearId) {
  document.getElementById(b64Id).value = '';
  document.getElementById(thumbId).innerHTML = '<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>';
  document.getElementById(lblId).textContent = 'Click to upload image';
  document.getElementById(clearId).style.display = 'none';
}
function selectType(btn) {
  document.querySelectorAll('#evtTypeRow .type-chip').forEach(function(c){c.classList.remove('sel');});
  btn.classList.add('sel');
  document.getElementById('evt_type_val').value = btn.dataset.type;
}

/* ── Student filter ── */
var ALL_Members = <?=json_encode(array_map(fn($s)=>[
  'fn'        => $s['firstname']??'',
  'ln'        => $s['lastname']??'',
  'email'     => $s['email']??'',
  'phone'     => $s['phone']??'',
  'image'     => $s['image']??'',
  'grade'     => $s['grade']??'',
  'domain'    => $s['domain']??'',
  'identify'  => $s['identify']??'',
  'is_banned' => !empty($s['is_banned']),
  'ban_reason'=> $s['ban_reason']??'',
], $Members))?>;

var curChip = 'all';

var DOMAIN_LABELS = {
  'intelligence artificielle':'AI','developpement web':'Web Dev','cyber securite':'Cybersec',
  'reseaux et telecommunications':'Networks','systemes embarques':'Embedded',
  'science des donnees':'Data Sci','genie logiciel':'Software Eng','autre':'Other'
};
function domLbl(d){return DOMAIN_LABELS[d.toLowerCase()]||d;}

var GRADE_CLASS = {'Student':'badge-student','Professor':'badge-professor','Researcher':'badge-researcher','Company manager':'badge-company'};

function setChip(f,btn){curChip=f;document.querySelectorAll('.chip').forEach(c=>c.classList.remove('on'));btn.classList.add('on');filterStu(document.getElementById('q')?.value||'');}

function filterStu(q){
  var list = ALL_Members.slice();
  if(curChip==='photo')    list=list.filter(s=>s.image);
  else if(curChip==='banned') list=list.filter(s=>s.is_banned);
  else if(curChip!=='all') list=list.filter(s=>s.grade===curChip);
  if(q.trim()) list=list.filter(s=>(s.fn+' '+s.ln+s.email+s.grade+s.domain).toLowerCase().includes(q.toLowerCase()));
  var tb=document.getElementById('stuBody'); if(!tb)return;
  var hint=document.getElementById('stuHint');
  tb.innerHTML='';
  if(!list.length){hint.textContent='No Members found';return;}
  hint.textContent=list.length+' student'+(list.length!==1?'s':'')+' found';
  list.forEach(function(s,i){
    var full=(s.fn+' '+s.ln).trim();
    var ini=((s.fn[0]||'?').toUpperCase()+(s.ln[0]||'').toUpperCase());
    var banned=s.is_banned;
    var avClass='stu-av'+(banned?' stu-av-banned':'');
    var phClass='stu-ph'+(banned?' stu-ph-banned':'');
    var nmClass='stu-name'+(banned?' stu-name-banned':'');
    var av=s.image?'<img src="'+s.image+'" class="'+avClass+'" onerror="this.style.display=\'none\'" alt=""/>':'<div class="'+phClass+'">'+ini+'</div>';
    var reasonHtml=banned&&s.ban_reason?'<div class="stu-ban-reason"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>'+(s.ban_reason.length>48?s.ban_reason.slice(0,48)+'…':s.ban_reason)+'</div>':'';
    var gCls=GRADE_CLASS[s.grade]||'badge-student';
    var gradePill=s.grade?'<span class="badge-pill '+gCls+'">'+s.grade.charAt(0).toUpperCase()+s.grade.slice(1)+'</span>':'—';
    var domPill=s.domain?'<span class="badge-pill badge-domain">'+domLbl(s.domain)+'</span>':'—';
    var statusPill=banned
      ?'<span class="status-pill status-banned"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Banned</span>'
      :'<span class="status-pill status-active"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>Active</span>';
    var actionBtn=banned
      ?'<button class="ca-btn ca-unban" type="button" onclick="confirmUnban(\''+s.identify+'\',\''+full.replace(/'/g,'\\\'')+'\')" ><svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>Unban</button>'
      :'<button class="ca-btn ca-ban" type="button" onclick="openBanModal(\''+s.identify+'\',\''+full.replace(/'/g,'\\\'')+'\')" ><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"/></svg>Ban</button>';
    var tr=document.createElement('tr');
    tr.style.cssText='animation:rowIn .35s cubic-bezier(.16,1,.3,1) '+(i*.04)+'s both'+(banned?';background:rgba(225,29,72,.04)':'');
    tr.innerHTML='<td><div class="stu-row">'+av+'<div><div class="'+nmClass+'">'+full+'</div><div class="stu-email">'+s.email+'</div>'+reasonHtml+'</div></div></td>'
      +'<td style="color:var(--muted)">'+(s.phone||'—')+'</td>'
      +'<td>'+gradePill+'</td>'
      +'<td>'+domPill+'</td>'
      +'<td>'+statusPill+'</td>'
      +'<td>'+actionBtn+'</td>';
    tb.appendChild(tr);
  });
}

/* ── Settings avatar picker ── */
function pickSettImg(input){
  var f=input.files[0];if(!f)return;
  var rd=new FileReader();
  rd.onload=function(e){
    document.getElementById('imgB64Sett').value=e.target.result;
    document.getElementById('sAvC').innerHTML='<img src="'+e.target.result+'" style="width:100%;height:100%;object-fit:cover;" alt=""/>';
  };
  rd.readAsDataURL(f);
}

/* ── Edit Announcement ── */
var _editAnnId = 0;
function openEditAnn(id, title, content) {
  _editAnnId = id;
  document.getElementById('eann_title').value   = title;
  document.getElementById('eann_content').value = content;
  document.getElementById('editAnnModal').classList.remove('hidden');
  setTimeout(function(){ document.getElementById('eann_title').focus(); }, 80);
}
document.addEventListener('click', function(e) {
  var btn = e.target.closest('.js-edit-ann');
  if (btn) openEditAnn(+btn.dataset.id, btn.dataset.title, btn.dataset.content);
});
document.getElementById('editAnnForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  document.getElementById('encEditAnn').value = _aes({id:_editAnnId,title:document.getElementById('eann_title').value,content:document.getElementById('eann_content').value});
  document.getElementById('dokEditAnn').value = _aes({t:Date.now()});
  this.submit();
});

/* ── Delete Announcement ── */
function confirmDelAnn(id) {
  document.getElementById('delModalTitle').textContent = 'Delete Announcement?';
  document.getElementById('delModalMsg').textContent   = 'This announcement will be permanently deleted.';
  document.getElementById('delModal').classList.remove('hidden');
  document.getElementById('delConfirmBtn').onclick = function() {
    document.getElementById('delAnnId').value = id;
    document.getElementById('delAnnForm').submit();
  };
}

/* ── Edit Event ── */
var _editEvtId = 0;
function openEditEvt(data) {
  _editEvtId = data.id;
  document.getElementById('eevt_title').value    = data.title    || '';
  document.getElementById('eevt_desc').value     = data.description || '';
  document.getElementById('eevt_location').value = data.location  || '';
  document.getElementById('eevt_capacity').value = data.capacity  || '';
  function toLocal(iso) {
    if (!iso) return '';
    var d = new Date(iso);
    var pad = function(n){return String(n).padStart(2,'0');};
    return d.getFullYear()+'-'+pad(d.getMonth()+1)+'-'+pad(d.getDate())+'T'+pad(d.getHours())+':'+pad(d.getMinutes());
  }
  document.getElementById('eevt_start_at').value = toLocal(data.start_at);
  document.getElementById('eevt_end_at').value   = toLocal(data.end_at);
  document.getElementById('editEvtModal').classList.remove('hidden');
  setTimeout(function(){ document.getElementById('eevt_title').focus(); }, 80);
}
document.addEventListener('click', function(e) {
  var btn = e.target.closest('.js-edit-evt');
  if (btn) openEditEvt({id:+btn.dataset.id,title:btn.dataset.title,description:btn.dataset.description,location:btn.dataset.location,start_at:btn.dataset.start,end_at:btn.dataset.end,capacity:btn.dataset.capacity});
});
document.getElementById('editEvtForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  var startRaw = document.getElementById('eevt_start_at').value;
  var endRaw   = document.getElementById('eevt_end_at').value;
  var cap      = document.getElementById('eevt_capacity').value;
  var payload  = {
    id:          _editEvtId,
    title:       document.getElementById('eevt_title').value,
    description: document.getElementById('eevt_desc').value,
    location:    document.getElementById('eevt_location').value,
    start_at:    startRaw ? new Date(startRaw).toISOString() : '',
  };
  if (endRaw) payload.end_at   = new Date(endRaw).toISOString();
  if (cap)    payload.capacity = parseInt(cap);
  document.getElementById('encEditEvt').value = _aes(payload);
  document.getElementById('dokEditEvt').value = _aes({t:Date.now()});
  this.submit();
});

/* ── Ban / Unban ── */
var _banIdentify = '';
function openBanModal(identify, name) {
  _banIdentify = identify;
  document.getElementById('banModalMsg').textContent = 'Ban "'+name+'" from accessing the platform. Please provide a reason.';
  document.getElementById('banReason').value = '';
  document.getElementById('banModal').classList.remove('hidden');
  setTimeout(function(){ document.getElementById('banReason').focus(); }, 80);
}
document.getElementById('banForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  var reason = document.getElementById('banReason').value.trim();
  if (!reason) return;
  document.getElementById('encBan').value = _aes({identify: _banIdentify, reason: reason});
  document.getElementById('dokBan').value = _aes({t: Date.now()});
  this.submit();
});

var _unbanIdentify = '';
function confirmUnban(identify, name) {
  _unbanIdentify = identify;
  document.getElementById('unbanModalMsg').textContent = 'Are you sure you want to unban "'+name+'"? They will regain full access.';
  document.getElementById('unbanModal').classList.remove('hidden');
}
document.getElementById('unbanForm')?.addEventListener('submit', function(e) {
  e.preventDefault();
  document.getElementById('encUnban').value = _aes({identify: _unbanIdentify});
  document.getElementById('dokUnban').value = _aes({t: Date.now()});
  this.submit();
});

/* ── Delete Event ── */
function confirmDelEvt(id) {
  document.getElementById('delModalTitle').textContent = 'Delete Event?';
  document.getElementById('delModalMsg').textContent   = 'This event will be permanently deleted.';
  document.getElementById('delModal').classList.remove('hidden');
  document.getElementById('delConfirmBtn').onclick = function() {
    document.getElementById('delEvtId').value = id;
    document.getElementById('delEvtForm').submit();
  };
}
</script>
</body>
</html>
