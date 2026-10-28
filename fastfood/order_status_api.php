<?php
session_start();
require_once __DIR__ . '/database.php';
header('Content-Type: application/json; charset=utf-8');
$ids = array_values(array_unique(array_filter(array_map('intval', $_SESSION['my_orders'] ?? []))));
if (!$ids) { echo json_encode(['ok'=>true,'orders'=>[]], JSON_UNESCAPED_UNICODE); exit; }
$ph = implode(',', array_fill(0, count($ids), '?'));
$st = $pdo->prepare("SELECT id,status,delivery_status,created_at FROM orders WHERE id IN ($ph)");
$st->execute($ids);
$rows = [];
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) $rows[(int)$r['id']] = $r;
echo json_encode(['ok'=>true,'orders'=>$rows], JSON_UNESCAPED_UNICODE);
