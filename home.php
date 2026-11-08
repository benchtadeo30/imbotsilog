<?php include 'auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Home - Imbo't Silog</title>
<link rel="stylesheet" href="home.css?v=6">
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
    <button type="button" class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">☰</button>
</div>

<!-- SLIDER -->
<div class="slider">
    <div class="slides" id="slides">
        <img src="Tapsilog.jpg">
        <img src="Bangsilog.jpg">
        <img src="Fried bangus.jpg">
        <img src="Embotsilog.jpg">
        <img src="Chicsilog.jpg">
    </div>

    <!-- ARROWS -->
    <button class="prev" onclick="prevSlide()">❮</button>
    <button class="next" onclick="nextSlide()">❯</button>

    <!-- DOTS -->
    <div class="dots">
        <span onclick="goToSlide(0)"></span>
        <span onclick="goToSlide(1)"></span>
        <span onclick="goToSlide(2)"></span>
        <span onclick="goToSlide(3)"></span>
        <span onclick="goToSlide(4)"></span>
    </div>
</div>

<!-- HOME CARDS -->
<div class="home-cards">

    <a href="menu.php#bestseller" class="card">
        <img src="bestseller.jpg">
        <p>Best Seller</p>
    </a>

  <div class="card" onclick="openHighlights()">
    <img src="achievements.png"> 
    <p>Highlights</p>
</div>

    <a href="menu.php#delivery" class="card">
        <img src="Delivery.jpg">
        <p>Delivery</p>
    </a>

    <a href="menu.php#family" class="card">
        <img src="familymeals.jpg">
        <p>Family Meals</p>
    </a>

    <a href="menu.php#order" class="card">
        <img src="ordernow.jpg">
        <p>Order Now</p>
    </a>

    <a href="more.php#Address" class="card">
        <img src="visitus.jpg">
        <p>Visit Us</p>
    </a>

</div>

<!-- HIGHLIGHTS MODAL -->
<div id="highlightsModal" class="modal">

    <div class="modal-content">
        <span class="close" onclick="closeHighlights()">&times;</span>

        <h2>🏆 Achievements</h2>

        <div class="achievements">

            <div class="achievement">
                <img src="Award1.jpg">
                <p>Best Silog Award 2023</p>
            </div>

            <div class="achievement">
                <img src="Award2.jpg">
                <p>Top Local Food Store</p>
            </div>

            <div class="achievement">
                <img src="Award3.jpg">
                <p>Customer Choice Award</p>
            </div>

        </div>

    </div>
</div>

<!-- FOOTER -->
<div class="footer">
    <div class="marquee">
        Welcome to Imbo’t Silog — bawat sandok umausok kain na!
    </div>
</div>


<script src="script.js?v=2"></script>
<?php include 'chatbot_widget.php'; ?>
</body>
</html>
