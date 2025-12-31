#!/bin/bash

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

clear
echo -e "${BLUE}=================================================${NC}"
echo -e "${BLUE}   INSTALADOR INTERATIVO ADASSOFT - CLI VERSION  ${NC}"
echo -e "${BLUE}=================================================${NC}"

# 1. Configurar .env
if [ ! -f .env ]; then
    echo -e "${YELLOW}Criando arquivo .env...${NC}"
    cp .env.example .env
else
    echo -e "${GREEN}Arquivo .env encontrado.${NC}"
fi

# Função para atualizar .env
update_env() {
    key=$1
    value=$2
    if grep -q "^${key}=" .env; then
        # Usa separador | para evitar problemas com / em URLs
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

echo -e "\n${YELLOW}--- Configuração do Ambiente ---${NC}"

# Pergunta URL
read -p "Digite a URL do sistema (ex: https://site.com): " APP_URL
update_env "APP_URL" "$APP_URL"
update_env "APP_ENV" "production"
update_env "APP_DEBUG" "false"

echo -e "\n${YELLOW}--- Configuração do Banco de Dados ---${NC}"

# Pergunta DB
read -p "Host do Banco (padrão: 127.0.0.1): " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}
update_env "DB_HOST" "$DB_HOST"

read -p "Porta do Banco (padrão: 3306): " DB_PORT
DB_PORT=${DB_PORT:-3306}
update_env "DB_PORT" "$DB_PORT"

read -p "Nome do Banco de Dados: " DB_DATABASE
update_env "DB_DATABASE" "$DB_DATABASE"

read -p "Usuário do Banco: " DB_USERNAME
update_env "DB_USERNAME" "$DB_USERNAME"

read -s -p "Senha do Banco: " DB_PASSWORD
echo ""
update_env "DB_PASSWORD" "$DB_PASSWORD"

echo -e "\n${GREEN}Configurações salvas no .env!${NC}"

# 2. Key Generate
echo -e "\n[Executando] Gerando Chave de Criptografia..."
php artisan key:generate --force

# 3. Limpar Cache antes de migrar
php artisan config:clear

# 4. Migrações
echo -e "\n[Executando] Migrando Banco de Dados..."
if php artisan migrate --force; then
    echo -e "${GREEN}Banco de dados atualizado com sucesso!${NC}"
else
    echo -e "${RED}Erro ao conectar no banco. Verifique as credenciais acima.${NC}"
    exit 1
fi

# 5. Storage
echo -e "\n[Executando] Configurando Arquivos..."
rm -rf public/storage
php artisan storage:link
rm -f storage/logs/*.log

# 6. Travar Instalador Web
touch storage/installed

# 7. Criar Usuário
echo -e "\n${YELLOW}--- Criação de Usuário Admin ---${NC}"
read -p "Deseja criar um usuário administrador agora? (S/n): " CRIAR_USER
if [[ "$CRIAR_USER" =~ ^[Ss] || -z "$CRIAR_USER" ]]; then
    php artisan make:filament-user
fi

# 8. Permissões Finais
echo -e "\n[Executando] Ajustando Permissões Finais..."
WEBUSER="www"
if id "www-data" &>/dev/null; then
    if ps aux | grep -q "[n]ginx.*www-data"; then
       WEBUSER="www-data"
    fi
fi

chown -R $WEBUSER:$WEBUSER .
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;
chmod -R 775 storage bootstrap/cache
mkdir -p storage/framework/{sessions,views,cache}
chown -R $WEBUSER:$WEBUSER storage bootstrap/cache

chmod +x install.sh

echo -e "\n${BLUE}=================================================${NC}"
echo -e "${GREEN}   INSTALAÇÃO CONCLUÍDA COM SUCESSO! 🚀          ${NC}"
echo -e "${BLUE}=================================================${NC}"
echo "Acesse: $APP_URL"
