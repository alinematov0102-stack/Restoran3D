<?php
session_start();
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/lang.php';

$orderId = (int)($_GET['order_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order) { header('Location: index.php'); exit; }
?>
<!doctype html>
<html lang="<?= htmlspecialchars(ff_current_lang()) ?>">
<head>
<meta charset="utf-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Buyurtma qabul qilindi — FastFood</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=1"><script src="../js/theme.js"></script>
<style>
.success-wrap{min-height:calc(100vh - 80px);display:grid;place-items:center;padding:40px 18px}.success-box{width:min(620px,100%);background:var(--surface);border:1px solid var(--line);border-radius:28px;box-shadow:var(--shadow-lg);padding:42px;text-align:center}.success-icon{width:76px;height:76px;margin:0 auto 20px;border-radius:24px;display:grid;place-items:center;background:rgba(22,163,74,.12);color:var(--success);font-size:38px;font-weight:900}.success-box h1{font-size:34px;margin-bottom:10px}.success-box p{color:var(--muted);max-width:470px;margin:0 auto 24px}.order-number{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border-radius:12px;background:var(--surface-2);border:1px solid var(--line);font-weight:800;margin-bottom:26px}.success-actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}.success-actions .btn{min-width:180px}.success-note{margin-top:22px;color:var(--muted);font-size:13px}
@media(max-width:560px){.success-box{padding:30px 20px}.success-box h1{font-size:27px}.success-actions{flex-direction:column}.success-actions .btn{width:100%}}
</style>
<script src="../js/parallax.js?v=1" defer></script>
</head><body>
<header><a class="logo" href="index.php"><img src="../img/logo-mark.svg" alt="" width="36" height="36"><span>FastFood</span></a><div style="display:flex;align-items:center;gap:10px"><a href="index.php"><?= t('home') ?></a><button type="button" class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">🌙</span></button><span class="lang-switch" id="langSwitch"><button onclick="setLanguage('uz')" data-lang="uz">UZ</button><button onclick="setLanguage('ru')" data-lang="ru">RU</button><button onclick="setLanguage('en')" data-lang="en">EN</button></span></div></header>
<main class="success-wrap"><section class="success-box"><div class="success-icon">✓</div><h1 data-i18n="Buyurtma qabul qilindi!">Buyurtma qabul qilindi!</h1><p data-i18n="Buyurtmangiz muvaffaqiyatli qabul qilindi. Buyurtma holatini Buyurtmalarim bo‘limidan kuzatishingiz mumkin.">Buyurtmangiz muvaffaqiyatli qabul qilindi. Buyurtma holatini Buyurtmalarim bo‘limidan kuzatishingiz mumkin.</p><div class="order-number">🧾 <span data-i18n="Buyurtma ID">Buyurtma ID</span> <b style="font-family:monospace">FF-<?= str_pad($orderId,6,'0',STR_PAD_LEFT) ?></b></div><div class="success-actions"><a class="btn" href="my_orders.php" data-i18n="Buyurtmalarimni ko‘rish">Buyurtmalarimni ko‘rish</a><a class="btn btn-secondary" href="products.php" data-i18n="Yana buyurtma berish">Yana buyurtma berish</a></div><div class="success-note" data-i18n="Chek va moliyaviy hujjatlar admin panelida saqlanadi.">Chek va moliyaviy hujjatlar admin panelida saqlanadi.</div></section></main>
<script src="../js/lang-switch.js"></script><script src="../js/admin-i18n.js"></script>
<script src="../js/global-i18n.js"></script><script src="../js/universal-i18n.js"></script></body></html>
