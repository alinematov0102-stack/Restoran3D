FROM php:8.2-apache

# PHP kengaytmalari: MySQL (PDO), rasm (GD: jpeg/webp/png), mbstring, opcache
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg-dev libwebp-dev libfreetype6-dev libonig-dev libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install pdo pdo_mysql gd mbstring opcache \
    && a2enmod rewrite deflate expires headers \
    && rm -rf /var/lib/apt/lists/*

# Apache: .htaccess, ServerName ogohlantirishini o'chirish, siqish va kesh
RUN printf 'ServerName localhost\n<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n' \
        > /etc/apache2/conf-available/app.conf \
    && printf '<IfModule mod_deflate.c>\n  AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json image/svg+xml\n</IfModule>\n<IfModule mod_expires.c>\n  ExpiresActive On\n  ExpiresByType text/css "access plus 7 days"\n  ExpiresByType application/javascript "access plus 7 days"\n  ExpiresByType image/svg+xml "access plus 30 days"\n  ExpiresByType image/webp "access plus 30 days"\n  ExpiresByType image/jpeg "access plus 30 days"\n  ExpiresByType image/png "access plus 30 days"\n  ExpiresByType model/gltf-binary "access plus 30 days"\n</IfModule>\n' \
        > /etc/apache2/conf-available/perf.conf \
    && a2enconf app perf

# PHP: yuklash limiti, opcache (tezlik), xavfsiz sessiya cookie
RUN printf 'upload_max_filesize=32M\npost_max_size=36M\nmemory_limit=256M\n\
opcache.enable=1\nopcache.memory_consumption=96\nopcache.max_accelerated_files=10000\nopcache.validate_timestamps=0\n\
session.cookie_httponly=1\nsession.cookie_samesite=Lax\nsession.cookie_secure=1\nsession.use_strict_mode=1\n' \
        > /usr/local/etc/php/conf.d/app.ini

WORKDIR /var/www/html
COPY . .

RUN mkdir -p uploads/models uploads/products \
    && chown -R www-data:www-data /var/www/html/uploads

# Render beradigan PORT ga moslash
CMD sed -i "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf \
    && sed -i "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/000-default.conf \
    && apache2-foreground
