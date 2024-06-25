/* eslint-disable no-undef */
// Implements script internationalization
const { __ } = wp.i18n;

(function ($) {
  'use strict'

  const lknLoadDebitFunctions = function () {
    const btnSubmit = document.getElementById('place_order')

    if (btnSubmit) {
      btnSubmit.setAttribute('type', 'button')
      btnSubmit.removeEventListener('click', lknDCProccessButton, true)
      btnSubmit.addEventListener('click', lknDCProccessButton, true)
    }
  }

  const lknVerifyGateway = function () {
    const debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit')

    if (debitPaymethod && debitPaymethod.checked === false) {
      const btnSubmit = document.getElementById('place_order')
      btnSubmit.setAttribute('type', 'submit')
      btnSubmit.removeEventListener('click', lknDCProccessButton, true)
    }
  }

  $(window).on('load', () => {
    const debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit')
    const debitForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
    const paymentBox = document.getElementById('payment')

    const lknWcCieloPaymentCCTypeInput = document.querySelector('#lkn_cc_type')

    lknWcCieloPaymentCCTypeInput.onchange = (e) => {
      if (e.target.value == 'Debit') {
        document.querySelector('#lkn_cc_installments').parentElement.style.display = 'none'
      } else {
        document.querySelector('#lkn_cc_installments').parentElement.style.display = ''
      }
    }

    if (document.querySelector('#lkn_dcno')) {
      document.querySelector('#lkn_dcno').onchange = (e) => {
        var cardBin = e.target.value.substring(0, 6);
        var url = window.location.origin + '/wp-json/lknWCGatewayCielo/checkCard?cardbin=' + cardBin;
        $.ajax({
          url: url,
          type: 'GET',
          headers: {
            'Accept': "application/json",
          },
          success: function (response) {
            console.log('Sucesso:', response);
            var options = document.querySelectorAll('#lkn_cc_type option');
            options.forEach(function (option) {
              if ('Crédito' == response.CardType && option.value !== 'Credit') {
                option.disabled = true;
                option.selected = false;
              } else if ('Débito' == response.CardType && option.value !== 'Debit') {
                option.disabled = true;
                option.selected = false;
              } else {
                option.disabled = false; // Reabilita opções que correspondem ao tipo de cartão
              }
            });
          },
          error: function (error) {
            console.error('Erro:', error);
          }
        });
      }
    }

    if (debitPaymethod || debitForm) {
      lknLoadDebitFunctions()
    }

    if (debitPaymethod) {
      debitPaymethod.removeEventListener('click', lknLoadDebitFunctions, true)
      debitPaymethod.addEventListener('click', lknLoadDebitFunctions, true)
    }

    if (paymentBox) {
      paymentBox.removeEventListener('click', lknVerifyGateway, true)
      paymentBox.addEventListener('click', lknVerifyGateway, true)
    }

    $('body').on('updated_checkout', function () {
      const debitForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
      const debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit')
      const paymentBox = document.getElementById('payment')

      if (debitPaymethod || debitForm) {
        lknLoadDebitFunctions()
      }

      if (debitPaymethod) {
        debitPaymethod.removeEventListener('click', lknLoadDebitFunctions, true)
        debitPaymethod.addEventListener('click', lknLoadDebitFunctions, true)
      }

      if (paymentBox) {
        paymentBox.removeEventListener('click', lknVerifyGateway, true)
        paymentBox.addEventListener('click', lknVerifyGateway, true)
      }
    })
  })
})(jQuery)

function bpmpi_config() {
  return {
    onReady: function () {

    },
    onSuccess: function (e) {
      // Card is eligible for authentication, and the bearer successfully authenticated
      const cavv = e.Cavv
      const xid = e.Xid
      const eci = e.Eci
      const version = e.Version
      const referenceId = e.ReferenceId
      const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]?.closest('form')

      if (Form3dsButton) {
        Form3dsButton.setAttribute('data-payment-cavv', cavv)
        Form3dsButton.setAttribute('data-payment-eci', eci)
        Form3dsButton.setAttribute('data-payment-ref_id', referenceId)
        Form3dsButton.setAttribute('data-payment-version', version)
        Form3dsButton.setAttribute('data-payment-xid', xid)
        const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
        const event = new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        })
        Button3ds.dispatchEvent(event)
      }

      document.getElementById('lkn_cavv').value = cavv
      document.getElementById('lkn_eci').value = eci
      document.getElementById('lkn_ref_id').value = referenceId
      document.getElementById('lkn_version').value = version
      document.getElementById('lkn_xid').value = xid

      const formCheckoutWC = document.getElementById('order_review')
      const formCartWC = document.getElementsByName('checkout')[0]

      if (formCartWC) {
        const btnSubmit = document.getElementById('place_order')
        btnSubmit.removeEventListener('click', lknDCProccessButton, true)
        btnSubmit.setAttribute('type', 'submit')
        btnSubmit.click()
      } else {
        formCheckoutWC.submit()
      }
    },
    onFailure: function (e) {
      // Card is not eligible for authentication, but the bearer failed payment

      const lknDebitCCForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
      if (lknDebitCCForm) {
        alert(__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
      }
    },
    onUnenrolled: function (e) {
      // Card is not eligible for authentication (unauthenticable)

      alert(__('Card Ineligible for Authentication', 'lkn-wc-gateway-cielo'))
    },
    onDisabled: function () {
      // Store don't require bearer authentication (class "bpmpi_auth" false -> disabled authentication).

      alert(__('Authentication disabled by the store', 'lkn-wc-gateway-cielo'))
    },
    onError: function (e) {
      // Error on proccess in authentication

      const lknDebitCCForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
      if (lknDebitCCForm) {
        alert(__('Error in the 3DS 2.0 authentication process check that your credentials are filled in correctly', 'lkn-wc-gateway-cielo'))
      }
    },
    onUnsupportedBrand: function (e) {
      // Provider not supported for authentication

      alert(__('Provider not supported by Cielo 3DS authentication', 'lkn-wc-gateway-cielo'))
    },

    Environment: 'PRD', // SDB or PRD
    Debug: false // true or false
  }
}

function lknDCProccessButton() {
  try {
    const cardNumber = document.getElementById('lkn_dcno').value.replace(/\D/g, '')
    let expDate = document.getElementById('lkn_dc_expdate').value

    expDate = expDate.split('/')

    document.getElementById('lkn_bpmpi_cardnumber').value = cardNumber
    document.getElementById('lkn_bpmpi_expmonth').value = expDate[0].replace(/\D/g, '')
    document.getElementById('lkn_bpmpi_expyear').value = expDate[1].replace(/\D/g, '')

    bpmpi_authenticate()
  } catch (error) {
    alert(__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
  }
}
