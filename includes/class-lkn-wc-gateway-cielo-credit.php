<?php
/**
 * Lkn_WC_Gateway_Cielo_Credit class
 *
 * @author   Link Nacional
 * @package  WooCommerce Cielo Payments Gateway
 * @since    1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cielo Credit Card Gateway.
 *
 * @class    Lkn_WC_Gateway_Cielo_Credit
 * @version  1.0.0
 */
class Lkn_WC_Gateway_Cielo_Credit extends WC_Payment_Gateway {
    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id                 = 'lkn_cielo_credit';
        $this->icon               = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields         = false;
        $this->supports           = [
            'products',
            'refunds',
        ];

        $this->method_title       = _x('Cielo - Cartão de crédito', 'Cielo payment method', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows credit card payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        // Action hook to load custom JavaScript
        // add_action( 'wp_enqueue_scripts', array( $this, 'payment_gateway_scripts' ) );
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type'    => 'checkbox',
                'label'   => __('Enable Credit Card Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __('Title', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default'     => _x('Cartão de crédito', 'Cielo payment method', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'lkn-wc-gateway-cielo'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'default'     => __('The goods are yours. No money needed.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'merchant_id' => [
                'title'       => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'merchant_key' => [
                'title'       => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'invoiceDesc' => [
                'title'       => __('Description', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Invoice description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'capture' => [
                'title'       => __('Capture', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label' => __('Enable automatic capture for payments', 'lkn-wc-gateway-cielo'),
                'default' => 'yes',
            ],
            'env' => [
                'title'       => __('Environment', 'lkn-wc-gateway-cielo'),
                'description' => __('Cielo API 3.0 environment.', 'lkn-wc-gateway-cielo'),
                'type'     => 'select',
                'options'  => [
                    'production'  => __('Produção', 'lkn-wc-gateway-cielo'),
                    'sandbox'  => __('Sandbox', 'lkn-wc-gateway-cielo'),
                ],
                'default' => 'sandbox',
                'desc_tip'    => true,
            ],
        ];
    }

    public function payment_fields() {
        // TODO style the payment form
        if ($this->description) {
            $this->description .= ' Test mode is enabled. You can use the dummy credit card numbers to test it.';
            echo wpautop(wp_kses_post($this->description));
        } ?>
    
        <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
    
            <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
    
            <div class="form-row form-row-wide">
                <label>Card Number <span class="required">*</span></label>
                <input id="lkn_ccno" name="lkn_ccno" type="text" autocomplete="off" maxlength="24" required>
            </div>
            <div class="form-row form-row-first">
                <label>Expiry Date <span class="required">*</span></label>
                <input id="lkn_expdate" name="lkn_expdate" type="text" autocomplete="off" placeholder="MM / YY" maxlength="7" required>
            </div>
            <div class="form-row form-row-last">
                <label>Card Code <span class="required">*</span></label>
                <input id="lkn_cvc" name="lkn_cvc" type="password" autocomplete="off" placeholder="CVC" maxlength="4" required>
            </div>
            <div class="clear"></div>
    
            <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>
    
            <div class="clear"></div>
    
        </fieldset>
    
        <?php
    }

    /**
     * Fields validation
     *
     * @return boolean
     */
    public function validate_fields() {
        if (empty($_POST['lkn_ccno'])) {
            wc_add_notice('Card number is required!', 'error');

            return false;
        }
        if (empty($_POST['lkn_expdate'])) {
            wc_add_notice('Expiration date is required!', 'error');

            return false;
        } elseif (!empty($_POST['lkn_expdate'])) {
            $expDateSplit = explode('/', sanitize_text_field($_POST['lkn_expdate']));
            $expDate = new DateTime('20' . $expDateSplit[1] . '-' . $expDateSplit[0] . '-01');
            $today = new DateTime();

            if ($today > $expDate) {
                wc_add_notice('Expiration date is invalid!', 'error');

                return false;
            }
        }
        if (empty($_POST['lkn_cvc'])) {
            wc_add_notice('CVV is required!', 'error');

            return false;
        }

        return true;
    }

    /**
     * Get card provider from number
     *
     * @param  string $cardNumber
     *
     * @return string|boolean
     */
    private function get_card_provider($cardNumber) {
        // Stores regex for Card Bin Tests
        $bin = [
            // elo
            '/(4011|431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/',
            // hipercard
            '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
            // diners
            '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
            // discover
            '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
            // jcb
            '/^(?:2131|1800|35\d{3})\d{11}$/',
            // aura
            '/^50[0-9]{14,17}$/',
            // amex
            '/^3[47][0-9]{13}$/',
            // mastercard
            '/^5[1-5]\d{14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{12}$/',
            // visa
            '/^4[0-9]{12}(?:[0-9]{3})?$/',
        ];

        // Test the cardNumber bin
        for ($c = 0; $c < count($bin); $c++) {
            if ($c > 10) {
                break;
            }
            if (preg_match($bin[$c], $cardNumber) == 1) {
                switch ($c) {
            case 0:
                return 'Elo';

                break;
            case 1:
                return 'Hipercard';

                break;
            case 2:
                return 'Diners';

                break;
            case 3:
                return 'Discover';

                break;
            case 4:
                return 'JCB';

                break;
            case 5:
                return 'Aura';

                break;
            case 6:
                return 'Amex';

                break;
            case 7:
                return 'Master';

                break;
            case 8:
                return 'Visa';

                break;
        }
            }
        }

        return false;
    }

    /**
     * Process the payment and return the result.
     *
     * @param  int  $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Card parameters
        $cardNum = preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_ccno']));
        $cardExpSplit = explode('/', preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_expdate'])));
        $cardExp = $cardExpSplit[0] . '/20' . $cardExpSplit[1];
        $cardCvv = sanitize_text_field($_POST['lkn_cvc']);
        $cardName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        // POST parameters
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $recurrencyId = uniqid('invoice_');
        $amount = number_format($order->get_total(), 2, '', '');
        $capture = ($this->get_option('capture') == 'yes') ? true : false;
        $description = sanitize_text_field($this->get_option('invoiceDesc'));
        $provider = $this->get_card_provider($cardNum);

        $args['headers'] = [
            'Content-Type' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantSecret,
        ];

        $args['body'] = json_encode([
            'MerchantOrderId' => $recurrencyId,
            'Payment' => [
                'Type' => 'CreditCard',
                'Amount' => $amount,
                'Installments' => 1,
                'Capture' => (bool)$capture,
                'SoftDescriptor' => $description,
                'CreditCard' => [
                    'CardNumber' => $cardNum,
                    'Holder' => $cardName,
                    'ExpirationDate' => $cardExp,
                    'SecurityCode' => $cardCvv,
                    'SaveCard' => false,
                    'Brand' => $provider,
                ],
            ],
        ]);


        $response = wp_remote_post($url . '1/sales', $args);

        error_log('response: ' . var_export($response, true), 3, __DIR__ . '/../err.log');

        $responseDecoded = json_decode($response['body']);

        // error_log('response decoded: ' . var_export($responseDecoded->Payment, true) . 'url: ' . var_export($url, true) . 'message: ' . var_export($response['body'], true) . ' POST: ' . var_export($_POST, true) . ' SEND REQUEST: ' . var_export($args, true), 3, __DIR__ . '/../err.log');

        if (isset($responseDecoded->Payment) && ($responseDecoded->Payment->Status == 1 || $responseDecoded->Payment->Status == 2)) {
            $order->payment_complete($responseDecoded->Payment->PaymentId);

            // Remove cart
            WC()->cart->empty_cart();

            $order->add_order_note('');

            // Return thankyou redirect
            return [
                'result' 	=> 'success',
                'redirect'	=> $this->get_return_url($order),
            ];
        } else {
            $message = __('Order payment failed. To make a successful payment using credit card, please review the gateway settings.', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }
    }

    /**
     * Proccess refund request in order
     *
     * @param  int $order_id
     * @param  float $amount
     * @param  string $reason
     *
     * @return boolean
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        // Do your refund here. Refund $amount for the order with ID $order_id
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $amount = number_format($amount, 2, '', '');

        $order = wc_get_order($order_id);
        $transactionId = $order->get_transaction_id();
        $order->add_order_note('');

        $args['headers'] = [
            'Content-Length' => 0,
            'Content-Type' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantSecret,
        ];

        $args['method'] = 'PUT';

        $response = wp_remote_request($url . '1/sales/' . $transactionId . '/void?amount=' . $amount, $args);
        $responseDecoded = json_decode($response['body']);

        error_log('headers: ' . var_export($args, true) . 'response: ' . var_export($response, true), 3, __DIR__ . '/../err.log');

        if ($responseDecoded->Status == 10 || $responseDecoded->Status == 11) {
            return true;
        } else {
            return false;
        }
    }
}
