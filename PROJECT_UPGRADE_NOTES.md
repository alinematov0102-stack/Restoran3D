# Restoran3D — boshqaruv va frontend bo'yicha yangilanishlar

## Xodim rollari

- **Admin** — barcha bo'limlar, xodimlar va backup.
- **Menejer** — operatsion boshqaruv, katalog, stollar, oshxona, afitsant, hisobot va moliya.
- **Afitsant** — POS va chek. Oshxona, kirim/chiqim, hisobot va xodimlar bo'limiga kira olmaydi.
- **Oshxona** — oshxona buyurtmalari va chek holatlari. Afitsant POS va moliya bo'limlariga kira olmaydi.
- **Kassir** — buyurtmalar, chek va kirim/chiqim.
- **Operator** — buyurtma qabul qilish va holatini yangilash; moliya va oshxona paneliga kira olmaydi.

Ruxsatlar faqat menyuni yashirish bilan cheklanmaydi: `admin/auth.php` dagi `ff_guard()` server tomonida ham tekshiradi. Shuning uchun xodim boshqa bo'lim URL manzilini qo'lda yozsa ham ruxsatsiz sahifaga kira olmaydi.

## Kirish yo'nalishi

`/admin/` endi xodimning roliga mos ish oynasiga yo'naltiradi. Masalan, afitsant `waiter.php`, oshxona xodimi `kitchen.php`, kassir/operator esa `receive_orders.php` sahifasiga o'tadi. Kirishsiz foydalanuvchi `login.php` ga yuboriladi. Saytning asosiy manzili esa `fastfood/index.php` mijozlar sahifasini ochishda davom etadi.

## Responsive interfeys

Admin yon menyusi noutbukda doimiy ko'rinadi. 1024px va undan kichik ekranlarda uch chiziqli tugma orqali yon panel ochiladi; fonni bosish yoki Escape tugmasi uni yopadi. Qo'shimcha CSS telefon va planshetlarda kartalar, formalar, jadvallar va tugmalarni moslashtiradi.

## Vue 3 tanlovi va keyingi migratsiya

Bu repozitoriyda FastFood admini alohida PHP sahifalari va PDO backend bilan ishlaydi. Uni birdan Vue/React SPA ga aylantirish uchun har bir amaliyotga autentifikatsiyalangan API endpoint, validatsiya, CSRF himoyasi va frontend komponentlari kerak bo'ladi. Shu sababli ushbu yangilanishda mavjud backend saqlandi va rollar hamda responsive navigatsiya mustahkamlandi.

Keyingi bosqich uchun **Vue 3** tavsiya qilinadi: avval dashboard, keyin buyurtmalar, katalog va xodimlar oynalari komponentlarga ajratiladi; PHP/PDO backend API sifatida qoladi. Bu React bilan ham amalga oshadi, ammo hozirgi Laravel Mix/PHP tuzilmasiga Vue'ni bosqichma-bosqich qo'shish sodda.

## Muhim

- `package.json` noto'g'ri tarkibda bo'lgan; u Laravel Mix build skriptlari va mavjud lockfile dependency'lariga moslab tiklandi.
- Production buildni deploy jarayonida `npm install` va `npm run production` bajaradi.
- Render'dagi real tezlikni faqat deploydan keyin, server loglari va brauzer Network/Performance o'lchovlari bilan tasdiqlash mumkin.
