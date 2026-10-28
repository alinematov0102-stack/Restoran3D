<?php
session_start();if(!isset($_SESSION['admin_id'])){header('Location: login.php');exit;}require_once __DIR__.'/../fastfood/database.php';require_once __DIR__.'/../fastfood/lang.php';require_once __DIR__.'/../fastfood/image_helper.php';
require_once __DIR__.'/../fastfood/auto_translate.php';
require_once __DIR__.'/../fastfood/model3d_helper.php';
$has3d=ff_ensure_3d_column($pdo);
$MODEL_DIR=__DIR__.'/../uploads/models';
$error='';$success='';
if(isset($_GET['delete_product'])){if($has3d){$q=$pdo->prepare('SELECT model_3d FROM products WHERE id=?');$q->execute([(int)$_GET['delete_product']]);ff_delete_model_file($q->fetchColumn()?:null,$MODEL_DIR);}$pdo->prepare('DELETE FROM products WHERE id=?')->execute([(int)$_GET['delete_product']]);header('Location: catalog.php');exit;}
if(isset($_GET['delete_category'])){$pdo->prepare('DELETE FROM categories WHERE id=?')->execute([(int)$_GET['delete_category']]);header('Location: catalog.php');exit;}
if($_SERVER['REQUEST_METHOD']==='POST'){
$type=$_POST['type']??'';
if($type==='category'){
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    if($name===''){
        $error='Kategoriya nomini kiriting.';
    }else{
        // Marketdagi kabi: UZ nomidan RU/EN avtomatik olinadi.
        // API vaqtincha ishlamasa ham kategoriya saqlanadi.
        $ru=ff_auto_translate($name,'ru');
        $en=ff_auto_translate($name,'en');
        if($id){
            $pdo->prepare('UPDATE categories SET name=?,name_ru=?,name_en=? WHERE id=?')->execute([$name,$ru,$en,$id]);
        }else{
            $pdo->prepare('INSERT INTO categories(name,name_ru,name_en) VALUES(?,?,?)')->execute([$name,$ru,$en]);
        }
        $success='Kategoriya saqlandi.' . (($ru!==''&&$en!=='') ? ' RU/EN avtomatik tarjima qilindi.' : ' Tarjima keyinroq to‘ldirilishi mumkin.');
    }
}
if($type==='product'){
    $id=(int)($_POST['id']??0);
    $name=trim($_POST['name']??'');
    $desc=trim($_POST['description']??'');
    $price=(float)($_POST['price']??0);
    $cat=(int)($_POST['category_id']??0);
    $url=trim($_POST['image']??'');
    $old=trim($_POST['existing_image']??'');
    $up=ff_handle_image_upload($_FILES['image_file']??[],__DIR__.'/../uploads/products','../uploads/products');
    if($up['error'])$error=$up['error'];
    // --- 3D model (.glb) ---
    $oldModel=trim($_POST['existing_model']??'');
    $model=$oldModel;
    $mu=$has3d?ff_handle_model_upload($_FILES['model_file']??[],$MODEL_DIR,'../uploads/models'):['ok'=>false,'path'=>null,'error'=>null];
    if(!$has3d&&!empty($_FILES['model_file']['name']))$error='Bazada model_3d ustuni yo‘q va avtomatik qo‘shilmadi. phpMyAdmin’da bajaring: ALTER TABLE products ADD COLUMN model_3d VARCHAR(500) NULL;';
    if($mu['error']&&!$error)$error=$mu['error'];
    if($mu['ok']){$model=$mu['path'];}
    elseif(!empty($_POST['remove_model'])){$model='';}
    $image=$up['ok']?$up['path']:($url?:($old?:'https://via.placeholder.com/800x600?text=No+Image'));
    if(!$error&&($name===''||$price<=0||$cat<=0))$error='Nomi, narx va kategoriyani to‘g‘ri kiriting.';
    if($error&&$mu['ok'])ff_delete_model_file($mu['path'],$MODEL_DIR);
    if(!$error){
        // Marketdagi kabi UZ -> RU/EN avtomatik tarjima.
        // Tarjima API javob bermasa ham mahsulotning o‘zi saqlanadi.
        $ru=ff_auto_translate($name,'ru');
        $en=ff_auto_translate($name,'en');
        $descRu=ff_auto_translate($desc,'ru');
        $descEn=ff_auto_translate($desc,'en');
        if(!$has3d){
            if($id){$pdo->prepare('UPDATE products SET name=?,name_ru=?,name_en=?,description=?,description_ru=?,description_en=?,price=?,category_id=?,image=? WHERE id=?')->execute([$name,$ru,$en,$desc,$descRu,$descEn,$price,$cat,$image,$id]);}else{$pdo->prepare('INSERT INTO products(name,name_ru,name_en,description,description_ru,description_en,price,category_id,image) VALUES(?,?,?,?,?,?,?,?,?)')->execute([$name,$ru,$en,$desc,$descRu,$descEn,$price,$cat,$image]);}
        }elseif($id){
            $pdo->prepare('UPDATE products SET name=?,name_ru=?,name_en=?,description=?,description_ru=?,description_en=?,price=?,category_id=?,image=?,model_3d=? WHERE id=?')->execute([$name,$ru,$en,$desc,$descRu,$descEn,$price,$cat,$image,($model?:null),$id]);
            if($oldModel&&$oldModel!==$model)ff_delete_model_file($oldModel,$MODEL_DIR);
        }else{
            $pdo->prepare('INSERT INTO products(name,name_ru,name_en,description,description_ru,description_en,price,category_id,image,model_3d) VALUES(?,?,?,?,?,?,?,?,?,?)')->execute([$name,$ru,$en,$desc,$descRu,$descEn,$price,$cat,$image,($model?:null)]);
        }
        $success='Mahsulot saqlandi.' . (($ru!==''&&$en!=='') ? ' RU/EN avtomatik tarjima qilindi.' : ' Tarjima keyinroq to‘ldirilishi mumkin.');
    }
}
}
$lang=ff_current_lang();$col=$lang==='ru'?'name_ru':($lang==='en'?'name_en':'name');$categories=$pdo->query('SELECT * FROM categories ORDER BY name ASC')->fetchAll();$products=$pdo->query("SELECT p.*,COALESCE(NULLIF(p.$col,''),p.name) display_name,COALESCE(NULLIF(c.$col,''),c.name) category_name FROM products p LEFT JOIN categories c ON p.category_id=c.id ORDER BY p.id DESC")->fetchAll();$counts=[];foreach($products as $pr){$key=(int)$pr['category_id'];$counts[$key]=($counts[$key]??0)+1;}
$editProduct=null;$editCategory=null;if(isset($_GET['edit_product'])){$s=$pdo->prepare('SELECT * FROM products WHERE id=?');$s->execute([(int)$_GET['edit_product']]);$editProduct=$s->fetch();}if(isset($_GET['edit_category'])){$s=$pdo->prepare('SELECT * FROM categories WHERE id=?');$s->execute([(int)$_GET['edit_category']]);$editCategory=$s->fetch();}
?>
<!doctype html><html lang="<?=$lang?>"><head><meta charset="utf-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FastFood — Katalog</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=1"><script src="../js/theme.js"></script><script src="../js/admin-i18n.js" defer></script><script src="../js/parallax.js?v=1" defer></script>
</head><body><?php include __DIR__.'/sidebar.php'; ?><main class="admin-content catalog-pro"><div class="page-topline"><div><div class="eyebrow" data-i18n="KATALOG">KATALOG</div><h1 data-i18n="Mahsulotlar va kategoriyalar">Mahsulotlar va kategoriyalar</h1><p class="admin-muted" data-i18n="Kategoriya chapda, mahsulotlar o‘ngda — boshqarish ancha oson.">Kategoriya chapda, mahsulotlar o‘ngda — boshqarish ancha oson.</p></div><div class="page-actions"><button class="btn" onclick="openCatalogModal('product')" data-i18n="+ Mahsulot">+ Mahsulot</button><button class="btn btn-secondary" onclick="openCatalogModal('category')" data-i18n="+ Kategoriya">+ Kategoriya</button></div></div>
<?php if($error):?><div class="alert alert-error">⚠ <?=htmlspecialchars($error)?></div><?php endif;?><?php if($success):?><div class="alert alert-success">✓ <?=htmlspecialchars($success)?></div><?php endif;?>
<div class="catalog-workspace"><aside class="category-sidebar-pro"><div class="category-side-head"><div><span data-i18n="KATEGORIYALAR">KATEGORIYALAR</span><strong><?=count($categories)?></strong></div><button class="icon-btn" onclick="openCatalogModal('category')">+</button></div><button class="side-cat active" data-cat="all"><span>◉</span><b data-i18n="Barchasi">Barchasi</b><em><?=count($products)?></em></button><?php foreach($categories as $c): ?><button class="side-cat" data-cat="<?=$c['id']?>"><span>◈</span><b><?=htmlspecialchars($c[$col]?:$c['name'])?></b><em><?=$counts[(int)$c['id']]??0?></em></button><?php endforeach; ?><div class="category-side-tip"><b data-i18n="Maslahat">Maslahat</b><span data-i18n="Kategoriya tanlang — o‘ng tomonda faqat shu mahsulotlar chiqadi.">Kategoriya tanlang — o‘ng tomonda faqat shu mahsulotlar chiqadi.</span></div></aside>
<section class="catalog-products-pro"><div class="catalog-pro-toolbar"><div><span class="panel-kicker" data-i18n="MAHSULOTLAR">MAHSULOTLAR</span><h2 id="catalogCount">Barcha mahsulotlar</h2></div><div class="catalog-pro-tools"><label class="search-box-pro">⌕<input id="catalogSearch" type="search" placeholder="Mahsulot qidirish..." data-i18n-placeholder="Mahsulot qidirish..."></label><select id="catalogSort"><option value="new" data-i18n="Yangi">Yangi</option><option value="name" data-i18n="Nomi">Nomi</option><option value="priceAsc" data-i18n="Arzon → qimmat">Arzon → qimmat</option><option value="priceDesc" data-i18n="Qimmat → arzon">Qimmat → arzon</option></select></div></div><div class="product-admin-grid-v4 catalog-grid-pro" id="productGrid"><?php foreach($products as $p): ?><article class="admin-product-card-v4" data-category="<?=$p['category_id']?>" data-name="<?=htmlspecialchars(mb_strtolower($p['display_name']))?>" data-price="<?=$p['price']?>" data-id="<?=$p['id']?>"><div class="admin-product-media-v4"><img src="<?=htmlspecialchars($p['image'])?>" alt="<?=htmlspecialchars($p['display_name'])?>" loading="lazy"><span class="product-cat-tag"><?=htmlspecialchars($p['category_name']?:'—')?></span><?php if(!empty($p['model_3d'])):?><span class="model-chip static">🧊 3D</span><?php endif;?></div><div class="admin-product-body-v4"><div class="product-topline-v4"><span>#<?=$p['id']?></span><strong><?=number_format($p['price'],0,'',' ')?> <small>so‘m</small></strong></div><h3><?=htmlspecialchars($p['display_name'])?></h3><p><?=htmlspecialchars($p['description']?:'')?></p><div class="product-actions-v4"><a class="btn btn-secondary btn-sm" href="catalog.php?edit_product=<?=$p['id']?>" data-i18n="Tahrirlash">Tahrirlash</a><a class="btn btn-danger btn-sm" href="catalog.php?delete_product=<?=$p['id']?>" onclick="return confirm('Mahsulot o‘chirilsinmi?')" data-i18n="O‘chirish">O‘chirish</a></div></div></article><?php endforeach; if(!$products):?><div class="empty-state"><div>🍔</div><strong data-i18n="Mahsulot yo‘q">Mahsulot yo‘q</strong><span data-i18n="Birinchi mahsulotni qo‘shing.">Birinchi mahsulotni qo‘shing.</span></div><?php endif;?></div></section></div>
</main>
<div class="catalog-modal" id="productModal"><div class="catalog-modal-box"><button class="modal-x" onclick="closeCatalogModal()">×</button><div class="panel-kicker" data-i18n="MAHSULOT">MAHSULOT</div><h2><?=$editProduct?'Mahsulotni tahrirlash':'Yangi mahsulot'?></h2><form method="post" enctype="multipart/form-data" class="stack-form"><input type="hidden" name="type" value="product"><input type="hidden" name="id" value="<?=$editProduct['id']??''?>"><input type="hidden" name="existing_image" value="<?=htmlspecialchars($editProduct['image']??'')?>"><input type="hidden" name="existing_model" value="<?=htmlspecialchars($editProduct['model_3d']??'')?>"><label>UZ / Asosiy nom<input name="name" required value="<?=htmlspecialchars($editProduct['name']??'')?>"><small class="admin-muted">RU va EN nomlari saqlash vaqtida Marketdagi kabi avtomatik tarjima qilinadi.</small></label><div class="two-col"><label data-i18n="Narx">Narx<input type="number" name="price" step="0.01" min="0" required value="<?=$editProduct['price']??''?>"></label><label data-i18n="Kategoriya">Kategoriya<select name="category_id" required><option value="">Tanlang</option><?php foreach($categories as $c):?><option value="<?=$c['id']?>" <?=($editProduct['category_id']??'')==$c['id']?'selected':''?>><?=htmlspecialchars($c['name'])?></option><?php endforeach;?></select></label></div><label data-i18n="Rasm">Rasm<input type="file" name="image_file" accept="image/*"></label><label data-i18n="Rasm URL">Rasm URL<input name="image" value="<?=htmlspecialchars(preg_match('~^https?://~',$editProduct['image']??'')?$editProduct['image']:'')?>"></label><div class="model3d-box"><div class="model3d-head"><b>🧊 3D model</b><small>.glb · 30 MB gacha</small></div><label class="model3d-drop" id="modelDrop"><input type="file" name="model_file" id="modelFile" accept=".glb,model/gltf-binary"><span id="modelDropText">📦 .glb faylni tanlang yoki shu yerga tashlang</span></label><model-viewer id="modelPreview" class="model3d-preview" camera-controls auto-rotate shadow-intensity="1.2" environment-image="neutral" tone-mapping="neutral" exposure="1.05"<?php if(!empty($editProduct['model_3d'])):?> src="<?=htmlspecialchars($editProduct['model_3d'])?>"<?php else:?> style="display:none"<?php endif;?>></model-viewer><?php if(!empty($editProduct['model_3d'])):?><label class="model3d-remove"><input type="checkbox" name="remove_model" value="1"> 3D modelni o‘chirish</label><?php endif;?><details class="model3d-help"><summary>Qanday qilib 3D model olaman?</summary><ol><li>Telefonda <b>Polycam</b>, <b>KIRI Engine</b> yoki <b>Luma AI</b> ilovasini oching va taomni aylanib suratga oling (3D skan).</li><li>Natijani <b>.glb</b> formatda eksport qiling.</li><li>Shu yerga yuklang — mijozlar saytda ovqatni 3D da ko‘radi.</li></ol><small>Maslahat: model 5–15 MB bo‘lsa sayt tez ochiladi. Tayyor modellarni Sketchfab kabi saytlardan ham (litsenziyasiga qarab) .glb ko‘rinishida olish mumkin.</small></details></div>
<label>Tavsif<textarea name="description" rows="3"><?=htmlspecialchars($editProduct['description']??'')?></textarea><small class="admin-muted">Tavsif ham RU va EN ga avtomatik tarjima qilinadi.</small></label><button type="submit" class="btn" data-i18n="Saqlash">Saqlash</button></form></div></div>
<div class="catalog-modal" id="categoryModal"><div class="catalog-modal-box small"><button class="modal-x" onclick="closeCatalogModal()">×</button><div class="panel-kicker" data-i18n="KATEGORIYA">KATEGORIYA</div><h2 data-i18n="Kategoriya yaratish">Kategoriya yaratish</h2><form method="post" class="stack-form"><input type="hidden" name="type" value="category"><input type="hidden" name="id" value="<?=$editCategory['id']??''?>"><label>UZ / Asosiy nom<input name="name" required value="<?=htmlspecialchars($editCategory['name']??'')?>"><small class="admin-muted">RU va EN nomlari saqlash vaqtida avtomatik tarjima qilinadi.</small></label><button type="submit" class="btn" data-i18n="Saqlash">Saqlash</button></form><div class="modal-category-list"><?php foreach($categories as $c):?><div><span><?=htmlspecialchars($c['name'])?></span><span class="row-actions"><a class="icon-btn" href="catalog.php?edit_category=<?=$c['id']?>">✎</a><a class="icon-btn danger" href="catalog.php?delete_category=<?=$c['id']?>" onclick="return confirm('Kategoriya o‘chirilsinmi?')">×</a></span></div><?php endforeach;?></div></div></div>
<script type="module" src="../js/model-viewer.min.js"></script>
<script src="../js/lang-switch.js"></script><script>
(function(){var f=document.getElementById('modelFile'),p=document.getElementById('modelPreview'),t=document.getElementById('modelDropText'),d=document.getElementById('modelDrop');if(!f)return;f.addEventListener('change',function(){var file=f.files[0];if(!file)return;if(!/\.glb$/i.test(file.name)){t.textContent='⚠ Faqat .glb fayl kerak';f.value='';return}if(file.size>30*1024*1024){t.textContent='⚠ Fayl 30 MB dan katta';f.value='';return}t.textContent='✓ '+file.name+' ('+(file.size/1048576).toFixed(1)+' MB)';p.style.display='block';p.src=URL.createObjectURL(file)});['dragover','dragenter'].forEach(function(e){d.addEventListener(e,function(ev){ev.preventDefault();d.classList.add('drag')})});['dragleave','drop'].forEach(function(e){d.addEventListener(e,function(){d.classList.remove('drag')})})})();
function openCatalogModal(type){document.getElementById(type==='product'?'productModal':'categoryModal').classList.add('open')}
function closeCatalogModal(){document.querySelectorAll('.catalog-modal').forEach(x=>x.classList.remove('open'))}
(function(){const grid=document.getElementById('productGrid'),search=document.getElementById('catalogSearch'),sort=document.getElementById('catalogSort'),count=document.getElementById('catalogCount');let cat='all';const cards=[...grid.querySelectorAll('.admin-product-card-v4')];function render(){const q=(search.value||'').toLowerCase().trim();const visible=cards.filter(c=>{const ok=(cat==='all'||c.dataset.category===cat)&&(!q||c.dataset.name.includes(q));c.style.display=ok?'':'none';return ok});cards.sort((a,b)=>{let m=sort.value;if(m==='name')return a.dataset.name.localeCompare(b.dataset.name);if(m==='priceAsc')return +a.dataset.price-+b.dataset.price;if(m==='priceDesc')return +b.dataset.price-+a.dataset.price;return +b.dataset.id-+a.dataset.id}).forEach(c=>grid.appendChild(c));count.textContent=(cat==='all'?'Barcha':'Kategoriya')+' mahsulotlar · '+visible.length}document.querySelectorAll('.side-cat').forEach(b=>b.addEventListener('click',()=>{document.querySelectorAll('.side-cat').forEach(x=>x.classList.remove('active'));b.classList.add('active');cat=b.dataset.cat;render()}));search.addEventListener('input',render);sort.addEventListener('change',render);render();<?php if($editProduct):?>openCatalogModal('product');<?php endif;?><?php if($editCategory):?>openCatalogModal('category');<?php endif;?>})();
</script></body></html>
