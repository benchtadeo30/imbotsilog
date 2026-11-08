<?php
header('Content-Type: application/json');
include 'auth.php'; require_admin(); include 'silog.php'; include_once 'order_items.php';

$input = json_decode(file_get_contents('php://input'), true);
$question = trim($input['question'] ?? '');
if ($question === '') { echo json_encode(['error'=>'Empty question']); exit; }

// Gather business context
$conn = new mysqli('localhost','root','','imbotsilog_db');
$tot = $conn->query("SELECT COUNT(*) c, COALESCE(SUM(CASE WHEN status='paid' THEN total_amount ELSE 0 END),0) r FROM orders")->fetch_assoc();
$top = [];
$ir = $conn->query("SELECT items FROM orders WHERE status='paid'");
$itemQty=[];
while($row=$ir->fetch_assoc()){ foreach(order_items_list($row['items']) as $it){ $n=$it['name']; if($n==='') continue; $itemQty[$n]=($itemQty[$n]??0)+(int)$it['qty']; } }
arsort($itemQty); $i=0;
foreach($itemQty as $n=>$q){ if($i++>=5) break; $top[]="$n ($q sold)"; }
$unavail=[]; $ur=$conn->query("SELECT name FROM menu_items WHERE is_available=0"); while($u=$ur->fetch_assoc()) $unavail[]=$u['name'];
$menuTotal=(int)$conn->query("SELECT COUNT(*) c FROM menu_items")->fetch_assoc()['c'];

$context = "Business: Imbo't Silog restaurant.\n";
$context .= "Total orders: ".$tot['c'].". Total revenue (paid): ₱".number_format((float)$tot['r'],2).".\n";
$context .= "Menu items: $menuTotal. Unavailable: ".count($unavail).($unavail? " (".implode(', ',$unavail).")":" (none)").".\n";
$context .= "Top selling items: ".implode(', ',$top).".\n";

$prompt = "You are an AI business advisor for a small restaurant (Imbo't Silog). Use the provided business data and insights. Answer the admin's question concisely, with data-backed recommendations. Do not fabricate data.\n\n".$context."\n\nAdmin question: ".$question."\n\nProvide: (1) direct answer, (2) supporting data, (3) recommended actions, (4) risks/caveats. Keep under 250 words.";

function call_model($model, $prompt, $timeout) {
  $payload = json_encode(['model'=>$model,'prompt'=>$prompt,'stream'=>false]);
  $ch = curl_init('http://localhost:11434/api/generate');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
  $resp = curl_exec($ch);
  $err = curl_error($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($err || !$resp || $http !== 200) return null;
  $j = json_decode($resp, true);
  return $j['response'] ?? null;
}

// Use local model (phi4-mini:latest) as primary; cloud models require auth that doesn't persist
$text = call_model('phi4-mini:latest', $prompt, 60);
$source = 'phi4-mini';
if ($text === null) {
  $text = call_model('beam:cloud', $prompt, 10); // short timeout for cloud fallback
  if ($text !== null) $source = 'beam:cloud';
}

if (is_string($text) && $text !== '') {
  echo json_encode(['answer'=>$text,'source'=>$source]);
  $conn->close();
  exit;
}

// Final rule-based fallback
$ans = "AI service unavailable. Based on data: ".$tot['c']." orders, ₱".number_format((float)$tot['r'],2)." revenue. Top item: ".($top[0]??'n/a').". Unavailable items: ".(count($unavail)?implode(', ',$unavail):'none').".";
echo json_encode(['answer'=>$ans,'source'=>'fallback']);
$conn->close();
?>
