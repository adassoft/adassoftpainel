# Release Notes - 2026-01-02 02:40

- Correção de CORS e Assets:
    - Adicionado suporte a CORS (Cross-Origin Resource Sharing) no `.htaccess` para permitir que fontes e ícones carreguem corretamente em domínios de revenda.
    - Otimização de cache para arquivos estáticos.

Instruções para atualização no servidor:
1. Execute `git pull`
2. Certifique-se de que o módulo `headers` do Apache esteja ativo (`a2enmod headers`).
