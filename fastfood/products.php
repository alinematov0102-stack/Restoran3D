<?php
// products.php - Kategoriya bo'yicha mahsulotlarni ko'rsatish
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/model3d_helper.php';
require_once __DIR__ . '/hero_helper.php';
$heroImg = ff_hero_image();
$m3dCol = ff_ensure_3d_column($pdo) ? 'model_3d' : 'NULL AS model_3d';

$lang = $_COOKIE['ff_lang'] ?? 'uz';
$nameCol = $lang === 'ru' ? 'name_ru' : ($lang === 'en' ? 'name_en' : 'name');
$descCol = $lang === 'ru' ? 'description_ru' : ($lang === 'en' ? 'description_en' : 'description');

$categoryId = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$categories = $pdo->query("SELECT id, name, COALESCE(NULLIF($nameCol, ''), name) AS display_name FROM categories")->fetchAll();

$baseSql = "SELECT id, image, $m3dCol, price, category_id,
    COALESCE(NULLIF($nameCol, ''), name) AS display_name,
    COALESCE(NULLIF($descCol, ''), description) AS display_desc
    FROM products";

if ($categoryId > 0) {
    $stmt = $pdo->prepare($baseSql . " WHERE category_id = ? ORDER BY id DESC");
    $stmt->execute([$categoryId]);
} else {
    $stmt = $pdo->query($baseSql . " ORDER BY id DESC");
}
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_COOKIE['ff_lang'] ?? 'uz') ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg">
<title>Mahsulotlar - FastFood</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=2">
<link rel="stylesheet" href="../css/ff-3d.css?v=1">
<script>document.documentElement.classList.add("ff-js")</script>
<script src="../js/theme.js"></script>
<script src="../js/ff-3d.js?v=1" defer></script>
<script src="../js/parallax.js?v=1" defer></script>
</head>
<body>
<header>
    <a class="logo" href="index.php"><img src="../img/logo-mark.svg" alt="" width="36" height="36"><span>FastFood</span></a>
    <div style="display:flex; align-items:center;">
        <a href="index.php"><?= t('home') ?></a>
        <a href="reserve.php"><?= t('reserve_table') ?></a>
        <a href="cart.php"><?= t('cart') ?></a>
        <span class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">☀️</span></span>
        <span class="lang-switch" id="langSwitch">
            <button onclick="setLanguage('uz')" data-lang="uz">UZ</button>
            <button onclick="setLanguage('ru')" data-lang="ru">RU</button>
            <button onclick="setLanguage('en')" data-lang="en">EN</button>
        </span>
    </div>
</header>

<section class="page-hero<?= $heroImg ? ' has-photo' : '' ?>">
  <div class="hero-fit-layers" aria-hidden="true">
    <?php if ($heroImg): ?><div class="px-hero-media" data-px=".30" data-px-max="44" style="background-image:url('<?= htmlspecialchars($heroImg) ?>')"></div><?php endif; ?>
    <i class="hero-bokeh b1" data-px="-.12" data-px-m="26"></i>
    <i class="hero-bokeh b3" data-px="-.06" data-px-m="40"></i>
    <i class="hero-bokeh b5" data-px="-.16" data-px-m="30"></i>
  </div>
  <div class="page-hero-inner"><span class="food-kicker">MENU</span><h1><?= t('products') ?></h1></div>
</section>

<div class="container">
    <div class="categories">
        <a href="products.php" class="<?= $categoryId === 0 ? 'active' : '' ?>"><?= t('all_categories') ?></a>
        <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?= $cat['id'] ?>" class="<?= $categoryId === (int)$cat['id'] ? 'active' : '' ?>">
                <?= htmlspecialchars($cat['display_name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="product-grid">
        <?php foreach ($products as $p): ?>
        <div class="product-card food-card">
            <span class="food-card-badge">NEW</span>
            <?php if (!empty($p['model_3d'])): ?><button type="button" class="model-chip" onclick="openProductModal(this.parentNode.querySelector('img'))">🧊 3D</button><?php endif; ?>
            <div class="ff-media">
                <img loading="lazy" decoding="async" src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['display_name']) ?>"
                     onerror="this.onerror=null; this.src='../css/food-placeholder.svg'"
                     onclick="openProductModal(this)"
                     data-id="<?= $p['id'] ?>"
                     data-name="<?= htmlspecialchars($p['display_name']) ?>"
                     data-desc="<?= htmlspecialchars($p['display_desc']) ?>"
                     data-price="<?= number_format($p['price'], 0, '', ' ') ?>"
                     data-img="<?= htmlspecialchars($p['image']) ?>"
                     data-model="<?= htmlspecialchars($p['model_3d'] ?? '') ?>">
            </div>
            <div class="info">
                <h3><?= htmlspecialchars($p['display_name']) ?></h3>
                <p><?= htmlspecialchars($p['display_desc']) ?></p>
                <span class="price"><?= number_format($p['price'], 0, '', ' ') ?> so'm</span>
                <a href="cart.php?action=add&id=<?= $p['id'] ?>" class="btn"><?= t('add_to_cart') ?></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/product_modal.php'; ?>

<!-- Til almashtirish: umumiy js/lang-switch.js fayli orqali (ishonchliroq) -->
<script src="../js/lang-switch.js"></script>
<script src="../js/global-i18n.js"></script><script src="../js/universal-i18n.js"></script></body>
</html>
