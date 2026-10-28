<?php
/**
 * Market loyihasidagi kabi UZ -> RU/EN avtomatik tarjima.
 * Google Translate'ning norasmiy gtx endpointidan foydalanadi.
 * Tarjima ishlamasa, asosiy UZ ma'lumotini saqlashga to'sqinlik qilmaydi.
 */
function ff_auto_translate(string $text, string $targetLang): string {
    $text = trim($text);
    $targetLang = strtolower(trim($targetLang));
    if ($text === '' || !in_array($targetLang, ['ru', 'en'], true)) return '';
    if (!function_exists('curl_init')) return '';

    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=uz&tl='
        . urlencode($targetLang) . '&dt=t&q=' . urlencode($text);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'FastFood-Market-Translate/1.0');
    $response = curl_exec($ch);
    $curlErr = curl_errno($ch);
    curl_close($ch);

    if ($curlErr || !$response) return '';
    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data[0]) || !is_array($data[0])) return '';

    $translated = '';
    foreach ($data[0] as $segment) {
        if (isset($segment[0]) && is_string($segment[0])) $translated .= $segment[0];
    }
    return trim($translated);
}
