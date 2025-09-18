const lknGooglePaySettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_google_pay_data', {});
const lknGooglePayLabelCielo = window.wp.htmlEntities.decodeEntities(lknGooglePaySettingsCielo.title);
const lknGooglePayContentCielo = props => {
  const { eventRegistration, emitResponse } = props;
  const { onPaymentSetup } = eventRegistration;

  // Registrar callback para quando o pagamento for processado
  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(() => {
      if (window.lknGooglePayData) {
        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              google_pay_data: JSON.stringify(window.lknGooglePayData),
              nonce_lkn_cielo_google_pay: lknGooglePaySettingsCielo.nonce,
              is_block_checkout: true
            }
          }
        };
      }
      return {
        type: emitResponse.responseTypes.ERROR,
        message: 'Dados do Google Pay não encontrados'
      };
    });

    return unsubscribe;
  }, [onPaymentSetup, emitResponse]);

  const googleButtonElement = document.querySelector('#lkn-google-pay-cielo-block');
  if (googleButtonElement && !googleButtonElement.firstChild) {
    const paymentsClient = new google.payments.api.PaymentsClient({
      environment: lknGooglePaySettingsCielo.env === 'PRODUCTION' ? 'PRODUCTION' : 'TEST'
    });

    // Definir métodos de pagamento permitidos
    const allowedPaymentMethods = [{
      type: 'CARD',
      parameters: {
        allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
        allowedCardNetworks: ['MASTERCARD', 'VISA', 'AMEX']
      },
      tokenizationSpecification: {
        type: 'PAYMENT_GATEWAY',
        parameters: {
          gateway: 'cielo',
          gatewayMerchantId: lknGooglePaySettingsCielo.googleMerchantId
        }
      }
    }];

    const button = paymentsClient.createButton({
      buttonColor: 'default',
      buttonType: lknGooglePaySettingsCielo.buttonText || 'pay',
      buttonRadius: 4,
      buttonBorderType: 'default_border',
      buttonLocale: lknGooglePaySettingsCielo.locale || 'pt',
      onClick: () => {
        // Pegar o valor total do elemento HTML
        const totalElement = document.querySelector('.wc-block-formatted-money-amount.wc-block-components-formatted-money-amount.wc-block-components-totals-footer-item-tax-value');
        let totalAmount = '0';

        if (totalElement) {
          const totalText = totalElement.textContent.trim();

          // Criar regex para manter apenas números, separador decimal e separador de milhares
          const thousandSeparator = lknGooglePaySettingsCielo.thousandSeparator || '.';
          const decimalSeparator = lknGooglePaySettingsCielo.decimalSeparator || ',';

          // Escapar caracteres especiais para regex
          const escapedThousand = thousandSeparator.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
          const escapedDecimal = decimalSeparator.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

          // Criar regex que mantém apenas dígitos e os separadores configurados
          const keepRegex = new RegExp('[^\\d' + escapedThousand + escapedDecimal + ']', 'g');
          const cleanAmount = totalText.replace(keepRegex, '');
          totalAmount = cleanAmount.replace(lknGooglePaySettingsCielo.thousandSeparator, '');
          totalAmount = totalAmount.replace(lknGooglePaySettingsCielo.decimalSeparator, '.');
        }

        const paymentDataRequest = {
          apiVersion: 2,
          apiVersionMinor: 0,
          allowedPaymentMethods: allowedPaymentMethods,
          merchantInfo: {
            merchantId: lknGooglePaySettingsCielo.googleMerchantId,
            merchantName: lknGooglePaySettingsCielo.googleMerchantName
          },
          transactionInfo: {
            totalPriceStatus: 'FINAL',
            totalPrice: totalAmount,
            currencyCode: lknGooglePaySettingsCielo.currency || 'BRL'
          }
        };

        paymentsClient.loadPaymentData(paymentDataRequest)
          .then(function (paymentData) {
            window.lknGooglePayData = paymentData;
            document.querySelector('.wc-block-checkout__actions_row button').click()
          })
          .catch(function (err) {
            console.error(err);
          });
      },
      allowedPaymentMethods: allowedPaymentMethods
    });

    googleButtonElement.appendChild(button);

    // Definir largura 100% para o botão
    button.firstChild.style.width = '100%';
  }

  return /*#__PURE__*/React.createElement("div", {
    id: "lkn-google-pay-cielo-block"
  });
};
const Lkn_GooglePay_Block_Gateway_Cielo = {
  name: 'lkn_cielo_google_pay',
  label: lknGooglePayLabelCielo,
  content: window.wp.element.createElement(lknGooglePayContentCielo),
  edit: window.wp.element.createElement(lknGooglePayContentCielo),
  canMakePayment: () => true,
  ariaLabel: lknGooglePayLabelCielo,
  supports: {
    features: lknGooglePaySettingsCielo.supports
  }
};
window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_GooglePay_Block_Gateway_Cielo);