FROM php:8.4-fpm-bookworm AS runtime

# Repo PGDG — pg_dump mesti sepadan versi server (postgres:16) untuk backup:run.
RUN apt-get update && apt-get install -y --no-install-recommends curl ca-certificates gnupg \
 && install -d /usr/share/postgresql-common/pgdg \
 && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc -o /usr/share/postgresql-common/pgdg/apt.postgresql.org.asc \
 && echo "deb [signed-by=/usr/share/postgresql-common/pgdg/apt.postgresql.org.asc] https://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" > /etc/apt/sources.list.d/pgdg.list

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libexif-dev \
    libxml2-dev libonig-dev \
    tesseract-ocr tesseract-ocr-msa ghostscript qpdf unpaper pngquant poppler-utils \
    python3 python3-pip python3-venv postgresql-client-16 curl \
 && python3 -m venv /opt/ocr \
 && /opt/ocr/bin/pip install --no-cache-dir ocrmypdf img2pdf \
 && ln -s /opt/ocr/bin/ocrmypdf /usr/local/bin/ocrmypdf \
 && ln -s /opt/ocr/bin/img2pdf /usr/local/bin/img2pdf \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl zip gd bcmath pcntl exif mbstring dom simplexml xmlreader opcache \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

FROM runtime AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /build
COPY . .
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative

# Bina aset SELEPAS vendor: tema Filament (resources/css/filament/theme.css)
# @import CSS vendor filament dan @source kelas dalam app/Filament — jadi stage
# ini MESTI ada vendor + app, bukan resources sahaja.
FROM node:20-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js ./
COPY app ./app
COPY --from=vendor /build/vendor ./vendor
RUN npm run build

FROM runtime AS app
WORKDIR /var/www/html
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /build/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /build/public/build ./public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/diwan.ini
COPY docker/entrypoint.sh /usr/local/bin/diwan-entrypoint

RUN chmod +x /usr/local/bin/diwan-entrypoint \
 && mkdir -p storage/app/tmp storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
 && chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R ug+rwX storage bootstrap/cache

USER www-data
ENTRYPOINT ["diwan-entrypoint"]
CMD ["php-fpm"]

HEALTHCHECK --interval=30s --timeout=10s --start-period=30s --retries=3 \
    CMD php artisan diwan:health --quiet || exit 1

FROM nginx:1.27-alpine AS web
COPY --from=app /var/www/html/public /var/www/html/public
COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
