# Release Notes - 2026-01-01 16:20

- URLs Amigáveis (Slugs):
    - Implementada a possibilidade de usar nomes nas URLs dos produtos (ex: `/produto/super-carne`).
    - Campo "URL Amigável" adicionado ao cadastro do software.
    - Compatibilidade mantida com URLs baseadas em ID.

Instruções para atualização no servidor:
1. Execute `git pull`
2. Execute `php artisan migrate`
