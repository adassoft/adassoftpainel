# Release Notes - 2026-01-02 03:00

- Melhorias de Identidade Visual (White Label):
    - **Ícone Separado:** Agora é possível fazer upload de um Ícone/Símbolo quadrado separado da Logo principal.
    - **Correção de Login:** A tela de login das revendas agora exibe corretamente a logo da revenda em vez do símbolo padrão do sistema (bola branca/azul).
    - **Responsividade:** Logos retangulares agora se adaptam melhor ao cabeçalho, enquanto o ícone é usado em favicons e avatares.

Instruções para atualização no servidor:
1. Execute `git pull`
2. Execute `php artisan migrate` para criar o novo campo `icone_path`.
