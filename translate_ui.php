<?php
// Lightweight server-side UI translation endpoint, using the same Google Translate
// endpoint as the Market project. No API key is required.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'POST required']); exit; }
$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
$target = $body['target'] ?? 'uz';
$texts = $body['texts'] ?? [];
if (!in_array($target, ['uz','ru','en'], true) || !is_array($texts)) { http_response_code(400); echo json_encode(['error'=>'bad request']); exit; }
$texts = array_values(array_unique(array_filter(array_map(function($v){ return is_string($v) ? trim($v) : ''; }, $texts))));
$texts = array_slice($texts, 0, 80);

function ff_ui_translate($text, $target) {
    if ($text === '' || $target === 'uz') return $text;
    if (!function_exists('curl_init')) return $text;
    $url = 'https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=' . rawurlencode($target) . '&dt=t&q=' . rawurlencode($text);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_TIMEOUT=>5, CURLOPT_CONNECTTIMEOUT=>3, CURLOPT_SSL_VERIFYPEER=>true]);
    $res = curl_exec($ch); curl_close($ch);
    if (!$res) return $text;
    $data = json_decode($res, true);
    if (!isset($data[0]) || !is_array($data[0])) return $text;
    $out=''; foreach ($data[0] as $seg) if (isset($seg[0])) $out .= $seg[0];
    return trim($out) !== '' ? trim($out) : $text;
}

$out=[];
foreach ($texts as $t) { $out[$t]=ff_ui_translate($t,$target); usleep(60000); }
echo json_encode(['translations'=>$out], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
