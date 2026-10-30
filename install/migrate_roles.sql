-- Mavjud bazaga rollarni qo'shish (TiDB SQL Editor'da bir marta ishga tushiring).
-- Eslatma: login sahifasi buni avtomatik ham bajaradi, bu fayl zaxira uchun.
USE restoran3d;
ALTER TABLE admin ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'admin';
ALTER TABLE admin ADD COLUMN full_name VARCHAR(120) NULL;
ALTER TABLE admin ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1;
