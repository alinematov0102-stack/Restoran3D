<?php
// mobile_shell.php - Telefon uchun ilova ko'rinishi: yon menyu, pastki tablar, savat tugmasi.
// Kompyuterda bular yashirin (css/mobile-app.css), faqat <=820px ekranda ko'rinadi.
$shellMode = $shellMode ?? 'nav';
$shellCats = $shellCats ?? [];
$shellActiveCat = $shellActiveCat ?? 0;
$shellFab = $shellFab ?? true;
if (!isset($cartCount)) {
    $cartCount = 0;
    foreach (($_SESSION['cart'] ?? []) as $__it) $cartCount += (int)($__it['quantity'] ?? 1);
}
if (!function_exists('ff_cat_icon')) {
    function ff_cat_icon(string $name): string {
        $n = mb_strtolower($name);
        $map = [
            '⭐' => ['aksiya', 'акци', 'promo', 'sale', 'chegirma'],
            '🍕' => ['pitsa', 'pizza', 'пицц'],
            '🍔' => ['burger', 'бургер', 'gamburger'],
            '🌯' => ['lavash', 'shaurma', 'shawarma', 'шаурм', 'лаваш', 'rolл', 'roll', 'ролл', 'doner', 'донер'],
            '🍟' => ['zakus', 'закус', 'snack', 'снек', 'fri', 'kartoshka', 'картоф', 'fries'],
            '🥤' => ['ichimlik', 'napit', 'напит', 'drink', 'cola', 'кола', 'sok', 'сок', 'juice', 'limonad'],
            '☕' => ['kofe', 'coffee', 'кофе', 'choy', 'чай', 'tea'],
            '🥗' => ['salat', 'салат', 'salad'],
            '🍰' => ['desert', 'десерт', 'shirin', 'tort', 'торт', 'dessert', 'muzqaymoq', 'морожен'],
            '🍲' => ['sho', 'суп', 'soup', 'osh', 'plov', 'плов', 'lag'],
            '🍱' => ['set', 'combo', 'комбо', 'сет'],
            '🍗' => ['tovuq', 'chicken', 'кур', 'nugget', 'крыл', 'wings'],
            '🥩' => ['kabob', 'kebab', 'кебаб', 'steak', 'go\'sht', 'мясо'],
        ];
        foreach ($map as $icon => $words) foreach ($words as $w) if ($w !== '' && mb_strpos($n, $w) !== false) return $icon;
        return '🍽️';
    }
}
$cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<div class="m-drawer-backdrop" id="mDrawerBackdrop" onclick="ffDrawer(false)"></div>
<aside class="m-drawer" id="mDrawer" aria-hidden="true">
  <div class="m-drawer-head">
    <img src="../img/logo-mark.svg" alt="" width="34" height="34"><strong>FastFood</strong>
    <button type="button" class="m-drawer-close" aria-label="<?= htmlspecialchars(t('m_close')) ?>" onclick="ffDrawer(false)">×</button>
  </div>
  <nav class="m-drawer-nav">
    <a href="index.php" class="<?= $cur === 'index.php' ? 'on' : '' ?>"><span>🏠</span><?= t('home') ?></a>
    <a href="products.php" class="<?= $cur === 'products.php' ? 'on' : '' ?>"><span>🍽️</span><?= t('products') ?></a>
    <a href="reserve.php" class="<?= $cur === 'reserve.php' ? 'on' : '' ?>"><span>📅</span><?= t('reserve_table') ?></a>
    <a href="cart.php" class="<?= $cur === 'cart.php' ? 'on' : '' ?>"><span>🛒</span><?= t('cart') ?> <b class="m-count" data-cart-count><?= (int)$cartCount ?></b></a>
    <a href="my_orders.php" class="<?= $cur === 'my_orders.php' ? 'on' : '' ?>"><span>🧾</span><?= t('my_orders') ?></a>
  </nav>
  <div class="m-drawer-tools">
    <div class="m-drawer-label"><?= t('m_settings') ?></div>
    <div class="m-drawer-row">
      <span class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">☀️</span></span>
      <span class="lang-switch">
        <button type="button" onclick="setLanguage('uz')" data-lang="uz">UZ</button>
        <button type="button" onclick="setLanguage('ru')" data-lang="ru">RU</button>
        <button type="button" onclick="setLanguage('en')" data-lang="en">EN</button>
      </span>
    </div>
  </div>
</aside>

<nav class="m-tabbar" aria-label="<?= htmlspecialchars(t('menu_open')) ?>">
<?php if ($shellMode === 'menu'): ?>
  <a href="products.php" class="<?= $shellActiveCat === 0 ? 'on' : '' ?>"><i>🍽️</i><span><?= t('all_categories') ?></span></a>
  <?php foreach ($shellCats as $c): ?>
  <a href="products.php?category=<?= (int)$c['id'] ?>" class="<?= $shellActiveCat === (int)$c['id'] ? 'on' : '' ?>"><i><?= ff_cat_icon((string)$c['name']) ?></i><span><?= htmlspecialchars($c['display_name'] ?? $c['name']) ?></span></a>
  <?php endforeach; ?>
<?php else: ?>
  <a href="index.php" class="<?= $cur === 'index.php' ? 'on' : '' ?>"><i>🏠</i><span><?= t('home') ?></span></a>
  <a href="products.php" class="<?= $cur === 'products.php' ? 'on' : '' ?>"><i>🍽️</i><span><?= t('products') ?></span></a>
  <a href="reserve.php" class="<?= $cur === 'reserve.php' ? 'on' : '' ?>"><i>📅</i><span><?= t('reserve_table') ?></span></a>
  <a href="my_orders.php" class="<?= $cur === 'my_orders.php' ? 'on' : '' ?>"><i>🧾</i><span><?= t('my_orders') ?></span></a>
<?php endif; ?>
</nav>

<?php if ($shellFab): ?>
<a class="m-fab" href="cart.php" aria-label="<?= htmlspecialchars(t('cart')) ?>">🛒<b class="m-fab-badge" data-cart-count <?= $cartCount > 0 ? '' : 'hidden' ?>><?= (int)$cartCount ?></b></a>
<?php endif; ?>
<div class="m-toast" id="mToast" role="status" aria-live="polite"><span id="mToastText"><?= t('added_to_cart') ?></span></div>
<script src="../js/mobile-app.js?v=5" defer></script>
