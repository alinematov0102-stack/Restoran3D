<?php
session_start();require_once __DIR__.'/auth.php';ff_guard();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/lang.php';
require_once __DIR__ . '/daily_close.php';
ff_close_past_days($pdo);
$agg=$pdo->query("SELECT COUNT(*) c, COALESCE(SUM(DATE(created_at)=CURDATE()),0) today_c, COALESCE(SUM(CASE WHEN DATE(created_at)=CURDATE() AND status!='bekor qilingan' THEN total END),0) today_r, COALESCE(SUM(status='yangi'),0) new_c, COALESCE(SUM(CASE WHEN status!='bekor qilingan' THEN total END),0) total_r FROM orders")->fetch();
$totalOrders=(int)$agg['c'];$todayOrders=(int)$agg['today_c'];$todayRevenue=(float)$agg['today_r'];$newOrders=(int)$agg['new_c'];$totalRevenue=(float)$agg['total_r'];
$totalProducts=(int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$tr=$pdo->query("SELECT COALESCE(SUM(CASE WHEN type='kirim' THEN amount END),0) i, COALESCE(SUM(CASE WHEN type='chiqim' THEN amount END),0) o FROM transactions")->fetch();
$totalIn=(float)$tr['i'];$totalOut=(float)$tr['o'];
$balance=$totalIn-$totalOut;
$byDay=[];foreach($pdo->query("SELECT DATE(created_at) d, COALESCE(SUM(total),0) v FROM orders WHERE created_at>=DATE_SUB(CURDATE(),INTERVAL 6 DAY) AND status!='bekor qilingan' GROUP BY DATE(created_at)")->fetchAll() as $r){$byDay[$r['d']]=(float)$r['v'];}
$days=[];
for($i=6;$i>=0;$i--){$d=date('Y-m-d',strtotime("-$i days"));$days[]=['label'=>date('d M',strtotime($d)),'value'=>$byDay[$d]??0.0];}
$max=max(1,max(array_column($days,'value')));
$lang=ff_current_lang();
?>
<!doctype html><html lang="<?=$lang?>"><head><meta charset="utf-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FastFood — Dashboard</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=2"><script src="../js/theme.js"></script><script src="../js/admin-i18n.js" defer></script><script src="../js/parallax.js?v=1" defer></script>
</head><body>
<?php include __DIR__.'/sidebar.php'; ?>
<main class="admin-content">
  <div class="page-topline"><div><div class="eyebrow" data-i18n="BOSH SAHIFA">BOSH SAHIFA</div><h1 data-i18n="Bugungi boshqaruv markazi">Bugungi boshqaruv markazi</h1><p class="admin-muted" data-i18n="Buyurtmalar, savdo va moliyaviy holatni bir oynadan kuzating.">Buyurtmalar, savdo va moliyaviy holatni bir oynadan kuzating.</p></div><div class="page-actions"><a class="btn" href="receive_orders.php" data-i18n="🛎️ Buyurtmalarni olish">🛎️ Buyurtmalarni olish</a><a class="btn btn-secondary" href="receive_orders.php" data-i18n="Buyurtmalarni ko‘rish">Buyurtmalarni ko‘rish</a></div></div>
  <?php if($newOrders): ?><div class="order-alert"><div style="display:flex;align-items:center;gap:12px"><span class="pulse"></span><div><strong>🔔 <?=$newOrders?> <span data-i18n="ta yangi buyurtma keldi">ta yangi buyurtma keldi</span></strong><small data-i18n="Yangi buyurtmalarni darhol ko‘rib chiqing.">Yangi buyurtmalarni darhol ko‘rib chiqing.</small></div></div><a class="btn btn-sm" href="receive_orders.php" data-i18n="Ochish">Ochish</a></div><?php endif; ?>
  <section class="metric-strip metric-strip-pro">
    <div><span data-i18n="Bugungi buyurtma">Bugungi buyurtma</span><strong><?=$todayOrders?></strong><small data-i18n="Jami faol savdo">Jami faol savdo</small></div>
    <div><span data-i18n="Bugungi tushum">Bugungi tushum</span><strong><?=number_format($todayRevenue,0,'',' ')?> <small>so‘m</small></strong><small data-i18n="Bekor qilinganlar chiqarilgan">Bekor qilinganlar chiqarilgan</small></div>
    <div><span data-i18n="Yangi buyurtmalar">Yangi buyurtmalar</span><strong><?=$newOrders?></strong><small data-i18n="Jonli bildirishnoma yoqilgan">Jonli bildirishnoma yoqilgan</small></div>
    <div><span data-i18n="Balans">Balans</span><strong><?=number_format($balance,0,'',' ')?> <small>so‘m</small></strong><small data-i18n="Kirim minus chiqim">Kirim minus chiqim</small></div>
  </section>
  <section class="dashboard-grid-pro">
    <div class="admin-panel chart-panel"><div class="panel-heading"><div><div class="panel-kicker" data-i18n="SAVDO">SAVDO</div><h2 data-i18n="So‘nggi 7 kun">So‘nggi 7 kun</h2></div><strong class="chart-total"><?=number_format($totalRevenue,0,'',' ')?> so‘m</strong></div><div class="bar-chart"><?php foreach($days as $d): ?><div class="bar-col"><span class="bar-value"><?=number_format($d['value'],0,'',' ')?></span><div class="bar-track"><i style="height:<?=max(4,round($d['value']/$max*100))?>%"></i></div><small><?=$d['label']?></small></div><?php endforeach; ?></div></div>
    <div class="admin-panel quick-panel"><div class="panel-kicker" data-i18n="TEZKOR AMALLAR">TEZKOR AMALLAR</div><h2 data-i18n="Ish jarayoni">Ish jarayoni</h2><a href="waiter.php"><span>🧑‍🍳</span><b data-i18n="Afitsant POS">Afitsant POS</b><small data-i18n="Stol va buyurtma olish">Stol va buyurtma olish</small><em>→</em></a><a href="catalog.php"><span>🍔</span><b data-i18n="Katalog">Katalog</b><small data-i18n="Mahsulot va kategoriyalar">Mahsulot va kategoriyalar</small><em>→</em></a><a href="tables.php"><span>🪑</span><b data-i18n="Stollar">Stollar</b><small data-i18n="Bo‘sh, band, bron holati">Bo‘sh, band, bron holati</small><em>→</em></a><a href="transactions.php"><span>💳</span><b data-i18n="Moliya">Moliya</b><small data-i18n="Kirim va chiqim">Kirim va chiqim</small><em>→</em></a><a href="reports.php"><span>📊</span><b data-i18n="Hisobotlar">Hisobotlar</b><small data-i18n="Savdo va top mahsulotlar">Savdo va top mahsulotlar</small><em>→</em></a></div>
  </section>
  <section class="finance-mini-grid"><div class="mini-finance in"><span data-i18n="Kirim">Kirim</span><b><?=number_format($totalIn,0,'',' ')?> so‘m</b></div><div class="mini-finance out"><span data-i18n="Chiqim">Chiqim</span><b><?=number_format($totalOut,0,'',' ')?> so‘m</b></div><div class="mini-finance"><span data-i18n="Jami buyurtmalar">Jami buyurtmalar</span><b><?=$totalOrders?></b></div><div class="mini-finance"><span data-i18n="Mahsulotlar">Mahsulotlar</span><b><?=$totalProducts?></b></div></section>
<div class="admin-panel" style="margin-top:18px"><div class="panel-heading"><div><div class="panel-kicker">KITCHEN</div><h2>Oshxona</h2></div><a class="btn" href="kitchen.php">🍳 Buyurtmalarni ko‘rish</a></div><p class="admin-muted">Yangi buyurtmalar oshxona cheki ko‘rinishida shu yerda chiqadi.</p></div>
</main><script src="../js/lang-switch.js"></script></body></html>
