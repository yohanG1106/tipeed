<?php
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

$chatId          = $_GET['chat_id'] ?? 0;
$userId          = $_SESSION['userid'];
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
$role        = $currentUserRole;

function ordinal($n) {
    $e = ['th','st','nd','rd','th','th','th','th','th','th'];
    return (($n % 100) >= 11 && ($n % 100) <= 13) ? $n.'th' : $n.$e[$n % 10];
}
if ($role === 'student') {
    $studentIDT = ($yearLevel && is_numeric($yearLevel)) ? ordinal($yearLevel)." Year" : "No year assigned";
} elseif ($role === 'faculty') { $studentIDT = "Faculty"; }
elseif ($role === 'admin')     { $studentIDT = "Administrator"; }
else                            { $studentIDT = ucfirst(htmlspecialchars($role)); }

// ── Fetch chat ────────────────────────────────────────────────
if ($currentUserRole === 'admin') {
    $cs = $conn->prepare("SELECT cc.*, c.course_code, c.course_name FROM course_chats cc JOIN courses c ON cc.course_id=c.course_id WHERE cc.chat_id=?");
    $cs->bind_param("i", $chatId);
} else {
    $cs = $conn->prepare("SELECT cc.*, c.course_code, c.course_name FROM course_chats cc JOIN courses c ON cc.course_id=c.course_id WHERE cc.chat_id=? AND cc.faculty_id=?");
    $cs->bind_param("ii", $chatId, $userId);
}
$cs->execute();
$chat = $cs->get_result()->fetch_assoc();
if (!$chat) { header("Location: faculty_chats.php"); exit; }

// ── Fetch members ─────────────────────────────────────────────
$ms = $conn->prepare("SELECT cm.*, u.first_name, u.last_name, u.email FROM course_chat_members cm JOIN users u ON cm.user_id=u.userid WHERE cm.chat_id=? ORDER BY CASE cm.role WHEN 'faculty' THEN 1 WHEN 'co-admin' THEN 2 ELSE 3 END, u.first_name");
$ms->bind_param("i", $chatId);
$ms->execute();
$members = $ms->get_result()->fetch_all(MYSQLI_ASSOC);

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $canManage = $currentUserRole === 'admin';
    if (!$canManage) {
        $oc = $conn->prepare("SELECT faculty_id FROM course_chats WHERE chat_id=? AND faculty_id=?");
        $oc->bind_param("ii", $chatId, $userId); $oc->execute(); $oc->store_result();
        $canManage = ($oc->num_rows > 0);
    }

    if ($canManage) {
        if (isset($_POST['remove_member'])) {
            $mid = $_POST['member_id'];
            $r = $conn->prepare("DELETE FROM course_chat_members WHERE member_id=? AND chat_id=?");
            $r->bind_param("ii", $mid, $chatId); $r->execute();
            header("Location: manage_members.php?chat_id=$chatId&msg=removed"); exit;
        }
        if (isset($_POST['change_role'])) {
            $mid = $_POST['member_id']; $nr = $_POST['new_role'];
            $r = $conn->prepare("UPDATE course_chat_members SET role=? WHERE member_id=? AND chat_id=?");
            $r->bind_param("sii", $nr, $mid, $chatId); $r->execute();
            header("Location: manage_members.php?chat_id=$chatId&msg=updated"); exit;
        }
        if (isset($_POST['add_member'])) {
            $email = mysqli_real_escape_string($conn, $_POST['email']);
            $newRole  = mysqli_real_escape_string($conn, $_POST['role']);
            $us = $conn->prepare("SELECT userid FROM users WHERE email=?");
            $us->bind_param("s", $email); $us->execute();
            $ur = $us->get_result();
            if ($ur->num_rows > 0) {
                $u2 = $ur->fetch_assoc();
                $ex = $conn->prepare("SELECT member_id FROM course_chat_members WHERE chat_id=? AND user_id=?");
                $ex->bind_param("ii", $chatId, $u2['userid']); $ex->execute(); $ex->store_result();
                if ($ex->num_rows === 0) {
                    $a = $conn->prepare("INSERT INTO course_chat_members(chat_id,user_id,role)VALUES(?,?,?)");
                    $a->bind_param("iis", $chatId, $u2['userid'], $newRole); $a->execute();
                    header("Location: manage_members.php?chat_id=$chatId&msg=added"); exit;
                } else { $error = "This user is already a member."; }
            } else { $error = "No account found with email <strong>".htmlspecialchars($email)."</strong>."; }
        }
    } else { $error = "You don't have permission to manage this chat."; }

    // Refresh members after non-redirect actions
    $ms->execute();
    $members = $ms->get_result()->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Members — TiPeed</title>
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
    body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; transition: background .25s, color .25s; }
    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }
    input, select { font-family: var(--font); }

    /* ── NAVBAR ──────────────────────────────────── */
    .navbar { position: fixed; top: 0; left: 0; right: 0; z-index: 100; height: var(--nav-h); background: var(--bg-card); border-bottom: 1px solid var(--border); display: flex; align-items: center; padding: 0 24px; gap: 20px; }
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
    .theme-btn { width: 34px; height: 34px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-2); font-size: 14px; display: flex; align-items: center; justify-content: center; transition: background .15s; }
    .theme-btn:hover { background: var(--bg-muted); }

    /* ── LAYOUT ──────────────────────────────────── */
    .layout { display: flex; padding-top: var(--nav-h); min-height: 100vh; }

    /* ── SIDEBAR ─────────────────────────────────── */
    .sidebar { width: var(--sidebar-w); flex-shrink: 0; position: sticky; top: var(--nav-h); height: calc(100vh - var(--nav-h)); overflow-y: auto; padding: 20px 12px; border-right: 1px solid var(--border); background: var(--bg-card); display: flex; flex-direction: column; gap: 6px; }
    .sidebar-profile { display: flex; align-items: center; gap: 10px; padding: 10px 10px 14px; border-bottom: 1px solid var(--border); margin-bottom: 6px; }
    .avatar-circle { width: 36px; height: 36px; border-radius: 50%; background: var(--bg-muted); border: 1px solid var(--border-2); display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 500; color: var(--text-2); flex-shrink: 0; font-family: var(--mono); }
    .profile-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .profile-role { font-size: 11px; color: var(--text-3); font-family: var(--mono); }
    .menu-item { display: flex; align-items: center; gap: 9px; padding: 7px 10px; border-radius: 8px; font-size: 13.5px; color: var(--text-2); transition: background .15s, color .15s; }
    .menu-item:hover { background: var(--bg-muted); color: var(--text); }
    .menu-item.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .menu-item i { width: 16px; text-align: center; font-size: 13px; color: var(--text-3); flex-shrink: 0; }
    .menu-divider { height: 1px; background: var(--border); margin: 8px 4px; }

    /* ── MAIN ────────────────────────────────────── */
    .main { flex: 1; min-width: 0; padding: 28px 32px 60px; overflow-y: auto; }

    /* ── PAGE HEADER ─────────────────────────────── */
    .page-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid var(--border); gap: 16px; }
    .page-title { font-size: 20px; font-weight: 500; letter-spacing: -.3px; }
    .page-sub   { font-size: 13px; color: var(--text-3); margin-top: 3px; font-family: var(--mono); }

    /* ── ALERTS ──────────────────────────────────── */
    .alert { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 10px; font-size: 13px; margin-bottom: 20px; animation: fadeUp .25s ease both; }
    .alert-success { background: var(--green-bg); color: var(--green); }
    .alert-error   { background: var(--red-bg);   color: var(--red); }
    .alert i { font-size: 14px; flex-shrink: 0; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

    /* ── CHAT BANNER ─────────────────────────────── */
    .chat-banner {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius-lg); padding: 18px 20px;
      display: flex; align-items: center; gap: 14px;
      margin-bottom: 22px;
    }
    .banner-ava {
      width: 48px; height: 48px; border-radius: 12px;
      background: var(--bg-muted); border: 1px solid var(--border-2);
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; font-weight: 500; color: var(--text-2);
      font-family: var(--mono); flex-shrink: 0;
    }
    .banner-name  { font-size: 15px; font-weight: 500; }
    .banner-meta  { display: flex; gap: 16px; margin-top: 4px; flex-wrap: wrap; }
    .banner-stat  { font-size: 11px; color: var(--text-3); font-family: var(--mono); display: flex; align-items: center; gap: 5px; }
    .banner-stat i { font-size: 10px; }
    .chip-admin { background: var(--red-bg); color: var(--red); font-size: 10px; font-family: var(--mono); font-weight: 500; padding: 2px 7px; border-radius: 5px; margin-left: 6px; vertical-align: middle; }

    /* ── BUTTONS ─────────────────────────────────── */
    .btn { display: inline-flex; align-items: center; gap: 6px; padding: 7px 15px; border-radius: 8px; font-size: 13px; font-weight: 500; border: none; transition: opacity .15s, background .15s; white-space: nowrap; }
    .btn i { font-size: 12px; }
    .btn:hover { opacity: .88; }
    .btn-solid  { background: var(--accent);   color: var(--accent-fg); }
    .btn-ghost  { background: none; border: 1px solid var(--border); color: var(--text-2); }
    .btn-ghost:hover { background: var(--bg-muted); opacity: 1; }
    .btn-green  { background: var(--green-bg); color: var(--green); }
    .btn-red    { background: var(--red-bg);   color: var(--red); }

    /* ── ADD MEMBER PANEL ────────────────────────── */
    .add-panel {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius-lg); overflow: hidden; margin-bottom: 16px;
    }
    .add-panel-header {
      padding: 14px 18px; border-bottom: 1px solid var(--border);
      display: flex; align-items: center; gap: 8px;
    }
    .add-panel-title { font-size: 13px; font-weight: 500; color: var(--text-2); letter-spacing: .04em; text-transform: uppercase; font-family: var(--mono); }
    .add-panel-body { padding: 18px; }

    .add-row { display: grid; grid-template-columns: 1fr 160px auto; gap: 10px; align-items: flex-end; }

    .field-group { display: flex; flex-direction: column; gap: 5px; }
    .field-label { font-size: 11px; font-weight: 500; color: var(--text-3); text-transform: uppercase; letter-spacing: .08em; font-family: var(--mono); }
    .field-input {
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 9px; padding: 9px 13px;
      font-size: 14px; color: var(--text); outline: none;
      transition: border-color .15s; width: 100%; appearance: none;
    }
    .field-input:focus { border-color: var(--border-2); }
    .field-input::placeholder { color: var(--text-3); }

    .error-inline { font-size: 13px; color: var(--red); margin-top: 10px; padding: 8px 12px; background: var(--red-bg); border-radius: 7px; }

    /* ── MEMBER FILTER ───────────────────────────── */
    .members-header {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 12px; gap: 12px;
    }
    .members-title { font-size: 13px; font-weight: 500; color: var(--text-2); letter-spacing: .04em; text-transform: uppercase; font-family: var(--mono); }
    .filter-wrap { position: relative; }
    .filter-wrap i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-3); font-size: 12px; pointer-events: none; }
    .filter-input { background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 7px 10px 7px 28px; font-size: 13px; color: var(--text); outline: none; width: 200px; }
    .filter-input:focus { border-color: var(--border-2); }
    .filter-input::placeholder { color: var(--text-3); }

    /* ── MEMBER LIST ─────────────────────────────── */
    .members-list { display: flex; flex-direction: column; gap: 8px; }

    .member-row {
      display: flex; align-items: center; gap: 12px;
      padding: 13px 16px;
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius);
      transition: border-color .15s, box-shadow .15s;
      animation: fadeUp .2s ease both;
    }
    .member-row:hover { border-color: var(--border-2); box-shadow: var(--shadow); }

    /* initials avatar */
    .mem-ava {
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--bg-muted); border: 1px solid var(--border-2);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 500; color: var(--text-2);
      font-family: var(--mono); flex-shrink: 0;
    }

    .mem-info { flex: 1; min-width: 0; }
    .mem-name  { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mem-email { font-size: 12px; color: var(--text-3); font-family: var(--mono); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .you-tag { font-size: 11px; color: var(--amber); font-family: var(--mono); margin-left: 5px; }

    /* role chip */
    .role-chip { display: inline-block; font-size: 11px; font-family: var(--mono); font-weight: 500; padding: 3px 9px; border-radius: 5px; white-space: nowrap; flex-shrink: 0; }
    .role-faculty   { background: var(--green-bg);  color: var(--green);  }
    .role-coadmin   { background: var(--blue-bg);   color: var(--blue);   }
    .role-student   { background: var(--amber-bg);  color: var(--amber);  }
    .role-protected { background: var(--bg-muted);  color: var(--text-3); }

    /* actions */
    .mem-actions { display: flex; align-items: center; gap: 8px; flex-shrink: 0; }

    .role-select {
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 7px; padding: 5px 10px;
      font-size: 12px; color: var(--text); outline: none;
      appearance: none; cursor: pointer; font-family: var(--mono);
      transition: border-color .15s;
    }
    .role-select:focus { border-color: var(--border-2); }

    .protect-label { font-size: 12px; color: var(--text-3); font-family: var(--mono); }

    /* ── EMPTY ───────────────────────────────────── */
    .empty-box { text-align: center; padding: 48px 20px; color: var(--text-3); border: 1px dashed var(--border-2); border-radius: var(--radius-lg); }
    .empty-box i { font-size: 32px; margin-bottom: 12px; display: block; }
    .empty-box p { font-size: 14px; }

    /* ── SCROLLBAR ───────────────────────────────── */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* ── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 860px) { .add-row { grid-template-columns: 1fr; } }
    @media (max-width: 720px) {
      .sidebar { display: none; }
      .main    { padding: 16px 14px 60px; }
      .nav-links, .search-wrap { display: none; }
      .mem-actions { flex-wrap: wrap; }
      .filter-input { width: 140px; }
    }
  </style>
</head>
<body>

<!-- ── NAVBAR ─────────────────────────────────────────── -->
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
      <input type="text" id="navSearch" placeholder="Search members…">
    </div>
    <button class="theme-btn" id="themeBtn"><i class="fas fa-moon"></i></button>
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
    <a href="profile.php"        class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>"   class="menu-item"><i class="fas fa-house"></i> Home</a>
    <a href="chat_interface.php" class="menu-item"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="CourseChat.php"     class="menu-item"><i class="fas fa-comments"></i> Communities</a>
    <a href="Community.php"      class="menu-item"><i class="fas fa-users"></i> Community</a>
    <?php if ($currentUserRole === 'admin'): ?>
    <a href="admin_reg.php"      class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
    <div class="menu-divider"></div>
    <a href="calendar.php" class="menu-item active"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <a href="#"            class="menu-item"><i class="fas fa-gear"></i> Settings</a>
    <a href="Help.php"     class="menu-item"><i class="fas fa-circle-question"></i> Help</a>
    <div class="menu-divider"></div>
    <a href="logout.php" class="menu-item" style="color:var(--red)">
      <i class="fas fa-arrow-right-from-bracket" style="color:var(--red)"></i> Log out
    </a>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- Page header -->
    <div class="page-header">
      <div>
        <div class="page-title">Manage Members</div>
        <div class="page-sub"><?= htmlspecialchars($chat['course_code'].' · '.$chat['course_name']) ?></div>
      </div>
      <a href="faculty_chats.php" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Back to Chats
      </a>
    </div>

    <!-- URL message alerts -->
    <?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">
      <i class="fas fa-circle-check"></i>
      <?php
        $msgs = ['added'=>'Member added successfully.','removed'=>'Member removed.','updated'=>'Role updated.'];
        echo $msgs[$_GET['msg']] ?? 'Action completed.';
      ?>
    </div>
    <?php endif; ?>

    <!-- Inline error alert -->
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
      <i class="fas fa-circle-exclamation"></i>
      <?= $error ?>
    </div>
    <?php endif; ?>

    <!-- Chat banner -->
    <div class="chat-banner">
      <div class="banner-ava"><?= strtoupper(substr($chat['group_name'], 0, 2)) ?></div>
      <div style="flex:1;min-width:0">
        <div class="banner-name">
          <?= htmlspecialchars($chat['group_name']) ?>
          <?php if ($currentUserRole === 'admin'): ?>
          <span class="chip-admin">Admin View</span>
          <?php endif; ?>
        </div>
        <div class="banner-meta">
          <div class="banner-stat"><i class="fas fa-users"></i><?= count($members) ?> member<?= count($members) !== 1 ? 's' : '' ?></div>
          <div class="banner-stat"><i class="fas fa-book"></i><?= htmlspecialchars($chat['course_code']) ?></div>
          <div class="banner-stat"><i class="fas fa-calendar"></i><?= date('M j, Y', strtotime($chat['created_at'])) ?></div>
        </div>
      </div>
    </div>

    <!-- Add member panel -->
    <div class="add-panel">
      <div class="add-panel-header">
        <i class="fas fa-user-plus" style="font-size:13px;color:var(--text-3)"></i>
        <div class="add-panel-title">Add Member</div>
      </div>
      <div class="add-panel-body">
        <form method="POST">
          <div class="add-row">
            <div class="field-group">
              <label class="field-label">Email address</label>
              <input type="email" name="email" class="field-input"
                     placeholder="student@tip.edu.ph" required autocomplete="off">
            </div>
            <div class="field-group">
              <label class="field-label">Role</label>
              <select name="role" class="field-input" required>
                <option value="student">Student</option>
                <option value="co-admin">Co-Admin</option>
                <?php if ($currentUserRole === 'admin'): ?>
                <option value="faculty">Faculty</option>
                <?php endif; ?>
              </select>
            </div>
            <button type="submit" name="add_member" class="btn btn-solid" style="height:40px;align-self:flex-end">
              <i class="fas fa-plus"></i> Add
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Members list -->
    <div class="members-header">
      <div class="members-title"><?= count($members) ?> Member<?= count($members) !== 1 ? 's' : '' ?></div>
      <div class="filter-wrap">
        <i class="fas fa-search"></i>
        <input type="text" class="filter-input" id="memberFilter" placeholder="Filter…">
      </div>
    </div>

    <div class="members-list" id="membersList">
      <?php if (empty($members)): ?>
      <div class="empty-box">
        <i class="fas fa-users-slash"></i>
        <p>No members yet — add someone using the form above.</p>
      </div>
      <?php else: ?>
        <?php foreach ($members as $m):
          $initials  = strtoupper(substr($m['first_name'],0,1).substr($m['last_name'],0,1));
          $fullName  = htmlspecialchars($m['first_name'].' '.$m['last_name']);
          $email     = htmlspecialchars($m['email']);
          $isMe      = ($m['user_id'] == $userId);
          $roleKey   = $m['role'];
          $roleClass = match($roleKey) {
            'faculty'  => 'role-faculty',
            'co-admin' => 'role-coadmin',
            'student'  => 'role-student',
            default    => 'role-protected',
          };
          $roleLabel = ucwords(str_replace('-', ' ', $roleKey));
          $canModify = ($currentUserRole === 'admin') || ($roleKey !== 'faculty');
        ?>
        <div class="member-row"
             data-name="<?= strtolower($m['first_name'].' '.$m['last_name']) ?>"
             data-email="<?= strtolower($m['email']) ?>">
          <div class="mem-ava"><?= $initials ?></div>
          <div class="mem-info">
            <div class="mem-name">
              <?= $fullName ?>
              <?php if ($isMe): ?><span class="you-tag">you</span><?php endif; ?>
            </div>
            <div class="mem-email"><?= $email ?></div>
          </div>
          <span class="role-chip <?= $roleClass ?>"><?= $roleLabel ?></span>
          <div class="mem-actions">
            <?php if ($canModify && !$isMe): ?>
              <!-- Role selector -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="member_id" value="<?= $m['member_id'] ?>">
                <input type="hidden" name="change_role" value="1">
                <select name="new_role" class="role-select" onchange="this.form.submit()" title="Change role">
                  <option value="student"  <?= $roleKey==='student'   ? 'selected':'' ?>>Student</option>
                  <option value="co-admin" <?= $roleKey==='co-admin'  ? 'selected':'' ?>>Co-Admin</option>
                  <?php if ($currentUserRole === 'admin'): ?>
                  <option value="faculty"  <?= $roleKey==='faculty'   ? 'selected':'' ?>>Faculty</option>
                  <?php endif; ?>
                </select>
              </form>
              <!-- Remove button -->
              <form method="POST" style="display:inline"
                    onsubmit="return confirm('Remove <?= addslashes($m['first_name']) ?> from this chat?')">
                <input type="hidden" name="member_id" value="<?= $m['member_id'] ?>">
                <button type="submit" name="remove_member" class="btn btn-red" style="padding:5px 11px">
                  <i class="fas fa-times"></i>
                </button>
              </form>
            <?php elseif ($isMe): ?>
              <span class="protect-label">you</span>
            <?php else: ?>
              <span class="protect-label"><?= $roleKey === 'faculty' ? 'owner' : 'protected' ?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

  </main>
</div><!-- /.layout -->

<script>
  // ── DARK MODE ──
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
  function updateIcon(t) {
    themeBtn.innerHTML = t === 'dark'
      ? '<i class="fas fa-sun"></i>'
      : '<i class="fas fa-moon"></i>';
  }

  // ── MEMBER FILTER ──
  function filterMembers(q) {
    document.querySelectorAll('.member-row').forEach(row => {
      const match = row.dataset.name.includes(q) || row.dataset.email.includes(q);
      row.style.display = match ? '' : 'none';
    });
  }
  document.getElementById('memberFilter')?.addEventListener('input', e => filterMembers(e.target.value.toLowerCase()));
  document.getElementById('navSearch')?.addEventListener('input',   e => filterMembers(e.target.value.toLowerCase()));

  // ── AUTO-DISMISS ALERTS ──
  document.querySelectorAll('.alert').forEach(el => {
    setTimeout(() => {
      el.style.transition = 'opacity .4s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 400);
    }, 5000);
  });
</script>
</body>
</html>