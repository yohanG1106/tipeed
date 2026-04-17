<?php
include "db_connect.php";
session_start();
if (!isset($_SESSION['userid'])) { header("Location: auth.php"); exit; }
$currentUserRole=isset($_SESSION['role'])?$_SESSION['role']:'';
$homePage=$currentUserRole==='admin'?'admin_home.php':($currentUserRole==='faculty'?'teacher_home.php':'student_home.php');
$studentName=$_SESSION['first_name']." ".$_SESSION['last_name'];
$yearLevel=isset($_SESSION['year_level'])?$_SESSION['year_level']:null;
$role=isset($_SESSION['role'])?$_SESSION['role']:"student";
function ordinal($n){$e=['th','st','nd','rd','th','th','th','th','th','th'];if(($n%100)>=11&&($n%100)<=13)return $n.'th';return $n.$e[$n%10];}
if($role==='student'){$studentIDT=($yearLevel&&is_numeric($yearLevel))?ordinal($yearLevel)." Year":"No year assigned";}
elseif($role==='faculty'){$studentIDT="Faculty";}
elseif($role==='admin'){$studentIDT="Administrator";}
else{$studentIDT=ucfirst(htmlspecialchars($role));}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Course Chat — TIPeed</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="tipeed-design.css">
  <style>
    /* ── Two-col layout ── */
    .create-grid {
      display: grid;
      grid-template-columns: 1fr 340px;
      gap: 24px;
      align-items: flex-start;
    }
    @media (max-width: 960px) { .create-grid { grid-template-columns: 1fr; } }

    /* ── Avatar upload ── */
    .avatar-upload {
      width: 100%;
      aspect-ratio: 1;
      max-width: 160px;
      border-radius: var(--radius2);
      background: var(--bg2);
      border: 2px dashed var(--border2);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: border-color .2s, background .2s;
      overflow: hidden;
      position: relative;
    }
    .avatar-upload:hover { border-color: var(--accent); background: var(--accent-bg); }
    .avatar-upload i { font-size: 1.5rem; color: var(--text3); margin-bottom: 6px; }
    .avatar-upload span { font-size: .73rem; color: var(--text3); text-align: center; padding: 0 8px; }

    /* ── Settings grid ── */
    .settings-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
    .setting-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 12px 14px;
      background: var(--bg2);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      cursor: pointer;
      transition: border-color .15s;
    }
    .setting-item:hover { border-color: var(--accent); }
    .setting-item input[type="checkbox"] { width: 16px; height: 16px; accent-color: var(--accent); flex-shrink: 0; }
    .setting-item label { font-size: .82rem; font-weight: 500; cursor: pointer; }

    /* ── Invite link row ── */
    .invite-row { display: flex; gap: 8px; align-items: stretch; }
    .invite-input {
      flex: 1;
      padding: 9px 12px;
      border: 1px solid var(--border);
      border-radius: var(--radius);
      background: var(--bg2);
      color: var(--text2);
      font-family: var(--mono);
      font-size: .78rem;
      outline: none;
    }

    /* ── People list ── */
    .people-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 0;
      border-bottom: 1px solid var(--border);
    }
    .people-item:last-child { border-bottom: none; }

    /* ── Success modal ── */
    .success-icon-wrap {
      width: 60px; height: 60px;
      border-radius: 50%;
      background: var(--success-bg);
      color: var(--success);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.6rem;
      margin: 0 auto 16px;
    }
  </style>
</head>
<body>
<div class="app">

  <!-- Topbar -->
  <header class="topbar">
    <div class="topbar-logo"><div class="logo-dot">T</div><span>TIPeed</span></div>
    <div class="topbar-search"><i class="fas fa-search"></i><input type="text" placeholder="Search…"></div>
    <nav class="topbar-nav">
      <a href="<?= $homePage ?>">Home</a>
      <?php if($currentUserRole==='admin'||$currentUserRole==='faculty'): ?>
      <a href="student_home.php">Thread</a>
      <a href="faculty_chats.php" class="active">Faculty</a>
      <?php endif; ?>
      <a href="Community.php">Community</a>
    </nav>
    <div class="topbar-right">
      <button class="tb-btn" id="themeBtn"><i class="fas fa-moon"></i></button>
    </div>
  </header>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-profile" id="toggleSidebar">
      <div class="profile-av av-gold"><?= strtoupper(substr($studentName,0,2)) ?></div>
      <div class="profile-name-block">
        <div class="profile-name"><?= htmlspecialchars($studentName) ?></div>
        <div class="profile-role"><?= htmlspecialchars($studentIDT) ?></div>
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section-label">Main</div>
      <a href="profile.php" class="nav-item"><span class="nav-icon"><i class="fas fa-user"></i></span><span class="nav-label">Profile</span></a>
      <a href="<?= $homePage ?>" class="nav-item"><span class="nav-icon"><i class="fas fa-home"></i></span><span class="nav-label">Home</span></a>
      <div class="nav-section-label">Engage</div>
      <a href="chat_interface.php" class="nav-item"><span class="nav-icon"><i class="fas fa-comment-dots"></i></span><span class="nav-label">Course Chat</span></a>
      <a href="CourseChat.php" class="nav-item"><span class="nav-icon"><i class="fas fa-comments"></i></span><span class="nav-label">Communities</span></a>
      <a href="Community.php" class="nav-item"><span class="nav-icon"><i class="fas fa-users"></i></span><span class="nav-label">Community</span></a>
      <?php if($currentUserRole==='admin'): ?><a href="admin_reg.php" class="nav-item"><span class="nav-icon"><i class="fas fa-user-plus"></i></span><span class="nav-label">Register</span></a><?php endif; ?>
      <div class="nav-section-label">Tools</div>
      <a href="calendar.php" class="nav-item"><span class="nav-icon"><i class="fas fa-calendar-alt"></i></span><span class="nav-label">Calendar</span></a>
      <a href="#" class="nav-item"><span class="nav-icon"><i class="fas fa-cog"></i></span><span class="nav-label">Settings</span></a>
      <a href="Help.php" class="nav-item"><span class="nav-icon"><i class="fas fa-question-circle"></i></span><span class="nav-label">Help</span></a>
      <a href="logout.php" class="nav-item"><span class="nav-icon"><i class="fas fa-sign-out-alt"></i></span><span class="nav-label">Log Out</span></a>
    </nav>
  </aside>

  <!-- Main -->
  <div class="main-wrap">
    <div class="main-content">

      <div class="page-hdr">
        <div>
          <div class="page-eyebrow">Faculty</div>
          <h1 class="page-title">Create Course Chat</h1>
          <div class="page-sub">Set up a new group for your course</div>
        </div>
        <a href="faculty_chats.php" class="btn btn-ghost"><i class="fas fa-arrow-left"></i> Back</a>
      </div>

      <div class="create-grid">

        <!-- Left column: main form -->
        <div style="display:flex;flex-direction:column;gap:20px">

          <!-- Basic info -->
          <div class="card">
            <div class="card-header"><div class="card-title">Group Info</div></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
              <div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap">
                <div class="avatar-upload" id="avatarUpload">
                  <input type="file" id="avatarInput" accept="image/*" style="display:none">
                  <i class="fas fa-camera"></i>
                  <span id="avatarHint">Upload group photo</span>
                </div>
                <div style="flex:1;min-width:220px;display:flex;flex-direction:column;gap:14px">
                  <div class="fg">
                    <label>Group Name <span style="color:var(--danger)">*</span></label>
                    <input type="text" id="groupName" class="ctrl" placeholder="e.g. CS101 Morning Section">
                  </div>
                  <div class="fg">
                    <label>Course <span style="color:var(--danger)">*</span></label>
                    <select id="courseCode" class="ctrl">
                      <option value="">Loading your courses…</option>
                    </select>
                    <div id="courseInfoDisplay"></div>
                  </div>
                  <div class="fg">
                    <label>Class Section</label>
                    <input type="text" id="classSection" class="ctrl" placeholder="e.g. Section A">
                  </div>
                </div>
              </div>
              <div class="fg">
                <label>Description</label>
                <textarea id="description" class="ctrl" placeholder="Describe this group's purpose…"></textarea>
              </div>
            </div>
          </div>

          <!-- Co-admins -->
          <div class="card">
            <div class="card-header">
              <div><div class="card-title">Co-Admins</div><div class="card-sub">Optional — grant admin rights</div></div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
              <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:flex-end">
                <div class="fg"><label>Name</label><input type="text" id="coAdminName" class="ctrl" placeholder="Full name"></div>
                <div class="fg"><label>Email</label><input type="email" id="coAdminEmail" class="ctrl" placeholder="email@tip.edu"></div>
                <div class="fg"><label style="opacity:0">Add</label><button class="btn btn-info" id="addCoAdminBtn"><i class="fas fa-plus"></i></button></div>
              </div>
              <div id="coAdminList">
                <div class="empty-state" style="padding:16px" id="coAdminEmpty">
                  <p>No co-admins added yet.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Students -->
          <div class="card">
            <div class="card-header">
              <div><div class="card-title">Add Students</div><div class="card-sub">Optional — invite directly</div></div>
            </div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
              <div style="display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:flex-end">
                <div class="fg"><label>Name</label><input type="text" id="studentName" class="ctrl" placeholder="Full name"></div>
                <div class="fg"><label>Email</label><input type="email" id="studentEmail" class="ctrl" placeholder="email@tip.edu"></div>
                <div class="fg"><label style="opacity:0">Add</label><button class="btn btn-primary" id="addStudentBtn"><i class="fas fa-plus"></i></button></div>
              </div>
              <div id="studentsList">
                <div class="empty-state" style="padding:16px" id="studentsEmpty">
                  <p>No students added yet.</p>
                </div>
              </div>
            </div>
          </div>

        </div>

        <!-- Right column: settings + create -->
        <div style="display:flex;flex-direction:column;gap:20px;position:sticky;top:24px">

          <!-- Settings -->
          <div class="card">
            <div class="card-header"><div class="card-title">Settings</div></div>
            <div class="card-body" style="display:flex;flex-direction:column;gap:12px">
              <div class="settings-grid">
                <div class="setting-item"><input type="checkbox" id="coAdmin" checked><label for="coAdmin">Allow Co-Admins</label></div>
                <div class="setting-item"><input type="checkbox" id="allowApproval" checked><label for="allowApproval">Require Approval</label></div>
                <div class="setting-item"><input type="checkbox" id="addStudent" checked><label for="addStudent">Student Addition</label></div>
              </div>
              <hr class="divider" style="margin:4px 0">
              <div class="fg">
                <label>Invite Link</label>
                <div class="invite-row">
                  <input type="text" class="invite-input" id="inviteLink" value="https://tipeed.com/join/" readonly>
                  <button class="btn btn-ghost btn-sm" id="generateBtn" title="Generate link"><i class="fas fa-link"></i></button>
                </div>
              </div>
            </div>
          </div>

          <!-- Create button -->
          <button class="btn btn-primary btn-lg" id="saveBtn" style="width:100%;justify-content:center">
            <i class="fas fa-plus-circle"></i> Create Course Chat
          </button>
          <a href="faculty_chats.php" class="btn btn-ghost" style="width:100%;justify-content:center">Cancel</a>
        </div>

      </div>
    </div>
  </div>
</div><!-- /app -->

<!-- Success modal -->
<div class="modal-backdrop" id="successModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Chat Created!</span>
      <button class="modal-close" onclick="document.getElementById('successModal').classList.remove('open')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body" style="text-align:center">
      <div class="success-icon-wrap"><i class="fas fa-check"></i></div>
      <p style="font-size:.88rem;color:var(--text2);margin-bottom:12px">Your course chat <strong id="successGroupName"></strong> has been created.</p>
      <div style="font-size:.75rem;color:var(--text3);margin-bottom:8px">Invite Link</div>
      <div class="invite-code-display" id="successLink" style="font-size:.78rem;letter-spacing:.04em;word-break:break-all"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" id="copySuccessBtn"><i class="fas fa-copy"></i> Copy Link</button>
      <a href="faculty_chats.php" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Go to Chats</a>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
  /* ── Theme ── */
  const html=document.documentElement;
  const themeBtn=document.getElementById('themeBtn');
  const saved=localStorage.getItem('tipeedTheme');
  if(saved){html.dataset.theme=saved;updateIcon();}
  themeBtn.addEventListener('click',()=>{html.dataset.theme=html.dataset.theme==='dark'?'light':'dark';localStorage.setItem('tipeedTheme',html.dataset.theme);updateIcon();});
  function updateIcon(){themeBtn.innerHTML=html.dataset.theme==='dark'?'<i class="fas fa-sun"></i>':'<i class="fas fa-moon"></i>';}
  document.getElementById('toggleSidebar').addEventListener('click',()=>document.getElementById('sidebar').classList.toggle('collapsed'));

  function showToast(msg){const t=document.getElementById('toast');t.textContent=msg;t.classList.add('show');setTimeout(()=>t.classList.remove('show'),3000);}

  /* ── Avatar ── */
  const au=document.getElementById('avatarUpload');
  const ai=document.getElementById('avatarInput');
  au.addEventListener('click',()=>ai.click());
  ai.addEventListener('change',function(){
    if(this.files[0]){
      const r=new FileReader();
      r.onload=e=>{au.style.backgroundImage=`url(${e.target.result})`;au.style.backgroundSize='cover';au.style.backgroundPosition='center';au.querySelector('i').style.display='none';document.getElementById('avatarHint').textContent='✓ Photo set';};
      r.readAsDataURL(this.files[0]);
    }
  });

  /* ── Load courses ── */
  async function loadCourses(){
    const sel=document.getElementById('courseCode');
    try {
      const r=await fetch('get_faculty_courses.php'); const d=await r.json();
      if(d.status==='success'){
        sel.innerHTML='<option value="">Select a course…</option>';
        d.data.forEach(c=>{const o=document.createElement('option');o.value=c.course_id;o.textContent=c.course_code+' — '+c.course_name;o.dataset.desc=c.course_description||'';sel.appendChild(o);});
        if(!d.data.length) sel.innerHTML='<option value="">No courses assigned</option>';
      }
    } catch(e){ sel.innerHTML='<option value="">Error loading courses</option>'; }
  }

  /* ── Course change ── */
  document.getElementById('courseCode').addEventListener('change',function(){
    const opt=this.options[this.selectedIndex];
    const disp=document.getElementById('courseInfoDisplay');
    const desc=opt.dataset?.desc;
    if(desc&&this.value){
      disp.innerHTML=`<div style="margin-top:8px;padding:10px 12px;background:var(--bg2);border-radius:var(--radius);font-size:.78rem;color:var(--text2);border-left:3px solid var(--accent)">${desc}</div>`;
      const gn=document.getElementById('groupName');
      if(!gn.value.trim()) gn.value=(opt.textContent.split('—')[1]||opt.textContent).trim()+' Study Group';
    } else disp.innerHTML='';
  });

  /* ── Invite link ── */
  document.getElementById('generateBtn').addEventListener('click',()=>{
    const gn=document.getElementById('groupName').value.trim()||'group';
    const slug=gn.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'');
    const code=Math.random().toString(36).substr(2,8);
    const link=`https://tipeed.com/join/${slug}-${code}`;
    document.getElementById('inviteLink').value=link;
    navigator.clipboard.writeText(link).catch(()=>{});
    showToast('Link generated & copied!');
  });

  /* ── People management ── */
  function makePeopleItem(name,email,listId,emptyId){
    const list=document.getElementById(listId);
    const empty=document.getElementById(emptyId);
    if(empty) empty.remove();
    const init=name.split(' ').map(w=>w[0]||'').join('').toUpperCase().substr(0,2);
    const div=document.createElement('div');
    div.className='people-item';
    div.innerHTML=`<div class="av av-36 av-blue">${init}</div>
      <div style="flex:1;min-width:0"><div style="font-size:.85rem;font-weight:600">${name}</div><div style="font-size:.76rem;color:var(--text3);font-family:var(--mono)">${email}</div></div>
      <button class="btn btn-sm btn-danger" onclick="removePeopleItem(this,'${listId}','${emptyId}')"><i class="fas fa-times"></i></button>`;
    list.appendChild(div);
  }
  function removePeopleItem(btn,listId,emptyId){
    btn.closest('.people-item').remove();
    if(!document.getElementById(listId).querySelectorAll('.people-item').length){
      const e=document.createElement('div');e.id=emptyId;e.className='empty-state';e.style.padding='16px';e.innerHTML='<p>None added yet.</p>';
      document.getElementById(listId).appendChild(e);
    }
  }
  function addPerson(nameId,emailId,listId,emptyId){
    const name=document.getElementById(nameId).value.trim();
    const email=document.getElementById(emailId).value.trim();
    if(!name||!email){showToast('Enter both name and email.');return;}
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){showToast('Enter a valid email.');return;}
    makePeopleItem(name,email,listId,emptyId);
    document.getElementById(nameId).value='';
    document.getElementById(emailId).value='';
    document.getElementById(nameId).focus();
  }

  document.getElementById('addCoAdminBtn').addEventListener('click',()=>addPerson('coAdminName','coAdminEmail','coAdminList','coAdminEmpty'));
  document.getElementById('addStudentBtn').addEventListener('click',()=>addPerson('studentName','studentEmail','studentsList','studentsEmpty'));
  ['coAdminEmail','studentEmail'].forEach(id=>{
    document.getElementById(id).addEventListener('keypress',e=>{if(e.key==='Enter'){e.preventDefault();document.getElementById(id==='coAdminEmail'?'addCoAdminBtn':'addStudentBtn').click();}});
  });

  /* ── Save ── */
  document.getElementById('saveBtn').addEventListener('click', async()=>{
    const gn=document.getElementById('groupName').value.trim();
    const ci=document.getElementById('courseCode').value;
    if(!gn){showToast('Enter a Group Name.');return;}
    if(!ci){showToast('Select a Course.');return;}
    const btn=document.getElementById('saveBtn');
    btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Creating…';btn.disabled=true;
    const coAdmins=[],students=[];
    document.querySelectorAll('#coAdminList .people-item').forEach(el=>{
      coAdmins.push({name:el.querySelector('div>div:first-child').textContent,email:el.querySelector('div>div:last-child').textContent});
    });
    document.querySelectorAll('#studentsList .people-item').forEach(el=>{
      students.push({name:el.querySelector('div>div:first-child').textContent,email:el.querySelector('div>div:last-child').textContent});
    });
    try {
      const res=await fetch('create_course_chat.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({
        groupName:gn,courseId:ci,classSection:document.getElementById('classSection').value.trim(),
        description:document.getElementById('description').value.trim(),
        coAdmin:document.getElementById('coAdmin').checked,allowApproval:document.getElementById('allowApproval').checked,
        addStudent:document.getElementById('addStudent').checked,coAdmins,students
      })});
      const d=await res.json();
      if(d.status==='success'){
        document.getElementById('successGroupName').textContent='"'+gn+'"';
        const link=d.inviteLink||document.getElementById('inviteLink').value;
        document.getElementById('successLink').textContent=link;
        document.getElementById('successModal').classList.add('open');
        btn.innerHTML='<i class="fas fa-plus-circle"></i> Create Course Chat';btn.disabled=false;
      } else throw new Error(d.message);
    } catch(e){ showToast('Error: '+e.message); btn.innerHTML='<i class="fas fa-plus-circle"></i> Create Course Chat';btn.disabled=false; }
  });

  document.getElementById('copySuccessBtn').addEventListener('click',()=>{
    navigator.clipboard.writeText(document.getElementById('successLink').textContent).then(()=>showToast('Copied!')).catch(()=>{});
  });

  /* ── Init ── */
  document.addEventListener('DOMContentLoaded', loadCourses);
</script>
</body>
</html>