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
        ];

        $this->supports = apply_filters('lkn_wc_cielo_debit_add_support', $this->supports);

        $this->method_title       = __('Cielo - Debit card', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows debit card payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        if (function_exists('wc_get_logger')) {
            $this->log = wc_get_logger();
        } else {
            $this->log = new WC_Logger();
        }

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);

        // Action hook to load custom JavaScript/CSS
        add_action('wp_enqueue_scripts', [$this, 'payment_gateway_scripts']);

        // Action hook to load admin JavaScript
        if (function_exists('get_plugins')) {
            // Only load if pro plugin doesn't exist
            $activeProPlugin = is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');

            if ($activeProPlugin == false) {
                add_action('admin_enqueue_scripts', [$this, 'admin_load_script']);
            }
        }
    }

    /**
     * Load admin JavaScript for the admin page
     *
     * @return void
     */
    public function admin_load_script() {
        wp_enqueue_script('lkn-wc-gateway-admin', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-wc-gateway-admin.js', ['wp-i18n'], $this->version, 'all');
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

        $env = $this->get_option('env');

        if ($env === 'production') {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-prd.js', ['wp-i18n'], $this->version, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        } else {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-sdb.js', ['wp-i18n'], $this->version, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        }

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
                'label'   => __('Enable Debit Card Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __('Title', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default'     => __('Debit card', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'lkn-wc-gateway-cielo'),
                'type'        => 'textarea',
                'default'     => __('Payment processed by Cielo API 3.0', 'lkn-wc-gateway-cielo'),
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
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
            'establishment_code' => [
                'title'       => __('Establishment Code', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Establishment code for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'merchant_name' => [
                'title'       => __('Merchant Name', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Establishment name registered on Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'mcc' => [
                'title'       => __('Establishment Category Code', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Establishment category code for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'invoiceDesc' => [
                'title'       => __('Invoice Description', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'default'     => __('order', 'lkn-wc-gateway-cielo'),
                'description' => __('Invoice description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ],
            'env' => [
                'title'       => __('Environment', 'lkn-wc-gateway-cielo'),
                'description' => __('Cielo API 3.0 environment.', 'lkn-wc-gateway-cielo'),
                'type'     => 'select',
                'options'  => [
                    'production'  => __('Produ????o', 'lkn-wc-gateway-cielo'),
                    'sandbox'  => __('Sandbox', 'lkn-wc-gateway-cielo'),
                ],
                'default' => 'sandbox',
                'desc_tip'    => true,
            ],
            'debug' => [
                'title'       => __('Debug', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label' => __('Enable log capture for payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ],
        ];

        $activeProPlugin = is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');

        if ($activeProPlugin == true) {
            $this->form_fields['capture'] = [
                'title'       => __('Capture', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label' => __('Enable automatic capture for payments', 'lkn-wc-gateway-cielo'),
                'default' => 'yes',
            ];
            $this->form_fields['license'] = [
                'title'       => __('License', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('License for Cielo API Pro plugin extensions.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => true,
            ];
        }
    }

    /**
     * Generate Cielo API 3.0 in auth token
     *
     * @return void
     */
    public function generate_debit_auth_token() {
        $env = $this->get_option('env');
        $clientId = $this->get_option('client_id');
        $clientSecret = $this->get_option('client_secret');
        $url = ($env === 'sandbox') ? 'https://mpisandbox.braspag.com.br/v2/auth/token/' : 'https://mpi.braspag.com.br/v2/auth/token/';

        $establishmentCode = $this->get_option('establishment_code');
        $merchantName = $this->get_option('merchant_name');
        $mcc = $this->get_option('mcc');
        $debug = $this->get_option('debug');

        $authCode = base64_encode($clientId . ':' . $clientSecret);

        $args['headers'] = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . $authCode,
        ];

        $args['body'] = json_encode([
            'EstablishmentCode' => $establishmentCode,
            'MerchantName' => $merchantName,
            'MCC' => $mcc,
        ]);


        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            if ($debug === 'yes') {
                $this->log->log('error', var_export($response->get_error_messages(), true), ['source' => 'woocommerce-cielo-debit']);
            }

            $message = __('Auth token generation failed.', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        } else {
            $responseDecoded = json_decode($response['body']);

            if (isset($responseDecoded->access_token)) {
                return $responseDecoded->access_token;
            } else {
                if ($debug === 'yes') {
                    $this->log->log('error', var_export($response, true), ['source' => 'woocommerce-cielo-debit']);
                }

                return false;
            }
        }
    }

    /**
     * Render the payment fields
     *
     * @return void
     */
    public function payment_fields() {
        $total_cart = 0;
        $accessToken = $this->generate_debit_auth_token();
        $url = get_page_link();

        if (!isset($_GET['pay_for_order'])) {
            $total_cart = number_format($this->get_order_total(), 2, '', '');
        } else {
            $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_GET['key']));
            $order = wc_get_order($order_id);
            $total_cart = number_format($order->get_total(), 2, '', '');
        }

        echo wpautop(wp_kses_post($this->description)); ?>
    
        <fieldset id="wc-<?php echo esc_attr($this->id); ?>-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
                
            <input type="hidden" name="lkn_auth_enabled" class="bpmpi_auth" value="true" />
            <input type="hidden" name="lkn_auth_enabled_notifyonly" class="bpmpi_auth_notifyonly" value="true" />
            <input type="hidden" name="lkn_access_token" class="bpmpi_accesstoken" value="<?php esc_attr_e($accessToken); ?>" />
            <input type="hidden" size="50" name="lkn_order_number" class="bpmpi_ordernumber" value="<?php esc_attr_e(uniqid()); ?>" />
            <input type="hidden" name="lkn_currency" class="bpmpi_currency" value="BRL"/>
            <input type="hidden" size="50" class="bpmpi_merchant_url" value="<?php esc_attr_e($url); ?>" />
            <input type="hidden" size="50" id="lkn_cielo_3ds_value" name="lkn_amount" class="bpmpi_totalamount" value="<?php esc_attr_e($total_cart); ?>" />
            <input type="hidden" size="2" name="lkn_installments" class="bpmpi_installments" value="1" />
            <input type="hidden" name="lkn_payment_method" class="bpmpi_paymentmethod" value="Debit" />
            <input type="hidden" id="lkn_bpmpi_cardnumber" class="bpmpi_cardnumber" />
            <input type="hidden" id="lkn_bpmpi_expmonth" maxlength="2" name="lkn_card_expiry_month" class="bpmpi_cardexpirationmonth" />
            <input type="hidden" id="lkn_bpmpi_expyear" maxlength="4" name="lkn_card_expiry_year" class="bpmpi_cardexpirationyear" />
            <input type="hidden" size="50" class="bpmpi_order_productcode" value="PHY" />
            <input type="hidden" id="lkn_cavv" name="lkn_cielo_3ds_cavv" value="" />
            <input type="hidden" id="lkn_eci" name="lkn_cielo_3ds_eci" value="" />
            <input type="hidden" id="lkn_ref_id" name="lkn_cielo_3ds_ref_id" value="" />
            <input type="hidden" id="lkn_version" name="lkn_cielo_3ds_version" value="" />
            <input type="hidden" id="lkn_xid" name="lkn_cielo_3ds_xid" value="" />

            <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
    
            <div class="form-row form-row-wide">
                <label><?php _e('Card Number', 'lkn-wc-gateway-cielo'); ?> <span class="required">*</span></label>
                <input id="lkn_dcno" name="lkn_dcno" type="tel" inputmode="numeric" class="masked" pattern="[0-9\s]{13,19}" autocomplete="cc-number" maxlength="19" placeholder="XXXX XXXX XXXX XXXX" data-valid-example="4444 4444 4444 4444" required>
            </div>
            <div class="form-row form-row-first">
                <label><?php _e('Expiry Date', 'lkn-wc-gateway-cielo'); ?> <span class="required">*</span></label>
                <input id="lkn_dc_expdate" name="lkn_dc_expdate" type="tel" placeholder="MM/YY" class="masked" pattern="(1[0-2]|0[1-9])\/(\d[\d])" autocomplete="cc-expdate" data-valid-example="05/28" required>
            </div>
            <div class="form-row form-row-last">
                <label><?php _e('Card Code', 'lkn-wc-gateway-cielo'); ?> <span class="required">*</span></label>
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
            wc_add_notice(__('Debit Card number is required!', 'lkn-wc-gateway-cielo'), 'error');

            return false;
        } elseif (!empty($_POST['lkn_dcno'])) {
            $cardNum = sanitize_text_field($_POST['lkn_dcno']);
            $isValid = !preg_match('/[^0-9\s]/', $cardNum);

            if ($isValid !== true || strlen($cardNum) < 12) {
                wc_add_notice(__('Debit Card number is invalid!', 'lkn-wc-gateway-cielo'), 'error');

                return false;
            }
        }

        if (empty($_POST['lkn_dc_expdate'])) {
            wc_add_notice(__('Expiration date is required!', 'lkn-wc-gateway-cielo'), 'error');

            return false;
        } elseif (!empty($_POST['lkn_dc_expdate'])) {
            $expDateSplit = explode('/', sanitize_text_field($_POST['lkn_dc_expdate']));
            $expDate = new DateTime('20' . $expDateSplit[1] . '-' . $expDateSplit[0] . '-01');
            $today = new DateTime();

            if ($today > $expDate) {
                wc_add_notice(__('Expiration date is invalid!', 'lkn-wc-gateway-cielo'), 'error');

                return false;
            }
        }

        if (empty($_POST['lkn_dc_cvc'])) {
            wc_add_notice(__('CVV is required!', 'lkn-wc-gateway-cielo'), 'error');

            return false;
        } elseif (!empty($_POST['lkn_dc_cvc'])) {
            $cvv = sanitize_text_field($_POST['lkn_dc_cvc']);
            $isValid = !preg_match('/\D/', $cvv);

            if ($isValid !== true || strlen($cvv) < 3) {
                wc_add_notice(__('CVV is invalid!', 'lkn-wc-gateway-cielo'), 'error');

                return false;
            }
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
        $cardNum = preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_dcno']));
        $cardExpSplit = explode('/', preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_dc_expdate'])));
        $cardExp = $cardExpSplit[0] . '/20' . $cardExpSplit[1];
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

        if ($currency !== 'BRL') {
            $amount = apply_filters('lkn_convert_amount', $amount, $currency);

            $order->add_meta_data('amount_converted', $amount, true);

            $amount = number_format($amount, 2, '', '');
        } else {
            $amount = number_format($amount, 2, '', '');
        }

        // Verify if authentication is data-only
        // @see {https://developercielo.github.io/manual/3ds}
        if ($eci == 4 && empty($cavv)) {
            $args['headers'] = [
                'Content-Type' => 'application/json',
                'MerchantId' => $merchantId,
                'MerchantKey' => $merchantSecret,
                'RequestId' => uniqid(),
            ];

            $args['body'] = json_encode([
                'MerchantOrderId' => $merchantOrderId,
                'Customer' => [
                    'Name' => $cardName,
                ],
                'Payment' => [
                    'Type' => 'DebitCard',
                    'Amount' => (int)$amount,
                    'Installments' => 1,
                    'Authenticate' => true,
                    'Capture' => (bool)$capture,
                    'SoftDescriptor' => $description,
                    'DebitCard' => [
                        'CardNumber' => $cardNum,
                        'Holder' => $cardName,
                        'ExpirationDate' => $cardExp,
                        'SecurityCode' => $cardCvv,
                        'Brand' => $provider,
                    ],
                    'ExternalAuthentication' => [
                        'Eci' => $eci,
                        'ReferenceId' => $refId,
                        'dataonly' => true,
                    ],
                ],
            ]);
        } else {
            $args['headers'] = [
                'Content-Type' => 'application/json',
                'MerchantId' => $merchantId,
                'MerchantKey' => $merchantSecret,
                'RequestId' => uniqid(),
            ];

            $args['body'] = json_encode([
                'MerchantOrderId' => $merchantOrderId,
                'Customer' => [
                    'Name' => $cardName,
                ],
                'Payment' => [
                    'Type' => 'DebitCard',
                    'Amount' => (int)$amount,
                    'Installments' => 1,
                    'Authenticate' => true,
                    'Capture' => (bool)$capture,
                    'SoftDescriptor' => $description,
                    'DebitCard' => [
                        'CardNumber' => $cardNum,
                        'Holder' => $cardName,
                        'ExpirationDate' => $cardExp,
                        'SecurityCode' => $cardCvv,
                        'Brand' => $provider,
                    ],
                    'ExternalAuthentication' => [
                        'Cavv' => $cavv,
                        'Xid' => $xid,
                        'Eci' => $eci,
                        'Version' => $version,
                        'ReferenceId' => $refId,
                    ],
                ],
            ]);
        }

        $response = wp_remote_post($url . '1/sales', $args);

        if (is_wp_error($response)) {
            if ($debug === 'yes') {
                $this->log->log('error', var_export($response->get_error_messages(), true), ['source' => 'woocommerce-cielo-debit']);
            }

            $message = __('Order payment failed. To make a successful payment using debit card, please review the gateway settings.', 'lkn-wc-gateway-cielo');

            throw new Exception($message);
        } else {
            $responseDecoded = json_decode($response['body']);

            if (isset($responseDecoded->Payment) && ($responseDecoded->Payment->Status == 1 || $responseDecoded->Payment->Status == 2)) {
                $order->payment_complete($responseDecoded->Payment->PaymentId);

                // Remove cart
                WC()->cart->empty_cart();

                $order->add_order_note(__('Payment completed successfully. Payment id:', 'lkn-wc-gateway-cielo') . ' ' . $responseDecoded->Payment->PaymentId);

                // Return thankyou redirect
                return [
                    'result' 	=> 'success',
                    'redirect'	=> $this->get_return_url($order),
                ];
            } else {
                if ($debug === 'yes') {
                    $this->log->log('error', var_export($response, true), ['source' => 'woocommerce-cielo-debit']);
                }

                $message = __('Order payment failed. Make sure your debit card is valid.', 'lkn-wc-gateway-cielo');

                throw new Exception($message);
            }
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
        $debug = $this->get_option('debug');
        $order = wc_get_order($order_id);
        $transactionId = $order->get_transaction_id();

        $response = apply_filters('lkn_wc_cielo_debit_refund', $url, $merchantId, $merchantSecret, $order_id, $amount);

        if (is_wp_error($response)) {
            if ($debug === 'yes') {
                $this->log->log('error', var_export($response->get_error_messages(), true), ['source' => 'woocommerce-cielo-debit']);
            }

            $order->add_order_note(__('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

            return false;
        } else {
            $responseDecoded = json_decode($response['body']);

            if ($responseDecoded->Status == 10 || $responseDecoded->Status == 11) {
                $order->add_order_note(__('Order refunded, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

                return true;
            } else {
                if ($debug === 'yes') {
                    $this->log->log('error', var_export($response, true), ['source' => 'woocommerce-cielo-debit']);
                }

                $order->add_order_note(__('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

                return false;
            }
        }
    }
}
