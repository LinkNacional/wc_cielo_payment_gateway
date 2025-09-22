document.addEventListener('DOMContentLoaded', function () {
  let observer;

  function initializeGooglePay() {
    const methodDiv = document.querySelector('.payment_box.payment_method_lkn_cielo_google_pay');
    const googleButtonElement = methodDiv.querySelector('#gpay-button-online-api-id');
    
    if (methodDiv && !googleButtonElement) {
      const paymentsClient = new google.payments.api.PaymentsClient({
        environment: lknWcCieloGooglePayVars.env === 'PRODUCTION' ? 'PRODUCTION' : 'TEST'
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
            gatewayMerchantId: lknWcCieloGooglePayVars.googleMerchantId
          }
        }
      }];
    
      const button = paymentsClient.createButton({
        buttonColor: 'default',
        buttonType: lknWcCieloGooglePayVars.buttonText || 'pay',
        buttonRadius: 4,
        buttonBorderType: 'default_border',
        buttonLocale: lknWcCieloGooglePayVars.locale || 'pt',
        onClick: () => {
          // Pegar o valor total do elemento HTML
          const totalElement = document.querySelector('tr.order-total .woocommerce-Price-amount bdi');
          let totalAmount = '0';
          
          if (totalElement) {
            const totalText = totalElement.textContent.trim();
            
            // Criar regex para manter apenas números, separador decimal e separador de milhares
            const thousandSeparator = lknWcCieloGooglePayVars.thousandSeparator || '.';
            const decimalSeparator = lknWcCieloGooglePayVars.decimalSeparator || ',';
            
            // Escapar caracteres especiais para regex
            const escapedThousand = thousandSeparator.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            const escapedDecimal = decimalSeparator.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
            
            // Criar regex que mantém apenas dígitos e os separadores configurados
            const keepRegex = new RegExp('[^\\d' + escapedThousand + escapedDecimal + ']', 'g');
            const cleanAmount = totalText.replace(keepRegex, '');
            totalAmount = cleanAmount.replace(lknWcCieloGooglePayVars.thousandSeparator, '');
            totalAmount = totalAmount.replace(lknWcCieloGooglePayVars.decimalSeparator, '.');
          }
          
          const paymentDataRequest = {
            apiVersion: 2,
            apiVersionMinor: 0,
            allowedPaymentMethods: allowedPaymentMethods,
            merchantInfo: {
              merchantId: lknWcCieloGooglePayVars.googleMerchantId,
              merchantName: lknWcCieloGooglePayVars.googleMerchantName
            },
            transactionInfo: {
              totalPriceStatus: 'FINAL',
              totalPrice: totalAmount,
              currencyCode: lknWcCieloGooglePayVars.currency || 'BRL'
            }
          };

          paymentsClient.loadPaymentData(paymentDataRequest)
            .then(function(paymentData) {
              console.log('Payment data received:', paymentData);
              
              window.googlePayPaymentData = paymentData;
              
              let originalXHROpen = XMLHttpRequest.prototype.open;
              let originalXHRSend = XMLHttpRequest.prototype.send;
            
              XMLHttpRequest.prototype.open = function (method, url, async, user, password) {
                this._requestURL = url; // Armazena a URL da requisição
                originalXHROpen.apply(this, arguments);
              };
            
              XMLHttpRequest.prototype.send = function (body) {
                if (this._requestURL && this._requestURL.includes('?wc-ajax=checkout')) {
                  let xhr = this; // Armazena referência ao objeto XMLHttpRequest
            
                  // Converter URLSearchParams para objeto JavaScript
                  let params = new URLSearchParams(body);
                  let bodyObject = {};
                  
                  for (let [key, value] of params) {
                    bodyObject[key] = value;
                  }
                  
                  // Adicionar dados do Google Pay
                  if (window.googlePayPaymentData) {
                    bodyObject.google_pay_data = JSON.stringify(window.googlePayPaymentData);
                    bodyObject.nonce_lkn_cielo_google_pay = lknWcCieloGooglePayVars.nonce
                  }
                  
                  // Converter de volta para URLSearchParams
                  let newParams = new URLSearchParams();
                  for (let [key, value] of Object.entries(bodyObject)) {
                    newParams.append(key, value);
                  }
                  
                  body = newParams.toString();
          
                  originalXHRSend.call(xhr, body);
                } else {
                  originalXHRSend.apply(this, arguments);
                }
              };

              document.querySelector('#place_order')?.click()
            })
            .catch(function(err) {
              console.error(err);
            });
        }, 
        allowedPaymentMethods: allowedPaymentMethods
      });
    
      methodDiv.appendChild(button);
      
      // Definir largura 100% para o botão
      button.firstChild.style.width = '100%';
      
      // Para de observar uma vez que o elemento foi encontrado e processado
      if (observer) {
        observer.disconnect();
      }
    }
  }

  // Configura o observer para aguardar o elemento aparecer
  observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'childList') {
        initializeGooglePay();
      }
    });
  });

  // Tenta inicializar imediatamente caso o elemento já esteja presente
  initializeGooglePay();

  // Começa a observar mudanças no body
  observer.observe(document.body, {
    childList: true,
    subtree: true
  });
})
