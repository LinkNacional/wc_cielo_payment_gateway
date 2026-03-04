window.jQuery(function ($) {
  // Verifica se janela foi carregada antes da criação do card
  $(window).on('load', lknCieloCreditCardRender)
  // Cria o card somente quando a requisição for concluida
  $(document).on('updated_checkout', lknCieloCreditCardRender)
  // $(document).on('wc-fragment-refreshed', lknCieloCreditCardRender)
  // $(document).on('woocommerce_cart_updated', lknCieloCreditCardRender)
  // $(document).on('woocommerce_checkout_update_order_review', lknCieloCreditCardRender)
  // Fallback para quando o evento não é disparado
  // lknCieloCreditCardRender()

  function lknCieloCreditCardRender() {
    if (document.querySelector('.wc-block-checkout')) {
      return // Não carrega para formulário em bloco
    }

    let $form = $('.woocommerce .woocommerce-checkout')
    if ($form.length === 0) {
      $form = $('#order_review')
    }

    // Busca containers de crédito e débito separadamente
    const containers = [
      { selector: '#cielo-credit-card-animation', gateway: 'lkn_cielo_credit' },
      { selector: '#cielo-debit-card-animation', gateway: 'lkn_cielo_debit' }
    ]

    containers.forEach(function (container) {
      const $container = $form.find(container.selector)
      if ($container.length && !$container.hasClass('lkn-cielo-card-inicialized')) {
        // Detecta os seletores de input pelo gateway
        let inputSelectors = getInputSelectorsByGateway({ val: () => container.gateway })
        if (!inputSelectors || !$form.find(inputSelectors.numberInput).length) {
          return
        }

        // maybe delete old card data
        $form.data('card', null)

        // init animated card
        $form.card({
          container: container.selector,
          formSelectors: inputSelectors,
          placeholders: {
            number: '•••• •••• •••• ••••',
            name: 'NOME',
            expiry: 'MM/ANO',
            cvc: 'CVC'
          },
          messages: {
            validDate: 'VALIDADE',
            monthYear: ''
          },
          debug: false
        })

        // Workaround para manter os dados do cartão renderizados após atualizações do checkout
        Object.values(inputSelectors).reverse().forEach(function (selector) {
          $(selector)[0]?.dispatchEvent(new CustomEvent('change'))
        })
        $(inputSelectors.numberInput)[0]?.dispatchEvent(new CustomEvent('focus'))
        $(inputSelectors.numberInput)[0]?.dispatchEvent(new CustomEvent('blur'))

        // Marca como inicializado
        $container.addClass('lkn-cielo-card-inicialized')
      }
    })
  }

  function getInputSelectorsByGateway(selectedPaymentMethod) {
    if (!selectedPaymentMethod || !selectedPaymentMethod.val()) {
      return null
    }

    const paymentMethodValue = selectedPaymentMethod.val()

    // Gateway de crédito
    if (paymentMethodValue === 'lkn_cielo_credit') {
      return {
        numberInput: '#lkn_ccno',
        nameInput: '#lkn_cc_cardholder_name',
        expiryInput: '#lkn_cc_expdate',
        cvcInput: '#lkn_cc_cvc'
      }
    }

    // Gateway de débito
    if (paymentMethodValue === 'lkn_cielo_debit') {
      return {
        numberInput: '#lkn_dcno',
        nameInput: '#lkn_dc_cardholder_name',
        expiryInput: '#lkn_dc_expdate',
        cvcInput: '#lkn_dc_cvc'
      }
    }

    return null
  }
})
