/* eslint-disable no-unused-vars */
/* eslint-disable no-undef */
// Implements script internationalization

function bpmpi_config () {
  return {
    onReady: function () {
    },
    onSuccess: function (e) {
      // Card is eligible for authentication, and the bearer successfully authenticated
      const cavv = e.Cavv
      const xid = e.Xid
      const eci = e.Eci
      const version = e.Version
      const referenceId = e.ReferenceId

      const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0].closest('form')

      Form3dsButton.setAttribute('data-payment-cavv', cavv)
      Form3dsButton.setAttribute('data-payment-eci', eci)
      Form3dsButton.setAttribute('data-payment-ref_id', referenceId)
      Form3dsButton.setAttribute('data-payment-version', version)
      Form3dsButton.setAttribute('data-payment-xid', xid)

      if (Form3dsButton) {
        const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
        const event = new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        })
        Button3ds.dispatchEvent(event)
      } else {
        console.error('Form3dsButton não encontrado.')
      }
    },
    onFailure: function (e) {
      // Card is not eligible for authentication, but the bearer failed payment
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))

      alert(__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
    },
    onUnenrolled: function (e) {
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))

      // Verificar se a opção allow_card_ineligible está habilitada
      const allowCardIneligible = window.lknDCScriptAllowCardIneligible === 'yes'
      
      if (allowCardIneligible) {
        
        // Continuar processamento sem 3DS - simular dados vazios
        const Form3dsButton = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0].closest('form')
        
        Form3dsButton.setAttribute('data-payment-cavv', '')
        Form3dsButton.setAttribute('data-payment-eci', '')
        Form3dsButton.setAttribute('data-payment-ref_id', e.ReferenceId || '')
        Form3dsButton.setAttribute('data-payment-version', e.Version || '')
        Form3dsButton.setAttribute('data-payment-xid', '')
        
        // Clicar no botão de finalizar pedido
        const Button3ds = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0]
        const event = new MouseEvent('click', {
          bubbles: true,
          cancelable: true,
          view: window
        })
        Button3ds.dispatchEvent(event)
      } else {
        // Card is not eligible for authentication (unauthenticable)
        alert(__('Card Ineligible for Authentication', 'lkn-wc-gateway-cielo'))
      }
    },
    onDisabled: function () {
      // Store don't require bearer authentication (class "bpmpi_auth" false -> disabled authentication).
      alert(__('Authentication disabled by the store', 'lkn-wc-gateway-cielo'))
    },
    onError: function (e) {
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))

      // Error on proccess in authentication
      alert(__('Error in the 3DS 2.2 authentication process check that your credentials are filled in correctly', 'lkn-wc-gateway-cielo'))
    },
    onUnsupportedBrand: function (e) {
      console.log('code ' + e.ReturnCode + ' ' + ' message ' + e.ReturnMessage + ' raw: ' + JSON.stringify(e))

      // Provider not supported for authentication
      alert(__('Provider not supported by Cielo 3DS authentication', 'lkn-wc-gateway-cielo'))
    },

    Environment: 'SDB', // SDB or PRD
    Debug: true // true or false
  }
}

function lknDCProccessButton () {
  // Verificar o tipo de cartão selecionado antes de processar
  const cardTypeSelect = document.querySelector('.lkn-credit-debit-card-type-select select')
  let cardType = 'Credit' // valor padrão

  if (cardTypeSelect && cardTypeSelect.value) {
    cardType = cardTypeSelect.value
  }
  
  if (cardType === 'Credit') {
    // Para cartão de crédito, pular 3DS e processar diretamente
    lknProcessCreditCard()
  } else {
    // Para cartão de débito, executar 3DS normalmente
    lknProcessDebitCard()
  }
}

// Função para processar cartão de crédito (sem 3DS)
function lknProcessCreditCard () {
  try {
    // Simular dados de autenticação para crédito (sem 3DS real)
    const form = document.querySelector('.wc-block-components-checkout-place-order-button').closest('form')
    
    // Definir dados vazios para 3DS (não utilizados em crédito)
    form.setAttribute('data-payment-cavv', '')
    form.setAttribute('data-payment-eci', '')
    form.setAttribute('data-payment-ref_id', '')
    form.setAttribute('data-payment-version', '')
    form.setAttribute('data-payment-xid', '')
    
    // Clicar no botão de finalizar pedido
    const checkoutButton = document.querySelector('.wc-block-components-checkout-place-order-button')
    if (checkoutButton) {
      checkoutButton.click()
    }
  } catch (error) {
    console.log(error)
    alert(__('Error processing credit card payment', 'lkn-wc-gateway-cielo'))
  }
}

// Função para processar cartão de débito (com 3DS)
function lknProcessDebitCard () {
  try {
    const cardNumber = document.getElementById('lkn_dcno').value.replace(/\D/g, '')
    const cardHolder = document.getElementById('lkn_dc_cardholder_name')

    if (cardHolder) {
      document.getElementById('lkn_bpmpi_billto_contactname').value = cardHolder.value
    } else {
      const firstNameElement = document.getElementById('billing_first_name')
      const lastNameElement = document.getElementById('billing_last_name')

      if(firstNameElement && lastNameElement) {
        firstName = firstNameElement.value
        lastName = lastNameElement.value
        document.getElementById('lkn_bpmpi_billto_contactname').value = firstName + ' ' + lastName
      }else{
        nameElement = document.querySelector('.wc-block-components-address-card__address-section')
        document.getElementById('lkn_bpmpi_billto_contactname').value = nameElement.valeu
      }
    }

    const phoneNumber = document.getElementById('billing-phone') ? document.getElementById('billing-phone').value : ''
    const billingCountry = document.getElementById('billing-country') ? document.getElementById('billing-country').value : ''
    const billingAddress1 = document.getElementById('billing-address_1') ? document.getElementById('billing-address_1').value : ''
    const billingAddress2 = document.getElementById('billing-address_2') ? document.getElementById('billing-address_2').value : ''
    const billingCity = document.getElementById('billing-city') ? document.getElementById('billing-city').value : ''
    const billingPostcode = document.getElementById('billing-postcode') ? document.getElementById('billing-postcode').value : ''
    const billingState = document.getElementById('billing-state') ? document.getElementById('billing-state').value : ''
    const email = document.getElementById('email') ? document.getElementById('email').value : ''
    const billingCpf = document.getElementById('billing_cpf') ? document.getElementById('billing_cpf').value : ''
    const billingCnpj = document.getElementById('billing_cnpj') ? document.getElementById('billing_cnpj').value : ''

    let expDate = document.getElementById('lkn_dc_expdate').value

    expDate = expDate.split('/')

    if (expDate.length === 2) {
      expDate[1] = '20' + expDate[1]
    }

    document.getElementById('lkn_bpmpi_cardnumber').value = cardNumber
    document.getElementById('lkn_bpmpi_expmonth').value = expDate[0].replace(/\D/g, '')
    document.getElementById('lkn_bpmpi_expyear').value = expDate[1].replace(/\D/g, '')

    if (document.getElementById('lkn_bpmpi_useraccount_guest').value === 'true') {
      document.getElementById('lkn_bpmpi_billto_customerid').value = billingCpf || billingCnpj
      document.getElementById('lkn_bpmpi_billto_phonenumber').value = phoneNumber
      document.getElementById('lkn_bpmpi_billto_email').value = email
      document.getElementById('lkn_bpmpi_billto_street1').value = billingAddress1
      document.getElementById('lkn_bpmpi_billto_street2').value = billingAddress2
      document.getElementById('lkn_bpmpi_billto_city').value = billingCity
      document.getElementById('lkn_bpmpi_billto_state').value = billingState
      document.getElementById('lkn_bpmpi_billto_zipcode').value = billingPostcode
      document.getElementById('lkn_bpmpi_billto_country').value = billingCountry
    }

    bpmpi_authenticate()
  } catch (error) {
    console.log(error)
    alert(__('Authentication failed check the card information and try again', 'lkn-wc-gateway-cielo'))
  }
}
