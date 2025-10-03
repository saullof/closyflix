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

# PATCH SCSS: garante que os $gray-*-alt existam E fiquem antes de qualquer uso
RUN set -eux; \
  FILE="resources/sass/_variables-dark.scss"; \
  if [ -f "$FILE" ]; then \
    NEED=0; \
    for n in 100 200 300 400 500 600 700 800 900; do \
      grep -q "\$gray-$n-alt" "$FILE" || NEED=1; \
    done; \
    if [ "$NEED" = "1" ]; then \
      TMP="$(mktemp)"; \
      for n in 100 200 300 400 500 600 700 800 900; do \
        echo "\$gray-$n-alt: \$gray-$n !default;" >> "$TMP"; \
      done; \
      cat "$FILE" >> "$TMP"; \
      mv "$TMP" "$FILE"; \
    fi; \
  fi

# Compila tentando os scripts mais comuns (e não falha se não existir)
RUN npm run prod || npm run production || npm run build || echo "Sem script de build; pulando."

# ---------- Fase 2: runtime PHP ----------
FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
 && docker-php-ext-install pdo_mysql mbstring bcmath xml gd zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
COPY . .

# Dependências PHP (ok se .env.example não existir)
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
 && (cp -n .env.example .env || true) \
 && php artisan key:generate --force || true \
 && php artisan storage:link || true

# Pastas de cache do Laravel + permissões (evita "valid cache path")
RUN set -eux; \
    mkdir -p storage/framework/{cache,data,sessions,testing,views} bootstrap/cache storage/logs; \
    chown -R www-data:www-data storage bootstrap/cache || true; \
    chmod -R 775 storage bootstrap/cache; \
    php artisan optimize:clear || true

# Copia os assets já buildados
COPY --from=assets /app/public /var/www/html/public

# Onde o Blade compila as views e porta de serviço
ENV VIEW_COMPILED_PATH=/var/www/html/storage/framework/views \
    PORT=8080

EXPOSE 8080

# CMD em JSON – cria pastas, corrige permissão e limpa caches em runtime
CMD ["sh","-lc", "\
  mkdir -p storage/framework/{cache,views,sessions,testing} bootstrap/cache storage/logs && \
  chmod -R 775 storage bootstrap/cache && \
  rm -f bootstrap/cache/*.php || true && \
  php artisan config:clear || true && \
  php artisan route:clear || true && \
  php artisan view:clear  || true && \
  php artisan migrate --force || true && \
  php artisan serve --host=0.0.0.0 --port=${PORT:-8080} \
"]

# CMD em JSON para tratar sinais corretamente
CMD ["sh","-lc","php artisan migrate --force || true; php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"]
