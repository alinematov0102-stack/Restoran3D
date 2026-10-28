<?php
// translate_fill.php - FAQAT BIR MARTA ishga tushiriladi.
// Mavjud kategoriya va mahsulotlarning nomi/tavsifini RU va EN tiliga
// avtomatik tarjima qilib, bazaga (name_ru, name_en, description_ru, description_en) yozadi.
//
// Ishlatib bo'lgach, XAVFSIZLIK UCHUN bu faylni serverdan O'CHIRIB TASHLANG.

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../fastfood/database.php';

function autoTranslate(string $text, string $targetLang): string {
    $text = trim($text);
    if ($text === '') return '';
    if (!function_exists('curl_init')) return '';

    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=uz&tl="
        . urlencode($targetLang) . "&dt=t&q=" . urlencode($text);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $curlErr = curl_errno($ch);
    curl_close($ch);

    if ($curlErr || !$response) return '';

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data[0]) || !is_array($data[0])) return '';

    $translated = '';
    foreach ($data[0] as $segment) {
        if (isset($segment[0])) $translated .= $segment[0];
    }
    return trim($translated);
}

echo "<pre style='font-family:monospace; padding:20px; background:#111; color:#eee;'>";

// --- Kategoriyalarni tarjima qilish ---
echo "== KATEGORIYALAR ==\n";
$stmt = $pdo->query("SELECT id, name, name_ru, name_en FROM categories WHERE name_ru IS NULL OR name_ru = '' OR name_en IS NULL OR name_en = ''");
$categories = $stmt->fetchAll();
$catCount = 0;

foreach ($categories as $cat) {
    $ru = autoTranslate($cat['name'], 'ru');
    $en = autoTranslate($cat['name'], 'en');

    $upd = $pdo->prepare("UPDATE categories SET name_ru = ?, name_en = ? WHERE id = ?");
    $upd->execute([$ru, $en, $cat['id']]);

    echo "#{$cat['id']} {$cat['name']}  ->  RU: {$ru}  |  EN: {$en}\n";
    $catCount++;
    usleep(300000); // 0.3 soniya kutish - API'ni haddan tashqari yuklamaslik uchun
}
echo "Jami {$catCount} ta kategoriya tarjima qilindi.\n\n";

// --- Mahsulotlarni tarjima qilish (nomi + tavsifi) ---
echo "== MAHSULOTLAR ==\n";
$stmt = $pdo->query("SELECT id, name, name_ru, name_en, description, description_ru, description_en FROM products WHERE name_ru IS NULL OR name_ru = '' OR name_en IS NULL OR name_en = ''");
$products = $stmt->fetchAll();
$prodCount = 0;

foreach ($products as $prod) {
    $nameRu = autoTranslate($prod['name'], 'ru');
    $nameEn = autoTranslate($prod['name'], 'en');
    usleep(300000);
    $descRu = autoTranslate($prod['description'] ?? '', 'ru');
    $descEn = autoTranslate($prod['description'] ?? '', 'en');

    $upd = $pdo->prepare("UPDATE products SET name_ru = ?, name_en = ?, description_ru = ?, description_en = ? WHERE id = ?");
    $upd->execute([$nameRu, $nameEn, $descRu, $descEn, $prod['id']]);

    echo "#{$prod['id']} {$prod['name']}  ->  RU: {$nameRu}  |  EN: {$nameEn}\n";
    $prodCount++;
    usleep(300000);
}
echo "Jami {$prodCount} ta mahsulot tarjima qilindi.\n\n";

echo "TAYYOR! Endi index.php / products.php ga qaytib, tilni almashtirib ko'ring.\n";
echo "Ishlatib bo'lgach, xavfsizlik uchun shu faylni (translate_fill.php) serverdan o'chiring.\n";
echo "</pre>";
