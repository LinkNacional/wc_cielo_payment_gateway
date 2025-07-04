document.addEventListener('DOMContentLoaded', function () {
  const observer = new MutationObserver(function () {
    const targetElement = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit')
    if (targetElement) {
      const parentLabel = targetElement.closest('label')
      if (parentLabel && !parentLabel.classList.contains('lkn-cielo-header-label')) {
        parentLabel.classList.add('lkn-cielo-header-label')
        if (!parentLabel.querySelector('.lkn-cielo-card-icons')) {
          const iconsContainer = document.createElement('div')
          iconsContainer.setAttribute('class', 'lkn-cielo-card-icons')

          const cardBrands = ['visa', 'mastercard', 'amex', 'elo']
          cardBrands.forEach(brand => {
            const icon = document.createElement('img')
            icon.setAttribute('src', lknCieloCardIcons[brand])
            icon.setAttribute('alt', `${brand} logo`)
            icon.setAttribute('style', 'width: 40px; height: auto;')
            iconsContainer.appendChild(icon)
          })

          parentLabel.appendChild(iconsContainer)

          const applyLogic = () => {
            const isChecked = parentLabel.classList.contains('wc-block-components-radio-control__option-checked')
            iconsContainer.style.filter = isChecked ? 'none' : 'grayscale(100%)'
            parentLabel.style.border = isChecked ? false : 'none'

            if (isChecked) {
              const contentContainer = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit__content')
              if (contentContainer) {
                contentContainer.classList.add('lkn-cielo-content-container')

                const idsToCheck = ['lkn_dc_expdate', 'lkn_dc_cvc']
                const idsToIcons = {
                  lkn_dcno: lknCieloInputIcons.lock,
                  lkn_dc_expdate: lknCieloInputIcons.calendar,
                  lkn_dc_cvc: lknCieloInputIcons.key
                }
                const inputs = contentContainer.querySelectorAll('input[type="text"], select')
                inputs.forEach(element => {
                  const containerInput = element.closest('.wc-block-components-text-input, .wc-block-components-sort-select')
                  if (containerInput) {
                    containerInput.style.position = 'relative' // Define o container como relativo

                    if (element.tagName === 'INPUT' && element.type === 'text') {
                      const placeholderText = element.getAttribute('placeholder') || ''
                      element.removeAttribute('placeholder') // Remove o placeholder padrão

                      if (idsToIcons[element.id]) { // Verifica se o ID está no objeto idsToIcons
                        const iconElement = document.createElement('img')
                        iconElement.setAttribute('src', idsToIcons[element.id])
                        iconElement.setAttribute('alt', `${element.id} icon`)
                        iconElement.style.position = 'absolute'
                        iconElement.style.right = '10px'
                        iconElement.style.top = '50%'
                        iconElement.style.transform = 'translateY(-50%)'
                        iconElement.style.width = '20px'
                        iconElement.style.height = '20px'

                        containerInput.appendChild(iconElement)
                      }

                      // Cria o elemento para o placeholder animado
                      const animatedPlaceholder = document.createElement('span')
                      animatedPlaceholder.textContent = placeholderText
                      animatedPlaceholder.classList.add('lkn-cielo-animated-placeholder')
                      containerInput.appendChild(animatedPlaceholder)

                      // Adiciona eventos para animação
                      element.addEventListener('focus', () => {
                        animatedPlaceholder.classList.add('active')
                      })

                      element.addEventListener('blur', () => {
                        if (!element.value) {
                          animatedPlaceholder.classList.remove('active')
                        }
                      })
                    }

                    if (idsToCheck.includes(element.id)) {
                      containerInput.style.width = '48%'
                    } else {
                      containerInput.style.width = '100%'
                    }
                  }
                  element.classList.add('lkn-cielo-custom-input')
                })

                const orderButton = document.getElementById('sendOrder')
                if (orderButton) {
                  const divContainer = orderButton.closest('div')
                  if (divContainer) {
                    divContainer.classList.add('lkn-wc-gateway-cielo-order-button')
                  }
                }
              }
            }
          }

          const classObserver = new MutationObserver(function (mutationsList) {
            mutationsList.forEach(mutation => {
              if (mutation.attributeName === 'class') {
                applyLogic()
              }
            })
          })

          classObserver.observe(parentLabel, { attributes: true })
          applyLogic()
        }
      }
    }
  })

  observer.observe(document.body, { childList: true, subtree: true })
})
