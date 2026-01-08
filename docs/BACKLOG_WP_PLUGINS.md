# Backlog: Ecossistema de Plugins WordPress (Produtos Próprios)

**Data de Registro:** 05/01/2026
**Status:** Ideia / Análise Futura

## O Conceito
Desenvolver plugins premium para WordPress focados em funcionalidades que já dominamos (ex: Gestão de Downloads, Controle de Licenças, Integrações) e vendê-los através do ecossistema AdasSoft.

## O Grande Diferencial (Sinergia)
Utilizar a plataforma **AdasSoft/Shield** como o **Servidor de Licenciamento (DRM)**.
1.  O cliente compra o plugin na loja AdasSoft.
2.  O Shield gera uma chave de licença (API Key).
3.  O plugin instalado no WordPress do cliente conecta na API do Shield para validar a licença e liberar atualizações automáticas.

## Ideia de Produto #1: "AdasSoft Download Manager for WP"
Uma versão "plugin" da funcionalidade de downloads segura que criamos.
- **Público Alvo:** Criadores de conteúdo, desenvolvedores, vendedores de info-produtos.
- **Funcionalidades:**
    - Proteção de arquivos (link direto bloqueado).
    - Contador de downloads.
    - Controle de versão.
    - Integração com gateways (venda de arquivos).
- **Potencial de Receita:** Assinatura anual (Benchmarking: $39 - $99 / ano).

## Viabilidade Técnica
- **Stack:** PHP, JS (jQuery/Vanilla), HTML/CSS (padrão WP).
- **Conhecimento Atual:** Totalmente compatível com a expertise da equipe em PHP/Laravel.
- **Custo:** Baixo (apenas tempo de desenvolvimento).

## Próximos Passos (Quando aprovado)
1.  Estudar "Boilerplate" de plugins WP modernos.
2.  Criar endpoint na API do Shield para `validate-license`.
3.  Desenvolver MVP do gerenciador de downloads.
