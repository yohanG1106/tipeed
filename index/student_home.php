<?php
include "db_connect.php";
session_start();
include 'db_connect.php';

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

$firstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : "User";
$lastName  = isset($_SESSION['last_name']) ? $_SESSION['last_name'] : "";
$role      = isset($_SESSION['role']) ? $_SESSION['role'] : "student";
$yearLevel = isset($_SESSION['year_level']) ? $_SESSION['year_level'] : null;

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$studentName = trim($firstName . " " . $lastName);

function ordinal($number) {
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($number % 100) >= 11 && ($number % 100) <= 13) return $number . 'th';
    return $number . $ends[$number % 10];
}

if ($role === 'student') {
    $studentIDT = ($yearLevel && is_numeric($yearLevel)) ? ordinal($yearLevel) . " Year" : "No year assigned";
} elseif ($role === 'faculty') {
    $studentIDT = "Faculty";
} elseif ($role === 'admin') {
    $studentIDT = "Administrator";
} else {
    $studentIDT = ucfirst(htmlspecialchars($role));
}

$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$homePage = $currentUserRole === 'admin' ? 'admin_home.php' : ($currentUserRole === 'teacher' ? 'teacher_home.php' : 'student_home.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TiPeed Forum</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* â”€â”€ TOKENS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    :root {
      --bg:        #f7f6f3;
      --bg-card:   #ffffff;
      --bg-muted:  #eeede9;
      --bg-input:  #f0efe9;
      --text:      #1a1916;
      --text-2:    #6b6a65;
      --text-3:    #9b9a96;
      --border:    rgba(26,25,22,.10);
      --border-2:  rgba(26,25,22,.18);
      --accent:    #1a1916;
      --accent-fg: #f7f6f3;
      --red:       #c0392b;
      --red-bg:    #fdf0ee;
      --amber:     #b45309;
      --amber-bg:  #fef7ec;
      --green:     #1e6b3a;
      --green-bg:  #edf6f0;
      --blue:      #1d4ed8;
      --blue-bg:   #eff4ff;
      --radius:    10px;
      --radius-lg: 16px;
      --sidebar-w: 240px;
      --nav-h:     56px;
      --font:      'DM Sans', sans-serif;
      --mono:      'DM Mono', monospace;
      --shadow:    0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
      --shadow-md: 0 4px 16px rgba(0,0,0,.08);
    }

    [data-theme="dark"] {
      --bg:        #111110;
      --bg-card:   #1c1b19;
      --bg-muted:  #252421;
      --bg-input:  #2a2926;
      --text:      #f0efe9;
      --text-2:    #a09f9a;
      --text-3:    #6b6a65;
      --border:    rgba(240,239,233,.10);
      --border-2:  rgba(240,239,233,.20);
      --accent:    #f0efe9;
      --accent-fg: #1a1916;
      --red-bg:    #2a1a18;
      --amber-bg:  #221a0e;
      --green-bg:  #0f1f16;
      --blue-bg:   #0f1628;
      --shadow:    0 1px 3px rgba(0,0,0,.3);
      --shadow-md: 0 4px 16px rgba(0,0,0,.4);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    html { font-size: 15px; -webkit-font-smoothing: antialiased; }

    body {
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      transition: background .25s, color .25s;
    }

    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }
    input, textarea, select { font-family: var(--font); }

    /* â”€â”€ NAVBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      height: var(--nav-h);
      background: var(--bg-card);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center;
      padding: 0 24px; gap: 20px;
    }

    .nav-logo {
      font-size: 17px; font-weight: 500;
      letter-spacing: -.3px;
      flex-shrink: 0;
    }
    .nav-logo span { color: var(--text-3); font-weight: 300; }

    .nav-links {
      display: flex; align-items: center; gap: 4px;
      flex: 1; padding-left: 8px;
    }
    .nav-links a {
      font-size: 14px; color: var(--text-2);
      padding: 5px 11px; border-radius: 7px;
      transition: background .15s, color .15s;
    }
    .nav-links a:hover { background: var(--bg-muted); color: var(--text); }
    .nav-links a.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }

    .nav-right { display: flex; align-items: center; gap: 10px; margin-left: auto; }

    .search-wrap {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 8px; padding: 0 12px; height: 34px;
    }
    .search-wrap i { color: var(--text-3); font-size: 13px; }
    .search-wrap input {
      background: none; border: none; outline: none;
      font-size: 13px; color: var(--text); width: 180px;
    }
    .search-wrap input::placeholder { color: var(--text-3); }

    .theme-btn {
      width: 34px; height: 34px;
      border: 1px solid var(--border); border-radius: 8px;
      background: var(--bg-input);
      color: var(--text-2); font-size: 14px;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s, color .15s;
    }
    .theme-btn:hover { background: var(--bg-muted); color: var(--text); }

    /* â”€â”€ LAYOUT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .layout {
      display: flex;
      padding-top: var(--nav-h);
      min-height: 100vh;
    }

    /* â”€â”€ SIDEBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .sidebar {
      width: var(--sidebar-w);
      flex-shrink: 0;
      position: sticky; top: var(--nav-h);
      height: calc(100vh - var(--nav-h));
      overflow-y: auto;
      padding: 20px 12px;
      border-right: 1px solid var(--border);
      background: var(--bg-card);
      display: flex; flex-direction: column; gap: 6px;
    }

    .sidebar-profile {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 10px 14px;
      border-bottom: 1px solid var(--border);
      margin-bottom: 6px;
    }
    .avatar-circle {
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--bg-muted);
      border: 1px solid var(--border-2);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 500; color: var(--text-2);
      flex-shrink: 0; font-family: var(--mono);
    }
    .profile-info { min-width: 0; }
    .profile-name {
      font-size: 13px; font-weight: 500;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .profile-role {
      font-size: 11px; color: var(--text-3);
      font-family: var(--mono);
    }

    .menu-item {
      display: flex; align-items: center; gap: 9px;
      padding: 7px 10px; border-radius: 8px;
      font-size: 13.5px; color: var(--text-2);
      transition: background .15s, color .15s;
    }
    .menu-item:hover { background: var(--bg-muted); color: var(--text); }
    .menu-item.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .menu-item i { width: 16px; text-align: center; font-size: 13px; color: var(--text-3); flex-shrink: 0; }
    .menu-item:hover i, .menu-item.active i { color: var(--text-2); }

    .menu-divider { height: 1px; background: var(--border); margin: 8px 4px; }

    /* â”€â”€ MAIN â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .main {
      flex: 1; min-width: 0;
      max-width: 680px;
      margin: 0 auto;
      padding: 28px 24px 60px;
    }

    /* â”€â”€ HERO BANNER â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .hero {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 28px 28px 24px;
      margin-bottom: 24px;
      position: relative; overflow: hidden;
    }
    .hero::before {
      content: '';
      position: absolute; inset: 0;
      background: repeating-linear-gradient(
        45deg,
        transparent, transparent 20px,
        rgba(0,0,0,.018) 20px, rgba(0,0,0,.018) 21px
      );
      pointer-events: none;
    }
    [data-theme="dark"] .hero::before {
      background: repeating-linear-gradient(
        45deg,
        transparent, transparent 20px,
        rgba(255,255,255,.025) 20px, rgba(255,255,255,.025) 21px
      );
    }
    .hero-eyebrow {
      font-size: 11px; font-weight: 500; color: var(--text-3);
      letter-spacing: .08em; text-transform: uppercase;
      font-family: var(--mono);
      margin-bottom: 8px;
    }
    .hero h1 {
      font-size: 26px; font-weight: 400; letter-spacing: -.5px;
      line-height: 1.2; margin-bottom: 6px;
    }
    .hero h1 strong { font-weight: 500; }
    .hero p {
      font-size: 14px; color: var(--text-2); line-height: 1.6;
    }

    /* â”€â”€ COMPOSE BOX â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .compose-box {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 14px 16px;
      display: flex; align-items: center; gap: 12px;
      margin-bottom: 24px;
      cursor: pointer;
      transition: border-color .15s, box-shadow .15s;
    }
    .compose-box:hover {
      border-color: var(--border-2);
      box-shadow: var(--shadow);
    }
    .compose-avatar { width: 32px; height: 32px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 500; color: var(--text-3); font-family: var(--mono); flex-shrink: 0; }
    .compose-placeholder { font-size: 14px; color: var(--text-3); flex: 1; }
    .compose-btn {
      background: var(--accent); color: var(--accent-fg);
      border: none; border-radius: 7px;
      padding: 6px 14px; font-size: 13px; font-weight: 500;
      transition: opacity .15s;
    }
    .compose-btn:hover { opacity: .85; }

    /* â”€â”€ SECTION HEADING â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .section-heading {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 14px;
    }
    .section-heading h2 {
      font-size: 13px; font-weight: 500; color: var(--text-2);
      letter-spacing: .05em; text-transform: uppercase;
      font-family: var(--mono);
    }
    .sort-bar { display: flex; gap: 4px; }
    .sort-btn {
      font-size: 12px; color: var(--text-3); background: none;
      border: 1px solid transparent; border-radius: 6px;
      padding: 3px 9px; transition: all .15s;
    }
    .sort-btn:hover { border-color: var(--border); color: var(--text-2); }
    .sort-btn.active { border-color: var(--border-2); color: var(--text); background: var(--bg-muted); }

    /* â”€â”€ TRENDING STRIP â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .trending-strip {
      display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;
      margin-bottom: 28px;
    }
    .trending-card {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 14px;
      cursor: pointer; transition: border-color .15s, box-shadow .15s;
    }
    .trending-card:hover { border-color: var(--border-2); box-shadow: var(--shadow); }
    .trending-card img {
      width: 100%; height: 90px; object-fit: cover;
      border-radius: 7px; margin-bottom: 10px;
      background: var(--bg-muted);
    }
    .trending-card .tc-meta {
      font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-bottom: 4px;
    }
    .trending-card .tc-title {
      font-size: 13px; font-weight: 500; line-height: 1.4;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .trending-card .tc-votes {
      font-size: 11px; color: var(--text-3); font-family: var(--mono);
      margin-top: 8px; display: flex; align-items: center; gap: 5px;
    }
    .trend-badge {
      display: inline-flex; align-items: center; gap: 4px;
      background: var(--amber-bg); color: var(--amber);
      font-size: 10px; font-family: var(--mono); font-weight: 500;
      padding: 2px 7px; border-radius: 5px; margin-bottom: 8px;
    }

    /* â”€â”€ THREAD CARD â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .thread-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px 18px 14px;
      margin-bottom: 10px;
      transition: border-color .15s, box-shadow .15s;
    }
    .thread-card:hover { border-color: var(--border-2); box-shadow: var(--shadow); }

    .thread-header {
      display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px;
    }
    .thread-ava {
      width: 34px; height: 34px; border-radius: 50%;
      background: var(--bg-muted); border: 1px solid var(--border);
      flex-shrink: 0; display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 500; color: var(--text-2); font-family: var(--mono);
    }
    .thread-meta-wrap { flex: 1; min-width: 0; }
    .thread-author { font-size: 13px; font-weight: 500; }
    .thread-time { font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-top: 1px; }

    .thread-menu-wrap { position: relative; }
    .menu-trigger {
      width: 28px; height: 28px; border: none; background: none;
      border-radius: 6px; color: var(--text-3);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; transition: background .15s, color .15s;
    }
    .menu-trigger:hover { background: var(--bg-muted); color: var(--text-2); }
    .dropdown {
      position: absolute; right: 0; top: calc(100% + 4px);
      background: var(--bg-card); border: 1px solid var(--border-2);
      border-radius: 10px; overflow: hidden;
      box-shadow: var(--shadow-md); z-index: 20; min-width: 140px;
      display: none;
    }
    .dropdown.open { display: block; }
    .dropdown button {
      width: 100%; display: flex; align-items: center; gap: 8px;
      padding: 9px 14px; font-size: 13px; color: var(--text-2);
      background: none; border: none;
      transition: background .12s, color .12s;
    }
    .dropdown button:hover { background: var(--bg-muted); color: var(--text); }
    .dropdown button.danger { color: var(--red); }
    .dropdown button.danger:hover { background: var(--red-bg); }
    .dropdown button i { font-size: 12px; width: 14px; }

    .thread-title { font-size: 15px; font-weight: 500; letter-spacing: -.2px; margin-bottom: 6px; }
    .thread-body { font-size: 14px; color: var(--text-2); line-height: 1.65; margin-bottom: 10px; }
    .thread-img {
      width: 100%; max-height: 280px; object-fit: cover;
      border-radius: 10px; margin-bottom: 12px;
      border: 1px solid var(--border);
    }

    .thread-footer {
      display: flex; align-items: center; gap: 4px;
      border-top: 1px solid var(--border);
      padding-top: 10px; margin-top: 4px;
    }
    .action-pill {
      display: flex; align-items: center; gap: 5px;
      padding: 5px 10px; border: none;
      background: none; border-radius: 7px;
      font-size: 12.5px; color: var(--text-3);
      transition: background .15s, color .15s;
    }
    .action-pill:hover { background: var(--bg-muted); color: var(--text-2); }
    .action-pill i { font-size: 12px; }

    .vote-group {
      display: flex; align-items: center;
      border: 1px solid var(--border); border-radius: 8px;
      overflow: hidden; margin-right: 4px;
    }
    .vote-btn {
      display: flex; align-items: center; gap: 4px;
      padding: 5px 9px; border: none; background: none;
      font-size: 12px; color: var(--text-3);
      transition: background .15s, color .15s;
    }
    .vote-btn:hover { background: var(--bg-muted); color: var(--text-2); }
    .vote-sep { width: 1px; background: var(--border); height: 20px; }
    .vote-count { font-family: var(--mono); font-size: 12px; color: var(--text-2); padding: 0 8px; }
    .comment-count { font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-left: auto; }

    /* â”€â”€ EMPTY STATE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .empty-state {
      text-align: center; padding: 48px 20px;
      border: 1px dashed var(--border-2);
      border-radius: var(--radius-lg); color: var(--text-3);
    }
    .empty-state p { font-size: 14px; margin-top: 8px; }

    /* â”€â”€ POPUP / MODAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .overlay {
      display: none; position: fixed; inset: 0; z-index: 200;
      background: rgba(0,0,0,.35); backdrop-filter: blur(4px);
      align-items: center; justify-content: center;
    }
    .overlay.open { display: flex; }

    .modal {
      background: var(--bg-card); border: 1px solid var(--border-2);
      border-radius: var(--radius-lg); box-shadow: var(--shadow-md);
      padding: 24px; width: 100%; max-width: 480px;
      margin: 16px;
    }
    .modal-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 20px;
    }
    .modal-header h3 { font-size: 16px; font-weight: 500; letter-spacing: -.2px; }
    .close-btn {
      width: 30px; height: 30px; border: none; background: var(--bg-muted);
      border-radius: 7px; color: var(--text-2); font-size: 16px;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s, color .15s;
    }
    .close-btn:hover { background: var(--border); color: var(--text); }

    .field-label {
      font-size: 11px; font-weight: 500; color: var(--text-3);
      text-transform: uppercase; letter-spacing: .07em; font-family: var(--mono);
      margin-bottom: 6px; display: block;
    }
    .field-wrap { margin-bottom: 14px; }
    .field-input {
      width: 100%; background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 8px; padding: 9px 12px;
      font-size: 14px; color: var(--text); outline: none;
      transition: border-color .15s;
    }
    .field-input:focus { border-color: var(--border-2); }
    textarea.field-input { resize: vertical; min-height: 90px; }
    select.field-input { appearance: none; cursor: pointer; }

    .file-label {
      display: flex; align-items: center; gap: 8px;
      padding: 9px 12px; background: var(--bg-input);
      border: 1px dashed var(--border-2); border-radius: 8px;
      font-size: 13px; color: var(--text-3); cursor: pointer;
      transition: border-color .15s;
    }
    .file-label:hover { border-color: var(--border); color: var(--text-2); }
    .file-label input { display: none; }
    .file-note { font-size: 11px; color: var(--text-3); margin-top: 4px; font-family: var(--mono); }

    .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px; }
    .btn-ghost {
      background: none; border: 1px solid var(--border);
      border-radius: 8px; padding: 7px 16px;
      font-size: 13px; color: var(--text-2);
      transition: background .15s, color .15s;
    }
    .btn-ghost:hover { background: var(--bg-muted); color: var(--text); }
    .btn-solid {
      background: var(--accent); color: var(--accent-fg);
      border: none; border-radius: 8px;
      padding: 7px 18px; font-size: 13px; font-weight: 500;
      transition: opacity .15s;
    }
    .btn-solid:hover { opacity: .85; }

    /* chip badges */
    .chip {
      display: inline-block; font-size: 11px; font-family: var(--mono);
      padding: 2px 8px; border-radius: 5px; font-weight: 500;
    }
    .chip-red    { background: var(--red-bg);   color: var(--red);   }
    .chip-amber  { background: var(--amber-bg);  color: var(--amber); }
    .chip-green  { background: var(--green-bg);  color: var(--green); }
    .chip-blue   { background: var(--blue-bg);   color: var(--blue);  }

    /* â”€â”€ SCROLLBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* â”€â”€ ANIMATIONS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(10px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .thread-card { animation: fadeUp .25s ease both; }
    .thread-card:nth-child(2) { animation-delay: .05s; }
    .thread-card:nth-child(3) { animation-delay: .1s; }
    .thread-card:nth-child(4) { animation-delay: .15s; }

    /* â”€â”€ RESPONSIVE â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    @media (max-width: 720px) {
      .sidebar { display: none; }
      .main { padding: 16px 14px 60px; }
      .trending-strip { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 460px) {
      .trending-strip { grid-template-columns: 1fr; }
      .nav-links { display: none; }
      .search-wrap { display: none; }
    }
  </style>
</head>
<body>

<!-- â”€â”€ NAVBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<nav class="navbar">
  <div class="nav-logo">Ti<span>Peed</span></div>
  <div class="nav-links">
    <a href="<?= $homePage ?>" class="active">Home</a>
    <?php if ($currentUserRole === 'admin' || $currentUserRole === 'faculty'): ?>
    <a href="student_home.php">Threads</a>
    <a href="faculty_chats.php">Faculty</a>
    <?php endif; ?>
    <a href="Community.php">Community</a>
    <a href="aboutus.php">About</a>
  </div>
  <div class="nav-right">
    <div class="search-wrap">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search threadsâ€¦">
    </div>
    <button class="theme-btn" id="themeBtn" title="Toggle dark mode">
      <i class="fas fa-moon"></i>
    </button>
  </div>
</nav>

<!-- â”€â”€ LAYOUT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-profile">
      <div class="avatar-circle"><?= strtoupper(substr($studentName, 0, 2)) ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($studentName) ?></div>
        <div class="profile-role"><?= htmlspecialchars($studentIDT) ?></div>
      </div>
    </div>

    <a href="profile.php" class="menu-item active"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>" class="menu-item"><i class="fas fa-house"></i> Home</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="CourseChat.php" class="menu-item"><i class="fas fa-comments"></i> Communities</a>
    <a href="Community.php" class="menu-item"><i class="fas fa-users"></i> Community</a>
    <?php if ($currentUserRole === 'admin'): ?>
    <a href="admin_reg.php" class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>

    <div class="menu-divider"></div>

    <a href="calendar.php" class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <a href="#" class="menu-item"><i class="fas fa-gear"></i> Settings</a>
    <a href="Help.php" class="menu-item"><i class="fas fa-circle-question"></i> Help</a>

    <div class="menu-divider"></div>

    <a href="logout.php" class="menu-item" style="color:var(--red)">
      <i class="fas fa-arrow-right-from-bracket" style="color:var(--red)"></i> Log out
    </a>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- Hero -->
    <div class="hero">
      <div class="hero-eyebrow">TiPeed Forum</div>
      <h1>Welcome, <strong><?= htmlspecialchars($firstName) ?></strong></h1>
      <p>Join the conversation â€” ask questions, share ideas, and connect with your campus.</p>
    </div>

    <!-- Compose -->
    <div class="compose-box" id="openCompose">
      <div class="compose-avatar"><?= strtoupper(substr($studentName, 0, 2)) ?></div>
      <div class="compose-placeholder">What's on your mind?</div>
      <button class="compose-btn">Post</button>
    </div>

    <!-- Trending -->
    <?php
    $sqlTrending = "
      SELECT t.thread_id, t.title, t.image_path,
             u.first_name, u.last_name,
             COALESCE(SUM(CASE WHEN v.vote='up' THEN 1 WHEN v.vote='down' THEN -1 ELSE 0 END), 0) AS total_votes
      FROM threads t
      JOIN users u ON t.user_id = u.userid
      LEFT JOIN thread_vote v ON t.thread_id = v.thread_id
      WHERE DATE(t.created_at) = CURDATE()
      GROUP BY t.thread_id
      HAVING total_votes > 0
      ORDER BY total_votes DESC
      LIMIT 4
    ";
    $resultTrending = mysqli_query($conn, $sqlTrending);
    if ($resultTrending && mysqli_num_rows($resultTrending) > 0):
    ?>
    <div class="section-heading"><h2>Trending Today</h2></div>
    <div class="trending-strip">
      <?php while ($row = mysqli_fetch_assoc($resultTrending)):
        $tImg = !empty($row['image_path']) ? htmlspecialchars($row['image_path']) : null;
        $tTitle = htmlspecialchars($row['title']);
        $tAuthor = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
        $tVotes = (int)$row['total_votes'];
      ?>
      <div class="trending-card" onclick="location.href='thread.php?id=<?= $row['thread_id'] ?>'">
        <?php if ($tImg): ?>
        <img src="<?= $tImg ?>" alt="Thread">
        <?php else: ?>
        <div style="height:90px;background:var(--bg-muted);border-radius:7px;margin-bottom:10px;display:flex;align-items:center;justify-content:center;color:var(--text-3);font-size:20px;">
          <i class="fas fa-image"></i>
        </div>
        <?php endif; ?>
        <div class="trend-badge"><i class="fas fa-fire" style="font-size:9px"></i> Hot</div>
        <div class="tc-meta"><?= $tAuthor ?></div>
        <div class="tc-title"><?= $tTitle ?></div>
        <div class="tc-votes"><i class="fas fa-arrow-up"></i> <?= $tVotes ?> votes</div>
      </div>
      <?php endwhile; ?>
    </div>
    <?php endif; ?>

    <!-- Threads -->
    <div class="section-heading">
      <h2>All Threads</h2>
      <div class="sort-bar">
        <button class="sort-btn active" data-sort="latest">Latest</button>
        <button class="sort-btn" data-sort="popular">Popular</button>
      </div>
    </div>

    <div id="threadsContainer">
    <?php
    $sql = "SELECT t.thread_id, t.title, t.content, t.image_path, t.created_at, t.user_id,
                   u.first_name, u.last_name,
                   COALESCE(SUM(CASE WHEN v.vote='up' THEN 1 WHEN v.vote='down' THEN -1 ELSE 0 END),0) AS total_votes,
                   (SELECT COUNT(*) FROM comments c WHERE c.thread_id = t.thread_id) AS total_comments
            FROM threads t
            JOIN users u ON t.user_id = u.userid
            LEFT JOIN thread_vote v ON t.thread_id = v.thread_id
            GROUP BY t.thread_id
            ORDER BY t.created_at DESC";

    $result = mysqli_query($conn, $sql);
    if ($result && mysqli_num_rows($result) > 0):
      while ($row = mysqli_fetch_assoc($result)):
        $author  = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
        $avatar  = strtoupper(substr($author, 0, 2));
        $title   = htmlspecialchars($row['title']);
        $content = nl2br(htmlspecialchars($row['content']));
        $time    = htmlspecialchars($row['created_at']);
        $votes   = (int)$row['total_votes'];
        $comments = (int)$row['total_comments'];
    ?>
    <div class="thread-card"
         data-id="<?= $row['thread_id'] ?>"
         data-time="<?= $time ?>"
         data-votes="<?= $votes ?>">

      <div class="thread-header">
        <div class="thread-ava"><?= $avatar ?></div>
        <div class="thread-meta-wrap">
          <div class="thread-author"><?= $author ?></div>
          <div class="thread-time"><?= $time ?></div>
        </div>
        <div class="thread-menu-wrap">
          <button class="menu-trigger"><i class="fas fa-ellipsis"></i></button>
          <div class="dropdown">
            <button class="report-btn" data-id="<?= $row['thread_id'] ?>" data-user-id="<?= $row['user_id'] ?>" data-username="<?= $author ?>">
              <i class="fas fa-flag"></i> Report
            </button>
            <button class="share-btn" data-id="<?= $row['thread_id'] ?>">
              <i class="fas fa-share-nodes"></i> Share
            </button>
            <?php if ($isAdmin || (int)$_SESSION['userid'] === (int)$row['user_id']): ?>
            <button class="delete-btn danger" data-id="<?= $row['thread_id'] ?>">
              <i class="fas fa-trash"></i> Delete
            </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <h3 class="thread-title"><?= $title ?></h3>
      <div class="thread-body"><?= $content ?></div>

      <?php if (!empty($row['image_path'])): ?>
      <img src="<?= htmlspecialchars($row['image_path']) ?>" class="thread-img" alt="Thread image">
      <?php endif; ?>

      <div class="thread-footer">
        <div class="vote-group">
          <button class="vote-btn upvote"><i class="fas fa-arrow-up"></i></button>
          <div class="vote-sep"></div>
          <span class="vote-count"><?= $votes ?></span>
          <div class="vote-sep"></div>
          <button class="vote-btn downvote"><i class="fas fa-arrow-down"></i></button>
        </div>
        <button class="action-pill comment-btn"><i class="far fa-comment"></i> Comment</button>
        <button class="action-pill"><i class="fas fa-share-nodes"></i> Share</button>
        <button class="action-pill"><i class="far fa-bookmark"></i> Save</button>
        <?php if ($comments > 0): ?>
        <span class="comment-count"><?= $comments ?> comment<?= $comments > 1 ? 's' : '' ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endwhile; else: ?>
    <div class="empty-state">
      <i class="fas fa-pen-to-square" style="font-size:28px;color:var(--text-3)"></i>
      <p>No threads yet â€” be the first to post!</p>
    </div>
    <?php endif; ?>
    </div>

  </main>
</div><!-- /.layout -->

<!-- â”€â”€ COMPOSE MODAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="overlay" id="composeOverlay">
  <div class="modal">
    <div class="modal-header">
      <h3>New Thread</h3>
      <button class="close-btn" id="closeCompose">&times;</button>
    </div>
    <form action="create_thread.php" method="POST" enctype="multipart/form-data">
      <div class="field-wrap">
        <label class="field-label">Title</label>
        <input type="text" name="title" class="field-input" placeholder="Give your thread a titleâ€¦" required>
      </div>
      <div class="field-wrap">
        <label class="field-label">Content</label>
        <textarea name="content" class="field-input" placeholder="Share your thoughtsâ€¦" required></textarea>
      </div>
      <div class="field-wrap">
        <label class="field-label">Image <span style="color:var(--text-3);font-weight:400">(optional)</span></label>
        <label class="file-label">
          <i class="fas fa-image"></i> Choose imageâ€¦
          <input type="file" name="image" accept="image/*">
        </label>
        <div class="file-note">JPG, PNG or GIF Â· max 5 MB</div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" id="cancelCompose">Cancel</button>
        <button type="submit" class="btn-solid">Post Thread</button>
      </div>
    </form>
  </div>
</div>

<!-- â”€â”€ REPORT MODAL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="overlay" id="reportOverlay">
  <div class="modal">
    <div class="modal-header">
      <h3>Submit Report</h3>
      <button class="close-btn" id="closeReport">&times;</button>
    </div>
    <form id="reportForm">
      <input type="hidden" id="reportedUserId" name="reported_user_id">
      <input type="hidden" id="locationType" name="location_type">
      <input type="hidden" id="locationId" name="location_id">
      <input type="hidden" id="reportedUserName" name="reported_user_name">

      <div class="field-wrap">
        <label class="field-label">Category</label>
        <select id="reportCategory" name="report_type" class="field-input" required>
          <option value="">Select category</option>
          <option value="inappropriate">Inappropriate Content</option>
          <option value="spam">Spam</option>
          <option value="harassment">Harassment</option>
          <option value="other">Other</option>
        </select>
      </div>
      <div class="field-wrap">
        <label class="field-label">Priority</label>
        <select id="reportPriority" name="priority" class="field-input" required>
          <option value="low">Low</option>
          <option value="medium" selected>Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>
      <div class="field-wrap">
        <label class="field-label">Description</label>
        <textarea id="reportDescription" name="description" class="field-input"
          placeholder="Describe the issueâ€¦" required></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" id="cancelReport">Cancel</button>
        <button type="submit" class="btn-solid">Submit</button>
      </div>
    </form>
  </div>
</div>

<script src="../java/script.js" defer></script>
<script>
  // â”€â”€ DARK MODE â”€â”€
  const root = document.documentElement;
  const themeBtn = document.getElementById('themeBtn');
  const saved = localStorage.getItem('tipeed-theme') || 'light';
  root.setAttribute('data-theme', saved);
  updateThemeIcon(saved);

  themeBtn.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('tipeed-theme', next);
    updateThemeIcon(next);
  });
  function updateThemeIcon(t) {
    themeBtn.innerHTML = t === 'dark'
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';
  }

  // â”€â”€ COMPOSE MODAL â”€â”€
  const composeOverlay = document.getElementById('composeOverlay');
  document.getElementById('openCompose').addEventListener('click', () => composeOverlay.classList.add('open'));
  document.getElementById('closeCompose').addEventListener('click', () => composeOverlay.classList.remove('open'));
  document.getElementById('cancelCompose').addEventListener('click', () => composeOverlay.classList.remove('open'));
  composeOverlay.addEventListener('click', e => { if (e.target === composeOverlay) composeOverlay.classList.remove('open'); });

  // â”€â”€ REPORT MODAL â”€â”€
  const reportOverlay = document.getElementById('reportOverlay');
  document.getElementById('closeReport').addEventListener('click', () => reportOverlay.classList.remove('open'));
  document.getElementById('cancelReport').addEventListener('click', () => reportOverlay.classList.remove('open'));
  reportOverlay.addEventListener('click', e => { if (e.target === reportOverlay) reportOverlay.classList.remove('open'); });

  document.querySelectorAll('.report-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('reportedUserId').value = btn.dataset.userId;
      document.getElementById('locationId').value = btn.dataset.id;
      document.getElementById('locationType').value = 'thread';
      document.getElementById('reportedUserName').value = btn.dataset.username;
      reportOverlay.classList.add('open');
    });
  });

  // â”€â”€ DROPDOWN MENUS â”€â”€
  document.querySelectorAll('.menu-trigger').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      const dd = btn.nextElementSibling;
      document.querySelectorAll('.dropdown.open').forEach(d => { if (d !== dd) d.classList.remove('open'); });
      dd.classList.toggle('open');
    });
  });
  document.addEventListener('click', () => document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open')));

  // â”€â”€ SORT â”€â”€
  document.querySelectorAll('.sort-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.sort;
      const container = document.getElementById('threadsContainer');
      const cards = [...container.querySelectorAll('.thread-card')];
      cards.sort((a, b) => {
        if (key === 'popular') return +b.dataset.votes - +a.dataset.votes;
        return new Date(b.dataset.time) - new Date(a.dataset.time);
      });
      cards.forEach(c => container.appendChild(c));
    });
  });
</script>
</body>
</html>