<?php

namespace Lkn\WcCieloPaymentGateway\Integrations;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Lkn_Wc_Cielo_Debit_Blocks extends AbstractPaymentMethodType
{
    private $gateway;
    protected $name = 'lkn_cielo_debit';

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_lkn_cielo_debit_settings', array());
        $lknWcGateWayCieloDebit = new Lkn_Wc_Gateway_Cielo_Debit();
        $lknWcGateWayCieloDebit->lkn_initialize_payment_gateway_scripts();
        $this->gateway = $lknWcGateWayCieloDebit;
    }

    public function is_active()
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles()
    {
        $custom_layout = isset($this->settings['checkout_layout']) ? $this->settings['checkout_layout'] : 'default';
        $pro_plugin_active = function_exists('is_plugin_active') && is_plugin_active('lkn-cielo-api-pro/lkn-cielo-api-pro.php');
        $pro_license_active = get_option('lkn_cielo_pro_license_boolean', false);
        $pro_plugin_version_valid = defined('LKN_CIELO_API_PRO_VERSION') && version_compare(LKN_CIELO_API_PRO_VERSION, '1.20.2', '>=');

        if ($custom_layout === 'default' && $pro_plugin_active && $pro_license_active) {
            $custom_layout = "yes";
        }

        $is_pro_plugin_valid = $pro_plugin_active && $pro_license_active && $custom_layout === 'yes' && $pro_plugin_version_valid;

        wp_register_script(
            'lkn_cielo_debit-blocks-integration',
            LKN_WC_GATEWAY_CIELO_URL . 'public/js/lknCieloDebitCompiled.js',
            array(
                'wc-blocks-registry',
                'wc-settings',
                'wp-element',
                'wp-html-entities',
                'wp-i18n',
                'wp-api'
            ),
            '1.0.0',
            true
        );

        wp_localize_script('lkn_cielo_debit-blocks-integration', 'lknCieloDebitConfig', array(
            'isProPluginValid' => $is_pro_plugin_valid
        ));

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('lkn_cielo_debit-blocks-integration');
        }

        if ($is_pro_plugin_valid) {
            wp_enqueue_script('lkn-wc-gateway-debit-checkout-layout', LKN_WC_GATEWAY_CIELO_URL . 'public/js/lkn-wc-gateway-checkout-layout.js', array(), LKN_WC_CIELO_VERSION, false);
            wp_localize_script('lkn-wc-gateway-debit-checkout-layout', 'lknCieloCardIcons', array(
                'visa'       => LKN_WC_GATEWAY_CIELO_URL . 'public/images/visa-icon.svg',
                'mastercard' => LKN_WC_GATEWAY_CIELO_URL . 'public/images/mastercard-icon.svg',
                'amex'       => LKN_WC_GATEWAY_CIELO_URL . 'public/images/amex-icon.svg',
                'elo'        => LKN_WC_GATEWAY_CIELO_URL . 'public/images/elo-icon.svg',
                'other_card' => LKN_WC_GATEWAY_CIELO_URL . 'public/images/other-card.svg',
                'other_card_alt'    => __('other card', 'lkn-wc-gateway-cielo')
            ));
            wp_localize_script('lkn-wc-gateway-debit-checkout-layout', 'lknCieloInputIcons', array(
                'calendar' => LKN_WC_GATEWAY_CIELO_URL . 'public/images/calendar.svg',
                'key'      => LKN_WC_GATEWAY_CIELO_URL . 'public/images/key.svg',
                'lock'     => LKN_WC_GATEWAY_CIELO_URL . 'public/images/lock.svg'
            ));
            wp_enqueue_style('lkn-wc-gateway-debit-checkout-layout', LKN_WC_GATEWAY_CIELO_URL . 'admin/css/lkn-wc-gateway-debit-card-checkout-layout.css', array(), LKN_WC_CIELO_VERSION, 'all');
        }

        do_action('lkn_wc_cielo_remove_cardholder_name_3ds', $this->gateway);
        return array('lkn_cielo_debit-blocks-integration');
    }

    private function get_client_ip()
    {
        $ip_address = '';
        $client_ip = isset($_SERVER['HTTP_CLIENT_IP']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP'])) : '';
        $forwarded_ip = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])) : '';
        $real_ip = isset($_SERVER['HTTP_X_REAL_IP']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_REAL_IP'])) : '';
        $remote_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';

        if (! empty($client_ip)) {
            $ip_address = $client_ip;
        } elseif (! empty($forwarded_ip)) {
            // Se estiver atrás de um proxy, `HTTP_X_FORWARDED_FOR` pode conter uma lista de IPs.
            $ip_list = explode(',', $forwarded_ip);
            $ip_address = trim($ip_list[0]); // Pega o primeiro IP da lista
        } elseif (! empty($real_ip)) {
            $ip_address = $real_ip;
        } else {
            $ip_address = $remote_ip;
        }

        return $ip_address;
    }

    public function get_payment_method_data()
    {
        if ($this->gateway->get_option('env') == 'sandbox') {
            $dirScriptConfig3DS = LKN_WC_GATEWAY_CIELO_URL . 'public/js/lkn-dc-script-sdb.js';
        } else {
            $dirScriptConfig3DS = LKN_WC_GATEWAY_CIELO_URL . 'public/js/lkn-dc-script-prd.js';
        }

        $installmentMinAmount = apply_filters('lkn_wc_cielo_set_installment_min_amount', '5,00', $this->gateway);
        $installmentMinAmount = preg_replace('/,/', '.', $installmentMinAmount);

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
            'accessToken' => isset($acessToken['access_token']) ? $acessToken['access_token'] : '',
            'accessTokenExpiration' => isset($acessToken['expires_in']) ? $acessToken['expires_in'] : '',
            'url' => get_page_link(),
            'orderNumber' => uniqid(),
            'activeInstallment' => $this->gateway->get_option('installment_payment'),
            'activeDiscount' => $this->gateway->get_option('installment_discount', 'no'),
            'dirScript3DS' => LKN_WC_GATEWAY_CIELO_URL . 'public/js/BP.Mpi.3ds20.min.js',
            'dirScriptConfig3DS' => $dirScriptConfig3DS,
            'totalCart' => $this->gateway->lknGetCartTotal(),
            'nonceCieloDebit' => wp_create_nonce('nonce_lkn_cielo_debit'),
            'installmentLimit' => $installmentLimit,
            'installments' => $installments,
            'installmentMinAmount' => $installmentMinAmount,
            'bec' => $this->gateway->get_option('establishment_code'),
            'client_ip' => $this->get_client_ip(),
            'user_guest' => ! is_user_logged_in(),
            'authentication_method' => is_user_logged_in() ? '02' : '01',
            'showCard' => $this->gateway->get_option('show_card_animation'),
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
