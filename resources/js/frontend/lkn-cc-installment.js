(function ($) {
    'use strict';

    $(window).load(function () {
        lkn_wc_cielo_load_installments();
        $('body').on('updated_checkout', lkn_wc_cielo_load_installments);
    });

    function lkn_wc_cielo_load_installments() {
        let lknInstallmentSelect = document.getElementById('lkn_cc_installments');
        let lknTotal = document.getElementById('lkn_cc_installment_total');
        let lknInstallmentLimit = document.getElementById('lkn_cc_installment_limit');
        let lknInstallmentInterest = document.getElementById('lkn_cc_installment_interest');

        if (lknInstallmentLimit) {
            lknInstallmentLimit = lknInstallmentLimit.value;
        }

        if (lknInstallmentInterest) {
            lknInstallmentInterest = JSON.parse(lknInstallmentInterest.value);
        }

        // Remove installment options and repopulate installments
        if (lknInstallmentSelect) {
            let amount = parseFloat(lknTotal.value);
            for (let c = 1; c < lknInstallmentSelect.childNodes.length; c + 2) {
                let childNode = lknInstallmentSelect.childNodes[c];
                lknInstallmentSelect.removeChild(childNode);
            }

            for (let i = 1; i <= lknInstallmentLimit; i++) {
                let installment = amount / i;
                let formatedInstallment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(installment);
                let option = document.createElement('option');
                let text = document.createTextNode(i + 'x ' + formatedInstallment + ' sem juros');

                for (let t = 0; t < lknInstallmentInterest.length; t++) {
                    const installmentObj = lknInstallmentInterest[t];
                    // Verify if it is the right installment
                    if (installmentObj.id === i) {
                        let interest = (amount + (amount * (installmentObj.interest / 100))) / i; // installment + (installment * (installmentObj.interest / 100));
                        let formatedInterest = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(interest);

                        text = document.createTextNode(i + 'x ' + formatedInterest);
                    }
                }

                option.value = i;
                option.appendChild(text);
                lknInstallmentSelect.appendChild(option);
                if ((amount / (i + 1)) < lkn_wc_cielo_credit.installment_min) {
                    break;
                }
            }
        }
    }
})(jQuery);