#!/bin/bash

# ==========================================
# SCRIPT DE CONFIGURAÇÃO DE PRODUÇÃO (EASYPANEL)
# ==========================================
# Este script resolve problemas comuns de deploy:
# 1. Configura .env
# 2. Corrige erro 413 (Upload Limits)
# 3. Corrige Assets (Livewire/Filament)
# 4. Roda Migrations
# ==========================================

echo "🚀 Iniciando Configuração de Produção..."

# --- 1. CONFIGURAR .ENV (Se não existir ou para forçar) ---
echo "🔧 Configurando .env..."
cat <<EOF > .env
APP_NAME=AdasSoft
APP_ENV=production
APP_DEBUG=false
APP_URL=https://express.adassoft.com
APP_KEY=base64:66+M2DXXAmZeBeS7GVurXIUa1dBgx2bNtizt0gqBClA=

DB_CONNECTION=mysql
DB_HOST=evolu_paineladassoft-db
DB_PORT=3306
DB_DATABASE=evolu
DB_USERNAME=mariadb
DB_PASSWORD=b4a01a2c826aa2a9078e

ASSET_URL=https://express.adassoft.com

# Drivers
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
EOF

# --- 2. CORRIGIR LIMITES DE UPLOAD (Erro 413) ---
echo "📦 Ajustando limites de upload (100MB)..."

# Ajustar Nginx (Limite de 500MB)
# Ajustar Nginx (Limite de 512MB) - Método Global (Mais seguro)
if [ -d "/etc/nginx/conf.d" ]; then
    echo "client_max_body_size 512M;" > /etc/nginx/conf.d/upload_limit.conf
    nginx -t && nginx -s reload
    echo "  ✅ Nginx atualizado (conf.d) para 512MB."
else
    # Fallback para o método antigo (sed) se conf.d não existir
    NGINX_SITE="/etc/nginx/sites-enabled/default"
    if [ -f "$NGINX_SITE" ]; then
        sed -i '/client_max_body_size/d' "$NGINX_SITE"
        sed -i '/server_name _;/a \    client_max_body_size 512M;' "$NGINX_SITE"
        nginx -t && nginx -s reload
        echo "  ✅ Nginx atualizado (sed) para 512MB."
    fi
fi

# Ajustar PHP (php.ini)
PHP_INI=$(php --ini | grep "Loaded Configuration File" | awk '{print $4}')

# Fallback se não encontrar via comando
if [ -z "$PHP_INI" ]; then 
    # Tenta adivinhar caminhos comuns
    if [ -f "/etc/php/8.2/fpm/php.ini" ]; then PHP_INI="/etc/php/8.2/fpm/php.ini"; fi
    if [ -f "/etc/php/8.1/fpm/php.ini" ]; then PHP_INI="/etc/php/8.1/fpm/php.ini"; fi
fi

if [ -f "$PHP_INI" ]; then
    echo "  -> PHP config found at $PHP_INI"
    # Lógica segura: Substitui se existe, Adiciona se não existe
    grep -q "upload_max_filesize" "$PHP_INI" && sed -i 's/upload_max_filesize.*/upload_max_filesize = 512M/' "$PHP_INI" || echo "upload_max_filesize = 512M" >> "$PHP_INI"
    grep -q "post_max_size" "$PHP_INI" && sed -i 's/post_max_size.*/post_max_size = 512M/' "$PHP_INI" || echo "post_max_size = 512M" >> "$PHP_INI"
    
    # Reiniciar processos PHP
    pkill php-fpm || true
else
    echo "  -> PHP config not found. Creating custom config..."
    mkdir -p /etc/php/8.2/fpm/conf.d/
    echo "upload_max_filesize = 512M" > /etc/php/8.2/fpm/conf.d/99-custom.ini
    echo "post_max_size = 512M" >> /etc/php/8.2/fpm/conf.d/99-custom.ini
    pkill php-fpm || true
fi

# --- 3. BANCO DE DADOS ---
echo "🗄️  Rodando Migrações..."
php artisan migrate --force

# --- 4. ASSETS E CACHE ---
echo "🎨 Publicando Assets e Limpando Cache..."
php artisan livewire:publish --assets
php artisan filament:assets
php artisan config:clear
php artisan view:clear
php artisan optimize

echo "✅ CONFIGURAÇÃO CONCLUÍDA!"
echo "Se o erro de upload persistir, reinicie o container pelo painel (Force Rebuild)."
