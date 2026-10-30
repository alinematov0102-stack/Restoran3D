<?php
// translate_ui.php - Interfeys matnlarini tarjima qilish (baza keshi + parallel so'rovlar)
// Bir marta tarjima qilingan matn bazada saqlanadi, keyingi foydalanuvchilarga Google kerak emas.
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
$body = json_decode(file_get_contents('php://input'), true);
$target = $body['target'] ?? 'uz';
$texts = $body['texts'] ?? [];
if (!in_array($target, ['uz', 'ru', 'en'], true) || !is_array($texts)) { http_response_code(400); echo json_encode(['error' => 'bad request']); exit; }

$clean = [];
foreach ($texts as $v) {
    if (!is_string($v)) continue;
    $v = trim($v);
    if ($v === '' || mb_strlen($v) > 300) continue;
    $clean[$v] = true;
}
$texts = array_slice(array_keys($clean), 0, 120);
if ($target === 'uz' || !$texts) { echo json_encode(['translations' => new stdClass()]); exit; }

require_once __DIR__ . '/fastfood/database.php';

function ff_cache_select(PDO $pdo, string $lang, array $hashes): array {
    $ph = implode(',', array_fill(0, count($hashes), '?'));
    $st = $pdo->prepare("SELECT h, dst FROM ui_translations WHERE lang=? AND h IN ($ph)");
    $st->execute(array_merge([$lang], $hashes));
    return $st->fetchAll(PDO::FETCH_KEY_PAIR);
}

$hash = [];
foreach ($texts as $t) $hash[$t] = md5($t);

try {
    $found = ff_cache_select($pdo, $target, array_values($hash));
} catch (Throwable $e) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS ui_translations (
            lang VARCHAR(5) NOT NULL, h CHAR(32) NOT NULL, src TEXT NOT NULL, dst TEXT NOT NULL,
            PRIMARY KEY (lang, h)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e2) { error_log('ui_translations: ' . $e2->getMessage()); }
    $found = [];
}

$out = [];
$missing = [];
foreach ($texts as $t) {
    if (isset($found[$hash[$t]])) $out[$t] = $found[$hash[$t]];
    else $missing[] = $t;
}

// Yetishmaydiganlarini Google'dan parallel olamiz
if ($missing && function_exists('curl_multi_init')) {
    $fresh = [];
    foreach (array_chunk($missing, 15) as $chunk) {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($chunk as $t) {
            $ch = curl_init('https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=' . rawurlencode($target) . '&dt=t&q=' . rawurlencode($t));
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 3, CURLOPT_SSL_VERIFYPEER => true]);
            curl_multi_add_handle($mh, $ch);
            $handles[$t] = $ch;
        }
        do { $st = curl_multi_exec($mh, $running); if ($running) curl_multi_select($mh, 1.0); } while ($running && $st === CURLM_OK);
        foreach ($handles as $t => $ch) {
            $res = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch); curl_close($ch);
            $data = $res ? json_decode($res, true) : null;
            if (!isset($data[0]) || !is_array($data[0])) continue;
            $o = '';
            foreach ($data[0] as $seg) if (isset($seg[0])) $o .= $seg[0];
            $o = trim($o);
            if ($o !== '') { $out[$t] = $o; $fresh[$t] = $o; }
        }
        curl_multi_close($mh);
    }
    if ($fresh) {
        try {
            $rows = []; $args = [];
            foreach ($fresh as $src => $dst) { $rows[] = '(?,?,?,?)'; array_push($args, $target, $hash[$src], $src, $dst); }
            $pdo->prepare('INSERT IGNORE INTO ui_translations (lang,h,src,dst) VALUES ' . implode(',', $rows))->execute($args);
        } catch (Throwable $e) { error_log('ui_translations save: ' . $e->getMessage()); }
    }
}
echo json_encode(['translations' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
