<?php
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

include 'db_connect.php';

$admin_name     = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$admin_initials = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));

$reports  = [];
$total = $unread = $pending = $resolved = 0;

$sql = "SELECT r.*, t.thread_id AS thread_id
        FROM reports r
        LEFT JOIN threads t ON r.thread_id = t.thread_id
        ORDER BY r.created_at DESC LIMIT 200";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
        $total++;
        $status = strtolower($row['status'] ?? 'pending');
        if ($status === 'pending')  $pending++;
        if ($status === 'resolved') $resolved++;
        $isRead = isset($row['is_read']) ? $row['is_read'] : ($status === 'resolved' ? 1 : 0);
        if (!$isRead) $unread++;
    }
}

$currentUserRole = $_SESSION['role'] ?? '';
if ($currentUserRole === 'admin')        $homePage = 'admin_home.php';
elseif ($currentUserRole === 'faculty')  $homePage = 'teacher_home.php';
else                                     $homePage = 'student_home.php';

$studentName = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$yearLevel   = $_SESSION['year_level'] ?? null;
$role        = $_SESSION['role'] ?? "student";

function ordinal($n) {
    $e = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($n % 100) >= 11 && ($n % 100) <= 13) return $n . 'th';
    return $n . $e[$n % 10];
}
if ($role === 'student')      $studentIDT = ($yearLevel && is_numeric($yearLevel)) ? ordinal($yearLevel) . " Year" : "No year assigned";
elseif ($role === 'faculty')  $studentIDT = "Faculty";
elseif ($role === 'admin')    $studentIDT = "Administrator";
else                          $studentIDT = ucfirst(htmlspecialchars($role));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports â€” TIPeed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    /* â”€â”€â”€ Reset â”€â”€â”€ */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }

    /* â”€â”€â”€ Tokens â”€â”€â”€ */
    :root {
      --bg:        #f7f6f3;
      --surface:   #ffffff;
      --surface2:  #f0ede8;
      --border:    #e4e0d8;
      --border2:   #ccc9be;
      --text:      #1a1916;
      --text2:     #5c5a54;
      --text3:     #9c9a94;
      --accent-bg: #1a1916;
      --accent-fg: #f7f6f3;
      --tag-bg:    #eceae4;
      --tag-text:  #5c5a54;
      --gold:      #f5b301;
      --gold-bg:   #fef9ec;  --gold-text: #b07d2a;
      --red-bg:    #fef2f0;  --red-text:  #c0392b;
      --green-bg:  #f0f9f4;  --green-text:#276749;
      --blue-bg:   #f0f5fe;  --blue-text: #2563a8;
      --amber-bg:  #fef9ec;  --amber-text:#b45309;
      --purple-bg: #f5f3ff;  --purple-text:#6d28d9;
      --radius:    8px;
      --radius-lg: 12px;
      --radius-xl: 16px;
      --font:      'DM Sans', system-ui, sans-serif;
      --mono:      'DM Mono', monospace;
      --trans:     all 0.16s ease;
    }
    [data-theme="dark"] {
      --bg:        #141310;
      --surface:   #1e1c19;
      --surface2:  #252320;
      --border:    #2e2c28;
      --border2:   #3a3834;
      --text:      #f0ede8;
      --text2:     #a8a49c;
      --text3:     #6e6b64;
      --accent-bg: #f0ede8;
      --accent-fg: #1a1916;
      --tag-bg:    #252320;
      --tag-text:  #a8a49c;
      --gold-bg:   #251e0e;  --gold-text: #dba94a;
      --red-bg:    #2a1614;  --red-text:  #f4826e;
      --green-bg:  #0f1f16;  --green-text:#52c487;
      --blue-bg:   #0f1624;  --blue-text: #6da4f4;
      --amber-bg:  #251e0e;  --amber-text:#fbbf24;
      --purple-bg: #1a1230;  --purple-text:#a78bfa;
    }

    /* â”€â”€â”€ Base â”€â”€â”€ */
    body {
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      font-size: 14px;
      line-height: 1.55;
      min-height: 100vh;
      overflow: hidden;
    }

    /* â”€â”€â”€ Navbar â”€â”€â”€ */
    .navbar {
      display: flex; align-items: center; gap: 4px;
      padding: 0 24px; height: 52px;
      background: var(--surface);
      border-bottom: 0.5px solid var(--border);
      position: sticky; top: 0; z-index: 100;
    }
    .logo { font-size: 15px; font-weight: 500; letter-spacing: -0.3px; margin-right: 16px; flex-shrink: 0; }
    .nav-links { display: flex; gap: 2px; flex: 1; }
    .nav-links a {
      padding: 5px 10px; border-radius: var(--radius);
      font-size: 13px; color: var(--text2); transition: var(--trans);
    }
    .nav-links a:hover, .nav-links a.active { color: var(--text); background: var(--tag-bg); }
    .nav-right { display: flex; align-items: center; gap: 6px; margin-left: auto; }
    .search {
      display: flex; align-items: center; gap: 8px;
      background: var(--surface2); border: 0.5px solid var(--border);
      border-radius: var(--radius); padding: 5px 10px;
    }
    .search i { font-size: 11px; color: var(--text3); }
    .search input {
      border: none; background: transparent; outline: none;
      font-family: var(--font); font-size: 13px; color: var(--text); width: 160px;
    }
    .search input::placeholder { color: var(--text3); }
    .icon-btn {
      width: 30px; height: 30px;
      border: 0.5px solid var(--border); background: transparent;
      border-radius: var(--radius);
      display: flex; align-items: center; justify-content: center;
      color: var(--text2); font-size: 13px; transition: var(--trans);
    }
    .icon-btn:hover { background: var(--tag-bg); color: var(--text); }

    /* â”€â”€â”€ Layout â”€â”€â”€ */
    .layout {
      display: grid;
      grid-template-columns: 220px 1fr;
      height: calc(100vh - 52px);
      overflow: hidden;
    }

    /* â”€â”€â”€ Sidebar â”€â”€â”€ */
    .sidebar {
      background: var(--surface);
      border-right: 0.5px solid var(--border);
      padding: 16px 0; height: 100%;
      overflow-y: auto; display: flex; flex-direction: column;
    }
    .sidebar::-webkit-scrollbar { width: 3px; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }
    .profile-block {
      display: flex; align-items: center; gap: 10px;
      padding: 0 16px 16px;
      border-bottom: 0.5px solid var(--border); margin-bottom: 8px;
    }
    .avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: var(--accent-bg); color: var(--accent-fg);
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 500; letter-spacing: 0.3px;
      flex-shrink: 0; font-family: var(--mono);
    }
    .profile-name { font-size: 13px; font-weight: 500; }
    .profile-role { font-size: 11px; color: var(--text3); margin-top: 1px; }
    .nav-section-label {
      padding: 10px 16px 4px; font-size: 10px; font-weight: 500;
      color: var(--text3); text-transform: uppercase; letter-spacing: 0.08em;
    }
    .menu-item {
      display: flex; align-items: center; gap: 10px;
      padding: 7px 16px; font-size: 13px; color: var(--text2);
      border-left: 2px solid transparent; transition: var(--trans); cursor: pointer;
    }
    .menu-item:hover { color: var(--text); background: var(--surface2); }
    .menu-item.active { color: var(--text); background: var(--surface2); border-left-color: var(--text); }
    .menu-item i { width: 14px; font-size: 12px; flex-shrink: 0; text-align: center; }
    .sidebar-bottom {
      margin-top: auto; padding: 12px 16px;
      border-top: 0.5px solid var(--border);
    }
    .sidebar-bottom a {
      display: flex; align-items: center; gap: 10px;
      font-size: 12px; color: var(--text3); padding: 6px 0; transition: var(--trans);
    }
    .sidebar-bottom a:hover { color: var(--red-text); }

    /* â”€â”€â”€ Main â”€â”€â”€ */
    .main { overflow-y: auto; padding: 28px 32px; }
    .main::-webkit-scrollbar { width: 4px; }
    .main::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }

    /* â”€â”€â”€ Page header â”€â”€â”€ */
    .page-eyebrow {
      font-size: 11px; font-weight: 500; color: var(--red-text);
      text-transform: uppercase; letter-spacing: 0.08em;
      margin-bottom: 4px; font-family: var(--mono);
    }
    .page-title-row {
      display: flex; align-items: center; justify-content: space-between;
      gap: 16px; flex-wrap: wrap; margin-bottom: 20px;
    }
    .page-title { font-size: 20px; font-weight: 500; letter-spacing: -0.4px; }
    .back-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--text3);
      border: 0.5px solid var(--border);
      background: var(--surface); padding: 5px 11px;
      border-radius: var(--radius); transition: var(--trans);
    }
    .back-link:hover { color: var(--text); background: var(--tag-bg); }
    .back-link i { font-size: 10px; }

    /* â”€â”€â”€ Stats strip â”€â”€â”€ */
    .stats-strip { display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
    .stat-card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 14px 20px; min-width: 100px;
      display: flex; flex-direction: column; gap: 2px;
    }
    .stat-num   { font-size: 22px; font-weight: 500; font-family: var(--mono); color: var(--text); }
    .stat-num.red    { color: var(--red-text); }
    .stat-num.amber  { color: var(--amber-text); }
    .stat-num.green  { color: var(--green-text); }
    .stat-label { font-size: 11px; color: var(--text3); }

    /* â”€â”€â”€ Toolbar â”€â”€â”€ */
    .toolbar { display: flex; gap: 8px; margin-bottom: 16px; flex-wrap: wrap; align-items: center; }
    .filter-bar {
      display: flex; gap: 4px;
      background: var(--surface); border: 0.5px solid var(--border);
      border-radius: var(--radius); padding: 4px;
    }
    .filter-btn {
      padding: 5px 10px; border-radius: 6px;
      font-size: 12px; font-weight: 500;
      border: none; background: transparent;
      color: var(--text2); cursor: pointer; transition: var(--trans);
    }
    .filter-btn:hover { color: var(--text); background: var(--tag-bg); }
    .filter-btn.active { background: var(--accent-bg); color: var(--accent-fg); }
    .toolbar-search {
      display: flex; align-items: center; gap: 8px;
      background: var(--surface); border: 0.5px solid var(--border);
      border-radius: var(--radius); padding: 6px 12px; flex: 1; max-width: 280px;
    }
    .toolbar-search i { font-size: 11px; color: var(--text3); }
    .toolbar-search input {
      border: none; background: transparent; outline: none;
      font-family: var(--font); font-size: 13px; color: var(--text); width: 100%;
    }
    .toolbar-search input::placeholder { color: var(--text3); }

    /* â”€â”€â”€ Reports list card â”€â”€â”€ */
    .reports-card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }

    /* â”€â”€â”€ Report row â”€â”€â”€ */
    .report-row {
      display: flex; gap: 12px; align-items: flex-start;
      padding: 14px 20px;
      border-bottom: 0.5px solid var(--border);
      transition: var(--trans); cursor: pointer;
    }
    .report-row:last-child { border-bottom: none; }
    .report-row:hover { background: var(--surface2); }
    .report-row.is-unread { border-left: 2px solid var(--red-text); }

    .row-icon {
      width: 32px; height: 32px; border-radius: var(--radius);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; flex-shrink: 0; margin-top: 1px;
    }
    .row-icon.inappropriate { background: var(--red-bg);    color: var(--red-text); }
    .row-icon.spam          { background: var(--amber-bg);  color: var(--amber-text); }
    .row-icon.harassment    { background: var(--purple-bg); color: var(--purple-text); }
    .row-icon.technical     { background: var(--blue-bg);   color: var(--blue-text); }
    .row-icon.other         { background: var(--tag-bg);    color: var(--tag-text); }

    .row-body { flex: 1; min-width: 0; }
    .row-top  { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; margin-bottom: 4px; }
    .row-title { font-size: 13px; font-weight: 500; }
    .row-desc  { font-size: 12px; color: var(--text2); line-height: 1.5; margin-bottom: 8px;
                 display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .row-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    .row-meta   { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
    .row-time   { font-size: 10px; color: var(--text3); font-family: var(--mono); }
    .row-reporter { font-size: 11px; color: var(--text2); }
    .row-actions { display: flex; gap: 5px; }

    /* â”€â”€â”€ Badges â”€â”€â”€ */
    .badge {
      display: inline-flex; align-items: center;
      padding: 2px 7px; border-radius: 4px;
      font-size: 10px; font-weight: 500; font-family: var(--mono);
    }
    .badge.pending      { background: var(--amber-bg); color: var(--amber-text); }
    .badge.resolved     { background: var(--green-bg); color: var(--green-text); }
    .badge.investigating{ background: var(--blue-bg);  color: var(--blue-text); }
    .badge-type         { background: var(--tag-bg);   color: var(--tag-text); font-size: 10px; font-family: var(--mono); padding: 2px 6px; border-radius: 4px; }

    /* â”€â”€â”€ Buttons â”€â”€â”€ */
    .btn {
      display: inline-flex; align-items: center; gap: 5px;
      padding: 5px 10px; border-radius: var(--radius);
      font-size: 11px; font-weight: 500; font-family: var(--font);
      border: 0.5px solid var(--border);
      background: var(--surface); color: var(--text2);
      cursor: pointer; transition: var(--trans);
    }
    .btn:hover { background: var(--tag-bg); color: var(--text); }
    .btn.resolve  { background: var(--green-bg); color: var(--green-text); border-color: rgba(39,103,73,0.2); }
    .btn.resolve:hover { background: var(--green-text); color: #fff; }
    .btn.danger   { background: var(--red-bg);   color: var(--red-text);   border-color: rgba(192,57,43,0.2); }
    .btn.danger:hover  { background: var(--red-text);   color: #fff; }
    .btn.primary  { background: var(--accent-bg); color: var(--accent-fg); border-color: var(--accent-bg); }
    .btn.primary:hover { opacity: 0.85; }
    .btn i { font-size: 10px; }

    /* â”€â”€â”€ Empty state â”€â”€â”€ */
    .empty-state {
      padding: 60px 24px; text-align: center;
    }
    .empty-icon {
      width: 52px; height: 52px; border-radius: 50%;
      background: var(--tag-bg); color: var(--text3);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin: 0 auto 14px;
    }
    .empty-title { font-size: 14px; font-weight: 500; margin-bottom: 4px; }
    .empty-sub   { font-size: 12px; color: var(--text3); }

    /* â”€â”€â”€ Load more â”€â”€â”€ */
    .load-more-row { padding: 14px 20px; border-top: 0.5px solid var(--border); text-align: center; }

    /* â”€â”€â”€ Detail Modal â”€â”€â”€ */
    .modal-bg {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.32); z-index: 200;
      display: none; align-items: center; justify-content: center;
    }
    .modal-bg.open { display: flex; }
    .modal {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-xl);
      width: 540px; max-width: 94vw;
      max-height: 88vh; overflow-y: auto;
    }
    .modal::-webkit-scrollbar { width: 3px; }
    .modal::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }
    .modal-header {
      padding: 18px 22px 14px;
      border-bottom: 0.5px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; background: var(--surface); z-index: 1;
    }
    .modal-title { font-size: 15px; font-weight: 500; }
    .modal-close {
      width: 26px; height: 26px; border: none; background: transparent;
      border-radius: var(--radius); color: var(--text3); font-size: 16px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: var(--trans);
    }
    .modal-close:hover { background: var(--tag-bg); color: var(--text); }
    .modal-body { padding: 20px 22px; }
    .detail-row {
      display: flex; gap: 16px; padding: 8px 0;
      border-bottom: 0.5px solid var(--border); font-size: 13px;
    }
    .detail-row:last-of-type { border-bottom: none; }
    .detail-key   { font-weight: 500; color: var(--text2); min-width: 130px; flex-shrink: 0; }
    .detail-val   { color: var(--text); flex: 1; word-break: break-all; }
    .content-box {
      background: var(--surface2); border: 0.5px solid var(--border);
      border-radius: var(--radius); padding: 12px 14px; margin: 12px 0;
      font-size: 13px; color: var(--text2); line-height: 1.6;
    }
    .content-box strong { color: var(--text); font-weight: 500; display: block; margin-bottom: 4px; }
    .modal-footer {
      padding: 14px 22px; border-top: 0.5px solid var(--border);
      display: flex; justify-content: flex-end; gap: 8px;
      position: sticky; bottom: 0; background: var(--surface);
    }
    .modal-footer .btn { padding: 7px 14px; font-size: 12px; }

    /* â”€â”€â”€ Toast â”€â”€â”€ */
    .toast {
      position: fixed; bottom: 20px; right: 20px;
      background: var(--surface); border: 0.5px solid var(--border);
      border-radius: var(--radius-lg); padding: 10px 16px;
      display: none; align-items: center; gap: 8px;
      font-size: 13px; color: var(--text);
      box-shadow: 0 4px 20px rgba(0,0,0,0.08); z-index: 300;
    }
    .toast.show { display: flex; }
  </style>
</head>
<body>

<!-- â•â•â• NAVBAR â•â•â• -->
<nav class="navbar">
  <div class="logo">TIPeed</div>
  <div class="nav-links">
    <a href="<?= $homePage ?>">Home</a>
    <?php if ($currentUserRole === 'admin'): ?>
      <a href="student_home.php">Thread</a>
      <a href="faculty_chats.php">Faculty</a>
    <?php endif; ?>
    <a href="Community.php">Community</a>
    <a href="aboutus.php">About Us</a>
  </div>
  <div class="nav-right">
    <div class="search">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search topicsâ€¦">
    </div>
    <button class="icon-btn" id="darkToggle" title="Toggle dark mode">
      <i class="fas fa-moon" id="darkIcon"></i>
    </button>
  </div>
</nav>

<!-- â•â•â• LAYOUT â•â•â• -->
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="profile-block">
      <div class="avatar"><?= $admin_initials ?></div>
      <div>
        <div class="profile-name"><?= htmlspecialchars($studentName) ?></div>
        <div class="profile-role"><?= $studentIDT ?></div>
      </div>
    </div>

    <div class="nav-section-label">General</div>
    <a href="profile.php"        class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>"   class="menu-item"><i class="fas fa-home"></i> Home</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="CourseChat.php"     class="menu-item"><i class="fas fa-comments"></i> Communities Chat</a>
    <a href="Community.php"      class="menu-item"><i class="fas fa-users"></i> Community</a>

    <div class="nav-section-label">Admin</div>
    <a href="admin_reg.php"  class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    
  

    <div class="nav-section-label">Support</div>
    <a href="calendar.php"   class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <div class="menu-item"><i class="fas fa-cog"></i> Settings</div>
    <a href="Help.php"       class="menu-item"><i class="fas fa-question-circle"></i> Help</a>

    <div class="sidebar-bottom">
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">

    <!-- Header -->
    <div class="page-eyebrow">Admin Â· Content Moderation</div>
    <div class="page-title-row">
      <div class="page-title">User Reports</div>
      <a href="<?= $homePage ?>" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
      </a>
    </div>

    <!-- Stats -->
    <div class="stats-strip">
      <div class="stat-card">
        <div class="stat-num" id="totalCount"><?= $total ?></div>
        <div class="stat-label">Total</div>
      </div>
      <div class="stat-card">
        <div class="stat-num red" id="unreadCount"><?= $unread ?></div>
        <div class="stat-label">Unread</div>
      </div>
      <div class="stat-card">
        <div class="stat-num amber" id="pendingCount"><?= $pending ?></div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-card">
        <div class="stat-num green" id="resolvedCount"><?= $resolved ?></div>
        <div class="stat-label">Resolved</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="unread">Unread</button>
        <button class="filter-btn" data-filter="pending">Pending</button>
        <button class="filter-btn" data-filter="resolved">Resolved</button>
        <button class="filter-btn" data-filter="inappropriate">Inappropriate</button>
        <button class="filter-btn" data-filter="spam">Spam</button>
        <button class="filter-btn" data-filter="harassment">Harassment</button>
      </div>
      <div class="toolbar-search">
        <i class="fas fa-search"></i>
        <input type="text" id="reportSearch" placeholder="Search reportsâ€¦">
      </div>
    </div>

    <!-- Reports card -->
    <div class="reports-card" id="reportsList">
      <?php if (empty($reports)): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-inbox"></i></div>
          <div class="empty-title">No reports found</div>
          <div class="empty-sub">There are currently no reports in the system.</div>
        </div>
      <?php else: ?>
        <?php foreach ($reports as $r):
          $r_status   = strtolower($r['status'] ?? 'pending');
          $r_type_raw = strtolower($r['report_type'] ?? 'other');
          $r_type     = preg_replace('/[^a-z0-9\-]/', '-', $r_type_raw);
          $isRead     = isset($r['is_read']) ? $r['is_read'] : ($r_status === 'resolved' ? 1 : 0);
          $isUnread   = !$isRead;

          switch ($r_type_raw) {
            case 'inappropriate': $icon = 'fa-exclamation-triangle'; break;
            case 'spam':          $icon = 'fa-ban'; break;
            case 'harassment':    $icon = 'fa-user-slash'; break;
            case 'technical':     $icon = 'fa-bug'; break;
            default:              $icon = 'fa-flag';
          }
          $typeClass = in_array($r_type_raw, ['inappropriate','spam','harassment','technical']) ? $r_type_raw : 'other';

          $desc    = htmlspecialchars((!empty($r['description']) ? $r['description'] : ($r['reported_content'] ?? '')) ?: 'No description');
          $reporter     = htmlspecialchars($r['reporter_name'] ?? 'Unknown');
          $reportedUser = htmlspecialchars($r['reported_user_name'] ?? ($r['reported_user_id'] ?? 'Unknown'));
          $created_at   = !empty($r['created_at']) ? date('M j, Y Â· H:i', strtotime($r['created_at'])) : 'â€”';
        ?>
        <div class="report-row <?= $isUnread ? 'is-unread' : '' ?>"
             data-status="<?= $r_status ?>"
             data-type="<?= $r_type ?>"
             data-unread="<?= $isUnread ? '1' : '0' ?>"
             data-text="<?= strtolower($reporter . ' ' . ($r['report_type'] ?? '') . ' ' . ($r['description'] ?? '')) ?>">

          <div class="row-icon <?= $typeClass ?>">
            <i class="fas <?= $icon ?>"></i>
          </div>

          <div class="row-body">
            <div class="row-top">
              <div class="row-title">
                <?= htmlspecialchars(ucfirst($r['report_type'] ?? 'Report')) ?> Â· <span style="font-family:var(--mono);font-size:11px;color:var(--text3)">#<?= htmlspecialchars($r['report_id']) ?></span>
              </div>
              <div style="display:flex;gap:5px;align-items:center">
                <span class="badge <?= $r_status ?>"><?= ucfirst($r_status) ?></span>
                <?php if ($isUnread): ?>
                  <span style="width:6px;height:6px;border-radius:50%;background:var(--red-text);display:inline-block"></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="row-desc"><?= $desc ?></div>

            <div class="row-footer">
              <div class="row-meta">
                <span class="row-reporter"><i class="fas fa-user" style="font-size:10px;margin-right:4px;color:var(--text3)"></i><?= $reporter ?></span>
                <span class="row-time"><?= $created_at ?></span>
                <span class="badge-type"><?= htmlspecialchars($r['report_type'] ?? 'Other') ?></span>
              </div>
              <div class="row-actions">
                <?php if (!empty($r['thread_id'])): ?>
                  <button class="btn open-thread-btn" data-thread-id="<?= intval($r['thread_id']) ?>">
                    <i class="fas fa-external-link-alt"></i> Thread
                  </button>
                <?php endif; ?>
                <?php if (!empty($r['comment_id'])): ?>
                  <button class="btn open-comment-btn" data-thread-id="<?= intval($r['thread_id'] ?? 0) ?>" data-comment-id="<?= intval($r['comment_id']) ?>">
                    <i class="fas fa-comment"></i> Comment
                  </button>
                <?php endif; ?>
                <?php if ($r_status !== 'resolved'): ?>
                  <button class="btn resolve resolve-btn" data-id="<?= htmlspecialchars($r['reportForm_id'] ?? $r['report_id']) ?>">
                    <i class="fas fa-check"></i> Resolve
                  </button>
                <?php endif; ?>
                <button class="btn details-btn"
                  data-id="<?= htmlspecialchars($r['report_id']) ?>"
                  data-type="<?= htmlspecialchars($r['report_type'] ?? '') ?>"
                  data-status="<?= $r_status ?>"
                  data-reporter="<?= $reporter ?>"
                  data-reported-user="<?= $reportedUser ?>"
                  data-date="<?= $created_at ?>"
                  data-description="<?= htmlspecialchars($r['description'] ?? '') ?>"
                  data-content="<?= htmlspecialchars($r['reported_content'] ?? '') ?>"
                  data-priority="<?= htmlspecialchars($r['priority'] ?? 'â€”') ?>">
                  Details
                </button>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
        <div class="load-more-row">
          <button class="btn" id="loadMoreBtn">Load more</button>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div><!-- /.layout -->

<!-- â•â•â• DETAIL MODAL â•â•â• -->
<div class="modal-bg" id="detailModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Report Details</div>
      <button class="modal-close" id="closeModal">&#x2715;</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer" id="modalFooter"></div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast">
  <i id="toastIcon" class="fas fa-check-circle" style="color:var(--green-text)"></i>
  <span id="toastMsg">Done.</span>
</div>

<script>
// â”€â”€ Dark mode â”€â”€
const darkToggle = document.getElementById('darkToggle');
const darkIcon   = document.getElementById('darkIcon');
let dark = localStorage.getItem('tipeed_dark') === '1';
function applyDark() {
  document.body.setAttribute('data-theme', dark ? 'dark' : '');
  darkIcon.className = dark ? 'fas fa-sun' : 'fas fa-moon';
}
applyDark();
darkToggle.addEventListener('click', () => {
  dark = !dark;
  localStorage.setItem('tipeed_dark', dark ? '1' : '0');
  applyDark();
});

// â”€â”€ Toast â”€â”€
function showToast(msg, type) {
  const t  = document.getElementById('toast');
  const ti = document.getElementById('toastIcon');
  document.getElementById('toastMsg').textContent = msg;
  ti.className = type === 'ok' ? 'fas fa-check-circle' : 'fas fa-times-circle';
  ti.style.color = type === 'ok' ? 'var(--green-text)' : 'var(--red-text)';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// â”€â”€ Modal â”€â”€
function openModal()  { document.getElementById('detailModal').classList.add('open'); }
function closeModal() { document.getElementById('detailModal').classList.remove('open'); }
document.getElementById('closeModal').addEventListener('click', closeModal);
document.getElementById('detailModal').addEventListener('click', e => {
  if (e.target === document.getElementById('detailModal')) closeModal();
});

// â”€â”€ Filter â”€â”€
document.querySelectorAll('.filter-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterRows();
  });
});
document.getElementById('reportSearch').addEventListener('input', filterRows);

function filterRows() {
  const f = document.querySelector('.filter-btn.active').dataset.filter;
  const q = document.getElementById('reportSearch').value.toLowerCase();
  document.querySelectorAll('.report-row').forEach(row => {
    const statusMatch =
      f === 'all'           ? true :
      f === 'unread'        ? row.dataset.unread === '1' :
      f === 'pending'       ? row.dataset.status === 'pending' :
      f === 'resolved'      ? row.dataset.status === 'resolved' :
      f === 'inappropriate' ? row.dataset.type.includes('inappropriate') :
      f === 'spam'          ? row.dataset.type.includes('spam') :
      f === 'harassment'    ? row.dataset.type.includes('harassment') : true;
    const textMatch = !q || row.dataset.text.includes(q);
    row.style.display = (statusMatch && textMatch) ? '' : 'none';
  });
}

// â”€â”€ Details modal â”€â”€
document.querySelectorAll('.details-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const d = btn.dataset;
    document.getElementById('modalTitle').textContent = 'Report #' + d.id;

    const statusBadge = `<span class="badge ${d.status}">${d.status.charAt(0).toUpperCase() + d.status.slice(1)}</span>`;

    document.getElementById('modalBody').innerHTML = `
      <div class="detail-row"><div class="detail-key">Report ID</div><div class="detail-val" style="font-family:var(--mono)">#${d.id}</div></div>
      <div class="detail-row"><div class="detail-key">Type</div><div class="detail-val">${d.type || 'â€”'}</div></div>
      <div class="detail-row"><div class="detail-key">Status</div><div class="detail-val">${statusBadge}</div></div>
      <div class="detail-row"><div class="detail-key">Reported By</div><div class="detail-val">${d.reporter}</div></div>
      <div class="detail-row"><div class="detail-key">Reported User</div><div class="detail-val">${d.reportedUser}</div></div>
      <div class="detail-row"><div class="detail-key">Date</div><div class="detail-val" style="font-family:var(--mono);font-size:12px">${d.date}</div></div>
      <div class="detail-row"><div class="detail-key">Priority</div><div class="detail-val">${d.priority || 'â€”'}</div></div>
      ${d.description ? `<div class="content-box"><strong>Description</strong>${d.description}</div>` : ''}
      ${d.content     ? `<div class="content-box"><strong>Reported Content</strong>${d.content}</div>` : ''}`;

    const footer = document.getElementById('modalFooter');
    footer.innerHTML = d.status !== 'resolved'
      ? `<button class="btn" onclick="closeModal()">Close</button>
         <button class="btn danger" onclick="closeModal()"><i class="fas fa-trash"></i> Delete</button>
         <button class="btn resolve" onclick="closeModal()"><i class="fas fa-check"></i> Mark Resolved</button>`
      : `<button class="btn primary" onclick="closeModal()">Close</button>`;

    openModal();
  });
});

// â”€â”€ Resolve inline â”€â”€
document.querySelectorAll('.resolve-btn').forEach(btn => {
  btn.addEventListener('click', e => {
    e.stopPropagation();
    const id  = btn.dataset.id;
    const row = btn.closest('.report-row');

    // Optimistic update
    const badge = row.querySelector('.badge');
    if (badge) { badge.className = 'badge resolved'; badge.textContent = 'Resolved'; }
    btn.remove();
    row.dataset.status = 'resolved';
    row.classList.remove('is-unread');
    row.dataset.unread = '0';

    // Sync counts
    const pEl = document.getElementById('pendingCount');
    const rEl = document.getElementById('resolvedCount');
    if (pEl) pEl.textContent = Math.max(0, parseInt(pEl.textContent) - 1);
    if (rEl) rEl.textContent = parseInt(rEl.textContent) + 1;

    showToast('Report marked as resolved.', 'ok');
  });
});

// â”€â”€ Row click â†’ details â”€â”€
document.querySelectorAll('.report-row').forEach(row => {
  row.addEventListener('click', () => {
    const detailsBtn = row.querySelector('.details-btn');
    if (detailsBtn) detailsBtn.click();
  });
});
</script>
</body>
</html>