<?php include 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>More - Imbo't Silog</title>
<link rel="stylesheet" href="more.css">
</head>

<body>

    <!-- SIDEBAR BACKDROP -->
<div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

<!-- SIDE NAV BUTTON -->
<div class="menu-btn" onclick="toggleSidebar()">
☰
</div>

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
        <?php if (is_logged_in() && user_role() === 'admin'): ?>
            <a href="orders.php" class="admin-link">🧾 Orders</a>
            <a href="admin_dashboard.php" class="admin-link">📊 Dashboard</a>
            <a href="admin_inventory.php" class="admin-link">📦 Inventory</a>
            <a href="admin_expenses.php" class="admin-link">💸 Expenses</a>
        <?php endif; ?>
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
        <?php if (is_logged_in()): ?>
            <?php if (user_role() === 'admin'): ?>
                <a href="orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'orders.php' ? 'active' : '' ?>">🧾 Orders</a>
                <a href="admin_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_dashboard.php' ? 'active' : '' ?>">📊 Dashboard</a>
                <a href="admin_inventory.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_inventory.php' ? 'active' : '' ?>">📦 Inventory</a>
                <a href="admin_expenses.php" class="<?= basename($_SERVER['PHP_SELF']) === 'admin_expenses.php' ? 'active' : '' ?>">💸 Expenses</a>
            <?php endif; ?>
            <a href="my_orders.php" class="<?= basename($_SERVER['PHP_SELF']) === 'my_orders.php' ? 'active' : '' ?>">📋 My Orders</a>
            <a href="logout.php">🚪 <?php echo htmlspecialchars(user_name() ?: 'Account'); ?></a>
        <?php else: ?>
            <a href="login.php" class="<?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : '' ?>">🔑 Login</a>
            <a href="register.php" class="<?= basename($_SERVER['PHP_SELF']) === 'register.php' ? 'active' : '' ?>">📝 Register</a>
        <?php endif; ?>
    </div>
    <button class="menu-btn" onclick="toggleSidebar()">☰</button>
</div>
</div>

<!-- CONTENT -->
<div class="container">

    <!-- LOCATION CARD -->
    <div class="card">
       <h2 class="section-title" id="Address">Address</h2>
        <p>Midland Street, Strella, Brgy San Juan,<br>Cainta, Rizal</p>

        <h3>Landmark</h3>
        <p>Tapat ng school ng San Juan</p>

        <h3>Open & Closing</h3>
        <p>10:00 AM to 12:00 PM</p>
    </div>

    <!-- CONTACT CARD -->
    <div class="card">
        <h2>📞 Contact Us</h2>

        <p>📱 0912-345-6789</p>
        <p>📧 imbotsilog@gmail.com</p>

        <div class="buttons">
            <a href="https://www.facebook.com/" class="btn">Facebook</a>
            <a href="https://www.instagram.com/?hl=en" class="btn">Instagram</a>
        </div>
    </div>

    <!-- EXTRA CARD -->
    <div class="card">
        <h2>🔥 Why Choose Us?</h2>
        <p>
        ✔ Fresh lutong bahay meals<br>
        ✔ Affordable presyo<br>
        ✔ Malinis at maayos na serbisyo<br>
        ✔ Perfect for barkada at pamilya
        </p>

       <a href="menu.php" class="order-btn">Order Now</a>
    </div>

</div>

<script>

function toggleSidebar() {

    const sidebar = document.getElementById("sidebar");

    if (sidebar.style.left === "0px") {
        sidebar.style.left = "-260px";
    } else {
        sidebar.style.left = "0px";
    }
}

</script>

<?php include 'chatbot_widget.php'; ?>
</body>
</html>
