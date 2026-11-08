<?php
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "imbotsilog_db");

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB connection failed"]));
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error", "message" => "No data received"]);
    exit;
}

/**
 * Normalize items into [{name, qty, price}, ...].
 * Accepts an array of objects OR a map keyed by item name (what the cart sends),
 * or a JSON string of either.
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
        $name = (isset($v['name']) && $v['name'] !== '') ? $v['name'] : (is_string($key) ? $key : '');
        $qty = (int)($v['qty'] ?? 0);
        $price = (float)($v['price'] ?? 0);
        if (is_string($name) && trim($name) !== '' && $qty > 0) {
            $out[] = ['name' => trim($name), 'qty' => $qty, 'price' => $price];
        }
    }
    return $out;
}

$name = mb_substr(trim((string)($data['customer_name'] ?? 'Walk-in')), 0, 100);
if ($name === '') $name = 'Walk-in';
$payment = mb_substr((string)($data['payment_method'] ?? 'cash'), 0, 30);
$orderItems = normalize_items($data['items'] ?? []);
if (empty($orderItems)) {
    echo json_encode(["status" => "error", "message" => "No valid items in order"]);
    exit;
}
$mergedItems = [];
foreach ($orderItems as $it) {
    $k = $it['name'];
    if (!isset($mergedItems[$k])) $mergedItems[$k] = ['name' => $k, 'qty' => 0, 'price' => $it['price']];
    $mergedItems[$k]['qty'] += $it['qty'];
}
$orderDate = $data['order_date'] ?? date('Y-m-d');
$d = DateTime::createFromFormat('Y-m-d', $orderDate);
if (!$d || $d->format('Y-m-d') !== $orderDate) $orderDate = date('Y-m-d');
$orderDateSql = $orderDate . ' 12:00:00';
// ALL orders start as pending; admin must mark as paid
$initialStatus = 'pending';

/* 💾 SAVE ORDER — validate + decrement stock atomically */
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
        $r = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$r) throw new Exception("Item not found: " . $nm);
        if ((int)$r['is_available'] !== 1) throw new Exception("Item not available: " . $nm);
        $stock = (int)$r['stock'];
        if ($need > $stock) {
            throw new Exception("Insufficient stock for " . $nm . " (available: " . $stock . ")");
        }
        $locked[$nm] = ['qty' => $need, 'price' => (float)$r['price']];
    }

    // server-computed total from DB prices (client total is never trusted)
    $subtotal = 0.0;
    $finalItems = [];
    foreach ($locked as $nm => $l) {
        $subtotal += $l['qty'] * $l['price'];
        $finalItems[] = ['name' => $nm, 'qty' => $l['qty'], 'price' => $l['price']];
    }
    $total = round($subtotal, 2);
    $items = json_encode($finalItems);

    /* atomic queue number inside the transaction (row locked with FOR UPDATE) */
    $qres = $conn->query("SELECT id, last_number FROM queue_tracker ORDER BY id DESC LIMIT 1 FOR UPDATE");
    $qrow = $qres ? $qres->fetch_assoc() : null;
    $newQueue = $qrow ? ((int)$qrow['last_number'] + 1) : 1;
    if ($qrow) {
        $qid = (int)$qrow['id'];
        $qstmt = $conn->prepare("UPDATE queue_tracker SET last_number = ? WHERE id = ?");
        if (!$qstmt) throw new Exception($conn->error);
        $qstmt->bind_param("ii", $newQueue, $qid);
        if (!$qstmt->execute()) throw new Exception($qstmt->error);
        $qstmt->close();
    } else {
        $conn->query("INSERT INTO queue_tracker (last_number) VALUES ($newQueue)");
    }

    $sql = "INSERT INTO orders (customer_name, items, total_amount, payment_method, order_type, queue_number, status, order_date)
            VALUES (?, ?, ?, ?, 'walkin', ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception($conn->error);
    $stmt->bind_param("ssdsiss", $name, $items, $total, $payment, $newQueue, $initialStatus, $orderDateSql);
    if (!$stmt->execute()) throw new Exception($stmt->error);
    $newId = (int)$conn->insert_id;
    $stmt->close();

    // decrement exactly the quantities validated under lock
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
            "queue" => $newQueue,
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