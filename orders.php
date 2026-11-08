<?php
include 'auth.php'; require_admin(); include 'silog.php'; include 'order_items.php';

// Mark order status
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action']??'')==='set_status') {
  if (!verify_csrf()) { http_response_code(403); echo 'Invalid request.'; exit; }
  $oid = (int)($_POST['order_id'] ?? 0);
  $new = $_POST['new_status'] ?? '';
  $allowed = ['preparing','on the way','paid','cancelled'];
  if (in_array($new, $allowed, true)) {
    $safe = $conn->real_escape_string($new);
    $conn->query("UPDATE orders SET status='$safe' WHERE id=$oid");
  }
  $qs = http_build_query(array_filter(['date'=>$_GET['date']??null,'period'=>$_GET['period']??null,'start'=>$_GET['start']??null,'end'=>$_GET['end']??null,'page'=>$_GET['page']??null]));
  header('Location: orders.php'.($qs?'?'.$qs:''));
  exit;
}

// Resolve selected range
$period = $_GET['period'] ?? '';
$start = null; $end = null; $singleDate = null;

function cdate($s) {
  $d = DateTime::createFromFormat('Y-m-d', $s);
  return ($d && $d->format('Y-m-d')===$s) ? $s : null;
}

if ($period === 'last7') {
  $end = date('Y-m-d'); $start = date('Y-m-d', strtotime('-6 days'));
} elseif ($period === 'last30') {
  $end = date('Y-m-d'); $start = date('Y-m-d', strtotime('-29 days'));
} elseif ($period === 'thisMonth') {
  $end = date('Y-m-d'); $start = date('Y-m-01');
} elseif ($period === 'custom') {
  $start = cdate($_GET['start'] ?? '');
  $end = cdate($_GET['end'] ?? '');
  if (!$start || !$end) { $period=''; }
} elseif (isset($_GET['date'])) {
  $singleDate = cdate($_GET['date']);
  if ($singleDate) { $start = $singleDate; $end = $singleDate; }
}

// default: today
if (!$start || !$end) {
  $period = ''; $singleDate = date('Y-m-d');
  $start = $singleDate; $end = $singleDate;
}

$today = date('Y-m-d');
$escStart = $conn->real_escape_string($start);
$escEnd = $conn->real_escape_string($end);

// totals for the range
$revRes = $conn->query("SELECT COALESCE(SUM(CASE WHEN status='paid' THEN total_amount ELSE 0 END),0) AS total, COUNT(*) AS cnt, COUNT(CASE WHEN status='paid' THEN 1 END) AS paidCnt FROM orders WHERE DATE(order_date) BETWEEN '$escStart' AND '$escEnd'");
$revRow = $revRes ? $revRes->fetch_assoc() : ['total'=>0,'cnt'=>0,'paidCnt'=>0];
$rangeTotal = (float)$revRow['total'];
$rangeCount = (int)$revRow['cnt'];
$rangePaidCount = (int)$revRow['paidCnt'];

// pagination
$perPage = 15;
$page = max(1, (int)($_GET['page'] ?? 1));
$totalRows = $rangeCount;
$totalPages = max(1, (int)ceil($totalRows / $perPage));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;

$rows = [];
$res = $conn->query("SELECT * FROM orders WHERE DATE(order_date) BETWEEN '$escStart' AND '$escEnd' ORDER BY order_date DESC, id DESC LIMIT $perPage OFFSET $offset");
if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;

$rangeLabel = ($start === $end) ? date('M d, Y', strtotime($start)) : date('M d, Y', strtotime($start)).' – '.date('M d, Y', strtotime($end));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders - Imbo't Silog</title>
<link rel="stylesheet" href="home.css?v=5">
<script src="sidebar.js?v=3" defer></script>
<style>
  :root { --orange:#ff7a00; --bg:#0a0a0a; --card:#141414; --border:#2a2a2a; }
  body { background:var(--bg); }
  .dash-wrap { max-width: 100%; margin: 0; padding: 20px 24px 48px; }
  .dash-title { color:#fff; font-size:28px; font-weight:800; margin:0; letter-spacing:-0.5px; }
  .dash-sub { color:#9a9a9a; font-size:13px; margin:4px 0 18px; }
  .panel { background:linear-gradient(180deg,#161616,#111); border:1px solid var(--border); border-radius:18px; padding:20px 22px; margin-bottom:18px; box-shadow:0 4px 20px rgba(0,0,0,0.3); }
  .filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
  .field { box-sizing:border-box; padding:10px 12px; border-radius:10px; border:1px solid #3a3a3a; background:#0f0f0f; color:#fff; font-family:'Inter',Arial,sans-serif; font-size:14px; }
  .btn { background:var(--orange); color:#000; border:none; font-weight:700; padding:10px 16px; border-radius:10px; cursor:pointer; font-family:'Inter',Arial,sans-serif; font-size:13px; text-decoration:none; display:inline-block; }
  .btn.sec { background:#2a2a2a; color:#fff; }
  .btn.sm { padding:6px 10px; font-size:12px; border-radius:8px; }
  .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:14px; margin-bottom:18px; }
  .kpi { background:linear-gradient(135deg,#1a1a1a,#121212); border:1px solid var(--border); border-radius:16px; padding:18px 20px; position:relative; overflow:hidden; }
  .kpi::before { content:''; position:absolute; top:0; left:0; width:3px; height:100%; background:var(--orange); }
  .kpi .label { color:#9a9a9a; font-size:11px; text-transform:uppercase; letter-spacing:.7px; font-weight:600; }
  .kpi .val { color:#fff; font-size:26px; font-weight:800; margin-top:6px; }
  .kpi .val.money { color:#4ade80; }
  .table-wrap { overflow-x:auto; }
  table.dash { width:100%; border-collapse:collapse; color:#fff; font-size:13px; }
  table.dash th, table.dash td { border-bottom:1px solid #1f1f1f; padding:11px 12px; text-align:left; vertical-align:middle; }
  table.dash th { color:var(--orange); font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #2a2a2a; white-space:nowrap; }
  table.dash tr:hover td { background:rgba(255,122,0,0.03); }
  table.dash tr:last-child td { border-bottom:none; }
  .badge { padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; color:#fff; background:#555; }
  .badge-paid, .badge-completed { background:#14532d; color:#86efac; }
  .badge-pending { background:#78350f; color:#fcd34d; }
  .badge-preparing { background:#5b21b6; color:#ddd6fe; }
  .badge-on-the-way { background:#1e40af; color:#93c5fd; }
  .badge-cancelled { background:#7f1d1d; color:#fca5a5; }
  .items { color:#9a9a9a; font-size:12px; max-width:260px; }
  .total { color:#4ade80; font-weight:700; }
  .queue { color:var(--orange); font-weight:700; }
  .pager { display:flex; gap:6px; flex-wrap:wrap; margin-top:14px; }
  .pager a { padding:6px 12px; border:1px solid #3a3a3a; border-radius:8px; color:#fff; text-decoration:none; font-size:12px; }
  .pager a.active { background:var(--orange); color:#000; border-color:var(--orange); font-weight:700; }
  .pager a.disabled { opacity:.4; pointer-events:none; }
  .empty { text-align:center; color:#777; padding:30px 10px; }
  .note { color:#9a9a9a; font-size:12px; margin:8px 0; }
  .range-tabs { display:flex; gap:6px; flex-wrap:wrap; }
  .range-tabs a { padding:6px 12px; border:1px solid #3a3a3a; border-radius:8px; color:#fff; text-decoration:none; font-size:12px; font-weight:600; }
  .range-tabs a.active { background:var(--orange); color:#000; border-color:var(--orange); }
  .custom-range { display:none; gap:6px; align-items:center; }
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
  <h1 class="dash-title">Orders</h1>
  <p class="dash-sub">All customer orders. Filter by day or last-days range to see totals and records.</p>

  <div class="kpi-grid">
    <div class="kpi"><div class="label">Range</div><div class="val" style="font-size:18px;"><?php echo $rangeLabel; ?></div></div>
    <div class="kpi"><div class="label">Total Orders</div><div class="val"><?php echo $rangeCount; ?></div></div>
    <div class="kpi"><div class="label">Paid Orders</div><div class="val"><?php echo $rangePaidCount; ?></div></div>
    <div class="kpi"><div class="label">Paid Revenue</div><div class="val money">₱<?php echo number_format($rangeTotal, 2); ?></div></div>
    <div class="kpi"><div class="label">Avg Paid Order</div><div class="val">₱<?php echo $rangePaidCount ? number_format($rangeTotal/$rangePaidCount, 2) : '0.00'; ?></div></div>
  </div>

  <div class="panel">
    <div class="range-tabs" style="margin-bottom:12px;">
      <?php
        $base = 'orders.php?';
        $curPeriod = $period ?: ($singleDate ? 'day' : '');
      ?>
      <a class="<?php echo ($curPeriod===''||$curPeriod==='day')?'active':''; ?>" href="orders.php">Today</a>
      <a class="<?php echo $curPeriod==='last7'?'active':''; ?>" href="orders.php?period=last7">Last 7 Days</a>
      <a class="<?php echo $curPeriod==='last30'?'active':''; ?>" href="orders.php?period=last30">Last 30 Days</a>
      <a class="<?php echo $curPeriod==='thisMonth'?'active':''; ?>" href="orders.php?period=thisMonth">This Month</a>
    </div>

    <form method="get" class="filters" style="margin-top:10px;">
      <label style="color:#fff;font-weight:700;font-size:13px;">Or pick a single day:</label>
      <input class="field" type="date" name="date" value="<?php echo $singleDate ? htmlspecialchars($singleDate) : ''; ?>" max="<?php echo $today; ?>">
      <button class="btn sec" type="submit">Fetch Day</button>
    </form>

    <div class="table-wrap" style="margin-top:14px;">
      <table class="dash">
        <thead><tr>
          <th>ID</th><th>Queue #</th><th>Customer</th><th>Address</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Time</th><th>Print</th>
        </tr></thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="10" class="empty">No orders found for <?php echo $rangeLabel; ?>.</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $row):
            $items_list = order_items_text($row['items']);
                        $dt = $row['order_date'] ? date('M d, h:i A', strtotime($row['order_date'])) : '';
                        $st = strtolower($row['status'] ?? 'pending');
                        $ot = strtolower($row['order_type'] ?? 'online');
                      ?>
                      <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><span class="queue">#<?php echo (int)$row['queue_number']; ?></span></td>
                        <td><b><?php echo htmlspecialchars($row['customer_name']); ?></b></td>
                        <td title="<?php echo htmlspecialchars($row['address']); ?>"><?php echo htmlspecialchars(mb_substr($row['address'],0,24)); ?></td>
                        <td class="items" title="<?php echo htmlspecialchars($row['items']); ?>"><?php echo htmlspecialchars($items_list); ?></td>
                        <td class="total">₱<?php echo number_format((float)$row['total_amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                        <td><span class="badge badge-<?php echo htmlspecialchars(str_replace(' ', '-', $st)); ?>"><?php echo ucfirst($st); ?></span></td>
                        <td style="white-space:nowrap;"><?php echo $dt; ?></td>
                        <td><button class="btn sm" onclick="printOrder(<?php echo (int)$row['id']; ?>)">Print</button>
                        <?php
                        $next = null; $nextLabel = '';
                        $showMarkPaid = false;
                        // Delivery workflow: pending -> preparing -> on the way -> paid
                        // Pickup/Walk-in workflow: pending -> paid (or pending -> preparing -> paid)
                        if ($st === 'pending') {
                            if ($ot === 'delivery') {
                                $next = 'preparing'; $nextLabel = '🍳 Preparing';
                            } else {
                                // Pickup/Walk-in can go directly to paid
                                $showMarkPaid = true;
                            }
                        }
                        elseif ($st === 'preparing') {
                            if ($ot === 'delivery') {
                                $next = 'on the way'; $nextLabel = '🛵 On the Way';
                            } else {
                                // Pickup/Walk-in: preparing -> paid
                                $showMarkPaid = true;
                            }
                        }
                        elseif ($st === 'on the way' || $st === 'on_the_way') {
                            // Only delivery reaches here
                            $showMarkPaid = true;
                        }
                        ?>
                        <?php if ($next): ?>
                          <form method="post" style="display:inline;margin-left:4px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="set_status">
                            <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="new_status" value="<?php echo htmlspecialchars($next); ?>">
                            <button class="btn sm" type="submit" style="background:#2a2a2a;color:#fff;"><?php echo $nextLabel; ?></button>
                          </form>
                        <?php endif; ?>
                        <?php if ($showMarkPaid): ?>
                          <form method="post" style="display:inline;margin-left:4px;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                            <input type="hidden" name="action" value="set_status">
                            <input type="hidden" name="order_id" value="<?php echo (int)$row['id']; ?>">
                            <input type="hidden" name="new_status" value="paid">
                            <button class="btn sm" type="submit" style="background:#14532d;color:#86efac;">✓ Mark Paid</button>
                          </form>
                        <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="note">Showing <?php echo count($rows) ? ($offset+1) : 0; ?>–<?php echo $offset + count($rows); ?> of <?php echo $totalRows; ?> orders (<?php echo $rangeLabel; ?>). Page <?php echo $page; ?> of <?php echo $totalPages; ?>.</p>

    <?php if ($totalPages > 1): ?>
    <div class="pager">
      <?php
        $qp = [];
        if ($period) $qp['period']=$period;
        if ($period==='custom') { $qp['start']=$start; $qp['end']=$end; }
        if ($singleDate && !$period) $qp['date']=$singleDate;
        $base = 'orders.php?'.http_build_query($qp).'&page=';
      ?>
      <a class="<?php echo $page<=1?'disabled':''; ?>" href="<?php echo $base.max(1,$page-1); ?>">◀ Prev</a>
      <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <a class="<?php echo $i===$page?'active':''; ?>" href="<?php echo $base.$i; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      <a class="<?php echo $page>=$totalPages?'disabled':''; ?>" href="<?php echo $base.min($totalPages,$page+1); ?>">Next ▶</a>
    </div>
    <?php endif; ?>
  </div>
</div>
<script>
  (function(){
    const d = new Date();
    const today = d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
    document.querySelectorAll('input[type="date"]').forEach(inp => { if(!inp.max) inp.max = today; });
  })();
  function printOrder(id) { window.open('print_order.php?id=' + id, '_blank'); }
</script>
</body>
</html>
