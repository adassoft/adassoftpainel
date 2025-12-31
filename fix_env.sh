#!/bin/bash

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${GREEN}=== Corretor de Ambiente de Banco de Dados ===${NC}"

if [ ! -f .env ]; then
    echo -e "${RED}Erro: Arquivo .env não encontrado!${NC}"
    exit 1
fi

# Função para atualizar .env
update_env() {
    key=$1
    value=$2
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        echo "${key}=${value}" >> .env
    fi
}

echo "Forçando configuração para MySQL..."
update_env "DB_CONNECTION" "mysql"
update_env "DB_PORT" "3306"

# Pergunta dados se ainda estiverem como padrão ou vazios
echo -e "\n${GREEN}Por favor, informe os dados do seu Banco MySQL no aaPanel:${NC}"

read -p "Database Host (127.0.0.1): " DB_HOST
DB_HOST=${DB_HOST:-127.0.0.1}
update_env "DB_HOST" "$DB_HOST"

read -p "Database Name: " DB_DATABASE
update_env "DB_DATABASE" "$DB_DATABASE"

read -p "Database Username: " DB_USERNAME
update_env "DB_USERNAME" "$DB_USERNAME"

read -s -p "Database Password: " DB_PASSWORD
echo ""
update_env "DB_PASSWORD" "$DB_PASSWORD"

echo -e "\n${GREEN}Dados salvos! Testando conexão e migrando...${NC}"

# Tenta migrar
php artisan config:clear
if php artisan migrate:fresh --force; then
    echo -e "${GREEN}SUCESSO! Banco de dados recriado no MySQL.${NC}"
    
    echo -e "\nCriando usuário Admin..."
    php artisan make:filament-user
    
    # Bloqueia instalador
    touch storage/installed
    
    echo -e "\n${GREEN}Tudo pronto! Acesse seu site.${NC}"
else
    echo -e "${RED}FALHA: Não foi possível conectar ao MySQL. Verifique a senha e o nome do banco.${NC}"
fi
