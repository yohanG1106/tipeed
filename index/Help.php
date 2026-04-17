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

$studentName     = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$admin_initials  = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
$studentCourse   = isset($_SESSION['course']) ? $_SESSION['course'] : "No course assigned";
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($currentUserRole === 'admin')         $homePage = 'admin_home.php';
elseif ($currentUserRole === 'faculty')   $homePage = 'teacher_home.php';
else                                      $homePage = 'student_home.php';

$yearLevel = isset($_SESSION['year_level']) ? $_SESSION['year_level'] : null;
$role      = isset($_SESSION['role']) ? $_SESSION['role'] : "student";

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
  <title>Help Center â€” TIPeed</title>
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
      --red-bg:    #fef2f0;   --red-text:   #c0392b;
      --green-bg:  #f0f9f4;   --green-text: #276749;
      --blue-bg:   #f0f5fe;   --blue-text:  #2563a8;
      --purple-bg: #f5f3ff;   --purple-text:#6d28d9;
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
    .main::-webkit-scrollbar { width: 4px; }
    .main::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }

    /* â”€â”€â”€ Hero â”€â”€â”€ */
    .hero {
      background: var(--accent-bg);
      border-radius: var(--radius-xl);
      padding: 36px 32px;
      margin-bottom: 28px;
      display: flex; align-items: center; justify-content: space-between;
      gap: 24px; flex-wrap: wrap;
    }
    .hero-left { flex: 1; min-width: 220px; }
    .hero-eyebrow {
      font-size: 10px; font-weight: 500; color: var(--gold);
      text-transform: uppercase; letter-spacing: 0.1em;
      font-family: var(--mono); margin-bottom: 8px;
    }
    .hero-title { font-size: 22px; font-weight: 500; color: var(--accent-fg); letter-spacing: -0.4px; margin-bottom: 6px; }
    .hero-sub   { font-size: 13px; color: rgba(240,237,232,0.55); }

    /* Hero search */
    .hero-search {
      display: flex; align-items: center; gap: 8px;
      background: rgba(240,237,232,0.08);
      border: 0.5px solid rgba(240,237,232,0.15);
      border-radius: var(--radius-lg); padding: 8px 14px;
      width: 280px; max-width: 100%;
    }
    .hero-search i { font-size: 12px; color: rgba(240,237,232,0.4); }
    .hero-search input {
      border: none; background: transparent; outline: none;
      font-family: var(--font); font-size: 13px;
      color: var(--accent-fg); width: 100%;
    }
    .hero-search input::placeholder { color: rgba(240,237,232,0.35); }
    .hero-right { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
    .hero-btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 16px; border-radius: var(--radius);
      font-size: 12px; font-weight: 500; cursor: pointer; transition: var(--trans);
      border: 0.5px solid rgba(240,237,232,0.2);
      background: rgba(240,237,232,0.08); color: var(--accent-fg);
    }
    .hero-btn:hover { background: rgba(240,237,232,0.14); }
    .hero-btn.gold { background: var(--gold); border-color: var(--gold); color: #fff; }
    .hero-btn.gold:hover { background: var(--gold-dark); border-color: var(--gold-dark); }
    .hero-btn i { font-size: 11px; }

    /* â”€â”€â”€ Two-col grid â”€â”€â”€ */
    .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }

    /* â”€â”€â”€ Card â”€â”€â”€ */
    .card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
    }
    .card-header {
      padding: 16px 20px;
      border-bottom: 0.5px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .card-title { font-size: 13px; font-weight: 500; }
    .card-sub   { font-size: 11px; color: var(--text3); margin-top: 2px; }
    .card-body  { padding: 16px 20px; }

    /* â”€â”€â”€ Help categories â”€â”€â”€ */
    .cat-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
      padding: 16px 20px;
    }
    .cat-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 12px 14px;
      background: var(--surface2);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      cursor: pointer; transition: var(--trans);
    }
    .cat-item:hover { border-color: var(--border2); background: var(--bg); }
    .cat-icon {
      width: 30px; height: 30px; border-radius: var(--radius);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; flex-shrink: 0;
    }
    .cat-icon.blue   { background: var(--blue-bg);   color: var(--blue-text); }
    .cat-icon.purple { background: var(--purple-bg); color: var(--purple-text); }
    .cat-icon.gold   { background: var(--gold-bg);   color: var(--gold-text); }
    .cat-icon.green  { background: var(--green-bg);  color: var(--green-text); }
    .cat-name { font-size: 12px; font-weight: 500; margin-bottom: 2px; }
    .cat-desc { font-size: 11px; color: var(--text3); line-height: 1.45; }
    .cat-arrow { margin-left: auto; color: var(--text3); font-size: 11px; flex-shrink: 0; margin-top: 2px; transition: var(--trans); }
    .cat-item:hover .cat-arrow { color: var(--text); transform: translateX(2px); }

    /* â”€â”€â”€ FAQ â”€â”€â”€ */
    .faq-list { display: flex; flex-direction: column; }
    .faq-item { border-bottom: 0.5px solid var(--border); }
    .faq-item:last-child { border-bottom: none; }
    .faq-q {
      display: flex; align-items: center; justify-content: space-between;
      padding: 13px 20px; cursor: pointer;
      font-size: 13px; font-weight: 500; gap: 12px;
      transition: var(--trans);
    }
    .faq-q:hover { background: var(--surface2); }
    .faq-icon { font-size: 11px; color: var(--text3); flex-shrink: 0; transition: transform 0.2s; }
    .faq-item.open .faq-icon { transform: rotate(180deg); color: var(--gold-text); }
    .faq-a {
      max-height: 0; overflow: hidden;
      transition: max-height 0.25s ease, padding 0.2s;
      font-size: 13px; color: var(--text2); line-height: 1.65;
      padding: 0 20px;
    }
    .faq-item.open .faq-a { max-height: 200px; padding: 0 20px 14px; }

    /* â”€â”€â”€ Contact methods â”€â”€â”€ */
    .contact-grid {
      display: grid; grid-template-columns: 1fr 1fr; gap: 10px;
      padding: 16px 20px;
    }
    .contact-item {
      display: flex; align-items: flex-start; gap: 10px;
      padding: 12px 14px;
      background: var(--surface2);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      transition: var(--trans); cursor: pointer;
    }
    .contact-item:hover { border-color: var(--border2); }
    .contact-icon {
      width: 30px; height: 30px; border-radius: var(--radius);
      background: var(--tag-bg); color: var(--text2);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; flex-shrink: 0;
    }
    .contact-name { font-size: 12px; font-weight: 500; margin-bottom: 2px; }
    .contact-desc { font-size: 11px; color: var(--text3); }
    .contact-link { font-size: 11px; color: var(--blue-text); margin-top: 3px; display: block; font-family: var(--mono); }

    /* â”€â”€â”€ Modal â”€â”€â”€ */
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
      width: 460px; max-width: 94vw;
      max-height: 90vh; overflow-y: auto;
    }
    .modal::-webkit-scrollbar { width: 3px; }
    .modal::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }
    .modal-header {
      padding: 18px 22px 14px;
      border-bottom: 0.5px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .modal-title { font-size: 15px; font-weight: 500; }
    .modal-sub   { font-size: 12px; color: var(--text3); margin-top: 2px; }
    .modal-close {
      width: 26px; height: 26px; border: none; background: transparent;
      border-radius: var(--radius); color: var(--text3); font-size: 16px;
      display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: var(--trans);
    }
    .modal-close:hover { background: var(--tag-bg); color: var(--text); }
    .modal-body { padding: 20px 22px; }
    .form-group { margin-bottom: 14px; }
    .form-label {
      display: block; font-size: 12px; font-weight: 500;
      color: var(--text2); margin-bottom: 5px;
    }
    .form-label .req { color: var(--red-text); margin-left: 2px; }
    .form-input, .form-select, .form-textarea {
      width: 100%; padding: 8px 11px;
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      background: var(--surface2); color: var(--text);
      font-family: var(--font); font-size: 13px;
      outline: none; transition: var(--trans);
    }
    .form-input:focus, .form-select:focus, .form-textarea:focus {
      border-color: var(--gold); background: var(--surface);
      box-shadow: 0 0 0 3px rgba(245,179,1,0.1);
    }
    .form-input::placeholder { color: var(--text3); }
    .form-textarea { min-height: 100px; resize: vertical; }
    .two-field { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
    .modal-footer {
      padding: 14px 22px;
      border-top: 0.5px solid var(--border);
      display: flex; justify-content: flex-end; gap: 8px;
    }
    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: var(--radius);
      font-size: 12px; font-weight: 500; font-family: var(--font);
      border: 0.5px solid var(--border);
      background: var(--surface); color: var(--text2);
      cursor: pointer; transition: var(--trans);
    }
    .btn:hover { background: var(--tag-bg); color: var(--text); }
    .btn.primary { background: var(--accent-bg); color: var(--accent-fg); border-color: var(--accent-bg); }
    .btn.primary:hover { opacity: 0.85; }
    .btn i { font-size: 10px; }

    /* Priority chips in select */
    .priority-row { display: grid; grid-template-columns: repeat(4,1fr); gap: 6px; }
    .priority-opt {
      padding: 6px 8px; border-radius: var(--radius);
      font-size: 11px; font-weight: 500; text-align: center;
      border: 0.5px solid var(--border); cursor: pointer; transition: var(--trans);
      font-family: var(--mono); color: var(--text2); background: var(--surface2);
    }
    .priority-opt:hover { border-color: var(--border2); color: var(--text); }
    .priority-opt.selected.low    { background: var(--green-bg); color: var(--green-text); border-color: rgba(39,103,73,0.3); }
    .priority-opt.selected.medium { background: var(--blue-bg);  color: var(--blue-text);  border-color: rgba(37,99,168,0.3); }
    .priority-opt.selected.high   { background: var(--gold-bg);  color: var(--gold-text);  border-color: rgba(245,179,1,0.3); }
    .priority-opt.selected.urgent { background: var(--red-bg);   color: var(--red-text);   border-color: rgba(192,57,43,0.3); }

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
    <?php if ($currentUserRole === 'admin' || $currentUserRole === 'faculty'): ?>
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

    <?php if ($currentUserRole === 'admin'): ?>
    <div class="nav-section-label">Admin</div>
    <a href="admin_reg.php"  class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    <a href="approval.php"   class="menu-item"><i class="fas fa-check-circle"></i> Approvals</a>
    <?php endif; ?>

    <div class="nav-section-label">Support</div>
    <a href="calendar.php"   class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <div class="menu-item"><i class="fas fa-cog"></i> Settings</div>
    <a href="Help.php"       class="menu-item active"><i class="fas fa-question-circle"></i> Help</a>

    <div class="sidebar-bottom">
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>
  </aside>

  <!-- Main -->
  <main class="main">

    <!-- Hero -->
    <div class="hero">
      <div class="hero-left">
        <div class="hero-eyebrow">Support Center</div>
        <div class="hero-title">How can we help you?</div>
        <div class="hero-sub">Search the docs, browse FAQs, or open a ticket.</div>
        <div class="hero-search" style="margin-top:16px">
          <i class="fas fa-search"></i>
          <input type="text" id="heroSearch" placeholder="Search help articlesâ€¦">
        </div>
      </div>
      <div class="hero-right">
        <button class="hero-btn gold" id="openTicketBtn">
          <i class="fas fa-ticket-alt"></i> Submit a Ticket
        </button>
        <a href="Tickets.php" class="hero-btn">
          <i class="fas fa-list"></i> My Tickets
        </a>
      </div>
    </div>

    <!-- Top row: Categories + FAQ -->
    <div class="two-col">

      <!-- Help Categories -->
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">Browse Topics</div>
            <div class="card-sub">Find guides by category</div>
          </div>
        </div>
        <div class="cat-grid">
          <div class="cat-item">
            <div class="cat-icon blue"><i class="fas fa-user-graduate"></i></div>
            <div style="flex:1">
              <div class="cat-name">Getting Started</div>
              <div class="cat-desc">Onboarding, account setup, first steps</div>
            </div>
            <i class="fas fa-chevron-right cat-arrow"></i>
          </div>
          <div class="cat-item">
            <div class="cat-icon purple"><i class="fas fa-comments"></i></div>
            <div style="flex:1">
              <div class="cat-name">Forum &amp; Discussions</div>
              <div class="cat-desc">Threads, replies, post management</div>
            </div>
            <i class="fas fa-chevron-right cat-arrow"></i>
          </div>
          <div class="cat-item">
            <div class="cat-icon gold"><i class="fas fa-calendar-alt"></i></div>
            <div style="flex:1">
              <div class="cat-name">Calendar &amp; Events</div>
              <div class="cat-desc">Create events, set reminders</div>
            </div>
            <i class="fas fa-chevron-right cat-arrow"></i>
          </div>
          <div class="cat-item">
            <div class="cat-icon green"><i class="fas fa-cog"></i></div>
            <div style="flex:1">
              <div class="cat-name">Account &amp; Settings</div>
              <div class="cat-desc">Profile, privacy, notifications</div>
            </div>
            <i class="fas fa-chevron-right cat-arrow"></i>
          </div>
        </div>
      </div>

      <!-- FAQ -->
      <div class="card">
        <div class="card-header">
          <div>
            <div class="card-title">Frequently Asked</div>
            <div class="card-sub">Quick answers to common questions</div>
          </div>
        </div>
        <div class="faq-list">
          <div class="faq-item">
            <div class="faq-q">
              How do I reset my password?
              <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-a">Go to the login page and click "Forgot Password". Enter your email and we'll send a reset link. Check your spam folder if you don't see it.</div>
          </div>
          <div class="faq-item">
            <div class="faq-q">
              How do I join a course community?
              <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-a">Course communities are created automatically for registered courses. If yours isn't visible, contact your instructor to confirm enrollment in the university system.</div>
          </div>
          <div class="faq-item">
            <div class="faq-q">
              Can I customize my notifications?
              <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-a">Yes â€” go to Settings to choose what triggers a notification (new threads, replies, announcements) and whether you receive them in-app or by email.</div>
          </div>
          <div class="faq-item">
            <div class="faq-q">
              How do I create a new discussion thread?
              <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-a">Navigate to your course community and click "New Thread". Add a title, write your content, and pick a category before posting.</div>
          </div>
          <div class="faq-item">
            <div class="faq-q">
              Is there a mobile app?
              <i class="fas fa-chevron-down faq-icon"></i>
            </div>
            <div class="faq-a">TIPeed is fully optimized for mobile browsers. A dedicated app is in development and will be announced when it's ready.</div>
          </div>
        </div>
      </div>

    </div><!-- /.two-col -->

    <!-- Contact methods -->
    <div class="card">
      <div class="card-header">
        <div>
          <div class="card-title">Still need help?</div>
          <div class="card-sub">Reach us through any of these channels</div>
        </div>
      </div>
      <div class="contact-grid">
        <div class="contact-item">
          <div class="contact-icon"><i class="fas fa-envelope"></i></div>
          <div>
            <div class="contact-name">Email Support</div>
            <div class="contact-desc">We reply within 24 hours</div>
            <a href="mailto:support@tiped.edu" class="contact-link">support@tiped.edu</a>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-icon"><i class="fas fa-phone"></i></div>
          <div>
            <div class="contact-name">Phone Support</div>
            <div class="contact-desc">Monâ€“Fri, 9 AMâ€“5 PM</div>
            <a href="tel:+18005551234" class="contact-link">+1 (800) 555-1234</a>
          </div>
        </div>
        <div class="contact-item" onclick="window.location.href='Tickets.php'">
          <div class="contact-icon"><i class="fas fa-comment-dots"></i></div>
          <div>
            <div class="contact-name">Live Chat</div>
            <div class="contact-desc">Available during business hours</div>
            <span class="contact-link">Open chat â†’</span>
          </div>
        </div>
        <div class="contact-item">
          <div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
          <div>
            <div class="contact-name">IT Help Desk</div>
            <div class="contact-desc">Library Building, Room 205</div>
            <span class="contact-link">View campus map â†’</span>
          </div>
        </div>
      </div>
    </div>

  </main>
</div><!-- /.layout -->

<!-- â•â•â• TICKET MODAL â•â•â• -->
<div class="modal-bg" id="ticketModal">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title">Submit a Support Ticket</div>
        <div class="modal-sub">We'll respond within one business day.</div>
      </div>
      <button class="modal-close" data-close="ticketModal">&#x2715;</button>
    </div>
    <div class="modal-body">
      <form id="ticketForm">
        <div class="form-group">
          <label class="form-label" for="ticketSubject">Subject <span class="req">*</span></label>
          <input type="text" id="ticketSubject" class="form-input" placeholder="Brief description of your issue" required>
        </div>
        <div class="two-field">
          <div class="form-group">
            <label class="form-label" for="ticketCategory">Category <span class="req">*</span></label>
            <select id="ticketCategory" class="form-select" required>
              <option value="">Selectâ€¦</option>
              <option value="technical">Technical Issue</option>
              <option value="account">Account Problem</option>
              <option value="content">Content Issue</option>
              <option value="feature">Feature Request</option>
              <option value="other">Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Priority <span class="req">*</span></label>
            <div class="priority-row">
              <div class="priority-opt low"    data-val="low">Low</div>
              <div class="priority-opt medium selected" data-val="medium">Med</div>
              <div class="priority-opt high"   data-val="high">High</div>
              <div class="priority-opt urgent" data-val="urgent">Urgent</div>
            </div>
            <input type="hidden" id="ticketPriority" value="medium">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="ticketDescription">Description <span class="req">*</span></label>
          <textarea id="ticketDescription" class="form-textarea" placeholder="Describe your issue in detailâ€¦" required></textarea>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn" data-close="ticketModal">Cancel</button>
      <button class="btn primary" id="submitTicket">
        <i class="fas fa-paper-plane"></i> Submit Ticket
      </button>
    </div>
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
  setTimeout(() => t.classList.remove('show'), 3500);
}

// â”€â”€ Modals â”€â”€
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

document.querySelectorAll('[data-close]').forEach(el => {
  el.addEventListener('click', () => closeModal(el.dataset.close));
});
document.querySelectorAll('.modal-bg').forEach(bg => {
  bg.addEventListener('click', e => { if (e.target === bg) bg.classList.remove('open'); });
});

document.getElementById('openTicketBtn').addEventListener('click', () => openModal('ticketModal'));

// â”€â”€ Priority selector â”€â”€
document.querySelectorAll('.priority-opt').forEach(opt => {
  opt.addEventListener('click', () => {
    document.querySelectorAll('.priority-opt').forEach(o => o.classList.remove('selected'));
    opt.classList.add('selected');
    document.getElementById('ticketPriority').value = opt.dataset.val;
  });
});

// â”€â”€ Submit ticket â”€â”€
document.getElementById('submitTicket').addEventListener('click', () => {
  const subject  = document.getElementById('ticketSubject').value.trim();
  const category = document.getElementById('ticketCategory').value;
  const priority = document.getElementById('ticketPriority').value;
  const desc     = document.getElementById('ticketDescription').value.trim();

  if (!subject || !category || !desc) {
    showToast('Please fill in all required fields.', 'err'); return;
  }

  const btn = document.getElementById('submitTicket');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="font-size:10px"></i> Submittingâ€¦';

  fetch('submit_ticket.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ subject, category, priority, description: desc })
  })
    .then(r => r.json())
    .then(data => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Ticket';
      if (data.success) {
        closeModal('ticketModal');
        document.getElementById('ticketForm').reset();
        document.querySelectorAll('.priority-opt').forEach(o => o.classList.remove('selected'));
        document.querySelector('.priority-opt.medium').classList.add('selected');
        document.getElementById('ticketPriority').value = 'medium';
        showToast(data.message || 'Ticket submitted successfully!', 'ok');
      } else {
        showToast(data.message || 'Something went wrong.', 'err');
      }
    })
    .catch(() => {
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Ticket';
      showToast('Server error â€” please try again.', 'err');
    });
});

// â”€â”€ FAQ accordion â”€â”€
document.querySelectorAll('.faq-q').forEach(q => {
  q.addEventListener('click', () => {
    const item = q.closest('.faq-item');
    const wasOpen = item.classList.contains('open');
    document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
    if (!wasOpen) item.classList.add('open');
  });
});

// â”€â”€ Hero search (filter FAQ items) â”€â”€
document.getElementById('heroSearch').addEventListener('input', function () {
  const q = this.value.toLowerCase();
  if (!q) { document.querySelectorAll('.faq-item').forEach(i => i.style.display = ''); return; }
  document.querySelectorAll('.faq-item').forEach(item => {
    const text = item.textContent.toLowerCase();
    item.style.display = text.includes(q) ? '' : 'none';
  });
});
</script>
</body>
</html>