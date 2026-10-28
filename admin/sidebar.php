<?php
$sidebarNewOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='yangi'")->fetchColumn();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-mark"><img src="../img/logo-mark.svg" alt="" width="42" height="42"></div>
    <div><strong>FastFood</strong><small>Control Center</small></div>
  </div>
  <div class="sidebar-section-label" data-i18n="Asosiy">Asosiy</div>
  <nav class="sidebar-nav">
    <a href="dashboard.php" class="<?= $currentPage==='dashboard.php'?'active':'' ?>"><span>⌂</span><span data-i18n="Bosh sahifa">Bosh sahifa</span></a>
    <a href="receive_orders.php" class="<?= $currentPage==='receive_orders.php'?'active':'' ?>"><span>🛎️</span><span data-i18n="Buyurtma olish">Buyurtma olish</span><?php if($sidebarNewOrders>0): ?><b class="nav-badge"><?= $sidebarNewOrders>99?'99+':$sidebarNewOrders ?></b><?php endif; ?></a>
    <a href="kitchen.php" class="<?= $currentPage==='kitchen.php'?'active':'' ?>"><span>🍳</span><span data-i18n="Oshxona">Oshxona</span><em class="nav-hint" data-i18n="Cheklar">Cheklar</em></a>
    <a href="waiter.php" class="<?= $currentPage==='waiter.php'?'active':'' ?>"><span>✦</span><span data-i18n="Afitsant">Afitsant</span><em class="nav-hint" data-i18n="Tezkor POS">Tezkor POS</em></a>
  </nav>
  <div class="sidebar-section-label" data-i18n="Boshqaruv">Boshqaruv</div>
  <nav class="sidebar-nav">
    <a href="catalog.php" class="<?= in_array($currentPage,['catalog.php','products.php','categories.php'])?'active':'' ?>"><span>▦</span><span data-i18n="Katalog">Katalog</span><em class="nav-hint" data-i18n="Mahsulotlar">Mahsulotlar</em></a>
    <a href="tables.php" class="<?= $currentPage==='tables.php'?'active':'' ?>"><span>▤</span><span data-i18n="Stollar">Stollar</span></a>
  </nav>
  <div class="sidebar-section-label" data-i18n="Moliya">Moliya</div>
  <nav class="sidebar-nav">
    <a href="transactions.php" class="<?= $currentPage==='transactions.php'?'active':'' ?>"><span>◌</span><span data-i18n="Kirim / Chiqim">Kirim / Chiqim</span></a>
    <a href="reports.php" class="<?= $currentPage==='reports.php'?'active':'' ?>"><span>▥</span><span data-i18n="Hisobotlar">Hisobotlar</span></a>
    <a href="archive.php" class="<?= $currentPage==='archive.php'?'active':'' ?>"><span>▣</span><span data-i18n="Arxiv">Arxiv</span></a>
    <a href="backup.php" class="<?= $currentPage==='backup.php'?'active':'' ?>"><span>💾</span><span data-i18n="Backup">Backup</span></a>
  </nav>
  <div class="sidebar-spacer"></div>
  <div class="sidebar-tools">
    <button type="button" class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">☀️</span><span data-i18n="Rejim">Rejim</span></button>
    <div class="lang-switch" id="langSwitch"><button onclick="setLanguage('uz')" data-lang="uz">UZ</button><button onclick="setLanguage('ru')" data-lang="ru">RU</button><button onclick="setLanguage('en')" data-lang="en">EN</button></div>
    <a class="logout-link" href="logout.php"><span>↪</span><span data-i18n="Chiqish">Chiqish</span></a>
  </div>
</aside>

<script src="../js/admin-live.js" defer></script>
<script src="../js/lang-switch.js" defer></script>
<script src="../js/admin-i18n.js" defer></script>
<script src="../js/global-i18n.js" defer></script><script src="../js/universal-i18n.js" defer></script>
