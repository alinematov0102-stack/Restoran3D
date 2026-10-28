<?php
session_start();
require_once __DIR__ . '/database.php';

$orderId=(int)($_POST['order_id']??0); $reason=trim($_POST['reason']??'');
$ids=array_values(array_filter(array_map('intval', $_SESSION['my_orders']??[])));
if($orderId<=0 || !in_array($orderId,$ids,true) || $reason==='') { header('Location: my_orders.php'); exit; }
$st=$pdo->prepare("SELECT status, table_number FROM orders WHERE id=?"); $st->execute([$orderId]); $o=$st->fetch();
if(!$o || in_array($o['status'],['yakunlangan','bekor qilingan'],true)){ header('Location: my_orders.php'); exit; }
try{
 $pdo->beginTransaction();
 $pdo->prepare("UPDATE orders SET status='bekor qilingan' WHERE id=?")->execute([$orderId]);
 $pdo->prepare("INSERT INTO order_cancellations(order_id,reason) VALUES(?,?)")->execute([$orderId,$reason]);
 if(!empty($o['table_number'])) $pdo->prepare("UPDATE restaurant_tables SET status='bosh' WHERE table_number=? AND status='band'")->execute([$o['table_number']]);
 $pdo->commit();
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();}
header('Location: my_orders.php'); exit;
