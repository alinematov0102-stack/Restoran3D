<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/lang.php';

if (!isset($_SESSION['waiter_cart'])) $_SESSION['waiter_cart'] = [];
function waiter_json(PDO $pdo, bool $ok=true, string $error=''): void {
    $cart=$_SESSION['waiter_cart']??[]; $items=[]; $total=0; $count=0;
    $ids=array_values(array_filter(array_map('intval',array_keys($cart))));
    if($ids){
        $ph=implode(',',array_fill(0,count($ids),'?'));
        $st=$pdo->prepare("SELECT id,name,name_ru,name_en,price,image FROM products WHERE id IN ($ph)"); $st->execute($ids);
        $map=[]; foreach($st->fetchAll() as $r) $map[(int)$r['id']]=$r;
        $lang=ff_current_lang(); $col=$lang==='ru'?'name_ru':($lang==='en'?'name_en':'name');
        foreach($cart as $id=>$qty){$id=(int)$id;$qty=(int)$qty;if($qty<1||!isset($map[$id]))continue;$r=$map[$id];$price=(float)$r['price'];$line=$price*$qty;$total+=$line;$count+=$qty;$items[]=['id'=>$id,'name'=>($r[$col]?:$r['name']),'price'=>$price,'image'=>$r['image'],'quantity'=>$qty,'line_total'=>$line];}
    }
    header('Content-Type: application/json; charset=utf-8'); echo json_encode(['ok'=>$ok,'error'=>$error,'items'=>$items,'count'=>$count,'total'=>$total],JSON_UNESCAPED_UNICODE); exit;
}

if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['cart_action'])){
    $action=$_POST['cart_action']; $id=(int)($_POST['id']??0);
    if($action==='clear') $_SESSION['waiter_cart']=[];
    elseif(in_array($action,['add','inc','dec','remove'],true) && $id>0){
        $st=$pdo->prepare('SELECT id FROM products WHERE id=?');$st->execute([$id]);
        if(!$st->fetch()){waiter_json($pdo,false,'Mahsulot topilmadi.');}
        $q=(int)($_SESSION['waiter_cart'][$id]??0);
        if($action==='add'||$action==='inc')$q++; if($action==='dec')$q--; if($action==='remove')$q=0;
        if($q>0)$_SESSION['waiter_cart'][$id]=$q;else unset($_SESSION['waiter_cart'][$id]);
    } else waiter_json($pdo,false,'Noto‘g‘ri amal.');
    waiter_json($pdo);
}

$error='';$doneOrderId=(int)($_GET['done']??0);
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['submit_order'])){
    $tableNumber=trim($_POST['table_number']??'');$customerName=trim($_POST['customer_name']??'');
    if(!$tableNumber)$error='Stolni tanlang.'; elseif(empty($_SESSION['waiter_cart']))$error='Savat bo‘sh — avval mahsulot tanlang.';
    else{
        $ids=array_values(array_filter(array_map('intval',array_keys($_SESSION['waiter_cart']))));
        $ph=implode(',',array_fill(0,count($ids),'?'));$s=$pdo->prepare("SELECT * FROM products WHERE id IN ($ph)");$s->execute($ids);$map=[];foreach($s->fetchAll() as $r)$map[(int)$r['id']]=$r;
        try{
            $pdo->beginTransaction();
            $lock=$pdo->prepare('SELECT status FROM restaurant_tables WHERE table_number=? FOR UPDATE');$lock->execute([$tableNumber]);$state=$lock->fetchColumn();
            if($state===false)throw new RuntimeException('Stol topilmadi.');
            if($state==='band')throw new RuntimeException('Bu stol hozir band. Boshqa stolni tanlang.');
            $total=0;foreach($_SESSION['waiter_cart'] as $id=>$qty)if(isset($map[$id]))$total+=(float)$map[$id]['price']*(int)$qty;
            $name=$customerName?:'Stol #'.$tableNumber.' mijozi';
            $pdo->prepare("INSERT INTO orders(customer_name,phone,address,table_number,total,status,order_type) VALUES(?, '-', '', ?, ?, 'tayyorlanmoqda', 'table')")->execute([$name,$tableNumber,$total]);
            $orderId=(int)$pdo->lastInsertId();
            $lang=ff_current_lang();$col=$lang==='ru'?'name_ru':($lang==='en'?'name_en':'name');$ins=$pdo->prepare('INSERT INTO order_items(order_id,product_id,product_name,price,quantity) VALUES(?,?,?,?,?)');
            foreach($_SESSION['waiter_cart'] as $id=>$qty)if(isset($map[$id]))$ins->execute([$orderId,$id,$map[$id][$col]?:$map[$id]['name'],$map[$id]['price'],$qty]);
            $pdo->prepare("UPDATE restaurant_tables SET status='band' WHERE table_number=?")->execute([$tableNumber]);
            $pdo->commit();$_SESSION['waiter_cart']=[];$_SESSION['waiter_table']=$tableNumber;header('Location: waiter.php?done='.$orderId);exit;
        }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage();}
    }
}
$lang=ff_current_lang();$nameCol=$lang==='ru'?'name_ru':($lang==='en'?'name_en':'name');
$categories=$pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();
$tables=$pdo->query("SELECT * FROM restaurant_tables ORDER BY CAST(table_number AS UNSIGNED), table_number")->fetchAll();
$products=$pdo->query("SELECT p.*,COALESCE(NULLIF(p.$nameCol,''),p.name) display_name,COALESCE(NULLIF(c.$nameCol,''),c.name) category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY c.name,p.name")->fetchAll();
$cartIds=array_values(array_filter(array_map('intval',array_keys($_SESSION['waiter_cart']))));$cartRows=[];$cartTotal=0;
if($cartIds){$ph=implode(',',array_fill(0,count($cartIds),'?'));$s=$pdo->prepare("SELECT p.*,COALESCE(NULLIF(p.$nameCol,''),p.name) display_name FROM products p WHERE p.id IN ($ph)");$s->execute($cartIds);foreach($s->fetchAll() as $r){$q=(int)$_SESSION['waiter_cart'][(int)$r['id']];$r['quantity']=$q;$r['line_total']=$q*(float)$r['price'];$cartTotal+=$r['line_total'];$cartRows[(int)$r['id']]=$r;}}
?>
<!doctype html><html lang="<?=htmlspecialchars($lang)?>"><head><meta charset="utf-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FastFood — Waiter POS</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=1"><script src="../js/theme.js"></script><script src="../js/admin-i18n.js" defer></script><script src="../js/parallax.js?v=1" defer></script>
</head><body>
<?php include __DIR__.'/sidebar.php'; ?>
<main class="admin-content waiter-v4">
<div class="waiter-topbar"><div><div class="eyebrow">TEZKOR POS</div><h1>Afitsant paneli</h1><p class="admin-muted">Stolni tanlang, mahsulotni bosing va buyurtmani bir necha soniyada yuboring.</p></div><div class="waiter-top-actions"><span class="live-dot"><i></i>Tizim faol</span><a class="btn btn-secondary" href="receive_orders.php">◉ Buyurtmalar</a></div></div>
<?php if($doneOrderId): ?><div class="order-success-banner"><span>✓</span><div><strong>Buyurtma qabul qilindi</strong><small>#<?=$doneOrderId?> muvaffaqiyatli saqlandi</small></div><a href="receipt.php?id=<?=$doneOrderId?>" target="_blank" class="btn btn-secondary">Chek</a></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error">⚠ <?=htmlspecialchars($error)?></div><?php endif; ?>
<section class="waiter-step-panel"><div class="step-title"><span>01</span><div><strong>Stolni tanlang</strong><small>Buyurtma qaysi stol uchun ekanini belgilang.</small></div></div><div class="table-picker" id="tablePicker"><?php foreach($tables as $t): ?><button type="button" class="table-pick <?=$t['status']==='band'?'busy':''?>" data-table="<?=htmlspecialchars($t['table_number'])?>" <?=$t['status']==='band'?'disabled':''?>><span>№<?=htmlspecialchars($t['table_number'])?></span><strong><?=$t['status']==='band'?'Band':'Bo‘sh'?></strong><small><?=((int)$t['seats'])?> o‘rin</small></button><?php endforeach; ?></div><input type="hidden" id="selectedTable" value="<?=htmlspecialchars($_SESSION['waiter_table']??'')?>"></section>
<div class="waiter-main-grid">
<section class="waiter-menu-panel"><div class="waiter-section-head"><div><span class="step-number">02</span><div><h2>Menyu</h2><p>Kategoriya va mahsulotni tanlang.</p></div></div><input id="waiterSearch" type="search" placeholder="Mahsulot qidirish..."></div>
<div class="waiter-category-bar"><button type="button" class="waiter-cat active" data-cat="all">Barchasi</button><?php foreach($categories as $c): ?><button type="button" class="waiter-cat" data-cat="<?=$c['id']?>"><?=htmlspecialchars($c[$nameCol]?:$c['name'])?></button><?php endforeach; ?></div>
<div class="waiter-product-grid" id="waiterProductGrid"><?php foreach($products as $p): ?><article class="waiter-product" data-category="<?=$p['category_id']?>" data-name="<?=htmlspecialchars(function_exists('mb_strtolower') ? mb_strtolower($p['display_name']) : strtolower($p['display_name']))?>"><div class="waiter-product-image"><img src="<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['display_name'])?>" loading="lazy" onerror="this.onerror=null;this.src='../css/food-placeholder.svg'"></div><div class="waiter-product-info"><span class="waiter-product-cat"><?=htmlspecialchars($p['category_name']?:'—')?></span><h3><?=htmlspecialchars($p['display_name'])?></h3><p><?=htmlspecialchars($p['description']?:'')?></p><div class="waiter-product-bottom"><strong><?=number_format((float)$p['price'],0,'',' ')?> <small>so'm</small></strong><a href="waiter.php?action=add&id=<?=$p['id']?>" class="quick-add js-cart-action" data-action="add" data-id="<?=$p['id']?>">+</a></div></div></article><?php endforeach; if(!$products): ?><div class="empty-state"><div>🍔</div><strong>Mahsulot yo‘q</strong><span>Avval katalogdan mahsulot qo‘shing.</span></div><?php endif; ?></div></section>
<aside class="waiter-cart-v4"><div class="cart-head"><div><span class="step-number">03</span><div><h2>Joriy buyurtma</h2><p><span id="cartCount"><?=array_sum($_SESSION['waiter_cart'])?></span> ta mahsulot</p></div></div><button type="button" id="clearCart" class="cart-clear" <?=empty($cartRows)?'hidden':''?>>Tozalash</button></div>
<div class="cart-items-v4" id="cartItemsV4"><?php if(!$cartRows): ?><div class="empty-state"><div>🛒</div><strong>Savat bo‘sh</strong><span>Mahsulotlardan + tugmasini bosing.</span></div><?php else: foreach($cartRows as $r): ?><div class="cart-item-v4"><div class="cart-item-thumb"><img src="<?=htmlspecialchars($r['image'] ?: '../css/food-placeholder.svg')?>" alt="" onerror="this.onerror=null;this.src='../css/food-placeholder.svg'"></div><div class="cart-item-main"><strong><?=htmlspecialchars($r['display_name'])?></strong><small><?=number_format((float)$r['price'],0,'',' ')?> so‘m</small><div class="qty-v4"><button type="button" class="js-cart-action qty-btn" data-action="dec" data-id="<?=$r['id']?>" aria-label="Kamaytirish">−</button><b><?=$r['quantity']?></b><button type="button" class="js-cart-action qty-btn" data-action="inc" data-id="<?=$r['id']?>" aria-label="Ko‘paytirish">+</button></div></div><strong class="cart-line-total"><?=number_format((float)$r['line_total'],0,'',' ')?> so‘m</strong></div><?php endforeach; endif; ?></div>
<div class="checkout-card-v4 <?=$cartRows?'':'is-empty'?>" id="checkoutCard"><form method="post" id="waiterOrderForm"><input type="hidden" name="submit_order" value="1"><input type="hidden" name="table_number" id="tableNumberField" value="<?=htmlspecialchars($_SESSION['waiter_table']??'')?>"><label>Mijoz nomi (ixtiyoriy)<input name="customer_name" placeholder="Stol mijozi"></label><div class="waiter-selected-table">Stol: <b id="selectedTableLabel"><?=htmlspecialchars($_SESSION['waiter_table']??'—')?></b></div><div class="cart-total-v4"><span>Jami</span><strong id="waiterCartTotal"><?=number_format((float)$cartTotal,0,'',' ')?> so‘m</strong></div><button class="order-submit" id="orderSubmit" type="submit" <?=$cartRows?'':'disabled'?>>✓ Buyurtmani yuborish</button></form></div></aside></div></main>
<script>
(function(){
 var selected=document.getElementById('selectedTable');
 var tableField=document.getElementById('tableNumberField');
 var tableLabel=document.getElementById('selectedTableLabel');
 var checkout=document.getElementById('checkoutCard');
 var submit=document.getElementById('orderSubmit');
 var cartBox=document.getElementById('cartItemsV4');
 var countBox=document.getElementById('cartCount');
 var totalBox=document.getElementById('waiterCartTotal');
 var clearBtn=document.getElementById('clearCart');
 var search=document.getElementById('waiterSearch');
 var currentCat='all';
 function money(n){return new Intl.NumberFormat('uz-UZ').format(Math.round(n))+' so‘m';}
 function esc(s){return String(s).replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});}
 function paint(data){
   var html='';
   if(!data.items.length) html='<div class="empty-state"><div>🛒</div><strong>Savat bo‘sh</strong><span>Mahsulotlardan + tugmasini bosing.</span></div>';
   for(var i=0;i<data.items.length;i++){var x=data.items[i];html+='<div class="cart-item-v4"><div class="cart-item-thumb"><img src="'+esc(x.image||'../css/food-placeholder.svg')+'" alt="" onerror="this.onerror=null;this.src=\'../css/food-placeholder.svg\'"></div><div class="cart-item-main"><strong>'+esc(x.name)+'</strong><small>'+money(x.price)+'</small><div class="qty-v4"><button type="button" class="js-cart-action qty-btn" data-action="dec" data-id="'+x.id+'" aria-label="Kamaytirish">−</button><b>'+x.quantity+'</b><button type="button" class="js-cart-action qty-btn" data-action="inc" data-id="'+x.id+'" aria-label="Ko‘paytirish">+</button></div></div><strong class="cart-line-total">'+money(x.line_total)+'</strong></div>';}
   cartBox.innerHTML=html;countBox.textContent=data.count;totalBox.textContent=money(data.total);var has=data.items.length>0;checkout.classList.toggle('is-empty',!has);submit.disabled=!has;clearBtn.hidden=!has;
 }
 function request(action,id){
   var fd=new FormData();fd.append('cart_action',action);if(id)fd.append('id',id);
   var y=window.scrollY;
   return fetch('waiter.php',{method:'POST',body:fd,credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest'}}).then(function(r){return r.json();}).then(function(data){if(!data.ok)throw new Error(data.error||'Xatolik');paint(data);window.scrollTo(0,y);return data;}).catch(function(e){alert(e.message);window.scrollTo(0,y);});
 }
 document.addEventListener('click',function(e){
   var a=e.target.closest('.js-cart-action');if(a){e.preventDefault();request(a.getAttribute('data-action'),a.getAttribute('data-id'));return;}
   var t=e.target.closest('.table-pick');if(t&&!t.disabled){e.preventDefault();document.querySelectorAll('.table-pick').forEach(function(b){b.classList.remove('active');});t.classList.add('active');var v=t.getAttribute('data-table')||'';selected.value=v;tableField.value=v;tableLabel.textContent=v||'—';try{localStorage.setItem('ff_waiter_table',v);}catch(_){}}
 });
 clearBtn.addEventListener('click',function(){request('clear');});
 document.querySelectorAll('.waiter-cat').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.waiter-cat').forEach(function(x){x.classList.remove('active');});b.classList.add('active');currentCat=b.getAttribute('data-cat');filterProducts();});});
 function filterProducts(){var q=(search.value||'').toLowerCase().trim();document.querySelectorAll('.waiter-product').forEach(function(p){var cat=currentCat==='all'||p.getAttribute('data-category')===currentCat;var name=p.getAttribute('data-name')||'';p.style.display=cat&&(!q||name.indexOf(q)!==-1)?'':'none';});}
 search.addEventListener('input',filterProducts);
 var saved='';try{saved=localStorage.getItem('ff_waiter_table')||'';}catch(_){}
 function markSavedTable(value){if(!value)return;var found=null;document.querySelectorAll('.table-pick').forEach(function(b){if((b.getAttribute('data-table')||'')===value)found=b;});if(found&&!found.disabled){found.classList.add('active');selected.value=value;tableField.value=value;tableLabel.textContent=value;}} if(!selected.value&&saved){markSavedTable(saved);} else if(selected.value){markSavedTable(selected.value);}
 document.getElementById('waiterOrderForm').addEventListener('submit',function(e){if(!tableField.value){e.preventDefault();alert('Avval stolni tanlang.');return;}if(submit.disabled){e.preventDefault();}});
})();
</script><script src="../js/lang-switch.js"></script></body></html>
