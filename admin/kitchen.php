<?php
session_start();require_once __DIR__.'/auth.php';ff_guard();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/lang.php';

$orders = $pdo->query("SELECT * FROM orders WHERE status IN ('qabul qilingan','tayyorlanmoqda') ORDER BY created_at ASC")->fetchAll();
?>
<!doctype html>
<html lang="<?=htmlspecialchars(ff_current_lang())?>">
<head>
<meta charset="utf-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>FastFood — Oshxona</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=2"><script src="../js/theme.js"></script>
<style>
.kitchen-head{display:flex;justify-content:space-between;align-items:center;gap:18px;margin-bottom:22px}.kitchen-head h1{margin:0;color:var(--text)}
.kitchen-controls{display:flex;gap:8px;align-items:center}.kitchen-controls .auto{display:flex;align-items:center;gap:7px;padding:10px 12px;border:1px solid var(--line);background:var(--surface);color:var(--text);border-radius:12px;font-size:12px;font-weight:800}
.kitchen-grid{display:grid;grid-template-columns:repeat(3,minmax(280px,1fr));gap:18px}
.kitchen-ticket{background:var(--surface);color:var(--text);border:1px solid var(--line);border-radius:18px;padding:20px;box-shadow:var(--shadow);position:relative;transition:.2s ease}.kitchen-ticket:hover{transform:translateY(-2px);box-shadow:var(--shadow-lg)}.kitchen-ticket.new{border:2px solid var(--primary);box-shadow:0 12px 34px rgba(239,68,68,.12)}
.ticket-top{display:flex;justify-content:space-between;gap:12px;border-bottom:1px dashed var(--line);padding-bottom:14px;margin-bottom:14px}.ticket-top b{display:block;font-size:24px;color:var(--text)}.ticket-top small{display:block;color:var(--muted);font-size:10px;font-weight:700;letter-spacing:.05em}.ticket-top .state{padding:6px 9px;border-radius:999px;background:var(--surface-2);color:var(--text);font-size:10px}
.ticket-meta{display:grid;grid-template-columns:1fr 1fr;gap:9px;margin-bottom:14px}.ticket-meta div{background:var(--surface-2);border:1px solid var(--line);border-radius:11px;padding:9px}.ticket-meta small{display:block;color:var(--muted);font-size:9px;margin-bottom:2px}.ticket-meta b{font-size:12px;color:var(--text)}
.ticket-items{border-top:1px dashed var(--line)}.ticket-item{display:grid;grid-template-columns:42px 1fr auto;align-items:center;gap:8px;padding:11px 0;border-bottom:1px dashed var(--line);font-size:13px}.ticket-item strong{font-size:16px;color:var(--primary)}.ticket-item span{font-weight:700;color:var(--text)}.ticket-item b{font-size:11px;color:var(--muted);white-space:nowrap}
.ticket-note{margin-top:12px;padding:11px 12px;border-radius:11px;background:rgba(245,158,11,.09);border:1px solid rgba(245,158,11,.16);font-size:11px;color:var(--text)}.ticket-total{margin-top:12px;padding:13px;border-radius:12px;background:rgba(239,68,68,.07);border:1px solid rgba(239,68,68,.14);display:flex;justify-content:space-between;color:var(--text)}.ticket-total b:last-child{color:var(--primary);font-size:16px}
.ticket-actions{display:flex;gap:8px;margin-top:14px}.ticket-actions a,.ticket-actions button{flex:1;border:0;border-radius:11px;padding:11px;font-weight:800;cursor:pointer;text-decoration:none;text-align:center}.ticket-actions .print{background:var(--surface-2);border:1px solid var(--line);color:var(--text)}.ticket-actions .done{background:var(--primary);color:#fff}.empty-kitchen{padding:70px;text-align:center;border:1px dashed var(--line);border-radius:18px;color:var(--muted);background:var(--surface)}
@media(max-width:1050px){.kitchen-grid{grid-template-columns:repeat(2,minmax(280px,1fr))}}@media(max-width:700px){.kitchen-grid{grid-template-columns:1fr}.kitchen-head{align-items:flex-start;flex-direction:column}.ticket-meta{grid-template-columns:1fr}}
@media print{html,body{background:#fff!important;color:#000!important}.sidebar,.kitchen-head,.ticket-actions{display:none!important}.admin-content{margin:0!important;padding:0!important}.kitchen-grid{display:block}.kitchen-ticket{display:none!important;box-shadow:none;border:0!important;margin:0}.kitchen-ticket.printing{display:block!important;width:80mm;max-width:80mm;margin:0 auto;padding:5mm;background:#fff;color:#000;border-radius:0}.kitchen-ticket.printing *{color:#000!important}.ticket-meta div{background:#fff;border:1px solid #ddd}.ticket-item{padding:5px 0}.ticket-total,.ticket-note{background:#fff;border:1px solid #ddd}}
</style>
<script src="../js/parallax.js?v=1" defer></script>
</head>
<body>
<?php include __DIR__.'/sidebar.php'; ?>
<main class="admin-content">
<div class="kitchen-head"><div><div class="eyebrow">KITCHEN</div><h1 data-i18n="Oshxona buyurtmalari">Oshxona buyurtmalari</h1><p class="admin-muted" data-i18n="Yangi buyurtma tushishi bilan shu yerda chek ko‘rinishida chiqadi.">Yangi buyurtma tushishi bilan shu yerda chek ko‘rinishida chiqadi.</p></div><div class="kitchen-controls"><label class="auto"><input type="checkbox" id="autoPrint"> Avto chop etish</label><button class="btn btn-secondary" onclick="location.reload()">↻ Yangilash</button><button class="btn btn-secondary" onclick="enableKitchenNotifications()">🔔 Bildirishnoma</button></div></div>
<div id="kitchenGrid" class="kitchen-grid">
<?php foreach($orders as $o): ?>
<?php $st=$pdo->prepare('SELECT * FROM order_items WHERE order_id=?');$st->execute([$o['id']]);$items=$st->fetchAll(); ?>
<article class="kitchen-ticket <?= $o['status']==='yangi'?'new':'' ?>" data-id="<?=$o['id']?>">
<div class="ticket-top"><div><small>FASTFOOD • <?=htmlspecialchars($o['created_at'])?></small><b>FF-<?=str_pad($o['id'],6,'0',STR_PAD_LEFT)?></b></div><div><small>HOLAT</small><b class="state"><?=htmlspecialchars($o['status'])?></b></div></div>
<div class="ticket-meta"><div><small>Buyurtma ID</small><b>FF-<?=str_pad($o['id'],6,'0',STR_PAD_LEFT)?></b></div><div><small>Mijoz</small><b><?=htmlspecialchars($o['customer_name'])?></b></div><div><small>Telefon</small><b><?=htmlspecialchars($o['phone'] ?: '—')?></b></div><div><small>Stol / Yetkazish</small><b><?=htmlspecialchars($o['table_number'] ? '#'.$o['table_number'] : 'Dostavka')?></b></div></div>
<div class="ticket-items"><?php foreach($items as $it): ?><div class="ticket-item"><strong><?=htmlspecialchars($it['quantity'])?> ×</strong><span><?=htmlspecialchars($it['product_name'])?></span><b><?=number_format($it['price']*$it['quantity'],0,'',' ')?> so‘m</b></div><?php endforeach; ?></div>
<?php if(!empty($o['address'])): ?><div class="ticket-note"><b>📍 Manzil:</b> <?=htmlspecialchars($o['address'])?></div><?php endif; ?>
<div class="ticket-total"><b>JAMI</b><b><?=number_format($o['total'],0,'',' ')?> so‘m</b></div>
<div class="ticket-actions"><?php if($o['status']==='qabul qilingan'): ?><button class="done" onclick="sendKitchen(<?=$o['id']?>)">🍳 Tayyorlashni boshlash</button><?php elseif($o['status']==='tayyorlanmoqda'): ?><button class="done" onclick="readyOrder(<?=$o['id']?>)">✓ Buyurtma tayyor</button><?php endif; ?><button class="print" onclick="printTicket(<?=$o['id']?>)">🖨 Chop etish</button></div>
</article>
<?php endforeach; ?>
</div>
<?php if(!$orders): ?><div class="empty-kitchen"><div style="font-size:42px">🍳</div><h2 data-i18n="Oshxona hozir bo‘sh">Oshxona hozir bo‘sh</h2><p data-i18n="Yangi buyurtmalar shu yerda avtomatik paydo bo‘ladi.">Yangi buyurtmalar shu yerda avtomatik paydo bo‘ladi.</p></div><?php endif; ?>
</main>
<script src="../js/lang-switch.js"></script>
<script>
let known=new Set(<?=json_encode(array_map(fn($o)=>(int)$o['id'],$orders))?>);
function printTicket(id){document.querySelectorAll('.kitchen-ticket').forEach(x=>x.classList.remove('printing'));const el=document.querySelector('.kitchen-ticket[data-id="'+id+'"]');if(!el)return;el.classList.add('printing');window.print();setTimeout(()=>el.classList.remove('printing'),800)}
async function acceptOrder(id){const fd=new FormData();fd.append('order_id',id);fd.append('action','accept');const r=await fetch('kitchen_action.php',{method:'POST',body:fd,cache:'no-store'});if(r.ok) location.reload()}
async function sendKitchen(id){const fd=new FormData();fd.append('order_id',id);fd.append('action','send_kitchen');const r=await fetch('kitchen_action.php',{method:'POST',body:fd,cache:'no-store'});if(r.ok) location.reload()}
async function readyOrder(id){const fd=new FormData();fd.append('order_id',id);fd.append('action','ready');const r=await fetch('kitchen_action.php',{method:'POST',body:fd,cache:'no-store'});if(r.ok) location.reload()}
async function enableKitchenNotifications(){try{if('Notification' in window){await Notification.requestPermission();}}catch(e){}}
async function pollKitchen(){try{const r=await fetch('kitchen_feed.php?_='+Date.now(),{cache:'no-store'});const d=await r.json();if(!d.ok)return;const ids=new Set(d.orders.map(o=>Number(o.id)));let fresh=d.orders.find(o=>!known.has(Number(o.id)));known=ids;if(fresh){document.title='🔔 Yangi buyurtma — FastFood'; try{if(document.hidden && 'Notification' in window && Notification.permission==='granted'){new Notification('FastFood',{body:'Yangi buyurtma FF-'+String(fresh.id).padStart(6,'0')});}}catch(e){} location.reload();}}catch(e){}}
setInterval(pollKitchen,5000);
</script>
</body></html>
