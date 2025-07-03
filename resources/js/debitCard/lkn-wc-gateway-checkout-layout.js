document.addEventListener('DOMContentLoaded', function () {
  const observer = new MutationObserver(function () {
    const targetElement = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit')
    if (targetElement) {
      // Obtém o elemento label pai
      const parentLabel = targetElement.closest('label')
      if (parentLabel && !parentLabel.classList.contains('lkn-cielo-header-label')) {
        parentLabel.classList.add('lkn-cielo-header-label')
        // Cria um contêiner para os ícones
        if (!parentLabel.querySelector('.lkn-cielo-card-icons')) {
          const iconsContainer = document.createElement('div')
          iconsContainer.setAttribute('class', 'lkn-cielo-card-icons')

          // Adiciona os ícones das bandeiras
          const cardBrands = ['visa', 'mastercard', 'amex', 'elo']
          cardBrands.forEach(brand => {
            const icon = document.createElement('img')
            icon.setAttribute('src', lknCieloCardIcons[brand]) // Usa as URLs fornecidas pelo PHP
            icon.setAttribute('alt', `${brand} logo`)
            icon.setAttribute('style', 'width: 40px; height: auto;')
            iconsContainer.appendChild(icon)
          })

          // Adiciona o contêiner de ícones como o último filho do label pai
          parentLabel.appendChild(iconsContainer)

          const classObserver = new MutationObserver(function (mutationsList) {
            mutationsList.forEach(mutation => {
              if (mutation.attributeName === 'class') {
                const isChecked = parentLabel.classList.contains('wc-block-components-radio-control__option-checked')

                // Aplica o filtro com base na classe
                iconsContainer.style.filter = isChecked ? 'none' : 'grayscale(100%)'

                // Adiciona a classe aos inputs se marcado
                if (isChecked) {
                  const contentContainer = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit__content')
                  if (contentContainer) {
                    contentContainer.classList.add('lkn-cielo-content-container')

                    const idsToCheck = ['lkn_dc_expdate', 'lkn_dc_cvc']

                    // Ajusta a largura do container de inputs
                    const inputs = contentContainer.querySelectorAll('input[type="text"], select')
                    inputs.forEach(element => {
                      const containerInput = element.closest('.wc-block-components-text-input, .wc-block-components-sort-select')
                      if (element.tagName === 'SELECT') {
                        element.classList.add('lkn-cielo-select-input')
                      }
                      if (containerInput) {
                        if (idsToCheck.includes(element.id)) {
                          containerInput.style.width = '48%'
                        } else {
                          containerInput.style.width = '100%'
                        }
                      }
                      element.classList.add('lkn-cielo-custom-input') // Adiciona a classe aos inputs
                    })
                  }
                }
              }
            })
          })

          // Observa mudanças na classe do parentLabel
          classObserver.observe(parentLabel, { attributes: true })

          // Aplica escala de cinza inicialmente se o radio não estiver marcado
          if (!targetElement.checked) {
            iconsContainer.style.filter = 'grayscale(100%)'
          }
        }
      }
    }
  })

  // Configura o observer para observar mudanças no document.body
  observer.observe(document.body, { childList: true, subtree: true })
})
