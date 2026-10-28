<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
if (!isset($_SESSION['admin_id'])) { http_response_code(401); echo json_encode(['ok'=>false]); exit; }
require_once __DIR__ . '/../fastfood/database.php';
$new=(int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='yangi'")->fetchColumn();
$latest=$pdo->query("SELECT id, customer_name, table_number, total, created_at FROM orders WHERE status='yangi' ORDER BY created_at DESC LIMIT 1")->fetch();
echo json_encode(['ok'=>true,'count'=>$new,'latest'=>$latest], JSON_UNESCAPED_UNICODE);
