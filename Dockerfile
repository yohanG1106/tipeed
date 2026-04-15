FROM php:8.2-apache

RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && a2enmod rewrite mpm_prefork \
    && a2dismod mpm_event || true \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/index/uploads/chat_files \
    && mkdir -p /var/www/html/index/uploads/community_files \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/index/uploads

EXPOSE 80