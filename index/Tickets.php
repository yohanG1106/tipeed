<?php
include "db_connect.php";
session_start();
include 'db_connect.php';

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

$firstName     = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : "User";
$lastName      = isset($_SESSION['last_name'])  ? $_SESSION['last_name']  : "";
$role          = isset($_SESSION['role'])        ? $_SESSION['role']       : "student";
$yearLevel     = isset($_SESSION['year_level'])  ? $_SESSION['year_level'] : null;
$currentUserId = $_SESSION['userid'];
$isAdmin       = ($role === 'admin');
$studentName   = trim($firstName . " " . $lastName);

function ordinal($n) {
    $e = ['th','st','nd','rd','th','th','th','th','th','th'];
    return (($n % 100) >= 11 && ($n % 100) <= 13) ? $n.'th' : $n.$e[$n % 10];
}
if ($role === 'student') {
    $studentIDT = ($yearLevel && is_numeric($yearLevel)) ? ordinal($yearLevel)." Year" : "No year assigned";
} else {
    $studentIDT = ucfirst(htmlspecialchars($role));
}

$currentUserRole = $role;
if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} elseif ($currentUserRole === 'teacher') {
    $homePage = 'teacher_home.php';
} else {
    $homePage = 'student_home.php';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Support Tickets â€” TiPeed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

  <style>
    /* â”€â”€ TOKENS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
      --teal:      #0f766e;
      --teal-bg:   #f0fdfa;
      --radius:    10px;
      --radius-lg: 16px;
      --sidebar-w: 240px;
      --panel-w:   300px;
      --nav-h:     56px;
      --font:      'DM Sans', sans-serif;
      --mono:      'DM Mono', monospace;
      --shadow:    0 1px 3px rgba(0,0,0,.06);
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
      --teal-bg:   #0d1f1e;
      --shadow-md: 0 4px 16px rgba(0,0,0,.4);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 15px; -webkit-font-smoothing: antialiased; }
    body {
      font-family: var(--font);
      background: var(--bg); color: var(--text);
      height: 100vh; overflow: hidden;
      transition: background .25s, color .25s;
    }
    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }
    input, select, textarea { font-family: var(--font); }

    /* â”€â”€ NAVBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
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
    .nav-links a:hover  { background: var(--bg-muted); color: var(--text); }
    .nav-links a.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .nav-right { display: flex; align-items: center; gap: 10px; margin-left: auto; }
    .search-wrap { display: flex; align-items: center; gap: 8px; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 0 12px; height: 34px; }
    .search-wrap i { color: var(--text-3); font-size: 13px; }
    .search-wrap input { background: none; border: none; outline: none; font-size: 13px; color: var(--text); width: 180px; }
    .search-wrap input::placeholder { color: var(--text-3); }
    .theme-btn { width: 34px; height: 34px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-2); font-size: 14px; display: flex; align-items: center; justify-content: center; transition: background .15s; }
    .theme-btn:hover { background: var(--bg-muted); }

    /* â”€â”€ FULL-HEIGHT LAYOUT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .layout {
      display: flex;
      height: calc(100vh - var(--nav-h));
      margin-top: var(--nav-h);
    }

    /* â”€â”€ LEFT NAV SIDEBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .nav-sidebar {
      width: var(--sidebar-w); flex-shrink: 0;
      border-right: 1px solid var(--border);
      background: var(--bg-card);
      display: flex; flex-direction: column; gap: 6px;
      padding: 20px 12px; overflow-y: auto;
    }
    .sidebar-profile { display: flex; align-items: center; gap: 10px; padding: 10px 10px 14px; border-bottom: 1px solid var(--border); margin-bottom: 6px; }
    .avatar-circle { width: 36px; height: 36px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border-2); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: var(--text-2); flex-shrink: 0; font-family: var(--mono); }
    .profile-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .profile-role { font-size: 11px; color: var(--text-3); font-family: var(--mono); }
    .menu-item { display: flex; align-items: center; gap: 9px; padding: 7px 10px; border-radius: 8px; font-size: 13.5px; color: var(--text-2); transition: background .15s, color .15s; }
    .menu-item:hover  { background: var(--bg-muted); color: var(--text); }
    .menu-item.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .menu-item i { width: 16px; text-align: center; font-size: 13px; color: var(--text-3); flex-shrink: 0; }
    .menu-divider { height: 1px; background: var(--border); margin: 8px 4px; }

    /* â”€â”€ TICKETS PANEL â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .tickets-panel {
      width: var(--panel-w); flex-shrink: 0;
      border-right: 1px solid var(--border);
      background: var(--bg-card);
      display: flex; flex-direction: column;
    }
    .tp-header { padding: 14px 16px; border-bottom: 1px solid var(--border); flex-shrink: 0; }
    .tp-title { font-size: 14px; font-weight: 500; margin-bottom: 10px; }
    .tp-filters { display: flex; gap: 6px; }
    .tp-select {
      flex: 1; background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 8px; padding: 6px 10px; font-size: 12px;
      color: var(--text); outline: none; appearance: none;
      font-family: var(--mono); cursor: pointer;
    }
    .tp-select:focus { border-color: var(--border-2); }

    .tp-search { padding: 8px 12px 0; flex-shrink: 0; }
    .tp-search-inner { position: relative; }
    .tp-search-inner i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-3); font-size: 12px; }
    .tp-search input { width: 100%; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 7px 10px 7px 28px; font-size: 13px; color: var(--text); outline: none; }
    .tp-search input::placeholder { color: var(--text-3); }
    .tp-search input:focus { border-color: var(--border-2); }

    .tp-list { flex: 1; overflow-y: auto; padding: 8px; }

    /* Ticket item */
    .ticket-item {
      padding: 12px 13px; border-radius: 9px;
      background: var(--bg-muted); border: 1px solid transparent;
      margin-bottom: 6px; cursor: pointer;
      transition: border-color .15s, background .15s;
      border-left: 3px solid var(--border-2);
    }
    .ticket-item:hover  { background: var(--bg-card); border-color: var(--border-2); }
    .ticket-item.active { background: var(--bg-card); border-color: var(--blue); border-left-color: var(--blue); }
    .ticket-item.pri-urgent { border-left-color: var(--red); }
    .ticket-item.pri-high   { border-left-color: var(--amber); }
    .ticket-item.pri-medium { border-left-color: var(--blue); }
    .ticket-item.pri-low    { border-left-color: var(--green); }

    .ti-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 8px; margin-bottom: 4px; }
    .ti-subject { font-size: 13px; font-weight: 500; line-height: 1.4; flex: 1; min-width: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ti-meta { display: flex; gap: 6px; align-items: center; margin-bottom: 4px; flex-wrap: wrap; }
    .ti-preview { font-size: 12px; color: var(--text-3); display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden; }
    .ti-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 6px; }
    .ti-date  { font-size: 10px; color: var(--text-3); font-family: var(--mono); }
    .ti-count { font-size: 10px; color: var(--text-3); font-family: var(--mono); }

    /* chips */
    .chip { display: inline-block; font-size: 10px; font-family: var(--mono); font-weight: 500; padding: 2px 7px; border-radius: 4px; white-space: nowrap; }
    .chip-open     { background: var(--green-bg);  color: var(--green);  }
    .chip-pending  { background: var(--amber-bg);  color: var(--amber);  }
    .chip-resolved { background: var(--teal-bg);   color: var(--teal);   }
    .chip-closed   { background: var(--red-bg);    color: var(--red);    }
    .chip-low      { background: var(--green-bg);  color: var(--green);  }
    .chip-medium   { background: var(--blue-bg);   color: var(--blue);   }
    .chip-high     { background: var(--amber-bg);  color: var(--amber);  }
    .chip-urgent   { background: var(--red-bg);    color: var(--red);    }

    /* no tickets */
    .no-tickets { text-align: center; padding: 40px 16px; color: var(--text-3); }
    .no-tickets i { font-size: 32px; display: block; margin-bottom: 10px; }
    .no-tickets p { font-size: 13px; }

    /* â”€â”€ CHAT AREA â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ */
    .chat-area {
      flex: 1; min-width: 0;
      display: flex; flex-direction: column;
      background: var(--bg);
    }

    /* Chat header */
    .chat-header {
      padding: 13px 20px; background: var(--bg-card);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      flex-shrink: 0; gap: 12px;
    }
    .ch-user { display: flex; align-items: center; gap: 12px; }
    .ch-ava { width: 36px; height: 36px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border-2); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 500; font-family: var(--mono); color: var(--text-2); flex-shrink: 0; }
    .ch-name { font-size: 14px; font-weight: 500; }
    .ch-subject { font-size: 12px; color: var(--text-3); font-family: var(--mono); margin-top: 2px; display: flex; align-items: center; gap: 6px; }
    .ch-actions { display: flex; gap: 6px; flex-shrink: 0; }

    .btn { display: inline-flex; align-items: center; gap: 5px; padding: 6px 13px; border-radius: 7px; font-size: 12px; font-weight: 500; border: none; transition: opacity .15s; white-space: nowrap; }
    .btn i { font-size: 11px; }
    .btn:hover { opacity: .85; }
    .btn-resolve { background: var(--green-bg); color: var(--green); }
    .btn-close2  { background: var(--bg-muted);  color: var(--text-2); border: 1px solid var(--border); }
    .btn-delete  { background: var(--red-bg);   color: var(--red); }

    /* Messages */
    .messages-area {
      flex: 1; overflow-y: auto; padding: 20px;
      display: flex; flex-direction: column; gap: 10px;
    }
    @keyframes fadeUp { from { opacity:0; transform:translateY(5px); } to { opacity:1; transform:translateY(0); } }

    .message { display: flex; max-width: 66%; animation: fadeUp .2s ease both; }
    .message.received { align-self: flex-start; }
    .message.sent     { align-self: flex-end;   flex-direction: row-reverse; }

    .msg-ava { width: 30px; height: 30px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 500; font-family: var(--mono); color: var(--text-2); flex-shrink: 0; margin-top: 2px; }
    .message.received .msg-ava { margin-right: 8px; }
    .message.sent     .msg-ava { margin-left:  8px; }

    .msg-bubble { padding: 10px 14px; border-radius: 14px; max-width: 100%; word-break: break-word; }
    .message.received .msg-bubble { background: var(--bg-card); border: 1px solid var(--border); border-bottom-left-radius: 4px; }
    .message.sent     .msg-bubble { background: var(--accent); color: var(--accent-fg); border-bottom-right-radius: 4px; }
    .msg-text { font-size: 14px; line-height: 1.55; }
    .msg-time { font-size: 10px; color: var(--text-3); font-family: var(--mono); margin-top: 5px; }
    .message.sent .msg-time { color: rgba(255,255,255,.45); text-align: right; }

    /* Empty chat */
    .chat-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; color: var(--text-3); gap: 12px; text-align: center; padding: 40px; }
    .chat-empty i { font-size: 44px; }
    .chat-empty h3 { font-size: 16px; font-weight: 500; color: var(--text-2); }
    .chat-empty p  { font-size: 13px; max-width: 280px; line-height: 1.6; }

    /* Input bar */
    .input-bar { padding: 12px 18px; background: var(--bg-card); border-top: 1px solid var(--border); display: flex; align-items: flex-end; gap: 8px; flex-shrink: 0; }
    .input-wrap { flex: 1; }
    .msg-input { width: 100%; background: var(--bg-input); border: 1px solid var(--border); border-radius: 10px; padding: 10px 14px; font-size: 14px; color: var(--text); outline: none; resize: none; max-height: 120px; transition: border-color .15s; line-height: 1.5; }
    .msg-input:focus { border-color: var(--border-2); }
    .msg-input::placeholder { color: var(--text-3); }
    .send-btn { width: 38px; height: 38px; flex-shrink: 0; background: var(--accent); color: var(--accent-fg); border: none; border-radius: 9px; font-size: 14px; display: flex; align-items: center; justify-content: center; transition: opacity .15s; }
    .send-btn:hover { opacity: .85; }

    /* Toast */
    #toastContainer { position: fixed; top: 20px; right: 20px; z-index: 9999; display: flex; flex-direction: column; gap: 8px; pointer-events: none; }
    .toast { pointer-events: all; background: var(--bg-card); border: 1px solid var(--border-2); border-radius: 10px; padding: 11px 14px; font-size: 13px; box-shadow: var(--shadow-md); display: flex; align-items: center; gap: 9px; max-width: 300px; transform: translateX(360px); opacity: 0; transition: transform .3s cubic-bezier(.16,1,.3,1), opacity .3s; }
    .toast.show { transform: translateX(0); opacity: 1; }
    .toast-icon-ok  { color: var(--green); font-size: 14px; }
    .toast-icon-err { color: var(--red);   font-size: 14px; }

    /* Scrollbar */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    @media (max-width: 1100px) { .nav-sidebar { display: none; } }
    @media (max-width: 720px)  { .tickets-panel { display: none; } .nav-links, .search-wrap { display: none; } }
  </style>
</head>
<body>

<!-- â”€â”€ NAVBAR â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
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
      <input type="text" id="navSearch" placeholder="Search ticketsâ€¦">
    </div>
    <button class="theme-btn" id="themeBtn"><i class="fas fa-moon"></i></button>
  </div>
</nav>

<!-- â”€â”€ LAYOUT â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€ -->
<div class="layout">

  <!-- Left nav sidebar -->
  <aside class="nav-sidebar">
    <div class="sidebar-profile">
      <div class="avatar-circle"><?= strtoupper(substr($studentName, 0, 2)) ?></div>
      <div>
        <div class="profile-name"><?= htmlspecialchars($studentName) ?></div>
        <div class="profile-role"><?= htmlspecialchars($studentIDT) ?></div>
      </div>
    </div>
    <a href="profile.php"        class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>"   class="menu-item"><i class="fas fa-house"></i> Home</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="CourseChat.php"     class="menu-item"><i class="fas fa-comments"></i> Communities</a>
    <a href="Community.php"      class="menu-item"><i class="fas fa-users"></i> Community</a>
    <?php if ($currentUserRole === 'admin'): ?>
    <a href="admin_reg.php"      class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
    <div class="menu-divider"></div>
    <a href="calendar.php"  class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <a href="settings.php"  class="menu-item"><i class="fas fa-gear"></i> Settings</a>
    <a href="Help.php"      class="menu-item active"><i class="fas fa-circle-question"></i> Help</a>
    <div class="menu-divider"></div>
    <a href="logout.php" class="menu-item" style="color:var(--red)">
      <i class="fas fa-arrow-right-from-bracket" style="color:var(--red)"></i> Log out
    </a>
  </aside>

  <!-- Tickets panel -->
  <div class="tickets-panel">
    <div class="tp-header">
      <div class="tp-title">Support Tickets</div>
      <div class="tp-filters">
        <select class="tp-select" id="statusFilter">
          <option value="all">All Status</option>
          <option value="open">Open</option>
          <option value="pending">Pending</option>
          <option value="resolved">Resolved</option>
          <option value="closed">Closed</option>
        </select>
        <select class="tp-select" id="priorityFilter">
          <option value="all">All Priority</option>
          <option value="low">Low</option>
          <option value="medium">Medium</option>
          <option value="high">High</option>
          <option value="urgent">Urgent</option>
        </select>
      </div>
    </div>
    <div class="tp-search">
      <div class="tp-search-inner">
        <i class="fas fa-search"></i>
        <input type="text" id="ticketSearch" placeholder="Search ticketsâ€¦">
      </div>
    </div>
    <div class="tp-list" id="ticketsList"></div>
  </div>

  <!-- Chat area -->
  <div class="chat-area">
    <div id="chatContainer" style="display:flex;flex-direction:column;height:100%">
      <div class="chat-empty">
        <i class="fas fa-inbox"></i>
        <h3>No ticket selected</h3>
        <p>Pick a ticket from the list to view the conversation and reply.</p>
      </div>
    </div>
  </div>

</div><!-- /.layout -->

<div id="toastContainer"></div>

<script>
  // â”€â”€ DARK MODE â”€â”€
  const root     = document.documentElement;
  const themeBtn = document.getElementById('themeBtn');
  const saved    = localStorage.getItem('tipeed-theme') || 'light';
  root.setAttribute('data-theme', saved);
  updateIcon(saved);
  themeBtn.addEventListener('click', () => {
    const n = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', n);
    localStorage.setItem('tipeed-theme', n);
    updateIcon(n);
  });
  function updateIcon(t) { themeBtn.innerHTML = t === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>'; }

  // â”€â”€ STATE â”€â”€
  let allTickets     = [];
  let selectedTicket = null;
  const currentUserID   = '<?= $currentUserId ?>';
  const currentUserRole = '<?= $role ?>';

  // â”€â”€ FETCH â”€â”€
  function fetchTickets(refreshChat = false) {
    fetch('get_tickets.php')
      .then(r => r.json())
      .then(data => {
        allTickets = data.map(t => ({ ...t, messages: t.messages || [] }));
        renderList();
        if (refreshChat && selectedTicket) {
          const upd = allTickets.find(t => t.id === selectedTicket.id);
          if (upd) { selectedTicket = upd; renderChat(); }
        }
      })
      .catch(e => console.error('Fetch tickets error:', e));
  }

  // â”€â”€ RENDER LIST â”€â”€
  function renderList() {
    const sv = document.getElementById('statusFilter').value;
    const pv = document.getElementById('priorityFilter').value;
    const sq = (document.getElementById('ticketSearch').value || document.getElementById('navSearch').value || '').toLowerCase();

    let list = allTickets;
    if (sv !== 'all') list = list.filter(t => t.status === sv);
    if (pv !== 'all') list = list.filter(t => t.priority === pv);
    if (sq) list = list.filter(t =>
      (t.subject||'').toLowerCase().includes(sq) ||
      (t.userName||'').toLowerCase().includes(sq) ||
      (t.description||'').toLowerCase().includes(sq)
    );

    const el = document.getElementById('ticketsList');
    if (!list.length) {
      el.innerHTML = `<div class="no-tickets"><i class="fas fa-ticket"></i><p>No tickets match your filters.</p></div>`;
      return;
    }
    el.innerHTML = '';
    list.forEach(t => {
      const div = document.createElement('div');
      const isActive = selectedTicket && selectedTicket.id === t.id;
      div.className = `ticket-item pri-${t.priority} ${isActive ? 'active' : ''}`;
      const preview = t.messages.length > 0 ? t.messages[t.messages.length-1].message : (t.description||'');
      div.innerHTML = `
        <div class="ti-top">
          <div class="ti-subject">${t.subject || 'Untitled'}</div>
          <span class="chip chip-${t.status}">${t.status}</span>
        </div>
        <div class="ti-meta">
          <span style="font-size:11px;color:var(--text-3)">${t.userName||'User'}</span>
          <span class="chip chip-${t.priority}">${t.priority}</span>
        </div>
        <div class="ti-preview">${preview}</div>
        <div class="ti-footer">
          <span class="ti-date">${formatDate(t.date)}</span>
          <span class="ti-count">${t.messages.length} msg${t.messages.length!==1?'s':''}</span>
        </div>`;
      div.addEventListener('click', () => { selectedTicket = t; renderList(); renderChat(); });
      el.appendChild(div);
    });
  }

  // â”€â”€ RENDER CHAT â”€â”€
  function renderChat() {
    if (!selectedTicket) return;
    const t = selectedTicket;
    const initials = (t.userName||'U').charAt(0).toUpperCase();
    const isAdmin  = currentUserRole === 'admin';

    const actionsHtml = isAdmin ? `
      <div class="ch-actions">
        <button class="btn btn-resolve" onclick="updateStatus('resolved')"><i class="fas fa-check"></i> Resolve</button>
        <button class="btn btn-close2"  onclick="updateStatus('closed')"><i class="fas fa-times"></i> Close</button>
        <button class="btn btn-delete"  onclick="deleteTicket()"><i class="fas fa-trash"></i> Delete</button>
      </div>` : '';

    document.getElementById('chatContainer').innerHTML = `
      <div class="chat-header">
        <div class="ch-user">
          <div class="ch-ava">${initials}</div>
          <div>
            <div class="ch-name">${t.userName||'User'}</div>
            <div class="ch-subject">
              ${t.subject||''}
              <span class="chip chip-${t.status}">${t.status}</span>
              <span class="chip chip-${t.priority}">${t.priority}</span>
            </div>
          </div>
        </div>
        ${actionsHtml}
      </div>
      <div class="messages-area" id="messagesArea">
        ${t.messages.length === 0 ? `
          <div class="chat-empty">
            <i class="fas fa-comments"></i>
            <h3>No messages yet</h3>
            <p>Send the first reply below.</p>
          </div>` :
          t.messages.map(m => {
            const isOwn = m.sender_id == currentUserID;
            const ava   = (m.sender_name||'U').charAt(0).toUpperCase();
            return `
              <div class="message ${isOwn?'sent':'received'}">
                <div class="msg-ava">${ava}</div>
                <div class="msg-bubble">
                  <div class="msg-text">${m.message}</div>
                  <div class="msg-time">${formatTime(m.timestamp)}</div>
                </div>
              </div>`;
          }).join('')
        }
      </div>
      <div class="input-bar">
        <div class="input-wrap">
          <textarea class="msg-input" id="chatInput" rows="1"
                    placeholder="Reply to ${t.userName||'the user'}â€¦"></textarea>
        </div>
        <button class="send-btn" id="sendBtn"><i class="fas fa-paper-plane"></i></button>
      </div>`;

    // scroll to bottom
    const ma = document.getElementById('messagesArea');
    if (ma) ma.scrollTop = ma.scrollHeight;

    // auto-resize
    const ci = document.getElementById('chatInput');
    if (ci) {
      ci.addEventListener('input', function() { this.style.height='auto'; this.style.height=this.scrollHeight+'px'; });
      ci.addEventListener('keydown', e => { if (e.key==='Enter'&&!e.shiftKey) { e.preventDefault(); sendMsg(); } });
    }
    document.getElementById('sendBtn')?.addEventListener('click', sendMsg);
  }

  // â”€â”€ SEND MESSAGE â”€â”€
  function sendMsg() {
    const ci  = document.getElementById('chatInput');
    const msg = ci?.value.trim();
    if (!msg || !selectedTicket) return;

    fetch('send_ticket_message.php', {
      method: 'POST',
      body: JSON.stringify({ ticket_id: selectedTicket.id, message: msg }),
      headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') {
        selectedTicket.messages.push(data.message);
        renderChat();
        showToast('Message sent', 'ok');
      } else { showToast(data.message || 'Failed to send', 'err'); }
    })
    .catch(() => showToast('Error sending message', 'err'));
  }

  // â”€â”€ UPDATE STATUS â”€â”€
  function updateStatus(status) {
    if (!selectedTicket) return;
    fetch('update_ticket_status.php', {
      method: 'POST',
      body: JSON.stringify({ ticket_id: selectedTicket.id, status }),
      headers: { 'Content-Type': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) { fetchTickets(true); showToast(`Ticket ${status}`, 'ok'); }
      else showToast('Failed to update status', 'err');
    });
  }

  // â”€â”€ DELETE TICKET â”€â”€
  function deleteTicket() {
    if (!selectedTicket) return;
    if (!confirm(`Delete ticket "${selectedTicket.subject}"? This cannot be undone.`)) return;
    allTickets = allTickets.filter(t => t.id !== selectedTicket.id);
    selectedTicket = null;
    renderList();
    document.getElementById('chatContainer').innerHTML = `
      <div class="chat-empty">
        <i class="fas fa-inbox"></i>
        <h3>No ticket selected</h3>
        <p>Pick a ticket from the list to view the conversation and reply.</p>
      </div>`;
    showToast('Ticket deleted', 'ok');
  }

  // â”€â”€ TOAST â”€â”€
  function showToast(msg, type = 'ok') {
    const c   = document.getElementById('toastContainer');
    const ico = type === 'ok' ? 'fa-circle-check toast-icon-ok' : 'fa-circle-xmark toast-icon-err';
    const el  = document.createElement('div');
    el.className = 'toast';
    el.innerHTML = `<i class="fas ${ico}"></i><span>${msg}</span>`;
    c.appendChild(el);
    setTimeout(() => el.classList.add('show'), 60);
    setTimeout(() => { el.classList.remove('show'); setTimeout(() => el.remove(), 320); }, 4000);
  }

  // â”€â”€ HELPERS â”€â”€
  function formatDate(ds) {
    if (!ds) return '';
    const d = new Date(ds);
    return d.toLocaleDateString([], { month: 'short', day: 'numeric' }) + ' ' +
           d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }
  function formatTime(ts) {
    if (!ts) return '';
    return new Date(ts).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  // â”€â”€ FILTER LISTENERS â”€â”€
  ['statusFilter','priorityFilter','ticketSearch'].forEach(id =>
    document.getElementById(id)?.addEventListener('input', renderList)
  );
  document.getElementById('navSearch')?.addEventListener('input', renderList);

  // â”€â”€ INIT â”€â”€
  document.addEventListener('DOMContentLoaded', () => {
    fetchTickets();
    setInterval(() => {
      const ci = document.getElementById('chatInput');
      if (!ci || !ci.value.length) fetchTickets(true);
    }, 5000);
  });
</script>
</body>
</html>