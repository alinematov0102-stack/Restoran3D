<?php
// hero.php - Bosh sahifadagi katta fon rasmini (restoran muhiti) yuklash.
session_start();require_once __DIR__.'/auth.php';ff_guard();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/lang.php';

$dir = __DIR__ . '/../uploads';
$msg = ''; $err = '';

function hero_current(string $dir): ?string {
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) if (is_file("$dir/hero.$e")) return "hero.$e";
    return null;
}
function hero_clear(string $dir): void {
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) if (is_file("$dir/hero.$e")) @unlink("$dir/hero.$e");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['delete'])) {
        hero_clear($dir); $msg = 'Rasm o‘chirildi.';
    } else {
        $f = $_FILES['hero'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            $err = 'Rasm tanlang.';
        } elseif ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
            $err = 'Rasm serverning yuklash limitidan katta. Kichikroq rasm tanlang (5 MB gacha).';
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            $err = 'Yuklashda xatolik (kod: ' . $f['error'] . ').';
        } elseif ($f['size'] > 10 * 1024 * 1024) {
            $err = 'Rasm juda katta. Maksimal 10 MB.';
        } else {
            $info = @getimagesize($f['tmp_name']);
            if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
                $err = 'Faqat JPG, PNG yoki WEBP rasm qabul qilinadi.';
            } elseif ($info[0] < 1000) {
                $err = 'Rasm juda kichik (' . $info[0] . ' px). Chiroyli chiqishi uchun kamida 1600 px kenglikda bo‘lsin.';
            } else {
                if (!is_dir($dir)) @mkdir($dir, 0755, true);
                $ok = false;
                if (function_exists('imagecreatetruecolor')) {
                    $src = $info[2] === IMAGETYPE_JPEG ? @imagecreatefromjpeg($f['tmp_name'])
                         : ($info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($f['tmp_name'])
                         : (function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($f['tmp_name']) : false));
                    if ($src) {
                        $w = $info[0]; $h = $info[1]; $maxW = 2200;
                        if ($w > $maxW) { $nh = (int)round($h * $maxW / $w); $dst = imagecreatetruecolor($maxW, $nh); imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $nh, $w, $h); imagedestroy($src); $src = $dst; }
                        hero_clear($dir);
                        $ok = imagejpeg($src, "$dir/hero.jpg", 86); imagedestroy($src);
                    }
                }
                if (!$ok) { // GD bo'lmasa — asl faylni o'zini saqlaymiz
                    $ext = $info[2] === IMAGETYPE_PNG ? 'png' : ($info[2] === IMAGETYPE_WEBP ? 'webp' : 'jpg');
                    hero_clear($dir); $ok = @move_uploaded_file($f['tmp_name'], "$dir/hero.$ext");
                }
                $ok ? $msg = 'Bosh sahifa rasmi saqlandi.' : $err = 'Rasmni saqlab bo‘lmadi (uploads papkasiga yozish huquqini tekshiring).';
            }
        }
    }
}
$cur = hero_current($dir);
$lang = ff_current_lang();
?>
<!doctype html><html lang="<?= $lang ?>"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FastFood — Bosh sahifa rasmi</title>
<link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=2"><script src="../js/theme.js"></script>
<style>.hero-admin-prev{height:260px;border-radius:16px;border:1px solid var(--line);background:var(--surface-2) center/cover no-repeat;display:grid;place-items:center;color:var(--muted);font-weight:700;margin:16px 0}.hero-admin-box{max-width:760px}.hero-admin-box ul{margin:8px 0 0 18px;color:var(--muted);font-size:13px;line-height:1.7}</style><script src="../js/parallax.js?v=1" defer></script>
</head>
<body><?php include __DIR__ . '/sidebar.php'; ?>
<main class="admin-content"><div class="hero-admin-box">
  <div class="page-topline"><div><div class="eyebrow">BOSH SAHIFA</div><h1>Bosh sahifa rasmi</h1><p class="admin-muted">Ixtiyoriy: saytning tepasidagi katta fon rasmi. Yuklamasangiz ham sayt chiroyli tayyor dizayn bilan ishlayveradi.</p></div></div>
  <?php if ($msg): ?><div class="alert alert-success">✓ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="hero-admin-prev" <?php if ($cur): ?>style="background-image:url('../uploads/<?= $cur ?>?v=<?= filemtime("$dir/$cur") ?>')"<?php endif; ?>><?php if (!$cur): ?>Rasm yuklanmagan — sayt tayyor dizayn bilan ko‘rinadi (rasm ixtiyoriy)<?php endif; ?></div>

  <form method="post" enctype="multipart/form-data" class="stack-form">
    <label>Yangi rasm tanlang<input type="file" name="hero" accept="image/jpeg,image/png,image/webp" required></label>
    <div class="form-actions"><button class="btn" type="submit">Saqlash</button>
    <?php if ($cur): ?><button class="btn btn-danger" type="submit" name="delete" value="1" formnovalidate onclick="return confirm('Rasm o‘chirilsinmi?')">O‘chirish</button><?php endif; ?></div>
  </form>

  <div class="admin-panel" style="margin-top:22px"><b>Qanday rasm yaxshi?</b><ul>
    <li>Restoranning ichki ko‘rinishi, stollar yoki chiroyli muhit — <b>yotiq (gorizontal)</b> rasm.</li>
    <li>Kamida <b>1600 px</b> kenglikda (tavsiya: 1920×1080), 10 MB gacha.</li>
    <li>Eng yaxshisi — o‘zingizning restoraningizdan olingan rasm. Bo‘lmasa <b>Unsplash.com</b> yoki <b>Pexels.com</b> da <i>"restaurant interior"</i> deb qidiring (ular tekin va savdo uchun ruxsat etilgan).</li>
    <li>Rasm o‘ng tomonda emas, <b>chap tomoni nisbatan bo‘sh</b> bo‘lsa yaxshi — chapda yozuv turadi.</li>
  </ul></div>
</div></main></body></html>
