<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use DateTime;
use Exception;
use WC_Logger;
use WC_Payment_Gateway;

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
final class LknWCGatewayCieloDebit extends WC_Payment_Gateway {
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
    private $accessToken;

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

        $this->method_title = __('Cielo - Debit and credit card', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows debit and credit card payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo') . '<a href="https://www.linknacional.com.br/wordpress/woocommerce/cielo/#cartao-debito-cielo-configurar" target="_blank">' . __('Learn more how to configure.', 'lkn-wc-gateway-cielo') . '</a>' . '<br><br>' . '<p>' . __('To use the 3DS functionality it is necessary to register for 3DS 2.2 (request to eCommerce Support).', 'lkn-wc-gateway-cielo') . '<a href="https://www.cielo.com.br/atendimento/" target="_blank">' . __('Learn more how to configure.', 'lkn-wc-gateway-cielo') . '</a>' . '<p>';

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);
        $this->accessToken = $this->generate_debit_auth_token();

        $post = get_post();
        if ($post && has_shortcode($post->post_content, 'woocommerce_checkout')) {
            wp_enqueue_script('lkn-fix-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-fix.js', array('wp-i18n', 'jquery'), $this->version, false);
            wp_localize_script('lkn-fix-script', 'lknWcCieloPaymentGatewayToken', $this->accessToken);
        }
        $this->log = new WC_Logger();

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

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
    public function admin_load_script(): void {
        wp_enqueue_script('lkn-wc-gateway-admin', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin.js', array('wp-i18n'), $this->version, 'all');
    }

    /**
     * Load gateway scripts/styles.
     */
    public function payment_gateway_scripts(): void {
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
        $installmentArgs = array();
        $installmentArgs = apply_filters('lkn_wc_cielo_js_credit_args', array('installment_min' => '5'));

        if ('production' === $env) {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-prd.js', array('wp-i18n', 'jquery'), $this->version, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        } else {
            wp_enqueue_script('lkn-dc-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-dc-script-sdb.js', array('wp-i18n', 'jquery'), $this->version, false);
            wp_set_script_translations('lkn-dc-script', 'lkn-wc-gateway-cielo', LKN_WC_CIELO_TRANSLATION_PATH);
        }
        wp_localize_script('lkn-dc-script', 'lknDCDirScript3DSCieloShortCode', LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/BP.Mpi.3ds20.min.js');
        wp_enqueue_script('lkn-mask-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/formatter.js', array('jquery'), $this->version, false);
        wp_enqueue_script('lkn-mask-script-load', plugin_dir_url(__FILE__) . '../resources/js/frontend/define-mask.js', array('lkn-mask-script', 'jquery'), $this->version, false);

        wp_enqueue_script('lkn-cc-dc-installment-script', plugin_dir_url(__FILE__) . '../resources/js/frontend/lkn-cc-dc-installment.js', array('jquery'), $this->version, false);
        wp_localize_script('lkn-cc-dc-installment-script', 'lknWCCieloCredit', $installmentArgs);

        wp_enqueue_style('lkn-dc-style', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-dc-style.css', array(), $this->version, 'all');

        wp_enqueue_style('lkn-mask', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-mask.css', array(), $this->version, 'all');
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields(): void {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable Debit and Credit Card Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'default' => __('Debit and credit card', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'description' => array(
                'title' => __('Description', 'lkn-wc-gateway-cielo'),
                'type' => 'textarea',
                'default' => __('Payment processed by Cielo API 3.0', 'lkn-wc-gateway-cielo'),
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'client_id' => array(
                'title' => __('Client Id', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo 3DS 2.2 registration required (ask for eCommerce support).', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'client_secret' => array(
                'title' => __('Client Secret', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo 3DS 2.2 registration required (ask for eCommerce support).', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'merchant_id' => array(
                'title' => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'merchant_key' => array(
                'title' => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type' => 'password',
                'description' => __('Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'establishment_code' => array(
                'title' => __('Establishment Code', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment code for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'merchant_name' => array(
                'title' => __('Merchant Name', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment name registered on Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'mcc' => array(
                'title' => __('Establishment Category Code', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'description' => __('Establishment category code for Cielo 3DS E-Commerce 3.0.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'required' => 'required'
                )
            ),
            'invoiceDesc' => array(
                'title' => __('Invoice Description', 'lkn-wc-gateway-cielo'),
                'type' => 'text',
                'default' => __('order', 'lkn-wc-gateway-cielo'),
                'description' => __('Invoice description that the customer will see on your checkout (special characters are not accepted).', 'lkn-wc-gateway-cielo'),
                'desc_tip' => true,
                'custom_attributes' => array(
                    'maxlength' => 50, // Tamanho máximo permitido
                    'pattern' => '[a-zA-Z]+( [a-zA-Z]+)*', // não pode conter espaços, traços, caracteres especiais ou números, apenas letras
                    'required' => 'required'
                )
            ),
            'env' => array(
                'title' => __('Environment', 'lkn-wc-gateway-cielo'),
                'description' => __('Cielo API 3.0 environment.', 'lkn-wc-gateway-cielo'),
                'type' => 'select',
                'options' => array(
                    'production' => __('Production', 'lkn-wc-gateway-cielo'),
                    'sandbox' => __('Development', 'lkn-wc-gateway-cielo'),
                ),
                'default' => 'production',
                'desc_tip' => true,
            ),
            'debug' => array(
                'title' => __('Debug', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => sprintf(
                    '%1$s. <a href="%2$s">%3$s</a>',
                    __('Enable log capture for payments', 'lkn-wc-gateway-cielo'),
                    admin_url('admin.php?page=wc-status&tab=logs'),
                    __('View logs', 'lkn-wc-gateway-cielo')
                ),
                'default' => 'no',
            ),
            'installment_payment' => array(
                'title' => __('Installment payments', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enables installment payments for amounts greater than 10,00 R$', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ),
            'placeholder' => array(
                'title' => __('Input placeholders', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enables input placeholders for debit/credit card fields for classic checkout', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
            ),
            'nonce_compatibility' => array(
                'title' => __('Nonce verification compatibility mode', 'lkn-wc-gateway-cielo'),
                'description' => __('Enable only if your checkout page is facing nonce verification issue', 'lkn-wc-gateway-cielo'),
                'label' => __('Enable for checkout page validation compatibility', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'default' => 'no',
                'desc_tip' => true,
            )
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
        try {
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

            $args['body'] = wp_json_encode(array(
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
        } catch ( Exception $e ) {
            $this->add_error( $e->getMessage() );
            $debug = $this->get_option('debug');

            if ('yes' === $debug) {
                $this->log->log('error', var_export($e->getMessage(), true), array('source' => 'woocommerce-cielo-debit'));
            }
            return false;
        }
    }

    /**
     * Calculate the total value of items in the WooCommerce cart.
     */
    public static function lknGetCartTotal() {
        $cart = WC()->cart;

        if (empty($cart)) {
            return 0;
        }

        $cart_items = $cart->get_cart();
        $total = 0;
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $product = $cart_item['data'];
            $total += $product->get_price() * $cart_item['quantity'];
        }
        return $total;

        return 0;
    }

    /**
     * Render the payment fields.
     */
    public function payment_fields(): void {
        $total_cart = number_format($this->get_order_total(), 2, '', '');
        $accessToken = $this->accessToken;
        $url = get_page_link();
        $nonce = wp_create_nonce( 'nonce_lkn_cielo_debit');
        $placeholder = $this->get_option('placeholder', 'no');
        $placeholderEnabled = false;

        if ('yes' === $placeholder) {
            $placeholderEnabled = true;
        }

        $activeInstallment = $this->get_option('installment_payment');
        $noLoginCheckout = isset($_GET['pay_for_order']) ? sanitize_text_field($_GET['pay_for_order']) : 'false';
        $installmentLimit = $this->get_option('installment_limit', 12);
        $installments = array();
        $installmentsTotal = number_format($this->get_order_total(), 2, '.', '');

        if (isset($_GET['pay_for_order'])) {
            $order_id = wc_get_order_id_by_order_key(sanitize_text_field($_GET['key']));
            $order = wc_get_order($order_id);
            $total_cart = number_format($order->get_total(), 2, '', '');
        }

        echo wp_kses_post(wpautop($this->description)); ?>

<fieldset
    id="wc-<?php echo esc_attr($this->id); ?>-cc-form"
    class="wc-credit-card-form wc-payment-form"
    style="background:transparent;"
>
    <input
        type="hidden"
        id="lkn_cielo_3ds_installment_show"
        value="no"
    />
    <input
        type="hidden"
        name="nonce_lkn_cielo_debit"
        class="nonce_lkn_cielo_debit"
        value="<?php echo esc_attr($nonce); ?>"
    />
    <input
        type="hidden"
        name="lkn_auth_enabled"
        class="bpmpi_auth"
        value="true"
    />
    <input
        type="hidden"
        name="lkn_auth_enabled_notifyonly"
        class="bpmpi_auth_notifyonly"
        value="true"
    />
    <input
        type="hidden"
        name="lkn_access_token"
        class="bpmpi_accesstoken"
        value="<?php echo esc_attr($accessToken); ?>"
    />
    <input
        type="hidden"
        size="50"
        name="lkn_order_number"
        class="bpmpi_ordernumber"
        value="<?php echo esc_attr(uniqid()); ?>"
    />
    <input
        type="hidden"
        name="lkn_currency"
        class="bpmpi_currency"
        value="BRL"
    />
    <input
        type="hidden"
        size="50"
        class="bpmpi_merchant_url"
        value="<?php echo esc_attr($url); ?>"
    />
    <input
        type="hidden"
        size="50"
        id="lkn_cielo_3ds_value"
        name="lkn_amount"
        class="bpmpi_totalamount"
        value="<?php echo esc_attr($total_cart); ?>"
    />
    <input
        type="hidden"
        size="2"
        name="lkn_installments"
        class="bpmpi_installments"
        value="1"
    />
    <input
        type="hidden"
        name="lkn_payment_method"
        class="bpmpi_paymentmethod"
        value="Debit"
    />
    <input
        type="hidden"
        id="lkn_bpmpi_cardnumber"
        class="bpmpi_cardnumber"
    />
    <input
        type="hidden"
        id="lkn_bpmpi_expmonth"
        maxlength="2"
        name="lkn_card_expiry_month"
        class="bpmpi_cardexpirationmonth"
    />
    <input
        type="hidden"
        id="lkn_bpmpi_expyear"
        maxlength="4"
        name="lkn_card_expiry_year"
        class="bpmpi_cardexpirationyear"
    />
    <input
        type="hidden"
        size="50"
        class="bpmpi_order_productcode"
        value="PHY"
    />
    <input
        type="hidden"
        id="lkn_cavv"
        name="lkn_cielo_3ds_cavv"
        value="true"
    />
    <input
        type="hidden"
        id="lkn_eci"
        name="lkn_cielo_3ds_eci"
        value="true"
    />
    <input
        type="hidden"
        id="lkn_ref_id"
        name="lkn_cielo_3ds_ref_id"
        value="true"
    />
    <input
        type="hidden"
        id="lkn_version"
        name="lkn_cielo_3ds_version"
        value="true"
    />
    <input
        type="hidden"
        id="lkn_xid"
        name="lkn_cielo_3ds_xid"
        value="true"
    />

    <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>

    <div class="form-row form-row-wide">
        <label
            for="lkn_dc_cardholder_name"><?php esc_html_e('Card Holder Name', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input
            id="lkn_dc_cardholder_name"
            name="lkn_dc_cardholder_name"
            type="text"
            autocomplete="cc-name"
            required
            placeholder="<?php echo $placeholderEnabled ? esc_attr('John Doe') : ''; ?>"
            data-placeholder="<?php echo $placeholderEnabled ? esc_attr('John Doe') : ''; ?>"
        >
    </div>

    <div class="form-row form-row-wide">
        <label
            for="lkn_dcno"><?php esc_html_e('Card Number', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input
            id="lkn_dcno"
            name="lkn_dcno"
            type="tel"
            inputmode="numeric"
            class="lkn-card-num"
            maxlength="24"
            required
            placeholder="<?php echo $placeholderEnabled ? esc_attr('XXXX XXXX XXXX XXXX') : ''; ?>"
            data-placeholder="<?php echo $placeholderEnabled ? esc_attr('XXXX XXXX XXXX XXXX') : ''; ?>"
        >
    </div>
    <div class="form-row form-row-wide">
        <label
            for="lkn_dc_expdate"><?php esc_html_e('Expiry Date', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input
            id="lkn_dc_expdate"
            name="lkn_dc_expdate"
            type="tel"
            inputmode="numeric"
            class="lkn-card-exp"
            maxlength="7"
            required
            placeholder="<?php echo $placeholderEnabled ? esc_attr('MM/YY') : ''; ?>"
            data-placeholder="<?php echo $placeholderEnabled ? esc_attr('MM/YY') : ''; ?>"
        >
    </div>
    <div class="form-row form-row-wide">
        <label
            for="lkn_dc_cvc"><?php esc_html_e('Security Code', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span></label>
        <input
            id="lkn_dc_cvc"
            name="lkn_dc_cvc"
            type="tel"
            inputmode="numeric"
            autocomplete="off"
            class="lkn-cvv"
            maxlength="4"
            required
            placeholder="<?php echo $placeholderEnabled ? esc_attr('CVV') : ''; ?>"
            data-placeholder="<?php echo $placeholderEnabled ? esc_attr('CVV') : ''; ?>"
        >
    </div>
    <div class="form-row form-row-wide">
        <label
            for="lkn_cc_type"><?php esc_html_e('Card type', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span>
        </label>
        <select
            id="lkn_cc_type"
            name="lkn_cc_type"
        >
            <option
                value="Credit"
                selected="1"
            ><?php esc_html_e('Credit card', 'lkn-wc-gateway-cielo'); ?>
            </option>
            <option value="Debit">
                <?php esc_html_e('Debit card', 'lkn-wc-gateway-cielo'); ?>
            </option>
        </select>
    </div>

    <?php if ('yes' === $activeInstallment) { ?>
    <input
        id="lkn_cc_dc_installment_total"
        type="hidden"
        value="<?php echo esc_attr($installmentsTotal); ?>"
    >
    <input
        id="lkn_cc_dc_no_login_checkout"
        type="hidden"
        value="<?php echo esc_attr($noLoginCheckout); ?>"
    >
    <input
        id="lkn_cc_dc_installment_limit"
        type="hidden"
        value="<?php echo esc_attr($installmentLimit); ?>"
    >
    <input
        id="lkn_cc_dc_installment_interest"
        type="hidden"
        value="<?php echo esc_attr(wp_json_encode($installments)); ?>"
    >

    <div
        id="lkn-cc-dc-installment-row"
        class="form-row form-row-wide"
    >
        <label
            for="lkn_cc_dc_installments"><?php esc_html_e('Installments', 'lkn-wc-gateway-cielo'); ?>
            <span class="required">*</span>
        </label>
        <select
            id="lkn_cc_dc_installments"
            name="lkn_cc_installments"
        >
            <option
                value="1"
                selected="1"
            >1 x R$0,00 sem juros</option>
        </select>
    </div>
    <?php } ?>

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
        if ( ! wp_verify_nonce($_POST['nonce_lkn_cielo_debit'], 'nonce_lkn_cielo_debit')) {
            $this->log->log('error', 'Nonce verification failed. Nonce: ' . var_export($_POST['nonce_lkn_cielo_debit'], true), array('source' => 'woocommerce-cielo-debit'));
            $this->add_notice_once(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo'), 'error');
            return false;
        }
        if ('no' === $validateCompatMode) {
            $dcnum = sanitize_text_field($_POST['lkn_dcno']);
            $expDate = sanitize_text_field($_POST['lkn_dc_expdate']);
            $cvv = sanitize_text_field($_POST['lkn_dc_cvc']);
            $cardName = sanitize_text_field($_POST['lkn_dc_cardholder_name']);

            $validdcNumber = $this->validate_card_number($dcnum, true);
            $validExpDate = $this->validate_exp_date($expDate, true);
            $validCvv = $this->validate_cvv($cvv, true);
            $validCardHolder = $this->validate_card_holder_name($cardName, true);

            if (true === $validdcNumber && true === $validExpDate && true === $validCvv && true === $validCardHolder) {
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
        $nonceInactive = $this->get_option('nonce_compatibility', 'no');

        if ( ! wp_verify_nonce($_POST['nonce_lkn_cielo_debit'], 'nonce_lkn_cielo_debit') && 'no' === $nonceInactive) {
            $this->log->log('error', 'Nonce verification failed. Nonce: ' . var_export($_POST['nonce_lkn_cielo_debit'], true), array('source' => 'woocommerce-cielo-debit'));
            $this->add_notice_once(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo'), 'error');
            throw new Exception(esc_attr(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo')));
        }

        $order = wc_get_order($order_id);

        // Card parameters
        $cardNum = preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_dcno']));
        $cardExpSplit = explode('/', preg_replace('/\s/', '', sanitize_text_field($_POST['lkn_dc_expdate'])));
        $cardExp = $cardExpSplit[0] . '/20' . $cardExpSplit[1];
        $cardExpShort = $cardExpSplit[0] . '/' . $cardExpSplit[1];
        $cardCvv = sanitize_text_field($_POST['lkn_dc_cvc']);
        $cardName = sanitize_text_field($_POST['lkn_dc_cardholder_name']);
        $cardType = sanitize_text_field($_POST['lkn_cc_type']);
        $installments = 1;

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
        $description = preg_replace('/[^a-zA-Z\s]+/', '', $description);
        $description = preg_replace('/\s+/', ' ', $description);
        $provider = $this->get_card_provider($cardNum);
        $debug = $this->get_option('debug');
        $currency = $order->get_currency();
        $activeInstallment = $this->get_option('installment_payment');

        if ($this->validate_card_holder_name($cardName, false) === false) {
            $message = __('Card Holder Name is required!', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if ($this->validate_card_number($cardNum, false) === false) {
            $message = __('Debit Card number is invalid!', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if ($this->validate_exp_date($cardExpShort, false) === false) {
            $message = __('Expiration date is invalid!', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if ($this->validate_cvv($cardCvv, false) === false) {
            $message = __('CVV is invalid!', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if (empty($merchantId)) {
            $message = __('Invalid Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if (empty($merchantSecret)) {
            $message = __('Invalid Cielo API 3.0 credentials.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if (empty($eci)) {
            $message = __('Invalid Cielo 3DS 2.2 authentication.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }

        if ('BRL' !== $currency) {
            $amount = apply_filters('lkn_wc_cielo_convert_amount', $amount, $currency);

            $order->add_meta_data('amount_converted', $amount, true);

            $amount = number_format($amount, 2, '', '');
        } else {
            $amount = number_format($amount, 2, '', '');
        }

        // If installments option is active verify $_POST attribute
        if ('yes' === $activeInstallment && 'Credit' == $cardType) {
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

                    throw new Exception(esc_attr($message));
                }
            }

            $order->add_order_note(__('Installments quantity', 'lkn-wc-gateway-cielo') . ' ' . $installments);
            $order->add_meta_data('installments', $installments, true);

            if ($this->get_option('installment_interest') === 'yes') {
                $interest = $this->get_option($installments . 'x', 0);
                $amount = apply_filters('lkn_wc_cielo_calculate_interest', $amount, $interest);
            }
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

            $body = array(
                'MerchantOrderId' => $merchantOrderId,
                'Customer' => array(
                    'Name' => $cardName,
                ),
                'Payment' => array(
                    'Type' => $cardType . "Card",
                    'Amount' => (int) $amount,
                    'Installments' => $installments,
                    'Authenticate' => true,
                    'Capture' => (bool) $capture,
                    'SoftDescriptor' => $description,
                    $cardType . "Card" => array(
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
            );

            $body = apply_filters('lkn_wc_cielo_process_body', $body, $_POST, $order_id);
            $args['body'] = wp_json_encode($body);
        } else {
            if (empty($cavv)) {
                $message = __('Invalid Cielo 3DS 2.2 authentication.', 'lkn-wc-gateway-cielo');

                throw new Exception(esc_attr($message));
            }
            if (empty($xid)) {
                $message = __('Invalid Cielo 3DS 2.2 authentication.', 'lkn-wc-gateway-cielo');

                throw new Exception(esc_attr($message));
            }

            $args['headers'] = array(
                'Content-Type' => 'application/json',
                'MerchantId' => $merchantId,
                'MerchantKey' => $merchantSecret,
                'RequestId' => uniqid(),
            );

            $body = array(
                'MerchantOrderId' => $merchantOrderId,
                'Customer' => array(
                    'Name' => $cardName,
                ),
                'Payment' => array(
                    'Type' => $cardType . "Card",
                    'Amount' => (int) $amount,
                    'Installments' => $installments,
                    'Authenticate' => true,
                    'Capture' => (bool) $capture,
                    'SoftDescriptor' => $description,
                    $cardType . "Card" => array(
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
            );

            $body = apply_filters('lkn_wc_cielo_process_body', $body, $_POST, $order_id);

            $args['body'] = wp_json_encode($body);
        }

        $response = wp_remote_post($url . '1/sales', $args);

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-debit'));
            }

            $message = __('Order payment failed. To make a successful payment using debit card, please review the gateway settings.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        $responseDecoded = json_decode($response['body']);

        if (isset($responseDecoded->Payment) && (1 == $responseDecoded->Payment->Status || 2 == $responseDecoded->Payment->Status)) {
            $order->payment_complete($responseDecoded->Payment->PaymentId);
            do_action("lkn_wc_cielo_change_order_status", $order, $this);
            
            // Remove cart
            WC()->cart->empty_cart();
            do_action("lkn_wc_cielo_update_order", $order_id);
            $order->update_meta_data('lkn_nsu', $responseDecoded->Payment->ProofOfSale);
            $order->add_order_note(
                __('Payment completed successfully. Payment id:', 'lkn-wc-gateway-cielo') .
                ' ' .
                $responseDecoded->Payment->PaymentId .
                PHP_EOL .
                __('Proof of sale (NSU)', 'lkn-wc-gateway-cielo') .
                ' - ' .
                $responseDecoded->Payment->ProofOfSale .
                PHP_EOL .
                'TID ' .
                $responseDecoded->Payment->Tid .
                ' - ' .
                $provider .
                ' (****' .
                substr($cardNum, -4) .
                ')' .
                PHP_EOL .
                __('Return code', 'lkn-wc-gateway-cielo') .
                ' - ' .
                $responseDecoded->Payment->ReturnCode
            );
            $order->save();

            // Return thankyou redirect
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
            );
        }
        if (isset($responseDecoded->Payment->ReturnCode) && 'GF' == $responseDecoded->Payment->ReturnCode) {
            // Error GF detected, notify site admin
            $error_message = "Return Code: " . $responseDecoded->Payment->ReturnCode . '. Return Message: ' . $responseDecoded->Payment->ReturnMessage . '.' . __('Please contact Cielo for further assistance.', 'lkn-wc-gateway-cielo');
            //wp_mail(get_option('admin_email'), 'Erro na transação Cielo', $error_message);

            // Registrar a mensagem de erro em um arquivo de log
            $this->log->log('error', $error_message, array('source' => 'woocommerce-cielo-credit'));

            $message = __('Order payment failed. Make sure your credit card is valid.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if ('yes' === $debug) {
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-debit'));
        }

        $message = __('Order payment failed. Make sure your debit card is valid.', 'lkn-wc-gateway-cielo');

        throw new Exception(esc_attr($message));
    }

    private function validate_card_holder_name($cardName, $renderNotice) {
        if (empty($cardName) || strlen($cardName) < 3) {
            if ($renderNotice) {
                $this->add_notice_once(__('Card Holder Name is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }

        return true;
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
    private function add_notice_once($message, $type): void {
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