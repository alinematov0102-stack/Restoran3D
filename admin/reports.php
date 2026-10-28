<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/lang.php';

$from = $_GET['from'] ?? date('Y-m-d');
$to = $_GET['to'] ?? date('Y-m-d');
if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $from)) $from=date('Y-m-d');
if (!preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $to)) $to=date('Y-m-d');
if ($from > $to) { [$from,$to]=[$to,$from]; }

$where = "DATE(created_at) BETWEEN ? AND ? AND status != 'bekor qilingan'";
$st=$pdo->prepare("SELECT COUNT(*) orders, COALESCE(SUM(total),0) revenue, COALESCE(AVG(total),0) avg_check FROM orders WHERE $where");
$st->execute([$from,$to]); $summary=$st->fetch() ?: ['orders'=>0,'revenue'=>0,'avg_check'=>0];

$cancelSt=$pdo->prepare("SELECT COUNT(*) FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status='bekor qilingan'");
$cancelSt->execute([$from,$to]); $cancelled=(int)$cancelSt->fetchColumn();

$topSt=$pdo->prepare("SELECT oi.product_name, SUM(oi.quantity) qty, SUM(oi.quantity*oi.price) revenue
 FROM order_items oi JOIN orders o ON o.id=oi.order_id
 WHERE DATE(o.created_at) BETWEEN ? AND ? AND o.status!='bekor qilingan'
 GROUP BY oi.product_name ORDER BY qty DESC, revenue DESC LIMIT 10");
$topSt->execute([$from,$to]); $top=$topSt->fetchAll();

$dailySt=$pdo->prepare("SELECT DATE(created_at) day, COUNT(*) orders, COALESCE(SUM(total),0) revenue
 FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND status!='bekor qilingan'
 GROUP BY DATE(created_at) ORDER BY day ASC");
$dailySt->execute([$from,$to]); $daily=$dailySt->fetchAll();

if (isset($_GET['export']) && $_GET['export']==='csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="fastfood-report-'.$from.'_'.$to.'.csv"');
    echo "\\xEF\\xBB\\xBF";
    $out=fopen('php://output','w');
    fputcsv($out,['FastFood report',$from,$to]);
    fputcsv($out,['Buyurtmalar',(int)$summary['orders']]);
    fputcsv($out,['Tushum',(float)$summary['revenue']]);
    fputcsv($out,['O‘rtacha chek',(float)$summary['avg_check']]);
    fputcsv($out,['Bekor qilingan',$cancelled]);
    fputcsv($out,[]); fputcsv($out,['Mahsulot','Soni','Tushum']);
    foreach($top as $r) fputcsv($out,[$r['product_name'],(int)$r['qty'],(float)$r['revenue']]);
    fclose($out); exit;
}
$lang=ff_current_lang();
function money($n){return number_format((float)$n,0,'',' ').' so‘m';}
?>
<!doctype html><html lang="<?=htmlspecialchars($lang)?>"><head><meta charset="utf-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FastFood — Hisobot</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=1"><script src="../js/theme.js"></script><script src="../js/admin-i18n.js" defer></script><script src="../js/parallax.js?v=1" defer></script>
</head><body>
<?php include __DIR__.'/sidebar.php'; ?>
<main class="admin-content">
<div class="page-topline"><div><div class="eyebrow" data-i18n="HISOBOT">HISOBOT</div><h1 data-i18n="Savdo hisobotlari">Savdo hisobotlari</h1><p class="admin-muted" data-i18n="Kunlik savdo, o‘rtacha chek va eng ko‘p sotilgan mahsulotlarni ko‘ring.">Kunlik savdo, o‘rtacha chek va eng ko‘p sotilgan mahsulotlarni ko‘ring.</p></div>
<div class="page-actions"><a class="btn btn-secondary" href="reports.php?from=<?=$from?>&to=<?=$to?>&export=csv">⬇ <span data-i18n="CSV yuklash">CSV yuklash</span></a></div></div>
<form class="date-filter" method="get" style="margin-bottom:18px"><input type="date" name="from" value="<?=$from?>"><span>→</span><input type="date" name="to" value="<?=$to?>"><button class="btn" data-i18n="Ko‘rsatish">Ko‘rsatish</button><a class="btn btn-secondary" href="reports.php?from=<?=date('Y-m-d')?>&to=<?=date('Y-m-d')?>" data-i18n="Bugun">Bugun</a><a class="btn btn-secondary" href="reports.php?from=<?=date('Y-m-d',strtotime('-6 days'))?>&to=<?=date('Y-m-d')?>" data-i18n="7 kun">7 kun</a></form>
<section class="metric-strip metric-strip-pro"><div><span data-i18n="Buyurtmalar">Buyurtmalar</span><strong><?=number_format((int)$summary['orders'])?></strong><small><?=htmlspecialchars($from)?> → <?=htmlspecialchars($to)?></small></div><div><span data-i18n="Tushum">Tushum</span><strong><?=money($summary['revenue'])?></strong><small data-i18n="Bekor qilinganlar hisoblanmagan">Bekor qilinganlar hisoblanmagan</small></div><div><span data-i18n="O‘rtacha chek">O‘rtacha chek</span><strong><?=money($summary['avg_check'])?></strong><small data-i18n="Bir buyurtmaga o‘rtacha">Bir buyurtmaga o‘rtacha</small></div><div><span data-i18n="Bekor qilingan">Bekor qilingan</span><strong><?=$cancelled?></strong><small data-i18n="Tanlangan davr">Tanlangan davr</small></div></section>
<section class="dashboard-grid-pro"><div class="admin-panel"><div class="panel-heading"><div><div class="panel-kicker" data-i18n="TOP 10">TOP 10</div><h2 data-i18n="Eng ko‘p sotilgan mahsulotlar">Eng ko‘p sotilgan mahsulotlar</h2></div></div><div class="table-scroll"><table><thead><tr><th>#</th><th data-i18n="Mahsulot">Mahsulot</th><th data-i18n="Soni">Soni</th><th data-i18n="Tushum">Tushum</th></tr></thead><tbody><?php foreach($top as $i=>$r):?><tr><td><?=($i+1)?></td><td><strong><?=htmlspecialchars($r['product_name'])?></strong></td><td><?=number_format((int)$r['qty'])?></td><td><?=money($r['revenue'])?></td></tr><?php endforeach;?><?php if(!$top):?><tr><td colspan="4" class="empty-table" data-i18n="Ma’lumot topilmadi.">Ma’lumot topilmadi.</td></tr><?php endif;?></tbody></table></div></div>
<div class="admin-panel"><div class="panel-heading"><div><div class="panel-kicker" data-i18n="KUNLAR BO‘YICHA">KUNLAR BO‘YICHA</div><h2 data-i18n="Kunlik savdo">Kunlik savdo</h2></div></div><div class="table-scroll"><table><thead><tr><th data-i18n="Sana">Sana</th><th data-i18n="Buyurtmalar">Buyurtmalar</th><th data-i18n="Tushum">Tushum</th></tr></thead><tbody><?php foreach($daily as $d):?><tr><td><?=htmlspecialchars($d['day'])?></td><td><?=number_format((int)$d['orders'])?></td><td><?=money($d['revenue'])?></td></tr><?php endforeach;?><?php if(!$daily):?><tr><td colspan="3" class="empty-table" data-i18n="Ma’lumot topilmadi.">Ma’lumot topilmadi.</td></tr><?php endif;?></tbody></table></div></div></section>
<div class="admin-panel" style="margin-top:18px"><div class="panel-heading"><div><div class="panel-kicker" data-i18n="TEZKOR MASLAHAT">TEZKOR MASLAHAT</div><h2 data-i18n="Ishlashni qulaylashtiring">Ishlashni qulaylashtiring</h2></div></div><p class="admin-muted" data-i18n="Hisobotni CSV qilib Excelda ochishingiz mumkin. Oshxonada buyurtma statusini tayyorlanmoqda → tayyor ketma-ketligida boshqaring.">Hisobotni CSV qilib Excelda ochishingiz mumkin. Oshxonada buyurtma statusini tayyorlanmoqda → tayyor ketma-ketligida boshqaring.</p></div>
</main><script src="../js/lang-switch.js"></script></body></html>
