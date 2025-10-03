# ---------- Fase 1: build dos assets ----------
FROM node:16-bullseye AS assets
WORKDIR /app

COPY package*.json ./

# não travar por peer deps e não rodar scripts (tem postinstall com "php artisan")
RUN npm config set legacy-peer-deps true \
 && npm config set fund false \
 && npm config set audit false \
 && npm install --legacy-peer-deps --ignore-scripts

COPY . .
# Compila tentando os scripts mais comuns
RUN npm run prod || npm run production || npm run build

# ---------- Fase 2: runtime PHP ----------
FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
 && docker-php-ext-install pdo_mysql mbstring bcmath xml gd zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
 && (cp -n .env.example .env || true) \
 && php artisan key:generate --force \
 && php artisan storage:link || true \
 && php artisan npm:publish || true

COPY --from=assets /app/public /var/www/html/public

ENV PORT=8080
EXPOSE 8080
CMD php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=${PORT}
