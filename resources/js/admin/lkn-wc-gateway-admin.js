let adminPage = lkn_find_get_parameter('section');

if (adminPage && (adminPage === 'lkn_cielo_credit' || adminPage === 'lkn_cielo_debit')) {
    let wcForm = document.getElementsByClassName('form-table')[0];
    let noticeDiv = document.createElement('div');
    noticeDiv.setAttribute('style', 'padding: 10px 5px;background-color: #fcf9e8;color: #646970;border: solid 1px lightgrey;border-left-color: #dba617;border-left-width: 4px;font-size: 14px;min-width: 625px;margin-top: 10px;');

    noticeDiv.innerHTML = 'Obtenha novas funcionalidades com <a href="https://www.linknacional.com.br/wordpress/woocommerce/cielo/" target="_blank">Cielo API Pro</a>';

    wcForm.append(noticeDiv);
}

function lkn_find_get_parameter(parameterName) {
    let result = null,
        tmp = [];
    location.search
        .substr(1)
        .split("&")
        .forEach(function (item) {
            tmp = item.split("=");
            if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1]);
        });
    return result;
}
