# ============================================================
#  Gimnasio Castillo Americano — imagen de producción
# ============================================================
#  Reemplaza a `php -S`, que es el servidor de DESARROLLO de PHP:
#  atiende UNA petición a la vez. Con 30 padres consultando boletines
#  al mismo tiempo, el nº 30 espera a que terminen los 29 anteriores.
#
#  Apache (mpm_prefork del contenedor oficial) atiende ~150 peticiones
#  concurrentes y además sirve el CSS/las imágenes sin pasar por PHP,
#  y respeta el .htaccess (que es lo que protege .env, /src, /config
#  y los boletines en PDF — con `php -S` ese archivo se IGNORA).
# ============================================================

FROM php:8.3-apache

# Extensiones que usa el proyecto: PostgreSQL (Neon), GD y zip para
# los PDF de dompdf, intl/mbstring para el texto con acentos.
RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev \
    && docker-php-ext-configure gd --with-jpeg --with-freetype \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql gd zip \
    && a2enmod rewrite headers expires deflate \
    && rm -rf /var/lib/apt/lists/*

# Ajustes de PHP para producción
RUN { \
        echo 'expose_php = Off'; \
        echo 'display_errors = Off'; \
        echo 'log_errors = On'; \
        echo 'error_log = /dev/stderr'; \
        echo 'memory_limit = 256M'; \
        echo 'upload_max_filesize = 12M'; \
        echo 'post_max_size = 12M'; \
        echo 'max_execution_time = 60'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.memory_consumption = 128'; \
        echo 'opcache.max_accelerated_files = 10000'; \
        echo 'opcache.validate_timestamps = 0'; \
        echo 'session.cookie_httponly = 1'; \
        echo 'session.cookie_samesite = Lax'; \
        echo 'session.use_strict_mode = 1'; \
    } > /usr/local/etc/php/conf.d/zz-gca.ini

WORKDIR /var/www/html

# Primero solo los manifiestos, para que el caché de capas no reinstale
# las dependencias cada vez que cambia un .php
COPY composer.json composer.lock ./
COPY lib/composer.json lib/composer.lock ./lib/

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction \
    && composer install -d lib --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

RUN composer dump-autoload --no-dev --optimize \
    && composer dump-autoload -d lib --no-dev --optimize \
    && chown -R www-data:www-data /var/www/html \
    && mkdir -p assets/uploads/boletines assets/uploads/docentes assets/uploads/noticias \
    && chown -R www-data:www-data assets/uploads

# .htaccess necesita AllowOverride para que sus reglas apliquen
RUN printf '%s\n' \
    '<Directory /var/www/html>' \
    '    Options -Indexes +FollowSymLinks' \
    '    AllowOverride All' \
    '    Require all granted' \
    '</Directory>' \
    > /etc/apache2/conf-available/gca.conf \
    && a2enconf gca

# Railway inyecta $PORT; Apache viene fijado al 80.
CMD ["sh", "-c", "\
    sed -i \"s/^Listen 80$/Listen ${PORT:-80}/\" /etc/apache2/ports.conf && \
    sed -i \"s/:80>/:${PORT:-80}>/\" /etc/apache2/sites-available/000-default.conf && \
    exec apache2-foreground"]
