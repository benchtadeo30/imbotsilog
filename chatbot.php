<?php
header('Content-Type: application/json');
// Customer-service AI chatbot for Imbo't Silog — uses local Ollama (phi4-mini:latest) with NLP fallback
$conn = new mysqli('localhost','root','','imbotsilog_db');
$input = json_decode(file_get_contents('php://input'), true);
$q = trim($input['message'] ?? $input['question'] ?? '');
if ($q === '') { echo json_encode(['answer'=>'Please type a question.','intent'=>'empty']); exit; }

$lower = strtolower($q);

// Load menu for context (used by NLP fallback & to inject into AI prompt)
$menu = [];
$mr = $conn->query("SELECT name, category, price, is_available FROM menu_items ORDER BY category, name");
while ($mr && $m = $mr->fetch_assoc()) $menu[] = $m;

$normalize = fn($s) => strtolower(trim($s));
$nameMap = [];
foreach ($menu as $m) { $nameMap[strtolower($m['name'])] = $m; }

// --- Try local Ollama first ---
function call_ollama($question, $menu, $conn) {
    $menuContext = '';
    foreach ($menu as $m) {
        if ((int)$m['is_available'] === 1) {
            $menuContext .= "- {$m['name']} ({$m['category']}): ₱{$m['price']}\n";
        }
    }
    if ($menuContext === '') $menuContext = 'No items currently available.';

    $systemPrompt = "You are a helpful, friendly customer-service assistant for Imbo't Silog (a Filipino silog restaurant).
Answer concisely in the same language as the user (English or Tagalog).
Use ONLY the information below. If asked about something not covered, politely say you don't have that info and offer what you can help with.

MENU (available items):
$menuContext

RESTAURANT INFO:
- Hours: Daily 9:00 AM – 9:00 PM (last order 8:30 PM)
- Location: Main branch (see About/More page for map)
- Contact: (02) 8123-4567 / 0917-123-4567
- Delivery: Yes, ₱20 shipping fee. Cash on delivery or online (Maya/GCash/Card).
- Reservations: Window/Middle/End tables, ₱50 fee paid at counter.
- Payment: Cash, Maya, GCash, Card. All orders start as 'pending' and must be confirmed 'paid' by admin before counting as revenue.
- Order status: Customers can view active orders on 'My Orders' page after login. Paid orders are archived.";

    $payload = json_encode([
        'model' => 'phi4-mini:latest',
        'prompt' => "<|system|>\n$systemPrompt\n<|user|>\n$question\n<|assistant|>\n",
        'stream' => false,
        'options' => ['temperature' => 0.2, 'num_predict' => 200]
    ]);

    $ch = curl_init('http://localhost:11434/api/generate');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);
    $resp = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    // curl_close() is deprecated in PHP 8.5+ (no-op since 8.0)
    @curl_close($ch);

    if ($httpCode !== 200 || !$resp) return null;
    $data = json_decode($resp, true);
    $text = trim($data['response'] ?? '');
    return $text !== '' ? $text : null;
}

$aiAnswer = call_ollama($q, $menu, $conn);
if ($aiAnswer !== null) {
    $conn->close();
    echo json_encode(['answer' => $aiAnswer, 'intent' => 'ai', 'source' => 'ollama'], JSON_UNESCAPED_UNICODE);
    exit;
}

// --- NLP Fallback (same logic as before) ---
$intent = 'unknown';
$answer = '';

$mentioned = null;
foreach ($nameMap as $nm => $m) {
    if (stripos($lower, $nm) !== false) { $mentioned = $m; break; }
}
if (!$mentioned) {
    foreach ($nameMap as $nm => $m) {
        if (strlen($nm) >= 4 && stripos($lower, str_replace(' ', '', $nm)) !== false) { $mentioned = $m; break; }
    }
}

// greeting
if (preg_match('/\b(hi|hello|hey|good (morning|afternoon|evening)|greetings)\b/', $lower)) {
    $intent = 'greeting';
    $answer = "Hi! Welcome to Imbo't Silog 👋 I'm your assistant. Ask me about our menu, prices, hours, location, or order status.";
}
// thanks
elseif (preg_match('/\b(thanks|thank you|salamat|ty)\b/', $lower)) {
    $intent = 'thanks';
    $answer = "You're welcome! 🙏 Is there anything else I can help you with?";
}
// hours
elseif (preg_match('/\b(open|close|hours|time|when|schedule)\b/', $lower)) {
    $intent = 'hours';
    $answer = "We're open daily from 9:00 AM to 9:00 PM. Last call for orders is 8:30 PM.";
}
// location / contact
elseif (preg_match('/\b(where|location|address|contact|phone|number|branch)\b/', $lower)) {
    $intent = 'location';
    $answer = "📍 Find us at Imbo't Silog! (Main branch) Contact: (02) 8123-4567 / 0917-123-4567. Visit our About/More page for the map.";
}
// delivery
elseif (preg_match('/\b(deliver|delivery|shipping|ship)\b/', $lower)) {
    $intent = 'delivery';
    $answer = "Yes, we deliver! 🛵 Shipping fee is ₱20. Place your order via 'Online Delivery' on the menu. Pay online (maya/gcash/card) or cash on delivery.";
}
// reservation
elseif (preg_match('/\b(reserve|reservation|table|book)\b/', $lower)) {
    $intent = 'reservation';
    $answer = "Table reservations are available — pick an area (Window/Middle/End) and table. Reservation fee ₱50 (paid at the counter).";
}
// unavailable
elseif (preg_match('/\b(unavailable|out of stock|sold out|wala)\b/', $lower)) {
    $intent = 'unavailable';
    $off = [];
    foreach ($menu as $m) if ((int)$m['is_available'] === 0) $off[] = $m['name'];
    $answer = empty($off) ? "Good news! All menu items are currently available. 🎉" : "Currently unavailable: " . implode(', ', $off) . ".";
}
// price of specific item
elseif ($mentioned && preg_match('/\b(price|cost|how much|magkano)\b/', $lower)) {
    $intent = 'price';
    $answer = "{$mentioned['name']} costs ₱{$mentioned['price']} ({$mentioned['category']}). " . ((int)$mentioned['is_available']===1 ? "Available now." : "Currently unavailable.");
}
// menu list
elseif (preg_match('/\b(menu|items|food|list|available)\b/', $lower) && !$mentioned) {
    $intent = 'menu';
    $cats = [];
    foreach ($menu as $m) { if ((int)$m['is_available'] === 1) $cats[$m['category']][] = $m['name'] . ' (₱' . $m['price'] . ')'; }
    $lines = [];
    foreach ($cats as $c => $its) $lines[] = "• $c: " . implode(', ', $its);
    $answer = "Here's our menu 👇\n" . implode("\n", $lines);
}
// specific item info
elseif ($mentioned) {
    $intent = 'item_info';
    $avail = ((int)$mentioned['is_available']===1) ? "✅ Available" : "❌ Unavailable";
    $answer = "{$mentioned['name']} — ₱{$mentioned['price']} ({$mentioned['category']}). Status: $avail.";
}
// payment
elseif (preg_match('/\b(pay|payment|maya|gcash|card|cash)\b/', $lower)) {
    $intent = 'payment';
    $answer = "We accept Cash, Maya, GCash, and Card. All orders start as 'pending' and only an admin marking an order 'paid' counts as revenue.";
}
// order status
elseif (preg_match('/\b(status|track|order|my order)\b/', $lower)) {
    $intent = 'order_status';
    $answer = "You can view your active orders on the 'My Orders' page (after login). Paid/completed orders are archived but kept as admin records. For order issues, call us at 0917-123-4567.";
}
// login / register
elseif (preg_match('/\b(login|log in|sign in|register|sign up|account|password)\b/', $lower)) {
    $intent = 'login';
    $answer = "To log in or create an account, click the 'Login' or 'Register' link at the top of the page. Use your email and password. Forgot password? Contact us at 0917-123-4567.";
}
// fallback
else {
    $intent = 'fallback';
    $answer = "I can help with: menu & prices, availability, opening hours, location/contact, delivery, reservation, payment methods, and order status. Try asking e.g. 'menu list', 'price of Tapsilog', 'are you open?', 'where are you located?'";
}

$conn->close();
echo json_encode(['answer'=>$answer, 'intent'=>$intent, 'source'=>'nlp_fallback'], JSON_UNESCAPED_UNICODE);
?>
