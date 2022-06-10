<?php
/**
 * Plugin Name: Cielo API 3.0 Pagamento para Woocommerce
 * Plugin URI: https://linknacional.com
 * Description: Adds the Cielo API 3.0 Payments gateway to your WooCommerce website.
 * Version: 1.0.0
 *
 * Author: Link Nacional
 * Author URI: https://linknacional.com
 *
 * Text Domain: lkn-wc-gateway-cielo
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 4.2
 * Tested up to: 6.0
 *
 * Copyright: © 2022 Link Nacional.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC Cielo Payment gateway plugin class.
 *
 * @class Lkn_WC_Cielo_Payment
 */
class Lkn_WC_Cielo_Payment {
    /**
     * Plugin bootstrapping.
     */
    public static function init() {

        // Cielo Payments gateway class.
        add_action('plugins_loaded', [__CLASS__, 'includes'], 0);

        // Make the Cielo Payments gateway available to WC.
        add_filter('woocommerce_payment_gateways', [__CLASS__, 'add_gateway']);

        // Meta links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [__CLASS__, 'lkn_wc_cielo_plugin_row_meta'], 10, 2);
    }

    /**
     * Add the Cielo Payment gateway to the list of available gateways.
     *
     * @param array
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

        // Make the Lkn_WC_Gateway_Cielo_Credit class available.
        if (class_exists('WC_Payment_Gateway')) {
            require_once 'includes/class-lkn-wc-gateway-cielo-credit.php';
            require_once 'includes/class-lkn-wc-gateway-cielo-debit.php';
        }
    }

    /**
     * Setup plugin constants for ease of use
     *
     * @return void
     */
    private static function setup_constants() {
        // Defines addon version number for easy reference.
        if (!defined('LKN_WC_CIELO_VERSION')) {
            define('LKN_WC_CIELO_VERSION', '1.0.0');
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
     * @param array $plugin_meta An array of the plugin's metadata.
     * @param string $plugin_file Path to the plugin file, relative to the plugins directory.
     *
     * @return array
    */
    public static function lkn_wc_cielo_plugin_row_meta($plugin_meta, $plugin_file) {
        $new_meta_links['setting'] = sprintf(
            '<a href="%1$s">%2$s</a>',
            admin_url('admin.php?page=wc-settings&tab=checkout'),
            __('Settings', 'give-pagseguro')
        );

        return array_merge($plugin_meta, $new_meta_links);
    }
}

Lkn_WC_Cielo_Payment::init();
