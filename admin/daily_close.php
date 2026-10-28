<?php
// daily_close.php - O'tgan kunlarning jami savdosini avtomatik "kirim" ga o'tkazadi
// Bu fayl admin/dashboard.php ochilganda avtomatik ishga tushadi (server sozlamasi shart emas),
// yoki soat 00:00 da aniq ishga tushishi uchun Windows Task Scheduler orqali CLI'da chaqirilishi mumkin
// (qarang: fastfood/daily_close_cron.php)

function ff_close_past_days(PDO $pdo): array {
    $closed = [];

    // Bugungi kundan oldingi, hali yopilmagan kunlarni topamiz
    $stmt = $pdo->query("
        SELECT DATE(o.created_at) AS order_date, SUM(o.total) AS day_total
        FROM orders o
        WHERE DATE(o.created_at) < CURDATE()
        AND o.status != 'bekor qilingan'
        AND DATE(o.created_at) NOT IN (SELECT closing_date FROM daily_closures)
        GROUP BY DATE(o.created_at)
    ");
    $pendingDays = $stmt->fetchAll();

    foreach ($pendingDays as $day) {
        $date = $day['order_date'];
        $amount = (float)$day['day_total'];

        if ($amount <= 0) {
            continue;
        }

        $pdo->beginTransaction();
        try {
            $ins = $pdo->prepare("INSERT INTO transactions (type, amount, description) VALUES ('kirim', ?, ?)");
            $ins->execute([$amount, "Kunlik savdo - " . $date]);

            $mark = $pdo->prepare("INSERT INTO daily_closures (closing_date, amount) VALUES (?, ?)");
            $mark->execute([$date, $amount]);

            $pdo->commit();
            $closed[] = ['date' => $date, 'amount' => $amount];
        } catch (Exception $e) {
            $pdo->rollBack();
        }
    }

    return $closed;
}
