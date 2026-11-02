# Render'ga joylash

1. Tashqi MySQL oching (Aiven / TiDB Cloud / Railway).
2. `install/schema.sql`, keyin `install/create_admin.sql` ni import qiling.
3. GitHub'ga push qiling (Dockerfile ildizda).
4. Render -> Web Service -> Runtime: Docker.
5. Environment: DB_HOST, DB_NAME, DB_USER, DB_PASS, APP_ENV=production
6. Admin: /admin/  (admin / admin12345)

## v4 o'zgarishlari
- Mijoz sayti telefonda ilova ko'rinishida (yon menyu, pastki kategoriya tablari, suzuvchi savat).
- Menyu ma'lumoti 2 daqiqa keshlanadi, admin o'zgartirsa kesh darhol tozalanadi.
- Xodimlar: rol + qo'shimcha ruxsatlar (Xodimlar bo'limi).
- Admin paneldan "Yetkazib berish" olib tashlandi.
- Tarjima: sahifadagi hamma matn, select, placeholder va keyin yuklangan kontent tarjima qilinadi.
