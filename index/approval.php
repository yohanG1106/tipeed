<?php
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid']) || $_SESSION['role'] !== 'admin') {
    header("Location: auth.php");
    exit();
}

$admin_name    = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$admin_initials = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} elseif ($currentUserRole === 'faculty') {
    $homePage = 'faculty_home.php';
} else {
    $homePage = 'student_home.php';
}

$studentName = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$yearLevel   = isset($_SESSION['year_level']) ? $_SESSION['year_level'] : null;
$role        = isset($_SESSION['role']) ? $_SESSION['role'] : "student";

function ordinal($n) {
    $e = ['th','st','nd','rd','th','th','th','th','th','th'];
    if (($n % 100) >= 11 && ($n % 100) <= 13) return $n . 'th';
    return $n . $e[$n % 10];
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Community Approvals â€” TIPeed</title>
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
      --gold-dark: #c48f00;
      --gold-bg:   #fef9ec;
      --gold-text: #b07d2a;
      --red-bg:    #fef2f0;  --red-text:   #c0392b;
      --green-bg:  #f0f9f4;  --green-text: #276749;
      --blue-bg:   #f0f5fe;  --blue-text:  #2563a8;
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

    /* â”€â”€â”€ Page header â”€â”€â”€ */
    .page-header { margin-bottom: 24px; }
    .page-eyebrow {
      font-size: 11px; font-weight: 500; color: var(--purple-text);
      text-transform: uppercase; letter-spacing: 0.08em;
      margin-bottom: 4px; font-family: var(--mono);
    }
    .page-title-row {
      display: flex; align-items: center;
      justify-content: space-between; gap: 16px; flex-wrap: wrap;
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
    .stats-strip {
      display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;
    }
    .stat-card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 14px 20px; min-width: 100px;
      display: flex; flex-direction: column; gap: 2px;
    }
    .stat-num {
      font-size: 22px; font-weight: 500;
      font-family: var(--mono); color: var(--text);
    }
    .stat-num.gold   { color: var(--gold-text); }
    .stat-num.green  { color: var(--green-text); }
    .stat-num.red    { color: var(--red-text); }
    .stat-label { font-size: 11px; color: var(--text3); }

    /* â”€â”€â”€ Filter bar â”€â”€â”€ */
    .filter-bar {
      display: flex; gap: 4px; margin-bottom: 16px;
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      padding: 5px; width: fit-content;
    }
    .filter-btn {
      padding: 5px 12px; border-radius: 6px;
      font-size: 12px; font-weight: 500;
      border: none; background: transparent;
      color: var(--text2); cursor: pointer; transition: var(--trans);
    }
    .filter-btn:hover { color: var(--text); background: var(--tag-bg); }
    .filter-btn.active { background: var(--accent-bg); color: var(--accent-fg); }

    /* â”€â”€â”€ Approval list â”€â”€â”€ */
    .approvals-card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }

    .approval-item {
      display: flex; gap: 14px; align-items: flex-start;
      padding: 16px 20px;
      border-bottom: 0.5px solid var(--border);
      transition: var(--trans); cursor: pointer;
    }
    .approval-item:last-child { border-bottom: none; }
    .approval-item:hover { background: var(--surface2); }
    .approval-item.is-pending { border-left: 2px solid var(--gold); }

    .item-icon {
      width: 36px; height: 36px; border-radius: var(--radius);
      background: var(--purple-bg); color: var(--purple-text);
      display: flex; align-items: center; justify-content: center;
      font-size: 14px; flex-shrink: 0; margin-top: 1px;
    }
    .item-body { flex: 1; min-width: 0; }
    .item-top {
      display: flex; align-items: flex-start;
      justify-content: space-between; gap: 12px; margin-bottom: 6px;
    }
    .item-title { font-size: 13px; font-weight: 500; }
    .item-desc { font-size: 12px; color: var(--text2); line-height: 1.5; margin-bottom: 8px; }

    .item-preview {
      background: var(--surface2);
      border: 0.5px solid var(--border);
      border-left: 2px solid var(--purple-text);
      border-radius: var(--radius);
      padding: 10px 12px; margin-bottom: 10px;
    }
    .item-preview-name { font-size: 12px; font-weight: 500; margin-bottom: 3px; }
    .item-preview-desc { font-size: 12px; color: var(--text2); margin-bottom: 6px; line-height: 1.5; }
    .item-preview-meta { display: flex; gap: 12px; font-size: 11px; color: var(--text3); font-family: var(--mono); }

    .user-row { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
    .user-av {
      width: 26px; height: 26px; border-radius: 50%;
      background: var(--gold-bg); color: var(--gold-text);
      display: flex; align-items: center; justify-content: center;
      font-size: 9px; font-weight: 500; flex-shrink: 0; font-family: var(--mono);
    }
    .user-name-sm { font-size: 12px; font-weight: 500; color: var(--text); }
    .user-email-sm { font-size: 11px; color: var(--text3); font-family: var(--mono); }

    .item-footer {
      display: flex; align-items: center;
      justify-content: space-between; gap: 10px; flex-wrap: wrap;
    }
    .item-meta { display: flex; align-items: center; gap: 8px; }
    .item-time { font-size: 10px; color: var(--text3); font-family: var(--mono); }
    .item-actions { display: flex; gap: 6px; }

    /* â”€â”€â”€ Badges â”€â”€â”€ */
    .badge {
      display: inline-flex; align-items: center;
      padding: 2px 7px; border-radius: 4px;
      font-size: 10px; font-weight: 500; font-family: var(--mono);
    }
    .badge.pending  { background: var(--gold-bg);   color: var(--gold-text); }
    .badge.approved { background: var(--green-bg);  color: var(--green-text); }
    .badge.rejected { background: var(--red-bg);    color: var(--red-text); }
    .badge.type     { background: var(--purple-bg); color: var(--purple-text); }

    /* â”€â”€â”€ Buttons â”€â”€â”€ */
    .btn {
      padding: 6px 12px; border-radius: var(--radius);
      font-size: 12px; font-weight: 500; font-family: var(--font);
      border: 0.5px solid var(--border);
      background: var(--surface); color: var(--text2);
      cursor: pointer; transition: var(--trans);
    }
    .btn:hover { background: var(--tag-bg); color: var(--text); }
    .btn.approve {
      background: var(--green-bg); color: var(--green-text);
      border-color: rgba(39,103,73,0.2);
    }
    .btn.approve:hover { background: var(--green-text); color: #fff; }
    .btn.reject {
      background: var(--red-bg); color: var(--red-text);
      border-color: rgba(192,57,43,0.2);
    }
    .btn.reject:hover { background: var(--red-text); color: #fff; }
    .btn.primary { background: var(--accent-bg); color: var(--accent-fg); border-color: var(--accent-bg); }
    .btn.primary:hover { opacity: 0.85; }

    /* â”€â”€â”€ Empty state â”€â”€â”€ */
    .empty-state {
      padding: 60px 24px; text-align: center;
    }
    .empty-icon {
      width: 48px; height: 48px; border-radius: 50%;
      background: var(--tag-bg); color: var(--text3);
      display: flex; align-items: center; justify-content: center;
      font-size: 20px; margin: 0 auto 14px;
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
      padding: 18px 22px 16px;
      border-bottom: 0.5px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; background: var(--surface); z-index: 1;
    }
    .modal-title  { font-size: 15px; font-weight: 500; }
    .modal-close  {
      width: 26px; height: 26px; border: none; background: transparent;
      border-radius: var(--radius); color: var(--text3);
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; cursor: pointer; transition: var(--trans);
    }
    .modal-close:hover { background: var(--tag-bg); color: var(--text); }
    .modal-body { padding: 20px 22px; }
    .detail-row {
      display: flex; gap: 16px; padding: 8px 0;
      border-bottom: 0.5px solid var(--border); font-size: 13px;
    }
    .detail-row:last-of-type { border-bottom: none; }
    .detail-key   { font-weight: 500; color: var(--text2); min-width: 120px; flex-shrink: 0; }
    .detail-val   { color: var(--text); flex: 1; word-break: break-all; }
    .content-box {
      background: var(--surface2);
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      padding: 12px 14px; margin: 10px 0;
      font-size: 13px; color: var(--text2); line-height: 1.6;
    }
    .content-box strong { color: var(--text); font-weight: 500; display: block; margin-bottom: 4px; }
    .modal-footer {
      padding: 14px 22px;
      border-top: 0.5px solid var(--border);
      display: flex; justify-content: flex-end; gap: 8px;
      position: sticky; bottom: 0; background: var(--surface);
    }

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
    <a href="community.php">Community</a>
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

  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="profile-block">
      <div class="avatar"><?php echo $admin_initials; ?></div>
      <div>
        <div class="profile-name"><?php echo $studentName; ?></div>
        <div class="profile-role"><?php echo $studentIDT; ?></div>
      </div>
    </div>

    <div class="nav-section-label">General</div>
    <a href="profile.php"        class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>"   class="menu-item"><i class="fas fa-home"></i> Home</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="coursechat.php"     class="menu-item"><i class="fas fa-comments"></i> Communities Chat</a>
    <a href="community.php"      class="menu-item"><i class="fas fa-users"></i> Community</a>

    <?php if ($currentUserRole === 'admin'): ?>
    <div class="nav-section-label">Admin</div>
    <a href="admin_reg.php"  class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    
    <?php endif; ?>

    <div class="nav-section-label">Support</div>
    <a href="calendar.php"   class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <div class="menu-item"><i class="fas fa-cog"></i> Settings</div>
    <a href="Help.php"       class="menu-item"><i class="fas fa-question-circle"></i> Help</a>

    <div class="sidebar-bottom">
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- Header -->
    <div class="page-header">
      <div class="page-eyebrow">Admin Â· Community Management</div>
      <div class="page-title-row">
        <div class="page-title">Community Approvals</div>
        <a href="<?= $homePage ?>" class="back-link">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-strip">
      <div class="stat-card">
        <div class="stat-num" id="totalCount">â€”</div>
        <div class="stat-label">Total</div>
      </div>
      <div class="stat-card">
        <div class="stat-num gold" id="pendingCount">â€”</div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-card">
        <div class="stat-num green" id="approvedCount">â€”</div>
        <div class="stat-label">Approved</div>
      </div>
      <div class="stat-card">
        <div class="stat-num red" id="rejectedCount">â€”</div>
        <div class="stat-label">Rejected</div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <button class="filter-btn active" data-filter="all">All</button>
      <button class="filter-btn" data-filter="pending">Pending</button>
      <button class="filter-btn" data-filter="approved">Approved</button>
      <button class="filter-btn" data-filter="rejected">Rejected</button>
    </div>

    <!-- List card -->
    <div class="approvals-card">
      <div id="approvalList">
        <!-- populated by JS -->
      </div>
      <div class="load-more-row" id="loadMoreRow" style="display:none">
        <button class="btn" id="loadMoreBtn">Load more</button>
      </div>
    </div>

  </main>
</div><!-- /.layout -->

<!-- â•â•â• DETAIL MODAL â•â•â• -->
<div class="modal-bg" id="detailModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Community Details</div>
      <button class="modal-close" id="closeModal">&#x2715;</button>
    </div>
    <div class="modal-body" id="modalBody"></div>
    <div class="modal-footer" id="modalFooter"></div>
  </div>
</div>

<!-- â•â•â• TOAST â•â•â• -->
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
  ti.className = type === 'ok'
    ? 'fas fa-check-circle'
    : type === 'warn'
    ? 'fas fa-exclamation-circle'
    : 'fas fa-times-circle';
  ti.style.color = type === 'ok'
    ? 'var(--green-text)'
    : type === 'warn'
    ? 'var(--gold-text)'
    : 'var(--red-text)';
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// â”€â”€ State â”€â”€
let allApprovals = [];
let currentFilter = 'all';
let currentModalId = null;

// â”€â”€ Init â”€â”€
async function init() {
  await loadApprovals();
  bindFilters();
  bindModal();
}

async function loadApprovals() {
  try {
    const res  = await fetch('fetch_pending_communities.php');
    const data = await res.json();
    if (data.success) {
      allApprovals = data.communities;
    } else {
      allApprovals = [];
    }
  } catch (e) {
    console.warn('Could not fetch approvals:', e);
    allApprovals = [];
  }
  render();
  updateStats();
}

// â”€â”€ Render â”€â”€
function getFiltered() {
  if (currentFilter === 'all') return allApprovals;
  return allApprovals.filter(a => a.status === currentFilter);
}

function initials(name) {
  return (name || '?').split(' ').map(n => n[0] || '').join('').toUpperCase().slice(0, 2);
}

function statusBadge(s) {
  return `<span class="badge ${s}">${s.charAt(0).toUpperCase() + s.slice(1)}</span>`;
}

function render() {
  const list = document.getElementById('approvalList');
  const items = getFiltered();

  if (!items.length) {
    list.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-check-double"></i></div>
        <div class="empty-title">No ${currentFilter === 'all' ? '' : currentFilter + ' '}community approvals</div>
        <div class="empty-sub">${currentFilter === 'pending' ? 'All communities have been reviewed.' : 'Nothing to show for this filter.'}</div>
      </div>`;
    return;
  }

  list.innerHTML = items.map(a => `
    <div class="approval-item ${a.status === 'pending' ? 'is-pending' : ''}" data-id="${a.id}">
      <div class="item-icon"><i class="fas fa-users"></i></div>
      <div class="item-body">
        <div class="item-top">
          <div class="item-title">New Community Request</div>
          <div>${statusBadge(a.status)}</div>
        </div>

        <div class="user-row">
          <div class="user-av">${initials(a.username)}</div>
          <div>
            <div class="user-name-sm">${a.username}</div>
            <div class="user-email-sm">${a.email}</div>
          </div>
        </div>

        <div class="item-preview">
          <div class="item-preview-name">${a.name}</div>
          <div class="item-preview-desc">${a.description || 'No description provided.'}</div>
          <div class="item-preview-meta">
            <span><i class="fas fa-tag" style="margin-right:4px"></i>${a.category || 'â€”'}</span>
            <span><i class="fas fa-lock" style="margin-right:4px"></i>${a.privacy || 'â€”'}</span>
          </div>
        </div>

        <div class="item-footer">
          <div class="item-meta">
            <span class="item-time">${a.timestamp}</span>
            <span class="badge type">Community</span>
          </div>
          <div class="item-actions">
            ${a.status === 'pending' ? `
              <button class="btn approve" onclick="event.stopPropagation();handleAction(${a.id},'approve')">
                <i class="fas fa-check" style="font-size:10px;margin-right:4px"></i>Approve
              </button>
              <button class="btn reject" onclick="event.stopPropagation();handleAction(${a.id},'reject')">
                <i class="fas fa-times" style="font-size:10px;margin-right:4px"></i>Reject
              </button>
            ` : ''}
            <button class="btn" onclick="event.stopPropagation();openModal(${a.id})">Details</button>
          </div>
        </div>
      </div>
    </div>`).join('');

  // Click row â†’ open modal
  list.querySelectorAll('.approval-item').forEach(el => {
    el.addEventListener('click', () => openModal(parseInt(el.dataset.id)));
  });
}

// â”€â”€ Stats â”€â”€
function updateStats() {
  const t = allApprovals.length;
  const p = allApprovals.filter(a => a.status === 'pending').length;
  const ap = allApprovals.filter(a => a.status === 'approved').length;
  const r = allApprovals.filter(a => a.status === 'rejected').length;
  document.getElementById('totalCount').textContent    = t;
  document.getElementById('pendingCount').textContent  = p;
  document.getElementById('approvedCount').textContent = ap;
  document.getElementById('rejectedCount').textContent = r;
}

// â”€â”€ Filters â”€â”€
function bindFilters() {
  document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      currentFilter = btn.dataset.filter;
      render();
    });
  });
}

// â”€â”€ Action (approve / reject) â”€â”€
async function handleAction(id, action) {
  const approval = allApprovals.find(a => a.id === id);
  if (!approval) return;

  try {
    const res  = await fetch('update_community_status.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ communityId: id, action })
    });
    const data = await res.json();

    if (data.success) {
      approval.status = data.status || (action === 'approve' ? 'approved' : 'rejected');
      render();
      updateStats();
      showToast(
        action === 'approve' ? `"${approval.name}" approved.` : `"${approval.name}" rejected.`,
        action === 'approve' ? 'ok' : 'err'
      );
      // If modal is open for this item, refresh its footer
      if (currentModalId === id) openModal(id);
    } else {
      showToast(data.message || 'Something went wrong.', 'err');
    }
  } catch (e) {
    showToast('Server error â€” please try again.', 'err');
  }
}

// â”€â”€ Modal â”€â”€
function openModal(id) {
  const a = allApprovals.find(x => x.id === id);
  if (!a) return;
  currentModalId = id;

  document.getElementById('modalTitle').textContent = `"${a.name}" â€” Details`;

  document.getElementById('modalBody').innerHTML = `
    <div class="user-row" style="margin-bottom:14px">
      <div class="user-av" style="width:36px;height:36px;font-size:12px">${initials(a.username)}</div>
      <div>
        <div class="user-name-sm">${a.username}</div>
        <div class="user-email-sm">${a.email}</div>
      </div>
    </div>
    <div class="detail-row"><div class="detail-key">Community ID</div><div class="detail-val"><span style="font-family:var(--mono)">#COM-${a.communityId || a.id}</span></div></div>
    <div class="detail-row"><div class="detail-key">Status</div><div class="detail-val">${statusBadge(a.status)}</div></div>
    <div class="detail-row"><div class="detail-key">Submitted</div><div class="detail-val" style="font-family:var(--mono);font-size:12px">${a.timestamp}</div></div>
    <div class="detail-row"><div class="detail-key">Category</div><div class="detail-val">${a.category || 'â€”'}</div></div>
    <div class="detail-row"><div class="detail-key">Privacy</div><div class="detail-val">${a.privacy || 'â€”'}</div></div>
    ${a.reviewedBy ? `<div class="detail-row"><div class="detail-key">Reviewed by</div><div class="detail-val">${a.reviewedBy}</div></div>` : ''}
    ${a.reviewDate  ? `<div class="detail-row"><div class="detail-key">Review date</div><div class="detail-val" style="font-family:var(--mono);font-size:12px">${a.reviewDate}</div></div>` : ''}
    <div class="content-box" style="margin-top:14px"><strong>Community Name</strong>${a.name}</div>
    <div class="content-box"><strong>Description</strong>${a.description || 'No description provided.'}</div>`;

  const footer = document.getElementById('modalFooter');
  if (a.status === 'pending') {
    footer.innerHTML = `
      <button class="btn" id="mClose">Close</button>
      <button class="btn reject" id="mReject"><i class="fas fa-times" style="font-size:10px;margin-right:4px"></i>Reject</button>
      <button class="btn approve" id="mApprove"><i class="fas fa-check" style="font-size:10px;margin-right:4px"></i>Approve</button>`;
    document.getElementById('mApprove').onclick = () => { handleAction(id,'approve'); closeModal(); };
    document.getElementById('mReject').onclick  = () => { handleAction(id,'reject');  closeModal(); };
    document.getElementById('mClose').onclick   = closeModal;
  } else {
    footer.innerHTML = `<button class="btn primary" id="mClose">Close</button>`;
    document.getElementById('mClose').onclick = closeModal;
  }

  document.getElementById('detailModal').classList.add('open');
}

function closeModal() {
  document.getElementById('detailModal').classList.remove('open');
  currentModalId = null;
}

function bindModal() {
  document.getElementById('closeModal').addEventListener('click', closeModal);
  document.getElementById('detailModal').addEventListener('click', e => {
    if (e.target === document.getElementById('detailModal')) closeModal();
  });
}

document.addEventListener('DOMContentLoaded', init);
</script>
</body>
</html>