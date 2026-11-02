<?php
// cache.php - kichik fayl keshi: bir xil ma'lumot uchun bazaga qayta-qayta bormaslik uchun.
// Admin biror narsani o'zgartirsa (admin/auth.php) kesh avtomatik tozalanadi.
function ff_cache_dir(): string {
    $d = sys_get_temp_dir() . '/ff_cache';
    if (!is_dir($d)) @mkdir($d, 0775, true);
    return $d;
}
function ff_cache_file(string $key): string { return ff_cache_dir() . '/' . md5($key) . '.json'; }

/** Kesh qiymatini qaytaradi yoki null (yo'q / eskirgan). */
function ff_cache_get(string $key, int $ttl) {
    $f = ff_cache_file($key);
    if (!is_file($f) || filemtime($f) < time() - $ttl) return null;
    $j = @file_get_contents($f);
    $d = $j === false ? null : json_decode($j, true);
    return is_array($d) && array_key_exists('v', $d) ? ['v' => $d['v']] : null;
}
function ff_cache_set(string $key, $value): void {
    $f = ff_cache_file($key);
    $tmp = $f . '.' . getmypid() . '.tmp';
    if (@file_put_contents($tmp, json_encode(['v' => $value], JSON_UNESCAPED_UNICODE)) !== false) @rename($tmp, $f);
}
function ff_cache_remember(string $key, int $ttl, callable $fn) {
    $hit = ff_cache_get($key, $ttl);
    if ($hit !== null) return $hit['v'];
    $v = $fn();
    ff_cache_set($key, $v);
    return $v;
}
function ff_cache_bust(): void {
    foreach (glob(ff_cache_dir() . '/*.json') ?: [] as $f) @unlink($f);
}
