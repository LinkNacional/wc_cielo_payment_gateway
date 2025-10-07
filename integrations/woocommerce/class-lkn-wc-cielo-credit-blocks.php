<?php

namespace Lkn\WcCieloPaymentGateway\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Lkn_Wc_Cielo_Credit_Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_cielo_credit';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_lkn_cielo_credit_settings', array());
        $lknWcGateWayCieloCredit = new Lkn_Wc_Gateway_Cielo_Credit();
        $lknWcGateWayCieloCredit->lkn_initialize_payment_gateway_scripts();
        $this->gateway = $lknWcGateWayCieloCredit;
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        wp_register_script(
            'lkn_cielo_credit-blocks-integration',
            LKN_WC_GATEWAY_CIELO_URL . 'public/js/lknCieloCreditCompiled.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ),
            '1.0.0',
            true
        );
        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('lkn_cielo_credit-blocks-integration');
        }

        $custom_layout = isset($this->settings['checkout_layout']) ? $this->settings['checkout_layout'] : 'default';
        $pro_plugin_active = function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');
        $pro_license_active = get_option('lkn_cielo_pro_license_boolean', false);
        $pro_plugin_version_valid = defined('LKN_CIELO_API_PRO_VERSION') && version_compare(LKN_CIELO_API_PRO_VERSION, '1.20.2', '>=');

        if ($custom_layout === 'default' && $pro_plugin_active && $pro_license_active) {
            $custom_layout = "yes";
        }

        if ($pro_plugin_active && $pro_license_active && $custom_layout === 'yes' && $pro_plugin_version_valid) {
            wp_enqueue_script('lkn-wc-gateway-credit-checkout-layout', LKN_WC_GATEWAY_CIELO_URL . 'public/js/lkn-wc-gateway-checkout-layout.js', array(), LKN_WC_CIELO_VERSION, false);
            wp_localize_script('lkn-wc-gateway-credit-checkout-layout', 'lknCieloCardIcons', array(
                'visa'       => LKN_WC_GATEWAY_CIELO_URL . 'public/images/visa-icon.svg',
                'mastercard' => LKN_WC_GATEWAY_CIELO_URL . 'public/images/mastercard-icon.svg',
                'amex'       => LKN_WC_GATEWAY_CIELO_URL . 'public/images/amex-icon.svg',
                'elo'        => LKN_WC_GATEWAY_CIELO_URL . 'public/images/elo-icon.svg',
                'other_card' => LKN_WC_GATEWAY_CIELO_URL . 'public/images/other-card.svg',
                'other_card_alt'    => __('other card', 'lkn-wc-gateway-cielo')
            ));
            wp_localize_script('lkn-wc-gateway-credit-checkout-layout', 'lknCieloInputIcons', array(
                'calendar' => LKN_WC_GATEWAY_CIELO_URL . 'public/images/calendar.svg',
                'key'      => LKN_WC_GATEWAY_CIELO_URL . 'public/images/key.svg',
                'lock'     => LKN_WC_GATEWAY_CIELO_URL . 'public/images/lock.svg'
            ));
            wp_enqueue_style('lkn-wc-gateway-credit-checkout-layout', LKN_WC_GATEWAY_CIELO_URL . 'admin/css/lkn-wc-gateway-credit-card-checkout-layout.css', array(), LKN_WC_CIELO_VERSION, 'all');
        }

        do_action('lkn_wc_cielo_remove_cardholder_name', $this->gateway);
        return array('lkn_cielo_credit-blocks-integration');
    }

    public function get_payment_method_data()
    {
        $installmentLimit = $this->gateway->get_option('installment_limit', 12);
        $installments = array();

        $installmentMinAmount = apply_filters('lkn_wc_cielo_set_installment_min_amount', '5,00', $this->gateway);
        $installmentMinAmount = preg_replace('/,/', '.', $installmentMinAmount);

        $installmentLimit = apply_filters('lkn_wc_cielo_set_installment_limit', $installmentLimit, $this->gateway);

        for ($i = 1; $i <= 12; $i++) {
            $installments[] = array(
                'index' => (string) $i,
                'label' => "teste $i"
            );
        }
        /**
         * @param $installments array
         * @param $gateway Lkn_Wc_Gateway_Cielo_Credit - Payment Gateway instance
         */
        $installments = apply_filters('lkn_wc_cielo_set_installments', $installments, $this->gateway);

        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'activeInstallment' => $this->gateway->get_option('installment_payment'),
            'activeDiscount' => $this->gateway->get_option('installment_discount', 'no'),
            'installmentLimit' => $installmentLimit,
            'installments' => $installments,
            'installmentMinAmount' => $installmentMinAmount,
            'totalCart' => $this->gateway->lknGetCartTotal(),
            'showCard' => $this->gateway->get_option('show_card_animation'),
            'nonceCieloCredit' => wp_create_nonce('nonce_lkn_cielo_credit'),
            'translations' => array(
                'cardNumber' => __('Card Number', 'lkn-wc-gateway-cielo'),
                'cardExpiryDate' => __('Expiry Date', 'lkn-wc-gateway-cielo'),
                'securityCode' => __('Security Code', 'lkn-wc-gateway-cielo'),
                'installments' => __('Installments', 'lkn-wc-gateway-cielo'),
                'cardHolder' => __('Card Holder Name', 'lkn-wc-gateway-cielo'),
            )
        );
    }
}
