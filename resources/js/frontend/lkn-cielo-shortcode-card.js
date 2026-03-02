window.jQuery(function ($) {
  // Verifica se janela foi carregada antes da criação do card
  $(window).on('load', lknCieloCreditCardRender)
  // Cria o card somente quando a requisição for concluida
  $(document).on('updated_checkout', lknCieloCreditCardRender)
  $(document).on('wc-fragment-refreshed', lknCieloCreditCardRender)
  $(document).on('woocommerce_cart_updated', lknCieloCreditCardRender)
  $(document).on('woocommerce_checkout_update_order_review', lknCieloCreditCardRender)
  // Fallback para quando o evento não é disparado
  lknCieloCreditCardRender()

  function lknCieloCreditCardRender() {
    // Não carrega para formulário em bloco
    if (!document.querySelector('.wc-block-checkout')) {
      // Verifica se é página de fatura ou se é página de checkout
      let $form = $('.woocommerce .woocommerce-checkout')
      if ($form.length === 0) {
        $form = $('#order_review')
      }
      const selectedPaymentMethod = $form.find('input[name="payment_method"]:checked')
      
      // Detecta qual gateway está ativo e define os seletores apropriados
      let inputSelectors = getInputSelectorsByGateway(selectedPaymentMethod)
      let paymentMethodValue = selectedPaymentMethod.val()
      
      // Define o container baseado no método de pagamento
      let containerSelector = paymentMethodValue === 'lkn_cielo_credit' ? '#cielo-credit-card-animation' : '#cielo-debit-card-animation'
      
      // Se não conseguiu definir os seletores, não há campo de crédito, ou não existe o container, sai
      if (!inputSelectors || (!$form.find(inputSelectors.numberInput).length) || (!$form.find(containerSelector).length)) {
        return
      }

      // maybe delete old card data
      $form.data('card', null)

      // init animated card
      $form.card({
        container: containerSelector,

        /**
         * Selectors
         */
        formSelectors: inputSelectors,

        /**
         * Placeholders
         */
        placeholders: {
          number: '•••• •••• •••• ••••',
          name: 'NOME',
          expiry: 'MM/ANO',
          cvc: 'CVC'
        },

        /**
         * Translation Brazilian Portuguese
         */
        messages: {
          validDate: 'VALIDADE',
          monthYear: ''
        },

        /**
         * Debug
         */
        debug: false // You can make this configurable later
      })

      // Workaround to maintain the card data rendered after checkout updates
      Object.values(inputSelectors).reverse().forEach(function (selector) {
        $(selector)[0]?.dispatchEvent(new CustomEvent('change'))
      })

      $(inputSelectors.numberInput)[0]?.dispatchEvent(new CustomEvent('focus'))
      $(inputSelectors.numberInput)[0]?.dispatchEvent(new CustomEvent('blur'))
    }

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
