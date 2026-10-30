<?php
// login.php - Admin tizimga kirish
session_start();
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/lang.php';
require_once __DIR__ . '/auth.php';
ff_ensure_staff_schema($pdo);

if (isset($_SESSION['admin_id'])) {
    header('Location: ' . ff_home());
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password'])) {
        if (isset($admin['is_active']) && (int)$admin['is_active'] !== 1) {
            $error = 'Bu hisob o\'chirilgan. Administrator bilan bog\'laning.';
        } else {
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            $_SESSION['admin_role'] = isset(FF_ROLES[$admin['role'] ?? 'admin']) ? ($admin['role'] ?? 'admin') : 'waiter';
            $_SESSION['admin_name'] = $admin['full_name'] ?? $admin['username'];
            $_SESSION['role_checked'] = time();
            header('Location: ' . ff_home());
            exit;
        }
    } else {
        $error = 'Login yoki parol noto\'g\'ri.';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= ff_current_lang() ?>">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg">
<title><?= t('admin_login_title') ?></title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=2">
<script src="../js/theme.js"></script>
<script src="../js/admin-i18n.js" defer></script>
<script src="../js/parallax.js?v=1" defer></script>
</head>
<body>
<div class="form-box">
    <img src="../img/logo-mark.svg" alt="FastFood" width="56" height="56" style="display:block;margin:0 0 14px">
    <h2><?= t('admin_login_title') ?>
        <span class="theme-toggle" onclick="toggleTheme()" style="float:right;"><span class="theme-toggle-icon">☀️</span></span>
    </h2>

    <span class="lang-switch" id="langSwitch" style="margin:0 0 16px;">
        <button onclick="setLanguage('uz')" data-lang="uz">UZ</button>
        <button onclick="setLanguage('ru')" data-lang="ru">RU</button>
        <button onclick="setLanguage('en')" data-lang="en">EN</button>
    </span>

    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label><?= t('admin_login_label') ?></label>
        <input type="text" name="username" required autofocus>

        <label><?= t('admin_password_label') ?></label>
        <input type="password" name="password" required>

        <button type="submit" class="btn" style="width:100%;"><?= t('admin_login_btn') ?></button>
    </form>
    
</div>

<script src="../js/lang-switch.js"></script>
<script src="../js/global-i18n.js" defer></script></body>
</html>
