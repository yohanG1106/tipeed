FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2dismod mpm_event mpm_worker mpm_prefork 2>/dev/null || true \
    && a2enmod mpm_prefork \
    && a2enmod rewrite

WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p /var/www/html/index/uploads/chat_files \
    && mkdir -p /var/www/html/index/uploads/community_files \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/index/uploads

EXPOSE 80