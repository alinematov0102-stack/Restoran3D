<?php
// cart.php - Savatga qo'shish / o'chirish / ko'rish (session asosida)
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/lang.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';

// Mahsulotni savatga qo'shish
if ($action === 'add' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if ($product) {
        if (isset($_SESSION['cart'][$id])) {
            $_SESSION['cart'][$id]['quantity']++;
        } else {
            $_SESSION['cart'][$id] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'] ?? '',
                'quantity' => 1
            ];
        }
    }
    header('Location: cart.php');
    exit;
}

// Savatdan o'chirish
if ($action === 'remove' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    unset($_SESSION['cart'][$id]);
    header('Location: cart.php');
    exit;
}

// Miqdorni oshirish / kamaytirish (+ / - tugmalari)
if (($action === 'inc' || $action === 'dec') && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if (isset($_SESSION['cart'][$id])) {
        if ($action === 'inc') {
            $_SESSION['cart'][$id]['quantity']++;
        } else {
            $_SESSION['cart'][$id]['quantity']--;
            if ($_SESSION['cart'][$id]['quantity'] <= 0) {
                unset($_SESSION['cart'][$id]);
            }
        }
    }
    header('Location: cart.php');
    exit;
}

foreach ($_SESSION['cart'] as $cid => $item) {
    if (!isset($_SESSION['cart'][$cid]['image'])) {
        $stmt = $pdo->prepare('SELECT image FROM products WHERE id = ?');
        $stmt->execute([(int)$cid]);
        $_SESSION['cart'][$cid]['image'] = (string)($stmt->fetchColumn() ?: '');
    }
}

$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(ff_current_lang()) ?>">
<head>
<meta charset="UTF-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg">
<title>Savat - FastFood</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=2">
<script src="../js/theme.js"></script>
<script src="../js/parallax.js?v=1" defer></script>
</head>
<body>
<header>
    <a class="logo" href="index.php"><img src="../img/logo-mark.svg" alt="" width="36" height="36"><span>FastFood</span></a>
    <div>
        <a href="index.php"><?= t('home') ?></a>
        <a href="my_orders.php"><?=t('my_orders')?></a><a href="cart.php"><?= t('cart') ?></a>
        <span class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">☀️</span></span>
        <span class="lang-switch" id="langSwitch">
            <button onclick="setLanguage('uz')" data-lang="uz">UZ</button>
            <button onclick="setLanguage('ru')" data-lang="ru">RU</button>
            <button onclick="setLanguage('en')" data-lang="en">EN</button>
        </span>
    </div>
</header>

<main class="cart-page">
  <div class="cart-head">
    <div><a class="back-link" href="index.php">← <?= htmlspecialchars(t('home')) ?></a><h1><?= htmlspecialchars(t('cart_title')) ?></h1><p class="cart-sub"><?= count($_SESSION['cart']) ?> <?= htmlspecialchars(t('items')) ?> · <?= htmlspecialchars(t('cart_subtitle')) ?></p></div>
    <div class="eyebrow">FASTFOOD ORDER</div>
  </div>

  <?php if (empty($_SESSION['cart'])): ?>
    <div class="empty-cart-card"><div class="empty-cart-icon">🛒</div><h2><?= htmlspecialchars(t('empty_cart')) ?></h2><p><?= htmlspecialchars(t('empty_cart_hint')) ?></p><a href="index.php" class="btn"><?= htmlspecialchars(t('view_products')) ?> →</a></div>
  <?php else: ?>
    <div class="cart-items-card">
      <?php foreach ($_SESSION['cart'] as $item): ?>
      <div class="cart-row">
        <div class="cart-product-art"><?php if (!empty($item['image'])): ?><img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>"><?php else: ?>🍔<?php endif; ?></div>
        <div class="cart-info"><h3><?= htmlspecialchars($item['name']) ?></h3><span class="price"><?= number_format($item['price'], 0, '', ' ') ?> so'm / <?= htmlspecialchars(t('per_unit')) ?></span></div>
        <div class="qty-control"><a href="cart.php?action=dec&id=<?= $item['id'] ?>" aria-label="minus">−</a><span><?= $item['quantity'] ?></span><a href="cart.php?action=inc&id=<?= $item['id'] ?>" aria-label="plus">+</a></div>
        <div class="cart-line-total"><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?> so'm</div>
        <a href="cart.php?action=remove&id=<?= $item['id'] ?>" class="cart-remove">✕ <?= htmlspecialchars(t('remove')) ?></a>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="cart-bottom">
      <div class="cart-total-box"><small><?= htmlspecialchars(t('total')) ?></small><strong><?= number_format($total, 0, '', ' ') ?> so'm</strong></div>
      <div class="cart-actions"><a href="index.php" class="btn btn-secondary">← <?= htmlspecialchars(t('view_products')) ?></a><a href="checkout.php" class="btn"><?= htmlspecialchars(t('place_order')) ?> <span>→</span></a></div>
    </div>
  <?php endif; ?>
</main>

<!-- Til almashtirish: umumiy js/lang-switch.js fayli orqali (ishonchliroq) -->
<script src="../js/lang-switch.js"></script>
<script src="../js/global-i18n.js"></script><script src="../js/universal-i18n.js"></script></body>
</html>
