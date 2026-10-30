<?php
// check_3d.php - 3D tizim tekshiruvi. Admin sifatida kirib oching: /admin/check_3d.php
session_start();require_once __DIR__.'/auth.php';ff_guard();
if (!isset($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/model3d_helper.php';

function ff_bytes(string $v): int { $v = trim($v); $n = (int)$v; $u = strtolower(substr($v, -1));
    return $u === 'g' ? $n * 1073741824 : ($u === 'm' ? $n * 1048576 : ($u === 'k' ? $n * 1024 : $n)); }

$dir = __DIR__ . '/../uploads/models';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
$cnt = 0;
try { $cnt = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE model_3d IS NOT NULL AND model_3d<>''")->fetchColumn(); } catch (Throwable $e) {}
$viewer = __DIR__ . '/../js/model-viewer.min.js';

$checks = [
    ['PHP versiyasi', PHP_VERSION, version_compare(PHP_VERSION, '7.4', '>=')],
    ['Bazada products.model_3d ustuni', ff_ensure_3d_column($pdo) ? 'bor' : 'YO‘Q — phpMyAdmin’da: ALTER TABLE products ADD COLUMN model_3d VARCHAR(500) NULL;', ff_ensure_3d_column($pdo)],
    ['uploads/models papkasi yoziladimi', is_writable($dir) ? 'ha' : 'YO‘Q — papkaga 755/775 huquq bering', is_writable($dir)],
    ['js/model-viewer.min.js fayli', is_file($viewer) ? round(filesize($viewer) / 1024) . ' KB' : 'TOPILMADI — js papkasiga yuklang', is_file($viewer)],
    ['upload_max_filesize', ini_get('upload_max_filesize'), ff_bytes(ini_get('upload_max_filesize')) >= 8 * 1048576],
    ['post_max_size', ini_get('post_max_size'), ff_bytes(ini_get('post_max_size')) >= 8 * 1048576],
    ['file_uploads yoqilganmi', ini_get('file_uploads') ? 'ha' : 'YO‘Q', (bool)ini_get('file_uploads')],
    ['3D modeli bor mahsulotlar soni', (string)$cnt, true],
];
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg"><meta name="viewport" content="width=device-width,initial-scale=1"><title>3D tekshiruv</title>
<style>body{font:15px system-ui;max-width:760px;margin:30px auto;padding:0 16px}table{width:100%;border-collapse:collapse}td{padding:10px;border-bottom:1px solid #ddd}.ok{color:#16a34a;font-weight:800}.bad{color:#dc2626;font-weight:800}code{background:#f3f4f6;padding:2px 6px;border-radius:5px}</style></head><body>
<h2>🧊 3D tizim tekshiruvi</h2>
<table><?php foreach ($checks as [$n, $v, $ok]): ?><tr><td><?= $ok ? '<span class="ok">✓</span>' : '<span class="bad">✗</span>' ?></td><td><?= htmlspecialchars($n) ?></td><td><?= htmlspecialchars($v) ?></td></tr><?php endforeach; ?></table>
<h3>3D ko‘rsatkich sinovi</h3>
<p>Quyida astronavt aylanib turishi kerak. Bo‘sh bo‘lsa — brauzer konsolini (F12 → Console) oching va xatoni yuboring.</p>
<script type="module" src="../js/model-viewer.min.js"></script>
<model-viewer id="t" camera-controls auto-rotate style="width:100%;height:320px;background:#eee;border-radius:12px" environment-image="neutral"
  src="https://modelviewer.dev/shared-assets/models/Astronaut.glb"></model-viewer>
<p id="r" style="font-weight:800">Tekshirilmoqda…</p>
<script>
setTimeout(function(){var d=customElements.get('model-viewer');document.getElementById('r').innerHTML=d?'<span class="ok">✓ model-viewer yuklandi.</span> Agar yuqorida astronavt ko‘rinmasa, bu faqat test model internetdan olinishi sababli bo‘lishi mumkin.':'<span class="bad">✗ model-viewer yuklanmadi — js/model-viewer.min.js fayli serverda yo‘q yoki yo‘li noto‘g‘ri.</span>'},1500);
document.getElementById('t').addEventListener('error',function(e){document.getElementById('r').innerHTML+=' <span class="bad">Test model yuklanmadi (internet).</span>'});
</script>
</body></html>
