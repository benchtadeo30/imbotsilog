///LOAD CART

let cart = JSON.parse(localStorage.getItem("cart")) || {};

let deliveryCart = {};

let selectedPayments = [];

let payments = [];

let selectedTables = [];

let finalTotalAmount = 0;

const SHIPPING_FEE = 20;



const cartItemsEl = document.getElementById("cart-items");

const cartTotalEl = document.getElementById("cart-total");

const cartCountEl = document.getElementById("cart-count");

const cartToggle = document.getElementById("cart-toggle");

const cartDropdown = document.getElementById("cart-dropdown");



// TOGGLE CART (dropdown removed; cart icon opens checkout)

// Cart icon opens drawer (click + hover)

if (cartToggle) {

    cartToggle.onclick = openCartDrawer;

    cartToggle.onmouseenter = openCartDrawer;

}



// ===== Pickup / Delivery choice =====

function startPickup() {

    const mode = document.getElementById("checkout-mode");

    const panel = document.getElementById("pickup-panel");

    const dPanel = document.getElementById("delivery-panel");

    if (mode) mode.style.display = "none";

    if (dPanel) dPanel.style.display = "none";

    if (panel) panel.style.display = "block";

    // render cart summary

    const itemsEl = document.getElementById("checkout-items");

    const totalEl = document.getElementById("checkout-total");

    if (itemsEl && totalEl) {

        let html = ""; let total = 0;

        for (let name in cart) { const it = cart[name]; html += `<p>${name} x${it.qty} = ₱${it.price * it.qty}</p>`; total += it.price * it.qty; }

        itemsEl.innerHTML = html || "<p style='color:#9a9a9a'>Cart is empty.</p>";

        totalEl.innerHTML = "Total: ₱" + total;

    }

    const qn = document.getElementById("queue-number");

    if (qn) qn.innerHTML = "🎟 Queue: <b>#"+ (Math.floor(100+Math.random()*900)) + "</b>";

}

function startDelivery() {

    const mode = document.getElementById("checkout-mode");

    const panel = document.getElementById("pickup-panel");

    const dPanel = document.getElementById("delivery-panel");

    if (mode) mode.style.display = "none";

    if (panel) panel.style.display = "none";

    if (dPanel) dPanel.style.display = "block";

}

function backToMode() {

    const mode = document.getElementById("checkout-mode");

    const panel = document.getElementById("pickup-panel");

    const dPanel = document.getElementById("delivery-panel");

    if (mode) mode.style.display = "block";

    if (panel) panel.style.display = "none";

    if (dPanel) dPanel.style.display = "none";

}

function printReceipt() {

    // use the last placed order id if available, else print the page

    if (window.lastOrderId) {

        window.open('print_order.php?id=' + window.lastOrderId, '_blank');

    } else {

        window.print();

    }

}

function confirmPickup() {

    const customerName = (document.getElementById("pickup-name")?.value || "").trim() || "Walk-in";

    const orderDate = document.getElementById("order-date")?.value || "";

    let total = 0; for (let name in cart) total += cart[name].price * cart[name].qty;

    if (total === 0) { showModal("Cart is empty."); return; }
    
    // Validate stock before sending to server
    for (let name in cart) {
        const item = cart[name];
        const stock = item?.stock || 0;
        if (item.qty > stock) {
            showModal("Only " + stock + " of " + name + " in stock.");
            return;
        }
    }
    
    let queue = Math.floor(100 + Math.random() * 900);

    document.getElementById("queue-number").innerHTML = `🎟 Your Number: <b>#${queue}</b>`;

    let orderData = { customer_name: customerName, address: "Pick-up", items: cart, total_amount: total, shipping_fee: 0, payment_method: "cash", order_type: "pickup", queue_number: queue, order_date: orderDate };

    fetch("save_order.php", { method:"POST", headers:{ "Content-Type":"application/json" }, body: JSON.stringify(orderData) })

    .then(r => r.json())

    .then(data => {

        if (data && data.status === "success") {

            window.lastOrderId = data.id;

            // show the server-assigned queue number (authoritative)
            if (data.queue) document.getElementById("queue-number").innerHTML = `🎟 Your Number: <b>#${data.queue}</b>`;

            cart = {}; localStorage.removeItem("cart"); updateCart();

            closeCartDrawer();

            // Refresh stock display
            refreshStockDisplay();

            // open receipt then go to orders

            if (data.id) window.open('print_order.php?id=' + data.id, '_blank');

            setTimeout(() => { window.location.href = "my_orders.php"; }, 1200);

        } else {

            showModal(data?.message || "Save failed");

        }

    })

    .catch(err => { console.error("SAVE ERROR", err); showModal("Save error"); });

}

function confirmDelivery() {

    const name = document.getElementById("d-name")?.value || "Walk-in";

    const address = document.getElementById("d-address")?.value || "N/A";

    const paymentMethod = document.getElementById("delivery-payment")?.value || "cash";

    const orderDate = document.getElementById("order-date")?.value || "";

    let total = 0;

    for (let item in deliveryCart) total += deliveryCart[item].qty * deliveryCart[item].price;

    if (total === 0) { showModal("No items in cart!"); return; }
    
    // Validate stock before sending to server
    for (let name in deliveryCart) {
        const item = deliveryCart[name];
        const stock = item?.stock || 0;
        if (item.qty > stock) {
            showModal("Only " + stock + " of " + name + " in stock.");
            return;
        }
    }
    
    fetch("save_order.php", { method:"POST", headers:{ "Content-Type":"application/json" }, body: JSON.stringify({ customer_name: name, address: address, items: deliveryCart, total_amount: total + SHIPPING_FEE, shipping_fee: SHIPPING_FEE, payment_method: paymentMethod, order_type: "delivery", order_date: orderDate }) })

    .then(res => res.json())

    .then(data => {

        console.log("DELIVERY SAVED:", data);

        if (data.status === "success") { window.lastOrderId = data.id; if (data.id) window.open('print_order.php?id=' + data.id, '_blank'); resetDelivery(); closeAll(); refreshStockDisplay(); setTimeout(() => { window.location.href = "my_orders.php"; }, 1200); }

        else { showModal(data.message || "Save failed"); }

    })

    .catch(err => { console.error("ERROR", err); showModal("Network error"); });

}

function openCartDrawer() {

    const dr = document.getElementById("cart-drawer");

    const ov = document.getElementById("cart-overlay");

    if (dr) dr.classList.add("active");

    if (ov) ov.classList.add("active");

}

function closeCartDrawer() {

    const dr = document.getElementById("cart-drawer");

    const ov = document.getElementById("cart-overlay");

    if (dr) dr.classList.remove("active");

    if (ov) ov.classList.remove("active");

}



// SAVE CART

function saveCart() {

    localStorage.setItem("cart", JSON.stringify(cart));

}



// CUSTOM MODAL (replaces alert)

function ensureModal() {

    if (document.getElementById("appModal")) return;

    const style = document.createElement("style");

    style.textContent = `

      .app-modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.72); backdrop-filter:blur(4px); -webkit-backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; z-index:9999; padding:20px; }

      .app-modal-card { background:linear-gradient(180deg,#1a1a1a,#121212); border:1px solid rgba(255,122,0,0.28); border-radius:20px; padding:26px 28px; max-width:380px; width:90%; text-align:center; box-shadow:0 24px 80px rgba(0,0,0,0.7), 0 0 0 1px rgba(255,122,0,0.08); animation:appFade .18s ease; }

      .app-modal-msg { color:#fff; font-size:14px; margin-bottom:20px; line-height:1.5; }

      .app-modal-btn { background:#ff7a00; color:#000; border:none; font-weight:700; padding:10px 28px; border-radius:10px; cursor:pointer; font-family:'Inter',Arial,sans-serif; font-size:13px; transition:transform .1s, box-shadow .15s; box-shadow:0 4px 14px rgba(255,122,0,0.3); }

      .app-modal-btn:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(255,122,0,0.4); }

      @keyframes appFade { from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }

    `;

    document.head.appendChild(style);

    const overlay = document.createElement("div");

    overlay.id = "appModal";

    overlay.className = "app-modal-overlay";

    overlay.style.display = "none";

    overlay.innerHTML = `<div class="app-modal-card"><div class="app-modal-msg" id="appModalMsg"></div><button class="app-modal-btn" onclick="closeAppModal()">OK</button></div>`;

    document.body.appendChild(overlay);

}

function showModal(msg) {

    ensureModal();

    document.getElementById("appModalMsg").innerHTML = String(msg);

    document.getElementById("appModal").style.display = "flex";

}

function closeAppModal() {

    const m = document.getElementById("appModal");

    if (m) m.style.display = "none";

}

function isUnavailable(name) {

    const list = (window.UNAVAILABLE_ITEMS || []).map(s => String(s).toLowerCase().trim());

    return list.includes(String(name).toLowerCase().trim());

}

function markUnavailableItems() {

    document.querySelectorAll(".menu-item").forEach(item => {

        const name = item.dataset.name;

        const stock = parseInt(item.dataset.stock || "0");

        if (isUnavailable(name) || stock <= 0) {

            item.classList.add("unavailable");

            item.style.opacity = "0.45";

            item.style.filter = "grayscale(0.6)";

            const btn = item.querySelector(".add-btn");

            if (btn) { btn.disabled = true; btn.textContent = "Out of stock"; btn.style.background = "#555"; btn.style.color = "#ccc"; btn.style.cursor = "not-allowed"; }

            const inp = item.querySelector(".qty-input");

            if (inp) { inp.disabled = true; }

            if (!item.querySelector(".unavail-badge")) {

                const b = document.createElement("span");

                b.className = "unavail-badge";

                b.textContent = stock <= 0 ? "Out of stock" : "Unavailable";

                b.style.cssText = "position:absolute;top:8px;right:8px;background:#7f1d1d;color:#fca5a5;font-size:11px;font-weight:700;padding:3px 8px;border-radius:999px;";

                item.style.position = "relative";

                item.appendChild(b);

            }

            return;

        }

        // normal (available) — ensure badge cleared

    });

}





// Refresh stock display from server
async function refreshStockDisplay() {
    try {
        const res = await fetch('menu.php', { headers: { 'X-Requested-With': 'fetch' } });
        const html = await res.text();
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        
        // Update stock-line elements and data-stock attributes
        doc.querySelectorAll('.menu-item').forEach(serverItem => {
            const name = serverItem.dataset.name;
            const stock = serverItem.dataset.stock;
            const clientItem = document.querySelector('.menu-item[data-name="' + CSS.escape(name) + '"]');
            if (clientItem && stock !== undefined) {
                // Update data-stock attribute
                clientItem.dataset.stock = stock;
                // Update qty-input max
                const qtyInput = clientItem.querySelector('.qty-input');
                if (qtyInput) qtyInput.max = stock;
                // Update stock-line text
                const stockLine = clientItem.querySelector('.stock-line');
                if (stockLine) stockLine.textContent = 'Stock: ' + stock;
                // Update add-btn if out of stock
                const btn = clientItem.querySelector('.add-btn');
                if (btn && stock <= 0) {
                    btn.disabled = true;
                    btn.textContent = 'Out of stock';
                    btn.style.background = '#555';
                    btn.style.color = '#ccc';
                    btn.style.cursor = 'not-allowed';
                }
            }
        });
        
        // Also update cart items' stock if they exist
        for (let name in cart) {
            const clientItem = document.querySelector('.menu-item[data-name="' + CSS.escape(name) + '"]');
            if (clientItem) {
                cart[name].stock = parseInt(clientItem.dataset.stock) || 0;
            }
        }
        for (let name in deliveryCart) {
            const clientItem = document.querySelector('.menu-item[data-name="' + CSS.escape(name) + '"]');
            if (clientItem) {
                deliveryCart[name].stock = parseInt(clientItem.dataset.stock) || 0;
            }
        }
    } catch (e) {
        console.error('Failed to refresh stock:', e);
    }
}


// ADD TO CART

document.addEventListener("click", function(e) {

    if (e.target.classList.contains("add-btn")) {

        if (e.target.disabled) return;

        const item = e.target.closest(".menu-item");

        const name = item.dataset.name;

        if (isUnavailable(name)) { showModal("This item is currently unavailable."); return; }

        const price = parseInt(item.dataset.price);

        const stock = parseInt(item.dataset.stock || "0");

        const qtyInput = item.querySelector(".qty-input");

        let qty = qtyInput ? parseInt(qtyInput.value) : 1;

        if (isNaN(qty) || qty < 1) qty = 1;

        if (stock <= 0) { showModal("This item is out of stock."); return; }

        if (qty > stock) { showModal("Only " + stock + " in stock."); qty = stock; }



        if (cart[name]) cart[name].qty += qty;

                else cart[name] = { price, qty, stock };



                updateCart();

                openCartDrawer();

    }

});



// UPDATE CART

function updateCart() {

    if (!cartItemsEl) return;

    

    cartItemsEl.innerHTML = "";

    let total = 0, count = 0;



    for (let name in cart) {

        const item = cart[name];

        total += item.price * item.qty;

        count += item.qty;



        const safeName = name.replace(/[^a-zA-Z0-9]/g, "");

        cartItemsEl.innerHTML += `

        <div class="cart-item">

            <b>${name}</b> ₱${item.price * item.qty}

            <div class="qty-controls">

                <button onclick="dec('${name}')">➖</button>

                ${item.qty}

                <button onclick="inc('${name}')">➕</button>

                <button class="remove-btn" onclick="removeItem('${name}')">❌</button>

            </div>

        </div>`;

    }



    if (cartTotalEl) cartTotalEl.innerHTML = "Total: ₱" + total;

    if (cartCountEl) cartCountEl.textContent = count;



    saveCart();

}



// CART CONTROLS

function inc(name) {
    const item = cart[name];
    const stock = item?.stock || 0;
    if (item && item.qty >= stock) {
        showModal("Only " + stock + " in stock.");
        return;
    }
    if (item) item.qty++;
    updateCart();
}



function dec(name) {
    if (cart[name] && cart[name].qty > 0) {
        cart[name].qty--;
        if (cart[name].qty <= 0) delete cart[name];
        updateCart();
    }
}



function removeItem(name) {

    delete cart[name];

    updateCart();

}



// ================= CHECKOUT =================

let orderDone = false;



function openCheckout() {

    const modal = document.getElementById("checkout-modal");

    const itemsEl = document.getElementById("checkout-items");

    const totalEl = document.getElementById("checkout-total");



    if (!modal || !itemsEl || !totalEl) return;



    let html = "";

    let total = 0;



    for (let name in cart) {

        let item = cart[name];

        html += `<p>${name} x${item.qty} = ₱${item.price * item.qty}</p>`;

        total += item.price * item.qty;

    }



    itemsEl.innerHTML = html || "<p style='color:#9a9a9a'>Cart is empty.</p>";

    totalEl.innerHTML = "Total: ₱" + total;



    const _mode = document.getElementById("checkout-mode");

    const _pickup = document.getElementById("pickup-panel");

    const _delivery = document.getElementById("delivery-panel");

    if (_mode) _mode.style.display = "block";

    if (_pickup) _pickup.style.display = "none";

    if (_delivery) _delivery.style.display = "none";



    modal.classList.add("open");

}



function closeCheckout() {

    const modal = document.getElementById("checkout-modal");

    if (modal) modal.classList.remove("open");

}



function selectCash(amount) {

    const buttons = document.querySelectorAll(".payment-options button");

    let clickedBtn = [...buttons].find(btn =>

        btn.textContent.replace("₱","") == amount

    );



    if (selectedPayments.includes(amount)) {

        selectedPayments = selectedPayments.filter(p => p !== amount);

        if (clickedBtn) clickedBtn.classList.remove("active");

    } else {

        selectedPayments.push(parseInt(amount));

        if (clickedBtn) clickedBtn.classList.add("active");

    }

    computeCheckoutPayment();

}



function computeCheckoutPayment() {

    let total = 0;

    for (let name in cart) {

        total += cart[name].price * cart[name].qty;

    }



    let totalPaid = selectedPayments.reduce((a,b) => a + b, 0);

    let change = totalPaid - total;



    let result = `💰 Binayad: ₱${totalPaid}<br>`;

    if (selectedPayments.length > 1) {

        result += `🧾 Combo: ${selectedPayments.join(" + ")}<br>`;

    }



    if (change < 0) {

        result += `❌ Kulang ang bayad!<br>Kulang: ₱${Math.abs(change)}`;

    } else {

        result += `🧾 Total: ₱${total}<br>🪙 Sukli: ₱${change}`;

    }



    const display = document.getElementById("change-display");

    if (display) display.innerHTML = result;

}



function confirmOrder() {

    const paymentMethod = document.getElementById("checkout-payment")?.value || "cash";

    const customerName = (document.getElementById("checkout-name")?.value || "").trim() || "Walk-in";

    const orderDate = document.getElementById("order-date")?.value || "";



    let total = 0;

    for (let name in cart) {

        total += cart[name].price * cart[name].qty;

    }

    if (total === 0) {

        showModal("Cart is empty.");

        return;

    }



    // Validate stock before sending to server
    for (let name in cart) {
        const item = cart[name];
        const stock = item?.stock || 0;
        if (item.qty > stock) {
            showModal("Only " + stock + " of " + name + " in stock.");
            return;
        }
    }

    if (paymentMethod === "cash") {

        if (selectedPayments.length === 0) {

            showModal("Pumili muna ng payment!");

            return;

        }

        let totalPaid = selectedPayments.reduce((a,b) => a + b, 0);

        if (totalPaid < total) {

            showModal("Kulang ang bayad!");

            return;

        }

    }



    let queue = Math.floor(100 + Math.random() * 900);



    document.getElementById("queue-number").innerHTML =

        `🎟 Your Number: <b>#${queue}</b><br>Tawagin nalang pag ready na!`;



    let orderData = {

        customer_name: customerName,

        address: "Counter",

        items: cart,

        total_amount: total,

        shipping_fee: 0,

        payment_method: paymentMethod,

        order_type: "walk-in",

        queue_number: queue,

        order_date: orderDate

    };

    fetch("save_order.php", {

            method: "POST",

            headers: { "Content-Type": "application/json" },

            body: JSON.stringify(orderData)

        })

        .then(res => res.json())

        .then(data => { 
            console.log("ORDER SAVED:", data);
            if (data && data.status === "success") {
                window.lastOrderId = data.id;
                // show the server-assigned queue number (authoritative)
                if (data.queue) {
                    const qn = document.getElementById("queue-number");
                    if (qn) qn.innerHTML = `🎟 Your Number: <b>#${data.queue}</b><br>Tawagin nalang pag ready na!`;
                }
                if (data.id) window.open('print_order.php?id=' + data.id, '_blank');
                refreshStockDisplay();
                setTimeout(() => { window.location.href = "my_orders.php"; }, 1200);
            } else {
                showModal(data.message || "Save failed");
            }
        })

        .catch(err => { console.error("SAVE ERROR:", err); showModal("Network error"); });



        cart = {};

        localStorage.removeItem("cart");

        updateCart();

        selectedPayments = [];

}



// ================= DELIVERY =================

function openDelivery() {

    document.getElementById("delivery-menu").style.display = "flex";

    loadDeliveryItems("all");

}



function loadDeliveryItems(type = "all") {

    const container = document.getElementById("delivery-items");

    if (!container) return;

    container.innerHTML = "";



    const allItems = Array.from(document.querySelectorAll(".menu-item"));

    const filtered = allItems.filter(item => {

        const cat = (item.dataset.category || "").toLowerCase();

        if (type === "all") return true;

        if (type === "sunny") return cat.includes("sunny");

        if (type === "rainy") return cat.includes("rainy");

        return true;

    });



    const groups = {};

    filtered.forEach(item => {

        const c = item.dataset.category || "Other";

        (groups[c] = groups[c] || []).push(item);

    });



    for (const c in groups) {

        container.innerHTML += `<h3 class="delivery-title">${c}</h3>`;

        groups[c].forEach(item => {

            const name = item.dataset.name;

            if (isUnavailable(name)) return;

            const price = parseInt(item.dataset.price);

            const stock = parseInt(item.dataset.stock || "0");

            if (stock <= 0) return;

            const imgEl = item.querySelector("img");

            const img = imgEl ? imgEl.src : "";

            const safeId = name.replace(/[^a-zA-Z0-9]/g, "");

            if (!deliveryCart[name]) deliveryCart[name] = { price, qty: 0, stock };

            else deliveryCart[name].stock = stock;

            container.innerHTML += `

                <div class="delivery-item">

                    <img src="${img}">

                    <div style="flex:1;text-align:left;">

                        <b>${name}</b><br>₱${price} <span style="color:#9a9a9a;font-size:11px;">(stock: ${stock})</span>

                    </div>

                    <div>

                        <button onclick="minusDelivery('${name.replace(/'/g,"\\'")}')">➖</button>

                        <span id="d-${safeId}">${deliveryCart[name].qty}</span>

                        <button onclick="addDelivery('${name.replace(/'/g,"\\'")}')">➕</button>

                    </div>

                </div>`;

        });

    }

    updateDeliveryUI();

}



function loadCategoryItems(selector, container) {

    const items = document.querySelectorAll(`${selector} .menu-item`);

    items.forEach(item => {

        const name = item.dataset.name;

        if (isUnavailable(name)) return;

        const price = parseInt(item.dataset.price);

        const img = item.querySelector("img").src;

        const safeId = name.replace(/[^a-zA-Z0-9]/g, "");



        if (!deliveryCart[name]) {

            deliveryCart[name] = { price, qty: 0 };

        }



        container.innerHTML += `

        <div class="delivery-item">

            <img src="${img}">

            <div style="flex:1;text-align:left;">

                <b>${name}</b><br>₱${price}

            </div>

            <div>

                <button onclick="minusDelivery('${name}')">➖</button>

                <span id="d-${safeId}">${deliveryCart[name].qty}</span>

                <button onclick="addDelivery('${name}')">➕</button>

            </div>

        </div>`;

    });

}



function addSection(selector, title) {

    const container = document.getElementById("delivery-items");

    const grid = document.querySelector(selector);



    if (!grid || !container) return;



    container.innerHTML += `<h3 class="delivery-title">${title}</h3>`;

    loadCategoryItems(selector, container);

}



function updateDeliveryUI() {

    let total = 0;

    for (let name in deliveryCart) {

        const item = deliveryCart[name];

        const safeId = name.replace(/[^a-zA-Z0-9]/g, "");

        const el = document.getElementById(`d-${safeId}`);

        if (el) el.textContent = item.qty;

        total += item.qty * item.price;

    }



    let finalTotal = total > 0 ? total + SHIPPING_FEE : 0;

    const totalEl = document.getElementById("delivery-total");

    if (totalEl) {

        totalEl.innerHTML = `Food: ₱${total}<br>Shipping: ₱${SHIPPING_FEE}<br><b>Total: ₱${finalTotal}</b>`;

    }

}



function addDelivery(name) {

    if (!deliveryCart[name]) deliveryCart[name] = { price: 0, qty: 0, stock: 0 };

    const stock = deliveryCart[name].stock || 0;

    if (deliveryCart[name].qty >= stock) {

        showModal("Only " + stock + " in stock.");

        return;

    }

    deliveryCart[name].qty++;

    updateDeliveryUI();

}



function minusDelivery(name) {

    if (deliveryCart[name] && deliveryCart[name].qty > 0) {

        deliveryCart[name].qty--;

        if (deliveryCart[name].qty <= 0) delete deliveryCart[name];

        updateDeliveryUI();

    }

}



function nextToForm() {

    document.getElementById("delivery-menu").style.display = "none";

    document.getElementById("delivery-form").style.display = "flex";

}



function nextToSummary() {

    payments = [];

    document.querySelectorAll(".payment-buttons button").forEach(btn => btn.classList.remove("active"));

    document.getElementById("payment-result").innerHTML = "";



    const name = document.getElementById("d-name").value;

    const address = document.getElementById("d-address").value;



    let summary = `<p><b>Name:</b> ${name}</p><p><b>Address:</b> ${address}</p><hr>`;

    let total = 0;



    for (let item in deliveryCart) {

        let data = deliveryCart[item];

        if (data.qty > 0) {

            summary += `<p>${item} x${data.qty} = ₱${data.qty * data.price}</p>`;

            total += data.qty * data.price;

        }

    }



    let finalTotal = total + SHIPPING_FEE;

    finalTotalAmount = finalTotal;



    summary += `<hr><p>Food: ₱${total}</p><p>Shipping: ₱${SHIPPING_FEE}</p><h3>Total: ₱${finalTotal}</h3>`;

    

    document.getElementById("summary-content").innerHTML = summary;

    document.getElementById("delivery-form").style.display = "none";

    document.getElementById("delivery-summary").style.display = "flex";

}



function pay(amount) {

    const buttons = document.querySelectorAll(".payment-buttons button");

    let clickedBtn = [...buttons].find(btn => btn.textContent.replace("₱","") == amount);



    if (payments.includes(amount)) {

        payments = payments.filter(p => p !== amount);

        if (clickedBtn) clickedBtn.classList.remove("active");

    } else {

        payments.push(parseInt(amount));

        if (clickedBtn) clickedBtn.classList.add("active");

    }

    computePayment();

}



function computePayment() {

    let totalPaid = payments.reduce((a, b) => a + b, 0);

    let change = totalPaid - finalTotalAmount;



    let result = `💵 Binayad: ₱${totalPaid}<br>`;

    if (payments.length > 1) result += `🧾 Combo: ${payments.join(" + ")}<br>`;



    if (change < 0) {

        result += `❌ Kulang ang bayad!<br>Kulang: ₱${Math.abs(change)}`;

    } else {

        result += `🧾 Total: ₱${finalTotalAmount}<br>🪙 Sukli: ₱${change}`;

    }



    document.getElementById("payment-result").innerHTML = result;

}



function resetDelivery() {

    deliveryCart = {};

    payments = [];

    finalTotalAmount = 0;

    updateDeliveryUI();

}



// ================= RESERVATION =================

function openReservation() {

    selectedTables = [];

    document.getElementById("reserve-modal").style.display = "flex";

    

}



function generateTables() {

    const area = document.getElementById("r-area").value;

    const people = parseInt(document.getElementById("r-people").value) || 0;



    const tableContainer = document.getElementById("table-container");

    const note = document.getElementById("table-note");



    selectedTables = [];

    tableContainer.innerHTML = "";

    note.innerHTML = "";



    let totalTables =

        area === "window" ? 2 :

        area === "middle" ? 6 :

        area === "end" ? 7 : 0;



    let maxSelect =

        people >= 11 ? 3 :

        people >= 6 ? 2 : 1;



    note.innerHTML = `You can select up to ${maxSelect} table(s)`;



    for (let i = 1; i <= totalTables; i++) {

        let btn = document.createElement("button");

        btn.type = "button";

        btn.textContent = "Table " + i;

        btn.classList.add("table-btn");



        btn.onclick = () => selectTable(btn, maxSelect);



        tableContainer.appendChild(btn);

    }

}



function selectTable(btn, max) {

    const tableName = btn.textContent;



    if (selectedTables.includes(tableName)) {

        selectedTables = selectedTables.filter(t => t !== tableName);

        btn.classList.remove("selected");

    } else {

        if (selectedTables.length >= max) {

            showModal("Limit reached!");

            return;

        }



        selectedTables.push(tableName);

        btn.classList.add("selected");

    }

}



function submitReservation() {

    const name = document.getElementById("r-name").value;

    const people = document.getElementById("r-people").value;

    const time = document.getElementById("r-time").value;

    const area = document.getElementById("r-area").value;



    if (!name || !people || !time || !area || selectedTables.length === 0) {

        showModal("Please complete all fields!");

        return;

    }



    document.getElementById("reserve-content").innerHTML = `

        <p><b>Name:</b> ${name}</p><p><b>People:</b> ${people}</p>

        <p><b>Time:</b> ${time}</p><p><b>Area:</b> ${area}</p>

        <p><b>Tables:</b> ${selectedTables.join(", ")}</p><hr>

        <h3>Reservation Fee: ₱50</h3>

    `;



    document.getElementById("reserve-modal").style.display = "none";

    document.getElementById("reserve-summary").style.display = "flex";

}



function resetReservation() {

    ['r-name', 'r-people', 'r-time', 'r-area'].forEach(id => {

        const el = document.getElementById(id);

        if (el) el.value = "";

    });

    document.getElementById("table-container").innerHTML = "";

    document.getElementById("table-note").innerHTML = "";

    selectedTables = [];

}



function confirmReservation() {



    const name = document.getElementById("r-name").value;

    const people = document.getElementById("r-people").value;

    const time = document.getElementById("r-time").value;

    const area = document.getElementById("r-area").value;



    if (!name || !people || !time || !area) {

        showModal("Complete all fields!");

        return;

    }



    if (selectedTables.length === 0) {

        showModal("Please select table(s)!");

        return;

    }



    // show fee message first

    const confirmPay = confirm(

        "Reservation Fee is ₱50.\nPay at counter.\n\nProceed?"

    );



    if (!confirmPay) return;



    fetch("save_reservation.php", {

        method: "POST",

        headers: {

            "Content-Type": "application/json"

        },

        body: JSON.stringify({

            customer_name: name,

            people: people,

            time: time,

            area: area,

            tables: selectedTables

        })

    })

    .then(res => res.json())

    .then(data => {

        if (data.status === "success") {

            showModal("✅ Reservation Confirmed! Pay ₱50 at counter.");

            closeAll();

        } else {

            showModal("Error: " + data.message);

        }

    })

    .catch(err => {

        console.error(err);

        showModal("Network error");

    });

}



// CLOSE ALL

function closeAll() {

    document.querySelectorAll(".modal").forEach(m => m.style.display = "none");

    resetReservation();

    resetDelivery();

    

    const name = document.getElementById("d-name");

    const address = document.getElementById("d-address");

    if (name) name.value = "";

    if (address) address.value = "";

    

    document.getElementById("summary-content").innerHTML = "";

    document.getElementById("payment-result").innerHTML = "";

}



// FILTERS

function filterMeals(type, el) {

    const all = document.getElementById("all-meals");

    const sunny = document.getElementById("sunny-meals");

    const rainy = document.getElementById("rainy-meals");



    [all, sunny, rainy].forEach(el => el.style.display = "none");



    if (type === "all" && all) all.style.display = "grid";

    else if (type === "sunny" && sunny) sunny.style.display = "grid";

    else if (type === "rainy" && rainy) rainy.style.display = "grid";



    document.querySelectorAll(".tab").forEach(tab => tab.classList.remove("active"));

    el.classList.add("active");

}



function filterDelivery(type, el) {

    document.querySelectorAll(".dtab").forEach(tab => tab.classList.remove("active"));

    el.classList.add("active");

    loadDeliveryItems(type);

}



function toggleSidebar() {

    const sidebar = document.getElementById("sidebar");
    const backdrop = document.querySelector(".sidebar-backdrop");

    if (!sidebar) return;

    const isOpen = sidebar.classList.toggle("open");
    if (backdrop) backdrop.classList.toggle("show", isOpen);
    document.querySelectorAll('.navbar .menu-btn[aria-controls="sidebar"]').forEach(button => {
        button.setAttribute("aria-expanded", String(isOpen));
    });
}

document.querySelectorAll("#sidebar nav a").forEach(link => {
    const linkPage = new URL(link.href, window.location.href).pathname.split("/").pop();
    const currentPage = window.location.pathname.split("/").pop();
    if (linkPage === currentPage) {
        link.setAttribute("aria-current", "page");
    }
});

document.addEventListener("keydown", event => {
    const sidebar = document.getElementById("sidebar");
    if (event.key === "Escape" && sidebar && sidebar.classList.contains("open")) {
        toggleSidebar();
    }
});


// INIT

window.onload = function () {

    updateCart();

    markUnavailableItems();

    // order date max = local today

    const od = document.getElementById("order-date");

    if (od) {

        const d = new Date();

        const today = d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');

        od.max = today;

        if (!od.value) od.value = today;

    }

    if (window.location.hash === "#delivery") {

        openDelivery();

    }

};