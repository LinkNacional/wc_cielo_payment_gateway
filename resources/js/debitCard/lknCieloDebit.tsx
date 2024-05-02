const settings_debitCard = window.wc.wcSettings.getSetting('lkn_cielo_debit_data', {})
const label_debitCard = window.wp.htmlEntities.decodeEntities(settings_debitCard.title)

const Content_cieloDebit = (props) => {
  const { eventRegistration, emitResponse } = props
  const { onPaymentSetup } = eventRegistration

  // TODO Adicionar campos de CPF e Bairro
  const [textInput, setTextInput] = window.wp.element.useState('')
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
              maxipagoCreditNumber: creditObject.maxipagoCreditNumber,
              maxipagoCreditInstallments: creditObject.maxipagoCreditInstallments,
              maxipagoCreditExpiry: creditObject.maxipagoCreditExpiry,
              maxipagoCreditCvc: creditObject.maxipagoCreditCvc,
              maxipagoCreditHolderName: creditObject.maxipagoCreditHolderName,
              maxipago_card_nonce: nonce_maxipago,
              billing_cpf: creditObject.maxipago_credit_cpf
            },
          },
        };
      }

      return {
        type: emitResponse.responseTypes.ERROR,
        message: translations.fieldsNotFilled,
      };

    });

    // Cancela a inscrição quando este componente é desmontado.
    return () => {
      unsubscribe();
    };
  }, [
    creditObject, // Adiciona creditObject como dependência
    emitResponse.responseTypes.ERROR,
    emitResponse.responseTypes.SUCCESS,
    onPaymentSetup,
    translations, // Adicione translations como dependência
  ]);

  return (
    <>
    <h1>Oi</h1>
      <wcComponents.TextInput
        id="maxipago_credit_number"
        label="Seu número de cartão"
        value={textInput}
        onChange={(value) => {
          setTextInput(value)
        }}
      />
    </>
  )
}

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
}

window.wc.wcBlocksRegistry.registerPaymentMethod(Block_Gateway_Debit_Card)