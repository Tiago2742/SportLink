FROM php:8.3-cli

# Dépendances système + extensions PHP nécessaires à Symfony + MySQL
RUN apt-get update && apt-get install -y \
    git unzip libicu-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql intl zip opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# On lance le serveur web intégré de Symfony
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]