<?php
session_start();
include "db_connect.php";

if (!isset($_SESSION['userid']) || ($_SESSION['role'] !== 'faculty' && $_SESSION['role'] !== 'admin')) {
    header("Location: auth.php");
    exit;
}

$chatId          = $_GET['chat_id'] ?? 0;
$userId          = $_SESSION['userid'];
$userRole        = $_SESSION['role'];
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($currentUserRole === 'admin') {
    $homePage = 'admin_home.php';
} elseif ($currentUserRole === 'faculty') {
    $homePage = 'faculty_home.php';
} else {
    $homePage = 'student_home.php';
}

// ── Fetch chat ────────────────────────────────────────────────
if ($userRole === 'admin') {
    $sql  = "SELECT cc.*, c.course_code, c.course_name
             FROM course_chats cc JOIN courses c ON cc.course_id = c.course_id
             WHERE cc.chat_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $chatId);
} else {
    $sql  = "SELECT cc.*, c.course_code, c.course_name
             FROM course_chats cc JOIN courses c ON cc.course_id = c.course_id
             WHERE cc.chat_id = ? AND cc.faculty_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $chatId, $userId);
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

$stmt->execute();
$chat = $stmt->get_result()->fetch_assoc();
if (!$chat) { header("Location: faculty_chats.php"); exit; }

// ── Handle POST ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $groupName   = mysqli_real_escape_string($conn, $_POST['group_name']);
    $classSection = mysqli_real_escape_string($conn, $_POST['class_section']);
    $description  = mysqli_real_escape_string($conn, $_POST['description']);
    $isCoAdmin    = isset($_POST['coadmin_allowed'])          ? 1 : 0;
    $isApproval   = isset($_POST['approval_allowed'])         ? 1 : 0;
    $isStudentAdd = isset($_POST['student_addition_allowed']) ? 1 : 0;

    $u = $conn->prepare("UPDATE course_chats SET group_name=?,class_section=?,description=?,is_coadmin_allowed=?,is_approval_allowed=?,is_student_addition_allowed=? WHERE chat_id=?");
    $u->bind_param("sssiiii", $groupName, $classSection, $description, $isCoAdmin, $isApproval, $isStudentAdd, $chatId);

    if ($u->execute()) {
        $success = "Changes saved successfully.";
        $stmt->execute(); $chat = $stmt->get_result()->fetch_assoc();
    } else {
        $error = "Error saving changes: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Chat — <?= htmlspecialchars($chat['group_name']) ?> — TiPeed</title>
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
    .main { flex: 1; min-width: 0; padding: 28px 32px 60px; overflow-y: auto; max-width: 860px; }

    /* ── PAGE HEADER ─────────────────────────────── */
    .page-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      margin-bottom: 24px; padding-bottom: 20px;
      border-bottom: 1px solid var(--border); gap: 16px;
    }
    .page-title { font-size: 20px; font-weight: 500; letter-spacing: -.3px; }
    .page-sub   { font-size: 13px; color: var(--text-3); margin-top: 3px; font-family: var(--mono); }

    /* ── ALERTS ──────────────────────────────────── */
    .alert {
      display: flex; align-items: center; gap: 10px;
      padding: 13px 16px; border-radius: 10px;
      font-size: 13px; margin-bottom: 22px;
      animation: fadeUp .25s ease both;
    }
    .alert-success { background: var(--green-bg); color: var(--green); }
    .alert-error   { background: var(--red-bg);   color: var(--red); }
    .alert i { font-size: 14px; flex-shrink: 0; }
    @keyframes fadeUp { from { opacity:0; transform:translateY(8px); } to { opacity:1; transform:translateY(0); } }

    /* ── CHAT IDENTITY CARD ──────────────────────── */
    .chat-identity {
      display: flex; align-items: center; gap: 16px;
      padding: 20px; margin-bottom: 24px;
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius-lg);
    }
    .chat-ava {
      width: 56px; height: 56px; border-radius: 14px;
      background: var(--bg-muted); border: 1px solid var(--border-2);
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; font-weight: 500; color: var(--text-2);
      font-family: var(--mono); flex-shrink: 0;
    }
    .chat-ava-info { flex: 1; min-width: 0; }
    .chat-ava-name { font-size: 16px; font-weight: 500; letter-spacing: -.2px; }
    .chat-ava-course { font-size: 12px; color: var(--text-3); font-family: var(--mono); margin-top: 3px; }
    .chat-ava-section { font-size: 12px; color: var(--text-3); margin-top: 1px; }
    .admin-note {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--red-bg); color: var(--red);
      font-size: 12px; padding: 4px 10px; border-radius: 6px; margin-top: 6px;
    }

    /* ── FORM CARD ───────────────────────────────── */
    .form-card {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: var(--radius-lg); overflow: hidden;
    }
    .form-section { padding: 24px 24px 0; }
    .form-section:last-child { padding-bottom: 24px; }

    /* ── FIELD ───────────────────────────────────── */
    .field-group { display: flex; flex-direction: column; gap: 5px; margin-bottom: 18px; }
    .field-label {
      font-size: 11px; font-weight: 500; color: var(--text-3);
      text-transform: uppercase; letter-spacing: .08em; font-family: var(--mono);
    }
    .field-req { color: var(--red); margin-left: 2px; }
    .field-input {
      width: 100%; background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 9px; padding: 10px 13px;
      font-size: 14px; color: var(--text); outline: none;
      transition: border-color .15s, background .15s;
    }
    .field-input:focus { border-color: var(--border-2); background: var(--bg-card); }
    .field-input::placeholder { color: var(--text-3); }
    textarea.field-input { resize: vertical; min-height: 100px; line-height: 1.6; }

    /* ── SETTINGS SECTION ────────────────────────── */
    .settings-divider {
      height: 1px; background: var(--border); margin: 0 0 20px;
    }
    .settings-heading {
      font-size: 13px; font-weight: 500; color: var(--text-2);
      letter-spacing: .05em; text-transform: uppercase;
      font-family: var(--mono); margin-bottom: 14px;
    }
    .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px; }

    .toggle-card {
      display: flex; align-items: flex-start; gap: 12px;
      padding: 14px; border: 1px solid var(--border);
      border-radius: var(--radius); cursor: pointer;
      transition: border-color .15s, background .15s;
      background: var(--bg-muted);
    }
    .toggle-card:hover { border-color: var(--border-2); }
    .toggle-card.on { background: var(--bg-card); border-color: var(--border-2); }

    /* custom checkbox */
    .toggle-box {
      width: 18px; height: 18px; border-radius: 5px;
      border: 1.5px solid var(--border-2); background: var(--bg-card);
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0; margin-top: 1px;
      transition: background .15s, border-color .15s;
    }
    .toggle-box.checked { background: var(--accent); border-color: var(--accent); }
    .toggle-box.checked::after { content: ''; width: 9px; height: 5px; border-left: 2px solid var(--accent-fg); border-bottom: 2px solid var(--accent-fg); transform: rotate(-45deg) translateY(-1px); display: block; }
    .toggle-card input[type="checkbox"] { display: none; }

    .toggle-label { font-size: 13px; font-weight: 500; color: var(--text); margin-bottom: 2px; }
    .toggle-desc  { font-size: 12px; color: var(--text-3); line-height: 1.5; }

    /* ── FORM ACTIONS ────────────────────────────── */
    .form-actions {
      display: flex; align-items: center; justify-content: flex-end;
      gap: 8px; padding: 18px 24px;
      border-top: 1px solid var(--border);
      background: var(--bg-muted);
    }
    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 8px 18px; border-radius: 8px; font-size: 13px;
      font-weight: 500; border: none; transition: opacity .15s, background .15s;
    }
    .btn i { font-size: 12px; }
    .btn:hover { opacity: .88; }
    .btn-ghost {
      background: none; border: 1px solid var(--border);
      color: var(--text-2);
    }
    .btn-ghost:hover { background: var(--bg-card); }
    .btn-solid { background: var(--accent); color: var(--accent-fg); }

    /* ── SCROLLBAR ───────────────────────────────── */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* ── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 720px) {
      .sidebar { display: none; }
      .main    { padding: 16px 14px 60px; }
      .nav-links, .search-wrap { display: none; }
      .settings-grid { grid-template-columns: 1fr; }
      .chat-identity { flex-direction: column; text-align: center; }
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
      <input type="text" placeholder="Search…">
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
        <div class="page-title">
          Edit Course Chat
          <?php if ($userRole === 'admin'): ?>
          <span style="font-size:13px;font-weight:400;color:var(--text-3);margin-left:8px;font-family:var(--mono)">Admin mode</span>
          <?php endif; ?>
        </div>
        <div class="page-sub"><?= htmlspecialchars($chat['course_code'].' · '.$chat['course_name']) ?></div>
      </div>
      <a href="faculty_chats.php" class="btn btn-ghost">
        <i class="fas fa-arrow-left"></i> Back to Chats
      </a>
    </div>

    <!-- Alerts -->
    <?php if (isset($success)): ?>
    <div class="alert alert-success">
      <i class="fas fa-circle-check"></i>
      <?= htmlspecialchars($success) ?>
    </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
      <i class="fas fa-circle-exclamation"></i>
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- Chat identity strip -->
    <div class="chat-identity">
      <div class="chat-ava"><?= strtoupper(substr($chat['group_name'], 0, 2)) ?></div>
      <div class="chat-ava-info">
        <div class="chat-ava-name"><?= htmlspecialchars($chat['group_name']) ?></div>
        <div class="chat-ava-course"><?= htmlspecialchars($chat['course_code'].' · '.$chat['course_name']) ?></div>
        <?php if ($chat['class_section']): ?>
        <div class="chat-ava-section"><?= htmlspecialchars($chat['class_section']) ?></div>
        <?php endif; ?>
        <?php if ($userRole === 'admin'): ?>
        <div class="admin-note">
          <i class="fas fa-shield-halved" style="font-size:11px"></i>
          Editing as administrator
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Form -->
    <div class="form-card">
      <form method="POST" id="editForm">

        <div class="form-section">
          <!-- Group name -->
          <div class="field-group">
            <label class="field-label">Group Name <span class="field-req">*</span></label>
            <input type="text" name="group_name" class="field-input"
                   value="<?= htmlspecialchars($chat['group_name']) ?>"
                   placeholder="e.g. CS101 – Morning Batch"
                   required>
          </div>

          <!-- Class section -->
          <div class="field-group">
            <label class="field-label">Class Section</label>
            <input type="text" name="class_section" class="field-input"
                   value="<?= htmlspecialchars($chat['class_section'] ?? '') ?>"
                   placeholder="e.g. Section A">
          </div>

          <!-- Description -->
          <div class="field-group">
            <label class="field-label">Description</label>
            <textarea name="description" class="field-input"
                      placeholder="Describe the purpose of this group…"><?= htmlspecialchars($chat['description'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Settings -->
        <div class="form-section">
          <div class="settings-divider"></div>
          <div class="settings-heading">Chat permissions</div>
          <div class="settings-grid">

            <!-- Co-admins -->
            <label class="toggle-card <?= $chat['is_coadmin_allowed'] ? 'on' : '' ?>" id="card-coadmin">
              <div class="toggle-box <?= $chat['is_coadmin_allowed'] ? 'checked' : '' ?>" id="box-coadmin"></div>
              <div>
                <div class="toggle-label">Allow Co-Admins</div>
                <div class="toggle-desc">Co-admins can manage members and settings.</div>
              </div>
              <input type="checkbox" name="coadmin_allowed" id="coadmin_allowed"
                     <?= $chat['is_coadmin_allowed'] ? 'checked' : '' ?>>
            </label>

            <!-- Approval -->
            <label class="toggle-card <?= $chat['is_approval_allowed'] ? 'on' : '' ?>" id="card-approval">
              <div class="toggle-box <?= $chat['is_approval_allowed'] ? 'checked' : '' ?>" id="box-approval"></div>
              <div>
                <div class="toggle-label">Require Approval</div>
                <div class="toggle-desc">New members need approval before joining.</div>
              </div>
              <input type="checkbox" name="approval_allowed" id="approval_allowed"
                     <?= $chat['is_approval_allowed'] ? 'checked' : '' ?>>
            </label>

            <!-- Student addition -->
            <label class="toggle-card <?= $chat['is_student_addition_allowed'] ? 'on' : '' ?>" id="card-studentadd">
              <div class="toggle-box <?= $chat['is_student_addition_allowed'] ? 'checked' : '' ?>" id="box-studentadd"></div>
              <div>
                <div class="toggle-label">Student Addition</div>
                <div class="toggle-desc">Students can invite and add other students.</div>
              </div>
              <input type="checkbox" name="student_addition_allowed" id="student_addition_allowed"
                     <?= $chat['is_student_addition_allowed'] ? 'checked' : '' ?>>
            </label>

          </div>
        </div>

        <!-- Actions -->
        <div class="form-actions">
          <a href="faculty_chats.php" class="btn btn-ghost">
            <i class="fas fa-times"></i> Cancel
          </a>
          <button type="submit" class="btn btn-solid">
            <i class="fas fa-floppy-disk"></i> Save Changes
          </button>
        </div>

      </form>
    </div>

  </main>
</div>

<script>
  // ── DARK MODE ──
  const root = document.documentElement;
  const themeBtn = document.getElementById('themeBtn');
  const saved = localStorage.getItem('tipeed-theme') || 'light';
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

  // ── CUSTOM TOGGLE CARDS ──
  const toggleDefs = [
    { card: 'card-coadmin',   box: 'box-coadmin',   cb: 'coadmin_allowed'          },
    { card: 'card-approval',  box: 'box-approval',  cb: 'approval_allowed'         },
    { card: 'card-studentadd',box: 'box-studentadd', cb: 'student_addition_allowed' },
  ];

  toggleDefs.forEach(({ card, box, cb }) => {
    const cardEl = document.getElementById(card);
    const boxEl  = document.getElementById(box);
    const cbEl   = document.getElementById(cb);

    // Sync visual state from real checkbox
    function sync() {
      if (cbEl.checked) {
        boxEl.classList.add('checked');
        cardEl.classList.add('on');
      } else {
        boxEl.classList.remove('checked');
        cardEl.classList.remove('on');
      }
    }

    cbEl.addEventListener('change', sync);
    sync(); // initial
  });

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