FROM php:8.3-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libpq-dev libzip-dev libicu-dev libpng-dev libjpeg-dev libfreetype6-dev libexif-dev \
    tesseract-ocr tesseract-ocr-msa ghostscript qpdf unpaper pngquant \
    python3 python3-pip python3-venv \
 && python3 -m venv /opt/ocr && /opt/ocr/bin/pip install --no-cache-dir ocrmypdf img2pdf \
 && ln -s /opt/ocr/bin/ocrmypdf /usr/local/bin/ocrmypdf \
 && ln -s /opt/ocr/bin/img2pdf /usr/local/bin/img2pdf \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_pgsql pgsql intl zip gd bcmath pcntl exif \
 && pecl install redis && docker-php-ext-enable redis \
 && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY docker/php.ini /usr/local/etc/php/conf.d/diwan.ini
