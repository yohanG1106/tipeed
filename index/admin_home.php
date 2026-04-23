<?php
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$admin_name = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$admin_initials = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
$admin_first = $_SESSION['first_name'];
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Home â€” TIPeed</title>
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
      --bg:         #f7f6f3;
      --surface:    #ffffff;
      --surface2:   #f0ede8;
      --border:     #e4e0d8;
      --border2:    #ccc9be;
      --text:       #1a1916;
      --text2:      #5c5a54;
      --text3:      #9c9a94;
      --accent-bg:  #1a1916;
      --accent-fg:  #f7f6f3;
      --tag-bg:     #eceae4;
      --tag-text:   #5c5a54;
      --red-bg:     #fef2f0;   --red-text:   #c0392b;
      --amber-bg:   #fef9ec;   --amber-text: #b07d2a;
      --green-bg:   #f0f9f4;   --green-text: #276749;
      --blue-bg:    #f0f5fe;   --blue-text:  #2563a8;
      --radius:     8px;
      --radius-lg:  12px;
      --radius-xl:  16px;
      --font:       'DM Sans', system-ui, sans-serif;
      --mono:       'DM Mono', monospace;
      --trans:      all 0.16s ease;
    }
    [data-theme="dark"] {
      --bg:         #141310;
      --surface:    #1e1c19;
      --surface2:   #252320;
      --border:     #2e2c28;
      --border2:    #3a3834;
      --text:       #f0ede8;
      --text2:      #a8a49c;
      --text3:      #6e6b64;
      --accent-bg:  #f0ede8;
      --accent-fg:  #1a1916;
      --tag-bg:     #252320;
      --tag-text:   #a8a49c;
      --red-bg:     #2a1614;   --red-text:   #f4826e;
      --amber-bg:   #251e0e;   --amber-text: #dba94a;
      --green-bg:   #0f1f16;   --green-text: #52c487;
      --blue-bg:    #0f1624;   --blue-text:  #6da4f4;
    }

    /* â”€â”€â”€ Base â”€â”€â”€ */
    body {
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      font-size: 14px;
      line-height: 1.55;
      min-height: 100vh;
    }

    /* â”€â”€â”€ Navbar â”€â”€â”€ */
    .navbar {
      display: flex;
      align-items: center;
      gap: 4px;
      padding: 0 24px;
      height: 52px;
      background: var(--surface);
      border-bottom: 0.5px solid var(--border);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .logo {
      font-size: 15px;
      font-weight: 500;
      letter-spacing: -0.3px;
      margin-right: 16px;
      flex-shrink: 0;
    }
    .nav-links { display: flex; gap: 2px; flex: 1; }
    .nav-links a {
      padding: 5px 10px;
      border-radius: var(--radius);
      font-size: 13px;
      color: var(--text2);
      transition: var(--trans);
    }
    .nav-links a:hover, .nav-links a.active {
      color: var(--text);
      background: var(--tag-bg);
    }
    .nav-right { display: flex; align-items: center; gap: 6px; margin-left: auto; }
    .search {
      display: flex;
      align-items: center;
      gap: 8px;
      background: var(--surface2);
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      padding: 5px 10px;
    }
    .search i { font-size: 11px; color: var(--text3); }
    .search input {
      border: none;
      background: transparent;
      outline: none;
      font-family: var(--font);
      font-size: 13px;
      color: var(--text);
      width: 160px;
    }
    .search input::placeholder { color: var(--text3); }
    .icon-btn {
      width: 30px;
      height: 30px;
      border: 0.5px solid var(--border);
      background: transparent;
      border-radius: var(--radius);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--text2);
      font-size: 13px;
      transition: var(--trans);
    }
    .icon-btn:hover { background: var(--tag-bg); color: var(--text); }
    .notif-wrap { position: relative; }
    .notif-badge {
      position: absolute;
      top: 5px;
      right: 5px;
      width: 6px;
      height: 6px;
      background: var(--red-text);
      border-radius: 50%;
      border: 1.5px solid var(--surface);
    }

    /* â”€â”€â”€ Layout â”€â”€â”€ */
    .layout {
      display: grid;
      grid-template-columns: 220px 1fr;
      min-height: calc(100vh - 52px);
    }

    /* â”€â”€â”€ Sidebar â”€â”€â”€ */
    .sidebar {
      background: var(--surface);
      border-right: 0.5px solid var(--border);
      padding: 16px 0;
      position: sticky;
      top: 52px;
      height: calc(100vh - 52px);
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }
    .sidebar::-webkit-scrollbar { width: 3px; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }
    .profile-block {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 0 16px 16px;
      border-bottom: 0.5px solid var(--border);
      margin-bottom: 8px;
    }
    .avatar {
      width: 34px;
      height: 34px;
      border-radius: 50%;
      background: var(--accent-bg);
      color: var(--accent-fg);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 500;
      letter-spacing: 0.3px;
      flex-shrink: 0;
      font-family: var(--mono);
    }
    .profile-name { font-size: 13px; font-weight: 500; }
    .profile-role { font-size: 11px; color: var(--text3); margin-top: 1px; }
    .nav-section-label {
      padding: 10px 16px 4px;
      font-size: 10px;
      font-weight: 500;
      color: var(--text3);
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 7px 16px;
      font-size: 13px;
      color: var(--text2);
      border-left: 2px solid transparent;
      transition: var(--trans);
      cursor: pointer;
    }
    .menu-item:hover { color: var(--text); background: var(--surface2); }
    .menu-item.active { color: var(--text); background: var(--surface2); border-left-color: var(--text); }
    .menu-item i { width: 14px; font-size: 12px; flex-shrink: 0; text-align: center; }
    .sidebar-bottom {
      margin-top: auto;
      padding: 12px 16px;
      border-top: 0.5px solid var(--border);
    }
    .sidebar-bottom a {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 12px;
      color: var(--text3);
      padding: 6px 0;
      transition: var(--trans);
    }
    .sidebar-bottom a:hover { color: var(--red-text); }
    .sidebar-bottom i { font-size: 12px; }

    /* â”€â”€â”€ Main â”€â”€â”€ */
    .main { padding: 24px 28px; overflow-y: auto; }

    /* â”€â”€â”€ Top row â”€â”€â”€ */
    .top-row {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 24px;
      flex-wrap: wrap;
    }
    .welcome h1 { font-size: 18px; font-weight: 500; letter-spacing: -0.3px; }
    .welcome p { font-size: 13px; color: var(--text3); margin-top: 2px; }
    .admin-btns { display: flex; gap: 6px; flex-wrap: wrap; }
    .admin-btn {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 7px 13px;
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      font-size: 12px;
      font-weight: 500;
      color: var(--text2);
      transition: var(--trans);
    }
    .admin-btn:hover { background: var(--surface2); color: var(--text); border-color: var(--border2); }
    .admin-btn i { font-size: 11px; }
    .admin-btn.primary {
      background: var(--accent-bg);
      color: var(--accent-fg);
      border-color: var(--accent-bg);
    }
    .admin-btn.primary:hover { opacity: 0.85; }

    /* â”€â”€â”€ Content grid â”€â”€â”€ */
    .content-grid {
      display: grid;
      grid-template-columns: 1fr 300px;
      gap: 20px;
      align-items: start;
    }
    .left-col { display: flex; flex-direction: column; gap: 16px; }
    .right-col { display: flex; flex-direction: column; gap: 16px; }

    /* â”€â”€â”€ Card â”€â”€â”€ */
    .card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 20px;
    }
    .card-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 16px;
    }
    .card-title { font-size: 13px; font-weight: 500; }
    .card-sub { font-size: 11px; color: var(--text3); margin-top: 2px; }
    .card-action {
      font-size: 12px;
      color: var(--text3);
      border: 0.5px solid var(--border);
      background: transparent;
      padding: 4px 10px;
      border-radius: var(--radius);
      transition: var(--trans);
      flex-shrink: 0;
    }
    .card-action:hover { background: var(--tag-bg); color: var(--text); }

    /* â”€â”€â”€ Notifications â”€â”€â”€ */
    .notif-list { display: flex; flex-direction: column; }
    .notif-item {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      padding: 10px 0;
      border-bottom: 0.5px solid var(--border);
    }
    .notif-item:last-child { border-bottom: none; padding-bottom: 0; }
    .notif-icon {
      width: 28px;
      height: 28px;
      border-radius: var(--radius);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      flex-shrink: 0;
    }
    .notif-icon.red   { background: var(--red-bg);   color: var(--red-text); }
    .notif-icon.amber { background: var(--amber-bg); color: var(--amber-text); }
    .notif-icon.blue  { background: var(--blue-bg);  color: var(--blue-text); }
    .notif-body { flex: 1; min-width: 0; }
    .notif-title { font-size: 12px; font-weight: 500; }
    .notif-msg   { font-size: 12px; color: var(--text2); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .notif-time  { font-size: 10px; color: var(--text3); margin-top: 3px; font-family: var(--mono); }
    .unread-dot  { width: 5px; height: 5px; background: var(--red-text); border-radius: 50%; flex-shrink: 0; margin-top: 6px; }

    /* â”€â”€â”€ Announcements â”€â”€â”€ */
    .announce-list { display: flex; flex-direction: column; }
    .announce-item {
      display: flex;
      gap: 12px;
      align-items: flex-start;
      padding: 11px 0;
      border-bottom: 0.5px solid var(--border);
    }
    .announce-item:last-child { border-bottom: none; padding-bottom: 0; }
    .ann-type {
      padding: 2px 7px;
      border-radius: 4px;
      font-size: 10px;
      font-weight: 500;
      letter-spacing: 0.03em;
      flex-shrink: 0;
      margin-top: 2px;
      font-family: var(--mono);
    }
    .ann-type.general  { background: var(--tag-bg);   color: var(--tag-text); }
    .ann-type.important{ background: var(--amber-bg); color: var(--amber-text); }
    .ann-type.urgent   { background: var(--red-bg);   color: var(--red-text); }
    .ann-title { font-size: 13px; font-weight: 500; line-height: 1.4; }
    .ann-body  { font-size: 12px; color: var(--text2); margin-top: 3px; line-height: 1.5; }
    .ann-meta  { font-size: 10px; color: var(--text3); margin-top: 4px; font-family: var(--mono); }

    /* â”€â”€â”€ Logs â”€â”€â”€ */
    .log-list { display: flex; flex-direction: column; }
    .log-item {
      display: grid;
      grid-template-columns: 8px 1fr auto;
      gap: 10px;
      align-items: center;
      padding: 8px 0;
      border-bottom: 0.5px solid var(--border);
    }
    .log-item:last-child { border-bottom: none; }
    .log-dot { width: 6px; height: 6px; border-radius: 50%; flex-shrink: 0; }
    .log-dot.green { background: var(--green-text); }
    .log-dot.amber { background: var(--amber-text); }
    .log-dot.red   { background: var(--red-text); }
    .log-dot.blue  { background: var(--blue-text); }
    .log-text      { font-size: 12px; color: var(--text2); }
    .log-text strong { color: var(--text); font-weight: 500; }
    .log-time      { font-size: 10px; color: var(--text3); font-family: var(--mono); white-space: nowrap; }

    /* â”€â”€â”€ Calendar â”€â”€â”€ */
    .cal-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
    .cal-nav { display: flex; gap: 4px; }
    .cal-nav-btn {
      width: 26px;
      height: 26px;
      border: 0.5px solid var(--border);
      background: transparent;
      border-radius: var(--radius);
      font-size: 10px;
      color: var(--text2);
      display: flex;
      align-items: center;
      justify-content: center;
      transition: var(--trans);
    }
    .cal-nav-btn:hover { background: var(--tag-bg); color: var(--text); }
    .cal-today-btn {
      height: 26px;
      padding: 0 8px;
      border: 0.5px solid var(--border);
      background: transparent;
      border-radius: var(--radius);
      font-size: 11px;
      font-family: var(--font);
      color: var(--text2);
      transition: var(--trans);
    }
    .cal-today-btn:hover { background: var(--tag-bg); color: var(--text); }
    .cal-grid {
      display: grid;
      grid-template-columns: repeat(7, 1fr);
      gap: 2px;
    }
    .cal-dow {
      font-size: 10px;
      color: var(--text3);
      text-align: center;
      padding: 3px 0;
      font-family: var(--mono);
    }
    .cal-day {
      aspect-ratio: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      border-radius: var(--radius);
      cursor: pointer;
      transition: var(--trans);
      font-family: var(--mono);
      color: var(--text2);
      position: relative;
    }
    .cal-day:hover { background: var(--tag-bg); color: var(--text); }
    .cal-day.today { background: var(--accent-bg); color: var(--accent-fg); font-weight: 500; }
    .cal-day.has-event::after {
      content: '';
      position: absolute;
      bottom: 3px;
      left: 50%;
      transform: translateX(-50%);
      width: 3px;
      height: 3px;
      border-radius: 50%;
      background: var(--amber-text);
    }
    .cal-day.other-month { color: var(--text3); opacity: 0.4; }

    /* â”€â”€â”€ Events â”€â”€â”€ */
    .event-item {
      display: flex;
      gap: 12px;
      padding: 8px 0;
      border-bottom: 0.5px solid var(--border);
    }
    .event-item:last-child { border-bottom: none; }
    .event-date { font-family: var(--mono); font-size: 10px; color: var(--text3); min-width: 34px; padding-top: 2px; }
    .event-title { font-size: 12px; font-weight: 500; }
    .event-desc  { font-size: 11px; color: var(--text3); margin-top: 1px; }

    /* â”€â”€â”€ Modal â”€â”€â”€ */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.35);
      z-index: 200;
      display: none;
      align-items: center;
      justify-content: center;
    }
    .modal-overlay.open { display: flex; }
    .modal {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 24px;
      width: 420px;
      max-width: 90vw;
    }
    .modal-title { font-size: 15px; font-weight: 500; margin-bottom: 4px; }
    .modal-sub   { font-size: 12px; color: var(--text3); margin-bottom: 20px; }
    .form-group  { margin-bottom: 14px; }
    .form-label  { display: block; font-size: 12px; font-weight: 500; color: var(--text2); margin-bottom: 5px; }
    .form-input,
    .form-textarea,
    .form-select {
      width: 100%;
      padding: 7px 10px;
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      background: var(--surface2);
      color: var(--text);
      font-family: var(--font);
      font-size: 13px;
      outline: none;
      transition: var(--trans);
    }
    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus { border-color: var(--border2); background: var(--surface); }
    .form-textarea { min-height: 80px; resize: vertical; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
    .btn {
      padding: 7px 14px;
      border-radius: var(--radius);
      font-size: 13px;
      font-weight: 500;
      font-family: var(--font);
      border: 0.5px solid var(--border);
      background: var(--surface);
      color: var(--text2);
      cursor: pointer;
      transition: var(--trans);
    }
    .btn:hover { background: var(--tag-bg); color: var(--text); }
    .btn.primary { background: var(--accent-bg); color: var(--accent-fg); border-color: var(--accent-bg); }
    .btn.primary:hover { opacity: 0.85; }

    /* â”€â”€â”€ Toast â”€â”€â”€ */
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 10px 16px;
      display: none;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: var(--text);
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
      z-index: 300;
    }
    .toast.show { display: flex; }
    .toast i { color: var(--green-text); font-size: 14px; }

    /* â”€â”€â”€ Responsive â”€â”€â”€ */
    @media (max-width: 900px) {
      .content-grid { grid-template-columns: 1fr; }
      .layout { grid-template-columns: 1fr; }
      .sidebar { display: none; }
    }
  </style>
</head>
<body>

<!-- â•â•â• NAVBAR â•â•â• -->
<nav class="navbar">
  <div class="logo">TIPeed</div>
  <div class="nav-links">
    <a href="admin_home.php" class="active">Home</a>
    <?php if ($currentUserRole === 'admin'): ?>
      <a href="student_home.php">Thread</a>
      <a href="faculty_chats.php">Faculty</a>
    <?php endif; ?>
    <a href="community.php">Community</a>
    <a href="aboutus.php">About Us</a>
  </div>
  <div class="nav-right">
    <div class="search">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search topicsâ€¦">
    </div>
    <div class="notif-wrap">
      <button class="icon-btn" title="Notifications" onclick="window.location.href='notifications.php'">
        <i class="fas fa-bell"></i>
      </button>
      <span class="notif-badge"></span>
    </div>
    <button class="icon-btn" id="darkToggle" title="Toggle dark mode">
      <i class="fas fa-moon" id="darkIcon"></i>
    </button>
  </div>
</nav>

<!-- â•â•â• LAYOUT â•â•â• -->
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="profile-block">
      <div class="avatar"><?php echo $admin_initials; ?></div>
      <div>
        <div class="profile-name"><?php echo $admin_name; ?></div>
        <div class="profile-role">Administrator</div>
      </div>
    </div>

    <div class="nav-section-label">General</div>
    <a href="admin_home.php" class="menu-item active"><i class="fas fa-home"></i> Home</a>
    <a href="profile.php"    class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="coursechat.php"     class="menu-item"><i class="fas fa-comments"></i> Communities Chat</a>
    <a href="community.php"      class="menu-item"><i class="fas fa-users"></i> Community</a>

    <div class="nav-section-label">Admin</div>
    <a href="admin_reg.php"  class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
   

    <div class="nav-section-label">Support</div>
     <a href="calendar.php"   class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <div class="menu-item"><i class="fas fa-cog"></i> Settings</div>
    <a href="Help.php" class="menu-item"><i class="fas fa-question-circle"></i> Help</a>

    <div class="sidebar-bottom">
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- Top row -->
    <div class="top-row">
      <div class="welcome">
        <h1>Welcome back, <?php echo $admin_first; ?></h1>
        <p>Here's what's happening in your community today.</p>
      </div>
      <div class="admin-btns">
        <button class="admin-btn" onclick="window.location.href='Tickets.php'">
          <i class="fas fa-hand-paper"></i> User Tickets
        </button>
        <button class="admin-btn" onclick="window.location.href='approval.php'">
          <i class="fas fa-check-circle"></i> Approval
        </button>
        <button class="admin-btn primary" onclick="window.location.href='report.php'">
          <i class="fas fa-chart-bar"></i> Report
        </button>
      </div>
    </div>

    <!-- Content grid -->
    <div class="content-grid">
      <div class="left-col">

        <!-- Notifications -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Notifications</div>
              <div class="card-sub">3 unread items</div>
            </div>
            <button class="card-action" onclick="window.location.href='notifications.php'">View all</button>
          </div>
          <div class="notif-list">
            <div class="notif-item">
              <div class="notif-icon red"><i class="fas fa-file-alt"></i></div>
              <div class="notif-body">
                <div class="notif-title">New Assignment</div>
                <div class="notif-msg">CIT 201 â€” Assignment 3 posted</div>
                <div class="notif-time">2 hours ago</div>
              </div>
              <div class="unread-dot"></div>
            </div>
            <div class="notif-item">
              <div class="notif-icon amber"><i class="fas fa-bullhorn"></i></div>
              <div class="notif-body">
                <div class="notif-title">Course Announcement</div>
                <div class="notif-msg">IT Briefing 2 â€” Class canceled</div>
                <div class="notif-time">5 hours ago</div>
              </div>
            </div>
            <div class="notif-item">
              <div class="notif-icon blue"><i class="fas fa-comments"></i></div>
              <div class="notif-body">
                <div class="notif-title">New Message</div>
                <div class="notif-msg">Jane sent you a message</div>
                <div class="notif-time">Yesterday</div>
              </div>
              <div class="unread-dot"></div>
            </div>
          </div>
        </div>

        <!-- Announcements -->
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title">Public Announcements</div>
              <div class="card-sub" id="annCount">3 active</div>
            </div>
            <button class="card-action" id="createAnnouncementBtn">
              <i class="fas fa-plus" style="font-size:10px;margin-right:4px"></i>Create
            </button>
          </div>
          <div class="announce-list" id="announcementsList">
            <div class="announce-item">
              <span class="ann-type urgent">urgent</span>
              <div>
                <div class="ann-title">Server Maintenance â€” Apr 14</div>
                <div class="ann-body">The TIPeed platform will be offline for scheduled maintenance from 11 PM to 2 AM.</div>
                <div class="ann-meta">Posted by <?php echo $admin_name; ?> Â· Apr 10, 2025 Â· 09:14</div>
              </div>
            </div>
            <div class="announce-item">
              <span class="ann-type important">important</span>
              <div>
                <div class="ann-title">End-of-Semester Exam Schedule</div>
                <div class="ann-body">Final examination schedules for all courses are now available on the portal.</div>
                <div class="ann-meta">Posted by <?php echo $admin_name; ?> Â· Apr 8, 2025 Â· 14:30</div>
              </div>
            </div>
            <div class="announce-item">
              <span class="ann-type general">general</span>
              <div>
                <div class="ann-title">Community Forum now open</div>
                <div class="ann-body">Students can now post and reply in the new community forum section.</div>
                <div class="ann-meta">Posted by <?php echo $admin_name; ?> Â· Apr 5, 2025 Â· 10:00</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Logs -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Activity Logs</div>
          </div>
          <div class="log-list">
            <div class="log-item">
              <span class="log-dot green"></span>
              <div class="log-text"><strong>Maria Santos</strong> registered a new account</div>
              <div class="log-time">09:42</div>
            </div>
            <div class="log-item">
              <span class="log-dot blue"></span>
              <div class="log-text"><strong><?php echo $admin_name; ?></strong> approved student enrollment</div>
              <div class="log-time">09:18</div>
            </div>
            <div class="log-item">
              <span class="log-dot amber"></span>
              <div class="log-text"><strong>Juan Cruz</strong> submitted a support ticket</div>
              <div class="log-time">08:55</div>
            </div>
            <div class="log-item">
              <span class="log-dot red"></span>
              <div class="log-text"><strong>3 failed login attempts</strong> on user account #4421</div>
              <div class="log-time">08:30</div>
            </div>
            <div class="log-item">
              <span class="log-dot green"></span>
              <div class="log-text"><strong>Rosa Dela Torre</strong> posted an announcement</div>
              <div class="log-time">Yesterday</div>
            </div>
          </div>
        </div>

      </div><!-- /.left-col -->

      <div class="right-col">

        <!-- Calendar -->
        <div class="card">
          <div class="cal-header">
            <div class="card-title" id="calTitle">April 2025</div>
            <div class="cal-nav">
              <button class="cal-nav-btn" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
              <button class="cal-today-btn" id="todayBtn">Today</button>
              <button class="cal-nav-btn" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
            </div>
          </div>
          <div class="cal-grid" id="calGrid"></div>
        </div>

        <!-- Upcoming Events -->
        <div class="card">
          <div class="card-header">
            <div class="card-title">Upcoming Events</div>
          </div>
          <div id="eventsList">
            <div class="event-item">
              <div class="event-date">Apr 14</div>
              <div>
                <div class="event-title">Server Maintenance</div>
                <div class="event-desc">11 PM â€“ 2 AM downtime window</div>
              </div>
            </div>
            <div class="event-item">
              <div class="event-date">Apr 18</div>
              <div>
                <div class="event-title">Final Exam Period Begins</div>
                <div class="event-desc">All departments</div>
              </div>
            </div>
            <div class="event-item">
              <div class="event-date">Apr 28</div>
              <div>
                <div class="event-title">Enrollment Opening</div>
                <div class="event-desc">AY 2025â€“2026</div>
              </div>
            </div>
          </div>
        </div>

      </div><!-- /.right-col -->
    </div><!-- /.content-grid -->

  </main>
</div><!-- /.layout -->

<!-- â•â•â• ANNOUNCEMENT MODAL â•â•â• -->
<div class="modal-overlay" id="announcementModal">
  <div class="modal">
    <div class="modal-title">Create Announcement</div>
    <div class="modal-sub">Published immediately to all users.</div>
    <div class="form-group">
      <label class="form-label" for="annTitle">Title</label>
      <input type="text" id="annTitle" class="form-input" placeholder="Announcement titleâ€¦">
    </div>
    <div class="form-group">
      <label class="form-label" for="annContent">Content</label>
      <textarea id="annContent" class="form-textarea" placeholder="Write your announcementâ€¦"></textarea>
    </div>
    <div class="form-group">
      <label class="form-label" for="annType">Type</label>
      <select id="annType" class="form-select">
        <option value="general">General</option>
        <option value="important">Important</option>
        <option value="urgent">Urgent</option>
      </select>
    </div>
    <div class="modal-footer">
      <button class="btn" id="cancelAnn">Cancel</button>
      <button class="btn primary" id="submitAnn">Publish</button>
    </div>
  </div>
</div>

<!-- â•â•â• TOAST â•â•â• -->
<div class="toast" id="toast">
  <i class="fas fa-check-circle"></i>
  <span id="toastMsg">Announcement published.</span>
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

// â”€â”€ Calendar â”€â”€
const MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const DAYS   = ['Su','Mo','Tu','We','Th','Fr','Sa'];
const EVENT_DAYS = { 3: [14, 18, 28] }; // month index: [days with events]

let cur = new Date();
cur.setDate(1);

function buildCal() {
  const grid  = document.getElementById('calGrid');
  const title = document.getElementById('calTitle');
  const today = new Date();
  const y = cur.getFullYear(), m = cur.getMonth();
  title.textContent = MONTHS[m] + ' ' + y;

  let html = DAYS.map(d => `<div class="cal-dow">${d}</div>`).join('');

  const firstDay = new Date(y, m, 1).getDay();
  const daysInMonth = new Date(y, m + 1, 0).getDate();
  const prevDays    = new Date(y, m, 0).getDate();
  const eventDays   = new Set((EVENT_DAYS[m] || []));

  for (let i = 0; i < firstDay; i++) {
    html += `<div class="cal-day other-month">${prevDays - firstDay + 1 + i}</div>`;
  }
  for (let d = 1; d <= daysInMonth; d++) {
    const isToday  = today.getFullYear() === y && today.getMonth() === m && today.getDate() === d;
    const hasEvent = eventDays.has(d);
    html += `<div class="cal-day${isToday ? ' today' : ''}${hasEvent ? ' has-event' : ''}">${d}</div>`;
  }
  const rem = 42 - (firstDay + daysInMonth);
  for (let i = 1; i <= rem; i++) html += `<div class="cal-day other-month">${i}</div>`;

  grid.innerHTML = html;
}

document.getElementById('prevMonth').addEventListener('click', () => { cur.setMonth(cur.getMonth() - 1); buildCal(); });
document.getElementById('nextMonth').addEventListener('click', () => { cur.setMonth(cur.getMonth() + 1); buildCal(); });
document.getElementById('todayBtn').addEventListener('click', () => { cur = new Date(); cur.setDate(1); buildCal(); });
buildCal();

// â”€â”€ Announcement modal â”€â”€
const modal     = document.getElementById('announcementModal');
const createBtn = document.getElementById('createAnnouncementBtn');
const cancelBtn = document.getElementById('cancelAnn');
const submitBtn = document.getElementById('submitAnn');
const toast     = document.getElementById('toast');
const toastMsg  = document.getElementById('toastMsg');

function showToast(msg, isError) {
  toastMsg.textContent = msg;
  toast.querySelector('i').style.color = isError ? 'var(--red-text)' : 'var(--green-text)';
  toast.classList.add('show');
  setTimeout(() => toast.classList.remove('show'), 2800);
}

createBtn.addEventListener('click', () => modal.classList.add('open'));
cancelBtn.addEventListener('click',  () => modal.classList.remove('open'));
modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });

submitBtn.addEventListener('click', () => {
  const title   = document.getElementById('annTitle').value.trim();
  const content = document.getElementById('annContent').value.trim();
  const type    = document.getElementById('annType').value;

  if (!title || !content) { showToast('Please fill in all fields.', true); return; }

  const typeClass = { general: 'general', important: 'important', urgent: 'urgent' };
  const now = new Date();
  const dateStr = now.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })
    + ' Â· ' + now.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });

  const list = document.getElementById('announcementsList');
  const item = document.createElement('div');
  item.className = 'announce-item';
  item.innerHTML = `
    <span class="ann-type ${typeClass[type]}">${type}</span>
    <div>
      <div class="ann-title">${title}</div>
      <div class="ann-body">${content}</div>
      <div class="ann-meta">Posted by <?php echo addslashes($admin_name); ?> Â· ${dateStr}</div>
    </div>`;
  list.prepend(item);

  // Update count
  const count = list.querySelectorAll('.announce-item').length;
  document.getElementById('annCount').textContent = count + ' active';

  document.getElementById('annTitle').value   = '';
  document.getElementById('annContent').value = '';
  modal.classList.remove('open');
  showToast('Announcement published.');
});
</script>
</body>
</html>