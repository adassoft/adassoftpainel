# Release Notes - 2026-01-02 03:50

- Correções de Banco de Dados:
    - Adicionada migration de segurança para garantir a existência da coluna `icone_path`, resolvendo erro 500 na aprovação de revendas.
- Melhorias de UX:
    - Alterados os campos de seleção de cor na tela de White Label para usar o seletor nativo do navegador.
    - Substituído texto "Shield System" por "Adassoft System" nas telas de aprovação.

Instruções para atualização no servidor:
1. Execute `git pull`
2. **Essencial:** Execute `php artisan migrate --force` para aplicar a correção do banco de dados.
