#!/bin/sh
set -e

mkdir -p /var/www/html/var/cache /var/www/html/var/log

# En prod : préchauffer le cache AVANT de fixer les permissions.
# cache:warmup tourne en root ici (pas d'utilisateur changé encore) ;
# le chown récursif qui suit remet TOUT var/ en www-data — fichiers de cache inclus.
# Fail-fast voulu : si le warmup échoue (secret manquant, config KO), le
# conteneur ne démarre pas et l'erreur est visible dans docker compose up.
if [ "$APP_ENV" = "prod" ]; then
    php bin/console cache:warmup
fi

chown -R www-data:www-data /var/www/html/var
chmod -R ug+rwX /var/www/html/var

exec "$@"
