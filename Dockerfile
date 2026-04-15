FROM php:8.2-apache

# Install PHP extensions needed for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite, fix MPM conflict (disable event, use prefork)
RUN a2enmod rewrite \
    && a2dismod mpm_event || true \
    && a2enmod mpm_prefork

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . /var/www/html/

# Set correct permissions for uploads directory
RUN mkdir -p /var/www/html/index/uploads/chat_files \
    && mkdir -p /var/www/html/index/uploads/community_files \
    && chown -R www-data:www-data /var/www/html/index/uploads \
    && chmod -R 775 /var/www/html/index/uploads

# Set document root to the index/ subfolder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/index

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/000-default.conf \
    && sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/apache2.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' \
        /etc/apache2/apache2.conf

# Apache listens on port 80 (Railway reverse-proxies to it)
EXPOSE 80

CMD ["apache2-foreground"]
