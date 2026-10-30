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
    'dispatch' => ['label' => 'Operator',     'home' => 'receive_orders.php'],
];

// Sahifa => ruxsat berilgan rollar. Ro'yxatda yo'q sahifa faqat admin uchun.
function ff_page_roles(): array {
    $all = ['admin','manager','waiter','kitchen','cashier','dispatch'];
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
        'receive_orders.php'  => ['admin','manager','cashier','dispatch'],
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
    // Rol aniqlanmagan eski sessiyaga admin huquqi berilmaydi. Qayta login talab qilinadi.
    $r = $_SESSION['admin_role'] ?? 'waiter';
    return isset(FF_ROLES[$r]) ? $r : 'waiter';
}

function ff_home(?string $role = null): string {
    $role = $role ?? ff_role();
    return FF_ROLES[$role]['home'] ?? 'login.php';
}

function ff_can(string $page): bool {
    $role = ff_role();
    if ($role === 'admin') return true;
    $map = ff_page_roles();
    return isset($map[$page]) && in_array($role, $map[$page], true);
}

function ff_is_json_endpoint(string $key): bool {
    return in_array($key, ['notifications.php','kitchen_feed.php','kitchen_action.php'], true);
}

function ff_base_prefix(string $key): string { return strpos($key, '/') !== false ? '../' : ''; }

// Har 5 daqiqada xodim hali faolmi va roli o'zgarmaganmi tekshiradi
function ff_revalidate(): void {
    if (empty($_SESSION['admin_id'])) return;
    $now = time();
    if (($_SESSION['role_checked'] ?? 0) > $now - 300) return;
    try {
        global $pdo;
        $st = $pdo->prepare('SELECT role,is_active FROM admin WHERE id=?');
        $st->execute([(int)$_SESSION['admin_id']]);
        $u = $st->fetch();
        if (!$u || (isset($u['is_active']) && (int)$u['is_active'] !== 1)) {
            $_SESSION = []; session_destroy(); return;
        }
        if (isset($u['role'])) $_SESSION['admin_role'] = $u['role'];
        $_SESSION['role_checked'] = $now;
    } catch (Throwable $e) { /* ustun hali yo'q bo'lsa e'tibor bermaymiz */ }
}

function ff_guard(): void {
    $key = ff_script_key();
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
    // Har bir ustunni alohida tekshiramiz: role mavjud bo'lib, full_name yo'q
    // bo'lgan eski bazalarda ham qolgan ustunlar yaratilishi kerak.
    $columns = [
        'role'      => "ALTER TABLE admin ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin'",
        'full_name' => "ALTER TABLE admin ADD COLUMN full_name VARCHAR(120) NULL",
        'is_active' => "ALTER TABLE admin ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1",
    ];
    foreach ($columns as $column => $sql) {
        try {
            $check = $pdo->prepare('SHOW COLUMNS FROM admin LIKE ?');
            $check->execute([$column]);
            if (!$check->fetch()) $pdo->exec($sql);
        } catch (Throwable $e) {
            error_log('staff schema (' . $column . '): ' . $e->getMessage());
        }
    }
}

function ff_csrf(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function ff_csrf_ok(): bool {
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''));
}
