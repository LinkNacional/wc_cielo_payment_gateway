const settingsDebitCard = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {})
const labelDebitCard = window.wp.htmlEntities.decodeEntities(settingsDebitCard.title)
const accessToken = window.wp.htmlEntities.decodeEntities(settingsDebitCard.accessToken)
const url = window.wp.htmlEntities.decodeEntities(settingsDebitCard.url)
const totalCart = window.wp.htmlEntities.decodeEntities(settingsDebitCard.totalCart) //verificar isso
const orderNumber = window.wp.htmlEntities.decodeEntities(settingsDebitCard.orderNumber)
const dirScript3DS = window.wp.htmlEntities.decodeEntities(settingsDebitCard.dirScript3DS)
const dirScriptConfig3DS = window.wp.htmlEntities.decodeEntities(settingsDebitCard.dirScriptConfig3DS)

const Content_cieloDebit = (props) => {
  const wcComponents = window.wc.blocksComponents
  const { eventRegistration, emitResponse } = props
  const { onPaymentSetup } = eventRegistration

  const [debitObject, setdebitObject] = window.wp.element.useState({
    lkn_dcno: '',
    lkn_dc_expdate: '',
    lkn_dc_cvc: '',
  })

  const formatDebitCardNumber = value => {
    if (value?.length > 19) return debitObject.lkn_dcno
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
        if (value.length > 4) return
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
    const scriptUrl = dirScript3DS; 
    const existingScript = document.querySelector(`script[src="${scriptUrl}"]`);
    
    if (!existingScript) {
      const script = document.createElement('script');
      script.src = scriptUrl;
      script.async = true;
      document.body.appendChild(script);
    }
  }, []);

  window.wp.element.useEffect(() => {
    const scriptUrl = dirScriptConfig3DS; 
    const existingScript = document.querySelector(`script[src="${scriptUrl}"]`);
    if(!existingScript){
      const script = document.createElement('script');
      script.src = scriptUrl;
      script.async = true;
      document.body.appendChild(script);
    }
  }, []);

  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      // Verifica se todos os campos do debitObject estão preenchidos
      const allFieldsFilled = Object.values(debitObject).every((field) => field.trim() !== '');

      if (allFieldsFilled) {
        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              lkn_dcno: debitObject.lkn_dcno,
              lkn_dc_expdate: debitObject.lkn_dc_expdate,
              lkn_dc_cvc: debitObject.lkn_dc_cvc
            },
          },
        };
      }

      return {
        type: emitResponse.responseTypes.ERROR,
        message: 'Por favor, preencha todos os campos.',
      };

    });

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe();
    };
  }, [debitObject,
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
    onPaymentSetup
  ]);

  return (
    <>
      <div>
        <h4>Pagamento processado pela Cielo API 3.0</h4>
      </div>

      <wcComponents.TextInput
        id="lkn_dcno"
        label="Número do Cartão"
        value={debitObject.lkn_dcno}
        onChange={(value) => {
          updatedebitObject('lkn_dcno', formatDebitCardNumber(value))
        }}
      />

      <wcComponents.TextInput
        id="lkn_dc_expdate"
        label="Data de Validade"
        value={debitObject.lkn_dc_expdate}
        onChange={(value) => {
          updatedebitObject('lkn_dc_expdate', value)
        }}
      />

      <wcComponents.TextInput
        id="lkn_dc_cvc"
        label="Código de Segurança (CVV)"
        value={debitObject.lkn_dc_cvc}
        onChange={(value) => {
          updatedebitObject('lkn_dc_cvc', value)
        }}
      />

      <div>
        <input type="hidden" name="lkn_auth_enabled" className="bpmpi_auth" value="true" />
        <input type="hidden" name="lkn_auth_enabled_notifyonly" className="bpmpi_auth_notifyonly" value="true" />
        <input type="hidden" name="lkn_access_token" className="bpmpi_accesstoken" value={accessToken} />
        <input type="hidden" size="50" name="lkn_order_number" className="bpmpi_ordernumber" value={orderNumber} />
        <input type="hidden" name="lkn_currency" className="bpmpi_currency" value="BRL" />
        <input type="hidden" size="50" className="bpmpi_merchant_url" value={url} />
        <input type="hidden" size="50" id="lkn_cielo_3ds_value" name="lkn_amount" className="bpmpi_totalamount" value={totalCart} />
        <input type="hidden" size="2" name="lkn_installments" className="bpmpi_installments" value="1" />
        <input type="hidden" name="lkn_payment_method" className="bpmpi_paymentmethod" value="Debit" />
        <input type="hidden" id="lkn_bpmpi_cardnumber" className="bpmpi_cardnumber" />
        <input type="hidden" id="lkn_bpmpi_expmonth" maxLength="2" name="lkn_card_expiry_month" className="bpmpi_cardexpirationmonth" />
        <input type="hidden" id="lkn_bpmpi_expyear" maxLength="4" name="lkn_card_expiry_year" className="bpmpi_cardexpirationyear" />
        <input type="hidden" size="50" className="bpmpi_order_productcode" value="PHY" />
        <input type="hidden" id="lkn_cavv" name="lkn_cielo_3ds_cavv" value />
        <input type="hidden" id="lkn_eci" name="lkn_cielo_3ds_eci" value />
        <input type="hidden" id="lkn_ref_id" name="lkn_cielo_3ds_ref_id" value />
        <input type="hidden" id="lkn_version" name="lkn_cielo_3ds_version" value />
        <input type="hidden" id="lkn_xid" name="lkn_cielo_3ds_xid" value />
      </div>
    </>
  )
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
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Debit_Card)