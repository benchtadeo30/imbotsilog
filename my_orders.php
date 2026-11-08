<?php
include 'auth.php'; require_login(); include 'silog.php'; include 'order_items.php';

$user = user_name() ?: 'Walk-in';
// customers see only their own active orders; admins see all
$isAdmin = (user_role() === 'admin');

// optional date filter
$date = $_GET['date'] ?? '';
$where = "WHERE (LOWER(customer_name) = '" . $conn->real_escape_string(strtolower($user)) . "'" . ($isAdmin ? " OR 1=1" : "") . ")";
if ($date) {
  $d = DateTime::createFromFormat('Y-m-d', $date);
  if ($d && $d->format('Y-m-d') === $date) {
    $where .= " AND DATE(order_date) = '" . $conn->real_escape_string($date) . "'";
  }
}

// show only active (non-paid, non-cancelled) orders on customer side
if (!$isAdmin) {
  $where .= " AND status NOT IN ('paid','cancelled')";
}

$res = $conn->query("SELECT * FROM orders $where ORDER BY order_date DESC, id DESC");
$rows = [];
if ($res) while ($r = $res->fetch_assoc()) $rows[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders - Imbo't Silog</title>
<link rel="stylesheet" href="home.css">
<style>
  :root { --orange:#ff7a00; --bg:#0a0a0a; --card:#141414; --border:#2a2a2a; }
  body { background:var(--bg); }
  .dash-wrap { max-width: 90%; margin: 0 auto; padding: 20px 24px 48px; }
  .dash-title { color:#fff; font-size:28px; font-weight:800; margin:0; letter-spacing:-0.5px; }
  .dash-sub { color:#9a9a9a; font-size:13px; margin:4px 0 18px; }
  .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:14px; margin-bottom:18px; }
  .kpi { background:linear-gradient(135deg,#1a1a1a,#121212); border:1px solid var(--border); border-radius:16px; padding:18px 20px; }
  .kpi::before { content:''; position:absolute; top:0; left:0; width:3px; height:100%; background:var(--orange); }
  .kpi .label { color:#9a9a9a; font-size:11px; text-transform:uppercase; letter-spacing:.7px; font-weight:600; }
  .kpi .val { color:#fff; font-size:22px; font-weight:800; margin-top:6px; }
  .panel { background:linear-gradient(180deg,#161616,#111); border:1px solid var(--border); border-radius:18px; padding:20px 22px; margin-bottom:18px; box-shadow:0 4px 20px rgba(0,0,0,0.3); }
  .table-wrap { overflow-x:auto; }
  table.dash { width:100%; border-collapse:collapse; color:#fff; font-size:13px; }
  table.dash th, table.dash td { border-bottom:1px solid #1f1f1f; padding:11px 12px; text-align:left; vertical-align:middle; }
  table.dash th { color:var(--orange); font-weight:700; font-size:11px; text-transform:uppercase; letter-spacing:.5px; border-bottom:2px solid #2a2a2a; white-space:nowrap; }
  table.dash tr:hover td { background:rgba(255,122,0,0.03); }
  table.dash tr:last-child td { border-bottom:none; }
  .badge { padding:4px 10px; border-radius:999px; font-size:11px; font-weight:700; color:#fff; background:#555; display:inline-block; }
  .badge-paid, .badge-completed { background:#14532d; color:#86efac; }
  .badge-pending { background:#78350f; color:#fcd34d; }
  .badge-preparing { background:#5b21b6; color:#ddd6fe; }
  .badge-on-the-way, .badge-on { background:#1e40af; color:#93c5fd; }
  .badge-cancelled { background:#7f1d1d; color:#fca5a5; }
  .items { color:#9a9a9a; font-size:12px; max-width:260px; }
  .total { color:#4ade80; font-weight:700; }
  .queue { color:var(--orange); font-weight:700; }
  .btn { background:var(--orange); color:#000; border:none; font-weight:700; padding:8px 14px; border-radius:10px; cursor:pointer; font-family:'Inter',Arial,sans-serif; font-size:13px; text-decoration:none; display:inline-block; }
  .btn.sm { padding:5px 10px; font-size:12px; border-radius:8px; }
  .empty { text-align:center; color:#777; padding:40px 10px; font-size:14px; }
  .note { color:#9a9a9a; font-size:12px; margin:8px 0; }
  .filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; margin-bottom:14px; }
  .field { box-sizing:border-box; padding:10px 12px; border-radius:10px; border:1px solid #3a3a3a; background:#0f0f0f; color:#fff; font-family:'Inter',Arial,sans-serif; font-size:14px; }
</style>
</head>
<body>

    <!-- SIDE NAV BUTTON -->
<div class="menu-btn" onclick="toggleSidebar()">☰</div>

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
        <a href="my_orders.php">📋 My Orders</a>
        <?php if (user_role() === 'admin'): ?>
            <a href="orders.php" class="admin-link">🧾 Orders</a>
            <a href="admin_dashboard.php" class="admin-link">📊 Dashboard</a>
            <a href="admin_inventory.php" class="admin-link">📦 Inventory</a>
            <a href="admin_expenses.php" class="admin-link">💸 Expenses</a>
        <?php endif; ?>
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
        <a href="my_orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_orders.php' ? 'active' : '' ?>">📋 My Orders</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>
</div>

<div class="dash-wrap">
  <h1 class="dash-title">My Orders</h1>
  <p class="dash-sub">Your active orders (paid orders disappear here but remain in admin records).</p>

  <div class="panel">
    <form method="get" class="filters">
      <label style="color:#fff;font-weight:700;font-size:13px;">Filter by date:</label>
      <input class="field" type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" max="<?php echo date('Y-m-d'); ?>">
      <button class="btn sec" type="submit">Filter</button>
      <a class="btn sec" href="my_orders.php" style="text-decoration:none;">All</a>
    </form>

    <div class="table-wrap">
      <table class="dash">
        <thead><tr>
          <th>ID</th><th>Queue #</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Date/Time</th><th>Receipt</th>
        </tr></thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="8" class="empty">No active orders found. Place an order from the menu!</td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $row):
            $items_list = order_items_text($row['items']);
            $dt = $row['order_date'] ? date('M d, Y h:i A', strtotime($row['order_date'])) : '';
            $st = strtolower($row['status'] ?? 'pending');
            $slug = str_replace(' ', '-', $st);
          ?>
          <tr>
            <td><?php echo (int)$row['id']; ?></td>
            <td><span class="queue">#<?php echo (int)$row['queue_number']; ?></span></td>
            <td class="items"><?php echo htmlspecialchars($items_list); ?></td>
            <td class="total">₱<?php echo number_format((float)$row['total_amount'], 2); ?></td>
            <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
            <td><span class="badge badge-<?php echo $slug; ?>"><?php echo ucfirst($st); ?></span></td>
            <td style="white-space:nowrap;"><?php echo $dt; ?></td>
            <td><a class="btn sm" href="print_order.php?id=<?php echo (int)$row['id']; ?>" target="_blank" style="text-decoration:none;">🖨 Print</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="note">Cancelled orders are hidden. Paid orders are removed from here (kept in admin records). Active orders: pending, preparing, on the way.</p>
  </div>
</div>
</body>
</html>
