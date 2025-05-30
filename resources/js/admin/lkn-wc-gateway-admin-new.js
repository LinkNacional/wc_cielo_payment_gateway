document.addEventListener('DOMContentLoaded', function () {
  const lknCieloAdminPage = lknCieloFindGetParameter('section')

  if (lknCieloAdminPage && (lknCieloAdminPage === 'lkn_cielo_credit' || lknCieloAdminPage === 'lkn_cielo_debit')) {
    let observer = null
    let timeout = null

    function runNotices(targetDiv) {
      if (!targetDiv) return

      // Hospedagem grátis
      if (!document.getElementById('lkn-cielo-hosting-notice')) {
        const lknCieloNoticeDiv = document.createElement('div')
        lknCieloNoticeDiv.setAttribute('style', 'background-color: #fcf9e8;color: #646970;border: solid 1px #d3d3d3;border-left: 4px #dba617 solid;font-size: 16px;margin-top: 10px;box-sizing: border-box;width: 100%;max-width: 370px;')
        lknCieloNoticeDiv.setAttribute('id', 'lkn-cielo-hosting-notice')
        lknCieloNoticeDiv.innerHTML = '<a  href="https://cliente.linknacional.com.br/solicitar/wordpress-woo-gratis/" target="_blank" style="text-decoration:none; display: block;padding: 10px;">Parabéns! Você ganhou uma hospedagem WooCommerce grátis por 12 meses. Solicite agora!</a>'
        targetDiv.append(lknCieloNoticeDiv)
      }

      // PRO
      if (!document.getElementById('lkn-cielo-pro-notice')) {
        const lknCieloProDiv = document.createElement('div')
        lknCieloProDiv.setAttribute('style', 'padding: 10px 5px;background-color: #fcf9e8;color: #646970;border: solid 1px lightgrey;border-left-color: #dba617;border-left-width: 4px;font-size: 14px;margin-top: 10px;width: 100%;box-sizing: border-box;max-width: 370px;')
        lknCieloProDiv.setAttribute('id', 'lkn-cielo-pro-notice')
        lknCieloProDiv.innerHTML = '<div style="font-size: 21px;padding: 6px 0px 10px 0px;">Obtenha novas funcionalidades com Cielo API Pro</div>' +
          '<a href="https://www.linknacional.com.br/wordpress/woocommerce/cielo/" target="_blank">Conheça e compre o plugin PRO</a>' +
          '<ul style="margin: 10px 28px;list-style: disclosure-closed;">' +
          '<li>Integração com PIX Cielo</li>' +
          '<li>Captura manual da transação/pedido</li>' +
          '<li>Ferramenta de reembolso total ou parcial</li>' +
          '<li>Compatibilidade com pagamentos feitos em moedas internacionais</li>' +
          '<li>Ajustes da taxa de juros de acordo com a parcela</li>' +
          '<li>Habilita o parcelamento em até 18x (Visa, Elo, Amex, Hipercard, Mastercard)</li>' +
          '<li>Configuração de quantidade máxima de parcelas</li>' +
          '<li>Compatibilidade com a opção de Checkout do Elementor para WooCommerce</li>' +
          '</ul>'
        targetDiv.append(lknCieloProDiv)
      }
    }

    function stopObserver() {
      if (observer) observer.disconnect()
      if (timeout) clearTimeout(timeout)
    }

    observer = new MutationObserver(function () {
      const logoDiv = document.getElementById('lknBlocksSettingsLogo')
      if (logoDiv) {
        runNotices(logoDiv)
        stopObserver()
      }
    })

    observer.observe(document.body, { childList: true, subtree: true })

    timeout = setTimeout(function () {
      const logoDiv = document.getElementById('lknBlocksSettingsLogo')
      if (logoDiv) {
        runNotices(logoDiv)
      } else {
        // Se não encontrou, usa o mainform para ambos
        const mainForm = document.getElementById('mainform')
        if (mainForm) runNotices(mainForm)
      }
      stopObserver()
    }, 7000)
  }

  function lknCieloFindGetParameter(parameterName) {
    let result = null
    let tmp = []
    location.search
      .substr(1)
      .split('&')
      .forEach(function (item) {
        tmp = item.split('=')
        if (tmp[0] === parameterName) result = decodeURIComponent(tmp[1])
      })
    return result
  }
})
