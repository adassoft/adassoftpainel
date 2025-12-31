<style>
    /* Forçar Grid na Tabela de Pedidos */
    .fi-ta-content-grid {
        display: grid !important;
        gap: 1.5rem !important;
    }

    .fi-ta-content-grid {
        grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
    }

    @media (min-width: 768px) {
        .fi-ta-content-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (min-width: 1024px) {
        .fi-ta-content-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        }
    }

    .fi-ta-record {
        height: 100% !important;
    }

    /* =========================================
       MENU LATERAL (DARK / SHARP THEME)
       ========================================= */

    /* 1. Sidebar Container */
    aside.fi-sidebar {
        background-color: #1e293b !important;
        /* Slate 800 */
        border-right: 1px solid #334155 !important;
    }

    aside.fi-sidebar header {
        background-color: #1e293b !important;
        border-bottom: 1px solid #334155 !important;
    }

    /* 2. ITENS DE MENU (Geral) */
    .fi-sidebar-item-label {
        font-weight: 500 !important;
        color: #94a3b8 !important;
        /* Texto Cinza */
    }

    .fi-sidebar-item-icon {
        color: #64748b !important;
        /* Ícone Cinza */
    }

    /* Remove arredondamentos GLOBADAMENTE dos botões do menu */
    .fi-sidebar-item-button,
    .fi-sidebar-item-button:hover,
    .fi-sidebar-item-active .fi-sidebar-item-button {
        border-radius: 0 !important;
    }

    /* 3. HOVER e ATIVO (Unificados na Lógica de Destaque) */

    /* Quando é Hover OU Ativo */
    .fi-sidebar-item-button:hover,
    .fi-sidebar-item-button.fi-active,
    .fi-sidebar-item-active .fi-sidebar-item-button {

        /* Fundo escuro com leve toque azulado */
        background-color: rgba(30, 41, 59, 1) !important;
        /* Base date */
        background: linear-gradient(90deg, rgba(6, 182, 212, 0.1) 0%, rgba(30, 41, 59, 0) 100%) !important;

        position: relative;
        color: white !important;
    }

    /* Texto e Ícone no Hover/Ativo */
    .fi-sidebar-item-button:hover .fi-sidebar-item-label,
    .fi-sidebar-item-button.fi-active .fi-sidebar-item-label,
    .fi-sidebar-item-active .fi-sidebar-item-label {
        color: #ffffff !important;
        font-weight: 700 !important;
    }

    .fi-sidebar-item-button:hover .fi-sidebar-item-icon,
    .fi-sidebar-item-button.fi-active .fi-sidebar-item-icon,
    .fi-sidebar-item-active .fi-sidebar-item-icon {
        color: #06b6d4 !important;
        /* Cyan 500 */
    }

    /* 4. A BARRA LATERAL (DESTQUE) - Appears on Hover AND Active */
    .fi-sidebar-item-button:hover::before,
    .fi-sidebar-item-button.fi-active::before,
    .fi-sidebar-item-active .fi-sidebar-item-button::before {
        content: "";
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        /* Largura da barra */
        background-color: #06b6d4;
        /* Cyan 500 */
        border-top-right-radius: 4px;
        /* Pontinha arredondada só na barra fica elegante */
        border-bottom-right-radius: 4px;
        z-index: 10;
        display: block !important;
    }

    /* Scrollbar Dark */
    aside.fi-sidebar nav::-webkit-scrollbar {
        width: 6px;
    }

    aside.fi-sidebar nav::-webkit-scrollbar-track {
        background: #1e293b;
    }

    aside.fi-sidebar nav::-webkit-scrollbar-thumb {
        background-color: #475569;
        border-radius: 20px;
    }

    /* Fix: Forçar 5 colunas e layout compacto vertical no widget de stats */
    @media (min-width: 1024px) {

        /* Grid de 5 colunas */
        .fi-wi-stats-overview .grid {
            grid-template-columns: repeat(5, minmax(0, 1fr)) !important;
            gap: 0.5rem !important;
        }

        /* Container do Card: Forçar pilha vertical */
        .fi-wi-stats-overview-stat {
            padding: 0.5rem 0.25rem !important;
            /* Menor padding */
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            /* Centralizar tudo */
            text-align: center !important;
            height: auto !important;
        }

        /* Forçar todos os filhos diretos a respeitarem a largura */
        .fi-wi-stats-overview-stat>div {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            display: flex !important;
            flex-direction: column !important;
            align-items: center !important;
        }

        /* 1. LABEL (Título) - Reduzir e remover quebras */
        .fi-wi-stats-overview-stat .text-sm,
        .fi-wi-stats-overview-stat span.text-sm {
            font-size: 0.75rem !important;
            line-height: 1rem !important;
            white-space: nowrap !important;
            margin-bottom: 0.25rem !important;
        }

        /* 2. VALOR (Número Grande) - Sobrepor classes do Tailwind (text-3xl, etc) */
        .fi-wi-stats-overview-stat .text-3xl,
        .fi-wi-stats-overview-stat .text-2xl,
        .fi-wi-stats-overview-stat .fi-wi-stats-overview-stat-value {
            font-size: 1.25rem !important;
            line-height: 1.5rem !important;
            font-weight: 800 !important;
            margin-bottom: 0.25rem !important;
        }

        /* 3. DESCRIÇÃO/ÍCONE (Texto inferior) */
        .fi-wi-stats-overview-stat .text-xs,
        .fi-wi-stats-overview-stat .text-gray-500 {
            font-size: 0.65rem !important;
            line-height: 0.8rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 0.25rem !important;
        }

        /* Ajustar ícones svg para não estourarem */
        .fi-wi-stats-overview-stat svg {
            width: 1rem !important;
            height: 1rem !important;
        }
    }
</style>