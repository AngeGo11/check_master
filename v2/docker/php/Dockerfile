FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev libonig-dev libsqlite3-dev libpng-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql pdo_sqlite mbstring intl zip bcmath opcache gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/check-master.ini
COPY docker/php/entrypoint.sh /usr/local/bin/check-master-entrypoint
RUN chmod +x /usr/local/bin/check-master-entrypoint

WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
ENTRYPOINT ["check-master-entrypoint"]
CMD ["php-fpm"]
