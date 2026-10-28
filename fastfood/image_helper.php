<?php
// image_helper.php - Mahsulot rasmlarini yuklash va standart o'lchamga
// (crop + resize) keltirish uchun umumiy funksiya.
//
// Nima uchun kerak: agar turli o'lchamdagi rasmlar to'g'ridan-to'g'ri saqlansa,
// kartochkalar balandligi/kengligi bir xil bo'lmay, dizayn "sakrab" ketadi.
// Bu funksiya har qanday rasmni belgilangan nisbatga (4:3) moslab kesadi va
// bitta o'lchamda (JPEG) saqlaydi - shu bois barcha kartochkalar har doim
// bir xil ko'rinadi.

define('FF_IMG_TARGET_W', 800);   // saqlanadigan rasm kengligi (px)
define('FF_IMG_TARGET_H', 600);   // saqlanadigan rasm balandligi (px) - 4:3 nisbat
define('FF_IMG_MAX_UPLOAD_BYTES', 6 * 1024 * 1024); // 6 MB

/**
 * Yuklangan faylni (odatiy $_FILES['image_file'] massivini) qabul qiladi,
 * uni FF_IMG_TARGET_W x FF_IMG_TARGET_H o'lchamiga crop+resize qiladi va
 * $uploadDir papkasiga JPEG sifatida saqlaydi.
 *
 * @return array ['ok' => bool, 'path' => string|null (saytga nisbatan yo'l), 'error' => string|null]
 */
function ff_handle_image_upload(array $file, string $uploadDir, string $publicPathPrefix): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'path' => null, 'error' => null]; // fayl tanlanmagan - xato emas
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'error' => "Faylni yuklashda xatolik yuz berdi (kod: {$file['error']})."];
    }

    if ($file['size'] > FF_IMG_MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'path' => null, 'error' => "Rasm hajmi juda katta. Maksimal 6 MB bo'lishi kerak."];
    }

    if (!function_exists('imagecreatetruecolor')) {
        return ['ok' => false, 'path' => null, 'error' => "Serverda GD kutubxonasi o'rnatilmagan, rasm qayta ishlanmadi."];
    }

    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        return ['ok' => false, 'path' => null, 'error' => "Fayl rasm emas yoki buzilgan."];
    }

    [$srcW, $srcH, $type] = $info;

    switch ($type) {
        case IMAGETYPE_JPEG:
            $src = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $src = @imagecreatefrompng($file['tmp_name']);
            break;
        case IMAGETYPE_WEBP:
            $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false;
            break;
        case IMAGETYPE_GIF:
            $src = @imagecreatefromgif($file['tmp_name']);
            break;
        default:
            return ['ok' => false, 'path' => null, 'error' => "Faqat JPG, PNG, WEBP yoki GIF formatidagi rasmlar qabul qilinadi."];
    }

    if (!$src) {
        return ['ok' => false, 'path' => null, 'error' => "Rasmni o'qib bo'lmadi."];
    }

    $targetW = FF_IMG_TARGET_W;
    $targetH = FF_IMG_TARGET_H;

    // "Cover" mantig'i: nisbatni saqlab, markazdan kesib, butun maydonni to'ldiradi
    // (Instagram/e-commerce saytlaridagi kabi - bo'sh joy yoki cho'zilish bo'lmaydi).
    $srcRatio = $srcW / $srcH;
    $targetRatio = $targetW / $targetH;

    if ($srcRatio > $targetRatio) {
        $cropH = $srcH;
        $cropW = (int) round($srcH * $targetRatio);
        $cropX = (int) round(($srcW - $cropW) / 2);
        $cropY = 0;
    } else {
        $cropW = $srcW;
        $cropH = (int) round($srcW / $targetRatio);
        $cropX = 0;
        $cropY = (int) round(($srcH - $cropH) / 2);
    }

    $dst = imagecreatetruecolor($targetW, $targetH);
    // Shaffof PNG fonlarini oq rangga o'tkazamiz (JPEG shaffoflikni qo'llab-quvvatlamaydi)
    $white = imagecolorallocate($dst, 255, 255, 255);
    imagefill($dst, 0, 0, $white);

    imagecopyresampled($dst, $src, 0, 0, $cropX, $cropY, $targetW, $targetH, $cropW, $cropH);
    imagedestroy($src);

    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }

    $filename = 'p_' . bin2hex(random_bytes(8)) . '.jpg';
    $fullPath = rtrim($uploadDir, '/') . '/' . $filename;

    $saved = imagejpeg($dst, $fullPath, 86);
    imagedestroy($dst);

    if (!$saved) {
        return ['ok' => false, 'path' => null, 'error' => "Rasmni saqlashda xatolik yuz berdi."];
    }

    return ['ok' => true, 'path' => rtrim($publicPathPrefix, '/') . '/' . $filename, 'error' => null];
}
