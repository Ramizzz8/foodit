# Foodit en un solo contenedor con FrankenPHP (servidor web + PHP).
# El mismo contenedor sirve también para la cola, Reverb y las tareas programadas
# (ver docker-compose del despliegue): solo cambia el comando.

# 1) Dependencias PHP
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --no-dev

# 2) Base con las extensiones que usa la aplicación
FROM dunglas/frankenphp:1-php8.4-bookworm AS base
RUN install-php-extensions pdo_sqlite gd intl zip bcmath pcntl opcache
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-foodit.ini
WORKDIR /app

# 3) Frontend: Vite necesita PHP para generar las rutas tipadas (Wayfinder)
FROM base AS assets
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm
COPY --from=vendor /app /app
# Datos públicos de Reverb para el navegador: se fijan al compilar.
ARG VITE_APP_NAME=Foodit
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ENV VITE_APP_NAME=$VITE_APP_NAME VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME
RUN npm ci --no-audit --no-fund \
    && APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build

# 4) Imagen final
FROM base
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build
COPY docker/entrypoint.sh /usr/local/bin/foodit-entrypoint
RUN chmod +x /usr/local/bin/foodit-entrypoint && rm -f /app/public/hot

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/app/storage/app/database.sqlite \
    DB_JOURNAL_MODE=wal \
    DB_BUSY_TIMEOUT=5000

EXPOSE 80
ENTRYPOINT ["foodit-entrypoint"]
CMD ["frankenphp", "php-server", "--root", "/app/public", "--listen", ":80"]
