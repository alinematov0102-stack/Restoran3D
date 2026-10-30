<?php
session_start();
require_once __DIR__ . '/auth.php';
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
ff_revalidate();
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
header('Location: ' . ff_home());
exit;
