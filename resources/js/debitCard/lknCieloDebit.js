import React from 'react'
import Cards from 'react-credit-cards'
import 'react-credit-cards/es/styles-compiled.css'
const lknDCsettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {})
const lknDCLabelCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.title)
const lknDCDescriptionCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.description)
const lknDCAccessTokenCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.accessToken)
const lknDCAccessTokenExpiration = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.accessTokenExpiration)
const lknDCshowCard = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.showCard)
const lknDCActiveInstallmentCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.activeInstallment)
const lknDCUrlCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.url)
const lknDCTotalCartCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.totalCart)
const lknDCOrderNumberCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.orderNumber)
const lknDCDirScript3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScript3DS)
const lknDCInstallmentLimitCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installmentLimit)
const lknDCInstallmentMinAmount = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installmentMinAmount)
const lknCC3DSinstallmentsCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.installments)
const lknDCDirScriptConfig3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScriptConfig3DS)
const lknDCTranslationsDebitCielo = lknDCsettingsCielo.translations
const lknDCNonceCieloDebit = lknDCsettingsCielo.nonceCieloDebit
const lknDCTranslationsCielo = lknDCsettingsCielo.translations
const lknDCBec = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.bec)
const lknDCClientIp = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.client_ip)
const lknDCUserGuest = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.user_guest)
const lknDCAuthMethod = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.authentication_method)
const lknDCClient = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.client)
const lknDCHideCheckoutButton = () => {
  const lknDCElement = document.querySelectorAll('.wc-block-components-checkout-place-order-button')
  if (lknDCElement && lknDCElement[0]) {
    lknDCElement[0].style.display = 'none'
  }
}
const lknDCInitCieloPaymentForm = () => {
  document.addEventListener('DOMContentLoaded', lknDCHideCheckoutButton)
  lknDCHideCheckoutButton()

  // Load Cielo 3DS BpMPI Script
  const scriptUrlBpmpi = lknDCDirScript3DSCielo
  const existingScriptBpmpi = document.querySelector(`script[src="${scriptUrlBpmpi}"]`)
  if (!existingScriptBpmpi) {
    const scriptBpmpi = document.createElement('script')
    scriptBpmpi.src = scriptUrlBpmpi
    scriptBpmpi.async = true
    document.body.appendChild(scriptBpmpi)
  }

  // Load Cielo 3DS Config Script
  const scriptUrl = lknDCDirScriptConfig3DSCielo
  const existingScript = document.querySelector(`script[src="${scriptUrl}"]`)
  if (!existingScript) {
    const script = document.createElement('script')
    script.src = scriptUrl
    script.async = true
    document.body.appendChild(script)
  }
}
const lknDCContentCielo = props => {
  const wcComponents = window.wc.blocksComponents
  const {
    eventRegistration,
    emitResponse
  } = props
  const {
    onPaymentSetup
  } = eventRegistration
  const [options, setOptions] = window.wp.element.useState([])
  const [isLoadingOptions, setIsLoadingOptions] = window.wp.element.useState(true)
  const [cardBinState, setCardBinState] = window.wp.element.useState(0)
  const [cardTypeOptions, setCardTypeOptions] = window.wp.element.useState([{
    key: 'Credit',
    label: lknDCTranslationsCielo.creditCard
  }, {
    key: 'Debit',
    label: lknDCTranslationsCielo.debitCard
  }])
  const [debitObject, setdebitObject] = window.wp.element.useState({
    lkn_dc_cardholder_name: '',
    lkn_dcno: '',
    lkn_dc_expdate: '',
    lkn_dc_cvc: '',
    lkn_cc_dc_installments: '1',
    // Definir padrão como 1 parcela
    lkn_cc_type: 'Credit'
  })
  const [focus, setFocus] = window.wp.element.useState('')

  // Nova função para buscar dados do carrinho diretamente
  const fetchCartData = async () => {
    try {
      const response = await fetch('/wp-json/wc/store/v1/cart', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        }
      })

      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`)
      }

      const cartData = await response.json()

      if (cartData && cartData.totals) {
        const totals = cartData.totals

        // Separa os valores para o cálculo correto:
        // Base para cálculo de parcelas = Subtotal + Shipping (sem fees, pois fees são calculadas por parcela)
        // Tax é aplicada no final, após o cálculo das parcelas
        const subtotal = parseFloat(totals.total_items) || 0
        const shipping = parseFloat(totals.total_shipping) || 0
        const fees = parseFloat(totals.total_fees) || 0 // Não entra no cálculo base
        const tax = parseFloat(totals.total_tax) || 0

        // Total base para cálculo de parcelas (sem fees e sem tax)
        const baseTotal = (subtotal + shipping) / 100 // WooCommerce usa centavos

        // Tax para ser aplicada no final
        const taxAmount = tax / 100

        // Atualiza as opções de parcelamento com o total base e tax separados
        if (baseTotal > 0) {
          setOptions([]) // Limpa opções antigas
          calculateInstallments(baseTotal, taxAmount)
        }

        return {
          subtotal: subtotal / 100,
          shipping: shipping / 100,
          fees: fees / 100,
          tax: taxAmount,
          baseTotal,
          taxAmount
        }
      }
    } catch (error) {
      console.error('Erro ao buscar dados do carrinho:', error)
      return null
    }
  }

  // Função para executar múltiplas requisições com delay (para o loading na primeira resposta)
  const fetchCartDataWithRetries = async (retries = 4, delay = 1500, onFirstData = null) => {
    let lastCartData = null
    let firstDataSent = false

    for (let i = 0; i < retries; i++) {
      const cartData = await fetchCartData()

      if (cartData) {
        lastCartData = cartData

        // Na primeira vez que obtém dados, chama o callback para parar o loading
        if (!firstDataSent && onFirstData) {
          onFirstData(cartData)
          firstDataSent = true
        }
      }      // Sempre aguarda o delay (exceto na última tentativa)
      if (i < retries - 1) {
        await new Promise(resolve => setTimeout(resolve, delay))
      }
    }

    return lastCartData
  }
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
      case 'lkn_dc_cardholder_name':
        // Atualiza o estado
        setdebitObject({
          ...debitObject,
          [key]: value
        })
        break
      case 'lkn_dc_expdate':
        if (value.length > 7) return

        // Verifica se o valor é uma data válida (MM/YY)
        const isValidDate = /^\d{2}\/\d{2}$/.test(value)
        if (!isValidDate) {
          // Remove caracteres não numéricos
          const cleanedValue = value?.replace(/\D/g, '')
          let formattedValue = cleanedValue?.replace(/^(.{2})(.{2})$/, '$1 / $2')

          // Se o tamanho da string for 6 (MMYYYY), formate para MM / YY
          if (cleanedValue.length === 6) {
            formattedValue = cleanedValue?.replace(/^(.{2})(.{2})(.{2})$/, '$1 / $3')
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
      case 'lkn_dcno':
        if (value.length > 7) {
          const cardBin = value.replace(' ', '').substring(0, 6)
          const url = wpApiSettings.root + 'lknWCGatewayCielo/checkCard?cardbin=' + cardBin
          if (cardBin !== cardBinState) {
            setCardBinState(cardBin) // Mova o setCardBinState para antes da requisição

            fetch(url, {
              method: 'GET',
              headers: {
                Accept: 'application/json'
              }
            }).then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText)
              }

              return response.json()
            }).then(data => {
              if (data.CardType == 'Crédito') {
                setCardTypeOptions([{
                  key: 'Credit',
                  label: lknDCTranslationsCielo.creditCard
                }])
                setdebitObject(prevState => ({
                  ...prevState,
                  lkn_cc_type: 'Credit'
                }))
              } else if (data.CardType == 'Débito') {
                setCardTypeOptions([{
                  key: 'Debit',
                  label: lknDCTranslationsCielo.debitCard
                }])
                setdebitObject(prevState => ({
                  ...prevState,
                  lkn_cc_type: 'Debit'
                }))
              } else {
                setCardTypeOptions([{
                  key: 'Credit',
                  label: lknDCTranslationsCielo.creditCard
                }, {
                  key: 'Debit',
                  label: lknDCTranslationsCielo.debitCard
                }])
              }
            }).catch(error => {
              console.error('Erro:', error)
            })
          }
          setdebitObject(prevState => ({
            ...prevState,
            lkn_dcno: value
          }))
        }
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
    const lknDCElement = document.querySelectorAll('.wc-block-components-checkout-place-order-button')
    if (lknDCElement && lknDCElement[0]) {
      // Hides the checkout button on cielo debit select
      lknDCElement[0].style.display = 'none'

      // Shows the checkout button on payment change
      return () => {
        lknDCElement[0].style.display = ''
      }
    }
  })
  const handleButtonClick = () => {
    // Verifica se todos os campos do debitObject estão preenchidos
    const allFieldsFilled = Object.keys(debitObject).filter(key => key !== 'lkn_dc_cardholder_name' && key !== 'lkn_save_debit_credit_card').every(key => debitObject[key].trim() !== '')

    // Seleciona os lknDCElements dos campos de entrada
    const cardNumberInput = document.getElementById('lkn_dcno')
    const expDateInput = document.getElementById('lkn_dc_expdate')
    const cvvInput = document.getElementById('lkn_dc_cvc')
    const cardHolder = document.getElementById('lkn_dc_cardholder_name')

    // Remove classes de erro e mensagens de validação existentes
    cardNumberInput?.classList.remove('has-error')
    expDateInput?.classList.remove('has-error')
    cvvInput?.classList.remove('has-error')
    cardHolder?.classList.remove('has-error')
    if (allFieldsFilled) {
      lknDCProccessButton()
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
    lknDCInitCieloPaymentForm()
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
            // TODO Corrigir campos faltando
            lkn_dcno: debitObject.lkn_dcno,
            lkn_dc_cardholder_name: debitObject.lkn_dc_cardholder_name,
            lkn_dc_expdate: debitObject.lkn_dc_expdate,
            lkn_dc_cvc: debitObject.lkn_dc_cvc,
            nonce_lkn_cielo_debit: lknDCNonceCieloDebit,
            lkn_cielo_3ds_cavv: paymentCavv,
            lkn_cielo_3ds_eci: paymentEci,
            lkn_cielo_3ds_ref_id: paymentReferenceId,
            lkn_cielo_3ds_version: paymentVersion,
            lkn_cielo_3ds_xid: paymentXid,
            lkn_cc_dc_installments: debitObject.lkn_cc_dc_installments,
            lkn_cc_type: debitObject.lkn_cc_type
          }
        }
      }
    })

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe()
    }
  }, [debitObject, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup])
  // Função para recalcular as opções de parcelas com os dados atuais do carrinho
  const recalculateInstallments = async (useRetries = false) => {
    // Ativa o loading e limpa as opções atuais
    setIsLoadingOptions(true)
    setOptions([])

    let cartData
    if (useRetries) {
      // Para mudanças dinâmicas, usa retries com callback para parar loading rapidamente
      let firstDataProcessed = false

      cartData = await fetchCartDataWithRetries(3, 1000, (firstData) => {
        // Para o loading na primeira resposta válida
        if (!firstDataProcessed) {
          calculateInstallments(firstData.baseTotal, firstData.taxAmount)
          setIsLoadingOptions(false)
          firstDataProcessed = true
        }
      })

      // Se obteve dados finais diferentes, atualiza silenciosamente
      if (cartData && firstDataProcessed) {
        calculateInstallments(cartData.baseTotal, cartData.taxAmount)
      }
    } else {
      // Uma única tentativa para mudanças rápidas
      cartData = await fetchCartData()

      if (cartData) {
        calculateInstallments(cartData.baseTotal, cartData.taxAmount)
      }      // Desativa o loading após processar mudanças rápidas
      setIsLoadingOptions(false)
    }
  }

  const calculateInstallments = (lknDCTotalCartCielo, taxAmount = 0) => {
    const installmentMin = parseFloat(lknDCInstallmentMinAmount)
    const newOptions = [] // Array local para construir as opções

    // Verifica se 'lknDCActiveInstallmentCielo' é 'yes' e o valor total é maior que 10
    if (lknDCActiveInstallmentCielo === 'yes' && lknDCTotalCartCielo > 10) {
      const maxInstallments = lknDCInstallmentLimitCielo // Limita o parcelamento

      for (let index = 1; index <= maxInstallments; index++) {
        // Valor base para cálculo (subtotal + shipping, sem fees)
        let baseValue = parseFloat(lknDCTotalCartCielo)
        let nextInstallmentAmount = baseValue / index

        // Verifica se atende o valor mínimo antes de aplicar descontos/juros
        if (nextInstallmentAmount < installmentMin) {
          break
        }

        let formatedInterest = false
        let typeText = ''

        // Busca a configuração específica para esta parcela no array installments
        const installmentConfig = lknDCsettingsCielo.installments.find(inst => inst.id === index)

        // Se o plugin PRO não está válido, não aplica desconto nem juros
        if (lknCieloDebitConfig.isProPluginValid && installmentConfig) {
          const interestOrDiscount = lknDCsettingsCielo.interestOrDiscount
          const interestPercent = parseFloat(installmentConfig.interest)

          if (interestOrDiscount === 'discount' && lknDCsettingsCielo.activeDiscount == "yes") {
            // Fórmula correta: (((subtotal + frete) * desconto) + tax) / parcelas
            const discountMultiplier = 1 - (interestPercent / 100)
            const baseWithDiscount = baseValue * discountMultiplier
            nextInstallmentAmount = (baseWithDiscount + taxAmount) / index
            formatedInterest = new Intl.NumberFormat('pt-br', {
              style: 'currency',
              currency: 'BRL'
            }).format(nextInstallmentAmount)
            typeText = ` (${interestPercent}% de desconto)`
          } else if (interestOrDiscount === "interest" && lknDCsettingsCielo.activeInstallment == "yes") {
            // Fórmula correta: (((subtotal + frete) * juros) + tax) / parcelas
            const interestMultiplier = 1 + (interestPercent / 100)
            const baseWithInterest = baseValue * interestMultiplier
            nextInstallmentAmount = (baseWithInterest + taxAmount) / index
            formatedInterest = new Intl.NumberFormat('pt-br', {
              style: 'currency',
              currency: 'BRL'
            }).format(nextInstallmentAmount)
            typeText = ` (${interestPercent}% de juros)`
          }
        }

        if (formatedInterest) {
          newOptions.push({
            key: index,
            label: `${index}x de ${formatedInterest}${typeText}`
          })
        } else {
          // Sem juros/desconto: (baseValue + tax) / parcelas
          const totalWithTax = baseValue + taxAmount
          const finalAmount = totalWithTax / index
          const installmentAmount = finalAmount.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          })

          // Se o plugin PRO está válido, usa as configurações originais
          if (lknCieloDebitConfig.isProPluginValid) {
            if (lknDCsettingsCielo.activeDiscount == 'yes') {
              newOptions.push({
                key: index,
                label: `${index}x de R$ ${installmentAmount}${lknDCsettingsCielo.interestOrDiscount == 'interest' ? ' sem juros' : ' sem desconto'}`
              })
            } else {
              newOptions.push({
                key: index,
                label: `${index}x de R$ ${installmentAmount} sem juros`
              })
            }
          } else {
            // Se o plugin PRO não está válido, mostra apenas o valor sem texto adicional
            newOptions.push({
              key: index,
              label: `${index}x de R$ ${installmentAmount}`
            })
          }
        }
      }
    } else {
      // À vista: baseValue + tax
      const totalAmount = (lknDCTotalCartCielo + taxAmount).toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
      newOptions.push({
        key: '1',
        label: `1x de R$ ${totalAmount} (à vista)`
      })
    }

    // Define todas as opções de uma vez
    setOptions(newOptions)
  }
  window.wp.element.useEffect(() => {
    // Executa a primeira busca no carregamento
    const loadInitialData = async () => {
      const finalCartData = await fetchCartDataWithRetries(4, 1500, (firstData) => {
        // Callback chamado na primeira resposta - para o loading imediatamente
        calculateInstallments(firstData.baseTotal, firstData.taxAmount)
        setIsLoadingOptions(false)
      })

      // Se os dados finais são diferentes dos primeiros, atualiza silenciosamente
      if (finalCartData && !isLoadingOptions) {
        calculateInstallments(finalCartData.baseTotal, finalCartData.taxAmount)
      }
    }

    loadInitialData()

    // Intercepta as requisições para detectar mudanças
    const originalFetch = window.fetch

    window.fetch = async (...args) => {
      const [resource, config] = args
      const url = typeof resource === 'string' ? resource : resource.url

      // Detecta mudanças no carrinho que requerem recálculo
      const shouldRecalculate = url && (
        url.includes('/wp-json/wc/store/v1/cart/select-shipping-rate')
      )

      const response = await originalFetch.apply(window, args)

      if (shouldRecalculate) {
        // Aguarda um pouco para o WooCommerce processar a mudança
        setTimeout(() => {
          recalculateInstallments(true) // usa retry leve para mudanças do carrinho
        }, 800) // 800ms de delay para dar tempo do WooCommerce processar
      }

      return response
    }

    // Cleanup: restaura o fetch original quando o componente é desmontado
    return () => {
      window.fetch = originalFetch
    }
  }, [])
  return /* #__PURE__ */React.createElement(React.Fragment, null, lknDCshowCard !== 'no' && /* #__PURE__ */React.createElement(Cards, {
    number: debitObject.lkn_dcno,
    name: debitObject.lkn_dc_cardholder_name,
    expiry: debitObject.lkn_dc_expdate.replace(/\s+/g, ''),
    cvc: debitObject.lkn_dc_cvc,
    placeholders: {
      name: 'NOME',
      expiry: 'MM/ANO',
      cvc: 'CVC',
      number: '•••• •••• •••• ••••'
    },
    locale: {
      valid: 'VÁLIDO ATÉ'
    },
    focused: focus
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_cardholder_name',
    label: lknDCTranslationsDebitCielo.cardHolder,
    value: debitObject.lkn_dc_cardholder_name,
    autocomplete: 'cc-name',
    onChange: value => {
      updatedebitObject('lkn_dc_cardholder_name', value)
    },
    required: true,
    onFocus: () => setFocus('name')
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dcno',
    label: lknDCTranslationsDebitCielo.cardNumber,
    value: debitObject.lkn_dcno,
    autocomplete: 'cc-number',
    onChange: value => {
      updatedebitObject('lkn_dcno', formatDebitCardNumber(value))
    },
    required: true,
    onFocus: () => setFocus('number')
  }), lknCieloDebitConfig.isProPluginValid && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_type',
    value: debitObject.lkn_cc_type,
    className: 'lkn-credit-debit-card-type-select',
    onChange: event => {
      updatedebitObject('lkn_cc_type', event.target.value)
    },
    options: cardTypeOptions
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_expdate',
    label: lknDCTranslationsDebitCielo.cardExpiryDate,
    value: debitObject.lkn_dc_expdate,
    autocomplete: 'cc-exp',
    onChange: value => {
      updatedebitObject('lkn_dc_expdate', value)
    },
    required: true,
    onFocus: () => setFocus('expiry')
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_dc_cvc',
    label: lknDCTranslationsDebitCielo.securityCode,
    value: debitObject.lkn_dc_cvc,
    autocomplete: 'cc-csc',
    onChange: value => {
      updatedebitObject('lkn_dc_cvc', value)
    },
    required: true,
    onFocus: () => setFocus('cvc')
  }), !lknCieloDebitConfig.isProPluginValid && /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '20px',
      width: '100%'
    }
  }), !lknCieloDebitConfig.isProPluginValid && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_type',
    label: lknDCTranslationsCielo.cardType,
    value: debitObject.lkn_cc_type,
    className: 'lkn-credit-debit-card-type-select',
    onChange: event => {
      updatedebitObject('lkn_cc_type', event.target.value)
    },
    options: cardTypeOptions
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '10px',
      width: '100%'
    }
  }), lknDCActiveInstallmentCielo === 'yes' && debitObject.lkn_cc_type == 'Credit' && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_dc_installments',
    label: lknDCTranslationsCielo.installments,
    value: debitObject.lkn_cc_dc_installments,
    className: `lkn_cielo_debit_select lkn-cielo-credit-debit-custom-select ${isLoadingOptions ? 'loading-options' : ''}`,
    disabled: isLoadingOptions,
    style: isLoadingOptions ? { opacity: 0.7, cursor: 'wait' } : {},
    onChange: event => {
      const installmentValue = event.target.value

      // Ignora se está selecionando a opção de loading
      if (installmentValue === 'loading') return

      updatedebitObject('lkn_cc_dc_installments', installmentValue)

      // Faz a requisição AJAX para atualizar as fees quando a parcela mudar
      if (window.lknCieloDebitConfig) {
        const formData = new FormData()
        formData.append('action', 'lkn_update_payment_fees')
        formData.append('payment_method', 'lkn_cielo_debit')
        formData.append('installment', installmentValue)
        formData.append('nonce', window.lknCieloDebitConfig.fees_nonce)

        fetch(window.lknCieloDebitConfig.ajax_url, {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            // Após a resposta AJAX, força recálculo do carrinho
            if (window.wp && window.wp.data) {
              window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
            }

            // Aguarda um pouco e depois recalcula as parcelas
            setTimeout(() => {
              recalculateInstallments()
            }, 500)
          })
          .catch(error => {
            // Mesmo em caso de erro, força o recálculo para manter consistência
            if (window.wp && window.wp.data) {
              window.wp.data.dispatch('wc/store/cart').invalidateResolutionForStore()
            }

            setTimeout(() => {
              recalculateInstallments()
            }, 500)
          })
      }
    },
    options: isLoadingOptions ? [{ key: 'loading', label: '🔄 Calculando parcelas...' }] : options
  }), lknDCActiveInstallmentCielo === 'cielo' && /* #__PURE__ */React.createElement(wcComponents.CheckboxControl, {
    id: 'lkn_save_debit_credit_card',
    label: 'Salvar cartão para compra segura e rápida.',
    checked: debitObject.lkn_save_debit_credit_card || false,
    onChange: (isChecked) => {
      updatedebitObject('lkn_save_debit_credit_card', isChecked)
    }
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '25px',
      width: '100%'
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
      margin: '2px',
      width: '100%'
    }
  }), /* #__PURE__ */React.createElement('div', {
    className: 'lkn-cielo-credit-debit-description',
    style: {
      width: '100%'
    }
  }, /* #__PURE__ */React.createElement('p', {
    style: {
      width: '100%',
      textAlign: 'center'
    }
  }, lknDCDescriptionCielo)),
  /* #__PURE__ */React.createElement('div', null, /* #__PURE__ */React.createElement('input', {
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
    name: 'lkn_auth_suppresschallenge',
    className: 'bpmpi_auth_suppresschallenge',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_access_token',
    className: 'bpmpi_accesstoken',
    value: lknDCAccessTokenCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_expires_in',
    id: 'expires_in',
    value: lknDCAccessTokenExpiration
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    name: 'lkn_order_number',
    className: 'bpmpi_ordernumber',
    value: lknDCOrderNumberCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    name: 'lkn_currency',
    className: 'bpmpi_currency',
    value: 'BRL'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    id: 'lkn_cielo_3ds_value',
    name: 'lkn_amount',
    className: 'bpmpi_totalamount',
    value: lknDCTotalCartCielo
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
    id: 'lkn_bpmpi_default_card',
    name: 'lkn_default_card',
    className: 'bpmpi_default_card',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_order_recurrence',
    name: 'lkn_order_recurrence',
    className: 'bpmpi_order_recurrence',
    value: 'false'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_order_productcode',
    value: 'PHY'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_transaction_mode',
    value: 'S'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    className: 'bpmpi_merchant_url',
    value: lknDCUrlCielo
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '14',
    id: 'lkn_bpmpi_billto_customerid',
    name: 'lkn_card_customerid',
    className: 'bpmpi_billto_customerid',
    value: lknDCClient.billing_document
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '120',
    id: 'lkn_bpmpi_billto_contactname',
    name: 'lkn_card_contactname',
    className: 'bpmpi_billto_contactname',
    value: lknDCClient.name
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '15',
    id: 'lkn_bpmpi_billto_phonenumber',
    name: 'lkn_card_phonenumber',
    className: 'bpmpi_billto_phonenumber',
    value: lknDCClient.billing_phone
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '255',
    id: 'lkn_bpmpi_billto_email',
    name: 'lkn_card_email',
    className: 'bpmpi_billto_email',
    value: lknDCClient.email
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '60',
    id: 'lkn_bpmpi_billto_street1',
    name: 'lkn_card_billto_street1',
    className: 'bpmpi_billto_street1',
    value: lknDCClient.billing_address_1
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '60',
    id: 'lkn_bpmpi_billto_street2',
    name: 'lkn_card_billto_street2',
    className: 'bpmpi_billto_street2',
    value: lknDCClient.billing_address_2
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '50',
    id: 'lkn_bpmpi_billto_city',
    name: 'lkn_card_billto_city',
    className: 'bpmpi_billto_city',
    value: lknDCClient.billing_city
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    id: 'lkn_bpmpi_billto_state',
    name: 'lkn_card_billto_state',
    className: 'bpmpi_billto_state',
    value: lknDCClient.billing_state
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '8',
    id: 'lkn_bpmpi_billto_zipcode',
    name: 'lkn_card_billto_zipcode',
    className: 'bpmpi_billto_zipcode',
    value: lknDCClient.billing_postcode
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '2',
    id: 'lkn_bpmpi_billto_country',
    name: 'lkn_card_billto_country',
    className: 'bpmpi_billto_country',
    value: lknDCClient.billing_country
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_shipto_sameasbillto',
    name: 'lkn_card_shipto_sameasbillto',
    className: 'bpmpi_shipto_sameasbillto',
    value: 'true'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_useraccount_guest',
    name: 'lkn_card_useraccount_guest',
    className: 'bpmpi_useraccount_guest',
    value: lknDCUserGuest
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    id: 'lkn_bpmpi_useraccount_authenticationmethod',
    name: 'lkn_card_useraccount_authenticationmethod',
    className: 'bpmpi_useraccount_authenticationmethod',
    value: lknDCAuthMethod
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '45',
    id: 'lkn_bpmpi_device_ipaddress',
    name: 'lkn_card_device_ipaddress',
    className: 'bpmpi_device_ipaddress',
    value: lknDCClientIp
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '7',
    id: 'lkn_bpmpi_device_channel',
    name: 'lkn_card_device_channel',
    className: 'bpmpi_device_channel',
    value: 'Browser'
  }), /* #__PURE__ */React.createElement('input', {
    type: 'hidden',
    size: '10',
    id: 'lkn_bpmpi_brand_establishment_code',
    name: 'lkn_card_brand_establishment_code',
    className: 'bpmpi_brand_establishment_code',
    value: lknDCBec
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
const Lkn_DC_Block_Gateway_Cielo = {
  name: 'lkn_cielo_debit',
  label: lknDCLabelCielo,
  content: window.wp.element.createElement(lknDCContentCielo),
  edit: window.wp.element.createElement(lknDCContentCielo),
  canMakePayment: () => true,
  ariaLabel: lknDCLabelCielo,
  supports: {
    features: lknDCsettingsCielo.supports
  }
}
window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_DC_Block_Gateway_Cielo)
