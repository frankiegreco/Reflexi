FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql mysqli \
    && a2enmod rewrite expires headers

# AllowOverride None è il default dell'immagine base: senza questo, il
# webroot/.htaccess (URL pulite /news/{slug}, rewrite di sitemap.xml) verrebbe
# ignorato in silenzio.
COPY apache-allowoverride.conf /etc/apache2/conf-enabled/zz-allowoverride.conf

# unzip: necessario a Composer per scompattare i pacchetti scaricati (senza,
# fallisce silenziosamente ogni volta che il container viene ricreato da zero)
RUN apt-get update && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

# Composer, per installare PHPMailer e Stripe
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json ./
RUN composer install --no-dev --optimize-autoloader --no-interaction || true
