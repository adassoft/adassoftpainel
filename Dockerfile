FROM serversideup/php:8.2-fpm-nginx

# Instalar Node.js e NPM (necessário para buildar assets do Filament/Vite)
RUN apt-get update \
    && apt-get install -y nodejs npm \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Copiar arquivos do projeto
COPY --chown=www-data:www-data . /var/www/html

# Instalar dependências do Composer
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Instalar dependências do NPM e buildar assets
RUN npm install && npm run build

# Permissões extras para storage e cache
RUN chmod -R 775 storage bootstrap/cache

# Copiar script de deploy personalizado e setar como entrypoint hook
COPY deploy.sh /etc/entrypoint.d/99-deploy.sh
RUN chmod +x /etc/entrypoint.d/99-deploy.sh
