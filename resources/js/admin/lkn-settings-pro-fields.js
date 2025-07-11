document.addEventListener('DOMContentLoaded', function () {
  const licenseFIeld = document.querySelector('[id$="_fake_license_field"]')
  if (licenseFIeld) {
    licenseFIeld.style.maxWidth = '400px'

    licenseFIeld.disabled = true

    licenseFIeld.style.backgroundColor = '#f0f0f0' // Cinza claro
    licenseFIeld.style.color = '#999' // Texto mais claro para reforçar que está bloqueado

    // Pegar o componente pai
    const parentElement = licenseFIeld.parentElement
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

  const cardHolderField = document.querySelector('[id$="_fake_cardholder_field"]')
  if (cardHolderField) {
    cardHolderField.disabled = true

    cardHolderField.style.backgroundColor = '#f0f0f0' // Cinza claro
    cardHolderField.style.color = '#999' // Texto mais claro para reforçar que está bloqueado

    // Pegar o componente pai
    const parentElement = cardHolderField.parentElement
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

  const moreFields = document.querySelector('[id$="_fake_and_more_field"]')
  if (moreFields) {
    moreFields.disabled = true

    moreFields.style.backgroundColor = '#f0f0f0' // Cinza claro
    moreFields.style.color = '#999' // Texto mais claro para reforçar que está bloqueado

    // Pegar o componente pai
    const parentElement = moreFields.parentElement
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
