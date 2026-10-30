<?php
session_start();
require_once __DIR__ . '/auth.php';
ff_guard();
if (ff_role() !== 'admin') { header('Location: ' . ff_home()); exit; }
require_once __DIR__ . '/../fastfood/database.php';
require_once __DIR__ . '/../fastfood/lang.php';
ff_ensure_staff_schema($pdo);

$msg = ''; $err = '';
$me = (int)$_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ff_csrf_ok()) { $err = 'Sessiya eskirgan, sahifani yangilang.'; }
    else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);
        try {
            if ($action === 'add') {
                $u = trim($_POST['username'] ?? '');
                $n = trim($_POST['full_name'] ?? '');
                $p = (string)($_POST['password'] ?? '');
                $r = $_POST['role'] ?? '';
                if ($u === '' || !preg_match('/^[A-Za-z0-9._-]{3,50}$/', $u)) $err = 'Login 3-50 belgi: harf, raqam, nuqta, chiziqcha.';
                elseif (strlen($p) < 8) $err = 'Parol kamida 8 belgi bo‘lsin.';
                elseif (!isset(FF_ROLES[$r])) $err = 'Rol noto‘g‘ri.';
                else {
                    $st = $pdo->prepare('SELECT COUNT(*) FROM admin WHERE username=?'); $st->execute([$u]);
                    if ((int)$st->fetchColumn() > 0) $err = 'Bu login band.';
                    else {
                        $pdo->prepare('INSERT INTO admin(username,password,role,full_name,is_active) VALUES(?,?,?,?,1)')
                            ->execute([$u, password_hash($p, PASSWORD_DEFAULT), $r, $n !== '' ? $n : $u]);
                        $msg = 'Xodim qo‘shildi.';
                    }
                }
            } elseif ($id > 0) {
                $st = $pdo->prepare('SELECT * FROM admin WHERE id=?'); $st->execute([$id]); $row = $st->fetch();
                if (!$row) { $err = 'Xodim topilmadi.'; }
                else {
                    $admins = (int)$pdo->query("SELECT COUNT(*) FROM admin WHERE role='admin' AND is_active=1")->fetchColumn();
                    $isLastAdmin = ($row['role'] === 'admin' && (int)$row['is_active'] === 1 && $admins <= 1);
                    if ($action === 'role') {
                        $r = $_POST['role'] ?? '';
                        if (!isset(FF_ROLES[$r])) $err = 'Rol noto‘g‘ri.';
                        elseif ($isLastAdmin && $r !== 'admin') $err = 'Oxirgi adminning rolini o‘zgartirib bo‘lmaydi.';
                        else { $pdo->prepare('UPDATE admin SET role=? WHERE id=?')->execute([$r, $id]); $msg = 'Rol yangilandi.'; }
                    } elseif ($action === 'toggle') {
                        if ($id === $me || $isLastAdmin) $err = 'Bu hisobni o‘chirib bo‘lmaydi.';
                        else { $pdo->prepare('UPDATE admin SET is_active=1-is_active WHERE id=?')->execute([$id]); $msg = 'Holat o‘zgardi.'; }
                    } elseif ($action === 'password') {
                        $p = (string)($_POST['password'] ?? '');
                        if (strlen($p) < 8) $err = 'Parol kamida 8 belgi bo‘lsin.';
                        else { $pdo->prepare('UPDATE admin SET password=? WHERE id=?')->execute([password_hash($p, PASSWORD_DEFAULT), $id]); $msg = 'Parol almashtirildi.'; }
                    } elseif ($action === 'delete') {
                        if ($id === $me || $isLastAdmin) $err = 'Bu hisobni o‘chirib bo‘lmaydi.';
                        else { $pdo->prepare('DELETE FROM admin WHERE id=?')->execute([$id]); $msg = 'Xodim o‘chirildi.'; }
                    }
                }
            }
        } catch (Throwable $e) { error_log('staff: ' . $e->getMessage()); $err = 'Xatolik yuz berdi.'; }
    }
}
$rows = $pdo->query('SELECT id,username,full_name,role,is_active,created_at FROM admin ORDER BY id')->fetchAll();
$csrf = ff_csrf();
$lang = ff_current_lang();
?>
<!doctype html><html lang="<?= htmlspecialchars($lang) ?>"><head><meta charset="utf-8"><link rel="icon" type="image/svg+xml" href="../img/favicon.svg"><meta name="viewport" content="width=device-width,initial-scale=1"><title>FastFood — Xodimlar</title><link rel="stylesheet" href="../css/style.css"><link rel="stylesheet" href="../css/theme-elegant.css?v=2"><script src="../js/theme.js"></script></head><body>
<?php include __DIR__ . '/sidebar.php'; ?>
<main class="admin-content">
  <div class="page-topline"><div><div class="eyebrow">XODIMLAR</div><h1>Xodimlar va rollar</h1><p class="admin-muted">Afitsant, oshxona, kassir va boshqa xodimlar faqat o‘z ish joyini ko‘radi.</p></div></div>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

  <div class="admin-panel">
    <div class="panel-kicker">YANGI XODIM</div><h2>Xodim qo‘shish</h2>
    <form method="post" class="staff-form">
      <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="add">
      <label>Ism<input name="full_name" maxlength="120" placeholder="Masalan: Aziz"></label>
      <label>Login<input name="username" required maxlength="50" autocomplete="off" placeholder="aziz"></label>
      <label>Parol (kamida 8)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
      <label>Rol<select name="role">
        <?php foreach (FF_ROLES as $k => $v): ?><option value="<?= $k ?>" <?= $k === 'waiter' ? 'selected' : '' ?>><?= htmlspecialchars($v['label']) ?></option><?php endforeach; ?>
      </select></label>
      <button class="btn" type="submit">Qo‘shish</button>
    </form>
  </div>

  <div class="admin-panel table-panel-pro" style="margin-top:18px">
    <div class="panel-heading"><div><div class="panel-kicker">RO‘YXAT</div><h2>Barcha xodimlar</h2></div><span class="soft-count"><?= count($rows) ?></span></div>
    <div class="table-scroll"><table>
      <thead><tr><th>Ism / login</th><th>Rol</th><th>Holat</th><th>Parol</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($rows as $r): $isMe = ((int)$r['id'] === $me); ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['full_name'] ?: $r['username']) ?></strong><br><small class="admin-muted"><?= htmlspecialchars($r['username']) ?><?= $isMe ? ' · siz' : '' ?></small></td>
          <td>
            <form method="post" class="inline-form">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="role"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <select name="role" onchange="this.form.submit()">
                <?php foreach (FF_ROLES as $k => $v): ?><option value="<?= $k ?>" <?= $r['role'] === $k ? 'selected' : '' ?>><?= htmlspecialchars($v['label']) ?></option><?php endforeach; ?>
              </select>
            </form>
          </td>
          <td>
            <form method="post" class="inline-form">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-secondary" <?= $isMe ? 'disabled' : '' ?>><?= (int)$r['is_active'] === 1 ? '✅ Faol' : '⛔ O‘chirilgan' ?></button>
            </form>
          </td>
          <td>
            <form method="post" class="inline-form">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="password"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="password" name="password" minlength="8" placeholder="Yangi parol" autocomplete="new-password" required>
              <button class="btn btn-sm">OK</button>
            </form>
          </td>
          <td>
            <?php if (!$isMe): ?>
            <form method="post" class="inline-form" onsubmit="return confirm('Xodim o‘chirilsinmi?')">
              <input type="hidden" name="csrf" value="<?= $csrf ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="icon-btn danger" title="O‘chirish">×</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </div>

  <div class="admin-panel" style="margin-top:18px">
    <div class="panel-kicker">RUXSATLAR</div><h2>Kim nimani ko‘radi</h2>
    <ul class="role-help">
      <li><b>Admin</b> — hammasi, xodimlar va backup.</li>
      <li><b>Menejer</b> — bosh sahifa, katalog, stollar, moliya, hisobotlar, oshxona va afitsant ko‘rinishi.</li>
      <li><b>Afitsant</b> — faqat tezkor POS va chek.</li>
      <li><b>Oshxona</b> — faqat oshxona cheklari.</li>
      <li><b>Kassir</b> — buyurtma qabul qilish, chek, kirim/chiqim.</li>
      <li><b>Yetkazib berish</b> — buyurtma qabul qilish va kuryerlarga biriktirish.</li>
    </ul>
  </div>
</main><script src="../js/lang-switch.js"></script></body></html>
