<?php
include 'auth.php';
require_admin();
include 'silog.php';

$messages=[]; $errors=[];
$editMenuId = isset($_GET['edit_menu']) ? (int)$_GET['edit_menu'] : 0;

if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!verify_csrf()) { $errors[]='Invalid request.'; }
    else {
        $action = $_POST['action'] ?? '';

        if ($action==='toggle_menu') {
            $tid=(int)($_POST['menu_id'] ?? 0);
            $conn->query("UPDATE menu_items SET is_available=IF(is_available=1,0,1) WHERE id={$tid}");
            $messages[]='Availability updated.';
        } elseif ($action==='delete_menu') {
            $did=(int)($_POST['menu_id'] ?? 0);
            $res=$conn->query("SELECT image_path FROM menu_items WHERE id={$did}");
            if ($row=$res->fetch_assoc()) { @unlink($row['image_path']); }
            $conn->query("DELETE FROM menu_items WHERE id={$did}");
            $messages[]='Menu item deleted.';
        } elseif ($action==='add_menu') {
            $mname = trim($_POST['menu_name'] ?? '');
            $mprice = (float)($_POST['menu_price'] ?? 0);
            $mcat = trim($_POST['menu_category'] ?? 'Meals');
            $mstock = filter_var($_POST['menu_stock'] ?? null, FILTER_VALIDATE_INT);
            $mavail = isset($_POST['menu_available']) ? 1 : 0;
            if ($mname==='' || $mprice<=0) $errors[]='Valid menu name and price required.';
            if ($mstock===false || $mstock===null || $mstock<0) $errors[]='Stock must be a whole number of 0 or more.';
            if (empty($_FILES['menu_image']['name'])) $errors[]='Menu image required.';
            if (!$errors) {
                $file = $_FILES['menu_image'];
                $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                if (!in_array($file['type'],$allowed)) $errors[]='Invalid image type.';
                elseif ($file['size']>5*1024*1024) $errors[]='Image too large.';
                else {
                    $ext = pathinfo($file['name'],PATHINFO_EXTENSION);
                    if (!is_dir('uploads/menu')) { @mkdir('uploads/menu',0777,true); }
                    $dest = 'uploads/menu/'.uniqid('menu_',true).'.'.$ext;
                    if (move_uploaded_file($file['tmp_name'],$dest)) {
                        $st=$conn->prepare('INSERT INTO menu_items (name,category,price,image_path,is_available,stock) VALUES (?,?,?,?,?,?)');
                        $st->bind_param('ssdsii',$mname,$mcat,$mprice,$dest,$mavail,$mstock);
                        if ($st->execute()) $messages[]='Menu item added.';
                        else { $errors[]='Failed to add menu item.'; @unlink($dest); }
                    } else $errors[]='Upload failed.';
                }
            }
        } elseif ($action==='edit_menu') {
            $mid=(int)($_POST['menu_id'] ?? 0);
            $mname = trim($_POST['menu_name'] ?? '');
            $mprice = (float)($_POST['menu_price'] ?? 0);
            $mcat = trim($_POST['menu_category'] ?? 'Meals');
            $mstock = filter_var($_POST['menu_stock'] ?? null, FILTER_VALIDATE_INT);
            $mavail = isset($_POST['menu_available']) ? 1 : 0;
            if ($mname==='' || $mprice<=0) $errors[]='Valid menu name and price required.';
            if ($mstock===false || $mstock===null || $mstock<0) $errors[]='Stock must be a whole number of 0 or more.';
            if (!$errors) {
                if (!empty($_FILES['menu_image']['name'])) {
                    $file = $_FILES['menu_image'];
                    $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
                    if (in_array($file['type'],$allowed) && $file['size']<=5*1024*1024) {
                        $ext = pathinfo($file['name'],PATHINFO_EXTENSION);
                        $dest = 'uploads/menu/'.uniqid('menu_',true).'.'.$ext;
                        if (move_uploaded_file($file['tmp_name'],$dest)) {
                            $old=$conn->query("SELECT image_path FROM menu_items WHERE id={$mid}")->fetch_assoc();
                            if ($old) @unlink($old['image_path']);
                            $st=$conn->prepare('UPDATE menu_items SET name=?,category=?,price=?,image_path=?,is_available=?,stock=? WHERE id=?');
                            $st->bind_param('ssdsiii',$mname,$mcat,$mprice,$dest,$mavail,$mstock,$mid);
                            if ($st->execute()) $messages[]='Menu item updated.';
                            else $errors[]='Update failed.';
                        } else $errors[]='Upload failed.';
                    } else $errors[]='Invalid image.';
                } else {
                    $st=$conn->prepare('UPDATE menu_items SET name=?,category=?,price=?,is_available=?,stock=? WHERE id=?');
                    $st->bind_param('ssdiii',$mname,$mcat,$mprice,$mavail,$mstock,$mid);
                    if ($st->execute()) $messages[]='Menu item updated.';
                    else $errors[]='Update failed.';
                }
                $editMenuId=0;
            }
        }
    }
}

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$allMenu = [];
$mr = $conn->query('SELECT * FROM menu_items ORDER BY id DESC');
while ($mr && ($m=$mr->fetch_assoc())) $allMenu[] = $m;

$rows = [];
foreach ($allMenu as $m) {
    $avail = (int)$m['is_available'] === 1;
    if ($search !== '' && stripos($m['name'],$search)===false) continue;
    if ($statusFilter==='available' && !$avail) continue;
    if ($statusFilter==='unavailable' && $avail) continue;
    $rows[] = $m;
}

// Pagination
$perPage = 8;
$totalFiltered = count($rows);
$totalPages = max(1, (int)ceil($totalFiltered / $perPage));
$page = max(1, (int)($_GET['page'] ?? 1));
if ($page > $totalPages) $page = $totalPages;
$offset = ($page - 1) * $perPage;
$pagedRows = array_slice($rows, $offset, $perPage);

$total = count($allMenu);
$availCount = count(array_filter($allMenu, fn($m)=>(int)$m['is_available']===1));
$unavailCount = $total - $availCount;

$editItem = null;
if ($editMenuId > 0) {
    foreach ($allMenu as $m) { if ((int)$m['id']===$editMenuId) { $editItem=$m; break; } }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inventory & Menu Availability - Imbo't Silog</title>
<link rel="stylesheet" href="home.css?v=5">
<script src="sidebar.js?v=3" defer></script>
<style>
  :root { --orange:#ff7a00; --bg:#0a0a0a; --card:#141414; --border:#2a2a2a; }
  body { background:var(--bg); }
  .dash-wrap { max-width: 100%; margin: 0; padding: 20px 24px 40px; }
  .dash-header { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin:8px 0 18px; }
  .dash-title { color:#fff; font-size:26px; font-weight:700; margin:0; }
  .dash-sub { color:#9a9a9a; font-size:13px; margin:4px 0 0; }
  .kpi-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(170px,1fr)); gap:14px; margin-bottom:18px; }
  .kpi { background:var(--card); border:1px solid var(--border); border-left:3px solid var(--orange); border-radius:14px; padding:16px 18px; }
  .kpi .label { color:#9a9a9a; font-size:12px; text-transform:uppercase; letter-spacing:.5px; }
  .kpi .val { color:#fff; font-size:28px; font-weight:700; margin-top:6px; }
  .kpi.avail .val { color:#4ade80; }
  .kpi.unavail .val { color:#f87171; }
  .panel { background:var(--card); border:1px solid var(--border); border-radius:16px; padding:16px; margin-bottom:16px; }
  .panel-title { color:var(--orange); font-size:16px; font-weight:700; margin:0 0 12px; }
  .filters { display:flex; gap:10px; flex-wrap:wrap; align-items:center; }
  .field { box-sizing:border-box; padding:10px 12px; border-radius:10px; border:1px solid #3a3a3a; background:#0f0f0f; color:#fff; font-family:'Inter',Arial,sans-serif; font-size:14px; }
  .btn { background:var(--orange); color:#000; border:none; font-weight:700; padding:10px 16px; border-radius:10px; cursor:pointer; font-family:'Inter',Arial,sans-serif; font-size:13px; text-decoration:none; display:inline-block; }
  .btn.sec { background:#2a2a2a; color:#fff; }
  .btn.danger { background:#7f1d1d; color:#fca5a5; }
  .btn.sm { padding:6px 10px; font-size:12px; border-radius:8px; }
  .table-wrap { overflow-x:auto; }
  table.dash { width:100%; border-collapse:collapse; color:#fff; font-size:13px; }
  table.dash th, table.dash td { border-bottom:1px solid #2a2a2a; padding:12px 10px; text-align:left; vertical-align:middle; }
  table.dash th { color:var(--orange); font-weight:700; font-size:12px; text-transform:uppercase; letter-spacing:.4px; white-space:nowrap; border-bottom:2px solid #2a2a2a; }
  table.dash tr:hover td { background:#1a1a1a; }
  .thumb { width:46px; height:46px; object-fit:cover; border-radius:10px; border:1px solid #333; }
  .badge { padding:4px 12px; border-radius:999px; font-size:11px; font-weight:700; letter-spacing:.3px; }
  .badge.ok { background:#14532d; color:#86efac; }
  .badge.off { background:#7f1d1d; color:#fca5a5; }
  .msg { color:#4ade80; margin:6px 0; font-size:13px; }
  .err { color:#f87171; margin:6px 0; font-size:13px; }
  .empty { text-align:center; color:#777; padding:30px 10px; }
  .actions { display:flex; gap:6px; flex-wrap:wrap; }
  .form-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:10px; }
  .checkbox-row { display:flex; align-items:center; gap:8px; color:#fff; font-size:13px; }
  .note { color:#9a9a9a; font-size:12px; margin:6px 0 0; }
  .modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.72); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; z-index:1000; padding:20px; }
  .modal-card { background:linear-gradient(180deg,#1a1a1a,#121212); border:1px solid rgba(255,122,0,0.28); border-radius:20px; padding:24px 26px; width:100%; max-width:520px; max-height:90vh; overflow-y:auto; box-shadow:0 24px 80px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,122,0,0.08); animation:fadeIn .18s ease; }
  .modal-card .panel-title { font-size:18px; letter-spacing:.3px; }
  .modal-close { background:rgba(255,255,255,0.06); color:#fff; border:1px solid rgba(255,255,255,0.1); border-radius:10px; width:34px; height:34px; cursor:pointer; font-weight:700; font-size:16px; display:flex; align-items:center; justify-content:center; transition:background .15s,border-color .15s,color .15s; }
  .modal-close:hover { background:rgba(255,122,0,0.15); border-color:rgba(255,122,0,0.4); color:#ff7a00; }
  .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
  .form-grid .field { width:100%; }
  .form-grid .full { grid-column:1 / -1; }
  .checkbox-row { display:flex; align-items:center; gap:8px; color:#fff; font-size:13px; padding:6px 0; }
  .modal-footer { display:flex; gap:10px; margin-top:16px; justify-content:flex-end; }
  @keyframes fadeIn { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
</style>
<script>
function openMenuModal(){ const m=document.getElementById('menuModal'); if(m) m.style.display='flex'; }
function closeMenuModal(){ const m=document.getElementById('menuModal'); if(m) m.style.display='none'; }
</script>
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
        <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">🧾 Orders</a>
        <a href="admin_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
        <a href="admin_inventory.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_inventory.php' ? 'active' : '' ?>">📦 Inventory</a>
        <a href="admin_expenses.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_expenses.php' ? 'active' : '' ?>">💸 Expenses</a>
        <a href="logout.php">🚪 Logout</a>
    </div>
    <button type="button" class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">☰</button>
</div>

<div class="dash-wrap">
  <div class="dash-header">
    <div>
      <h1 class="dash-title">Inventory & Menu Availability</h1>
      <p class="dash-sub">Toggle items on/off. Unavailable items cannot be ordered by customers.</p>
    </div>
  </div>

  <?php foreach($messages as $m) echo '<div class="msg">✅ '.htmlspecialchars($m).'</div>'; ?>
  <?php foreach($errors as $e) echo '<div class="err">⚠️ '.htmlspecialchars($e).'</div>'; ?>

  <div class="kpi-grid">
    <div class="kpi"><div class="label">Total Items</div><div class="val"><?php echo $total; ?></div></div>
    <div class="kpi avail"><div class="label">Available</div><div class="val"><?php echo $availCount; ?></div></div>
    <div class="kpi unavail"><div class="label">Unavailable</div><div class="val"><?php echo $unavailCount; ?></div></div>
  </div>

  <div class="panel">
    <form method="get" class="filters">
      <input class="field" style="min-width:220px;flex:1;" name="q" placeholder="Search menu item..." value="<?php echo htmlspecialchars($search); ?>">
      <select class="field" name="status">
        <option value="all" <?php if($statusFilter==='all')echo'selected'; ?>>All</option>
        <option value="available" <?php if($statusFilter==='available')echo'selected'; ?>>Available</option>
        <option value="unavailable" <?php if($statusFilter==='unavailable')echo'selected'; ?>>Unavailable</option>
      </select>
      <button class="btn sec" type="submit">Filter</button>
      <a class="btn sec" href="admin_inventory.php" style="text-decoration:none;">Reset</a>
    </form>
  </div>

  <div style="margin-bottom:16px;">
    <button class="btn" type="button" onclick="openMenuModal()">+ Add Menu Item</button>
  </div>

  <!-- MODAL -->
  <div id="menuModal" class="modal-overlay" style="display:none;">
    <div class="modal-card">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;padding-bottom:12px;border-bottom:1px solid rgba(255,255,255,0.08);">
        <h3 class="panel-title" style="margin:0;"><?php echo $editItem ? '✏️ Edit Menu Item' : '➕ Add Menu Item'; ?></h3>
        <button type="button" class="modal-close" onclick="closeMenuModal()">✕</button>
      </div>
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
        <input type="hidden" name="action" value="<?php echo $editItem ? 'edit_menu' : 'add_menu'; ?>">
        <?php if ($editItem): ?><input type="hidden" name="menu_id" value="<?php echo (int)$editItem['id']; ?>"><?php endif; ?>
        <div class="form-grid">
          <input class="field" name="menu_name" placeholder="Food Name" value="<?php echo $editItem?htmlspecialchars($editItem['name']):''; ?>" required>
          <input class="field" name="menu_price" type="number" step="0.01" placeholder="Price (₱)" value="<?php echo $editItem?htmlspecialchars($editItem['price']):''; ?>" required>
          <input class="field" name="menu_stock" type="number" min="0" step="1" placeholder="Stock quantity" value="<?php echo $editItem ? (int)$editItem['stock'] : '0'; ?>" required>
          <select class="field" name="menu_category" required>
            <?php
            $cats = ['Best Sellers','Family Meals','Meals','Sunny','Rainy','Drinks','Add Ons'];
            $curCat = $editItem ? ($editItem['category'] ?? 'Meals') : 'Meals';
            foreach ($cats as $c) echo '<option value="'.htmlspecialchars($c).'" '.($curCat===$c?'selected':'').'>'.htmlspecialchars($c).'</option>';
            ?>
          </select>
          <label class="btn sec full" style="display:inline-block;cursor:pointer;text-align:center;">Choose Image
            <input name="menu_image" type="file" accept="image/*" style="display:none;" onchange="document.getElementById('menu_file_name').textContent = this.files.length ? this.files[0].name : 'No file selected';">
          </label>
          <span id="menu_file_name" class="full" style="color:#9a9a9a;font-size:12px;">No file selected</span>
          <label class="checkbox-row full"><input type="checkbox" name="menu_available" <?php echo (!$editItem || (int)$editItem['is_available']===1)?'checked':''; ?>> Available</label>
        </div>
        <div class="modal-footer">
          <button class="btn" type="submit"><?php echo $editItem ? 'Update' : 'Add Menu Item'; ?></button>
          <?php if ($editItem): ?><a class="btn sec" href="admin_inventory.php" style="text-decoration:none;">Cancel</a><?php endif; ?>
        </div>
        <?php if ($editItem && $editItem['image_path']): ?>
          <div style="margin-top:12px;"><img src="<?php echo htmlspecialchars($editItem['image_path']); ?>" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #333;"></div>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <?php if ($editItem): ?>
  <script>window.addEventListener('DOMContentLoaded', openMenuModal);</script>
  <?php endif; ?>

  <div class="panel">
    <h3 class="panel-title">🍽️ Menu Items</h3>
    <div class="table-wrap">
      <table class="dash">
        <thead><tr>
          <th>Image</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th>
        </tr></thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="7" class="empty">No menu items found.</td></tr>
          <?php endif; ?>
          <?php foreach($pagedRows as $m):
            $avail = (int)$m['is_available']===1;
          ?>
          <tr>
            <td><?php if($m['image_path']): ?><img class="thumb" src="<?php echo htmlspecialchars($m['image_path']); ?>" alt=""><?php else: ?>—<?php endif; ?></td>
            <td><b><?php echo htmlspecialchars($m['name']); ?></b></td>
            <td><?php echo htmlspecialchars($m['category'] ?? '—'); ?></td>
            <td>₱<?php echo number_format((float)$m['price'],2); ?></td>
            <td><?php echo (int)$m['stock']; ?></td>
            <td><span class="badge <?php echo $avail?'ok':'off'; ?>"><?php echo $avail?'Available':'Unavailable'; ?></span></td>
            <td>
              <div class="actions">
                <form method="post" style="display:inline;">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="toggle_menu">
                  <input type="hidden" name="menu_id" value="<?php echo (int)$m['id']; ?>">
                  <button class="btn sm <?php echo $avail?'danger':'sec'; ?>" type="submit"><?php echo $avail?'Make Unavailable':'Make Available'; ?></button>
                </form>
                <a class="btn sm sec" href="admin_inventory.php?edit_menu=<?php echo (int)$m['id']; ?>" style="text-decoration:none;">Edit</a>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this menu item?');">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                  <input type="hidden" name="action" value="delete_menu">
                  <input type="hidden" name="menu_id" value="<?php echo (int)$m['id']; ?>">
                  <button class="btn sm danger" type="submit">Delete</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="note">Showing <?php echo count($pagedRows) ? ($offset+1) : 0; ?>–<?php echo $offset + count($pagedRows); ?> of <?php echo $totalFiltered; ?> items<?php if ($totalFiltered !== $total) echo ' (filtered)'; ?>. Page <?php echo $page; ?> of <?php echo $totalPages; ?>.</p>

    <?php if ($totalPages > 1): ?>
    <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center;margin-top:10px;">
      <?php
        $qs = http_build_query(['q'=>$search,'status'=>$statusFilter]);
        $base = 'admin_inventory.php?'.$qs.'&page=';
      ?>
      <a class="btn sec sm" href="<?php echo $base.max(1,$page-1); ?>" <?php if($page<=1) echo 'style="pointer-events:none;opacity:.4;"'; ?>>◀ Prev</a>
      <?php for ($i=1; $i<=$totalPages; $i++): ?>
        <a class="btn <?php echo $i===$page?'':'sec'; ?> sm" href="<?php echo $base.$i; ?>"><?php echo $i; ?></a>
      <?php endfor; ?>
      <a class="btn sec sm" href="<?php echo $base.min($totalPages,$page+1); ?>" <?php if($page>=$totalPages) echo 'style="pointer-events:none;opacity:.4;"'; ?>>Next ▶</a>
    </div>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
