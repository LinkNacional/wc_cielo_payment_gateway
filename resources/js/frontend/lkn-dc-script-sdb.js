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
      if (btnSubmit) {
        btnSubmit.setAttribute('type', 'submit')
        btnSubmit.removeEventListener('click', lknDCProccessButton, true)
      }
    }
  }

  $(window).on('load', () => {
    const debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit')
    const debitForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
    const paymentBox = document.getElementById('payment')
    const lknWcCieloPaymentCCTypeInput = document.querySelector('#lkn_cc_type')
    const lknWcCieloCcDcInstallment = document.querySelector('#lkn_cc_dc_installments')
    const lknWcCieloCcDcNo = document.querySelector('#lkn_dcno')

    if (lknWcCieloCcDcNo && lknWcCieloPaymentCCTypeInput) {
      lknWcCieloPaymentCCTypeInput.onchange = (e) => {
        if (e.target.value === 'Debit' && lknWcCieloCcDcInstallment) {
          lknWcCieloCcDcInstallment.parentElement.style.display = 'none'
        } else if (lknWcCieloCcDcInstallment) {
          lknWcCieloCcDcInstallment.parentElement.style.display = ''
        }
      }

      lknWcCieloCcDcNo.onchange = (e) => {
        const cardBin = e.target.value.substring(0, 6)
        const url = window.location.origin + '/wp-json/lknWCGatewayCielo/checkCard?cardbin=' + cardBin
        $.ajax({
          url,
          type: 'GET',
          headers: {
            Accept: 'application/json'
          },
          success: function (response) {
            const options = document.querySelectorAll('#lkn_cc_type option')

            // Reset all options: enable all and deselect all
            options.forEach(function (option) {
              option.disabled = false
              option.selected = false
            })

            options.forEach(function (option) {
              if (response.CardType === 'Crédito' && option.value !== 'Credit') {
                option.disabled = true
                option.selected = false
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = ''
                }
              } else if (response.CardType === 'Débito' && option.value !== 'Debit') {
                option.disabled = true
                option.selected = false
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = 'none'
                }
              } else if (response.CardType === 'Crédito' && option.value === 'Credit') {
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = ''
                }
                option.selected = true
              } else if (response.CardType === 'Débito' && option.value === 'Debit') {
                option.selected = true
              } else if (response.CardType === 'Multiplo') {
                if (lknWcCieloCcDcInstallment) {
                  lknWcCieloCcDcInstallment.parentElement.style.display = ''
                }
              }
            })
          },
          error: function (error) {
            console.error('Erro:', error)
          }
        })
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
      const debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit')
      const debitForm = document.getElementById('wc-lkn_cielo_debit-cc-form')
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

function bpmpi_config () {
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

      const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
      const formCartWC = document.getElementsByName('checkout')[0] ? document.getElementsByName('checkout')[0] : document.querySelector('#order_review')

      if (formCartWC) {
        const btnSubmit = document.getElementById('place_order')

        if (btnSubmit) {
          btnSubmit.removeEventListener('click', lknDCProccessButton, true)
          btnSubmit.setAttribute('type', 'submit')
          btnSubmit.click()
        }
      } else if (Button3ds) {
        if (formCartWC) {
          const btnSubmit = document.getElementById('place_order')
          btnSubmit.removeEventListener('click', lknDCProccessButton, true)
          btnSubmit.setAttribute('type', 'submit')
          btnSubmit.click()
        } else {
          if (Button3ds) {
            Button3ds.click()
          }
        }
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
        alert(__('Error in the 3DS 2.2 authentication process check that your credentials are filled in correctly', 'lkn-wc-gateway-cielo'))
      }
    },
    onUnsupportedBrand: function (e) {
      // Provider not supported for authentication

      alert(__('Provider not supported by Cielo 3DS authentication', 'lkn-wc-gateway-cielo'))
    },

    Environment: 'SDB', // SDB or PRD
    Debug: true // true or false
  }
}

function lknDCProccessButton () {
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


//Carrega js do 3DS
document.addEventListener('DOMContentLoaded', function () {
  let radioInputs = Array.from(document.querySelectorAll('input[type=radio][id^=payment_method]'));

  // Adiciona um listener a cada elemento
  radioInputs.forEach(input => {
    input.addEventListener('change', function() {
      if (this.id === 'payment_method_lkn_cielo_debit') {
        lknWcGatewayCieloLoadScript();
      }
    });
  });

  radioInputCieloDebit = document.getElementById('payment_method_lkn_cielo_debit');
  if(radioInputCieloDebit){
    if(radioInputCieloDebit.checked){
      lknWcGatewayCieloLoadScript();
    }
  }

  function lknWcGatewayCieloLoadScript(){
    const scriptUrlBpmpi = lknDCDirScript3DSCieloShortCode;
    const existingScriptBpmpi = document.querySelector(`script[src="${scriptUrlBpmpi}"]`);

    if (!existingScriptBpmpi) {
      const scriptBpmpi = document.createElement('script');
      scriptBpmpi.src = scriptUrlBpmpi;
      scriptBpmpi.async = true;
      document.body.appendChild(scriptBpmpi);
    }
  }
})