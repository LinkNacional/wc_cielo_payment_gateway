<?php
/**
 * Lkn_WC_Gateway_Cielo_Debit class.
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
 * Cielo API 3.0 Debit Gateway.
 *
 * @class    Lkn_WC_Gateway_Cielo_Debit
 *
 * @version  1.0.0
 */
class Lkn_WC_Gateway_Cielo_Debit extends WC_Payment_Gateway {
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
        $this->id = 'lkn_cielo_debit';
        $this->icon = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        $this->supports = apply_filters('lkn_wc_cielo_debit_add_support', $this->supports);

        $this->method_title = __('Cielo - Debit card', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows debit card payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo') . '<a href="https://www.linknacional.com.br/wordpress/woocommerce/cielo/#cartao-debito-cielo-configurar" target="_blank">' . __('Learn more how to configure.', 'lkn-wc-gateway-cielo') . '</a>';

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

        // Action hook to load custom JavaScript/CSS
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

        $env = $this->get_option('env');

        if ('production' === $env) {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-prd.js', array('wp-i18n', 'jquery'), $this->version, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        } else {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-sdb.js', array('wp-i18n', 'jquery'), $this->version, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        }

        wp_enqueue_script('lkn-mask-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/formatter.js', array('jquery'), $this->version, false);

        wp_enqueue_script('lkn-cielo-debit-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/BP.Mpi.3ds20.min.js', array('jquery'), $this->version, false);

        wp_enqueue_style('lkn-dc-style', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-dc-style.css', array(), $this->version, 'all');

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
                'label' => __('Enable Debit Card Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('Debit card', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'lkn-wc-gateway-cielo'),
                'type' => 'textarea',
                'default' => __('Payment processed by Cielo API 3.0', 'lkn-wc-gateway-cielo'),
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'client_id' => array(
                'title' => __('Client Id', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo 3DS 2.0 registration required (ask for eCommerce support).', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'client_secret' => array(
                'title' => __('Client Secret', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo 3DS 2.0 registration required (ask for eCommerce support).', 'lkn-wc-gateway-cielo'),
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
            'establishment_code' => array(
                'title' => __('Establishment Code', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment code for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'merchant_name' => array(
                'title' => __('Merchant Name', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment name registered on Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
            ),
            'mcc' => array(
                'title' => __('Establishment Category Code', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment category code for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
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
                    'production' => __('Produção', 'lkn-wc-gateway-cielo'),
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
        );

        $customConfigs = apply_filters('lkn_wc_cielo_get_custom_configs', array(), $this->id);

        if ( ! empty($customConfigs)) {
            $this->form_fields = array_merge($this->form_fields, $customConfigs);
        }
    }

    /**
     * Generate Cielo API 3.0 in auth token.
     */
    public function generate_debit_auth_token() {
        $env = $this->get_option('env');
        $clientId = $this->get_option('client_id');
        $clientSecret = $this->get_option('client_secret');
        $url = ('sandbox' === $env) ? 'https://mpisandbox.braspag.com.br/v2/auth/token/' : 'https://mpi.braspag.com.br/v2/auth/token/';

        $establishmentCode = $this->get_option('establishment_code');
        $merchantName = $this->get_option('merchant_name');
        $mcc = $this->get_option('mcc');
        $debug = $this->get_option('debug');

        $authCode = base64_encode($clientId . ':' . $clientSecret);

        $args['headers'] = array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $authCode,
        );

        $args['body'] = json_encode(array(
            'EstablishmentCode' => $establishmentCode,
            'MerchantName' => $merchantName,
            'MCC' => $mcc,
        ));

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-debit'));
            }

            $message = __('Auth token generation failed.', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }
        $responseDecoded = json_decode($response['body']);

        if (isset($responseDecoded->access_token)) {
            return $responseDecoded->access_token;
        }
        if ('yes' === $debug) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-debit'));
        }

        return false;
    }

    /**
     * Render the payment fields.
     */
    public function payment_fields() {
        $total_cart = number_format($this->get_order_total(), 2, '', '');
        $accessToken = $this->generate_debit_auth_token();
        $url = get_page_link();

        if (isset($_GET['pay_for_order'])) {
            $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_GET['key']));
            $order = wc_get_order($order_id);
            $total_cart = number_format($order->get_total(), 2, '', '');
        }

        echo wpautop(wp_kses_post($this->description)); ?>

<fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form"
    class="wc-credit-card-form wc-payment-form" style="background:transparent;">

    <input type="hidden" name="lkn_auth_enabled" class="bpmpi_auth" value="true" />
    <input type="hidden" name="lkn_auth_enabled_notifyonly" class="bpmpi_auth_notifyonly" value="true" />
    <input type="hidden" name="lkn_access_token" class="bpmpi_accesstoken"
        value="<?php esc_attr_e($accessToken); ?>" />
    <input type="hidden" size="50" name="lkn_order_number" class="bpmpi_ordernumber"
        value="<?php esc_attr_e(uniqid()); ?>" />
    <input type="hidden" name="lkn_currency" class="bpmpi_currency" value="BRL" />
    <input type="hidden" size="50" class="bpmpi_merchant_url"
        value="<?php esc_attr_e($url); ?>" />
    <input type="hidden" size="50" id="lkn_cielo_3ds_value" name="lkn_amount" class="bpmpi_totalamount"
        value="<?php esc_attr_e($total_cart); ?>" />
    <input type="hidden" size="2" name="lkn_installments" class="bpmpi_installments" value="1" />
    <input type="hidden" name="lkn_payment_method" class="bpmpi_paymentmethod" value="Debit" />
    <input type="hidden" id="lkn_bpmpi_cardnumber" class="bpmpi_cardnumber" />
    <input type="hidden" id="lkn_bpmpi_expmonth" maxlength="2" name="lkn_card_expiry_month"
        class="bpmpi_cardexpirationmonth" />
    <input type="hidden" id="lkn_bpmpi_expyear" maxlength="4" name="lkn_card_expiry_year"
        class="bpmpi_cardexpirationyear" />
    <input type="hidden" size="50" class="bpmpi_order_productcode" value="PHY" />
    <input type="hidden" id="lkn_cavv" name="lkn_cielo_3ds_cavv" value="" />
    <input type="hidden" id="lkn_eci" name="lkn_cielo_3ds_eci" value="" />
    <input type="hidden" id="lkn_ref_id" name="lkn_cielo_3ds_ref_id" value="" />
    <input type="hidden" id="lkn_version" name="lkn_cielo_3ds_version" value="" />
    <input type="hidden" id="lkn_xid" name="lkn_cielo_3ds_xid" value="" />

    <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>

    <div class="form-row form-row-wide">
        <label><?php _e('Card Number', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input id="lkn_dcno" name="lkn_dcno" type="tel" inputmode="numeric" class="lkn-card-num" maxlength="24"
            placeholder="XXXX XXXX XXXX XXXX" required>
    </div>
    <div class="form-row form-row-first">
        <label><?php _e('Expiry Date', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input id="lkn_dc_expdate" name="lkn_dc_expdate" type="tel" inputmode="numeric" placeholder="MM/YY"
            class="lkn-card-exp" maxlength="7" required>
    </div>
    <div class="form-row form-row-last">
        <label><?php _e('Card Code', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input id="lkn_dc_cvc" name="lkn_dc_cvc" type="tel" inputmode="numeric" autocomplete="off" placeholder="CVV"
            class="lkn-cvv" maxlength="4" required>
    </div>
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
            $dcnum = sanitize_text_field($_POST['lkn_dcno']);
            $expDate = sanitize_text_field($_POST['lkn_dc_expdate']);
            $cvv = sanitize_text_field($_POST['lkn_dc_cvc']);

            $validdcNumber = $this->validate_card_number($dcnum, true);
            $validExpDate = $this->validate_exp_date($expDate, true);
            $validCvv = $this->validate_cvv($cvv, true);

            if (true === $validdcNumber && true === $validExpDate && true === $validCvv) {
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
        $cardNum = preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_dcno']));
        $cardExpSplit = explode('/', preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_dc_expdate'])));
        $cardExp = $cardExpSplit[0] . '/20' . $cardExpSplit[1];
        $cardExpShort = $cardExpSplit[0] . '/' . $cardExpSplit[1];
        $cardCvv = sanitize_text_field($_POST['lkn_dc_cvc']);
        $cardName = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();

        // Authentication parameters
        $xid = sanitize_text_field($_POST['lkn_cielo_3ds_xid']);
        $cavv = sanitize_text_field($_POST['lkn_cielo_3ds_cavv']);
        $eci = sanitize_text_field($_POST['lkn_cielo_3ds_eci']);
        $version = sanitize_text_field($_POST['lkn_cielo_3ds_version']);
        $refId = sanitize_text_field($_POST['lkn_cielo_3ds_ref_id']);

        // POST parameters
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $merchantOrderId = uniqid('invoice_');
        $amount = $order->get_total();
        $capture = ($this->get_option('capture', 'yes') == 'yes') ? true : false;
        $description = sanitize_text_field($this->get_option('invoiceDesc'));
        $provider = $this->get_card_provider($cardNum);
        $debug = $this->get_option('debug');
        $currency = $order->get_currency();

        if ($this->validate_card_number($cardNum, false) === false) {
            $message = __('Debit Card number is invalid!', 'lkn-wc-gateway-cielo');

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
        if (empty($eci)) {
            $message = __('Invalid Cielo 3DS 2.0 authentication.', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        }

        if ('BRL' !== $currency) {
            $amount = apply_filters('lkn_wc_cielo_convert_amount', $amount, $currency);

            $order->add_meta_data('amount_converted', $amount, true);

            $amount = number_format($amount, 2, '', '');
        } else {
            $amount = number_format($amount, 2, '', '');
        }

        // Verify if authentication is data-only
        // @see {https://developercielo.github.io/manual/3ds}
        if (4 == $eci && empty($cavv)) {
            $args['headers'] = array(
                'Content-Type' => 'application/json',
                'MerchantId' => $merchantId,
                'MerchantKey' => $merchantSecret,
                'RequestId' => uniqid(),
            );

            $args['body'] = json_encode(array(
                'MerchantOrderId' => $merchantOrderId,
                'Customer' => array(
                    'Name' => $cardName,
                ),
                'Payment' => array(
                    'Type' => 'DebitCard',
                    'Amount' => (int) $amount,
                    'Installments' => 1,
                    'Authenticate' => true,
                    'Capture' => (bool) $capture,
                    'SoftDescriptor' => $description,
                    'DebitCard' => array(
                        'CardNumber' => $cardNum,
                        'Holder' => $cardName,
                        'ExpirationDate' => $cardExp,
                        'SecurityCode' => $cardCvv,
                        'Brand' => $provider,
                    ),
                    'ExternalAuthentication' => array(
                        'Eci' => $eci,
                        'ReferenceId' => $refId,
                        'dataonly' => true,
                    ),
                ),
            ));
        } else {
            if (empty($cavv)) {
                $message = __('Invalid Cielo 3DS 2.0 authentication.', 'lkn-wc-gateway-cielo');

                throw new Exception($message);
            }
            if (empty($xid)) {
                $message = __('Invalid Cielo 3DS 2.0 authentication.', 'lkn-wc-gateway-cielo');

                throw new Exception($message);
            }

            $args['headers'] = array(
                'Content-Type' => 'application/json',
                'MerchantId' => $merchantId,
                'MerchantKey' => $merchantSecret,
                'RequestId' => uniqid(),
            );

            $args['body'] = json_encode(array(
                'MerchantOrderId' => $merchantOrderId,
                'Customer' => array(
                    'Name' => $cardName,
                ),
                'Payment' => array(
                    'Type' => 'DebitCard',
                    'Amount' => (int) $amount,
                    'Installments' => 1,
                    'Authenticate' => true,
                    'Capture' => (bool) $capture,
                    'SoftDescriptor' => $description,
                    'DebitCard' => array(
                        'CardNumber' => $cardNum,
                        'Holder' => $cardName,
                        'ExpirationDate' => $cardExp,
                        'SecurityCode' => $cardCvv,
                        'Brand' => $provider,
                    ),
                    'ExternalAuthentication' => array(
                        'Cavv' => $cavv,
                        'Xid' => $xid,
                        'Eci' => $eci,
                        'Version' => $version,
                        'ReferenceId' => $refId,
                    ),
                ),
            ));
        }

        $response = wp_remote_post($url . '1/sales', $args);

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-debit'));
            }

            $message = __('Order payment failed. To make a successful payment using debit card, please review the gateway settings.', 'lkn-wc-gateway-cielo');

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
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-debit'));
        }

        $message = __('Order payment failed. Make sure your debit card is valid.', 'lkn-wc-gateway-cielo');

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

        $response = apply_filters('lkn_wc_cielo_debit_refund', $url, $merchantId, $merchantSecret, $order_id, $amount);

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-debit'));
            }

            $order->add_order_note(__('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return false;
        }
        $responseDecoded = json_decode($response['body']);

        if (10 == $responseDecoded->Status || 11 == $responseDecoded->Status) {
            $order->add_order_note(__('Order refunded, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return true;
        }
        if ('yes' === $debug) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-debit'));
        }

        $order->add_order_note(__('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

        return false;
    }

    /**
     * Validate card number.
     *
     * @param string $dcnum
     * @param bool   $renderNotice
     *
     * @return bool
     */
    private function validate_card_number($dcnum, $renderNotice) {
        if (empty($dcnum)) {
            if ($renderNotice) {
                $this->add_notice_once(__('Debit Card number is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        $isValid = ! preg_match('/[^0-9\s]/', $dcnum);

        if (true !== $isValid || strlen($dcnum) < 12) {
            if ($renderNotice) {
                $this->add_notice_once(__('Debit Card number is invalid!', 'lkn-wc-gateway-cielo'), 'error');
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
                    $this->add_notice_once(__('Debit card is expired!', 'lkn-wc-gateway-cielo'), 'error');
                }

                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->add_notice_once(__('Expiration date is invalid!', 'lkn-wc-gateway-cielo'), 'error');

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
            $this->add_notice_once(__('CVV is required!', 'lkn-wc-gateway-cielo'), 'error');

            return false;
        }
        $isValid = ! preg_match('/\D/', $cvv);

        if (true !== $isValid || strlen($cvv) < 3) {
            $this->add_notice_once(__('CVV is invalid!', 'lkn-wc-gateway-cielo'), 'error');

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