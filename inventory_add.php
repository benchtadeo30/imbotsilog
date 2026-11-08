<?php include 'auth.php'; require_admin(); include 'silog.php'; $msg=''; $err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  if (!verify_csrf()) $err='Invalid request.';
  else {
    $name=trim($_POST['item_name']??''); $cat=trim($_POST['category']??''); $qty=(int)($_POST['quantity']??0); $unit=trim($_POST['unit']??''); $min=(int)($_POST['minimum_stock']??0); $cost=(float)($_POST['cost_per_unit']??0);
    if ($name===''||$unit==='') $err='Item name and unit required.';
    if ($qty<0||$min<0||$cost<0) $err='Values cannot be negative.';
    if (!$err) {
      $stmt=$conn->prepare('INSERT INTO inventory (item_name,category,current_stock,unit,reorder_level,cost_per_unit,updated_at) VALUES (?,?,?,?,?,?,NOW())');
      $stmt->bind_param('ssiisd',$name,$cat,$qty,$unit,$min,$cost);
      if ($stmt->execute()) { header('Location: admin_inventory.php'); exit; } else $err='Could not add item.';
    }
  }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Add Inventory Item - Imbo't Silog</title><link rel="stylesheet" href="home.css">
<style>.dash-wrap{max-width:700px;margin:0 auto;padding:30px}.field{width:100%;box-sizing:border-box;padding:10px;border-radius:10px;border:1px solid #444;background:#0f0f0f;color:#fff;font-family:'Inter',Arial,sans-serif;margin:6px 0 12px}.btn{background:#ff7a00;color:#000;border:none;font-weight:bold;padding:12px 16px;border-radius:10px;cursor:pointer;font-family:'Inter',Arial,sans-serif}.err{color:#f87171}h1{color:#ff7a00}label{color:#fff}</style></head>
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
<h1>Add Inventory Item</h1>
<?php if($err) echo '<div class="err">'.$err.'</div>'; ?>
<form method="post">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
<input class="field" name="item_name" placeholder="Item Name" required>
<input class="field" name="category" placeholder="Category">
<input class="field" name="quantity" type="number" placeholder="Quantity" required>
<input class="field" name="unit" placeholder="Unit" required>
<input class="field" name="minimum_stock" type="number" placeholder="Minimum Stock">
<input class="field" name="cost_per_unit" type="number" step="0.01" placeholder="Cost per Unit">
<button class="btn" type="submit">Save</button>
<a class="btn" style="background:#333;color:#fff;text-decoration:none;display:inline-block;margin-left:8px;" href="admin_inventory.php">Cancel</a>
</form>
</div></body></html>
