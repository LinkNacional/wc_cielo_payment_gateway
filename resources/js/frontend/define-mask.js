/* eslint-disable no-undef */
(function ($) {
  'use strict'

  $(window).on('load', () => {
    lknWCCieloLoadMask()
    $('body').on('updated_checkout', lknWCCieloLoadMask)
  })

  function lknWCCieloLoadMask () {
    $('.lkn-cvv').mask('00000000')
    $('.lkn-card-exp').mask('00 / 00')
    $('.lkn-card-num').mask('0#')
  }
})(jQuery)
