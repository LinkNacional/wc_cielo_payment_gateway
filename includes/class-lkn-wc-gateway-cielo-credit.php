<?php
/**
 * Lkn_WC_Gateway_Cielo_Credit class.
 *
 * @author   Link Nacional
 *
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * Cielo API 3.0 Credit Card Gateway.
 *
 * @class    Lkn_WC_Gateway_Cielo_Credit
 *
 * @version  1.0.0
 */
class Lkn_WC_Gateway_Cielo_Credit extends WC_Payment_Gateway {
    /**
     * Define instructions to configure and use this plugin.
     *
     * @since   1.3.2
     *
     * @var string
     */
    public $instructions = '';

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     *
     * @var string the current version of this plugin
     */
    private $version = LKN_WC_CIELO_VERSION;

    /**
     * Log instance to store debug messages and codes.
     *
     * @since   1.3.2
     *
     * @var WC_Logger
     */
    private $log;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id = 'lkn_cielo_credit';
        $this->icon = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        $this->supports = apply_filters('lkn_wc_cielo_credit_add_support', $this->supports);

        $this->method_title = __('Cielo - Credit card', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows credit card payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        $this->log = new WC_Logger();

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        // Action hook to load custom JavaScript
        add_action('wp_enqueue_scripts', array($this, 'payment_gateway_scripts'));

        // Action hook to load admin JavaScript
        if (function_exists('get_plugins')) {
            // Only load if pro plugin doesn't exist
            $activeProPlugin = is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');

            if (false == $activeProPlugin) {
                add_action('admin_enqueue_scripts', array($this, 'admin_load_script'));
            }
        }
    }

    /**
     * Load admin JavaScript for the admin page.
     */
    public function admin_load_script() {
        wp_enqueue_script('lkn-wc-gateway-admin', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin.js', array('wp-i18n'), $this->version, 'all');
    }

    /**
     * Load gateway scripts/styles.
     */
    public function payment_gateway_scripts() {
        // Don't load scripts outside payment page
        if (
            ! is_checkout()
            && ! isset($_GET['pay_for_order']) // wpcs: csrf ok.
            && ! is_add_payment_method_page()
            && ! isset($_GET['change_payment_method']) // wpcs: csrf ok.
            || is_order_received_page()
        ) {
            return;
        }

        // If is not enabled bail.
        if ('yes' !== $this->enabled) {
            return;
        }

        $installmentArgs = array();
        $installmentArgs = apply_filters('lkn_wc_cielo_js_credit_args', array('installment_min' => '5'));

        wp_enqueue_script('lkn-mask-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/formatter.js', array('jquery'), $this->version, false);
        wp_enqueue_script('lkn-mask-script-load', plugin_dir_url(__FILE__) . '../resources/js/frontend/define-mask.js', array('lkn-mask-script', 'jquery'), $this->version, false);

        wp_enqueue_script('lkn-installment-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-cc-installment.js', array('jquery'), $this->version, false);
        wp_localize_script('lkn-installment-script', 'lkn_wc_cielo_credit', $installmentArgs);

        wp_enqueue_style('lkn-cc-style', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-cc-style.css', array(), $this->version, 'all');

        wp_enqueue_style('lkn-mask', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-mask.css', array(), $this->version, 'all');
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable Credit Card Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('Credit card', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'lkn-wc-gateway-cielo'),
                'type' => 'textarea',
                'default' => __('Payment processed by Cielo API 3.0', 'lkn-wc-gateway-cielo'),
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'merchant_id' => array(
                'title' => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'merchant_key' => array(
                'title' => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'invoiceDesc' => array(
                'title' => __('Invoice Description', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'default' => __('order', 'lkn-wc-gateway-cielo'),
                'description' => __('Invoice description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'env' => array(
                'title' => __('Environment', 'lkn-wc-gateway-cielo'),
                'description' => __('Cielo API 3.0 environment.', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'options' => array(
                    'production' => __('Production', 'lkn-wc-gateway-cielo'),
                    'sandbox' => __('Sandbox', 'lkn-wc-gateway-cielo'),
                ),
                'default' => 'sandbox',
                'desc_tip' => true,
            ),
            'debug' => array(
                'title' => __('Debug', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable log capture for payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ),
            'installment_payment' => array(
                'title' => __('Installment payments', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enables installment payments for amounts greater than 10,00 R$', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ),
        );

        $customConfigs = apply_filters('lkn_wc_cielo_get_custom_configs', array(), $this->id);

        if ( ! empty($customConfigs)) {
            $this->form_fields = array_merge($this->form_fields, $customConfigs);
        }
    }

    /**
     * Render the payment fields.
     */
    public function payment_fields() {
        $activeInstallment = $this->get_option('installment_payment');
        $total_cart = number_format($this->get_order_total(), 2, '.', '');
        $noLoginCheckout = isset($_GET['pay_for_order']) ? sanitize_text_field($_GET['pay_for_order']) : 'false';
        $installmentLimit = $this->get_option('installment_limit', 12);
        $installments = array();

        for ($c = 1; $c <= $installmentLimit; ++$c) {
            $interest = preg_replace('/\D/', '', $this->get_option($c . 'x', 0));
            if ($interest > 0) {
                $installments[] = array('id' => $c, 'interest' => $interest);
            }
        }

        if ('yes' === $activeInstallment) {
            if (isset($_GET['pay_for_order'])) {
                $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_GET['key']));
                $order = wc_get_order($order_id);
                $total_cart = number_format($order->get_total(), 2, '.', '');
            }
        }

        echo wpautop(wp_kses_post($this->description)); ?>

<fieldset id="wc-<?php esc_attr_e($this->id); ?>-cc-form"
    class="wc-credit-card-form wc-payment-form" style="background:transparent;">

    <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>

    <div class="form-row form-row-wide">
        <label><?php _e('Card Number', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input id="lkn_ccno" name="lkn_ccno" type="tel" inputmode="numeric" class="lkn-card-num" maxlength="24"
            placeholder="XXXX XXXX XXXX XXXX" required>
    </div>
    <div class="form-row form-row-first">
        <label><?php _e('Expiry Date', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input id="lkn_cc_expdate" name="lkn_cc_expdate" type="tel" inputmode="numeric" placeholder="MM/YY"
            class="lkn-card-exp" maxlength="7" required>
    </div>
    <div class="form-row form-row-last">
        <label><?php _e('Card Code', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input id="lkn_cc_cvc" name="lkn_cc_cvc" type="tel" inputmode="numeric" placeholder="CVV" class="lkn-cvv"
            maxlength="8" required>
    </div>
    <?php
            if ('yes' === $activeInstallment) {
                ?>
    <input id="lkn_cc_installment_total" type="hidden"
        value="<?php esc_attr_e($total_cart); ?>">
    <input id="lkn_cc_no_login_checkout" type="hidden"
        value="<?php esc_attr_e($noLoginCheckout); ?>">
    <input id="lkn_cc_installment_limit" type="hidden"
        value="<?php esc_attr_e($installmentLimit); ?>">
    <input id="lkn_cc_installment_interest" type="hidden"
        value="<?php esc_attr_e(json_encode($installments)); ?>">

    <div class="form-row form-row-wide">
        <label><?php _e('Installments', 'lkn-wc-gateway-cielo'); ?>
        </label>
        <select id="lkn_cc_installments" name="lkn_cc_installments">
            <option value="1" selected="1">1 x R$0,00 sem juros</option>
        </select>
    </div>
    <?php
            } ?>
    <div id="lkn-cc-notice"></div>
    <div class="clear"></div>

    <?php do_action('woocommerce_credit_card_form_end', $this->id); ?>

    <div class="clear"></div>

</fieldset>

<?php
    }

    /**
     * Fields validation.
     *
     * @return bool
     */
    public function validate_fields() {
        $validateCompatMode = $this->get_option('input_validation_compatibility', 'no');
        if ('no' === $validateCompatMode) {
            $ccnum = sanitize_text_field($_POST['lkn_ccno']);
            $expDate = sanitize_text_field($_POST['lkn_cc_expdate']);
            $cvv = sanitize_text_field($_POST['lkn_cc_cvc']);

            $validCcNumber = $this->validate_card_number($ccnum, true);
            $validExpDate = $this->validate_exp_date($expDate, true);
            $validCvv = $this->validate_cvv($cvv, true);

            if (true === $validCcNumber && true === $validExpDate && true === $validCvv) {
                return true;
            }

            return false;
        }

        return true;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Card parameters
        $cardNum = preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_ccno']));
        $cardExpSplit = explode('/', preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_cc_expdate'])));
        $cardExp = $cardExpSplit[0] . '/20' . $cardExpSplit[1];
        $cardExpShort = $cardExpSplit[0] . '/' . $cardExpSplit[1];
        $cardCvv = sanitize_text_field($_POST['lkn_cc_cvc']);
        $cardName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $installments = 1;

        // POST parameters
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $merchantOrderId = uniqid('invoice_');
        $amount = $order->get_total();
        $capture = ($this->get_option('capture', 'yes') == 'yes') ? true : false;
        $description = sanitize_text_field($this->get_option('invoiceDesc'));
        $description = preg_replace(array('/(á|à|ã|â|ä)/', '/(Á|À|Ã|Â|Ä)/', '/(é|è|ê|ë)/', '/(É|È|Ê|Ë)/', '/(í|ì|î|ï)/', '/(Í|Ì|Î|Ï)/', '/(ó|ò|õ|ô|ö)/', '/(Ó|Ò|Õ|Ô|Ö)/', '/(ú|ù|û|ü)/', '/(Ú|Ù|Û|Ü)/', '/(ñ)/', '/(Ñ)/', '/(ç)/', '/(Ç)/'), explode(' ', 'a A e E i I o O u U n N c C'), $description);
        $provider = $this->get_card_provider($cardNum);
        $debug = $this->get_option('debug');
        $currency = $order->get_currency();
        $activeInstallment = $this->get_option('installment_payment');

        if ($this->validate_card_number($cardNum, false) === false) {
            $message = __('Credit Card number is invalid!', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }
        if ($this->validate_exp_date($cardExpShort, false) === false) {
            $message = __('Expiration date is invalid!', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }
        if ($this->validate_cvv($cardCvv, false) === false) {
            $message = __('CVV is invalid!', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }
        if (empty($merchantId)) {
            $message = __('Invalid Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }
        if (empty($merchantSecret)) {
            $message = __('Invalid Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }

        // Convert the amount to equivalent in BRL
        if ('BRL' !== $currency) {
            $amount = apply_filters('lkn_wc_cielo_convert_amount', $amount, $currency);

            $order->add_meta_data('amount_converted', $amount, true);
        }

        // If installments option is active verify $_POST attribute
        if ('yes' === $activeInstallment) {
            $installments = (int) sanitize_text_field($_POST['lkn_cc_installments']);

            if ($installments > 12) {
                if (
                    'Elo' !== $provider
                    && 'Visa' !== $provider
                    && 'Master' !== $provider
                    && 'Amex' !== $provider
                    && 'Hipercard' !== $provider
                ) {
                    $message = __('Order payment failed. Installment quantity invalid.', 'lkn-wc-gateway-cielo');

                    throw new Exception($message);
                }
            }

            $order->add_order_note(__('Installments quantity', 'lkn-wc-gateway-cielo') . ' ' . $installments);
            $order->add_meta_data('installments', $installments, true);

            if ($this->get_option('installment_interest') === 'yes') {
                $interest = $this->get_option($installments . 'x', 0);
                $amount = apply_filters('lkn_wc_cielo_calculate_interest', $amount, $interest);
            }
        }

        $amountFormated = number_format($amount, 2, '', '');

        $args['headers'] = array(
            'Content-Type' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantSecret,
        );

        $args['body'] = json_encode(array(
            'MerchantOrderId' => $merchantOrderId,
            'Payment' => array(
                'Type' => 'CreditCard',
                'Amount' => (int) $amountFormated,
                'Installments' => $installments,
                'Capture' => (bool) $capture,
                'SoftDescriptor' => $description,
                'CreditCard' => array(
                    'CardNumber' => $cardNum,
                    'Holder' => $cardName,
                    'ExpirationDate' => $cardExp,
                    'SecurityCode' => $cardCvv,
                    'SaveCard' => false,
                    'Brand' => $provider,
                ),
            ),
        ));

        $response = wp_remote_post($url . '1/sales', $args);

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-credit'));
            }

            $message = __('Order payment failed. Please review the gateway settings.', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }
        $responseDecoded = json_decode($response['body']);

        if (isset($responseDecoded->Payment) && (1 == $responseDecoded->Payment->Status || 2 == $responseDecoded->Payment->Status)) {
            $order->payment_complete($responseDecoded->Payment->PaymentId);

            // Remove cart
            WC()->cart->empty_cart();

            $order->add_order_note(__('Payment completed successfully. Payment id:', 'lkn-wc-gateway-cielo') . ' ' . $responseDecoded->Payment->PaymentId);

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }
        if ('yes' === $debug) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-credit'));
        }

        $message = __('Order payment failed. Make sure your credit card is valid.', 'lkn-wc-gateway-cielo');

        throw new Exception($message);
    }

    /**
     * Proccess refund request in order.
     *
     * @param int    $order_id
     * @param float  $amount
     * @param string $reason
     *
     * @return bool
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        // Do your refund here. Refund $amount for the order with ID $order_id
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $debug = $this->get_option('debug');
        $order = wc_get_order($order_id);
        $transactionId = $order->get_transaction_id();

        $response = apply_filters('lkn_wc_cielo_credit_refund', $url, $merchantId, $merchantSecret, $order_id, $amount);

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-credit'));
            }

            $order->add_order_note(__('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return false;
        }
        $responseDecoded = json_decode($response['body']);

        if (isset($responseDecoded->Status) && (10 == $responseDecoded->Status || 11 == $responseDecoded->Status || 2 == $responseDecoded->Status || 1 == $responseDecoded->Status)) {
            $order->add_order_note(__('Order refunded, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return true;
        }
        if ('yes' === $debug) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-credit'));
        }

        $order->add_order_note(__('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

        return false;
    }

    /**
     * Validate card number.
     *
     * @param string $ccnum
     * @param bool   $renderNotice
     *
     * @return bool
     */
    private function validate_card_number($ccnum, $renderNotice) {
        if (empty($ccnum)) {
            if ($renderNotice) {
                $this->add_notice_once(__('Credit Card number is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        $isValid = ! preg_match('/[^0-9\s]/', $ccnum);

        if (true !== $isValid || strlen($ccnum) < 12) {
            if ($renderNotice) {
                $this->add_notice_once(__('Credit Card number is invalid!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }

        return true;
    }

    /**
     * Validate card expiration date.
     *
     * @param string $expDate
     * @param bool   $renderNotice
     *
     * @return bool
     */
    private function validate_exp_date($expDate, $renderNotice) {
        if (empty($expDate)) {
            if ($renderNotice) {
                $this->add_notice_once(__('Expiration date is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        $expDateSplit = explode('/', $expDate);

        try {
            $expDate = new DateTime('20' . trim($expDateSplit[1]) . '-' . trim($expDateSplit[0]) . '-01');
            $today = new DateTime();

            if ($today > $expDate) {
                if ($renderNotice) {
                    $this->add_notice_once(__('Credit card is expired!', 'lkn-wc-gateway-cielo'), 'error');
                }

                return false;
            }

            return true;
        } catch (Exception $e) {
            if ($renderNotice) {
                $this->add_notice_once(__('Expiration date is invalid!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
    }

    /**
     * Validate card cvv.
     *
     * @param string $cvv
     * @param bool   $renderNotice
     *
     * @return bool
     */
    private function validate_cvv($cvv, $renderNotice) {
        if (empty($cvv)) {
            if ($renderNotice) {
                $this->add_notice_once(__('CVV is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        $isValid = ! preg_match('/\D/', $cvv);

        if (true !== $isValid || strlen($cvv) < 3) {
            if ($renderNotice) {
                $this->add_notice_once(__('CVV is invalid!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }

        return true;
    }

    /**
     * Verify if WooCommerce notice exists before adding.
     *
     * @param string $message
     * @param string $type
     */
    private function add_notice_once($message, $type) {
        if ( ! wc_has_notice($message, $type)) {
            wc_add_notice($message, $type);
        }
    }

    /**
     * Get card provider from number.
     *
     * @param string $cardNumber
     *
     * @return string|bool
     */
    private function get_card_provider($cardNumber) {
        $brand = '';
        $brand = apply_filters('lkn_wc_cielo_get_card_brand', $brand, $cardNumber);

        $this->log->log('error', 'GET BRAND CIELO CARD: ' . var_export($brand, true), array('source' => 'woocommerce-cielo-credit'));

        if (empty($brand)) {
            // Stores regex for Card Bin Tests
            $bin = array(
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
            );

            // Test the cardNumber bin
            for ($c = 0; $c < count($bin); ++$c) {
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
        } else {
            return sanitize_text_field($brand);
        }
    }
}
?>