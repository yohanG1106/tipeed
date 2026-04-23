<?php
require 'db_connect.php';
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['userid'];

$user_query = "SELECT * FROM users WHERE userid = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) { header('Location: login.php'); exit(); }

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

if ($role === 'student')      { $studentIDT = ($yearLevel && is_numeric($yearLevel)) ? ordinal($yearLevel) . ' Year' : 'No year assigned'; }
elseif ($role === 'faculty')  { $studentIDT = 'Faculty'; }
elseif ($role === 'admin')    { $studentIDT = 'Administrator'; }
else                          { $studentIDT = ucfirst(htmlspecialchars($role)); }

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $fn = $_POST['first_name']; $ln = $_POST['last_name'];
        $em = $_POST['email'];      $yl = $_POST['year_level'];
        $q  = $conn->prepare("UPDATE users SET first_name=?,last_name=?,email=?,year_level=? WHERE userid=?");
        $q->bind_param("sssii", $fn, $ln, $em, $yl, $user_id);
        if ($q->execute()) { $success = "Profile updated successfully!"; $user_stmt->execute(); $user = $user_stmt->get_result()->fetch_assoc(); }
        else { $error = "Error updating profile: " . $conn->error; }
    }
    if (isset($_POST['change_password'])) {
        $cp = $_POST['current_password']; $np = $_POST['new_password']; $pp = $_POST['confirm_password'];
        if (password_verify($cp, $user['password'])) {
            if ($np === $pp) {
                if (strlen($np) >= 6) {
                    $hp = password_hash($np, PASSWORD_DEFAULT);
                    $q  = $conn->prepare("UPDATE users SET password=?,is_temp_password=0 WHERE userid=?");
                    $q->bind_param("si", $hp, $user_id);
                    $success = $q->execute() ? "Password changed successfully!" : "Error: " . $conn->error;
                } else { $error = "Password must be at least 6 characters."; }
            } else { $error = "New passwords do not match."; }
        } else { $error = "Current password is incorrect."; }
    }
}

// Stats
$thread_count = $conn->prepare("SELECT COUNT(*) FROM threads WHERE user_id=?");
$thread_count->bind_param("i", $user_id); $thread_count->execute();
$thread_count = $thread_count->get_result()->fetch_row()[0];

$likes_count = $conn->prepare("SELECT COUNT(*) FROM thread_vote WHERE user_id=? AND vote='up'");
$likes_count->bind_param("i", $user_id); $likes_count->execute();
$likes_count = $likes_count->get_result()->fetch_row()[0];

$comm_count = $conn->prepare("SELECT COUNT(*) FROM community_members WHERE user_id=?");
$comm_count->bind_param("i", $user_id); $comm_count->execute();
$comm_count = $comm_count->get_result()->fetch_row()[0];

$liked_q = $conn->prepare("SELECT t.*,tv.created_at as liked_date FROM threads t INNER JOIN thread_vote tv ON t.thread_id=tv.thread_id WHERE tv.user_id=? AND tv.vote='up' ORDER BY tv.created_at DESC LIMIT 5");
$liked_q->bind_param("i", $user_id); $liked_q->execute();
$liked_threads = $liked_q->get_result()->fetch_all(MYSQLI_ASSOC);

$my_threads_q = $conn->prepare("SELECT * FROM threads WHERE user_id=? ORDER BY created_at DESC LIMIT 5");
$my_threads_q->bind_param("i", $user_id); $my_threads_q->execute();
$my_threads = $my_threads_q->get_result()->fetch_all(MYSQLI_ASSOC);

$avatar_initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));
$year_levels = ['','1st Year','2nd Year','3rd Year','4th Year','5th Year'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile â€” TiPeed Forum</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* â”€â”€ TOKENS â”€â”€ */
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

    /* â”€â”€ NAVBAR â”€â”€ */
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

    /* â”€â”€ LAYOUT â”€â”€ */
    .layout { display: flex; padding-top: var(--nav-h); min-height: 100vh; }

    /* â”€â”€ SIDEBAR â”€â”€ */
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

    /* â”€â”€ MAIN â”€â”€ */
    .main {
      flex: 1; min-width: 0;
      padding: 28px 28px 80px;
      max-width: 1000px;
      margin: 0 auto;
      width: 100%;
    }

    /* â”€â”€ ALERT â”€â”€ */
    .alert {
      display: flex; align-items: center; gap: 10px;
      padding: 11px 14px; border-radius: var(--radius);
      font-size: 13.5px; margin-bottom: 20px;
    }
    .alert-success { background: var(--green-bg); color: var(--green); }
    .alert-error   { background: var(--red-bg);   color: var(--red);   }
    .alert i { font-size: 13px; }

    /* â”€â”€ GRID â”€â”€ */
    .profile-grid {
      display: grid;
      grid-template-columns: 260px 1fr;
      gap: 16px;
      align-items: start;
    }

    /* â”€â”€ CARD â”€â”€ */
    .card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px;
      margin-bottom: 16px;
    }
    .card:last-child { margin-bottom: 0; }

    /* â”€â”€ AVATAR HERO â”€â”€ */
    .avatar-hero {
      width: 72px; height: 72px; border-radius: 50%;
      background: var(--bg-muted); border: 1px solid var(--border-2);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; font-weight: 500; color: var(--text-2);
      font-family: var(--mono); margin: 0 auto 14px;
    }
    .hero-name { font-size: 17px; font-weight: 500; text-align: center; letter-spacing: -.2px; margin-bottom: 3px; }
    .hero-handle { font-size: 12px; color: var(--text-3); text-align: center; font-family: var(--mono); margin-bottom: 18px; }

    /* â”€â”€ STATS ROW â”€â”€ */
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1px; background: var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 16px; }
    .stat-cell { background: var(--bg-card); padding: 12px 8px; text-align: center; }
    .stat-val { font-size: 18px; font-weight: 500; font-family: var(--mono); letter-spacing: -.5px; }
    .stat-lbl { font-size: 11px; color: var(--text-3); margin-top: 2px; }

    /* â”€â”€ TEMP PASSWORD WARNING â”€â”€ */
    .temp-warn {
      display: flex; align-items: center; gap: 8px;
      font-size: 12px; color: var(--amber);
      background: var(--amber-bg); border-radius: 8px;
      padding: 8px 10px; margin-top: 10px;
    }
    .temp-warn i { font-size: 11px; }

    /* â”€â”€ SECTION HEADING â”€â”€ */
    .card-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 16px; padding-bottom: 12px;
      border-bottom: 1px solid var(--border);
    }
    .card-title { font-size: 13px; font-weight: 500; color: var(--text-2); letter-spacing: .05em; text-transform: uppercase; font-family: var(--mono); }
    .link-btn { font-size: 13px; color: var(--text-3); background: none; border: none; transition: color .15s; }
    .link-btn:hover { color: var(--text); }

    /* â”€â”€ INFO ROWS â”€â”€ */
    .info-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; padding: 9px 0; border-bottom: 1px solid var(--border); }
    .info-row:last-child { border-bottom: none; }
    .info-label { font-size: 12px; color: var(--text-3); font-family: var(--mono); white-space: nowrap; padding-top: 1px; }
    .info-value { font-size: 13.5px; color: var(--text); text-align: right; word-break: break-all; }

    /* â”€â”€ THREAD ITEMS â”€â”€ */
    .thread-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 10px 0; border-bottom: 1px solid var(--border);
      cursor: pointer; transition: color .15s;
    }
    .thread-item:last-child { border-bottom: none; }
    .thread-item:hover .ti-title { color: var(--text); }
    .ti-icon { width: 30px; height: 30px; border-radius: 8px; background: var(--bg-muted); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 11px; color: var(--text-3); flex-shrink: 0; }
    .ti-body { flex: 1; min-width: 0; }
    .ti-title { font-size: 13.5px; font-weight: 500; color: var(--text-2); line-height: 1.4; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; transition: color .15s; }
    .ti-meta { font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-top: 2px; }

    .empty-msg { font-size: 13px; color: var(--text-3); padding: 12px 0; text-align: center; }

    /* â”€â”€ TABS â”€â”€ */
    .tabs { display: flex; gap: 2px; margin-bottom: 16px; }
    .tab-btn { font-size: 13px; color: var(--text-3); background: none; border: none; padding: 5px 12px; border-radius: 7px; transition: background .15s, color .15s; }
    .tab-btn:hover { background: var(--bg-muted); color: var(--text-2); }
    .tab-btn.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .tab-panel { display: none; }
    .tab-panel.active { display: block; }

    /* â”€â”€ BUTTONS â”€â”€ */
    .btn-solid { background: var(--accent); color: var(--accent-fg); border: none; border-radius: 8px; padding: 7px 16px; font-size: 13px; font-weight: 500; transition: opacity .15s; width: 100%; }
    .btn-solid:hover { opacity: .85; }
    .btn-ghost { background: none; border: 1px solid var(--border); border-radius: 8px; padding: 7px 14px; font-size: 13px; color: var(--text-2); transition: background .15s, color .15s; }
    .btn-ghost:hover { background: var(--bg-muted); color: var(--text); }

    /* â”€â”€ OVERLAY / MODAL â”€â”€ */
    .overlay { display: none; position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,.35); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
    .overlay.open { display: flex; }
    .modal { background: var(--bg-card); border: 1px solid var(--border-2); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); padding: 24px; width: 100%; max-width: 440px; margin: 16px; }
    .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .modal-header h3 { font-size: 16px; font-weight: 500; letter-spacing: -.2px; }
    .close-btn { width: 30px; height: 30px; border: none; background: var(--bg-muted); border-radius: 7px; color: var(--text-2); font-size: 16px; display: flex; align-items: center; justify-content: center; transition: background .15s; }
    .close-btn:hover { background: var(--border); }
    .field-wrap { margin-bottom: 14px; }
    .field-label { font-size: 11px; font-weight: 500; color: var(--text-3); text-transform: uppercase; letter-spacing: .07em; font-family: var(--mono); margin-bottom: 6px; display: block; }
    .field-input { width: 100%; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; font-size: 14px; color: var(--text); outline: none; transition: border-color .15s; }
    .field-input:focus { border-color: var(--border-2); }
    select.field-input { appearance: none; cursor: pointer; }
    .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px; }
    .modal-btn-solid { background: var(--accent); color: var(--accent-fg); border: none; border-radius: 8px; padding: 7px 18px; font-size: 13px; font-weight: 500; transition: opacity .15s; }
    .modal-btn-solid:hover { opacity: .85; }

    /* â”€â”€ SCROLLBAR â”€â”€ */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* â”€â”€ RESPONSIVE â”€â”€ */
    @media (max-width: 860px) {
      .profile-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 720px) {
      .sidebar { display: none; }
      .main { padding: 16px 14px 60px; }
    }
    @media (max-width: 460px) {
      .nav-links { display: none; }
      .search-wrap { display: none; }
    }
  </style>
</head>
<body>

<!-- â”€â”€ NAVBAR â”€â”€ -->
<nav class="navbar">
  <div class="nav-logo">Ti<span>Peed</span></div>
  <div class="nav-links">
    <a href="<?= $homePage ?>">Home</a>
    <?php if ($currentUserRole === 'admin' || $currentUserRole === 'faculty'): ?>
    <a href="student_home.php">Threads</a>
    <a href="faculty_chats.php">Faculty</a>
    <?php endif; ?>
    <a href="community.php">Community</a>
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

<!-- â”€â”€ LAYOUT â”€â”€ -->
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
    <a href="profile.php" class="menu-item active"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>" class="menu-item"><i class="fas fa-house"></i> Home</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="coursechat.php" class="menu-item"><i class="fas fa-comments"></i> Communities Chat</a>
    <a href="community.php" class="menu-item"><i class="fas fa-users"></i> Community</a>
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

    <?php if (isset($success)): ?>
    <div class="alert alert-success"><i class="fas fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
    <div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="profile-grid">

      <!-- LEFT COLUMN -->
      <div>
        <!-- Identity card -->
        <div class="card">
          <div class="avatar-hero"><?= $avatar_initials ?></div>
          <div class="hero-name"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></div>
          <div class="hero-handle">@<?= htmlspecialchars($user['student_id'] ?? 'user' . $user['userid']) ?></div>

          <div class="stats-row">
            <div class="stat-cell">
              <div class="stat-val"><?= $thread_count ?></div>
              <div class="stat-lbl">Threads</div>
            </div>
            <div class="stat-cell">
              <div class="stat-val"><?= $likes_count ?></div>
              <div class="stat-lbl">Likes</div>
            </div>
            <div class="stat-cell">
              <div class="stat-val"><?= $comm_count ?></div>
              <div class="stat-lbl">Groups</div>
            </div>
          </div>

          <button class="btn-solid" id="openEditProfile">Edit profile</button>
        </div>

        <!-- Security card -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Security</span>
          </div>
          <button class="btn-solid" id="openChangePassword">Change password</button>
          <?php if ($user['is_temp_password']): ?>
          <div class="temp-warn"><i class="fas fa-triangle-exclamation"></i> Please change your temporary password</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIGHT COLUMN -->
      <div>
        <!-- Account info card -->
        <div class="card">
          <div class="card-header">
            <span class="card-title">Account information</span>
            <button class="link-btn" id="openEditProfile2"><i class="fas fa-pen" style="font-size:11px;margin-right:4px"></i>Edit</button>
          </div>
          <div class="info-row"><span class="info-label">Full name</span><span class="info-value"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></span></div>
          <div class="info-row"><span class="info-label">Student ID</span><span class="info-value"><?= htmlspecialchars($user['student_id'] ?? 'Not set') ?></span></div>
          <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= htmlspecialchars($user['email']) ?></span></div>
          <div class="info-row"><span class="info-label">Year level</span><span class="info-value"><?= isset($year_levels[$user['year_level']]) ? $year_levels[$user['year_level']] : 'Not set' ?></span></div>
          <div class="info-row"><span class="info-label">Role</span><span class="info-value"><?= ucfirst($user['role']) ?></span></div>
        </div>

        <!-- Activity card -->
        <div class="card">
          <div class="card-header" style="margin-bottom:12px">
            <span class="card-title">Activity</span>
          </div>
          <div class="tabs">
            <button class="tab-btn active" data-tab="liked">Liked threads</button>
            <button class="tab-btn" data-tab="mine">My threads</button>
          </div>

          <div class="tab-panel active" id="tab-liked">
            <?php if (empty($liked_threads)): ?>
            <div class="empty-msg"><i class="far fa-heart" style="display:block;font-size:20px;margin-bottom:6px;color:var(--text-3)"></i>No liked threads yet</div>
            <?php else: foreach ($liked_threads as $t): ?>
            <div class="thread-item" onclick="location.href='comment.php?thread_id=<?= $t['thread_id'] ?>'">
              <div class="ti-icon"><i class="fas fa-arrow-up"></i></div>
              <div class="ti-body">
                <div class="ti-title"><?= htmlspecialchars($t['title']) ?></div>
                <div class="ti-meta">Liked <?= date('M j, Y', strtotime($t['liked_date'])) ?></div>
              </div>
            </div>
            <?php endforeach; endif; ?>
          </div>

          <div class="tab-panel" id="tab-mine">
            <?php if (empty($my_threads)): ?>
            <div class="empty-msg"><i class="fas fa-pen-to-square" style="display:block;font-size:20px;margin-bottom:6px;color:var(--text-3)"></i>No threads posted yet</div>
            <?php else: foreach ($my_threads as $t): ?>
            <div class="thread-item" onclick="location.href='comment.php?thread_id=<?= $t['thread_id'] ?>'">
              <div class="ti-icon"><i class="fas fa-comment"></i></div>
              <div class="ti-body">
                <div class="ti-title"><?= htmlspecialchars($t['title']) ?></div>
                <div class="ti-meta"><?= date('M j, Y', strtotime($t['created_at'])) ?></div>
              </div>
            </div>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- â”€â”€ EDIT PROFILE MODAL â”€â”€ -->
<div class="overlay" id="editProfileOverlay">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit profile</h3>
      <button class="close-btn" id="closeEditProfile">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="update_profile" value="1">
      <div class="field-wrap">
        <label class="field-label">First name</label>
        <input type="text" name="first_name" class="field-input" value="<?= htmlspecialchars($user['first_name']) ?>" required>
      </div>
      <div class="field-wrap">
        <label class="field-label">Last name</label>
        <input type="text" name="last_name" class="field-input" value="<?= htmlspecialchars($user['last_name']) ?>" required>
      </div>
      <div class="field-wrap">
        <label class="field-label">Email</label>
        <input type="email" name="email" class="field-input" value="<?= htmlspecialchars($user['email']) ?>" required>
      </div>
      <div class="field-wrap">
        <label class="field-label">Year level</label>
        <select name="year_level" class="field-input">
          <?php for ($i=1;$i<=5;$i++): ?>
          <option value="<?= $i ?>" <?= $user['year_level']==$i?'selected':'' ?>><?= $year_levels[$i] ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" id="cancelEditProfile">Cancel</button>
        <button type="submit" class="modal-btn-solid">Save changes</button>
      </div>
    </form>
  </div>
</div>

<!-- â”€â”€ CHANGE PASSWORD MODAL â”€â”€ -->
<div class="overlay" id="changePasswordOverlay">
  <div class="modal">
    <div class="modal-header">
      <h3>Change password</h3>
      <button class="close-btn" id="closeChangePassword">&times;</button>
    </div>
    <form method="POST" id="passwordForm">
      <input type="hidden" name="change_password" value="1">
      <div class="field-wrap">
        <label class="field-label">Current password</label>
        <input type="password" name="current_password" class="field-input" required>
      </div>
      <div class="field-wrap">
        <label class="field-label">New password</label>
        <input type="password" name="new_password" class="field-input" required>
      </div>
      <div class="field-wrap">
        <label class="field-label">Confirm new password</label>
        <input type="password" name="confirm_password" class="field-input" required>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" id="cancelChangePassword">Cancel</button>
        <button type="submit" class="modal-btn-solid">Change password</button>
      </div>
    </form>
  </div>
</div>

<script>
  /* â”€â”€ THEME â”€â”€ */
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

  /* â”€â”€ TABS â”€â”€ */
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
  });

  /* â”€â”€ MODAL HELPERS â”€â”€ */
  function openModal(id)  { document.getElementById(id).classList.add('open'); }
  function closeModal(id) { document.getElementById(id).classList.remove('open'); }

  function bindModal(openIds, overlayId, closeIds) {
    openIds.forEach(id => { const el = document.getElementById(id); if (el) el.addEventListener('click', () => openModal(overlayId)); });
    closeIds.forEach(id => { const el = document.getElementById(id); if (el) el.addEventListener('click', () => closeModal(overlayId)); });
    document.getElementById(overlayId).addEventListener('click', e => { if (e.target.id === overlayId) closeModal(overlayId); });
  }

  bindModal(['openEditProfile','openEditProfile2'], 'editProfileOverlay', ['closeEditProfile','cancelEditProfile']);
  bindModal(['openChangePassword'], 'changePasswordOverlay', ['closeChangePassword','cancelChangePassword']);

  /* â”€â”€ RESET PASSWORD FORM ON CLOSE â”€â”€ */
  document.getElementById('cancelChangePassword').addEventListener('click', () => document.getElementById('passwordForm').reset());
  document.getElementById('closeChangePassword').addEventListener('click',  () => document.getElementById('passwordForm').reset());
</script>
</body>
</html>