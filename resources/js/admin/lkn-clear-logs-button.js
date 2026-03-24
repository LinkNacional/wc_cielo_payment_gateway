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
          type: 'POST',
          url: lknWcCieloTranslations.ajaxUrl,
          data: {
            action: 'lkn_cielo_clear_order_logs',
            nonce: lknWcCieloTranslations.nonce
          },
          success: function (response) {
            if (response.success) {
              // Feedback visual de sucesso
              lknWcCieloValidateButton.value = '✓ ' + (response.data.message || 'Logs limpos com sucesso!')
              lknWcCieloValidateButton.disabled = false
              lknWcCieloValidateButton.className = lknWcCieloValidateButton.className.replace(' is-busy', '')
              lknWcCieloValidateButton.style.backgroundColor = '#46b450'
              lknWcCieloValidateButton.style.color = 'white'
              
              // Restaurar botão após 3 segundos
              setTimeout(function() {
                lknWcCieloValidateButton.value = lknWcCieloTranslations.clearLogs
                lknWcCieloValidateButton.style.backgroundColor = ''
                lknWcCieloValidateButton.style.color = ''
              }, 3000)
            } else {
              console.error('Error:', response.data)
              alert('Erro: ' + (response.data.message || 'Falha ao limpar logs'))
              lknWcCieloValidateButton.disabled = false
              lknWcCieloValidateButton.className = lknWcCieloValidateButton.className.replace(' is-busy', '')
            }
          },
          error: function (xhr, status, error) {
            console.error('AJAX Error:', error)
            alert('Erro na requisição: ' + error)
            lknWcCieloValidateButton.disabled = false
            lknWcCieloValidateButton.className = lknWcCieloValidateButton.className.replace(' is-busy', '')
          }
        })
      } else {
        // User cancelled, re-enable button
        lknWcCieloValidateButton.disabled = false
        lknWcCieloValidateButton.className = lknWcCieloValidateButton.className.replace(' is-busy', '')
      }
    })
  }
})