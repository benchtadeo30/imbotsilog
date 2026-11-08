<?php
include 'auth.php'; require_admin(); include 'silog.php';
if ($_SERVER['REQUEST_METHOD']!=='POST' || !verify_csrf()) { http_response_code(403); echo 'Invalid request.'; exit; }
$id=(int)($_POST['id']??0);
$stmt=$conn->prepare('SELECT item_name FROM inventory WHERE id=?'); $stmt->bind_param('i',$id); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc();
if ($row) {
  $name=$conn->real_escape_string($row['item_name']);
  $r=$conn->query("SELECT image_path FROM menu_items WHERE name='{$name}'");
  if ($x=$r->fetch_assoc()) @unlink($x['image_path']);
  $conn->query("DELETE FROM menu_items WHERE name='{$name}'");
  $conn->query("DELETE FROM inventory WHERE id={$id}");
}
header('Location: admin_inventory.php'); exit;
