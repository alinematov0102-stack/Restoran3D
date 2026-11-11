-- Tayyor kategoriyalar (pastki bo'limlarda ko'rinadi). TiDB SQL Editor'da bir marta ishga tushiring.
-- Allaqachon bor nomlar qaytadan qo'shilmaydi. Keyin admin paneldagi Katalog bo'limidan
-- mahsulotlarni shu kategoriyalarga biriktiring, keraksizini o'chiring.
USE restoran3d;
INSERT INTO categories (name, name_ru, name_en) SELECT 'Taomlar', 'Блюда', 'Main dishes' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Taomlar');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Ichimliklar', 'Напитки', 'Drinks' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Ichimliklar');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Desertlar', 'Десерты', 'Desserts' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Desertlar');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Salatlar', 'Салаты', 'Salads' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Salatlar');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Pitsa', 'Пицца', 'Pizza' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Pitsa');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Burger', 'Бургеры', 'Burgers' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Burger');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Lavash', 'Лаваш', 'Lavash' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Lavash');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Zakuskilar', 'Закуски', 'Snacks' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Zakuskilar');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Sho''rvalar', 'Супы', 'Soups' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Sho''rvalar');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Kofe va choy', 'Кофе и чай', 'Coffee & tea' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Kofe va choy');
INSERT INTO categories (name, name_ru, name_en) SELECT 'Setlar', 'Сеты', 'Combos' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM categories WHERE name = 'Setlar');
