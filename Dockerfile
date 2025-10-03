# ---------- Fase 1: build dos assets ----------
FROM node:16-bullseye AS assets
WORKDIR /app

COPY package*.json ./

# não travar por peer deps e não rodar scripts (tem postinstall com "php artisan")
RUN npm config set legacy-peer-deps true \
 && npm config set fund false \
 && npm config set audit false \
 && npm install --legacy-peer-deps --ignore-scripts

# agora copiamos o código-fonte
COPY . .

# Fallbacks no TOPO do _variables-dark.scss (precisam existir antes de serem usados)
RUN if [ -f resources/sass/_variables-dark.scss ]; then \
      (printf '%s\n' \
      '// Fallbacks para cinzas *-alt' \
      '$gray-100-alt: $gray-100 !default;' \
      '$gray-200-alt: $gray-200 !default;' \
      '$gray-300-alt: $gray-300 !default;' \
      '$gray-400-alt: $gray-400 !default;' \
      '$gray-500-alt: $gray-500 !default;' \
      '$gray-600-alt: $gray-600 !default;' \
      '$gray-700-alt: $gray-700 !default;' \
      '$gray-800-alt: $gray-800 !default;' \
      '$gray-900-alt: $gray-900 !default;' \
      '' \
      ; cat resources/sass/_variables-dark.scss) \
      > /tmp/_vars-dark.scss && mv /tmp/_vars-dark.scss resources/sass/_variables-dark.scss; \
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

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
 && (cp -n .env.example .env || true) \
 && php artisan key:generate --force \
 && php artisan storage:link || true \
 && php artisan npm:publish || true

# sobrescreve a pasta public com os assets gerados no estágio de build
COPY --from=assets /app/public /var/www/html/public

ENV PORT=8080
EXPOSE 8080
CMD php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=${PORT}
