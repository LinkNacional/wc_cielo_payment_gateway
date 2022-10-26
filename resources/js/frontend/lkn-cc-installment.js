//  Declaritive initalization
window.addEventListener('DOMContentLoaded', function () {
    // Verify if is checkout page or cart page
    let noLoginCheckout = document.getElementById('lkn_cc_no_login_checkout');

    if (noLoginCheckout && noLoginCheckout.value === 'true') {
        let lknInstallmentSelect = document.getElementById('lkn_cc_installments');
        let lknTotal = document.getElementById('lkn_cc_installment_total');

        // Remove installment options and repopulate installments
        if (lknInstallmentSelect) {
            let amount = lknTotal.value;
            for (let c = 1; c < lknInstallmentSelect.childNodes.length; c + 2) {
                let childNode = lknInstallmentSelect.childNodes[c];
                lknInstallmentSelect.removeChild(childNode);
            }
            for (let i = 1; i < 13; i++) {
                let installment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(amount / i);
                let option = document.createElement('option');
                let text = document.createTextNode(i + 'x ' + installment + ' sem juros');
                option.value = i;
                option.appendChild(text);
                lknInstallmentSelect.appendChild(option);
                if ((amount / (i + 1)) < 5) {
                    break;
                }
            }
        }
    } else {
        jQuery('body').on('updated_checkout', function () {
            let lknInstallmentSelect = document.getElementById('lkn_cc_installments');
            let lknTotal = document.getElementById('lkn_cc_installment_total');

            // Remove installment options and repopulate installments
            if (lknInstallmentSelect) {
                let amount = lknTotal.value;
                for (let c = 1; c < lknInstallmentSelect.childNodes.length; c + 2) {
                    let childNode = lknInstallmentSelect.childNodes[c];
                    lknInstallmentSelect.removeChild(childNode);
                }
                for (let i = 1; i < 13; i++) {
                    let installment = new Intl.NumberFormat('pt-br', { style: 'currency', currency: 'BRL' }).format(amount / i);
                    let option = document.createElement('option');
                    let text = document.createTextNode(i + 'x ' + installment + ' sem juros');
                    option.value = i;
                    option.appendChild(text);
                    lknInstallmentSelect.appendChild(option);
                    if ((amount / (i + 1)) < 5) {
                        break;
                    }
                }
            }
        });
    }
});