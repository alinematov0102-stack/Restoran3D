<?php
// daily_close_cron.php - Har kuni soat 00:00 da aniq ishga tushirish uchun (Windows Task Scheduler)
//
// SOZLASH YO'RIQNOMASI:
// 1. Windows'da "Task Scheduler" (Vazifalar rejalashtiruvchisi) dasturini oching
// 2. "Create Basic Task" -> nomini yozing (masalan "FastFood Kunlik Yopish")
// 3. Trigger: Daily, vaqti: 00:00
// 4. Action: "Start a program"
//    Program/script:  D:\OpenServer\modules\php\PHP-8.1\php.exe   (sizdagi PHP yo'liga moslang)
//    Arguments:        "D:\OpenServer\domains\f18\dars3admin2\fastfood\daily_close_cron.php"
// 5. Saqlang - shundan keyin har kuni aynan 00:00 da avtomatik ishlaydi

require __DIR__ . '/database.php';
require __DIR__ . '/../admin/daily_close.php';

$closed = ff_close_past_days($pdo);

if (empty($closed)) {
    echo "Yopish uchun kunlar topilmadi.\n";
} else {
    foreach ($closed as $d) {
        echo $d['date'] . " kuni uchun " . number_format($d['amount'], 0, '', ' ') . " so'm kirimga qo'shildi.\n";
    }
}
