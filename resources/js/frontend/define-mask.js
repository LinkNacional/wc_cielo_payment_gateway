(function ($) {
    'use strict';

    $(window).load(function () {
        lkn_wc_cielo_load_mask();
        $('body').on('updated_checkout', lkn_wc_cielo_load_mask);
    });

    function lkn_wc_cielo_load_mask() {
        $('.lkn-cvv').mask('00000000');
        $('.lkn-card-exp').mask('00 / 00');
        $('.lkn-card-num').mask('0#');
    }
})(jQuery);
