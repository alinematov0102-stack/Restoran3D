<?php
// tables.php - Admin: restoran stollarini boshqarish
session_start();require_once __DIR__.'/auth.php';ff_guard();
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/lang.php';

$error = '';
$success = '';

if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM restaurant_tables WHERE id = ?")->execute([(int)$_GET['delete']]);
    header('Location: tables.php');
    exit;
}

if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("SELECT status FROM restaurant_tables WHERE id = ?");
    $stmt->execute([$id]);
    $cur = $stmt->fetchColumn();
    $new = ($cur === 'band') ? "bo'sh" : 'band';
    $pdo->prepare("UPDATE restaurant_tables SET status = ? WHERE id = ?")->execute([$new, $id]);
    header('Location: tables.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $number = trim($_POST['table_number'] ?? '');
    $seats = (int)($_POST['seats'] ?? 4);

    if ($number === '') {
        $error = "Stol raqamini kiriting.";
    } else {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE restaurant_tables SET table_number = ?, seats = ? WHERE id = ?");
            $stmt->execute([$number, $seats, $id]);
            $success = "Stol yangilandi.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO restaurant_tables (table_number, seats) VALUES (?, ?)");
            $stmt->execute([$number, $seats]);
            $success = "Stol qo'shildi.";
        }
    }
}

if (isset($_GET['cancel_reservation'])) {
    $pdo->prepare("UPDATE reservations SET status = 'bekor qilingan' WHERE id = ?")->execute([(int)$_GET['cancel_reservation']]);
    header('Location: tables.php');
    exit;
}

$editTable = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM restaurant_tables WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editTable = $stmt->fetch();
}

$tables = $pdo->query("SELECT * FROM restaurant_tables ORDER BY CAST(table_number AS UNSIGNED) ASC, table_number ASC")->fetchAll();

$reservations = $pdo->query("
    SELECT * FROM reservations
    WHERE status = 'band' AND reservation_time >= NOW()
    ORDER BY reservation_time ASC
")->fetchAll();

$reservationsByTable = [];
$reservedTableIds = [];
foreach ($reservations as $r) {
    $reservedTableIds[(int)$r['table_id']] = true;
    $reservationsByTable[$r['table_id']][] = $r;
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(ff_current_lang()) ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg">
<title>Stollar - Admin</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=2">
<script src="../js/theme.js"></script>
<script src="../js/admin-i18n.js" defer></script>
<script src="../js/parallax.js?v=1" defer></script>
</head>
<body>
<?php include __DIR__ . '/sidebar.php'; ?>


<script>(function(){var f=location.pathname.split('/').pop()||'dashboard.php';document.querySelectorAll('.sidebar a[href]').forEach(function(a){if(a.getAttribute('href')===f)a.classList.add('active')})})();</script>


<div class="admin-content">
    <h2><?= $editTable ? "Stolni tahrirlash" : "Yangi stol qo'shish" ?></h2>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form id="addTable" method="post" action="tables.php" class="form-box" style="margin:0 0 30px;">
        <input type="hidden" name="id" value="<?= $editTable['id'] ?? '' ?>">

        <label>Stol raqami</label>
        <input type="text" name="table_number" value="<?= htmlspecialchars($editTable['table_number'] ?? '') ?>" required autofocus placeholder="Masalan: 1, 2, VIP-1">

        <label>O'rindiqlar soni</label>
        <input type="number" name="seats" value="<?= $editTable['seats'] ?? 4 ?>" min="1">

        <button type="submit" class="btn" style="width:100%;"><?= $editTable ? "Yangilash" : "Qo'shish" ?></button>
        <?php if ($editTable): ?>
            <a href="tables.php" class="btn btn-secondary" style="width:100%; margin-top:10px; display:block; text-align:center;">Bekor qilish</a>
        <?php endif; ?>
    </form>

    <?php $freeCount=0; $busyCount=0; foreach($tables as $tt){ if($tt['status']==='band')$busyCount++; else $freeCount++; } ?>
    <div class="order-stats"><div class="order-stat"><span>Jami stol</span><b><?=count($tables)?></b></div><div class="order-stat"><span>Bo‘sh</span><b><?=$freeCount?></b></div><div class="order-stat"><span>Band</span><b><?=$busyCount?></b></div><div class="order-stat"><span>Kelgusi bandlik</span><b><?=count($reservations)?></b></div></div>
    <div class="admin-toolbar"><div><h2>Stollar xaritasi</h2><p class="admin-muted">Stol holatini bir bosishda o‘zgartiring.</p></div><a class="btn" href="#addTable">+ Yangi stol</a></div>
    <div class="table-board">
        <?php foreach ($tables as $t): ?>
        <div class="table-tile <?= $t['status'] === 'band' ? 'busy' : (isset($reservedTableIds[(int)$t['id']]) ? 'reserved' : 'free') ?>">
            <h3 style="font-size:22px; padding-top:0;">Stol #<?= htmlspecialchars($t['table_number']) ?></h3>
            <p style="margin-bottom:8px;"><?= (int)$t['seats'] ?> o'rindiq</p>
            <span class="price" style="background:none; padding:0; color:<?= $t['status'] === 'band' ? 'var(--err)' : 'var(--ok)' ?>;">
                <?= $t['status'] === 'band' ? '● Band' : (isset($reservedTableIds[(int)$t['id']]) ? '● Bron' : '● Bo\'sh') ?>
            </span>
            <div style="display:flex; gap:8px; margin-top:14px;">
                <a href="tables.php?toggle=<?= $t['id'] ?>" class="btn" style="flex:1;"><?= $t['status'] === 'band' ? "Bo'shatish" : (isset($reservedTableIds[(int)$t['id']]) ? "Bronni ko‘rish" : "Band qilish") ?></a>
                <a href="tables.php?edit=<?= $t['id'] ?>" class="btn btn-secondary">✎</a>
                <a href="tables.php?delete=<?= $t['id'] ?>" class="btn btn-secondary" onclick="return confirm('O\'chirilsinmi?')">🗑</a>
            </div>

            <?php if (!empty($reservationsByTable[$t['id']])): ?>
            <div style="margin-top:14px; padding-top:14px; border-top:1px dashed var(--line);">
                <div style="font-family:var(--font-mono); font-size:11px; text-transform:uppercase; letter-spacing:0.06em; color:var(--text-mute); margin-bottom:8px;">Kelgusi bandlar</div>
                <?php foreach ($reservationsByTable[$t['id']] as $r): ?>
                <div style="display:flex; justify-content:space-between; align-items:center; font-size:12.5px; padding:6px 0;">
                    <span>
                        <b style="color:var(--gold-bright); font-family:var(--font-mono);"><?= date('d.m H:i', strtotime($r['reservation_time'])) ?></b>
                        — <?= htmlspecialchars($r['customer_name']) ?>
                    </span>
                    <a href="tables.php?cancel_reservation=<?= $r['id'] ?>" onclick="return confirm('Bandlik bekor qilinsinmi?')" style="color:var(--err); font-size:11px; text-decoration:underline;">bekor qilish</a>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php if (empty($tables)): ?>
            <p style="color:var(--text-dim);">Hali stollar qo'shilmagan.</p>
        <?php endif; ?>
    </div>
</div>

<script src="../js/lang-switch.js"></script>
</body>
</html>
