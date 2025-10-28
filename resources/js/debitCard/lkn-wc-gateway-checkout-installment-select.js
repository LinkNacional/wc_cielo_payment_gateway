jQuery(document).ready(function ($) {

    /**
     * Single Responsibility: Detecta e inicializa selects Cielo
     */
    class CieloSelectDetector {
        constructor() {
            // Constants dentro da classe
            this.PAYMENT_METHOD = 'lkn_cielo_debit';
            this.SELECTOR = '.lkn_cielo_debit_select select';
            this.MARKER_CLASS = 'lkn-debit-card-cielo-initialized';
            this.observer = null;
            this.init();
        }

        init() {
            this.detectExistingSelect();
            this.startObserver();
        }

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
                return; // Já foi inicializado
            }

            selectElement.addClass(this.MARKER_CLASS);
            new CieloSelectHandler(selectElement, this.PAYMENT_METHOD);
        }
    }

    /**
     * Single Responsibility: Gerencia eventos do select
     */
    class CieloSelectHandler {
        constructor(selectElement, paymentMethod) {
            this.selectElement = selectElement;
            this.paymentMethod = paymentMethod;
            this.attachEvents();
        }

        attachEvents() {
            this.selectElement.on('change', () => {
                const installmentValue = this.selectElement.val() || '';
                new AjaxService().updateFees(this.paymentMethod, installmentValue);
            });
        }
    }

    /**
     * Single Responsibility: Gerencia comunicação AJAX
     */
    class AjaxService {
        updateFees(paymentMethod, installment) {
            $.ajax({
                url: lkn_cielo_debit_card_ajax_params.ajax_url,
                type: 'POST',
                data: {
                    action: 'lkn_update_payment_fees',
                    payment_method: paymentMethod,
                    installment: installment,
                    nonce: lkn_cielo_debit_card_ajax_params.nonce
                },
                success: (response) => {
                    if (response.success) {
                        window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore();
                    }
                }
            });
        }
    }

    // Initialize
    new CieloSelectDetector();

    $(document.body).on('updated_checkout', () => {
        new CieloSelectDetector();
    });
});