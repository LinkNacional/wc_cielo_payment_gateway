/* eslint-disable no-undef */
// Implements script internationalization

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

      const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0].closest('form')

      Form3dsButton.setAttribute('data-payment-cavv', cavv)
      Form3dsButton.setAttribute('data-payment-eci', eci)
      Form3dsButton.setAttribute('data-payment-ref_id', referenceId)
      Form3dsButton.setAttribute('data-payment-version', version)
      Form3dsButton.setAttribute('data-payment-xid', xid)

      if (Form3dsButton) {
        const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
        const event = new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        })

        Button3ds.dispatchEvent(event)
      } else {
        console.error('Form3dsButton não encontrado.')
      }
    },
    onFailure: function (e) {
      // Card is not eligible for authentication, but the bearer failed payment
      alert(__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
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
      alert(__('Error in the 3DS 2.2 authentication process check that your credentials are filled in correctly', 'lkn-wc-gateway-cielo'))
    },
    onUnsupportedBrand: function (e) {
      // Provider not supported for authentication
      alert(__('Provider not supported by Cielo 3DS authentication', 'lkn-wc-gateway-cielo'))
    },

    Environment: 'PRD', // SDB or PRD
    Debug: false // true or false
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
