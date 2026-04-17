FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html/

RUN echo 'session.save_path = "/tmp"' >> /usr/local/etc/php/php.ini

RUN mkdir -p /var/www/html/index/uploads/chat_files \
    && mkdir -p /var/www/html/index/uploads/community_files \
    && chmod -R 775 /var/www/html/index/uploads

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/var/www/html/index", "/var/www/html/router.php"]