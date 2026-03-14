<?php
/**
 * PHPUnit Bootstrap File
 * 
 * Inicializa o ambiente de testes usando Brain\Monkey para mockar WordPress/WooCommerce
 * 
 * @package Lkn\WCCieloPaymentGateway\Tests
 */

// Require Composer autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Initialize Brain\Monkey for WordPress function mocking
\Brain\Monkey\setUp();

// Define WordPress constants that are commonly used
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__) . '/');
}

if (!defined('WP_CONTENT_DIR')) {
    define('WP_CONTENT_DIR', ABSPATH . 'wp-content/');
}

if (!defined('WP_PLUGIN_DIR')) {
    define('WP_PLUGIN_DIR', WP_CONTENT_DIR . 'plugins/');
}

if (!defined('WP_DEBUG')) {
    define('WP_DEBUG', false);
}

// Define plugin constants
if (!defined('LKN_WC_CIELO_VERSION')) {
    define('LKN_WC_CIELO_VERSION', '1.29.1');
}

if (!defined('LKN_WC_CIELO_PLUGIN_DIR')) {
    define('LKN_WC_CIELO_PLUGIN_DIR', dirname(__DIR__) . '/');
}

if (!defined('LKN_WC_CIELO_PLUGIN_URL')) {
    define('LKN_WC_CIELO_PLUGIN_URL', 'http://example.org/wp-content/plugins/wc_cielo_payment_gateway/');
}

// Load helper functions for tests
require_once __DIR__ . '/TestHelpers.php';

// Clean up after each test
register_shutdown_function(function() {
    \Brain\Monkey\tearDown();
});
