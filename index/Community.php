<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include "db_connect.php";
session_start();
require 'db_connect.php';

$user_id = $_SESSION['userid'] ?? 0;

$sql = "SELECT
            c.communities_id, c.name, c.description, c.category, c.privacy, c.status, c.created_at,
            CONCAT(u.first_name, ' ', u.last_name) AS creator_name,
            (SELECT COUNT(*) FROM community_members cm WHERE cm.community_id = c.communities_id) AS member_count,
            EXISTS(
                SELECT 1 FROM community_members cm2
                WHERE cm2.community_id = c.communities_id AND cm2.user_id = ?
            ) AS is_member
        FROM communities c
        JOIN users u ON c.created_by = u.userid
        WHERE
            c.status = 'approved'
            OR (
                c.status = 'pending'
                AND (
                    c.created_by = ?
                    OR EXISTS (SELECT 1 FROM users WHERE userid = ? AND role = 'admin')
                )
            )
        ORDER BY c.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $user_id, $user_id, $user_id);
$stmt->execute();
$communities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$studentName     = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$admin_initials  = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';

if ($currentUserRole === 'admin')         $homePage = 'admin_home.php';
elseif ($currentUserRole === 'teacher')   $homePage = 'teacher_home.php';
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

// Category color map (PHP side for badge classes)
$catClass = [
    'Education'     => 'cat-blue',
    'Technology'    => 'cat-purple',
    'Gaming'        => 'cat-green',
    'Sports'        => 'cat-amber',
    'Entertainment' => 'cat-coral',
    'Health'        => 'cat-teal',
    'Other'         => 'cat-gray',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Community â€” TIPeed</title>
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
      --teal-bg:   #f0fdfb;   --teal-text:  #0f766e;
      --amber-bg:  #fef9ec;   --amber-text: #b45309;
      --coral-bg:  #fef2f0;   --coral-text: #c2410c;
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
      --teal-bg:   #0a1e1c;  --teal-text: #2dd4bf;
      --amber-bg:  #251e0e;  --amber-text:#fbbf24;
      --coral-bg:  #2a1614;  --coral-text:#fb923c;
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
    .page-header { margin-bottom: 24px; }
    .page-eyebrow {
      font-size: 11px; font-weight: 500; color: var(--gold-text);
      text-transform: uppercase; letter-spacing: 0.08em;
      margin-bottom: 4px; font-family: var(--mono);
    }
    .page-title-row {
      display: flex; align-items: center; justify-content: space-between;
      gap: 16px; flex-wrap: wrap; margin-bottom: 16px;
    }
    .page-title { font-size: 20px; font-weight: 500; letter-spacing: -0.4px; }

    /* â”€â”€â”€ Filter + search bar â”€â”€â”€ */
    .toolbar {
      display: flex; align-items: center; gap: 10px; flex-wrap: wrap;
      margin-bottom: 24px;
    }
    .filter-bar {
      display: flex; gap: 4px;
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      padding: 4px;
    }
    .filter-btn {
      padding: 5px 12px; border-radius: 6px;
      font-size: 12px; font-weight: 500;
      border: none; background: transparent;
      color: var(--text2); cursor: pointer; transition: var(--trans);
    }
    .filter-btn:hover { color: var(--text); background: var(--tag-bg); }
    .filter-btn.active { background: var(--accent-bg); color: var(--accent-fg); }
    .toolbar-search {
      display: flex; align-items: center; gap: 8px;
      background: var(--surface); border: 0.5px solid var(--border);
      border-radius: var(--radius); padding: 6px 12px; flex: 1; max-width: 260px;
    }
    .toolbar-search i { font-size: 11px; color: var(--text3); }
    .toolbar-search input {
      border: none; background: transparent; outline: none;
      font-family: var(--font); font-size: 13px; color: var(--text); width: 100%;
    }
    .toolbar-search input::placeholder { color: var(--text3); }

    /* â”€â”€â”€ Community grid â”€â”€â”€ */
    .comm-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(272px, 1fr));
      gap: 16px;
    }

    /* â”€â”€â”€ Community card â”€â”€â”€ */
    .comm-card {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      overflow: hidden;
      transition: var(--trans);
      display: flex; flex-direction: column;
    }
    .comm-card:hover {
      border-color: var(--border2);
      box-shadow: 0 4px 20px rgba(0,0,0,0.06);
      transform: translateY(-2px);
    }

    /* Banner */
    .comm-banner {
      height: 80px;
      background: var(--surface2);
      position: relative;
      flex-shrink: 0;
    }
    .comm-banner-inner {
      position: absolute; inset: 0;
      background: repeating-linear-gradient(
        45deg,
        transparent, transparent 10px,
        rgba(0,0,0,0.02) 10px, rgba(0,0,0,0.02) 20px
      );
    }
    /* Per-category banner tint */
    .comm-card[data-cat="Education"]     .comm-banner { background: var(--blue-bg); }
    .comm-card[data-cat="Technology"]    .comm-banner { background: var(--purple-bg); }
    .comm-card[data-cat="Gaming"]        .comm-banner { background: var(--green-bg); }
    .comm-card[data-cat="Sports"]        .comm-banner { background: var(--amber-bg); }
    .comm-card[data-cat="Entertainment"] .comm-banner { background: var(--coral-bg); }
    .comm-card[data-cat="Health"]        .comm-banner { background: var(--teal-bg); }

    .comm-av {
      position: absolute; bottom: -18px; left: 18px;
      width: 40px; height: 40px; border-radius: var(--radius);
      background: var(--surface);
      border: 2px solid var(--surface);
      display: flex; align-items: center; justify-content: center;
      font-size: 15px; font-weight: 500; color: var(--text2);
      font-family: var(--mono);
      box-shadow: 0 1px 4px rgba(0,0,0,0.1);
    }

    /* Options menu (admin) */
    .comm-options {
      position: absolute; top: 10px; right: 10px;
    }
    .comm-options-btn {
      width: 26px; height: 26px;
      background: rgba(255,255,255,0.8);
      border: 0.5px solid rgba(0,0,0,0.08);
      border-radius: var(--radius);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; color: var(--text2);
      cursor: pointer; transition: var(--trans);
    }
    .comm-options-btn:hover { background: var(--surface); color: var(--text); }
    .comm-options-menu {
      display: none; position: absolute; right: 0; top: 30px;
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 4px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      z-index: 20; min-width: 140px;
    }
    .comm-options-menu.open { display: block; }
    .comm-options-menu form { margin: 0; }
    .delete-opt {
      display: flex; align-items: center; gap: 8px;
      width: 100%; padding: 7px 10px;
      background: none; border: none;
      font-family: var(--font); font-size: 12px;
      color: var(--red-text); cursor: pointer;
      border-radius: 6px; transition: var(--trans);
    }
    .delete-opt:hover { background: var(--red-bg); }

    /* Pending badge */
    .pending-badge {
      position: absolute; top: 10px; left: 10px;
      background: var(--gold-bg); color: var(--gold-text);
      font-size: 10px; font-weight: 500; font-family: var(--mono);
      padding: 2px 7px; border-radius: 4px;
      border: 0.5px solid rgba(245,179,1,0.25);
    }

    /* Body */
    .comm-body {
      padding: 28px 18px 18px;
      flex: 1; display: flex; flex-direction: column; gap: 6px;
    }
    .comm-name { font-size: 14px; font-weight: 500; line-height: 1.35; }
    .comm-desc {
      font-size: 12px; color: var(--text2); line-height: 1.55;
      display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
    }
    .comm-tags { display: flex; flex-wrap: wrap; gap: 5px; margin-top: 4px; }
    .tag {
      display: inline-flex; align-items: center; gap: 4px;
      padding: 2px 7px; border-radius: 4px;
      font-size: 10px; font-weight: 500; font-family: var(--mono);
    }
    /* Category tag colors */
    .cat-blue    { background: var(--blue-bg);   color: var(--blue-text); }
    .cat-purple  { background: var(--purple-bg); color: var(--purple-text); }
    .cat-green   { background: var(--green-bg);  color: var(--green-text); }
    .cat-amber   { background: var(--amber-bg);  color: var(--amber-text); }
    .cat-coral   { background: var(--coral-bg);  color: var(--coral-text); }
    .cat-teal    { background: var(--teal-bg);   color: var(--teal-text); }
    .cat-gray    { background: var(--tag-bg);    color: var(--tag-text); }
    .tag-private { background: var(--red-bg);    color: var(--red-text); }
    .tag-public  { background: var(--green-bg);  color: var(--green-text); }

    .comm-meta {
      display: flex; align-items: center; justify-content: space-between;
      font-size: 11px; color: var(--text3); font-family: var(--mono);
      margin-top: 2px;
    }

    /* Footer / action */
    .comm-footer { padding: 12px 18px; border-top: 0.5px solid var(--border); }
    .btn {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 7px 14px; border-radius: var(--radius);
      font-size: 12px; font-weight: 500; font-family: var(--font);
      border: 0.5px solid var(--border);
      background: var(--surface); color: var(--text2);
      cursor: pointer; transition: var(--trans); width: 100%; justify-content: center;
    }
    .btn:hover { background: var(--tag-bg); color: var(--text); }
    .btn.primary  { background: var(--accent-bg); color: var(--accent-fg); border-color: var(--accent-bg); }
    .btn.primary:hover { opacity: 0.85; }
    .btn.gold     { background: var(--gold); color: #fff; border-color: var(--gold); }
    .btn.gold:hover { background: var(--gold-dark); border-color: var(--gold-dark); }
    .btn i { font-size: 11px; }

    /* â”€â”€â”€ Floating create btn â”€â”€â”€ */
    .fab {
      position: fixed; bottom: 28px; right: 28px;
      width: 46px; height: 46px;
      background: var(--accent-bg); color: var(--accent-fg);
      border: none; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-size: 18px; cursor: pointer; z-index: 50;
      transition: var(--trans);
      box-shadow: 0 4px 16px rgba(0,0,0,0.14);
    }
    .fab:hover { opacity: 0.85; transform: scale(1.06); }

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
      width: 440px; max-width: 94vw;
      max-height: 92vh; overflow-y: auto;
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
      display: flex; align-items: center; justify-content: center; cursor: pointer; transition: var(--trans);
    }
    .modal-close:hover { background: var(--tag-bg); color: var(--text); }
    .modal-body { padding: 20px 22px; }
    .form-group { margin-bottom: 14px; }
    .form-label {
      display: block; font-size: 12px; font-weight: 500;
      color: var(--text2); margin-bottom: 5px;
    }
    .form-label .req { color: var(--red-text); margin-left: 2px; }
    .form-input,
    .form-textarea,
    .form-select {
      width: 100%; padding: 8px 11px;
      border: 0.5px solid var(--border);
      border-radius: var(--radius);
      background: var(--surface2); color: var(--text);
      font-family: var(--font); font-size: 13px;
      outline: none; transition: var(--trans);
    }
    .form-input:focus, .form-textarea:focus, .form-select:focus {
      border-color: var(--gold); background: var(--surface);
      box-shadow: 0 0 0 3px rgba(245,179,1,0.1);
    }
    .form-input::placeholder { color: var(--text3); }
    .form-textarea { min-height: 80px; resize: vertical; }
    .modal-footer {
      padding: 14px 22px;
      border-top: 0.5px solid var(--border);
      display: flex; justify-content: flex-end; gap: 8px;
    }
    .modal-footer .btn { width: auto; }
    .form-msg {
      font-size: 12px; padding: 8px 10px; border-radius: var(--radius);
      margin-bottom: 12px; display: none;
    }
    .form-msg.ok  { background: var(--green-bg); color: var(--green-text); display: block; }
    .form-msg.err { background: var(--red-bg);   color: var(--red-text);   display: block; }

    /* â”€â”€â”€ Empty state â”€â”€â”€ */
    .empty-state {
      grid-column: 1 / -1;
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
    <a href="Community.php" class="active">Community</a>
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
    <a href="Community.php"      class="menu-item active"><i class="fas fa-users"></i> Community</a>

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

  <!-- Main -->
  <main class="main">

    <div class="page-header">
      <div class="page-eyebrow">Discover &amp; Connect</div>
      <div class="page-title-row">
        <div class="page-title">Communities</div>
        <button class="btn primary" style="width:auto" id="openCreateModal">
          <i class="fas fa-plus" style="font-size:11px"></i> New Community
        </button>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="joined">Joined</button>
        <button class="filter-btn" data-filter="open">Open</button>
      </div>
      <div class="toolbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search communitiesâ€¦" id="commSearch">
      </div>
    </div>

    <!-- Grid -->
    <div class="comm-grid" id="commGrid">
      <?php if (!empty($communities)): ?>
        <?php foreach ($communities as $c):
          $cat      = htmlspecialchars($c['category']);
          $catCls   = $catClass[$c['category']] ?? 'cat-gray';
          $isPublic = $c['privacy'] === 'Public';
          $joined   = (bool)$c['is_member'];
          $pending  = $c['status'] === 'pending';
          $date     = date('M j, Y', strtotime($c['created_at']));
          $initials = strtoupper(substr($c['name'], 0, 2));
        ?>
        <div class="comm-card"
             data-cat="<?= $cat ?>"
             data-joined="<?= $joined ? '1' : '0' ?>"
             data-open="<?= $isPublic ? '1' : '0' ?>"
             data-name="<?= strtolower(htmlspecialchars($c['name'])) ?>">

          <div class="comm-banner">
            <div class="comm-banner-inner"></div>
            <?php if ($pending): ?>
              <span class="pending-badge">Pending</span>
            <?php endif; ?>
            <div class="comm-av"><?= $initials ?></div>
            <?php if ($_SESSION['role'] === 'admin'): ?>
              <div class="comm-options">
                <button class="comm-options-btn" onclick="toggleOpts(this)">
                  <i class="fas fa-ellipsis-v"></i>
                </button>
                <div class="comm-options-menu">
                  <form method="POST" action="delete_community.php" onsubmit="return confirm('Delete this community?')">
                    <input type="hidden" name="community_id" value="<?= $c['communities_id'] ?>">
                    <button type="submit" class="delete-opt">
                      <i class="fas fa-trash" style="font-size:11px"></i> Delete
                    </button>
                  </form>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <div class="comm-body">
            <div class="comm-name"><?= htmlspecialchars($c['name']) ?></div>
            <div class="comm-desc"><?= htmlspecialchars($c['description']) ?></div>
            <div class="comm-tags">
              <span class="tag <?= $catCls ?>"><?= $cat ?></span>
              <span class="tag <?= $isPublic ? 'tag-public' : 'tag-private' ?>">
                <i class="fas <?= $isPublic ? 'fa-globe' : 'fa-lock' ?>" style="font-size:8px"></i>
                <?= $c['privacy'] ?>
              </span>
            </div>
            <div class="comm-meta">
              <span><i class="fas fa-users" style="margin-right:4px"></i><?= $c['member_count'] ?> members</span>
              <span><?= $date ?></span>
            </div>
          </div>

          <div class="comm-footer">
            <?php if ($joined): ?>
              <a href="coursechat.php?community_id=<?= $c['communities_id'] ?>" class="btn gold">
                <i class="fas fa-comments"></i> Enter Chat
              </a>
            <?php elseif ($isPublic): ?>
              <form method="POST" action="join_community.php" style="margin:0">
                <input type="hidden" name="community_id" value="<?= $c['communities_id'] ?>">
                <button type="submit" class="btn primary">
                  <i class="fas fa-user-plus"></i> Join
                </button>
              </form>
            <?php else: ?>
              <button class="btn" onclick="openPrivate(<?= $c['communities_id'] ?>)">
                <i class="fas fa-lock"></i> Join Private
              </button>
            <?php endif; ?>
          </div>

        </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="fas fa-users"></i></div>
          <div class="empty-title">No communities yet</div>
          <div class="empty-sub">Be the first to create one!</div>
        </div>
      <?php endif; ?>
    </div>

  </main>
</div><!-- /.layout -->

<!-- FAB (mobile / alternate) -->
<button class="fab" id="fabCreate" title="New Community">
  <i class="fas fa-plus"></i>
</button>

<!-- â•â•â• CREATE MODAL â•â•â• -->
<div class="modal-bg" id="createModal">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title">Create a Community</div>
        <div class="modal-sub">Pending admin approval before it goes live.</div>
      </div>
      <button class="modal-close" data-close="createModal">&#x2715;</button>
    </div>
    <div class="modal-body">
      <form id="communityForm">
        <div class="form-group">
          <label class="form-label" for="commName">Name <span class="req">*</span></label>
          <input type="text" id="commName" name="name" class="form-input" placeholder="e.g. Data Science Club" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="commDesc">Description <span class="req">*</span></label>
          <textarea id="commDesc" name="description" class="form-textarea" placeholder="What is this community about?" required></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="form-group">
            <label class="form-label" for="commCat">Category <span class="req">*</span></label>
            <select id="commCat" name="category" class="form-select" required>
              <option value="">Selectâ€¦</option>
              <option>Education</option><option>Technology</option><option>Gaming</option>
              <option>Sports</option><option>Entertainment</option><option>Health</option><option>Other</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="commPrivacy">Privacy <span class="req">*</span></label>
            <select id="commPrivacy" name="privacy" class="form-select" required>
              <option value="Public">Public</option>
              <option value="Private">Private</option>
            </select>
          </div>
        </div>
        <div class="form-group" id="accessCodeGroup" style="display:none">
          <label class="form-label" for="commAccessCode">Access Code</label>
          <input type="text" id="commAccessCode" name="access_code" class="form-input" placeholder="Code members need to join">
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn" data-close="createModal">Cancel</button>
      <button class="btn primary" id="submitCreate">Create Community</button>
    </div>
  </div>
</div>

<!-- â•â•â• PRIVATE ACCESS MODAL â•â•â• -->
<div class="modal-bg" id="privateModal">
  <div class="modal">
    <div class="modal-header">
      <div>
        <div class="modal-title">Private Community</div>
        <div class="modal-sub">Enter the access code to join.</div>
      </div>
      <button class="modal-close" data-close="privateModal">&#x2715;</button>
    </div>
    <div class="modal-body">
      <form id="privateForm">
        <input type="hidden" id="privateCommunityId" name="community_id">
        <div id="privateMsg" class="form-msg"></div>
        <div class="form-group">
          <label class="form-label" for="privateCode">Access Code <span class="req">*</span></label>
          <input type="text" id="privateCode" name="access_code" class="form-input" placeholder="Enter the access codeâ€¦" required>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn" data-close="privateModal">Cancel</button>
      <button class="btn primary" id="submitPrivate">Join</button>
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
  setTimeout(() => t.classList.remove('show'), 3000);
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

document.getElementById('openCreateModal').addEventListener('click', () => openModal('createModal'));
document.getElementById('fabCreate').addEventListener('click', () => openModal('createModal'));

// â”€â”€ Privacy toggle â”€â”€
document.getElementById('commPrivacy').addEventListener('change', function () {
  document.getElementById('accessCodeGroup').style.display = this.value === 'Private' ? '' : 'none';
  document.getElementById('commAccessCode').required = (this.value === 'Private');
});

// â”€â”€ Create community â”€â”€
document.getElementById('submitCreate').addEventListener('click', () => {
  const form = document.getElementById('communityForm');
  if (!form.checkValidity()) { form.reportValidity(); return; }
  const fd = new FormData(form);
  fetch('community_handler.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('Community submitted for approval.', 'ok');
        closeModal('createModal');
        form.reset();
        document.getElementById('accessCodeGroup').style.display = 'none';
        setTimeout(() => location.reload(), 1200);
      } else {
        showToast(data.message || 'Something went wrong.', 'err');
      }
    })
    .catch(() => showToast('Server error â€” please try again.', 'err'));
});

// â”€â”€ Private join â”€â”€
function openPrivate(id) {
  document.getElementById('privateCommunityId').value = id;
  document.getElementById('privateCode').value = '';
  const msg = document.getElementById('privateMsg');
  msg.textContent = '';
  msg.className = 'form-msg';
  openModal('privateModal');
}

document.getElementById('submitPrivate').addEventListener('click', () => {
  const form = document.getElementById('privateForm');
  if (!form.checkValidity()) { form.reportValidity(); return; }
  const fd = new FormData(form);
  fetch('join_private_community.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      const msg = document.getElementById('privateMsg');
      if (data.success) {
        msg.textContent = 'Joined successfully!';
        msg.className = 'form-msg ok';
        setTimeout(() => { closeModal('privateModal'); location.reload(); }, 1200);
      } else {
        msg.textContent = data.message || 'Incorrect access code.';
        msg.className = 'form-msg err';
      }
    })
    .catch(() => {
      const msg = document.getElementById('privateMsg');
      msg.textContent = 'Server error â€” please try again.';
      msg.className = 'form-msg err';
    });
});

// â”€â”€ Admin options menu â”€â”€
function toggleOpts(btn) {
  const menu = btn.nextElementSibling;
  document.querySelectorAll('.comm-options-menu.open').forEach(m => { if (m !== menu) m.classList.remove('open'); });
  menu.classList.toggle('open');
}
document.addEventListener('click', e => {
  if (!e.target.closest('.comm-options')) {
    document.querySelectorAll('.comm-options-menu.open').forEach(m => m.classList.remove('open'));
  }
});

// â”€â”€ Filter â”€â”€
const filterBtns = document.querySelectorAll('.filter-btn');
filterBtns.forEach(btn => {
  btn.addEventListener('click', () => {
    filterBtns.forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterCards();
  });
});
document.getElementById('commSearch').addEventListener('input', filterCards);

function filterCards() {
  const f = document.querySelector('.filter-btn.active').dataset.filter;
  const q = document.getElementById('commSearch').value.toLowerCase();
  document.querySelectorAll('.comm-card').forEach(card => {
    const nameMatch  = card.dataset.name.includes(q);
    const filterMatch =
      f === 'all'    ? true :
      f === 'joined' ? card.dataset.joined === '1' :
      f === 'open'   ? card.dataset.open   === '1' : true;
    card.style.display = (nameMatch && filterMatch) ? '' : 'none';
  });
}
</script>
</body>
</html>