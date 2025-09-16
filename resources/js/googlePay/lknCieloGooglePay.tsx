const lknGooglePaySettingsCielo = window.wc.wcSettings.getSetting('lkn_cielo_google_pay_data', {})
const lknGooglePayLabelCielo = window.wp.htmlEntities.decodeEntities(lknGooglePaySettingsCielo.title)
console.log(lknGooglePaySettingsCielo)

const lknGooglePayContentCielo = (props) => {
  return (
    <div className="lkn-google-pay-cielo-block">
    </div>
  )
}


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

window.wc.wcBlocksRegistry.registerPaymentMethod(Lkn_GooglePay_Block_Gateway_Cielo)
