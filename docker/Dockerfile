FROM php:8.4-apache

# padrões de desenvolvimento; o docker-compose.prod.yml troca para os de produção
ENV PHP_DISPLAY_ERRORS=On \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 \
    PHP_OPCACHE_REVALIDATE_FREQ=2 \
    PHP_SESSION_COOKIE_SECURE=0

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev \
    && docker-php-ext-install pdo_pgsql opcache \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite expires headers deflate remoteip \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && printf '<Directory /var/www/html/public>\n    AllowOverride All\n</Directory>\n' > /etc/apache2/conf-enabled/mater.conf \
    && printf 'display_errors=${PHP_DISPLAY_ERRORS}\nerror_reporting=E_ALL & ~E_WARNING & ~E_NOTICE & ~E_DEPRECATED\nlog_errors=On\nupload_max_filesize=10M\n' > /usr/local/etc/php/conf.d/mater.ini \
    && printf 'opcache.enable=1\nopcache.memory_consumption=128\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=${PHP_OPCACHE_VALIDATE_TIMESTAMPS}\nopcache.revalidate_freq=${PHP_OPCACHE_REVALIDATE_FREQ}\nrealpath_cache_size=4096K\nrealpath_cache_ttl=600\n' > /usr/local/etc/php/conf.d/opcache-mater.ini \
    && printf 'expose_php=Off\nsession.cookie_httponly=1\nsession.use_strict_mode=1\nsession.cookie_samesite=Lax\nsession.cookie_secure=${PHP_SESSION_COOKIE_SECURE}\n' > /usr/local/etc/php/conf.d/security-mater.ini

COPY docker/apache-cache.conf /etc/apache2/conf-enabled/cache.conf
COPY docker/apache-security.conf /etc/apache2/conf-enabled/security-mater.conf

WORKDIR /var/www/html

# o código vai dentro da imagem (produção); em desenvolvimento o docker-compose.yml monta a pasta por cima
COPY --chown=www-data:www-data . /var/www/html
RUN mkdir -p storage && chown www-data:www-data storage && chmod 750 storage
