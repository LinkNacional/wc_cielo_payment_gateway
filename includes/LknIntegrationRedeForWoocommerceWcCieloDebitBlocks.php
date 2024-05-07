<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;

final class LknIntegrationRedeForWoocommerceWcCieloDebitBlocks extends AbstractPaymentMethodType {
    private $gateway;
    protected $name = 'lkn_cielo_debit';

    public function initialize(): void {
        $this->settings = get_option( 'woocommerce_lkn_cielo_debit_settings', array() );
        $this->gateway = new LknWCGatewayCieloDebit();
    }

    public function is_active() {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'lkn_cielo_debit-blocks-integration',
            plugin_dir_url( __FILE__ ) . '../resources/js/debitCard/lknCieloDebit.js',
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
            wp_set_script_translations( 'lkn_cielo_debit-blocks-integration');
        }

        return array('lkn_cielo_debit-blocks-integration');
    }

    public function get_payment_method_data() {
        if ($this->gateway->get_option('env') == 'sandbox') {
            $dirScriptConfig3DS = LKN_WC_GATEWAY_CIELO_URL . 'resources/js/frontend/lkn-dc-script-sdb.js';
        } else {
            $dirScriptConfig3DS = LKN_WC_GATEWAY_CIELO_URL . 'resources/js/frontend/lkn-dc-script-prd.js';
        }
        
        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'accessToken' => $this->gateway->generate_debit_auth_token(),
            'url' => get_page_link(),
            'totalCart' => '', //TODO verificar como pegar valor do carrinho 
            'orderNumber' => uniqid(),
            'dirScript3DS' => LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/BP.Mpi.3ds20.min.js',
            'dirScriptConfig3DS' => $dirScriptConfig3DS,
            'totalCart' => $this->gateway->lknGetCartTotal()
        );
    }
}
?>