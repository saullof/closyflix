# ---------- Fase 1: build dos assets ----------
FROM node:18-alpine AS assets
WORKDIR /app
COPY package*.json ./
RUN npm ci || npm install
COPY . .
# Ajuste conforme seu projeto: Mix usa "prod", Vite usa "build"
RUN npm run prod || npm run build

# ---------- Fase 2: runtime PHP ----------
FROM php:8.2-cli

# Dependências do Laravel + extensões PHP
RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
 && docker-php-ext-install pdo_mysql mbstring bcmath xml gd zip \
 && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Código
COPY . .

# Dependências PHP e preparo do app
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
 && (cp -n .env.example .env || true) \
 && php artisan key:generate --force \
 && php artisan storage:link || true

# Copia os assets compilados
COPY --from=assets /app/public /var/www/html/public

# Porta e start
ENV PORT=8080
EXPOSE 8080
CMD php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=${PORT}
