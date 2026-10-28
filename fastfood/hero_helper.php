<?php
// hero_helper.php - Admin "Hero" bo'limidan yuklangan restoran fon rasmini topadi (uploads/hero.*).
// Rasm yo'q bo'lsa null qaytaradi - u holda CSS dagi atmosferali gradient ko'rinadi.
function ff_hero_image(): ?string
{
    foreach (['jpg', 'jpeg', 'png', 'webp'] as $e) {
        $f = __DIR__ . '/../uploads/hero.' . $e;
        if (is_file($f)) return '../uploads/hero.' . $e . '?v=' . filemtime($f);
    }
    return null;
}
