<?php

namespace Lkn\WcCieloPaymentGateway\Includes;

use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Helper;
use DateTime;
use Exception;
use WC_Logger;
use WC_Subscriptions_Order;
use WC_Payment_Gateway;
use WC_Subscription;

/**
 * Lkn_WC_Gateway_Cielo_Credit class.
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
 * @class    Lkn_WC_Gateway_Cielo_Credit
 *
 * @version  1.0.0
 */
final class Lkn_Wc_Gateway_Cielo_Credit extends WC_Payment_Gateway
{
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
    public function __construct()
    {
        $this->id = 'lkn_cielo_credit';
        $this->icon = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields = true;
        $this->supports = array(
            'products',
        );

        $this->supports = apply_filters('lkn_wc_cielo_credit_add_support', $this->supports);

        $this->method_title = __('Cielo - Credit Card', 'lkn-wc-gateway-cielo');
        $this->method_description = __('Allows credit card payment with Cielo API 3.0.', 'lkn-wc-gateway-cielo');

        // Load the settings.
        $this->lkn_init_form_fields();
        $this->init_settings();

        $this->icon = Lkn_Wc_Cielo_Helper::getIconUrl();
        // Define user set variables.
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        $this->log = new WC_Logger();

        // Actions.
        add_filter('woocommerce_new_order_note_data', array($this, 'lkn_add_gateway_name_to_notes'), 10, 2);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_action('woocommerce_scheduled_subscription_payment_' . $this->id, array($this, 'process_subscription_payment'), 10, 3);

        // Action hook to load admin JavaScript
        if (function_exists('get_plugins')) {
            add_action('admin_enqueue_scripts', array($this, 'lkn_admin_load_script'));
        }
    }

    /**
     * Process subscription payment.
     *
     * @param  float     $amount
     * @param  WC_Order  $order
     * @return void
     */
    public function process_subscription_payment($amount, $order, $isRetry = false): void
    {
        do_action('lkn_wc_cielo_scheduled_subscription_payment', $amount, $order, $isRetry);
    }

    /**
     * Load admin JavaScript for the admin page.
     */
    public function lkn_admin_load_script(): void
    {
        wp_enqueue_script('lkn-wc-gateway-admin', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/js/lkn-wc-gateway-admin.js', array('wp-i18n'), $this->version, 'all');

        $pro_plugin_exists = file_exists(WP_PLUGIN_DIR . '/lkn-cielo-api-pro/lkn-cielo-api-pro.php');
        $pro_plugin_active = function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');

        wp_localize_script('lkn-wc-gateway-admin', 'lknCieloProStatus', array(
            'isProActive' => $pro_plugin_exists && $pro_plugin_active ? true : false,
        ));

        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        if ('wc-settings' === $page && 'checkout' === $tab && $section == $this->id) {
            wp_enqueue_script('lknWCGatewayCieloCreditSettingsLayoutScript', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/js/lkn-wc-gateway-admin-layout.js', array('jquery'), $this->version, false);
            wp_localize_script('lknWCGatewayCieloCreditSettingsLayoutScript', 'lknWcCieloTranslationsInput', array(
                'modern' => __('Modern version', 'lkn-wc-gateway-cielo'),
                'standard' => __('Standard version', 'lkn-wc-gateway-cielo'),
                'enable' => __('Enable', 'lkn-wc-gateway-cielo'),
                'disable' => __('Disable', 'lkn-wc-gateway-cielo'),
            ));
            wp_enqueue_style('lkn-admin-layout', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/css/lkn-admin-layout.css', array(), $this->version, 'all');
            wp_enqueue_script('lknWCGatewayCieloCreditClearButtonScript', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/js/lkn-clear-logs-button.js', array('jquery', 'wp-api'), $this->version, false);
            wp_localize_script('lknWCGatewayCieloCreditClearButtonScript', 'lknWcCieloTranslations', array(
                'clearLogs' => __('Limpar Logs', 'lkn-wc-gateway-cielo'),
                'alertText' => __('Deseja realmente deletar todos logs dos pedidos?', 'lkn-wc-gateway-cielo'),
                'production' => __('Use this in the live store to charge real payments.', 'lkn-wc-gateway-cielo'),
                'sandbox' => __('Use this for testing purposes in the Cielo sandbox environment.', 'lkn-wc-gateway-cielo'),
                'enable' => __('Enable', 'lkn-wc-gateway-cielo'),
                'disable' => __('Disable', 'lkn-wc-gateway-cielo'),
            ));
        }
    }

    public function lkn_initialize_payment_gateway_scripts()
    {
        // Aqui você pode adicionar o hook manualmente dentro da função
        add_action('wp_enqueue_scripts', [$this, 'lkn_payment_gateway_scripts']);
    }

    /**
     * Load gateway scripts/styles.
     */
    public function lkn_payment_gateway_scripts(): void
    {
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
        $order_id = absint(get_query_var('order-pay'));
        $order = wc_get_order($order_id);

        if ($order) {
            $currency = $order->get_currency();
        } else {
            $currency = get_woocommerce_currency();
        }

        $installmentArgs['currency'] = $currency;

        wp_enqueue_script('lkn-mask-script', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'public/js/formatter.js', array('jquery'), $this->version, false);
        wp_enqueue_script('lkn-mask-script-load', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'public/js/define-mask.js', array('lkn-mask-script', 'jquery'), $this->version, false);

        wp_enqueue_script('lkn-installment-script', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'public/js/lkn-cc-installment.js', array('jquery'), $this->version, false);
        wp_localize_script('lkn-installment-script', 'lknWCCieloCredit', $installmentArgs);
        wp_localize_script('lkn-installment-script', 'lknWCCieloCreditDiscount', $this->get_option('installment_discount'));

        wp_enqueue_style('lkn-cc-style', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/css/lkn-cc-style.css', array(), $this->version, 'all');

        wp_enqueue_style('lkn-mask', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/css/lkn-mask.css', array(), $this->version, 'all');
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function lkn_init_form_fields(): void
    {
        $this->form_fields = array(
            'general' => array(
                'title' => esc_attr__('General', 'lkn-wc-gateway-cielo'),
                'type' => 'title',
            ),
            'enabled' => array(
                'title' => __('Enable/Disable', 'lkn-wc-gateway-cielo'),
                'type' => 'checkbox',
                'label' => __('Enable Credit Card Payments', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Enable or disable the credit card payment method.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Check this box and save to enable credit card settings.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Enable this option to allow customers to pay with credit cards using Cielo API 3.0.', 'lkn-wc-gateway-cielo')
                )
            ),
            'title' => array(
                'title'       => __('Title', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'default'     => __('Credit card', 'lkn-wc-gateway-cielo'),
                'description' => __('This controls the title which the user sees during checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter the title that will be shown to customers during the checkout process.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('This text will appear as the payment method title during checkout. Choose something your customers will easily understand, like “Pay with credit card (Cielo)”.', 'lkn-wc-gateway-cielo')
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
            'merchant_id' => array(
                'title'       => __('Merchant Id', 'lkn-wc-gateway-cielo'),
                'type'        => 'password',
                'description' => __('Cielo credentials.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter the Merchant ID provided by Cielo for API integration.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'required' => 'required',
                    'data-title-description' => __('This is your Cielo Merchant ID used to authenticate API requests. You can find it in your Cielo dashboard.', 'lkn-wc-gateway-cielo')
                )
            ),
            'merchant_key' => array(
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
                    'production' => __('Production', 'lkn-wc-gateway-cielo'),
                    'sandbox'    => __('Development', 'lkn-wc-gateway-cielo'),
                ),
                'default'   => 'production',
                'desc_tip'  => __('Choose between production or development mode for Cielo API.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Select "Development" to test transactions in sandbox mode. Use "Production" for real transactions.', 'lkn-wc-gateway-cielo')
                )
            ),
            'invoiceDesc' => array(
                'title'       => __('Invoice Description', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'default'     => __('order', 'lkn-wc-gateway-cielo'),
                'description' => __('Invoice description that the customer will see on your checkout (special characters are not accepted).', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter a brief description that will appear on the customer invoice.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'maxlength' => 50,
                    'pattern'   => '[a-zA-Z]+( [a-zA-Z]+)*',
                    'required'  => 'required',
                    'data-title-description' => __('This description will be used on the transaction invoice. It should be readable and not contain special characters or numbers.', 'lkn-wc-gateway-cielo')
                )
            ),
            'credit_card' => array(
                'title' => esc_attr__('Card', 'lkn-wc-gateway-cielo'),
                'type'  => 'title',
            ),
            'installment_payment' => array(
                'title'   => __('Installment payments', 'lkn-wc-gateway-cielo'),
                'type'    => 'checkbox',
                'label'   => __('Enables installment payments for amounts greater than 10,00 R$', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('When enabled and using the PRO version of the plugin, an additional tab will appear for advanced installment configuration.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Enable this to allow payment in installments.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('When enabled, customers can split payments in installments for orders above R$10. Useful for improving conversion.', 'lkn-wc-gateway-cielo')
                )
            ),
            'placeholder' => array(
                'title'   => __('Input placeholders', 'lkn-wc-gateway-cielo'),
                'type'    => 'checkbox',
                'label'   => __('Enables input placeholders for debit/credit card fields for classic checkout', 'lkn-wc-gateway-cielo'),
                'default' => 'no',
                'description' => __('Displays placeholder text in card input fields to guide users during checkout.', 'lkn-wc-gateway-cielo'),
                'desc_tip' => __('Enable to show example placeholders in credit/debit card fields.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Adds sample text in the card fields to help users understand what information is expected.', 'lkn-wc-gateway-cielo')
                )
            ),
            'nonce_compatibility' => array(
                'title'       => __('Nonce verification compatibility mode', 'lkn-wc-gateway-cielo'),
                'description' => __('Enable only if your checkout page is facing nonce verification issue', 'lkn-wc-gateway-cielo'),
                'label'       => __('Enable for checkout page validation compatibility', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'default'     => 'no',
                'desc_tip'    => __('Only enable if your site has issues validating nonce during checkout.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Use this option if your checkout process fails due to nonce validation errors. Only enable if you know what you are doing.', 'lkn-wc-gateway-cielo')
                )
            ),
            'show_card_animation' => array(
                'title'       => __('Exibir cartão animado', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'label'       => __('Exibir cartão animado durante o checkout', 'lkn-wc-gateway-cielo'),
                'description' => __('Exibe um cartão com animações durante o checkout de pagamento do pedido.', 'lkn-wc-gateway-cielo'),
                'default'     => 'yes',
                'desc_tip'    => __('Displays an animated credit card in the checkout form.', 'lkn-wc-gateway-cielo'),
                'custom_attributes' => array(
                    'data-title-description' => __('Enhance user experience by showing a dynamic card preview while filling out card details.', 'lkn-wc-gateway-cielo')
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

        if (
            ! file_exists(WP_PLUGIN_DIR . '/lkn-cielo-api-pro/lkn-cielo-api-pro.php') ||
            ! (function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php'))
        ) {
            $this->form_fields['pro'] = array(
                'title' => esc_attr__('PRO', 'lkn-wc-gateway-cielo'),
                'type'  => 'title',
            );

            $this->form_fields['fake_license_field'] = array(
                'title'       => __('License', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Enter your license key here. This field is disabled for editing.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Enter your Link nacional license key to activate PRO features.', 'lkn-wc-gateway-cielo'),
                'id'          => 'fake_license_field',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                    'data-title-description' => __('This field displays your current license key. Editing is disabled.', 'lkn-wc-gateway-cielo'),
                ),
            );

            $this->form_fields['fake_cardholder_field'] = array(
                'title'       => __('Cardholder Name', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Enter the cardholder name. This field is not editable.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('This cardholder name field is read-only for security reasons.', 'lkn-wc-gateway-cielo'),
                'id'          => 'fake_cardholder_field',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                    'data-title-description' => __('This field displays the cardholder name but is disabled for editing.', 'lkn-wc-gateway-cielo'),
                ),
            );

            $this->form_fields['fake_layout'] = array(
                'title'       => __('Layout', 'lkn-wc-gateway-cielo'),
                'type'        => 'checkbox',
                'description' => __('Choose the layout style for the checkout page.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Select between Modern Version and Standard Version for the checkout layout.', 'lkn-wc-gateway-cielo'),
                'options'     => array(
                    'yes'  => __('Modern Version', 'lkn-wc-gateway-cielo'),
                    'no' => __('Standard Version', 'lkn-wc-gateway-cielo'),
                ),
                'default'     => 'no',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                    'data-title-description' => __('Choose the layout style for the checkout page.', 'lkn-wc-gateway-cielo'),
                ),
            );

            $this->form_fields['fake_and_more_field'] = array(
                'title'       => __('And much more...', 'lkn-wc-gateway-cielo'),
                'type'        => 'text',
                'description' => __('Discover all PRO features by activating your license.', 'lkn-wc-gateway-cielo'),
                'desc_tip'    => __('Unlock advanced features and enhancements with the PRO version.', 'lkn-wc-gateway-cielo'),
                'id'          => 'fake_and_more_field',
                'custom_attributes' => array(
                    'readonly' => 'readonly',
                    'data-title-description' => __('This is just a sample field to highlight more PRO features.', 'lkn-wc-gateway-cielo'),
                ),
            );
        }

        $customConfigs = apply_filters('lkn_wc_cielo_get_custom_configs', array(), $this->id);

        if (! empty($customConfigs)) {
            $this->form_fields = array_merge($this->form_fields, $customConfigs);
        }
    }

    /**
     * Render the payment fields.
     */
    public function payment_fields(): void
    {
        wp_enqueue_style('lknWCGatewayCieloFixIconsStyle', WC_CIELO_PAYMENT_GATEWAY_DIR_URL . 'admin/css/lkn-fix-icons-styles.css', array(), $this->version, 'all');
        $activeInstallment = $this->get_option('installment_payment');
        $total_cart = number_format($this->get_order_total(), 2, '.', '');
        $noLoginCheckout = isset($_GET['pay_for_order']) ? sanitize_text_field(wp_unslash($_GET['pay_for_order'])) : 'false';
        $installmentLimit = $this->get_option('installment_limit', 12);
        $installments = array();
        $nonce = wp_create_nonce('nonce_lkn_cielo_credit');
        $placeholder = $this->get_option('placeholder', 'no');
        $placeholderEnabled = false;

        $installmentLimit = apply_filters('lkn_wc_cielo_set_installment_limit', $installmentLimit, $this);
        $installmentMin = preg_replace('/,/', '.', $this->get_option('installment_min', '5,00'));

        if ('yes' === $placeholder) {
            $placeholderEnabled = true;
        }

        for ($c = 1; $c <= $installmentLimit; ++$c) {
            $interest = $this->get_option($c . 'x', 0);
            if ($interest > 0) {
                $installments[] = array('id' => $c, 'interest' => $interest);
            } elseif ($this->get_option('installment_discount') == 'yes') {
                $discount = $this->get_option($c . 'x_discount', 0);
                if ($discount > 0) {
                    $installments[] = array('id' => $c, 'discount' => $discount);
                }
            }
        }

        if ('yes' === $activeInstallment) {
            if (isset($_GET['pay_for_order'])) {
                $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
                $order_id = wc_get_order_id_by_order_key($key);
                $order = wc_get_order($order_id);
                $total_cart = number_format($order->get_total(), 2, '.', '');
            }
        }

        echo wp_kses_post(wpautop($this->description)); ?>

        <fieldset
            id="wc-<?php echo esc_attr($this->id); ?>-cc-form"
            class="wc-credit-card-form wc-payment-form"
            style="background:transparent;">

            <?php do_action('woocommerce_credit_card_form_start', $this->id); ?>
            <input
                type="hidden"
                name="nonce_lkn_cielo_credit"
                class="nonce_lkn_cielo_credit"
                value="<?php echo esc_attr($nonce); ?>" />

            <div class="form-row form-row-wide">
                <label
                    for="lkn_cc_cardholder_name"><?php esc_html_e('Card Holder Name', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span></label>
                <input
                    id="lkn_cc_cardholder_name"
                    name="lkn_cc_cardholder_name"
                    type="text"
                    autocomplete="cc-name"
                    required
                    placeholder="<?php echo $placeholderEnabled ? esc_attr('John Doe') : ''; ?>"
                    data-placeholder="<?php echo $placeholderEnabled ? esc_attr('John Doe') : ''; ?>">
            </div>

            <div class="form-row form-row-wide">
                <label
                    for="lkn_ccno"><?php esc_html_e('Card Number', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span></label>
                <input
                    id="lkn_ccno"
                    name="lkn_ccno"
                    type="tel"
                    inputmode="numeric"
                    class="lkn-card-num"
                    maxlength="24"
                    required
                    placeholder="<?php echo $placeholderEnabled ? esc_attr('XXXX XXXX XXXX XXXX') : ''; ?>"
                    data-placeholder="<?php echo $placeholderEnabled ? esc_attr('XXXX XXXX XXXX XXXX') : ''; ?>">
            </div>
            <div class="form-row form-row-wide">
                <label
                    for="lkn_cc_expdate"><?php esc_html_e('Expiry Date', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span></label>
                <input
                    id="lkn_cc_expdate"
                    name="lkn_cc_expdate"
                    type="tel"
                    inputmode="numeric"
                    class="lkn-card-exp"
                    maxlength="7"
                    required
                    placeholder="<?php echo $placeholderEnabled ? esc_attr('MM/YY') : ''; ?>"
                    data-placeholder="<?php echo $placeholderEnabled ? esc_attr('MM/YY') : ''; ?>">
            </div>
            <div class="form-row form-row-wide">
                <label
                    for="lkn_cc_cvc"><?php esc_html_e('Security Code', 'lkn-wc-gateway-cielo'); ?>
                    <span class="required">*</span></label>
                <input
                    id="lkn_cc_cvc"
                    name="lkn_cc_cvc"
                    type="tel"
                    inputmode="numeric"
                    class="lkn-cvv"
                    maxlength="8"
                    required
                    placeholder="<?php echo $placeholderEnabled ? esc_attr('CVV') : ''; ?>"
                    data-placeholder="<?php echo $placeholderEnabled ? esc_attr('CVV') : ''; ?>">
            </div>
            <?php
            if ('yes' === $activeInstallment) {
            ?>
                <input
                    id="lkn_cc_installment_total"
                    type="hidden"
                    value="<?php echo esc_attr($total_cart); ?>">
                <input
                    id="lkn_cc_no_login_checkout"
                    type="hidden"
                    value="<?php echo esc_attr($noLoginCheckout); ?>">
                <input
                    id="lkn_cc_installment_limit"
                    type="hidden"
                    value="<?php echo esc_attr($installmentLimit); ?>">
                <input
                    id="lkn_cc_installment_min"
                    type="hidden"
                    value="<?php echo esc_attr($installmentMin); ?>">
                <input
                    id="lkn_cc_installment_interest"
                    type="hidden"
                    value="<?php echo esc_attr(wp_json_encode($installments)); ?>">

                <div class="form-row form-row-wide">
                    <select
                        id="lkn_cc_installments"
                        name="lkn_cc_installments">
                        <option
                            value="1"
                            selected="1">1 x R$0,00 sem juros</option>
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

        do_action('lkn_wc_cielo_remove_cardholder_name', $this);
    }

    /**
     * Fields validation.
     *
     * @return bool
     */
    public function validate_fields()
    {
        $validateCompatMode = $this->get_option('input_validation_compatibility', 'no');
        $nonce = isset($_POST['nonce_lkn_cielo_credit']) ? sanitize_text_field(wp_unslash($_POST['nonce_lkn_cielo_credit'])) : '';

        if (! wp_verify_nonce($nonce, 'nonce_lkn_cielo_credit') && 'no' === $validateCompatMode) {
            $this->log->log('error', 'Nonce verification failed. Nonce: ' . var_export($nonce, true), array('source' => 'woocommerce-cielo-credit'));
            $this->lkn_add_notice_once(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo'), 'error');
            return false;
        }
        if ('no' === $validateCompatMode) {
            $ccnum = isset($_POST['lkn_ccno']) ? sanitize_text_field(wp_unslash($_POST['lkn_ccno'])) : '';
            $expDate = isset($_POST['lkn_cc_expdate']) ? sanitize_text_field(wp_unslash($_POST['lkn_cc_expdate'])) : '';
            $cvv = isset($_POST['lkn_cc_cvc']) ? sanitize_text_field(wp_unslash($_POST['lkn_cc_cvc'])) : '';

            $validCcNumber = $this->lkn_validate_card_number($ccnum, true);
            $validExpDate = $this->lkn_validate_exp_date($expDate, true);
            $validCvv = $this->lkn_validate_cvv($cvv, true);

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
    public function process_payment($order_id)
    {
        // LÓGICA WORDPRESS: Validação de nonce de segurança
        $nonceInactive = $this->get_option('nonce_compatibility', 'no');
        $nonce = isset($_POST['nonce_lkn_cielo_credit']) ? sanitize_text_field(wp_unslash($_POST['nonce_lkn_cielo_credit'])) : '';

        if (! wp_verify_nonce($nonce, 'nonce_lkn_cielo_credit') && 'no' === $nonceInactive) {
            $this->log->log('error', 'Nonce verification failed. Nonce: ' . var_export($nonce, true), array('source' => 'woocommerce-cielo-credit'));
            $this->lkn_add_notice_once(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo'), 'error');
            throw new Exception(esc_attr(__('Nonce verification failed, try reloading the page', 'lkn-wc-gateway-cielo')));
        }

        // LÓGICA WORDPRESS: Obter dados do pedido WooCommerce
        $order = wc_get_order($order_id);

        // LÓGICA WORDPRESS: Sanitização e validação de dados do cartão
        $cardNum = preg_replace('/\s/', '', isset($_POST['lkn_ccno']) ? sanitize_text_field(wp_unslash($_POST['lkn_ccno'])) : '');
        $cardExpSplit = explode('/', preg_replace('/\s/', '', isset($_POST['lkn_cc_expdate']) ? sanitize_text_field(wp_unslash($_POST['lkn_cc_expdate'])) : ''));
        $cardExp = $cardExpSplit[0] . '/20' . $cardExpSplit[1];
        $cardExpShort = $cardExpSplit[0] . '/' . $cardExpSplit[1];
        $cardCvv = isset($_POST['lkn_cc_cvc']) ? sanitize_text_field(wp_unslash($_POST['lkn_cc_cvc'])) : '';
        $cardName = isset($_POST['lkn_cc_cardholder_name']) ? sanitize_text_field(wp_unslash($_POST['lkn_cc_cardholder_name'])) : '';
        $cardName = apply_filters('lkn_wc_cielo_get_cardholder_name', $cardName, $this, $order);
        $installments = 1;

        // POST parameters
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';
        $merchantId = sanitize_text_field($this->get_option('merchant_id'));
        $merchantSecret = sanitize_text_field($this->get_option('merchant_key'));
        $merchantOrderId = uniqid('invoice_');
        $amount = $order->get_total();
        $capture = ($this->get_option('capture', 'yes') == 'yes') ? true : false;
        $saveCard = ($this->get_option('save_card_token', 'yes') == 'yes') ? true : false;
        $description = sanitize_text_field($this->get_option('invoiceDesc'));
        $description = preg_replace('/[^a-zA-Z\s]+/', '', $description);
        $description = preg_replace('/\s+/', ' ', $description);
        $provider = $this->lkn_get_card_provider($cardNum);
        $debug = $this->get_option('debug');
        $currency = $order->get_currency();
        $activeInstallment = $this->get_option('installment_payment');

        if ($this->lkn_validate_card_holder_name($cardName, false) === false) {
            $message = __('Card Holder Name is required!', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if ($this->lkn_validate_card_number($cardNum, false) === false) {
            $message = __('Credit Card number is invalid!', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if ($this->lkn_validate_exp_date($cardExpShort, false) === false) {
            $message = __('Expiration date is invalid!', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        if ($this->lkn_validate_cvv($cardCvv, false) === false) {
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

        // Adicione esta linha para processar o pagamento recorrente se o pedido contiver uma assinatura
        if (class_exists('WC_Subscriptions_Order') && WC_Subscriptions_Order::order_contains_subscription($order_id)) {
            $order = apply_filters('lkn_wc_cielo_process_recurring_payment', $order);
            $saveCard = true;
        }
        $amount = $order->get_total();

        // Convert the amount to equivalent in BRL
        if ('BRL' !== $currency) {
            $amount = apply_filters('lkn_wc_cielo_convert_amount', $amount, $currency, $this);

            $order->add_meta_data('amount_converted', $amount, true);
        }
        
        $amountFormated = number_format($amount, 2, '', '');
        $url = ($this->get_option('env') == 'production') ? 'https://api.cieloecommerce.cielo.com.br/' : 'https://apisandbox.cieloecommerce.cielo.com.br/';

        $args['headers'] = array(
            'Content-Type' => 'application/json',
            'MerchantId' => $merchantId,
            'MerchantKey' => $merchantSecret,
        );

        $body = array(
            'MerchantOrderId' => $merchantOrderId,
            'Payment' => array(
                'Type' => 'CreditCard',
                'Amount' => (int) $amountFormated,
                'Installments' => $installments,
                'Capture' => (bool) $capture,
                'SoftDescriptor' => $description,
                'CreditCard' => array(
                    'CardNumber' => $cardNum,
                    'ExpirationDate' => $cardExp,
                    'SecurityCode' => $cardCvv,
                    'SaveCard' => $saveCard,
                    'Brand' => $provider,
                    'CardOnFile' => array(
                        'Usage' => 'First'
                    )
                ),
            ),
        );

        do_action('lkn_wc_cielo_zero_auth', $body, $args['headers'], $this);

        $args['body'] = wp_json_encode($body);
        $args['timeout'] = 120;

        $response = wp_remote_post($url . '1/sales', $args);

        if (is_wp_error($response)) {
            if ('yes' === $debug) {
                $this->log->log('error', var_export($response->get_error_messages(), true), array('source' => 'woocommerce-cielo-credit'));
            }

            $message = __('Order payment failed. Please review the gateway settings.', 'lkn-wc-gateway-cielo');

            throw new Exception(esc_attr($message));
        }
        $responseDecoded = json_decode($response['body']);

        if ($this->get_option('debug') === 'yes') {
            $lknWcCieloHelper = new Lkn_Wc_Cielo_Helper();

            $orderLogsArray = array(
                'url' => $url . '1/sales',
                'headers' => array(
                    'Content-Type' => $args['headers']['Content-Type'],
                    'MerchantId' => $lknWcCieloHelper->censorString($args['headers']['MerchantId'], 10),
                    'MerchantKey' => $lknWcCieloHelper->censorString($args['headers']['MerchantKey'], 10)
                ),
                'body' => json_decode($args['body'], true), // Decodificar como array associativo
                'response' => json_decode(json_encode($responseDecoded), true) // Certificar que responseDecoded é um array associativo
            );

            // Censurar o número do cartão de crédito
            $orderLogsArray['body']['Payment']['CreditCard']['CardNumber'] = substr($orderLogsArray['body']['Payment']['CreditCard']['CardNumber'], 0, 6) . '******' . substr($orderLogsArray['body']['Payment']['CreditCard']['CardNumber'], -4);

            // Remover a parte de "Links"
            unset($orderLogsArray['response']['Payment']['Links']);

            $orderLogs = json_encode($orderLogsArray);
            $order->update_meta_data('lknWcCieloOrderLogs', $orderLogs);
        }

        if (isset($responseDecoded->Payment) && (1 == $responseDecoded->Payment->Status || 2 == $responseDecoded->Payment->Status)) {
            // Adicionar metadados do pagamento
            $order->add_meta_data('paymentId', $responseDecoded->Payment->PaymentId, true);
            $order->update_meta_data('lkn_nsu', $responseDecoded->Payment->ProofOfSale);

            // Executar ações de mudança de status
            do_action("lkn_wc_cielo_change_order_status", $order, $this, $capture);

            // Adicionar nota do pedido com detalhes do pagamento
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
                    __('Installments quantity', 'lkn-wc-gateway-cielo') .
                    ' - ' .
                    $installments .
                    'x' .
                    PHP_EOL .
                    __('Return code', 'lkn-wc-gateway-cielo') .
                    ' - ' .
                    $responseDecoded->Payment->ReturnCode
            );

            // Completar pagamento
            $order->payment_complete($responseDecoded->Payment->PaymentId);

            // Gerenciar salvamento de cartão (se aplicável)
            if ($saveCard || (class_exists('WC_Subscriptions_Order') && WC_Subscriptions_Order::order_contains_subscription($order_id))) {
                $user_id = $order->get_user_id();

                if (! isset($responseDecoded->Payment->CreditCard->CardToken)) {
                    $order->add_order_note('O token para cobranças automáticas não foi gerado, então as cobranças automáticas não poderão ser efetuadas.');
                }

                // Dados do cartão de pagamento
                $cardPayment = array(
                    'cardToken' => $responseDecoded->Payment->CreditCard->CardToken,
                    'brand' => $provider,
                );

                if (0 != $user_id) {
                    $cardsArray = get_user_meta($user_id, 'card_array', true);
                    $cardsArray = is_array($cardsArray) ? $cardsArray : array();
                    $lastFourDigits = $responseDecoded->Payment->CreditCard->CardNumber;
                    $expirationDate = $responseDecoded->Payment->CreditCard->ExpirationDate;

                    // Adiciona o novo cartão à lista
                    $cardsArray[] = array(
                        'cardToken' => $cardPayment['cardToken'],
                        'brand' => $provider,
                        'cardDigits' => $lastFourDigits,
                        'expirationDate' => $expirationDate,
                    );

                    // Atualiza os metadados do usuário
                    update_user_meta($user_id, 'card_array', $cardsArray);
                    update_user_meta($user_id, 'default_card', array_key_last($cardsArray));
                }
            }

            // Finalizar processo
            WC()->cart->empty_cart();
            do_action("lkn_wc_cielo_update_order", $order_id, $this);
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
            $this->log->log('error', var_export($response, true), array('source' => 'woocommerce-cielo-credit'));
        }

        $message = __('Order payment failed. Make sure your credit card is valid.', 'lkn-wc-gateway-cielo');

        throw new Exception(esc_attr($message));
    }

    /**
     * Calculate the total value of items in the WooCommerce cart.
     */
    public static function lknGetCartTotal()
    {
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
        return number_format($total, 2, '', '');

        return 0;
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
    private function lkn_validate_card_number($ccnum, $renderNotice)
    {
        if (empty($ccnum)) {
            if ($renderNotice) {
                $this->lkn_add_notice_once(__('Credit Card number is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        $isValid = ! preg_match('/[^0-9\s]/', $ccnum);

        if (true !== $isValid || strlen($ccnum) < 12) {
            if ($renderNotice) {
                $this->lkn_add_notice_once(__('Credit Card number is invalid!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }

        return true;
    }

    private function lkn_validate_card_holder_name($cardName, $renderNotice)
    {
        if (empty($cardName) || strlen($cardName) < 3) {
            if ($renderNotice) {
                $this->lkn_add_notice_once(__('Card Holder Name is required!', 'lkn-wc-gateway-cielo'), 'error');
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
    private function lkn_validate_exp_date($expDate, $renderNotice)
    {
        if (empty($expDate)) {
            if ($renderNotice) {
                $this->lkn_add_notice_once(__('Expiration date is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        $expDateSplit = explode('/', $expDate);

        try {
            $expDate = new DateTime('20' . trim($expDateSplit[1]) . '-' . trim($expDateSplit[0]) . '-01');
            $today = new DateTime();

            if ($today > $expDate) {
                if ($renderNotice) {
                    $this->lkn_add_notice_once(__('Credit card is expired!', 'lkn-wc-gateway-cielo'), 'error');
                }

                return false;
            }

            return true;
        } catch (Exception $e) {
            if ($renderNotice) {
                $this->lkn_add_notice_once(__('Expiration date is invalid!', 'lkn-wc-gateway-cielo'), 'error');
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
    private function lkn_validate_cvv($cvv, $renderNotice)
    {
        if (empty($cvv)) {
            if ($renderNotice) {
                $this->lkn_add_notice_once(__('CVV is required!', 'lkn-wc-gateway-cielo'), 'error');
            }

            return false;
        }
        $isValid = ! preg_match('/\D/', $cvv);

        if (true !== $isValid || strlen($cvv) < 3) {
            if ($renderNotice) {
                $this->lkn_add_notice_once(__('CVV is invalid!', 'lkn-wc-gateway-cielo'), 'error');
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
    private function lkn_add_notice_once($message, $type): void
    {
        if (! wc_has_notice($message, $type)) {
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
    private function lkn_get_card_provider($cardNumber)
    {
        $brand = '';
        $brand = apply_filters('lkn_wc_cielo_get_card_brand', $brand, $cardNumber, $this->id);

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

    public function lkn_add_gateway_name_to_notes($note_data, $args)
    {
        // Verificar se é uma nota de mudança de status e se o pedido usa este gateway
        if (isset($args['order_id'])) {
            $order = wc_get_order($args['order_id']);

            if ($order && $order->get_payment_method() === $this->id) {
                // Verificar se o prefixo já existe para evitar duplicação
                if (strpos($note_data['comment_content'], $this->method_title . ' — ') === false) {
                    // Adicionar prefixo com nome do gateway
                    $note_data['comment_content'] = $this->method_title . ' — ' . $note_data['comment_content'];
                }
            }
        }
        return $note_data;
    }
}
?>