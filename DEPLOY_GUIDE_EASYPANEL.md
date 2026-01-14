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
# Rodar no Console (Solução Definitiva)
echo "client_max_body_size 512M;" > /etc/nginx/conf.d/upload_limiter.conf && nginx -s reload
```

Ou simplesmente rode o script atualizado:
```bash
cd /code && git pull && bash setup_production.sh
```

### ✅ Solução Definitiva para Uploads Grandes (128MB)

Para garantir que os limites de upload (PHP e Nginx) persistam mesmo após o `Rebuild`, adicionamos o script `setup_production.sh`.

**Configuração Obrigatória no Easypanel:**
1. Vá até a aba **Settings** do seu serviço.
2. Na seção **Build**, procure por **Build Command** ou **Deploy Command**.
3. Adicione o comando:
   ```bash
   bash setup_production.sh
   ```
4. Salve e clique em **Deploy**.

Isso executará nosso script de configuração automaticamente a cada nova versão, garantindo:
- Nginx com `client_max_body_size 128M`
- PHP com `upload_max_filesize = 128M`
- Cache limpo e otimizado

---
### Solução Manual (Emergência)
Se precisar aplicar imediatamente sem redeploy:
```bash
git pull
bash setup_production.sh
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

## ⚙️ Configuração de Filas (Queue Worker) - OBRIGATÓRIO

Para que o envio de e-mails em massa e campanhas funcione, você precisa de um "trabalhador" processando os pedidos em segundo plano. Sem isso, os envios ficarão eternamente "Pending".

### Como ativar no Easypanel:

1. Vá nas configurações do seu **App**.
2. Vá na aba **Processes** (ou Services).
3. Provavelmente já existe o processo "Web". Clique em **Add Process** (ou +).
4. Configure o novo processo assim:
   - **Name**: `worker`
   - **Command**: `php artisan queue:work --tries=3 --timeout=150`
   - **Type**: `Background` (ou mantenha o padrão se não tiver opção)
5. Salve e clique em **Deploy**.

Isso fará com que o sistema processe as mensagens automaticamente.

---

## ⚠️ Dica de Ouro: "Deploy que funciona de primeira"

Para garantir que novos deploys funcionem sem intervenção manual:
1. Vá nas configurações do App no Easypanel.
2. Procure por **Deploy Command** ou **Build Command**.
3. Adicione o comando de execução do script: `bash setup_production.sh`
   * *Nota: Isso depende de como o Easypanel processa o build. Se não funcionar no build, mantenha o hábito de rodar o script manualmente no Console após updates grandes.*
