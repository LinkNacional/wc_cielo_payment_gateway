window.addEventListener('DOMContentLoaded', function () {
    // lkn-cvv
    // lkn-card-exp
    // lkn-card-num
    // $('.lkn-cvv').mask('00 / 00');
    // $('.lkn-card-exp').mask('0000');
    // $('.lkn-card-num').mask('0000#');

    // Verify if is checkout page or cart page
    let noLoginCheckout = document.getElementById('lkn_cc_no_login_checkout');

    if (noLoginCheckout && noLoginCheckout.value === 'true') {
        jQuery(function ($) {
            $('.lkn-cvv').mask('00000000');
            $('.lkn-card-exp').mask('00 / 00');
            $('.lkn-card-num').mask('0#');
        });
    } else {
        jQuery('body').on('updated_checkout', function () {
            jQuery(function ($) {
                $('.lkn-cvv').mask('00000000');
                $('.lkn-card-exp').mask('00 / 00');
                $('.lkn-card-num').mask('0#');
            });
        });
    }
});
