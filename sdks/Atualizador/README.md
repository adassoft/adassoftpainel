# Atualizador do Sistema

Este é o projeto do aplicativo de atualização (Updater).

## Como Compilar
1. Abra o arquivo `Atualizador.dpr` no Delphi.
2. Certifique-se de que os caminhos para as units do Shield (`..\Projeto_delphi\Registro\...`) estão corretos/acessíveis.
3. Compile o projeto (Release).

## Instalação
1. Copie o executável gerado (`Atualizador.exe`) para a pasta da sua aplicação principal (onde fica o `ProjetoTest.exe`).
2. O sistema principal irá chamar este executável automaticamente quando houver uma atualização.

## Funcionamento
- O atualizador verifica novamente a API para obter a URL de download segura.
- Realiza backup (simulado) e extração do ZIP.
