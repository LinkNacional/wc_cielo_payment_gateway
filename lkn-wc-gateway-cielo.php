<?php

/**
 * Plugin Name: Payment Gateway for Cielo API on WooCommerce
 * Plugin URI: https://www.linknacional.com.br/wordpress/woocommerce/cielo/
 * Description: Adds the Cielo API 3.0 Payments gateway to your WooCommerce website.
 * Version: 1.20.0
 * Author: Link Nacional
 * Author URI: https://linknacional.com.br
 * Text Domain: lkn-wc-gateway-cielo
 * Domain Path: /languages/
 * Requires Plugins: woocommerce
 * Copyright: © 2025 Link Nacional.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloCredit;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloDebit;
use Lkn\WCCieloPaymentGateway\Includes\LknWCGatewayCieloEndpoint;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloHelper;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloCreditBlocks;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloDebitBlocks;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix;
use Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPixBlocks;

// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * WC Cielo Payment gateway plugin class.
 *
 * @class Lkn_WC_Cielo_Payment
 */
final class LknWCCieloPayment
{
    /**
     * Show plugin dependency notice.
     *
     * @since
     */
    public static function __lkn_wc_gateway_cielo_dependency_notice(): void
    {
        // Admin notice.
        $message = sprintf(
            '<strong>%1$s</strong> %2$s <a href="%3$s" target="_blank">%4$s</a>  %5$s %6$s+ %7$s.',
            __('Activation Error:', 'lkn-wc-gateway-cielo'),
            __('You must have', 'lkn-wc-gateway-cielo'),
            'https://wordpress.org/plugins/woocommerce/',
            __('WooCommerce', 'lkn-wc-gateway-cielo'),
            __('version', 'lkn-wc-gateway-cielo'),
            LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION,
            __('for the Cielo API 3.0 Payments for WooCommerce add-on to activate', 'lkn-wc-gateway-cielo')
        );

        echo wp_kses_post($message);
    }

    /**
     * Notice for No Core Activation.
     *
     * @since
     */
    public static function __lkn_wc_gateway_cielo_inactive_notice(): void
    {
        // Admin notice.
        $message = sprintf(
            '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s" target="_blank">%4$s</a> %5$s.</p></div>',
            __('Activation Error:', 'lkn-wc-gateway-cielo'),
            __('You must have', 'lkn-wc-gateway-cielo'),
            'https://wordpress.org/plugins/woocommerce/',
            __('WooCommerce', 'lkn-wc-gateway-cielo'),
            __('plugin installed and activated for the Cielo API 3.0 Payments for WooCommerce add-on to activate', 'lkn-wc-gateway-cielo')
        );

        echo wp_kses_post($message);
    }

    /**
     * Plugin bootstrapping.
     */
    public static function init(): void
    {
        add_action('beforewoocommerce_init', array(__CLASS__, 'wcEditorBlocksActive'));
        add_action('woocommerce_blocks_payment_method_type_registration', array(__CLASS__, 'wcEditorBlocksAddPaymentMethod'));

        // Cielo Payments gateway class.
        add_action('plugins_loaded', array(__CLASS__, 'includes'), 0);

        // New order email with installments.
        add_filter('woocommerce_email_order_meta_fields', array(__CLASS__, 'email_order_meta_fields'), 10, 3);

        // Make the Cielo Payments gateway available to WC.
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));

        // Meta links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'lkn_wc_cielo_plugin_row_meta'), 10, 2);

        // Meta links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'lkn_wc_cielo_plugin_row_meta_pro'), 10, 2);

        // Thank you page with installments.
        add_action('woocommerce_order_details_after_order_table', array(__CLASS__, 'order_details_after_order_table'), 10, 1);

        add_action('rest_api_init', array(new LknWCGatewayCieloEndpoint(), 'registerOrderCaptureEndPoint'));

        add_action('add_meta_boxes', array(new LknWcCieloHelper(), 'showOrderLogs'));

        add_action('admin_notices', array(__CLASS__, 'lkn_admin_notice'));

        add_action('woocommerce_order_details_after_order_table', array('Lkn\WCCieloPaymentGateway\Includes\LknWcCieloPix', 'showPix'));

        $page    = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
        $tab     = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : '';
        $section = isset($_GET['section']) ? sanitize_text_field(wp_unslash($_GET['section'])) : '';

        $sections = [
            'lkn_cielo_credit',
            'lkn_cielo_debit',
            'lkn_wc_cielo_pix',
            'lkn_cielo_pix',
            'lkn_cielo_boleto'
        ];

        if (
            $page === 'wc-settings' &&
            $tab === 'checkout' &&
            in_array($section, $sections, true)
        ) {
            add_action('woocommerce_init', function () {
                $versions = 'Plugin Cielo v' . LKN_WC_CIELO_VERSION . ' | WooCommerce v' . WC()->version;

                wp_enqueue_script('lknCieloForWoocommerceCard', plugin_dir_url(__FILE__) . 'resources/js/admin/lkn-woocommerce-admin-card.js', array('jquery'), LKN_WC_CIELO_VERSION, false);
                wp_enqueue_style('lknCieloForWoocommerceCard', plugin_dir_url(__FILE__) . 'resources/css/frontend/lkn-woocommerce-admin-card.css', array(), LKN_WC_CIELO_VERSION, 'all');
                wp_enqueue_script('lknCieloForWoocommerceProSettings', plugin_dir_url(__FILE__) . 'resources/js/admin/lkn-settings-pro-fields.js', array(), LKN_WC_CIELO_VERSION, false);

                wp_localize_script(
                    'lknCieloForWoocommerceProSettings',
                    'lknCieloProSettingsVars',
                    array(
                        'proOnly' => __('Available only in PRO', 'lkn-wc-gateway-cielo'),
                    )
                );

                wc_get_template(
                    'adminSettingsCard.php',
                    array(
                        'backgrounds' => array(
                            'right' => plugin_dir_url(__FILE__) . 'resources/img/backgroundCardRight.svg',
                            'left' => plugin_dir_url(__FILE__) . 'resources/img/backgroundCardLeft.svg'
                        ),
                        'logo' => plugin_dir_url(__FILE__) . 'resources/img/linkNacionalLogo.webp',
                        'stars' => plugin_dir_url(__FILE__) . 'resources/img/stars.svg',
                        'versions' => $versions

                    ),
                    'woocommerce/adminSettingsCard/',
                    plugin_dir_path(__FILE__) . '/includes/templates/'
                );
            });
        }
    }

    public static function lkn_admin_notice(): void
    {
        if (! file_exists(WP_PLUGIN_DIR . '/fraud-detection-for-woocommerce/fraud-detection-for-woocommerce.php') && ! is_plugin_active('integration-rede-for-woocommerce/integration-rede-for-woocommerce.php')) {
            require LKN_WC_GATEWAY_CIELO_DIR . 'includes/views/notices/LknWcCieloDownloadNotice.php';
        }
    }

    public static function wcEditorBlocksActive(): void
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                __FILE__,
                true
            );
        }
    }

    public static function wcEditorBlocksAddPaymentMethod(Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry): void
    {
        if (! class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        $payment_method_registry->register(new LknWcCieloCreditBlocks());
        $payment_method_registry->register(new LknWcCieloDebitBlocks());
        $payment_method_registry->register(new LknWcCieloPixBlocks());
    }

    /**
     * Check plugin environment.
     *
     * @return bool|null
     *
     * @since
     */
    public static function check_environment()
    {
        // Is not admin
        if (! is_admin() || ! current_user_can('activate_plugins')) {
            return null;
        }

        // Load plugin helper functions.
        if (! function_exists('deactivate_plugins') || ! function_exists('is_plugin_active')) {
            require_once ABSPATH . '/wp-admin/includes/plugin.php';
        }

        // Flag to check whether deactivate plugin or not.
        $is_deactivate_plugin = false;

        // Verify dependency cases.
        switch (true) {
            case doing_action('activate_' . LKN_WC_GATEWAY_CIELO_BASENAME):
            case doing_action('plugins_loaded'):
                // Check to see if Woo is activated, if it isn't deactivate and show a banner.
                $is_installed = false;

                if (function_exists('get_plugins')) {
                    $all_plugins = get_plugins();
                    $is_installed = ! empty($all_plugins['woocommerce/woocommerce.php']);
                }

                if (! $is_installed) {
                    add_action('admin_notices', array(__CLASS__, '__lkn_wc_gateway_cielo_dependency_notice'));
                }

                // Check for if give plugin activate or not.
                $is_wc_active = class_exists('WooCommerce');

                if (! $is_wc_active) {
                    add_action('admin_notices', array(__CLASS__, '__lkn_wc_gateway_cielo_inactive_notice'));

                    $is_deactivate_plugin = true;
                }

                break;
        }

        // Don't let this plugin activate.
        if ($is_deactivate_plugin) {
            // Deactivate plugin.
            deactivate_plugins(LKN_WC_GATEWAY_CIELO_BASENAME);

            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }

            return false;
        }

        return true;
    }

    /**
     * Add the Cielo Payment gateway to the list of available gateways.
     *
     * @param array
     * @param mixed $gateways
     */
    public static function add_gateway($gateways)
    {
        $gateways[] = new LknWCGatewayCieloCredit();
        $gateways[] = new LknWCGatewayCieloDebit();
        $gateways[] = new LknWcCieloPix();

        return $gateways;
    }

    /**
     * Plugin includes.
     */
    public static function includes(): void
    {
        LknWCCieloPayment::setup_constants();
        LknWCCieloPayment::check_environment();
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url()
    {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_abspath()
    {
        return trailingslashit(plugin_dir_path(__FILE__));
    }

    /**
     * Plugin row meta links.
     *
     * @since
     *
     * @param array  $plugin_meta an array of the plugin's metadata
     * @param string $plugin_file path to the plugin file, relative to the plugins directory
     *
     * @return array
     */
    public static function lkn_wc_cielo_plugin_row_meta($plugin_meta, $plugin_file)
    {
        $new_meta_links['setting'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url('admin.php?page=wc-settings&tab=checkout'),
            __('Settings', 'lkn-wc-gateway-cielo')
        );

        return array_merge($plugin_meta, $new_meta_links);
    }

    public static function lkn_wc_cielo_plugin_row_meta_pro($plugin_meta, $plugin_file)
    {
        // Defina o URL e o texto do link
        $url = 'https://www.linknacional.com.br/wordpress/woocommerce/cielo/';
        $link_text = sprintf(
            '<span style="color: red; font-weight: bold;">%s</span>',
            __('Be pro', 'lkn-wc-gateway-cielo')
        );

        // Crie o novo link de meta
        $new_meta_link = sprintf('<a href="%1$s">%2$s</a>', $url, $link_text);

        // Adicione o novo link ao array de metadados do plugin
        $plugin_meta[] = $new_meta_link;

        return $plugin_meta;
    }

    /**
     * Show the Installments info in Thank you page.
     *
     * @param WC_Order $order
     */
    public static function order_details_after_order_table($order): void
    {
        $installment = $order->get_meta('installments');

        if ($installment && $installment > 1) {
            echo '<div id="lkn-wc-installment-notice"><p><strong>' . esc_html__('Installment', 'lkn-wc-gateway-cielo') . ':</strong> ' . esc_html($installment) . 'x</p></div>';
        }
    }

    /**
     * Show the Installments info in the new order notification email.
     *
     * @param array    $fields
     * @param bool     $sent_to_admin
     * @param WC_Order $order
     */
    public static function email_order_meta_fields($fields, $sent_to_admin, $order)
    {
        $installment = $order->get_meta('installments');
        if ($installment && $installment > 1) {
            $fields['installment'] = array(
                'label' => __('Installment', 'lkn-wc-gateway-cielo'),
                'value' => $installment,
            );
        }

        return $fields;
    }

    /**
     * Setup plugin constants for ease of use.
     */
    private static function setup_constants(): void
    {
        // Defines addon version number for easy reference.
        if (! defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.20.0');
        }
        if (! defined('LKN_WC_CIELO_TRANSLATION_PATH')) {
            define('LKN_WC_CIELO_TRANSLATION_PATH', plugin_dir_path(__FILE__) . 'languages/');
        }
        if (! defined('LKN_WC_GATEWAY_CIELO_BASENAME')) {
            define('LKN_WC_GATEWAY_CIELO_BASENAME', plugin_basename(__FILE__));
        }
        if (! defined('LKN_WC_GATEWAY_CIELO_DIR')) {
            define('LKN_WC_GATEWAY_CIELO_DIR', plugin_dir_path(__FILE__));
        }
        if (! defined('LKN_WC_GATEWAY_CIELO_URL')) {
            define('LKN_WC_GATEWAY_CIELO_URL', plugin_dir_url(__FILE__));
        }
        if (! defined('LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION')) {
            define('LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION', '5.0.0');
        }
    }
}

LknWCCieloPayment::init();
