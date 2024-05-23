const settingsDebitCard = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {})
const labelDebitCard = window.wp.htmlEntities.decodeEntities(settingsDebitCard.title)
const accessToken = window.wp.htmlEntities.decodeEntities(settingsDebitCard.accessToken)
const url = window.wp.htmlEntities.decodeEntities(settingsDebitCard.url)
const totalCart = window.wp.htmlEntities.decodeEntities(settingsDebitCard.totalCart)
const orderNumber = window.wp.htmlEntities.decodeEntities(settingsDebitCard.orderNumber)
const dirScript3DS = window.wp.htmlEntities.decodeEntities(settingsDebitCard.dirScript3DS)
const dirScriptConfig3DS = window.wp.htmlEntities.decodeEntities(settingsDebitCard.dirScriptConfig3DS)
const translationsDebit = settingsDebitCard.translations
const nonceCieloDebit = settingsDebitCard.nonceCieloDebit
const Content_cieloDebit = props => {
  const wcComponents = window.wc.blocksComponents
  const {
    eventRegistration,
    emitResponse
  } = props
  const {
    onPaymentSetup
  } = eventRegistration
  const [debitObject, setdebitObject] = window.wp.element.useState({
    lkn_dcno: '',
    lkn_dc_expdate: '',
    lkn_dc_cvc: ''
  })
  const formatDebitCardNumber = value => {
    if (value?.length > 24) return debitObject.lkn_dcno
    // Remove caracteres não numéricos
    const cleanedValue = value?.replace(/\D/g, '')
    // Adiciona espaços a cada quatro dígitos
    const formattedValue = cleanedValue?.replace(/(.{4})/g, '$1 ')?.trim()
    return formattedValue
  }
  const updatedebitObject = (key, value) => {
    switch (key) {
      case 'lkn_dc_expdate':
        if (value.length > 7) return

        // Verifica se o valor é uma data válida (MM/YY)
        const isValidDate = /^\d{2}\/\d{2}$/.test(value)
        if (!isValidDate) {
          // Remove caracteres não numéricos
          const cleanedValue = value?.replace(/\D/g, '')
          let formattedValue = cleanedValue?.replace(/^(.{2})/, '$1 / ')?.trim()

          // Se o tamanho da string for 5, remove o espaço e a barra adicionados anteriormente
          if (formattedValue.length === 4) {
            formattedValue = formattedValue.replace(/\s\//, '')
          }

          // Atualiza o estado
          setdebitObject({
            ...debitObject,
            [key]: formattedValue
          })
        }
        return
      case 'lkn_dc_cvc':
        if (value.length > 8) return
        break
      default:
        break
    }
    setdebitObject({
      ...debitObject,
      [key]: value
    })
  }
  window.wp.element.useEffect(() => {
    const elemento = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
    elemento.style.display = 'none'
    return () => {
      elemento.style.display = ''
    }
  })
  window.wp.element.useEffect(() => {
    const scriptUrl = dirScript3DS
    const existingScript = document.querySelector(`script[src="${scriptUrl}"]`)
    if (!existingScript) {
      const script = document.createElement('script')
      script.src = scriptUrl
      script.async = true
      document.body.appendChild(script)
    }
  }, [])
  window.wp.element.useEffect(() => {
    const scriptUrl = dirScriptConfig3DS
    const existingScript = document.querySelector(`script[src="${scriptUrl}"]`)
    if (!existingScript) {
      const script = document.createElement('script')
      script.src = scriptUrl
      script.async = true
      document.body.appendChild(script)
    }
  }, [])
  const handleButtonClick = () => {
    // Verifica se todos os campos do debitObject estão preenchidos
    const allFieldsFilled = Object.values(debitObject).every(field => field.trim() !== '')

    // Seleciona os elementos dos campos de entrada
    const cardNumberInput = document.getElementById('lkn_dcno')
    const expDateInput = document.getElementById('lkn_dc_expdate')
    const cvvInput = document.getElementById('lkn_dc_cvc')

    // Remove classes de erro e mensagens de validação existentes
    cardNumberInput?.classList.remove('has-error')
    expDateInput?.classList.remove('has-error')
    cvvInput?.classList.remove('has-error')
    if (allFieldsFilled) {
      lknProccessButton()
    } else {
      // Adiciona classes de erro aos campos vazios
      if (debitObject.lkn_dcno.trim() === '') {
        const parentDiv = cardNumberInput?.parentElement
        parentDiv?.classList.add('has-error')
      }
      if (debitObject.lkn_dc_expdate.trim() === '') {
        const parentDiv = expDateInput?.parentElement
        parentDiv?.classList.add('has-error')
      }
      if (debitObject.lkn_dc_cvc.trim() === '') {
        const parentDiv = cvvInput?.parentElement
        parentDiv?.classList.add('has-error')
      }
    }
  }
  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      const Button3dsEnviar = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0].closest('form')
      const paymentCavv = Button3dsEnviar?.getAttribute('data-payment-cavv')
      const paymentEci = Button3dsEnviar?.getAttribute('data-payment-eci')
      const paymentReferenceId = Button3dsEnviar?.getAttribute('data-payment-ref_id')
      const paymentVersion = Button3dsEnviar?.getAttribute('data-payment-version')
      const paymentXid = Button3dsEnviar?.getAttribute('data-payment-xid')
      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
            lkn_dcno: debitObject.lkn_dcno,
            lkn_dc_expdate: debitObject.lkn_dc_expdate,
            lkn_dc_cvc: debitObject.lkn_dc_cvc,
            nonce_lkn_cielo_debit: nonceCieloDebit,
            lkn_cielo_3ds_cavv: paymentCavv,
            lkn_cielo_3ds_eci: paymentEci,
            lkn_cielo_3ds_ref_id: paymentReferenceId,
            lkn_cielo_3ds_version: paymentVersion,
            lkn_cielo_3ds_xid: paymentXid
          }
        }
      }
    })

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe()
    }
  }, [debitObject, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup])
  return /* #__PURE__ */React.createElement(React.Fragment, null, /* #__PURE__ */React.createElement('div', null, /* #__PURE__ */React.createElement('h4', null, 'Pagamento processado pela Cielo API 3.0')), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_cardholder_name_debit',
    label: translations.cardHolder,
    value: creditObject.lkn_cardholder_name_debit
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dcno',
    label: translationsDebit.cardNumber,
    value: debitObject.lkn_dcno,
    onChange: value => {
      updatedebitObject('lkn_dcno', formatDebitCardNumber(value))
    },
    required: true
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_expdate',
    label: translationsDebit.cardExpiryDate,
    value: debitObject.lkn_dc_expdate,
    onChange: value => {
      updatedebitObject('lkn_dc_expdate', value)
    },
    required: true
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_cvc',
    label: translationsDebit.securityCode,
    value: debitObject.lkn_dc_cvc,
    onChange: value => {
      updatedebitObject('lkn_dc_cvc', value)
    },
    required: true
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '30px'
    }
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      display: 'flex',
      justifyContent: 'center'
    }
  }, /* #__PURE__ */React.createElement(wcComponents.Button, {
    id: 'sendOrder',
    onClick: handleButtonClick
  }, /* #__PURE__ */React.createElement('span', null, 'Finalizar pedido'))), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '20px'
    }
  }), /* #__PURE__ */React.createElement('div', null, /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_auth_enabled',
    className: 'bpmpi_auth',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_auth_enabled_notifyonly',
    className: 'bpmpi_auth_notifyonly',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_access_token',
    className: 'bpmpi_accesstoken',
    value: accessToken
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    name: 'lkn_order_number',
    className: 'bpmpi_ordernumber',
    value: orderNumber
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_currency',
    className: 'bpmpi_currency',
    value: 'BRL'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_merchant_url',
    value: url
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    id: 'lkn_cielo_3ds_value',
    name: 'lkn_amount',
    className: 'bpmpi_totalamount',
    value: totalCart
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    name: 'lkn_installments',
    className: 'bpmpi_installments',
    value: '1'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_payment_method',
    className: 'bpmpi_paymentmethod',
    value: 'Debit'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_cardnumber',
    className: 'bpmpi_cardnumber'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_expmonth',
    maxLength: '2',
    name: 'lkn_card_expiry_month',
    className: 'bpmpi_cardexpirationmonth'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_expyear',
    maxLength: '4',
    name: 'lkn_card_expiry_year',
    className: 'bpmpi_cardexpirationyear'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_order_productcode',
    value: 'PHY'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_cavv',
    name: 'lkn_cielo_3ds_cavv',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_eci',
    name: 'lkn_cielo_3ds_eci',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_ref_id',
    name: 'lkn_cielo_3ds_ref_id',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_version',
    name: 'lkn_cielo_3ds_version',
    value: true
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_xid',
    name: 'lkn_cielo_3ds_xid',
    value: true
  })))
}
const Block_Gateway_Debit_Card = {
  name: 'lkn_cielo_debit',
  label: labelDebitCard,
  content: window.wp.element.createElement(Content_cieloDebit),
  edit: window.wp.element.createElement(Content_cieloDebit),
  canMakePayment: () => true,
  ariaLabel: labelDebitCard,
  supports: {
    features: settingsDebitCard.supports
  }
}
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Debit_Card)
