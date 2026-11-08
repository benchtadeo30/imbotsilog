<?php
include 'auth.php'; require_admin(); include 'silog.php';
$id=(int)($_GET['id']??0);
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf()) { echo 'Invalid request.'; exit; }
  $stmt=$conn->prepare('UPDATE inventory SET archived=1 WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute();
  header('Location: admin_inventory.php'); exit;
}
$stmt=$conn->prepare('SELECT item_name FROM inventory WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $item=$stmt->get_result()->fetch_assoc();
if (!$item) { echo 'Item not found.'; exit; }
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Archive Item</title><link rel="stylesheet" href="home.css"><style>.dash-wrap{max-width:500px;margin:0 auto;padding:30px}h1{color:#ff7a00}p{color:#fff}.btn{background:#ff7a00;color:#000;border:none;font-weight:bold;padding:10px 16px;border-radius:10px;cursor:pointer}.btn.sec{background:#333;color:#fff}</style></head>
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
<div class="navbar"><img src="logo.jpg" class="logo" alt="Imbo't Silog"><div class="nav-links"><a href="admin_inventory.php">📦 Back to Inventory</a><a href="logout.php">🚪 Logout</a></div><button class="menu-btn" onclick="toggleSidebar()">☰</button></div>
<div class="dash-wrap">
<h1>Archive Item</h1>
<p>Archive "<strong><?php echo htmlspecialchars($item['item_name']); ?></strong>"?</p>
<form method="post">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
<button class="btn" type="submit">Confirm Archive</button>
<a class="btn sec" href="admin_inventory.php" style="text-decoration:none;display:inline-block;margin-left:8px;">Cancel</a>
</form>
</div></body></html>
