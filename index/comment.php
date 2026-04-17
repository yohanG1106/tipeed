<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include "db_connect.php";
session_start();

include 'db_connect.php';

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

$threadId  = isset($_GET['thread_id'])  ? $_GET['thread_id']  : null;
$commentId = isset($_GET['comment_id']) ? $_GET['comment_id'] : null;

$storeParams = "";
if ($threadId)  $storeParams .= "localStorage.setItem('currentThreadId', '$threadId');";
if ($commentId) $storeParams .= "localStorage.setItem('currentCommentId', '$commentId');";

$firstName = isset($_SESSION['first_name']) ? $_SESSION['first_name'] : "User";
$lastName  = isset($_SESSION['last_name'])  ? $_SESSION['last_name']  : "";
$studentName = trim($firstName . " " . $lastName);

$currentUserId   = $_SESSION['userid'];
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$yearLevel       = isset($_SESSION['year_level']) ? $_SESSION['year_level'] : null;
$role            = $currentUserRole;

function ordinal($n) {
    $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($n % 100) >= 11 && ($n % 100) <= 13) return $n . 'th';
    return $n . $ends[$n % 10];
}

if ($role === 'student') {
    $studentIDT = ($yearLevel && is_numeric($yearLevel)) ? ordinal($yearLevel) . " Year" : "No year assigned";
} elseif ($role === 'faculty') { $studentIDT = "Faculty";
} elseif ($role === 'admin')   { $studentIDT = "Administrator";
} else { $studentIDT = ucfirst(htmlspecialchars($role)); }

$homePage = $currentUserRole === 'admin' ? 'admin_home.php'
          : ($currentUserRole === 'teacher' ? 'faculty_home.php' : 'student_home.php');

$isAdmin = $currentUserRole === 'admin';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Thread — TiPeed Forum</title>
  <script><?php echo $storeParams; ?></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* ── TOKENS ──────────────────────────────────── */
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
      max-width: 680px;
      margin: 0 auto;
      padding: 28px 24px 80px;
    }

    /* ── BACK BUTTON ── */
    .back-btn {
      display: inline-flex; align-items: center; gap: 7px;
      font-size: 13px; color: var(--text-3);
      padding: 5px 0; margin-bottom: 20px;
      background: none; border: none;
      cursor: pointer; transition: color .15s;
    }
    .back-btn:hover { color: var(--text-2); }
    .back-btn i { font-size: 12px; }

    /* ── THREAD CARD ── */
    .thread-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px 20px 16px;
      margin-bottom: 16px;
    }
    .thread-header { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 14px; }
    .thread-ava { width: 36px; height: 36px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: var(--text-2); font-family: var(--mono); }
    .thread-meta { flex: 1; min-width: 0; }
    .thread-author { font-size: 13px; font-weight: 500; }
    .thread-time { font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-top: 1px; }
    .thread-title { font-size: 18px; font-weight: 500; letter-spacing: -.3px; margin-bottom: 8px; line-height: 1.3; }
    .thread-body { font-size: 14px; color: var(--text-2); line-height: 1.7; margin-bottom: 14px; }
    .thread-img { width: 100%; max-height: 340px; object-fit: cover; border-radius: 10px; margin-bottom: 14px; border: 1px solid var(--border); display: block; }

    /* ── THREAD FOOTER ── */
    .thread-footer { display: flex; align-items: center; gap: 4px; border-top: 1px solid var(--border); padding-top: 12px; }
    .vote-group { display: flex; align-items: center; border: 1px solid var(--border); border-radius: 8px; overflow: hidden; margin-right: 4px; }
    .vote-btn { display: flex; align-items: center; gap: 4px; padding: 5px 10px; border: none; background: none; font-size: 12px; color: var(--text-3); transition: background .15s, color .15s; }
    .vote-btn:hover { background: var(--bg-muted); color: var(--text-2); }
    .vote-btn.up-active { color: #e44; }
    .vote-btn.down-active { color: var(--blue); }
    .vote-sep { width: 1px; background: var(--border); height: 20px; }
    .vote-count { font-family: var(--mono); font-size: 12px; color: var(--text-2); padding: 0 9px; }
    .action-pill { display: flex; align-items: center; gap: 5px; padding: 5px 10px; border: none; background: none; border-radius: 7px; font-size: 12.5px; color: var(--text-3); transition: background .15s, color .15s; }
    .action-pill:hover { background: var(--bg-muted); color: var(--text-2); }
    .action-pill i { font-size: 12px; }

    /* ── THREAD OPTIONS MENU ── */
    .thread-menu-wrap { position: relative; }
    .menu-trigger { width: 28px; height: 28px; border: none; background: none; border-radius: 6px; color: var(--text-3); display: flex; align-items: center; justify-content: center; font-size: 13px; transition: background .15s, color .15s; }
    .menu-trigger:hover { background: var(--bg-muted); color: var(--text-2); }
    .dropdown { position: absolute; right: 0; top: calc(100% + 4px); background: var(--bg-card); border: 1px solid var(--border-2); border-radius: 10px; overflow: hidden; box-shadow: var(--shadow-md); z-index: 20; min-width: 148px; display: none; }
    .dropdown.open { display: block; }
    .dropdown button { width: 100%; display: flex; align-items: center; gap: 8px; padding: 9px 14px; font-size: 13px; color: var(--text-2); background: none; border: none; transition: background .12s, color .12s; }
    .dropdown button:hover { background: var(--bg-muted); color: var(--text); }
    .dropdown button.danger { color: var(--red); }
    .dropdown button.danger:hover { background: var(--red-bg); }
    .dropdown button i { font-size: 12px; width: 14px; }

    /* ── COMMENTS SECTION ── */
    .comments-section { margin-top: 6px; }
    .comments-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
    .comments-title { font-size: 13px; font-weight: 500; color: var(--text-2); letter-spacing: .05em; text-transform: uppercase; font-family: var(--mono); }
    .comments-count { font-size: 11px; color: var(--text-3); font-family: var(--mono); }

    /* ── COMPOSE COMMENT ── */
    .compose-comment {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 14px 16px;
      margin-bottom: 16px;
      transition: border-color .15s;
    }
    .compose-comment:focus-within { border-color: var(--border-2); }
    .compose-top { display: flex; align-items: flex-start; gap: 10px; }
    .compose-ava { width: 32px; height: 32px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 500; color: var(--text-3); font-family: var(--mono); flex-shrink: 0; margin-top: 2px; }
    .compose-comment textarea {
      width: 100%; background: none; border: none; outline: none;
      font-size: 14px; color: var(--text); resize: none;
      line-height: 1.6; min-height: 60px;
      font-family: var(--font);
    }
    .compose-comment textarea::placeholder { color: var(--text-3); }
    .compose-actions { display: flex; justify-content: flex-end; margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border); }
    .btn-post { background: var(--accent); color: var(--accent-fg); border: none; border-radius: 7px; padding: 6px 16px; font-size: 13px; font-weight: 500; transition: opacity .15s; }
    .btn-post:hover { opacity: .85; }
    .btn-ghost { background: none; border: 1px solid var(--border); border-radius: 7px; padding: 6px 14px; font-size: 13px; color: var(--text-2); transition: background .15s, color .15s; }
    .btn-ghost:hover { background: var(--bg-muted); color: var(--text); }

    /* ── COMMENT CARD ── */
    .comment-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 14px 16px 10px;
      margin-bottom: 8px;
      transition: border-color .15s;
      animation: fadeUp .2s ease both;
    }
    .comment-card:hover { border-color: var(--border-2); }
    .comment-card.highlighted { border-color: var(--amber); background: var(--amber-bg); }

    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(8px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .comment-header { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .comment-ava { width: 28px; height: 28px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 500; color: var(--text-2); font-family: var(--mono); flex-shrink: 0; }
    .comment-author { font-size: 13px; font-weight: 500; flex: 1; }
    .comment-time { font-size: 11px; color: var(--text-3); font-family: var(--mono); }
    .comment-body { font-size: 14px; color: var(--text-2); line-height: 1.65; margin-bottom: 10px; padding-left: 36px; }
    .comment-footer { display: flex; align-items: center; gap: 4px; padding-left: 36px; }
    .pill-btn { display: flex; align-items: center; gap: 5px; padding: 4px 9px; border: none; background: none; border-radius: 6px; font-size: 12px; color: var(--text-3); transition: background .15s, color .15s; }
    .pill-btn:hover { background: var(--bg-muted); color: var(--text-2); }
    .pill-btn i { font-size: 11px; }
    .pill-btn.liked { color: var(--amber); }
    .like-count { font-family: var(--mono); font-size: 11px; }

    /* ── NESTED COMMENTS ── */
    .nested-wrap { margin-left: 36px; margin-top: 8px; border-left: 2px solid var(--border); padding-left: 12px; display: flex; flex-direction: column; gap: 6px; }
    .nested-comment { background: var(--bg-muted); border: 1px solid var(--border); border-radius: 10px; padding: 10px 12px 8px; }
    .nested-comment .comment-body { padding-left: 0; }
    .nested-comment .comment-footer { padding-left: 0; }

    /* ── REPLY COMPOSE ── */
    .reply-compose { margin-top: 8px; margin-left: 36px; }
    .reply-compose textarea { width: 100%; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 8px 10px; font-size: 13px; color: var(--text); outline: none; resize: none; min-height: 54px; font-family: var(--font); transition: border-color .15s; }
    .reply-compose textarea:focus { border-color: var(--border-2); }
    .reply-actions { display: flex; gap: 6px; margin-top: 6px; justify-content: flex-end; }

    /* ── EMPTY STATE ── */
    .empty-state { text-align: center; padding: 40px 20px; color: var(--text-3); }
    .empty-state i { font-size: 24px; }
    .empty-state p { font-size: 14px; margin-top: 8px; }

    /* ── LOADING ── */
    .skeleton { background: var(--bg-muted); border-radius: 6px; animation: shimmer 1.5s infinite; }
    @keyframes shimmer { 0%,100% { opacity: 1; } 50% { opacity: .5; } }

    /* ── OVERLAY / MODAL ── */
    .overlay { display: none; position: fixed; inset: 0; z-index: 200; background: rgba(0,0,0,.35); backdrop-filter: blur(4px); align-items: center; justify-content: center; }
    .overlay.open { display: flex; }
    .modal { background: var(--bg-card); border: 1px solid var(--border-2); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); padding: 24px; width: 100%; max-width: 480px; margin: 16px; }
    .modal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; }
    .modal-header h3 { font-size: 16px; font-weight: 500; letter-spacing: -.2px; }
    .close-btn { width: 30px; height: 30px; border: none; background: var(--bg-muted); border-radius: 7px; color: var(--text-2); font-size: 16px; display: flex; align-items: center; justify-content: center; transition: background .15s, color .15s; }
    .close-btn:hover { background: var(--border); color: var(--text); }
    .field-wrap { margin-bottom: 14px; }
    .field-label { font-size: 11px; font-weight: 500; color: var(--text-3); text-transform: uppercase; letter-spacing: .07em; font-family: var(--mono); margin-bottom: 6px; display: block; }
    .field-input { width: 100%; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 9px 12px; font-size: 14px; color: var(--text); outline: none; transition: border-color .15s; }
    .field-input:focus { border-color: var(--border-2); }
    textarea.field-input { resize: vertical; min-height: 80px; }
    select.field-input { appearance: none; cursor: pointer; }
    .modal-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 20px; }
    .btn-solid { background: var(--accent); color: var(--accent-fg); border: none; border-radius: 8px; padding: 7px 18px; font-size: 13px; font-weight: 500; transition: opacity .15s; }
    .btn-solid:hover { opacity: .85; }

    /* ── SCROLLBAR ── */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* ── RESPONSIVE ── */
    @media (max-width: 720px) {
      .sidebar { display: none; }
      .main { padding: 16px 14px 80px; }
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
      <div class="avatar-circle"><?= strtoupper(substr($studentName, 0, 2)) ?></div>
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

    <button class="back-btn" id="backBtn">
      <i class="fas fa-arrow-left"></i> Back to threads
    </button>

    <!-- Thread loaded here -->
    <div id="threadContainer"></div>

    <!-- Comments section -->
    <div class="comments-section">
      <div class="comments-header">
        <span class="comments-title">Comments</span>
        <span class="comments-count" id="commentsCount">—</span>
      </div>

      <!-- Compose comment -->
      <div class="compose-comment">
        <div class="compose-top">
          <div class="compose-ava"><?= strtoupper(substr($studentName, 0, 2)) ?></div>
          <textarea id="commentInput" placeholder="Add a comment…" rows="2"></textarea>
        </div>
        <div class="compose-actions">
          <button class="btn-post" id="commentSubmit">Post comment</button>
        </div>
      </div>

      <div id="commentsList"></div>
    </div>

  </main>
</div>

<!-- ── REPORT MODAL ── -->
<div class="overlay" id="reportOverlay">
  <div class="modal">
    <div class="modal-header">
      <h3>Submit report</h3>
      <button class="close-btn" id="closeReport">&times;</button>
    </div>
    <form id="reportForm">
      <input type="hidden" id="reportedUserId"   name="reported_user_id">
      <input type="hidden" id="locationType"      name="location_type">
      <input type="hidden" id="locationId"        name="location_id">
      <input type="hidden" id="reportedUserName"  name="reported_user_name">

      <div class="field-wrap">
        <label class="field-label">Category</label>
        <select id="reportCategory" name="report_type" class="field-input" required>
          <option value="">Select category</option>
          <option value="inappropriate">Inappropriate content</option>
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
        <textarea id="reportDescription" name="description" class="field-input" placeholder="Describe the issue…" required></textarea>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn-ghost" id="cancelReport">Cancel</button>
        <button type="submit" class="btn-solid">Submit</button>
      </div>
    </form>
  </div>
</div>

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

  /* ── BACK ── */
  document.getElementById('backBtn').addEventListener('click', () => history.length > 1 ? history.back() : location.href = 'student_home.php');

  /* ── GLOBALS ── */
  const currentUserId   = <?= json_encode($currentUserId) ?>;
  const currentUserRole = <?= json_encode($currentUserRole) ?>;
  const urlParams       = new URLSearchParams(location.search);
  const highlightCommentId = <?= json_encode($commentId) ?>;

  function getThreadId() {
    const p = urlParams.get('thread_id');
    if (p && p !== '0') { localStorage.setItem('currentThreadId', p); return p; }
    return localStorage.getItem('currentThreadId');
  }

  function initials(name) {
    if (!name) return '?';
    return name.trim().split(' ').filter(Boolean).map(w => w[0]).join('').slice(0,2).toUpperCase();
  }

  /* ── LOAD ── */
  function load() {
    const tid = getThreadId();
    if (!tid) { document.getElementById('threadContainer').innerHTML = '<p style="color:var(--text-3);font-size:14px">No thread selected.</p>'; return; }
    fetch(`get_thread.php?thread_id=${tid}`)
      .then(r => r.json())
      .then(data => {
        if (!data || data.error) { document.getElementById('threadContainer').innerHTML = '<p style="color:var(--text-3);font-size:14px">Thread not found.</p>'; return; }
        renderThread(data.thread);
        renderComments(data.thread.comments || []);
      })
      .catch(() => { document.getElementById('threadContainer').innerHTML = '<p style="color:var(--red);font-size:14px">Error loading thread.</p>'; });
  }

  /* ── THREAD ── */
  function renderThread(t) {
    const canDelete = (t.user_id == currentUserId) || currentUserRole === 'admin';
    const el = document.createElement('div');
    el.className = 'thread-card';
    el.innerHTML = `
      <div class="thread-header">
        <div class="thread-ava">${initials(t.username)}</div>
        <div class="thread-meta">
          <div class="thread-author">${esc(t.username)}</div>
          <div class="thread-time">${esc(t.time)}</div>
        </div>
        <div class="thread-menu-wrap">
          <button class="menu-trigger"><i class="fas fa-ellipsis"></i></button>
          <div class="dropdown">
            <button class="do-report-thread"><i class="fas fa-flag"></i> Report</button>
            <button class="do-share-thread"><i class="fas fa-share-nodes"></i> Share</button>
            ${canDelete ? `<button class="do-delete-thread danger"><i class="fas fa-trash"></i> Delete</button>` : ''}
          </div>
        </div>
      </div>
      <h2 class="thread-title">${esc(t.title)}</h2>
      ${t.body ? `<div class="thread-body">${esc(t.body).replace(/\n/g,'<br>')}</div>` : ''}
      ${t.image ? `<img src="${esc(t.image)}" class="thread-img" alt="Thread image">` : ''}
      <div class="thread-footer">
        <div class="vote-group">
          <button class="vote-btn vote-up"><i class="fas fa-arrow-up"></i></button>
          <div class="vote-sep"></div>
          <span class="vote-count">${t.votes || 0}</span>
          <div class="vote-sep"></div>
          <button class="vote-btn vote-down"><i class="fas fa-arrow-down"></i></button>
        </div>
        <button class="action-pill"><i class="fas fa-share-nodes"></i> Share</button>
        <button class="action-pill"><i class="far fa-bookmark"></i> Save</button>
      </div>
    `;

    const vc = el.querySelector('.vote-count');
    el.querySelector('.vote-up').addEventListener('click',   () => voteThread(t.thread_id, 'up', vc, el));
    el.querySelector('.vote-down').addEventListener('click', () => voteThread(t.thread_id, 'down', vc, el));

    el.querySelector('.do-report-thread').addEventListener('click', () => openReport({ locationType:'thread', locationId: t.thread_id, userId: t.user_id, userName: t.username }));
    el.querySelector('.do-share-thread').addEventListener('click', () => { navigator.clipboard.writeText(`${location.origin}/comment.php?thread_id=${t.thread_id}`).then(() => alert('Link copied!')); });
    if (canDelete) {
      el.querySelector('.do-delete-thread').addEventListener('click', () => {
        if (!confirm('Delete this thread?')) return;
        fetch('delete_thread.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`thread_id=${t.thread_id}` })
          .then(r => r.json()).then(d => { if (d.status === 'success') location.href = 'student_home.php'; else alert(d.message || 'Failed'); });
      });
    }

    setupDropdown(el);
    document.getElementById('threadContainer').innerHTML = '';
    document.getElementById('threadContainer').appendChild(el);
  }

  /* ── VOTE ── */
  function voteThread(tid, type, vcEl, container) {
    fetch('vote.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`thread_id=${tid}&vote=${type}` })
      .then(r => r.json()).then(d => {
        if (d.error) return;
        vcEl.textContent = d.total;
        container.querySelector('.vote-up').classList.toggle('up-active', d.user_vote === 'up');
        container.querySelector('.vote-down').classList.toggle('down-active', d.user_vote === 'down');
      });
  }

  /* ── COMMENTS ── */
  function renderComments(comments) {
    const list = document.getElementById('commentsList');
    const count = countAll(comments);
    document.getElementById('commentsCount').textContent = `${count} comment${count !== 1 ? 's' : ''}`;
    list.innerHTML = '';
    if (!comments.length) {
      list.innerHTML = '<div class="empty-state"><i class="far fa-comment"></i><p>No comments yet — be the first!</p></div>';
      return;
    }
    comments.forEach(c => list.appendChild(buildComment(c, false)));
    if (highlightCommentId) {
      const el = list.querySelector(`[data-cid="${highlightCommentId}"]`);
      if (el) { el.classList.add('highlighted'); el.scrollIntoView({ behavior:'smooth', block:'center' }); setTimeout(() => el.classList.remove('highlighted'), 5000); }
    }
  }

  function countAll(arr) {
    return arr.reduce((n, c) => n + 1 + countAll(c.replies || []), 0);
  }

  function buildComment(c, nested) {
    const canDelete = (c.user_id == currentUserId) || currentUserRole === 'admin';
    const wrap = document.createElement('div');
    wrap.className = nested ? 'nested-comment' : 'comment-card';
    wrap.dataset.cid = c.comment_id;
    wrap.innerHTML = `
      <div class="comment-header">
        <div class="comment-ava">${initials(c.username)}</div>
        <span class="comment-author">${esc(c.username)}</span>
        <span class="comment-time">${esc(c.created_at)}</span>
        <div class="thread-menu-wrap" style="margin-left:auto">
          <button class="menu-trigger" style="width:24px;height:24px;font-size:11px"><i class="fas fa-ellipsis"></i></button>
          <div class="dropdown">
            <button class="do-report-comment"><i class="fas fa-flag"></i> Report</button>
            ${canDelete ? `<button class="do-delete-comment danger"><i class="fas fa-trash"></i> Delete</button>` : ''}
          </div>
        </div>
      </div>
      <div class="comment-body">${esc(c.content).replace(/\n/g,'<br>')}</div>
      <div class="comment-footer">
        <button class="pill-btn do-reply"><i class="fas fa-reply"></i> Reply</button>
        <button class="pill-btn do-like ${c.isLiked ? 'liked' : ''}">
          <i class="${c.isLiked ? 'fas' : 'far'} fa-thumbs-up"></i>
          <span class="like-count">${c.likes || 0}</span>
        </button>
      </div>
    `;

    wrap.querySelector('.do-report-comment').addEventListener('click', () => openReport({ locationType:'comment', locationId: c.comment_id, userId: c.user_id, userName: c.username }));

    if (canDelete) {
      wrap.querySelector('.do-delete-comment').addEventListener('click', () => {
        if (!confirm('Delete this comment?')) return;
        fetch('delete_comment.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`comment_id=${c.comment_id}&user_id=${currentUserId}&role=${currentUserRole}` })
          .then(r => r.json()).then(d => { if (d.success) load(); else alert(d.error || 'Failed'); });
      });
    }

    /* like */
    const likeBtn = wrap.querySelector('.do-like');
    const likeCount = wrap.querySelector('.like-count');
    likeBtn.addEventListener('click', () => {
      fetch('like_comment.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`comment_id=${c.comment_id}` })
        .then(r => r.json()).then(d => {
          if (d.error) return;
          c.likes = d.total; c.isLiked = d.liked;
          likeCount.textContent = c.likes;
          likeBtn.querySelector('i').className = c.isLiked ? 'fas fa-thumbs-up' : 'far fa-thumbs-up';
          likeBtn.classList.toggle('liked', c.isLiked);
        });
    });

    /* reply */
    wrap.querySelector('.do-reply').addEventListener('click', () => {
      if (wrap.querySelector('.reply-compose')) return;
      const rc = document.createElement('div');
      rc.className = 'reply-compose';
      rc.innerHTML = `
        <textarea placeholder="Write a reply…" rows="2"></textarea>
        <div class="reply-actions">
          <button class="btn-ghost do-cancel-reply">Cancel</button>
          <button class="btn-post do-post-reply">Post reply</button>
        </div>
      `;
      rc.querySelector('.do-cancel-reply').addEventListener('click', () => rc.remove());
      rc.querySelector('.do-post-reply').addEventListener('click', () => {
        const txt = rc.querySelector('textarea').value.trim();
        if (txt) postComment(txt, c.comment_id);
      });
      wrap.appendChild(rc);
    });

    /* nested replies */
    if (c.replies && c.replies.length) {
      const nest = document.createElement('div');
      nest.className = 'nested-wrap';
      c.replies.forEach(r => nest.appendChild(buildComment(r, true)));
      wrap.appendChild(nest);
    }

    setupDropdown(wrap);
    return wrap;
  }

  /* ── POST COMMENT ── */
  function postComment(content, parentId = null) {
    const tid = getThreadId();
    fetch('add_comment.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body: `thread_id=${encodeURIComponent(tid)}&content=${encodeURIComponent(content)}&parent_id=${encodeURIComponent(parentId || '')}` })
    .then(r => r.json()).then(d => { if (d.success) { load(); document.getElementById('commentInput').value = ''; } else alert(d.error || 'Failed'); });
  }

  document.getElementById('commentSubmit').addEventListener('click', () => {
    const v = document.getElementById('commentInput').value.trim();
    if (v) postComment(v);
  });
  document.getElementById('commentInput').addEventListener('keydown', e => {
    if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
      const v = document.getElementById('commentInput').value.trim();
      if (v) postComment(v);
    }
  });

  /* ── DROPDOWN ── */
  function setupDropdown(root) {
    root.querySelectorAll('.menu-trigger').forEach(btn => {
      btn.addEventListener('click', e => {
        e.stopPropagation();
        const dd = btn.nextElementSibling;
        document.querySelectorAll('.dropdown.open').forEach(d => { if (d !== dd) d.classList.remove('open'); });
        dd.classList.toggle('open');
      });
    });
  }
  document.addEventListener('click', () => document.querySelectorAll('.dropdown.open').forEach(d => d.classList.remove('open')));

  /* ── REPORT MODAL ── */
  const reportOverlay = document.getElementById('reportOverlay');
  document.getElementById('closeReport').addEventListener('click', () => reportOverlay.classList.remove('open'));
  document.getElementById('cancelReport').addEventListener('click', () => reportOverlay.classList.remove('open'));
  reportOverlay.addEventListener('click', e => { if (e.target === reportOverlay) reportOverlay.classList.remove('open'); });

  function openReport({ locationType, locationId, userId, userName }) {
    document.getElementById('locationType').value    = locationType;
    document.getElementById('locationId').value      = locationId;
    document.getElementById('reportedUserId').value  = userId;
    document.getElementById('reportedUserName').value = userName;
    reportOverlay.classList.add('open');
  }

  document.getElementById('reportForm').addEventListener('submit', e => {
    e.preventDefault();
    const fd = new FormData(e.target);
    fetch('submit_report.php', { method:'POST', body: fd })
      .then(r => r.json()).then(d => {
        if (d.success) { alert(`Report submitted!\nID: ${d.reportId}\nOur moderators will review it shortly.`); reportOverlay.classList.remove('open'); e.target.reset(); }
        else alert(d.error || 'Failed to submit report.');
      }).catch(() => alert('Error submitting report.'));
  });

  /* ── HELPERS ── */
  function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  /* ── INIT ── */
  load();
</script>
</body>
</html>