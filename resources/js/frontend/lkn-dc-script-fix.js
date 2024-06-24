/* eslint-disable no-undef */
// Implements script internationalization

(function ($) {


  $(window).on('load', () => {
    document.querySelector('.bpmpi_accesstoken').value = lknWcCieloPaymentGatewayToken
    document.querySelectorAll('.blockUI.blockOverlay').forEach((load)=>{
      load.remove()
    })
  })
})(jQuery)