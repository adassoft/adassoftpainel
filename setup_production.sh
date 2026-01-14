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
APP_URL=https://adassoft.com
APP_KEY=base64:66+M2DXXAmZeBeS7GVurXIUa1dBgx2bNtizt0gqBClA=

DB_CONNECTION=mysql
DB_HOST=evolu_paineladassoft-db
DB_PORT=3306
DB_DATABASE=evolu
DB_USERNAME=mariadb
DB_PASSWORD=b4a01a2c826aa2a9078e

ASSET_URL=https://adassoft.com

# Drivers
BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database
SESSION_DRIVER=file
SESSION_LIFETIME=120
EOF

# --- 2. CORRIGIR LIMITES DE UPLOAD (128MB) ---
echo "📦 Ajustando limites de upload (128MB)..."

# Ajustar Nginx (Limite de 128M)
if [ -d "/etc/nginx/conf.d" ]; then
    echo "client_max_body_size 128M;" > /etc/nginx/conf.d/upload_final.conf
    # Try multiple reload methods
    nginx -s reload 2>/dev/null || /etc/init.d/nginx reload 2>/dev/null || true
    echo "  ✅ Nginx atualizado para 128M."
fi

# Ajustar PHP (Criar arquivo ini direto que o PHP lê automaticamente)
# Isso é mais falível que tentar editar o php.ini principal
DIR_CONF="/usr/local/etc/php/conf.d"
[ ! -d "$DIR_CONF" ] && DIR_CONF="/etc/php/8.2/cli/conf.d"
[ ! -d "$DIR_CONF" ] && DIR_CONF="/etc/php/8.3/cli/conf.d"

if [ -d "$DIR_CONF" ]; then
    echo "upload_max_filesize = 128M" > "$DIR_CONF/99-custom-limits.ini"
    echo "post_max_size = 128M" >> "$DIR_CONF/99-custom-limits.ini"
    echo "memory_limit = 512M" >> "$DIR_CONF/99-custom-limits.ini"
    echo "max_execution_time = 300" >> "$DIR_CONF/99-custom-limits.ini"
    echo "  ✅ PHP config injetada em $DIR_CONF"
else
    # Fallback: Tenta escrever onde der
    echo "upload_max_filesize = 128M" > /app/.user.ini
    echo "post_max_size = 128M" >> /app/.user.ini
fi

# Reload PHP Process
pkill -USR2 php-fpm 2>/dev/null || killall -USR2 php-fpm 2>/dev/null || true

# --- 3. BANCO DE DADOS ---
echo "🗄️  Rodando Migrações..."
php artisan migrate --force

# --- 4. ASSETS E CACHE ---
echo "🎨 Publicando Assets e Limpando Cache..."
php artisan livewire:publish --assets
php artisan filament:assets
php artisan optimize:clear
php artisan icon:cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Forçar limpeza final para evitar quebra de layout (Filament Assets)
php artisan optimize:clear

echo "✅ CONFIGURAÇÃO CONCLUÍDA!"
echo "Se o erro de upload persistir, certifique-se de adicionar 'bash setup_production.sh' no Build Command do Easypanel."
