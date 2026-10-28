<?php
// reserve.php - Mijoz stolni ma'lum vaqtga band qiladi
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/lang.php';

$error = '';
$success = '';

$tables = $pdo->query("SELECT * FROM restaurant_tables ORDER BY CAST(table_number AS UNSIGNED) ASC, table_number ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tableId = (int)($_POST['table_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $date = trim($_POST['date'] ?? '');
    $time = trim($_POST['time'] ?? '');

    if ($tableId <= 0 || $name === '' || $phone === '' || $date === '' || $time === '') {
        $error = "Barcha maydonlarni to'ldiring.";
    } else {
        $reservationDateTime = $date . ' ' . $time . ':00';
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $reservationDateTime);

        if (!$dt || $dt < new DateTime()) {
            $error = "Iltimos, kelajakdagi to'g'ri sana va vaqtni tanlang.";
        } else {
            // Shu stol uchun shu vaqt atrofida (±1 soat) band qilingan-qilinmaganini tekshirish
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM reservations
                WHERE table_id = ? AND status = 'band'
                AND ABS(TIMESTAMPDIFF(MINUTE, reservation_time, ?)) < 60
            ");
            $stmt->execute([$tableId, $reservationDateTime]);
            $conflict = $stmt->fetchColumn();

            if ($conflict > 0) {
                $error = "Bu stol tanlangan vaqt atrofida allaqachon band qilingan. Boshqa vaqt yoki stol tanlang.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO reservations (table_id, customer_name, phone, reservation_time) VALUES (?, ?, ?, ?)");
                $stmt->execute([$tableId, $name, $phone, $reservationDateTime]);
                $success = "Stol muvaffaqiyatli band qilindi! Sizni kutamiz.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(ff_current_lang()) ?>">
<head>
<meta charset="UTF-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg">
<title>Stol band qilish - FastFood</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=1">
<script src="../js/theme.js"></script>
<script src="../js/parallax.js?v=1" defer></script>
</head>
<body>
<header>
    <a class="logo" href="index.php"><img src="../img/logo-mark.svg" alt="" width="36" height="36"><span>FastFood</span></a>
    <div>
        <a href="index.php"><?= t('home') ?></a>
        <a href="reserve.php"><?= t('reserve_table') ?></a>
        <a href="cart.php"><?= t('cart') ?></a>
        <span class="theme-toggle" onclick="toggleTheme()"><span class="theme-toggle-icon">☀️</span></span>
        <span class="lang-switch" id="langSwitch">
            <button onclick="setLanguage('uz')" data-lang="uz">UZ</button>
            <button onclick="setLanguage('ru')" data-lang="ru">RU</button>
            <button onclick="setLanguage('en')" data-lang="en">EN</button>
        </span>
    </div>
</header>

<div class="form-box">
    <h2>Stolni oldindan band qiling</h2>
    <p style="color:var(--text-dim); font-size:13px; margin-bottom:18px;">Kelmoqchi bo'lgan sana va vaqtingizni tanlang, stolingiz sizni kutadi.</p>

    <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <?php if (!$success): ?>
    <form method="post" action="reserve.php">
        <label>Ism familiya</label>
        <input type="text" name="name" required>

        <label>Telefon raqam</label>
        <input type="text" name="phone" placeholder="+998 90 123 45 67" required>

        <label>Stol</label>
        <select name="table_id" required>
            <option value="">-- Stolni tanlang --</option>
            <?php foreach ($tables as $t): ?>
                <option value="<?= $t['id'] ?>">Stol #<?= htmlspecialchars($t['table_number']) ?> (<?= (int)$t['seats'] ?> o'rindiq)</option>
            <?php endforeach; ?>
        </select>

        <label>Sana</label>
        <input type="date" name="date" min="<?= date('Y-m-d') ?>" required>

        <label>Vaqt</label>
        <input type="time" name="time" required>

        <button type="submit" class="btn" style="width:100%;">Stolni band qilish</button>
    </form>
    <?php else: ?>
        <a href="index.php" class="btn" style="width:100%; display:block; text-align:center;"><?= t('back_home') ?></a>
    <?php endif; ?>
</div>

<!-- Til almashtirish: umumiy js/lang-switch.js fayli orqali (ishonchliroq) -->
<script src="../js/lang-switch.js"></script>
<script src="../js/global-i18n.js"></script><script src="../js/universal-i18n.js"></script></body>
</html>
