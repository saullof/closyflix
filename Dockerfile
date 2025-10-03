# ---------- Fase 1: build dos assets ----------
FROM node:16-bullseye AS assets
WORKDIR /app

COPY package*.json ./
# não travar por peer deps e não rodar scripts (tem postinstall com "php artisan")
RUN npm config set legacy-peer-deps true \
 && npm config set fund false \
 && npm config set audit false \
 && npm install --legacy-peer-deps --ignore-scripts

# Traga o código agora (precisamos dos .scss para buildar)
COPY . .

# Garante que os *-alt existam (clona valores dos cinzas padrão) — segura se o arquivo não existir
RUN set -eux; \
  if [ -f resources/sass/_variables-dark.scss ]; then \
    for n in 100 200 300 400 500 600 700 800 900; do \
      grep -q "\$gray-$n-alt" resources/sass/_variables-dark.scss || \
      echo "\$gray-$n-alt: \$gray-$n !default;" >> resources/sass/_variables-dark.scss; \
    done; \
  fi

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

# Dependências PHP (ok se .env.example não existir)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
 && (cp -n .env.example .env || true) \
 && php artisan key:generate --force || true \
 && php artisan storage:link || true

# Pastas de cache do Laravel + permissões
RUN set -eux; \
    mkdir -p storage/framework/{cache,data,sessions,testing,views} bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache || true; \
    chmod -R 775 storage bootstrap/cache; \
    php artisan optimize:clear || true

# Copia os assets já buildados
COPY --from=assets /app/public /var/www/html/public

# Garante onde o Blade compila as views
ENV VIEW_COMPILED_PATH=/var/www/html/storage/framework/views \
    PORT=8080

EXPOSE 8080

# JSON args recomendado
CMD ["sh","-lc","php artisan migrate --force || true; php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
