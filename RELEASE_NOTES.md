# Release Notes - 2026-01-02 04:00

- Correções Críticas de Banco de Dados:
    - Alteradas colunas `logo_path`, `icone_path` e `dominios` para permitirem valores nulos (`NULL`), corrigindo erro 500 ao aprovar revendas sem logo ou configurações parciais.

Instruções para atualização no servidor:
1. Execute `git pull`
2. **Essencial:** Execute `php artisan migrate --force` para corrigir a estrutura da tabela.
