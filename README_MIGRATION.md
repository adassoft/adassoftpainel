# Guia de Migração e Implantação - Laravel 11

## Status do Projeto
Este projeto Laravel substitui o antigo backend PHP procedural. Ele contém:
1. **Painel Administrativo (FilamentPHP)**: Gestão de Usuários, Empresas, Softwares e Licenças.
2. **API de Validação**: Endpoint `/api/validacao` compatível com os clientes Desktop existentes.
3. **Webhook Asaas**: Endpoint `/api/webhooks/asaas` para processamento de pagamentos.

## Como Rodar em Produção (XAMPP)

Para que o sistema responda na URL raiz ou substitua o antigo sem usar `artisan serve`, você deve configurar o Apache.

### Opção 1: Virtual Host (Recomendado)
Edite `C:\xampp\apache\conf\extra\httpd-vhosts.conf` e adicione:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/adassoft/public"
    ServerName adassoft.local
    # Se quiser usar o domínio real, troque para:
    # ServerName adassoft.com.br
    
    <Directory "C:/xampp/htdocs/adassoft/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Adicione `127.0.0.1 adassoft.local` no seu arquivo `C:\Windows\System32\drivers\etc\hosts`.

### Opção 2: Acesso via Subdiretório (Atual)
Se continuar acessando via `http://localhost/adassoft`:
1. As requisições devem ir para `http://localhost/adassoft/public/`.
2. Para ocultar o `/public`, crie um arquivo `.htaccess` na raiz `C:/xampp/htdocs/adassoft` com:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

## Compatibilidade com API Antiga
Os softwares legados acessam `http://seudominio.com/api_validacao.php`.
Para redirecionar isso para a nova API Laravel:

No `.htaccess` da raiz do domínio (onde o `api_validacao.php` ficava):

```apache
RewriteEngine On
RewriteRule ^api_validacao\.php$ /api/validacao [L,QSA]
```
(Ajuste o caminho destino conforme sua instalação Laravel)

## Credenciais
- **Admin Panel**: `/admin`
- **Usuário**: `admin@adassoft.com`
- **Senha**: `password`
