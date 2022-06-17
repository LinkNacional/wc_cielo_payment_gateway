console.log('HELLO WORLD DEBIT');

let lkn_proccess_button = function () {
    console.log('chamar função de débito');

    let cardNumber = document.getElementById('lkn_dcno').value.replace(/\D/g, '');
    let expDate = document.getElementById('lkn_dc_expdate').value;

    expDate = expDate.split('/');

    document.getElementById('lkn_bpmpi_cardnumber').value = cardNumber;
    document.getElementById('lkn_bpmpi_expmonth').value = expDate[0].replace(/\D/g, '');
    document.getElementById('lkn_bpmpi_expyear').value = expDate[1].replace(/\D/g, '');

    bpmpi_authenticate();
};

let lkn_load_debit_functions = function () {
    console.log('FUNÇÕES DE DÉBITO CARREGADAS!');
    let btnSubmit = document.getElementById('place_order');

    btnSubmit.setAttribute('type', 'button');
    btnSubmit.removeEventListener('click', lkn_proccess_button, true);
    btnSubmit.addEventListener('click', lkn_proccess_button, true);
};

let lkn_verify_gateway = function () {
    let debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit');

    if (debitPaymethod.checked === false) {
        let btnSubmit = document.getElementById('place_order');
        btnSubmit.setAttribute('type', 'submit');
        btnSubmit.removeEventListener('click', lkn_proccess_button, true);
    }

    console.log('debit paymethod ' + debitPaymethod.checked);
};

document.addEventListener('DOMContentLoaded', function () {
    console.log('Janela totalmente carregada');

    let debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit');
    let paymentBox = document.getElementById('payment');

    debitPaymethod.removeEventListener('click', lkn_load_debit_functions, true);
    debitPaymethod.addEventListener('click', lkn_load_debit_functions, true);

    paymentBox.removeEventListener('click', lkn_verify_gateway, true);
    paymentBox.addEventListener('click', lkn_verify_gateway, true);

    console.log('debit paymethod value: ' + debitPaymethod.checked);
}, false);

function bpmpi_config() {
    return {
        onReady: function () {
            // Evento indicando quando a inicialização do script terminou.
            // document.getElementById('btnSendOrder').removeAttribute('disabled');
            // document.getElementById('btnSendOrder').classList.remove('btnDisabled');
            // document.getElementById('loading-btn-3ds').classList.remove('give-loading-animation');
        },
        onSuccess: function (e) {
            // Cartão elegível para autenticação, e portador autenticou com sucesso.
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

            var formWC = document.getElementById('order_review');
            formWC.submit();
        },
        onFailure: function (e) {
            // Cartão elegível para autenticação, porém o portador finalizou com falha.
            var xid = e.Xid;
            var eci = e.Eci;
            var version = e.Version;
            var referenceId = e.ReferenceId;

            alert('Falha na autenticação verifique as informações e tente novamente');
        },
        onUnenrolled: function (e) {
            // Cartão não elegível para autenticação (não autenticável).
            var xid = e.Xid;
            var eci = e.Eci;
            var version = e.Version;
            var referenceId = e.ReferenceId;

            alert('Cartão Inelegível para Autenticação');
        },
        onDisabled: function () {
            // Loja não requer autenticação do portador (classe "bpmpi_auth" false -> autenticação desabilitada).
            alert('Autenticação desabilitada pela loja');
        },
        onError: function (e) {
            // Erro no processo de autenticação.
            var xid = e.Xid;
            var eci = e.Eci;
            var returnCode = e.ReturnCode;
            var returnMessage = e.ReturnMessage;
            var referenceId = e.ReferenceId;

            alert('Erro no processo de autenticação verifique se todos os campos estão preenchidos corretamente');
        },
        onUnsupportedBrand: function (e) {
            // Bandeira não suportada para autenticação.
            var returnCode = e.ReturnCode;
            var returnMessage = e.ReturnMessage;

            alert('Bandeira não suportada pela autenticação 3DS 2.0 da Cielo');
        },
        // TODO get attributes dinamically or hardcode them
        Environment: 'SDB',
        Debug: true // false
    };
}