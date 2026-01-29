/**
 * Cielo Analytics React Component
 * Página de analytics das transações Cielo com Grid.js
 */
import React, { useEffect, useRef, useState } from 'react';
import { __ } from '@wordpress/i18n';
import { addFilter } from '@wordpress/hooks';
import { Grid, html } from 'gridjs';
import { decode } from '@toon-format/toon';
import 'gridjs/dist/theme/mermaid.css';

// Componente principal para Analytics do Cielo
const CieloAnalyticsPage = () => {
    const gridRef = useRef<HTMLDivElement>(null);
    const [transactionData, setTransactionData] = useState([]);
    const [loading, setLoading] = useState(true);
    const [loadingMore, setLoadingMore] = useState(false);
    const [error, setError] = useState(null);
    
    // Estados de paginação
    const [currentPage, setCurrentPage] = useState(1);
    const [hasNextPage, setHasNextPage] = useState(true);
    const [totalCount, setTotalCount] = useState(0);
    
    // Estados para filtros de data
    const [startDate, setStartDate] = useState('');
    const [endDate, setEndDate] = useState('');
    const [activeFilter, setActiveFilter] = useState('hoje'); // 'hoje', '7dias', '30dias', 'personalizado'
    
    // Estados para configurações de paginação
    const [queryLimit, setQueryLimit] = useState(50); // Limite de consultas do banco (mais registros para encontrar transações Cielo)
    const [perPageLimit, setPerPageLimit] = useState(10);     // Transações por página exibidas no grid

    // Função para decodificar dados TOON usando a biblioteca @toon-format/toon
    const decodeToonData = (toonString: string) => {
        try {
            return decode(toonString);
        } catch (e) {
            console.error('Erro ao decodificar TOON:', e);
            return null;
        }
    };

    // Função para decodificar entidades HTML corretamente
    const decodeHtmlEntities = (str: string) => {
        if (!str) return str;
        
        // Criar elemento temporário para decodificar entidades HTML
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = str;
        return tempDiv.textContent || tempDiv.innerText || str;
    };

    // Função para extrair valor limpo de HTML
    const extractCleanValue = (htmlString: string) => {
        if (!htmlString || typeof htmlString !== 'string') return 'N/A';
        
        // Primeiro remove as tags HTML
        const withoutTags = htmlString.replace(/<[^>]+>/g, '');
        
        // Depois decodifica todas as entidades HTML (incluindo moedas dinâmicas)
        const decoded = decodeHtmlEntities(withoutTags);
        
        // Remove espaços extras e quebras de linha
        return decoded.replace(/\s+/g, ' ').trim();
    };

    // Função para gerar mensagem completa para debug
    const generateWhatsAppMessage = (transactionData: any) => {
        const fields = [
            // Dados do cartão
            `Cartão:${transactionData.card?.masked || 'N/A'}`,
            `CVV Enviado:${transactionData.transaction?.cvv_sent || 'N/A'}`,
            `Tipo do Cartão:${transactionData.card?.type || 'N/A'}`,
            `Bandeira:${transactionData.card?.brand || 'N/A'}`,
            `Vencimento:${transactionData.card?.expiry || 'N/A'}`,
            `Portador:${transactionData.card?.holder_name || 'N/A'}`,
            
            // Dados da transação
            `Parcelas:${transactionData.transaction?.installments || 'N/A'}`,
            `Valor Parcela:${extractCleanValue(transactionData.transaction?.installment_amount)}`,
            `Captura:${transactionData.transaction?.capture || 'N/A'}`,
            `Recorrente:${transactionData.transaction?.recurrent || 'N/A'}`,
            `3DS Auth:${transactionData.transaction?.['3ds_auth'] || 'N/A'}`,
            `TID:${transactionData.transaction?.tid || 'N/A'}`,
            `Payment ID:${transactionData.transaction?.payment_id || 'N/A'}`,
            `NSU:${transactionData.transaction?.nsu || 'N/A'}`,
            
            // Valores
            `Total:${extractCleanValue(transactionData.amounts?.total)}`,
            `Subtotal:${extractCleanValue(transactionData.amounts?.subtotal)}`,
            `Frete:${extractCleanValue(transactionData.amounts?.shipping)}`,
            `Juros/Desc:${extractCleanValue(transactionData.amounts?.interest_discount)}`,
            `Moeda:${transactionData.amounts?.currency || 'N/A'}`,
            
            // Sistema
            `Data/Hora:${transactionData.system?.request_datetime || 'N/A'}`,
            `Ambiente:${transactionData.system?.environment || 'N/A'}`,
            `Gateway:${transactionData.system?.gateway || 'N/A'}`,
            `Order ID:${transactionData.system?.order_id || 'N/A'}`,
            `Reference:${transactionData.system?.reference || 'N/A'}`,
            
            // Merchant
            `Merchant ID:${transactionData.merchant?.id_masked || 'N/A'}`,
            `Merchant Key:${transactionData.merchant?.key_masked || 'N/A'}`,
            
            // Resposta da API (essencial para debug)
            `Return Code:${transactionData.response?.return_code || 'N/A'}`,
            `HTTP Status:${transactionData.response?.http_status || 'N/A'}`
        ];
        
        return `PLUGIN-CIELO-PRO: ${fields.join('; ')};`;
    };

    // Função para gerar link do WhatsApp
    const generateWhatsAppLink = (transactionData: any) => {
        const message = generateWhatsAppMessage(transactionData);
        return `https://wa.me/?text=${encodeURIComponent(message)}`;
    };

    // Função para buscar dados via AJAX
    const fetchTransactionData = async (page = 1, append = false) => {
        try {
            if (page === 1) {
                setLoading(true);
            } else {
                setLoadingMore(true);
            }
            
            if (page === 1) {
                setError(null);
            }

            const response = await fetch((window as any).lknCieloAjax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: (window as any).lknCieloAjax.action_get_recent_orders,
                    nonce: (window as any).lknCieloAjax.nonce,
                    response_format: 'toon',
                    page: page.toString(),
                    query_limit: queryLimit.toString(),
                    start_date: startDate,
                    end_date: endDate
                })
            });

            // Verificar Content-Type para determinar formato da resposta
            const contentType = response.headers.get('Content-Type') || '';
            const isJsonResponse = contentType.includes('application/json');
            const isTextResponse = contentType.includes('text/plain');
            
            let result;
            
            if (isTextResponse) {
                // Resposta em formato TOON (text/plain)
                const responseText = await response.text();
                result = decodeToonData(responseText);
                
                if (!result) {
                    throw new Error('Falha ao decodificar resposta TOON');
                }
            } else if (isJsonResponse) {
                // Resposta em formato JSON padrão do WordPress
                result = await response.json();
                
                // Se é um wrapper JSON com dados TOON dentro
                if (result.success && result.data?.format === 'toon' && result.data?.toon_data) {
                    const toonData = decodeToonData(result.data.toon_data);
                    if (toonData) {
                        result = toonData;
                    }
                }
            } else {
                // Fallback: tentar como JSON primeiro, depois TOON
                try {
                    result = await response.json();
                } catch {
                    const responseText = await response.text();
                    result = decodeToonData(responseText);
                    
                    if (!result) {
                        throw new Error('Formato de resposta não reconhecido');
                    }
                }
            }

            if (result.success) {
                // Processar dados da estrutura JSON decodificada
                const formattedData = result.data.orders.map((order: any) => {
                    const transactionData = order.transaction_data;
                    
                    return [
                        transactionData.card?.masked || 'N/A',
                        transactionData.transaction?.cvv_sent || 'N/A',
                        transactionData.card?.type || 'N/A',
                        transactionData.transaction?.installments || 'N/A',
                        transactionData.transaction?.installment_amount || 'N/A',
                        transactionData.card?.brand || 'N/A',
                        transactionData.card?.expiry || 'N/A',
                        transactionData.system?.request_datetime || 'N/A',
                        transactionData.amounts?.total || 'N/A',
                        transactionData.amounts?.subtotal || 'N/A',
                        transactionData.amounts?.shipping || 'N/A',
                        transactionData.amounts?.interest_discount || 'N/A',
                        transactionData.amounts?.currency || 'N/A',
                        transactionData.system?.environment || 'N/A',
                        transactionData.merchant?.id_masked || 'N/A',
                        transactionData.merchant?.key_masked || 'N/A',
                        transactionData.response?.return_code || 'N/A',
                        transactionData.response?.http_status || 'N/A',
                        transactionData.system?.gateway || 'N/A',
                        transactionData.transaction?.capture || 'N/A',
                        transactionData.transaction?.recurrent || 'N/A',
                        transactionData.transaction?.['3ds_auth'] || 'N/A',
                        transactionData.system?.order_id || 'N/A',
                        transactionData.system?.reference || 'N/A',
                        transactionData.transaction?.tid || 'N/A',
                        transactionData.card?.holder_name || 'N/A',
                        transactionData // Passa o objeto completo para a última coluna (botão WhatsApp)
                    ];
                });
                
                if (append) {
                    // Acumular dados existentes
                    setTransactionData(prev => [...prev, ...formattedData]);
                } else {
                    // Substituir dados
                    setTransactionData(formattedData);
                }
                
                // Atualizar estado de paginação
                const pagination = result.data.pagination;
                setCurrentPage(pagination.page);
                setHasNextPage(pagination.has_next);
                setTotalCount(pagination.total_count);
                
            } else {
                setError(result.data?.message || 'Erro ao carregar dados');
            }
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Erro de conexão ao carregar dados';
            setError(errorMessage);
            console.error('Erro na requisição AJAX:', err);
        } finally {
            setLoading(false);
            setLoadingMore(false);
        }
    };

    // Função para carregar mais dados
    const loadMoreData = () => {
        if (hasNextPage && !loadingMore) {
            fetchTransactionData(currentPage + 1, true);
        }
    };

    // Função para aplicar filtros de data
    const applyDateFilters = () => {
        setCurrentPage(1);
        setTransactionData([]);
        fetchTransactionData(1, false);
    };

    // Função para limpar filtros de data  
    const clearDateFilters = () => {
        setStartDate('');
        setEndDate('');
        setActiveFilter('hoje');
        setCurrentPage(1);
        setTransactionData([]);
        fetchTransactionData(1, false);
    };

    // Funções para filtros rápidos de data
    const formatDateForInput = (date: Date) => {
        return date.toISOString().split('T')[0];
    };

    const setDateFilter = (filterType: string) => {
        const today = new Date();
        let startDateValue = '';
        let endDateValue = formatDateForInput(today);

        switch (filterType) {
            case 'hoje':
                startDateValue = formatDateForInput(today);
                break;
            case '7dias':
                const sevenDaysAgo = new Date();
                sevenDaysAgo.setDate(today.getDate() - 7);
                startDateValue = formatDateForInput(sevenDaysAgo);
                break;
            case '30dias':
                const thirtyDaysAgo = new Date();
                thirtyDaysAgo.setDate(today.getDate() - 30);
                startDateValue = formatDateForInput(thirtyDaysAgo);
                break;
            default:
                return;
        }

        setStartDate(startDateValue);
        setEndDate(endDateValue);
        setActiveFilter(filterType);
        setCurrentPage(1);
        setTransactionData([]);
        
        // Aplicar filtro automaticamente
        setTimeout(() => {
            fetchTransactionData(1, false);
        }, 0);
    };

    // Função para detectar quando as datas são alteradas manualmente
    const handleDateChange = (type: 'start' | 'end', value: string) => {
        if (type === 'start') {
            setStartDate(value);
        } else {
            setEndDate(value);
        }
        
        // Mudar para personalizado quando as datas são alteradas manualmente
        if (activeFilter !== 'personalizado') {
            setActiveFilter('personalizado');
        }
    };

    // Função para exportar dados em CSV
    const exportToCSV = () => {
        if (transactionData.length === 0) {
            alert(__('Não há dados para exportar', 'lkn-wc-gateway-cielo'));
            return;
        }

        // Cabeçalhos das colunas (sem a coluna do botão de suporte)
        const headers = [
            'Cartão', 'CVV Enviado', 'Tipo', 'Parcelas', 'Vlr. Parcela', 'Bandeira', 
            'Vencimento', 'Data/Hora', 'Total', 'Subtotal', 'Frete', 'Juros/Desc.', 
            'Moeda', 'Ambiente', 'Merchant ID', 'Merchant KEY', 'Cód. Resp.', 'Status', 
            'Gateway', 'Captura', 'Recorrente', '3DS Auth', 'Order ID', 'Reference', 
            'TID', 'Portador'
        ];

        // Converter dados para CSV (removendo a última coluna do botão)
        const csvContent = [headers, ...transactionData.map(row => row.slice(0, -1))]
            .map(row => row.map(field => {
                // Limpar dados HTML se necessário
                const cleanField = typeof field === 'string' ? extractCleanValue(field) : field;
                // Escapar aspas e envolver em aspas se contém vírgula
                return `"${String(cleanField).replace(/"/g, '""')}"`;
            }).join(','))
            .join('\n');

        // Download do arquivo
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `cielo-transacoes-${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // Função para exportar dados em XLS (Excel)
    const exportToXLS = () => {
        if (transactionData.length === 0) {
            alert(__('Não há dados para exportar', 'lkn-wc-gateway-cielo'));
            return;
        }

        // Cabeçalhos das colunas (sem a coluna do botão de suporte)
        const headers = [
            'Cartão', 'CVV Enviado', 'Tipo', 'Parcelas', 'Vlr. Parcela', 'Bandeira', 
            'Vencimento', 'Data/Hora', 'Total', 'Subtotal', 'Frete', 'Juros/Desc.', 
            'Moeda', 'Ambiente', 'Merchant ID', 'Merchant KEY', 'Cód. Resp.', 'Status', 
            'Gateway', 'Captura', 'Recorrente', '3DS Auth', 'Order ID', 'Reference', 
            'TID', 'Portador'
        ];

        // Gerar HTML table que o Excel pode interpretar
        let xlsContent = '<html><head><meta charset="UTF-8"></head><body><table border="1">';
        
        // Cabeçalho
        xlsContent += '<tr>';
        headers.forEach(header => {
            xlsContent += `<th style="background-color: #f0f0f0; font-weight: bold;">${escapeHtml(header)}</th>`;
        });
        xlsContent += '</tr>';
        
        // Dados (removendo a última coluna do botão)
        transactionData.forEach(row => {
            xlsContent += '<tr>';
            row.slice(0, -1).forEach((cell, index) => {
                let cleanCell;
                // Para a coluna de parcelas (índice 3), tratar especialmente
                if (index === 3) {
                    // Se for número ou string numérica, manter o valor original
                    cleanCell = (cell && !isNaN(cell)) ? cell : extractCleanValue(cell);
                } else {
                    cleanCell = extractCleanValue(cell);
                }
                xlsContent += `<td>${escapeHtml(cleanCell)}</td>`;
            });
            xlsContent += '</tr>';
        });
        
        xlsContent += '</table></body></html>';

        // Download do arquivo
        const blob = new Blob([xlsContent], { type: 'application/vnd.ms-excel;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `cielo-transacoes-${new Date().toISOString().split('T')[0]}.xls`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // Função auxiliar para escapar caracteres HTML
    const escapeHtml = (text: any): string => {
        if (text === null || text === undefined) return '';
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    // Buscar dados quando o componente for montado e aplicar filtro "hoje" por padrão
    useEffect(() => {
        setDateFilter('hoje');
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
                        sort: true
                    },
                    { 
                        name: 'CVV Enviado',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Tipo',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Parcelas',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Vlr. Parcela',
                        resizable: true,
                        sort: true,
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
                    },
                    { 
                        name: 'Bandeira',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Vencimento',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Data/Hora',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Total',
                        resizable: true,
                        sort: true,
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
                    },
                    { 
                        name: 'Subtotal',
                        resizable: true,
                        sort: true,
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
                    },
                    { 
                        name: 'Frete',
                        resizable: true,
                        sort: true,
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
                    },
                    { 
                        name: 'Juros/Desc.',
                        resizable: true,
                        sort: true,
                        formatter: (cell: string) => {
                            return cell && cell.includes('<span') ? html(cell) : cell;
                        }
                    },
                    { 
                        name: 'Moeda',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Ambiente',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Merchant ID',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Merchant KEY',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Cód. Resp.',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Status',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Gateway',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Captura',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Recorrente',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: '3DS Auth',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Order ID',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Reference',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'TID',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Portador',
                        resizable: true,
                        sort: true
                    },
                    { 
                        name: 'Enviar Dados',
                        resizable: true,
                        sort: false,
                        formatter: (cell: any) => {
                            if (typeof cell === 'object' && cell) {
                                const whatsappLink = generateWhatsAppLink(cell);
                                return html(`
                                    <a href="${whatsappLink}" target="_blank" rel="noopener noreferrer" 
                                       style="display: inline-flex; align-items: center; gap: 5px; padding: 5px 10px; 
                                              background-color: #25D366; color: white; text-decoration: none; 
                                              border-radius: 5px; font-size: 12px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.893 3.384"/>
                                        </svg>
                                        Suporte
                                    </a>
                                `);
                            }
                            return 'N/A';
                        }
                    }
                ],
                data: transactionData,
                search: true,
                sort: true,
                pagination: {
                    enabled: true,
                    limit: perPageLimit
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
                        showing: __('Mostrando', 'lkn-wc-gateway-cielo'),
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
    }, [transactionData, loading, perPageLimit]); // Dependências: transactionData, loading e perPageLimit

    return (
        <div className="woocommerce-layout">
            <div className="woocommerce-layout__primary">
                <div className="woocommerce-layout__main">
                    {/* Tabela de Transações */}
                    <div className="woocommerce-card">
                        <div className="woocommerce-card__header">
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '15px' }}>
                                <h2>{__('Transações Cielo', 'lkn-wc-gateway-cielo')}</h2>
                                <div style={{ display: 'flex', gap: '10px' }}>
                                    <button
                                        onClick={exportToCSV}
                                        disabled={loading || transactionData.length === 0}
                                        className="button"
                                        style={{ 
                                            padding: '8px 16px', 
                                            fontSize: '14px',
                                            backgroundColor: '#0073aa',
                                            color: 'white',
                                            border: '1px solid #0073aa',
                                            borderRadius: '3px',
                                            cursor: loading || transactionData.length === 0 ? 'not-allowed' : 'pointer',
                                            opacity: loading || transactionData.length === 0 ? 0.6 : 1
                                        }}
                                        title={__('Exportar dados em formato CSV', 'lkn-wc-gateway-cielo')}
                                    >
                                        📄 {__('Exportar CSV', 'lkn-wc-gateway-cielo')}
                                    </button>
                                    <button
                                        onClick={exportToXLS}
                                        disabled={loading || transactionData.length === 0}
                                        className="button"
                                        style={{ 
                                            padding: '8px 16px', 
                                            fontSize: '14px',
                                            backgroundColor: '#217346',
                                            color: 'white',
                                            border: '1px solid #217346',
                                            borderRadius: '3px',
                                            cursor: loading || transactionData.length === 0 ? 'not-allowed' : 'pointer',
                                            opacity: loading || transactionData.length === 0 ? 0.6 : 1
                                        }}
                                        title={__('Exportar dados em formato Excel', 'lkn-wc-gateway-cielo')}
                                    >
                                        📊 {__('Exportar XLS', 'lkn-wc-gateway-cielo')}
                                    </button>
                                </div>
                            </div>
                            
                            {/* Seção de Filtros de Data */}
                            <div style={{ marginTop: '20px' }}>
                                {/* Título das últimas transações */}
                                <h3 style={{ 
                                    fontSize: '16px', 
                                    fontWeight: '600', 
                                    marginBottom: '15px', 
                                    color: '#1e1e1e',
                                    borderBottom: '1px solid #ddd',
                                    paddingBottom: '5px'
                                }}>
                                    {__('Últimas transações:', 'lkn-wc-gateway-cielo')}
                                </h3>
                                
                                {/* Botões de filtro rápido */}
                                <div style={{ marginBottom: '15px', display: 'flex', gap: '10px', alignItems: 'center', flexWrap: 'wrap' }}>
                                    <button
                                        onClick={() => setDateFilter('hoje')}
                                        className={`button ${activeFilter === 'hoje' ? 'button-primary' : ''}`}
                                        style={{ padding: '6px 12px', fontSize: '13px' }}
                                    >
                                        {__('Hoje', 'lkn-wc-gateway-cielo')}
                                    </button>
                                    <button
                                        onClick={() => setDateFilter('7dias')}
                                        className={`button ${activeFilter === '7dias' ? 'button-primary' : ''}`}
                                        style={{ padding: '6px 12px', fontSize: '13px' }}
                                    >
                                        {__('Últimos 7 dias', 'lkn-wc-gateway-cielo')}
                                    </button>
                                    <button
                                        onClick={() => setDateFilter('30dias')}
                                        className={`button ${activeFilter === '30dias' ? 'button-primary' : ''}`}
                                        style={{ padding: '6px 12px', fontSize: '13px' }}
                                    >
                                        {__('Último 30 dias', 'lkn-wc-gateway-cielo')}
                                    </button>
                                    <button
                                        onClick={() => setActiveFilter('personalizado')}
                                        className={`button ${activeFilter === 'personalizado' ? 'button-primary' : ''}`}
                                        style={{ 
                                            padding: '6px 12px', 
                                            fontSize: '13px',
                                            cursor: activeFilter === 'personalizado' ? 'pointer' : 'default'
                                        }}
                                        disabled={activeFilter !== 'personalizado'}
                                    >
                                        {__('Personalizado', 'lkn-wc-gateway-cielo')}
                                    </button>
                                </div>
                                
                                {/* Consulta personalizada */}
                                <div style={{ marginBottom: '15px' }}>
                                    <h4 style={{ 
                                        fontSize: '14px', 
                                        fontWeight: '500', 
                                        marginBottom: '10px',
                                        color: '#666'
                                    }}>
                                        {__('Datas da consulta:', 'lkn-wc-gateway-cielo')}
                                    </h4>
                                    <div style={{ display: 'flex', gap: '10px', alignItems: 'center', flexWrap: 'wrap' }}>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                                            <label style={{ fontSize: '14px', fontWeight: '500' }}>
                                                {__('Data Inicial:', 'lkn-wc-gateway-cielo')}
                                            </label>
                                            <input
                                                type="date"
                                                value={startDate}
                                                onChange={(e) => handleDateChange('start', e.target.value)}
                                                style={{ padding: '5px', border: '1px solid #ddd', borderRadius: '4px' }}
                                            />
                                        </div>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                                            <label style={{ fontSize: '14px', fontWeight: '500' }}>
                                                {__('Data Final:', 'lkn-wc-gateway-cielo')}
                                            </label>
                                            <input
                                                type="date"
                                                value={endDate}
                                                onChange={(e) => handleDateChange('end', e.target.value)}
                                                style={{ padding: '5px', border: '1px solid #ddd', borderRadius: '4px' }}
                                            />
                                        </div>
                                        <button
                                            onClick={applyDateFilters}
                                            disabled={loading}
                                            className="button button-primary"
                                            style={{ padding: '5px 15px', fontSize: '14px' }}
                                        >
                                            {__('Filtrar', 'lkn-wc-gateway-cielo')}
                                        </button>
                                        <button
                                            onClick={clearDateFilters}
                                            disabled={loading}
                                            className="button"
                                            style={{ padding: '5px 15px', fontSize: '14px' }}
                                        >
                                            {__('Limpar', 'lkn-wc-gateway-cielo')}
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Configurações de Limite */}
                            <div style={{ marginTop: '20px', marginBottom: '15px' }}>
                                <h4 style={{ 
                                    fontSize: '14px', 
                                    fontWeight: '500', 
                                    marginBottom: '10px',
                                    color: '#666'
                                }}>
                                    {__('Paginação:', 'lkn-wc-gateway-cielo')}
                                </h4>
                                <div style={{ display: 'flex', gap: '15px', alignItems: 'center', flexWrap: 'wrap' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                                        <label htmlFor="query-limit-input" style={{ fontSize: '14px', fontWeight: '500' }}>
                                            {__('Limite de consultas por sessão:', 'lkn-wc-gateway-cielo')}
                                        </label>
                                        <input
                                            id="query-limit-input"
                                            type="number"
                                            value={queryLimit}
                                            onChange={(e) => setQueryLimit(Math.max(1, parseInt(e.target.value) || 1))}
                                            min="1"
                                            max="1000"
                                            style={{ 
                                                padding: '5px', 
                                                border: '1px solid #ddd', 
                                                borderRadius: '4px',
                                                width: '80px'
                                            }}
                                        />
                                    </div>
                                    <button
                                        onClick={() => {
                                            setCurrentPage(1);
                                            setTransactionData([]);
                                            fetchTransactionData(1, false);
                                        }}
                                        disabled={loading}
                                        className="button button-primary"
                                        style={{ padding: '5px 15px', fontSize: '14px' }}
                                    >
                                        {__('Filtrar', 'lkn-wc-gateway-cielo')}
                                    </button>
                                    <button
                                        onClick={() => {
                                            setQueryLimit(50);
                                            setPerPageLimit(10);
                                        }}
                                        disabled={loading}
                                        className="button"
                                        style={{ padding: '5px 15px', fontSize: '14px' }}
                                    >
                                        {__('Restaurar Padrão', 'lkn-wc-gateway-cielo')}
                                    </button>
                                </div>
                                
                                {/* Configuração de exibição separada */}
                                <div style={{ marginTop: '15px', paddingTop: '15px', borderTop: '1px solid #e0e0e0' }}>
                                    <div style={{ display: 'flex', alignItems: 'center', gap: '5px' }}>
                                        <label htmlFor="per-page-limit-input" style={{ fontSize: '14px', fontWeight: '500' }}>
                                            {__('Transações por página:', 'lkn-wc-gateway-cielo')}
                                        </label>
                                        <input
                                            id="per-page-limit-input"
                                            type="number"
                                            value={perPageLimit}
                                            onChange={(e) => setPerPageLimit(Math.max(1, parseInt(e.target.value) || 1))}
                                            min="1"
                                            max="100"
                                            style={{ 
                                                padding: '5px', 
                                                border: '1px solid #ddd', 
                                                borderRadius: '4px',
                                                width: '80px'
                                            }}
                                        />
                                    </div>
                                </div>
                            </div>
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
                                    <button onClick={() => fetchTransactionData(1)} className="button">
                                        {__('Tentar novamente', 'lkn-wc-gateway-cielo')}
                                    </button>
                                </div>
                            )}
                            {!loading && !error && (
                                <>
                                    {/* Informações de paginação */}
                                    <div style={{ marginBottom: '15px', fontSize: '14px', color: '#666', padding: '10px', backgroundColor: '#f9f9f9', borderRadius: '4px' }}>
                                        {__('Mostrando', 'lkn-wc-gateway-cielo')} {transactionData.length} {__('de', 'lkn-wc-gateway-cielo')} {totalCount} {__('transações', 'lkn-wc-gateway-cielo')}
                                        {currentPage > 1 && (
                                            <span style={{ marginLeft: '10px' }}>
                                                ({__('Página', 'lkn-wc-gateway-cielo')} {currentPage})
                                            </span>
                                        )}
                                    </div>
                                    
                                    <div ref={gridRef} className="cielo-grid-container"></div>
                                    
                                    {/* Botão carregar mais */}
                                    {hasNextPage && (
                                        <div style={{ textAlign: 'center', marginTop: '20px', padding: '15px' }}>
                                            <button 
                                                onClick={loadMoreData}
                                                disabled={loadingMore}
                                                className="button button-primary"
                                                style={{
                                                    padding: '10px 20px',
                                                    fontSize: '14px',
                                                    cursor: loadingMore ? 'not-allowed' : 'pointer',
                                                    opacity: loadingMore ? 0.6 : 1
                                                }}
                                            >
                                                {loadingMore ? __('Carregando...', 'lkn-wc-gateway-cielo') : __('Carregar mais transações', 'lkn-wc-gateway-cielo')}
                                            </button>
                                        </div>
                                    )}
                                </>
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