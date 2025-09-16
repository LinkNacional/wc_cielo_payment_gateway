<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use DateTime;
use Exception;
use WC_Logger;
use WC_Subscriptions_Order;
use WC_Payment_Gateway;
use WC_Subscription;

/**
 * Lkn_WC_Gateway_cielo_google_pay class.
 *
 * @author   Link Nacional
 *
 * @since    1.0.0
 */

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Cielo API 3.0 Credit Card Gateway.
 *
 * @class    Lkn_WC_Gateway_cielo_google_pay
 *
 * @version  1.0.0
 */
final class LknWCGatewayCieloGooglePay extends WC_Payment_Gateway
{
    public $instructions = '';

    private $version = LKN_WC_CIELO_VERSION;

    private $log;

    public function __construct()
    {
        $this->id = 'lkn_cielo_google_pay';
        $this->icon = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        $this->method_title = __('Cielo - Google Pay', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows Google Pay payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo');

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

        // Action hook to load admin JavaScript
        if (function_exists('get_plugins')) {
            add_action('admin_enqueue_scripts', array($this, 'admin_load_script'));
        }
    }

    /**
     * Load admin JavaScript for the admin page.
     */
    public function admin_load_script(): void
    {
        wp_enqueue_script('lkn-wc-gateway-admin', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin.js', array('wp-i18n'), $this->version, 'all');

        $pro_plugin_exists = file_exists(WP_PLUGIN_DIR . '/lkn-cielo-api-pro/lkn-cielo-api-pro.php');
        $pro_plugin_active = function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');

        wp_localize_script('lkn-wc-gateway-admin', 'lknCieloProStatus', array(
            'isProActive' => $pro_plugin_exists && $pro_plugin_active ? true : false,
        ));

        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        if ('wc-settings' === $page && 'checkout' === $tab && $section == $this->id) {
            wp_enqueue_script('lknWCGatewayCieloGooglePaySettingsLayoutScript', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-wc-gateway-admin-layout.js', array('jquery'), $this->version, false);
            wp_localize_script('lknWCGatewayCieloGooglePaySettingsLayoutScript', 'lknWcCieloTranslationsInput', array(
                'modern' => __('Modern version', 'lkn-wc-gateway-cielo'),
                'standard' => __('Standard version', 'lkn-wc-gateway-cielo'),
                'enable' => __('Enable', 'lkn-wc-gateway-cielo'),
                'disable' => __('Disable', 'lkn-wc-gateway-cielo'),
            ));
            wp_enqueue_style('lkn-admin-layout', plugin_dir_url(__FILE__) . '../resources/css/frontend/lkn-admin-layout.css', array(), $this->version, 'all');
            wp_enqueue_script('lknWCGatewayCieloGooglePayClearButtonScript', plugin_dir_url(__FILE__) . '../resources/js/admin/lkn-clear-logs-button.js', array('jquery', 'wp-api'), $this->version, false);
            wp_localize_script('lknWCGatewayCieloGooglePayClearButtonScript', 'lknWcCieloTranslations', array(
                'clearLogs' => __('Limpar Logs', 'lkn-wc-gateway-cielo'),
                'alertText' => __('Deseja realmente deletar todos logs dos pedidos?', 'lkn-wc-gateway-cielo'),
                'production' => __('Use this in the live store to charge real payments.', 'lkn-wc-gateway-cielo'),
                'sandbox' => __('Use this for testing purposes in the Cielo sandbox environment.', 'lkn-wc-gateway-cielo'),
                'enable' => __('Enable', 'lkn-wc-gateway-cielo'),
                'disable' => __('Disable', 'lkn-wc-gateway-cielo'),
            ));
        }
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields(): void
    {
        $this->form_fields = array(
            'general' => array(
                'title' => esc_attr__('General', 'lkn-wc-gateway-cielo'),
                'type' => 'title',
            ),
            'enabled' => array(
                'title' => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable Google Pay Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Enable or disable the Google Pay payment method.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Check this box and save to enable Google Pay settings.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Enable this option to allow customers to pay with Google Pay using Cielo API 3.0.', 'lkn-wc-gateway-cielo')
                )
            ),
            'title' => array(
                'title'       => __('Title', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'default'     => __('Google Pay', 'lkn-wc-gateway-cielo'),
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter the title that will be shown to customers during the checkout process.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('This text will appear as the payment method title during checkout. Choose something your customers will easily understand, like “Pay with Google Pay (Cielo)”.', 'lkn-wc-gateway-cielo')
                )
            ),
            'description' => array(
                'title'       => __('Description', 'lkn-wc-gateway-cielo'),
                'type'        => 'textarea',
                'default'     => __('Payment processed by Cielo API 3.0', 'lkn-wc-gateway-cielo'),
                'description' => __('Payment method description that the customer will see on your checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('This description appears below the payment method title at checkout. Use it to inform your customers about the payment processing details.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Provide a brief message that informs the customer how the payment will be processed. For example: “Your payment will be securely processed by Cielo.”', 'lkn-wc-gateway-cielo')
                )
            ),
            'google_merchant_id' => array(
                'title'       => __('Google Merchant Id', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Chave de produção do Google Pay.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter the Merchant ID provided by Google Pay.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Enter the Google Merchant ID for your Google Pay integration.', 'lkn-wc-gateway-cielo')
                )
            ),
            'google_merchant_name' => array(
                'title'       => __('Google Merchant Name', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Nome da loja no Google Pay.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter the Merchant Name provided by Google Pay.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('Enter the Google Merchant Name for your Google Pay integration.', 'lkn-wc-gateway-cielo')
                )
            ),
            'google_text_button' => array(
                'title'       => __('Google Pay Button Text', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'options'     => array(
                    'pay'    => __('Pay', 'lkn-wc-gateway-cielo'),
                    'buy'    => __('Buy', 'lkn-wc-gateway-cielo'),
                    'checkout' => __('Checkout', 'lkn-wc-gateway-cielo'),
                    'donate' => __('Donate', 'lkn-wc-gateway-cielo'),
                ),
                'default'   => 'pay',
                'desc_tip'  => __('Choose the text to display on the Google Pay button.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Select the text you want to display on the Google Pay button.', 'lkn-wc-gateway-cielo')
                )
            ),
            'cielo_merchant_id' => array(
                'title'       => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter the Merchant ID provided by Cielo for API integration.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('This is your Cielo Merchant ID used to authenticate API requests. You can find it in your Cielo dashboard.', 'lkn-wc-gateway-cielo')
                )
            ),
            'cielo_merchant_key' => array(
                'title'       => __('Merchant Key', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter the Merchant Key provided by Cielo for secure requests.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('This is your secret Merchant Key used to sign transactions with Cielo API. Keep it safe and do not share.', 'lkn-wc-gateway-cielo')
                )
            ),
            'env' => array(
                'title'       => __('Environment', 'lkn-wc-gateway-cielo'),
                'type'        => 'select',
                'options'     => array(
                    'PRODUCTION' => __('Production', 'lkn-wc-gateway-cielo'),
                    'TEST'    => __('Development', 'lkn-wc-gateway-cielo'),
                ),
                'default'   => 'production',
                'desc_tip'  => __('Choose between production or development mode for Cielo API.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Select "Development" to test transactions in sandbox mode. Use "Production" for real transactions.', 'lkn-wc-gateway-cielo')
                )
            ),
            'developer' => array(
                'title' => esc_attr__('Developer', 'lkn-wc-gateway-cielo'),
                'type'  => 'title',
            ),
            'debug' => array(
                'title'   => __('Debug', 'lkn-wc-gateway-cielo'),
                'type'    => 'checkbox',
                'label'   => sprintf(
                    '%1$s. <a href="%2$s">%3$s</a>',
                    __('Enable log capture for payments', 'lkn-wc-gateway-cielo'),
                    admin_url('admin.php?page=wc-status&tab=logs'),
                    __('View logs', 'lkn-wc-gateway-cielo')
                ),
                'default'  => 'no',
                'description' => __('Enable this option to log payment requests and responses for troubleshooting purposes.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Useful for identifying errors in payment requests or responses during development or support.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('When enabled, all Cielo transactions will be logged. You can access logs via WooCommerce > Status > Logs.', 'lkn-wc-gateway-cielo')
                )
            ),
            'show_order_logs' => array(
                'title'   => __('Visualizar Log no Pedido', 'lkn-wc-gateway-cielo'),
                'type'    => 'checkbox',
                'label'   => __('Habilita visualização do log da transação dentro do pedido.', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Displays Cielo transaction logs inside WooCommerce order details.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Useful for quickly viewing payment log data without accessing the system log files.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Enable this to show the transaction details for Cielo payments directly in each order’s admin panel.', 'lkn-wc-gateway-cielo')
                )
            ),
            'clear_order_records' => array(
                'title' => __('Limpar logs nos Pedidos', 'lkn-wc-gateway-cielo'),
                'type'  => 'button',
                'id'    => 'validateLicense',
                'class' => 'woocommerce-save-button components-button is-primary',
                'description' => __('Click this button to delete all Cielo log data stored in orders.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Use only if you no longer need the Cielo transaction logs for past orders.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('This will permanently remove all stored logs from WooCommerce orders. Ideal after resolving issues or for privacy.', 'lkn-wc-gateway-cielo')
                )
            ),
        );
    }

    /**
     * Render the payment fields.
     */
    public function payment_fields(): void
    {
        wp_enqueue_script('lknWCGatewayCieloGooglePayScript', 'https://pay.google.com/gp/p/js/pay.js', array('jquery'), $this->version, false);
        wp_enqueue_script('lknWCGatewayCieloGooglePayCheckoutScript', plugin_dir_url(__FILE__) . '../resources/js/googlePay/lkn-wc-google-pay.js', array('jquery', 'wp-api'), $this->version, false);
        wp_localize_script('lknWCGatewayCieloGooglePayCheckoutScript', 'lknWcCieloGooglePayVars', array(
            'env' => $this->get_option('env', 'TEST'),
            'googleMerchantId' => $this->get_option('google_merchant_id'),
            'googleMerchantName' => $this->get_option('google_merchant_name'),
            'buttonText' => $this->get_option('google_text_button', 'pay'),
            'currency' => get_woocommerce_currency(),
            'locale' => substr(get_locale(), 0, 2),
            'thousandSeparator' => wc_get_price_thousand_separator(),
            'decimalSeparator' => wc_get_price_decimal_separator(),
            'nonce' => wp_create_nonce('nonce_lkn_cielo_google_pay'),
        ));
    }

    /**
     * Fields validation.
     *
     * @return bool
     */
    public function validate_fields()
    {
        return true;
    }

    /**
     * Process the payment and return the result.
     *
     * @param int $order_id
     *
     * @return array
     */
    public function process_payment($order_id)
    {
        $nonceInactive = $this->get_option('nonce_compatibility', 'no');
        $nonce = isset($_POST['nonce_lkn_cielo_google_pay']) ? sanitize_text_field(wp_unslash($_POST['nonce_lkn_cielo_google_pay'])) : '';
        
        
        if (! wp_verify_nonce($nonce, 'nonce_lkn_cielo_google_pay') && 'no' === $nonceInactive) {
            $this->log->log('error', 'Nonce verification failed. Nonce: ' . var_export($nonce, true), array('source' => 'woocommerce-cielo-google-pay'));
            $this->add_notice_once(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo'), 'error');
            throw new Exception(esc_attr(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo')));
        }
        $data = json_decode(wp_unslash($_POST['google_pay_data']));
        $paymentData = json_decode($data->paymentMethodData->tokenizationData->token);
        $encryptedMessage = json_encode($paymentData->signedMessage);

        throw new Exception(($encryptedMessage));
        throw new Exception(wp_unslash($data->paymentMethodData->tokenizationData->token));
        throw new Exception(json_encode($data));
        
        $order = wc_get_order($order_id);

        // Card parameters
        

            // Return thankyou redirect
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
        
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
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        // Do your refund here. Refund $amount for the order with ID $order_id
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $debug = $this->get_option('debug');
        $order = wc_get_order($order_id);
        $transactionId = $order->get_transaction_id();

        $response = apply_filters('lkn_wc_cielo_google_pay_refund', $url, $merchantId, $merchantSecret, $order_id, $amount);

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-google-pay'));
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
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-google-pay'));
        }

        $order->add_order_note(__('Order refund failed, payment id:', 'lkn-wc-gateway-cielo') . ' ' . $transactionId);

        return false;
    }


    /**
     * Verify if WooCommerce notice exists before adding.
     *
     * @param string $message
     * @param string $type
     */
    private function add_notice_once($message, $type): void
    {
        if (! wc_has_notice($message, $type)) {
            wc_add_notice($message, $type);
        }
    }
}
?>