<?php include 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>About - Imbo't Silog</title>
<link rel="stylesheet" href="about.css">
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

<!-- TOP NAVBAR -->
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

<!-- ABOUT CONTENT -->
<div class="container">

    <!-- ABOUT -->
    <div class="card">
        <h2>ABOUT IMBO'T SILOG</h2>
        <p>
        A special dish that brings joy to every table, Imbo’t Silog! Here, you get more than just a meal,
        it’s an experience filled with warmth and flavor. Every serving we prepare is fresh and hot off
        the stove, so when it reaches you, wisps of steam still rise up to delight your senses.

        It’s made with delicious garlic fried rice, eggs cooked just the way you like them, and the star
        of the show, perfectly prepared meatloaf (imbotido) that’s tender and full of taste.

        Every ingredient is carefully chosen and crafted with care, so with each bite, you’ll taste the
        true essence of Filipino cooking that will energize your day.
        </p>
    </div>

    <!-- MISSION -->
    <div class="card">
        <h2>MISSION</h2>
        <p>
        We are committed to crafting delicious, high quality silog meals with care and passion.
        Each spoonful is made with fresh local ingredients, cooked to perfection so it steams and
        smokes with every serving, bringing joy, energy, and genuine Filipino flavor to every customer.
        </p>
    </div>

    <!-- VISION -->
    <div class="card">
        <h2>VISION</h2>
        <p>
        To be the leading destination for authentic, sizzling silog meals in Central Luzon,
        where every serving brings warmth to families and communities, and the smoky aroma
        of our dishes becomes a beloved part of daily life.
        </p>
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
