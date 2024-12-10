<?php

namespace Lkn\WCCieloPaymentGateway\Includes;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;

final class LknWcCieloDebitBlocks extends AbstractPaymentMethodType {
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
            '1.0.0',
            true
        );
        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'lkn_cielo_debit-blocks-integration');
        }

        do_action('lkn_wc_cielo_remove_cardholder_name_3ds', $this->gateway);
        return array('lkn_cielo_debit-blocks-integration');
    }

    private function get_client_ip() {
        $ip_address = '';

        if ( ! empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Se estiver atrás de um proxy, `HTTP_X_FORWARDED_FOR` pode conter uma lista de IPs.
            $ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip_address = trim($ip_list[0]); // Pega o primeiro IP da lista
        } elseif ( ! empty($_SERVER['HTTP_X_REAL_IP'])) {
            $ip_address = $_SERVER['HTTP_X_REAL_IP'];
        } else {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        return $ip_address;
    }

    public function get_payment_method_data() {
        if ($this->gateway->get_option('env') == 'sandbox') {
            $dirScriptConfig3DS = LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/lkn-dc-script-sdb.js';
        } else {
            $dirScriptConfig3DS = LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/lkn-dc-script-prd.js';
        }

        $installmentLimit = $this->gateway->get_option('installment_limit', 12);
        $installments = array();

        $installmentLimit = apply_filters('lkn_wc_cielo_set_installment_limit', $installmentLimit, $this->gateway);

        $installments = apply_filters('lkn_wc_cielo_set_installments', $installments, $this->gateway);
        $user = wp_get_current_user();

        $billingDocument = get_user_meta($user->ID, 'billing_cpf', true);

        if (empty($billingDocument) || false === $billingDocument) {
            $billingDocument = get_user_meta($user->ID, 'billing_cnpj', true);

            if (empty($billingDocument) || false === $billingDocument) {
                $billingDocument = '';
            }
        }
        $acessToken = $this->gateway->generate_debit_auth_token();

        return array(
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'accessToken' => $acessToken['access_token'],
            'accessTokenExpiration' => $acessToken['expires_in'],
            'url' => get_page_link(),
            'orderNumber' => uniqid(),
            'activeInstallment' => $this->gateway->get_option('installment_payment'),
            'dirScript3DS' => LKN_WC_GATEWAY_CIELO_URL . 'resources/js/debitCard/BP.Mpi.3ds20.min.js',
            'dirScriptConfig3DS' => $dirScriptConfig3DS,
            'totalCart' => $this->gateway->lknGetCartTotal(),
            'nonceCieloDebit' => wp_create_nonce( 'nonce_lkn_cielo_debit' ),
            'installmentLimit' => $installmentLimit,
            'installments' => $installments,
            'bec' => $this->gateway->get_option('establishment_code'),
            'client_ip' => $this->get_client_ip(),
            'user_guest' => ! is_user_logged_in(),
            'authentication_method' => is_user_logged_in() ? '02' : '01',
            'client' => array(
                'name' => $user->display_name,
                'email' => $user->user_email,
                'billing_phone' => get_user_meta($user->ID, 'billing_phone', true),
                'billing_address_1' => get_user_meta($user->ID, 'billing_address_1', true),
                'billing_address_2' => get_user_meta($user->ID, 'billing_address_2', true),
                'billing_city' => get_user_meta($user->ID, 'billing_city', true),
                'billing_state' => get_user_meta($user->ID, 'billing_state', true),
                'billing_postcode' => get_user_meta($user->ID, 'billing_postcode', true),
                'billing_country' => get_user_meta($user->ID, 'billing_country', true),
                'billing_document' => $billingDocument
            ),
            'translations' => array(
                'cardNumber' => __('Card Number', 'lkn-wc-gateway-cielo'),
                'cardExpiryDate' => __('Expiry Date', 'lkn-wc-gateway-cielo'),
                'securityCode' => __('Security Code', 'lkn-wc-gateway-cielo'),
                'installments' => __('Installments', 'lkn-wc-gateway-cielo'),
                'cardHolder' => __('Card Holder Name', 'lkn-wc-gateway-cielo'),
                'creditCard' => __('Credit card', 'lkn-wc-gateway-cielo'),
                'debitCard' => __('Debit card', 'lkn-wc-gateway-cielo'),
                'cardType' => __('Card type', 'lkn-wc-gateway-cielo'),
            )
        );
    }
}
