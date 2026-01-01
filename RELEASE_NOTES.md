# Release Notes - 2026-01-01 07:44

- Correção CRÍTICA: Ajuste nos tipos das colunas de chave estrangeira (user_id) de Integer para UnsignedBigInteger em todas as novas tabelas (suggestions, tickets, votes, messages) para corrigir o erro "Foreign key constraint is incorrectly formed".

Instruções para correção no servidor:
1. Execute `git pull`
2. Execute `php artisan migrate`
