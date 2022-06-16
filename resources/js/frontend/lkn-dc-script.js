console.log('HELLO WORLD DEBIT');

let lkn_load_debit_functions = function () {
    console.log('FUNÇÕES DE DÉBITO CARREGADAS!');
    let btnSubmit = document.getElementById('place_order');
    let debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit');
    btnSubmit.setAttribute('type', 'button');
    btnSubmit.addEventListener('click', function () {
        console.log('chamar função de débito');

        let cardNumber = document.getElementById('lkn_dcno').value;
        let expDate = document.getElementById('lkn_dc_expdate').value;

        expDate = expDate.split('/');

        document.getElementById('lkn_bpmpi_cardnumber').value = cardNumber.replace('/\D/g', '');
        document.getElementById('lkn_bpmpi_expmonth').value = expDate[0];
        document.getElementById('lkn_bpmpi_expyear').value = expDate[1];

        bpmpi_authenticate();
    }, true);
};

document.addEventListener('DOMContentLoaded', function () {
    console.log('Janela totalmente carregada');

    let debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit');
    let paymentBox = document.getElementById('payment');

    debitPaymethod.addEventListener('click', lkn_load_debit_functions, true);
    paymentBox.addEventListener('click', function () {
        let debitPaymethod = document.getElementById('payment_method_lkn_cielo_debit');

        if(debitPaymethod.checked === false) {
            let btnSubmit = document.getElementById('place_order');
            btnSubmit.setAttribute('type', 'submit');
        }

        console.log('debit paymethod ' + debitPaymethod.checked);
    }, true);

    console.log('debit paymethod value: ' + debitPaymethod.checked);
}, false);

var env = 'SDB'; //SDB or PRD

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

            document.getElementById('cavv').value = cavv;
            document.getElementById('eci').value = eci;
            document.getElementById('ref_id').value = referenceId;
            document.getElementById('version').value = version;
            document.getElementById('xid').value = xid;

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
        Environment: env,
        Debug: true // false
    };
}