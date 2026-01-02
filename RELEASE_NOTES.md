# Release Notes - 2026-01-02 02:50

- Correção Definitiva de Fontes e Ícones (Revendas):
    - Alterado o carregamento de CSS e JS para usar caminhos relativos (`/css/...`) em vez de absolutos (`https://url/...`).
    - Isso elimina problemas de CORS ao acessar o sistema via domínios de revenda, garantindo que os ícones carreguem corretamente.

Instruções para atualização no servidor:
1. Execute `git pull`
