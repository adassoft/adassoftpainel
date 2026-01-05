# Configuração de E-mail

Para que o envio de e-mail funcione no ambiente local ou de produção, é necessário configurar as variáveis de ambiente no arquivo `.env`.

Atualmente, o sistema está configurado como:
`MAIL_MAILER=log`

Isso faz com que os e-mails sejam gravados em `storage/logs/laravel.log` em vez de serem enviados.

## Para usar SMTP (Gmail, Outlook, Mailtrap, etc)

Edite o arquivo `.env` e altere/adicione:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io (ou smtp.gmail.com)
MAIL_PORT=2525 (ou 587)
MAIL_USERNAME=seu_usuario
MAIL_PASSWORD=sua_senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="seu@email.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Se estiver usando o EasyPanel, certifique-se de que essas variáveis de ambiente estejam configuradas nas configurações do Serviços do Aplicativo.
