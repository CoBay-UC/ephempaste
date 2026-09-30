#!/bin/sh
set -eu

# Start PHP-FPM in the background, then keep Nginx in the foreground so Docker
# can supervise the web container correctly.
php-fpm -D
exec nginx -g 'daemon off;'
