<?php include 'auth.php'; require_admin(); include 'silog.php'; $today = date('Y-m-d'); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - Imbo't Silog</title>
  <link rel="stylesheet" href="home.css?v=5">
  <script src="sidebar.js?v=3" defer></script>
  <script src="chart.umd.min.js"></script>
  <style>
  :root { --orange:#ff7a00; --bg:#0a0a0a; --card:#141414; --border:#2a2a2a; }
  body { background:var(--bg); }
  .dash-wrap { max-width: 100%; margin: 0; padding: 20px 24px 48px; }
  .dash-title { color: #fff; font-size: 28px; font-weight: 800; margin: 20px 0 6px; letter-spacing: -0.5px; }
  .dash-sub { color: #9a9a9a; margin: 0 0 18px; font-size: 13px; }
  .period-bar { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; background: linear-gradient(180deg,#161616,#111); border: 1px solid var(--border); border-radius: 16px; padding: 14px 18px; margin-bottom: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
  .period-bar label { color: #fff; font-weight: 700; font-size: 13px; }
  .period-bar select, .period-bar input { padding: 8px 10px; border-radius: 10px; border: 1px solid #3a3a3a; background: #0f0f0f; color: #fff; font-family: 'Inter', Arial, sans-serif; font-size: 13px; transition: border-color .15s; }
  .period-bar select:focus, .period-bar input:focus { border-color: var(--orange); outline: none; }
  .pbtn { background: var(--orange); color: #000; border: none; font-weight: 700; padding: 8px 14px; border-radius: 10px; cursor: pointer; font-family: 'Inter', Arial, sans-serif; font-size: 12px; transition: transform .1s, box-shadow .15s; box-shadow: 0 4px 14px rgba(255,122,0,0.25); }
  .pbtn:hover { transform: translateY(-1px); }
  .pbtn:disabled { opacity: .4; cursor: not-allowed; transform: none; }
  .pbtn.sec { background: #2a2a2a; color: #fff; box-shadow: none; }
  .pbtn.sec:hover { background: #333; }
  .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 14px; margin-bottom: 20px; }
  .kpi { background: linear-gradient(135deg,#1a1a1a,#121212); border: 1px solid var(--border); border-radius: 16px; padding: 16px 18px; position: relative; overflow: hidden; transition: transform .15s, border-color .15s; }
  .kpi:hover { transform: translateY(-2px); border-color: rgba(255,122,0,0.3); }
  .kpi::before { content:''; position: absolute; top:0; left:0; width:3px; height:100%; background: var(--orange); }
  .kpi .label { color: #9a9a9a; font-size: 11px; text-transform: uppercase; letter-spacing: .7px; font-weight: 600; }
  .kpi .val { color: #fff; font-size: 24px; font-weight: 800; margin-top: 6px; letter-spacing: -0.3px; }
  .kpi .delta { font-size: 12px; margin-top: 6px; font-weight: 600; }
  .up { color: #4ade80; } .down { color: #f87171; } .flat { color: #9a9a9a; }
  .section-title { color: #fff; font-size: 18px; font-weight: 700; margin: 24px 0 12px; display: flex; align-items: center; gap: 8px; }
  .section-title::after { content:''; flex:1; height:1px; background: linear-gradient(90deg,#2a2a2a,transparent); }
  .chart-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 16px; }
  .chart-card { background: linear-gradient(180deg,#161616,#111); border: 1px solid var(--border); border-radius: 18px; padding: 18px 20px; min-height: 320px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); transition: border-color .15s; }
  .chart-card:hover { border-color: rgba(255,122,0,0.2); }
  .chart-card h4 { color: #fff; margin: 0 0 12px; font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 6px; }
  .chart-card h4::before { content:''; width: 3px; height: 14px; background: var(--orange); border-radius: 2px; }
  .chart-wrap { position: relative; height: 280px; }
  .table-wrap { background: linear-gradient(180deg,#161616,#111); border: 1px solid var(--border); border-radius: 18px; padding: 14px 16px; overflow-x: auto; margin-bottom: 14px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); }
  table.dash { width: 100%; border-collapse: collapse; color: #fff; font-size: 13px; }
  table.dash th, table.dash td { border-bottom: 1px solid #1f1f1f; padding: 10px 12px; text-align: left; }
  table.dash th { background: transparent; color: var(--orange); font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; border-bottom: 2px solid #2a2a2a; }
  table.dash tr:hover td { background: rgba(255,122,0,0.03); }
  table.dash tr:last-child td { border-bottom: none; }
  .badge { padding: 4px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; }
  .badge.crit { background: #7f1d1d; color: #fca5a5; }
  .badge.low { background: #78350f; color: #fcd34d; }
  .badge.ok { background: #14532d; color: #86efac; }
  .note { color: #9a9a9a; font-size: 12px; margin: 8px 0; }
  .no-data { color: #9a9a9a; padding: 20px; text-align: center; }
  .loading { color: var(--orange); padding: 10px; text-align: left; }
  .forecast-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px,1fr)); gap: 12px; margin-bottom: 14px; }
  .fc-card { background: linear-gradient(135deg,#1a1a1a,#121212); border: 1px solid #2a2a2a; border-radius: 16px; padding: 16px; position: relative; overflow: hidden; transition: transform .15s, border-color .15s; }
  .fc-card:hover { transform: translateY(-2px); border-color: rgba(255,122,0,0.3); }
  .fc-card::before { content:''; position:absolute; top:0; left:0; width:3px; height:100%; background:var(--orange); }
  .fc-card .fc-label { color:#9a9a9a; font-size:11px; text-transform:uppercase; letter-spacing:.7px; font-weight:600; }
  .fc-card .fc-val { color:#fff; font-size:18px; font-weight:800; margin-top:8px; letter-spacing:-0.3px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .fc-card .fc-val > div { white-space:normal; font-size:11px; color:#9a9a9a; margin-top:4px; font-weight:400; letter-spacing:0; }
  .fc-card .fc-icon { position:absolute; top:12px; right:14px; font-size:20px; opacity:0.18; }
  .fc-card.conf .fc-val { color:#4ade80; }
  .conf-bar-wrap { margin-top:10px; }
  .conf-bar { height:6px; background:#2a2a2a; border-radius:3px; overflow:hidden; }
  .conf-fill { height:100%; background:linear-gradient(90deg,#ff7a00,#4ade80); border-radius:3px; transition: width .6s ease; }
  .insight-list { list-style: none; padding: 0; margin: 0; display: grid; gap: 10px; }
  .insight-card { background: linear-gradient(180deg,#161616,#111); border: 1px solid #2a2a2a; border-left: 3px solid #ff7a00; border-radius: 12px; padding: 12px 16px; color: #e5e5e5; font-size: 13px; line-height: 1.5; display: flex; align-items: flex-start; gap: 10px; }
  .insight-card .ic { color: #ff7a00; font-size: 16px; flex-shrink: 0; margin-top: 1px; }
  .insight-card.warn { border-left-color: #fcd34d; }
  .insight-card.warn .ic { color: #fcd34d; }
  .insight-card.good { border-left-color: #4ade80; }
  .insight-card.good .ic { color: #4ade80; }
  .insight-card.bad { border-left-color: #f87171; }
  .insight-card.bad .ic { color: #f87171; }
  .action-card { background: linear-gradient(180deg,#161616,#101010); border: 1px solid #2a2a2a; border-radius: 16px; padding: 16px 18px; margin-bottom: 12px; transition: border-color .15s; }
  .action-card:hover { border-color: rgba(255,122,0,0.25); }
  .action-card .ac-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; flex-wrap: wrap; margin-bottom: 10px; }
  .action-card .ac-title { color: #fff; font-weight: 700; font-size: 14px; }
  .action-card .ac-priority { padding: 3px 10px; border-radius: 999px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
  .action-card .ac-priority.high { background: rgba(248,113,113,0.15); color: #f87171; border: 1px solid rgba(248,113,113,0.3); }
  .action-card .ac-priority.med { background: rgba(252,211,77,0.15); color: #fcd34d; border: 1px solid rgba(252,211,77,0.3); }
  .action-card .ac-priority.low { background: rgba(134,239,172,0.15); color: #86efac; border: 1px solid rgba(134,239,172,0.3); }
  .action-card .ac-row { color: #d4d4d4; font-size: 13px; line-height: 1.5; margin-bottom: 6px; }
  .action-card .ac-row b { color: var(--orange); font-weight: 600; }
  .action-card .ac-conf { color: #9a9a9a; font-size: 11px; margin-top: 6px; }
  @media (max-width: 900px) { .chart-grid { grid-template-columns: 1fr; } }
  @media (max-width: 640px) { .dash-wrap { padding: 14px; } }
  @keyframes fadeUp { from{opacity:0;transform:translateY(10px)} to{opacity:1;transform:none} }
  .chart-card, .table-wrap, .kpi { animation: fadeUp .3s ease; }
  </style>
</head>
<body>

    <!-- SIDEBAR BACKDROP -->
<div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<div id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <img src="logo.jpg" class="logo" alt="Imbo't Silog">
        <button class="close-side" onclick="toggleSidebar()">✕</button>
    </div>
    <nav>
        <a href="home.php">🏠 Home</a>
        <a href="menu.php">☰ Menu</a>
        <a href="about.php">💬 About</a>
        <a href="more.php">ℹ️ More</a>
        <a href="orders.php" class="admin-link">🧾 Orders</a>
        <a href="admin_dashboard.php" class="admin-link">📊 Dashboard</a>
        <a href="admin_inventory.php" class="admin-link">📦 Inventory</a>
        <a href="admin_expenses.php" class="admin-link">💸 Expenses</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>
    <div class="sidebar-footer">
        Imbo't Silog &copy; <?= date('Y') ?>
    </div>
</div>

<!-- NAVBAR -->
<div class="navbar">
    <img src="logo.jpg" class="logo" alt="Imbo't Silog">
    <div class="nav-links">
        <a href="home.php" class="<?= basename($_SERVER['PHP_SELF']) === 'home.php' ? 'active' : '' ?>">🏠 Home</a>
        <a href="menu.php" class="<?= basename($_SERVER['PHP_SELF']) === 'menu.php' ? 'active' : '' ?>">☰ Menu</a>
        <a href="about.php" class="<?= basename($_SERVER['PHP_SELF']) === 'about.php' ? 'active' : '' ?>">💬 About</a>
        <a href="more.php" class="<?= basename($_SERVER['PHP_SELF']) === 'more.php' ? 'active' : '' ?>">ℹ️ More</a>
        <a href="orders.php" class="admin-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">🧾 Orders</a>
        <a href="admin_dashboard.php" class="admin-link <?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="admin_inventory.php" class="admin-link <?= basename($_SERVER['PHP_SELF']) === 'admin_inventory.php' ? 'active' : '' ?>">📦 Inventory</a>
        <a href="admin_expenses.php" class="admin-link <?= basename($_SERVER['PHP_SELF']) === 'admin_expenses.php' ? 'active' : '' ?>">💸 Expenses</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
    <button type="button" class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">☰</button>
</div>

<div class="dash-wrap">
  <h1 class="dash-title">Imbo't Silog Admin Dashboard</h1>
  <p class="dash-sub">Business insights, forecasts, and recommended actions from your actual order records.</p>

  <div class="period-bar">
    <label>Period:</label>
    <select id="period" onchange="onPeriodChange()">
      <option value="today">Today</option>
      <option value="yesterday">Yesterday</option>
      <option value="last7" selected>Last 7 Days</option>
      <option value="last30">Last 30 Days</option>
      <option value="thisWeek">This Week</option>
      <option value="lastWeek">Last Week</option>
      <option value="thisMonth">This Month</option>
      <option value="lastMonth">Last Month</option>
      <option value="custom">Custom Range</option>
    </select>
    <span id="custom-range" style="display:none; gap:6px; align-items:center;">
      <input type="date" id="start-date">
      <span style="color:#fff">to</span>
      <input type="date" id="end-date">
      <button class="pbtn sec" onclick="applyCustom()">Apply</button>
    </span>
    <button class="pbtn" id="prev-btn" onclick="navigate(-1)">◀ Previous</button>
    <button class="pbtn" id="next-btn" onclick="navigate(1)">Next ▶</button>
  </div>

  <div id="range-label" class="note"></div>
  <div id="loading" class="loading">Loading dashboard data…</div>
  <div id="content" style="display:none;">

    <div class="kpi-grid">
      <div class="kpi"><div class="label">Total Revenue</div><div class="val money" id="k-revenue">₱0</div><div class="delta" id="k-revenue-d"></div></div>
      <div class="kpi"><div class="label">Orders (Selected Day)</div><div class="val" id="k-orders">0</div><div class="delta" id="k-orders-d"></div></div>
      <div class="kpi"><div class="label">Overall Stock</div><div class="val" id="k-stock">0</div><div class="delta flat" id="k-stock-d">menu items stock</div></div>
      <div class="kpi"><div class="label">Total Expenses</div><div class="val money" id="k-expenses" style="color:#f87171;">₱0</div><div class="delta flat" id="k-expenses-d">selected period</div></div>
      <div class="kpi" id="k-profit-card"><div class="label">Net Profit / Loss</div><div class="val" id="k-profit">₱0</div><div class="delta flat" id="k-profit-d">revenue − expenses</div></div>
    </div>

    <div id="loss-banner" style="display:none; background:rgba(127,29,29,0.25); border:1px solid #f87171; border-left:4px solid #f87171; border-radius:12px; padding:14px 18px; margin-bottom:18px; color:#fca5a5; font-weight:600; font-size:13px;"></div>

      <h2 class="section-title">Sales Performance</h2>
    <div class="chart-card">
      <h4>Sales Trend</h4>
      <div class="chart-wrap"><canvas id="salesChart"></canvas></div>
    </div>

    <h2 class="section-title">Top Sellers</h2>
    <div class="table-wrap" style="margin-bottom:14px;">
      <h4 style="color:#ff7a00;margin:0 0 8px;">Top Sellers</h4>
      <table class="dash" id="top-menu-table"><thead><tr><th>Item</th><th>Qty</th><th>Revenue</th><th>vs Prev</th></tr></thead><tbody></tbody></table>
      <h4 style="color:#ff7a00;margin:16px 0 8px;">Lowest Performers</h4>
      <table class="dash" id="low-menu-table"><thead><tr><th>Item</th><th>Qty</th><th>Revenue</th></tr></thead><tbody></tbody></table>
    </div>

    <div id="peak-text" class="note" style="margin-bottom:14px;"></div>

    <h2 class="section-title">Menu Items (Stock &amp; Availability)</h2>
    <div class="table-wrap" style="margin-bottom:14px;">
      <table class="dash" id="menu-stock-table">
        <thead><tr><th>Item</th><th>Category</th><th>Stock</th><th>Status</th></tr></thead>
        <tbody></tbody>
      </table>
    </div>
    <div id="menu-stock-pager" style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:24px;"></div>

    <h2 class="section-title">Business Insights</h2>
    <ul class="insight-list" id="insights"></ul>

    <h2 class="section-title">Recommended Actions</h2>
    <div id="actions"></div>

    <div id="no-data" class="no-data" style="display:none;">No business records were found for this period.</div>
  </div>
</div>

<script>
const _t = new Date();
const TODAY = _t.getFullYear()+'-'+String(_t.getMonth()+1).padStart(2,'0')+'-'+String(_t.getDate()).padStart(2,'0');
let state = { start: null, end: null };
let charts = {};

function peso(n){ n = Number(n)||0; return '₱' + n.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}); }
function fmtDate(s){ const d=new Date(s+'T00:00:00'); return d.toLocaleDateString('en-PH',{month:'short',day:'numeric',year:'numeric'}); }
function delta(cur,prev){
  if (prev == null || prev == 0) return '<span class="flat">no previous data</span>';
  const p = ((cur-prev)/prev*100);
  const cls = p>0.5?'up':(p<-0.5?'down':'flat');
  const arrow = p>0.5?'▲':(p<-0.5?'▼':'');
  return `<span class="${cls}">${arrow} ${Math.abs(p).toFixed(1)}% vs prev period</span>`;
}

function currentRangeDays(){ return (new Date(state.end)-new Date(state.start))/(86400000)+1; }

function setRange(s,e){
  state.start=s; state.end=e;
  document.getElementById('start-date').value=s;
  document.getElementById('end-date').value=e;
}

function onPeriodChange(){
  const v = document.getElementById('period').value;
  const wrap = document.getElementById('custom-range');
  wrap.style.display = (v==='custom') ? 'flex' : 'none';
  const t = new Date(TODAY+'T00:00:00');
  let s,e;
  if (v==='today'){ s=e=TODAY; }
  else if (v==='yesterday'){ const d=new Date(t); d.setDate(d.getDate()-1); s=e=d.toISOString().slice(0,10); }
  else if (v==='last7'){ const d=new Date(t); d.setDate(d.getDate()-6); s=d.toISOString().slice(0,10); e=TODAY; }
  else if (v==='last30'){ const d=new Date(t); d.setDate(d.getDate()-29); s=d.toISOString().slice(0,10); e=TODAY; }
  else if (v==='thisWeek'){ const d=new Date(t); const day=d.getDay()||7; d.setDate(d.getDate()-(day-1)); s=d.toISOString().slice(0,10); e=TODAY; }
  else if (v==='lastWeek'){ const d=new Date(t); const day=d.getDay()||7; d.setDate(d.getDate()-(day-1)-7); s=d.toISOString().slice(0,10); const e2=new Date(d); e2.setDate(e2.getDate()+6); e=e2.toISOString().slice(0,10); }
  else if (v==='thisMonth'){ s=TODAY.slice(0,7)+'-01'; e=TODAY; }
  else if (v==='lastMonth'){ const d=new Date(t.getFullYear(),t.getMonth(),0); e=d.toISOString().slice(0,10); const s2=new Date(t.getFullYear(),t.getMonth()-1,1); s=s2.toISOString().slice(0,10); }
  else if (v==='custom'){ return; }
  if (s) { setRange(s,e); loadData(); }
}

function applyCustom(){
  const s=document.getElementById('start-date').value, e=document.getElementById('end-date').value;
  if (!s||!e) return;
  setRange(s,e); loadData();
}

function navigate(dir){
  const len = currentRangeDays();
  const s=new Date(state.start+'T00:00:00'), e=new Date(state.end+'T00:00:00');
  s.setDate(s.getDate()+dir*len); e.setDate(e.getDate()+dir*len);
  const ns=s.toISOString().slice(0,10), ne=e.toISOString().slice(0,10);
  if (ne > TODAY) return;
  setRange(ns,ne);
  document.getElementById('period').value='custom';
  document.getElementById('custom-range').style.display='flex';
  loadData();
}

function renderBaseAI(){
  const d = window.lastData || {};
  if (d.insights && d.insights.length) {
    document.getElementById('insights').innerHTML = d.insights.map(i=>{
      let cls=''; let ic='💡';
      const low = i.toLowerCase();
      if (low.includes('declin')||low.includes('drop')||low.includes('low')||low.includes('risk')) { cls='warn'; ic='⚠️'; }
      else if (low.includes('increas')||low.includes('growth')||low.includes('high')) { cls='good'; ic='📈'; }
      else if (low.includes('cancel')||low.includes('critical')) { cls='bad'; ic='🚨'; }
      return `<li class="insight-card ${cls}"><span class="ic">${ic}</span><span>${i}</span></li>`;
    }).join('');
  } else {
    document.getElementById('insights').innerHTML = '<li class="insight-card">No insights available.</li>';
  }
  if (d.actions && d.actions.length) {
    document.getElementById('actions').innerHTML = d.actions.map(a=>{
      const pri = String(a.priority||'').toLowerCase();
      const pc = pri.startsWith('h')?'high':(pri.startsWith('m')?'med':'low');
      return `<div class="action-card">
        <div class="ac-head">
          <div class="ac-title">${a.title||''}</div>
          <span class="ac-priority ${pc}">${(a.priority||'').toUpperCase()}</span>
        </div>
        <div class="ac-row"><b>Reason:</b> ${a.reason||''}</div>
        <div class="ac-row"><b>Suggested Action:</b> ${a.action||''}</div>
        <div class="ac-row"><b>Expected Result:</b> ${a.result||''}</div>
        ${a.confidence?`<div class="ac-conf">Confidence: ${a.confidence}</div>`:''}
      </div>`;
    }).join('');
  } else {
    document.getElementById('actions').innerHTML = '<div class="no-data">No recommended actions for this period.</div>';
  }
}

async function loadAI(){
  try {
    const res = await fetch(`admin_ai.php?start=${state.start}&end=${state.end}`);
    const data = await res.json();
    if (data.ok) {
      if (data.insights && data.insights.length) {
        document.getElementById('insights').innerHTML = data.insights.map(i=>{
          let cls=''; let ic='💡';
          const low = i.toLowerCase();
          if (low.includes('declin')||low.includes('drop')||low.includes('low')||low.includes('risk')) { cls='warn'; ic='⚠️'; }
          else if (low.includes('increas')||low.includes('growth')||low.includes('high')) { cls='good'; ic='📈'; }
          else if (low.includes('cancel')||low.includes('critical')) { cls='bad'; ic='🚨'; }
          return `<li class="insight-card ${cls}"><span class="ic">${ic}</span><span>${i}</span></li>`;
        }).join('');
      }
      if (data.actions && data.actions.length) {
        document.getElementById('actions').innerHTML = data.actions.map(a=>{
          const pri = String(a.priority||'').toLowerCase();
          const pc = pri.startsWith('h')?'high':(pri.startsWith('m')?'med':'low');
          return `<div class="action-card">
            <div class="ac-head">
              <div class="ac-title">${a.title||''}</div>
              <span class="ac-priority ${pc}">${(a.priority||'').toUpperCase()}</span>
            </div>
            <div class="ac-row"><b>Reason:</b> ${a.reason||''}</div>
            <div class="ac-row"><b>Suggested Action:</b> ${a.action||''}</div>
            <div class="ac-row"><b>Expected Result:</b> ${a.result||''}</div>
          </div>`;
        }).join('');
      }
    } else {
      renderBaseAI();
    }
  } catch(e) {
    renderBaseAI();
  }
}

function fitText(id, maxSize, minSize) {
  const el = document.getElementById(id);
  if (!el) return;
  el.style.fontSize = maxSize + 'px';
  let size = maxSize;
  while (size > minSize && el.scrollWidth > el.clientWidth + 1) {
    size -= 1;
    el.style.fontSize = size + 'px';
  }
}

async function loadData(){
  document.getElementById('loading').style.display='block';
  document.getElementById('content').style.display='none';
  const url = `admin_dashboard_data.php?start=${state.start}&end=${state.end}`;
  let data;
  try {
    const res = await fetch(url);
    data = await res.json();
  } catch(err) { document.getElementById('loading').textContent='Failed to load data.'; return; }

  const r = data.range;
  document.getElementById('range-label').textContent = `Selected period: ${fmtDate(r.start)} – ${fmtDate(r.end)}  (compared with ${fmtDate(r.prev_start)} – ${fmtDate(r.prev_end)})`;
  document.getElementById('next-btn').disabled = (r.end >= TODAY);

  const c = data.current, p = data.previous;
  const rangeTotal = c.total_sales;
  document.getElementById('k-revenue').textContent = '₱' + (rangeTotal||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
  document.getElementById('k-revenue-d').innerHTML = (p && p.total_sales) ? delta(c.total_sales, p.total_sales) : '<span class="flat">no previous data</span>';
  document.getElementById('k-orders').textContent = c.total_orders;
  document.getElementById('k-orders-d').innerHTML = (p && p.total_orders) ? delta(c.total_orders, p.total_orders) : '<span class="flat">no previous data</span>';
  const overallStock = (data.menu_list || []).reduce((a,m)=>a+(m.stock||0),0);
  document.getElementById('k-stock').textContent = overallStock.toLocaleString();
  document.getElementById('k-stock-d').innerHTML = '<span class="flat">' + (data.menu_list||[]).length + ' menu items</span>';
  const expTotal = data.expenses_total || 0;
  const netProfit = data.net_profit || 0;
  document.getElementById('k-expenses').textContent = '₱' + expTotal.toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
  const profitEl = document.getElementById('k-profit');
  profitEl.textContent = '₱' + Math.abs(netProfit).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
  profitEl.style.color = netProfit >= 0 ? '#4ade80' : '#f87171';
  document.getElementById('k-profit-d').innerHTML = netProfit >= 0 ? '<span class="flat">profit</span>' : '<span style="color:#f87171;font-weight:700;">LOSS / bankrupt risk</span>';
  document.getElementById('k-profit').textContent = (netProfit < 0 ? '- ' : '') + '₱' + Math.abs(netProfit).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
  const banner = document.getElementById('loss-banner');
  if (banner) {
    if (netProfit < 0) {
      banner.style.display = 'block';
      banner.innerHTML = '⚠️ Financial loss on this period: expenses exceeded revenue by ₱' + Math.abs(netProfit).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2}) + '. Review costs to avoid bankrupt risk.';
    } else {
      banner.style.display = 'none';
    }
  }

  const t = data.charts.trend;
  if (charts['salesChart']) charts['salesChart'].destroy();
  const ctx = document.getElementById('salesChart').getContext('2d');
  charts['salesChart'] = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: t.labels,
      datasets: [
        { type:'bar',  label:'Expenses (₱)', data: t.expenses, backgroundColor:'rgba(248,113,113,0.5)', borderColor:'#f87171', borderWidth:1, borderRadius:4, order:2 },
        { type:'line', label:'Revenue (₱)', data: t.values, borderColor:'#ff7a00', backgroundColor:'rgba(255,122,0,0.12)', tension:0.35, fill:true, pointBackgroundColor:'#ff7a00', pointRadius:3, borderWidth:2, order:1 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { labels: { color: '#fff' } }, tooltip: { callbacks: { label: ctx => ctx.dataset.label + ': ₱' + ctx.parsed.y.toLocaleString() } } },
      scales: { x: { ticks: { color:'#bbb', font:{size:10} }, grid: { color:'#2a2a2a' } }, y: { ticks: { color:'#bbb', callback: v=>'₱'+v }, grid: { color:'#2a2a2a' }, beginAtZero: true } }
    }
  });
  // Peer charts removed (single visualization: sales trend only).
  if (document.getElementById('ordersChart')) makeChart('ordersChart','line', data.charts.orders.labels, [
    {label:'Completed',data:data.charts.orders.completed,borderColor:'#4ade80',tension:.3},
    {label:'Pending',data:data.charts.orders.pending,borderColor:'#fbbf24',tension:.3},
    {label:'Cancelled',data:data.charts.orders.cancelled,borderColor:'#f87171',tension:.3},
  ]);
  if (document.getElementById('statusChart')) { const sc = data.charts.status; makeChart('statusChart','doughnut', Object.keys(sc), [{data:Object.values(sc),backgroundColor:['#4ade80','#fbbf24','#f87171']}]); }
  const menu = data.charts.menu;
  if (document.getElementById('menuChart')) makeChart('menuChart','bar', menu.map(m=>m.name), [{label:'Qty sold',data:menu.map(m=>m.qty),backgroundColor:'#ff7a00'}], 'x');
  const ph = data.charts.peak_hours;
  if (document.getElementById('peakChart')) makeChart('peakChart','bar', ph.labels, [{label:'Orders',data:ph.values,backgroundColor:'#ff7a00'}]);

  if (data.peak_hours_list.length){
    const p = data.peak_hours_list[0];
    document.getElementById('peak-text').textContent = `Peak period: ${String(p.hour).padStart(2,'0')}:00 with ${p.orders} orders.`;
  } else {
    document.getElementById('peak-text').textContent = 'No peak data available.';
  }

  fillTable('top-menu-table', menu.map(m=>`<tr><td>${m.name}</td><td>${m.qty}</td><td>${peso(m.revenue)}</td><td>${m.change===null?'—':(m.change+'%')}</td></tr>`).join(''));
  fillTable('low-menu-table', data.menu_low.map(m=>`<tr><td>${m.name}</td><td>${m.qty}</td><td>${peso(m.revenue)}</td></tr>`).join(''));

  // Menu items stock & availability list (with pagination)
  const menuStock = data.menu_list || [];
  const MS_PER_PAGE = 10;
  window.menuStockData = menuStock;
  window.menuStockPage = 1;
  function renderMenuStockPage(p) {
    const total = window.menuStockData.length;
    const totalPages = Math.max(1, Math.ceil(total / MS_PER_PAGE));
    if (p < 1) p = 1; if (p > totalPages) p = totalPages;
    window.menuStockPage = p;
    const start = (p-1)*MS_PER_PAGE;
    const slice = window.menuStockData.slice(start, start+MS_PER_PAGE);
    document.querySelector('#menu-stock-table tbody').innerHTML = slice.map(m => {
      return `<tr><td><b>${m.name}</b></td><td>${m.category}</td><td>${m.stock}</td><td><span class="badge ${m.status==='Available'?(m.stock<=0?'crit':'ok'):'off'}">${m.status==='Available'?(m.stock<=0?'Out of stock':'Available'):'Unavailable'}</span></td></tr>`;
    }).join('') || '<tr><td colspan="4" class="no-data">No menu items.</td></tr>';
    const pager = document.getElementById('menu-stock-pager');
    if (!pager) return;
    if (totalPages <= 1) { pager.innerHTML = ''; return; }
    let html = '';
    const mk = (label, pg, disabled, active) => `<a href="#" onclick="renderMenuStockPage(${pg});return false;" style="padding:6px 12px;border:1px solid #3a3a3a;border-radius:8px;color:#fff;text-decoration:none;font-size:12px;${active?'background:#ff7a00;color:#000;border-color:#ff7a00;font-weight:700;':''}${disabled?'opacity:.4;pointer-events:none;':''}">${label}</a>`;
    html += mk('◀ Prev', p-1, p<=1, false);
    for (let i=1; i<=totalPages; i++) html += mk(i, i, false, i===p);
    html += mk('Next ▶', p+1, p>=totalPages, false);
    pager.innerHTML = html;
  }
  window.renderMenuStockPage = renderMenuStockPage;
  renderMenuStockPage(1);

  // Forecast section removed; keep chart rendering minimal.

  window.lastData = data;
  document.getElementById('insights').innerHTML = '<li class="loading">Generating insights…</li>';
  document.getElementById('actions').innerHTML = '<div class="loading">Generating recommended actions…</div>';

  document.getElementById('no-data').style.display = data.has_data ? 'none' : 'block';
  document.getElementById('loading').style.display='none';
  document.getElementById('content').style.display='block';
  renderBaseAI();
  // Async AI polishing (failover: beam -> phi4-mini -> deterministic fallback)
  loadAI();
}

function fillTable(id, html){
  const tbody = document.querySelector('#'+id+' tbody');
  if (tbody) tbody.innerHTML = html || '<tr><td colspan="4" class="no-data">No data.</td></tr>';
}

function makeChart(id, type, labels, datasets, axisFlag){
  if (charts[id]) charts[id].destroy();
  const ctx = document.getElementById(id).getContext('2d');
  const opts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: '#fff' } } },
    scales: type==='doughnut' ? {} : {
      x: { ticks: { color:'#bbb' }, grid: { color:'#2a2a2a' } },
      y: { ticks: { color:'#bbb' }, grid: { color:'#2a2a2a' }, beginAtZero: true }
    }
  };
  if (axisFlag==='x') opts.indexAxis = 'y';
  charts[id] = new Chart(ctx, { type, data: { labels, datasets }, options: opts });
}

document.addEventListener('DOMContentLoaded', ()=>{
  document.getElementById('period').value = 'last7';
  onPeriodChange();
});
</script>
</body>
</html>
