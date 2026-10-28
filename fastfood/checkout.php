<?php
// checkout.php - Buyurtmani rasmiylashtirish
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/lang.php';

if (empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

$tables = $pdo->query("SELECT * FROM restaurant_tables ORDER BY CAST(table_number AS UNSIGNED) ASC, table_number ASC")->fetchAll();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $orderType = $_POST['order_type'] ?? 'yetkazib berish';
    $address = trim($_POST['address'] ?? '');
    $tableNumber = trim($_POST['table_number'] ?? '');

    if ($name === '') {
        $error = 'Ism familiyani kiriting.';
    } elseif ($orderType === 'yetkazib berish' && $phone === '') {
        $error = 'Yetkazib berish uchun telefon raqamini kiriting.';
    } elseif ($orderType === 'yetkazib berish' && $address === '') {
        $error = 'Yetkazib berish uchun manzilni kiriting.';
    }
    else {
        $total = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total += $item['price'] * $item['quantity'];
        }

        $finalAddress = $orderType === 'stol' ? '' : $address;
        $finalTable = $orderType === 'stol' ? $tableNumber : null;

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO orders (customer_name, phone, address, table_number, total, order_type, status, delivery_status) VALUES (?, ?, ?, ?, ?, ?, 'yangi', ?)");
            $deliveryStatus = $orderType === 'yetkazib berish' ? 'new' : null;
            $stmt->execute([$name, $phone, $finalAddress, $finalTable, $total, $orderType === 'stol' ? 'table' : 'delivery', $deliveryStatus]);
            $orderId = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
            foreach ($_SESSION['cart'] as $item) {
                $stmt->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['quantity']]);
            }

            if ($finalTable) {
                $lock = $pdo->prepare("SELECT status FROM restaurant_tables WHERE table_number=? FOR UPDATE");
                $lock->execute([$finalTable]);
                $tableState = $lock->fetchColumn();
                if ($tableState === false || $tableState === 'band') { throw new RuntimeException('Bu stol hozir band. Boshqa stolni tanlang.'); }
                $pdo->prepare("UPDATE restaurant_tables SET status = 'band' WHERE table_number = ?")->execute([$finalTable]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            if (!isset($_SESSION['my_orders'])) $_SESSION['my_orders'] = [];
            $_SESSION['my_orders'][] = (int)$orderId;
            $_SESSION['my_orders'] = array_values(array_unique(array_slice($_SESSION['my_orders'], -20)));

            header('Location: success.php?order_id=' . $orderId);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Buyurtmani saqlashda xatolik yuz berdi.';
        }
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
<title>Buyurtma berish - FastFood</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=1">
<script src="../js/theme.js"></script>
<script src="../js/parallax.js?v=1" defer></script>
</head>
<body>
<header>
    <a class="logo" href="index.php"><img src="../img/logo-mark.svg" alt="" width="36" height="36"><span>FastFood</span></a>
    <div>
        <a href="index.php"><?= t('home') ?></a>
        <a href="cart.php"><?= t('cart') ?></a><a href="my_orders.php"><?= t('my_orders') ?></a>
        <span class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">☀️</span></span>
        <span class="lang-switch" id="langSwitch">
            <button onclick="setLanguage('uz')" data-lang="uz">UZ</button>
            <button onclick="setLanguage('ru')" data-lang="ru">RU</button>
            <button onclick="setLanguage('en')" data-lang="en">EN</button>
        </span>
    </div>
</header>

<main class="checkout-page">
  <div class="checkout-head">
    <a class="back-link" href="cart.php">← <?= htmlspecialchars(t('cart')) ?></a>
    <div class="checkout-step"><span class="active">1</span><i></i><span class="active">2</span><i></i><span>3</span></div>
  </div>

  <div class="checkout-layout">
    <section class="checkout-card checkout-form-card">
      <div class="checkout-title-row">
        <div>
          <span class="eyebrow">FASTFOOD</span>
          <h1><?= htmlspecialchars(t('checkout_title')) ?></h1>
          <p><?= htmlspecialchars(t('checkout_subtitle')) ?></p>
        </div>
        <div class="checkout-total-badge"><small><?= htmlspecialchars(t('total_amount')) ?></small><strong><?= number_format($total, 0, '', ' ') ?> so'm</strong></div>
      </div>

      <?php if ($error): ?>
        <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="post" action="checkout.php" id="checkoutForm" novalidate>
        <div class="checkout-section-title"><span>01</span><div><strong><?= htmlspecialchars(t('customer_info')) ?></strong><small><?= htmlspecialchars(t('customer_info_hint')) ?></small></div></div>
        <label><?= htmlspecialchars(t('name_label')) ?></label>
        <input class="checkout-input" type="text" name="name" required autocomplete="name" placeholder="<?= htmlspecialchars(t('name_placeholder')) ?>">

        <div class="checkout-section-title order-section"><span>02</span><div><strong><?= htmlspecialchars(t('order_type_label')) ?></strong><small><?= htmlspecialchars(t('order_type_hint')) ?></small></div></div>
        <div class="order-type-cards">
          <label class="order-type-card active" id="deliveryCard">
            <input type="radio" name="order_type" value="yetkazib berish" checked onchange="toggleOrderType()">
            <span class="type-icon">🛵</span><span><b><?= htmlspecialchars(t('delivery')) ?></b><small><?= htmlspecialchars(t('delivery_hint')) ?></small></span><em>✓</em>
          </label>
          <label class="order-type-card" id="dineInCard">
            <input type="radio" name="order_type" value="stol" onchange="toggleOrderType()">
            <span class="type-icon">🍽️</span><span><b><?= htmlspecialchars(t('dine_in_short')) ?></b><small><?= htmlspecialchars(t('dine_in_hint')) ?></small></span><em>✓</em>
          </label>
        </div>

        <div id="phoneField" class="field-conditional">
          <label><?= htmlspecialchars(t('phone_label')) ?> <span>*</span></label>
          <input class="checkout-input" type="tel" name="phone" id="phoneInput" placeholder="+998 90 123 45 67" autocomplete="tel">
          <small class="field-note">🔒 <?= htmlspecialchars(t('phone_hint')) ?></small>
        </div>

        <div id="addressField" class="field-conditional">
          <label><?= htmlspecialchars(t('address_label')) ?> <span>*</span></label>
          <textarea class="checkout-input" name="address" id="addressInput" rows="3" placeholder="<?= htmlspecialchars(t('address_placeholder')) ?>"></textarea>
        </div>

        <div id="tableField" class="field-conditional" style="display:none;">
          <div class="dinein-note"><span>🍽️</span><div><strong><?= htmlspecialchars(t('dine_in_ready')) ?></strong><small><?= htmlspecialchars(t('dine_in_ready_hint')) ?></small></div></div>
          <label><?= htmlspecialchars(t('table_label')) ?> <span class="optional">— <?= htmlspecialchars(t('optional')) ?></span></label>
          <select class="checkout-input" name="table_number">
            <option value=""><?= htmlspecialchars(t('no_table_number')) ?></option>
            <?php foreach ($tables as $t): ?>
              <option value="<?= htmlspecialchars($t['table_number']) ?>" <?= $t['status'] === 'band' ? 'disabled' : '' ?>>Stol #<?= htmlspecialchars($t['table_number']) ?> — <?= (int)$t['seats'] ?> <?= htmlspecialchars(t('seats')) ?><?= $t['status'] === 'band' ? ' — band' : '' ?></option>
            <?php endforeach; ?>
          </select>
          <small class="field-note">💡 <?= htmlspecialchars(t('table_optional_hint')) ?></small>
        </div>

        <button type="submit" class="checkout-submit"><span><?= htmlspecialchars(t('confirm_order')) ?></span><strong>→</strong></button>
      </form>
    </section>

    <aside class="checkout-card order-summary-card">
      <div class="summary-top"><span class="eyebrow"><?= htmlspecialchars(t('order_summary')) ?></span><span><?= count($_SESSION['cart']) ?> <?= htmlspecialchars(t('items')) ?></span></div>
      <div class="summary-items">
      <?php foreach ($_SESSION['cart'] as $item): ?>
        <div class="summary-item"><div class="summary-item-art">🍔</div><div class="summary-item-main"><strong><?= htmlspecialchars($item['name']) ?></strong><small><?= $item['quantity'] ?> × <?= number_format($item['price'], 0, '', ' ') ?> so'm</small></div><b><?= number_format($item['price'] * $item['quantity'], 0, '', ' ') ?></b></div>
      <?php endforeach; ?>
      </div>
      <div class="summary-total"><span><?= htmlspecialchars(t('total')) ?></span><strong><?= number_format($total, 0, '', ' ') ?> <small>so'm</small></strong></div>
      <div class="summary-trust"><div>⚡ <span><?= htmlspecialchars(t('fast_service')) ?></span></div><div>🔒 <span><?= htmlspecialchars(t('secure_order')) ?></span></div><div>❤️ <span><?= htmlspecialchars(t('fresh_food')) ?></span></div></div>
    </aside>
  </div>
</main>

<script>
function toggleOrderType() {
  const delivery = document.querySelector('input[name="order_type"]:checked').value === 'yetkazib berish';
  const phone = document.getElementById('phoneField');
  const address = document.getElementById('addressField');
  const table = document.getElementById('tableField');
  const phoneInput = document.getElementById('phoneInput');
  const addressInput = document.getElementById('addressInput');
  phone.style.display = delivery ? 'block' : 'none';
  address.style.display = delivery ? 'block' : 'none';
  table.style.display = delivery ? 'none' : 'block';
  phoneInput.required = delivery;
  addressInput.required = delivery;
  document.getElementById('deliveryCard').classList.toggle('active', delivery);
  document.getElementById('dineInCard').classList.toggle('active', !delivery);
}
toggleOrderType();
</script>

<!-- Til almashtirish: umumiy js/lang-switch.js fayli orqali (ishonchliroq) -->
<script src="../js/lang-switch.js"></script>
<script src="../js/global-i18n.js"></script><script src="../js/universal-i18n.js"></script></body>
</html>
