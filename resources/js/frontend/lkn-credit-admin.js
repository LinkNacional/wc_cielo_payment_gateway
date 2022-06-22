console.log('ADMIN SCRIPT LOADED')

let input = document.getElementById('woocommerce_lkn_cielo_credit_license');

if (input) {
    input.setAttribute('readonly', '');
    input.addEventListener('click', function() {
        // TODO change redirection to other thing maybe style the setting
        window.open('https://linknacional.com.br');
    }, false);
}
