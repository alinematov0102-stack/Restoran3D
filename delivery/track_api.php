<?php
session_start();
require_once __DIR__ . '/../fastfood/database.php';
header('Content-Type: application/json; charset=utf-8');
$id=(int)($_GET['order_id']??0);
$ids=array_map('intval',$_SESSION['my_orders']??[]);
if(!$id || !in_array($id,$ids,true)){http_response_code(403);echo json_encode(['ok'=>false]);exit;}
$st=$pdo->prepare("SELECT o.id,o.status,o.delivery_status,o.delivery_lat,o.delivery_lng,o.address,o.courier_id,c.name courier_name,c.phone courier_phone,c.vehicle,c.current_lat,c.current_lng,c.location_updated_at FROM orders o LEFT JOIN delivery_couriers c ON c.id=o.courier_id WHERE o.id=? AND o.order_type='delivery'");
$st->execute([$id]);$o=$st->fetch();
if(!$o){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
echo json_encode(['ok'=>true,'order'=>$o],JSON_UNESCAPED_UNICODE);
