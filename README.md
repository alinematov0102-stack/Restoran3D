# ZARAFSHON — Laravel restoran loyihasi

Loyiha Laravel 8, Blade va MySQL bilan ishlaydi. Saytdagi menyu ma’lumotlari `taoms` jadvalidan olinadi; admin panel orqali taom qo‘shish, tahrirlash, rasm yuklash va o‘chirish mumkin.

## OpenServer / Windows’da ishga tushirish

1. Loyihani `D:\OpenServer\domains\taomlar` papkasiga joylang.
2. Shu papkada terminal ochib, bog‘liqliklarni o‘rnating:
   ```bash
   composer install
   npm install
   ```
3. `.env.example` faylidan `.env` nusxa oling. `APP_URL`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` qiymatlarini o‘zingizdagi OpenServer/MySQL sozlamalariga moslang. `taomlar` nomli bo‘sh MySQL bazasini yarating.
4. Ilova kalitini yarating va jadvallarni ishga tushiring:
   ```bash
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   ```
5. Frontend assetlarini yig‘ing:
   ```bash
   npm run dev
   ```
6. OpenServer domeni yoki `php artisan serve` orqali saytni oching. Birinchi marta admin panelga kirish uchun `/register` orqali foydalanuvchi yarating, so‘ng `/login` qilib `/admin` manziliga kiring.

## Muhim

- ZIP ichiga maxfiy `.env`, `node_modules`, `vendor`, sessiyalar va loglar kiritilmadi. `.env` faylini o‘zingiz yarating; hech qachon haqiqiy `.env` faylini ommaga tarqatmang.
- `php artisan migrate --seed` demo taomlarni faqat `taoms` jadvali bo‘sh bo‘lsa qo‘shadi.
- Rasm yuklash `storage/app/public/taomlar` ichiga yoziladi. `php artisan storage:link` bu rasmlarni brauzerda ko‘rsatish uchun kerak.
- Admin sahifasi `auth` middleware bilan himoyalangan. Ishlab chiqarish muhitiga chiqarishdan oldin ro‘yxatdan o‘tishni cheklash va administrator rollarini qo‘shish tavsiya etiladi.
- Tashqi Google Fonts va Unsplash hero rasmi internet bo‘lganda yuklanadi; menyu, narxlar va CRUD mahalliy Laravel/MySQL orqali ishlaydi.
