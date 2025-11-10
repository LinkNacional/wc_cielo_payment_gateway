(function ($) {
    'use strict';

    let paymentMethodObserver;
    let currentRadioButtons = [];

    function updateShortcodeData() {
        jQuery('body').trigger('update_checkout');
    }

    function removeCurrentListeners() {
        currentRadioButtons.forEach(function (radio) {
            if (radio && radio.removeEventListener) {
                radio.removeEventListener('change', updateShortcodeData);
            }
        });
        currentRadioButtons = [];
    }

    function addPaymentMethodListeners() {
        removeCurrentListeners();

        const radioButtons = document.querySelectorAll('input[name="payment_method"]');

        radioButtons.forEach(function (radio) {
            radio.addEventListener('change', updateShortcodeData);
            currentRadioButtons.push(radio);
        });
    }

    function initializeObserver() {
        paymentMethodObserver = new MutationObserver(function (mutations) {
            let shouldUpdateListeners = false;

            mutations.forEach(function (mutation) {
                if (mutation.type === 'childList') {
                    const addedNodes = Array.from(mutation.addedNodes);
                    const removedNodes = Array.from(mutation.removedNodes);

                    const hasPaymentMethodNodes = [...addedNodes, ...removedNodes].some(function (node) {
                        if (node.nodeType === Node.ELEMENT_NODE) {
                            return node.querySelector && node.querySelector('input[name="payment_method"]') !== null;
                        }
                        return false;
                    });

                    if (hasPaymentMethodNodes) {
                        shouldUpdateListeners = true;
                    }
                }

                if (mutation.type === 'attributes' && mutation.target.name === 'payment_method') {
                    shouldUpdateListeners = true;
                }
            });

            if (shouldUpdateListeners) {
                setTimeout(addPaymentMethodListeners, 100);
            }
        });

        const config = {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['name', 'value', 'checked']
        };

        const checkoutContainer = document.querySelector('.woocommerce-checkout') || document.body;
        paymentMethodObserver.observe(checkoutContainer, config);
    }

    $(document).ready(function () {
        setTimeout(function () {
            addPaymentMethodListeners();
            initializeObserver();
        }, 500);

        $(document.body).on('updated_checkout', function () {
            setTimeout(function () {
                addPaymentMethodListeners();
            }, 200);
        });
    });

    $(window).on('beforeunload', function () {
        removeCurrentListeners();
    });

})(jQuery);