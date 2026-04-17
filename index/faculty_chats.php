<?php
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'faculty' && $_SESSION['role'] !== 'admin')) {
    header("Location: auth.php");
    exit;
}

$userId          = $_SESSION['userid'];
$currentUserRole = $_SESSION['role'];

if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} elseif ($currentUserRole === 'faculty') {
    $homePage = 'teacher_home.php';
} else {
    $homePage = 'student_home.php';
}

// ── Fetch chats ────────────────────────────────────────────────
if ($currentUserRole === 'admin') {
    $sql  = "SELECT cc.*, c.course_code, c.course_name,
                COUNT(DISTINCT cm.user_id) AS member_count, cc.invite_code
             FROM course_chats cc
             JOIN courses c ON cc.course_id = c.course_id
             LEFT JOIN course_chat_members cm ON cc.chat_id = cm.chat_id
             GROUP BY cc.chat_id ORDER BY cc.created_at DESC";
    $stmt = $conn->prepare($sql);
} else {
    $sql  = "SELECT cc.*, c.course_code, c.course_name,
                COUNT(DISTINCT cm.user_id) AS member_count, cc.invite_code
             FROM course_chats cc
             JOIN courses c ON cc.course_id = c.course_id
             LEFT JOIN course_chat_members cm ON cc.chat_id = cm.chat_id
             WHERE cc.faculty_id = ?
             GROUP BY cc.chat_id ORDER BY cc.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
}
$stmt->execute();
$chats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Invite code actions ────────────────────────────────────────
if (isset($_GET['generate_invite'], $_GET['chat_id'])) {
    $chatId     = $_GET['chat_id'];
    $inviteCode = substr(strtoupper(bin2hex(random_bytes(3))), 0, 6);
    $u = $conn->prepare("UPDATE course_chats SET invite_code=? WHERE chat_id=?");
    $u->bind_param("si", $inviteCode, $chatId);
    header("Location: faculty_chats.php?" . ($u->execute()
        ? "success=invite_generated&code=$inviteCode"
        : "error=invite_failed"));
    exit;
}
if (isset($_GET['delete_invite'], $_GET['chat_id'])) {
    $chatId = $_GET['chat_id'];
    $u = $conn->prepare("UPDATE course_chats SET invite_code=NULL WHERE chat_id=?");
    $u->bind_param("i", $chatId);
    header("Location: faculty_chats.php?" . ($u->execute()
        ? "success=invite_deleted"
        : "error=delete_failed"));
    exit;
}

// ── Profile helpers ────────────────────────────────────────────
$studentName = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$yearLevel   = isset($_SESSION['year_level']) ? $_SESSION['year_level'] : null;
$role        = $currentUserRole;

function ordinal($n) {
    $e = ['th','st','nd','rd','th','th','th','th','th','th'];
    return (($n % 100) >= 11 && ($n % 100) <= 13) ? $n.'th' : $n.$e[$n % 10];
}
if ($role === 'student') {
    $studentIDT = ($yearLevel && is_numeric($yearLevel)) ? ordinal($yearLevel)." Year" : "No year assigned";
} elseif ($role === 'faculty') { $studentIDT = "Faculty"; }
elseif ($role === 'admin')   { $studentIDT = "Administrator"; }
else                          { $studentIDT = ucfirst(htmlspecialchars($role)); }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Course Chats — TiPeed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">

  <style>
    /* ── TOKENS ─────────────────────────────────── */
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
      --teal-bg:   #0d1f1e;
      --shadow-md: 0 4px 16px rgba(0,0,0,.4);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 15px; -webkit-font-smoothing: antialiased; }
    body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; transition: background .25s, color .25s; }
    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }
    input, select, textarea { font-family: var(--font); }

    /* ── NAVBAR ──────────────────────────────────── */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      height: var(--nav-h);
      background: var(--bg-card);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center;
      padding: 0 24px; gap: 20px;
    }
    .nav-logo { font-size: 17px; font-weight: 500; letter-spacing: -.3px; flex-shrink: 0; }
    .nav-logo span { color: var(--text-3); font-weight: 300; }
    .nav-links { display: flex; align-items: center; gap: 4px; flex: 1; padding-left: 8px; }
    .nav-links a { font-size: 14px; color: var(--text-2); padding: 5px 11px; border-radius: 7px; transition: background .15s, color .15s; }
    .nav-links a:hover  { background: var(--bg-muted); color: var(--text); }
    .nav-links a.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .nav-right { display: flex; align-items: center; gap: 10px; margin-left: auto; }
    .search-wrap {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 8px; padding: 0 12px; height: 34px;
    }
    .search-wrap i { color: var(--text-3); font-size: 13px; }
    .search-wrap input { background: none; border: none; outline: none; font-size: 13px; color: var(--text); width: 180px; }
    .search-wrap input::placeholder { color: var(--text-3); }
    .theme-btn {
      width: 34px; height: 34px; border: 1px solid var(--border);
      border-radius: 8px; background: var(--bg-input);
      color: var(--text-2); font-size: 14px;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s, color .15s;
    }
    .theme-btn:hover { background: var(--bg-muted); color: var(--text); }

    /* ── LAYOUT ──────────────────────────────────── */
    .layout { display: flex; padding-top: var(--nav-h); min-height: 100vh; }

    /* ── SIDEBAR ─────────────────────────────────── */
    .sidebar {
      width: var(--sidebar-w); flex-shrink: 0;
      position: sticky; top: var(--nav-h);
      height: calc(100vh - var(--nav-h)); overflow-y: auto;
      padding: 20px 12px; border-right: 1px solid var(--border);
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
      background: var(--bg-muted); border: 1px solid var(--border-2);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 500; color: var(--text-2);
      flex-shrink: 0; font-family: var(--mono);
    }
    .profile-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .profile-role { font-size: 11px; color: var(--text-3); font-family: var(--mono); }
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

    /* ── MAIN ────────────────────────────────────── */
    .main { flex: 1; min-width: 0; padding: 28px 32px 60px; overflow-y: auto; }

    /* ── PAGE HEADER ─────────────────────────────── */
    .page-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      margin-bottom: 24px; padding-bottom: 20px;
      border-bottom: 1px solid var(--border); gap: 16px;
    }
    .page-title { font-size: 20px; font-weight: 500; letter-spacing: -.3px; }
    .page-sub   { font-size: 13px; color: var(--text-3); margin-top: 2px; font-family: var(--mono); }

    /* ── BUTTONS ─────────────────────────────────── */
    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 15px; border-radius: 8px;
      font-size: 13px; font-weight: 500; border: none;
      transition: opacity .15s, background .15s;
      white-space: nowrap;
    }
    .btn:hover { opacity: .88; }
    .btn i { font-size: 12px; }
    .btn-solid  { background: var(--accent);   color: var(--accent-fg); }
    .btn-blue   { background: var(--blue-bg);  color: var(--blue); }
    .btn-teal   { background: var(--teal-bg);  color: var(--teal); }
    .btn-muted  { background: var(--bg-muted); color: var(--text-2); }
    .btn-green  { background: var(--green-bg); color: var(--green); }
    .btn-red    { background: var(--red-bg);   color: var(--red); }
    .btn-amber  { background: var(--amber-bg); color: var(--amber); }

    /* ── ALERTS ──────────────────────────────────── */
    .alert {
      display: flex; align-items: center; gap: 10px;
      padding: 12px 16px; border-radius: 10px;
      font-size: 13px; margin-bottom: 20px;
      animation: fadeUp .25s ease both;
    }
    .alert-success { background: var(--green-bg); color: var(--green); }
    .alert-error   { background: var(--red-bg);   color: var(--red); }
    .alert i { font-size: 14px; flex-shrink: 0; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

    /* ── SEARCH / FILTER ROW ─────────────────────── */
    .filter-row {
      display: flex; gap: 8px; align-items: center; margin-bottom: 20px; flex-wrap: wrap;
    }
    .filter-input {
      flex: 1; min-width: 200px;
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 9px; padding: 8px 13px;
      font-size: 14px; color: var(--text); outline: none;
      transition: border-color .15s;
    }
    .filter-input:focus { border-color: var(--border-2); }
    .filter-input::placeholder { color: var(--text-3); }

    /* ── CHATS GRID ──────────────────────────────── */
    .chats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
      gap: 12px;
      margin-bottom: 36px;
    }

    /* ── CHAT CARD ───────────────────────────────── */
    .chat-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 18px;
      display: flex; flex-direction: column; gap: 12px;
      transition: border-color .15s, box-shadow .15s;
      animation: fadeUp .2s ease both;
    }
    .chat-card:hover { border-color: var(--border-2); box-shadow: var(--shadow); }

    /* card top row */
    .card-top {
      display: flex; align-items: flex-start;
      justify-content: space-between; gap: 12px;
    }
    .card-avatar {
      width: 42px; height: 42px; border-radius: 10px;
      background: var(--bg-muted); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; font-weight: 500; color: var(--text-2);
      font-family: var(--mono); flex-shrink: 0;
    }
    .card-title-wrap { flex: 1; min-width: 0; }
    .card-name {
      font-size: 15px; font-weight: 500; letter-spacing: -.15px;
      margin-bottom: 3px;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }
    .card-course { font-size: 12px; color: var(--text-3); font-family: var(--mono); }
    .card-section { font-size: 12px; color: var(--text-3); margin-top: 1px; }
    .card-creator { font-size: 11px; color: var(--red); font-family: var(--mono); margin-top: 2px; }

    /* status chip */
    .chip {
      display: inline-block; font-size: 11px; font-family: var(--mono);
      padding: 2px 8px; border-radius: 5px; font-weight: 500; white-space: nowrap;
    }
    .chip-green  { background: var(--green-bg);  color: var(--green);  }
    .chip-muted  { background: var(--bg-muted);  color: var(--text-3); }
    .chip-amber  { background: var(--amber-bg);  color: var(--amber);  }
    .chip-blue   { background: var(--blue-bg);   color: var(--blue);   }
    .chip-red    { background: var(--red-bg);    color: var(--red);    }
    .chip-teal   { background: var(--teal-bg);   color: var(--teal);   }

    /* description */
    .card-desc {
      font-size: 13px; color: var(--text-2); line-height: 1.6;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }

    /* stats row */
    .card-stats {
      display: flex; gap: 16px;
      padding-top: 12px; border-top: 1px solid var(--border);
    }
    .stat {
      display: flex; align-items: center; gap: 5px;
      font-size: 12px; color: var(--text-3); font-family: var(--mono);
    }
    .stat i { font-size: 11px; }

    /* invite code inline display */
    .invite-inline {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--bg-muted); border-radius: 7px;
      padding: 5px 10px;
      font-family: var(--mono); font-size: 13px; color: var(--text-2);
      letter-spacing: .08em;
    }
    .invite-inline i { font-size: 11px; color: var(--text-3); }

    /* actions row */
    .card-actions { display: flex; gap: 6px; flex-wrap: wrap; }

    /* ── EMPTY STATE ─────────────────────────────── */
    .empty-state {
      text-align: center; padding: 60px 20px;
      border: 1px dashed var(--border-2);
      border-radius: var(--radius-lg);
      color: var(--text-3);
    }
    .empty-state i { font-size: 36px; margin-bottom: 14px; display: block; }
    .empty-state h2 { font-size: 17px; font-weight: 500; color: var(--text-2); margin-bottom: 6px; }
    .empty-state p  { font-size: 13px; margin-bottom: 20px; }

    /* ── INVITE CODES PANEL ──────────────────────── */
    .invite-panel {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 22px 24px;
      margin-top: 32px;
    }
    .invite-panel-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 16px; padding-bottom: 14px;
      border-bottom: 1px solid var(--border);
    }
    .invite-panel-title { font-size: 14px; font-weight: 500; }
    .invite-panel-sub   { font-size: 12px; color: var(--text-3); font-family: var(--mono); margin-top: 2px; }

    /* how-to callout */
    .howto {
      display: flex; gap: 12px;
      background: var(--blue-bg); border-radius: 10px;
      padding: 14px 16px; margin-bottom: 18px;
      font-size: 13px; color: var(--blue); line-height: 1.65;
    }
    .howto i { font-size: 14px; flex-shrink: 0; margin-top: 2px; }

    /* invite code grid */
    .invite-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; }

    .invite-card {
      background: var(--bg-muted);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 16px;
      display: flex; flex-direction: column; gap: 10px;
    }
    .invite-card-name { font-size: 13px; font-weight: 500; }
    .invite-code-display {
      font-family: var(--mono); font-size: 22px; font-weight: 500;
      letter-spacing: .18em; text-align: center;
      background: var(--bg-card); border: 1px dashed var(--border-2);
      border-radius: 8px; padding: 10px 0; color: var(--text);
    }
    .invite-card-actions { display: flex; gap: 6px; }

    /* ── TOAST NOTIFICATIONS ─────────────────────── */
    #notifContainer {
      position: fixed; top: 20px; right: 20px;
      z-index: 9999; display: flex; flex-direction: column; gap: 8px;
      pointer-events: none;
    }
    .notif {
      pointer-events: all;
      background: var(--bg-card); border: 1px solid var(--border-2);
      border-radius: 10px; padding: 12px 14px;
      font-size: 13px; color: var(--text);
      box-shadow: var(--shadow-md);
      display: flex; align-items: flex-start; gap: 10px;
      max-width: 320px;
      transform: translateX(360px); opacity: 0;
      transition: transform .3s cubic-bezier(.16,1,.3,1), opacity .3s;
    }
    .notif.show { transform: translateX(0); opacity: 1; }
    .notif-icon-ok   { color: var(--green); font-size: 14px; flex-shrink: 0; margin-top: 1px; }
    .notif-icon-err  { color: var(--red);   font-size: 14px; flex-shrink: 0; margin-top: 1px; }
    .notif-body { flex: 1; line-height: 1.5; }
    .notif-close {
      background: none; border: none; color: var(--text-3);
      font-size: 14px; cursor: pointer; padding: 0; line-height: 1;
    }

    /* ── SCROLLBAR ───────────────────────────────── */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* ── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 720px) {
      .sidebar { display: none; }
      .main    { padding: 16px 14px 60px; }
      .chats-grid { grid-template-columns: 1fr; }
      .nav-links, .search-wrap { display: none; }
    }
  </style>
</head>
<body>

<!-- ── NAVBAR ────────────────────────────────────────── -->
<nav class="navbar">
  <div class="nav-logo">Ti<span>Peed</span></div>
  <div class="nav-links">
    <a href="<?= $homePage ?>">Home</a>
    <?php if ($currentUserRole === 'admin' || $currentUserRole === 'faculty'): ?>
    <a href="student_home.php">Threads</a>
    <a href="faculty_chats.php" class="active">Faculty</a>
    <?php endif; ?>
    <a href="Community.php">Community</a>
    <a href="aboutus.php">About</a>
  </div>
  <div class="nav-right">
    <div class="search-wrap">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" placeholder="Search chats…">
    </div>
    <button class="theme-btn" id="themeBtn" title="Toggle dark mode">
      <i class="fas fa-moon"></i>
    </button>
  </div>
</nav>

<!-- ── LAYOUT ─────────────────────────────────────────── -->
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-profile">
      <div class="avatar-circle"><?= strtoupper(substr($studentName, 0, 2)) ?></div>
      <div>
        <div class="profile-name"><?= htmlspecialchars($studentName) ?></div>
        <div class="profile-role"><?= htmlspecialchars($studentIDT) ?></div>
      </div>
    </div>

    <a href="profile.php"      class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>" class="menu-item"><i class="fas fa-house"></i> Home</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="CourseChat.php"   class="menu-item"><i class="fas fa-comments"></i> Communities</a>
    <a href="Community.php"    class="menu-item"><i class="fas fa-users"></i> Community</a>
    <?php if ($currentUserRole === 'admin'): ?>
    <a href="admin_reg.php"    class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
    <div class="menu-divider"></div>
    <a href="calendar.php"     class="menu-item active"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <a href="#"                class="menu-item"><i class="fas fa-gear"></i> Settings</a>
    <a href="Help.php"         class="menu-item"><i class="fas fa-circle-question"></i> Help</a>
    <div class="menu-divider"></div>
    <a href="logout.php"       class="menu-item" style="color:var(--red)">
      <i class="fas fa-arrow-right-from-bracket" style="color:var(--red)"></i> Log out
    </a>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- Alerts -->
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
      <i class="fas fa-circle-check"></i>
      <?php
        if ($_GET['success'] === 'invite_generated')
          echo 'Invite code generated — <strong>' . htmlspecialchars($_GET['code'] ?? '') . '</strong>';
        elseif ($_GET['success'] === 'invite_deleted')
          echo 'Invite code deleted successfully.';
      ?>
    </div>
    <?php endif; ?>
    <?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
      <i class="fas fa-circle-exclamation"></i>
      <?php
        if ($_GET['error'] === 'invite_failed')  echo 'Failed to generate invite code. Please try again.';
        if ($_GET['error'] === 'delete_failed')  echo 'Failed to delete invite code. Please try again.';
      ?>
    </div>
    <?php endif; ?>

    <!-- Page header -->
    <div class="page-header">
      <div>
        <div class="page-title">
          <?= $currentUserRole === 'admin' ? 'All Course Chats' : 'My Course Chats' ?>
          <?php if ($currentUserRole === 'admin'): ?>
          <span class="chip chip-red" style="vertical-align:middle;margin-left:8px">Admin View</span>
          <?php endif; ?>
        </div>
        <div class="page-sub"><?= count($chats) ?> chat<?= count($chats) !== 1 ? 's' : '' ?></div>
      </div>
      <?php if ($currentUserRole === 'faculty'): ?>
      <a href="faculty_create_chat.php" class="btn btn-solid">
        <i class="fas fa-plus"></i> New Chat
      </a>
      <?php endif; ?>
    </div>

    <!-- Filter -->
    <div class="filter-row">
      <input class="filter-input" id="filterInput" placeholder="Filter by name, course or description…">
    </div>

    <?php if (empty($chats)): ?>
    <!-- Empty state -->
    <div class="empty-state">
      <i class="fas fa-comments"></i>
      <h2>No Course Chats Yet</h2>
      <p><?= $currentUserRole === 'admin'
            ? 'No course chats have been created yet.'
            : 'Create your first course chat to connect with students and co-admins.' ?></p>
      <?php if ($currentUserRole === 'faculty'): ?>
      <a href="faculty_create_chat.php" class="btn btn-solid">
        <i class="fas fa-plus"></i> Create Your First Chat
      </a>
      <?php endif; ?>
    </div>

    <?php else: ?>

    <!-- Chat cards grid -->
    <div class="chats-grid" id="chatsGrid">
      <?php foreach ($chats as $chat):
        $initials = strtoupper(substr($chat['group_name'], 0, 2));
        $isActive = (bool)$chat['is_coadmin_allowed'];
        $hasCode  = !empty($chat['invite_code']);
        $canManage = ($currentUserRole === 'admin' || $chat['faculty_id'] == $userId);
      ?>
      <div class="chat-card"
           data-name="<?= strtolower(htmlspecialchars($chat['group_name'])) ?>"
           data-course="<?= strtolower(htmlspecialchars($chat['course_code'].' '.$chat['course_name'])) ?>"
           data-desc="<?= strtolower(htmlspecialchars($chat['description'] ?? '')) ?>">

        <!-- Top row -->
        <div class="card-top">
          <div class="card-avatar"><?= $initials ?></div>
          <div class="card-title-wrap">
            <div class="card-name"><?= htmlspecialchars($chat['group_name']) ?></div>
            <div class="card-course">
              <?= htmlspecialchars($chat['course_code'] . ' · ' . $chat['course_name']) ?>
            </div>
            <?php if ($chat['class_section']): ?>
            <div class="card-section"><?= htmlspecialchars($chat['class_section']) ?></div>
            <?php endif; ?>
            <?php if ($currentUserRole === 'admin'):
              $fSql  = "SELECT first_name, last_name FROM users WHERE userid = ?";
              $fStmt = $conn->prepare($fSql);
              $fStmt->bind_param("i", $chat['faculty_id']);
              $fStmt->execute();
              $faculty = $fStmt->get_result()->fetch_assoc();
            ?>
            <div class="card-creator">
              <i class="fas fa-chalkboard-user"></i>
              <?= htmlspecialchars($faculty['first_name'] . ' ' . $faculty['last_name']) ?>
            </div>
            <?php endif; ?>
          </div>
          <span class="chip <?= $isActive ? 'chip-green' : 'chip-muted' ?>">
            <?= $isActive ? 'Active' : 'Inactive' ?>
          </span>
        </div>

        <!-- Description -->
        <?php if ($chat['description']): ?>
        <div class="card-desc"><?= htmlspecialchars($chat['description']) ?></div>
        <?php endif; ?>

        <!-- Stats -->
        <div class="card-stats">
          <div class="stat">
            <i class="fas fa-users"></i>
            <span><?= $chat['member_count'] ?> member<?= $chat['member_count'] != 1 ? 's' : '' ?></span>
          </div>
          <div class="stat">
            <i class="fas fa-calendar"></i>
            <span><?= date('M j, Y', strtotime($chat['created_at'])) ?></span>
          </div>
          <?php if ($hasCode && $canManage): ?>
          <div class="invite-inline">
            <i class="fas fa-key"></i><?= htmlspecialchars($chat['invite_code']) ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="card-actions">
          <a href="chat_interface.php?chat_id=<?= $chat['chat_id'] ?>" class="btn btn-blue">
            <i class="fas fa-comments"></i> Enter Chat
          </a>
          <?php if ($canManage): ?>
          <a href="edit_chat.php?chat_id=<?= $chat['chat_id'] ?>" class="btn btn-teal">
            <i class="fas fa-pen"></i> Edit
          </a>
          <a href="manage_members.php?chat_id=<?= $chat['chat_id'] ?>" class="btn btn-muted">
            <i class="fas fa-users-gear"></i> Members
          </a>
          <?php if (!$hasCode): ?>
          <a href="faculty_chats.php?generate_invite=1&chat_id=<?= $chat['chat_id'] ?>" class="btn btn-amber">
            <i class="fas fa-key"></i> Get Invite Code
          </a>
          <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <!-- Active Invite Codes Panel -->
    <?php
    $activeInvites = array_filter($chats, fn($c) =>
      !empty($c['invite_code']) && ($currentUserRole === 'admin' || $c['faculty_id'] == $userId)
    );
    ?>
    <div class="invite-panel">
      <div class="invite-panel-header">
        <div>
          <div class="invite-panel-title">Active Invite Codes</div>
          <div class="invite-panel-sub"><?= count($activeInvites) ?> active</div>
        </div>
      </div>

      <div class="howto">
        <i class="fas fa-circle-info"></i>
        <div>
          Generate a code for a chat, then share it with students.
          Students enter it in the chat interface under <strong>Join New Chat</strong> — they'll be added automatically.
        </div>
      </div>

      <?php if (empty($activeInvites)): ?>
      <div style="text-align:center;padding:32px 20px;color:var(--text-3)">
        <i class="fas fa-key" style="font-size:28px;display:block;margin-bottom:10px"></i>
        <p style="font-size:13px">No active invite codes. Generate one from a chat card above.</p>
      </div>
      <?php else: ?>
      <div class="invite-grid">
        <?php foreach ($activeInvites as $chat): ?>
        <div class="invite-card">
          <div class="invite-card-name"><?= htmlspecialchars($chat['group_name']) ?></div>
          <div class="invite-code-display"><?= htmlspecialchars($chat['invite_code']) ?></div>
          <div class="invite-card-actions">
            <button class="btn btn-green" onclick="copyCode('<?= htmlspecialchars($chat['invite_code']) ?>', this)">
              <i class="fas fa-copy"></i> Copy
            </button>
            <a href="faculty_chats.php?delete_invite=1&chat_id=<?= $chat['chat_id'] ?>"
               class="btn btn-red"
               onclick="return confirm('Delete this invite code?')">
              <i class="fas fa-trash"></i> Delete
            </a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <?php endif; ?>

  </main>
</div><!-- /.layout -->

<div id="notifContainer"></div>

<script>
  // ── DARK MODE ──
  const root     = document.documentElement;
  const themeBtn = document.getElementById('themeBtn');
  const saved    = localStorage.getItem('tipeed-theme') || 'light';
  root.setAttribute('data-theme', saved);
  updateIcon(saved);

  themeBtn.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('tipeed-theme', next);
    updateIcon(next);
  });
  function updateIcon(t) {
    themeBtn.innerHTML = t === 'dark'
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';
  }

  // ── FILTER ──
  document.getElementById('filterInput')?.addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.chat-card').forEach(card => {
      const match = card.dataset.name.includes(q)
                 || card.dataset.course.includes(q)
                 || card.dataset.desc.includes(q);
      card.style.display = match ? '' : 'none';
    });
  });

  // Navbar search mirrors filter
  document.getElementById('searchInput')?.addEventListener('input', function () {
    const fi = document.getElementById('filterInput');
    if (fi) { fi.value = this.value; fi.dispatchEvent(new Event('input')); }
  });

  // ── COPY CODE ──
  function copyCode(code, btn) {
    navigator.clipboard.writeText(code).then(() => {
      const orig = btn.innerHTML;
      btn.innerHTML = '<i class="fas fa-check"></i> Copied!';
      showNotif('Code <strong>' + code + '</strong> copied to clipboard.', 'ok');
      setTimeout(() => btn.innerHTML = orig, 2000);
    }).catch(() => {
      // Fallback
      const ta = document.createElement('textarea');
      ta.value = code; document.body.appendChild(ta); ta.select();
      document.execCommand('copy'); document.body.removeChild(ta);
      showNotif('Code copied!', 'ok');
    });
  }

  // ── TOAST ──
  function showNotif(msg, type = 'ok') {
    const icon = type === 'ok' ? 'fa-circle-check' : 'fa-circle-xmark';
    const cls  = type === 'ok' ? 'notif-icon-ok'   : 'notif-icon-err';
    const c    = document.getElementById('notifContainer');
    const el   = document.createElement('div');
    el.className = 'notif';
    el.innerHTML = `
      <i class="fas ${icon} ${cls}"></i>
      <div class="notif-body">${msg}</div>
      <button class="notif-close" onclick="dismissNotif(this.parentElement)">&times;</button>`;
    c.appendChild(el);
    setTimeout(() => el.classList.add('show'), 60);
    setTimeout(() => dismissNotif(el), 5000);
  }
  function dismissNotif(el) {
    el.classList.remove('show');
    setTimeout(() => el.remove(), 320);
  }

  // Auto-dismiss URL alerts after 4 s
  document.querySelector('.alert')?.addEventListener('animationend', () => {
    const a = document.querySelector('.alert');
    if (a) setTimeout(() => a.style.opacity = '0', 4000);
  });
</script>
</body>
</html>