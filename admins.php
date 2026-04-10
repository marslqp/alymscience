<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Админ-панель | AlymChems</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;800&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:"Poppins",sans-serif;background:radial-gradient(circle at top,#0f172a,#020617);color:white;min-height:100vh;}
nav{display:flex;justify-content:space-between;align-items:center;padding:14px 40px;background:rgba(255,255,255,0.04);backdrop-filter:blur(12px);border-bottom:1px solid rgba(255,255,255,0.06);position:sticky;top:0;z-index:100;}
.logo{font-weight:800;font-size:18px;}.logo span{color:#ef4444;}
.container{max-width:1200px;margin:0 auto;padding:30px 20px;}
.tabs{display:flex;gap:4px;margin-bottom:28px;background:rgba(255,255,255,0.04);border-radius:14px;padding:6px;border:1px solid rgba(255,255,255,0.07);}
.tab{flex:1;padding:10px;background:none;border:none;color:#64748b;font-family:inherit;font-size:13px;font-weight:600;cursor:pointer;border-radius:10px;transition:0.2s;}
.tab.active{background:#dc2626;color:white;}
.tab:hover:not(.active){color:white;background:rgba(255,255,255,0.06);}
.panel{display:none;}.panel.active{display:block;}
.stat-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:28px;}
.stat-card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.07);border-radius:14px;padding:20px;text-align:center;}
.stat-n{font-size:36px;font-weight:800;color:#38bdf8;}
.stat-l{font-size:12px;color:#64748b;margin-top:4px;}
.table-wrap{background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.07);border-radius:16px;overflow:hidden;}
table{width:100%;border-collapse:collapse;}
th{background:rgba(255,255,255,0.06);padding:12px 16px;text-align:left;font-size:12px;color:#64748b;letter-spacing:1px;text-transform:uppercase;font-weight:700;}
td{padding:12px 16px;font-size:13px;border-top:1px solid rgba(255,255,255,0.04);}
tr:hover td{background:rgba(255,255,255,0.02);}
.role-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.role-student{background:rgba(37,99,235,0.2);color:#93c5fd;border:1px solid #2563eb;}
.role-teacher{background:rgba(245,158,11,0.2);color:#fbbf24;border:1px solid #f59e0b;}
.role-admin{background:rgba(239,68,68,0.2);color:#f87171;border:1px solid #ef4444;}
.btn{padding:7px 14px;border:none;border-radius:8px;cursor:pointer;font-family:inherit;font-size:12px;font-weight:700;transition:0.2s;}
.btn-red{background:#dc2626;color:white;}.btn-red:hover{background:#b91c1c;}
.btn-blue{background:#2563eb;color:white;}.btn-blue:hover{background:#1d4ed8;}
.btn-yellow{background:#d97706;color:white;}.btn-yellow:hover{background:#b45309;}
.btn-ghost{background:rgba(255,255,255,0.08);color:#94a3b8;border:1px solid rgba(255,255,255,0.1);}
.btn-ghost:hover{color:white;}
.search-bar{display:flex;gap:12px;margin-bottom:20px;flex-wrap:wrap;}
.search-bar input{flex:1;min-width:200px;padding:10px 14px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:white;font-size:14px;outline:none;font-family:inherit;transition:0.2s;}
.search-bar input:focus{border-color:#ef4444;}
.search-bar select{padding:10px 14px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:white;font-size:14px;outline:none;font-family:inherit;}
.search-bar select option{background:#1e293b;}
.msg{padding:10px 14px;border-radius:10px;font-size:13px;margin-bottom:16px;display:none;}
.msg.err{background:rgba(239,68,68,0.1);border:1px solid #ef4444;color:#f87171;}
.msg.ok{background:rgba(16,185,129,0.1);border:1px solid #10b981;color:#4ade80;}
.msg.show{display:block;}
.field{margin-bottom:14px;}
label{display:block;font-size:12px;color:#94a3b8;margin-bottom:5px;}
.field input{width:100%;padding:10px 14px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:white;font-size:14px;outline:none;font-family:inherit;transition:0.2s;}
.field input:focus{border-color:#ef4444;}
.card{background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:22px;margin-bottom:16px;}
h2{font-size:20px;font-weight:800;margin-bottom:18px;}
.empty{text-align:center;padding:40px;color:#475569;font-size:14px;}
.recent-item{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid rgba(255,255,255,0.04);}
.recent-item:last-child{border:none;}
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.75);z-index:200;display:none;align-items:center;justify-content:center;padding:20px;}
.modal-overlay.open{display:flex;}
.modal{background:#0f172a;border:1px solid rgba(255,255,255,0.1);border-radius:20px;padding:28px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;}
.modal h3{font-size:18px;font-weight:800;margin-bottom:20px;color:#ef4444;}
.info-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.05);font-size:13px;}
.info-row span:first-child{color:#64748b;}
.btn-large{width:100%;padding:12px;margin-top:10px;font-size:14px;border-radius:12px;}
.login-screen{display:flex;align-items:center;justify-content:center;min-height:100vh;}
.login-box{background:rgba(255,255,255,0.05);border:1px solid rgba(255,255,255,0.1);border-radius:20px;padding:36px;width:100%;max-width:380px;text-align:center;}
.login-box h2{color:#ef4444;margin-bottom:20px;}
.login-box input{width:100%;padding:12px 14px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.1);border-radius:10px;color:white;font-size:15px;outline:none;font-family:inherit;margin-bottom:14px;}
.login-box input:focus{border-color:#ef4444;}
.pagination{display:flex;gap:8px;justify-content:center;margin-top:16px;flex-wrap:wrap;}
.page-btn{padding:6px 12px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.1);border-radius:8px;color:#94a3b8;cursor:pointer;font-size:13px;font-family:inherit;}
.page-btn.active-p{background:#dc2626;color:white;border-color:#dc2626;}
@media(max-width:640px){nav{padding:12px 16px;}.tabs{flex-wrap:wrap;}.container{padding:16px;}th,td{padding:8px 10px;}}
</style>
</head>
<body>

<!-- LOGIN SCREEN -->
<div class="login-screen" id="login-screen">
  <div class="login-box">
    <div style="font-size:32px;margin-bottom:8px;">🛡️</div>
    <h2>Вход в админ-панель</h2>
    <div id="login-msg" class="msg" style="display:none;"></div>
    <input type="password" id="admin-token" placeholder="Введи секретный токен" onkeydown="if(event.key==='Enter')doAdminLogin()">
    <button class="btn btn-red btn-large" onclick="doAdminLogin()">Войти</button>
    <div style="margin-top:14px;font-size:12px;color:#475569;">Токен задаётся через переменную окружения ADMIN_TOKEN на сервере</div>
  </div>
</div>

<!-- MAIN PANEL -->
<div id="main-panel" style="display:none;">
<nav>
  <div class="logo">🛡️ Alym<span>Chems</span> Admin</div>
  <div style="display:flex;gap:12px;align-items:center;">
    <span style="font-size:12px;color:#64748b;">Админ-панель</span>
    <button class="btn btn-ghost" onclick="window.location.href='index.html'">← На сайт</button>
    <button class="btn btn-red" onclick="doLogout()">Выйти</button>
  </div>
</nav>

<div class="container">
  <div class="tabs">
    <button class="tab active" onclick="switchTab('dashboard')">📊 Дашборд</button>
    <button class="tab" onclick="switchTab('users')">👥 Пользователи</button>
    <button class="tab" onclick="switchTab('teachers')">👩‍🏫 Учителя</button>
    <button class="tab" onclick="switchTab('classes')">📚 Классы</button>
    <button class="tab" onclick="switchTab('settings')">⚙️ Настройки</button>
  </div>

  <!-- DASHBOARD -->
  <div id="tab-dashboard" class="panel active">
    <h2>📊 Общая статистика</h2>
    <div class="stat-row" id="stat-row">
      <div class="stat-card"><div class="stat-n" id="s-total">—</div><div class="stat-l">Пользователей</div></div>
      <div class="stat-card"><div class="stat-n" id="s-students">—</div><div class="stat-l">Учеников</div></div>
      <div class="stat-card"><div class="stat-n" id="s-teachers">—</div><div class="stat-l">Учителей</div></div>
      <div class="stat-card"><div class="stat-n" id="s-classes">—</div><div class="stat-l">Классов</div></div>
      <div class="stat-card"><div class="stat-n" id="s-scores">—</div><div class="stat-l">Результатов</div></div>
    </div>
    <h2>🕐 Последние регистрации</h2>
    <div class="card" id="recent-list"><div class="empty">Загрузка...</div></div>
  </div>

  <!-- USERS -->
  <div id="tab-users" class="panel">
    <h2>👥 Все пользователи</h2>
    <div class="search-bar">
      <input id="user-search" placeholder="Поиск по имени..." oninput="debounceSearch()" type="search">
      <select id="role-filter" onchange="loadUsers()">
        <option value="">Все роли</option>
        <option value="student">Ученики</option>
        <option value="teacher">Учителя</option>
        <option value="admin">Админы</option>
      </select>
      <button class="btn btn-blue" onclick="loadUsers()">🔍 Найти</button>
    </div>
    <div id="users-msg" class="msg"></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Имя</th><th>Класс / Роль</th><th>Роль</th><th>Очки</th><th>Дата</th><th>Действия</th></tr></thead>
        <tbody id="users-tbody"><tr><td colspan="7" style="text-align:center;color:#475569;padding:30px;">Загрузка...</td></tr></tbody>
      </table>
    </div>
    <div class="pagination" id="users-pagination"></div>
  </div>

  <!-- TEACHERS -->
  <div id="tab-teachers" class="panel">
    <h2>👩‍🏫 Учителя</h2>
    <div id="teachers-msg" class="msg"></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Имя</th><th>Предмет</th><th>Дата рег.</th><th>Действия</th></tr></thead>
        <tbody id="teachers-tbody"><tr><td colspan="5" style="text-align:center;color:#475569;padding:30px;">Загрузка...</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- CLASSES -->
  <div id="tab-classes" class="panel">
    <h2>📚 Все классы</h2>
    <div id="classes-msg" class="msg"></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Название</th><th>Учитель</th><th>Учеников</th><th>Код</th><th>Дата</th><th>Действия</th></tr></thead>
        <tbody id="classes-tbody"><tr><td colspan="7" style="text-align:center;color:#475569;padding:30px;">Загрузка...</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- SETTINGS -->
  <div id="tab-settings" class="panel">
    <h2>⚙️ Настройки</h2>
    <div class="card" style="max-width:480px;">
      <h2 style="font-size:16px;margin-bottom:14px;">🔑 Код регистрации для учителей</h2>
      <p style="font-size:13px;color:#64748b;margin-bottom:16px;">Этот код нужен при регистрации учителя. Меняй его при необходимости.</p>
      <div id="settings-msg" class="msg"></div>
      <div class="field"><label>Новый код (мин. 6 символов)</label><input type="password" id="new-teacher-code" placeholder="••••••••"></div>
      <div class="field"><label>Повтори код</label><input type="password" id="new-teacher-code2" placeholder="••••••••"></div>
      <button class="btn btn-blue" onclick="updateTeacherCode()">Обновить код</button>
    </div>
    <div class="card" style="max-width:480px;margin-top:16px;">
      <h2 style="font-size:16px;margin-bottom:14px;">🗄️ База данных</h2>
      <p style="font-size:13px;color:#64748b;margin-bottom:14px;">Если таблицы не созданы — запусти инициализацию БД.</p>
      <button class="btn btn-yellow" onclick="setupDB()">⚙️ Инициализировать БД</button>
      <div id="db-msg" class="msg" style="margin-top:12px;"></div>
    </div>
  </div>
</div>
</div>

<!-- USER DETAIL MODAL -->
<div class="modal-overlay" id="user-modal">
  <div class="modal">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
      <h3 id="modal-user-name">Пользователь</h3>
      <button onclick="closeUserModal()" style="background:none;border:none;color:#64748b;font-size:22px;cursor:pointer;">×</button>
    </div>
    <div id="modal-user-info"></div>
    <div style="margin-top:20px;">
      <div style="font-size:12px;color:#64748b;margin-bottom:10px;">ИЗМЕНИТЬ РОЛЬ</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <button class="btn btn-blue" onclick="changeRole('student')">🎓 Ученик</button>
        <button class="btn btn-yellow" onclick="changeRole('teacher')">👩‍🏫 Учитель</button>
        <button class="btn" style="background:#7c3aed;" onclick="changeRole('admin')">🛡️ Админ</button>
      </div>
    </div>
    <button class="btn btn-yellow btn-large" onclick="resetScore()">🔄 Обнулить очки</button>
    <button class="btn btn-red btn-large" onclick="deleteUser()">🗑 Удалить пользователя</button>
    <div id="modal-user-msg" class="msg" style="margin-top:12px;"></div>
    <div style="margin-top:16px;">
      <div style="font-size:12px;color:#64748b;margin-bottom:8px;">ПОСЛЕДНИЕ РЕЗУЛЬТАТЫ</div>
      <div id="modal-scores"></div>
    </div>
  </div>
</div>

<script>
let adminToken = sessionStorage.getItem('admin_token') || '';
let currentUserId = null;
let allUsers = [];
let currentPage = 1;
const PAGE_SIZE = 20;
let searchTimer = null;

function getToken() { return adminToken; }

async function adminFetch(action, method='GET', body=null, params={}) {
  let url = `admin.php?action=${action}&token=${encodeURIComponent(getToken())}`;
  for(const [k,v] of Object.entries(params)) url += `&${k}=${encodeURIComponent(v)}`;
  const opts = {method, headers:{'Content-Type':'application/json','X-Admin-Token':getToken()}};
  if(body) opts.body=JSON.stringify(body);
  const r = await fetch(url, opts);
  return r.json();
}

function showMsgEl(id,type,text){
  const el=document.getElementById(id);
  el.className='msg '+type+' show';
  el.textContent=text;
  setTimeout(()=>el.classList.remove('show'),4000);
}

// ── AUTH ───────────────────────────────────────────────────────────────────
async function doAdminLogin() {
  const tok = document.getElementById('admin-token').value.trim();
  if(!tok){return;}
  adminToken = tok;
  // Test with stats
  const data = await adminFetch('stats');
  if(data.error==='unauthorized'){
    const el=document.getElementById('login-msg');
    el.className='msg err show'; el.textContent='Неверный токен!';
    el.style.display='block';
    return;
  }
  sessionStorage.setItem('admin_token', tok);
  document.getElementById('login-screen').style.display='none';
  document.getElementById('main-panel').style.display='block';
  renderStats(data);
  renderRecent(data.recent||[]);
}

function doLogout(){
  sessionStorage.removeItem('admin_token');
  window.location.reload();
}

// ── TABS ──────────────────────────────────────────────────────────────────
function switchTab(name){
  const names=['dashboard','users','teachers','classes','settings'];
  document.querySelectorAll('.tab').forEach((t,i)=>t.classList.toggle('active',names[i]===name));
  document.querySelectorAll('.panel').forEach(p=>p.classList.remove('active'));
  document.getElementById('tab-'+name).classList.add('active');
  if(name==='users') loadUsers();
  if(name==='teachers') loadTeachers();
  if(name==='classes') loadClasses();
}

// ── DASHBOARD ─────────────────────────────────────────────────────────────
function renderStats(d){
  document.getElementById('s-total').textContent = d.users?.total_users||0;
  document.getElementById('s-students').textContent = d.users?.students||0;
  document.getElementById('s-teachers').textContent = d.users?.teachers||0;
  document.getElementById('s-classes').textContent = d.class_count||0;
  document.getElementById('s-scores').textContent = d.score_count||0;
}
function renderRecent(list){
  const el=document.getElementById('recent-list');
  if(!list.length){el.innerHTML='<div class="empty">Нет данных</div>';return;}
  el.innerHTML=list.map(u=>`
    <div class="recent-item">
      <span style="font-size:20px;">${u.role==='teacher'?'👩‍🏫':u.role==='admin'?'🛡️':'🎓'}</span>
      <div style="flex:1;">
        <div style="font-size:14px;font-weight:600;">${u.fullname}</div>
        <div style="font-size:12px;color:#64748b;">${u.grade} · ${u.created_at?.slice(0,16)}</div>
      </div>
      <span class="role-badge role-${u.role}">${u.role}</span>
    </div>
  `).join('');
}

// ── USERS ─────────────────────────────────────────────────────────────────
function debounceSearch(){clearTimeout(searchTimer);searchTimer=setTimeout(loadUsers,400);}

async function loadUsers(){
  currentPage=1;
  const search = document.getElementById('user-search').value.trim();
  const role   = document.getElementById('role-filter').value;
  const data = await adminFetch('users','GET',null,{search,role});
  allUsers = data.users||[];
  renderUsersPage();
}

function renderUsersPage(){
  const start=(currentPage-1)*PAGE_SIZE;
  const page=allUsers.slice(start,start+PAGE_SIZE);
  const tbody=document.getElementById('users-tbody');
  if(!page.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;color:#475569;padding:30px;">Ничего не найдено</td></tr>';return;}
  tbody.innerHTML=page.map(u=>`
    <tr>
      <td style="color:#64748b;">${u.id}</td>
      <td style="font-weight:600;">${u.fullname}</td>
      <td style="color:#64748b;">${u.grade}</td>
      <td><span class="role-badge role-${u.role}">${u.role==='student'?'Ученик':u.role==='teacher'?'Учитель':'Админ'}</span></td>
      <td style="color:#38bdf8;font-weight:700;">${u.total_score}</td>
      <td style="color:#475569;font-size:11px;">${u.created_at?.slice(0,10)}</td>
      <td><button class="btn btn-ghost" onclick="openUserModal(${u.id})">Открыть</button></td>
    </tr>
  `).join('');
  renderPagination();
}

function renderPagination(){
  const total=Math.ceil(allUsers.length/PAGE_SIZE);
  const el=document.getElementById('users-pagination');
  if(total<=1){el.innerHTML='';return;}
  el.innerHTML=Array.from({length:total},(_,i)=>`<button class="page-btn ${i+1===currentPage?'active-p':''}" onclick="goPage(${i+1})">${i+1}</button>`).join('');
}
function goPage(p){currentPage=p;renderUsersPage();}

// ── TEACHERS ──────────────────────────────────────────────────────────────
async function loadTeachers(){
  const data = await adminFetch('users','GET',null,{role:'teacher'});
  const tbody=document.getElementById('teachers-tbody');
  const list=data.users||[];
  if(!list.length){tbody.innerHTML='<tr><td colspan="5" style="text-align:center;color:#475569;padding:30px;">Нет учителей</td></tr>';return;}
  tbody.innerHTML=list.map(u=>`
    <tr>
      <td style="color:#64748b;">${u.id}</td>
      <td style="font-weight:600;">${u.fullname}</td>
      <td style="color:#fbbf24;">${u.subject||u.grade?.replace('TEACHER-','')||'—'}</td>
      <td style="color:#475569;font-size:11px;">${u.created_at?.slice(0,10)}</td>
      <td style="display:flex;gap:6px;">
        <button class="btn btn-ghost" onclick="openUserModal(${u.id})">Открыть</button>
        <button class="btn btn-red" onclick="quickDelete(${u.id},'${u.fullname.replace(/'/g,"\\'")}')">🗑</button>
      </td>
    </tr>
  `).join('');
}

// ── CLASSES ───────────────────────────────────────────────────────────────
async function loadClasses(){
  const data = await adminFetch('classes');
  const tbody=document.getElementById('classes-tbody');
  const list=data.classes||[];
  if(!list.length){tbody.innerHTML='<tr><td colspan="7" style="text-align:center;color:#475569;padding:30px;">Нет классов</td></tr>';return;}
  tbody.innerHTML=list.map(c=>`
    <tr>
      <td style="color:#64748b;">${c.id}</td>
      <td style="font-weight:600;">📚 ${c.class_name}</td>
      <td>${c.teacher_name}</td>
      <td style="color:#38bdf8;font-weight:700;">${c.member_count}</td>
      <td><span style="font-family:monospace;color:#fbbf24;">${c.invite_code}</span></td>
      <td style="color:#475569;font-size:11px;">${c.created_at?.slice(0,10)}</td>
      <td><button class="btn btn-red" onclick="deleteClass(${c.id},'${c.class_name.replace(/'/g,"\\'")}')">🗑 Удалить</button></td>
    </tr>
  `).join('');
}

async function deleteClass(id,name){
  if(!confirm(`Удалить класс "${name}"?`))return;
  await adminFetch('delete_class','POST',{id});
  showMsgEl('classes-msg','ok','Класс удалён');
  loadClasses();
}

// ── USER MODAL ────────────────────────────────────────────────────────────
async function openUserModal(id){
  currentUserId=id;
  document.getElementById('user-modal').classList.add('open');
  const data=await adminFetch('user_detail','GET',null,{id});
  if(data.error){alert(data.error);return;}
  const u=data.user;
  document.getElementById('modal-user-name').textContent=u.fullname;
  document.getElementById('modal-user-info').innerHTML=`
    <div class="info-row"><span>ID</span><span>${u.id}</span></div>
    <div class="info-row"><span>Роль</span><span class="role-badge role-${u.role}">${u.role}</span></div>
    <div class="info-row"><span>Класс</span><span>${u.grade}</span></div>
    <div class="info-row"><span>Предмет</span><span>${u.subject||'—'}</span></div>
    <div class="info-row"><span>Очки</span><span style="color:#38bdf8;font-weight:700;">${u.total_score}</span></div>
    <div class="info-row"><span>Дата регистрации</span><span>${u.created_at?.slice(0,16)}</span></div>
  `;
  const scores=data.scores||[];
  document.getElementById('modal-scores').innerHTML=scores.length
    ? scores.map(s=>`<div style="display:flex;justify-content:space-between;font-size:12px;padding:6px 0;border-bottom:1px solid rgba(255,255,255,0.04);"><span style="color:#64748b;">${s.topic||'—'}</span><span style="color:#38bdf8;font-weight:700;">+${s.score}</span></div>`).join('')
    : '<div style="color:#475569;font-size:13px;">Нет результатов</div>';
}

function closeUserModal(){document.getElementById('user-modal').classList.remove('open');currentUserId=null;}

async function changeRole(role){
  if(!currentUserId)return;
  if(!confirm(`Изменить роль на "${role}"?`))return;
  const data=await adminFetch('change_role','POST',{id:currentUserId,role});
  if(data.success){
    showMsgEl('modal-user-msg','ok','Роль обновлена!');
    loadUsers(); loadTeachers();
    openUserModal(currentUserId);
  } else showMsgEl('modal-user-msg','err',data.error||'Ошибка');
}

async function resetScore(){
  if(!currentUserId)return;
  if(!confirm('Обнулить очки этого пользователя?'))return;
  await adminFetch('reset_score','POST',{id:currentUserId});
  showMsgEl('modal-user-msg','ok','Очки обнулены');
  openUserModal(currentUserId);
  loadUsers();
}

async function deleteUser(){
  if(!currentUserId)return;
  if(!confirm('УДАЛИТЬ ПОЛЬЗОВАТЕЛЯ НАВСЕГДА? Это нельзя отменить!'))return;
  const data=await adminFetch('delete_user','POST',{id:currentUserId});
  if(data.success){
    closeUserModal();
    showMsgEl('users-msg','ok','Пользователь удалён');
    loadUsers(); loadTeachers();
    const stats=await adminFetch('stats');
    renderStats(stats);
  }
}

async function quickDelete(id,name){
  if(!confirm(`Удалить "${name}"?`))return;
  await adminFetch('delete_user','POST',{id});
  showMsgEl('teachers-msg','ok',`${name} удалён`);
  loadTeachers();
  const stats=await adminFetch('stats');
  renderStats(stats);
}

// ── SETTINGS ─────────────────────────────────────────────────────────────
async function updateTeacherCode(){
  const c1=document.getElementById('new-teacher-code').value;
  const c2=document.getElementById('new-teacher-code2').value;
  if(c1.length<6){showMsgEl('settings-msg','err','Код минимум 6 символов');return;}
  if(c1!==c2){showMsgEl('settings-msg','err','Коды не совпадают');return;}
  const data=await adminFetch('update_teacher_code','POST',{code:c1});
  if(data.success) showMsgEl('settings-msg','ok','Код обновлён!');
  else showMsgEl('settings-msg','err',data.error||'Ошибка');
}

async function setupDB(){
  const el=document.getElementById('db-msg');
  el.className='msg ok show'; el.textContent='Запускаем...';
  try{
    const r=await fetch('setup_db.php');
    const text=await r.text();
    el.textContent='✅ '+text;
  }catch(e){
    el.className='msg err show'; el.textContent='Ошибка: '+e.message;
  }
}

// ── INIT ──────────────────────────────────────────────────────────────────
if(adminToken){
  adminFetch('stats').then(data=>{
    if(data.error==='unauthorized'){sessionStorage.removeItem('admin_token');adminToken='';return;}
    document.getElementById('login-screen').style.display='none';
    document.getElementById('main-panel').style.display='block';
    renderStats(data); renderRecent(data.recent||[]);
  });
}
</script>
</body>
</html>
