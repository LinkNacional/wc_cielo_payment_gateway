const settings_creditCard = window.wc.wcSettings.getSetting('lkn_cielo_credit_data', {})
const label_creditCard = window.wp.htmlEntities.decodeEntities(settings_creditCard.title)
const activeInstallment = window.wp.htmlEntities.decodeEntities(settings_creditCard.activeInstallment)
const totalCartCredit = window.wp.htmlEntities.decodeEntities(settings_creditCard.totalCart)
const installmentLimit = window.wp.htmlEntities.decodeEntities(settings_creditCard.installmentLimit)
const translations = settings_creditCard.translations
const nonceCieloCredit = settings_creditCard.nonceCieloCredit
const Content_cieloCredit = props => {
  const [options, setOptions] = window.wp.element.useState([])
  const {
    eventRegistration,
    emitResponse
  } = props
  const {
    onPaymentSetup
  } = eventRegistration
  const [creditObject, setCreditObject] = window.wp.element.useState({
    lkn_ccno: '',
    lkn_cc_expdate: '',
    lkn_cc_cvc: '',
    lkn_cc_installments: '1' // Definir padrão como 1 parcela
  })
  const formatCreditCardNumber = value => {
    if (value?.length > 24) return creditObject.lkn_ccno
    // Remove caracteres não numéricos
    const cleanedValue = value?.replace(/\D/g, '')
    // Adiciona espaços a cada quatro dígitos
    const formattedValue = cleanedValue?.replace(/(.{4})/g, '$1 ')?.trim()
    return formattedValue
  }
  const updateCreditObject = (key, value) => {
    switch (key) {
      case 'lkn_cc_expdate':
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
          setCreditObject({
            ...creditObject,
            [key]: formattedValue
          })
        }
        return
      case 'lkn_cc_cvc':
        if (value.length > 8) return
        break
      default:
        break
    }
    setCreditObject({
      ...creditObject,
      [key]: value
    })
  }
  const wcComponents = window.wc.blocksComponents
  window.wp.element.useEffect(() => {
    const installmentMin = 5
    // Verifica se 'activeInstallment' é 'yes' e o valor total é maior que 10
    if (activeInstallment === 'yes' && totalCartCredit > 10) {
      const maxInstallments = installmentLimit // Limita o parcelamento até 12 vezes, deixei fixo para teste

      for (let index = 1; index <= maxInstallments; index++) {
        const installmentAmount = (totalCartCredit / index).toLocaleString('pt-BR', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        })
        const nextInstallmentAmount = totalCartCredit / index
        if (nextInstallmentAmount < installmentMin) {
          break
        }
        setOptions(prevOptions => [...prevOptions, {
          key: index,
          label: `${index}x de R$ ${installmentAmount} sem juros`
        }])
      }
    } else {
      setOptions(prevOptions => [...prevOptions, {
        key: '1',
        label: `1x de R$ ${totalCartCredit} (à vista)`
      }])
    }
  }, [])
  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      // Verifica se todos os campos do creditObject estão preenchidos
      const allFieldsFilled = Object.values(creditObject).every(field => field.trim() !== '')
      if (allFieldsFilled) {
        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              lkn_ccno: creditObject.lkn_ccno,
              lkn_cc_expdate: creditObject.lkn_cc_expdate,
              lkn_cc_cvc: creditObject.lkn_cc_cvc,
              lkn_cc_installments: creditObject.lkn_cc_installments,
              nonce_lkn_cielo_credit: nonceCieloCredit
            }
          }
        }
      }
      return {
        type: emitResponse.responseTypes.ERROR,
        message: 'Por favor, preencha todos os campos.'
      }
    })

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe()
    }
  }, [creditObject, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup])
  return /* #__PURE__ */React.createElement(React.Fragment, null, /* #__PURE__ */React.createElement('div', null, /* #__PURE__ */React.createElement('h4', null, 'Pagamento processado pela Cielo API 3.0')), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_cardholder_name',
    label: translations.cardHolder,
    value: creditObject.lkn_cardholder_name
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_ccno',
    label: translations.cardNumber,
    value: creditObject.lkn_ccno,
    onChange: value => {
      updateCreditObject('lkn_ccno', formatCreditCardNumber(value))
    }
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_cc_expdate',
    label: translations.cardExpiryDate,
    value: creditObject.lkn_cc_expdate,
    onChange: value => {
      updateCreditObject('lkn_cc_expdate', value)
    }
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_cc_cvc',
    label: translations.securityCode,
    value: creditObject.lkn_cc_cvc,
    onChange: value => {
      updateCreditObject('lkn_cc_cvc', value)
    }
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '20px'
    }
  }), activeInstallment === 'yes' && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_installments',
    label: translations.installments,
    value: creditObject.lkn_cc_installments,
    onChange: event => {
      updateCreditObject('lkn_cc_installments', event.target.value)
    },
    options
  }))
}
const Block_Gateway_Credit_Card = {
  name: 'lkn_cielo_credit',
  label: label_creditCard,
  content: window.wp.element.createElement(Content_cieloCredit),
  edit: window.wp.element.createElement(Content_cieloCredit),
  canMakePayment: () => true,
  ariaLabel: label_creditCard,
  supports: {
    features: settings_creditCard.supports
  }
}
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Credit_Card)
