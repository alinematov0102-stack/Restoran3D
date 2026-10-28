<?php
session_start();
require_once __DIR__ . '/../fastfood/database.php';
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['courier_id'])) { http_response_code(401); echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit; }
$lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($_POST['lng'] ?? null, FILTER_VALIDATE_FLOAT);
if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) { http_response_code(422); echo json_encode(['ok'=>false,'error'=>'invalid_location']); exit; }
$st=$pdo->prepare("UPDATE delivery_couriers SET current_lat=?, current_lng=?, location_updated_at=NOW(), status='online' WHERE id=?");
$st->execute([$lat,$lng,(int)$_SESSION['courier_id']]);
echo json_encode(['ok'=>true,'lat'=>$lat,'lng'=>$lng,'updated_at'=>date('Y-m-d H:i:s')]);
