(function ($) {
  $(window).load(function () {
    // Selecionar os elementos
    let lknWcCieloCreditBlocksSettingsLayoutMenuVar = 1
    const mainForm = document.querySelector('#mainform')
    const fistH1 = mainForm.querySelector('h1')
    const submitP = mainForm.querySelector('p.submit')
    const tables = mainForm.querySelectorAll('table')

    const textareaElements = document.querySelectorAll('textarea')
    if (textareaElements.length > 0) {
      textareaElements.forEach(function (textarea) {
        textarea.style.maxWidth = '400px'
      })
    }

    if (mainForm && fistH1 && submitP && tables) {
      // Criar uma nova div
      const newDiv = document.createElement('div')
      newDiv.id = 'lknWcCieloCreditBlocksSettingsLayoutDiv'

      const parentFlexDiv = document.createElement('div')
      parentFlexDiv.id = 'lknWcCieloCreditBlocksSettingsFlexContainer'
      parentFlexDiv.style.display = 'flex'
      parentFlexDiv.style.flexDirection = 'row' // opcional: padrão
      parentFlexDiv.style.gap = '20px'
      parentFlexDiv.style.flexWrap = 'wrap'
      parentFlexDiv.style.position = 'relative'

      const logoDiv = document.createElement('div')
      logoDiv.id = 'lknWcCieloCreditBlocksSettingsLogo'
      logoDiv.style.minWidth = '30%'
      logoDiv.style.height = '100%'
      logoDiv.style.display = 'flex'
      logoDiv.style.justifyContent = 'center'
      logoDiv.style.alignItems = 'start'
      logoDiv.style.backgroundColor = 'transparent'
      logoDiv.style.borderRadius = '10px'
      logoDiv.style.padding = '30px 24px'
      logoDiv.style.position = 'sticky'
      logoDiv.style.top = '110px'

      // Acessar o próximo elemento após fistH1
      let currentElement = fistH1 // Começar com fistH1

      // Mover fistH1 e todos os elementos entre fistH1 e submitP para a nova div
      while (currentElement && currentElement !== submitP.nextElementSibling) {
        const nextElement = currentElement.nextElementSibling // Armazenar o próximo elemento antes de mover
        newDiv.appendChild(currentElement) // Mover o elemento atual para a nova div
        currentElement = nextElement // Atualizar currentElement para o próximo
      }

      // Mover submitP para a nova div
      newDiv.appendChild(submitP)

      // Mover a div existente para dentro da nova div pai
      parentFlexDiv.appendChild(newDiv)
      parentFlexDiv.appendChild(logoDiv)

      // Adicionar a nova estrutura flex ao formulário
      mainForm.appendChild(parentFlexDiv)

      const subTitles = mainForm.querySelectorAll('.wc-settings-sub-title')
      const descriptionElement = mainForm.querySelector('p')
      const divElement = document.createElement('div')
      if (subTitles && descriptionElement) {
        // Criar a div que irá conter os novos elementos <p>
        divElement.id = 'lknWcCieloCreditBlocksSettingsLayoutMenu'
        const aElements = []
        subTitles.forEach((subTitle, index) => {
          // Criar um novo elemento <a> e adicionar o elemento <p> a ele
          const aElement = document.createElement('a')
          aElement.textContent = subTitle.textContent
          aElement.href = '#' + subTitle.textContent
          aElement.className = 'nav-tab'
          aElement.onclick = (event) => {
            lknWcCieloCreditBlocksSettingsLayoutMenuVar = index + 1
            aElements.forEach((pElement, indexP) => {
              if (indexP == index) {
                aElements[index].className = 'nav-tab nav-tab-active'
              } else {
                aElements[indexP].className = 'nav-tab'
              }
            })
            changeLayout()
          }

          // Adicionar o novo elemento <a> à div
          divElement.appendChild(aElement)
          aElements.push(aElement)

          // Remover o subtítulo original
          subTitle.parentNode.removeChild(subTitle)
        })

        aElements[0].className = 'nav-tab nav-tab-active'

        // Inserir a div após o segundo ou primeiro <p>
        const pElements = mainForm.querySelectorAll('p:not([class])')
        const nodeArray = Array.from(pElements)
        const lastNode = nodeArray[nodeArray.length - 1]
        if (lastNode) {
          lastNode.parentNode.insertBefore(divElement, lastNode.nextSibling)
        }

        tables.forEach((table, index) => {
          if (index != 0 && index != 1) {
            table.style.display = 'none'
          }
          table.menuIndex = index
        })

        function changeLayout() {
          tables.forEach((table, index) => {
            switch (lknWcCieloCreditBlocksSettingsLayoutMenuVar) {
              case 1:
                if (index == 0 || index == 1) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 2:
                if (index == 2) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 3:
                if (index == 3) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 4:
                if (index == 4) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 5:
                if (index == 5) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
              case 6:
                if (index == 6) {
                  table.style.display = 'table'
                } else {
                  table.style.display = 'none'
                }
                break
            }
          })
        }

        const select = document.querySelector('select[name^="woocommerce_lkn_"][name$="_env"]')
        console.log(select)
        if (select) {
          const desc = document.createElement('p')
          desc.classList.add('description')
          desc.style.marginTop = '8px'
          select.parentNode.appendChild(desc)

          function updateDesc() {
            const val = select.value
            desc.textContent = lknWcCieloTranslations[val] || ''
          }

          select.addEventListener('change', updateDesc)
          updateDesc()
        }

        document.querySelectorAll('.form-table > tbody > tr').forEach(tr => {
          const label = tr.querySelector('th label')
          const helpTip = tr.querySelector('.woocommerce-help-tip')
          const forminp = tr.querySelector('.forminp')
          const legend = tr.querySelector('.forminp legend')
          const fieldset = tr.querySelector('.forminp fieldset')
          const titledesc = tr.querySelector('.titledesc')

          if (titledesc && label) {
            label.style.fontSize = '20px'
            label.style.color = '#121519'

            titledesc.style.paddingTop = '44px'
          }

          if (label && helpTip && titledesc) {
            const helpText = helpTip.getAttribute('aria-label')
            if (helpText) {
              const p = document.createElement('p')
              p.textContent = helpText
              p.style.margin = '5px 0 10px'
              p.style.fontSize = '13px'
              p.style.color = '#343B45'
              label.after(p)
            }

            helpTip.remove()
          }

          if (forminp && legend && label && fieldset) {
            fieldset.style.display = 'flex'
            fieldset.style.flexDirection = 'column'
            fieldset.style.width = '100%'
            fieldset.style.flex = '1'

            const titleText = label.textContent.trim()

            // Cria divs para header e body
            const headerDiv = document.createElement('div')
            headerDiv.className = 'lkn-header-cart'
            headerDiv.style.minHeight = '44px'

            const bodyDiv = document.createElement('div')
            bodyDiv.className = 'lkn-body-cart'
            bodyDiv.style.display = 'flex'
            bodyDiv.style.flexDirection = 'column'
            bodyDiv.style.alignItems = 'start'
            bodyDiv.style.justifyContent = 'center'
            bodyDiv.style.minHeight = '136px'
            bodyDiv.style.color = '#2C3338'

            // Cria título interno
            const titleInside = document.createElement('div')
            titleInside.textContent = titleText
            titleInside.style.fontWeight = 'bold'
            titleInside.style.fontSize = '16px'
            titleInside.style.margin = '6px 4px'

            // Cria descrição vazia
            const descBlock = document.createElement('div')
            descBlock.className = 'description-title'

            // Cria linha divisória
            const divider = document.createElement('div')
            divider.style.borderTop = '1px solid #ccc'
            divider.style.margin = '8px 0'
            divider.style.width = '100%'

            // Move o legend para o header
            headerDiv.appendChild(legend)
            headerDiv.appendChild(titleInside)
            headerDiv.appendChild(descBlock)
            headerDiv.appendChild(divider)

            // Move os demais elementos do fieldset para body (exceto o legend que já foi removido)
            const childrenToMove = []
            fieldset.childNodes.forEach(node => {
              if (node !== legend) {
                childrenToMove.push(node)
              }
            })

            childrenToMove.forEach(node => bodyDiv.appendChild(node))
            const brElement = bodyDiv.querySelector('br')
            if (brElement) {
              brElement.remove()
            }

            const pElement = bodyDiv.querySelector('p')
            if (!pElement) {
              const p = document.createElement('p')
              p.classList.add('description')
              p.style.marginTop = '8px'
              bodyDiv.appendChild(p)
            }

            const inputElement = bodyDiv.querySelector('input, select, textarea')
            if (inputElement) {
              const titleText = inputElement.getAttribute('data-title-description')
              if (titleText) {
                descBlock.textContent = titleText
              }
            }

            const checkboxLabel = bodyDiv.querySelector('label')

            if (checkboxLabel) {
              const input = checkboxLabel.querySelector('input')
              const nameAttr = input?.getAttribute('name')
              const checked = input?.checked

              // Oculta o checkbox original
              if (input) {
                input.style.display = 'none'
                checkboxLabel.style.display = 'none'

                // Cria os rádios
                const radioYes = document.createElement('label')
                radioYes.innerHTML = `
                <input type="radio" name="${nameAttr}-control" value="1" ${checked ? 'checked' : ''}>
                  ${lknWcCieloTranslationsInput.enable}
                `

                const radioNo = document.createElement('label')
                radioNo.innerHTML = `
                <input type="radio" name="${nameAttr}-control" value="0" ${!checked ? 'checked' : ''}>
                  ${lknWcCieloTranslationsInput.disable}
                `

                const radioYesInput = radioYes.querySelector('input')
                const radioNoInput = radioNo.querySelector('input')

                // Vincula os eventos para controlar o checkbox oculto
                radioYesInput.addEventListener('change', () => {
                  if (radioYesInput.checked) input.checked = true
                })

                radioNoInput.addEventListener('change', () => {
                  if (radioNoInput.checked) input.checked = false
                })

                // Adiciona os radios
                bodyDiv.insertBefore(radioNo, bodyDiv.firstChild)
                bodyDiv.insertBefore(radioYes, bodyDiv.firstChild)
              }
            }

            // Limpa o fieldset e insere os novos containers
            fieldset.innerHTML = ''
            fieldset.appendChild(headerDiv)
            fieldset.appendChild(bodyDiv)

            // Estiliza o forminp
            forminp.style.display = 'flex'
            forminp.style.flexDirection = 'column'
            forminp.style.alignItems = 'flex-start'
            forminp.style.backgroundColor = 'white'
            forminp.style.padding = '10px 30px'
            forminp.style.borderRadius = '4px'
            forminp.style.minHeight = '200px'
            forminp.style.boxSizing = 'border-box'
            forminp.style.width = '100%'
          }
        })
        const installmentSelect = document.querySelector('select[id$="interest_or_discount"]')
        let installmentValue = 'interest'
        let installInput = ''
        if (installmentSelect) {
          installmentValue = installmentSelect.value
        }

        if (installmentValue === 'interest') {
          installInput = document.querySelector('input[id$="installment_interest"]')
        } else if (installmentValue === 'discount') {
          installInput = document.querySelector('input[id$="installment_discount"]')
        }

        if (installInput && installInput.checked) {
          const discountBlocks = document.querySelectorAll(`input[name^="woocommerce_lkn_cielo_"][name$="${installmentValue}"]`)

          const lknBody = installInput.closest('.lkn-body-cart')
          if (lknBody) {
            const pInstallments = lknBody.querySelector('.description')

            if (pInstallments) {
              pInstallments.style.marginBottom = '40px'
            }
          }

          if (discountBlocks.length > 0) {
            discountBlocks.forEach(discountBlock => {
              const trComponent = discountBlock.closest('tr')
              if (!trComponent) return

              const fieldset = trComponent.querySelector('fieldset')
              if (!fieldset) return

              const lknBody = installInput.closest('.lkn-body-cart')
              if (!lknBody) return

              // Evita o erro se fieldset contém o lknBody (estrutura cíclica)
              if (!fieldset.contains(lknBody)) {
                lknBody.appendChild(fieldset)
                trComponent.remove()
              }
            })
          }
        }

        // Caso o formulário tenha um campo inválido, força o click no menu em que o campo inválido está
        mainForm.addEventListener('invalid', function (event) {
          const invalidField = event.target
          if (invalidField) {
            let parentNode = invalidField.parentNode
            while (parentNode && parentNode.tagName !== 'TABLE') {
              parentNode = parentNode.parentNode
            }
            if (parentNode) {
              // Força o click no menu em que o campo inválido está
              aElements[parentNode.menuIndex - 1].click()
            }
          }
        }, true)

        const urlHash = window.location.hash
        if (urlHash) {
          const targetElement = aElements.find(a => a.href.endsWith(urlHash))
          if (targetElement) {
            targetElement.click()
          }
        }
      }

      const hrElement = document.createElement('hr')
      hrElement.style.margin = '2px 0 40px'
      hrElement.style.width = '100%'
      divElement.parentElement.insertBefore(hrElement, divElement.nextSibling)
      lknWcCieloValidateMerchantInputs()
    }

    const message = $('<p id="footer-left" class="alignleft"></p>')

    message.html('Se você gosta do plugin <strong>lkn-wc-gateway-cielo</strong>, deixe-nos uma classificação de <a href="https://wordpress.org/support/plugin/lkn-wc-gateway-cielo/reviews/?filter=5#postform" target="_blank" class="give-rating-link" style="text-decoration:none;" data-rated="Obrigado :)">★★★★★</a>. Leva um minuto e nos ajuda muito. Obrigado antecipadamente!')

    message.css({
      'text-align': 'center',
      padding: '10px',
      'font-size': '13px',
      color: '#666'
    })

    $('#lknWcCieloCreditBlocksSettingsLayoutDiv').append(message).css('display', 'table')

    $('.give-rating-link').on('click', function (e) {
      $('#footer-left').html('Obrigado :)').css('text-align', 'center')
    })
  })

  function lknWcCieloValidateMerchantInputs() {
    const urlParams = new URLSearchParams(window.location.search)
    const sectionParam = urlParams.get('section')

    if (sectionParam) {
      const merchantIdInput = document.querySelector(`#woocommerce_${sectionParam}_merchant_id`)
      const merchantKeyInput = document.querySelector(`#woocommerce_${sectionParam}_merchant_key`)

      if (merchantIdInput && merchantKeyInput) {
        function validateInput(input, expectedLength, message) {
          const parent = input.parentElement
          let errorMsg = parent.querySelector('.validation-error')

          if (input.value.length !== expectedLength) {
            if (!errorMsg) {
              errorMsg = document.createElement('p')
              errorMsg.className = 'validation-error'
              errorMsg.style.color = 'red'
              errorMsg.style.fontWeight = '500'
              errorMsg.style.marginTop = '5px'
              errorMsg.style.fontSize = 'small'
              parent.appendChild(errorMsg)
            }
            errorMsg.textContent = message
          } else {
            if (errorMsg) errorMsg.remove()
          }
        }

        function validateFields() {
          validateInput(merchantIdInput, 36, 'O Merchant ID deve ter 36 caracteres.')
          validateInput(merchantKeyInput, 40, 'A Merchant Key deve ter 40 caracteres.')
        }

        merchantIdInput.addEventListener('input', validateFields)
        merchantKeyInput.addEventListener('input', validateFields)

        validateFields() // Valida ao carregar a página
      }
    }
  }
})(jQuery)
