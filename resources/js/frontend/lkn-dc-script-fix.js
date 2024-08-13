/* eslint-disable no-undef */
// Implements script internationalization

function lknWcCieloPaymentGatewayRemoveLoad () {
  if (document.querySelector('.bpmpi_accesstoken')) {
    document.querySelector('.bpmpi_accesstoken').value = lknWcCieloPaymentGatewayToken

    // Remova os elementos de overlay
    const loadOverlay = document.querySelectorAll('.blockUI.blockOverlay')
    if (loadOverlay) {
      loadOverlay.forEach((load) => {
        load.remove()
      })
    }
  }
}

document.onchange = () => {
  setTimeout(() => {
    lknWcCieloPaymentGatewayRemoveLoad()
  }, 1000)
}

(function ($) {
  $(window).on('load', () => {
    // Defina o valor do access token
    lknWcCieloPaymentGatewayRemoveLoad()
    document.onchange = () => {
      setTimeout(() => {
        lknWcCieloPaymentGatewayRemoveLoad()
      }, 1000)
    }
  })
})(jQuery)
