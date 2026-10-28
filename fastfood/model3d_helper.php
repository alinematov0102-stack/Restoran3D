<?php
// model3d_helper.php - Mahsulotlar uchun 3D model (.glb) bilan ishlash.
//
// 1) ff_ensure_3d_column()   - products jadvalida model_3d ustuni bo'lmasa, o'zi qo'shadi
//                              (eski bazani qo'lda o'zgartirish shart emas).
// 2) ff_handle_model_upload() - .glb faylni tekshirib, uploads/models/ ga xavfsiz saqlaydi.
// 3) ff_delete_model_file()   - eski modelni diskdan o'chiradi.

define('FF_MODEL_MAX_BYTES', 30 * 1024 * 1024); // 30 MB

/** model_3d ustuni borligini ta'minlaydi. Ustun bor (yoki qo'shildi) bo'lsa true. */
function ff_ensure_3d_column(PDO $pdo): bool
{
    static $result = null;
    if ($result !== null) return $result;
    try {
        if ($pdo->query("SHOW COLUMNS FROM products LIKE 'model_3d'")->fetch()) {
            return $result = true;
        }
        $pdo->exec("ALTER TABLE products ADD COLUMN model_3d VARCHAR(500) NULL");
        return $result = true;
    } catch (Throwable $e) {
        error_log('FastFood 3D column failed: ' . $e->getMessage());
        return $result = false;
    }
}

/**
 * @return array ['ok'=>bool, 'path'=>?string, 'error'=>?string]
 * Fayl tanlanmagan bo'lsa xato emas: ok=false, error=null.
 */
function ff_handle_model_upload(array $file, string $uploadDir, string $publicPathPrefix): array
{
    if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'path' => null, 'error' => null];
    }
    if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
        return ['ok' => false, 'path' => null, 'error' => "3D fayl serverning yuklash limitidan katta. Hosting sozlamasida upload_max_filesize va post_max_size ni 32M ga oshiring yoki modelni kichraytiring."];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'path' => null, 'error' => "3D faylni yuklashda xatolik (kod: {$file['error']})."];
    }
    if ($file['size'] > FF_MODEL_MAX_BYTES) {
        return ['ok' => false, 'path' => null, 'error' => "3D model juda katta. Maksimal 30 MB (tavsiya: 5–15 MB)."];
    }

    $ext = strtolower(pathinfo($file['name'] ?? '', PATHINFO_EXTENSION));
    if ($ext !== 'glb') {
        return ['ok' => false, 'path' => null, 'error' => "Faqat .glb formatdagi 3D model qabul qilinadi."];
    }

    // GLB sarlavhasi: "glTF" (4 bayt) + versiya (uint32 = 2)
    $fh = @fopen($file['tmp_name'], 'rb');
    $head = $fh ? fread($fh, 12) : '';
    if ($fh) fclose($fh);
    if (strlen($head) < 12 || substr($head, 0, 4) !== 'glTF' || unpack('V', substr($head, 4, 4))[1] !== 2) {
        return ['ok' => false, 'path' => null, 'error' => "Fayl to'g'ri .glb (glTF 2.0) emas yoki buzilgan."];
    }

    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0755, true);
    }
    $filename = 'm_' . bin2hex(random_bytes(8)) . '.glb';
    $fullPath = rtrim($uploadDir, '/') . '/' . $filename;

    if (!@move_uploaded_file($file['tmp_name'], $fullPath)) {
        return ['ok' => false, 'path' => null, 'error' => "3D modelni saqlab bo'lmadi (papka yozish huquqini tekshiring)."];
    }
    return ['ok' => true, 'path' => rtrim($publicPathPrefix, '/') . '/' . $filename, 'error' => null];
}

/** Faqat uploads/models ichidagi faylni o'chiradi (tashqi URL yoki boshqa yo'llarga tegmaydi). */
function ff_delete_model_file(?string $publicPath, string $uploadDir): void
{
    if (!$publicPath) return;
    $name = basename($publicPath);
    if (!preg_match('/^m_[a-f0-9]{16}\.glb$/', $name)) return;
    $full = rtrim($uploadDir, '/') . '/' . $name;
    if (is_file($full)) @unlink($full);
}

/** Mijoz sahifasidagi 3D oyna uchun kichik tarjimalar (uz/ru/en). */
function ff_3d_text(string $key): string
{
    static $tx = [
        'uz' => ['photo' => 'Rasm', 'view3d' => '3D ko\'rish', 'hint' => 'Aylantirish uchun suring · Kattalashtirish uchun ikki barmoq', 'ar' => 'Stolingizda ko\'ring (AR)', 'loading' => '3D model yuklanmoqda…'],
        'ru' => ['photo' => 'Фото', 'view3d' => '3D просмотр', 'hint' => 'Проведите, чтобы повернуть · Двумя пальцами — приблизить', 'ar' => 'Посмотреть на столе (AR)', 'loading' => 'Загрузка 3D модели…'],
        'en' => ['photo' => 'Photo', 'view3d' => 'View in 3D', 'hint' => 'Drag to rotate · Pinch to zoom', 'ar' => 'View on your table (AR)', 'loading' => 'Loading 3D model…'],
    ];
    $lang = $_COOKIE['ff_lang'] ?? 'uz';
    if (!isset($tx[$lang])) $lang = 'uz';
    return $tx[$lang][$key] ?? $tx['uz'][$key] ?? $key;
}
