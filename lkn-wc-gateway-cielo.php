<?php
/**
 * Plugin Name: Payment Gateway for Cielo API on WooCommerce
 * Plugin URI: https://www.linknacional.com.br/wordpress/woocommerce/cielo/
 * Description: Adds the Cielo API 3.0 Payments gateway to your WooCommerce website.
 *
 * Version: 1.5.0
 *
 * Author: Link Nacional
 * Author URI: https://linknacional.com.br
 *
 * Text Domain: lkn-wc-gateway-cielo
 * Domain Path: /languages/
 *
 * Requires at least: 5.7
 * Tested up to: 6.0
 *
 * Copyright: © 2022 Link Nacional.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined('ABSPATH')) {
    exit;
}

/**
 * WC Cielo Payment gateway plugin class.
 *
 * @class Lkn_WC_Cielo_Payment
 */
class Lkn_WC_Cielo_Payment {
    /**
     * Show plugin dependency notice.
     *
     * @since
     */
    public static function __lkn_wc_gateway_cielo_dependency_notice() {
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

        echo $message;
    }

    /**
     * Notice for No Core Activation.
     *
     * @since
     */
    public static function __lkn_wc_gateway_cielo_inactive_notice() {
        // Admin notice.
        $message = sprintf(
            '<div class="notice notice-error"><p><strong>%1$s</strong> %2$s <a href="%3$s" target="_blank">%4$s</a> %5$s.</p></div>',
            __('Activation Error:', 'lkn-wc-gateway-cielo'),
            __('You must have', 'lkn-wc-gateway-cielo'),
            'https://wordpress.org/plugins/woocommerce/',
            __('WooCommerce', 'lkn-wc-gateway-cielo'),
            __('plugin installed and activated for the Cielo API 3.0 Payments for WooCommerce add-on to activate', 'lkn-wc-gateway-cielo')
        );

        echo $message;
    }

    /**
     * Plugin bootstrapping.
     */
    public static function init() {
        // Load text domains
        add_action('init', array(__CLASS__, 'lkn_wc_gateway_cielo_load_textdomain'));

        // Cielo Payments gateway class.
        add_action('plugins_loaded', array(__CLASS__, 'includes'), 0);

        // New order email with installments.
        add_filter('woocommerce_email_order_meta_fields', array(__CLASS__, 'email_order_meta_fields'), 10, 3);

        // Make the Cielo Payments gateway available to WC.
        add_filter('woocommerce_payment_gateways', array(__CLASS__, 'add_gateway'));

        // Meta links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array(__CLASS__, 'lkn_wc_cielo_plugin_row_meta'), 10, 2);

        // Thank you page with installments.
        add_action('woocommerce_order_details_after_order_table', array(__CLASS__, 'order_details_after_order_table'), 10, 1);
    }

    /**
     * Check plugin environment.
     *
     * @return bool|null
     *
     * @since
     */
    public static function check_environment() {
        // Is not admin
        if ( ! is_admin() || ! current_user_can('activate_plugins')) {
            return null;
        }

        // Load plugin helper functions.
        if ( ! function_exists('deactivate_plugins') || ! function_exists('is_plugin_active')) {
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

                if ( ! $is_installed) {
                    add_action('admin_notices', array(__CLASS__, '__lkn_wc_gateway_cielo_dependency_notice'));
                }

                // Check for if give plugin activate or not.
                $is_wc_active = class_exists('WooCommerce');

                if ( ! $is_wc_active) {
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
     * Load the plugin text domain.
     */
    public static function lkn_wc_gateway_cielo_load_textdomain() {
        load_plugin_textdomain('lkn-wc-gateway-cielo', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    /**
     * Add the Cielo Payment gateway to the list of available gateways.
     *
     * @param array
     * @param mixed $gateways
     */
    public static function add_gateway($gateways) {
        $gateways[] = 'Lkn_WC_Gateway_Cielo_Credit';
        $gateways[] = 'Lkn_WC_Gateway_Cielo_Debit';

        return $gateways;
    }

    /**
     * Plugin includes.
     */
    public static function includes() {
        Lkn_WC_Cielo_Payment::setup_constants();
        Lkn_WC_Cielo_Payment::check_environment();

        // Make the Lkn_WC_Gateway_Cielo_Credit class available.
        if (class_exists('WC_Payment_Gateway')) {
            require_once 'includes/class-lkn-wc-gateway-cielo-credit.php';

            require_once 'includes/class-lkn-wc-gateway-cielo-debit.php';
        }
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_url() {
        return untrailingslashit(plugins_url('/', __FILE__));
    }

    /**
     * Plugin url.
     *
     * @return string
     */
    public static function plugin_abspath() {
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
    public static function lkn_wc_cielo_plugin_row_meta($plugin_meta, $plugin_file) {
        $new_meta_links['setting'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url('admin.php?page=wc-settings&tab=checkout'),
            __('Settings', 'lkn-wc-gateway-cielo')
        );

        return array_merge($plugin_meta, $new_meta_links);
    }

    /**
     * Show the Installments info in Thank you page.
     *
     * @param WC_Order $order
     */
    public static function order_details_after_order_table($order) {
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
    public static function email_order_meta_fields($fields, $sent_to_admin, $order) {
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
    private static function setup_constants() {
        // Defines addon version number for easy reference.
        if ( ! defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.5.0');
        }
        if ( ! defined('LKN_WC_CIELO_TRANSLATION_PATH')) {
            define('LKN_WC_CIELO_TRANSLATION_PATH', plugin_dir_path(__FILE__) . 'languages/');
        }
        if ( ! defined('LKN_WC_GATEWAY_CIELO_BASENAME')) {
            define('LKN_WC_GATEWAY_CIELO_BASENAME', plugin_basename(__FILE__));
        }
        if ( ! defined('LKN_WC_GATEWAY_CIELO_DIR')) {
            define('LKN_WC_GATEWAY_CIELO_DIR', plugin_dir_path(__FILE__));
        }
        if ( ! defined('LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION')) {
            define('LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION', '5.0.0');
        }
    }
}

Lkn_WC_Cielo_Payment::init();
