const settings_creditCard = window.wc.wcSettings.getSetting('lkn_cielo_credit_data', {})
const label_creditCard = window.wp.htmlEntities.decodeEntities(settings_creditCard.title)

const Content_cieloCredit = (props) => {
  const { eventRegistration, emitResponse } = props
  const { onPaymentSetup } = eventRegistration

  const [creditObject, setCreditObject] = window.wp.element.useState({
    creditNumber: '',
    creditExpiry: '',
    creditCvc: '',
    creditHolderName: '',
    creditInstallments: '1', // Definir padrão como 1 parcela
  })

  const wcComponents = window.wc.blocksComponents

  window.wp.element.useEffect(() => {
    const unsubscribe = onPaymentSetup(async () => {
      // Verifica se todos os campos do creditObject estão preenchidos
      const allFieldsFilled = Object.values(creditObject).every((field) => field.trim() !== '');

      if (allFieldsFilled) {
        return {
          type: emitResponse.responseTypes.SUCCESS,
          meta: {
            paymentMethodData: {
              creditNumber: creditObject.creditNumber,
              creditExpiry: creditObject.creditExpiry,
              creditCvc: creditObject.creditCvc,
              creditHolderName: creditObject.creditHolderName,
              creditInstallments: creditObject.creditInstallments
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
  }, [creditObject, 
      emitResponse.responseTypes.ERROR,
      emitResponse.responseTypes.SUCCESS,
      onPaymentSetup
    ]);

  return (
    <>
      <h1>Informações do Cartão de Crédito</h1>
      <wcComponents.TextInput
        id="creditNumber"
        label="Número do Cartão"
        value={creditObject.creditNumber}
        onChange={(value) => setCreditObject({ ...creditObject, creditNumber: value })}
      />

      <wcComponents.TextInput
        id="creditExpiry"
        label="Data de Validade"
        value={creditObject.creditExpiry}
        onChange={(value) => setCreditObject({ ...creditObject, creditExpiry: value })}
      />

      <wcComponents.TextInput
        id="creditCvc"
        label="Código de Segurança (CVV)"
        value={creditObject.creditCvc}
        onChange={(value) => setCreditObject({ ...creditObject, creditCvc: value })}
      />

      <wcComponents.TextInput
        id="creditHolderName"
        label="Nome do Titular do Cartão"
        value={creditObject.creditHolderName}
        onChange={(value) => setCreditObject({ ...creditObject, creditHolderName: value })}
      />

      {/* Adicione um campo de seleção para as parcelas, se necessário */}
      <wcComponents.Select
        id="creditInstallments"
        label="Parcelas"
        value={creditObject.creditInstallments}
        onChange={(value) => setCreditObject({ ...creditObject, creditInstallments: value })}
      />
    </>
  )
  
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