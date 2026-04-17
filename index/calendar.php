<?php
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$homePage = $currentUserRole === 'admin' ? 'admin_home.php' : ($currentUserRole === 'faculty' ? 'faculty_home.php' : 'student_home.php');

$firstName   = $_SESSION['first_name'] ?? 'User';
$lastName    = $_SESSION['last_name']  ?? '';
$studentName = trim($firstName . ' ' . $lastName);
$yearLevel   = isset($_SESSION['year_level']) ? $_SESSION['year_level'] : null;
$role        = $currentUserRole;

function ordinal($n) {
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($n % 100) >= 11 && ($n % 100) <= 13) return $n . 'th';
    return $n . $ends[$n % 10];
}

if ($role === 'student')     { $studentIDT = ($yearLevel && is_numeric($yearLevel)) ? ordinal($yearLevel) . ' Year' : 'No year assigned'; }
elseif ($role === 'faculty') { $studentIDT = 'Faculty'; }
elseif ($role === 'admin')   { $studentIDT = 'Administrator'; }
else                         { $studentIDT = ucfirst(htmlspecialchars($role)); }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Calendar — TiPeed Forum</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* ── TOKENS ── */
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
    body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; transition: background .25s, color .25s; }
    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }
    input, textarea, select { font-family: var(--font); }

    /* ── NAVBAR ── */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      height: var(--nav-h); background: var(--bg-card);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; padding: 0 24px; gap: 20px;
    }
    .nav-logo { font-size: 17px; font-weight: 500; letter-spacing: -.3px; flex-shrink: 0; }
    .nav-logo span { color: var(--text-3); font-weight: 300; }
    .nav-links { display: flex; align-items: center; gap: 4px; flex: 1; padding-left: 8px; }
    .nav-links a { font-size: 14px; color: var(--text-2); padding: 5px 11px; border-radius: 7px; transition: background .15s, color .15s; }
    .nav-links a:hover { background: var(--bg-muted); color: var(--text); }
    .nav-links a.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .nav-right { display: flex; align-items: center; gap: 10px; margin-left: auto; }
    .search-wrap { display: flex; align-items: center; gap: 8px; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 0 12px; height: 34px; }
    .search-wrap i { color: var(--text-3); font-size: 13px; }
    .search-wrap input { background: none; border: none; outline: none; font-size: 13px; color: var(--text); width: 180px; }
    .search-wrap input::placeholder { color: var(--text-3); }
    .theme-btn { width: 34px; height: 34px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-2); font-size: 14px; display: flex; align-items: center; justify-content: center; transition: background .15s, color .15s; }
    .theme-btn:hover { background: var(--bg-muted); color: var(--text); }

    /* ── LAYOUT ── */
    .layout { display: flex; padding-top: var(--nav-h); min-height: 100vh; }

    /* ── SIDEBAR ── */
    .sidebar {
      width: var(--sidebar-w); flex-shrink: 0;
      position: sticky; top: var(--nav-h);
      height: calc(100vh - var(--nav-h));
      overflow-y: auto; padding: 20px 12px;
      border-right: 1px solid var(--border);
      background: var(--bg-card);
      display: flex; flex-direction: column; gap: 6px;
    }
    .sidebar-profile { display: flex; align-items: center; gap: 10px; padding: 10px 10px 14px; border-bottom: 1px solid var(--border); margin-bottom: 6px; }
    .avatar-circle { width: 36px; height: 36px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border-2); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: var(--text-2); flex-shrink: 0; font-family: var(--mono); }
    .profile-info { min-width: 0; }
    .profile-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .profile-role { font-size: 11px; color: var(--text-3); font-family: var(--mono); }
    .menu-item { display: flex; align-items: center; gap: 9px; padding: 7px 10px; border-radius: 8px; font-size: 13.5px; color: var(--text-2); transition: background .15s, color .15s; }
    .menu-item:hover { background: var(--bg-muted); color: var(--text); }
    .menu-item.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .menu-item i { width: 16px; text-align: center; font-size: 13px; color: var(--text-3); flex-shrink: 0; }
    .menu-item:hover i, .menu-item.active i { color: var(--text-2); }
    .menu-divider { height: 1px; background: var(--border); margin: 8px 4px; }

    /* ── MAIN ── */
    .main {
      flex: 1; min-width: 0;
      padding: 28px 24px 60px;
      display: flex; flex-direction: column;
    }

    /* ── PAGE HEADER ── */
    .page-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 24px; gap: 16px; flex-wrap: wrap;
    }
    .page-header-left { display: flex; align-items: baseline; gap: 12px; }
    .page-title { font-size: 22px; font-weight: 500; letter-spacing: -.4px; }
    .month-label { font-size: 15px; color: var(--text-3); font-family: var(--mono); }

    .cal-nav { display: flex; align-items: center; gap: 6px; }
    .nav-btn {
      width: 32px; height: 32px;
      border: 1px solid var(--border); border-radius: 8px;
      background: var(--bg-input); color: var(--text-2); font-size: 12px;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s, color .15s, border-color .15s;
    }
    .nav-btn:hover { background: var(--bg-muted); color: var(--text); border-color: var(--border-2); }
    .nav-sep { width: 1px; height: 18px; background: var(--border); margin: 0 2px; }
    .today-btn {
      height: 32px; padding: 0 14px;
      border: 1px solid var(--border-2); border-radius: 8px;
      background: var(--accent); color: var(--accent-fg);
      font-size: 13px; font-weight: 500;
      transition: opacity .15s;
    }
    .today-btn:hover { opacity: .85; }

    /* ── CALENDAR GRID ── */
    .cal-body {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 16px;
      flex: 1;
      align-items: start;
    }

    .cal-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }

    /* day-of-week header */
    .cal-dow-row {
      display: grid; grid-template-columns: repeat(7, 1fr);
      border-bottom: 1px solid var(--border);
    }
    .cal-dow {
      padding: 10px 0; text-align: center;
      font-size: 11px; font-weight: 500; color: var(--text-3);
      letter-spacing: .06em; text-transform: uppercase; font-family: var(--mono);
    }

    /* days grid */
    .cal-grid {
      display: grid; grid-template-columns: repeat(7, 1fr);
      grid-template-rows: repeat(6, minmax(88px, 1fr));
    }
    .cal-day {
      border-right: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      padding: 8px 8px 6px;
      cursor: pointer;
      transition: background .12s;
      position: relative;
      overflow: hidden;
      min-height: 88px;
    }
    .cal-day:nth-child(7n) { border-right: none; }
    .cal-grid .cal-day:nth-last-child(-n+7) { border-bottom: none; }
    .cal-day:hover { background: var(--bg-muted); }
    .cal-day.other-month .cal-date { color: var(--text-3); }
    .cal-day.other-month { background: var(--bg-muted); opacity: .55; }
    .cal-day.is-today { background: var(--bg-input); }
    .cal-day.is-today .cal-date-wrap .cal-date {
      background: var(--accent); color: var(--accent-fg);
      width: 24px; height: 24px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px;
    }
    .cal-day.is-selected { background: var(--bg-muted); }
    .cal-day.is-selected .cal-date-wrap .cal-date {
      background: var(--text-2); color: var(--bg-card);
      width: 24px; height: 24px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px;
    }

    .cal-date-wrap { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 4px; }
    .cal-date { font-size: 13px; font-weight: 500; font-family: var(--mono); line-height: 24px; padding: 0 2px; }
    .cal-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--amber); margin-top: 9px; flex-shrink: 0; }

    .cal-events { display: flex; flex-direction: column; gap: 2px; }
    .cal-event-pill {
      font-size: 10.5px; padding: 2px 6px; border-radius: 4px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
      cursor: pointer; transition: opacity .12s;
      font-family: var(--mono);
    }
    .cal-event-pill:hover { opacity: .8; }
    .cal-event-pill.general  { background: var(--bg-muted); color: var(--text-2); }
    .cal-event-pill.important { background: var(--amber-bg); color: var(--amber); }
    .cal-event-pill.urgent   { background: var(--red-bg); color: var(--red); }

    /* ── RIGHT PANEL ── */
    .panel-stack { display: flex; flex-direction: column; gap: 12px; }

    .panel {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px 18px 14px;
    }
    .panel-title {
      font-size: 11px; font-weight: 500; color: var(--text-3);
      letter-spacing: .07em; text-transform: uppercase; font-family: var(--mono);
      margin-bottom: 14px;
    }

    /* Selected date panel */
    .sel-date-big { font-size: 26px; font-weight: 500; letter-spacing: -.5px; margin-bottom: 2px; }
    .sel-day-sub { font-size: 13px; color: var(--text-3); font-family: var(--mono); margin-bottom: 12px; }
    .event-badge {
      display: inline-flex; align-items: center; gap: 5px;
      background: var(--bg-muted); border-radius: 6px;
      padding: 4px 10px; font-size: 12px; color: var(--text-2); font-family: var(--mono);
    }

    /* Add event form */
    .field-wrap { margin-bottom: 12px; }
    .field-label { font-size: 11px; font-weight: 500; color: var(--text-3); text-transform: uppercase; letter-spacing: .07em; font-family: var(--mono); margin-bottom: 5px; display: block; }
    .field-input { width: 100%; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; font-size: 13.5px; color: var(--text); outline: none; transition: border-color .15s; }
    .field-input:focus { border-color: var(--border-2); }
    textarea.field-input { resize: vertical; min-height: 60px; }
    select.field-input { appearance: none; cursor: pointer; }
    .btn-solid { width: 100%; background: var(--accent); color: var(--accent-fg); border: none; border-radius: 8px; padding: 8px 16px; font-size: 13px; font-weight: 500; transition: opacity .15s; display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 4px; }
    .btn-solid:hover { opacity: .85; }
    .btn-solid:disabled { opacity: .5; cursor: not-allowed; }

    /* Spinner */
    .spinner { width: 14px; height: 14px; border: 2px solid rgba(255,255,255,.3); border-top-color: #fff; border-radius: 50%; animation: spin 0.8s linear infinite; display: none; }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* Upcoming events */
    .upcoming-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 9px 0; border-bottom: 1px solid var(--border);
      cursor: pointer; transition: color .12s;
    }
    .upcoming-item:last-child { border-bottom: none; padding-bottom: 0; }
    .upcoming-item:hover .up-title { color: var(--text); }
    .up-badge {
      width: 38px; height: 38px; flex-shrink: 0;
      background: var(--bg-muted); border: 1px solid var(--border);
      border-radius: 8px; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
    }
    .up-day  { font-size: 14px; font-weight: 500; font-family: var(--mono); line-height: 1; }
    .up-mon  { font-size: 9px; color: var(--text-3); font-family: var(--mono); text-transform: uppercase; margin-top: 1px; }
    .up-body { flex: 1; min-width: 0; }
    .up-title { font-size: 13px; font-weight: 500; color: var(--text-2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .12s; }
    .up-desc  { font-size: 11px; color: var(--text-3); margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    .empty-msg { text-align: center; padding: 20px 0; color: var(--text-3); font-size: 13px; }
    .empty-msg i { font-size: 20px; display: block; margin-bottom: 6px; }

    /* ── TOAST ── */
    .toast {
      position: fixed; bottom: 24px; right: 24px; z-index: 300;
      display: flex; align-items: center; gap: 9px;
      background: var(--bg-card); border: 1px solid var(--border-2);
      border-radius: 10px; box-shadow: var(--shadow-md);
      padding: 11px 16px; font-size: 13.5px;
      transform: translateY(80px); opacity: 0;
      transition: transform .25s, opacity .25s;
      pointer-events: none;
    }
    .toast.show { transform: translateY(0); opacity: 1; }
    .toast i { color: var(--green); font-size: 14px; }

    /* ── OVERLAY / MODAL ── */
    .overlay { display: none; position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,.35); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
    .overlay.open { display: flex; }
    .modal { background: var(--bg-card); border: 1px solid var(--border-2); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); padding: 24px; width: 100%; max-width: 420px; margin: 16px; }
    .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 18px; }
    .modal-header h3 { font-size: 16px; font-weight: 500; }
    .close-btn { width: 28px; height: 28px; border: none; background: var(--bg-muted); border-radius: 7px; color: var(--text-2); font-size: 16px; display: flex; align-items: center; justify-content: center; transition: background .15s; }
    .close-btn:hover { background: var(--border); }
    .detail-row { display: flex; align-items: flex-start; gap: 10px; padding: 8px 0; border-bottom: 1px solid var(--border); font-size: 13.5px; }
    .detail-row:last-of-type { border-bottom: none; }
    .detail-key { font-size: 11px; color: var(--text-3); font-family: var(--mono); text-transform: uppercase; width: 72px; flex-shrink: 0; padding-top: 1px; }
    .detail-val { color: var(--text-2); flex: 1; line-height: 1.5; }
    .modal-footer { display: flex; gap: 8px; justify-content: flex-end; margin-top: 18px; }
    .btn-ghost { background: none; border: 1px solid var(--border); border-radius: 8px; padding: 6px 14px; font-size: 13px; color: var(--text-2); transition: background .15s; }
    .btn-ghost:hover { background: var(--bg-muted); }
    .btn-danger { background: none; border: 1px solid var(--border); border-radius: 8px; padding: 6px 14px; font-size: 13px; color: var(--red); transition: background .15s; }
    .btn-danger:hover { background: var(--red-bg); }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 1000px) {
      .cal-body { grid-template-columns: 1fr; }
    }
    @media (max-width: 720px) {
      .sidebar { display: none; }
      .main { padding: 16px 12px 60px; }
      .cal-day { min-height: 60px; padding: 5px; }
      .cal-event-pill { display: none; }
    }
    @media (max-width: 460px) {
      .nav-links { display: none; }
      .search-wrap { display: none; }
    }
  </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar">
  <div class="nav-logo">Ti<span>Peed</span></div>
  <div class="nav-links">
    <a href="<?= $homePage ?>">Home</a>
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
      <input type="text" placeholder="Search threads…">
    </div>
    <button class="theme-btn" id="themeBtn" title="Toggle dark mode">
      <i class="fas fa-moon"></i>
    </button>
  </div>
</nav>

<!-- ── LAYOUT ── -->
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-profile">
      <div class="avatar-circle"><?= strtoupper(substr($studentName,0,2)) ?></div>
      <div class="profile-info">
        <div class="profile-name"><?= htmlspecialchars($studentName) ?></div>
        <div class="profile-role"><?= htmlspecialchars($studentIDT) ?></div>
      </div>
    </div>
    <a href="profile.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>" class="menu-item"><i class="fas fa-house"></i> Home</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="CourseChat.php" class="menu-item"><i class="fas fa-comments"></i> Communities</a>
    <a href="Community.php" class="menu-item"><i class="fas fa-users"></i> Community</a>
    <?php if ($currentUserRole === 'admin'): ?>
    <a href="admin_reg.php" class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
    <div class="menu-divider"></div>
    <a href="calendar.php" class="menu-item active"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <a href="#" class="menu-item"><i class="fas fa-gear"></i> Settings</a>
    <a href="Help.php" class="menu-item"><i class="fas fa-circle-question"></i> Help</a>
    <div class="menu-divider"></div>
    <a href="logout.php" class="menu-item" style="color:var(--red)">
      <i class="fas fa-arrow-right-from-bracket" style="color:var(--red)"></i> Log out
    </a>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- Page header -->
    <div class="page-header">
      <div class="page-header-left">
        <h1 class="page-title">Calendar</h1>
        <span class="month-label" id="monthLabel"></span>
      </div>
      <div class="cal-nav">
        <button class="nav-btn" id="prevYear" title="Previous year"><i class="fas fa-angles-left"></i></button>
        <button class="nav-btn" id="prevMonth" title="Previous month"><i class="fas fa-angle-left"></i></button>
        <button class="nav-btn" id="nextMonth" title="Next month"><i class="fas fa-angle-right"></i></button>
        <button class="nav-btn" id="nextYear" title="Next year"><i class="fas fa-angles-right"></i></button>
        <div class="nav-sep"></div>
        <button class="today-btn" id="todayBtn">Today</button>
      </div>
    </div>

    <!-- Body -->
    <div class="cal-body">

      <!-- Calendar grid -->
      <div class="cal-card">
        <div class="cal-dow-row">
          <div class="cal-dow">Sun</div>
          <div class="cal-dow">Mon</div>
          <div class="cal-dow">Tue</div>
          <div class="cal-dow">Wed</div>
          <div class="cal-dow">Thu</div>
          <div class="cal-dow">Fri</div>
          <div class="cal-dow">Sat</div>
        </div>
        <div class="cal-grid" id="calGrid"></div>
      </div>

      <!-- Right panel -->
      <div class="panel-stack">

        <!-- Selected date -->
        <div class="panel" id="selPanel">
          <div class="panel-title">Selected date</div>
          <div class="sel-date-big" id="selDateBig">—</div>
          <div class="sel-day-sub" id="selDaySub">—</div>
          <span class="event-badge" id="selBadge"><i class="fas fa-calendar-day" style="font-size:11px"></i> 0 events</span>
        </div>

        <!-- Add event -->
        <div class="panel">
          <div class="panel-title">Add event</div>
          <form id="eventForm">
            <div class="field-wrap">
              <label class="field-label">Title</label>
              <input type="text" id="eventTitle" class="field-input" placeholder="Event name…" required>
            </div>
            <div class="field-wrap">
              <label class="field-label">Description</label>
              <textarea id="eventDesc" class="field-input" placeholder="Optional note…"></textarea>
            </div>
            <div class="field-wrap">
              <label class="field-label">Type</label>
              <select id="eventType" class="field-input">
                <option value="general">General</option>
                <option value="important">Important</option>
                <option value="urgent">Urgent</option>
              </select>
            </div>
            <div class="field-wrap">
              <label class="field-label">Date</label>
              <input type="date" id="eventDate" class="field-input" required>
            </div>
            <button type="submit" class="btn-solid" id="submitBtn">
              <span id="submitText">Add event</span>
              <div class="spinner" id="spinner"></div>
            </button>
          </form>
        </div>

        <!-- Upcoming events -->
        <div class="panel">
          <div class="panel-title">Upcoming events</div>
          <div id="upcomingList"></div>
        </div>

      </div>
    </div>
  </main>
</div>

<!-- ── EVENT DETAIL MODAL ── -->
<div class="overlay" id="detailOverlay">
  <div class="modal">
    <div class="modal-header">
      <h3 id="detailTitle">Event</h3>
      <button class="close-btn" id="closeDetail">&times;</button>
    </div>
    <div id="detailBody"></div>
    <div class="modal-footer">
      <button class="btn-ghost" id="closeDetail2">Close</button>
      <button class="btn-danger" id="deleteBtn">Delete</button>
    </div>
  </div>
</div>

<!-- ── TOAST ── -->
<div class="toast" id="toast"><i class="fas fa-circle-check"></i> <span id="toastMsg">Done</span></div>

<script>
  /* ── THEME ── */
  const root = document.documentElement;
  const themeBtn = document.getElementById('themeBtn');
  const saved = localStorage.getItem('tipeed-theme') || 'light';
  root.setAttribute('data-theme', saved);
  updateIcon(saved);
  themeBtn.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('tipeed-theme', next);
    updateIcon(next);
  });
  function updateIcon(t) {
    themeBtn.innerHTML = t === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
  }

  /* ── STATE ── */
  let currentDate = new Date();
  let selectedDate = new Date();
  let events = [];
  let activeEventId = null;

  const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  const SHORT_MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

  /* ── HELPERS ── */
  function fmtDate(d) {
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
  }
  function dayEvents(d) { return events.filter(e => e.date === fmtDate(d)); }

  function toast(msg) {
    const el = document.getElementById('toast');
    document.getElementById('toastMsg').textContent = msg;
    el.classList.add('show');
    setTimeout(() => el.classList.remove('show'), 2800);
  }

  /* ── FETCH ── */
  async function fetchEvents() {
    try {
      const r = await fetch('get_events.php');
      const data = await r.json();
      events = data.map(e => ({
        id: String(e.event_id),
        title: e.title,
        description: e.description || '',
        type: e.event_type || 'general',
        date: e.date.split(' ')[0],
        author: e.author || ''
      }));
    } catch(err) { events = []; }
  }

  /* ── RENDER CALENDAR ── */
  function renderCalendar() {
    const y = currentDate.getFullYear();
    const m = currentDate.getMonth();
    document.getElementById('monthLabel').textContent = `${MONTHS[m]} ${y}`;

    const grid = document.getElementById('calGrid');
    grid.innerHTML = '';

    const firstDay = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m+1, 0).getDate();
    const prevDays = new Date(y, m, 0).getDate();
    const today = new Date();
    const total = 42;

    for (let i = 0; i < total; i++) {
      let day, date, isOther = false;
      if (i < firstDay) {
        day = prevDays - firstDay + 1 + i;
        date = new Date(y, m-1, day);
        isOther = true;
      } else if (i >= firstDay + daysInMonth) {
        day = i - firstDay - daysInMonth + 1;
        date = new Date(y, m+1, day);
        isOther = true;
      } else {
        day = i - firstDay + 1;
        date = new Date(y, m, day);
      }

      const isToday    = fmtDate(date) === fmtDate(today);
      const isSel      = fmtDate(date) === fmtDate(selectedDate);
      const dayEvts    = dayEvents(date);

      const cell = document.createElement('div');
      cell.className = 'cal-day' +
        (isOther ? ' other-month' : '') +
        (isToday ? ' is-today' : '') +
        (isSel   ? ' is-selected' : '');

      cell.innerHTML = `
        <div class="cal-date-wrap">
          <span class="cal-date">${day}</span>
          ${dayEvts.length && !isOther ? '<span class="cal-dot"></span>' : ''}
        </div>
        <div class="cal-events">
          ${dayEvts.slice(0,2).map(e =>
            `<div class="cal-event-pill ${e.type}" data-eid="${e.id}">${esc(e.title)}</div>`
          ).join('')}
          ${dayEvts.length > 2 ? `<div class="cal-event-pill general">+${dayEvts.length-2} more</div>` : ''}
        </div>
      `;

      cell.addEventListener('click', () => selectDate(date));
      cell.querySelectorAll('.cal-event-pill[data-eid]').forEach(pill => {
        pill.addEventListener('click', e => { e.stopPropagation(); openDetail(pill.dataset.eid); });
      });

      grid.appendChild(cell);
    }

    renderUpcoming();
    updateSelPanel();
  }

  /* ── SELECT DATE ── */
  function selectDate(d) {
    selectedDate = d;
    document.getElementById('eventDate').value = fmtDate(d);
    renderCalendar();
    updateSelPanel();
  }

  /* ── SELECTED PANEL ── */
  function updateSelPanel() {
    document.getElementById('selDateBig').textContent = selectedDate.toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' });
    document.getElementById('selDaySub').textContent  = selectedDate.toLocaleDateString('en-US', { weekday:'long' });
    const n = dayEvents(selectedDate).length;
    document.getElementById('selBadge').innerHTML = `<i class="fas fa-calendar-day" style="font-size:11px"></i> ${n} event${n!==1?'s':''}`;
  }

  /* ── UPCOMING ── */
  function renderUpcoming() {
    const list = document.getElementById('upcomingList');
    const today = new Date(); today.setHours(0,0,0,0);
    const upcoming = events
      .filter(e => new Date(e.date) >= today)
      .sort((a,b) => new Date(a.date) - new Date(b.date))
      .slice(0, 5);

    if (!upcoming.length) {
      list.innerHTML = '<div class="empty-msg"><i class="far fa-calendar"></i>No upcoming events</div>';
      return;
    }

    list.innerHTML = upcoming.map(e => {
      const d = new Date(e.date);
      return `
        <div class="upcoming-item" data-eid="${e.id}">
          <div class="up-badge">
            <span class="up-day">${d.getDate()}</span>
            <span class="up-mon">${SHORT_MONTHS[d.getMonth()]}</span>
          </div>
          <div class="up-body">
            <div class="up-title">${esc(e.title)}</div>
            <div class="up-desc">${esc(e.description) || 'No description'}</div>
          </div>
        </div>`;
    }).join('');

    list.querySelectorAll('.upcoming-item').forEach(el => {
      el.addEventListener('click', () => openDetail(el.dataset.eid));
    });
  }

  /* ── OPEN DETAIL ── */
  function openDetail(id) {
    const e = events.find(ev => ev.id === id);
    if (!e) return;
    activeEventId = id;
    document.getElementById('detailTitle').textContent = e.title;
    const d = new Date(e.date);
    document.getElementById('detailBody').innerHTML = `
      <div class="detail-row"><span class="detail-key">Date</span><span class="detail-val">${d.toLocaleDateString('en-US',{weekday:'long',year:'numeric',month:'long',day:'numeric'})}</span></div>
      <div class="detail-row"><span class="detail-key">Type</span><span class="detail-val">${e.type.charAt(0).toUpperCase()+e.type.slice(1)}</span></div>
      ${e.description ? `<div class="detail-row"><span class="detail-key">Note</span><span class="detail-val">${esc(e.description)}</span></div>` : ''}
      ${e.author ? `<div class="detail-row"><span class="detail-key">By</span><span class="detail-val">${esc(e.author)}</span></div>` : ''}
    `;
    document.getElementById('detailOverlay').classList.add('open');
  }

  function closeDetail() { document.getElementById('detailOverlay').classList.remove('open'); activeEventId = null; }
  document.getElementById('closeDetail').addEventListener('click', closeDetail);
  document.getElementById('closeDetail2').addEventListener('click', closeDetail);
  document.getElementById('detailOverlay').addEventListener('click', e => { if (e.target.id === 'detailOverlay') closeDetail(); });

  /* ── DELETE ── */
  document.getElementById('deleteBtn').addEventListener('click', async () => {
    if (!activeEventId || !confirm('Delete this event?')) return;
    try {
      const r = await fetch('delete_event.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({event_id: activeEventId})
      });
      const d = await r.json();
      if (d.success) { await fetchEvents(); renderCalendar(); closeDetail(); toast('Event deleted.'); }
      else alert(d.message || 'Failed to delete.');
    } catch(err) { alert('Error deleting event.'); }
  });

  /* ── ADD EVENT ── */
  document.getElementById('eventForm').addEventListener('submit', async e => {
    e.preventDefault();
    const title = document.getElementById('eventTitle').value.trim();
    const desc  = document.getElementById('eventDesc').value.trim();
    const type  = document.getElementById('eventType').value;
    const date  = document.getElementById('eventDate').value;
    if (!title) return;

    const btn = document.getElementById('submitBtn');
    const txt = document.getElementById('submitText');
    const sp  = document.getElementById('spinner');
    btn.disabled = true; txt.textContent = 'Adding…'; sp.style.display = 'block';

    try {
      const fd = new FormData();
      fd.append('title', title); fd.append('description', desc);
      fd.append('event_date', date); fd.append('event_type', type);

      const r = await fetch('save_event.php', {method:'POST', body: fd});
      const result = await r.json();

      if (result.success) {
        await fetchEvents(); renderCalendar();
        e.target.reset();
        document.getElementById('eventDate').value = fmtDate(selectedDate);
        toast('Event added!');
      } else { alert(result.message || 'Failed to save.'); }
    } catch(err) { alert('Error saving event.'); }
    finally { btn.disabled = false; txt.textContent = 'Add event'; sp.style.display = 'none'; }
  });

  /* ── NAV ── */
  document.getElementById('prevYear').addEventListener('click',  () => { currentDate.setFullYear(currentDate.getFullYear()-1); renderCalendar(); });
  document.getElementById('prevMonth').addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth()-1); renderCalendar(); });
  document.getElementById('nextMonth').addEventListener('click', () => { currentDate.setMonth(currentDate.getMonth()+1); renderCalendar(); });
  document.getElementById('nextYear').addEventListener('click',  () => { currentDate.setFullYear(currentDate.getFullYear()+1); renderCalendar(); });
  document.getElementById('todayBtn').addEventListener('click',  () => { currentDate = new Date(); selectedDate = new Date(); document.getElementById('eventDate').value = fmtDate(selectedDate); renderCalendar(); });

  /* ── ESC HELPER ── */
  function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── INIT ── */
  async function init() {
    document.getElementById('eventDate').value = fmtDate(selectedDate);
    await fetchEvents();
    renderCalendar();
  }

  document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>