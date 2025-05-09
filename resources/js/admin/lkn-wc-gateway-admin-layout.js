(function ($) {
  $(window).load(function () {
    // Selecionar os elementos
    let lknWcCieloCreditBlocksSettingsLayoutMenuVar = 1
    const mainForm = document.querySelector('#mainform')
    const fistH1 = mainForm.querySelector('h1')
    const submitP = mainForm.querySelector('p.submit')
    const tables = mainForm.querySelectorAll('table')

    if (mainform && fistH1 && submitP && tables) {
      // Criar uma nova div
      const newDiv = document.createElement('div')
      newDiv.id = 'lknWcCieloCreditBlocksSettingsLayoutDiv'

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

      // Adicionar a nova div ao mainForm
      mainForm.appendChild(newDiv)

      const subTitles = mainForm.querySelectorAll('.wc-settings-sub-title')
      const descriptionElement = mainForm.querySelector('p')
      const divElement = document.createElement('div')
      if (subTitles && descriptionElement) {
        // Criar a div que irá conter os novos elementos <p>
        divElement.id = 'lknWcCieloCreditBlocksSettingsLayoutMenu'
        aElements = []
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
                  table.style.display = 'block'
                } else {
                  table.style.display = 'none'
                }
                break
              case 2:
                if (index == 2) {
                  table.style.display = 'block'
                } else {
                  table.style.display = 'none'
                }
                break
              case 3:
                if (index == 3) {
                  table.style.display = 'block'
                } else {
                  table.style.display = 'none'
                }
                break
              case 4:
                if (index == 4) {
                  table.style.display = 'block'
                } else {
                  table.style.display = 'none'
                }
                break
            }
          })
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

    $('#lknWcCieloCreditBlocksSettingsLayoutDiv').append(message).css('display', 'block')

    $('.give-rating-link').on('click', function (e) {
      $('#footer-left').html('Obrigado :)').css('text-align', 'center')
    })
  })

  function lknWcCieloValidateMerchantInputs() {
    const urlParams = new URLSearchParams(window.location.search);
    const sectionParam = urlParams.get("section");

    if(sectionParam){
      const merchantIdInput = document.querySelector(`#woocommerce_${sectionParam}_merchant_id`);
      const merchantKeyInput = document.querySelector(`#woocommerce_${sectionParam}_merchant_key`);
      
      if(merchantIdInput && merchantKeyInput){
        function validateInput(input, expectedLength, message) {
            const parent = input.parentElement;
            let errorMsg = parent.querySelector(".validation-error");
    
            if (input.value.length !== expectedLength) {
                if (!errorMsg) {
                    errorMsg = document.createElement("p");
                    errorMsg.className = "validation-error";
                    errorMsg.style.color = "red";
                    errorMsg.style.fontWeight = "500";
                    errorMsg.style.marginTop = "5px";
                    errorMsg.style.fontSize = "small";
                    parent.appendChild(errorMsg);
                }
                errorMsg.textContent = message;
            } else {
                if (errorMsg) errorMsg.remove();
            }
        }
    
        function validateFields() {
            validateInput(merchantIdInput, 36, "O Merchant ID deve ter 36 caracteres.");
            validateInput(merchantKeyInput, 40, "A Merchant Key deve ter 40 caracteres.");
        }
    
        merchantIdInput.addEventListener("input", validateFields);
        merchantKeyInput.addEventListener("input", validateFields);
    
        validateFields(); // Valida ao carregar a página
      }
    }
  }
})(jQuery)
