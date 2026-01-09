import React from 'react'
import Cards from 'react-credit-cards'
import 'react-credit-cards/es/styles-compiled.css'
const lknCCSettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_credit_data', {})
const lknCCLabelCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.title)
const lknCCDescriptionCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.description)
const lknCCActiveInstallmentCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.activeInstallment)
const lknCCTotalCartCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.totalCart)
const lknCCShowCard = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.showCard)
const lknCCInstallmentLimitCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.installmentLimit)
const lknCCInstallmentMinAmount = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.installmentMinAmount)
const lknCCinstallmentsCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.installments)
const lknCCTranslationsCielo = lknCCSettingsCielo.translations
const lknCCNonceCieloCredit = lknCCSettingsCielo.nonceCieloCredit

// Função para formatação de moeda baseada nas configurações do WooCommerce
const formatCurrency = (amount) => {
  if (!window.lknCieloCreditConfig || !window.lknCieloCreditConfig.currency) {
    // Fallback para BRL se as configurações não estiverem disponíveis
    return new Intl.NumberFormat('pt-BR', {
      style: 'currency',
      currency: 'BRL'
    }).format(amount)
  }

  const currency = window.lknCieloCreditConfig.currency
  const locale = currency.code === 'BRL' ? 'pt-BR' : 'en-US' // Pode ser expandido conforme necessário

  return new Intl.NumberFormat(locale, {
    style: 'currency',
    currency: currency.code,
    minimumFractionDigits: currency.decimals,
    maximumFractionDigits: currency.decimals
  }).format(amount)
}

const lknCCContentCielo = props => {
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
  const [creditObject, setCreditObject] = window.wp.element.useState({
    lkn_cc_cardholder_name: '',
    lkn_ccno: '',
    lkn_cc_expdate: '',
    lkn_cc_cvc: '',
    lkn_cc_installments: '1' // Definir padrão como 1 parcela
  })
  const [cardBinState, setCardBinState] = window.wp.element.useState(0)
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

        // Nova fórmula: base = subtotal + shipping
        // Juros/desconto aplicado sobre a base dentro de calculateInstallments
        // Depois soma: externalFees - discount + taxes
        const subtotal = parseFloat(totals.total_items) || 0
        const shipping = parseFloat(totals.total_shipping) || 0
        const discount = parseFloat(totals.total_discount) || 0 // Desconto de cupons (valor negativo)
        const tax = parseFloat(totals.total_tax) || 0

        // Calcula fees externas (excluindo as do plugin Cielo)
        let externalFees = 0
        if (cartData.totals.total_fees && cartData.fees) {
          // Filtra apenas fees que NÃO são do plugin Cielo
          const cardInterestLabel = 'Card Interest'
          const cardDiscountLabel = 'Card Discount'

          cartData.fees.forEach(fee => {
            const feeName = fee.name || ''
            // Se a fee NÃO contém os labels do plugin Cielo, é uma fee externa
            if (!feeName.includes(cardInterestLabel) && !feeName.includes(cardDiscountLabel)) {
              externalFees += parseFloat(fee.totals.total) || 0
            }
          })
        }

        // Base para cálculo de juros/desconto: apenas subtotal + frete
        const baseAmount = (subtotal + shipping) / 100

        // Valores que serão somados no final
        const additionalValues = {
          externalFees: externalFees / 100,
          discount: discount / 100, // Já é negativo
          tax: tax / 100
        }

        // Atualiza as opções de parcelamento com a base e valores adicionais
        if (baseAmount > 0) {
          setOptions([]) // Limpa opções antigas
          calculateInstallments(baseAmount, additionalValues)
        }

        return {
          subtotal: subtotal / 100,
          shipping: shipping / 100,
          baseAmount,
          additionalValues
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
      case 'lkn_cc_cardholder_name':
        // Atualiza o estado
        setCreditObject({
          ...creditObject,
          [key]: value
        })
        break
      case 'lkn_cc_expdate':
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
          setCreditObject({
            ...creditObject,
            [key]: formattedValue
          })
        }
        return
      case 'lkn_cc_cvc':
        if (value.length > 8) return
      default:
        break
    }
    setCreditObject({
      ...creditObject,
      [key]: value
    })
  }
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
          calculateInstallments(firstData.baseAmount, firstData.additionalValues)
          setIsLoadingOptions(false)
          firstDataProcessed = true
        }
      })

      // Se obteve dados finais diferentes, atualiza silenciosamente
      if (cartData && firstDataProcessed) {
        calculateInstallments(cartData.baseAmount, cartData.additionalValues)
      }
    } else {
      // Uma única tentativa para mudanças rápidas
      cartData = await fetchCartData()

      if (cartData) {
        calculateInstallments(cartData.baseAmount, cartData.additionalValues)
      }      // Desativa o loading após processar mudanças rápidas
      setIsLoadingOptions(false)
    }
  }

  const calculateInstallments = (baseAmount, additionalValues = {}) => {
    // Valores padrão para additionalValues caso estejam undefined
    const safeAdditionalValues = {
      externalFees: additionalValues.externalFees || 0,
      discount: additionalValues.discount || 0,
      tax: additionalValues.tax || 0
    }

    const installmentMin = parseFloat(lknCCInstallmentMinAmount)
    const newOptions = [] // Array local para construir as opções

    // Verifica se 'lknCCActiveInstallmentCielo' é 'yes' e o valor base é maior que 10
    if (lknCCActiveInstallmentCielo === 'yes' && baseAmount > 10) {
      const maxInstallments = lknCCInstallmentLimitCielo // Limita o parcelamento

      for (let index = 1; index <= maxInstallments; index++) {
        // Começa com o valor base (subtotal + shipping)
        let baseValue = parseFloat(baseAmount)
        let totalValue = baseValue
        let nextInstallmentAmount = totalValue / index

        let formatedInterest = false
        let typeText = ''

        // Busca a configuração específica para esta parcela no array installments
        const installmentConfig = lknCCinstallmentsCielo.find(inst => inst.id === index)

        // Se o plugin PRO está válido, aplica juros/descontos sobre a BASE
        if (lknCieloCreditConfig.isProPluginValid && installmentConfig) {
          const interestOrDiscount = lknCCSettingsCielo.interestOrDiscount
          const interestPercent = parseFloat(installmentConfig.interest)

          if (interestOrDiscount === 'discount' && lknCCSettingsCielo.activeDiscount == "yes") {
            // Aplica desconto sobre a BASE (subtotal + shipping)
            const discountMultiplier = 1 - (interestPercent / 100)
            totalValue = baseValue * discountMultiplier

            // Soma valores adicionais no final
            totalValue += safeAdditionalValues.externalFees + safeAdditionalValues.discount + safeAdditionalValues.tax

            nextInstallmentAmount = totalValue / index
            formatedInterest = formatCurrency(nextInstallmentAmount)
            typeText = ` (${interestPercent}${lknCCTranslationsCielo.withDiscount})`
          } else if (interestOrDiscount === "interest" && lknCCSettingsCielo.activeInstallment == "yes") {
            // Aplica juros sobre a BASE (subtotal + shipping)
            const interestMultiplier = 1 + (interestPercent / 100)
            totalValue = baseValue * interestMultiplier

            // Soma valores adicionais no final
            totalValue += safeAdditionalValues.externalFees + safeAdditionalValues.discount + safeAdditionalValues.tax

            nextInstallmentAmount = totalValue / index
            formatedInterest = formatCurrency(nextInstallmentAmount)
            typeText = ` (${interestPercent}${lknCCTranslationsCielo.withInterest})`
          }
        } else {
          // Sem juros/desconto: usa apenas a base + valores adicionais
          totalValue = baseValue + safeAdditionalValues.externalFees + safeAdditionalValues.discount + safeAdditionalValues.tax
          nextInstallmentAmount = totalValue / index
        }

        // Verifica se atende o valor mínimo
        if (nextInstallmentAmount < installmentMin) {
          break
        }

        if (formatedInterest) {
          newOptions.push({
            key: index,
            label: `${lknCCTranslationsCielo.installmentText.replace('%1$d', index).replace('%2$s', formatedInterest)}${typeText}`
          })
        } else {
          // Sem juros/desconto do plugin: usa o total calculado
          const finalAmount = totalValue / index
          const installmentAmount = finalAmount.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
          })

          // Se o plugin PRO está válido, usa as configurações originais
          if (lknCieloCreditConfig.isProPluginValid) {
            if (lknCCSettingsCielo.activeDiscount == 'yes') {
              newOptions.push({
                key: index,
                label: `${lknCCTranslationsCielo.installmentText.replace('%1$d', index).replace('%2$s', `R$ ${installmentAmount}`)} ${lknCCSettingsCielo.interestOrDiscount == 'interest' ? lknCCTranslationsCielo.noInterest : lknCCTranslationsCielo.noDiscount}`
              })
            } else {
              newOptions.push({
                key: index,
                label: `${lknCCTranslationsCielo.installmentText.replace('%1$d', index).replace('%2$s', `R$ ${installmentAmount}`)} ${lknCCTranslationsCielo.noInterest}`
              })
            }
          } else {
            // Se o plugin PRO não está válido, mostra apenas o valor sem texto adicional
            newOptions.push({
              key: index,
              label: lknCCTranslationsCielo.installmentText.replace('%1$d', index).replace('%2$s', `R$ ${installmentAmount}`)
            })
          }
        }
      }
    } else {
      // À vista: usa o valor base + valores adicionais
      const totalAmountValue = baseAmount + safeAdditionalValues.externalFees + safeAdditionalValues.discount + safeAdditionalValues.tax
      const totalAmount = totalAmountValue.toLocaleString('pt-BR', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
      })
      newOptions.push({
        key: '1',
        label: `${lknCCTranslationsCielo.installmentText.replace('%1$d', '1').replace('%2$s', `R$ ${totalAmount}`)} ${lknCCTranslationsCielo.cashPayment}`
      })
    }

    // Define todas as opções de uma vez
    setOptions(newOptions)
  }
  window.wp.element.useEffect(() => {
    // Executa a primeira busca no carregamento
    const loadInitialData = async () => {
      // Chama lkn_update_payment_fees uma única vez na inicialização
      if (window.lknCieloCreditConfig) {
        const formData = new FormData()
        formData.append('action', 'lkn_update_payment_fees')
        formData.append('payment_method', 'lkn_cielo_credit')
        formData.append('installment', creditObject.lkn_cc_installments)
        formData.append('card_type', 'Credit')
        formData.append('nonce', window.lknCieloCreditConfig.fees_nonce)

        try {
          await fetch(window.lknCieloCreditConfig.ajax_url, {
            method: 'POST',
            body: formData
          })
        } catch (error) {
          console.error('Erro ao inicializar sessão de pagamento:', error)
        }
      }

      const finalCartData = await fetchCartDataWithRetries(4, 1500, (firstData) => {
        // Callback chamado na primeira resposta - para o loading imediatamente
        calculateInstallments(firstData.baseAmount, firstData.additionalValues)
        setIsLoadingOptions(false)
      })

      // Se os dados finais são diferentes dos primeiros, atualiza silenciosamente
      if (finalCartData && !isLoadingOptions) {
        calculateInstallments(finalCartData.baseAmount, finalCartData.additionalValues)
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
  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      // Verifica se todos os campos do creditObject estão preenchidos
      const allFieldsFilled = Object.keys(creditObject).filter(key => key !== 'lkn_cc_cardholder_name').every(key => creditObject[key].trim() !== '')
      if (allFieldsFilled) {
        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              lkn_ccno: creditObject.lkn_ccno,
              lkn_cc_cardholder_name: creditObject.lkn_cc_cardholder_name,
              lkn_cc_expdate: creditObject.lkn_cc_expdate,
              lkn_cc_cvc: creditObject.lkn_cc_cvc,
              lkn_cc_installments: creditObject.lkn_cc_installments,
              nonce_lkn_cielo_credit: lknCCNonceCieloCredit
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
  return /* #__PURE__ */React.createElement(React.Fragment, null, lknCCShowCard !== 'no' && /* #__PURE__ */React.createElement(Cards, {
    number: creditObject.lkn_ccno,
    name: creditObject.lkn_cc_cardholder_name,
    expiry: creditObject.lkn_cc_expdate.replace(/\s+/g, ''),
    cvc: creditObject.lkn_cc_cvc,
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
    id: 'lkn_cc_cardholder_name',
    label: lknCCTranslationsCielo.cardHolder,
    value: creditObject.lkn_cc_cardholder_name,
    autocomplete: 'cc-name',
    onChange: value => {
      updateCreditObject('lkn_cc_cardholder_name', value)
    },
    required: true,
    onFocus: () => setFocus('name')
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_ccno',
    label: lknCCTranslationsCielo.cardNumber,
    value: creditObject.lkn_ccno,
    autocomplete: 'cc-number',
    onChange: value => {
      updateCreditObject('lkn_ccno', formatCreditCardNumber(value))
    },
    required: true,
    onFocus: () => setFocus('number')
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_cc_expdate',
    label: lknCCTranslationsCielo.cardExpiryDate,
    value: creditObject.lkn_cc_expdate,
    autocomplete: 'cc-exp',
    onChange: value => {
      updateCreditObject('lkn_cc_expdate', value)
    },
    required: true,
    onFocus: () => setFocus('expiry')
  }), /* #__PURE__ */React.createElement(wcComponents.TextInput, {
    id: 'lkn_cc_cvc',
    label: lknCCTranslationsCielo.securityCode,
    value: creditObject.lkn_cc_cvc,
    autocomplete: 'cc-csc',
    onChange: value => {
      updateCreditObject('lkn_cc_cvc', value)
    },
    required: true,
    onFocus: () => setFocus('cvc')
  }), /* #__PURE__ */React.createElement('div', {
    style: {
      marginBottom: '20px'
    }
  }), lknCCActiveInstallmentCielo === 'yes' && /* #__PURE__ */React.createElement(wcComponents.SortSelect, {
    id: 'lkn_cc_installments',
    label: lknCCTranslationsCielo.installments,
    value: creditObject.lkn_cc_installments,
    className: `lkn_cielo_credit_select lkn-cielo-credit-custom-select ${isLoadingOptions ? 'loading-options' : ''}`,
    disabled: isLoadingOptions,
    style: isLoadingOptions ? { opacity: 0.7, cursor: 'wait' } : {},
    onChange: event => {
      const installmentValue = event.target.value

      // Ignora se está selecionando a opção de loading
      if (installmentValue === 'loading') return

      updateCreditObject('lkn_cc_installments', installmentValue)

      // Faz a requisição AJAX para atualizar as fees quando a parcela mudar
      if (window.lknCieloCreditConfig) {
        const formData = new FormData()
        formData.append('action', 'lkn_update_payment_fees')
        formData.append('payment_method', 'lkn_cielo_credit')
        formData.append('installment', installmentValue)
        formData.append('card_type', 'Credit')
        formData.append('nonce', window.lknCieloCreditConfig.fees_nonce)

        fetch(window.lknCieloCreditConfig.ajax_url, {
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
    options: isLoadingOptions ? [{ key: 'loading', label: `🔄 ${lknCCTranslationsCielo.calculatingInstallments}` }] : options
  }), /* #__PURE__ */React.createElement('div', {
    className: 'lkn-cielo-credit-description',
    style: {
      width: '100%'
    }
  }, /* #__PURE__ */React.createElement('p', {
    style: {
      width: '100%',
      textAlign: 'center'
    }
  }, lknCCDescriptionCielo)))
}
const Lkn_CC_Block_Gateway_Cielo = {
  name: 'lkn_cielo_credit',
  label: lknCCLabelCielo,
  content: window.wp.element.createElement(lknCCContentCielo),
  edit: window.wp.element.createElement(lknCCContentCielo),
  canMakePayment: () => true,
  ariaLabel: lknCCLabelCielo,
  supports: {
    features: lknCCSettingsCielo.supports
  }
}
window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_CC_Block_Gateway_Cielo)
