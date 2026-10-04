#!/bin/sh
set -e

if [ "$RUN_MIGRATIONS" = "1" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
    php bin/console app:create-admin "${ADMIN_LOGIN:-admin}" "${ADMIN_PASSWORD:-admin}"
fi

exec "$@"
