<?php
/**
 * Shared helpers for reading the `orders.items` column.
 *
 * Orders are stored in whichever shape the client sent:
 *   - array of objects:  [ {"name":"Tapsilog","price":85,"qty":2}, ... ]
 *   - map keyed by name: { "Tapsilog": {"price":85,"qty":2}, ... }   <-- what the menu cart sends
 *
 * Always read through these helpers so both shapes render correctly.
 */

if (!function_exists('order_items_list')) {
    /**
     * @return array<int, array{name:string, qty:int, price:float}>
     */
    function order_items_list($raw) {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($raw)) return [];

        $out = [];
        foreach ($raw as $key => $v) {
            if (!is_array($v)) continue;
            $name = (isset($v['name']) && $v['name'] !== '') ? $v['name'] : (is_string($key) ? $key : '');
            if (!is_string($name) || trim($name) === '') continue;
            $out[] = [
                'name'  => trim($name),
                'qty'   => (int)($v['qty'] ?? 0),
                'price' => (float)($v['price'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * One-line summary, e.g. "Tapsilog x2, Egg x1". Returns 'N/A' when empty.
     */
    function order_items_text($raw) {
        $parts = [];
        foreach (order_items_list($raw) as $i) {
            $parts[] = $i['name'] . ' x' . $i['qty'];
        }
        return $parts ? implode(', ', $parts) : 'N/A';
    }
}
