<?php include 'auth.php'; ?>
<?php include 'silog.php'; $unavailNames=[]; $uq=@$conn->query("SELECT name FROM menu_items WHERE is_available=0"); if($uq){ while($ur=$uq->fetch_assoc()) $unavailNames[]=strtolower(trim($ur['name'])); } 
$itemsByCat=[]; $iq=@$conn->query("SELECT * FROM menu_items ORDER BY category ASC, id ASC"); if($iq){ while($it=$iq->fetch_assoc()){ $itemsByCat[$it['category']][] = $it; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu - Imbo't Silog</title>
<link rel="stylesheet" href="menu.css?v=5">
<script>window.UNAVAILABLE_ITEMS = <?php echo json_encode($unavailNames); ?> || [];</script>
</head>
<body>

    <!-- SIDEBAR BACKDROP -->
<div class="sidebar-backdrop" onclick="toggleSidebar()"></div>

    <!-- SIDE NAV BUTTON -->
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
        <div class="cart-container">
            <div class="cart-icon" id="cart-toggle" onclick="openCheckout()">🛒 <span id="cart-count">0</span></div>
        </div>
    </div>
    <button type="button" class="menu-btn" onclick="toggleSidebar()" aria-label="Toggle navigation" aria-controls="sidebar" aria-expanded="false">☰</button>
</div>

<div class="menu-page">
<h1>Silog Meals Menu</h1>

<div class="menu-options">
    <button onclick="showSection('dinein')">🍽️ Order Now</button>
    <button id="delivery-btn" onclick="openDelivery()">🛵 Online Delivery</button>
    <button onclick="openReservation()">🪑 Table Reservation</button>
</div>

<div style="text-align:center;margin:10px 0 20px;">
    <label style="color:#9a9a9a;font-size:13px;font-weight:600;">Order Date:
        <input type="date" id="order-date" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>" style="margin-left:8px;padding:6px 10px;border-radius:8px;border:1px solid #3a3a3a;background:#0f0f0f;color:#fff;font-family:'Inter',Arial,sans-serif;font-size:13px;">
    </label>
</div>

<?php foreach ($itemsByCat as $cat => $its): ?>
<h2 class="section-title"><?php echo htmlspecialchars($cat); ?></h2>
<div class="menu-grid" data-category="<?php echo htmlspecialchars($cat); ?>">
    <?php foreach ($its as $it):
        $avail = (int)$it['is_available'] === 1;
    ?>
    <div class="menu-item" data-name="<?php echo htmlspecialchars($it['name']); ?>" data-price="<?php echo (int)$it['price']; ?>" data-category="<?php echo htmlspecialchars($it['category']); ?>" data-stock="<?php echo (int)$it['stock']; ?>" <?php if(!$avail) echo 'data-unavailable="1"'; ?> style="position:relative;">
        <img src="<?php echo htmlspecialchars($it['image_path']); ?>">
        <h3><?php echo htmlspecialchars($it['name']); ?></h3>
        <p>₱<?php echo (int)$it['price']; ?></p>
        <p class="stock-line" style="font-size:12px;color:#9a9a9a;margin:0 0 8px;">Stock: <?php echo (int)$it['stock']; ?></p>
        <div style="display:flex;gap:6px;align-items:center;">
            <input type="number" min="1" max="<?php echo (int)$it['stock']; ?>" value="1" class="qty-input" style="width:56px;padding:6px 8px;border-radius:8px;border:1px solid #3a3a3a;background:#0f0f0f;color:#fff;font-family:'Inter',Arial,sans-serif;font-size:13px;">
            <button class="add-btn" style="flex:1;">Add to Cart</button>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endforeach; ?>

</div>

<!-- DELIVERY -->
<div id="delivery" class="menu-section" style="display:none;">
    <h2>🛵 Online Delivery</h2>
    <button onclick="openDelivery()" class="order-btn">Start Delivery Order</button>
</div>

<!-- RESERVE -->
<div id="reserve" class="menu-section" style="display:none;">
    <h2>🪑 Table Reservation</h2>
    <p>Coming Soon...</p>
</div>

<!-- DELIVERY SYSTEM -->
<div id="delivery-menu" class="modal">
    <div class="modal-content">
        <h2>🛵 Select Your Order</h2>
        <div class="delivery-tabs">
            <span onclick="filterDelivery('all', this)" class="dtab active">ALL</span>
            <span onclick="filterDelivery('sunny', this)" class="dtab">SUNNY</span>
            <span onclick="filterDelivery('rainy', this)" class="dtab">RAINY</span>
        </div>
        <div id="delivery-items"></div>
        <h3 id="delivery-total">Total: ₱0</h3>
        <button onclick="nextToForm()">Next</button>
        <button onclick="closeAll()">Cancel</button>
    </div>
</div>

<div id="delivery-form" class="modal">
    <div class="modal-content">
        <h2>📍 Delivery Info</h2>
        <form onsubmit="event.preventDefault(); nextToSummary();">
            <input type="text" id="d-name" placeholder="Your Name" value="<?php echo htmlspecialchars(user_name()); ?>" required><br><br>
            <input type="text" id="d-address" placeholder="Address" required><br><br>
            <button type="submit">Next</button>
            <button type="button" onclick="closeAll()">Cancel</button>
        </form>
    </div>
</div>

<div id="delivery-summary" class="modal">
    <div class="modal-content">
        <hr>
        <h3>💰 Select Payment</h3>
        <div class="payment-buttons">
            <button onclick="pay(1000)">₱1000</button>
            <button onclick="pay(500)">₱500</button>
            <button onclick="pay(200)">₱200</button>
            <button onclick="pay(100)">₱100</button>
            <button onclick="pay(50)">₱50</button>
        </div>
        <div id="payment-result"></div>
        <select id="delivery-payment" style="width:100%;box-sizing:border-box;padding:10px;border-radius:10px;border:1px solid #444;background:#0f0f0f;color:#fff;font-family:'Inter',Arial,sans-serif;margin:6px 0;">
            <option value="cash">Cash</option>
            <option value="maya">Maya</option>
            <option value="gcash">GCash</option>
            <option value="card">Card</option>
        </select>
        <h2>🧾 Order Summary</h2>
        <div id="summary-content"></div>
        <button onclick="confirmDelivery()">Confirm</button>
        <button onclick="closeAll()">Cancel</button>
    </div>
</div>

<!-- CHECKOUT MODAL ( drawer ) -->
<div id="checkout-modal" class="modal">
    <div class="modal-content">
        <button class="cart-drawer-close" style="position:absolute;top:12px;right:12px;z-index:2;" onclick="closeCheckout()">✕</button>
        <!-- Method choice -->
        <div id="checkout-mode">
            <h2>🧾 Checkout</h2>
            <p style="color:#9a9a9a;font-size:13px;margin:6px 0 14px;">How would you like to get your order?</p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                <button onclick="startPickup()" class="confirm-btn" style="background:#2a2a2a;color:#fff;margin:0;">🧾 Pick Up</button>
                <button onclick="startDelivery()" class="confirm-btn" style="background:#2a2a2a;color:#fff;margin:0;">🛵 Delivery</button>
            </div>
        </div>
        <!-- Pick-up summary -->
        <div id="pickup-panel" style="display:none;">
            <h2>🧾 Pick Up Order</h2>
            <div id="checkout-items"></div>
            <h3 id="checkout-total"></h3>
            <input type="text" id="pickup-name" placeholder="Customer Name (optional)" value="<?php echo htmlspecialchars(user_name()); ?>" style="width:100%;box-sizing:border-box;padding:10px;border-radius:10px;border:1px solid #444;background:#0f0f0f;color:#fff;font-family:'Inter',Arial,sans-serif;margin:6px 0;">
            <h3 id="queue-number" style="color:#ff7a00;"></h3>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:8px;">
                <button onclick="printReceipt()" class="confirm-btn" style="background:#2a2a2a;color:#fff;margin:0;">🖨️ Print Receipt</button>
                <button onclick="confirmPickup()" class="confirm-btn" style="margin:0;">Confirm Order</button>
            </div>
            <button onclick="backToMode()" class="close-btn" style="margin-top:8px;">← Back</button>
        </div>
        <!-- Delivery (kept simple; uses existing delivery flow) -->
        <div id="delivery-panel" style="display:none;">
            <h2>🛵 Delivery</h2>
            <p style="color:#9a9a9a;font-size:13px;">You'll be taken to the delivery order screen.</p>
            <button onclick="openDelivery();closeCheckout();" class="confirm-btn" style="margin-top:10px;">Continue to Delivery</button>
            <button onclick="backToMode()" class="close-btn" style="margin-top:8px;">← Back</button>
        </div>
        <h3 id="change-display"></h3>
    </div>
</div>

<!-- TABLE RESERVATION -->
<div id="reserve-modal" class="modal">
    <div class="modal-content">
        <h2>🪑 Table Reservation</h2>
        <form onsubmit="event.preventDefault(); submitReservation();">
            <input type="text" id="r-name" placeholder="Your Name" required><br><br>
            <input type="number" id="r-people" placeholder="How many people" oninput="generateTables()" required><br><br>
            <input type="time" id="r-time" required><br><br>
            <label>Select Area:</label><br>
            <select id="r-area" onchange="generateTables()" required>
                <option value="">-- Choose Area --</option>
                <option value="window">Window</option>
                <option value="middle">Middle</option>
                <option value="end">End</option>
            </select>
            <br><br>
            <label>Select Table:</label><br>
            <div id="table-container"></div>
            <p id="table-note" style="font-size:12px; color:orange;"></p>
            <br><br>
            <button type="submit">Confirm</button>
            <button type="button" onclick="closeAll()">Cancel</button>
        </form>
    </div>
</div>

<div id="reserve-summary" class="modal">
    <div class="modal-content">
        <h2>📋 Reservation Summary</h2>
        <div id="reserve-content"></div>
        <button onclick="confirmReservation()">Confirm</button>
        <button onclick="closeAll()">Close</button>
    </div>
</div>

<!-- SUCCESS MESSAGE -->
<div id="success-msg" class="success-msg">✅ Order Confirmed!</div>

<!-- CART DRAWER -->
<div id="cart-overlay" class="cart-overlay" onclick="closeCartDrawer()"></div>
<div id="cart-drawer" class="cart-drawer">
    <div class="cart-drawer-header">
        <h3>🛒 Your Cart</h3>
        <button class="cart-drawer-close" onclick="closeCartDrawer()">✕</button>
    </div>
    <div id="cart-items" class="cart-drawer-items"></div>
    <div id="cart-total" class="cart-drawer-total"></div>
    <button onclick="openCheckout();closeCartDrawer();" class="cart-drawer-checkout">Checkout</button>
</div>

<script src="menu.js?v=4"></script>
<script>
/*
 * Safety net for a stale/missing menu.js.
 * These are assigned ONLY when the real implementation is not present, so they can
 * never shadow the validated functions in menu.js (which is what caused orders to
 * bypass the stock check). They also enforce the stock ceiling themselves.
 */
(function () {
    function msg(t) { if (typeof showModal === 'function') showModal(t); else alert(t); }
    function stockOk(map) {
        for (var n in map) {
            var it = map[n];
            if (!it) continue;
            var stock = (typeof it.stock === 'number') ? it.stock : null;
            if (stock !== null && it.qty > stock) { msg('Only ' + stock + ' of ' + n + ' in stock.'); return false; }
        }
        return true;
    }
    function refreshIfPossible() { if (typeof refreshStockDisplay === 'function') refreshStockDisplay(); }

    if (typeof window.openCartDrawer !== 'function') window.openCartDrawer = function () {
        var dr = document.getElementById('cart-drawer'), ov = document.getElementById('cart-overlay');
        if (dr) dr.classList.add('active'); if (ov) ov.classList.add('active');
    };
    if (typeof window.closeCartDrawer !== 'function') window.closeCartDrawer = function () {
        var dr = document.getElementById('cart-drawer'), ov = document.getElementById('cart-overlay');
        if (dr) dr.classList.remove('active'); if (ov) ov.classList.remove('active');
    };
    if (typeof window.closeCheckout !== 'function') window.closeCheckout = function () {
        var m = document.getElementById('checkout-modal'); if (m) m.classList.remove('open');
    };
    if (typeof window.backToMode !== 'function') window.backToMode = function () {
        var mode = document.getElementById('checkout-mode'), pu = document.getElementById('pickup-panel'), dl = document.getElementById('delivery-panel');
        if (mode) mode.style.display = 'block'; if (pu) pu.style.display = 'none'; if (dl) dl.style.display = 'none';
    };
    if (typeof window.printReceipt !== 'function') window.printReceipt = function () {
        if (window.lastOrderId) window.open('print_order.php?id=' + window.lastOrderId, '_blank'); else window.print();
    };
    if (typeof window.openCheckout !== 'function') window.openCheckout = function () {
        var m = document.getElementById('checkout-modal'); if (!m) return;
        var itemsEl = document.getElementById('checkout-items'), totalEl = document.getElementById('checkout-total');
        var html = '', total = 0;
        for (var n in cart) { var it = cart[n]; html += '<p>' + n + ' x' + it.qty + ' = ₱' + (it.price * it.qty) + '</p>'; total += it.price * it.qty; }
        if (itemsEl) itemsEl.innerHTML = html || "<p style='color:#9a9a9a'>Cart is empty.</p>";
        if (totalEl) totalEl.innerHTML = 'Total: ₱' + total;
        var mode = document.getElementById('checkout-mode'), pu = document.getElementById('pickup-panel'), dl = document.getElementById('delivery-panel');
        if (mode) mode.style.display = 'block'; if (pu) pu.style.display = 'none'; if (dl) dl.style.display = 'none';
        m.classList.add('open');
    };
    if (typeof window.startPickup !== 'function') window.startPickup = function () {
        var mode = document.getElementById('checkout-mode'), pu = document.getElementById('pickup-panel'), dl = document.getElementById('delivery-panel');
        if (mode) mode.style.display = 'none'; if (dl) dl.style.display = 'none'; if (pu) pu.style.display = 'block';
        var itemsEl = document.getElementById('checkout-items'), totalEl = document.getElementById('checkout-total');
        var html = '', total = 0;
        for (var n in cart) { var it = cart[n]; html += '<p>' + n + ' x' + it.qty + ' = ₱' + (it.price * it.qty) + '</p>'; total += it.price * it.qty; }
        if (itemsEl) itemsEl.innerHTML = html || "<p style='color:#9a9a9a'>Cart is empty.</p>";
        if (totalEl) totalEl.innerHTML = 'Total: ₱' + total;
        var qn = document.getElementById('queue-number');
        if (qn) qn.innerHTML = '🎟 Queue: <b>#' + Math.floor(100 + Math.random() * 900) + '</b>';
    };
    if (typeof window.startDelivery !== 'function') window.startDelivery = function () {
        var mode = document.getElementById('checkout-mode'), pu = document.getElementById('pickup-panel'), dl = document.getElementById('delivery-panel');
        if (mode) mode.style.display = 'none'; if (pu) pu.style.display = 'none'; if (dl) dl.style.display = 'block';
    };

    if (typeof window.confirmPickup !== 'function') window.confirmPickup = function () {
        var customerName = ((document.getElementById('pickup-name') || {}).value || '').trim() || 'Walk-in';
        var orderDate = ((document.getElementById('order-date') || {}).value) || '';
        if (typeof cart !== 'object' || cart === null) { msg('Cart unavailable.'); return; }
        var total = 0; for (var n in cart) total += cart[n].price * cart[n].qty;
        if (total === 0) { msg('Cart is empty.'); return; }
        if (!stockOk(cart)) return;
        var queue = Math.floor(100 + Math.random() * 900);
        var qn = document.getElementById('queue-number'); if (qn) qn.innerHTML = '🎟 Your Number: <b>#' + queue + '</b>';
        var orderData = { customer_name: customerName, address: 'Pick-up', items: cart, total_amount: total, shipping_fee: 0, payment_method: 'cash', order_type: 'pickup', queue_number: queue, order_date: orderDate };
        fetch('save_order.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(orderData) })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data && data.status === 'success') {
                    window.lastOrderId = data.id; cart = {}; localStorage.removeItem('cart');
                    if (typeof updateCart === 'function') updateCart();
                    if (typeof closeCartDrawer === 'function') closeCartDrawer();
                    refreshIfPossible();
                    if (data.id) window.open('print_order.php?id=' + data.id, '_blank');
                    setTimeout(function () { window.location.href = 'my_orders.php'; }, 1200);
                } else { msg((data && data.message) || 'Save failed'); }
            }).catch(function (e) { console.error('SAVE ERROR', e); msg('Network error'); });
    };
    if (typeof window.confirmDelivery !== 'function') window.confirmDelivery = function () {
        var name = ((document.getElementById('d-name') || {}).value) || 'Walk-in';
        var address = ((document.getElementById('d-address') || {}).value) || 'N/A';
        var paymentMethod = ((document.getElementById('delivery-payment') || {}).value) || 'cash';
        var orderDate = ((document.getElementById('order-date') || {}).value) || '';
        if (typeof deliveryCart !== 'object' || deliveryCart === null) { msg('Cart unavailable.'); return; }
        var total = 0; for (var k in deliveryCart) total += deliveryCart[k].qty * deliveryCart[k].price;
        if (total === 0) { msg('No items in cart!'); return; }
        if (!stockOk(deliveryCart)) return;
        fetch('save_order.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ customer_name: name, address: address, items: deliveryCart, total_amount: total + SHIPPING_FEE, shipping_fee: SHIPPING_FEE, payment_method: paymentMethod, order_type: 'delivery', order_date: orderDate }) })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.status === 'success') {
                    window.lastOrderId = data.id;
                    if (data.id) window.open('print_order.php?id=' + data.id, '_blank');
                    if (typeof resetDelivery === 'function') resetDelivery();
                    if (typeof closeAll === 'function') closeAll();
                    refreshIfPossible();
                    setTimeout(function () { window.location.href = 'my_orders.php'; }, 1200);
                } else { msg((data && data.message) || 'Save failed'); }
            })
            .catch(function (e) { console.error('ERROR', e); msg('Network error'); });
    };
})();
</script>
<?php include 'chatbot_widget.php'; ?>
</body>
</html>
