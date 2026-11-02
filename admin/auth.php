<?php
// auth.php - Rollar va ruxsatlar (bitta joyda boshqariladi)
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/../fastfood/database.php'; // global $pdo

const FF_ROLES = [
    'admin'    => ['label' => 'Admin',        'home' => 'dashboard.php'],
    'manager'  => ['label' => 'Menejer',      'home' => 'dashboard.php'],
    'waiter'   => ['label' => 'Afitsant',     'home' => 'waiter.php'],
    'kitchen'  => ['label' => 'Oshxona',      'home' => 'kitchen.php'],
    'cashier'  => ['label' => 'Kassir',       'home' => 'receive_orders.php'],
];

// Sahifa => ruxsat berilgan rollar. Ro'yxatda yo'q sahifa faqat admin uchun.
function ff_page_roles(): array {
    $all = ['admin','manager','waiter','kitchen','cashier'];
    return [
        'dashboard.php'       => ['admin','manager'],
        'catalog.php'         => ['admin','manager'],
        'products.php'        => ['admin','manager'],
        'categories.php'      => ['admin','manager'],
        'tables.php'          => ['admin','manager'],
        'hero.php'            => ['admin','manager'],
        'reports.php'         => ['admin','manager'],
        'archive.php'         => ['admin','manager'],
        'check_3d.php'        => ['admin','manager'],
        'transactions.php'    => ['admin','manager','cashier'],
        'receive_orders.php'  => ['admin','manager','cashier'],
        'receipt.php'         => ['admin','manager','cashier','waiter'],
        'waiter.php'          => ['admin','manager','waiter'],
        'kitchen.php'         => ['admin','manager','kitchen'],
        'kitchen_ticket.php'  => ['admin','manager','kitchen'],
        'kitchen_feed.php'    => ['admin','manager','kitchen'],
        'kitchen_action.php'  => ['admin','manager','kitchen'],
        'notifications.php'   => $all,
        // faqat admin: staff.php, backup.php, translate_fill.php
    ];
}

function ff_script_key(): string {
    $f = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $b = basename($f);
    return basename(dirname($f)) === 'delivery' ? 'delivery/' . $b : $b;
}

function ff_role(): string {
    // Sessiyada rol bo'lmasa, hech qachon admin huquqini bermaymiz.
    $r = $_SESSION['admin_role'] ?? '';
    if ($r === 'dispatch') $r = 'cashier'; // olib tashlangan rol
    return isset(FF_ROLES[$r]) ? $r : 'waiter';
}

function ff_home(?string $role = null): string {
    $role = $role ?? ff_role();
    return FF_ROLES[$role]['home'] ?? 'login.php';
}

// Admin xodimga rolidan tashqari qo'shimcha bo'limlarni ham ochib berishi mumkin
function ff_assignable_pages(): array {
    return [
        'dashboard.php'      => 'Bosh sahifa (statistika)',
        'receive_orders.php' => 'Buyurtma olish',
        'waiter.php'         => 'Afitsant (POS)',
        'kitchen.php'        => 'Oshxona',
        'transactions.php'   => 'Kirim / Chiqim',
        'reports.php'        => 'Hisobotlar',
        'catalog.php'        => 'Katalog (mahsulotlar)',
        'tables.php'         => 'Stollar',
        'hero.php'           => 'Bosh banner',
        'archive.php'        => 'Arxiv',
        'receipt.php'        => 'Chek',
    ];
}
function ff_extra_pages(): array {
    $raw = (string)($_SESSION['admin_extra'] ?? '');
    $ok = array_keys(ff_assignable_pages());
    return array_values(array_intersect(array_filter(array_map('trim', explode(',', $raw))), $ok));
}
function ff_can(string $page): bool {
    $role = ff_role();
    if ($role === 'admin') return true;
    $map = ff_page_roles();
    if (isset($map[$page]) && in_array($role, $map[$page], true)) return true;
    // kitchen_feed/action/ticket va notifications asosiy sahifaga ergashadi
    $follow = ['kitchen_feed.php' => 'kitchen.php', 'kitchen_action.php' => 'kitchen.php', 'kitchen_ticket.php' => 'kitchen.php'];
    $base = $follow[$page] ?? $page;
    return in_array($base, ff_extra_pages(), true);
}

function ff_is_json_endpoint(string $key): bool {
    return in_array($key, ['notifications.php','kitchen_feed.php','kitchen_action.php'], true);
}

function ff_base_prefix(string $key): string { return strpos($key, '/') !== false ? '../' : ''; }

// Har 5 daqiqada xodim hali faolmi va roli o'zgarmaganmi tekshiradi
function ff_revalidate(): void {
    if (empty($_SESSION['admin_id'])) return;
    try {
        global $pdo;
        if (empty($_SESSION['schema_ok'])) {
            ff_ensure_staff_schema($pdo);
            $_SESSION['schema_ok'] = 1;
        }
        // Har bir admin so'rovida bazadan qayta tekshiramiz:
        // o'chirilgan xodim yoki o'zgargan rol darhol kuchga kiradi.
        $st = $pdo->prepare('SELECT role,is_active,extra_pages FROM admin WHERE id=?');
        $st->execute([(int)$_SESSION['admin_id']]);
        $u = $st->fetch();
        if (!$u || (isset($u['is_active']) && (int)$u['is_active'] !== 1)) {
            $_SESSION = [];
            session_destroy();
            return;
        }
        $role = (string)($u['role'] ?? '');
        $_SESSION['admin_role'] = isset(FF_ROLES[$role]) ? $role : 'waiter';
        $_SESSION['admin_extra'] = (string)($u['extra_pages'] ?? '');
    } catch (Throwable $e) {
        // Bazani tekshirib bo'lmasa, eski sessiyaga ishonmaymiz.
        error_log('admin session revalidation failed: ' . $e->getMessage());
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}

function ff_guard(): void {
    $key = ff_script_key();
    // Katalogni o'zgartiradigan sahifalarda POST/so'rov bo'lsa mijoz menyusi keshini tozalaymiz
    if (($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET)) && in_array($key, ['catalog.php','products.php','categories.php','hero.php','tables.php'], true)) {
        require_once __DIR__ . '/../fastfood/cache.php';
        ff_cache_bust();
    }
    $pre = ff_base_prefix($key);
    ff_revalidate();
    if (empty($_SESSION['admin_id'])) {
        if (ff_is_json_endpoint($key)) { http_response_code(401); header('Content-Type: application/json'); echo '{"ok":false}'; exit; }
        header('Location: ' . $pre . 'login.php'); exit;
    }
    if (!ff_can($key)) {
        if (ff_is_json_endpoint($key)) { http_response_code(403); header('Content-Type: application/json'); echo '{"ok":false,"error":"forbidden"}'; exit; }
        // Ruxsati yo'q sahifa: o'z ish joyiga qaytariladi
        $home = ff_home();
        if ($home === $key) { http_response_code(403); exit('403'); }
        header('Location: ' . $pre . $home); exit;
    }
}

// admin jadvaliga role/full_name/is_active ustunlarini bir marta qo'shadi
function ff_ensure_staff_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return; $done = true;
    try {
        $have = [];
        foreach ($pdo->query('SHOW COLUMNS FROM admin')->fetchAll() as $c) $have[strtolower($c['Field'] ?? '')] = true;
        $add = [
            'role'        => "VARCHAR(20) NOT NULL DEFAULT 'admin'",
            'full_name'   => 'VARCHAR(120) NULL',
            'is_active'   => 'TINYINT(1) NOT NULL DEFAULT 1',
            'extra_pages' => 'VARCHAR(500) NULL',
        ];
        foreach ($add as $col => $def) if (empty($have[$col])) $pdo->exec("ALTER TABLE admin ADD COLUMN $col $def");
    } catch (Throwable $e) { error_log('staff schema: ' . $e->getMessage()); }
}

function ff_csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function ff_csrf_ok(): bool {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''));
}
