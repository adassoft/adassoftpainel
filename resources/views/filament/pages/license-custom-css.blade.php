<style>
    /* Forçar layout vertical nas ações dentro do Grid de Cards de Licenças */
    .fi-ta-content-grid .fi-ta-actions {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 0.5rem !important;
    }

    /* Ajustar botões e links para ocuparem 100% da largura e alinhar à esquerda */
    .fi-ta-content-grid .fi-ta-actions .fi-btn,
    .fi-ta-content-grid .fi-ta-actions .fi-link {
        width: 100% !important;
        justify-content: flex-start !important; /* Alinha ícone e texto à esquerda */
        text-align: left !important;
        margin-bottom: 0.25rem;
    }

    /* Remove padding lateral excessivo dos links para alinhar melhor visualmente */
    .fi-ta-content-grid .fi-ta-actions .fi-link {
        padding-left: 0.5rem !important; 
    }

    /* Remover margens laterais que o layout horizontal poderia ter */
    .fi-ta-content-grid .fi-ta-actions>* {
        margin-left: 0 !important;
        margin-right: 0 !important;
    }
</style>