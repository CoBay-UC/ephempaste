FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        libpq-dev \
        libonig-dev \
    && docker-php-ext-install pdo_pgsql mbstring \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/php/app.ini /usr/local/etc/php/conf.d/zz-ephempaste.ini
COPY docker/start-web.sh /usr/local/bin/start-web.sh
COPY . /var/www/html

RUN chmod +x /usr/local/bin/start-web.sh \
    && mkdir -p /run/nginx \
    && nginx -t

WORKDIR /var/www/html

EXPOSE 80

CMD ["/usr/local/bin/start-web.sh"]
