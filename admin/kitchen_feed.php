<?php
session_start();header('Content-Type: application/json; charset=utf-8');
if(!isset($_SESSION['admin_id'])){http_response_code(401);echo json_encode(['ok'=>false]);exit;}
require_once __DIR__.'/../fastfood/database.php';
$rows=$pdo->query("SELECT id,customer_name,table_number,total,status,created_at FROM orders WHERE status IN ('yangi','qabul qilingan','tayyorlanmoqda') ORDER BY created_at ASC")->fetchAll();
echo json_encode(['ok'=>true,'orders'=>$rows],JSON_UNESCAPED_UNICODE);
