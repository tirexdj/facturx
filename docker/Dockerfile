# Multi-stage Dockerfile pour FacturX
FROM php:8.4-fpm-alpine as base

# Arguments de build
ARG UID=1000
ARG GID=1000

# Installation des dépendances système
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    icu-dev \
    oniguruma-dev \
    postgresql-dev \
    redis \
    supervisor \
    nginx \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo \
        pdo_pgsql \
        pcntl \
        zip \
        sockets

# Installation de Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Création de l'utilisateur
RUN addgroup -g ${GID} -S laravel \
    && adduser -u ${UID} -S laravel -G laravel

# Configuration PHP
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-custom.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Répertoire de travail
WORKDIR /var/www

# Copie des fichiers de dépendances
COPY composer.json composer.lock ./

# Stage de développement
FROM base as development

# Installation des dépendances de développement
RUN composer install --no-scripts --no-autoloader

# Copie du code source
COPY . .

# Finalisation de l'installation Composer
RUN composer dump-autoload --optimize

# Configuration des permissions
RUN chown -R laravel:laravel /var/www \
    && chmod -R 755 storage bootstrap/cache

USER laravel

EXPOSE 9000

CMD ["php-fpm"]

# Stage de production
FROM base as production

# Variables d'environnement pour la production
ENV APP_ENV=production
ENV APP_DEBUG=false

# Installation des dépendances de production uniquement
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copie du code source
COPY . .

# Finalisation de l'installation Composer
RUN composer dump-autoloader --optimize

# Installation de Node.js pour le build des assets
RUN apk add --no-cache nodejs npm

# Build des assets frontend
RUN npm ci --production=false && npm run build && rm -rf node_modules

# Configuration des permissions
RUN chown -R laravel:laravel /var/www \
    && chmod -R 755 storage bootstrap/cache

# Optimisations Laravel
RUN php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && php artisan event:cache

USER laravel

EXPOSE 9000

CMD ["php-fpm"]

# Stage pour les workers de queue
FROM production as queue

USER root

# Installation de Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

USER laravel

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
