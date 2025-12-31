# Guia de Deploy e Solução de Problemas - Easypanel

Este documento explica como configurar o projeto para rodar perfeitamente no Easypanel (usando a build padrão Nixpacks), corrigindo problemas de upload, banco de dados e assets.

## 🚀 Script de Configuração Automática

Para configurar tudo de uma vez (ideal para primeira instalação ou correções), utilizamos o script `setup_production.sh`.

### Como rodar:

1. Acesse o **Easypanel**.
2. Vá no seu Serviço (App) > Aba **Console**.
3. Digite o seguinte comando e aperte Enter:

```bash
cd /code && bash setup_production.sh
```

### O que este script faz?
1. **Cria o arquivo `.env`**: Preenche com as credenciais do banco e URL corretas.
2. **Corrige Erro 413 (Upload)**: Aumenta o limite do Nginx e PHP para 100MB.
3. **Corrige Assets**: Publica arquivos do Livewire e Filament para que carreguem corretamente.
4. **Banco de Dados**: Roda `php artisan migrate --force`.
5. **Limpeza**: Limpa e recria os caches do Laravel.

---

## 🛠️ Soluções Manuais (Caso precise)

Se preferir fazer passo a passo ou o script falhar:

### 1. Erro "413 Payload Too Large" (Falha no Upload)

Ocorre quando o arquivo enviado é maior que o permitido. O ajuste precisa ser feito em **dois lugares**:

**A) Ajuste do PHP (Interface Visual):**
1. No Easypanel, vá na aba **Settings** (ou Geral) do seu App.
2. Procure a seção **PHP**.
3. Em **Tamanho Máximo de Upload**, coloque `500M` (ou o quanto precisar).
4. Em **Tempo Máximo de Execução**, aumente para `300` (para uploads lentos não caírem).
5. Clique em **Salvar**.

**B) Ajuste do Nginx (Via Script - OBRIGATÓRIO):**
O Nginx bloqueia uploads grandes antes mesmo de chegarem no PHP. A interface do Easypanel *não* ajusta isso, então precisamos rodar o comando:

```bash
# Rodar no Console
bash setup_production.sh
```

Ou manualmente:
```bash
sed -i '/server_name _;/a \  client_max_body_size 500M;' /etc/nginx/sites-enabled/default && nginx -s reload
```

### 2. Assets 404 (Livewire/Filament não carregam)
O servidor não encontra os arquivos JS/CSS virtuais.
**Solução:** Publicar os arquivos fisicamente.

```bash
php artisan livewire:publish --assets
php artisan filament:assets
```

### 3. Criar Usuário Admin
Se precisar criar um novo usuário de acesso:

```bash
php artisan make:filament-user
```

---

## ⚠️ Dica de Ouro: "Deploy que funciona de primeira"

Para garantir que novos deploys funcionem sem intervenção manual:
1. Vá nas configurações do App no Easypanel.
2. Procure por **Deploy Command** ou **Build Command**.
3. Adicione o comando de execução do script: `bash setup_production.sh`
   * *Nota: Isso depende de como o Easypanel processa o build. Se não funcionar no build, mantenha o hábito de rodar o script manualmente no Console após updates grandes.*
