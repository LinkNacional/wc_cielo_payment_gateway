jQuery(document).ready(function ($) {
    /**
     * Classe única para gerenciar tudo relacionado ao Cielo Credit Card
     */
    class CieloCreditCardManager {
        constructor() {
            // Configurações
            this.PAYMENT_METHOD = 'lkn_cielo_credit';
            this.SELECTOR = '.lkn_cielo_credit_select select';
            this.MARKER_CLASS = 'lkn-credit-card-cielo-initialized';
            this.TARGET_URL = '/wp-json/wc/store/v1/cart/select-shipping-rate';

            // Estado
            this.observer = null;
            this.originalFetch = window.fetch;
            this.isUpdatingOptions = false;
            this.isProcessingShipping = false;

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

                if (this.isTargetRequest(resource)) {
                    // Evita múltiplas requisições simultâneas
                    if (this.isProcessingShipping) {
                        return this.originalFetch.apply(window, args);
                    }

                    this.isProcessingShipping = true;

                    return this.originalFetch.apply(window, args)
                        .then(response => {
                            setTimeout(() => {
                                this.updateFeesAfterShippingChange();
                                this.isProcessingShipping = false;
                            }, 500);
                            return response;
                        })
                        .catch(error => {
                            // Em caso de erro, libera a flag
                            setTimeout(() => {
                                this.isProcessingShipping = false;
                            }, 500);
                            throw error;
                        });
                }

                return this.originalFetch.apply(window, args);
            };
        }

        isTargetRequest(resource) {
            const url = typeof resource === 'string' ? resource : resource.url;
            return url && url.includes(this.TARGET_URL);
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
                url: lkn_cielo_credit_card_ajax_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'lkn_update_payment_fees',
                    payment_method: this.PAYMENT_METHOD,
                    installment: installment,
                    nonce: lkn_cielo_credit_card_ajax_params.fees_nonce
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
                return;
            }

            if (this.isUpdatingOptions) {
                return;
            }

            const currentInstallment = selectElement.val() || '1';

            $.ajax({
                url: lkn_cielo_credit_card_ajax_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'lkn_update_payment_fees',
                    payment_method: this.PAYMENT_METHOD,
                    installment: currentInstallment,
                    nonce: lkn_cielo_credit_card_ajax_params.fees_nonce
                },
                success: (response) => {
                    if (response.success) {
                        // Usa as opções retornadas pela própria função
                        if (response.data && Array.isArray(response.data) && response.data.length > 0) {
                            this.replaceSelectOptions(selectElement, response.data);
                        }
                    }
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
    new CieloCreditCardManager();
});