<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://linknacional.com.br
 * @since             1.0.0
 * @package           LknWcCieloPaymentGateway
 *
 * @wordpress-plugin
 * Plugin Name:       Payment Gateway for Cielo API on WooCommerce
 * Plugin URI:        https://www.linknacional.com.br/wordpress/woocommerce/cielo/
 * Description:       Adds the Cielo API 3.0 Payments gateway to your WooCommerce website.
 * Version:           1.25.0
 * Author:            Link Nacional
 * Author URI:        https://linknacional.com.br
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       lkn-wc-gateway-cielo
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 */

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

require_once 'lkn-wc-cielo-payment-gateway-activator.php';
