const lknWcCieloUrlParams = new URLSearchParams(window.location.search)
const lknWcCieloSection = lknWcCieloUrlParams.get('section')
// Dom loaded

// Dom carregado

document.addEventListener('DOMContentLoaded', function () { 
  const lknWcCieloValidateButton = document.querySelector(`#woocommerce_${lknWcCieloSection}_clear_order_records`)
  const lknWcCieloForValidateButton = document.querySelector(`label[for="woocommerce_${lknWcCieloSection}_clear_order_records"]`)
  if (lknWcCieloForValidateButton) {
    lknWcCieloForValidateButton.removeAttribute('for')
  }

  if (lknWcCieloValidateButton) {
    lknWcCieloValidateButton.value = lknWcCieloTranslations.clearLogs
    lknWcCieloValidateButton.addEventListener('click', function () {
      lknWcCieloValidateButton.disabled = true
      lknWcCieloValidateButton.className = lknWcCieloValidateButton.className + ' is-busy'
      let licenseInput = document.querySelector(`#woocommerce_${lknWcCieloSection}_clear_order_records`)

      if (licenseInput && confirm(lknWcCieloTranslations.alertText)) {
        jQuery.ajax({
          type: 'POST',
          url: window.location.origin + '/wp-json/lknWcCieloApiPro/validateLicense',
          contentType: 'application/json',
          data: JSON.stringify({
            license: licenseInput.value
          }),
          success: function (status) {
            lknWcCieloValidateButton.disabled = false
            lknWcCieloValidateButton.className = lknWcCieloValidateButton.className.replace(' is-busy', '')
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