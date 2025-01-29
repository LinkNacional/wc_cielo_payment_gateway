const lknWcCieloUrlParams = new URLSearchParams(window.location.search)
const lknWcCieloSection = lknWcCieloUrlParams.get('section')
// Dom loaded

// Dom carregado

document.addEventListener('DOMContentLoaded', function () { 
  const lknWcCieloValidateButton = document.querySelector(`#woocommerce_${lknWcCieloSection}_clear_order_records`)
  const lknWcCieloShowOrderLogs = document.querySelector(`#woocommerce_${lknWcCieloSection}_show_order_logs`)
  const lknWcCieloDebug = document.querySelector(`#woocommerce_${lknWcCieloSection}_debug`)

  function changeShowOrderLogs() {
    if(!lknWcCieloDebug.checked) {
      lknWcCieloShowOrderLogs.checked = false
      lknWcCieloShowOrderLogs.disabled = true
    }
    else{
      lknWcCieloShowOrderLogs.disabled = false
    }
  }

  if(lknWcCieloDebug && lknWcCieloShowOrderLogs) {
    changeShowOrderLogs()
    lknWcCieloDebug.onchange = () => {
      changeShowOrderLogs()
    }
  }


  const lknWcCieloForValidateButton = document.querySelector(`label[for="woocommerce_${lknWcCieloSection}_clear_order_records"]`)
  if (lknWcCieloForValidateButton) {
    lknWcCieloForValidateButton.removeAttribute('for')
  }

  if (lknWcCieloValidateButton) {
    lknWcCieloValidateButton.value = lknWcCieloTranslations.clearLogs
    lknWcCieloValidateButton.addEventListener('click', function () {
      lknWcCieloValidateButton.disabled = true
      lknWcCieloValidateButton.className = lknWcCieloValidateButton.className + ' is-busy'

      if (confirm(lknWcCieloTranslations.alertText)) {
        jQuery.ajax({
          type: 'DELETE',
          url: wpApiSettings.root + 'lknWCGatewayCielo/clearOrderLogs',
          contentType: 'application/json',
          success: function (status) {
            lknWcCieloValidateButton.disabled = false
            location.reload()
          },
          error: function (error) {
            console.error(error)
            lknWcCieloValidateButton.disabled = false
            lknWcCieloValidateButton.className = lknWcCieloValidateButton.className.replace(' is-busy', '')
          }
        })
      }
    })
  }
})