<?php
// api/thumb.php - Kichik (tez yuklanadigan) rasm: /api/thumb.php?p=uploads/products/x.jpg&w=240
// Birinchi so'rovda kichraytirib diskka saqlaydi, keyingilari tayyor fayldan beriladi.
// Biror narsa xato ketsa, asl rasmga yo'naltiradi (rasm hech qachon "sinmaydi").

$p = (string)($_GET['p'] ?? '');
$w = (int)($_GET['w'] ?? 240);
$orig = '../' . $p;

if (!preg_match('#^uploads/(products|hero)[A-Za-z0-9._/-]*\.(jpe?g|png|webp)$#i', $p) || strpos($p, '..') !== false) {
    http_response_code(404); exit;
}
$src = __DIR__ . '/../' . $p;
if (!is_file($src)) { http_response_code(404); exit; }
if (!in_array($w, [120, 160, 240, 360, 480, 640], true)) $w = 240;

function go_original(string $to): void { header('Cache-Control: no-store'); header('Location: ' . $to, true, 302); exit; }

try {
    if (!function_exists('imagecreatetruecolor') || !function_exists('imagecreatefromstring')) go_original($orig);
    $webp = function_exists('imagewebp') && stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'image/webp') !== false;
    $ext = $webp ? 'webp' : 'jpg';
    $dir = sys_get_temp_dir() . '/ff_thumbs';
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $mt = (int)filemtime($src);
    $cache = $dir . '/' . md5($p . '|' . $w . '|' . $mt) . '.' . $ext;

    if (!is_file($cache)) {
        $info = @getimagesize($src);
        if (!$info || $info[0] < 1 || $info[1] < 1) go_original($orig);
        if ($info[0] <= $w) go_original($orig);                 // asl rasm allaqachon kichik
        $data = @file_get_contents($src);
        $im = $data === false ? false : @imagecreatefromstring($data);
        if (!$im) go_original($orig);
        $h = (int)max(1, round($info[1] * $w / $info[0]));
        $out = imagecreatetruecolor($w, $h);
        imagefill($out, 0, 0, imagecolorallocate($out, 255, 255, 255));
        imagecopyresampled($out, $im, 0, 0, 0, 0, $w, $h, $info[0], $info[1]);
        $tmp = $cache . '.' . getmypid() . '.tmp';
        $ok = $webp ? imagewebp($out, $tmp, 78) : imagejpeg($out, $tmp, 80);
        imagedestroy($im); imagedestroy($out);
        if (!$ok || !is_file($tmp)) go_original($orig);
        @rename($tmp, $cache);
    }

    $etag = '"' . md5_file($cache) . '"';
    header('Content-Type: ' . ($webp ? 'image/webp' : 'image/jpeg'));
    header('Cache-Control: public, max-age=2592000, immutable');
    header('Vary: Accept');
    header('ETag: ' . $etag);
    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) { http_response_code(304); exit; }
    header('Content-Length: ' . filesize($cache));
    readfile($cache);
} catch (Throwable $e) {
    error_log('thumb: ' . $e->getMessage());
    go_original($orig);
}
