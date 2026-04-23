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
  <title>Register Faculty â€” TIPeed</title>
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
      --gold-bg:   #251e0e;
      --gold-text: #dba94a;
      --red-bg:    #2a1614;  --red-text:   #f4826e;
      --green-bg:  #0f1f16;  --green-text: #52c487;
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
    .logo { font-size: 15px; font-weight: 500; letter-spacing: -0.3px; margin-right: 16px; flex-shrink: 0; }
    .nav-links { display: flex; gap: 2px; flex: 1; }
    .nav-links a {
      padding: 5px 10px;
      border-radius: var(--radius);
      font-size: 13px;
      color: var(--text2);
      transition: var(--trans);
    }
    .nav-links a:hover, .nav-links a.active { color: var(--text); background: var(--tag-bg); }
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
      border: none; background: transparent; outline: none;
      font-family: var(--font); font-size: 13px; color: var(--text); width: 160px;
    }
    .search input::placeholder { color: var(--text3); }
    .icon-btn {
      width: 30px; height: 30px;
      border: 0.5px solid var(--border);
      background: transparent;
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
      padding: 16px 0;
      height: 100%;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }
    .sidebar::-webkit-scrollbar { width: 3px; }
    .sidebar::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }
    .profile-block {
      display: flex; align-items: center; gap: 10px;
      padding: 0 16px 16px;
      border-bottom: 0.5px solid var(--border);
      margin-bottom: 8px;
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
      padding: 10px 16px 4px;
      font-size: 10px; font-weight: 500; color: var(--text3);
      text-transform: uppercase; letter-spacing: 0.08em;
    }
    .menu-item {
      display: flex; align-items: center; gap: 10px;
      padding: 7px 16px;
      font-size: 13px; color: var(--text2);
      border-left: 2px solid transparent;
      transition: var(--trans); cursor: pointer;
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
      font-size: 12px; color: var(--text3);
      padding: 6px 0; transition: var(--trans);
    }
    .sidebar-bottom a:hover { color: var(--red-text); }

    /* â”€â”€â”€ Main â”€â”€â”€ */
    .main { overflow-y: auto; padding: 28px 32px; }

    /* â”€â”€â”€ Page header â”€â”€â”€ */
    .page-header { margin-bottom: 24px; }
    .page-eyebrow {
      font-size: 11px; font-weight: 500; color: var(--gold-text);
      text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px;
      font-family: var(--mono);
    }
    .page-title { font-size: 20px; font-weight: 500; letter-spacing: -0.4px; }
    .page-sub   { font-size: 13px; color: var(--text3); margin-top: 3px; }

    /* â”€â”€â”€ Form card â”€â”€â”€ */
    .form-card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      max-width: 780px;
    }
    .form-card-header {
      padding: 16px 22px;
      border-bottom: 0.5px solid var(--border);
      display: flex; align-items: center; gap: 10px;
    }
    .form-card-icon {
      width: 30px; height: 30px; border-radius: var(--radius);
      background: var(--gold-bg); color: var(--gold-text);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; flex-shrink: 0;
    }
    .form-card-title { font-size: 13px; font-weight: 500; }
    .form-card-sub   { font-size: 11px; color: var(--text3); margin-top: 1px; }
    .form-body { padding: 22px; }

    /* â”€â”€â”€ Field groups â”€â”€â”€ */
    .field-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 14px;
    }
    .field-row.single { grid-template-columns: 1fr; }
    .field-group { display: flex; flex-direction: column; gap: 5px; }
    .field-label {
      font-size: 12px; font-weight: 500; color: var(--text2);
    }
    .field-label .req { color: var(--red-text); margin-left: 2px; }
    .field-input,
    .field-select {
      padding: 8px 11px;
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      background: var(--surface2);
      color: var(--text);
      font-family: var(--font);
      font-size: 13px;
      outline: none;
      transition: var(--trans);
      width: 100%;
    }
    .field-input:focus, .field-select:focus {
      border-color: var(--gold);
      background: var(--surface);
      box-shadow: 0 0 0 3px rgba(245,179,1,0.1);
    }
    .field-input::placeholder { color: var(--text3); }
    .field-hint { font-size: 11px; color: var(--text3); }

    /* â”€â”€â”€ Divider â”€â”€â”€ */
    .section-divider {
      border: none;
      border-top: 0.5px solid var(--border);
      margin: 20px 0;
    }
    .section-label {
      font-size: 11px; font-weight: 500; color: var(--text3);
      text-transform: uppercase; letter-spacing: 0.08em;
      margin-bottom: 14px; font-family: var(--mono);
    }

    /* â”€â”€â”€ Course chips â”€â”€â”€ */
    .course-chips {
      display: flex; flex-wrap: wrap; gap: 6px;
      margin-top: 10px; min-height: 32px;
    }
    .chip {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 10px;
      background: var(--gold-bg);
      border: 0.5px solid rgba(245,179,1,0.3);
      border-radius: 99px;
      font-size: 12px; color: var(--gold-text);
      font-family: var(--mono);
    }
    .chip-remove {
      background: none; border: none;
      color: var(--gold-text); cursor: pointer;
      font-size: 11px; padding: 0; line-height: 1;
      opacity: 0.6; transition: opacity 0.15s;
    }
    .chip-remove:hover { opacity: 1; }
    .chip-empty { font-size: 12px; color: var(--text3); padding: 4px 0; }

    /* â”€â”€â”€ Checkbox setting â”€â”€â”€ */
    .setting-row { display: flex; align-items: center; gap: 10px; margin-bottom: 14px; }
    .setting-row input[type="checkbox"] {
      width: 16px; height: 16px;
      accent-color: var(--gold);
      cursor: pointer;
    }
    .setting-row label { font-size: 13px; color: var(--text2); cursor: pointer; }
    .setting-row .setting-desc { font-size: 11px; color: var(--text3); margin-top: 1px; }

    /* â”€â”€â”€ Actions â”€â”€â”€ */
    .form-actions {
      display: flex; gap: 8px; justify-content: flex-end;
      padding-top: 20px;
      border-top: 0.5px solid var(--border);
      margin-top: 4px;
    }
    .btn {
      padding: 8px 16px;
      border-radius: var(--radius);
      font-size: 13px; font-weight: 500;
      font-family: var(--font);
      border: 0.5px solid var(--border);
      background: var(--surface);
      color: var(--text2);
      cursor: pointer;
      transition: var(--trans);
    }
    .btn:hover { background: var(--tag-bg); color: var(--text); }
    .btn.primary {
      background: var(--accent-bg); color: var(--accent-fg);
      border-color: var(--accent-bg);
    }
    .btn.primary:hover { opacity: 0.85; }
    .btn.gold {
      background: var(--gold); color: #fff;
      border-color: var(--gold);
    }
    .btn.gold:hover { background: var(--gold-dark); border-color: var(--gold-dark); }

    /* â”€â”€â”€ Success modal â”€â”€â”€ */
    .modal-bg {
      position: fixed; inset: 0;
      background: rgba(0,0,0,0.32);
      z-index: 200;
      display: none; align-items: center; justify-content: center;
    }
    .modal-bg.open { display: flex; }
    .modal {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-xl);
      padding: 32px 28px;
      text-align: center;
      width: 360px;
      max-width: 90vw;
      animation: popIn 0.2s ease-out;
    }
    @keyframes popIn {
      from { opacity:0; transform: scale(0.92); }
      to   { opacity:1; transform: scale(1); }
    }
    .modal-icon {
      width: 52px; height: 52px; border-radius: 50%;
      background: var(--green-bg); color: var(--green-text);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin: 0 auto 16px;
    }
    .modal-title { font-size: 16px; font-weight: 500; margin-bottom: 6px; }
    .modal-msg   { font-size: 13px; color: var(--text2); line-height: 1.6; }
    .modal-actions { margin-top: 22px; }

    /* â”€â”€â”€ Confetti â”€â”€â”€ */
    .confetti-wrap {
      position: fixed; inset: 0; pointer-events: none; z-index: 201;
    }
    .c-piece {
      position: absolute;
      width: 8px; height: 8px;
      opacity: 0;
      animation: cfFall linear forwards;
    }
    @keyframes cfFall {
      0%   { opacity:1; transform: translateY(-80px) rotate(0deg); }
      100% { opacity:0; transform: translateY(110vh) rotate(400deg); }
    }

    /* â”€â”€â”€ Toast â”€â”€â”€ */
    .toast {
      position: fixed; bottom: 20px; right: 20px;
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 10px 16px;
      display: none; align-items: center; gap: 8px;
      font-size: 13px; color: var(--text);
      box-shadow: 0 4px 20px rgba(0,0,0,0.08);
      z-index: 300;
    }
    .toast.show { display: flex; }
    .toast i    { font-size: 14px; }
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
    <a href="admin_reg.php" class="menu-item active"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>

    <div class="nav-section-label">Support</div>
    <a href="calendar.php"  class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <div class="menu-item"><i class="fas fa-cog"></i> Settings</div>
    <a href="Help.php"      class="menu-item"><i class="fas fa-question-circle"></i> Help</a>

    <div class="sidebar-bottom">
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <div class="page-header">
      <div class="page-eyebrow">Admin Â· Faculty Management</div>
      <div class="page-title">Register Faculty Account</div>
      <div class="page-sub">Create a new faculty account and assign courses.</div>
    </div>

    <div class="form-card">

      <!-- Personal info -->
      <div class="form-card-header">
        <div class="form-card-icon"><i class="fas fa-user"></i></div>
        <div>
          <div class="form-card-title">Personal Information</div>
          <div class="form-card-sub">Name and contact details for the faculty member</div>
        </div>
      </div>
      <div class="form-body">

        <div class="field-row">
          <div class="field-group">
            <label class="field-label" for="firstName">First Name <span class="req">*</span></label>
            <input type="text" id="firstName" class="field-input" placeholder="e.g. Maria">
          </div>
          <div class="field-group">
            <label class="field-label" for="lastName">Last Name <span class="req">*</span></label>
            <input type="text" id="lastName" class="field-input" placeholder="e.g. Santos">
          </div>
        </div>

        <div class="field-row">
          <div class="field-group">
            <label class="field-label" for="email">Email Address <span class="req">*</span></label>
            <input type="email" id="email" class="field-input" placeholder="e.g. m.santos@tip.edu.ph">
          </div>
          <div class="field-group">
            <label class="field-label" for="secondEmail">Secondary Email <span style="font-size:11px;color:var(--text3);font-weight:400">(optional)</span></label>
            <input type="email" id="secondEmail" class="field-input" placeholder="Alternate email address">
          </div>
        </div>

        <hr class="section-divider">
        <div class="section-label">Course Assignment</div>

        <div class="field-row single">
          <div class="field-group">
            <label class="field-label" for="courseCode">Add Course <span class="req">*</span></label>
            <select id="courseCode" class="field-select">
              <option value="">Loading CCS Coursesâ€¦</option>
            </select>
            <div class="field-hint" style="margin-top:6px">Select one or more courses to assign to this faculty.</div>
          </div>
        </div>

        <div class="course-chips" id="selectedCoursesDisplay">
          <span class="chip-empty">No courses selected yet</span>
        </div>

        <hr class="section-divider">
        <div class="section-label">Permissions</div>

        <div class="setting-row">
          <input type="checkbox" id="coAdmin" checked>
          <div>
            <label for="coAdmin">Grant Co-Admin privileges</label>
            <div class="setting-desc">Allows this faculty member to manage course groups and members.</div>
          </div>
        </div>

        <div class="form-actions">
          <button class="btn" onclick="window.location.href='<?= $homePage ?>'">Cancel</button>
          <button class="btn gold" id="saveBtn">
            <i class="fas fa-plus" style="font-size:11px;margin-right:4px"></i>Create Account
          </button>
        </div>

      </div><!-- /.form-body -->
    </div><!-- /.form-card -->

  </main>
</div><!-- /.layout -->

<!-- â•â•â• SUCCESS MODAL â•â•â• -->
<div class="modal-bg" id="successModal">
  <div class="modal">
    <div class="modal-icon"><i class="fas fa-check"></i></div>
    <div class="modal-title">Account created</div>
    <div class="modal-msg" id="successMessage">Faculty account has been set up successfully.</div>
    <div class="modal-actions">
      <button class="btn primary" id="closeModal" style="width:100%;justify-content:center">Continue</button>
    </div>
  </div>
</div>

<!-- â•â•â• CONFETTI â•â•â• -->
<div class="confetti-wrap" id="confettiWrap"></div>

<!-- â•â•â• TOAST â•â•â• -->
<div class="toast" id="toast">
  <i class="fas fa-exclamation-circle" style="color:var(--red-text)"></i>
  <span id="toastMsg">Please fill in all required fields.</span>
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
  const tm = document.getElementById('toastMsg');
  t.querySelector('i').style.color = type === 'ok' ? 'var(--green-text)' : 'var(--red-text)';
  t.querySelector('i').className   = type === 'ok' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
  tm.textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3000);
}

// â”€â”€ Course chips â”€â”€
const courseSelect = document.getElementById('courseCode');
const chipsDisplay = document.getElementById('selectedCoursesDisplay');
let selectedCourses = [];

courseSelect.addEventListener('change', function () {
  const opt = this.options[this.selectedIndex];
  const id  = parseInt(opt.value);
  if (!isNaN(id) && !selectedCourses.some(c => c.id === id)) {
    selectedCourses.push({ id, name: opt.textContent.trim() });
    renderChips();
  }
  this.selectedIndex = 0;
});

function renderChips() {
  if (!selectedCourses.length) {
    chipsDisplay.innerHTML = '<span class="chip-empty">No courses selected yet</span>';
    return;
  }
  chipsDisplay.innerHTML = selectedCourses.map((c, i) => `
    <span class="chip">
      ${c.name}
      <button class="chip-remove" data-i="${i}" title="Remove"><i class="fas fa-times"></i></button>
    </span>`).join('');
  chipsDisplay.querySelectorAll('.chip-remove').forEach(btn => {
    btn.addEventListener('click', () => {
      selectedCourses.splice(parseInt(btn.dataset.i), 1);
      renderChips();
    });
  });
}

// â”€â”€ Load courses â”€â”€
fetch('get_course.php')
  .then(r => r.json())
  .then(data => {
    courseSelect.innerHTML = '<option value="">Select a CCS courseâ€¦</option>';
    data.forEach(c => {
      const o = document.createElement('option');
      o.value = c.course_id;
      o.textContent = `${c.course_code} â€” ${c.course_name}`;
      courseSelect.appendChild(o);
    });
  })
  .catch(() => {
    courseSelect.innerHTML = '<option value="">Failed to load courses</option>';
  });

// â”€â”€ Confetti â”€â”€
function launchConfetti() {
  const wrap = document.getElementById('confettiWrap');
  wrap.innerHTML = '';
  const colors = ['#f5b301','#ff6b6b','#4ecdc4','#45b7d1','#96ceb4','#a78bfa'];
  for (let i = 0; i < 120; i++) {
    const el = document.createElement('div');
    el.className = 'c-piece';
    el.style.left = Math.random() * 100 + '%';
    el.style.background = colors[Math.floor(Math.random() * colors.length)];
    el.style.borderRadius = Math.random() > 0.5 ? '50%' : '0';
    el.style.animationDuration = (2 + Math.random() * 2.5) + 's';
    el.style.animationDelay    = (Math.random() * 1.5) + 's';
    wrap.appendChild(el);
  }
  setTimeout(() => { wrap.innerHTML = ''; }, 6000);
}

// â”€â”€ Save â”€â”€
const saveBtn   = document.getElementById('saveBtn');
const modal     = document.getElementById('successModal');
const successMsg = document.getElementById('successMessage');
const closeModal = document.getElementById('closeModal');

saveBtn.addEventListener('click', () => {
  const firstName = document.getElementById('firstName').value.trim();
  const lastName  = document.getElementById('lastName').value.trim();
  const email     = document.getElementById('email').value.trim();
  const isCoAdmin = document.getElementById('coAdmin').checked ? 1 : 0;

  if (!firstName || !lastName || !email) {
    showToast('Please fill in First Name, Last Name, and Email.', 'err'); return;
  }
  if (!selectedCourses.length) {
    showToast('Please assign at least one course.', 'err'); return;
  }

  saveBtn.disabled = true;
  saveBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin" style="font-size:11px;margin-right:4px"></i>Creatingâ€¦';

  fetch('create_faculty.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
      firstName, lastName, email,
      courses: JSON.stringify(selectedCourses.map(c => c.id)),
      isCoAdmin
    })
  })
    .then(r => r.json())
    .then(data => {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-plus" style="font-size:11px;margin-right:4px"></i>Create Account';

      if (data.status === 'success' || data.status === 'warning') {
        successMsg.textContent = data.message || `Account for ${firstName} ${lastName} created successfully!`;
        modal.classList.add('open');
        launchConfetti();
      } else {
        showToast(data.message || 'Error creating faculty account.', 'err');
      }
    })
    .catch(() => {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-plus" style="font-size:11px;margin-right:4px"></i>Create Account';
      showToast('Server error â€” please try again.', 'err');
    });
});

closeModal.addEventListener('click', () => modal.classList.remove('open'));
modal.addEventListener('click', e => { if (e.target === modal) modal.classList.remove('open'); });
</script>
</body>
</html>