<?php
include 'auth.php'; require_admin(); include 'silog.php';

$messages=[]; $errors=[];
$date = $_GET['date'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) $date = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'') === 'add_expense') {
  if (!verify_csrf()) $errors[]='Invalid request.';
  else {
    $edate = $_POST['expense_date'] ?? $date;
    $d2 = DateTime::createFromFormat('Y-m-d', $edate);
    if (!$d2 || $d2->format('Y-m-d') !== $edate) $edate = $date;
    $cat = trim($_POST['category'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $amt = (float)($_POST['amount'] ?? 0);
    if ($cat==='' || $amt<=0) $errors[]='Category and a positive amount are required.';
    if (!$errors) {
      $st = $conn->prepare('INSERT INTO expenses (expense_date, category, description, amount) VALUES (?,?,?,?)');
      $st->bind_param('sssd', $edate, $cat, $desc, $amt);
      if ($st->execute()) $messages[]='Expense added.';
      else $errors[]='Failed to add expense.';
      $st->close();
      $date = $edate;
    }
  }
}
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'') === 'delete_expense') {
  if (!verify_csrf()) $errors[]='Invalid request.';
  else {
    $eid = (int)($_POST['id'] ?? 0);
    $conn->query("DELETE FROM expenses WHERE id=$eid");
    $messages[]='Expense deleted.';
  }
}

$escDate = $conn->real_escape_string($date);
$totRes = $conn->query("SELECT COALESCE(SUM(amount),0) AS total FROM expenses WHERE expense_date='$escDate'");
$dayExpTotal = (float)$totRes->fetch_assoc()['total'];

// pagination
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$cntRes = $conn->query("SELECT COUNT(*) c FROM expenses WHERE expense_date='$escDate'");
$totalRows = (int)$cntRes->fetch_assoc()['c'];
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page-1)*$perPage;
$rows = [];
$r = $conn->query("SELECT * FROM expenses WHERE expense_date='$escDate' ORDER BY id DESC LIMIT $perPage OFFSET $offset");
if ($r) while ($rr=$r->fetch_assoc()) $rows[] = $rr;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Expenses - Imbo't Silog</title>
<link rel="stylesheet" href="home.css?v=5">
<script src="sidebar.js?v=3" defer></script>
<style>
  :root { --orange:#ff7a00; --bg:#0a0a0a; --card:#141414; --border:#2a2a2a; }
  body { background:var(--bg); }
  .dash-wrap { max-width: 90%; margin: 0 auto; padding: 20px 24px 48px; }
  .dash-title { color:#fff; font-size:28px; font-weight:800; margin:0; letter-spacing:-0.5px; }
  .dash-sub { color:#9a9a9a; font-size:13px; margin:4px 0 18px; }
  .panel { background:linear-gradient(180deg,#161616,#111); border:1px solid var(--border); border-radius:18px; padding:20px 22px; margin-bottom:18px; box-shadow:0 4px 20px rgba(0,0,0,0.3); }
  .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:18px; }
  .kpi { background:linear-gradient(135deg,#1a1a1a,#121212); border:1px solid var(--border); border-radius:16px; padding:18px 20px; position:relative; overflow:hidden; }
  .kpi::before { content:''; position:absolute; top:0; left:0; width:3px; height:100%; background:var(--orange); }
  .kpi .label { color:#9a9a9a; font-size:11px; text-transform:uppercase; letter-spacing:.7px; font-weight:600; }
  .kpi .val { color:#fff; font-size:26px; font-weight:800; margin-top:6px; }
  .kpi .val.money { color:#f87171; }
  .filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
  .field { box-sizing:border-box; padding:10px 12px; border-radius:10px; border:1px solid #3a3a3a; background:#0f0f0f; color:#fff; font-family:'Inter',Arial,sans-serif; font-size:14px; }
  .btn { background:var(--orange); color:#000; border:none; font-weight:700; padding:10px 16px; border-radius:10px; cursor:pointer; font-family:'Inter',Arial,sans-serif; font-size:13px; text-decoration:none; display:inline-block; }
  .btn.sec { background:#2a2a2a; color:#fff; }
  .btn.danger { background:#7f1d1d; color:#fca5a5; }
  .btn.sm { padding:6px 10px; font-size:12px; border-radius:8px; }
  .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:10px; margin-bottom:10px; }
  .table-wrap { overflow-x:auto; }
  table.dash { width:100%; border-collapse:collapse; color:#fff; font-size:13px; }
  table.dash th, table.dash td { border-bottom:1px solid #1f1f1f; padding:11px 12px; text-align:left; vertical-align:middle; }
  table.dash th { color:var(--orange); font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #2a2a2a; white-space:nowrap; }
  table.dash tr:hover td { background:rgba(255,122,0,0.03); }
  table.dash tr:last-child td { border-bottom:none; }
  .msg { color:#4ade80; margin:6px 0; font-size:13px; }
  .err { color:#f87171; margin:6px 0; font-size:13px; }
  .empty { text-align:center; color:#777; padding:30px 10px; }
  .note { color:#9a9a9a; font-size:12px; margin:8px 0; }
  .pager { display:flex; gap:6px; flex-wrap:wrap; margin-top:14px; }
  .pager a { padding:6px 12px; border:1px solid #3a3a3a; border-radius:8px; color:#fff; text-decoration:none; font-size:12px; }
  .pager a.active { background:var(--orange); color:#000; border-color:var(--orange); font-weight:700; }
  .pager a.disabled { opacity:.4; pointer-events:none; }
</style>
</head>
<body>

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
        <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">🧾 Orders</a>
        <a href="admin_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="admin_inventory.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_inventory.php' ? 'active' : '' ?>">📦 Inventory</a>
        <a href="admin_expenses.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_expenses.php' ? 'active' : '' ?>">💸 Expenses</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
    <button type="button" class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">☰</button>
</div>

<div class="dash-wrap">
  <h1 class="dash-title">Expenses</h1>
  <p class="dash-sub">Record daily business expenses. They are compared against revenue on the dashboard.</p>

  <?php foreach($messages as $m) echo '<div class="msg">✅ '.htmlspecialchars($m).'</div>'; ?>
  <?php foreach($errors as $e) echo '<div class="err">⚠️ '.htmlspecialchars($e).'</div>'; ?>

  <div class="kpi-grid">
    <div class="kpi"><div class="label">Date</div><div class="val" style="font-size:18px;"><?php echo date('M d, Y', strtotime($date)); ?></div></div>
    <div class="kpi"><div class="label">Total Expenses</div><div class="val money">₱<?php echo number_format($dayExpTotal, 2); ?></div></div>
    <div class="kpi"><div class="label">Records</div><div class="val"><?php echo $totalRows; ?></div></div>
  </div>

  <div class="panel">
    <h3 style="color:var(--orange);margin:0 0 12px;">Add Expense</h3>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
      <input type="hidden" name="action" value="add_expense">
      <div class="form-grid">
        <input class="field" type="date" name="expense_date" value="<?php echo htmlspecialchars($date); ?>" max="<?php echo date('Y-m-d'); ?>" required>
        <select class="field" name="category" required>
          <option value="">Category</option>
          <?php foreach (['Ingredients','Utilities','Rent','Salaries','Supplies','Marketing','Maintenance','Other'] as $c) echo '<option value="'.htmlspecialchars($c).'">'.htmlspecialchars($c).'</option>'; ?>
        </select>
        <input class="field" name="description" placeholder="Description (optional)">
        <input class="field" name="amount" type="number" step="0.01" placeholder="Amount (₱)" required>
      </div>
      <button class="btn" type="submit">Add Expense</button>
    </form>
  </div>

  <div class="panel">
    <div class="filters">
      <label style="color:#fff;font-weight:700;font-size:13px;">View date:</label>
      <form method="get" style="display:flex;gap:10px;align-items:center;">
        <input class="field" type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" max="<?php echo date('Y-m-d'); ?>">
        <button class="btn sec" type="submit">Load</button>
      </form>
      <a class="btn sec" href="admin_expenses.php" style="text-decoration:none;">Today</a>
    </div>

    <div class="table-wrap">
      <table class="dash">
        <thead><tr><th>ID</th><th>Date</th><th>Category</th><th>Description</th><th>Amount</th><th></th></tr></thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="empty">No expenses recorded for this date.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $e): ?>
          <tr>
            <td><?php echo (int)$e['id']; ?></td>
            <td><?php echo date('M d, Y', strtotime($e['expense_date'])); ?></td>
            <td><?php echo htmlspecialchars($e['category']); ?></td>
            <td><?php echo htmlspecialchars($e['description'] ?: '—'); ?></td>
            <td class="total" style="color:#f87171;">₱<?php echo number_format((float)$e['amount'], 2); ?></td>
            <td>
              <form method="post" style="display:inline;" onsubmit="return confirm('Delete this expense?');">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                <input type="hidden" name="action" value="delete_expense">
                <input type="hidden" name="id" value="<?php echo (int)$e['id']; ?>">
                <button class="btn sm danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="note">Showing <?php echo count($rows) ? ($offset+1) : 0; ?>–<?php echo $offset + count($rows); ?> of <?php echo $totalRows; ?> expenses. Page <?php echo $page; ?> of <?php echo $totalPages; ?>.</p>

    <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php $base = 'admin_expenses.php?date='.urlencode($date).'&page='; ?>
      <a class="<?php echo $page<=1?'disabled':''; ?>" href="<?php echo $base.max(1,$page-1); ?>">◀ Prev</a>
      <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <a class="<?php echo $i===$page?'active':''; ?>" href="<?php echo $base.$i; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      <a class="<?php echo $page>=$totalPages?'disabled':''; ?>" href="<?php echo $base.min($totalPages,$page+1); ?>">Next ▶</a>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
