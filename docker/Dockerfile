FROM node:20-alpine AS assets
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM composer:2 AS vendor
WORKDIR /build
COPY . .
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader \
    --classmap-authoritative \
    --ignore-platform-req=ext-pcntl \
    --ignore-platform-req=ext-posix

FROM php:8.3-fpm-bookworm AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libzip-dev libicu-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libexif-dev \
    tesseract-ocr tesseract-ocr-msa ghostscript qpdf unpaper pngquant \
    python3 python3-pip python3-venv postgresql-client curl \
 && python3 -m venv /opt/ocr \
 && /opt/ocr/bin/pip install --no-cache-dir ocrmypdf img2pdf \
 && ln -s /opt/ocr/bin/ocrmypdf /usr/local/bin/ocrmypdf \
 && ln -s /opt/ocr/bin/img2pdf /usr/local/bin/img2pdf \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_pgsql pgsql intl zip gd bcmath pcntl exif \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apt-get clean \
 && rm -rf /var/lib/apt/lists/*

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
