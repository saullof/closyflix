# ---------- Fase 1: build dos assets ----------
FROM node:16-bullseye AS assets
WORKDIR /app

# Instala dependências sem travar em peer deps
COPY package*.json ./
RUN npm config set legacy-peer-deps true \
 && npm config set fund false \
 && npm config set audit false \
 && npm install --legacy-peer-deps --ignore-scripts || npm install --ignore-scripts

# Precisamos do compilador SASS e do cookieconsent
RUN npm install -D sass cookieconsent@3

# Traz o código (scss, views, etc.)
COPY . .

# Garante que os $gray-*-alt existam no _variables-dark.scss (erro que você tinha)
RUN set -eux; \
  f="resources/sass/_variables-dark.scss"; \
  if [ -f "$f" ]; then \
    need=0; for n in 100 200 300 400 500 600 700 800 900; do grep -q "\$gray-$n-alt" "$f" || need=1; done; \
    if [ "$need" = "1" ]; then \
      tmp="$(mktemp)"; \
      for n in 100 200 300 400 500 600 700 800 900; do echo "\$gray-$n-alt: \$gray-$n !default;" >> "$tmp"; done; \
      cat "$f" >> "$tmp"; mv "$tmp" "$f"; \
    fi; \
  fi

# Compila os SCSS sem depender de scripts NPM do projeto
RUN mkdir -p public/css/theme public/libs/cookieconsent/build && \
    if [ -f resources/sass/bootstrap.dark.scss ]; then \
      npx sass --no-source-map resources/sass/bootstrap.dark.scss public/css/theme/bootstrap.dark.css; \
    else \
      : > public/css/theme/bootstrap.dark.css; \
    fi && \
    if [ -f resources/sass/bootstrap.scss ]; then \
      npx sass --no-source-map resources/sass/bootstrap.scss public/css/theme/bootstrap.css || true; \
    fi && \
    cp node_modules/cookieconsent/build/cookieconsent.min.css public/libs/cookieconsent/build/ && \
    cp node_modules/cookieconsent/build/cookieconsent.min.js  public/libs/cookieconsent/build/

# (Opcional) se seu projeto tiver Mix/Vite e scripts, pode tentar também:
# RUN npm run prod || npm run production || npm run build || true


# ---------- Fase 2: runtime PHP ----------
FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libcurl4-openssl-dev \
 && docker-php-ext-install pdo_mysql mbstring bcmath xml gd zip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# Patch: evita realpath() nulo no view compiled
RUN sed -i "s#realpath(storage_path('framework/views'))#storage_path('framework/views')#g" config/view.php || true

# Dependências PHP
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
 && (cp -n .env.example .env || true) \
 && php artisan key:generate --force || true

# Pastas de storage/cache + permissões
RUN set -eux; \
    mkdir -p storage/framework/{cache,views,sessions,testing} storage/logs bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache public || true; \
    chmod -R 775 storage bootstrap/cache public; \
    php artisan optimize:clear || true

# Copia os assets já buildados do estágio 1
COPY --from=assets /app/public /var/www/html/public

# Onde o Blade compila as views + porta
ENV VIEW_COMPILED_PATH=/var/www/html/storage/framework/views \
    PORT=8080 \
    LOG_CHANNEL=single \
    LOG_LEVEL=debug \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file

EXPOSE 8080

# Start: garante diretórios/perm., limpa caches e sobe o server
CMD ["sh","-lc", "\
  mkdir -p storage/framework/{cache,views,sessions,testing} storage/logs bootstrap/cache && \
  chown -R www-data:www-data storage bootstrap/cache public || true && \
  chmod -R 775 storage bootstrap/cache public && \
  rm -f bootstrap/cache/*.php || true && \
  php artisan optimize:clear || true && \
  php artisan migrate --force || true && \
  php artisan serve --host=0.0.0.0 --port=${PORT:-8080} \
"]
