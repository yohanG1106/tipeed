FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite

COPY . /var/www/html/
COPY index/ /var/www/html/

RUN echo 'session.save_path = "/tmp"' >> /usr/local/etc/php/php.ini

RUN mkdir -p /var/www/html/uploads/chat_files \
    && mkdir -p /var/www/html/uploads/community_files \
    && chmod -R 775 /var/www/html/uploads

EXPOSE 80

ENV APACHE_DOCUMENT_ROOT /var/www/html