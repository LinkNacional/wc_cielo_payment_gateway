<?php

use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Payment_Gateway;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Payment_Gateway_Activator;
use Lkn\WcCieloPaymentGateway\Includes\Lkn_Wc_Cielo_Payment_Gateway_Deactivator;

// If this file is called directly, abort.
if (! defined('WPINC')) {
    die;
}

require_once __DIR__ . '/vendor/autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
if (! defined('WC_CIELO_PAYMENT_GATEWAY_VERSION')) {
    define('WC_CIELO_PAYMENT_GATEWAY_VERSION', '1.25.0');
}

if (! defined('WC_CIELO_PAYMENT_GATEWAY_FILE')) {
    define('WC_CIELO_PAYMENT_GATEWAY_FILE', __DIR__ . '/lkn-wc-cielo-payment-gateway.php');
}

if (! defined('WC_CIELO_PAYMENT_GATEWAY_DIR')) {
    define('WC_CIELO_PAYMENT_GATEWAY_DIR', plugin_dir_path(WC_CIELO_PAYMENT_GATEWAY_FILE));
}

if (! defined('WC_CIELO_PAYMENT_GATEWAY_BASENAME')) {
    define('WC_CIELO_PAYMENT_GATEWAY_BASENAME', plugin_basename(WC_CIELO_PAYMENT_GATEWAY_FILE));
}

if (! defined('WC_CIELO_PAYMENT_GATEWAY_DIR_URL')) {
    define('WC_CIELO_PAYMENT_GATEWAY_DIR_URL', plugin_dir_url(WC_CIELO_PAYMENT_GATEWAY_FILE));
}

if (! defined('WC_CIELO_PAYMENT_GATEWAY_FILE_BASENAME')) {
    define('WC_CIELO_PAYMENT_GATEWAY_FILE_BASENAME', plugin_basename(__DIR__ . '/lkn-wc-cielo-payment-gateway.php'));
}

if (! defined('WC_CIELO_PAYMENT_GATEWAY_BASE_FILE')) {
    define('WC_CIELO_PAYMENT_GATEWAY_BASE_FILE', __DIR__ . '/lkn-wc-cielo-payment-gateway.php');
}
if (! defined('LKN_WC_CIELO_VERSION')) {
    define('LKN_WC_CIELO_VERSION', WC_CIELO_PAYMENT_GATEWAY_VERSION);
}
if (! defined('LKN_WC_CIELO_TRANSLATION_PATH')) {
    define('LKN_WC_CIELO_TRANSLATION_PATH', WC_CIELO_PAYMENT_GATEWAY_DIR . 'languages/');
}
if (! defined('LKN_WC_GATEWAY_CIELO_BASENAME')) {
    define('LKN_WC_GATEWAY_CIELO_BASENAME', WC_CIELO_PAYMENT_GATEWAY_BASENAME);
}
if (! defined('LKN_WC_GATEWAY_CIELO_DIR')) {
    define('LKN_WC_GATEWAY_CIELO_DIR', WC_CIELO_PAYMENT_GATEWAY_DIR);
}
if (! defined('WC_CIELO_PAYMENT_GATEWAY_DIR_URL')) {
    define('WC_CIELO_PAYMENT_GATEWAY_DIR_URL', WC_CIELO_PAYMENT_GATEWAY_DIR_URL);
}
if (! defined('LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION')) {
    define('LKN_WC_GATEWAY_CIELO_MIN_WC_VERSION', '5.0.0');
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-lkn-wc-cielo-payment-gateway-activator.php
 */
function lkn_activate_wc_cielo_payment_gateway(): void
{
    Lkn_Wc_Cielo_Payment_Gateway_Activator::lkn_activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-lkn-wc-cielo-payment-gateway-deactivator.php
 */
function lkn_deactivate_wc_cielo_payment_gateway(): void
{
    Lkn_Wc_Cielo_Payment_Gateway_Deactivator::lkn_deactivate();
}

register_activation_hook(__FILE__, 'lkn_activate_wc_cielo_payment_gateway');
register_deactivation_hook(__FILE__, 'lkn_deactivate_wc_cielo_payment_gateway');

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function lkn_run_wc_cielo_payment_gateway(): void
{
    $plugin = new Lkn_Wc_Cielo_Payment_Gateway();
    $plugin->lkn_run();
}

lkn_run_wc_cielo_payment_gateway();
