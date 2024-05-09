<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;

final class LknIntegrationRedeForWoocommerceWcCieloCreditBlocks extends AbstractPaymentMethodType {
    private $gateway;
    protected $name = 'lkn_cielo_credit';

    public function initialize(): void {
        $this->settings = get_option( 'woocommerce_lkn_cielo_credit_settings', array() );
        $this->gateway = new LknWCGatewayCieloCredit();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'lkn_cielo_credit-blocks-integration',
            plugin_dir_url( __FILE__ ) . '../resources/js/creditCard/lknCieloCredit.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
            ),
            null,
            true
        );
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'lkn_cielo_credit-blocks-integration');
        }

        return array('lkn_cielo_credit-blocks-integration');
    }

    public function get_payment_method_data() {
        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'activeInstallment' => $this->gateway->get_option('installment_payment'),
            'installmentLimit' => $this->gateway->get_option('installment_limit', 12),
            'totalCart' => $this->gateway->lknGetCartTotal(),
            'translations' => [
                'cardNumber' => __('Card Number', 'lkn-wc-gateway-cielo'),
                'cardExpiryDate' => __('Expiry Date', 'lkn-wc-gateway-cielo'),
                'securityCode' => __('Security Code', 'lkn-wc-gateway-cielo'),
                'installments' => __('Installments', 'lkn-wc-gateway-cielo')
            ]
        );
    }
}
?>