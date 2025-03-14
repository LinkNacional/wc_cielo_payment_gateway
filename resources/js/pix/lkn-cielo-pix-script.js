function changeModalVisibility() {
  modal = document.querySelector('#lknCieloShareModal')
  if (modal) {
    if (modal.style.display == 'none' || !modal.style.display) {
      modal.style.display = 'flex'
    } else {
      modal.style.display = 'none'
    }
  }
}

document.addEventListener('DOMContentLoaded', function () {
  lknCieloPixCodeButton = document.querySelector('#lknCieloPixCodeButton')
  if (lknCieloPixCodeButton) {
    const originalButtonText = lknCieloPixCodeButton.textContent
    lknCieloPixCodeButton.addEventListener('click', (e) => {
      e.preventDefault()

      linkInput = document.querySelector('#lknCieloPixCodeInput')
      linkInput.select()
      navigator.clipboard.writeText(linkInput.value)

      // Verifica se o texto do botão é o texto original antes de executar o código
      if (lknCieloPixCodeButton.textContent === originalButtonText) {
        lknCieloPixCodeButton.textContent = phpVariables.copiedText
        setTimeout(function () {
          lknCieloPixCodeButton.textContent = originalButtonText
        }, 1000)
      }
    })
  }

  shareButton = document.querySelector('#lknCieloSharePixCodeButton')
  if (shareButton) {
    shareButton.addEventListener('click', () => {
      changeModalVisibility()
    })
  }

  pixSpan = document.querySelector('#lknCieloPixCodeSpan')
  if (pixSpan && shareButton) {
    spanStyle = getComputedStyle(pixSpan).width
    shareButton.style.width = spanStyle
  }

  shareButtonEmail = document.querySelector('#lknCieloShareButtonIconEmail')
  shareButtonWhatsapp = document.querySelector('#lknCieloShareButtonIconWhatsapp')
  shareButtonTelegram = document.querySelector('#lknCieloShareButtonIconTelegram')
  linkInput = document.querySelector('#lknCieloPixCodeInput')

  if (
    shareButtonEmail &&
    linkInput &&
    shareButtonWhatsapp &&
    shareButtonTelegram
  ) {
    shareButtonWhatsapp.href = 'https://api.whatsapp.com/send?text=' + linkInput.value
    shareButtonTelegram.href = 'https://t.me/share/url?url=' + linkInput.value
    shareButtonEmail.href = 'mailto:?subject=Pix&body=' + linkInput.value

    shareButtonWhatsapp.target = '_blank'
    shareButtonTelegram.target = '_blank'
  }

  // Lógica para alterar o texto do botão caso o tema seja o Divi
  shareButtons = document.querySelectorAll('.lknCieloShareButtonIcon')
  if (shareButtons && (phpVariables.currentTheme == 'Divi' || phpVariables.currentTheme == 'Divi Ecommerce')) {
    shareButtons.forEach(shareButton => {
      shareButton.style.color = '#2EA3F2'
    })
  }
})
