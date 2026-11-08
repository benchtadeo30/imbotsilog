<?php
include 'auth.php'; require_admin(); include 'silog.php';
if ($_SERVER['REQUEST_METHOD']!=='POST' || !verify_csrf()) { http_response_code(403); echo 'Invalid request.'; exit; }
$id=(int)($_POST['id']??0);
$stmt=$conn->prepare('SELECT item_name FROM inventory WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc();
if (!$row) { header('Location: admin_inventory.php'); exit; }
$name=$row['item_name'];
$conn->query("UPDATE menu_items SET is_available=IF(is_available=1,0,1) WHERE name='".$conn->real_escape_string($name)."'");
header('Location: admin_inventory.php'); exit;
