FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite expires headers deflate \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-enabled/mater.conf \
    && printf 'display_errors=${PHP_DISPLAY_ERRORS}\nerror_reporting=E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED\nlog_errors=On\nupload_max_filesize=10M\n' > /usr/local/etc/php/conf.d/mater.ini \
    && printf 'opcache.enable=1\nopcache.memory_consumption=128\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=1\nopcache.revalidate_freq=${PHP_OPCACHE_REVALIDATE_FREQ}\nrealpath_cache_size=4096K\nrealpath_cache_ttl=600\n' > /usr/local/etc/php/conf.d/opcache-mater.ini

COPY docker/apache-cache.conf /etc/apache2/conf-enabled/cache.conf

WORKDIR /var/www/html
