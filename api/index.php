<?php
// api/index.php - Vue ilovasi uchun JSON API.
// Eski PHP sayt bilan bir xil sessiya va bir xil jadvallar ishlatiladi, shuning uchun ikkalasi birga ishlaydi.
require_once __DIR__ . '/../fastfood/menu_data.php';   // config, kesh, ff_db(), ff_menu()
require_once __DIR__ . '/../fastfood/hero_helper.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function out($data, int $code = 200) { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); exit; }
function fail(string $code, int $http = 400) { out(['ok' => false, 'error' => $code], $http); }

/** '../uploads/x.jpg' -> 'uploads/x.jpg' (Vue ilovasi /shop/ ichida, shuning uchun o'zi '../' qo'shadi) */
function norm_path($p): string {
    $p = trim((string)$p);
    if ($p === '' || preg_match('#^https?://#i', $p)) return $p;
    return ltrim(preg_replace('#^(\.\./|\./)+#', '', $p), '/');
}
function cart_items(string $lang): array {
    $menu = ff_menu($lang);
    $idx = [];
    foreach ($menu['products'] as $p) $idx[(int)$p['id']] = $p;
    $items = []; $total = 0.0; $count = 0;
    foreach (($_SESSION['cart'] ?? []) as $id => $it) {
        $q = (int)($it['quantity'] ?? 1); $price = (float)($it['price'] ?? 0);
        $p = $idx[(int)$id] ?? null;
        $items[] = [
            'id' => (int)$id,
            'name' => $p['display_name'] ?? (string)($it['name'] ?? ''),
            'price' => $price, 'quantity' => $q,
            'image' => norm_path($p['image'] ?? ($it['image'] ?? '')),
        ];
        $total += $price * $q; $count += $q;
    }
    return ['items' => $items, 'total' => $total, 'count' => $count];
}
function my_order_ids(): array {
    return array_values(array_filter(array_map('intval', $_SESSION['my_orders'] ?? [])));
}
function order_rows(array $ids): array {
    if (!$ids) return [];
    $pdo = ff_db();
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $st = $pdo->prepare("SELECT id, status, created_at, total, table_number, order_type, address FROM orders WHERE id IN ($ph) ORDER BY id DESC");
    $st->execute($ids);
    $orders = $st->fetchAll();
    $it = $pdo->prepare("SELECT order_id, product_name, price, quantity FROM order_items WHERE order_id IN ($ph)");
    $it->execute($ids);
    $byOrder = [];
    foreach ($it->fetchAll() as $row) $byOrder[(int)$row['order_id']][] = ['name' => $row['product_name'], 'price' => (float)$row['price'], 'quantity' => (int)$row['quantity']];
    $res = [];
    foreach ($orders as $o) {
        $res[] = [
            'id' => (int)$o['id'], 'code' => 'FF-' . str_pad((string)$o['id'], 6, '0', STR_PAD_LEFT),
            'status' => $o['status'], 'created_at' => $o['created_at'], 'total' => (float)$o['total'],
            'type' => !empty($o['table_number']) ? 'table' : 'delivery', 'table_number' => $o['table_number'],
            'address' => $o['address'] ?? '', 'items' => $byOrder[(int)$o['id']] ?? [],
            'can_cancel' => !in_array($o['status'], ['yakunlangan', 'bekor qilingan'], true),
        ];
    }
    return $res;
}

$r = (string)($_GET['r'] ?? '');
$method = $_SERVER['REQUEST_METHOD'];
$lang = in_array($_GET['lang'] ?? '', ['uz', 'ru', 'en'], true) ? $_GET['lang'] : 'uz';
$body = [];
if ($method === 'POST') {
    // oddiy <form> bilan boshqa saytdan yuborishni to'sadi (brauzer bu sarlavhani cross-site qo'sha olmaydi)
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'fetch') fail('forbidden', 403);
    $body = json_decode(file_get_contents('php://input') ?: '[]', true);
    if (!is_array($body)) $body = [];
}
session_start();
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) $_SESSION['cart'] = [];

try {
    switch ($r) {
        case 'menu': {
            $m = ff_menu($lang);
            session_write_close();
            $cats = array_map(fn($c) => ['id' => (int)$c['id'], 'name' => $c['display_name']], $m['categories']);
            $prods = array_map(fn($p) => [
                'id' => (int)$p['id'], 'name' => $p['display_name'], 'desc' => (string)($p['display_desc'] ?? ''),
                'price' => (float)$p['price'], 'image' => norm_path($p['image']), 'model' => norm_path($p['model_3d'] ?? ''),
                'cat' => (int)$p['category_id'],
            ], $m['products']);
            $hero = ff_hero_image();
            out(['ok' => true, 'categories' => $cats, 'products' => $prods, 'hero' => $hero ? norm_path($hero) : null]);
        }
        case 'cart': {
            if ($method === 'POST') {
                $op = (string)($body['op'] ?? ''); $id = (int)($body['id'] ?? 0);
                if ($op === 'add') {
                    $st = ff_db()->prepare('SELECT id, name, price, image FROM products WHERE id = ?'); $st->execute([$id]);
                    $p = $st->fetch();
                    if (!$p) fail('not_found', 404);
                    if (isset($_SESSION['cart'][$id])) $_SESSION['cart'][$id]['quantity']++;
                    else $_SESSION['cart'][$id] = ['id' => $p['id'], 'name' => $p['name'], 'price' => $p['price'], 'image' => $p['image'] ?? '', 'quantity' => 1];
                } elseif ($op === 'inc' && isset($_SESSION['cart'][$id])) {
                    $_SESSION['cart'][$id]['quantity']++;
                } elseif ($op === 'dec' && isset($_SESSION['cart'][$id])) {
                    if (--$_SESSION['cart'][$id]['quantity'] <= 0) unset($_SESSION['cart'][$id]);
                } elseif ($op === 'remove') {
                    unset($_SESSION['cart'][$id]);
                } elseif ($op === 'clear') {
                    $_SESSION['cart'] = [];
                } else fail('bad_request');
            }
            $c = cart_items($lang);
            session_write_close();
            out(['ok' => true] + $c);
        }
        case 'tables': {
            session_write_close();
            $rows = ff_db()->query("SELECT * FROM restaurant_tables ORDER BY CAST(table_number AS UNSIGNED) ASC, table_number ASC")->fetchAll();
            out(['ok' => true, 'tables' => array_map(fn($t) => ['id' => (int)$t['id'], 'table_number' => (string)$t['table_number'], 'seats' => isset($t['seats']) ? (int)$t['seats'] : null, 'status' => $t['status'] ?? 'bosh'], $rows)]);
        }
        case 'checkout': {
            if ($method !== 'POST') fail('method', 405);
            if (empty($_SESSION['cart'])) fail('empty_cart');
            if (($_SESSION['last_post'] ?? 0) > time() - 3) fail('too_fast', 429);
            $name = mb_substr(trim((string)($body['name'] ?? '')), 0, 120);
            $phone = mb_substr(trim((string)($body['phone'] ?? '')), 0, 40);
            $type = ($body['order_type'] ?? '') === 'stol' ? 'stol' : 'yetkazib berish';
            $address = mb_substr(trim((string)($body['address'] ?? '')), 0, 255);
            $table = mb_substr(trim((string)($body['table_number'] ?? '')), 0, 20);
            if ($name === '') fail('name_required');
            if ($type === 'yetkazib berish' && $phone === '') fail('phone_required');
            if ($type === 'yetkazib berish' && $address === '') fail('address_required');
            if ($type === 'stol' && $table === '') fail('table_required');
            $total = 0.0;
            foreach ($_SESSION['cart'] as $it) $total += (float)$it['price'] * (int)$it['quantity'];
            $pdo = ff_db();
            try {
                $pdo->beginTransaction();
                $pdo->prepare("INSERT INTO orders (customer_name, phone, address, table_number, total, order_type, status, delivery_status) VALUES (?, ?, ?, ?, ?, ?, 'yangi', ?)")
                    ->execute([$name, $phone, $type === 'stol' ? '' : $address, $type === 'stol' ? $table : null, $total, $type === 'stol' ? 'table' : 'delivery', $type === 'yetkazib berish' ? 'new' : null]);
                $orderId = (int)$pdo->lastInsertId();
                $ins = $pdo->prepare('INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)');
                foreach ($_SESSION['cart'] as $it) $ins->execute([$orderId, $it['id'], $it['name'], $it['price'], $it['quantity']]);
                if ($type === 'stol') {
                    $lock = $pdo->prepare('SELECT status FROM restaurant_tables WHERE table_number=? FOR UPDATE'); $lock->execute([$table]);
                    $state = $lock->fetchColumn();
                    if ($state === false || $state === 'band') throw new RuntimeException('table_busy');
                    $pdo->prepare("UPDATE restaurant_tables SET status = 'band' WHERE table_number = ?")->execute([$table]);
                }
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                if ($e->getMessage() === 'table_busy') fail('table_busy', 409);
                error_log('api checkout: ' . $e->getMessage());
                fail('save_failed', 500);
            }
            $_SESSION['cart'] = [];
            $_SESSION['my_orders'] = array_values(array_unique(array_slice(array_merge($_SESSION['my_orders'] ?? [], [$orderId]), -20)));
            $_SESSION['last_post'] = time();
            session_write_close();
            out(['ok' => true, 'order_id' => $orderId]);
        }
        case 'orders': {
            session_write_close();
            out(['ok' => true, 'orders' => order_rows(my_order_ids())]);
        }
        case 'order': {
            session_write_close();
            $id = (int)($_GET['id'] ?? 0);
            if (!in_array($id, my_order_ids(), true)) fail('not_found', 404);
            $rows = order_rows([$id]);
            if (!$rows) fail('not_found', 404);
            out(['ok' => true, 'order' => $rows[0]]);
        }
        case 'cancel': {
            if ($method !== 'POST') fail('method', 405);
            $id = (int)($body['order_id'] ?? 0); $reason = mb_substr(trim((string)($body['reason'] ?? '')), 0, 500);
            if ($id <= 0 || !in_array($id, my_order_ids(), true)) fail('not_found', 404);
            if ($reason === '') fail('reason_required');
            $pdo = ff_db();
            $st = $pdo->prepare('SELECT status, table_number FROM orders WHERE id=?'); $st->execute([$id]); $o = $st->fetch();
            if (!$o || in_array($o['status'], ['yakunlangan', 'bekor qilingan'], true)) fail('cannot_cancel');
            try {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE orders SET status='bekor qilingan' WHERE id=?")->execute([$id]);
                $pdo->prepare('INSERT INTO order_cancellations(order_id,reason) VALUES(?,?)')->execute([$id, $reason]);
                if (!empty($o['table_number'])) $pdo->prepare("UPDATE restaurant_tables SET status='bosh' WHERE table_number=? AND status='band'")->execute([$o['table_number']]);
                $pdo->commit();
            } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); fail('save_failed', 500); }
            session_write_close();
            out(['ok' => true]);
        }
        case 'reserve': {
            if ($method !== 'POST') fail('method', 405);
            if (($_SESSION['last_post'] ?? 0) > time() - 3) fail('too_fast', 429);
            $tableId = (int)($body['table_id'] ?? 0);
            $name = mb_substr(trim((string)($body['name'] ?? '')), 0, 120); $phone = mb_substr(trim((string)($body['phone'] ?? '')), 0, 40);
            $date = trim((string)($body['date'] ?? '')); $time = trim((string)($body['time'] ?? ''));
            if ($tableId <= 0 || $name === '' || $phone === '' || $date === '' || $time === '') fail('fields_required');
            $when = $date . ' ' . $time . ':00';
            $dt = DateTime::createFromFormat('Y-m-d H:i:s', $when);
            if (!$dt || $dt < new DateTime()) fail('past_time');
            $pdo = ff_db();
            $st = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE table_id = ? AND status = 'band' AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, ?)) < 60");
            $st->execute([$tableId, $when]);
            if ((int)$st->fetchColumn() > 0) fail('conflict', 409);
            $pdo->prepare('INSERT INTO reservations (table_id, customer_name, phone, reservation_time) VALUES (?, ?, ?, ?)')->execute([$tableId, $name, $phone, $when]);
            $_SESSION['last_post'] = time();
            session_write_close();
            out(['ok' => true]);
        }
        default:
            fail('unknown', 404);
    }
} catch (Throwable $e) {
    error_log('api: ' . $e->getMessage());
    fail('server_error', 500);
}
