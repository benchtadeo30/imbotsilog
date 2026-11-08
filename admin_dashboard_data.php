<?php
include_once 'auth.php'; require_admin();
header('Content-Type: application/json; charset=utf-8');
include 'silog.php';
include_once 'order_items.php';

function clean_date($s) {
  $d = DateTime::createFromFormat('Y-m-d', $s);
  return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}

function q($conn, $sql) {
  $r = mysqli_query($conn, $sql);
  if (!$r) return [];
  $rows = [];
  while ($row = mysqli_fetch_assoc($r)) $rows[] = $row;
  return $rows;
}

$start = clean_date($_GET['start'] ?? '');
$end = clean_date($_GET['end'] ?? '');
if (!$start) $start = date('Y-m-d', strtotime('-6 days'));
if (!$end) $end = date('Y-m-d');
if ($start > $end) { $tmp = $start; $start = $end; $end = $tmp; }

// Previous equivalent period
$sD = new DateTime($start);
$eD = new DateTime($end);
$days = (int)$sD->diff($eD)->days + 1;
$prevEnd = (clone $sD)->modify('-1 day')->format('Y-m-d');
$prevStart = (clone $sD)->modify("-{$days} days")->format('Y-m-d');

function orders_in_range($conn, $start, $end) {
  $s = mysqli_real_escape_string($conn, $start);
  $e = mysqli_real_escape_string($conn, $end);
  return q($conn, "SELECT * FROM orders WHERE DATE(order_date) BETWEEN '$s' AND '$e' ORDER BY order_date ASC");
}

$orders = orders_in_range($conn, $start, $end);
$prevOrders = orders_in_range($conn, $prevStart, $prevEnd);

function summarize_orders($orders) {
  $summary = [
    'total_sales' => 0.0,
    'total_orders' => count($orders),
    'paid_orders' => 0,
    'completed' => 0,
    'pending' => 0,
    'cancelled' => 0,
    'customers' => [],
    'items_qty' => [],
    'item_revenue' => [],
    'hourly' => array_fill(0,24,0),
    'daily' => [],
    'status_counts' => ['Paid'=>0,'Preparing'=>0,'On the way'=>0,'Pending'=>0,'Cancelled'=>0],
  ];
  foreach ($orders as $o) {
    $total = (float)$o['total_amount'];
    $st = strtolower(trim($o['status'] ?? 'pending'));
    $isPaid = ($st === 'paid');  // Only "paid" counts as revenue
    if ($isPaid) {
      $summary['total_sales'] += $total;
    }
    if ($st === 'paid') { $summary['completed']++; $summary['paid_orders']++; $summary['status_counts']['Paid']++; }
    elseif ($st === 'preparing') { $summary['pending']++; $summary['status_counts']['Preparing']++; }
    elseif ($st === 'on the way' || $st === 'on_the_way') { $summary['pending']++; $summary['status_counts']['On the way']++; }
    elseif ($st === 'cancelled') { $summary['cancelled']++; $summary['status_counts']['Cancelled']++; }
    else { $summary['pending']++; $summary['status_counts']['Pending']++; }

    if (!empty($o['customer_name'])) $summary['customers'][$o['customer_name']] = true;

    if (!empty($o['order_date']) && $isPaid) {
      $dt = new DateTime($o['order_date']);
      $h = (int)$dt->format('G');
      $summary['hourly'][$h] += $total;
      $day = $dt->format('Y-m-d');
      if (!isset($summary['daily'][$day])) $summary['daily'][$day] = ['sales'=>0,'orders'=>0];
      $summary['daily'][$day]['sales'] += $total;
      $summary['daily'][$day]['orders']++;
    } elseif (!empty($o['order_date'])) {
      // still track order counts per day for pending/cancelled? optional; skip to keep revenue-only
    }

    foreach (order_items_list($o['items'] ?? '') as $it) {
      $name = $it['name'];
      $qty = (int)$it['qty'];
      $price = (float)$it['price'];
      if ($name === '') continue;
      if ($isPaid) {
        $summary['items_qty'][$name] = ($summary['items_qty'][$name] ?? 0) + $qty;
        $summary['item_revenue'][$name] = ($summary['item_revenue'][$name] ?? 0) + ($qty * $price);
      }
    }
  }
  $summary['avg_order'] = ($summary['paid_orders'] ?? 0) > 0 ? $summary['total_sales'] / $summary['paid_orders'] : 0;
  $summary['customers_served'] = count($summary['customers']);
  return $summary;
}

$cur = summarize_orders($orders);
$prev = summarize_orders($prevOrders);

// All historical orders for forecasting
$allOrders = q($conn, "SELECT * FROM orders ORDER BY order_date ASC");
$allSummary = summarize_orders($allOrders);

// Daily sales series across all history for trend
$allDaily = $allSummary['daily'];
ksort($allDaily);
$dailySalesVals = array_values(array_map(fn($d)=>$d['sales'], $allDaily));
$nDays = count($dailySalesVals);
$meanDaily = $nDays ? array_sum($dailySalesVals)/$nDays : 0;
$variance = 0;
foreach ($dailySalesVals as $v) $variance += pow($v - $meanDaily, 2);
$stdDaily = $nDays > 1 ? sqrt($variance / ($nDays-1)) : 0;

// Day-of-week pattern (average sales by weekday 0=Sun)
$byWeekday = array_fill(0,7,[]);
foreach ($allDaily as $date => $d) {
  $w = (int)date('w', strtotime($date));
  $byWeekday[$w][] = $d['sales'];
}
$weekdayAvg = [];
for ($i=0;$i<7;$i++) $weekdayAvg[$i] = count($byWeekday[$i]) ? array_sum($byWeekday[$i])/count($byWeekday[$i]) : $meanDaily;

// Tomorrow forecast based on weekday of tomorrow + recent trend
$tomorrow = new DateTime('tomorrow');
$tw = (int)$tomorrow->format('w');
$base = $weekdayAvg[$tw] ?: $meanDaily;
// recent 7 day avg
$recent7 = array_slice($dailySalesVals, -7);
$recentAvg = count($recent7) ? array_sum($recent7)/count($recent7) : $meanDaily;
$forecastSales = round(($base * 0.6 + $recentAvg * 0.4), 2);
$forecastMin = round(max(0, $forecastSales - $stdDaily), 2);
$forecastMax = round($forecastSales + $stdDaily, 2);

// Orders forecast
$dailyOrdersVals = array_values(array_map(fn($d)=>$d['orders'], $allDaily));
$meanOrders = count($dailyOrdersVals) ? array_sum($dailyOrdersVals)/count($dailyOrdersVals) : 0;
$stdOrders = 0;
if (count($dailyOrdersVals) > 1) {
  $v=0; foreach($dailyOrdersVals as $x) $v+=pow($x-$meanOrders,2);
  $stdOrders = sqrt($v/(count($dailyOrdersVals)-1));
}
$forecastOrders = round($meanOrders);
$forecastOrdersMin = max(0, (int)round($meanOrders - $stdOrders));
$forecastOrdersMax = (int)round($meanOrders + $stdOrders);

// Peak historical hour (by order count across all history? we only have hourly sales; use order counts by hour)
$hourCounts = array_fill(0,24,0);
foreach ($allOrders as $o) {
  if (!empty($o['order_date'])) {
    $h = (int)date('G', strtotime($o['order_date']));
    $hourCounts[$h]++;
  }
}
$peakHour = null; $maxHc=0;
for ($h=0;$h<24;$h++) { if ($hourCounts[$h]>$maxHc){$maxHc=$hourCounts[$h];$peakHour=$h;} }
$peakStart = $peakHour !== null ? $peakHour : 8;
$peakEnd = ($peakStart + 2) % 24;

// Top item historical
arsort($allSummary['items_qty']);
$topItemName = array_key_first($allSummary['items_qty']) ?: '';
$topItemQty = $topItemName ? $allSummary['items_qty'][$topItemName] : 0;

// Confidence based on data amount & stability
$cv = $meanDaily > 0 ? ($stdDaily / $meanDaily) : 1;
if ($nDays >= 14 && $cv < 0.25) $confidence = 'High';
elseif ($nDays >= 7 && $cv < 0.45) $confidence = 'Medium';
else $confidence = 'Low';

// Inventory consumption estimate (per day) using all historical orders
$itemDailyConsumption = [];
foreach ($allSummary['items_qty'] as $name => $qty) {
  $itemDailyConsumption[$name] = $nDays > 0 ? ($qty / $nDays) : 0;
}
// map to inventory if name matches case-insensitive
$invRows = q($conn, "SELECT * FROM inventory ORDER BY item_name ASC");
$inventoryOut = [];
foreach ($invRows as $inv) {
  $name = $inv['item_name'];
  $stock = (int)$inv['current_stock'];
  $reorder = (int)$inv['reorder_level'];
  $unit = $inv['unit'];
  // find consumption
  $cons = 0;
  foreach ($itemDailyConsumption as $iname => $c) {
    if (strcasecmp($iname, $name) === 0) { $cons = $c; break; }
  }
  // fallback: try partial match
  if ($cons == 0) {
    foreach ($itemDailyConsumption as $iname => $c) {
      if (stripos($iname, $name) !== false || stripos($name, $iname) !== false) { $cons = $c; break; }
    }
  }
  $daysRem = $cons > 0 ? round($stock / $cons, 1) : null;
  if ($daysRem === null) $status = 'Unknown';
  elseif ($daysRem <= 1.5) $status = 'Critical';
  elseif ($daysRem <= 3) $status = 'Low';
  else $status = 'Healthy';
  // predicted 3 day consumption
  $pred3 = round($cons * 3, 1);
  $shortage = $pred3 > $stock;
  $recommended = $shortage ? ceil($pred3 - $stock + $reorder) : 0;
  $inventoryOut[] = [
    'name'=>$name,
    'category'=>$inv['category'],
    'stock'=>$stock,
    'unit'=>$unit,
    'reorder'=>$reorder,
    'daily_consumption'=>round($cons,2),
    'days_remaining'=>$daysRem,
    'status'=>$status,
    'pred_3day'=>$pred3,
    'shortage'=>$shortage,
    'recommended_restock'=>$recommended
  ];
}

// Top/low menu performance for selected period
$menuRows = [];
foreach ($cur['items_qty'] as $name => $qty) {
  $rev = $cur['item_revenue'][$name] ?? 0;
  // previous
  $prevQty = $prev['items_qty'][$name] ?? 0;
  $change = $prevQty > 0 ? round((($qty-$prevQty)/$prevQty)*100, 1) : null;
  $menuRows[] = ['name'=>$name,'qty'=>$qty,'revenue'=>round($rev,2),'change'=>$change];
}
usort($menuRows, fn($a,$b)=>$b['qty']<=>$a['qty']);
$topMenu = array_slice($menuRows,0,6);
$lowMenu = array_slice(array_reverse($menuRows),0,5);

// Peak ordering hours chart (selected period order counts by hour)
$hourlyOrders = array_fill(0,24,0);
foreach ($orders as $o) {
  if (!empty($o['order_date'])) {
    $hourlyOrders[(int)date('G', strtotime($o['order_date']))]++;
  }
}
// top 3 peak hours
$peakHoursList = [];
$ar = $hourlyOrders; arsort($ar);
$i=0;
foreach ($ar as $h=>$c) {
  if ($i>=3) break;
  if ($c>0) $peakHoursList[] = ['hour'=>$h,'orders'=>$c];
  $i++;
}

// Expenses for the selected period
$expensesTotal = 0.0;
$expRes = $conn->query("SELECT COALESCE(SUM(amount),0) AS t FROM expenses WHERE expense_date BETWEEN '" . $conn->real_escape_string($start) . "' AND '" . $conn->real_escape_string($end) . "'");
if ($expRes) { $expensesTotal = (float)$expRes->fetch_assoc()['t']; }
$netProfit = $cur['total_sales'] - $expensesTotal;

// Per-day expenses aligned to trend range
$expensesDaily = [];
$edr = $conn->query("SELECT expense_date, COALESCE(SUM(amount),0) AS t FROM expenses WHERE expense_date BETWEEN '" . $conn->real_escape_string($start) . "' AND '" . $conn->real_escape_string($end) . "' GROUP BY expense_date");
if ($edr) { while ($er = $edr->fetch_assoc()) { $expensesDaily[$er['expense_date']] = (float)$er['t']; } }

$insights = [];
$prevSales = $prev['total_sales'];
if ($prev['total_orders'] > 0 || $cur['total_orders'] > 0) {
  if ($prevSales > 0) {
    $chg = (($cur['total_sales']-$prevSales)/$prevSales)*100;
    $dir = $chg >= 0 ? 'increased' : 'decreased';
    $insights[] = 'Sales ' . $dir . ' by ' . number_format(abs($chg),1) . '% compared with the previous equivalent period.';
  }
  if (!empty($cur['items_qty'])) {
    arsort($cur['items_qty']);
    $top = array_key_first($cur['items_qty']);
    $topQty = $cur['items_qty'][$top];
    $pct = $cur['total_orders']>0 ? round(($topQty/array_sum($cur['items_qty']))*100,1) : 0;
    $insights[] = $top . ' was the top-selling item (' . $topQty . ' servings, ' . $pct . '% of ordered items).';
  }
  if (count($peakHoursList) > 0) {
    $ph = $peakHoursList[0]['hour'];
    $next = ($ph + 1) % 24;
    $ordersInPeak = 0;
    foreach ($peakHoursList as $p) $ordersInPeak += $p['orders'];
    $pctPeak = $cur['total_orders']>0 ? round($ordersInPeak/$cur['total_orders']*100,1) : 0;
    $insights[] = 'Peak ordering hour: ' . sprintf('%02d:00', $ph) . ' with ' . $peakHoursList[0]['orders'] . ' orders (' . $pctPeak . '% of period orders).';
  }
  // low stock count
  $lowStock = array_filter($inventoryOut, fn($i)=>$i['status']==='Low'||$i['status']==='Critical');
  if (count($lowStock) > 0) {
    $insights[] = count($lowStock) . ' inventory item(s) are at low or critical stock levels.';
  }
  if ($cur['total_orders'] === 0) {
    $insights[] = 'No business records were found for the selected period.';
  }
}

// Financial insight from expenses
if ($expensesTotal > 0) {
  if ($netProfit >= 0) {
    $insights[] = 'Profitable period: net profit ₱' . number_format($netProfit,2) . ' (revenue ₱' . number_format($cur['total_sales'],2) . ' − expenses ₱' . number_format($expensesTotal,2) . ').';
  } else {
    $insights[] = '⚠️ Financial loss: expenses exceeded revenue by ₱' . number_format(abs($netProfit),2) . ' (revenue ₱' . number_format($cur['total_sales'],2) . ', expenses ₱' . number_format($expensesTotal,2) . '). Risk of loss.';
  }
} elseif ($cur['total_sales'] > 0) {
  $insights[] = 'Revenue recorded: ₱' . number_format($cur['total_sales'],2) . ' with no expenses logged.';
}

// Recommended actions
$actions = [];
// Financial action based on profit/loss
if ($expensesTotal > 0 && $netProfit < 0) {
  $actions[] = [
    'priority' => 'High',
    'title' => 'Reduce costs to avoid daily loss',
    'reason' => 'Expenses (₱' . number_format($expensesTotal,2) . ') exceeded revenue (₱' . number_format($cur['total_sales'],2) . ') by ₱' . number_format(abs($netProfit),2) . '.',
    'action' => 'Review and cut non-essential expenses, renegotiate supplier rates, and reduce waste.',
    'result' => 'Return to profitability.',
    'confidence' => $confidence
  ];
} elseif ($expensesTotal > 0 && $netProfit >= 0) {
  $actions[] = [
    'priority' => 'Low',
    'title' => 'Reinvest surplus profit',
    'reason' => 'Net profit of ₱' . number_format($netProfit,2) . ' recorded after expenses.',
    'action' => 'Consider reinvesting into marketing or stock to grow revenue.',
    'result' => 'Sustainable business growth.',
    'confidence' => $confidence
  ];
}

// restock first critical/low
foreach ($inventoryOut as $inv) {
  if ($inv['shortage']) {
    $actions[] = [
      'priority' => $inv['status']==='Critical' ? 'High' : 'High',
      'title' => 'Restock ' . $inv['name'],
      'reason' => 'Current stock (' . $inv['stock'] . ' ' . $inv['unit'] . ') is below the estimated 3-day consumption (' . $inv['pred_3day'] . ' ' . $inv['unit'] . ').',
      'action' => 'Purchase at least ' . $inv['recommended_restock'] . ' ' . $inv['unit'] . '.',
      'result' => 'Reduce risk of unavailable menu items.',
      'confidence' => $confidence
    ];
  }
}
// peak staffing
if ($peakHour !== null && $maxHc > 0) {
  $actions[] = [
    'priority' => 'Medium',
    'title' => 'Prepare kitchen and staff before peak hour',
    'reason' => 'Order volume is highest around ' . sprintf('%02d:00', $peakStart) . '.',
    'action' => 'Ensure additional staff and ingredient prep before the peak period.',
    'result' => 'Faster service and fewer delays.',
    'confidence' => $confidence
  ];
}
// top item prep
if ($topItemName) {
  $actions[] = [
    'priority' => 'Medium',
    'title' => 'Increase preparation of ' . $topItemName,
    'reason' => $topItemName . ' is the top-selling item across available history.',
    'action' => 'Prepare additional servings ahead of peak hours.',
    'result' => 'Lower risk of stock shortage for high-demand item.',
    'confidence' => $confidence
  ];
}
$actions = array_slice($actions, 0, 5);

// Menu items stock/status list (for dashboard)
$menuListOut = [];
$ml = $conn->query("SELECT name, category, stock, is_available FROM menu_items ORDER BY name ASC");
while ($ml && ($m=$ml->fetch_assoc())) {
  $menuListOut[] = [
    'name' => $m['name'],
    'category' => $m['category'],
    'stock' => (int)$m['stock'],
    'status' => ((int)$m['is_available']===1) ? 'Available' : 'Unavailable',
  ];
}

// Sales trend output labels
$spanDays = $days;
if ($spanDays <= 1) {
  $trendLabels = [];
  $trendValues = [];
  $trendExpenses = [];
  for ($h=0;$h<24;$h++) {
    $trendLabels[] = sprintf('%02d:00', $h);
    // hourly sales not separated; derive from orders? We'll provide daily sales only, but include hourly revenue collected
    $trendValues[] = round($cur['hourly'][$h], 2);
    $trendExpenses[] = 0;
  }
} else {
  $trendLabels = [];
  $trendValues = [];
  $trendExpenses = [];
  // ensure all dates in range
  $cursor = new DateTime($start);
  while ($cursor->format('Y-m-d') <= $end) {
    $d = $cursor->format('Y-m-d');
    $trendLabels[] = $cursor->format('M d');
    $trendValues[] = isset($cur['daily'][$d]) ? round($cur['daily'][$d]['sales'],2) : 0;
    $trendExpenses[] = isset($expensesDaily[$d]) ? round($expensesDaily[$d],2) : 0;
    $cursor->modify('+1 day');
  }
}

// Orders trend (same labels) with completed/pending/cancelled per day
$ordersTrendLabels = [];
$ordersCompleted = [];
$ordersPending = [];
$ordersCancelled = [];
if ($spanDays <= 1) {
  $ordersTrendLabels = $trendLabels;
  // not split hourly by status; leave daily counts aggregated? We'll provide single day totals in arrays
  $ordersCompleted[] = $cur['completed'];
  $ordersPending[] = $cur['pending'];
  $ordersCancelled[] = $cur['cancelled'];
} else {
  for ($i=0;$i<count($trendLabels);$i++) {
    $ordersCompleted[] = null;
  }
  // Simpler: provide daily totals via status counts not per-day; keep aggregate
  $ordersTrendLabels = $trendLabels;
  $ordersCompleted = array_fill(0, count($trendLabels), 0);
  $ordersPending = array_fill(0, count($trendLabels), 0);
  $ordersCancelled = array_fill(0, count($trendLabels), 0);
  $cursor = new DateTime($start);
  $i=0;
  while ($cursor->format('Y-m-d') <= $end) {
    $d = $cursor->format('Y-m-d');
    // count statuses per day by scanning orders
    foreach ($orders as $o) {
      if ($o['order_date'] && date('Y-m-d', strtotime($o['order_date'])) === $d) {
        $st = strtolower($o['status'] ?? 'pending');
        if ($st === 'completed') $ordersCompleted[$i]++;
        elseif ($st === 'cancelled') $ordersCancelled[$i]++;
        else $ordersPending[$i]++;
      }
    }
    $cursor->modify('+1 day'); $i++;
  }
}

$out = [
  'range' => ['start'=>$start,'end'=>$end,'days'=>$days,'prev_start'=>$prevStart,'prev_end'=>$prevEnd],
  'current' => [
    'total_sales'=>$cur['total_sales'],
    'total_orders'=>$cur['total_orders'],
    'avg_order'=>$cur['avg_order'],
    'customers'=>$cur['customers_served'],
    'completed'=>$cur['completed'],
    'pending'=>$cur['pending'],
    'cancelled'=>$cur['cancelled'],
    'top_item'=> !empty($cur['items_qty']) ? (array_key_first($cur['items_qty'])) : null,
    'status_counts'=>$cur['status_counts'],
  ],
  'previous' => [
    'total_sales'=>$prev['total_sales'],
    'total_orders'=>$prev['total_orders'],
    'avg_order'=>$prev['avg_order'],
  ],
  'charts' => [
    'trend' => ['labels'=>$trendLabels,'values'=>$trendValues,'expenses'=>$trendExpenses],
    'orders' => ['labels'=>$ordersTrendLabels,'completed'=>$ordersCompleted,'pending'=>$ordersPending,'cancelled'=>$ordersCancelled],
    'status' => $cur['status_counts'],
    'menu' => $topMenu,
    'peak_hours' => ['labels'=>array_map(fn($x)=>sprintf('%02d:00',$x), range(0,23)), 'values'=>$hourlyOrders],
  ],
  'peak_hours_list' => $peakHoursList,
  'inventory' => $inventoryOut,
  'menu_list' => $menuListOut,
  'expenses_total' => $expensesTotal,
  'net_profit' => $netProfit,
  'menu_low' => $lowMenu,
  'forecast' => [
    'sales' => $forecastSales,
    'sales_min' => $forecastMin,
    'sales_max' => $forecastMax,
    'orders' => $forecastOrders,
    'orders_min' => $forecastOrdersMin,
    'orders_max' => $forecastOrdersMax,
    'peak_start' => $peakStart,
    'peak_end' => $peakEnd,
    'top_item' => $topItemName,
    'confidence' => $confidence,
  ],
  'insights' => $insights,
  'actions' => $actions,
  'has_data' => count($orders) > 0,
];

echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
