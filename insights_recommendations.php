<?php
header('Content-Type: application/json');
include 'auth.php'; require_admin(); include 'silog.php';
$conn = new mysqli('localhost','root','','imbotsilog_db');
if ($conn->connect_error) { echo json_encode(['error'=>'DB failed']); exit; }

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? ($_GET['action'] ?? 'list');

if ($action === 'list') {
    $rows = [];
    $r = $conn->query("SELECT * FROM ai_recommendations ORDER BY created_at DESC LIMIT 50");
    while ($row = $r->fetch_assoc()) $rows[] = $row;
    echo json_encode(['recommendations'=>$rows]);
    exit;
}

if ($action === 'update') {
    $id = (int)($input['id'] ?? 0);
    $status = $input['status'] ?? '';
    if (!in_array($status, ['pending','accepted','rejected','implemented'])) { echo json_encode(['error'=>'bad status']); exit; }
    $implemented = $status==='implemented' ? ", implemented_at=NOW()" : "";
    $conn->query("UPDATE ai_recommendations SET status='".$conn->real_escape_string($status)."'$implemented WHERE id=$id");
    echo json_encode(['ok'=>true]);
    exit;
}

if ($action === 'generate') {
    // Build recommendations from current data
    $tot = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(total_amount),0) r FROM orders")->fetch_assoc();
    $top = [];
    $ir = $conn->query("SELECT items FROM orders");
    $itemQty=[];
    while($row=$ir->fetch_assoc()){ $its=json_decode($row['items'],true); if(is_array($its)) foreach($its as $it){ $n=$it['name']??'Unknown'; $itemQty[$n]=($itemQty[$n]??0)+(int)($it['qty']??0);} }
    arsort($itemQty); $i=0; $topName=''; $topQty=0;
    foreach($itemQty as $n=>$q){ if($i++==0){$topName=$n;$topQty=$q;} }

    // last 7 vs prev 7 revenue
    $daily=[]; for($i=13;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i days"));$daily[$d]=0;}
    $r=$conn->query("SELECT DATE(order_date) d, COALESCE(SUM(total_amount),0) r FROM orders WHERE order_date>=DATE_SUB(CURDATE(),INTERVAL 14 DAY) GROUP BY DATE(order_date)");
    while($row=$r->fetch_assoc()) if(isset($daily[$row['d']])) $daily[$row['d']]=(float)$row['r'];
    $arr=array_values($daily);
    $last7=array_sum(array_slice($arr,7,7)); $prev7=array_sum(array_slice($arr,0,7));
    $revChange = $prev7>0 ? (($last7-$prev7)/$prev7)*100 : 0;

    $unavail=[]; $ur=$conn->query("SELECT name FROM menu_items WHERE is_available=0"); while($u=$ur->fetch_assoc()) $unavail[]=$u['name'];

    $recs = [];
    // Rec 1: revenue trend
    if ($revChange < -5) {
        $recs[] = ['title'=>'Address declining revenue','priority'=>'High','reason'=>'Revenue declined '.round(abs($revChange),1).'% in the last 7 days.','data_support'=>'Last 7 days: ₱'.number_format($last7,2).' vs previous 7 days: ₱'.number_format($prev7,2).'.','expected_benefit'=>'Stabilize and recover revenue.','risks'=>'May require promotions/discounts.','steps'=>'1. Review top items.\n2. Run a promotion on best sellers.\n3. Check for stock/availability issues.'];
    } elseif ($revChange > 5) {
        $recs[] = ['title'=>'Scale winning products','priority'=>'Medium','reason'=>'Revenue grew '.round($revChange,1).'% recently.','data_support'=>'Last 7 days: ₱'.number_format($last7,2).'.','expected_benefit'=>'Increase revenue further.','risks'=>'Overstocking.','steps'=>'1. Promote top seller '.$topName.'.\n2. Ensure stock availability.\n3. Consider upselling.'];
    }
    // Rec 2: unavailable items
    if (count($unavail)>0) {
        $recs[] = ['title'=>'Restore unavailable menu items','priority'=>'High','reason'=>count($unavail).' item(s) are currently unavailable.','data_support'=>'Unavailable: '.implode(', ',$unavail).'.','expected_benefit'=>'Recover lost sales.','risks'=>'Ingredient sourcing.','steps'=>'1. Check ingredient supply.\n2. Re-enable items in availability page.\n3. Notify customers.'];
    }
    // Rec 3: top seller promotion
    if ($topName) {
        $recs[] = ['title'=>'Promote top seller: '.$topName,'priority'=>'Low','reason'=>$topName.' is the best-selling item ('.$topQty.' sold).','data_support'=>'Top item by quantity sold across all orders.','expected_benefit'=>'Boost revenue with proven product.','risks'=>'Demand may exceed supply.','steps'=>'1. Feature '.$topName.' on homepage.\n2. Create a bundle.\n3. Ensure ingredients stocked.'];
    }
    // Rec 4: data collection
    if ($tot['c'] < 30) {
        $recs[] = ['title'=>'Collect more sales data','priority'=>'Medium','reason'=>'Only '.$tot['c'].' orders recorded. Predictions have high uncertainty.','data_support'=>'Total orders: '.$tot['c'].'.','expected_benefit'=>'Improve forecast accuracy.','risks'=>'None.','steps'=>'1. Continue operating.\n2. Re-run insights after more orders.'];
    }

    $ins = $conn->prepare("INSERT INTO ai_recommendations (title,priority,reason,data_support,expected_benefit,risks,steps) VALUES (?,?,?,?,?,?,?)");
    foreach ($recs as $r) {
        $ins->bind_param('sssssss',$r['title'],$r['priority'],$r['reason'],$r['data_support'],$r['expected_benefit'],$r['risks'],$r['steps']);
        $ins->execute();
    }
    echo json_encode(['generated'=>count($recs)]);
    exit;
}

echo json_encode(['error'=>'unknown action']);
$conn->close();
?>
