# Release Notes - 2026-01-01 15:20

- Integração Google Shopping:
    - Adicionados campos GTIN, Categoria Google e Marca no cadastro de Softwares.
    - Criada rota de Feed XML Automático: `/feeds/google.xml`.
    - Lógica de fallback inteligente para aprovação de produtos digitais (SaaS).

Instruções para atualização no servidor:
1. Execute `git pull`
2. Execute `php artisan migrate` (ESSENCIAL para criar as novas colunas no banco)
