<?php
header('Content-Type: application/json');
include 'auth.php'; require_admin(); include 'silog.php'; include_once 'order_items.php';

$conn = new mysqli('localhost','root','','imbotsilog_db');
if ($conn->connect_error) { echo json_encode(['error'=>'DB failed']); exit; }

// Daily aggregates for last 14 days
$days = 14;
$daily = [];
for ($i = $days-1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $daily[$d] = ['date'=>$d,'orders'=>0,'revenue'=>0.0];
}
$res = $conn->query("SELECT DATE(order_date) d, COUNT(*) c, COALESCE(SUM(CASE WHEN status='paid' THEN total_amount ELSE 0 END),0) r FROM orders WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL $days DAY) GROUP BY DATE(order_date)");
while ($r = $res->fetch_assoc()) {
    if (isset($daily[$r['d']])) { $daily[$r['d']]['orders']=(int)$r['c']; $daily[$r['d']]['revenue']=(float)$r['r']; }
}
$dailyArr = array_values($daily);

// Totals (all time)
$totRes = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(CASE WHEN status='paid' THEN total_amount ELSE 0 END),0) r FROM orders");
$totRow = $totRes->fetch_assoc();
$totalOrders = (int)$totRow['c'];
$totalRevenue = (float)$totRow['r'];

// Last 7 vs prev 7
$last7Rev = 0; $prev7Rev = 0; $last7Ord = 0; $prev7Ord = 0;
foreach ($dailyArr as $i => $d) {
    if ($i >= 7) { $last7Rev += $d['revenue']; $last7Ord += $d['orders']; }
    else { $prev7Rev += $d['revenue']; $prev7Ord += $d['orders']; }
}
$revChange = $prev7Rev > 0 ? (($last7Rev - $prev7Rev)/$prev7Rev)*100 : 0;
$ordChange = $prev7Ord > 0 ? (($last7Ord - $prev7Ord)/$prev7Ord)*100 : 0;

// Top items by qty sold (parse items JSON)
$itemQty = [];
$ir = $conn->query("SELECT items FROM orders WHERE status='paid'");
while ($row = $ir->fetch_assoc()) {
    foreach (order_items_list($row['items']) as $it) {
        $name = $it['name'];
        if ($name === '') continue;
        if (!isset($itemQty[$name])) $itemQty[$name]=0;
        $itemQty[$name] += (int)$it['qty'];
    }
}
arsort($itemQty);
$topItems = [];
$i=0;
foreach ($itemQty as $name => $q) { if ($i++>=8) break; $topItems[]=['name'=>$name,'qty'=>$q]; }

// Menu availability
$menuRes = $conn->query("SELECT COUNT(*) c FROM menu_items");
$menuTotal = (int)$menuRes->fetch_assoc()['c'];
$menuUnavailRes = $conn->query("SELECT COUNT(*) c FROM menu_items WHERE is_available=0");
$menuUnavail = (int)$menuUnavailRes->fetch_assoc()['c'];
$unavailNames = [];
$ur = $conn->query("SELECT name FROM menu_items WHERE is_available=0");
while ($u = $ur->fetch_assoc()) $unavailNames[] = $u['name'];

// Forecast next 7 days revenue using linear trend on last 7 days
$last7 = array_slice($dailyArr, 7, 7);
$revenues = array_column($last7, 'revenue');
$n = count($revenues);
$sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
for ($i=0; $i<$n; $i++) { $sumX += $i; $sumY += $revenues[$i]; $sumXY += $i*$revenues[$i]; $sumX2 += $i*$i; }
$denom = ($n*$sumX2 - $sumX*$sumX);
$slope = $denom != 0 ? ($n*$sumXY - $sumX*$sumY)/$denom : 0;
$intercept = $n>0 ? ($sumY - $slope*$sumX)/$n : 0;
$avgRev = $n>0 ? array_sum($revenues)/$n : 0;
$forecast = [];
$forecastTotal = 0;
for ($i=0; $i<7; $i++) {
    $x = $n + $i;
    $val = max(0, $intercept + $slope*$x);
    $forecast[] = ['day' => date('D M d', strtotime("+".($i+1)." days")), 'value' => round($val,2)];
    $forecastTotal += $val;
}
$forecastOrders = round(($last7Ord/7) * 7);

// Confidence: based on data points and variance
$variance = 0;
foreach ($revenues as $rv) { $variance += pow($rv - $avgRev, 2); }
$stdDev = $n>1 ? sqrt($variance/($n-1)) : 0;
$cv = $avgRev > 0 ? ($stdDev/$avgRev) : 1;
$confidence = max(30, min(95, round((1 - min($cv,1))*100)));

// Risks
$risks = [];
if ($revChange < -10) $risks[] = ['level'=>'high','text'=>'Revenue declined '.round(abs($revChange),1).'% in the last 7 days vs previous 7 days.'];
if ($menuUnavail > 0) $risks[] = ['level'=>'medium','text'=>$menuUnavail.' menu item(s) currently unavailable: '.implode(', ',$unavailNames).'.'];
if (count($dailyArr) < 7) $risks[] = ['level'=>'low','text'=>'Limited historical data (less than 7 days). Predictions may be unreliable.'];
if ($totalOrders < 20) $risks[] = ['level'=>'medium','text'=>'Low order volume ('.$totalOrders.' total). Forecasts have high uncertainty.'];
if (empty($risks)) $risks[] = ['level'=>'low','text'=>'No major risks detected based on current data.'];

// Opportunities
$opps = [];
if ($revChange > 10) $opps[] = 'Revenue grew '.round($revChange,1).'% — consider scaling top performers.';
if (!empty($topItems)) $opps[] = 'Top seller: '.$topItems[0]['name'].' ('.$topItems[0]['qty'].' sold). Promote or bundle it.';
if ($menuUnavail === 0) $opps[] = 'All menu items available — good availability.';
$opps[] = 'Forecasted revenue next 7 days: ₱'.number_format($forecastTotal,2).'.';

echo json_encode([
    'generated_at' => date('Y-m-d H:i:s'),
    'total_orders' => $totalOrders,
    'total_revenue' => round($totalRevenue,2),
    'last7_revenue' => round($last7Rev,2),
    'prev7_revenue' => round($prev7Rev,2),
    'rev_change_pct' => round($revChange,1),
    'last7_orders' => $last7Ord,
    'prev7_orders' => $prev7Ord,
    'ord_change_pct' => round($ordChange,1),
    'avg_daily_revenue' => round($avgRev,2),
    'daily' => $dailyArr,
    'top_items' => $topItems,
    'menu_total' => $menuTotal,
    'menu_unavailable' => $menuUnavail,
    'unavailable_names' => $unavailNames,
    'forecast' => $forecast,
    'forecast_total' => round($forecastTotal,2),
    'forecast_orders' => $forecastOrders,
    'confidence' => $confidence,
    'slope' => round($slope,2),
    'risks' => $risks,
    'opportunities' => $opps,
]);
$conn->close();
?>
