<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$envFile = $root . '/.env';
$lockFile = __DIR__ . '/.installed';
if (is_file($lockFile)) { http_response_code(403); exit('Installer is locked.'); }

$error=''; $success=false;
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $host=trim($_POST['db_host']??''); $db=trim($_POST['db_name']??''); $user=trim($_POST['db_user']??''); $pass=(string)($_POST['db_pass']??'');
    $app=trim($_POST['app_name']??'FastFood') ?: 'FastFood';
    $adminUser=trim($_POST['admin_user']??''); $adminPass=(string)($_POST['admin_pass']??'');
    $courierUser=trim($_POST['courier_user']??''); $courierPass=(string)($_POST['courier_pass']??''); $courierName=trim($_POST['courier_name']??''); $courierPhone=trim($_POST['courier_phone']??'');
    if($host===''||$db===''||$user===''||$adminUser===''||strlen($adminPass)<8) $error='Database ma’lumotlari va kamida 8 belgili admin parolini to‘ldiring.';
    else {
      try {
        $safeDb=str_replace('`','',$db);
        try {
          $pdo=new PDO('mysql:host='.$host.';dbname='.$safeDb.';charset=utf8mb4',$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $first) {
          $server=new PDO('mysql:host='.$host.';charset=utf8mb4',$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
          $server->exec('CREATE DATABASE IF NOT EXISTS `'.$safeDb.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
          $pdo=new PDO('mysql:host='.$host.';dbname='.$safeDb.';charset=utf8mb4',$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
        }
        $pdo->exec('USE `'.$safeDb.'`');
        $sql=file_get_contents(__DIR__.'/schema.sql');
        foreach(array_filter(array_map('trim',preg_split('/;\s*(?:\r?\n|$)/',$sql))) as $statement) $pdo->exec($statement);
        $pdo->prepare('DELETE FROM admin WHERE username=?')->execute([$adminUser]);
        $pdo->prepare('INSERT INTO admin(username,password) VALUES(?,?)')->execute([$adminUser,password_hash($adminPass,PASSWORD_DEFAULT)]);
        if($courierUser!=='' && strlen($courierPass)>=8 && $courierName!=='') {
          $pdo->prepare('DELETE FROM delivery_couriers WHERE username=?')->execute([$courierUser]);
          $pdo->prepare('INSERT INTO delivery_couriers(username,password,name,phone,vehicle) VALUES(?,?,?,?,?)')->execute([$courierUser,password_hash($courierPass,PASSWORD_DEFAULT),$courierName,$courierPhone,trim($_POST['courier_vehicle']??'')]);
        }
        $env="APP_ENV=production\nAPP_NAME=".str_replace(["\r","\n"],'',$app)."\nDB_HOST=".str_replace(["\r","\n"],'',$host)."\nDB_NAME=".str_replace(["\r","\n"],'',$db)."\nDB_USER=".str_replace(["\r","\n"],'',$user)."\nDB_PASS=".str_replace(["\r","\n"],'',$pass)."\n";
        if(file_put_contents($envFile,$env,LOCK_EX)===false) throw new RuntimeException('.env faylini yozib bo‘lmadi.');
        file_put_contents($lockFile,date('c'),LOCK_EX);
        $success=true;
      } catch(Throwable $e) { $error='O‘rnatishda xatolik: '.htmlspecialchars($e->getMessage()); }
    }
}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FastFood Installer</title><style>body{font-family:Inter,Arial,sans-serif;background:#f5f5f5;margin:0;padding:30px}.box{max-width:760px;margin:auto;background:#fff;border-radius:24px;padding:30px;box-shadow:0 15px 50px #0001}h1{margin-top:0}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}label{display:block;font-weight:700;font-size:13px}input{width:100%;box-sizing:border-box;padding:12px;border:1px solid #ddd;border-radius:12px;margin-top:6px}.full{grid-column:1/-1}.btn{margin-top:20px;background:#9b2c3a;color:#fff;border:0;border-radius:12px;padding:13px 18px;font-weight:800;cursor:pointer}.err{background:#fee2e2;color:#991b1b;padding:12px;border-radius:12px}.ok{background:#dcfce7;color:#166534;padding:16px;border-radius:12px}@media(max-width:650px){.grid{grid-template-columns:1fr}}</style></head><body><div class="box"><h1>🍔 FastFood — O‘rnatish</h1><p>Database va birinchi admin akkauntini shu yerda sozlang.</p><?php if($error):?><div class="err"><?=$error?></div><?php endif;?><?php if($success):?><div class="ok"><b>✓ O‘rnatish tugadi.</b><br><br><a href="../">Saytga</a> &nbsp; <a href="../admin/">Admin panelga</a><br><small>Installer bloklandi. Uni qayta ishlatib bo‘lmaydi.</small></div><?php else: ?><form method=post><div class=grid><label>Restaurant nomi<input name=app_name value="FastFood" required></label><div></div><label>DB Host<input name=db_host value="localhost" required></label><label>DB Name<input name=db_name value="fastfood" required></label><label>DB User<input name=db_user required></label><label>DB Password<input type=password name=db_pass></label><label>Admin Login<input name=admin_user value="admin" required></label><label>Admin Password (min 8)<input type=password name=admin_pass required></label><label class=full>Kuryer login (ixtiyoriy)<input name=courier_user placeholder="courier"></label><label>Kuryer password (min 8)<input type=password name=courier_pass></label><label>Kuryer ismi<input name=courier_name placeholder="Ali"></label><label>Kuryer telefon<input name=courier_phone placeholder="+998..."></label><label>Kuryer transporti<input name=courier_vehicle placeholder="Nexia 3"></label></div><button class=btn type=submit>🚀 O‘rnatishni boshlash</button></form><?php endif;?></div></body></html>
