<?php
/**
 * Lkn_WC_Gateway_Cielo_Debit class
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
 * Cielo API 3.0 Debit Gateway.
 *
 * @class    Lkn_WC_Gateway_Cielo_Debit
 * @version  1.0.0
 */
class Lkn_WC_Gateway_Cielo_Debit extends WC_Payment_Gateway {
    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version = LKN_WC_CIELO_VERSION;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id                 = 'lkn_cielo_debit';
        $this->icon               = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields         = true;
        $this->supports           = [
            'products',
            'refunds',
        ];

        $this->method_title       = _x('Cielo - Cartão de débito', 'Dummy payment method', 'woocommerce-gateway-dummy');
        $this->method_description = __('Allows debit card payment with Cielo API 3.0.', 'woocommerce-gateway-dummy');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        // Action hook to load custom JavaScript/CSS
        add_action('wp_enqueue_scripts', [$this, 'payment_gateway_scripts']);
    }

    /**
     * Load gateway scripts/styles
     *
     * @return void
     */
    public function payment_gateway_scripts() {
        // Don't load scripts outside payment page
        if (
            !is_product()
            && !(is_cart() || is_checkout())
            && !isset($_GET['pay_for_order']) // wpcs: csrf ok.
            && !is_add_payment_method_page()
            && !isset($_GET['change_payment_method']) // wpcs: csrf ok.
            || (is_order_received_page())
        ) {
            return;
        }

        // If is not enabled bail.
        if ($this->enabled !== 'yes') {
            return;
        }

        wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script.js', [], $this->version, false);

        wp_enqueue_script('lkn-mask-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-mask.js', [], $this->version, false);

        wp_enqueue_script('lkn-cielo-debit-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/BP.Mpi.3ds20.min.js', [], $this->version, false);

        wp_enqueue_style('lkn-dc-style', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-dc-style.css', [], $this->version, 'all');

        wp_enqueue_style('lkn-mask', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-mask.css', [], $this->version, 'all');
    }

    /**
     * Initialise Gateway Settings Form Fields.
     *
     * @return void
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
            'client_id' => [
                'title'       => __('Client Id', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'client_secret' => [
                'title'       => __('Client Secret', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
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
                'title'       => __('Invoice Description', 'lkn-wc-gateway-cielo'),
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

    /**
     * Render the payment fields
     *
     * @return void
     */
    public function payment_fields() {
        $env = $this->get_option('env');
        // TODO style the payment form
        if ($env === 'sandbox') {
            $this->description .= ' Test mode is enabled. You can use the dummy debit card numbers to test it.';
            echo wpautop(wp_kses_post($this->description));
        } ?>
    
        <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
    
            <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
    
            <div class="form-row form-row-wide">
                <label>Card Number <span class="required">*</span></label>
                <input id="lkn_dcno" name="lkn_dcno" type="text" autocomplete="off" maxlength="19" required>
            </div>
            <div class="form-row form-row-first">
                <label>Expiry Date <span class="required">*</span></label>
                <input id="lkn_dc_expdate" name="lkn_dc_expdate" type="text" autocomplete="off" placeholder="MM/YY" class="masked" pattern="(1[0-2]|0[1-9])\/(\d[\d])" data-valid-example="05/28" required>
            </div>
            <div class="form-row form-row-last">
                <label>Card Code <span class="required">*</span></label>
                <input id="lkn_dc_cvc" name="lkn_dc_cvc" type="tel" autocomplete="off" placeholder="CVV" maxlength="4" required>
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
        if (empty($_POST['lkn_dcno'])) {
            wc_add_notice('Debit Card number is required!', 'error');

            return false;
        }
        if (empty($_POST['lkn_dc_expdate'])) {
            wc_add_notice('Expiration date is required!', 'error');

            return false;
        } elseif (!empty($_POST['lkn_dc_expdate'])) {
            $expDateSplit = explode('/', sanitize_text_field($_POST['lkn_dc_expdate']));
            $expDate = new DateTime('20' . $expDateSplit[1] . '-' . $expDateSplit[0] . '-01');
            $today = new DateTime();

            if ($today > $expDate) {
                wc_add_notice('Expiration date is invalid!', 'error');

                return false;
            }
        }
        if (empty($_POST['lkn_dc_cvc'])) {
            wc_add_notice('CVV is required!', 'error');

            return false;
        }

        return true;
    }

    /**
     * Process the payment and return the result.
     *
     * @param  int  $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $payment_result = 'success';

        if ('success' === $payment_result) {
            $order = wc_get_order($order_id);

            $order->payment_complete();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return [
                'result' 	=> 'success',
                'redirect'	=> $this->get_return_url($order),
            ];
        } else {
            $message = __('Order payment failed. To make a successful payment using Cielo debit card, please review the gateway settings.', 'woocommerce-gateway-dummy');

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
        $order->add_order_note('Order refunded, payment id: ' . $transactionId);

        $args['headers'] = [
            'Content-Length' => 0,
            'Content-Type' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantSecret,
        ];

        $args['method'] = 'PUT';

        $response = wp_remote_request($url . '1/sales/' . $transactionId . '/void?amount=' . $amount, $args);

        if (is_wp_error($response)) {
            error_log('Refund errors: ' . var_export($response->get_error_messages(), true), 3, __DIR__ . '/../err.log');

            $order->add_order_note('Order refunded error, payment id: ' . $transactionId);

            return false;
        } else {
            $responseDecoded = json_decode($response['body']);

            error_log('headers: ' . var_export($args, true) . 'response: ' . var_export($response, true), 3, __DIR__ . '/../err.log');

            if ($responseDecoded->Status == 10 || $responseDecoded->Status == 11) {
                return true;
            } else {
                return false;
            }
        }
    }
}
