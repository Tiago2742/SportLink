#!/bin/sh
set -e

# Volume Docker php_var : créé en root, Apache tourne en www-data
mkdir -p /var/www/html/var/cache /var/www/html/var/log
chown -R www-data:www-data /var/www/html/var
chmod -R ug+rwX /var/www/html/var

exec "$@"
