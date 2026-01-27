/**
 * Cielo Analytics React Component
 * Página de analytics das transações Cielo com Grid.js
 */
import React, { useEffect, useRef } from 'react';
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { Grid } from 'gridjs';
import 'gridjs/dist/theme/mermaid.css';

// Dados mockados das transações Cielo
const mockTransactions = [
    [
        '**** 1234', // cartão
        'Crédito', // tipo do cartão
        '3x', // parcelas
        'R$ 166,67', // valor das parcelas
        'Visa', // bandeira
        '12/2028', // data de expiração
        '2026-01-27 10:30:15', // data da requisição / hora
        'R$ 500,00', // valor total
        'R$ 500,00', // subtotal
        'R$ 0,00', // juros/desconto
        'BRL', // moeda
        'Sandbox', // ambiente
        'MERCHANT123', // merchant ID
        'MERCKEY***', // merchant KEY
        '4', // codigo da resposta
        'Autorizada', // status da requisição
        'Cielo', // gateway de pagamento
        'Sim', // captura
        'Não', // recorrente
        'Não', // autenticação 3ds
        'ORD001', // orderID
        'REF001', // reference
        'TID12345', // tid
        'João Silva', // nome do portador
        'Sim' // enviar dados
    ],
    [
        '**** 5678',
        'Débito',
        '1x',
        'R$ 250,00',
        'Mastercard',
        '06/2027',
        '2026-01-27 09:15:42',
        'R$ 250,00',
        'R$ 250,00',
        'R$ 0,00',
        'BRL',
        'Produção',
        'MERCHANT123',
        'MERCKEY***',
        '4',
        'Autorizada',
        'Cielo',
        'Sim',
        'Não',
        'Sim',
        'ORD002',
        'REF002',
        'TID67890',
        'Maria Santos',
        'Sim'
    ],
    [
        '**** 9012',
        'Crédito',
        '6x',
        'R$ 83,33',
        'Elo',
        '03/2029',
        '2026-01-27 08:45:21',
        'R$ 500,00',
        'R$ 500,00',
        'R$ 15,00',
        'BRL',
        'Produção',
        'MERCHANT456',
        'MERCKEY***',
        '4',
        'Autorizada',
        'Cielo',
        'Sim',
        'Não',
        'Não',
        'ORD003',
        'REF003',
        'TID24680',
        'Pedro Costa',
        'Sim'
    ],
    [
        '**** 3456',
        'Crédito',
        '1x',
        'R$ 150,00',
        'American Express',
        '09/2026',
        '2026-01-27 14:20:33',
        'R$ 150,00',
        'R$ 150,00',
        'R$ 0,00',
        'BRL',
        'Sandbox',
        'MERCHANT789',
        'MERCKEY***',
        '5',
        'Negada',
        'Cielo',
        'Não',
        'Não',
        'N/A',
        'ORD004',
        'REF004',
        'TID13579',
        'Ana Oliveira',
        'Sim'
    ],
    [
        '**** 7890',
        'Crédito',
        '12x',
        'R$ 41,67',
        'Visa',
        '11/2030',
        '2026-01-27 16:10:55',
        'R$ 500,00',
        'R$ 500,00',
        'R$ 25,00',
        'BRL',
        'Produção',
        'MERCHANT123',
        'MERCKEY***',
        '4',
        'Autorizada',
        'Cielo',
        'Sim',
        'Sim',
        'Sim',
        'ORD005',
        'REF005',
        'TID97531',
        'Carlos Ferreira',
        'Sim'
    ]
];

// Componente principal para Analytics do Cielo
const CieloAnalyticsPage = () => {
    const gridRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (gridRef.current) {
            // Configuração do Grid.js
            const grid = new Grid({
                columns: [
                    { 
                        name: 'Cartão',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Tipo',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Parcelas',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Vlr. Parcela',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Bandeira',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Vencimento',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Data/Hora',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Total',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Subtotal',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Juros/Desc.',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Moeda',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Ambiente',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Merchant ID',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Merchant KEY',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Cód. Resp.',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Status',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Gateway',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Captura',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Recorrente',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: '3DS Auth',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Order ID',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Reference',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'TID',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Portador',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    },
                    { 
                        name: 'Enviar Dados',
                        resizable: true,
                        sort: true,
                        width: '20%'
                    }
                ],
                data: mockTransactions,
                search: true,
                sort: true,
                pagination: {
                    enabled: true,
                    limit: 10
                },
                className: {
                    table: 'cielo-transactions-table',
                    header: 'cielo-table-header',
                    tbody: 'cielo-table-body'
                },
                language: {
                    search: {
                        placeholder: __('Buscar transações...', 'lkn-wc-gateway-cielo')
                    },
                    pagination: {
                        previous: __('Anterior', 'lkn-wc-gateway-cielo'),
                        next: __('Próxima', 'lkn-wc-gateway-cielo'),
                        navigate: (page: number, pages: number) => `${__('Página', 'lkn-wc-gateway-cielo')} ${page} ${__('de', 'lkn-wc-gateway-cielo')} ${pages}`,
                        page: (page: number) => `${__('Página', 'lkn-wc-gateway-cielo')} ${page}`,
                        showing: (from: number, to: number, total: number) => `${__('Mostrando', 'lkn-wc-gateway-cielo')} ${from} ${__('a', 'lkn-wc-gateway-cielo')} ${to} ${__('de', 'lkn-wc-gateway-cielo')} ${total} ${__('registros', 'lkn-wc-gateway-cielo')}`,
                        of: __('de', 'lkn-wc-gateway-cielo'),
                        to: __('a', 'lkn-wc-gateway-cielo'),
                        results: () => __('registros', 'lkn-wc-gateway-cielo')
                    },
                    loading: __('Carregando...', 'lkn-wc-gateway-cielo'),
                    noRecordsFound: __('Nenhuma transação encontrada', 'lkn-wc-gateway-cielo'),
                    error: __('Ocorreu um erro ao carregar os dados', 'lkn-wc-gateway-cielo')
                }
            });

            // Renderizar o grid
            grid.render(gridRef.current);

            // Cleanup
            return () => {
                if (grid) {
                    grid.destroy();
                }
            };
        }
    }, []);

    return (
        <div className="woocommerce-layout">
            <div className="woocommerce-layout__primary">
                <div className="woocommerce-layout__main">
                    {/* Tabela de Transações */}
                    <div className="woocommerce-card">
                        <div className="woocommerce-card__header">
                            <h2>{__('Transações Cielo', 'lkn-wc-gateway-cielo')}</h2>
                        </div>
                        <div className="woocommerce-card__body">
                            <div ref={gridRef} className="cielo-grid-container"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

// Registra a página nos filtros do WooCommerce Admin
function initCieloAnalytics() {
    // Registra a página nos relatórios do WooCommerce Admin
    addFilter(
        'woocommerce_admin_reports_list',
        'cielo-transactions',
        (reports) => [
            ...reports,
            {
                report: 'cielo-transactions',
                title: __('Cielo Transações', 'lkn-wc-gateway-cielo'),
                component: CieloAnalyticsPage
            }
        ]
    );

    // Registra a página no sistema de roteamento do WooCommerce Admin
    addFilter(
        'woocommerce_admin_pages',
        'cielo-transactions',
        (pages) => [
            ...pages,
            {
                container: CieloAnalyticsPage,
                path: '/analytics/cielo-transactions',
                wpOpenMenu: 'toplevel_page_woocommerce',
                capability: 'view_woocommerce_reports',
                navArgs: {
                    id: 'woocommerce-analytics-cielo-transactions'
                }
            }
        ]
    );

    console.log('Cielo Analytics (Grid.js): Page registered successfully');
}

// Inicializa a extensão
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCieloAnalytics);
} else {
    initCieloAnalytics();
}

export default CieloAnalyticsPage;