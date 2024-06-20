const lknDCsettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {})
const lknDCLabelCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.title)
const lknDCAccessTokenCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.accessToken)
const lknDCUrlCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.url)
const lknDCTotalCartCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.totalCart)
const lknDCOrderNumberCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.orderNumber)
const lknDCDirScript3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScript3DS)
const lknDCDirScriptConfig3DSCielo = window.wp.htmlEntities.decodeEntities(lknDCsettingsCielo.dirScriptConfig3DS)
const lknDCTranslationsDebitCielo = lknDCsettingsCielo.translations
const lknDCNonceCieloDebit = lknDCsettingsCielo.nonceCieloDebit;

const lknDCContentCielo = (props) => {
  const wcComponents = window.wc.blocksComponents
  const { eventRegistration, emitResponse } = props
  const { onPaymentSetup } = eventRegistration

  const [debitObject, setdebitObject] = window.wp.element.useState({
    lkn_dc_cardholder_name: '',
    lkn_dcno: '',
    lkn_dc_expdate: '',
    lkn_dc_cvc: '',
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
    const lknDCElement = document.querySelectorAll('.wc-block-components-checkout-place-order-button')

    if (lknDCElement && lknDCElement[0]) {
      lknDCElement[0].style.display = 'none';

      return () => {
        lknDCElement[0].style.display = '';
      };
    }
  })

  window.wp.element.useEffect(() => {
    const scriptUrl = lknDCDirScript3DSCielo;
    const existingScript = document.querySelector(`script[src="${scriptUrl}"]`);

    if (!existingScript) {
      const script = document.createElement('script');
      script.src = scriptUrl;
      script.async = true;
      document.body.appendChild(script);
    }
  }, []);

  window.wp.element.useEffect(() => {
    const scriptUrl = lknDCDirScriptConfig3DSCielo;
    const existingScript = document.querySelector(`script[src="${scriptUrl}"]`);
    if (!existingScript) {
      const script = document.createElement('script');
      script.src = scriptUrl;
      script.async = true;
      document.body.appendChild(script);
    }
  }, []);


  const handleButtonClick = () => {
    // Verifica se todos os campos do debitObject estão preenchidos
    const allFieldsFilled = Object.values(debitObject).every((field) => field.trim() !== '');

    // Seleciona os lknDCElements dos campos de entrada
    const cardNumberInput = document.getElementById('lkn_dcno');
    const expDateInput = document.getElementById('lkn_dc_expdate');
    const cvvInput = document.getElementById('lkn_dc_cvc');
    const cardHolder = document.getElementById('lkn_dc_cardholder_name');

    // Remove classes de erro e mensagens de validação existentes
    cardNumberInput?.classList.remove('has-error');
    expDateInput?.classList.remove('has-error');
    cvvInput?.classList.remove('has-error');
    cardHolder?.classList.remove('has-error')

    if (allFieldsFilled) {
      lknDCProccessButton();
    } else {
      // Adiciona classes de erro aos campos vazios
      if (debitObject.lkn_dc_cardholder_name.trim() === '') {
        const parentDiv = cardHolder?.parentElement;
        parentDiv?.classList.add('has-error');
      }

      if (debitObject.lkn_dcno.trim() === '') {
        const parentDiv = cardNumberInput?.parentElement;
        parentDiv?.classList.add('has-error');
      }

      if (debitObject.lkn_dc_expdate.trim() === '') {
        const parentDiv = expDateInput?.parentElement;
        parentDiv?.classList.add('has-error');
      }

      if (debitObject.lkn_dc_cvc.trim() === '') {
        const parentDiv = cvvInput?.parentElement;
        parentDiv?.classList.add('has-error');
      }
    }
  };

  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      const Button3dsEnviar = document.querySelectorAll('.wc-block-components-checkout-place-order-button')[0].closest('form')

      const paymentCavv = Button3dsEnviar?.getAttribute('data-payment-cavv');
      const paymentEci = Button3dsEnviar?.getAttribute('data-payment-eci');
      const paymentReferenceId = Button3dsEnviar?.getAttribute('data-payment-ref_id');
      const paymentVersion = Button3dsEnviar?.getAttribute('data-payment-version');
      const paymentXid = Button3dsEnviar?.getAttribute('data-payment-xid');

      return {
        type: emitResponse.responseTypes.SUCCESS,
        meta: {
          paymentMethodData: {
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
          },
        },
      };
    }
    );

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
        id="lkn_dc_cardholder_name"
        label={lknDCTranslationsDebitCielo.cardHolder}
        value={debitObject.lkn_dc_cardholder_name}
        onChange={(value) => {
          updatedebitObject('lkn_dc_cardholder_name', value)
        }}
        required
      />

      <wcComponents.TextInput
        id="lkn_dcno"
        label={lknDCTranslationsDebitCielo.cardNumber}
        value={debitObject.lkn_dcno}
        onChange={(value) => {
          updatedebitObject('lkn_dcno', formatDebitCardNumber(value))
        }}
        required
      />

      <wcComponents.TextInput
        id="lkn_dc_expdate"
        label={lknDCTranslationsDebitCielo.cardExpiryDate}
        value={debitObject.lkn_dc_expdate}
        onChange={(value) => {
          updatedebitObject('lkn_dc_expdate', value)
        }}
        required
      />

      <wcComponents.TextInput
        id="lkn_dc_cvc"
        label={lknDCTranslationsDebitCielo.securityCode}
        value={debitObject.lkn_dc_cvc}
        onChange={(value) => {
          updatedebitObject('lkn_dc_cvc', value)
        }}
        required
      />

      <div style={{ marginBottom: '30px' }}></div>

      <div style={{ display: 'flex', justifyContent: 'center' }}>
        <wcComponents.Button
          id="sendOrder"
          onClick={handleButtonClick}
        >
          <span>Finalizar pedido</span>
        </wcComponents.Button>
      </div>

      <div style={{ marginBottom: '20px' }}></div>

      <div>
        <input type="hidden" name="lkn_auth_enabled" className="bpmpi_auth" value="true" />
        <input type="hidden" name="lkn_auth_enabled_notifyonly" className="bpmpi_auth_notifyonly" value="true" />
        <input type="hidden" name="lkn_access_token" className="bpmpi_accesstoken" value={lknDCAccessTokenCielo} />
        <input type="hidden" size="50" name="lkn_order_number" className="bpmpi_ordernumber" value={lknDCOrderNumberCielo} />
        <input type="hidden" name="lkn_currency" className="bpmpi_currency" value="BRL" />
        <input type="hidden" size="50" className="bpmpi_merchant_url" value={lknDCUrlCielo} />
        <input type="hidden" size="50" id="lkn_cielo_3ds_value" name="lkn_amount" className="bpmpi_totalamount" value={lknDCTotalCartCielo} />
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
};

window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_DC_Block_Gateway_Cielo)
