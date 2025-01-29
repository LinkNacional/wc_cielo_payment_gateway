(function ($) {
    $(window).on('load', () => {
        const lknWcGatewayCieloRenewToken = () => {
            const expiresInput = document.querySelector('#expires_in');
            const tokenInput = document.querySelector('.bpmpi_accesstoken');
            if (expiresInput && tokenInput) {
                let expiresInSeconds = parseInt(expiresInput.value);
                setTimeout(() => {
                    $.ajax({
                        url: wpApiSettings.root + 'lknWCGatewayCielo/getAcessToken',
                        contentType: 'application/json',
                        method: 'GET',
                        success: function(response) {
                            expiresInput.value = response.expires_in;
                            tokenInput.value = response.access_token;
                            lknWcGatewayCieloRenewToken();
                        },
                        error: function(error) {
                            console.error('Error getting access token:', error);
                        }
                    });
                }, expiresInSeconds * 1000);
            }
        };

        lknWcGatewayCieloRenewToken();

        let mutationCalled = false;

        const radioInputCieloDebitId = 'radio-control-wc-payment-method-options-lkn_cielo_debit';
        const observer = new MutationObserver((mutationsList) => {
            const radioInputCieloDebit = document.getElementById(radioInputCieloDebitId);
            if (radioInputCieloDebit && radioInputCieloDebit.checked && !mutationCalled) {
                mutationCalled = true;
                lknWcGatewayCieloRenewToken()
            }
        })

        observer.observe(document.body, {
            childList: true,
            subtree: true,
        });
    });
})(jQuery);