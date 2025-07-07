document.addEventListener('DOMContentLoaded', function () {
  let debounceTimeout = null
  const observer = new MutationObserver(function () {
    const targetElement = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit')
    if (targetElement) {
      const parentLabel = targetElement.closest('label')
      if (parentLabel && !parentLabel.classList.contains('lkn-cielo-header-label')) {
        parentLabel.classList.add('lkn-cielo-header-label')
        if (!parentLabel.querySelector('.lkn-cielo-card-icons')) {
          const iconsContainer = document.createElement('div')
          iconsContainer.setAttribute('class', 'lkn-cielo-card-icons')

          const cardBrands = ['visa', 'mastercard', 'elo', 'amex', 'other_card']
          cardBrands.forEach(brand => {
            const icon = document.createElement('img')
            icon.setAttribute('src', lknCieloCardIcons[brand])
            icon.setAttribute('alt', `${brand} logo`)
            icon.setAttribute('style', 'width: 40px; height: auto; transition: filter 0.3s ease;')
            iconsContainer.appendChild(icon)
          })

          parentLabel.appendChild(iconsContainer)

          const applyLogic = () => {
            const isChecked = parentLabel.classList.contains('wc-block-components-radio-control__option-checked')
            iconsContainer.style.filter = isChecked ? 'none' : 'grayscale(20%)'

            if (!isChecked) {
              iconsContainer.querySelectorAll('img').forEach(icon => {
                icon.style.filter = 'none'
              })
            }

            parentLabel.style.border = isChecked ? false : 'none'

            if (isChecked) {
              const contentContainer = document.getElementById('radio-control-wc-payment-method-options-lkn_cielo_debit__content')
              if (contentContainer) {
                contentContainer.classList.add('lkn-cielo-content-container')

                const idsToCheck = ['lkn_dc_expdate', 'lkn_dc_cvc', 'lkn_dcno']
                const idsToIcons = {
                  lkn_dcno: lknCieloInputIcons.lock,
                  lkn_dc_expdate: lknCieloInputIcons.calendar,
                  lkn_dc_cvc: lknCieloInputIcons.key
                }
                const inputs = contentContainer.querySelectorAll('input[type="text"]')
                inputs.forEach(element => {
                  const containerInput = element.closest('.wc-block-components-text-input, .wc-block-components-sort-select')
                  if (containerInput) {
                    containerInput.style.position = 'relative' // Define o container como relativo

                    if (element.tagName === 'INPUT' && element.type === 'text') {
                      if (element.id === 'lkn_dcno') {
                        element.addEventListener('input', () => {
                          const value = element.value.trim()
                          let cieloBrand = window.lknCieloBrand?.toLowerCase()

                          if (value.length > 0 && value.length < 7 && window.lknCieloBrand) {
                            clearTimeout(debounceTimeout)
                            cieloBrand = 'lkn_empty'
                            window.lknCieloBrand = null
                          }

                          iconsContainer.querySelectorAll('img').forEach(icon => {
                            if (!icon.src.includes(cieloBrand)) {
                              icon.style.filter = 'grayscale(100%)'
                            }
                          })

                          if (value.length === 0) {
                            clearTimeout(debounceTimeout)
                            iconsContainer.querySelectorAll('img').forEach(icon => {
                              icon.style.filter = 'none'
                            })
                          } else if (value.length >= 7) {
                            // Limpa o timeout anterior para evitar múltiplas chamadas
                            clearTimeout(debounceTimeout)

                            debounceTimeout = setTimeout(() => {
                              let attempts = 0
                              const maxAttempts = 10

                              const intervalCheck = setInterval(() => {
                                const brand = window.lknCieloBrand?.toLowerCase()
                                attempts++

                                if (window.lknCieloBrand) {
                                  iconsContainer.querySelectorAll('img').forEach(icon => {
                                    const iconBrand = icon.getAttribute('alt').replace(' logo', '').toLowerCase()

                                    if (cardBrands.includes(brand)) {
                                      icon.style.filter = iconBrand === brand ? 'none' : 'grayscale(100%)'
                                    } else if (brand && !cardBrands.includes(brand)) {
                                      icon.style.filter = iconBrand === 'other_card' ? 'none' : 'grayscale(100%)'
                                    } else {
                                      icon.style.filter = 'grayscale(100%)' // Se não encontrar, deixa tudo em cinza
                                    }
                                  })

                                  clearInterval(intervalCheck) // Para o intervalo ao encontrar o valor
                                } else {
                                  // Se `window.lknCieloBrand` não existir, mantém todos os ícones em cinza
                                  iconsContainer.querySelectorAll('img').forEach(icon => {
                                    icon.style.filter = 'grayscale(100%)'
                                  })

                                  if (attempts >= maxAttempts) {
                                    clearInterval(intervalCheck) // Para o intervalo após atingir o número máximo de tentativas
                                  }
                                }
                              }, 1000)
                            }, 1500)
                          }
                        })
                      }

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
