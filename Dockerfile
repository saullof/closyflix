# ---------- Fase 1: build dos assets ----------
# Node 16 é mais compatível com Laravel Mix v4
FROM node:16-bullseye AS assets
WORKDIR /app

# Copia apenas manifests primeiro p/ cache
COPY package*.json ./

# Ajustes de npm para não travar por peer deps
RUN npm config set legacy-peer-deps true \
 && npm config set fund false \
 && npm config set audit false

# Instala (sem mexer no seu package.json)
RUN npm install

# Copia o restante do projeto
COPY . .

# Webpack 4 + OpenSSL 3 (necessário em Node >=17)
ENV NODE_OPTIONS=--openssl-legacy-provider

# Compile seus assets (Mix usa "prod"; se for Vite, trocar para "build")
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

# Copia o código
COPY . .

# Dependências PHP e preparo do app
RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
 && (cp -n .env.example .env || true) \
 && php artisan key:generate --force \
 && php artisan storage:link || true

# Copia os assets compilados da fase Node
COPY --from=assets /app/public /var/www/html/public

# Porta e comando de start
ENV PORT=8080
EXPOSE 8080
CMD php artisan migrate --force || true && php artisan serve --host=0.0.0.0 --port=${PORT}
