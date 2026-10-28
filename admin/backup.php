<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../fastfood/database.php';

function sqlQuote(PDO $pdo, $value): string {
    if ($value === null) return 'NULL';
    return $pdo->quote((string)$value);
}

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$stamp = date('Y-m-d_H-i-s');
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="fastfood-backup-'.$stamp.'.sql"');
echo "-- FastFood database backup\n-- Generated: ".date('c')."\n\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
foreach ($tables as $table) {
    $safe = str_replace('`','``',$table);
    $create = $pdo->query('SHOW CREATE TABLE `'.$safe.'`')->fetch(PDO::FETCH_ASSOC);
    $createSql = $create['Create Table'] ?? '';
    if ($createSql === '') continue;
    echo "DROP TABLE IF EXISTS `{$safe}`;\n{$createSql};\n\n";
    $rows = $pdo->query('SELECT * FROM `'.$safe.'`');
    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
        $cols = [];
        $vals = [];
        foreach ($row as $col => $value) {
            $cols[] = '`'.str_replace('`','``',$col).'`';
            $vals[] = sqlQuote($pdo, $value);
        }
        echo 'INSERT INTO `'.$safe.'` ('.implode(',', $cols).') VALUES ('.implode(',', $vals).');' . "\n";
    }
    echo "\n";
}
echo "SET FOREIGN_KEY_CHECKS=1;\n";
exit;
