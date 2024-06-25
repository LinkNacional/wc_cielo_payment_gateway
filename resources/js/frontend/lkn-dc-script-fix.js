/* eslint-disable no-undef */
// Implements script internationalization

(function ($) {
  $(window).on('load', () => {
    // Defina o valor do access token
    if (document.querySelector('.bpmpi_accesstoken')) {
      document.querySelector('.bpmpi_accesstoken').value = lknWcCieloPaymentGatewayToken;

      // Remova os elementos de overlay
      document.querySelectorAll('.blockUI.blockOverlay').forEach((load) => {
        load.remove();
      });
    }
  });
})(jQuery);
