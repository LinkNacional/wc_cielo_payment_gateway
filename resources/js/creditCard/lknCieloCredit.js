const lknCCSettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_credit_data', {});
const lknCCLabelCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.title);
const lknCCActiveInstallmentCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.activeInstallment);
const lknCCTotalCartCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.totalCart);
const lknCCInstallmentLimitCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.installmentLimit);
const lknCCinstallmentsCielo = window.wp.htmlEntities.decodeEntities(lknCCSettingsCielo.installments);
const lknCCTranslationsCielo = lknCCSettingsCielo.translations;
const lknCCNonceCieloCredit = lknCCSettingsCielo.nonceCieloCredit;
const lknCCContentCielo = props => {
  const [options, setOptions] = window.wp.element.useState([]);
  const {
    eventRegistration,
    emitResponse
  } = props;
  const {
    onPaymentSetup
  } = eventRegistration;
  const [creditObject, setCreditObject] = window.wp.element.useState({
    lkn_cc_cardholder_name: '',
    lkn_ccno: '',
    lkn_cc_expdate: '',
    lkn_cc_cvc: '',
    lkn_cc_installments: '1' // Definir padrão como 1 parcela
  });
  const formatCreditCardNumber = value => {
    if (value?.length > 24) return creditObject.lkn_ccno;
    // Remove caracteres não numéricos
    const cleanedValue = value?.replace(/\D/g, '');
    // Adiciona espaços a cada quatro dígitos
    const formattedValue = cleanedValue?.replace(/(.{4})/g, '$1 ')?.trim();
    return formattedValue;
  };
  const updateCreditObject = (key, value) => {
    switch (key) {
      case 'lkn_cc_cardholder_name':
        // Atualiza o estado
        setCreditObject({
          ...creditObject,
          [key]: value
        });
        break;
      case 'lkn_cc_expdate':
        if (value.length > 7) return;

        // Verifica se o valor é uma data válida (MM/YY)
        const isValidDate = /^\d{2}\/\d{2}$/.test(value);
        if (!isValidDate) {
          // Remove caracteres não numéricos
          const cleanedValue = value?.replace(/\D/g, '');
          let formattedValue = cleanedValue?.replace(/^(.{2})(.{2})$/, '$1 / $2');

          // Se o tamanho da string for 6 (MMYYYY), formate para MM / YY
          if (cleanedValue.length === 6) {
            formattedValue = cleanedValue?.replace(/^(.{2})(.{2})(.{2})$/, '$1 / $3');
          }

          // Atualiza o estado
          setCreditObject({
            ...creditObject,
            [key]: formattedValue
          });
        }
        return;
      case 'lkn_cc_cvc':
        if (value.length > 8) return;
        break;
      default:
        break;
    }
    setCreditObject({
      ...creditObject,
      [key]: value
    });
  };
  const wcComponents = window.wc.blocksComponents;
  const calculateInstallments = lknCCTotalCartCielo => {
    const installmentMin = 5;
    if (lknCCActiveInstallmentCielo === 'yes' && lknCCTotalCartCielo > 10) {
      const maxInstallments = lknCCInstallmentLimitCielo;
      for (let index = 1; index <= maxInstallments; index++) {
        const installmentAmount = (lknCCTotalCartCielo / index).toLocaleString('pt-BR', {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
        let nextInstallmentAmount = lknCCTotalCartCielo / index;
        if (nextInstallmentAmount < installmentMin) {
          break;
        }
        let formatedInterest = false;
        for (let t = 0; t < lknCCinstallmentsCielo.length; t++) {
          const installmentObj = lknCCinstallmentsCielo[t];
          if (installmentObj.id === index) {
            nextInstallmentAmount = (lknCCTotalCartCielo + lknCCTotalCartCielo * (parseFloat(installmentObj.interest) / 100)) / index;
            formatedInterest = new Intl.NumberFormat('pt-br', {
              style: 'currency',
              currency: 'BRL'
            }).format(nextInstallmentAmount);
          }
        }
        if (formatedInterest) {
          setOptions(prevOptions => [...prevOptions, {
            key: index,
            label: `${index}x de ${formatedInterest}`
          }]);
        } else {
          setOptions(prevOptions => [...prevOptions, {
            key: index,
            label: `${index}x de R$ ${installmentAmount} sem juros`
          }]);
        }
      }
    } else {
      setOptions(prevOptions => [...prevOptions, {
        key: '1',
        label: `1x de R$ ${lknCCTotalCartCielo} (à vista)`
      }]);
    }
  };
  window.wp.element.useEffect(() => {
    calculateInstallments(lknCCTotalCartCielo);
    const intervalId = setInterval(() => {
      var targetNode = document.querySelector('.wc-block-formatted-money-amount.wc-block-components-formatted-money-amount.wc-block-components-totals-footer-item-tax-value');
      // Configuração do observer: quais mudanças serão observadas
      if (targetNode) {
        var config = {
          childList: true,
          subtree: true,
          characterData: true
        };
        var changeValue = () => {
          setOptions([]);
          // Remover tudo exceto os números e a vírgula
          let newValue = targetNode.textContent.replace(/[^\d,]/g, '');

          // Substituir a vírgula por um ponto
          newValue = newValue.replace(',', '.');

          // Converter para número
          newValue = parseFloat(newValue);
          calculateInstallments(newValue);
        };
        changeValue();

        // Função de callback que será executada quando ocorrerem mudanças
        var callback = function (mutationsList, observer) {
          for (var mutation of mutationsList) {
            if (mutation.type === 'childList' || mutation.type === 'characterData') {
              changeValue();
            }
          }
        };

        // Cria uma instância do observer e o conecta ao nó alvo
        var observer = new MutationObserver(callback);
        observer.observe(targetNode, config);
        clearInterval(intervalId);
      }
    }, 500);
  }, []);
  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      // Verifica se todos os campos do creditObject estão preenchidos
      const allFieldsFilled = Object.values(creditObject).every(field => field.trim() !== '');
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
  }, [creditObject, emitResponse.responseTypes.ERROR, emitResponse.responseTypes.SUCCESS, onPaymentSetup]);
  return /*#__PURE__*/React.createElement(React.Fragment, null, /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("h4", null, "Pagamento processado pela Cielo API 3.0")), /*#__PURE__*/React.createElement(wcComponents.TextInput, {
    id: "lkn_cc_cardholder_name",
    label: lknCCTranslationsCielo.cardHolder,
    value: creditObject.lkn_cc_cardholder_name,
    onChange: value => {
      updateCreditObject('lkn_cc_cardholder_name', value);
    },
    required: true
  }), /*#__PURE__*/React.createElement(wcComponents.TextInput, {
    id: "lkn_ccno",
    label: lknCCTranslationsCielo.cardNumber,
    value: creditObject.lkn_ccno,
    onChange: value => {
      updateCreditObject('lkn_ccno', formatCreditCardNumber(value));
    },
    required: true
  }), /*#__PURE__*/React.createElement(wcComponents.TextInput, {
    id: "lkn_cc_expdate",
    label: lknCCTranslationsCielo.cardExpiryDate,
    value: creditObject.lkn_cc_expdate,
    onChange: value => {
      updateCreditObject('lkn_cc_expdate', value);
    },
    required: true
  }), /*#__PURE__*/React.createElement(wcComponents.TextInput, {
    id: "lkn_cc_cvc",
    label: lknCCTranslationsCielo.securityCode,
    value: creditObject.lkn_cc_cvc,
    onChange: value => {
      updateCreditObject('lkn_cc_cvc', value);
    },
    required: true
  }), /*#__PURE__*/React.createElement("div", {
    style: {
      marginBottom: '20px'
    }
  }), lknCCActiveInstallmentCielo === 'yes' && /*#__PURE__*/React.createElement(wcComponents.SortSelect, {
    id: "lkn_cc_installments",
    label: lknCCTranslationsCielo.installments,
    value: creditObject.lkn_cc_installments,
    onChange: event => {
      updateCreditObject('lkn_cc_installments', event.target.value);
    },
    options: options
  }));
};
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
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_CC_Block_Gateway_Cielo);