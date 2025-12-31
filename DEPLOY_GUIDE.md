# Guia de Deploy - AdasSoft (Laravel Filament) no aaPanel

Este documento registra os passos essenciais e as correções de problemas comuns encontrados durante o deploy no ambiente aaPanel/Linux.

## 1. Configuração do Banco de Dados
- O Laravel 11 tenta usar SQLite por padrão se o MySQL não estiver configurado corretamente.
- **Problema Comum:** Erro `table has more than one primary key` ou `attempt to write a readonly database`.
- **Solução:** 
  1. Certifique-se de que o `.env` esteja configurado com `DB_CONNECTION=mysql`.
  2. Use o script `fix_env.sh` (se disponível) para forçar as credenciais corretas.

## 2. Configuração do Servidor Web (Nginx) - CRÍTICO
O Filament/Livewire não funciona com a configuração padrão do Nginx no aaPanel porque os assets JS são servidos via rota do Laravel, não como arquivos estáticos reais.

**Erro:** Console do navegador mostra `404 Not Found` para `livewire.min.js` ou senha visível no login.

**Solução:**
Adicione o seguinte bloco no arquivo de configuração do Nginx (`vhost` ou Config no painel), **antes** do bloco `location /`:

```nginx
# Correção Essencial para Livewire/Filament
location /livewire {
    try_files $uri $uri/ /index.php?$query_string;
}

# Garante que o index.php processa tudo
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 3. Permissões de Pasta
O erro 500 ou tela branca muitas vezes é permissão. O usuário do webserver geralmente é `www` ou `www-data`.

**Comandos de Correção:**
```bash
chown -R www:www .
chmod -R 775 storage bootstrap/cache
```

## 4. Model User e Legado
O sistema legado usa colunas `nome`, `login`, `senha`. O Filament espera `name`, `email`, `password`.

- **Arquivo `app/Models/User.php`:** Deve conter Mutators (`setNameAttribute`) e `fillable` configurados com os campos virtuais (`name`, `password`) para que o comando `make:filament-user` funcione.
- Se receber erro `Field 'nome' doesn't have a default value`, é porque o Model User no servidor está desatualizado.

## 5. Scripts Auxiliares
Mantemos na raiz scripts para automatizar tarefas repetitivas:

1.  **`install.sh`**: Deploy completo inicial (configura permissões, env, migrações).
2.  **`fix_env.sh`**: Força conexão MySQL caso o Laravel tente usar SQLite.
3.  **`fix_assets.sh`**: Corrige URLs de assets e limpa cache quando o layout quebra ou fica sem estilo.
4.  **`deploy.sh`**: Script para ambientes Docker (Easypanel).

## 6. Docker (Easypanel/Coolify)
Para evitar problemas de servidor como os acima, o projeto já possui `Dockerfile`.
- Basta conectar o repositório Git ao Easypanel.
- O `deploy.sh` interno roda as migrações automaticamente.
- Variáveis de ambiente (`APP_KEY`, `DB_HOST`, etc) devem ser configuradas no painel do Easypanel.
