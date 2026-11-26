#!/bin/sh
# Prepara el almacenamiento (volumen persistente) y, en el contenedor web,
# la base de datos. Después ejecuta el comando del servicio.
set -e
cd /app

mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
         storage/framework/sessions storage/framework/views storage/logs
[ -f storage/app/database.sqlite ] || touch storage/app/database.sqlite

if [ "${FOODIT_ROL:-web}" = "web" ]; then
    php artisan foodit:preparar
    php artisan storage:link --force >/dev/null 2>&1 || true
fi

php artisan optimize >/dev/null

exec "$@"
