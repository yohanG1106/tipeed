<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>TiPeed — Sign In</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;1,9..40,300&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= filemtime('assets/css/style.css'); ?>">
  <script src="assets/js/app.js?v=<?= filemtime('assets/js/app.js'); ?>"></script>

  <style>
    /* ── TOKENS ──────────────────────────────────── */
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
      --radius:    10px;
      --radius-lg: 16px;
      --font:      'DM Sans', sans-serif;
      --mono:      'DM Mono', monospace;
      --shadow:    0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04);
      --shadow-lg: 0 8px 40px rgba(0,0,0,.10);
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
      --shadow-lg: 0 8px 40px rgba(0,0,0,.5);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 15px; -webkit-font-smoothing: antialiased; }

    body {
      font-family: var(--font);
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
      display: flex;
      transition: background .25s, color .25s;
    }

    a { color: inherit; text-decoration: none; }
    button { font-family: var(--font); cursor: pointer; }
    input, select, textarea { font-family: var(--font); }

    /* ── SLIDESHOW SIDE ──────────────────────────── */
    .visual-side {
      flex: 1;
      position: relative;
      overflow: hidden;
      display: flex;
      align-items: flex-end;
      padding: 48px;
    }

    .slide-bg {
      position: absolute; inset: 0; z-index: 0;
    }
    .slide-bg img {
      position: absolute; inset: 0;
      width: 100%; height: 100%; object-fit: cover;
      opacity: 0;
      transition: opacity 1s ease;
    }
    .slide-bg img.active { opacity: 1; }

    /* warm-to-transparent overlay */
    .slide-bg::after {
      content: '';
      position: absolute; inset: 0;
      background: linear-gradient(
        135deg,
        rgba(26,25,22,.72) 0%,
        rgba(26,25,22,.30) 60%,
        rgba(26,25,22,.05) 100%
      );
      z-index: 1;
    }

    .visual-content {
      position: relative; z-index: 2;
    }
    .visual-logo {
      font-size: 15px; font-weight: 500; color: rgba(247,246,243,.6);
      font-family: var(--mono); letter-spacing: .12em;
      text-transform: uppercase; margin-bottom: 28px;
    }
    .visual-tagline {
      font-size: clamp(28px, 3.5vw, 44px);
      font-weight: 300; color: #f7f6f3;
      line-height: 1.18; letter-spacing: -.6px;
    }
    .visual-tagline strong { font-weight: 500; display: block; }
    .visual-tagline em {
      font-style: normal; color: rgba(247,246,243,.5);
      font-size: .65em; display: block; margin-top: 14px;
      font-weight: 300; letter-spacing: .02em; line-height: 1.6;
    }

    /* slide dots */
    .slide-dots {
      display: flex; gap: 6px; margin-top: 36px;
    }
    .slide-dot {
      width: 20px; height: 2px; border-radius: 2px;
      background: rgba(247,246,243,.3);
      transition: background .3s, width .3s;
    }
    .slide-dot.active { background: #f7f6f3; width: 32px; }

    /* ── AUTH SIDE ───────────────────────────────── */
    .auth-side {
      width: 420px;
      flex-shrink: 0;
      background: var(--bg-card);
      border-left: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      padding: 36px 40px;
      overflow-y: auto;
    }

    .auth-top {
      display: flex; align-items: center; justify-content: space-between;
      margin-bottom: 40px;
    }
    .auth-logo {
      font-size: 16px; font-weight: 500; letter-spacing: -.2px;
    }
    .auth-logo span { color: var(--text-3); font-weight: 300; }

    .theme-btn {
      width: 32px; height: 32px;
      border: 1px solid var(--border); border-radius: 8px;
      background: var(--bg-muted); color: var(--text-2);
      font-size: 13px; display: flex; align-items: center; justify-content: center;
      transition: background .15s, color .15s;
    }
    .theme-btn:hover { background: var(--bg); color: var(--text); }

    /* Tab switcher */
    .tab-row {
      display: flex; gap: 2px;
      background: var(--bg-muted); border-radius: 10px;
      padding: 3px; margin-bottom: 32px;
    }
    .tab-btn {
      flex: 1; padding: 7px 10px; border: none;
      background: none; border-radius: 8px;
      font-size: 13px; font-weight: 500; color: var(--text-3);
      transition: background .2s, color .2s;
    }
    .tab-btn.active {
      background: var(--bg-card); color: var(--text);
      box-shadow: var(--shadow);
    }

    /* Form panels */
    .form-panel { display: none; flex-direction: column; gap: 12px; flex: 1; }
    .form-panel.active { display: flex; }

    .panel-heading { margin-bottom: 4px; }
    .panel-heading h2 { font-size: 20px; font-weight: 500; letter-spacing: -.3px; }
    .panel-heading p { font-size: 13px; color: var(--text-3); margin-top: 4px; }

    .field-group { display: flex; flex-direction: column; gap: 5px; }
    .field-label {
      font-size: 11px; font-weight: 500; color: var(--text-3);
      text-transform: uppercase; letter-spacing: .08em;
      font-family: var(--mono);
    }

    .field-input {
      width: 100%;
      background: var(--bg-input); border: 1px solid var(--border);
      border-radius: 9px; padding: 10px 13px;
      font-size: 14px; color: var(--text); outline: none;
      transition: border-color .15s, background .15s;
      appearance: none;
    }
    .field-input:focus { border-color: var(--border-2); background: var(--bg-card); }
    .field-input::placeholder { color: var(--text-3); }

    .pw-wrap { position: relative; }
    .pw-wrap .field-input { padding-right: 40px; }
    .pw-eye {
      position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
      color: var(--text-3); font-size: 13px; cursor: pointer;
      transition: color .15s;
    }
    .pw-eye:hover { color: var(--text-2); }

    .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

    .select-wrap { position: relative; }
    .select-wrap::after {
      content: '\f078';
      font-family: 'Font Awesome 6 Free'; font-weight: 900;
      position: absolute; right: 13px; top: 50%; transform: translateY(-50%);
      font-size: 10px; color: var(--text-3); pointer-events: none;
    }
    .select-wrap select { cursor: pointer; }

    .agree-row {
      display: flex; align-items: flex-start; gap: 9px;
      font-size: 13px; color: var(--text-2);
    }
    .agree-row input[type="checkbox"] {
      width: 15px; height: 15px; margin-top: 2px;
      accent-color: var(--accent); cursor: pointer; flex-shrink: 0;
    }
    .agree-row a { color: var(--accent); text-decoration: underline; text-underline-offset: 2px; }

    .submit-btn {
      width: 100%; background: var(--accent); color: var(--accent-fg);
      border: none; border-radius: 9px; padding: 11px;
      font-size: 14px; font-weight: 500; letter-spacing: .01em;
      transition: opacity .15s; margin-top: 4px;
    }
    .submit-btn:hover { opacity: .85; }
    .submit-btn:disabled { opacity: .5; cursor: not-allowed; }

    .auth-footer {
      text-align: center; font-size: 13px; color: var(--text-3);
      margin-top: 8px;
    }
    .auth-footer a { color: var(--text-2); font-weight: 500; cursor: pointer; }
    .auth-footer a:hover { color: var(--text); }

    .divider {
      display: flex; align-items: center; gap: 12px;
      font-size: 11px; color: var(--text-3); font-family: var(--mono);
      margin: 4px 0;
    }
    .divider::before, .divider::after {
      content: ''; flex: 1; height: 1px; background: var(--border);
    }

    /* ── NOTIFICATIONS ───────────────────────────── */
    #notifContainer {
      position: fixed; top: 20px; right: 20px;
      z-index: 9999; display: flex; flex-direction: column; gap: 8px;
      pointer-events: none;
    }
    .notif {
      pointer-events: all;
      background: var(--bg-card); border: 1px solid var(--border-2);
      border-radius: 10px; padding: 12px 14px;
      font-size: 13px; color: var(--text);
      box-shadow: var(--shadow-lg);
      display: flex; align-items: flex-start; gap: 10px;
      max-width: 320px;
      transform: translateX(360px); opacity: 0;
      transition: transform .3s cubic-bezier(.16,1,.3,1), opacity .3s;
    }
    .notif.show { transform: translateX(0); opacity: 1; }
    .notif-icon { font-size: 14px; flex-shrink: 0; margin-top: 1px; }
    .notif-icon.err  { color: var(--red); }
    .notif-icon.ok   { color: var(--green); }
    .notif-icon.warn { color: var(--amber); }
    .notif-body { flex: 1; line-height: 1.5; }
    .notif-close {
      background: none; border: none; color: var(--text-3);
      font-size: 14px; padding: 0; line-height: 1; flex-shrink: 0; cursor: pointer;
    }
    .notif-close:hover { color: var(--text-2); }

    /* ── RESPONSIVE ──────────────────────────────── */
    @media (max-width: 760px) {
      body { flex-direction: column; }
      .visual-side { min-height: 200px; flex: 0 0 200px; }
      .auth-side { width: 100%; border-left: none; border-top: 1px solid var(--border); padding: 28px 24px; }
    }
  </style>
</head>
<body>

  <!-- VISUAL SIDE -->
  <div class="visual-side">
    <div class="slide-bg" id="slideBg">
      <img src="../assets/home1.jpg" alt="" class="active">
      <img src="../assets/home2.jpg" alt="">
      <img src="../assets/home3.jpg" alt="">
    </div>

    <div class="visual-content">
      <div class="visual-logo">TiPeed</div>
      <div class="visual-tagline">
        <strong>Lifelong learners.</strong>
        Problem solvers.
        <em>Your campus community — threads, chats, and connections all in one place.</em>
      </div>
      <div class="slide-dots" id="slideDots">
        <div class="slide-dot active"></div>
        <div class="slide-dot"></div>
        <div class="slide-dot"></div>
      </div>
    </div>
  </div>

  <!-- AUTH SIDE -->
  <aside class="auth-side">

    <div class="auth-top">
      <div class="auth-logo">Ti<span>Peed</span></div>
      <button class="theme-btn" id="themeBtn" title="Toggle dark mode">
        <i class="fas fa-moon"></i>
      </button>
    </div>

    <!-- Tab switcher -->
    <div class="tab-row">
      <button class="tab-btn active" id="tabLogin" onclick="showLogin()">Sign in</button>
      <button class="tab-btn" id="tabRegister" onclick="showRegister()">Create account</button>
    </div>

    <!-- LOGIN PANEL -->
    <div class="form-panel active" id="loginPanel">
      <div class="panel-heading">
        <h2>Welcome back</h2>
        <p>Sign in to your TiPeed account</p>
      </div>

      <form action="login.php" method="POST" id="loginFormElement">
        <div class="field-group">
          <label class="field-label">Email</label>
          <input type="email" name="email" class="field-input"
            placeholder="you@tip.edu.ph" required
            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
        </div>

        <div class="field-group">
          <label class="field-label">Password</label>
          <div class="pw-wrap">
            <input type="password" id="loginPassword" name="password"
              class="field-input" placeholder="••••••••" required>
            <i class="fas fa-eye pw-eye" onclick="togglePw('loginPassword', this)"></i>
          </div>
        </div>

        <button type="submit" class="submit-btn" id="loginBtn">Sign In</button>
      </form>

      <div class="auth-footer">
        No account yet? <a onclick="showRegister()">Create one</a>
      </div>
    </div>

    <!-- REGISTER PANEL -->
    <div class="form-panel" id="registerPanel">
      <div class="panel-heading">
        <h2>Join TiPeed</h2>
        <p>Create your student account</p>
      </div>

      <form action="register.php" method="POST">
        <div class="row-2">
          <div class="field-group">
            <label class="field-label">First Name</label>
            <input type="text" name="first_name" class="field-input" placeholder="Juan" required>
          </div>
          <div class="field-group">
            <label class="field-label">Last Name</label>
            <input type="text" name="last_name" class="field-input" placeholder="dela Cruz" required>
          </div>
        </div>

        <div class="row-2">
          <div class="field-group">
            <label class="field-label">Student ID</label>
            <input type="text" name="student_id" class="field-input" placeholder="2024-00001" required>
          </div>
          <div class="field-group">
            <label class="field-label">Year Level</label>
            <div class="select-wrap">
              <select name="year_level" class="field-input" required>
                <option value="" disabled selected>Year</option>
                <option value="1">1st Year</option>
                <option value="2">2nd Year</option>
                <option value="3">3rd Year</option>
                <option value="4">4th Year</option>
              </select>
            </div>
          </div>
        </div>

        <div class="field-group">
          <label class="field-label">Email</label>
          <input type="email" name="email" class="field-input" placeholder="you@tip.edu.ph" required>
        </div>

        <div class="field-group">
          <label class="field-label">Password</label>
          <div class="pw-wrap">
            <input type="password" id="regPassword" name="password"
              class="field-input" placeholder="••••••••" required>
            <i class="fas fa-eye pw-eye" onclick="togglePw('regPassword', this)"></i>
          </div>
        </div>

        <div class="field-group">
          <label class="field-label">Confirm Password</label>
          <div class="pw-wrap">
            <input type="password" id="regConfirmPassword" name="confirm_password"
              class="field-input" placeholder="••••••••" required>
            <i class="fas fa-eye pw-eye" onclick="togglePw('regConfirmPassword', this)"></i>
          </div>
        </div>

        <div class="agree-row">
          <input type="checkbox" id="agree" required>
          <label for="agree">I agree to the <a href="#">Terms &amp; Conditions</a></label>
        </div>

        <button type="submit" class="submit-btn">Create Account</button>
      </form>

      <div class="auth-footer">
        Already have an account? <a onclick="showLogin()">Sign in</a>
      </div>
    </div>

  </aside>

  <!-- NOTIFICATIONS -->
  <div id="notifContainer"></div>

  <script>
    // ── DARK MODE ──
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
      themeBtn.innerHTML = t === 'dark'
        ? '<i class="fas fa-sun"></i>'
        : '<i class="fas fa-moon"></i>';
    }

    // ── SLIDESHOW ──
    const slides = document.querySelectorAll('#slideBg img');
    const dots   = document.querySelectorAll('#slideDots .slide-dot');
    let cur = 0;
    setInterval(() => {
      slides[cur].classList.remove('active');
      dots[cur].classList.remove('active');
      cur = (cur + 1) % slides.length;
      slides[cur].classList.add('active');
      dots[cur].classList.add('active');
    }, 5000);

    // ── TABS ──
    function showLogin() {
      document.getElementById('loginPanel').classList.add('active');
      document.getElementById('registerPanel').classList.remove('active');
      document.getElementById('tabLogin').classList.add('active');
      document.getElementById('tabRegister').classList.remove('active');
      window.history.replaceState({}, document.title, window.location.pathname);
    }
    function showRegister() {
      document.getElementById('registerPanel').classList.add('active');
      document.getElementById('loginPanel').classList.remove('active');
      document.getElementById('tabRegister').classList.add('active');
      document.getElementById('tabLogin').classList.remove('active');
      window.history.replaceState({}, document.title, window.location.pathname);
    }

    // ── PASSWORD TOGGLE ──
    function togglePw(id, icon) {
      const f = document.getElementById(id);
      const isHidden = f.type === 'password';
      f.type = isHidden ? 'text' : 'password';
      icon.classList.toggle('fa-eye', !isHidden);
      icon.classList.toggle('fa-eye-slash', isHidden);
    }

    // ── NOTIFICATIONS ──
    function showNotif(msg, type = 'err') {
      const icons = { err: 'fa-circle-xmark', ok: 'fa-circle-check', warn: 'fa-triangle-exclamation' };
      const container = document.getElementById('notifContainer');
      const el = document.createElement('div');
      el.className = 'notif';
      el.innerHTML = `
        <i class="fas ${icons[type]} notif-icon ${type}"></i>
        <div class="notif-body">${msg}</div>
        <button class="notif-close" onclick="dismissNotif(this.parentElement)">&times;</button>
      `;
      container.appendChild(el);
      setTimeout(() => el.classList.add('show'), 60);
      setTimeout(() => dismissNotif(el), 5000);
    }
    function dismissNotif(el) {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 320);
    }

    // ── URL ERROR PARAMS ──
    document.addEventListener('DOMContentLoaded', () => {
      const p = new URLSearchParams(window.location.search);
      const err = p.get('error');
      if (err === 'invalid_password') { showNotif('Incorrect password. Please try again.', 'err'); showLogin(); }
      else if (err === 'no_account')  { showNotif('No account found with that email.', 'err'); showLogin(); }

      <?php if (!empty($login_error)): ?>
        showNotif('<?php echo addslashes($login_error); ?>', 'err');
      <?php endif; ?>
    });

    // ── AJAX LOGIN ──
    document.getElementById('loginFormElement').addEventListener('submit', function(e) {
      e.preventDefault();
      const btn = document.getElementById('loginBtn');
      btn.textContent = 'Signing in…';
      btn.disabled = true;

      fetch('login.php', { method: 'POST', body: new FormData(this), credentials: 'include' })
        .then(res => {
          if (res.redirected) { window.location.href = res.url; return null; }
          return res.text();
        })
        .then(data => {
          if (!data) return;
          if (data.includes('Invalid password'))      showNotif('Incorrect password. Please try again.', 'err');
          else if (data.includes('No account found')) showNotif('No account found with that email.', 'err');
        })
        .catch(() => showNotif('Something went wrong. Please try again.', 'err'))
        .finally(() => { btn.textContent = 'Sign In'; btn.disabled = false; });
    });
  </script>
</body>
</html>