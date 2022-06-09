<?php
/**
 * Lkn_WC_Gateway_Cielo_Debit class
 *
 * @author   SomewhereWarm <info@somewherewarm.gr>
 * @package  WooCommerce Dummy Payments Gateway
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
 * @version  1.0.3
 */
class Lkn_WC_Gateway_Cielo_Debit extends WC_Payment_Gateway {
    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->id                 = 'lkn_cielo_debit';
        $this->icon               = apply_filters('lkn_wc_cielo_gateway_icon', '');
        $this->has_fields         = false;
        $this->supports           = [
            'products',
            'refunds',
        ];

        $this->method_title       = _x('Dummy Payment', 'Dummy payment method', 'woocommerce-gateway-dummy');
        $this->method_description = __('Allows dummy payments.', 'woocommerce-gateway-dummy');

        // Load the settings.
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables.
        $this->title        = $this->get_option('title');
        $this->description  = $this->get_option('description');
        $this->instructions = $this->get_option('instructions', $this->description);

        // Actions.
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_scheduled_subscription_payment_dummy', [$this, 'process_subscription_payment'], 10, 2);
    }

    /**
     * Initialise Gateway Settings Form Fields.
     */
    public function init_form_fields() {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'woocommerce-gateway-dummy'),
                'type'    => 'checkbox',
                'label'   => __('Enable Dummy Payments', 'woocommerce-gateway-dummy'),
                'default' => 'yes',
            ],
            'title' => [
                'title'       => __('Title', 'woocommerce-gateway-dummy'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-dummy'),
                'default'     => _x('Dummy Payment', 'Dummy payment method', 'woocommerce-gateway-dummy'),
                'desc_tip'    => true,
            ],
            'description' => [
                'title'       => __('Description', 'woocommerce-gateway-dummy'),
                'type'        => 'textarea',
                'description' => __('Payment method description that the customer will see on your checkout.', 'woocommerce-gateway-dummy'),
                'default'     => __('The goods are yours. No money needed.', 'woocommerce-gateway-dummy'),
                'desc_tip'    => true,
            ],
            'result' => [
                'title'    => __('Payment result', 'woocommerce-gateway-dummy'),
                'desc'     => __('Determine if order payments are successful when using this gateway.', 'woocommerce-gateway-dummy'),
                'id'       => 'woo_dummy_payment_result',
                'type'     => 'select',
                'options'  => [
                    'success'  => __('Success', 'woocommerce-gateway-dummy'),
                    'failure'  => __('Failure', 'woocommerce-gateway-dummy'),
                ],
                'desc_tip' => true,
            ],
        ];
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
     * Process the payment and return the result.
     *
     * @param  int  $order_id
     * @return array
     */
    public function process_payment($order_id) {
        $payment_result = $this->get_option('result');

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
            $message = __('Order payment failed. To make a successful payment using Dummy Payments, please review the gateway settings.', 'woocommerce-gateway-dummy');

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
        return true;
    }
}
