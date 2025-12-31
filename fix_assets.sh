#!/bin/bash

echo "=== Corrigindo Assets e URL ==="

# Pergunta a URL correta
read -p "Digite a URL completa do seu site (ex: https://express.adassoft.com): " APP_URL

# Remove barra final se tiver
APP_URL=${APP_URL%/}

echo "Definindo APP_URL para: $APP_URL"
sed -i "s|^APP_URL=.*|APP_URL=$APP_URL|" .env

# Força HTTPS no .env para evitar mixed content
if grep -q "ASSET_URL=" .env; then
    sed -i "s|^ASSET_URL=.*|ASSET_URL=$APP_URL|" .env
else
    echo "ASSET_URL=$APP_URL" >> .env
fi

echo "Limpando Caches..."
php artisan optimize:clear

echo "Publicando Assets do Filament..."
php artisan filament:assets --force

echo "Otimizando..."
php artisan optimize
php artisan view:cache

echo "=== SUCESSO ==="
echo "Tente acessar o site novamente (use Ctrl+F5 no navegador)."
