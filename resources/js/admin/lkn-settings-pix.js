document.addEventListener('DOMContentLoaded', function () {
  // Pix description
  const pixDescription = document.getElementById('woocommerce_lkn_wc_cielo_pix_description')
  if (pixDescription) {
    pixDescription.style.maxWidth = '400px'

    pixDescription.disabled = true

    pixDescription.style.backgroundColor = '#f0f0f0' // Cinza claro
    pixDescription.style.color = '#999' // Texto mais claro para reforçar que está bloqueado

    // Pegar o componente pai
    const parentElement = pixDescription.parentElement
    if (parentElement) {
      // Criar o texto informativo
      const proText = document.createElement('span')
      proText.textContent = lknCieloProSettingsVars.proOnly
      proText.style.color = 'red'
      proText.style.fontWeight = 'bold'

      // Adicionar o texto ao final do componente pai
      parentElement.appendChild(proText)
    }
  }

  // Pix payment complete status
  const pixPaymentStatus = document.getElementById('woocommerce_lkn_wc_cielo_pix_payment_complete_status')
  if (pixPaymentStatus) {
    pixPaymentStatus.disabled = true

    pixPaymentStatus.style.backgroundColor = '#f0f0f0' // Cinza claro
    pixPaymentStatus.style.color = '#999' // Texto mais claro para reforçar que está bloqueado

    // Pegar o componente pai
    const parentElement = pixPaymentStatus.parentElement
    if (parentElement) {
      // Criar o texto informativo
      const proText = document.createElement('span')
      proText.textContent = lknCieloProSettingsVars.proOnly
      proText.style.color = 'red'
      proText.style.fontWeight = 'bold'

      // Adicionar o texto ao final do componente pai
      parentElement.appendChild(proText)
    }
  }

  // Pix layout
  const pixLayout = document.getElementById('woocommerce_lkn_wc_cielo_pix_pix_layout')
  if (pixLayout) {
    pixLayout.disabled = true

    pixLayout.style.backgroundColor = '#f0f0f0' // Cinza claro
    pixLayout.style.color = '#999' // Texto mais claro para reforçar que está bloqueado

    // Pegar o componente pai
    const parentElement = pixLayout.parentElement
    if (parentElement) {
      // Criar o texto informativo
      const proText = document.createElement('span')
      proText.textContent = lknCieloProSettingsVars.proOnly
      proText.style.color = 'red'
      proText.style.fontWeight = 'bold'

      // Adicionar o texto ao final do componente pai
      parentElement.appendChild(proText)
    }
  }

  // Pix location
  const pixLocation = document.getElementById('woocommerce_lkn_wc_cielo_pix_layout_location')
  if (pixLocation) {
    pixLocation.disabled = true

    pixLocation.style.backgroundColor = '#f0f0f0' // Cinza claro
    pixLocation.style.color = '#999' // Texto mais claro para reforçar que está bloqueado

    // Pegar o componente pai
    const parentElement = pixLocation.parentElement
    if (parentElement) {
      // Criar o texto informativo
      const proText = document.createElement('span')
      proText.textContent = lknCieloProSettingsVars.proOnly
      proText.style.color = 'red'
      proText.style.fontWeight = 'bold'

      // Adicionar o texto ao final do componente pai
      parentElement.appendChild(proText)
    }
  }
})
