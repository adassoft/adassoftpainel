#!/bin/bash

# Este script roda automaticamente na inicialização do container (graças ao /etc/entrypoint.d/)

echo "🚀 Iniciando deploy..."

# Link do storage (se não existir)
php artisan storage:link || true

# Limpeza de cache
php artisan optimize:clear

# Cache de configuração/rotas/views para produção
php artisan optimize

# Rodar migrações (força schema mysql)
# O --force é necessário em produção
echo "📦 Rodando migrações..."
php artisan migrate --force

echo "✅ Deploy concluído!"
