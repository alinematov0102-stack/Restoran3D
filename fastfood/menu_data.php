<?php
// menu_data.php - Mijoz sayti uchun menyu ma'lumoti (kategoriya + mahsulotlar), keshlanadi.
// Kesh bor bo'lsa bazaga umuman ulanilmaydi - sahifa bir necha barobar tez ochiladi.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/cache.php';
require_once __DIR__ . '/model3d_helper.php';

/** Baza ulanishini faqat kerak bo'lganda ochadi. */
function ff_db(): PDO {
    if (!isset($GLOBALS['pdo'])) require_once __DIR__ . '/database.php';
    return $GLOBALS['pdo'];
}

function ff_menu(string $lang): array {
    if (!in_array($lang, ['uz', 'ru', 'en'], true)) $lang = 'uz';
    return ff_cache_remember('menu:' . $lang, 120, function () use ($lang) {
        $pdo = ff_db();
        $has3d = ff_cache_remember('has3d', 3600, fn() => ff_ensure_3d_column($pdo));
        $m3d = $has3d ? 'model_3d' : 'NULL AS model_3d';
        $nameCol = $lang === 'ru' ? 'name_ru' : ($lang === 'en' ? 'name_en' : 'name');
        $descCol = $lang === 'ru' ? 'description_ru' : ($lang === 'en' ? 'description_en' : 'description');
        $cats = $pdo->query("SELECT id, name, COALESCE(NULLIF($nameCol, ''), name) AS display_name FROM categories ORDER BY id")->fetchAll();
        $prods = $pdo->query("SELECT id, image, $m3d, price, category_id,
                COALESCE(NULLIF($nameCol, ''), name) AS display_name,
                COALESCE(NULLIF($descCol, ''), description) AS display_desc
            FROM products ORDER BY id DESC")->fetchAll();
        return ['categories' => $cats, 'products' => $prods];
    });
}

function ff_cart_count(): int {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $n = 0;
    foreach (($_SESSION['cart'] ?? []) as $it) $n += (int)($it['quantity'] ?? 1);
    session_write_close();   // sessiya qulfini darhol bo'shatamiz (parallel so'rovlar kutib qolmasin)
    return $n;
}
