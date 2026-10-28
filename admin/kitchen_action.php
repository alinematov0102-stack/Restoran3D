<?php
session_start();
if(!isset($_SESSION['admin_id'])){http_response_code(401);exit;}
require_once __DIR__ . '/../fastfood/database.php';
$id=(int)($_POST['order_id']??0);$action=$_POST['action']??'';
if($id && $action==='accept'){$s=$pdo->prepare("UPDATE orders SET status='qabul qilingan' WHERE id=? AND status='yangi'");$s->execute([$id]);}
elseif($id && $action==='send_kitchen'){$s=$pdo->prepare("UPDATE orders SET status='tayyorlanmoqda' WHERE id=? AND status IN ('yangi','qabul qilingan')");$s->execute([$id]);}
elseif($id && $action==='ready'){$s=$pdo->prepare("UPDATE orders SET status='tayyor' WHERE id=? AND status='tayyorlanmoqda'");$s->execute([$id]);}
header('Location: kitchen.php');exit;
