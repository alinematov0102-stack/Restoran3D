<?php
// index.php - Bosh sahifa (mashhur mahsulotlar ko'rsatiladi)
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/lang.php';
require_once __DIR__ . '/model3d_helper.php';
require_once __DIR__ . '/hero_helper.php';
$heroImg = ff_hero_image();
$m3dCol = ff_ensure_3d_column($pdo) ? 'model_3d' : 'NULL AS model_3d';

// Tanlangan til (cookie orqali) - bazada saqlangan tarjimalarni ko'rsatish uchun
$lang = $_COOKIE['ff_lang'] ?? 'uz';
$nameCol = $lang === 'ru' ? 'name_ru' : ($lang === 'en' ? 'name_en' : 'name');
$descCol = $lang === 'ru' ? 'description_ru' : ($lang === 'en' ? 'description_en' : 'description');

$categories = $pdo->query("SELECT id, name, COALESCE(NULLIF($nameCol, ''), name) AS display_name FROM categories")->fetchAll();
$products = $pdo->query("
    SELECT id, image, $m3dCol, price,
        COALESCE(NULLIF($nameCol, ''), name) AS display_name,
        COALESCE(NULLIF($descCol, ''), description) AS display_desc
    FROM products ORDER BY id DESC LIMIT 8
")->fetchAll();

session_start();
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_COOKIE['ff_lang'] ?? 'uz') ?>">
<head>
<meta charset="UTF-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg">
<title>FastFood - Bosh sahifa</title>
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
        <a href="products.php"><?= t('products') ?></a>
        <a href="reserve.php"><?= t('reserve_table') ?></a>
        <a href="cart.php"><?= t('cart') ?> (<?= $cartCount ?>)</a><a href="my_orders.php"><?= t('my_orders') ?></a>
        <span class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">☀️</span></span>
        <span class="lang-switch" id="langSwitch">
            <button onclick="setLanguage('uz')" data-lang="uz">UZ</button>
            <button onclick="setLanguage('ru')" data-lang="ru">RU</button>
            <button onclick="setLanguage('en')" data-lang="en">EN</button>
        </span>
    </div>
</header>

<main class="ff-home">
<section class="hero-fit<?= $heroImg ? ' has-photo' : '' ?>">
  <div class="hero-fit-layers" aria-hidden="true">
    <?php if ($heroImg): ?><div class="px-hero-media" data-px=".28" data-px-max="110" style="background-image:url('<?= htmlspecialchars($heroImg) ?>')"></div><?php endif; ?>
    <i class="hero-bokeh b1" data-px="-.10" data-px-m="26"></i>
    <i class="hero-bokeh b2" data-px="-.18" data-px-m="-34"></i>
    <i class="hero-bokeh b3" data-px="-.06" data-px-m="40"></i>
    <i class="hero-bokeh b4" data-px="-.22" data-px-m="-20"></i>
    <i class="hero-bokeh b5" data-px="-.14" data-px-m="30"></i>
  </div>
  <div class="hero-fit-copy">
    <h1><?= t('welcome') ?></h1>
    <p><?= t('hero_subtitle') ?></p>
    <div class="hero-fit-actions">
      <a class="btn" href="products.php"><?= t('products') ?></a>
      <a class="hero-fit-ghost" href="reserve.php"><?= t('reserve_table') ?></a>
    </div>
    <ul class="hero-fit-meta">
      <li><?= t('hero_badge1') ?></li>
      <li>15–25 min</li>
      <li><?= t('hero_badge3') ?></li>
    </ul>
  </div>
  <div class="hero-fit-brand" aria-hidden="true">
    <span class="hb-ring r1"></span><span class="hb-ring r2"></span><span class="hb-ring r3"></span>
    <div class="hb-orbit" style="--a:0deg;--d:30s;--r:100"><div class="hb-chip"><span style="--bd:0s">🍟</span></div></div>
    <div class="hb-orbit" style="--a:120deg;--d:42s;--r:135"><div class="hb-chip"><span style="--bd:-1s">🍔</span></div></div>
    <div class="hb-orbit" style="--a:200deg;--d:30s;--r:100"><div class="hb-chip"><span style="--bd:-2s">🥤</span></div></div>
    <div class="hb-orbit" style="--a:300deg;--d:58s;--r:135"><div class="hb-chip"><span style="--bd:-.5s">🍕</span></div></div>
    <img class="hb-mark" src="../img/logo-mark.svg" alt="">
    <div class="hb-name"><?= htmlspecialchars(APP_NAME) ?></div>
    <div class="hb-tag">Restaurant · Delivery</div>
  </div>
</section>

<div class="food-section-head"><div><span class="food-kicker">MENU</span><h2><?= t('new_products') ?></h2></div><a href="products.php"><?= t('products') ?> <span>→</span></a></div>
    <div class="categories food-categories">
        <?php foreach ($categories as $cat): ?>
            <a href="products.php?category=<?= $cat['id'] ?>"><?= htmlspecialchars($cat['display_name']) ?></a>
        <?php endforeach; ?>
    </div>

    <div class="product-grid">
        <?php foreach ($products as $p): ?>
        <div class="product-card food-card">
            <span class="food-card-badge">NEW</span>
            <?php if (!empty($p['model_3d'])): ?><button type="button" class="model-chip" onclick="openProductModal(this.parentNode.querySelector('img'))">🧊 3D</button><?php endif; ?>
            <div class="ff-media">
                <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['display_name']) ?>"
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
</main>

<?php include __DIR__ . '/product_modal.php'; ?>

<!-- Til almashtirish: umumiy js/lang-switch.js fayli orqali (ishonchliroq) -->
<script src="../js/lang-switch.js"></script>
<script src="../js/global-i18n.js"></script><script src="../js/universal-i18n.js"></script></body>
</html>
