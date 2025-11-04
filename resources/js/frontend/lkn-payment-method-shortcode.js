(function ($) {
    'use strict';

    let paymentMethodObserver;
    let currentRadioButtons = [];

    // Função para atualizar dados do shortcode
    function updateShortcodeData() {
        console.log('Payment method changed - updating shortcode data');

        // Aqui você pode adicionar a lógica específica para atualizar os dados do shortcode
        // Por exemplo: trigger de eventos customizados, chamadas AJAX, etc.

        // Trigger evento customizado para outros scripts escutarem
        jQuery('body').trigger('update_checkout');

        // Se precisar fazer uma chamada AJAX ou outra atualização específica
        // você pode implementar aqui
    }

    // Função para remover event listeners dos radio buttons atuais
    function removeCurrentListeners() {
        currentRadioButtons.forEach(function (radio) {
            if (radio && radio.removeEventListener) {
                radio.removeEventListener('change', updateShortcodeData);
            }
        });
        currentRadioButtons = [];
    }

    // Função para adicionar event listeners aos radio buttons
    function addPaymentMethodListeners() {
        // Remove listeners anteriores
        removeCurrentListeners();

        // Busca todos os radio buttons com name="payment_method"
        const radioButtons = document.querySelectorAll('input[name="payment_method"]');

        radioButtons.forEach(function (radio) {
            // Adiciona o event listener
            radio.addEventListener('change', updateShortcodeData);
            // Armazena referência para poder remover depois
            currentRadioButtons.push(radio);
        });

        console.log('Added listeners to', radioButtons.length, 'payment method radio buttons');
    }

    // Função para inicializar o observer
    function initializeObserver() {
        // Cria novo observer para monitorar mudanças no DOM
        paymentMethodObserver = new MutationObserver(function (mutations) {
            let shouldUpdateListeners = false;

            mutations.forEach(function (mutation) {
                // Verifica se houve mudanças nos nós
                if (mutation.type === 'childList') {
                    // Verifica se algum nó adicionado ou removido contém radio buttons de payment_method
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

                // Verifica mudanças de atributos nos radio buttons existentes
                if (mutation.type === 'attributes' && mutation.target.name === 'payment_method') {
                    shouldUpdateListeners = true;
                }
            });

            if (shouldUpdateListeners) {
                console.log('Payment method DOM changed - updating listeners');
                // Pequeno delay para garantir que o DOM foi totalmente atualizado
                setTimeout(addPaymentMethodListeners, 100);
            }
        });

        // Configuração do observer
        const config = {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['name', 'value', 'checked']
        };

        // Inicia o observer no body ou em um container específico do checkout
        const checkoutContainer = document.querySelector('.woocommerce-checkout') || document.body;
        paymentMethodObserver.observe(checkoutContainer, config);

        console.log('Payment method observer initialized');
    }

    // Aguarda o DOM carregar completamente
    $(document).ready(function () {
        console.log('LKN Payment Method Shortcode script loaded');

        // Aguarda um pouco mais para garantir que o DOM do checkout esteja totalmente carregado
        setTimeout(function () {
            // Adiciona listeners iniciais
            addPaymentMethodListeners();

            // Inicializa o observer
            initializeObserver();
        }, 500);

        // Re-inicializa após updates do checkout (para compatibilidade com AJAX)
        $(document.body).on('updated_checkout', function () {
            console.log('Checkout updated - reinitializing payment method listeners');
            setTimeout(function () {
                addPaymentMethodListeners();
            }, 200);
        });
    });

    // Cleanup ao descarregar a página
    $(window).on('beforeunload', function () {
        removeCurrentListeners();
    });

})(jQuery);