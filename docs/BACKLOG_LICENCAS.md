# Backlog: Módulo de Revenda de Licenças de Terceiros (On-Demand)

**Data de Registro:** 05/01/2026
**Status:** Standby (Baixa Prioridade devido à competitividade de mercado)

## Cenário
Venda de softwares de terceiros (ex: Winflector, MS Office) onde:
1.  **Validade:** O tempo da licença começa a contar no momento da emissão na distribuidora.
2.  **Moeda:** Custo em Dólar (USD), exigindo repasse cambial.
3.  **Volume:** Baixo volume de vendas, tornando inviável compra de lotes (estoque).

## Arquitetura Proposta (Just-in-Time)

### 1. Modelo de Venda "Sob Encomenda"
Ao contrário dos produtos SaaS (entrega imediata), estes produtos funcionariam como um serviço de intermediação.
- **Cliente:** Compra e paga. Recebe aviso de "Processando Emissão de Licença".
- **Admin:** Recebe alerta. Compra a licença na distribuidora (garantindo validade total). Insere a chave no pedido.
- **Entrega:** Sistema envia e-mail final com a chave ativa.

### 2. Precificação Dinâmica (Dólar)
Para proteger a margem contra flutuação cambial:
- **Campos no Produto:** `custo_usd` (Decimal), `margem_lucro` (%), `moeda_base` ('USD').
- **Automação:** Job diário/horário (`Schedule`) consulta API de câmbio (ex: AwesomeAPI).
- **Cálculo:** `Preço BRL = (Custo USD * Cotação) + Margem`.
- **Frontend:** Exibe o preço final em Reais, sempre atualizado.

### 3. Implementação Técnica Necessária
1.  **Tabela de Produtos:** Flag `is_manual_delivery` ou `tipo_produto = 'terceiros'`.
2.  **Pedidos:** Novo status `aguardando_emissao` e campo `serial_key`.
3.  **Checkout:** Remover promessa de "Download Imediato" para esses itens.
4.  **Admin:** Ação "Entregar Licença" no pedido, disparando notificação `LicenseDeliveredNotification`.

---
*Este documento serve como referência para implementação futura caso a viabilidade comercial destes produtos retorne.*
