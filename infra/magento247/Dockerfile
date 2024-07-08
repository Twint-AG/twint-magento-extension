FROM php:8.2.12-fpm@sha256:62fba478afcfb1a31c402ef29b0ae504fe52c1c6b65371e647c4672fa7299951 as base

ENV COMPOSER_HOME=/tmp/composer

RUN apt-get update && apt-get install -y --no-install-recommends gnupg \
    vim \
    cron \
    git \
    nginx \
    netcat-openbsd \
    libicu-dev \
    libfreetype6-dev \
    libjpeg-dev \
    libpng-dev \
    libsodium-dev \
    libxml2-dev \
    libxslt-dev \
    libzip-dev \
    rsync \
    supervisor \
    unzip \
    ;

RUN pecl install apcu-5.1.22

RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg
RUN docker-php-ext-install -j "$(nproc)" \
    bcmath \
    gd \
    intl \
    mysqli \
    opcache \
    pdo_mysql \
    sockets \
    soap \
    xsl \
    zip \
    ;

RUN docker-php-ext-enable apcu

RUN echo "memory_limit=1G" >> /usr/local/etc/php/conf.d/zz-memory-limit-php.ini
RUN echo "apc.enable=1" >> /usr/local/etc/php/conf.d/zz-apcu.ini
RUN echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/zz-apcu.ini
RUN echo "opcache.memory_consumption=512MB" >> /usr/local/etc/php/conf.d/zz-opcache.conf
RUN echo "opcache.max_accelerated_files=60000" >> /usr/local/etc/php/conf.d/zz-opcache.conf
RUN echo "opcache.consistency_checks=0" >> /usr/local/etc/php/conf.d/zz-opcache.conf
RUN echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/zz-opcache.conf
RUN echo "opcache.enable_cli=1" >> /usr/local/etc/php/conf.d/zz-opcache.conf

RUN echo "[www]" >> /usr/local/etc/php-fpm.d/zz-status.conf
RUN echo "; Expose PHP-FPM status" >> /usr/local/etc/php-fpm.d/zz-status.conf
RUN echo "pm.status_listen=9001" >> /usr/local/etc/php-fpm.d/zz-status.conf
RUN echo "pm.status_path=/status" >> /usr/local/etc/php-fpm.d/zz-status.conf

RUN mkdir -p /var/lib/nginx/{body,fastcgi} && \
    chown -R www-data:www-data /var/lib/nginx

RUN touch /run/nginx.pid && \
    chown www-data:www-data /run/nginx.pid \

RUN touch /var/run/crond.pid  && chown www-data:www-data /var/run/crond.pid

FROM base as build

COPY etc/supervisord.conf /etc/supervisor/supervisord.conf
COPY etc/nginx /etc/nginx

RUN curl https://files.magerun.net/n98-magerun2.phar -o /usr/local/bin/magerun \
    && chmod 755 /usr/local/bin/magerun

RUN mkdir -p /var/log/supervisor \
    && chown www-data:www-data /var/log/supervisor

USER www-data
WORKDIR /var/www/html

ARG COMPOSER_AUTH

COPY --from=composer:2.7.1@sha256:da5213f1c0c4db435ad335be4e782ebf8677b45d37677a6db1e73e474c7ad947 /usr/bin/composer /usr/bin/composer

COPY --chown=www-data composer.json composer.json

# Repo auth config
#
ARG MAGENTO_REPO_USERNAME
ARG MAGENTO_REPO_PASSWORD
RUN composer config --global http-basic.repo.magento.com ${MAGENTO_REPO_USERNAME} ${MAGENTO_REPO_PASSWORD}
ARG GITLAB_USERNAME
ARG GITLAB_TOKEN
RUN composer config --global http-basic.git.nfq.asia ${GITLAB_USERNAME} ${GITLAB_TOKEN}

RUN composer validate && \
    composer install \
        --no-interaction \
        --no-progress \
        --no-autoloader  \
        --no-dev

COPY --chown=www-data _app/etc/config.php app/etc/config.php

COPY --chown=www-data _bin bin
COPY _bin/entrypoint.sh /usr/local/bin/docker-php-entrypoint

COPY setup/performance-toolkit/profiles/ce/mok.xml setup/performance-toolkit/profiles/ce/mok.xml

EXPOSE 8080

FROM build as dev

RUN composer install \
    --no-interaction \
    --no-progress

FROM build as app

ENV MAGE_MODE=production

RUN composer dump-autoload \
        --optimize \
        --apcu
RUN rm -rf /var/www/html/vendor/twint/magento-2/zinfra
RUN php -d memory_limit=2G bin/magento setup:di:compile
RUN rm -rf /var/www/html/var/cache
RUN rm -rf /var/www/html/var/page_cache
RUN rm -rf /var/www/html/var/session

COPY --chown=www-data _app/etc/env.docker.php app/etc/env.php
COPY --chown=www-data update_module.sh update_module.sh
COPY --chown=www-data mount-folders.sh mount-folders.sh
COPY --chown=www-data run.sh run.sh
COPY --chown=www-data mount-folders.txt mount-folders.txt
RUN chmod +x update_module.sh mount-folders.sh run.sh

RUN ./run.sh
