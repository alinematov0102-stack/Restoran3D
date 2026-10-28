# FastFood Production — o‘rnatish

## 1. Hosting
- PHP 8.x va MySQL/MariaDB hosting tayyorlang.
- HTTPS/SSL yoqing.

## 2. Fayllar
ZIP tarkibini hostingdagi web papkaga yuklang.

## 3. Installer
Brauzerda `/install/` ni oching va database + admin ma’lumotlarini kiriting.
Installer database jadvallarini yaratadi, adminni yaratadi va `.env` yozadi. O‘rnatish tugagach installer avtomatik qulflanadi.

## 4. Admin
`/admin/` orqali kiring.

## 5. Kuryer
Installerda ixtiyoriy kuryer account yaratish mumkin.

## 6. Git
`.env`, real uploadlar va backup fayllarini GitHub'ga push qilmang.

## 7. Cron
Kunlik moliyaviy yopish uchun `fastfood/daily_close_cron.php` ni hosting cron'iga qo‘ying.
