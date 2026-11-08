<?php include 'silog.php'; include 'order_items.php';
$id = (int)($_GET['id'] ?? 0);
$stmt = $conn->prepare("SELECT * FROM orders WHERE id=? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
if (!$row) { http_response_code(404); echo 'Order not found.'; exit; }
$items = order_items_list($row['items']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt #<?php echo (int)$row['id']; ?></title>
  <style>
    :root { --w: 320px; }
    body { font-family: "Courier New", monospace; margin: 0; padding: 16px; background:#f2f2f2; }
    .receipt {
      width: var(--w); margin: 0 auto; background:#fff; padding:18px 16px;
      box-shadow: 0 4px 14px rgba(0,0,0,.15); border-radius:8px;
    }
    .center { text-align:center; }
    .brand { font-weight:bold; font-size:18px; margin:0; }
    .sub { font-size:12px; color:#555; margin:2px 0 10px; }
    .divider { border-top:1px dashed #aaa; margin:10px 0; }
    .meta { font-size:12px; line-height:1.6; }
    .meta b { display:inline-block; width:88px; }
    .queue { font-size:26px; font-weight:bold; letter-spacing:2px; margin:8px 0; }
    table { width:100%; border-collapse:collapse; margin-top:6px; font-size:12px; }
    th, td { padding:4px 0; text-align:left; }
    th { border-bottom:1px dashed #aaa; font-weight:bold; }
    .qty { text-align:center; width:34px; }
    .price { text-align:right; width:70px; }
    .totrow td { font-weight:bold; border-top:1px dashed #aaa; padding-top:8px; }
    .totrow .price { font-size:14px; }
    .thanks { text-align:center; margin-top:14px; font-size:12px; color:#555; }
    .actions { text-align:center; margin-top:16px; }
    .btn { padding:8px 16px; border:1px solid #999; background:#fff; cursor:pointer; border-radius:6px; margin:0 4px; }
    @media print {
      body { background:#fff; padding:0; }
      .receipt { box-shadow:none; border-radius:0; width:100%; }
      .actions { display:none; }
    }
  </style>
</head>
<body>
  <div class="receipt">
    <div class="center">
      <p class="brand">IMBO'T SILOG</p>
      <p class="sub">Bawat sandok umausok, kain na!</p>
    </div>
    <div class="divider"></div>
    <div class="center queue">QUEUE #<?php echo (int)$row['queue_number']; ?></div>
    <div class="meta">
      <div><b>Order ID:</b> <?php echo (int)$row['id']; ?></div>
      <div><b>Customer:</b> <?php echo htmlspecialchars($row['customer_name']); ?></div>
      <div><b>Address:</b> <?php echo htmlspecialchars($row['address']); ?></div>
      <div><b>Date/Time:</b> <?php echo date('M d, Y h:i A', strtotime($row['order_date'])); ?></div>
      <div><b>Payment:</b> <?php echo htmlspecialchars($row['payment_method']); ?></div>
      <div><b>Status:</b> <?php echo htmlspecialchars(ucfirst($row['status'])); ?></div>
    </div>
    <div class="divider"></div>
    <table>
      <thead>
        <tr>
          <th>Item</th>
          <th class="qty">Qty</th>
          <th class="price">Price</th>
          <th class="price">Line</th>
        </tr>
      </thead>
      <tbody>
        <?php $grand=0; foreach($items as $it):
          $qty=(int)($it['qty']??0); $price=(float)($it['price']??0); $line=$qty*$price; $grand+=$line; ?>
          <tr>
            <td><?php echo htmlspecialchars($it['name']??''); ?></td>
            <td class="qty"><?php echo $qty; ?></td>
            <td class="price"><?php echo number_format($price,2); ?></td>
            <td class="price"><?php echo number_format($line,2); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr class="totrow">
          <td colspan="3">TOTAL</td>
          <td class="price">₱<?php echo number_format((float)$row['total_amount'],2); ?></td>
        </tr>
      </tfoot>
    </table>
    <div class="divider"></div>
    <div class="thanks">
      Salamat sa pag-order! / Thank you!<br>
      Kunin ang resibo para sa reference.
    </div>
    <div class="actions">
      <button onclick="window.print()">Print</button>
      <button onclick="window.close()">Close</button>
    </div>
  </div>
  <script> setTimeout(()=>window.print(), 300); </script>
</body>
</html>
