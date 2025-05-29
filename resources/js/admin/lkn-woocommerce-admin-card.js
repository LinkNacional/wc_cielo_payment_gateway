(function ($) {
  let wcFromFound = false
  const observer = new MutationObserver(() => {
    const wcForm = document.querySelector('#lknWcCieloCreditBlocksSettingsLogo')
    const cardDiv = document.querySelector('#lknCieloWoocommerceSettingsCard')

    if (!wcForm) {
      wcFromFound = false
      if (cardDiv) {
        cardDiv.style.display = 'none'
      }
    }

    if (!wcForm || !cardDiv) {
      return
    }

    if (wcForm && cardDiv && !wcFromFound) {
      wcFromFound = true
      cardDiv.style.display = 'block'
      observer.disconnect()
      wcForm.appendChild(cardDiv) // ou insertBefore(cardDiv, wcForm.firstChild) se quiser no topo
    }
  })

  observer.observe(document.body, {
    childList: true,
    subtree: true
  })
})(jQuery)
