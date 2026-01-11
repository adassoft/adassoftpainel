PROJETO FIREMONKEY (FMX) - SDK SHIELD
=========================================

Este projeto foi gerado para demonstrar o uso do SDK Shield em aplicações Multiplataforma (Windows, Android, iOS, macOS).

ESTRUTURA:
----------
- ProjetoFMX.dpr: Arquivo principal do projeto Delphi.
- uMain.pas / uMain.fmx: Formulário principal (Exemplo de Verificação de Licença).
- Registro\: Pasta contendo as unidades do SDK (Shield.Core, Shield.API, etc.).
  - Shield.Security.pas: Foi ADAPTADO para suportar Mobile e Windows (Cross-platform).

COMO USAR:
----------
1. Abra o arquivo 'ProjetoFMX.dpr' no Delphi.
2. Abra a unit 'uMain.pas' e vá no evento 'FormCreate'.
3. Configure sua API KEY e URL da API do Shield.
4. Compile e execute (F9).

OBSERVAÇÃO SOBRE FORMULÁRIOS ANTIGOS (VCL):
-------------------------------------------
A pasta 'Registro\Views' contém formulários do projeto antigo (VCL) (.dfm).
Eles NÃO funcionam diretament no FireMonkey (.fmx).
Você deve recriar as telas de "Cadastro", "Renovação", etc., usando componentes FMX (TEdit, TLabel, TButton) se desejar usá-las, copiando apenas a lógica (código Pascal) dos arquivos .pas antigos.

O núcleo da lógica (Shield.Core.pas) porém, funciona 100% igual.
