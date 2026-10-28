FROM php:8.2-apache

# PHP kengaytmalari: MySQL (PDO) va rasm bilan ishlash (GD, webp/jpeg/png), mbstring
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev libonig-dev libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install pdo pdo_mysql gd mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# .htaccess ishlashi uchun
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/app.conf \
    && a2enconf app

# 3D modellar uchun yuklash limiti
RUN printf 'upload_max_filesize=32M\npost_max_size=36M\nmemory_limit=256M\n' \
    > /usr/local/etc/php/conf.d/uploads.ini

WORKDIR /var/www/html
COPY . .

RUN mkdir -p uploads/models uploads/products \
    && chown -R www-data:www-data /var/www/html/uploads

# Render beradigan PORT ga moslash
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground
