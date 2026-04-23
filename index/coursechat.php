<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

// â”€â”€ AJAX: send message â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_message'])) {
    $message     = mysqli_real_escape_string($conn, $_POST['message']);
    $community_id = $_POST['community_id'];
    $filePath    = null;

    if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/community_files/';
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . '_' . basename($_FILES['file_attachment']['name']);
        $filePath = $uploadDir . $fileName;
        if (!move_uploaded_file($_FILES['file_attachment']['tmp_name'], $filePath)) {
            echo "file_upload_error"; exit;
        }
    }

    if (!empty(trim($message)) || $filePath) {
        $stmt = $conn->prepare("INSERT INTO community_messages (community_id, user_id, message, file_path) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $community_id, $_SESSION['userid'], $message, $filePath);
        echo $stmt->execute() ? "success" : "error";
    } else {
        echo "empty";
    }
    exit;
}

// â”€â”€ AJAX: fetch new messages â”€â”€
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_messages'], $_GET['last_id'])) {
    $lastId      = $_GET['last_id'];
    $community_id = $_GET['community_id'];
    $stmt = $conn->prepare("
        SELECT m.*, u.first_name, u.last_name, u.userid
        FROM community_messages m
        JOIN users u ON m.user_id = u.userid
        WHERE m.community_id = ? AND m.cmessages_id > ?
        ORDER BY m.created_at ASC");
    $stmt->bind_param("ii", $community_id, $lastId);
    $stmt->execute();
    header('Content-Type: application/json');
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

// â”€â”€ Session vars â”€â”€
$studentName     = $_SESSION['first_name'] . " " . $_SESSION['last_name'];
$admin_initials  = strtoupper(substr($_SESSION['first_name'], 0, 1) . substr($_SESSION['last_name'], 0, 1));
$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$isAdmin         = ($currentUserRole === 'admin');

if ($currentUserRole === 'admin')        $homePage = 'admin_home.php';
elseif ($currentUserRole === 'faculty')  $homePage = 'teacher_home.php';
else                                     $homePage = 'student_home.php';

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

// â”€â”€ Community data â”€â”€
$community_id = $_GET['community_id'] ?? null;
$messages     = [];
$community    = null;
$memberCount  = 0;
$lastActivity = null;

if ($community_id) {
    $stmt = $conn->prepare("SELECT * FROM communities WHERE communities_id = ?");
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $community = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$isAdmin) {
        $check = $conn->prepare("SELECT 1 FROM community_members WHERE community_id = ? AND user_id = ?");
        $check->bind_param("ii", $community_id, $_SESSION['userid']);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) { echo "<p>You must join this community to view messages.</p>"; exit; }
    }

    $s = $conn->prepare("SELECT COUNT(*) as c FROM community_members WHERE community_id = ?");
    $s->bind_param("i", $community_id); $s->execute();
    $memberCount = $s->get_result()->fetch_assoc()['c'];

    $s = $conn->prepare("SELECT MAX(created_at) as la FROM community_messages WHERE community_id = ?");
    $s->bind_param("i", $community_id); $s->execute();
    $lastActivity = $s->get_result()->fetch_assoc()['la'];

    $stmt = $conn->prepare("
        SELECT m.cmessages_id, m.message, m.file_path, m.created_at,
               u.first_name, u.last_name, u.userid
        FROM community_messages m
        JOIN users u ON m.user_id = u.userid
        WHERE m.community_id = ?
        ORDER BY m.created_at ASC");
    $stmt->bind_param("i", $community_id);
    $stmt->execute();
    $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$lastMessageId = !empty($messages) ? end($messages)['cmessages_id'] : 0;

function formatFileSize($bytes) {
    if ($bytes == 0) return '0 B';
    $s = ['B','KB','MB','GB'];
    $i = floor(log($bytes) / log(1024));
    return number_format($bytes / pow(1024, $i), 1) . ' ' . $s[$i];
}
function autoLinkUrls($text) {
    return preg_replace('/(\b(https?|ftp):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', htmlspecialchars($text));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Community Chat â€” TIPeed</title>
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
      height: 100vh;
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
      font-family: var(--font); font-size: 13px; color: var(--text); width: 140px;
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

    /* â”€â”€â”€ Shell â”€â”€â”€ */
    .shell {
      display: grid;
      grid-template-columns: 220px 1fr;
      height: calc(100vh - 52px);
      overflow: hidden;
    }

    /* â”€â”€â”€ Left sidebar (nav) â”€â”€â”€ */
    .nav-sidebar {
      background: var(--surface);
      border-right: 0.5px solid var(--border);
      padding: 16px 0; display: flex; flex-direction: column;
      overflow-y: auto;
    }
    .nav-sidebar::-webkit-scrollbar { width: 3px; }
    .nav-sidebar::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }
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
    .nav-sidebar-bottom {
      margin-top: auto; padding: 12px 16px;
      border-top: 0.5px solid var(--border);
    }
    .nav-sidebar-bottom a {
      display: flex; align-items: center; gap: 10px;
      font-size: 12px; color: var(--text3); padding: 6px 0; transition: var(--trans);
    }
    .nav-sidebar-bottom a:hover { color: var(--red-text); }

    /* â”€â”€â”€ Chat shell â”€â”€â”€ */
    .chat-shell {
      display: grid;
      grid-template-columns: 260px 1fr;
      height: 100%;
      overflow: hidden;
    }

    /* â”€â”€â”€ Communities sidebar â”€â”€â”€ */
    .comm-sidebar {
      border-right: 0.5px solid var(--border);
      background: var(--surface);
      display: flex; flex-direction: column;
      overflow: hidden;
    }
    .comm-sidebar-head {
      padding: 16px;
      border-bottom: 0.5px solid var(--border);
      flex-shrink: 0;
    }
    .comm-sidebar-title { font-size: 13px; font-weight: 500; margin-bottom: 2px; }
    .comm-sidebar-sub   { font-size: 11px; color: var(--text3); }
    .comm-search {
      display: flex; align-items: center; gap: 8px;
      margin: 10px 16px 0;
      background: var(--surface2); border: 0.5px solid var(--border);
      border-radius: var(--radius); padding: 6px 10px;
    }
    .comm-search i { font-size: 11px; color: var(--text3); }
    .comm-search input {
      border: none; background: transparent; outline: none;
      font-family: var(--font); font-size: 12px; color: var(--text); width: 100%;
    }
    .comm-search input::placeholder { color: var(--text3); }
    .comm-section-label {
      padding: 10px 16px 6px; font-size: 10px; font-weight: 500;
      color: var(--text3); text-transform: uppercase; letter-spacing: 0.08em;
    }
    .comm-list { flex: 1; overflow-y: auto; padding-bottom: 8px; }
    .comm-list::-webkit-scrollbar { width: 3px; }
    .comm-list::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }
    .comm-item {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 16px; cursor: pointer; transition: var(--trans);
      border-left: 2px solid transparent;
    }
    .comm-item:hover { background: var(--surface2); }
    .comm-item.active { background: var(--surface2); border-left-color: var(--gold); }
    .comm-av {
      width: 34px; height: 34px; border-radius: var(--radius);
      background: var(--gold-bg); color: var(--gold-text);
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 500; flex-shrink: 0; font-family: var(--mono);
    }
    .comm-info { flex: 1; min-width: 0; }
    .comm-name  { font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .comm-desc  { font-size: 11px; color: var(--text3); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 1px; }
    .admin-pip {
      font-size: 9px; font-weight: 500; font-family: var(--mono);
      background: var(--red-bg); color: var(--red-text);
      padding: 1px 5px; border-radius: 3px; flex-shrink: 0;
    }

    /* â”€â”€â”€ Main chat area â”€â”€â”€ */
    .chat-area {
      display: flex; flex-direction: column;
      overflow: hidden; background: var(--bg);
    }
    .chat-topbar {
      display: flex; align-items: center; justify-content: space-between;
      padding: 12px 20px; background: var(--surface);
      border-bottom: 0.5px solid var(--border); flex-shrink: 0;
    }
    .chat-topbar-title { font-size: 14px; font-weight: 500; }
    .chat-topbar-meta  { font-size: 11px; color: var(--text3); margin-top: 2px; font-family: var(--mono); }
    .chat-topbar-actions { display: flex; gap: 4px; }
    .topbar-btn {
      width: 30px; height: 30px; border: none; background: transparent;
      border-radius: var(--radius); color: var(--text3);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; cursor: pointer; transition: var(--trans);
    }
    .topbar-btn:hover { background: var(--tag-bg); color: var(--text); }

    /* â”€â”€â”€ Messages â”€â”€â”€ */
    .messages-wrap {
      flex: 1; overflow-y: auto; padding: 20px;
      display: flex; flex-direction: column; gap: 12px;
    }
    .messages-wrap::-webkit-scrollbar { width: 4px; }
    .messages-wrap::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }

    /* Date separator */
    .date-sep {
      display: flex; align-items: center; gap: 12px;
      font-size: 10px; color: var(--text3); font-family: var(--mono);
      margin: 4px 0;
    }
    .date-sep::before, .date-sep::after {
      content: ''; flex: 1; height: 0.5px; background: var(--border);
    }

    /* Message bubble */
    .msg {
      display: flex; gap: 10px; max-width: 72%;
      animation: fadeUp 0.2s ease;
    }
    @keyframes fadeUp {
      from { opacity:0; transform: translateY(6px); }
      to   { opacity:1; transform: translateY(0); }
    }
    .msg.own { align-self: flex-end; flex-direction: row-reverse; }
    .msg-av {
      width: 30px; height: 30px; border-radius: 50%;
      background: var(--tag-bg); color: var(--text2);
      display: flex; align-items: center; justify-content: center;
      font-size: 10px; font-weight: 500; flex-shrink: 0;
      margin-top: 2px; font-family: var(--mono);
    }
    .msg.own .msg-av { background: var(--gold-bg); color: var(--gold-text); }
    .msg-bubble {
      background: var(--surface);
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 10px 14px;
      max-width: 100%;
    }
    .msg.own .msg-bubble {
      background: var(--accent-bg);
      border-color: var(--accent-bg);
      color: var(--accent-fg);
    }
    .msg-meta {
      display: flex; align-items: baseline; gap: 8px; margin-bottom: 4px;
    }
    .msg-sender { font-size: 11px; font-weight: 500; color: var(--gold-text); }
    .msg.own .msg-sender { color: rgba(247,246,243,0.6); }
    .msg-time   { font-size: 10px; color: var(--text3); font-family: var(--mono); }
    .msg.own .msg-time { color: rgba(247,246,243,0.45); }
    .msg-text   { font-size: 13px; line-height: 1.55; word-break: break-word; }
    .msg-text a { color: var(--blue-text); }
    .msg.own .msg-text a { color: rgba(247,246,243,0.8); text-decoration: underline; }

    /* File attachment */
    .msg-file {
      display: flex; align-items: center; gap: 10px;
      background: rgba(0,0,0,0.04); border-radius: var(--radius);
      padding: 8px 10px; margin-top: 6px; max-width: 280px;
    }
    .msg.own .msg-file { background: rgba(247,246,243,0.12); }
    .msg-file-icon { font-size: 16px; color: var(--text3); flex-shrink: 0; }
    .msg.own .msg-file-icon { color: rgba(247,246,243,0.6); }
    .msg-file-info { flex: 1; min-width: 0; }
    .msg-file-name { font-size: 12px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .msg-file-size { font-size: 10px; color: var(--text3); font-family: var(--mono); }
    .msg.own .msg-file-size { color: rgba(247,246,243,0.5); }
    .msg-file-dl   { color: var(--text3); font-size: 13px; transition: var(--trans); }
    .msg-file-dl:hover { color: var(--text); }
    .msg.own .msg-file-dl { color: rgba(247,246,243,0.6); }
    .msg.own .msg-file-dl:hover { color: var(--accent-fg); }

    /* Image attachment */
    .msg-img { margin-top: 6px; max-width: 260px; border-radius: var(--radius); overflow: hidden; }
    .msg-img img { width: 100%; height: auto; display: block; cursor: zoom-in; transition: opacity 0.15s; }
    .msg-img img:hover { opacity: 0.9; }

    /* Empty state */
    .empty-chat {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: var(--text3); gap: 8px; text-align: center; padding: 40px;
    }
    .empty-chat-icon {
      width: 52px; height: 52px; border-radius: 50%;
      background: var(--tag-bg); color: var(--text3);
      display: flex; align-items: center; justify-content: center;
      font-size: 22px; margin-bottom: 6px;
    }
    .empty-chat-title { font-size: 14px; font-weight: 500; color: var(--text2); }
    .empty-chat-sub   { font-size: 12px; color: var(--text3); }

    /* â”€â”€â”€ Input area â”€â”€â”€ */
    .input-area {
      padding: 14px 20px;
      background: var(--surface);
      border-top: 0.5px solid var(--border);
      flex-shrink: 0;
    }
    .attachment-bar {
      display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px;
    }
    .att-chip {
      display: inline-flex; align-items: center; gap: 6px;
      padding: 4px 10px; background: var(--surface2);
      border: 0.5px solid var(--border); border-radius: 99px;
      font-size: 11px; color: var(--text2);
    }
    .att-chip-name { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .att-chip-rm {
      background: none; border: none; color: var(--text3);
      font-size: 11px; cursor: pointer; padding: 0; transition: var(--trans);
    }
    .att-chip-rm:hover { color: var(--red-text); }
    .input-row {
      display: flex; align-items: flex-end; gap: 8px;
    }
    .input-box {
      flex: 1; position: relative;
    }
    .msg-input {
      width: 100%; padding: 9px 14px;
      border: 0.5px solid var(--border);
      border-radius: var(--radius-lg);
      background: var(--surface2); color: var(--text);
      font-family: var(--font); font-size: 13px;
      outline: none; resize: none; max-height: 110px;
      transition: var(--trans);
    }
    .msg-input:focus { border-color: var(--gold); background: var(--surface); }
    .msg-input::placeholder { color: var(--text3); }
    .attach-btn {
      width: 36px; height: 36px; flex-shrink: 0;
      border: 0.5px solid var(--border); background: var(--surface);
      border-radius: var(--radius); color: var(--text3);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; cursor: pointer; transition: var(--trans);
      position: relative; overflow: hidden;
    }
    .attach-btn:hover { background: var(--tag-bg); color: var(--text); }
    .attach-btn input[type="file"] {
      position: absolute; inset: 0; opacity: 0; cursor: pointer; font-size: 0;
    }
    .send-btn {
      width: 36px; height: 36px; flex-shrink: 0;
      background: var(--accent-bg); color: var(--accent-fg);
      border: none; border-radius: var(--radius);
      display: flex; align-items: center; justify-content: center;
      font-size: 13px; cursor: pointer; transition: var(--trans);
    }
    .send-btn:hover { opacity: 0.85; }
    .send-btn:disabled { background: var(--tag-bg); color: var(--text3); cursor: not-allowed; opacity: 1; }

    /* â”€â”€â”€ Image lightbox â”€â”€â”€ */
    .lightbox {
      position: fixed; inset: 0; background: rgba(0,0,0,0.85);
      z-index: 300; display: none; align-items: center; justify-content: center;
    }
    .lightbox.open { display: flex; }
    .lightbox-close {
      position: absolute; top: 16px; right: 20px;
      background: none; border: none; color: #fff;
      font-size: 24px; cursor: pointer; opacity: 0.7; transition: opacity 0.15s;
    }
    .lightbox-close:hover { opacity: 1; }
    .lightbox img { max-width: 90vw; max-height: 90vh; border-radius: var(--radius-lg); }

    /* â”€â”€â”€ No community selected â”€â”€â”€ */
    .no-comm {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      gap: 10px; color: var(--text3);
    }
    .no-comm-icon {
      width: 64px; height: 64px; border-radius: 50%;
      background: var(--tag-bg); color: var(--text3);
      display: flex; align-items: center; justify-content: center;
      font-size: 28px; margin-bottom: 8px;
    }
    .no-comm-title { font-size: 15px; font-weight: 500; color: var(--text2); }
    .no-comm-sub   { font-size: 13px; color: var(--text3); }
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
    <a href="community.php" class="active">Community</a>
    <a href="aboutus.php">About Us</a>
  </div>
  <div class="nav-right">
    <div class="search">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search topicsâ€¦" id="globalSearch">
    </div>
    <button class="icon-btn" id="darkToggle" title="Toggle dark mode">
      <i class="fas fa-moon" id="darkIcon"></i>
    </button>
  </div>
</nav>

<!-- â•â•â• SHELL â•â•â• -->
<div class="shell">

  <!-- Nav sidebar -->
  <aside class="nav-sidebar">
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
    <a href="coursechat.php"     class="menu-item active"><i class="fas fa-comments"></i> Communities Chat</a>
    <a href="community.php"      class="menu-item"><i class="fas fa-users"></i> Community</a>

    <?php if ($currentUserRole === 'admin'): ?>
    <div class="nav-section-label">Admin</div>
    <a href="admin_reg.php" class="menu-item"><i class="fas fa-user-plus"></i> Register</a>

    <?php endif; ?>

    <div class="nav-section-label">Support</div>
    <a href="calendar.php"  class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <div class="menu-item"><i class="fas fa-cog"></i> Settings</div>
    <a href="Help.php"      class="menu-item"><i class="fas fa-question-circle"></i> Help</a>

    <div class="nav-sidebar-bottom">
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
    </div>
  </aside>

  <!-- Chat shell -->
  <div class="chat-shell">

    <!-- Communities list -->
    <div class="comm-sidebar">
      <div class="comm-sidebar-head">
        <div class="comm-sidebar-title">Communities</div>
        <div class="comm-sidebar-sub"><?= $isAdmin ? 'Admin â€” all groups visible' : 'Your joined groups' ?></div>
      </div>
      <div class="comm-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Filter communitiesâ€¦" id="commSearch">
      </div>
      <div class="comm-section-label"><?= $isAdmin ? 'All Communities' : 'Your Communities' ?></div>
      <div class="comm-list" id="commList">
        <?php
        if ($isAdmin) {
            $stmt = $conn->prepare("SELECT * FROM communities ORDER BY created_at DESC");
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("SELECT c.* FROM communities c JOIN community_members m ON c.communities_id = m.community_id WHERE m.user_id = ? ORDER BY c.created_at DESC");
            $stmt->bind_param("i", $_SESSION['userid']);
            $stmt->execute();
        }
        $communities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        ?>
        <?php if (!empty($communities)): ?>
          <?php foreach ($communities as $comm): ?>
            <a href="coursechat.php?community_id=<?= $comm['communities_id'] ?>"
               class="comm-item <?= $comm['communities_id'] == $community_id ? 'active' : '' ?>">
              <div class="comm-av"><?= strtoupper(substr($comm['name'], 0, 2)) ?></div>
              <div class="comm-info">
                <div class="comm-name"><?= htmlspecialchars($comm['name']) ?></div>
                <div class="comm-desc"><?= htmlspecialchars($comm['description'] ?? 'Community group') ?></div>
              </div>
              <?php if ($isAdmin): ?><span class="admin-pip">ALL</span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-chat" style="padding:32px 16px">
            <div class="empty-chat-icon"><i class="fas fa-users"></i></div>
            <div class="empty-chat-title"><?= $isAdmin ? 'No communities yet' : 'No communities joined' ?></div>
            <div class="empty-chat-sub"><?= $isAdmin ? 'None have been created yet.' : 'Browse Community to join one.' ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div><!-- /.comm-sidebar -->

    <!-- Main chat area -->
    <?php if ($community_id && $community): ?>
    <div class="chat-area">
      <!-- Top bar -->
      <div class="chat-topbar">
        <div>
          <div class="chat-topbar-title">
            <?= htmlspecialchars($community['name']) ?>
            <?php if ($isAdmin): ?>
              <span class="admin-pip" style="margin-left:6px;font-size:9px">ADMIN</span>
            <?php endif; ?>
          </div>
          <div class="chat-topbar-meta">
            <span id="memberCount"><?= $memberCount ?></span> members Â·
            Last active: <span id="lastActivity"><?= $lastActivity ? date('M j, g:i A', strtotime($lastActivity)) : 'no activity yet' ?></span>
          </div>
        </div>
        <div class="chat-topbar-actions">
          <button class="topbar-btn" title="Search"><i class="fas fa-search"></i></button>
          <button class="topbar-btn" title="Info"><i class="fas fa-circle-info"></i></button>
        </div>
      </div>

      <!-- Messages -->
      <div class="messages-wrap" id="messagesWrap">
        <?php if (!empty($messages)): ?>
          <?php
          $prevDate = null;
          foreach ($messages as $msg):
            $msgDate = date('F j, Y', strtotime($msg['created_at']));
            $isOwn   = $msg['userid'] == $_SESSION['userid'];
            $initials = strtoupper(substr($msg['first_name'],0,1) . substr($msg['last_name'],0,1));
            $senderLabel = $isOwn ? 'You' : htmlspecialchars($msg['first_name'] . ' ' . $msg['last_name']);
            $timeLabel   = date('g:i A', strtotime($msg['created_at']));
          ?>
            <?php if ($msgDate !== $prevDate): ?>
              <div class="date-sep"><?= $msgDate ?></div>
              <?php $prevDate = $msgDate; ?>
            <?php endif; ?>
            <div class="msg <?= $isOwn ? 'own' : '' ?>" data-mid="<?= $msg['cmessages_id'] ?>">
              <div class="msg-av"><?= $initials ?></div>
              <div class="msg-bubble">
                <div class="msg-meta">
                  <span class="msg-sender"><?= $senderLabel ?></span>
                  <span class="msg-time"><?= $timeLabel ?></span>
                </div>
                <?php if (!empty($msg['message'])): ?>
                  <div class="msg-text"><?= autoLinkUrls($msg['message']) ?></div>
                <?php endif; ?>
                <?php if (!empty($msg['file_path'])): ?>
                  <?php
                    $ext = strtolower(pathinfo($msg['file_path'], PATHINFO_EXTENSION));
                    $imgExts = ['jpg','jpeg','png','gif','bmp','webp'];
                    $fname = basename($msg['file_path']);
                    $fsize = file_exists($msg['file_path']) ? formatFileSize(filesize($msg['file_path'])) : '';
                  ?>
                  <?php if (in_array($ext, $imgExts)): ?>
                    <div class="msg-img">
                      <img src="<?= $msg['file_path'] ?>" alt="Image"
                           onclick="openLightbox('<?= $msg['file_path'] ?>')">
                    </div>
                  <?php else: ?>
                    <div class="msg-file">
                      <i class="fas fa-file msg-file-icon"></i>
                      <div class="msg-file-info">
                        <div class="msg-file-name"><?= htmlspecialchars($fname) ?></div>
                        <div class="msg-file-size"><?= $fsize ?></div>
                      </div>
                      <a href="<?= $msg['file_path'] ?>" class="msg-file-dl" download="<?= htmlspecialchars($fname) ?>">
                        <i class="fas fa-download"></i>
                      </a>
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-chat">
            <div class="empty-chat-icon"><i class="fas fa-comments"></i></div>
            <div class="empty-chat-title">No messages yet</div>
            <div class="empty-chat-sub">Be the first to say something!</div>
          </div>
        <?php endif; ?>
      </div><!-- /.messages-wrap -->

      <!-- Input -->
      <div class="input-area">
        <div class="attachment-bar" id="attachBar"></div>
        <div class="input-row">
          <div class="input-box">
            <textarea class="msg-input" id="msgInput" placeholder="Write a messageâ€¦" rows="1"></textarea>
          </div>
          <label class="attach-btn" title="Attach file">
            <i class="fas fa-paperclip"></i>
            <input type="file" id="fileInput" accept="image/*,.pdf,.doc,.docx,.txt,.zip,.rar,.xls,.xlsx,.ppt,.pptx,.js,.html,.css,.php,.py,.java,.cpp,.c,.cs">
          </label>
          <button class="send-btn" id="sendBtn" title="Send">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
      </div>
    </div><!-- /.chat-area -->

    <?php else: ?>
    <div class="no-comm">
      <div class="no-comm-icon"><i class="fas fa-users"></i></div>
      <div class="no-comm-title">Select a community</div>
      <div class="no-comm-sub">Choose a group from the list to start chatting.</div>
      <?php if ($isAdmin): ?>
        <div style="font-size:12px;color:var(--gold-text);margin-top:8px">
          <i class="fas fa-shield-alt" style="margin-right:4px"></i>You have admin access to all communities.
        </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div><!-- /.chat-shell -->
</div><!-- /.shell -->

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
  <button class="lightbox-close" onclick="closeLightbox()">&#x2715;</button>
  <img id="lightboxImg" src="" alt="">
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

// â”€â”€ Community filter â”€â”€
document.getElementById('commSearch')?.addEventListener('input', function () {
  const q = this.value.toLowerCase();
  document.querySelectorAll('.comm-item').forEach(el => {
    el.style.display = el.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
});

// â”€â”€ Chat logic â”€â”€
const wrap      = document.getElementById('messagesWrap');
const msgInput  = document.getElementById('msgInput');
const fileInput = document.getElementById('fileInput');
const attachBar = document.getElementById('attachBar');
const sendBtn   = document.getElementById('sendBtn');
const communityId = <?= $community_id ? (int)$community_id : 'null' ?>;
let lastMsgId   = <?= (int)$lastMessageId ?>;
let isSending   = false;
let attachment  = null;
let refreshTimer;

// Auto-resize textarea
if (msgInput) {
  msgInput.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
  });
  msgInput.addEventListener('keydown', e => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
  });
}

// File input
if (fileInput) {
  fileInput.addEventListener('change', function () {
    if (!this.files.length) return;
    attachment = this.files[0];
    renderAttachBar();
  });
}

function renderAttachBar() {
  if (!attachBar) return;
  if (!attachment) { attachBar.innerHTML = ''; return; }
  attachBar.innerHTML = `
    <div class="att-chip">
      <i class="fas fa-paperclip" style="font-size:10px;color:var(--text3)"></i>
      <span class="att-chip-name">${attachment.name}</span>
      <button class="att-chip-rm" onclick="clearAttachment()">&#x2715;</button>
    </div>`;
}

function clearAttachment() {
  attachment = null;
  if (fileInput) fileInput.value = '';
  renderAttachBar();
}

// Send
async function sendMessage() {
  if (isSending || !communityId) return;
  const msg = msgInput ? msgInput.value.trim() : '';
  if (!msg && !attachment) return;

  isSending = true;
  if (sendBtn) { sendBtn.disabled = true; sendBtn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>'; }

  const fd = new FormData();
  fd.append('ajax_message', 'true');
  fd.append('message', msg);
  fd.append('community_id', communityId);
  if (attachment) fd.append('file_attachment', attachment);

  try {
    const res  = await fetch('coursechat.php', { method: 'POST', body: fd });
    const text = await res.text();
    if (text === 'success') {
      if (msgInput) { msgInput.value = ''; msgInput.style.height = 'auto'; }
      clearAttachment();
      await fetchNew();
    }
  } catch(e) { console.error(e); }

  isSending = false;
  if (sendBtn) { sendBtn.disabled = false; sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>'; }
  if (msgInput) msgInput.focus();
}

if (sendBtn) sendBtn.addEventListener('click', sendMessage);

// Fetch new messages
async function fetchNew() {
  if (!communityId) return;
  try {
    const res  = await fetch(`coursechat.php?ajax_get_messages=true&last_id=${lastMsgId}&community_id=${communityId}`);
    const msgs = await res.json();
    if (msgs?.length) {
      msgs.forEach(m => {
        if (!document.querySelector(`[data-mid="${m.cmessages_id}"]`)) {
          appendMessage(m);
          lastMsgId = Math.max(lastMsgId, m.cmessages_id);
        }
      });
      scrollBottom();
      updateLastActivity();
    }
  } catch(e) { /* silent */ }
}

function appendMessage(m) {
  if (!wrap) return;
  const isOwn = m.userid == <?= (int)$_SESSION['userid'] ?>;
  const init  = (m.first_name[0] + m.last_name[0]).toUpperCase();
  const sender = isOwn ? 'You' : escHtml(m.first_name + ' ' + m.last_name);
  const time   = new Date(m.created_at).toLocaleTimeString('en-US', { hour:'numeric', minute:'2-digit', hour12:true });

  // Remove empty state
  const es = wrap.querySelector('.empty-chat');
  if (es) es.remove();

  const el = document.createElement('div');
  el.className = `msg${isOwn ? ' own' : ''}`;
  el.dataset.mid = m.cmessages_id;

  let fileHtml = '';
  if (m.file_path) {
    const fname = m.file_path.split('/').pop();
    const ext   = fname.split('.').pop().toLowerCase();
    const imgExts = ['jpg','jpeg','png','gif','bmp','webp'];
    if (imgExts.includes(ext)) {
      fileHtml = `<div class="msg-img"><img src="${m.file_path}" alt="Image" onclick="openLightbox('${m.file_path}')"></div>`;
    } else {
      fileHtml = `
        <div class="msg-file">
          <i class="fas fa-file msg-file-icon"></i>
          <div class="msg-file-info">
            <div class="msg-file-name">${escHtml(fname)}</div>
          </div>
          <a href="${m.file_path}" class="msg-file-dl" download="${escHtml(fname)}"><i class="fas fa-download"></i></a>
        </div>`;
    }
  }

  const msgHtml = m.message ? `<div class="msg-text">${autoLink(escHtml(m.message))}</div>` : '';

  el.innerHTML = `
    <div class="msg-av">${init}</div>
    <div class="msg-bubble">
      <div class="msg-meta">
        <span class="msg-sender">${sender}</span>
        <span class="msg-time">${time}</span>
      </div>
      ${msgHtml}${fileHtml}
    </div>`;

  wrap.appendChild(el);
}

function autoLink(text) {
  return text.replace(/(\b(https?|ftp):\/\/[-A-Z0-9+&@#/%?=~_|!:,.;]*[-A-Z0-9+&@#/%=~_|])/ig,
    url => `<a href="${url}" target="_blank" rel="noopener noreferrer">${url}</a>`);
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function scrollBottom() { if (wrap) wrap.scrollTop = wrap.scrollHeight; }

function updateLastActivity() {
  const el = document.getElementById('lastActivity');
  if (el) el.textContent = new Date().toLocaleString('en-US', { month:'short', day:'numeric', hour:'numeric', minute:'2-digit', hour12:true });
}

// Lightbox
function openLightbox(src) {
  document.getElementById('lightboxImg').src = src;
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() { document.getElementById('lightbox').classList.remove('open'); }
document.getElementById('lightbox')?.addEventListener('click', e => { if (e.target.id === 'lightbox') closeLightbox(); });

// Init
document.addEventListener('DOMContentLoaded', () => {
  scrollBottom();
  if (msgInput) msgInput.focus();
  refreshTimer = setInterval(fetchNew, 2000);
});
window.addEventListener('beforeunload', () => clearInterval(refreshTimer));
</script>
</body>
</html>