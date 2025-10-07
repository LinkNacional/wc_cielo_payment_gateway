const cieloPixSettings = window.wc.wcSettings.getSetting('lkn_wc_cielo_pix_data', {})
const cieloPix = window.wp.htmlEntities.decodeEntities(cieloPixSettings.title)
const ContentCieloPix = props => {
  const wcComponents = window.wc.blocksComponents
  const [userCpf, setUserCpf] = window.wp.element.useState('')
  const {
    eventRegistration,
    emitResponse
  } = props
  const {
    onPaymentSetup
  } = eventRegistration
  const handleCpfChange = value => {
    const numericValue = value.replace(/\D/g, '')
    setUserCpf(numericValue)
  }
  const handleCpfBlur = () => {
    let maskedValue = ''
    const value = userCpf

    // Determine the mask based on the input value
    if (value.length === 11) {
      // CPF mask
      maskedValue = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
    } else if (value.length === 14) {
      // CNPJ mask
      maskedValue = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5')
    } else {
      // Invalid input length, do not apply any mask
      maskedValue = value
    }
    setUserCpf(maskedValue)
  }
  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            billing_cpf: userCpf
          }
        }
      }
    })

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe()
    }
  }, [userCpf, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup])
  return /* #__PURE__ */React.createElement('div', {
    id: 'LknCieloPixFields'
  }, /* #__PURE__ */React.createElement('p', null, cieloPixSettings.description), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    label: 'CPF/CNPJ',
    maxlength: '18',
    value: userCpf,
    onChange: handleCpfChange,
    onBlur: handleCpfBlur
  }))
}
const blockGatewayCieloPix = {
  name: 'lkn_wc_cielo_pix',
  label: cieloPix,
  content: window.wp.element.createElement(ContentCieloPix),
  edit: window.wp.element.createElement(ContentCieloPix),
  canMakePayment: () => true,
  ariaLabel: cieloPix,
  supports: {
    features: cieloPixSettings.supports
  }
}
window.wc.wcBlocksRegistry.registerPaymentMethod(blockGatewayCieloPix)
