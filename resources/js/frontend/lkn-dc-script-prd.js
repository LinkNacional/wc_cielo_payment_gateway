// Implements script internationalization
const { __ } = wp.i18n;

(function ($) {
    'use strict';

    let lkn_load_debit_functions = function () {
        let btnSubmit = document.getElementById('place_order');

        if (btnSubmit) {
            btnSubmit.setAttribute('type', 'button');
            btnSubmit.removeEventListener('click', lkn_proccess_button, true);
            btnSubmit.addEventListener('click', lkn_proccess_button, true);
        }
    };

    let lkn_verify_gateway = function () {
        let debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit');

        if (debitPaymethod.checked === false) {
            let btnSubmit = document.getElementById('place_order');
            btnSubmit.setAttribute('type', 'submit');
            btnSubmit.removeEventListener('click', lkn_proccess_button, true);
        }
    };

    $(window).load(function () {
        lkn_load_debit_functions();

        let debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit');
        let paymentBox = document.getElementById('payment');

        debitPaymethod.removeEventListener('click', lkn_load_debit_functions, true);
        debitPaymethod.addEventListener('click', lkn_load_debit_functions, true);

        paymentBox.removeEventListener('click', lkn_verify_gateway, true);
        paymentBox.addEventListener('click', lkn_verify_gateway, true);

        $('body').on('updated_checkout', function () {
            lkn_load_debit_functions();

            let debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit');
            let paymentBox = document.getElementById('payment');

            debitPaymethod.removeEventListener('click', lkn_load_debit_functions, true);
            debitPaymethod.addEventListener('click', lkn_load_debit_functions, true);

            paymentBox.removeEventListener('click', lkn_verify_gateway, true);
            paymentBox.addEventListener('click', lkn_verify_gateway, true);
        });
    });
})(jQuery);

function bpmpi_config() {
    return {
        onReady: function () {

        },
        onSuccess: function (e) {
            // Card is eligible for authentication, and the bearer successfully authenticated
            var cavv = e.Cavv;
            var xid = e.Xid;
            var eci = e.Eci;
            var version = e.Version;
            var referenceId = e.ReferenceId;

            document.getElementById('lkn_cavv').value = cavv;
            document.getElementById('lkn_eci').value = eci;
            document.getElementById('lkn_ref_id').value = referenceId;
            document.getElementById('lkn_version').value = version;
            document.getElementById('lkn_xid').value = xid;

            var formCheckoutWC = document.getElementById('order_review');
            var formCartWC = document.getElementsByName('checkout')[0];

            if (formCartWC) {
                var btnSubmit = document.getElementById('place_order');
                btnSubmit.removeEventListener('click', lkn_proccess_button, true);
                btnSubmit.setAttribute('type', 'submit');
                btnSubmit.click();
            } else {
                formCheckoutWC.submit();
            }
        },
        onFailure: function (e) {
            // Card is not eligible for authentication, but the bearer failed payment

            alert(__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'));
        },
        onUnenrolled: function (e) {
            // Card is not eligible for authentication (unauthenticable)

            alert(__('Card Ineligible for Authentication', 'lkn-wc-gateway-cielo'));
        },
        onDisabled: function () {
            // Store don't require bearer authentication (class "bpmpi_auth" false -> disabled authentication).

            alert(__('Authentication disabled by the store', 'lkn-wc-gateway-cielo'));
        },
        onError: function (e) {
            // Error on proccess in authentication

            alert(__('Error in the 3DS 2.0 authentication process check that your credentials are filled in correctly', 'lkn-wc-gateway-cielo'));
        },
        onUnsupportedBrand: function (e) {
            // Provider not supported for authentication

            alert(__('Provider not supported by Cielo 3DS authentication', 'lkn-wc-gateway-cielo'));
        },

        Environment: 'PRD', // SDB or PRD
        Debug: false // true or false
    };
}

function lkn_proccess_button() {
    try {
        let cardNumber = document.getElementById('lkn_dcno').value.replace(/\D/g, '');
        let expDate = document.getElementById('lkn_dc_expdate').value;

        expDate = expDate.split('/');

        document.getElementById('lkn_bpmpi_cardnumber').value = cardNumber;
        document.getElementById('lkn_bpmpi_expmonth').value = expDate[0].replace(/\D/g, '');
        document.getElementById('lkn_bpmpi_expyear').value = expDate[1].replace(/\D/g, '');

        bpmpi_authenticate();
    } catch (error) {
        alert(__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'));
    }
}