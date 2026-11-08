<?php
include 'auth.php'; require_admin();
header('Content-Type: application/json; charset=utf-8');

function clean_date_ai($s) {
  $d = DateTime::createFromFormat('Y-m-d', $s);
  return ($d && $d->format('Y-m-d') === $s) ? $s : null;
}

$start = clean_date_ai($_GET['start'] ?? '');
$end = clean_date_ai($_GET['end'] ?? '');
if (!$start) $start = date('Y-m-d', strtotime('-6 days'));
if (!$end) $end = date('Y-m-d');

$_GET['start']=$start; $_GET['end']=$end;
ob_start();
include 'admin_dashboard_data.php';
$raw = ob_get_clean();
$data = $raw ? json_decode($raw, true) : null;
if (!$data) {
  echo json_encode(['insights'=>[], 'actions'=>[], 'ok'=>false]);
  exit;
}

$c = $data['current'];
$f = $data['forecast'];
$expenses = $data['expenses_total'] ?? 0;
$net = $data['net_profit'] ?? 0;
$ins = $data['insights'] ?? [];
$invLow = array_values(array_filter($data['inventory'] ?? [], fn($i)=>$i['status']==='Low'||$i['status']==='Critical'));
$invLowNames = implode(', ', array_map(fn($i)=>$i['name'], $invLow)) ?: 'none';

$prompt = "You are a business analyst for Imbo't Silog restaurant. Use ONLY the data. Return ONLY valid JSON: {\"insights\":[...],\"actions\":[{\"priority\":\"High|Medium|Low\",\"title\":\"...\",\"reason\":\"...\",\"action\":\"...\",\"result\":\"...\"}]}. No markdown.\n";
$prompt .= "Period: $start to $end\n";
$prompt .= "Sales: {$c['total_sales']}, Orders: {$c['total_orders']}, AOV: {$c['avg_order']}, Customers: {$c['customers']}\n";
$prompt .= "Top item: ".($c['top_item'] ?? 'none')." (".($c['top_qty'] ?? 0).")\n";
$prompt .= "Prev sales: {$data['previous']['total_sales']}, prev orders: {$data['previous']['total_orders']}\n";
$prompt .= "Forecast: sales {$f['sales']} ({$f['sales_min']}-{$f['sales_max']}), orders {$f['orders']}, peak {$f['peak_start']}:00-{$f['peak_end']}:00, top demand {$f['top_item']}\n";
$prompt .= "Expenses: $expenses, Net profit: $net\n";
$prompt .= "Low stock: $invLowNames\n";
$prompt .= "Base insights: ".implode(' | ', $ins)."\n";
$prompt .= "Max 5 insights, max 4 actions. Be concise.";

function call_model($model, $prompt, $timeout) {
  $payload = json_encode([
    'model' => $model,
    'prompt' => $prompt,
    'stream' => false,
    'options' => ['temperature' => 0.2]
  ]);
  $ch = curl_init('http://localhost:11434/api/generate');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
  $res = curl_exec($ch);
  $err = curl_error($ch);
  $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($res === false || $err || $http !== 200) return null;
  $j = json_decode($res, true);
  return $j['response'] ?? null;
}

// Use local model (phi4-mini:latest) as primary; cloud models (beam:cloud) require auth that doesn't persist
$text = call_model('phi4-mini:latest', $prompt, 60);
$source = 'phi4-mini';
if ($text === null) {
  $text = call_model('beam:cloud', $prompt, 10); // short timeout for cloud fallback
  if ($text !== null) $source = 'beam:cloud';
}

$out = ['insights'=>[], 'actions'=>[], 'ok'=>false];
if (is_string($text) && $text !== '') {
  if (preg_match('/\{.*\}/s', $text, $m)) {
    $parsed = json_decode($m[0], true);
    if (is_array($parsed)) {
      $ins = $parsed['insights'] ?? [];
      if (is_string($ins)) $ins = [$ins];
      if (is_array($ins)) $out['insights'] = array_values(array_filter(array_map('trim', $ins)));
      $acts = $parsed['actions'] ?? [];
      if (is_string($acts)) $acts = [$acts];
      if (is_array($acts)) $out['actions'] = array_values($acts);
      $out['ok'] = true;
    }
  }
  if (!$out['ok']) {
    $out['insights'] = array_values(array_filter(array_map('trim', explode("\n", $text))));
    $out['ok'] = true;
  }
}

echo json_encode($out, JSON_UNESCAPED_UNICODE);
