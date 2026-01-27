/**
 * Cielo Analytics React Component
 * Página de analytics das transações Cielo com Grid.js
 */
import React, { useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { Grid, html } from 'gridjs';
import 'gridjs/dist/theme/mermaid.css';

// Componente principal para Analytics do Cielo
const CieloAnalyticsPage = () => {
    const gridRef = useRef<HTMLDivElement>(null);
    const [transactionData, setTransactionData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    // Função para buscar dados via AJAX
    const fetchTransactionData = async () => {
        try {
            setLoading(true);
            setError(null);

            const response = await fetch((window as any).lknCieloAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: (window as any).lknCieloAjax.action_get_recent_orders,
                    nonce: (window as any).lknCieloAjax.nonce
                })
            });

            const result = await response.json();

            if (result.success) {
                // Converter dados da API para o formato do Grid.js
                const formattedData = result.data.orders.map((order: any) => [
                    order.card_masked,
                    order.card_type,
                    order.installments,
                    order.installment_amount,
                    order.card_brand,
                    order.card_expiry,
                    order.request_datetime,
                    order.total_amount,
                    order.subtotal,
                    order.shipping,
                    order.interest_discount,
                    order.currency,
                    order.environment,
                    order.merchant_id,
                    order.merchant_key,
                    order.return_code,
                    order.status_http,
                    order.gateway,
                    order.capture,
                    order.recurrent,
                    order.three_ds_auth,
                    order.order_id,
                    order.reference,
                    order.tid,
                    order.cardholder_name,
                    order.cvv_sent
                ]);
                
                setTransactionData(formattedData);
            } else {
                setError(result.data?.message || 'Erro ao carregar dados');
            }
        } catch (err) {
            setError('Erro de conexão ao carregar dados');
            console.error('Erro na requisição AJAX:', err);
        } finally {
            setLoading(false);
        }
    };

    // Buscar dados quando o componente for montado
    useEffect(() => {
        fetchTransactionData();
    }, []);

    // Configurar e renderizar o Grid quando os dados estiverem prontos
    useEffect(() => {
        if (gridRef.current && !loading) {
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
                        width: '20%',
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
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
                        width: '20%',
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
                    },
                    { 
                        name: 'Subtotal',
                        resizable: true,
                        sort: true,
                        width: '20%',
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
                    },
                    { 
                        name: 'Frete',
                        resizable: true,
                        sort: true,
                        width: '20%',
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
                    },
                    { 
                        name: 'Juros/Desc.',
                        resizable: true,
                        sort: true,
                        width: '20%',
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
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
                data: transactionData,
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
                style: {
                    table: {
                        'white-space': 'nowrap'
                    }
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
    }, [transactionData, loading]); // Dependências: transactionData e loading

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
                            {loading && (
                                <div className="loading-indicator">
                                    <p>{__('Carregando transações...', 'lkn-wc-gateway-cielo')}</p>
                                </div>
                            )}
                            {error && (
                                <div className="error-message">
                                    <p>{__('Erro:', 'lkn-wc-gateway-cielo')} {error}</p>
                                    <button onClick={fetchTransactionData} className="button">
                                        {__('Tentar novamente', 'lkn-wc-gateway-cielo')}
                                    </button>
                                </div>
                            )}
                            {!loading && !error && (
                                <div ref={gridRef} className="cielo-grid-container"></div>
                            )}
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