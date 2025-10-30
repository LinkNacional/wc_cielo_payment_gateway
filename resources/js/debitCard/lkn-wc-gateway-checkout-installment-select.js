jQuery(document).ready(function ($) {
    console.log(lkn_cielo_debit_card_ajax_params)
    /**
     * Classe única para gerenciar tudo relacionado ao Cielo Debit Card
     */
    class CieloDebitCardManager {
        constructor() {
            // Configurações
            this.PAYMENT_METHOD = 'lkn_cielo_debit';
            this.SELECTOR = '.lkn_cielo_debit_select select';
            this.MARKER_CLASS = 'lkn-debit-card-cielo-initialized';
            this.SHIPPING_URL = '/wp-json/wc/store/v1/cart/select-shipping-rate';
            this.BATCH_URL = '/wp-json/wc/store/v1/batch';

            // Estado para controle de sequência
            this.observer = null;
            this.originalFetch = window.fetch;
            this.isUpdatingOptions = false;
            this.isProcessingShipping = false;
            this.shippingCompleted = false; // Flag para controlar sequência
            this.sequenceTimeout = null; // Timeout para resetar sequência

            this.init();
        }

        init() {
            this.setupFetchInterceptor();
            this.detectExistingSelect();
            this.startObserver();
        }

        // === FETCH INTERCEPTOR ===
        setupFetchInterceptor() {
            window.fetch = (...args) => {
                const [resource, config] = args;

                // Verifica se é requisição de shipping
                if (this.isShippingRequest(resource)) {
                    return this.handleShippingRequest(args);
                }

                // Verifica se é requisição de batch (somente se shipping foi completado)
                if (this.isBatchRequest(resource) && this.shippingCompleted) {
                    return this.handleBatchRequest(args);
                }

                return this.originalFetch.apply(window, args);
            };
        }

        isShippingRequest(resource) {
            const url = typeof resource === 'string' ? resource : resource.url;
            return url && url.includes(this.SHIPPING_URL);
        }

        isBatchRequest(resource) {
            const url = typeof resource === 'string' ? resource : resource.url;
            return url && url.includes(this.BATCH_URL);
        }

        handleShippingRequest(args) {
            console.log('Detectou requisição de shipping');

            // Evita múltiplas requisições simultâneas
            if (this.isProcessingShipping) {
                return this.originalFetch.apply(window, args);
            }

            this.isProcessingShipping = true;
            this.shippingCompleted = false;

            // Limpa timeout anterior se existir
            if (this.sequenceTimeout) {
                clearTimeout(this.sequenceTimeout);
            }

            return this.originalFetch.apply(window, args)
                .then(response => {
                    console.log('Shipping request completada');
                    this.shippingCompleted = true;
                    this.isProcessingShipping = false;

                    // Timeout para resetar a sequência se batch não vier
                    this.sequenceTimeout = setTimeout(() => {
                        console.log('Timeout: resetando sequência shipping');
                        this.resetSequence();
                    }, 5000); // 5 segundos de timeout

                    return response;
                })
                .catch(error => {
                    console.log('Erro na shipping request');
                    this.resetSequence();
                    throw error;
                });
        }

        handleBatchRequest(args) {
            console.log('Detectou requisição de batch após shipping');

            return this.originalFetch.apply(window, args)
                .then(response => {
                    console.log('Batch request completada - executando AJAX');

                    // Agora sim executa a atualização das fees
                    setTimeout(() => {
                        this.updateFeesAfterShippingChange();
                        this.resetSequence();
                    }, 500);

                    return response;
                })
                .catch(error => {
                    console.log('Erro na batch request');
                    this.resetSequence();
                    throw error;
                });
        }

        resetSequence() {
            console.log('Resetando sequência');
            this.shippingCompleted = false;
            this.isProcessingShipping = false;

            if (this.sequenceTimeout) {
                clearTimeout(this.sequenceTimeout);
                this.sequenceTimeout = null;
            }
        }

        // === SELECT DETECTION ===
        detectExistingSelect() {
            const selectElement = $(this.SELECTOR);
            if (selectElement.length) {
                this.initializeSelect(selectElement);
            }
        }

        startObserver() {
            this.observer = new MutationObserver(() => {
                this.detectExistingSelect();
            });

            this.observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }

        initializeSelect(selectElement) {
            if (selectElement.hasClass(this.MARKER_CLASS)) {
                return;
            }

            selectElement.addClass(this.MARKER_CLASS);
            this.attachSelectEvents(selectElement);
        }

        // === EVENT HANDLING ===
        attachSelectEvents(selectElement) {
            selectElement.on('change', () => {
                if (this.isUpdatingOptions) {
                    return;
                }

                const installmentValue = selectElement.val() || '';
                this.updateFees(installmentValue);
            });
        }

        // === AJAX OPERATIONS ===
        updateFees(installment) {
            console.log('fazendo requisição - change select')
            $.ajax({
                url: lkn_cielo_debit_card_ajax_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'lkn_update_payment_fees',
                    payment_method: this.PAYMENT_METHOD,
                    installment: installment,
                    nonce: lkn_cielo_debit_card_ajax_params.fees_nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Usa as opções retornadas pela própria função
                        if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                            const selectElement = $(this.SELECTOR);
                            if (selectElement.length) {
                                this.replaceSelectOptions(selectElement, response.data);
                            }
                        }
                        window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore();
                    }
                }
            });
        }

        updateFeesAfterShippingChange() {
            const selectElement = $(this.SELECTOR);

            if (!selectElement.length) {
                console.log('Select não encontrado para atualização após shipping');
                return;
            }

            if (this.isUpdatingOptions) {
                console.log('Já está atualizando opções, pulando');
                return;
            }

            console.log('Executando atualização de fees após shipping + batch');
            const currentInstallment = selectElement.val() || '1';

            $.ajax({
                url: lkn_cielo_debit_card_ajax_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'lkn_update_payment_fees',
                    payment_method: this.PAYMENT_METHOD,
                    installment: currentInstallment,
                    nonce: lkn_cielo_debit_card_ajax_params.fees_nonce
                },
                success: (response) => {
                    console.log('Resposta da atualização após shipping:', response);
                    if (response.success) {
                        // Usa as opções retornadas pela própria função
                        if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                            this.replaceSelectOptions(selectElement, response.data);
                        }
                    }
                },
                error: (xhr, status, error) => {
                    console.log('Erro na atualização após shipping:', error);
                }
            });
        }

        // === SELECT MANIPULATION ===
        replaceSelectOptions(selectElement, options) {
            this.isUpdatingOptions = true;

            const currentValue = selectElement.val();

            selectElement.empty();

            options.forEach(option => {
                const optionElement = $('<option>', {
                    value: option.value,
                    html: this.decodeHtmlEntities(option.label)
                });
                selectElement.append(optionElement);
            });

            if (selectElement.find(`option[value="${currentValue}"]`).length) {
                selectElement.val(currentValue);
            } else {
                selectElement.prop('selectedIndex', 0);
            }

            setTimeout(() => {
                this.isUpdatingOptions = false;
            }, 100);

        }

        // === UTILITIES ===
        decodeHtmlEntities(text) {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = text;
            return textarea.value;
        }
    }

    // Initialize
    new CieloDebitCardManager();
});