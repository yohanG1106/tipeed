<?php
session_start();
include "db_connect.php";

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

// ── Join by invite code ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_by_link'])) {
    $inviteCode = trim($_POST['invite_code'] ?? '');
    if (!empty($inviteCode)) {
        $s = $conn->prepare("SELECT chat_id, group_name FROM course_chats WHERE invite_code = ?");
        $s->bind_param("s", $inviteCode); $s->execute();
        $chatData = $s->get_result()->fetch_assoc();
        if ($chatData) {
            $tid = $chatData['chat_id'];
            $c = $conn->prepare("SELECT * FROM course_chat_members WHERE chat_id=? AND user_id=?");
            $c->bind_param("ii", $tid, $userId); $c->execute(); $c->store_result();
            if ($c->num_rows === 0) {
                $j = $conn->prepare("INSERT INTO course_chat_members (chat_id,user_id,role,joined_at) VALUES(?,?,'student',NOW())");
                $j->bind_param("ii", $tid, $userId);
                if ($j->execute()) { header("Location: chat_interface.php?chat_id=$tid&joined=1"); exit; }
                else $joinError = "Failed to join. Please try again.";
            } else { header("Location: chat_interface.php?chat_id=$tid"); exit; }
        } else { $joinError = "Invalid invite code. Please check and try again."; }
    } else { $joinError = "Please enter an invite code."; }
}

// ── Fetch accessible chats ────────────────────────────────────
if ($currentUserRole === 'admin') {
    $s = $conn->prepare("SELECT cc.chat_id,cc.group_name,c.course_name,c.course_code,cc.faculty_id,u.first_name as faculty_first,u.last_name as faculty_last FROM course_chats cc JOIN courses c ON cc.course_id=c.course_id JOIN users u ON cc.faculty_id=u.userid ORDER BY cc.group_name");
    $s->execute();
} elseif ($currentUserRole === 'faculty') {
    $s = $conn->prepare("SELECT cc.chat_id,cc.group_name,c.course_name,c.course_code,cc.faculty_id,u.first_name as faculty_first,u.last_name as faculty_last FROM course_chats cc JOIN courses c ON cc.course_id=c.course_id JOIN users u ON cc.faculty_id=u.userid WHERE cc.faculty_id=? UNION SELECT cc.chat_id,cc.group_name,c.course_name,c.course_code,cc.faculty_id,u.first_name,u.last_name FROM course_chat_members cm JOIN course_chats cc ON cm.chat_id=cc.chat_id JOIN courses c ON cc.course_id=c.course_id JOIN users u ON cc.faculty_id=u.userid WHERE cm.user_id=? ORDER BY group_name");
    $s->bind_param("ii", $userId, $userId); $s->execute();
} else {
    $s = $conn->prepare("SELECT cc.chat_id,cc.group_name,c.course_name,c.course_code,cc.faculty_id,u.first_name as faculty_first,u.last_name as faculty_last FROM course_chat_members cm JOIN course_chats cc ON cm.chat_id=cc.chat_id JOIN courses c ON cc.course_id=c.course_id JOIN users u ON cc.faculty_id=u.userid WHERE cm.user_id=? ORDER BY cc.group_name");
    $s->bind_param("i", $userId); $s->execute();
}
$userChats = $s->get_result()->fetch_all(MYSQLI_ASSOC);

if (!$chatId && !empty($userChats)) {
    header("Location: chat_interface.php?chat_id=" . $userChats[0]['chat_id']); exit;
}

// ── Verify access ─────────────────────────────────────────────
$access = null;
if ($chatId) {
    if ($currentUserRole === 'admin') {
        $a = $conn->prepare("SELECT cm.role,cc.group_name,c.course_name,c.course_code,cc.chat_id,cc.description,cc.created_at,cc.faculty_id,u.first_name as faculty_first,u.last_name as faculty_last FROM course_chats cc JOIN courses c ON cc.course_id=c.course_id JOIN users u ON cc.faculty_id=u.userid LEFT JOIN course_chat_members cm ON cc.chat_id=cm.chat_id AND cm.user_id=? WHERE cc.chat_id=?");
        $a->bind_param("ii", $userId, $chatId);
    } elseif ($currentUserRole === 'faculty') {
        $a = $conn->prepare("SELECT cm.role,cc.group_name,c.course_name,c.course_code,cc.chat_id,cc.description,cc.created_at,cc.faculty_id,u.first_name as faculty_first,u.last_name as faculty_last FROM course_chats cc JOIN courses c ON cc.course_id=c.course_id JOIN users u ON cc.faculty_id=u.userid LEFT JOIN course_chat_members cm ON cc.chat_id=cm.chat_id AND cm.user_id=? WHERE cc.chat_id=? AND (cc.faculty_id=? OR cm.user_id=?)");
        $a->bind_param("iiii", $userId, $chatId, $userId, $userId);
    } else {
        $a = $conn->prepare("SELECT cm.role,cc.group_name,c.course_name,c.course_code,cc.chat_id,cc.description,cc.created_at,cc.faculty_id,u.first_name as faculty_first,u.last_name as faculty_last FROM course_chat_members cm JOIN course_chats cc ON cm.chat_id=cc.chat_id JOIN courses c ON cc.course_id=c.course_id JOIN users u ON cc.faculty_id=u.userid WHERE cm.chat_id=? AND cm.user_id=?");
        $a->bind_param("ii", $chatId, $userId);
    }
    $a->execute();
    $access = $a->get_result()->fetch_assoc();
    if (!$access) {
        header("Location: " . (!empty($userChats) ? "chat_interface.php?chat_id=".$userChats[0]['chat_id'] : $homePage));
        exit;
    }
}

// ── Stats & messages ──────────────────────────────────────────
$mc = $conn->prepare("SELECT COUNT(*) as total_members FROM course_chat_members WHERE chat_id=?");
$mc->bind_param("i", $chatId); $mc->execute();
$memberCount = $mc->get_result()->fetch_assoc()['total_members'];

$la = $conn->prepare("SELECT MAX(created_at) as last_activity FROM chat_messages WHERE chat_id=?");
$la->bind_param("i", $chatId); $la->execute();
$lastActivity = $la->get_result()->fetch_assoc()['last_activity'];

$ms = $conn->prepare("SELECT m.*,u.first_name,u.last_name,u.userid FROM chat_messages m JOIN users u ON m.user_id=u.userid WHERE m.chat_id=? ORDER BY m.created_at ASC");
$ms->bind_param("i", $chatId); $ms->execute();
$messages = $ms->get_result()->fetch_all(MYSQLI_ASSOC);
$lastMessageId = !empty($messages) ? end($messages)['message_id'] : 0;

// ── AJAX: send message ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_message'])) {
    $message  = mysqli_real_escape_string($conn, $_POST['message']);
    $filePath = null;
    if (isset($_FILES['file_attachment']) && $_FILES['file_attachment']['error'] === UPLOAD_ERR_OK) {
        $dir = 'uploads/chat_files/';
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $fn = time().'_'.basename($_FILES['file_attachment']['name']);
        $filePath = $dir.$fn;
        if (!move_uploaded_file($_FILES['file_attachment']['tmp_name'], $filePath)) { echo "file_upload_error"; exit; }
    }
    if (!empty(trim($message)) || $filePath) {
        $i = $conn->prepare("INSERT INTO chat_messages(chat_id,user_id,message,file_path)VALUES(?,?,?,?)");
        $i->bind_param("iiss", $chatId, $userId, $message, $filePath);
        echo $i->execute() ? "success" : "error";
    } else { echo "empty"; }
    exit;
}

// ── AJAX: fetch new messages ──────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_messages'], $_GET['last_id'])) {
    $lid = $_GET['last_id'];
    $n = $conn->prepare("SELECT m.*,u.first_name,u.last_name,u.userid FROM chat_messages m JOIN users u ON m.user_id=u.userid WHERE m.chat_id=? AND m.message_id>? ORDER BY m.created_at ASC");
    $n->bind_param("ii", $chatId, $lid); $n->execute();
    header('Content-Type: application/json');
    echo json_encode($n->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

function formatFileSize($b) {
    if ($b == 0) return '0 B';
    $k = 1024; $s = ['B','KB','MB','GB'];
    $i = floor(log($b) / log($k));
    return number_format($b / pow($k, $i), 2).' '.$s[$i];
}
function autoLinkUrls($t) {
    return preg_replace('/(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/i',
        '<a href="$1" target="_blank" rel="noopener noreferrer">$1</a>', $t);
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Chat — <?= isset($access) ? htmlspecialchars($access['group_name']) : 'TiPeed' ?></title>
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
      --chat-list-w: 264px;
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
      --shadow-md: 0 4px 16px rgba(0,0,0,.4);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 15px; -webkit-font-smoothing: antialiased; }
    body {
      font-family: var(--font);
      background: var(--bg); color: var(--text);
      height: 100vh; overflow: hidden;
      transition: background .25s, color .25s;
    }
    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }
    input, textarea { font-family: var(--font); }

    /* ── NAVBAR ──────────────────────────────────── */
    .navbar {
      position: fixed; top: 0; left: 0; right: 0; z-index: 100;
      height: var(--nav-h);
      background: var(--bg-card); border-bottom: 1px solid var(--border);
      display: flex; align-items: center; padding: 0 24px; gap: 20px;
    }
    .nav-logo { font-size: 17px; font-weight: 500; letter-spacing: -.3px; flex-shrink: 0; }
    .nav-logo span { color: var(--text-3); font-weight: 300; }
    .nav-links { display: flex; align-items: center; gap: 4px; flex: 1; padding-left: 8px; }
    .nav-links a { font-size: 14px; color: var(--text-2); padding: 5px 11px; border-radius: 7px; transition: background .15s, color .15s; }
    .nav-links a:hover { background: var(--bg-muted); color: var(--text); }
    .nav-links a.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .nav-right { display: flex; align-items: center; gap: 10px; margin-left: auto; }
    .search-wrap {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 8px; padding: 0 12px; height: 34px;
    }
    .search-wrap i { color: var(--text-3); font-size: 13px; }
    .search-wrap input { background: none; border: none; outline: none; font-size: 13px; color: var(--text); width: 160px; }
    .search-wrap input::placeholder { color: var(--text-3); }
    .theme-btn {
      width: 34px; height: 34px; border: 1px solid var(--border);
      border-radius: 8px; background: var(--bg-input);
      color: var(--text-2); font-size: 14px;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s;
    }
    .theme-btn:hover { background: var(--bg-muted); }

    /* ── FULL-HEIGHT LAYOUT ──────────────────────── */
    .layout {
      display: flex;
      height: calc(100vh - var(--nav-h));
      margin-top: var(--nav-h);
    }

    /* ── LEFT NAV SIDEBAR ────────────────────────── */
    .nav-sidebar {
      width: var(--sidebar-w); flex-shrink: 0;
      border-right: 1px solid var(--border);
      background: var(--bg-card);
      display: flex; flex-direction: column; gap: 6px;
      padding: 20px 12px; overflow-y: auto;
    }
    .sidebar-profile {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 10px 14px;
      border-bottom: 1px solid var(--border); margin-bottom: 6px;
    }
    .avatar-circle {
      width: 36px; height: 36px; border-radius: 50%;
      background: var(--bg-muted); border: 1px solid var(--border-2);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 500; color: var(--text-2);
      flex-shrink: 0; font-family: var(--mono);
    }
    .profile-name { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .profile-role { font-size: 11px; color: var(--text-3); font-family: var(--mono); }
    .menu-item {
      display: flex; align-items: center; gap: 9px;
      padding: 7px 10px; border-radius: 8px;
      font-size: 13.5px; color: var(--text-2);
      transition: background .15s, color .15s;
    }
    .menu-item:hover { background: var(--bg-muted); color: var(--text); }
    .menu-item.active { background: var(--bg-muted); color: var(--text); font-weight: 500; }
    .menu-item i { width: 16px; text-align: center; font-size: 13px; color: var(--text-3); flex-shrink: 0; }
    .menu-divider { height: 1px; background: var(--border); margin: 8px 4px; }

    /* ── CHAT LIST PANEL ─────────────────────────── */
    .chat-list-panel {
      width: var(--chat-list-w); flex-shrink: 0;
      border-right: 1px solid var(--border);
      background: var(--bg-card);
      display: flex; flex-direction: column;
    }
    .clp-header {
      padding: 16px 16px 14px;
      border-bottom: 1px solid var(--border);
      flex-shrink: 0;
    }
    .clp-title { font-size: 14px; font-weight: 500; }
    .clp-sub   { font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-top: 2px; }

    /* search inside panel */
    .clp-search {
      margin: 10px 12px 0; position: relative;
    }
    .clp-search i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--text-3); font-size: 12px; }
    .clp-search input {
      width: 100%; background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 8px; padding: 7px 10px 7px 30px;
      font-size: 13px; color: var(--text); outline: none;
    }
    .clp-search input::placeholder { color: var(--text-3); }
    .clp-search input:focus { border-color: var(--border-2); }

    .chat-list { flex: 1; overflow-y: auto; padding: 8px; }

    .chat-item {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 10px; border-radius: 9px;
      transition: background .15s; cursor: pointer;
      border: 2px solid transparent;
    }
    .chat-item:hover  { background: var(--bg-muted); }
    .chat-item.active { background: var(--bg-muted); border-color: var(--border-2); }

    .ci-ava {
      width: 36px; height: 36px; border-radius: 9px;
      background: var(--bg-muted); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 500; color: var(--text-2);
      font-family: var(--mono); flex-shrink: 0;
    }
    .ci-name    { font-size: 13px; font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ci-course  { font-size: 11px; color: var(--text-3); font-family: var(--mono); margin-top: 1px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ci-faculty { font-size: 10px; color: var(--text-3); margin-top: 1px; font-style: italic; }

    .admin-chip {
      display: inline-block; background: var(--red-bg); color: var(--red);
      font-size: 9px; font-family: var(--mono); font-weight: 500;
      padding: 1px 5px; border-radius: 4px; margin-left: 4px; vertical-align: middle;
    }

    /* Join chat section (students) */
    .join-section {
      padding: 14px; border-top: 1px solid var(--border);
      background: var(--bg-muted); flex-shrink: 0;
    }
    .join-label { font-size: 12px; font-weight: 500; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; color: var(--text-2); }
    .join-label i { font-size: 11px; color: var(--text-3); }
    .join-row { display: flex; gap: 6px; }
    .join-input {
      flex: 1; background: var(--bg-card); border: 1px solid var(--border);
      border-radius: 8px; padding: 8px 11px;
      font-size: 13px; font-family: var(--mono); color: var(--text);
      outline: none; letter-spacing: .06em;
      transition: border-color .15s;
    }
    .join-input:focus { border-color: var(--border-2); }
    .join-input::placeholder { color: var(--text-3); letter-spacing: 0; }
    .join-btn {
      background: var(--accent); color: var(--accent-fg);
      border: none; border-radius: 8px; padding: 0 14px;
      font-size: 13px; font-weight: 500; white-space: nowrap;
      transition: opacity .15s;
    }
    .join-btn:hover { opacity: .85; }
    .join-msg { font-size: 12px; margin-top: 6px; padding: 7px 10px; border-radius: 7px; }
    .join-msg.err { background: var(--red-bg); color: var(--red); }
    .join-msg.ok  { background: var(--green-bg); color: var(--green); }
    .join-hint { font-size: 11px; color: var(--text-3); margin-top: 6px; line-height: 1.5; }

    /* ── MAIN CHAT AREA ──────────────────────────── */
    .chat-main { flex: 1; display: flex; flex-direction: column; min-width: 0; }

    /* Chat header */
    .chat-header {
      padding: 13px 20px; background: var(--bg-card);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
      flex-shrink: 0;
    }
    .ch-name { font-size: 15px; font-weight: 500; letter-spacing: -.15px; }
    .ch-meta { font-size: 12px; color: var(--text-3); font-family: var(--mono); margin-top: 2px; }
    .ch-actions { display: flex; gap: 6px; }
    .icon-btn {
      width: 32px; height: 32px; border: 1px solid var(--border);
      border-radius: 8px; background: var(--bg-input);
      color: var(--text-2); font-size: 13px;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s;
    }
    .icon-btn:hover { background: var(--bg-muted); }

    /* Messages scroll area */
    .messages-area {
      flex: 1; overflow-y: auto; padding: 20px;
      display: flex; flex-direction: column; gap: 12px;
      background: var(--bg);
    }

    /* Individual message */
    .message {
      display: flex; gap: 10px; max-width: 68%;
      animation: fadeUp .2s ease both;
    }
    @keyframes fadeUp { from { opacity:0; transform:translateY(6px); } to { opacity:1; transform:translateY(0); } }
    .message.own { align-self: flex-end; flex-direction: row-reverse; }

    .msg-ava {
      width: 32px; height: 32px; border-radius: 50%;
      background: var(--bg-muted); border: 1px solid var(--border);
      display: flex; align-items: center; justify-content: center;
      font-size: 11px; font-weight: 500; color: var(--text-2);
      font-family: var(--mono); flex-shrink: 0;
    }
    .msg-bubble {
      background: var(--bg-card); border: 1px solid var(--border);
      border-radius: 14px; padding: 10px 14px; max-width: 100%;
    }
    .message.own .msg-bubble {
      background: var(--accent); color: var(--accent-fg);
      border-color: transparent;
    }
    .msg-meta {
      display: flex; align-items: baseline; gap: 6px; margin-bottom: 4px;
    }
    .msg-sender { font-size: 12px; font-weight: 500; color: var(--text-2); }
    .message.own .msg-sender { color: rgba(255,255,255,.65); }
    .msg-time   { font-size: 11px; color: var(--text-3); font-family: var(--mono); }
    .message.own .msg-time { color: rgba(255,255,255,.45); }
    .msg-text   { font-size: 14px; line-height: 1.55; word-break: break-word; }
    .msg-text a { color: var(--blue); text-decoration: underline; text-underline-offset: 2px; }
    .message.own .msg-text a { color: rgba(255,255,255,.85); }

    /* Image attachment */
    .img-attach { margin-top: 8px; border-radius: 8px; overflow: hidden; max-width: 280px; }
    .img-attach img { width: 100%; height: auto; display: block; cursor: pointer; transition: opacity .15s; }
    .img-attach img:hover { opacity: .9; }

    /* File attachment */
    .file-attach {
      display: flex; align-items: center; gap: 8px;
      background: var(--bg-muted); border-radius: 8px;
      padding: 8px 11px; margin-top: 8px; max-width: 260px;
    }
    .message.own .file-attach { background: rgba(255,255,255,.15); }
    .file-attach i { color: var(--text-3); font-size: 16px; flex-shrink: 0; }
    .message.own .file-attach i { color: rgba(255,255,255,.6); }
    .file-attach-name { font-size: 13px; font-weight: 500; flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .file-attach-dl { color: var(--blue); font-size: 13px; flex-shrink: 0; }
    .message.own .file-attach-dl { color: rgba(255,255,255,.8); }

    /* Empty state in messages */
    .chat-empty {
      flex: 1; display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      color: var(--text-3); gap: 10px; text-align: center; padding: 40px 20px;
    }
    .chat-empty i { font-size: 40px; }
    .chat-empty h3 { font-size: 16px; font-weight: 500; color: var(--text-2); }
    .chat-empty p  { font-size: 13px; max-width: 280px; line-height: 1.6; }

    /* ── MESSAGE INPUT BAR ───────────────────────── */
    .input-bar {
      padding: 12px 18px; background: var(--bg-card);
      border-top: 1px solid var(--border);
      display: flex; align-items: flex-end; gap: 8px;
      flex-shrink: 0;
    }
    .input-wrap { flex: 1; display: flex; flex-direction: column; gap: 6px; }
    .msg-input {
      width: 100%; background: var(--bg-input);
      border: 1px solid var(--border); border-radius: 10px;
      padding: 10px 14px; font-size: 14px; color: var(--text);
      outline: none; resize: none; max-height: 120px;
      transition: border-color .15s; line-height: 1.5;
    }
    .msg-input:focus { border-color: var(--border-2); }
    .msg-input::placeholder { color: var(--text-3); }

    /* attach preview */
    .attach-preview { display: flex; gap: 6px; flex-wrap: wrap; }
    .attach-item {
      display: flex; align-items: center; gap: 5px;
      padding: 4px 10px; background: var(--bg-muted);
      border-radius: 20px; font-size: 12px; color: var(--text-2);
    }
    .attach-rm { background: none; border: none; color: var(--red); font-size: 12px; cursor: pointer; line-height: 1; }

    /* file button */
    .file-wrap { position: relative; flex-shrink: 0; }
    .file-wrap input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
    .file-btn {
      width: 38px; height: 38px;
      border: 1px solid var(--border); border-radius: 9px;
      background: var(--bg-input); color: var(--text-2); font-size: 14px;
      display: flex; align-items: center; justify-content: center;
      transition: background .15s;
    }
    .file-btn:hover { background: var(--bg-muted); }

    /* send button */
    .send-btn {
      width: 38px; height: 38px; flex-shrink: 0;
      background: var(--accent); color: var(--accent-fg);
      border: none; border-radius: 9px; font-size: 14px;
      display: flex; align-items: center; justify-content: center;
      transition: opacity .15s;
    }
    .send-btn:hover   { opacity: .85; }
    .send-btn:disabled { opacity: .4; cursor: not-allowed; }

    /* ── IMAGE LIGHTBOX ──────────────────────────── */
    .lightbox {
      display: none; position: fixed; inset: 0; z-index: 300;
      background: rgba(0,0,0,.88);
      align-items: center; justify-content: center;
    }
    .lightbox.open { display: flex; }
    .lightbox img { max-width: 90%; max-height: 90%; border-radius: 8px; }
    .lightbox-close {
      position: absolute; top: 20px; right: 24px;
      background: none; border: none; color: #fff;
      font-size: 28px; cursor: pointer; line-height: 1;
    }

    /* ── SCROLLBAR ───────────────────────────────── */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* ── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 1100px) { .nav-sidebar { display: none; } }
    @media (max-width: 720px)  { .chat-list-panel { display: none; } .nav-links,.search-wrap { display: none; } }
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
    <a href="faculty_chats.php">Faculty</a>
    <?php endif; ?>
    <a href="Community.php">Community</a>
    <a href="aboutus.php">About</a>
  </div>
  <div class="nav-right">
    <div class="search-wrap">
      <i class="fas fa-search"></i>
      <input type="text" id="navSearch" placeholder="Search chats…">
    </div>
    <button class="theme-btn" id="themeBtn"><i class="fas fa-moon"></i></button>
  </div>
</nav>

<!-- ── LAYOUT ─────────────────────────────────────────── -->
<div class="layout">

  <!-- Left nav sidebar -->
  <aside class="nav-sidebar">
    <div class="sidebar-profile">
      <div class="avatar-circle"><?= strtoupper(substr($studentName, 0, 2)) ?></div>
      <div>
        <div class="profile-name"><?= htmlspecialchars($studentName) ?></div>
        <div class="profile-role"><?= htmlspecialchars($studentIDT) ?></div>
      </div>
    </div>
    <a href="profile.php"        class="menu-item"><i class="fas fa-user"></i> Profile</a>
    <a href="<?= $homePage ?>"   class="menu-item"><i class="fas fa-house"></i> Home</a>
    <a href="chat_interface.php" class="menu-item active"><i class="fas fa-comment-dots"></i> Course Chat</a>
    <a href="CourseChat.php"     class="menu-item"><i class="fas fa-comments"></i> Communities</a>
    <a href="Community.php"      class="menu-item"><i class="fas fa-users"></i> Community</a>
    <?php if ($currentUserRole === 'admin'): ?>
    <a href="admin_reg.php"      class="menu-item"><i class="fas fa-user-plus"></i> Register</a>
    <?php endif; ?>
    <div class="menu-divider"></div>
    <a href="calendar.php" class="menu-item"><i class="fas fa-calendar-alt"></i> Calendar</a>
    <a href="#"            class="menu-item"><i class="fas fa-gear"></i> Settings</a>
    <a href="Help.php"     class="menu-item"><i class="fas fa-circle-question"></i> Help</a>
    <div class="menu-divider"></div>
    <a href="logout.php" class="menu-item" style="color:var(--red)">
      <i class="fas fa-arrow-right-from-bracket" style="color:var(--red)"></i> Log out
    </a>
  </aside>

  <!-- Chat list panel -->
  <div class="chat-list-panel">
    <div class="clp-header">
      <div class="clp-title">
        <?php
          if ($currentUserRole === 'admin')   echo 'All Course Chats <span class="admin-chip">ADMIN</span>';
          elseif ($currentUserRole === 'faculty') echo 'My Course Chats';
          else echo 'Joined Chats';
        ?>
      </div>
      <div class="clp-sub"><?= count($userChats) ?> chat<?= count($userChats) !== 1 ? 's' : '' ?></div>
    </div>

    <div class="clp-search">
      <i class="fas fa-search"></i>
      <input type="text" id="chatSearch" placeholder="Filter chats…">
    </div>

    <div class="chat-list" id="chatListEl">
      <?php if (!empty($userChats)): ?>
        <?php foreach ($userChats as $ch): ?>
        <a href="chat_interface.php?chat_id=<?= $ch['chat_id'] ?>"
           class="chat-item <?= $ch['chat_id'] == $chatId ? 'active' : '' ?>"
           data-name="<?= strtolower(htmlspecialchars($ch['group_name'])) ?>"
           data-course="<?= strtolower(htmlspecialchars($ch['course_code'].' '.$ch['course_name'])) ?>">
          <div class="ci-ava"><?= strtoupper(substr($ch['group_name'], 0, 2)) ?></div>
          <div style="min-width:0;flex:1">
            <div class="ci-name"><?= htmlspecialchars($ch['group_name']) ?></div>
            <div class="ci-course"><?= htmlspecialchars($ch['course_code'].' · '.$ch['course_name']) ?></div>
            <?php if ($currentUserRole === 'admin' || $currentUserRole === 'student'): ?>
            <div class="ci-faculty">
              <?= htmlspecialchars($ch['faculty_first'].' '.$ch['faculty_last']) ?>
            </div>
            <?php endif; ?>
          </div>
        </a>
        <?php endforeach; ?>
      <?php else: ?>
      <div class="chat-empty" style="padding:32px 16px">
        <i class="fas fa-comments"></i>
        <p><?= $currentUserRole === 'student' ? 'No chats joined yet.' : 'No chats created yet.' ?></p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Join by code (students only) -->
    <?php if ($currentUserRole === 'student'): ?>
    <div class="join-section">
      <div class="join-label"><i class="fas fa-key"></i> Join New Chat</div>
      <form method="POST" id="joinForm">
        <div class="join-row">
          <input type="text" name="invite_code" class="join-input"
                 placeholder="Invite code"
                 value="<?= isset($_POST['invite_code']) ? htmlspecialchars($_POST['invite_code']) : '' ?>"
                 autocomplete="off">
          <button type="submit" name="join_by_link" class="join-btn">Join</button>
        </div>
        <?php if (isset($joinError)): ?>
        <div class="join-msg err"><i class="fas fa-circle-xmark"></i> <?= $joinError ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['joined'])): ?>
        <div class="join-msg ok"><i class="fas fa-circle-check"></i> Joined successfully!</div>
        <?php endif; ?>
      </form>
      <div class="join-hint">Ask your instructor for an invite code.</div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Main chat area -->
  <div class="chat-main">
    <?php if ($chatId && isset($access)): ?>

    <!-- Chat header -->
    <div class="chat-header">
      <div>
        <div class="ch-name">
          <?= htmlspecialchars($access['group_name']) ?>
          <?php if ($currentUserRole === 'admin'): ?>
          <span class="admin-chip" style="font-size:10px;padding:2px 7px">ADMIN VIEW</span>
          <?php endif; ?>
        </div>
        <div class="ch-meta">
          <span id="memberCount"><?= $memberCount ?></span> member<?= $memberCount != 1 ? 's' : '' ?> ·
          <?php if ($currentUserRole !== 'faculty' || $currentUserRole === 'admin'): ?>
          <?= htmlspecialchars($access['faculty_first'].' '.$access['faculty_last']) ?> ·
          <?php endif; ?>
          Last: <span id="lastActivity"><?= $lastActivity ? date('M j, g:i A', strtotime($lastActivity)) : 'No messages yet' ?></span>
        </div>
      </div>
      <div class="ch-actions">
        <div class="icon-btn" title="Info"><i class="fas fa-circle-info"></i></div>
      </div>
    </div>

    <!-- Messages -->
    <div class="messages-area" id="messagesArea">
      <?php if (!empty($messages)): ?>
        <?php foreach ($messages as $msg):
          $isOwn = $msg['userid'] == $userId;
          $ava   = strtoupper(substr($msg['first_name'],0,1).substr($msg['last_name'],0,1));
          $sender = $isOwn ? 'You' : htmlspecialchars($msg['first_name'].' '.$msg['last_name']);
          $time   = date('M j, g:i A', strtotime($msg['created_at']));
          $ext    = strtolower(pathinfo($msg['file_path'] ?? '', PATHINFO_EXTENSION));
          $imgExts = ['jpg','jpeg','png','gif','bmp','webp'];
        ?>
        <div class="message <?= $isOwn ? 'own' : '' ?>" data-message-id="<?= $msg['message_id'] ?>">
          <div class="msg-ava"><?= $ava ?></div>
          <div class="msg-bubble">
            <div class="msg-meta">
              <span class="msg-sender"><?= $sender ?></span>
              <span class="msg-time"><?= $time ?></span>
            </div>
            <?php if (!empty($msg['message'])): ?>
            <div class="msg-text"><?= autoLinkUrls(htmlspecialchars($msg['message'])) ?></div>
            <?php endif; ?>
            <?php if (!empty($msg['file_path'])):
              $fn   = basename($msg['file_path']);
              $fsz  = file_exists($msg['file_path']) ? formatFileSize(filesize($msg['file_path'])) : '';
              if (in_array($ext, $imgExts)): ?>
            <div class="img-attach">
              <img src="<?= htmlspecialchars($msg['file_path']) ?>"
                   alt="Image"
                   onclick="openLightbox('<?= htmlspecialchars($msg['file_path']) ?>')">
            </div>
            <?php else: ?>
            <div class="file-attach">
              <i class="fas fa-file"></i>
              <span class="file-attach-name"><?= htmlspecialchars($fn) ?></span>
              <a href="<?= htmlspecialchars($msg['file_path']) ?>" download="<?= htmlspecialchars($fn) ?>" class="file-attach-dl">
                <i class="fas fa-download"></i>
              </a>
            </div>
            <?php endif; endif; ?>
          </div>
        </div>
        <?php endforeach; ?>
      <?php else: ?>
      <div class="chat-empty">
        <i class="fas fa-comments"></i>
        <h3>No messages yet</h3>
        <p>Send the first message to start the conversation.</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- Input bar -->
    <div class="input-bar">
      <div class="input-wrap">
        <textarea class="msg-input" id="messageInput" rows="1" placeholder="Type a message…"></textarea>
        <div class="attach-preview" id="attachPreview"></div>
      </div>
      <div class="file-wrap" title="Attach file">
        <input type="file" id="fileInput"
               accept="image/*,.pdf,.doc,.docx,.txt,.zip,.rar,.xls,.xlsx,.ppt,.pptx,.js,.html,.css,.php,.py,.java,.cpp,.c,.cs">
        <div class="file-btn"><i class="fas fa-paperclip"></i></div>
      </div>
      <button class="send-btn" id="sendBtn"><i class="fas fa-paper-plane"></i></button>
    </div>

    <?php else: ?>
    <div class="chat-empty">
      <i class="fas fa-comments"></i>
      <h3>Select a chat</h3>
      <p>Choose a course chat from the list to start messaging.</p>
      <?php if ($currentUserRole === 'student' && empty($userChats)): ?>
      <p style="margin-top:8px">Ask your instructor for an invite code to join a chat.</p>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>

</div><!-- /.layout -->

<!-- Lightbox -->
<div class="lightbox" id="lightbox">
  <button class="lightbox-close" onclick="closeLightbox()">&times;</button>
  <img id="lightboxImg" src="" alt="">
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
    root.setAttribute('data-theme', n); localStorage.setItem('tipeed-theme', n); updateIcon(n);
  });
  function updateIcon(t) { themeBtn.innerHTML = t === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>'; }

  // ── CHAT LIST FILTER ──
  function filterChats(q) {
    document.querySelectorAll('.chat-item').forEach(el => {
      const match = el.dataset.name?.includes(q) || el.dataset.course?.includes(q);
      el.style.display = match ? '' : 'none';
    });
  }
  document.getElementById('chatSearch')?.addEventListener('input', e => filterChats(e.target.value.toLowerCase()));
  document.getElementById('navSearch')?.addEventListener('input', e => filterChats(e.target.value.toLowerCase()));

  // ── MESSAGING ──
  const messagesArea = document.getElementById('messagesArea');
  const messageInput = document.getElementById('messageInput');
  const fileInput    = document.getElementById('fileInput');
  const attachPreview = document.getElementById('attachPreview');
  const sendBtn      = document.getElementById('sendBtn');
  let lastMessageId  = <?= $lastMessageId ?>;
  let isSending      = false;
  let currentAttach  = null;
  const chatId       = <?= $chatId ?: 0 ?>;
  const myId         = <?= $userId ?>;

  if (messageInput) {
    messageInput.addEventListener('input', function () {
      this.style.height = 'auto';
      this.style.height = this.scrollHeight + 'px';
    });
    messageInput.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
    });
  }

  if (fileInput) {
    fileInput.addEventListener('change', function () {
      if (this.files.length > 0) { currentAttach = this.files[0]; showAttachPreview(currentAttach); }
    });
  }

  function showAttachPreview(file) {
    attachPreview.innerHTML = '';
    const div = document.createElement('div');
    div.className = 'attach-item';
    div.innerHTML = `<i class="fas ${getIcon(file.name)}"></i>
      <span style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${file.name}</span>
      <button class="attach-rm" onclick="removeAttach()"><i class="fas fa-times"></i></button>`;
    attachPreview.appendChild(div);
  }
  function removeAttach() { currentAttach = null; attachPreview.innerHTML = ''; fileInput.value = ''; }
  function getIcon(name) {
    const ext = name.split('.').pop().toLowerCase();
    const map = { jpg:'fa-file-image',jpeg:'fa-file-image',png:'fa-file-image',gif:'fa-file-image',
                  pdf:'fa-file-pdf',doc:'fa-file-word',docx:'fa-file-word',
                  xls:'fa-file-excel',xlsx:'fa-file-excel',ppt:'fa-file-powerpoint',pptx:'fa-file-powerpoint',
                  txt:'fa-file-lines',zip:'fa-file-zipper',rar:'fa-file-zipper',
                  js:'fa-file-code',html:'fa-file-code',css:'fa-file-code',php:'fa-file-code',
                  py:'fa-file-code',java:'fa-file-code',cpp:'fa-file-code',c:'fa-file-code',cs:'fa-file-code' };
    return map[ext] || 'fa-file';
  }

  function sendMessage() {
    if (isSending || !chatId) return;
    const msg = messageInput.value.trim();
    if (!msg && !currentAttach) return;
    isSending = true; sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    const fd = new FormData();
    fd.append('ajax_message', 'true');
    fd.append('message', msg);
    if (currentAttach) fd.append('file_attachment', currentAttach);
    fetch(`chat_interface.php?chat_id=${chatId}`, { method: 'POST', body: fd })
      .then(r => r.text())
      .then(res => {
        if (res === 'success') {
          messageInput.value = ''; messageInput.style.height = 'auto';
          removeAttach(); fetchNew();
        } else if (res === 'file_upload_error') { alert('File upload failed.'); }
      })
      .catch(console.error)
      .finally(() => {
        isSending = false; sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        messageInput.focus();
      });
  }

  function fetchNew() {
    if (!chatId) return;
    fetch(`chat_interface.php?chat_id=${chatId}&ajax_get_messages=true&last_id=${lastMessageId}`)
      .then(r => r.json())
      .then(msgs => {
        if (msgs && msgs.length > 0) {
          msgs.forEach(m => { addMessage(m); lastMessageId = Math.max(lastMessageId, m.message_id); });
          scrollBottom(); updateActivity();
        }
      }).catch(console.error);
  }

  function addMessage(m) {
    const isOwn = m.userid == myId;
    const ava   = (m.first_name[0] + m.last_name[0]).toUpperCase();
    const sender = isOwn ? 'You' : `${m.first_name} ${m.last_name}`;
    const t = new Date(m.created_at).toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'numeric',hour12:true});
    let fileHtml = '';
    if (m.file_path) {
      const fn  = m.file_path.split('/').pop();
      const ext = fn.split('.').pop().toLowerCase();
      const imgs = ['jpg','jpeg','png','gif','bmp','webp'];
      if (imgs.includes(ext)) {
        fileHtml = `<div class="img-attach"><img src="${m.file_path}" onclick="openLightbox('${m.file_path}')" alt=""></div>`;
      } else {
        fileHtml = `<div class="file-attach"><i class="fas fa-file"></i><span class="file-attach-name">${fn}</span><a href="${m.file_path}" download="${fn}" class="file-attach-dl"><i class="fas fa-download"></i></a></div>`;
      }
    }
    const txt = m.message ? `<div class="msg-text">${autoLink(m.message)}</div>` : '';
    const el  = document.createElement('div');
    el.className = `message ${isOwn ? 'own' : ''}`;
    el.setAttribute('data-message-id', m.message_id);
    el.innerHTML = `<div class="msg-ava">${ava}</div><div class="msg-bubble"><div class="msg-meta"><span class="msg-sender">${sender}</span><span class="msg-time">${t}</span></div>${txt}${fileHtml}</div>`;
    const empty = messagesArea?.querySelector('.chat-empty');
    if (empty) empty.remove();
    messagesArea?.appendChild(el);
  }

  function autoLink(text) {
    return text.replace(/(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|])/ig,
      url => `<a href="${url}" target="_blank" rel="noopener">${url}</a>`);
  }

  function scrollBottom() { if (messagesArea) messagesArea.scrollTop = messagesArea.scrollHeight; }
  function updateActivity() {
    const el = document.getElementById('lastActivity');
    if (el) el.textContent = new Date().toLocaleString('en-US',{month:'short',day:'numeric',hour:'numeric',minute:'numeric',hour12:true});
  }

  // Lightbox
  function openLightbox(src)  { document.getElementById('lightboxImg').src = src; document.getElementById('lightbox').classList.add('open'); }
  function closeLightbox()    { document.getElementById('lightbox').classList.remove('open'); }
  document.getElementById('lightbox')?.addEventListener('click', e => { if (e.target === e.currentTarget) closeLightbox(); });

  // Send btn
  sendBtn?.addEventListener('click', sendMessage);

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    scrollBottom();
    setInterval(fetchNew, 2000);
    messageInput?.focus();
  });
  window.addEventListener('beforeunload', () => {});

  // Join form — block Enter triggering submit
  document.querySelector('input[name="invite_code"]')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') e.preventDefault();
  });

  <?php if (isset($joinError)): ?>
  document.querySelector('input[name="invite_code"]')?.focus();
  <?php endif; ?>
</script>
</body>
</html>