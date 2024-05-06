const settings_debitCard = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {});
const label_debitCard = window.wp.htmlEntities.decodeEntities(settings_debitCard.title);
const accessToken = window.wp.htmlEntities.decodeEntities(settings_debitCard.accessToken);
const url = window.wp.htmlEntities.decodeEntities(settings_debitCard.url);
const totalCart = window.wp.htmlEntities.decodeEntities(settings_debitCard.totalCart);
const orderNumber = window.wp.htmlEntities.decodeEntities(settings_debitCard.orderNumber);

const Content_cieloDebit = props => {
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onPaymentSetup
  } = eventRegistration;
  const [debitObject, setdebitObject] = window.wp.element.useState({
    lkn_dcno: '',
    lkn_dc_expdate: '',
    lkn_dc_cvc: ''
  });
  const formatDebitCardNumber = value => {
    if (value?.length > 19) return debitObject.lkn_dcno;
    // Remove caracteres não numéricos
    const cleanedValue = value?.replace(/\D/g, '');
    // Adiciona espaços a cada quatro dígitos
    const formattedValue = cleanedValue?.replace(/(.{4})/g, '$1 ')?.trim();
    return formattedValue;
  };
  const updatedebitObject = (key, value) => {
    switch (key) {
      case 'lkn_dc_expdate':
        if (value.length > 7) return;

        // Verifica se o valor é uma data válida (MM/YY)
        const isValidDate = /^\d{2}\/\d{2}$/.test(value);
        if (!isValidDate) {
          // Remove caracteres não numéricos
          const cleanedValue = value?.replace(/\D/g, '');
          let formattedValue = cleanedValue?.replace(/^(.{2})/, '$1 / ')?.trim();

          // Se o tamanho da string for 5, remove o espaço e a barra adicionados anteriormente
          if (formattedValue.length === 4) {
            formattedValue = formattedValue.replace(/\s\//, '');
          }

          // Atualiza o estado
          setdebitObject({
            ...debitObject,
            [key]: formattedValue
          });
        }
        return;
      case 'lkn_dc_cvc':
        if (value.length > 4) return;
        break;
      default:
        break;
    }
    setdebitObject({
      ...debitObject,
      [key]: value
    });
  };
  const wcComponents = window.wc.blocksComponents;
  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      // Verifica se todos os campos do debitObject estão preenchidos
      const allFieldsFilled = Object.values(debitObject).every(field => field.trim() !== '');
      if (allFieldsFilled) {
        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              lkn_dcno: debitObject.lkn_dcno,
              lkn_dc_expdate: debitObject.lkn_dc_expdate,
              lkn_dc_cvc: debitObject.lkn_dc_cvc
            }
          }
        };
      }
      return {
        type: emitResponse.responseTypes.ERROR,
        message: 'Por favor, preencha todos os campos.'
      };
    });

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe();
    };
  }, [debitObject, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup]);
  return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h3", {
    style: {
      textAlign: 'center'
    }
  }, "Informa\xE7\xF5es do Cart\xE3o de D\xE9bito")), /*#__PURE__*/React.createElement(wcComponents.TextInput, {
    id: "lkn_dcno",
    label: "N\xFAmero do Cart\xE3o",
    value: debitObject.lkn_dcno,
    onChange: value => {
      updatedebitObject('lkn_dcno', formatDebitCardNumber(value));
    }
  }), /*#__PURE__*/React.createElement(wcComponents.TextInput, {
    id: "lkn_dc_expdate",
    label: "Data de Validade",
    value: debitObject.lkn_dc_expdate,
    onChange: value => {
      updatedebitObject('lkn_dc_expdate', value);
    }
  }), /*#__PURE__*/React.createElement(wcComponents.TextInput, {
    id: "lkn_dc_cvc",
    label: "C\xF3digo de Seguran\xE7a (CVV)",
    value: debitObject.lkn_dc_cvc,
    onChange: value => {
      updatedebitObject('lkn_dc_cvc', value);
    }
  }), /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    name: "lkn_auth_enabled",
    className: "bpmpi_auth",
    value: "true"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    name: "lkn_auth_enabled_notifyonly",
    className: "bpmpi_auth_notifyonly",
    value: "true"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    name: "lkn_access_token",
    className: "bpmpi_accesstoken",
    value: accessToken
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    size: "50",
    name: "lkn_order_number",
    className: "bpmpi_ordernumber",
    value: orderNumber
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    name: "lkn_currency",
    className: "bpmpi_currency",
    value: "BRL"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    size: "50",
    className: "bpmpi_merchant_url",
    value: url
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    size: "50",
    id: "lkn_cielo_3ds_value",
    name: "lkn_amount",
    className: "bpmpi_totalamount",
    value: totalCart
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    size: "2",
    name: "lkn_installments",
    className: "bpmpi_installments",
    value: "1"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    name: "lkn_payment_method",
    className: "bpmpi_paymentmethod",
    value: "Debit"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    id: "lkn_bpmpi_cardnumber",
    className: "bpmpi_cardnumber"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    id: "lkn_bpmpi_expmonth",
    maxLength: "2",
    name: "lkn_card_expiry_month",
    className: "bpmpi_cardexpirationmonth"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    id: "lkn_bpmpi_expyear",
    maxLength: "4",
    name: "lkn_card_expiry_year",
    className: "bpmpi_cardexpirationyear"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    size: "50",
    className: "bpmpi_order_productcode",
    value: "PHY"
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    id: "lkn_cavv",
    name: "lkn_cielo_3ds_cavv",
    value: ""
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    id: "lkn_eci",
    name: "lkn_cielo_3ds_eci",
    value: ""
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    id: "lkn_ref_id",
    name: "lkn_cielo_3ds_ref_id",
    value: ""
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    id: "lkn_version",
    name: "lkn_cielo_3ds_version",
    value: ""
  }), /*#__PURE__*/React.createElement("input", {
    type: "hidden",
    id: "lkn_xid",
    name: "lkn_cielo_3ds_xid",
    value: ""
  })));
};
const Block_Gateway_Debit_Card = {
  name: 'lkn_cielo_debit',
  label: label_debitCard,
  content: window.wp.element.createElement(Content_cieloDebit),
  edit: window.wp.element.createElement(Content_cieloDebit),
  canMakePayment: () => true,
  ariaLabel: label_debitCard,
  supports: {
    features: settings_debitCard.supports
  }
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Debit_Card);