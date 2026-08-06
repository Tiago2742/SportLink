# ── Stage base : dépendances système, Apache, config ──────────────────────
# Utilisé en DEV (target: base dans docker-compose.yml).
# Le code arrive par bind-mount ; vendor par le volume back_vendor.
FROM php:8.3-apache AS base

RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache \
    && a2enmod rewrite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# OPcache — validate_timestamps=0 critique sur Windows bind mount
# (évite ~300 stat() par requête via WSL2, gain de 4-8s)
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.interned_strings_buffer=16'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

COPY apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /var/www/html

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

# ── Stage prod : code figé + dépendances Composer sans dev ────────────────
# Utilisé en PROD (target: prod dans docker-compose.prod.yml).
# Pas de bind-mount : le code est figé dans l'image au moment du build.
# Les clés JWT et .env.local sont montés depuis l'hôte (voir docker-compose.prod.yml).
FROM base AS prod

COPY . .
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize-autoloader \
    --classmap-authoritative
