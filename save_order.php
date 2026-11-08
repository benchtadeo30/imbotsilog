<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$conn = new mysqli("localhost", "root", "", "imbotsilog_db");

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "DB connection failed"
    ]);
    exit;
}

$input = file_get_contents("php://input");
$order_data = json_decode($input, true);

if (!is_array($order_data)) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid JSON"
    ]);
    exit;
}

/**
 * Normalize the shapes the client may send into a flat list: [{name, qty, price}, ...]
 *
 * Supported shapes:
 *   - array of objects:  [ {"name":"Tapsilog","qty":2,"price":85}, ... ]
 *   - map keyed by name: { "Tapsilog": {"qty":2,"price":85}, ... }   <-- what the menu cart sends
 *   - a JSON string containing either of the above
 */
function normalize_items($raw) {
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) return [];
    $out = [];
    foreach ($raw as $key => $v) {
        if (!is_array($v)) continue;
        // the name may live in the value, otherwise fall back to the map key
        $name = (isset($v['name']) && $v['name'] !== '') ? $v['name'] : (is_string($key) ? $key : '');
        $qty = (int)($v['qty'] ?? 0);
        $price = (float)($v['price'] ?? 0);
        if (is_string($name) && trim($name) !== '' && $qty > 0) {
            $out[] = ['name' => trim($name), 'qty' => $qty, 'price' => $price];
        }
    }
    return $out;
}

$customer = mb_substr(trim((string)($order_data['customer_name'] ?? 'Walk-in')), 0, 100);
if ($customer === '') $customer = 'Walk-in';
$address = mb_substr(trim((string)($order_data['address'] ?? '')), 0, 255);
$payment = mb_substr((string)($order_data['payment_method'] ?? 'cash'), 0, 30);
$payment = $payment === '' ? 'cash' : $payment;
$order_type = (($order_data['order_type'] ?? 'online') === 'delivery') ? 'delivery' : 'pickup';
// ALL orders start as pending; admin must mark as paid
$initialStatus = 'pending';
// shipping is server-enforced (matches SHIPPING_FEE in menu.js): delivery = 20, pickup = 0
$shipping = ($order_type === 'delivery') ? 20.0 : 0.0;

$orderItems = normalize_items($order_data['items'] ?? []);
if (empty($orderItems)) {
    echo json_encode(["status" => "error", "message" => "No valid items in order"]);
    exit;
}

// merge duplicate lines for the same item (2x + 3x => 5x)
$mergedItems = [];
foreach ($orderItems as $it) {
    $k = $it['name'];
    if (!isset($mergedItems[$k])) $mergedItems[$k] = ['name' => $k, 'qty' => 0, 'price' => $it['price']];
    $mergedItems[$k]['qty'] += $it['qty'];
}

$orderDate = $order_data['order_date'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $orderDate);
if (!$d || $d->format('Y-m-d') !== $orderDate) $orderDate = date('Y-m-d');
// keep as date at noon? store datetime as YYYY-MM-DD 12:00:00
$orderDateSql = $orderDate . ' 12:00:00';

// --- Validate stock and decrement it atomically so concurrent orders cannot oversell ---
// Prices and availability are read from the DB under the row lock — the client-sent
// total_amount / item prices are NEVER trusted (they would corrupt paid revenue).
$conn->begin_transaction();
try {
    $locked = [];   // name => ['qty'=>N, 'price'=>db price]
    foreach ($mergedItems as $it) {
        $nm = $it['name'];
        $need = (int)$it['qty'];

        $stmt = $conn->prepare("SELECT stock, price, is_available FROM menu_items WHERE name = ? LIMIT 1 FOR UPDATE");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("s", $nm);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            throw new Exception("Item not found: " . $nm);
        }
        if ((int)$row['is_available'] !== 1) {
            throw new Exception("Item not available: " . $nm);
        }
        $stock = (int)$row['stock'];
        if ($need > $stock) {
            throw new Exception("Insufficient stock for " . $nm . " (available: " . $stock . ")");
        }
        $locked[$nm] = ['qty' => $need, 'price' => (float)$row['price']];
    }

    // server-computed totals from DB prices
    $subtotal = 0.0;
    $finalItems = [];
    foreach ($locked as $nm => $l) {
        $subtotal += $l['qty'] * $l['price'];
        $finalItems[] = ['name' => $nm, 'qty' => $l['qty'], 'price' => $l['price']];
    }
    $total = round($subtotal + $shipping, 2);
    $items = json_encode($finalItems);

    // server-side atomic queue number from queue_tracker (client value is ignored)
    $qres = $conn->query("SELECT id, last_number FROM queue_tracker ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $qrow = $qres ? $qres->fetch_assoc() : null;
    $queue = $qrow ? ((int)$qrow['last_number'] + 1) : 1;
    if ($qrow) {
        $qid = (int)$qrow['id'];
        $qstmt = $conn->prepare("UPDATE queue_tracker SET last_number = ? WHERE id = ?");
        if (!$qstmt) throw new Exception($conn->error);
        $qstmt->bind_param("ii", $queue, $qid);
        if (!$qstmt->execute()) throw new Exception($qstmt->error);
        $qstmt->close();
    } else {
        $conn->query("INSERT INTO queue_tracker (last_number) VALUES ($queue)");
    }

    $sql = "INSERT INTO orders
(customer_name, address, total_amount, shipping_fee, payment_method, items, order_type, status, queue_number, order_date)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param(
        "ssddssssis",
        $customer,
        $address,
        $total,
        $shipping,
        $payment,
        $items,
        $order_type,
        $initialStatus,
        $queue,
        $orderDateSql
    );
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $newId = (int)$conn->insert_id;
    $stmt->close();

    // decrement exactly the quantities that were validated under lock
    foreach ($locked as $nm => $l) {
        $qty = (int)$l['qty'];
        $stmt = $conn->prepare("UPDATE menu_items SET stock = GREATEST(stock - ?, 0) WHERE name = ?");
        if (!$stmt) throw new Exception($conn->error);
        $stmt->bind_param("is", $qty, $nm);
        if (!$stmt->execute()) throw new Exception($stmt->error);
        $stmt->close();
    }

    $conn->commit();
    echo json_encode([
        "status" => "success",
        "queue" => $queue,
        "id" => $newId,
        "total" => $total
    ]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>