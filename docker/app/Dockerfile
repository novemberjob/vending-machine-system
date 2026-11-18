# syntax=docker/dockerfile:1

# ---- Stage 1: install PHP dependencies ----
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install \
        --no-interaction \
        --prefer-dist \
        --no-progress \
        --optimize-autoloader \
        --no-scripts

# ---- Stage 2: application (PHP-FPM on Amazon Linux 2023) ----
FROM public.ecr.aws/amazonlinux/amazonlinux:2023 AS app

# PHP 8.3 is used instead of 8.2 because 8.2 reaches end of life on 2026-12-31.
# The MariaDB client provides mysql/mysqladmin for the entrypoint's DB bootstrap.
RUN dnf install -y --setopt=install_weak_deps=False \
        php8.3-cli \
        php8.3-common \
        php8.3-fpm \
        php8.3-intl \
        php8.3-mbstring \
        php8.3-mysqlnd \
        php8.3-opcache \
        php8.3-pdo \
        php8.3-process \
        php8.3-sodium \
        php8.3-xml \
        php8.3-zip \
        mariadb1011-client-utils \
    && dnf clean all \
    && mkdir -p /var/lib/php/session /run/php-fpm \
    && chown -R nobody:nobody /var/lib/php/session

WORKDIR /var/www/html

# Composer autoloader and third-party packages.
COPY --from=vendor /app/vendor ./vendor

# Application code.
COPY . .

# FPM listens on TCP :9000 for Nginx; keep env vars visible to PHP workers.
COPY docker/app/www.conf /etc/php-fpm.d/www.conf
COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R nobody:nobody /var/www/html

ENV DB_HOST=db \
    DB_PORT=3306 \
    DB_NAME=vms \
    DB_USER=root \
    DB_PASS=rootpass \
    JWT_SECRET=change-this-to-a-long-random-secret \
    ADMIN_NAME=Admin \
    ADMIN_EMAIL=admin@example.com \
    ADMIN_PASSWORD=admin12345

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
# AL2023's main php-fpm.conf sets daemonize=yes after including pool files, so a
# pool-level "daemonize = no" is overwritten. Force foreground + stderr via flags.
CMD ["php-fpm", "--nodaemonize", "--force-stderr"]
