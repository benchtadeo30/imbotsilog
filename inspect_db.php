<?php
$conn = new mysqli("localhost", "root", "", "imbotsilog_db");
if ($conn->connect_error) { echo "CONN ERROR: ".$conn->connect_error."\n"; exit(1); }
echo "DB connected\n";
$res = $conn->query("SHOW TABLES");
echo "TABLES:\n";
while ($r = $res->fetch_row()) echo " - ".$r[0]."\n";
echo "\nORDERS SCHEMA:\n";
$res = $conn->query("SHOW CREATE TABLE orders");
$row = $res->fetch_assoc();
echo $row['Create Table']."\n";
echo "\nDISTINCT payment_method:\n";
$res = $conn->query("SELECT payment_method, COUNT(*) c FROM orders GROUP BY payment_method");
while ($r = $res->fetch_assoc()) echo " - ".json_encode($r['payment_method']).": ".$r['c']."\n";
echo "\nRECENT 20 orders:\n";
$res = $conn->query("SELECT id, customer_name, total_amount, payment_method, order_type, status, order_date FROM orders ORDER BY id DESC LIMIT 20");
while ($r = $res->fetch_assoc()) {
    echo sprintf("#%d | %s | ₱%s | %s | %s | %s | %s\n", $r['id'], $r['customer_name'], $r['total_amount'], $r['payment_method'], $r['order_type'], $r['status'], $r['order_date']);
}
$conn->close();
?>