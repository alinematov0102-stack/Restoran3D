<?php
require_once __DIR__ . '/auth.php';
$currentPage = basename($_SERVER['PHP_SELF']);
$inDelivery  = basename(dirname($_SERVER['PHP_SELF'])) === 'delivery';
$base        = $inDelivery ? '../' : '';
$sidebarNewOrders = 0;
if (ff_can('receive_orders.php') || ff_can('waiter.php')) {
    $sidebarNewOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='yangi'")->fetchColumn();
}
$roleKey   = ff_role();
$roleLabel = FF_ROLES[$roleKey]['label'];
$userName  = $_SESSION['admin_name'] ?? ($_SESSION['admin_username'] ?? '');

// [fayl, belgi, nom, ikkinchi matn, qo'shimcha aktiv fayllar]
$menu = [
  ['Asosiy', [
    ['dashboard.php',      '⌂',  'Bosh sahifa',      '',            []],
    ['receive_orders.php', '🛎️', 'Buyurtma olish',   '',            []],
    ['kitchen.php',        '🍳', 'Oshxona',          'Cheklar',     []],
    ['waiter.php',         '✦',  'Afitsant',         'Tezkor POS',  []],
    ['delivery/index.php', '🛵', 'Yetkazib berish',  '',            []],
  ]],
  ['Boshqaruv', [
    ['catalog.php',        '▦',  'Katalog',          'Mahsulotlar', ['products.php','categories.php']],
    ['tables.php',         '▤',  'Stollar',          '',            []],
    ['hero.php',           '🖼️', 'Bosh banner',      '',            []],
    ['staff.php',          '👥', 'Xodimlar',         'Rollar',      []],
  ]],
  ['Moliya', [
    ['transactions.php',   '◌',  'Kirim / Chiqim',   '',            []],
    ['reports.php',        '▥',  'Hisobotlar',       '',            []],
    ['archive.php',        '▣',  'Arxiv',            '',            []],
    ['backup.php',         '💾', 'Backup',           '',            []],
  ]],
];
?>
<header class="admin-topbar">
  <button type="button" class="nav-toggle" id="navToggle" aria-label="Menyu" aria-controls="adminSidebar" aria-expanded="false">
    <span></span><span></span><span></span>
  </button>
  <div class="topbar-brand"><img src="<?= $base ?>../img/logo-mark.svg" alt="" width="30" height="30"><strong>FastFood</strong></div>
  <span class="topbar-role"><?= htmlspecialchars($roleLabel) ?></span>
</header>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<aside class="sidebar" id="adminSidebar">
  <div class="sidebar-brand">
    <div class="brand-mark"><img src="<?= $base ?>../img/logo-mark.svg" alt="" width="42" height="42"></div>
    <div><strong>FastFood</strong><small>Control Center</small></div>
    <button type="button" class="nav-close" id="navClose" aria-label="Yopish">×</button>
  </div>
<?php foreach ($menu as [$section, $items]):
    $visible = array_filter($items, fn($i) => $i[0] === 'staff.php' ? $roleKey === 'admin' : ff_can($i[0]));
    if (!$visible) continue; ?>
  <div class="sidebar-section-label" data-i18n="<?= $section ?>"><?= $section ?></div>
  <nav class="sidebar-nav">
<?php foreach ($visible as [$file, $icon, $name, $hint, $extra]):
        $key = $inDelivery ? 'delivery/' . $currentPage : $currentPage;
        $active = ($key === $file) || in_array($currentPage, $extra, true);
        $href = $base . $file; ?>
    <a href="<?= $href ?>" class="<?= $active ? 'active' : '' ?>"><span><?= $icon ?></span><span data-i18n="<?= $name ?>"><?= $name ?></span><?php if ($file === 'receive_orders.php' && $sidebarNewOrders > 0): ?><b class="nav-badge"><?= $sidebarNewOrders > 99 ? '99+' : $sidebarNewOrders ?></b><?php elseif ($hint): ?><em class="nav-hint" data-i18n="<?= $hint ?>"><?= $hint ?></em><?php endif; ?></a>
<?php endforeach; ?>
  </nav>
<?php endforeach; ?>
  <div class="sidebar-spacer"></div>
  <div class="sidebar-user"><b><?= htmlspecialchars($userName) ?></b><small><?= htmlspecialchars($roleLabel) ?></small></div>
  <div class="sidebar-tools">
    <button type="button" class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">☀️</span><span data-i18n="Rejim">Rejim</span></button>
    <div class="lang-switch" id="langSwitch"><button onclick="setLanguage('uz')" data-lang="uz">UZ</button><button onclick="setLanguage('ru')" data-lang="ru">RU</button><button onclick="setLanguage('en')" data-lang="en">EN</button></div>
    <a class="logout-link" href="<?= $base ?>logout.php"><span>↪</span><span data-i18n="Chiqish">Chiqish</span></a>
  </div>
</aside>

<script>
(function(){
  var b=document.body, t=document.getElementById('navToggle'), bd=document.getElementById('sidebarBackdrop'), c=document.getElementById('navClose');
  function set(o){b.classList.toggle('nav-open',o);t.setAttribute('aria-expanded',o?'true':'false')}
  t.addEventListener('click',function(){set(!b.classList.contains('nav-open'))});
  bd.addEventListener('click',function(){set(false)});
  c.addEventListener('click',function(){set(false)});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')set(false)});
  document.querySelectorAll('#adminSidebar .sidebar-nav a').forEach(function(a){a.addEventListener('click',function(){set(false)})});
  window.addEventListener('resize',function(){if(window.innerWidth>1024)set(false)});
})();
</script>
<script src="<?= $base ?>../js/admin-live.js" defer></script>
<script src="<?= $base ?>../js/lang-switch.js" defer></script>
<script src="<?= $base ?>../js/admin-i18n.js" defer></script>
<script src="<?= $base ?>../js/global-i18n.js" defer></script><script src="<?= $base ?>../js/universal-i18n.js" defer></script>
