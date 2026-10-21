FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-enabled/mater.conf \
    && printf 'display_errors=Off\nlog_errors=On\nupload_max_filesize=10M\n' > /usr/local/etc/php/conf.d/mater.ini

WORKDIR /var/www/html
