<?php
include "db_connect.php";
session_start();

if (!isset($_SESSION['userid'])) {
    header("Location: auth.php");
    exit;
}

$currentUserRole = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$homePage = $currentUserRole === 'admin' ? 'admin_home.php' : ($currentUserRole === 'faculty' ? 'faculty_home.php' : 'student_home.php');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About â€” TiPeed Forum</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <style>
    /* â”€â”€ TOKENS â”€â”€ */
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
      --shadow:    0 1px 3px rgba(0,0,0,.3);
      --shadow-md: 0 4px 16px rgba(0,0,0,.4);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 15px; -webkit-font-smoothing: antialiased; scroll-behavior: smooth; }
    body { font-family: var(--font); background: var(--bg); color: var(--text); min-height: 100vh; transition: background .25s, color .25s; overflow-x: hidden; }
    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }
    input, textarea { font-family: var(--font); }

    /* â”€â”€ NAVBAR â”€â”€ */
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
    .theme-btn { width: 34px; height: 34px; border: 1px solid var(--border); border-radius: 8px; background: var(--bg-input); color: var(--text-2); font-size: 14px; display: flex; align-items: center; justify-content: center; transition: background .15s, color .15s; }
    .theme-btn:hover { background: var(--bg-muted); color: var(--text); }

    /* â”€â”€ PAGE BODY â”€â”€ */
    .page-body { padding-top: var(--nav-h); }

    /* â”€â”€ HERO â”€â”€ */
    .hero-section {
      display: grid; grid-template-columns: 1fr 1fr;
      min-height: calc(100vh - var(--nav-h));
      align-items: center;
    }
    .hero-left {
      padding: 80px 64px 80px 80px;
      display: flex; flex-direction: column; justify-content: center;
    }
    .hero-eyebrow {
      font-size: 11px; font-weight: 500; color: var(--text-3);
      letter-spacing: .1em; text-transform: uppercase; font-family: var(--mono);
      margin-bottom: 20px;
    }
    .hero-h1 {
      font-size: clamp(40px, 5vw, 68px);
      font-weight: 400; letter-spacing: -2px; line-height: 1.05;
      margin-bottom: 24px;
    }
    .hero-h1 strong { font-weight: 500; }
    .hero-sub {
      font-size: 16px; color: var(--text-2); line-height: 1.7;
      max-width: 420px;
    }
    .hero-right {
      height: calc(100vh - var(--nav-h)); overflow: hidden;
      border-left: 1px solid var(--border);
    }
    .hero-right img {
      width: 100%; height: 100%; object-fit: cover;
      filter: grayscale(20%) contrast(1.05);
      transition: transform 8s ease;
    }
    .hero-right:hover img { transform: scale(1.04); }

    /* â”€â”€ MEANING BAND â”€â”€ */
    .meaning-band {
      background: var(--accent); color: var(--accent-fg);
      padding: 72px 80px;
    }
    .meaning-inner { max-width: 760px; }
    .band-label {
      font-size: 11px; font-weight: 500; color: rgba(247,246,243,.45);
      letter-spacing: .1em; text-transform: uppercase; font-family: var(--mono);
      margin-bottom: 20px;
    }
    .band-h2 {
      font-size: clamp(26px, 3.5vw, 42px);
      font-weight: 400; letter-spacing: -.8px; line-height: 1.2;
      margin-bottom: 20px;
    }
    .band-p { font-size: 16px; line-height: 1.8; color: rgba(247,246,243,.72); }
    .band-p strong { color: var(--accent-fg); font-weight: 500; }

    /* â”€â”€ TWO-COL TEXT SECTION â”€â”€ */
    .two-col-section {
      display: grid; grid-template-columns: 1fr 1fr;
      border-top: 1px solid var(--border);
    }
    .two-col-text {
      padding: 72px 64px;
      border-right: 1px solid var(--border);
    }
    .two-col-text:last-child { border-right: none; }
    .section-label {
      font-size: 11px; font-weight: 500; color: var(--text-3);
      letter-spacing: .1em; text-transform: uppercase; font-family: var(--mono);
      margin-bottom: 16px;
    }
    .section-h2 {
      font-size: clamp(22px, 2.5vw, 32px);
      font-weight: 500; letter-spacing: -.4px; line-height: 1.25;
      margin-bottom: 20px;
    }
    .section-p { font-size: 14px; color: var(--text-2); line-height: 1.8; margin-bottom: 14px; }
    .section-p:last-child { margin-bottom: 0; }

    /* â”€â”€ STATS â”€â”€ */
    .stats-section {
      background: var(--bg-card);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      display: grid; grid-template-columns: repeat(4, 1fr);
    }
    .stat-cell {
      padding: 52px 40px;
      border-right: 1px solid var(--border);
    }
    .stat-cell:last-child { border-right: none; }
    .stat-num {
      font-size: 44px; font-weight: 500; letter-spacing: -2px;
      font-family: var(--mono); margin-bottom: 6px;
      opacity: 0; transform: translateY(16px);
      transition: opacity .6s, transform .6s;
    }
    .stat-lbl {
      font-size: 12px; color: var(--text-3); font-family: var(--mono);
      text-transform: uppercase; letter-spacing: .07em;
    }
    .stat-cell.visible .stat-num { opacity: 1; transform: translateY(0); }
    .stat-cell:nth-child(2).visible .stat-num { transition-delay: .1s; }
    .stat-cell:nth-child(3).visible .stat-num { transition-delay: .2s; }
    .stat-cell:nth-child(4).visible .stat-num { transition-delay: .3s; }

    /* â”€â”€ TEAM â”€â”€ */
    .team-section {
      padding: 80px;
      background: var(--bg);
      border-top: 1px solid var(--border);
    }
    .team-header { margin-bottom: 48px; }
    .team-label {
      font-size: 11px; font-weight: 500; color: var(--text-3);
      letter-spacing: .1em; text-transform: uppercase; font-family: var(--mono);
      margin-bottom: 12px;
    }
    .team-h2 {
      font-size: clamp(24px, 3vw, 38px);
      font-weight: 500; letter-spacing: -.5px;
    }
    .team-grid {
      display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;
    }
    .member-card {
      background: var(--bg-card);
      border: 1px solid var(--border);
      border-radius: var(--radius-lg);
      padding: 32px 28px 24px;
      transition: border-color .2s, box-shadow .2s;
      opacity: 0; transform: translateY(20px);
      transition: opacity .6s, transform .6s, border-color .2s, box-shadow .2s;
    }
    .member-card.visible { opacity: 1; transform: translateY(0); }
    .member-card:nth-child(2).visible { transition-delay: .1s; }
    .member-card:nth-child(3).visible { transition-delay: .2s; }
    .member-card:hover { border-color: var(--border-2); box-shadow: var(--shadow-md); }

    .member-avatar {
      width: 72px; height: 72px; border-radius: 50%;
      object-fit: cover; object-position: top;
      border: 1px solid var(--border-2);
      margin-bottom: 18px; display: block;
      filter: grayscale(15%);
    }
    .member-name { font-size: 16px; font-weight: 500; margin-bottom: 4px; }
    .member-role {
      font-size: 12px; color: var(--text-3);
      font-family: var(--mono); margin-bottom: 16px;
    }
    .member-bio { font-size: 13.5px; color: var(--text-2); line-height: 1.65; margin-bottom: 20px; }
    .member-link {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 12px; color: var(--text-3);
      border: 1px solid var(--border); border-radius: 7px;
      padding: 5px 12px;
      transition: background .15s, color .15s, border-color .15s;
    }
    .member-link:hover { background: var(--bg-muted); color: var(--text-2); border-color: var(--border-2); }
    .member-link i { font-size: 11px; }

    /* â”€â”€ FOOTER â”€â”€ */
    .site-footer {
      background: var(--bg-card);
      border-top: 1px solid var(--border);
      padding: 40px 80px;
      display: flex; align-items: center; justify-content: space-between; gap: 20px;
      flex-wrap: wrap;
    }
    .footer-brand { font-size: 16px; font-weight: 500; letter-spacing: -.3px; }
    .footer-brand span { color: var(--text-3); font-weight: 300; }
    .footer-links { display: flex; align-items: center; gap: 16px; }
    .footer-link {
      width: 32px; height: 32px; border-radius: 8px;
      border: 1px solid var(--border); background: var(--bg-input);
      display: flex; align-items: center; justify-content: center;
      color: var(--text-3); font-size: 13px;
      transition: background .15s, color .15s, border-color .15s;
    }
    .footer-link:hover { background: var(--bg-muted); color: var(--text-2); border-color: var(--border-2); }
    .footer-copy { font-size: 12px; color: var(--text-3); font-family: var(--mono); }

    /* â”€â”€ CHAT WIDGET â”€â”€ */
    .chat-widget { position: fixed; bottom: 24px; right: 24px; z-index: 200; }
    .chat-fab {
      width: 44px; height: 44px; border-radius: 50%;
      background: var(--accent); color: var(--accent-fg);
      border: none; font-size: 16px;
      display: flex; align-items: center; justify-content: center;
      box-shadow: var(--shadow-md);
      transition: opacity .15s, transform .15s;
    }
    .chat-fab:hover { opacity: .88; transform: scale(1.05); }

    .chat-popup {
      position: absolute; bottom: 56px; right: 0;
      width: 320px;
      background: var(--bg-card); border: 1px solid var(--border-2);
      border-radius: var(--radius-lg); box-shadow: var(--shadow-md);
      display: none; flex-direction: column; overflow: hidden;
    }
    .chat-popup.open { display: flex; animation: fadeUp .2s ease; }
    @keyframes fadeUp { from { opacity:0; transform: translateY(8px); } to { opacity:1; transform: translateY(0); } }

    .chat-head {
      padding: 14px 16px; background: var(--bg-muted);
      border-bottom: 1px solid var(--border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .chat-head-title { font-size: 13px; font-weight: 500; }
    .chat-close { background: none; border: none; color: var(--text-3); font-size: 16px; transition: color .15s; }
    .chat-close:hover { color: var(--text); }

    .chat-msgs {
      padding: 14px 14px 10px;
      height: 220px; overflow-y: auto;
      display: flex; flex-direction: column; gap: 8px;
    }
    .msg {
      max-width: 80%; font-size: 13px; line-height: 1.5;
      padding: 8px 12px; border-radius: 10px;
    }
    .msg-bot { background: var(--bg-muted); color: var(--text-2); align-self: flex-start; border-bottom-left-radius: 3px; }
    .msg-user { background: var(--accent); color: var(--accent-fg); align-self: flex-end; border-bottom-right-radius: 3px; }

    .chat-input-row {
      padding: 10px 12px; border-top: 1px solid var(--border);
      display: flex; gap: 8px;
    }
    .chat-input-row input { flex: 1; background: var(--bg-input); border: 1px solid var(--border); border-radius: 8px; padding: 7px 10px; font-size: 13px; color: var(--text); outline: none; transition: border-color .15s; }
    .chat-input-row input:focus { border-color: var(--border-2); }
    .chat-input-row input::placeholder { color: var(--text-3); }
    .chat-send { width: 32px; height: 32px; background: var(--accent); color: var(--accent-fg); border: none; border-radius: 8px; font-size: 12px; display: flex; align-items: center; justify-content: center; transition: opacity .15s; }
    .chat-send:hover { opacity: .85; }

    /* â”€â”€ SCROLL ANIMATIONS â”€â”€ */
    .reveal {
      opacity: 0; transform: translateY(20px);
      transition: opacity .7s ease, transform .7s ease;
    }
    .reveal.visible { opacity: 1; transform: translateY(0); }

    /* â”€â”€ SCROLLBAR â”€â”€ */
    ::-webkit-scrollbar { width: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: var(--border-2); border-radius: 99px; }

    /* â”€â”€ RESPONSIVE â”€â”€ */
    @media (max-width: 960px) {
      .hero-section { grid-template-columns: 1fr; }
      .hero-left { padding: 60px 32px; }
      .hero-right { height: 50vh; border-left: none; border-top: 1px solid var(--border); }
      .two-col-section { grid-template-columns: 1fr; }
      .two-col-text { border-right: none; border-bottom: 1px solid var(--border); padding: 48px 32px; }
      .stats-section { grid-template-columns: repeat(2, 1fr); }
      .stat-cell:nth-child(2) { border-right: none; }
      .stat-cell:nth-child(3) { border-top: 1px solid var(--border); }
      .team-grid { grid-template-columns: 1fr 1fr; }
      .team-section { padding: 60px 32px; }
      .meaning-band { padding: 60px 32px; }
      .site-footer { padding: 32px; }
    }
    @media (max-width: 600px) {
      .team-grid { grid-template-columns: 1fr; }
      .stats-section { grid-template-columns: 1fr 1fr; }
      .stat-cell { padding: 36px 24px; }
      .stat-num { font-size: 32px; }
      .hero-left { padding: 48px 20px; }
      .nav-links { display: none; }
      .search-wrap { display: none; }
      .site-footer { flex-direction: column; align-items: flex-start; gap: 16px; }
    }
  </style>
</head>
<body>

<!-- â”€â”€ NAVBAR â”€â”€ -->
<nav class="navbar">
  <div class="nav-logo">Ti<span>Peed</span></div>
  <div class="nav-links">
    <a href="<?= $homePage ?>">Home</a>
    <?php if ($currentUserRole === 'admin' || $currentUserRole === 'faculty'): ?>
    <a href="student_home.php">Threads</a>
    <a href="faculty_chats.php">Faculty</a>
    <?php endif; ?>
    <a href="community.php">Community</a>
    <a href="aboutus.php" class="active">About</a>
  </div>
  <div class="nav-right">
    <div class="search-wrap">
      <i class="fas fa-search"></i>
      <input type="text" placeholder="Search threadsâ€¦">
    </div>
    <button class="theme-btn" id="themeBtn" title="Toggle dark mode">
      <i class="fas fa-moon"></i>
    </button>
  </div>
</nav>

<div class="page-body">

  <!-- â”€â”€ HERO â”€â”€ -->
  <section class="hero-section">
    <div class="hero-left">
      <div class="hero-eyebrow reveal">TiPeed Forum</div>
      <h1 class="hero-h1 reveal">About<br><strong>our platform</strong></h1>
      <p class="hero-sub reveal">A web-based forum built for collaborative academic discussions among CCS students at the Technological Institute of the Philippines.</p>
    </div>
    <div class="hero-right">
      <img src="https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1000&q=80" alt="Students collaborating">
    </div>
  </section>

  <!-- â”€â”€ MEANING â”€â”€ -->
  <section class="meaning-band">
    <div class="meaning-inner">
      <div class="band-label">The name</div>
      <h2 class="band-h2">What does TiPeed mean?</h2>
      <p class="band-p"><strong>TI</strong> â€” Technological Institute of the Philippines &nbsp;Â·&nbsp; <strong>Pee</strong> â€” Peer-to-Peer &nbsp;Â·&nbsp; <strong>d</strong> â€” Discussions.<br><br>The name reflects our mission to facilitate structured peer-to-peer academic conversations within the TIP community â€” moving fragmented group chats into a purpose-built, organized space.</p>
    </div>
  </section>

  <!-- â”€â”€ PURPOSE + VISION â”€â”€ -->
  <div class="two-col-section">
    <div class="two-col-text">
      <div class="section-label reveal">Purpose</div>
      <h2 class="section-h2 reveal">A central hub for course discussions</h2>
      <p class="section-p reveal">TiPeed is tailored for the academic needs of CCS students â€” a dedicated space for each course, where students initiate threads, share resources, and learn from one another.</p>
      <p class="section-p reveal">Special attention is given to technical and programming discussions: code sharing, troubleshooting, and subject-specific Q&amp;A are first-class features, not afterthoughts.</p>
    </div>
    <div class="two-col-text">
      <div class="section-label reveal">Vision</div>
      <h2 class="section-h2 reveal">The primary collaboration hub for CCS</h2>
      <p class="section-p reveal">We believe platforms should adapt with the educational landscape. TiPeed aims to foster a culture of knowledge sharing and peer-to-peer learning that extends well beyond the classroom.</p>
      <p class="section-p reveal">Through continuous iteration and user-centered design, we're building an ecosystem where students thrive academically while forming meaningful connections with their peers.</p>
    </div>
  </div>

  <!-- â”€â”€ STATS â”€â”€ -->
  <div class="stats-section">
    <div class="stat-cell">
      <div class="stat-num">600+</div>
      <div class="stat-lbl">Active users</div>
    </div>
    <div class="stat-cell">
      <div class="stat-num">700+</div>
      <div class="stat-lbl">Discussion threads</div>
    </div>
    <div class="stat-cell">
      <div class="stat-num">1.2k</div>
      <div class="stat-lbl">Resources shared</div>
    </div>
    <div class="stat-cell">
      <div class="stat-num">110+</div>
      <div class="stat-lbl">Courses supported</div>
    </div>
  </div>

  <!-- â”€â”€ TEAM â”€â”€ -->
  <section class="team-section">
    <div class="team-header">
      <div class="team-label">The people</div>
      <h2 class="team-h2">Meet the team</h2>
    </div>
    <div class="team-grid">

      <div class="member-card">
        <img src="../assets/Johan.jpg" alt="Johan Gallardo" class="member-avatar">
        <div class="member-name">Johan Gallardo</div>
        <div class="member-role">Documentation</div>
        <p class="member-bio">Responsible for crafting clear, comprehensive documentation that keeps the project organized and understandable for the whole team.</p>
        <a href="https://www.instagram.com/johangallardo_/" class="member-link" target="_blank" rel="noopener">
          <i class="fab fa-instagram"></i> @johangallardo_
        </a>
      </div>

      <div class="member-card">
        <img src="../assets/Jhanmatt.jpg" alt="Jhanmatt Gappi" class="member-avatar">
        <div class="member-name">Jhanmatt Gappi</div>
        <div class="member-role">Back-end developer</div>
        <p class="member-bio">Architects the server-side logic, database design, and API endpoints that power TiPeed's core functionality.</p>
        <a href="https://www.instagram.com/ji07734/" class="member-link" target="_blank" rel="noopener">
          <i class="fab fa-instagram"></i> @ji07734
        </a>
      </div>

      <div class="member-card">
        <img src="../assets/Darren.jpg" alt="Darren Penarroyo" class="member-avatar">
        <div class="member-name">Darren Penarroyo</div>
        <div class="member-role">Front-end developer</div>
        <p class="member-bio">Designs and builds the user interface â€” translating ideas into clean, accessible, and responsive web experiences.</p>
        <a href="https://www.instagram.com/da.pnry__/" class="member-link" target="_blank" rel="noopener">
          <i class="fab fa-instagram"></i> @da.pnry__
        </a>
      </div>

    </div>
  </section>

  <!-- â”€â”€ FOOTER â”€â”€ -->
  <footer class="site-footer">
    <div class="footer-brand">Ti<span>Peed</span></div>
    <div class="footer-links">
      <a href="#" class="footer-link" title="Instagram"><i class="fab fa-instagram"></i></a>
      <a href="#" class="footer-link" title="Facebook"><i class="fab fa-facebook-f"></i></a>
      <a href="#" class="footer-link" title="Twitter / X"><i class="fab fa-twitter"></i></a>
    </div>
    <div class="footer-copy">Â© 2024 TiPeed Forum. All rights reserved.</div>
  </footer>

</div>

<!-- â”€â”€ CHAT WIDGET â”€â”€ -->
<div class="chat-widget">
  <div class="chat-popup" id="chatPopup">
    <div class="chat-head">
      <span class="chat-head-title">TiPeed Support</span>
      <button class="chat-close" id="chatClose"><i class="fas fa-times"></i></button>
    </div>
    <div class="chat-msgs" id="chatMsgs">
      <div class="msg msg-bot">Hi! How can I help you today?</div>
    </div>
    <div class="chat-input-row">
      <input type="text" id="chatInput" placeholder="Type a messageâ€¦">
      <button class="chat-send" id="chatSend"><i class="fas fa-paper-plane"></i></button>
    </div>
  </div>
  <button class="chat-fab" id="chatFab" title="Support chat">
    <i class="fas fa-comment"></i>
  </button>
</div>

<script>
  /* â”€â”€ THEME â”€â”€ */
  const root = document.documentElement;
  const themeBtn = document.getElementById('themeBtn');
  const saved = localStorage.getItem('tipeed-theme') || 'light';
  root.setAttribute('data-theme', saved);
  updateIcon(saved);
  themeBtn.addEventListener('click', () => {
    const next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-theme', next);
    localStorage.setItem('tipeed-theme', next);
    updateIcon(next);
  });
  function updateIcon(t) {
    themeBtn.innerHTML = t === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
  }

  /* â”€â”€ SCROLL REVEAL â”€â”€ */
  const observer = new IntersectionObserver((entries) => {
    entries.forEach(el => {
      if (el.isIntersecting) { el.target.classList.add('visible'); }
    });
  }, { threshold: 0.12 });

  document.querySelectorAll('.reveal, .member-card, .stat-cell').forEach(el => observer.observe(el));

  /* â”€â”€ CHAT â”€â”€ */
  const chatFab   = document.getElementById('chatFab');
  const chatPopup = document.getElementById('chatPopup');
  const chatClose = document.getElementById('chatClose');
  const chatInput = document.getElementById('chatInput');
  const chatSend  = document.getElementById('chatSend');
  const chatMsgs  = document.getElementById('chatMsgs');

  chatFab.addEventListener('click', () => chatPopup.classList.toggle('open'));
  chatClose.addEventListener('click', () => chatPopup.classList.remove('open'));

  const botReplies = [
    "Happy to help! Could you give me more details?",
    "Thanks for reaching out. Let me look into that.",
    "Great question! I'll find the right information for you.",
    "I'm here to assist. What specifically are you looking for?",
    "Got it â€” let me check our resources for you."
  ];

  function sendMsg() {
    const text = chatInput.value.trim();
    if (!text) return;
    addMsg(text, true);
    chatInput.value = '';
    setTimeout(() => addMsg(botReplies[Math.floor(Math.random() * botReplies.length)], false), 900);
  }

  function addMsg(text, isUser) {
    const div = document.createElement('div');
    div.className = `msg ${isUser ? 'msg-user' : 'msg-bot'}`;
    div.textContent = text;
    chatMsgs.appendChild(div);
    chatMsgs.scrollTop = chatMsgs.scrollHeight;
  }

  chatSend.addEventListener('click', sendMsg);
  chatInput.addEventListener('keydown', e => { if (e.key === 'Enter') sendMsg(); });
</script>
</body>
</html>